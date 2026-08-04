# syntax=docker/dockerfile:1
# =============================================================================
# DiaPredict ML microservice — FastAPI + scikit-learn
# Deploy sebagai SERVICE TERPISAH di Railway.
#   Build context: root repo (agar bisa menyalin folder model/)
#   Railway: Root Directory = "/", Dockerfile Path = "ml-service/Dockerfile"
# =============================================================================
FROM python:3.13-slim

WORKDIR /app

# Dependency Python
COPY ml-service/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Artefak model + kode service.
# model_metadata.json WAJIB ikut: berkas itu yang mengunci urutan fitur saat inferensi
# (mencegah bug tertukarnya BMI dan Hipertensi), memuat threshold & batas winsorization,
# serta daftar `models` yang menentukan model pembanding mana yang dimuat saat boot.
# knn_model.pkl & svm_model.pkl dipakai panel perbandingan di halaman detail analisis;
# bila keduanya dihapus, layanan tetap jalan dengan model produksi saja.
COPY model/rf_model.pkl model/knn_model.pkl model/svm_model.pkl \
     model/scaler.pkl model/model_metadata.json ./model/
COPY ml-service/main.py .

ENV MODEL_DIR=/app/model
EXPOSE 8000

# Railway menyuntikkan $PORT; default 8000 untuk lokal.
CMD ["sh", "-c", "uvicorn main:app --host 0.0.0.0 --port ${PORT:-8000}"]
