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

def predict():
    try:
        # Check if we have the correct number of arguments (5 features + script name)
        if len(sys.argv) != 6:
            print(json.dumps({"error": "Invalid number of arguments. Expected 5."}))
            sys.exit(1)

        # Parse inputs
        age = float(sys.argv[1])
        hypertension = float(sys.argv[2])
        bmi = float(sys.argv[3])
        hba1c_level = float(sys.argv[4])
        blood_glucose_level = float(sys.argv[5])

        # Create input array
        input_data = [[age, hypertension, bmi, hba1c_level, blood_glucose_level]]

        script_dir = os.path.dirname(os.path.abspath(__file__))
        
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

        # Scale input
        input_scaled = scaler.transform(input_data)

        # Predict
        prediction = rf.predict(input_scaled)[0]
        probabilities = rf.predict_proba(input_scaled)[0]
        
        # Calculate optimal threshold (from notebook it was 0.4965)
        threshold = 0.4965
        prob_diabetes = probabilities[1]
        
        final_prediction = 1 if prob_diabetes >= threshold else 0

        # Output JSON
        result = {
            "prediction": int(final_prediction),
            "probability": float(prob_diabetes),
            "raw_probability": float(probabilities[1])
        }
        
        print(json.dumps(result))

    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    predict()
