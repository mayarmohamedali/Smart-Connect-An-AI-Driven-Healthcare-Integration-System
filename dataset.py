#dataset 
# %% [1] Imports (Updated to bypass broken library)
import pandas as pd
import numpy as np
import seaborn as sns
import matplotlib.pyplot as plt

# Preprocessing tools
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.impute import SimpleImputer

# Modeling (We will handle imbalance inside the model)
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score

print("Libraries loaded. (Proceeding with internal Class Weight balancing)")
# %%
# %% [2] Load & Clean Data
# Load the dataset
# NOTE: Ensure the file name matches exactly what is in your folder
df = pd.read_csv('raian1000csv.csv')

# 1. Drop Identifiers (They confuse the model)
df = df.drop(columns=['ID', 'Name'], errors='ignore')

# 2. Clean Target (Diagnosis)
# Remove rows where Diagnosis is missing (we can't train on nothing)
df = df.dropna(subset=['Diagnosis'])
# Standardize text: "Flu " -> "flu", "Stoke" -> "stroke"
df['Diagnosis'] = df['Diagnosis'].astype(str).str.lower().str.strip()

# 3. Feature Engineering: Extract Month for Epidemic Prediction
# We need to know WHICH month a disease happens to predict outbreaks
df['checkin date'] = pd.to_datetime(df['checkin date'], errors='coerce')
df['Admission_Month'] = df['checkin date'].dt.month

# Drop original dates now that we have the month
df = df.drop(columns=['checkin date', 'checkout date'], errors='ignore')

# 4. Force Numeric Types
# Sometimes Excel saves numbers as text. We force them to be numbers.
for col in df.columns:
    if col != 'Diagnosis':
        df[col] = pd.to_numeric(df[col], errors='coerce')

print(f"Data Loaded & Cleaned. Rows: {df.shape[0]}, Columns: {df.shape[1]}")
print("Unique Diagnoses:", df['Diagnosis'].nunique())

# %%
