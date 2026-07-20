# 🩸 Diapredict - Sistem Deteksi Dini & Prediksi Risiko Diabetes

Diapredict adalah aplikasi web modern berbasis **Laravel** yang terintegrasi dengan kecerdasan buatan (**Machine Learning - Python**) untuk memprediksi risiko diabetes pasien berdasarkan 8 parameter klinis. Aplikasi ini melatih model klasifikasi menggunakan algoritma **Random Forest** dengan teknik penanganan data tidak seimbang (SMOTE) untuk akurasi prediksi yang optimal.

---

## 🛠️ Tech Stack (Teknologi yang Digunakan)

### 1. Backend & Web Core
* **Framework**: Laravel 11.x / 13.x (PHP >= 8.3)
* **Database**: MySQL (default) atau SQLite (sebagai alternatif)
* **Autentikasi**: Sistem Autentikasi Kustom (Registrasi, Login, Logout)
* **Fitur Utama**: Riwayat Analisis Medis, Detail Prediksi, Manajemen Pengguna

### 2. Frontend & Design System
* **Bundler & Build Tool**: Vite 8.x
* **CSS Framework**: Tailwind CSS v4.0.0 (menggunakan plugin baru `@tailwindcss/vite` dengan konfigurasi tema langsung di dalam CSS)
* **Fonts**: Google Fonts (`Public Sans` & `Instrument Sans` via Bunny Fonts)
* **Layout**: Desain responsif, modern, dengan micro-animation, serta elemen kartu berbasis glassmorphism.

### 3. Machine Learning (AI)
* **Bahasa Pemrograman**: Python >= 3.10
* **Algoritma**: Random Forest Classifier (Scikit-Learn)
* **Teknik Penyeimbangan Data**: SMOTE (Synthetic Minority Over-sampling Technique via `imbalanced-learn`)
* **Pengunduh Dataset**: `kagglehub` (dataset: `iammustafatz/diabetes-prediction-dataset`)
* **Serialization**: `pickle` (untuk mengekspor model `rf_model.pkl` dan standardizer `scaler.pkl`)

---

## 📋 Prasyarat Sistem (Prerequisites)

Pastikan komputer Anda sudah terinstal perangkat lunak berikut:
1. **Laragon** (sangat direkomendasikan untuk pengguna Windows) atau XAMPP.
2. **PHP >= 8.3** (pastikan ekstensi `pdo_mysql`, `openssl`, `mbstring`, dan `curl` aktif).
3. **Composer** (Dependency manager PHP).
4. **Node.js & NPM** (untuk compiler frontend).
5. **Python >= 3.10** beserta `pip` (package manager Python).

---

## 🚀 Langkah-Langkah Setup dari Awal (Setup Guide - MySQL)

Ikuti langkah-langkah di bawah ini untuk menyiapkan project dari awal di komputer lokal Anda menggunakan database **MySQL** (Laragon/XAMPP):

### Langkah 1: Posisikan Project di Web Server
Pindahkan folder project `diapredict` ke direktori root web server Laragon Anda (biasanya di `C:\laragon\www\diapredict`).

### Langkah 2: Buat Database MySQL
1. Buka database manager Anda (HeidiSQL, phpMyAdmin, dll) yang terhubung ke Laragon.
2. Buat database baru dengan nama **`diapredict`**.

### Langkah 3: Setup File Environment (`.env`)
1. Buka terminal/CMD di direktori project Anda, lalu buat salinan file `.env` dari `.env.example`:
   ```bash
   copy .env.example .env
   ```
2. Buka file `.env` baru tersebut menggunakan text editor Anda, lalu sesuaikan bagian database berikut:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=diapredict
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   *(Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` jika Anda mengatur kata sandi pada database MySQL Laragon/XAMPP Anda)*.

### Langkah 4: Install Dependencies & Jalankan Migrasi
Kembali ke terminal, jalankan perintah berikut untuk menginstal dependency PHP & Node.js serta memigrasikan tabel database:
```bash
# 1. Menginstal library PHP via Composer
composer install

# 2. Men-generate Application Key Laravel
php artisan key:generate

# 3. Menjalankan migrasi database ke MySQL
php artisan migrate

# 4. Menginstal library Node.js & mem-build asset frontend
npm install
npm run build
```

---

### Langkah 5: Setup Environment Python (Virtual Environment)
Disarankan menggunakan Python Virtual Environment agar library machine learning tidak bentrok dengan versi global.

1. Buka CMD/Terminal di dalam direktori project, lalu buat virtual environment baru:
   ```bash
   python -m venv venv
   ```

2. Aktifkan Virtual Environment tersebut:
   * **Menggunakan Command Prompt (CMD)**:
     ```cmd
     venv\Scripts\activate
     ```
   * **Menggunakan PowerShell**:
     ```powershell
     .\venv\Scripts\Activate.ps1
     ```

3. Instal semua package Python yang diperlukan untuk proses training dan prediksi:
   ```bash
   pip install numpy pandas scikit-learn imbalanced-learn kagglehub
   ```

---

### Langkah 6: Training Machine Learning Model (Opsional)
Project ini memerlukan file model (`rf_model.pkl`) dan file scaler (`scaler.pkl`) agar fungsi prediksi dapat berjalan. File model bawaan sudah tersedia, namun jika Anda ingin melatih ulang model dengan dataset terbaru, jalankan langkah berikut:

1. Pastikan virtual environment dalam keadaan **aktif**.
2. Jalankan script training:
   ```bash
   python model/train_model.py
   ```
   *Script ini akan otomatis mengunduh dataset resmi dari Kaggle via `kagglehub`, melakukan preprocessing data, menangani ketidakseimbangan kelas dengan SMOTE, melatih model Random Forest, dan mengekspor hasilnya ke folder `model/`.*

---

### Langkah 7: Konfigurasi Python Path di `.env`
Buka kembali file `.env` Anda, sesuaikan jalur interpreter python agar Laravel dapat mengeksekusi model.
Jika Anda menggunakan Virtual Environment sesuai Langkah 5, masukkan/sesuaikan baris berikut di paling bawah file `.env`:
```env
# Path ke python executable untuk AI model predictions
PYTHON_PATH="C:\LARAGON\laragon\www\diapredict\venv\Scripts\python.exe"
```
*(Sesuaikan path absolute di atas dengan lokasi folder project Anda jika berbeda)*.

---

*(Catatan Alternatif: Jika di kemudian hari Anda ingin menggunakan **SQLite**, cukup ubah `DB_CONNECTION=sqlite` di file `.env`, buat file kosong di `database/database.sqlite`, lalu jalankan kembali `php artisan migrate`)*.

---

## 🖥️ Cara Menjalankan Project (How to Run)

Setelah seluruh langkah instalasi selesai, Anda dapat menjalankan aplikasi dengan sangat mudah.

### Perintah Utama (Satu Perintah untuk Semua)
Cukup jalankan satu perintah berikut di terminal Anda:
```bash
composer run dev
```
Perintah kustom ini akan menjalankan beberapa service secara paralel:
1. **Web Server Laravel** (`php artisan serve`) di `http://127.0.0.1:8000`
2. **Vite Development Server** (`npm run dev`) untuk compiler CSS/JS secara instan.
3. **Queue Listener** (`php artisan queue:listen`) untuk memproses jobs latar belakang.
4. **Artisan Pail** (`php artisan pail`) untuk memantau logs error secara real-time.

Akses aplikasi melalui browser Anda pada alamat:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

---

## 🔍 Alur Kerja Prediksi Aplikasi
1. Pengguna melakukan registrasi/login ke sistem.
2. Pengguna menavigasi ke halaman **Mulai Analisis** dan menginputkan 8 data klinis:
   * **Jenis Kelamin**: Laki-laki / Perempuan
   * **Usia**: Usia pasien (tahun)
   * **Hipertensi**: Ya / Tidak
   * **Penyakit Jantung**: Ya / Tidak
   * **Riwayat Merokok**: Pilihan kategori (Pernah, Tidak Pernah, Aktif, dll)
   * **BMI**: Body Mass Index (Indeks Massa Tubuh)
   * **Kadar HbA1c**: Rata-rata gula darah 3 bulan terakhir (%)
   * **Kadar Gula Darah Acak**: Kadar glukosa darah saat ini (mg/dL)
3. Setelah tombol **Analisis Sekarang** diklik, [AnalysisController.php](file:///c:/LARAGON/laragon/www/diapredict/app/Http/Controllers/AnalysisController.php) akan menangkap input, memvalidasinya, dan menjalankan script Python [predict.py](file:///c:/LARAGON/laragon/www/diapredict/model/predict.py) menggunakan fungsi PHP `exec()` dengan perintah terenkapsulasi secara aman.
4. Script Python memuat model `rf_model.pkl` dan `scaler.pkl`, menstandarisasi input, dan memproses prediksi dengan *threshold* probabilitas **0.36** (nilai optimal yang didapat dari tuning Random Forest).
5. Hasil prediksi dikembalikan dalam bentuk JSON ke Laravel, disimpan ke database `analysis_results`, dan disajikan kepada pengguna dalam bentuk grafik persentase risiko yang interaktif dan mudah dipahami.
