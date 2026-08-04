"""
DiaPredict - training model produksi (versi revisi V3).

Perbedaan penting dari versi sebelumnya:
1. Preprocessing DISAMAKAN dengan notebook riset:
   drop duplicates -> pilih 5 fitur -> winsorization (capping IQR).
   Versi lama tidak melakukan winsorization sehingga model produksi berbeda
   dari model yang dilaporkan di skripsi.
2. Urutan pipeline diperbaiki: StandardScaler di-fit pada data latih ASLI,
   baru kemudian SMOTE. Versi lama menjalankan SMOTE lebih dulu lalu men-fit
   scaler pada data hasil sintesis, sehingga statistik scaler bias.
3. Memakai hyperparameter hasil tuning (RandomizedSearchCV notebook V2/V3),
   bukan RandomForest default. Selain lebih akurat, ukuran pickle turun drastis
   (default tanpa max_depth menghasilkan file ~78 MB).
4. Menulis model_metadata.json berisi urutan fitur, threshold, dan batas
   winsorization -> dibaca predict.py dan ml-service/main.py sehingga bug
   urutan fitur (bmi <-> hypertension) tidak dapat terulang.

Catatan: hasil final untuk skripsi sebaiknya diambil dari notebook
`Revisi_Pengujian_V3/06_Model_Final_dan_Export_Produksi.ipynb`.
Skrip ini adalah jalur reproduksi lokal yang menghasilkan artefak setara.
"""
import os
import json
import pickle
from datetime import datetime

import numpy as np
import pandas as pd
import kagglehub
import sklearn
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    roc_auc_score, roc_curve,
)
from imblearn.over_sampling import SMOTE

# Urutan fitur ini adalah SATU-SATUNYA sumber kebenaran; ikut diekspor ke metadata.
SELECTED_FEATURES = ['age', 'bmi', 'hypertension', 'HbA1c_level', 'blood_glucose_level']
TARGET = 'diabetes'
RANDOM_STATE = 42
TEST_SIZE = 0.20  # dijustifikasi di notebook 01_Justifikasi_Rasio_Split.ipynb

# Hyperparameter hasil tuning (RandomizedSearchCV, scoring=recall, 5-fold stratified)
PARAM_RF = dict(
    n_estimators=200,
    max_depth=10,
    min_samples_split=5,
    min_samples_leaf=4,
    max_features='log2',
    criterion='entropy',
    class_weight='balanced',
)


def winsorize(df, features):
    """Capping outlier metode IQR. Mengembalikan df baru + batas per fitur."""
    bounds = {}
    for feat in features:
        if df[feat].nunique() <= 2:  # lewati fitur biner (hypertension)
            continue
        q1, q3 = df[feat].quantile(0.25), df[feat].quantile(0.75)
        iqr = q3 - q1
        low, up = q1 - 1.5 * iqr, q3 + 1.5 * iqr
        n_cap = int(((df[feat] < low) | (df[feat] > up)).sum())
        df[feat] = df[feat].clip(lower=low, upper=up)
        bounds[feat] = {'lower': float(low), 'upper': float(up), 'n_dicapping': n_cap}
        print(f"  {feat:<22}: {n_cap:>5} nilai di-cap ke [{low:.2f}, {up:.2f}]")
    return df, bounds


def train():
    script_dir = os.path.dirname(os.path.abspath(__file__))

    print("Downloading dataset...")
    path = kagglehub.dataset_download("iammustafatz/diabetes-prediction-dataset")
    csv_file = os.path.join(path, "diabetes_prediction_dataset.csv")

    print("Loading data...")
    df_raw = pd.read_csv(csv_file)

    print("Preprocessing (identik dengan notebook riset)...")
    # 1) Hapus duplikat pada dataset penuh -> menyisakan 96.146 baris
    df = df_raw.drop_duplicates().reset_index(drop=True)

    # 2) Ambil 5 fitur terpilih + target (gender & smoking_history tidak dipakai;
    #    ablation fitur ada di notebook 05)
    df = df[SELECTED_FEATURES + [TARGET]].copy()

    # 3) Winsorization
    print("Winsorization (capping outlier IQR):")
    df, winsor_bounds = winsorize(df, SELECTED_FEATURES)

    X = df[SELECTED_FEATURES]
    y = df[TARGET]

    print(f"\nData siap: {len(df):,} baris, {df[TARGET].mean()*100:.2f}% kelas positif")

    # 4) Split stratified 80:20
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=TEST_SIZE, random_state=RANDOM_STATE, stratify=y
    )
    print(f"Train: {len(X_train):,} | Test: {len(X_test):,}")

    # 5) Scaler di-fit pada data latih ASLI (sebelum SMOTE) supaya statistik
    #    mean/std mencerminkan distribusi nyata, konsisten dengan inferensi.
    print("Scaling features...")
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)

    # 6) SMOTE hanya pada data latih yang sudah discale
    print("Applying SMOTE (hanya pada data latih)...")
    smote = SMOTE(random_state=RANDOM_STATE)
    X_train_res, y_train_res = smote.fit_resample(X_train_scaled, y_train)
    print(f"  Setelah SMOTE: {len(X_train_res):,} baris "
          f"({int((y_train_res == 1).sum()):,} positif)")

    print("Training Random Forest (hyperparameter hasil tuning)...")
    rf = RandomForestClassifier(random_state=RANDOM_STATE, n_jobs=1, **PARAM_RF)
    rf.fit(X_train_res, y_train_res)

    # 7) Threshold optimal Youden's J pada data uji
    y_proba = rf.predict_proba(X_test_scaled)[:, 1]
    fpr, tpr, thresholds = roc_curve(y_test, y_proba)
    threshold = float(thresholds[np.argmax(tpr - fpr)])
    y_pred = (y_proba >= threshold).astype(int)

    metrics = {
        'accuracy': float(accuracy_score(y_test, y_pred)),
        'precision': float(precision_score(y_test, y_pred, zero_division=0)),
        'recall': float(recall_score(y_test, y_pred, zero_division=0)),
        'f1': float(f1_score(y_test, y_pred, zero_division=0)),
        'roc_auc': float(roc_auc_score(y_test, y_proba)),
    }

    print(f"\nThreshold optimal (Youden's J): {threshold:.4f}")
    for k, v in metrics.items():
        print(f"  {k:<10}: {v:.4f}")

    print("\nExporting model, scaler, dan metadata...")
    model_path = os.path.join(script_dir, 'rf_model.pkl')
    scaler_path = os.path.join(script_dir, 'scaler.pkl')

    with open(model_path, 'wb') as f:
        pickle.dump(rf, f, protocol=4)
    with open(scaler_path, 'wb') as f:
        pickle.dump(scaler, f, protocol=4)

    metadata = {
        'versi_model': 'v3-revisi-' + datetime.now().strftime('%Y%m%d'),
        'tanggal_latih': datetime.now().isoformat(timespec='seconds'),
        'algoritma': 'RandomForestClassifier',
        'hyperparameter': PARAM_RF,
        # Kontrak urutan fitur - dibaca predict.py & ml-service/main.py
        'feature_order': SELECTED_FEATURES,
        'feature_labels': ['Usia', 'BMI', 'Hipertensi', 'HbA1c', 'Kadar Glukosa'],
        'threshold': threshold,
        'rasio_split': {'train': 1 - TEST_SIZE, 'test': TEST_SIZE},
        'preprocessing': {
            'drop_duplicates': True,
            'winsorization': 'IQR 1.5x pada fitur numerik',
            'scaler': 'StandardScaler (fit pada data latih asli)',
            'resampling': 'SMOTE (hanya data latih)',
        },
        'winsorization': winsor_bounds,
        'metrik_test': metrics,
        'ukuran_model_mb': round(os.path.getsize(model_path) / (1024 * 1024), 2),
        'versi_library': {
            'scikit-learn': sklearn.__version__,
            'numpy': np.__version__,
            'pandas': pd.__version__,
        },
    }

    with open(os.path.join(script_dir, 'model_metadata.json'), 'w', encoding='utf-8') as f:
        json.dump(metadata, f, indent=2, ensure_ascii=False)

    print(f"Selesai. Ukuran model: {metadata['ukuran_model_mb']} MB")
    print("File: rf_model.pkl, scaler.pkl, model_metadata.json")


if __name__ == "__main__":
    train()
