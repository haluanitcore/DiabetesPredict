# SPEC BERSAMA — Notebook Revisi V3 (WAJIB DIIKUTI SEMUA NOTEBOOK)

Dokumen ini adalah kontrak teknis antar-notebook. Setiap notebook di folder ini WAJIB
memakai preamble, konstanta, dan fungsi yang sama persis supaya angka antar notebook
konsisten dan dapat digabung oleh notebook `06`.

---

## 0. Aturan umum

- Target runtime: **Google Colab** (CPU standar). Notebook harus bisa dijalankan
  dari atas ke bawah tanpa editing.
- Bahasa narasi: **Bahasa Indonesia** (gaya sama dengan notebook V2 lama).
- Setiap cell kode diawali komentar banner:
  ```
  # ============================================================
  # CELL n: <judul singkat>
  # ============================================================
  ```
- Tidak ada emoji di dalam kode/print (V2 sempat error encoding di Windows).
- Setiap eksperimen WAJIB berakhir dengan: (a) tabel `DataFrame` yang disimpan via
  `simpan_tabel`, (b) minimal 1 gambar disimpan via `simpan_gambar`, (c) blok
  `print` kesimpulan yang langsung bisa disalin ke skripsi.
- Setiap notebook diakhiri cell **"RINGKASAN UNTUK SKRIPSI"** berisi paragraf
  kesimpulan yang menjawab poin revisi penguji secara eksplisit.
- Warna konsisten lintas notebook & website:
  - Random Forest = `#3498db`
  - KNN = `#e74c3c`
  - SVM (Linear) = `#2ecc71`
  - Aksen highlight = `#f39c12`

---

## 1. Preamble WAJIB (salin apa adanya sebagai CELL 1–4 tiap notebook)

### CELL 1 — Instalasi
```python
# ============================================================
# CELL 1: Instalasi Library
# ============================================================
!pip install -q pandas numpy matplotlib seaborn scikit-learn imbalanced-learn statsmodels kagglehub
```

### CELL 2 — Import & Konstanta Global
```python
# ============================================================
# CELL 2: Import & Konstanta Global
# ============================================================
import os, json, time, math, warnings, itertools
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns

from sklearn.ensemble import RandomForestClassifier
from sklearn.neighbors import KNeighborsClassifier
from sklearn.svm import LinearSVC, SVC
from sklearn.calibration import CalibratedClassifierCV
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import (
    train_test_split, StratifiedKFold, StratifiedShuffleSplit,
    RepeatedStratifiedKFold, cross_validate, cross_val_predict,
    learning_curve, validation_curve, GridSearchCV, RandomizedSearchCV
)
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    roc_auc_score, roc_curve, average_precision_score,
    precision_recall_curve, confusion_matrix, classification_report,
    brier_score_loss
)
from imblearn.pipeline import Pipeline as ImbPipeline
from imblearn.over_sampling import SMOTE

warnings.filterwarnings('ignore')

RANDOM_STATE = 42
np.random.seed(RANDOM_STATE)

SELECTED_FEATURES = ['age', 'bmi', 'hypertension', 'HbA1c_level', 'blood_glucose_level']
FEATURE_LABELS    = ['Usia', 'BMI', 'Hipertensi', 'HbA1c', 'Kadar Glukosa']
TARGET            = 'diabetes'

WARNA_MODEL = {'Random Forest': '#3498db', 'KNN': '#e74c3c', 'SVM (Linear)': '#2ecc71'}
WARNA_AKSEN = '#f39c12'

plt.rcParams['figure.figsize'] = (12, 6)
plt.rcParams['font.size'] = 11
plt.rcParams['axes.titlesize'] = 13
plt.rcParams['axes.labelsize'] = 11
plt.rcParams['axes.grid'] = True
plt.rcParams['grid.alpha'] = 0.25
sns.set_style('whitegrid')

# --- Folder output -------------------------------------------------------
# Set PAKAI_DRIVE = True bila ingin hasil tersimpan permanen di Google Drive
# (WAJIB True kalau ingin notebook 06 membaca hasil notebook 01-05).
PAKAI_DRIVE = False

if PAKAI_DRIVE:
    from google.colab import drive
    drive.mount('/content/drive')
    OUTPUT_DIR = '/content/drive/MyDrive/DiaPredict_Revisi'
else:
    OUTPUT_DIR = '/content/hasil_revisi'

for sub in ['', '/tabel', '/gambar', '/json']:
    os.makedirs(OUTPUT_DIR + sub, exist_ok=True)

print(f'Folder output : {OUTPUT_DIR}')
print(f'Fitur         : {SELECTED_FEATURES}')
```

### CELL 3 — Fungsi Utilitas Penyimpanan
```python
# ============================================================
# CELL 3: Fungsi Utilitas Penyimpanan Hasil
# ============================================================
def simpan_tabel(df, nama, tampilkan=True):
    """Simpan DataFrame ke CSV di OUTPUT_DIR/tabel dan tampilkan."""
    path = f'{OUTPUT_DIR}/tabel/{nama}.csv'
    df.to_csv(path, index=False)
    print(f'[TABEL DISIMPAN] {path}')
    if tampilkan:
        display(df)
    return df

def simpan_json(obj, nama):
    """Simpan dict/list hasil eksperimen ke JSON (dipakai notebook 06 & website)."""
    path = f'{OUTPUT_DIR}/json/{nama}.json'
    def _konversi(o):
        if isinstance(o, (np.integer,)):  return int(o)
        if isinstance(o, (np.floating,)): return float(o)
        if isinstance(o, (np.ndarray,)):  return o.tolist()
        if isinstance(o, (np.bool_,)):    return bool(o)
        return str(o)
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(obj, f, indent=2, ensure_ascii=False, default=_konversi)
    print(f'[JSON DISIMPAN] {path}')
    return obj

def simpan_gambar(nama, fig=None, dpi=150):
    """Simpan figure matplotlib aktif ke OUTPUT_DIR/gambar."""
    path = f'{OUTPUT_DIR}/gambar/{nama}.png'
    (fig or plt).savefig(path, dpi=dpi, bbox_inches='tight')
    print(f'[GAMBAR DISIMPAN] {path}')
    return path

def garis(judul='', lebar=70):
    print('=' * lebar)
    if judul:
        print(f'  {judul}')
        print('=' * lebar)
```

### CELL 4 — Data Loading + Cleaning (IDENTIK dengan notebook V2)
```python
# ============================================================
# CELL 4: Load Dataset + Cleaning + Winsorization
# (Identik dengan pipeline notebook V2 agar hasil dapat dibandingkan)
# ============================================================
import kagglehub

def muat_dan_bersihkan_data(verbose=True):
    path = kagglehub.dataset_download('iammustafatz/diabetes-prediction-dataset')
    csv_file = os.path.join(path, 'diabetes_prediction_dataset.csv')
    df_raw = pd.read_csv(csv_file)

    # 1) Hapus duplikat pada dataset penuh (SAMA seperti V2 -> sisa 96.146 baris)
    df = df_raw.drop_duplicates().reset_index(drop=True)

    # 2) Ambil 5 fitur terpilih + target
    df = df[SELECTED_FEATURES + [TARGET]].copy()

    # 3) Winsorization (capping IQR) hanya untuk fitur numerik non-biner
    numeric_feats = [f for f in SELECTED_FEATURES if df[f].nunique() > 2]
    ringkas = []
    for feat in numeric_feats:
        Q1, Q3 = df[feat].quantile(0.25), df[feat].quantile(0.75)
        IQR = Q3 - Q1
        low, up = Q1 - 1.5 * IQR, Q3 + 1.5 * IQR
        n_cap = int(((df[feat] < low) | (df[feat] > up)).sum())
        df[feat] = df[feat].clip(lower=low, upper=up)
        ringkas.append({'fitur': feat, 'batas_bawah': low, 'batas_atas': up, 'n_dicapping': n_cap})

    if verbose:
        garis('DATA SIAP PAKAI')
        print(f'Baris (setelah hapus duplikat) : {len(df):,}')
        print(f'Distribusi kelas               : '
              f'{(df[TARGET]==0).sum():,} sehat / {(df[TARGET]==1).sum():,} diabetes '
              f'({df[TARGET].mean()*100:.2f}% positif)')
        display(pd.DataFrame(ringkas))
    return df

df_clean = muat_dan_bersihkan_data()
X_all = df_clean[SELECTED_FEATURES].copy()
y_all = df_clean[TARGET].copy()
```

### CELL 5 — Pabrik Pipeline (WAJIB, dipakai semua notebook)
```python
# ============================================================
# CELL 5: Pabrik Pipeline Model (anti data leakage)
# Urutan: StandardScaler -> SMOTE -> Classifier (imblearn Pipeline,
# sehingga SMOTE HANYA aktif saat fit, tidak saat predict/validasi)
# ============================================================

# Hyperparameter terbaik hasil tuning notebook V2 (baseline pembanding)
PARAM_RF_V2  = dict(n_estimators=200, max_depth=10, min_samples_split=5,
                    min_samples_leaf=4, max_features='log2', criterion='entropy',
                    class_weight='balanced')
PARAM_KNN_V2 = dict(n_neighbors=21, weights='uniform', metric='euclidean', leaf_size=20)
PARAM_SVM_V2 = dict(C=0.1, max_iter=3000)

def buat_pipeline_rf(pakai_smote=True, **params):
    p = {**PARAM_RF_V2, **params}
    langkah = [('scaler', StandardScaler())]
    if pakai_smote:
        langkah.append(('smote', SMOTE(random_state=RANDOM_STATE)))
    langkah.append(('clf', RandomForestClassifier(random_state=RANDOM_STATE, n_jobs=-1, **p)))
    return ImbPipeline(langkah)

def buat_pipeline_knn(pakai_smote=True, **params):
    p = {**PARAM_KNN_V2, **params}
    langkah = [('scaler', StandardScaler())]
    if pakai_smote:
        langkah.append(('smote', SMOTE(random_state=RANDOM_STATE)))
    langkah.append(('clf', KNeighborsClassifier(n_jobs=-1, **p)))
    return ImbPipeline(langkah)

def buat_pipeline_svm(pakai_smote=True, kernel='linear', C=0.1, gamma='scale',
                      degree=3, max_iter=3000, kalibrasi='sigmoid'):
    """kernel='linear' -> LinearSVC (cepat). Kernel lain -> SVC."""
    if kernel == 'linear':
        base = LinearSVC(C=C, max_iter=max_iter, class_weight='balanced',
                         dual=False, random_state=RANDOM_STATE)
    else:
        base = SVC(kernel=kernel, C=C, gamma=gamma, degree=degree,
                   class_weight='balanced', random_state=RANDOM_STATE)
    langkah = [('scaler', StandardScaler())]
    if pakai_smote:
        langkah.append(('smote', SMOTE(random_state=RANDOM_STATE)))
    langkah.append(('clf', CalibratedClassifierCV(base, cv=3, method=kalibrasi)))
    return ImbPipeline(langkah)

PABRIK_MODEL = {
    'Random Forest': buat_pipeline_rf,
    'KNN'          : buat_pipeline_knn,
    'SVM (Linear)' : buat_pipeline_svm,
}
```

### CELL 6 — Fungsi Evaluasi Standar
```python
# ============================================================
# CELL 6: Fungsi Evaluasi Standar (dipakai seluruh notebook)
# ============================================================
def threshold_youden(y_true, y_proba):
    fpr, tpr, thr = roc_curve(y_true, y_proba)
    return float(thr[np.argmax(tpr - fpr)])

def hitung_metrik(y_true, y_pred, y_proba=None):
    hasil = {
        'accuracy' : accuracy_score(y_true, y_pred),
        'precision': precision_score(y_true, y_pred, zero_division=0),
        'recall'   : recall_score(y_true, y_pred, zero_division=0),
        'f1'       : f1_score(y_true, y_pred, zero_division=0),
    }
    if y_proba is not None:
        hasil['roc_auc']  = roc_auc_score(y_true, y_proba)
        hasil['ap_score'] = average_precision_score(y_true, y_proba)
        hasil['brier']    = brier_score_loss(y_true, y_proba)
    return hasil

def evaluasi_holdout(model, X_tr, y_tr, X_te, y_te, tuning_threshold=True):
    """Fit -> prediksi -> metrik pada threshold 0.5 dan threshold Youden."""
    t0 = time.time(); model.fit(X_tr, y_tr); waktu_latih = time.time() - t0
    t0 = time.time(); y_proba = model.predict_proba(X_te)[:, 1]; waktu_infer = time.time() - t0

    thr = threshold_youden(y_te, y_proba) if tuning_threshold else 0.5
    m_def  = hitung_metrik(y_te, (y_proba >= 0.5).astype(int), y_proba)
    m_tune = hitung_metrik(y_te, (y_proba >= thr).astype(int), y_proba)
    return {
        'threshold': thr,
        'waktu_latih_s': waktu_latih,
        'waktu_infer_ms': waktu_infer * 1000,
        **{f'{k}_default': v for k, v in m_def.items()},
        **{f'{k}_tuned'  : v for k, v in m_tune.items()},
    }

def ci95_proporsi(p, n):
    """Confidence interval 95% (Wald) untuk metrik berbasis proporsi (mis. recall)."""
    if n == 0: return (np.nan, np.nan, np.nan)
    se = math.sqrt(max(p * (1 - p), 1e-12) / n)
    return (p - 1.96 * se, p + 1.96 * se, 1.96 * se)
```

---

## 2. Kontrak nama file JSON antar-notebook

Notebook `06` membaca file berikut. Nama HARUS persis:

| Notebook | Nama JSON | Isi minimal |
|---|---|---|
| 01 | `hasil_split_ratio` | `{"rasio_terpilih": 0.2, "tabel": [...], "learning_curve": {...}, "margin_of_error": [...], "kesimpulan": "..."}` |
| 02 | `hasil_pemilihan_k` | `{"k_terpilih": int, "alasan": "...", "sweep": [...], "one_se_rule": {...}, "grid_weights_metric": [...]}` |
| 03 | `hasil_svm_hyperplane` | `{"kernel_terpilih": "linear", "C_terpilih": float, "perbandingan_kernel": [...], "analisis_margin": [...], "bobot_w": {...}, "kesimpulan": "..."}` |
| 04 | `hasil_validasi_statistik` | `{"repeated_cv": [...], "nested_cv": [...], "uji_statistik": [...], "strategi_threshold": [...], "kalibrasi": [...]}` |
| 05 | `hasil_ablation_robustness` | `{"resampling": [...], "fitur": [...], "robustness": [...], "subgrup": [...], "matriks_keputusan": [...], "model_produksi": "Random Forest"}` |
| 06 | `experiments` | Gabungan semua di atas + metadata model final (dipakai website) |

Aturan: bila file input tidak ditemukan, notebook 06 WAJIB tetap jalan
(pakai `try/except` + pesan peringatan), bukan crash.

---

## 3. Strategi biaya komputasi (WAJIB dipatuhi)

Tuning RF di V2 memakan 85 menit. Karena itu:

- Sediakan konstanta `MODE_CEPAT = True` di awal tiap notebook eksperimen.
  - `MODE_CEPAT = True`  -> pakai subsample stratified (`N_SUBSAMPLE`) + grid diperkecil.
  - `MODE_CEPAT = False` -> data penuh 96.146 baris (untuk hasil final skripsi).
- Fungsi subsample WAJIB stratified:
  ```python
  def ambil_subsample(X, y, n, seed=RANDOM_STATE):
      if n >= len(X): return X, y
      sss = StratifiedShuffleSplit(n_splits=1, train_size=n, random_state=seed)
      idx, _ = next(sss.split(X, y))
      return X.iloc[idx], y.iloc[idx]
  ```
- Cetak estimasi waktu di awal tiap eksperimen berat, dan `print` progres per iterasi
  (jangan diam lama tanpa output).
- Setiap eksperimen berat menyimpan hasil segera setelah selesai (checkpoint),
  supaya kalau Colab terputus tidak hilang semua.

---

## 4. Daftar notebook di folder ini

| File | Isi | Menjawab revisi |
|---|---|---|
| `00_Setup_dan_Eksplorasi_Data.ipynb` | Preamble, EDA, verifikasi reproduksi angka V2 | Fondasi |
| `01_Justifikasi_Rasio_Split.ipynb` | Eksperimen rasio split, learning curve, margin of error | "kenapa 80:20" |
| `02_Justifikasi_Pemilihan_K_KNN.ipynb` | Sweep k, elbow, one-SE rule, grid weights/metric | "kenapa k=20/21" |
| `03_Justifikasi_Hyperplane_SVM.ipynb` | Perbandingan kernel, grid C/gamma, margin & support vector, visualisasi hyperplane | "kenapa hyperplane ini" |
| `04_Validasi_Statistik_dan_Threshold.ipynb` | Repeated CV, nested CV, uji statistik, strategi threshold, kalibrasi | "perbanyak pengujian" |
| `05_Ablation_Robustness_dan_Keputusan_Model.ipynb` | Ablation resampling & fitur, uji robustness, subgrup, matriks keputusan | "perbanyak pengujian" |
| `06_Model_Final_dan_Export_Produksi.ipynb` | Latih model final, export `rf_model.pkl` + `scaler.pkl` + `model_metadata.json` + `experiments.json` | Sinkronisasi web |
