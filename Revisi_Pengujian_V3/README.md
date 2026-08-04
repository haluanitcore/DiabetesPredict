# Revisi Pengujian V3 — DiaPredict

Folder ini berisi seluruh notebook yang menjawab catatan revisi penguji:

> "Pengujian tidak detil, tidak ada pemilihan knp k=20 dr knn, knp hyperplane pada svm yg dipilih,
> knp splitting data pake 80:20. Diperbanyak pengujiannya."

---

## 1. Notebook mana yang dipakai website?

Model yang berjalan di website DiaPredict berasal dari **`Diabetes_Prediction_RF_KNN_SVM_V2 (1).ipynb`**.
Dasar penelusurannya:

| Bukti | Lokasi |
|---|---|
| Halaman perbandingan menyebut nama notebook secara eksplisit | `resources/views/analysis/comparison.blade.php` |
| Seluruh angka di tabel & grafik web sama persis dengan output notebook V2 | cell 25, 29, 30 notebook V2 |
| Threshold produksi `0.4965` hanya ada di notebook V2 | `model/predict.py`, `ml-service/main.py` |
| Lima fitur yang dipakai produksi = hasil feature selection V2 | `model/train_model.py` |

Dua notebook lain (`Diabetes_Prediction_RF_XAI.ipynb` yang memakai 8+ fitur, dan `Naive.Bayes.ipynb`)
adalah versi eksplorasi lama dan **tidak** dipakai sistem.

### Titik lemah notebook V2 terhadap catatan penguji

| Catatan penguji | Kondisi di V2 |
|---|---|
| Kenapa k pada KNN | `RandomizedSearchCV` atas 7 kandidat `[3,5,7,9,11,15,21]`, terpilih **k = 21** tanpa analisis. Penguji menulis "k=20"; angka sebenarnya 21 dan perlu diklarifikasi. |
| Kenapa hyperplane SVM | Hanya `LinearSVC` dengan tuning `C`. Tidak ada perbandingan kernel, tidak ada analisis margin maupun support vector. |
| Kenapa split 80:20 | `test_size=0.20` ditulis langsung tanpa pembanding. |
| Pengujian kurang banyak | Hanya satu holdout, 5-fold CV saat tuning, dan McNemar. Tidak ada interval kepercayaan, nested CV, ablation, uji robustness, maupun evaluasi subgrup. |

---

## 2. Daftar notebook

Jalankan **berurutan**. Notebook 00 wajib dijalankan pertama karena menyiapkan data dan fungsi bersama.

| No | Berkas | Isi | Menjawab |
|---|---|---|---|
| 00 | `00_Setup_dan_Eksplorasi_Data.ipynb` | Preamble bersama, EDA, verifikasi reproduksi angka V2 | Fondasi |
| 01 | `01_Justifikasi_Rasio_Split.ipynb` | 7 rasio × 5 seed, learning curve, margin of error, holdout vs CV | "kenapa 80:20" |
| 02 | `02_Justifikasi_Pemilihan_K_KNN.ipynb` | Sweep k=1..51, kurva bias–variance, one-SE rule, uji antar-k, grid weights/metric | "kenapa k" |
| 03 | `03_Justifikasi_Hyperplane_SVM.ipynb` | Perbandingan kernel, grid C×gamma, lebar margin & support vector, visualisasi hyperplane, bobot w | "kenapa hyperplane ini" |
| 04 | `04_Validasi_Statistik_dan_Threshold.ipynb` | Repeated CV + CI, nested CV, 4 uji statistik, 8 strategi threshold, kalibrasi, decision curve | "perbanyak pengujian" |
| 05 | `05_Ablation_Robustness_dan_Keputusan_Model.ipynb` | Ablation resampling & fitur, uji robustness, evaluasi subgrup, matriks keputusan multi-kriteria | "perbanyak pengujian" |
| 06 | `06_Model_Final_dan_Export_Produksi.ipynb` | Latih model final, ekspor artefak produksi + `experiments.json` untuk website | Sinkronisasi sistem |

`_SPEC_BERSAMA.md` adalah kontrak teknis antar-notebook (preamble, konstanta, nama berkas hasil).
Jangan mengubah preamble di satu notebook saja — ubah di spec lalu samakan semuanya, agar hasil tetap dapat dibandingkan.

### Versi gabungan (satu berkas)

**`DiaPredict_Revisi_V3_GABUNGAN.ipynb`** memuat ketujuh notebook di atas dalam satu berkas
(188 cell: 106 kode + 82 markdown, 8.931 baris kode). Isi kode tiap bagian **identik** dengan
notebook aslinya; hanya dua hal yang berubah:

1. Preamble (CELL 1–6) dimuat **sekali saja**, bukan tujuh kali.
2. `MODE_CEPAT` dipusatkan di satu **PANEL KENDALI**, bukan tersebar di lima berkas.

Pilih salah satu, jangan dijalankan keduanya:

| | Tujuh notebook terpisah | Satu notebook gabungan |
|---|---|---|
| Menjalankan sebagian | Buka berkas yang diperlukan saja | Jalankan CELL 1–7 lalu bagian yang dituju |
| Menjalankan paralel di beberapa tab Colab | Bisa | Tidak |
| Tempat mengatur `MODE_CEPAT` | 5 berkas | 1 tempat |
| Cocok untuk | Pengerjaan bertahap | Lampiran skripsi, satu kali jalan utuh |

Setiap bagian di notebook gabungan bersifat mandiri — sudah diverifikasi secara statis bahwa
tidak ada bagian yang membaca variabel milik bagian lain. Jadi bila runtime terputus, cukup
jalankan ulang CELL 1–7 lalu lanjutkan dari bagian yang tertunda. Pengecualian: **BAGIAN 7**
membaca berkas JSON hasil BAGIAN 1–6, sehingga harus dijalankan paling akhir.

---

## 3. Cara menjalankan di Google Colab

### Langkah 1 — Unggah ke Google Drive

Unggah folder `Revisi_Pengujian_V3` ke Google Drive (mis. ke `MyDrive`).
Cukup berkas `.ipynb` saja; `README.md` dan `_SPEC_BERSAMA.md` tidak ikut dijalankan.

### Langkah 2 — Buka notebook di Colab

Klik kanan berkas `.ipynb` di Drive → **Open with** → **Google Colaboratory**.
Runtime CPU standar sudah cukup; GPU tidak memberi percepatan karena scikit-learn tidak memakainya.

### Langkah 3 — Ubah DUA baris sebelum menjalankan

Hanya dua pengaturan yang perlu disentuh di setiap notebook:

| Pengaturan | Letak | Ubah menjadi |
|---|---|---|
| `PAKAI_DRIVE` | **CELL 2** (semua notebook 00–06) | `True` |
| `MODE_CEPAT` | **CELL 7** (hanya notebook 01–05) | lihat penjelasan di bawah |

**`PAKAI_DRIVE = True` wajib.** Dengan `False`, hasil hanya tersimpan di `/content` dan
hilang begitu runtime berakhir, sehingga notebook 06 tidak akan menemukan hasil notebook 01–05.
Saat dijalankan, Colab akan meminta izin akses Drive — setujui.

**`MODE_CEPAT`** mengatur biaya komputasi:

```python
MODE_CEPAT = True    # subsample (20.000-30.000 baris) + grid kecil -> untuk UJI ALUR
MODE_CEPAT = False   # data penuh 96.146 baris                     -> untuk ANGKA FINAL SKRIPSI
```

### Langkah 4 — Jalankan dua putaran

**Putaran pertama (`MODE_CEPAT = True`)** — tujuannya memastikan seluruh sel berjalan tanpa error,
bukan mengambil angka. Jalankan lewat menu **Runtime → Run all**.

**Putaran kedua (`MODE_CEPAT = False`)** — inilah yang menghasilkan angka untuk skripsi.
Jalankan ulang **Runtime → Run all** setelah mengubah sakelarnya.

Jangan melaporkan angka dari putaran pertama di skripsi: putaran itu memakai sebagian data
dan grid pencarian yang diperkecil.

### Urutan menjalankan

```
00  ->  01  ->  02  ->  03  ->  04  ->  05  ->  06
```

Namun ketergantungan sesungguhnya lebih longgar dari yang terlihat:

- **Notebook 00–05 berdiri sendiri.** Masing-masing memuat dan membersihkan datanya sendiri,
  jadi tidak saling menunggu. Bila Colab mengizinkan beberapa sesi sekaligus, notebook 01–05
  boleh dijalankan berbarengan di tab berbeda — keluarannya bernama berbeda sehingga tidak bentrok.
- **Notebook 06 harus terakhir**, karena ia membaca berkas JSON hasil notebook 00–05.
  Bila ada yang belum dijalankan, notebook 06 tetap jalan dengan nilai cadangan dan mencetak
  peringatan bagian mana yang belum tersedia.

### Dataset

Notebook mengunduh dataset otomatis lewat `kagglehub`. Dataset ini **sudah tersedia di cache Colab**
(`Using Colab cache for faster access...`), jadi **tidak perlu akun atau token Kaggle**.

### Perkiraan waktu

Setiap notebook mencetak estimasi waktunya sendiri di CELL 7 sebelum eksperimen berat dimulai —
angka itu lebih tepercaya daripada patokan umum karena menyesuaikan mesin yang sedang Anda pakai.

Sebagai gambaran kasar: tuning Random Forest pada notebook V2 memakan **85 menit** untuk data penuh.
Dengan `MODE_CEPAT = True` satu notebook umumnya selesai dalam hitungan puluhan menit; dengan
`MODE_CEPAT = False` siapkan waktu beberapa jam untuk keseluruhan rangkaian.

Setiap eksperimen berat menyimpan hasilnya segera setelah selesai (berkas `checkpoint_*.json`),
sehingga runtime yang terputus di tengah jalan tidak menghapus seluruh kemajuan.

### Bila Colab terputus di tengah jalan

Buka kembali notebook yang sama, pastikan `PAKAI_DRIVE = True`, lalu **Run all** dari awal.
Bagian yang sudah selesai akan ditulis ulang dengan hasil yang sama (seluruh notebook memakai
`random_state` tetap, sehingga hasilnya dapat direproduksi).

---

## 4. Keluaran

Setiap notebook menulis ke `OUTPUT_DIR`:

```
OUTPUT_DIR/
├── tabel/     *.csv   -> tabel siap tempel ke skripsi
├── gambar/    *.png   -> grafik 150 dpi siap tempel ke skripsi
├── json/      *.json  -> hasil terstruktur, dibaca notebook 06
└── produksi/          -> dihasilkan notebook 06
    ├── rf_model.pkl
    ├── scaler.pkl
    ├── model_metadata.json
    └── experiments.json
```

### Menyalin kembali ke aplikasi

| Berkas hasil | Tujuan di repo |
|---|---|
| `produksi/rf_model.pkl` | `model/rf_model.pkl` |
| `produksi/scaler.pkl` | `model/scaler.pkl` |
| `produksi/model_metadata.json` | `model/model_metadata.json` |
| `produksi/experiments.json` | `public/data/experiments.json` |

Lalu jalankan `php artisan cache:clear` agar halaman membaca data terbaru.
Halaman **Comparison** dan **Metodologi** membaca seluruh angkanya dari `public/data/experiments.json`,
sehingga memperbarui hasil riset tidak perlu menyunting satu pun berkas tampilan.

---

## 5. Perbaikan sistem yang menyertai revisi ini

### 5.1 Bug urutan fitur pada inferensi (kritis)

Model dilatih dengan urutan `[age, bmi, hypertension, HbA1c_level, blood_glucose_level]`,
tetapi kode inferensi lama mengirim `[age, hypertension, bmi, HbA1c_level, blood_glucose_level]`
— **kolom BMI dan Hipertensi tertukar pada setiap prediksi.**

Dampaknya diukur pada model produksi yang sedang berjalan, memakai 4.000 profil pasien simulasi
(usia 18–80, BMI 16–45, HbA1c 4–9, glukosa 80–280, hipertensi acak):

| Ukuran | Nilai |
|---|---|
| Rata-rata selisih probabilitas | 0,0339 |
| Selisih probabilitas terbesar | 0,7400 |
| Kasus dengan selisih > 0,05 | 13,4% |
| **Keputusan akhir berubah** | **3,30% kasus** |
| &nbsp;&nbsp;→ positif menjadi negatif (false negative baru) | 118 kasus |
| &nbsp;&nbsp;→ negatif menjadi positif | 14 kasus |

Sebagian besar perubahan mengarah ke **false negative**, yaitu arah kesalahan yang paling
berbahaya dalam konteks medis. Catatan: profil pasien di atas disebar merata, bukan mengikuti
distribusi populasi nyata, sehingga persentase ini adalah gambaran besaran dampak, bukan
estimasi prevalensi kesalahan di lapangan.

**Perbaikan:** `model/predict.py` dan `ml-service/main.py` kini menyusun vektor fitur
berdasarkan `feature_order` dari `model/model_metadata.json`, bukan mengikuti urutan argumen
atau urutan field permintaan. Notebook 06 juga menyertakan sel verifikasi yang memperagakan
selisih kedua urutan tersebut sebagai uji regresi.

### 5.2 Model produksi tidak sama dengan model skripsi

`model/train_model.py` versi lama memakai `RandomForestClassifier` default (100 pohon, tanpa
`max_depth`), tanpa winsorization, dan men-fit scaler pada data hasil SMOTE. Akibatnya model yang
melayani pengguna bukan model yang dilaporkan di skripsi, dan berkas pickle-nya membengkak
hingga sekitar 78 MB.

Skrip tersebut telah ditulis ulang agar mengikuti pipeline notebook: winsorization, split 80:20
stratified, scaler di-fit pada data latih asli, SMOTE hanya pada data latih, dan hyperparameter
hasil tuning. Skrip ini juga menulis `model_metadata.json`.

### 5.3 Label metode XAI

Notebook V2 memilih model dengan recall tertinggi (KNN) untuk analisis XAI. Karena KNN bukan
model berbasis pohon, TreeSHAP tidak dapat dipakai, sehingga angka yang dihitung sebenarnya
adalah **Permutation Importance**, bukan nilai SHAP — meskipun website menampilkannya dengan
label "SHAP". Label ini sudah dikoreksi dan kini dibaca dari berkas data beserta catatan metodenya.

---

## 6. Checklist sebelum sidang

- [ ] Notebook 00–06 dijalankan dengan `MODE_CEPAT = False` dan `PAKAI_DRIVE = True`
- [ ] Seluruh berkas di `tabel/` dan `gambar/` diunduh untuk lampiran skripsi
- [ ] Bagian "RINGKASAN UNTUK SKRIPSI" di tiap notebook disalin ke Bab 3/4
- [ ] Nilai k final dipastikan (hasil sweep + one-SE rule), dan penulisan "k=20" pada dokumen diperbaiki menjadi nilai yang benar
- [ ] Artefak produksi disalin ke `model/` dan `public/data/`
- [ ] Halaman Comparison dan Metodologi dibuka untuk memastikan seluruh grafik terisi
- [ ] `python model/predict.py 55 0 28.5 6.8 150` dijalankan dan hasilnya cocok dengan notebook 06
