# DiaPredict — Laporan Analisis Arsitektur & Rencana Deployment Produksi

> Dianalisa sebagai Senior Full Stack / DevOps / Cloud & Software Architect.
> Target deployment: **Docker → GitHub → Railway**.

---

## 1. Ringkasan Project

**DiaPredict** adalah aplikasi web prediksi risiko diabetes (project skripsi). Aplikasi
Laravel yang memanggil model Machine Learning Python (Random Forest scikit-learn) untuk
menghitung probabilitas diabetes dari 5 parameter medis.

| Aspek | Teknologi |
|-------|-----------|
| Framework backend | **Laravel 13**, PHP **8.3** |
| Frontend | Blade + **Tailwind CSS 4** + **Vite 8** (server-rendered, bukan SPA) |
| Database | **MySQL 8** (sessions, cache, jobs, users, analysis_results) |
| Model AI | **Python 3.13** + scikit-learn RandomForest (`rf_model.pkl` **78 MB** + `scaler.pkl`) |
| Integrasi ML | PHP `exec()` memanggil `model/predict.py` per-request (CLI) |
| Auth | Session-based custom (`AuthController`), bcrypt rounds 12 |
| Queue / Cache / Session | Driver **database** (belum pakai Redis) |
| Storage | Filesystem lokal (`local` disk) |

### Alur aplikasi (end-to-end)
1. User register/login → session disimpan di tabel `sessions` (MySQL).
2. User mengisi form analisis (`/analysis`): age, hypertension, weight, height, BMI, HbA1c, blood glucose.
3. `AnalysisController@process` memvalidasi input, lalu menjalankan:
   `exec("python predict.py <age> <hypertension> <bmi> <hba1c> <glucose>")`.
4. `predict.py` meng-*unpickle* model + scaler (78 MB tiap panggilan), scaling, prediksi,
   menerapkan threshold 0.4965, dan mengembalikan JSON `{prediction, probability}`.
5. Hasil disimpan ke tabel `analysis_results` (dengan `gender/heart_disease/smoking_history`
   di-hardcode = 0 karena form hanya mengumpulkan 5 fitur).
6. User melihat riwayat (`/analysis/history`) dan detail (`/analysis/{id}`).
7. Health check tersedia di `/up` (bawaan Laravel — dipakai Railway healthcheck).

---

## 2. Masalah Produksi yang Teridentifikasi

### 🔴 KRITIS (menyebabkan gagal jalan / celah keamanan)
| # | Masalah | Dampak | Solusi |
|---|---------|--------|--------|
| K1 | **Python path hardcoded Windows** (`C:\Users\lapanui\...python.exe`) | `exec()` gagal total di server Linux | ✅ Sudah diperbaiki: fallback `python3` + `PYTHON_PATH` diset di Dockerfile |
| K2 | **Tidak ada `requirements.txt`** untuk Python | Model tak bisa jalan di server (sklearn/numpy tak terpasang) | ✅ Dibuat `model/requirements.txt` |
| K3 | **Versi scikit-learn saat pickle tidak dipin** | Unpickle bisa gagal / hasil salah bila versi beda | Pin versi **persis** dari mesin training (`pip freeze`) — lihat catatan di requirements.txt |
| K4 | `APP_DEBUG=true`, `APP_ENV=local` di `.env` | Bocornya stack trace & konfigurasi ke publik | ✅ `.env.production.example` set `APP_DEBUG=false`, `APP_ENV=production` |
| K5 | **DB pakai `root` tanpa password**, host `127.0.0.1` | Tak berlaku di Railway; kredensial harus dari plugin | ✅ Map ke variabel `${MYSQL*}` Railway |
| K6 | **`APP_KEY` asli ada di `.env`** (walau `.env` di-gitignore) | Rotasi wajib untuk produksi | Generate baru, simpan di Railway Variables |

### 🟠 PENTING (memengaruhi stabilitas / performa)
| # | Masalah | Catatan |
|---|---------|---------|
| P1 | **Model 78 MB di-load ulang tiap prediksi** (proses Python baru per request) | Latency ~2–5 dtk/prediksi, boros CPU. OK untuk demo skripsi; untuk skala → jadikan microservice (lihat §4) |
| P2 | **Prediksi sinkron (blocking)** lewat `exec()` | Request menunggu Python selesai; bisa timeout saat beban tinggi. Alternatif: queue worker |
| P3 | **`rf_model.pkl` 78 MB di Git biasa** | Repo membengkak; push lambat. Rekomendasi: **Git LFS** atau unduh model saat build |
| P4 | Session/Cache/Queue di **database** | Cukup untuk skala kecil; Redis lebih cepat & mengurangi beban DB |
| P5 | Tidak ada worker/scheduler yang berjalan di produksi | Belum kritikal (belum ada job), siapkan bila prediksi dipindah ke queue |

### 🟡 MINOR / kebersihan
- Data dummy `gender/heart_disease/smoking_history = 0` disimpan padahal kolom NOT NULL — konsisten, tapi form & skema tidak sinkron.
- Artefak riset (`*.ipynb` ~4 MB, `code_html/`, script `*.js/.cjs`) ikut di repo → dikecualikan via `.dockerignore`.
- CORS **tidak relevan** (aplikasi server-rendered Blade, tanpa API terpisah / frontend domain lain).
- Reverse proxy & SSL ditangani otomatis oleh Railway (tidak perlu Nginx manual).

---

## 3. Evaluasi Arsitektur: Docker → GitHub → Railway

**Verdict: alur sudah TEPAT** untuk skala project ini. Justifikasi:
- Railway mendukung deploy dari **Dockerfile** langsung dari GitHub (auto-deploy on push).
- Docker menyelesaikan masalah utama: **PHP + Python dalam satu lingkungan** yang reproducible.
- SSL, domain, reverse proxy, dan `$PORT` ditangani Railway — tidak perlu konfigurasi manual.

### Yang perlu penyesuaian
| Komponen | Rekomendasi | Alasan |
|----------|-------------|--------|
| **Database** | **Jangan** jalankan MySQL di dalam container app. Pakai **Railway MySQL plugin** (service terpisah) | Data persisten & survive redeploy; container app bersifat ephemeral (data hilang tiap deploy) |
| **Model AI** | Untuk sekarang: **satu container** (PHP+Python) — paling cepat & minim risiko. Untuk skala: pisahkan jadi **FastAPI microservice** | Monolit = 1 image besar (~1.5 GB) & model reload/req. Microservice = model di-load sekali di memori, bisa scale independen |
| **Redis** | Opsional. Tambah **Railway Redis** bila trafik naik → pindahkan session/cache/queue | Mengurangi beban MySQL, lebih cepat |
| **Object storage** | Belum perlu (tidak ada upload file user). Bila nanti ada → S3-compatible (Cloudflare R2 / AWS S3) | Filesystem container ephemeral |

**Rekomendasi final:** mulai dengan **arsitektur monolit 2-service** (App container + MySQL plugin).
Migrasi ke microservice ML + Redis hanya jika/ketika ada kebutuhan skala nyata.

---

## 4. Alternatif Arsitektur Model AI

**Opsi A — Monolit (DIPILIH untuk awal).** PHP `exec()` → `python3 predict.py` dalam container yang sama.
- ➕ Perubahan kode minimal, cepat online, 1 service.
- ➖ Model reload 78 MB/request (~2–5 dtk), image besar, tidak scale mandiri.

**Opsi B — Microservice ML (jalur skala).** Service FastAPI/Flask Python terpisah, Laravel memanggil via HTTP.
- ➕ Model di-load sekali di memori (prediksi <100 ms), scale independen, image PHP ramping.
- ➖ Perlu ubah `AnalysisController` (ganti `exec()` → HTTP client), 2 service, ada jaringan internal.

> Untuk skripsi/demo dengan trafik rendah → **Opsi A**. Bila jadi produk nyata → **Opsi B**.

### Opsi B sudah disiapkan (folder `ml-service/`)
Aplikasi kini mendukung **kedua mode** lewat env `ML_MODE`:
- `ML_MODE=exec` (default) → monolit, tak butuh service tambahan.
- `ML_MODE=http` → panggil FastAPI (`ml-service/`) via `ML_SERVICE_URL`.

**Deploy microservice di Railway:**
1. Add service baru dari repo yang sama → Settings: **Root Directory `/`**, **Dockerfile Path `ml-service/Dockerfile`**.
2. Railway generate host internal, mis. `ml-service.railway.internal`.
3. Di service **App**, set `ML_MODE=http` dan `ML_SERVICE_URL=http://ml-service.railway.internal:8000`
   (private networking — tidak kena biaya egress, tak perlu domain publik untuk ML service).
4. Health check ML service: `GET /health`.

| File microservice | Fungsi |
|-------------------|--------|
| `ml-service/main.py` | FastAPI: load model sekali saat startup, endpoint `/predict` & `/health` |
| `ml-service/requirements.txt` | fastapi, uvicorn, scikit-learn, numpy, scipy |
| `ml-service/Dockerfile` | Python 3.13-slim, salin `model/*.pkl`, jalankan uvicorn di `$PORT` |

---

## 5. Deliverable yang Sudah Dibuat

| File | Fungsi |
|------|--------|
| `Dockerfile` | Multi-stage: build Vite → composer vendor → runtime PHP 8.3 Apache + Python venv |
| `docker/vhost.conf` | Virtual host Apache, docroot `/public`, log ke stdout/stderr |
| `docker/entrypoint.sh` | Set `$PORT` Railway, cache config/route/view, `storage:link`, `migrate --force` |
| `.dockerignore` | Kecualikan vendor, node_modules, notebook, .env, artefak riset |
| `model/requirements.txt` | Dependency Python untuk inference (scikit-learn, numpy, scipy) |
| `.env.production.example` | Template env produksi (map ke variabel Railway MySQL) |
| `railway.json` | Paksa builder Dockerfile + healthcheck `/up` + restart policy |
| `.github/workflows/ci.yml` | CI: build assets, test SQLite, verifikasi image Docker |
| `AnalysisController.php` | ✅ Fallback Python diubah `python3` (aman Linux) |

---

## 6. Deployment Plan (Langkah demi Langkah)

### Fase 0 — Persiapan lokal
1. **Cocokkan versi scikit-learn**: di mesin yang melatih model jalankan `pip freeze | grep -i scikit`, samakan di `model/requirements.txt` (kritis — K3).
2. Uji build image lokal: `docker build -t diapredict .` lalu `docker run -p 8080:8080 --env-file .env diapredict`.
3. Pastikan `rf_model.pkl` & `scaler.pkl` ikut ter-commit (atau siapkan Git LFS bila repo terlalu berat).

### Fase 1 — GitHub
4. `git init` (repo belum ada git) → commit → push ke GitHub repository baru (private).
5. Pastikan `.env` **tidak** ikut (sudah di `.gitignore`).
6. Pertimbangkan **Git LFS** untuk `*.pkl`:
   `git lfs install && git lfs track "*.pkl" && git add .gitattributes`.

### Fase 2 — Railway
7. New Project → **Deploy from GitHub repo** → pilih repo (Railway deteksi `railway.json` → builder Dockerfile).
8. Add plugin → **MySQL**. Railway otomatis expose `MYSQLHOST/PORT/DATABASE/USER/PASSWORD`.
9. Di service App → **Variables**, isi dari `.env.production.example`:
   - `APP_KEY` (dari `php artisan key:generate --show`), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`.
   - `DB_*` map ke `${MYSQLHOST}` dst.
   - `PYTHON_PATH=/opt/venv/bin/python` (opsional, sudah default di image).
10. Deploy. Entrypoint akan cache config & jalankan `migrate --force` otomatis.

### Fase 3 — Domain, SSL, Monitoring
11. Settings → Networking → **Generate Domain** (`*.up.railway.app`) atau tambah **Custom Domain** + CNAME. SSL otomatis (Let's Encrypt).
12. Set `APP_URL` sesuai domain final, redeploy.
13. **Logging**: sudah ke stdout/stderr → tampil di tab Logs Railway.
14. **Monitoring**: pantau tab Metrics (CPU/RAM/Network). Healthcheck `/up` sudah aktif.

### Fase 4 — Backup & Rollback
15. **Backup DB**: aktifkan backup MySQL Railway, atau `mysqldump` terjadwal.
16. **Rollback**: Railway menyimpan riwayat deployment → klik **Redeploy** versi sebelumnya bila error.

---

## 7. Checklist Verifikasi Pasca-Deploy
- [ ] `/up` mengembalikan 200 (healthcheck hijau).
- [ ] Halaman home, login, register tampil (asset Vite ter-load).
- [ ] Register user baru berhasil (menulis ke MySQL).
- [ ] Submit form analisis → hasil prediksi muncul (**bukti Python + model bekerja**).
- [ ] Riwayat & detail analisis tampil benar.
- [ ] Tidak ada stack trace bocor (`APP_DEBUG=false`).
- [ ] Log Railway bersih dari error fatal.
- [ ] Data tetap ada setelah redeploy (DB terpisah, bukan di container).

---

## 8. Checklist Prioritas (urut kepentingan)

| Prioritas | Tugas | Kesulitan | Risiko bila diabaikan | Rekomendasi |
|-----------|-------|-----------|-----------------------|-------------|
| 1️⃣ | Cocokkan versi scikit-learn di requirements.txt | 🟢 Mudah | 🔴 Model gagal/hasil salah | Wajib — verifikasi via `pip freeze` |
| 2️⃣ | Uji `docker build` + run lokal sampai prediksi jalan | 🟡 Sedang | 🔴 Deploy gagal | Wajib sebelum push |
| 3️⃣ | Push ke GitHub (+ Git LFS untuk .pkl) | 🟢 Mudah | 🟠 Repo berat | LFS direkomendasikan |
| 4️⃣ | Railway: MySQL plugin + Variables produksi | 🟢 Mudah | 🔴 App tak konek DB | Wajib |
| 5️⃣ | Set APP_KEY/DEBUG/ENV produksi | 🟢 Mudah | 🔴 Keamanan | Wajib |
| 6️⃣ | Domain + SSL + set APP_URL | 🟢 Mudah | 🟡 UX/redirect | Direkomendasikan |
| 7️⃣ | Backup DB + uji rollback | 🟡 Sedang | 🟠 Kehilangan data | Direkomendasikan |
| 8️⃣ | (Skala) Redis untuk session/cache/queue | 🟡 Sedang | 🟢 Performa | Opsional |
| 9️⃣ | (Skala) Pisahkan model jadi FastAPI microservice | 🔴 Sulit | 🟢 Skalabilitas | Hanya bila trafik tinggi |

---

## 9. Catatan Biaya (Railway)
- App service + MySQL plugin muat di paket Hobby/Trial untuk demo.
- Image ~1.2–1.5 GB (karena Python + sklearn). Perhatikan kuota build & memori runtime
  (RandomForest 78 MB butuh RAM cukup saat unpickle — sediakan ≥512 MB, idealnya 1 GB).
