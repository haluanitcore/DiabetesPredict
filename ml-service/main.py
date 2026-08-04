"""
DiaPredict ML microservice (FastAPI).

Model & scaler di-load SEKALI saat startup (bukan tiap request seperti mode exec),
sehingga prediksi jauh lebih cepat (<100 ms) dan hemat CPU.

Endpoint:
  GET  /health   -> status + apakah model termuat + versi model
  POST /predict  -> {age, hypertension, bmi, hba1c_level, blood_glucose_level}
                    -> {prediction, probability, raw_probability, threshold, model_version}

PENTING (perbaikan bug revisi V3):
Urutan field pada request TIDAK sama dengan urutan fitur saat training
(['age', 'bmi', 'hypertension', 'HbA1c_level', 'blood_glucose_level']).
Versi sebelumnya menyusun array sebagai [age, hypertension, bmi, hba1c, glucose]
sehingga BMI dan Hipertensi tertukar. Sekarang vektor fitur disusun berdasarkan
`feature_order` dari model_metadata.json (dengan fallback ke urutan training).
"""
import json
import os
import pickle
import warnings
from contextlib import asynccontextmanager

import numpy as np
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

warnings.filterwarnings("ignore", category=UserWarning)

# Batasi threading agar stabil di container kecil
os.environ.setdefault("OPENBLAS_NUM_THREADS", "1")
os.environ.setdefault("MKL_NUM_THREADS", "1")
os.environ.setdefault("OMP_NUM_THREADS", "1")

MODEL_DIR = os.environ.get("MODEL_DIR", os.path.join(os.path.dirname(__file__), "..", "model"))

# Urutan fitur saat training (fallback bila model_metadata.json belum ada)
DEFAULT_FEATURE_ORDER = ["age", "bmi", "hypertension", "HbA1c_level", "blood_glucose_level"]
DEFAULT_THRESHOLD = float(os.environ.get("PREDICT_THRESHOLD", "0.4965"))  # dari notebook

_state = {"model": None, "scaler": None, "metadata": {}}


def _load_metadata():
    meta_path = os.path.join(MODEL_DIR, "model_metadata.json")
    if not os.path.exists(meta_path):
        return {}
    try:
        with open(meta_path, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {}


def _read_threshold(meta):
    """
    Notebook 06 menulis `threshold` sebagai objek {"nilai": ...}; metadata versi
    lama menulisnya sebagai angka. Keduanya diterima agar pergantian artefak model
    tidak memutus layanan.
    """
    raw = meta.get("threshold", DEFAULT_THRESHOLD)
    if isinstance(raw, dict):
        raw = raw.get("nilai", DEFAULT_THRESHOLD)
    try:
        return float(raw)
    except (TypeError, ValueError):
        return DEFAULT_THRESHOLD


def _read_winsorization(meta):
    """
    Batas capping outlier. Bentuk baru: preprocessing.winsorization dengan kunci
    batas_bawah/batas_atas/dicapping. Bentuk lama: winsorization dengan lower/upper.
    """
    bounds = meta.get("preprocessing", {}).get("winsorization") or meta.get("winsorization")
    if not isinstance(bounds, dict):
        return {}

    hasil = {}
    for name, b in bounds.items():
        if not isinstance(b, dict) or b.get("dicapping") is False:
            continue  # fitur biner tidak di-clip
        low = b.get("batas_bawah", b.get("lower"))
        up = b.get("batas_atas", b.get("upper"))
        if low is None and up is None:
            continue
        hasil[name] = {"lower": low, "upper": up}
    return hasil


def _load_artifacts():
    model_path = os.path.join(MODEL_DIR, "rf_model.pkl")
    scaler_path = os.path.join(MODEL_DIR, "scaler.pkl")
    if not os.path.exists(model_path) or not os.path.exists(scaler_path):
        raise RuntimeError(f"Model files tidak ditemukan di {MODEL_DIR}")
    with open(model_path, "rb") as f:
        _state["model"] = pickle.load(f)
    with open(scaler_path, "rb") as f:
        _state["scaler"] = pickle.load(f)
    _state["metadata"] = _load_metadata()
    _load_pembanding(_state["metadata"])


def _load_pembanding(meta):
    """
    Muat model pembanding (KNN, SVM) sekali saat boot, sama seperti model produksi.
    Ketiganya berbagi scaler.pkl yang sama - sudah diverifikasi mean & scale-nya
    identik saat artefak diekspor dari notebook gabungan.

    Model yang berkasnya tidak ada dilewati diam-diam: layanan harus tetap hidup
    dengan model produksi saja bila artefak pembanding belum ikut ter-deploy.
    """
    _state["models"] = {}
    for entri in meta.get("models", []) or []:
        kunci, berkas = entri.get("id"), entri.get("berkas")
        if not kunci or not berkas:
            continue
        if kunci == "rf":
            _state["models"][kunci] = {"clf": _state["model"], "meta": entri}
            continue
        path = os.path.join(MODEL_DIR, berkas)
        if not os.path.exists(path):
            continue
        try:
            with open(path, "rb") as f:
                _state["models"][kunci] = {"clf": pickle.load(f), "meta": entri}
        except Exception:
            continue


def _prediksi_semua(scaled):
    """Jalankan tiap model dengan AMBANGNYA SENDIRI (RF 0.4621, KNN 0.3810, SVM 0.4951)."""
    hasil = {}
    for kunci, isi in (_state.get("models") or {}).items():
        entri = isi["meta"]
        try:
            prob = float(isi["clf"].predict_proba(scaled)[0][1])
        except Exception:
            continue
        thr = float(entri.get("threshold", DEFAULT_THRESHOLD))
        item = {
            "nama": entri.get("nama", kunci.upper()),
            "probability": prob,
            "prediction": int(prob >= thr),
            "threshold": thr,
            "produksi": bool(entri.get("produksi", False)),
        }
        k = (entri.get("kuantisasi") or {}).get("k") or (entri.get("hyperparameter") or {}).get("n_neighbors")
        if k:
            # Probabilitas KNN adalah proporsi tetangga positif; bentuk pecahannya
            # lebih jujur daripada desimal karena hanya ada k+1 nilai yang mungkin.
            item["k"] = int(k)
            item["tetangga_positif"] = int(round(prob * int(k)))
        if entri.get("metrik_test"):
            item["metrik_test"] = entri["metrik_test"]
        hasil[kunci] = item
    return hasil


@asynccontextmanager
async def lifespan(app: FastAPI):
    _load_artifacts()  # muat sekali saat boot
    yield
    _state["model"] = None
    _state["scaler"] = None
    _state["metadata"] = {}
    _state["models"] = {}


app = FastAPI(title="DiaPredict ML Service", version="2.0.0", lifespan=lifespan)


class PredictRequest(BaseModel):
    age: float = Field(..., ge=0, le=120)
    hypertension: int = Field(..., ge=0, le=1)
    bmi: float = Field(..., ge=10, le=100)
    hba1c_level: float = Field(..., ge=3, le=20)
    blood_glucose_level: float = Field(..., ge=50, le=500)


class PredictResponse(BaseModel):
    prediction: int
    probability: float
    raw_probability: float
    threshold: float
    model_version: str
    # Tambahan untuk panel perbandingan di halaman detail. Dibuat opsional supaya
    # klien lama yang hanya membaca field di atas tetap berfungsi.
    models: dict | None = None
    konsensus: dict | None = None


@app.get("/health")
def health():
    ok = _state["model"] is not None and _state["scaler"] is not None
    meta = _state["metadata"]
    return {
        "status": "ok" if ok else "degraded",
        "model_loaded": ok,
        "model_version": meta.get("versi_model", "legacy"),
        "feature_order": meta.get("feature_order", DEFAULT_FEATURE_ORDER),
        "threshold": _read_threshold(meta),
    }


def _build_features(req: PredictRequest, feature_order, winsorization=None):
    """Susun vektor fitur SESUAI urutan training, bukan urutan field request."""
    values_by_name = {
        "age": float(req.age),
        "bmi": float(req.bmi),
        "hypertension": float(req.hypertension),
        "HbA1c_level": float(req.hba1c_level),
        "blood_glucose_level": float(req.blood_glucose_level),
    }
    row = []
    for name in feature_order:
        if name not in values_by_name:
            raise ValueError(f"Fitur '{name}' tidak tersedia dari request.")
        value = values_by_name[name]
        bounds = (winsorization or {}).get(name)
        if bounds:
            if bounds.get("lower") is not None:
                value = max(value, float(bounds["lower"]))
            if bounds.get("upper") is not None:
                value = min(value, float(bounds["upper"]))
        row.append(value)
    return np.array([row], dtype=float)


@app.post("/predict", response_model=PredictResponse)
def predict(req: PredictRequest):
    if _state["model"] is None or _state["scaler"] is None:
        raise HTTPException(status_code=503, detail="Model belum termuat")

    meta = _state["metadata"]
    feature_order = meta.get("feature_order", DEFAULT_FEATURE_ORDER)
    threshold = _read_threshold(meta)

    try:
        features = _build_features(req, feature_order, _read_winsorization(meta))
        scaled = _state["scaler"].transform(features)
        probs = _state["model"].predict_proba(scaled)[0]
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=500, detail=f"Gagal prediksi: {exc}")

    prob_diabetes = float(probs[1])
    final = 1 if prob_diabetes >= threshold else 0

    models = _prediksi_semua(scaled)
    konsensus = None
    if models:
        setuju = sum(1 for m in models.values() if m["prediction"] == final)
        konsensus = {"setuju": setuju, "total": len(models),
                     "bulat": setuju == len(models), "label": final}

    return PredictResponse(
        prediction=final,
        probability=prob_diabetes,
        raw_probability=prob_diabetes,
        threshold=threshold,
        model_version=str(meta.get("versi_model", "legacy")),
        models=models or None,
        konsensus=konsensus,
    )
