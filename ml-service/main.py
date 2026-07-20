"""
DiaPredict ML microservice (FastAPI).

Model & scaler di-load SEKALI saat startup (bukan tiap request seperti mode exec),
sehingga prediksi jauh lebih cepat (<100 ms) dan hemat CPU.

Endpoint:
  GET  /health   -> status + apakah model termuat
  POST /predict  -> {age, hypertension, bmi, hba1c_level, blood_glucose_level}
                    -> {prediction, probability, raw_probability}
"""
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
THRESHOLD = float(os.environ.get("PREDICT_THRESHOLD", "0.4965"))  # dari notebook

_state = {"model": None, "scaler": None}


def _load_artifacts():
    model_path = os.path.join(MODEL_DIR, "rf_model.pkl")
    scaler_path = os.path.join(MODEL_DIR, "scaler.pkl")
    if not os.path.exists(model_path) or not os.path.exists(scaler_path):
        raise RuntimeError(f"Model files tidak ditemukan di {MODEL_DIR}")
    with open(model_path, "rb") as f:
        _state["model"] = pickle.load(f)
    with open(scaler_path, "rb") as f:
        _state["scaler"] = pickle.load(f)


@asynccontextmanager
async def lifespan(app: FastAPI):
    _load_artifacts()  # muat sekali saat boot
    yield
    _state["model"] = None
    _state["scaler"] = None


app = FastAPI(title="DiaPredict ML Service", version="1.0.0", lifespan=lifespan)


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


@app.get("/health")
def health():
    ok = _state["model"] is not None and _state["scaler"] is not None
    return {"status": "ok" if ok else "degraded", "model_loaded": ok}


@app.post("/predict", response_model=PredictResponse)
def predict(req: PredictRequest):
    if _state["model"] is None or _state["scaler"] is None:
        raise HTTPException(status_code=503, detail="Model belum termuat")

    # Urutan fitur harus SAMA dengan saat training: [age, hypertension, bmi, hba1c, glucose]
    features = np.array([[req.age, req.hypertension, req.bmi,
                          req.hba1c_level, req.blood_glucose_level]], dtype=float)
    try:
        scaled = _state["scaler"].transform(features)
        probs = _state["model"].predict_proba(scaled)[0]
    except Exception as exc:  # noqa: BLE001
        raise HTTPException(status_code=500, detail=f"Gagal prediksi: {exc}")

    prob_diabetes = float(probs[1])
    final = 1 if prob_diabetes >= THRESHOLD else 0
    return PredictResponse(
        prediction=final,
        probability=prob_diabetes,
        raw_probability=prob_diabetes,
    )
