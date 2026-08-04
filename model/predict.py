"""
DiaPredict - inferensi lokal (mode 'exec').

Dipanggil oleh AnalysisController::predictViaExec dengan urutan ARGUMEN CLI:
    predict.py <age> <hypertension> <bmi> <hba1c_level> <blood_glucose_level>

PENTING (perbaikan bug revisi V3):
Urutan argumen CLI di atas TIDAK sama dengan urutan FITUR saat training
(['age', 'bmi', 'hypertension', 'HbA1c_level', 'blood_glucose_level']).
Versi sebelumnya meneruskan argumen CLI apa adanya ke scaler/model sehingga
kolom BMI dan Hipertensi tertukar pada setiap prediksi. Sekarang vektor fitur
disusun eksplisit berdasarkan `feature_order` dari model_metadata.json.
"""
import sys
import json
import os
import warnings

# Prevent some libraries from trying to use multi-threading/networking features that cause WinError 10106
os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["OMP_NUM_THREADS"] = "1"

# Suppress scikit-learn warnings about feature names
warnings.filterwarnings("ignore", category=UserWarning)

# Urutan fitur saat training (fallback bila model_metadata.json belum ada).
DEFAULT_FEATURE_ORDER = ["age", "bmi", "hypertension", "HbA1c_level", "blood_glucose_level"]
DEFAULT_THRESHOLD = 0.4965


def load_metadata(script_dir):
    """Baca model_metadata.json (dihasilkan notebook 06). Aman bila belum ada."""
    meta_path = os.path.join(script_dir, "model_metadata.json")
    if not os.path.exists(meta_path):
        return {}
    try:
        with open(meta_path, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {}


def read_threshold(metadata):
    """
    Ambil ambang keputusan dari metadata.

    Notebook 06 menulis `threshold` sebagai objek {"nilai": ..., "metode": ...},
    sedangkan metadata versi lama menulisnya sebagai angka biasa. Keduanya diterima
    supaya pergantian artefak model tidak memutus inferensi.
    """
    raw = metadata.get("threshold", DEFAULT_THRESHOLD)
    if isinstance(raw, dict):
        raw = raw.get("nilai", DEFAULT_THRESHOLD)
    try:
        return float(raw)
    except (TypeError, ValueError):
        return DEFAULT_THRESHOLD


def read_winsorization(metadata):
    """
    Ambil batas capping outlier. Notebook 06 menyimpannya di
    preprocessing.winsorization dengan kunci batas_bawah/batas_atas/dicapping;
    metadata versi lama memakai bentuk datar dengan kunci lower/upper.
    """
    bounds = metadata.get("preprocessing", {}).get("winsorization") or metadata.get("winsorization")
    if not isinstance(bounds, dict):
        return {}

    hasil = {}
    for name, b in bounds.items():
        if not isinstance(b, dict):
            continue
        # Fitur biner (mis. hypertension) ditandai dicapping=false: jangan di-clip.
        if b.get("dicapping") is False:
            continue
        low = b.get("batas_bawah", b.get("lower"))
        up = b.get("batas_atas", b.get("upper"))
        if low is None and up is None:
            continue
        hasil[name] = {"lower": low, "upper": up}
    return hasil


def build_feature_vector(values_by_name, feature_order, winsorization=None):
    """
    Susun vektor fitur sesuai urutan training, bukan sesuai urutan argumen CLI.
    Bila metadata menyertakan batas winsorization, nilai input ikut di-clip
    supaya konsisten dengan preprocessing saat training.
    """
    row = []
    for name in feature_order:
        if name not in values_by_name:
            raise ValueError(f"Fitur '{name}' tidak tersedia dari input.")
        value = float(values_by_name[name])
        bounds = (winsorization or {}).get(name)
        if bounds:
            if bounds.get("lower") is not None:
                value = max(value, float(bounds["lower"]))
            if bounds.get("upper") is not None:
                value = min(value, float(bounds["upper"]))
        row.append(value)
    return [row]


def predict_all_models(script_dir, metadata, input_scaled, fallback=None):
    """
    Jalankan seluruh model yang terdaftar di metadata["models"] pada input yang
    SUDAH di-scale, lalu kembalikan hasil per model.

    Tiap model memakai AMBANG-nya sendiri: RF 0.4621, KNN 0.3810, SVM 0.4951.
    Memakai satu ambang untuk ketiganya akan membuat KNN tampak lebih buruk
    daripada kenyataannya, karena ambang optimalnya memang jauh lebih rendah.

    Berkas model yang tidak ditemukan dilewati tanpa menggagalkan prediksi utama,
    supaya penambahan/pengurangan artefak tidak pernah membuat sistem berhenti.
    """
    import pickle

    daftar = metadata.get("models")
    if not isinstance(daftar, list) or not daftar:
        if fallback:
            kunci, nama, prob, thr = fallback
            return {kunci: {"nama": nama, "probability": prob,
                            "prediction": int(prob >= thr), "threshold": thr,
                            "produksi": True}}
        return {}

    hasil = {}
    for entri in daftar:
        kunci = entri.get("id")
        berkas = entri.get("berkas")
        if not kunci or not berkas:
            continue

        path = os.path.join(script_dir, berkas)
        if not os.path.exists(path):
            continue

        try:
            with open(path, "rb") as f:
                clf = pickle.load(f)
            prob = float(clf.predict_proba(input_scaled)[0][1])
        except Exception:
            continue  # satu model bermasalah tidak boleh menjatuhkan yang lain

        thr = float(entri.get("threshold", DEFAULT_THRESHOLD))
        item = {
            "nama": entri.get("nama", kunci.upper()),
            "probability": prob,
            "prediction": int(prob >= thr),
            "threshold": thr,
            "produksi": bool(entri.get("produksi", False)),
        }

        # KNN: probabilitas adalah proporsi tetangga positif, jadi hanya bisa
        # bernilai kelipatan 1/k. Sertakan bentuk aslinya supaya tampilan tidak
        # memberi kesan presisi desimal yang sebenarnya tidak ada.
        k = (entri.get("kuantisasi") or {}).get("k") or (entri.get("hyperparameter") or {}).get("n_neighbors")
        if k:
            item["k"] = int(k)
            item["tetangga_positif"] = int(round(prob * int(k)))

        metrik = entri.get("metrik_test") or {}
        if metrik:
            item["metrik_test"] = metrik

        hasil[kunci] = item

    return hasil


def predict():
    try:
        # Check if we have the correct number of arguments (5 features + script name)
        if len(sys.argv) != 6:
            print(json.dumps({"error": "Invalid number of arguments. Expected 5."}))
            sys.exit(1)

        # Parse inputs - urutan CLI: age, hypertension, bmi, hba1c, glucose
        values_by_name = {
            "age": float(sys.argv[1]),
            "hypertension": float(sys.argv[2]),
            "bmi": float(sys.argv[3]),
            "HbA1c_level": float(sys.argv[4]),
            "blood_glucose_level": float(sys.argv[5]),
        }

        script_dir = os.path.dirname(os.path.abspath(__file__))
        metadata = load_metadata(script_dir)

        feature_order = metadata.get("feature_order", DEFAULT_FEATURE_ORDER)
        threshold = read_threshold(metadata)
        winsorization = read_winsorization(metadata)

        # Susun input SESUAI urutan fitur training (memperbaiki bug bmi <-> hypertension)
        input_data = build_feature_vector(values_by_name, feature_order, winsorization)

        # Check if model files exist
        model_path = os.path.join(script_dir, 'rf_model.pkl')
        scaler_path = os.path.join(script_dir, 'scaler.pkl')

        if not os.path.exists(model_path) or not os.path.exists(scaler_path):
             print(json.dumps({"error": "Model files not found. Please train the model first."}))
             sys.exit(1)

        # Load model and scaler using pickle
        import pickle
        with open(model_path, 'rb') as f:
            rf = pickle.load(f)
        with open(scaler_path, 'rb') as f:
            scaler = pickle.load(f)

        # Scale input. Ketiga model memakai scaler yang sama - sudah diverifikasi
        # mean & scale-nya identik saat artefak diekspor dari notebook gabungan.
        input_scaled = scaler.transform(input_data)

        # Predict (model produksi)
        probabilities = rf.predict_proba(input_scaled)[0]
        prob_diabetes = probabilities[1]

        final_prediction = 1 if prob_diabetes >= threshold else 0

        # Output JSON. Field lama dipertahankan apa adanya supaya controller
        # yang sudah ada tidak perlu berubah; blok "models" dan "konsensus"
        # adalah tambahan untuk panel perbandingan di halaman detail.
        result = {
            "prediction": int(final_prediction),
            "probability": float(prob_diabetes),
            "raw_probability": float(prob_diabetes),
            "threshold": threshold,
            "model_version": metadata.get("versi_model", "legacy"),
        }

        models = predict_all_models(
            script_dir, metadata, input_scaled,
            fallback=("rf", "Random Forest", float(prob_diabetes), threshold),
        )
        if models:
            result["models"] = models
            setuju = sum(1 for m in models.values() if m["prediction"] == final_prediction)
            result["konsensus"] = {
                "setuju": setuju,
                "total": len(models),
                "bulat": setuju == len(models),
                "label": int(final_prediction),
            }

        print(json.dumps(result))

    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    predict()
