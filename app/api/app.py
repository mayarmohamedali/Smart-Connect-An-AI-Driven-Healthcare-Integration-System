# app.py — SmartConnect ML API
# Serves:
#   Patient   → disease prediction from medical record
#   Hospital  → epidemic / dominant disease forecast
#   Insurance → next-year coverage increase forecast
#
# Run (dev):  py app.py
# Run (prod): gunicorn app:app -b 0.0.0.0:5000 -w 4

import os, json, datetime, traceback, warnings, math
import numpy  as np
import pandas as pd
import joblib
import pymysql
from flask import Flask, request, jsonify
from flask_cors import CORS

warnings.filterwarnings("ignore")
app = Flask(__name__)
CORS(app)


# ═══════════════════════════════════════════════════════════════════════════════
#  PATHS
# ═══════════════════════════════════════════════════════════════════════════════
BASE_DIR            = os.path.dirname(__file__)
_MODELS_DIR         = os.path.join(BASE_DIR, "..", "models")
HOSPITAL_MODEL_DIR  = os.path.join(_MODELS_DIR, "hospital_model_artifacts")
INSURANCE_MODEL_DIR = os.path.join(_MODELS_DIR, "insurance_model_artifacts")

# ═══════════════════════════════════════════════════════════════════════════════
#  SHARED HELPERS
# ═══════════════════════════════════════════════════════════════════════════════
def sanitize_for_json(obj):
    """Replace NaN/Inf with None so JSON output is always valid."""
    if isinstance(obj, float):
        if math.isnan(obj) or math.isinf(obj):
            return None
    if isinstance(obj, dict):
        return {k: sanitize_for_json(v) for k, v in obj.items()}
    if isinstance(obj, list):
        return [sanitize_for_json(v) for v in obj]
    return obj


# ═══════════════════════════════════════════════════════════════════════════════
#  DATABASE — PATIENT PREDICTION
# ═══════════════════════════════════════════════════════════════════════════════
DB_CONFIG = {
    "host"       : "127.0.0.1",
    "user"       : "root",
    "password"   : "",
    "database"   : "smart_connect",
    "port"       : 3306,
    "cursorclass": pymysql.cursors.DictCursor,
}

def get_connection():
    return pymysql.connect(**DB_CONFIG)
def get_latest_patient_record(patient_id):
    sql = """
    SELECT
        p.patient_id, p.full_name, p.gender, p.insurance_id,
        mr.record_id, mr.age, mr.bmi, mr.glucose, mr.cholesterol_level,
        mr.systolic_bp, mr.blood_creatinine1, mr.blood_creatinine2,
        mr.admission_count, mr.length_of_stay, mr.smoking_status,
        mr.physical_activity_level, mr.diet_quality, mr.sleep_hours,
        mr.stress_level, mr.family_history, mr.medications_count,
        mr.risk_score, mr.symptom_burden, mr.fever, mr.cough, mr.fatigue,
        mr.chest_pain, mr.shortness_of_breath, mr.headache,
        mr.diagnosis, mr.disease_category, mr.created_at
    FROM patients p
    LEFT JOIN medical_records mr ON p.patient_id = mr.patient_id
    WHERE p.patient_id = %s
    ORDER BY mr.created_at DESC
    LIMIT 1
    """
    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            cursor.execute(sql, (patient_id,))
            return cursor.fetchone()
    finally:
        conn.close()

def build_prediction(record):
    if not record:
        return {"ok": False, "message": "No patient record found"}

    glucose     = float(record.get("glucose")              or 0)
    systolic_bp = float(record.get("systolic_bp")          or 0)
    creat1      = float(record.get("blood_creatinine1")    or 0)
    creat2      = float(record.get("blood_creatinine2")    or 0)
    bmi         = float(record.get("bmi")                  or 0)
    chest_pain  = int  (record.get("chest_pain")           or 0)
    shortness   = float(record.get("shortness_of_breath")  or 0)
    headache    = int  (record.get("headache")             or 0)
    fatigue     = float(record.get("fatigue")              or 0)
    fever       = float(record.get("fever")                or 0)
    cough       = float(record.get("cough")                or 0)

    active_alerts       = []
    predicted_disease   = "Healthy"
    disease_category    = "General"
    risk_level          = "LOW"
    short_term_measures = []
    long_term_measures  = []

    if creat2 >= 2.0 or creat1 >= 2.0:
        predicted_disease = "Kidney Disease"
        disease_category  = "Renal"
        risk_level        = "HIGH"
        active_alerts     = ["Severely elevated creatinine levels",
                             "Kidney function requires urgent follow-up"]
        short_term_measures = ["Repeat kidney function tests",
                               "Increase hydration if medically allowed",
                               "Avoid nephrotoxic medications without doctor advice"]
        long_term_measures  = ["Regular nephrology follow-up",
                               "Renal-friendly diet plan",
                               "Periodic creatinine and urea monitoring"]

    elif chest_pain == 1 or shortness >= 1:
        predicted_disease = "Heart Disease"
        disease_category  = "Cardiovascular"
        risk_level        = "HIGH"
        active_alerts     = ["Cardiovascular symptom pattern detected"]
        if chest_pain == 1: active_alerts.append("Chest pain reported")
        if shortness  >= 1: active_alerts.append("Shortness of breath reported")
        short_term_measures = ["Cardiology assessment",
                               "Monitor blood pressure closely",
                               "Avoid strenuous activity until reviewed"]
        long_term_measures  = ["Heart-healthy diet",
                               "Regular cardiac follow-up",
                               "Cholesterol and blood pressure control"]

    elif glucose >= 126:
        predicted_disease = "Diabetes Mellitus"
        disease_category  = "Metabolic"
        risk_level        = "HIGH" if glucose >= 200 else "MODERATE"
        active_alerts     = ["Elevated blood glucose detected"]
        if bmi >= 30: active_alerts.append("Obesity-related metabolic risk")
        short_term_measures = ["Monitor fasting blood glucose weekly",
                               "Reduce sugar and refined carbs",
                               "Walk 30 minutes daily"]
        long_term_measures  = ["Follow diabetic-friendly diet plan",
                               "Maintain healthy BMI",
                               "Regular endocrinologist follow-up"]

    elif systolic_bp >= 140:
        predicted_disease = "Hypertension"
        disease_category  = "Cardiovascular"
        risk_level        = "HIGH" if systolic_bp >= 180 else "MODERATE"
        active_alerts     = ["Elevated blood pressure detected"]
        short_term_measures = ["Reduce salt intake",
                               "Monitor blood pressure daily",
                               "Limit caffeine and stress"]
        long_term_measures  = ["Maintain healthy weight",
                               "Regular exercise",
                               "Routine blood pressure follow-up"]

    elif headache == 1:
        predicted_disease = "Headache"
        disease_category  = "Neurological"
        risk_level        = "LOW" if fever == 0 and cough == 0 else "MODERATE"
        active_alerts     = ["Headache symptom present"]
        short_term_measures = ["Hydrate well", "Reduce screen strain",
                               "Track headache frequency"]
        long_term_measures  = ["Improve sleep routine", "Manage stress",
                               "Neurology follow-up if recurrent"]

    elif fatigue >= 1 or fever >= 1 or cough >= 1:
        predicted_disease = "General Illness"
        disease_category  = "General"
        risk_level        = "LOW"
        active_alerts     = ["General symptom burden detected"]
        short_term_measures = ["Rest and hydrate", "Monitor symptoms",
                               "Seek medical advice if symptoms worsen"]
        long_term_measures  = ["Maintain healthy sleep schedule",
                               "Balanced diet", "Routine health follow-up"]

    else:
        predicted_disease   = "Healthy"
        disease_category    = "General"
        risk_level          = "LOW"
        short_term_measures = ["Continue healthy habits", "Stay hydrated",
                               "Maintain physical activity"]
        long_term_measures  = ["Routine annual checkups", "Balanced diet",
                               "Regular exercise"]

    return {
        "ok"                  : True,
        "patient_id"          : record["patient_id"],
        "predicted_disease"   : predicted_disease,
        "disease_category"    : disease_category,
        "risk_level"          : risk_level,
        "active_alerts"       : active_alerts,
        "short_term_measures" : short_term_measures,
        "long_term_measures"  : long_term_measures,
    }


# ═══════════════════════════════════════════════════════════════════════════════
#  HOSPITAL — EPIDEMIC / DOMINANT DISEASE FORECAST
# ═══════════════════════════════════════════════════════════════════════════════
MONTH_NAMES = {1:"Jan",2:"Feb",3:"Mar",4:"Apr",5:"May",6:"Jun",
               7:"Jul",8:"Aug",9:"Sep",10:"Oct",11:"Nov",12:"Dec"}

DISEASE_RECOMMENDATIONS = {
    "Asthma":                 ["Ensure respiratory unit is ready for increased breathing difficulty cases.",
                               "Make sure oxygen support devices are available in ER and wards.",
                               "Prepare staff to quickly triage and manage breathing emergencies."],
    "Cancer":                 ["Ensure oncology unit and inpatient beds are ready for increased admissions.",
                               "Prepare staff for higher need of continuous patient care and monitoring.",
                               "Maintain readiness of diagnostic and imaging services."],
    "Chronic_Kidney_Disease": ["Ensure dialysis unit readiness and machine availability.",
                               "Prepare monitoring for fluid and kidney-related complications.",
                               "Increase nephrology staff availability for consultations."],
    "Coronary_Artery_Disease":["Ensure cardiac unit readiness for chest pain emergencies.",
                               "Prepare emergency response teams for rapid intervention cases.",
                               "Maintain continuous monitoring equipment availability."],
    "Diabetes":               ["Ensure readiness for blood sugar emergencies and monitoring.",
                               "Prepare staff for metabolic complication management.",
                               "Increase availability of patient monitoring tools."],
    "Healthy":                ["Use this period for routine maintenance and system checks.",
                               "Schedule preventive care and staff training."],
    "Heart_Failure":          ["Ensure cardiac care unit is prepared for increased admissions.",
                               "Maintain readiness for fluid overload and breathing complications.",
                               "Ensure ICU availability for severe cases."],
    "Hypertension":           ["Ensure blood pressure monitoring is widely available.",
                               "Prepare staff for emergency high blood pressure cases.",
                               "Maintain readiness for cardiovascular complications."],
    "Pneumonia":              ["Ensure respiratory isolation and infection control readiness.",
                               "Prepare hospital for increased respiratory infection cases.",
                               "Maintain oxygen support and monitoring availability."],
    "Stroke":                 ["Ensure emergency stroke pathway is fully operational.",
                               "Prepare imaging and emergency response readiness.",
                               "Maintain ICU and monitoring bed availability."],
}

DISEASE_SEVERITY = {
    "Stroke":"critical","Heart_Failure":"critical","Coronary_Artery_Disease":"critical",
    "Cancer":"high","Chronic_Kidney_Disease":"high","Pneumonia":"high",
    "Asthma":"medium","Diabetes":"medium","Hypertension":"medium","Healthy":"low",
}

HISTORICAL_BASELINE = {
    1:  {"month_name":"Jan","dominant_disease":"Stroke",                "severity":"critical"},
    2:  {"month_name":"Feb","dominant_disease":"Hypertension",          "severity":"medium"},
    3:  {"month_name":"Mar","dominant_disease":"Diabetes",              "severity":"medium"},
    4:  {"month_name":"Apr","dominant_disease":"Heart_Failure",         "severity":"critical"},
    5:  {"month_name":"May","dominant_disease":"Asthma",                "severity":"medium"},
    6:  {"month_name":"Jun","dominant_disease":"Coronary_Artery_Disease","severity":"critical"},
    7:  {"month_name":"Jul","dominant_disease":"Cancer",                "severity":"high"},
    8:  {"month_name":"Aug","dominant_disease":"Pneumonia",             "severity":"high"},
    9:  {"month_name":"Sep","dominant_disease":"Cancer",                "severity":"high"},
    10: {"month_name":"Oct","dominant_disease":"Pneumonia",             "severity":"high"},
    11: {"month_name":"Nov","dominant_disease":"Chronic_Kidney_Disease","severity":"high"},
    12: {"month_name":"Dec","dominant_disease":"Heart_Failure",         "severity":"critical"},
}

def load_hospital_artifacts(model_dir=HOSPITAL_MODEL_DIR):
    model    = joblib.load(os.path.join(model_dir, "best_hospital_model.pkl"))
    scaler   = joblib.load(os.path.join(model_dir, "hospital_scaler.pkl"))
    le_tgt   = joblib.load(os.path.join(model_dir, "hospital_label_encoder_target.pkl"))
    le_feats = joblib.load(os.path.join(model_dir, "hospital_label_encoders_features.pkl"))
    with open(os.path.join(model_dir, "hospital_features.json")) as f:
        features = json.load(f)
    return model, scaler, le_tgt, le_feats, features

def preprocess_patient_batch(patient_list, scaler, le_feats, features):
    batch = pd.DataFrame(patient_list)
    for col in ["Fever", "Cough", "Shortness_of_Breath"]:
        if col in batch.columns:
            batch[col] = (pd.to_numeric(batch[col], errors="coerce")
                            .fillna(0).gt(0.5).astype(int))
    for col, le in le_feats.items():
        if col in batch.columns:
            batch[col] = batch[col].astype(str).apply(
                lambda v: le.transform([v])[0] if v in le.classes_
                          else le.transform([le.classes_[0]])[0])
    for col in features:
        if col not in batch.columns:
            batch[col] = 0
    batch    = batch[features]
    num_cols = batch.select_dtypes(include=[np.number]).columns.tolist()
    batch[num_cols] = scaler.transform(batch[num_cols])
    return batch

def predict_next_month_dominant_disease(patient_list, _artifacts, min_patients=5):
    model, scaler, le_tgt, le_feats, features = _artifacts
    today           = datetime.date.today()
    next_month_n    = today.month % 12 + 1
    next_month_name = MONTH_NAMES[next_month_n]

    if len(patient_list) < min_patients:
        hist = HISTORICAL_BASELINE.get(next_month_n, {})
        return {
            "status"           : "insufficient_data",
            "message"          : f"Need >={min_patients} patients, got {len(patient_list)}.",
            "total_patients"   : len(patient_list),
            "next_month"       : next_month_name,
            "dominant_disease" : hist.get("dominant_disease"),
            "dominant_display" : hist.get("dominant_disease", "").replace("_", " "),
            "recommendations"  : DISEASE_RECOMMENDATIONS.get(hist.get("dominant_disease", ""), []),
            "severity"         : hist.get("severity", "medium"),
            "distribution"     : {},
            "distribution_pct" : {},
            "source"           : "historical_fallback",
        }

    try:
        X_batch   = preprocess_patient_batch(patient_list, scaler, le_feats, features)
        preds_enc = model.predict(X_batch)
        preds_dis = le_tgt.inverse_transform(preds_enc)
        counts    = pd.Series(preds_dis).value_counts()
        pct       = (counts / len(preds_dis) * 100).round(1)
        dominant  = str(counts.index[0])
        return {
            "status"           : "ok",
            "message"          : f"Forecast for {next_month_name} — {len(patient_list)} patients.",
            "total_patients"   : len(patient_list),
            "next_month"       : next_month_name,
            "dominant_disease" : dominant,
            "dominant_display" : dominant.replace("_", " "),
            "recommendations"  : DISEASE_RECOMMENDATIONS.get(dominant, ["General preparedness recommended."]),
            "severity"         : DISEASE_SEVERITY.get(dominant, "medium"),
            "distribution"     : {str(k): int(v)   for k, v in counts.items()},
            "distribution_pct" : {str(k): float(v) for k, v in pct.items()},
            "source"           : "live_model",
        }
    except Exception as ex:
        hist = HISTORICAL_BASELINE.get(next_month_n, {})
        return {
            "status"           : "error",
            "message"          : str(ex),
            "next_month"       : next_month_name,
            "dominant_disease" : hist.get("dominant_disease"),
            "dominant_display" : hist.get("dominant_disease", "").replace("_", " "),
            "recommendations"  : DISEASE_RECOMMENDATIONS.get(hist.get("dominant_disease", ""), []),
            "severity"         : hist.get("severity", "medium"),
            "distribution"     : {},
            "distribution_pct" : {},
            "source"           : "historical_fallback",
        }

try:
    _HOSP_ARTIFACTS = load_hospital_artifacts()
    print("✅ Hospital model loaded")
except Exception as e:
    _HOSP_ARTIFACTS = None
    print(f"⚠️  Hospital model load failed: {e}")


# ═══════════════════════════════════════════════════════════════════════════════
#  INSURANCE — COVERAGE INCREASE FORECAST
# ═══════════════════════════════════════════════════════════════════════════════
COMPANY_COV_TARGETS = {
    "AXA":     {2021:0.780,2022:0.785,2023:0.790,2024:0.796,2025:0.802,2026:0.808},
    "Allianz": {2021:0.700,2022:0.705,2023:0.710,2024:0.716,2025:0.722,2026:0.728},
    "Bupa":    {2021:0.830,2022:0.836,2023:0.841,2024:0.847,2025:0.853,2026:0.859},
    "MetLife": {2021:0.730,2022:0.736,2023:0.741,2024:0.747,2025:0.753,2026:0.759},
}
COMPANY_COST_BASE   = {"AXA":9000,  "Allianz":7800,  "Bupa":10500, "MetLife":8200}
COMPANY_COST_GROWTH = {"AXA":0.065, "Allianz":0.060, "Bupa":0.070, "MetLife":0.062}
PATIENT_BASE        = {"AXA":1150,  "Allianz":1200,  "Bupa":1275,  "MetLife":1300}
PATIENT_GROWTH      = {"AXA":0.040, "Allianz":0.038, "Bupa":0.042, "MetLife":0.036}

INSURANCE_FEATURES = [
    "Coverage_Growth_Lag1", "Coverage_Growth_Lag2", "Patient_Count_Growth",
    "Patient_Count", "Avg_Treatment_Cost", "Avg_Coverage_Pct",
    "Avg_Claim_Amount", "Claim_Approved_Rate",
]
ML_WEIGHT    = 0.60
TREND_WEIGHT = 0.40

INSURANCE_ACTIONS = {
    "AXA"    : "Review AXA pricing changes and prepare policyholder communication.",
    "Allianz": "Assess Allianz pricing trend and notify relevant teams.",
    "Bupa"   : "Prepare Bupa premium review and monitor cost escalation.",
    "MetLife": "Review MetLife pricing changes and assess claim-cost drivers.",
}

def insurance_preprocessing(df_input, random_state=42):
    """Full preprocessing — matches notebook Cell 3 exactly."""
    df = df_input.copy()
    df["Year_int"] = df["Year"].round().fillna(0).astype(int)
    df = df.dropna(subset=["Insurance_Company", "Treatment_Cost"])
    tc = df["Treatment_Cost"]
    q1, q3 = tc.quantile(0.25), tc.quantile(0.75)
    iqr = q3 - q1
    df["Treatment_Cost"] = tc.clip(lower=max(q1 - 3*iqr, 0), upper=q3 + 3*iqr)
    df["Coverage_Percentage"] = df["Coverage_Percentage"].clip(0.50, 1.00)
    for co, yr_map in COMPANY_COV_TARGETS.items():
        for yr, cov_t in yr_map.items():
            mask = (df["Insurance_Company"] == co) & (df["Year_int"] == yr)
            if mask.sum() == 0: continue
            df.loc[mask, "Coverage_Percentage"] = cov_t
    for co in COMPANY_COST_BASE:
        for yr in range(2021, 2027):
            mask   = (df["Insurance_Company"] == co) & (df["Year_int"] == yr)
            cost_t = COMPANY_COST_BASE[co] * (1 + COMPANY_COST_GROWTH[co]) ** (yr - 2021)
            cm     = df.loc[mask, "Treatment_Cost"].mean()
            if cm > 0: df.loc[mask, "Treatment_Cost"] *= (cost_t / cm)
    parts = []
    for co in PATIENT_BASE:
        for i, yr in enumerate(range(2021, 2027)):
            mask     = (df["Insurance_Company"] == co) & (df["Year_int"] == yr)
            cur      = df[mask]
            target_n = int(PATIENT_BASE[co] * (1 + PATIENT_GROWTH[co]) ** i)
            if len(cur) == 0: continue
            parts.append(cur.sample(n=target_n, replace=True, random_state=random_state).copy())
    df = pd.concat(parts, ignore_index=True) if parts else df
    df["Claim_Approved"] = (df["Claim_Status"] == "Approved").astype(int)
    df["Total_Coverage"] = df["Treatment_Cost"] * df["Coverage_Percentage"]
    for col in df.select_dtypes(include=[np.number]).columns:
        df[col] = df[col].fillna(df[col].median())
    return df

def aggregate_annual(df_input, features=None):
    if features is None: features = INSURANCE_FEATURES
    annual = df_input.groupby(["Insurance_Company", "Year_int"]).agg(
        Total_Coverage_Sum  = ("Total_Coverage",      "sum"),
        Patient_Count       = ("Total_Coverage",      "count"),
        Avg_Treatment_Cost  = ("Treatment_Cost",      "mean"),
        Avg_Coverage_Pct    = ("Coverage_Percentage", "mean"),
        Avg_Claim_Amount    = ("Claim_Amount",        "mean"),
        Claim_Approved_Rate = ("Claim_Status", lambda x: (x == "Approved").mean()),
    ).reset_index().sort_values(["Insurance_Company", "Year_int"])
    annual["Coverage_Growth_Lag1"] = annual.groupby("Insurance_Company")["Total_Coverage_Sum"].pct_change(1) * 100
    annual["Coverage_Growth_Lag2"] = annual.groupby("Insurance_Company")["Total_Coverage_Sum"].pct_change(2) * 100
    annual["Patient_Count_Growth"] = annual.groupby("Insurance_Company")["Patient_Count"].pct_change(1) * 100
    annual["Target_Next_Coverage"] = annual.groupby("Insurance_Company")["Total_Coverage_Sum"].shift(-1)
    latest  = int(annual["Year_int"].max())
    pred_df = annual[annual["Year_int"] == latest].reset_index(drop=True)
    return annual, pred_df

def _weighted_trend(growth_series):
    growths = growth_series.dropna().values
    if len(growths) == 0: return 0.0
    w = np.array([0.1, 0.2, 0.3, 0.4])[-len(growths):]
    w /= w.sum()
    return float(np.dot(w, growths[-len(w):]))

def build_forecast_from_model(model, annual_df, pred_df, features=None, ml_weight=ML_WEIGHT):
    if features is None: features = INSURANCE_FEATURES
    X_p      = pred_df[features].fillna(0).values
    ml_preds = model.predict(X_p)
    rows = []
    for i, (_, row) in enumerate(pred_df.iterrows()):
        co      = row["Insurance_Company"]
        cur_cov = float(row["Total_Coverage_Sum"])
        ml_pct  = ((float(ml_preds[i]) / cur_cov) - 1) * 100 if cur_cov > 0 else 0.0
        trend   = _weighted_trend(annual_df[annual_df["Insurance_Company"] == co]["Coverage_Growth_Lag1"])
        blended = ml_weight * ml_pct + (1 - ml_weight) * trend
        uncert  = abs(ml_pct - trend) / 2
        rows.append({
            "Insurance_Company"  : co,
            "Current_Coverage"   : round(cur_cov, 2),
            "ML_Change_Pct"      : round(ml_pct,    2),
            "Trend_Change_Pct"   : round(trend,     2),
            "Blended_Change_Pct" : round(blended,   2),
            "Lower_Pct"          : round(blended - uncert, 2),
            "Upper_Pct"          : round(blended + uncert, 2),
            "Uncertainty_pp"     : round(uncert, 2),
        })
    return pd.DataFrame(rows)

def load_insurance_artifacts(model_dir=INSURANCE_MODEL_DIR):
    model = joblib.load(os.path.join(model_dir, "insurance_model.pkl"))
    with open(os.path.join(model_dir, "insurance_features.json"))         as f: features = json.load(f)
    with open(os.path.join(model_dir, "insurance_company_profiles.json")) as f: profiles = json.load(f)
    with open(os.path.join(model_dir, "insurance_meta.json"))             as f: meta     = json.load(f)
    baseline = pd.read_csv(os.path.join(model_dir, "insurance_annual_baseline.csv"))
    forecast = pd.read_csv(os.path.join(model_dir, "insurance_2027_forecast.csv"))
    return {"model": model, "features": features, "profiles": profiles,
            "meta": meta, "baseline": baseline, "forecast": forecast}

# ─────────────────────────────────────────────────────────────────────────────
# FIX 1 + 2 + 3: get_company_forecast_from_artifacts()
#
# ORIGINAL BUGS:
#   1. history["coverage"] contained Total_Coverage_Sum (EGP monetary values).
#      The chart Y-axis therefore showed raw currency totals, not percentages.
#   2. history["growth"] was passed to the template but the PHP/JS chart only
#      used history["coverage"], so the YoY % growth series was never plotted
#      and historical data appeared to be "missing".
#   3. ML_Change_Pct / Trend_Change_Pct were correctly read from the CSV, but
#      the history object mismatch caused the chart to render incorrectly,
#      making the overall prediction appear wrong.
#
# THE FIX:
#   • Build history as a list of {year, yoy_pct} records — exactly matching
#     the notebook's get_company_forecast() format (Cell 12).
#   • The first year (2021) has no lag-1 value so yoy_pct is null/None — we
#     skip it to avoid a gap at the left edge of the chart.
#   • Also expose ML_Change_Pct and Trend_Change_Pct at the top level so
#     the PHP template can display them in the three-box breakdown without
#     digging into nested dicts.
# ─────────────────────────────────────────────────────────────────────────────
def get_company_forecast_from_artifacts(company_name, arts):
    """
    Read the saved forecast CSV and return a consistent response dict.

    History format (FIXED):
        history = [{"year": 2022, "yoy_pct": 7.4}, {"year": 2023, ...}, ...]
        — year-over-year % growth, skipping the first year (no lag available).
        This matches the notebook's get_company_forecast() exactly and drives
        a percentage-based chart on the frontend.
    """
    fc  = arts["forecast"]
    row = fc[fc["Insurance_Company"] == company_name]
    if row.empty:
        return {"error": f"No forecast found for: {company_name}"}

    r           = row.iloc[0]
    blended     = float(r.get("Blended_Change_Pct", r.get("blended_change_pct", 0)))
    lower       = float(r.get("Lower_Pct",          r.get("lower_pct",  blended)))
    upper       = float(r.get("Upper_Pct",          r.get("upper_pct",  blended)))
    ml_pct      = float(r.get("ML_Change_Pct",      r.get("ml_change_pct",    blended)))
    trend_pct   = float(r.get("Trend_Change_Pct",   r.get("trend_change_pct", blended)))

    # --- FIXED: build history as [{year, yoy_pct}] — percentage growth only ---
    hist        = arts["baseline"]
    co_hist     = (hist[hist["Insurance_Company"] == company_name]
                   .sort_values("Year_int"))

    history_list = []
    for _, hr in co_hist.iterrows():
        yoy = hr.get("Coverage_Growth_Lag1")
        # Skip rows where the lag is not available (NaN = first year in series)
        if yoy is None or (isinstance(yoy, float) and math.isnan(yoy)):
            continue
        history_list.append({
            "year"    : int(hr["Year_int"]),
            "yoy_pct" : round(float(yoy), 2),
        })

    # sanitize NaN / Inf just in case
    history_list = sanitize_for_json(history_list)

    return {
        "status"             : "ok",
        "company"            : company_name,
        "predicted_year"     : 2027,
        "blended_change_pct" : round(blended,   2),
        "lower_pct"          : round(lower,     2),
        "upper_pct"          : round(upper,     2),
        "uncertainty_pp"     : round(float(r.get("Uncertainty_pp", abs(upper - lower) / 2)), 2),
        # Expose ML and Trend components at top level for the PHP three-box display
        "ML_Change_Pct"      : round(ml_pct,    2),
        "Trend_Change_Pct"   : round(trend_pct, 2),
        "action"             : INSURANCE_ACTIONS.get(company_name,
                               "Review pricing changes and assess claim-cost drivers."),
        # FIXED: list of {year, yoy_pct} — percentage history, not monetary sums
        "history"            : history_list,
        "source"             : "trained_model",
    }


def get_live_forecast_from_db(company_name, db_patients, arts):
    MIN_ROWS = 10
    if len(db_patients) < MIN_ROWS:
        result = get_company_forecast_from_artifacts(company_name, arts)
        result["source"]  = "trained_model"
        result["db_rows"] = len(db_patients)
        return result

    try:
        df = pd.DataFrame(db_patients)
        df["Insurance_Company"] = company_name
        df["Year_int"] = df["Year"].round().astype(int)
        unique_years = df["Year_int"].nunique()

        if unique_years < 2:
            result        = get_company_forecast_from_artifacts(company_name, arts)
            avg_cost      = float(df["Treatment_Cost"].mean())
            avg_coverage  = float(df["Coverage_Percentage"].clip(0.5, 1.0).mean())
            avg_claim     = float(df["Claim_Amount"].mean())
            approved_rate = float((df["Claim_Status"] == "Approved").mean())
            baseline_cost = COMPANY_COST_BASE.get(company_name, avg_cost)
            cost_delta_pct = ((avg_cost - baseline_cost) / baseline_cost) * 100 \
                             if baseline_cost > 0 else 0.0
            adjustment  = cost_delta_pct * 0.3
            old_blended = result["blended_change_pct"]
            new_blended = round(old_blended + adjustment, 2)
            uncertainty = abs(adjustment) + result.get("uncertainty_pp", 0)
            result.update({
                "source"             : "live_db_adjusted",
                "db_rows"            : len(db_patients),
                "blended_change_pct" : new_blended,
                "lower_pct"          : round(new_blended - uncertainty, 2),
                "upper_pct"          : round(new_blended + uncertainty, 2),
                "uncertainty_pp"     : round(uncertainty, 2),
                "message"            : (
                    f"Live DB adjustment: {len(db_patients)} claims from {unique_years} year(s). "
                    f"Cost delta vs baseline: {cost_delta_pct:+.1f}%. "
                    f"Blended forecast adjusted from {old_blended}% to {new_blended}%."
                ),
                "db_stats": {
                    "avg_treatment_cost" : round(avg_cost,      2),
                    "avg_coverage_pct"   : round(avg_coverage,  4),
                    "avg_claim_amount"   : round(avg_claim,      2),
                    "claim_approved_rate": round(approved_rate,  4),
                    "unique_years"       : unique_years,
                },
            })
            return result

        df_clean = insurance_preprocessing(df)
        annual, pred_df = aggregate_annual(df_clean, arts["features"])

        if pred_df.empty or pred_df[arts["features"]].isnull().all(axis=None):
            raise ValueError("pred_df is empty or all-NaN after aggregation")

        fc  = build_forecast_from_model(arts["model"], annual, pred_df, arts["features"])
        fc_row = fc[fc["Insurance_Company"] == company_name]
        if fc_row.empty:
            raise ValueError("No forecast row produced for this company")

        r           = fc_row.iloc[0]
        blended     = float(r["Blended_Change_Pct"])
        ml_pct      = float(r["ML_Change_Pct"])
        trend_pct   = float(r["Trend_Change_Pct"])

        # FIXED: same percentage-based history format for live path
        co_annual   = annual[annual["Insurance_Company"] == company_name].sort_values("Year_int")
        history_list = []
        for _, hr in co_annual.iterrows():
            yoy = hr.get("Coverage_Growth_Lag1")
            if yoy is None or (isinstance(yoy, float) and math.isnan(yoy)):
                continue
            history_list.append({
                "year"    : int(hr["Year_int"]),
                "yoy_pct" : round(float(yoy), 2),
            })
        history_list = sanitize_for_json(history_list)

        return {
            "status"             : "ok",
            "company"            : company_name,
            "predicted_year"     : datetime.date.today().year + 1,
            "blended_change_pct" : round(blended,   2),
            "lower_pct"          : round(float(r["Lower_Pct"]),      2),
            "upper_pct"          : round(float(r["Upper_Pct"]),      2),
            "uncertainty_pp"     : round(float(r["Uncertainty_pp"]), 2),
            "ML_Change_Pct"      : round(ml_pct,    2),
            "Trend_Change_Pct"   : round(trend_pct, 2),
            "action"             : INSURANCE_ACTIONS.get(company_name,
                                   "Review pricing changes and assess claim-cost drivers."),
            "db_rows"            : len(db_patients),
            "source"             : "live_db",
            "history"            : history_list,
            "message"            : f"Live forecast from {len(db_patients)} DB records.",
        }

    except Exception as ex:
        result = get_company_forecast_from_artifacts(company_name, arts)
        result["source"]   = "trained_model_fallback"
        result["db_error"] = str(ex)
        result["db_rows"]  = len(db_patients)
        return result

try:
    _INS_ARTIFACTS = load_insurance_artifacts()
    print("✅ Insurance model loaded")
except Exception as e:
    _INS_ARTIFACTS = None
    print(f"⚠️  Insurance model load failed: {e}")


# ═══════════════════════════════════════════════════════════════════════════════
#  ROUTES — SHARED
# ═══════════════════════════════════════════════════════════════════════════════
@app.route("/health",     methods=["GET"])
@app.route("/api/health", methods=["GET"])
def health_check():
    return jsonify({
        "status"          : "ok",
        "hospital_model"  : _HOSP_ARTIFACTS is not None,
        "insurance_model" : _INS_ARTIFACTS  is not None,
        "timestamp"       : datetime.datetime.utcnow().isoformat(),
    }), 200


# ═══════════════════════════════════════════════════════════════════════════════
#  ROUTES — PATIENT
# ═══════════════════════════════════════════════════════════════════════════════
@app.route("/predict/patient/<int:patient_id>", methods=["GET"])
def predict_patient(patient_id):
    try:
        record = get_latest_patient_record(patient_id)
        result = build_prediction(record)
        if not result.get("ok"):
            return jsonify(result), 404
        return jsonify(result), 200
    except Exception as e:
        return jsonify({
            "ok"     : False,
            "message": "Prediction failed",
            "error"  : str(e),
            "trace"  : traceback.format_exc(),
        }), 500


# ═══════════════════════════════════════════════════════════════════════════════
#  ROUTES — HOSPITAL
# ═══════════════════════════════════════════════════════════════════════════════
@app.route("/api/hospital/health", methods=["GET"])
def hospital_health():
    return jsonify({"status": "ok", "model_loaded": _HOSP_ARTIFACTS is not None}), 200

@app.route("/api/hospital/forecast", methods=["POST"])
def hospital_forecast():
    if _HOSP_ARTIFACTS is None:
        return jsonify({"error": "Hospital model not loaded."}), 503
    try:
        body         = request.get_json(force=True)
        patient_list = body.get("patients", [])
        result = predict_next_month_dominant_disease(
            patient_list, _HOSP_ARTIFACTS, int(body.get("min_patients", 20))
        )
        return jsonify(result), 200
    except Exception as e:
        return jsonify({"error": str(e), "trace": traceback.format_exc()}), 500

@app.route("/api/hospital/historical", methods=["GET"])
def historical_forecast():
    month_filter = request.args.get("month", type=int)
    if month_filter:
        entry = HISTORICAL_BASELINE.get(month_filter)
        if not entry:
            return jsonify({"error": f"Invalid month: {month_filter}"}), 400
        out = dict(entry)
        out["recommendations"]  = DISEASE_RECOMMENDATIONS.get(entry["dominant_disease"], [])
        out["dominant_display"] = entry["dominant_disease"].replace("_", " ")
        return jsonify(out), 200
    result = []
    for m, data in HISTORICAL_BASELINE.items():
        result.append({
            "month"            : m,
            "month_name"       : data["month_name"],
            "dominant_disease" : data["dominant_disease"],
            "dominant_display" : data["dominant_disease"].replace("_", " "),
            "severity"         : data["severity"],
            "recommendations"  : DISEASE_RECOMMENDATIONS.get(data["dominant_disease"], []),
        })
    return jsonify({"status": "ok", "calendar": result}), 200


# ═══════════════════════════════════════════════════════════════════════════════
#  ROUTES — INSURANCE
# ═══════════════════════════════════════════════════════════════════════════════
@app.route("/api/insurance/health", methods=["GET"])
def insurance_health():
    return jsonify({"status": "ok", "model_loaded": _INS_ARTIFACTS is not None}), 200

@app.route("/api/insurance/forecast", methods=["GET"])
def insurance_forecast_saved():
    if _INS_ARTIFACTS is None:
        return jsonify({"error": "Insurance model not loaded."}), 503
    company = request.args.get("company", "").strip()
    if not company:
        return jsonify({"error": "Missing ?company= parameter"}), 400
    result = get_company_forecast_from_artifacts(company, _INS_ARTIFACTS)
    return jsonify(result), (400 if "error" in result else 200)

@app.route("/api/insurance/forecast/live", methods=["POST"])
def insurance_forecast_live():
    if _INS_ARTIFACTS is None:
        return jsonify({"error": "Insurance model not loaded."}), 503
    try:
        body        = request.get_json(force=True)
        company     = body.get("company", "").strip()
        db_patients = body.get("patients", [])
        if not company:
            return jsonify({"error": "Missing 'company' field in request body"}), 400
        result = get_live_forecast_from_db(company, db_patients, _INS_ARTIFACTS)
        return jsonify(result), 200
    except Exception as e:
        return jsonify({"error": str(e), "trace": traceback.format_exc()}), 500

@app.route("/api/insurance/all", methods=["GET"])
def insurance_all_companies():
    if _INS_ARTIFACTS is None:
        return jsonify({"error": "Insurance model not loaded."}), 503
    results = []
    for co in ["AXA", "Allianz", "Bupa", "MetLife"]:
        r = get_company_forecast_from_artifacts(co, _INS_ARTIFACTS)
        if "error" not in r:
            results.append(r)
    return jsonify({"status": "ok", "forecasts": results}), 200


# ═══════════════════════════════════════════════════════════════════════════════
#  ENTRY POINT
# ═══════════════════════════════════════════════════════════════════════════════
if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port, debug=False)