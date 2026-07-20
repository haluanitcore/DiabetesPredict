import os
import numpy as np
import pandas as pd
import kagglehub
import pickle
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
from imblearn.over_sampling import SMOTE

def train():
    print("Downloading dataset...")
    path = kagglehub.dataset_download("iammustafatz/diabetes-prediction-dataset")
    csv_file = os.path.join(path, "diabetes_prediction_dataset.csv")
    
    print("Loading data...")
    df = pd.read_csv(csv_file)
    
    print("Preprocessing...")
    # Drop duplicates
    df = df.drop_duplicates()
    
    # Filter valid gender
    df = df[df['gender'] != 'Other']
    
    # Encode categorical variables
    gender_map = {'Female': 0, 'Male': 1}
    df['gender'] = df['gender'].map(gender_map)
    
    smoking_map = {'No Info': 0, 'never': 1, 'former': 2, 'current': 3, 'not current': 4, 'ever': 5}
    df['smoking_history'] = df['smoking_history'].map(smoking_map)
    
    # Separate features and target
    selected_features = ['age', 'bmi', 'hypertension', 'HbA1c_level', 'blood_glucose_level']
    X = df[selected_features]
    y = df['diabetes']
    
    # Train-test split
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)
    
    print("Applying SMOTE...")
    smote = SMOTE(random_state=42)
    X_train_res, y_train_res = smote.fit_resample(X_train, y_train)
    
    print("Scaling features...")
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train_res)
    
    print("Training Random Forest model...")
    # Using parameters close to what was tuned in the notebook. Set n_jobs=1 for Windows compatibility.
    rf = RandomForestClassifier(n_estimators=100, random_state=42, class_weight='balanced', n_jobs=1)
    rf.fit(X_train_scaled, y_train_res)
    
    print("Exporting model and scaler...")
    # Ensure model directory exists relative to this script
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # Save model and scaler using pickle (avoids joblib/asyncio issues on Windows)
    with open(os.path.join(script_dir, 'rf_model.pkl'), 'wb') as f:
        pickle.dump(rf, f)
    with open(os.path.join(script_dir, 'scaler.pkl'), 'wb') as f:
        pickle.dump(scaler, f)
        
    print("Model and scaler saved successfully using pickle!")

if __name__ == "__main__":
    train()
