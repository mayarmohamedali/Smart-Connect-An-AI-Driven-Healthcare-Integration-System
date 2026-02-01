#dataset 
print("Python is working 🎉")
# %%
# %% [1] Imports
import pandas as pd
import numpy as np
import seaborn as sns
import matplotlib.pyplot as plt

# Preprocessing tools
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.impute import SimpleImputer

# Handling Imbalance
from imblearn.over_sampling import SMOTE

# Modeling and Metrics
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score

# Configuration to show all columns
pd.set_option('display.max_columns', None)
print("Libraries loaded successfully.")
# %%
