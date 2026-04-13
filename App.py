# app.py — Hospital Epidemic Forecast API
# Run (dev):  python app.py
# Run (prod): gunicorn app:app -b 0.0.0.0:5000 -w 4

import os, json, datetime, traceback
import numpy as np
import pandas as pd
import joblib
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)   # Allow PHP / browser to call this from any origin

MODEL_DIR   = os.path.join(os.path.dirname(__file__), "hospital_model_artifacts")
MONTH_NAMES = {1:"Jan",2:"Feb",3:"Mar",4:"Apr",5:"May",6:"Jun",
               7:"Jul",8:"Aug",9:"Sep",10:"Oct",11:"Nov",12:"Dec"}

DISEASE_RECOMMENDATIONS = {
    "Asthma": [
        "Ensure respiratory unit is ready for increased breathing difficulty cases.",
        "Make sure oxygen support devices are available in ER and wards.",
        "Prepare staff to quickly triage and manage breathing emergencies.",
    ],
    "Cancer": [
        "Ensure oncology unit and inpatient beds are ready for increased admissions.",
        "Prepare staff for higher need of continuous patient care and monitoring.",
        "Maintain readiness of diagnostic and imaging services.",
    ],
    "Chronic_Kidney_Disease": [
        "Ensure dialysis unit readiness and machine availability.",
        "Prepare monitoring for fluid and kidney-related complications.",
        "Increase nephrology staff availability for consultations.",
    ],
    "Coronary_Artery_Disease": [
        "Ensure cardiac unit readiness for chest pain emergencies.",
        "Prepare emergency response teams for rapid intervention cases.",
        "Maintain continuous monitoring equipment availability.",
    ],
    "Diabetes": [
        "Ensure readiness for blood sugar emergencies and monitoring.",
        "Prepare staff for metabolic complication management.",
        "Increase availability of patient monitoring tools.",
    ],
    "Healthy": [
        "Use this period for routine maintenance and system checks.",
        "Schedule preventive care and staff training.",
    ],
    "Heart_Failure": [
        "Ensure cardiac care unit is prepared for increased admissions.",
        "Maintain readiness for fluid overload and breathing complications.",
        "Ensure ICU availability for severe cases.",
    ],
    "Hypertension": [
        "Ensure blood pressure monitoring is widely available.",
        "Prepare staff for emergency high blood pressure cases.",
        "Maintain readiness for cardiovascular complications.",
    ],
    "Pneumonia": [
        "Ensure respiratory isolation and infection control readiness.",
        "Prepare hospital for increased respiratory infection cases.",
        "Maintain oxygen support and monitoring availability.",
    ],
    "Stroke": [
        "Ensure emergency stroke pathway is fully operational.",
        "Prepare imaging and emergency response readiness.",
        "Maintain ICU and monitoring bed availability.",
    ],
}

DISEASE_SEVERITY = {
    "Stroke": "critical",
    "Heart_Failure": "critical",
    "Coronary_Artery_Disease": "critical",
    "Cancer": "high",
    "Chronic_Kidney_Disease": "high",
    "Pneumonia": "high",
    "Asthma": "medium",
    "Diabetes": "medium",
    "Hypertension": "medium",
    "Healthy": "low",
}

# ─── Artifact loader (called ONCE at startup) ─────────────────────────────────
def load_hospital_artifacts(model_dir=MODEL_DIR):
    model    = joblib.load(os.path.join(model_dir, "best_hospital_model.pkl"))
    scaler   = joblib.load(os.path.join(model_dir, "hospital_scaler.pkl"))
    le_tgt   = joblib.load(os.path.join(model_dir, "hospital_label_encoder_target.pkl"))
    le_feats = joblib.load(os.path.join(model_dir, "hospital_label_encoders_features.pkl"))
    with open(os.path.join(model_dir, "hospital_features.json")) as f:
        features = json.load(f)
    return model, scaler, le_tgt, le_feats, features


# ─── Preprocessing (single-pass — matches training exactly) ──────────────────
def preprocess_patient_batch(patient_list, scaler, le_feats, features):
    batch = pd.DataFrame(patient_list)

    for col in ["Fever", "Cough", "Shortness_of_Breath"]:
        if col in batch.columns:
            batch[col] = (pd.to_numeric(batch[col], errors="coerce")
                          .fillna(0).gt(0.5).astype(int))

    for col, le in le_feats.items():
        if col in batch.columns:
            batch[col] = batch[col].astype(str).apply(
                lambda v: le.transform([v])[0]
                          if v in le.classes_
                          else le.transform([le.classes_[0]])[0]
            )

    for col in features:
        if col not in batch.columns:
            batch[col] = 0
    batch = batch[features]

    num_cols = batch.select_dtypes(include=[np.number]).columns.tolist()
    batch[num_cols] = scaler.transform(batch[num_cols])
    return batch


# ─── Core forecast function ───────────────────────────────────────────────────
def predict_next_month_dominant_disease(patient_list, _artifacts, min_patients=20):
    model, scaler, le_tgt, le_feats, features = _artifacts
    today           = datetime.date.today()
    next_month_n    = today.month % 12 + 1
    next_month_name = MONTH_NAMES[next_month_n]

    if len(patient_list) < min_patients:
        return {
            "status"           : "insufficient_data",
            "message"          : f"Need >={min_patients} patients to generate a reliable forecast, got {len(patient_list)}.",
            "total_patients"   : len(patient_list),
            "next_month"       : next_month_name,
            "dominant_disease" : None,
            "recommendations"  : [],
            "severity"         : None,
            "distribution"     : {},
            "distribution_pct" : {},
        }

    X_batch   = preprocess_patient_batch(patient_list, scaler, le_feats, features)
    preds_enc = model.predict(X_batch)
    preds_dis = le_tgt.inverse_transform(preds_enc)

    counts    = pd.Series(preds_dis).value_counts()
    pct       = (counts / len(preds_dis) * 100).round(1)
    dominant  = str(counts.index[0])

    return {
        "status"           : "ok",
        "message"          : f"Forecast ready for {next_month_name} based on {len(patient_list)} patients.",
        "total_patients"   : len(patient_list),
        "next_month"       : next_month_name,
        "dominant_disease" : dominant,
        "dominant_display" : dominant.replace("_", " "),
        "recommendations"  : DISEASE_RECOMMENDATIONS.get(dominant, ["General preparedness recommended."]),
        "severity"         : DISEASE_SEVERITY.get(dominant, "medium"),
        "distribution"     : {str(k): int(v)   for k, v in counts.items()},
        "distribution_pct" : {str(k): float(v) for k, v in pct.items()},
    }


# ─── Load model once at startup ───────────────────────────────────────────────
try:
    _ARTIFACTS = load_hospital_artifacts()
    print(f"✅ Model loaded — ready on port {int(os.environ.get('PORT', 5000))}")
except Exception as e:
    _ARTIFACTS = None
    print(f"⚠️  Model load failed: {e}")
    print("   Make sure hospital_model_artifacts/ folder is in the same directory as app.py")


# ─── Endpoints ────────────────────────────────────────────────────────────────
@app.route("/api/hospital/health", methods=["GET"])
def health_check():
    return jsonify({
        "status"       : "ok",
        "model_loaded" : _ARTIFACTS is not None,
        "timestamp"    : datetime.datetime.utcnow().isoformat()
    }), 200


@app.route("/api/hospital/forecast", methods=["POST"])
def hospital_forecast():
    if _ARTIFACTS is None:
        return jsonify({"error": "Model artifacts not loaded. Check server logs."}), 503
    try:
        body         = request.get_json(force=True)
        patient_list = body.get("patients", [])
        min_p        = int(body.get("min_patients", 20))
        result = predict_next_month_dominant_disease(
            patient_list = patient_list,
            _artifacts   = _ARTIFACTS,
            min_patients = min_p,
        )
        return jsonify(result), 200
    except Exception as e:
        return jsonify({"error": str(e), "trace": traceback.format_exc()}), 500


# ─── Historical calendar endpoint (uses stored monthly baseline) ──────────────
@app.route("/api/hospital/historical", methods=["GET"])
def historical_forecast():
    """
    Returns the historical monthly dominant disease baseline.
    The PHP dashboard calls this on page load to show the full year calendar.
    Optionally filtered by ?month=5 (1-12).
    """
    # Static historical baseline (from the notebook's Section E output).
    # Replace these values with your actual model output if you retrain.
    HISTORICAL_BASELINE = {
        1:  {"month_name": "Jan",  "dominant_disease": "Stroke",               "severity": "critical"},
        2:  {"month_name": "Feb",  "dominant_disease": "Hypertension",          "severity": "medium"},
        3:  {"month_name": "Mar",  "dominant_disease": "Diabetes",              "severity": "medium"},
        4:  {"month_name": "Apr",  "dominant_disease": "Heart_Failure",         "severity": "critical"},
        5:  {"month_name": "May",  "dominant_disease": "Asthma",                "severity": "medium"},
        6:  {"month_name": "Jun",  "dominant_disease": "Coronary_Artery_Disease","severity": "critical"},
        7:  {"month_name": "Jul",  "dominant_disease": "Cancer",                "severity": "high"},
        8:  {"month_name": "Aug",  "dominant_disease": "Pneumonia",             "severity": "high"},
        9:  {"month_name": "Sep",  "dominant_disease": "Cancer",                "severity": "high"},
        10: {"month_name": "Oct",  "dominant_disease": "Pneumonia",             "severity": "high"},
        11: {"month_name": "Nov",  "dominant_disease": "Chronic_Kidney_Disease","severity": "high"},
        12: {"month_name": "Dec",  "dominant_disease": "Heart_Failure",         "severity": "critical"},
    }

    month_filter = request.args.get("month", type=int)
    if month_filter:
        entry = HISTORICAL_BASELINE.get(month_filter)
        if not entry:
            return jsonify({"error": f"Invalid month: {month_filter}"}), 400
        entry_with_recs = dict(entry)
        entry_with_recs["recommendations"] = DISEASE_RECOMMENDATIONS.get(
            entry["dominant_disease"], []
        )
        entry_with_recs["dominant_display"] = entry["dominant_disease"].replace("_", " ")
        return jsonify(entry_with_recs), 200

    # Return full year
    result = []
    for month_n, data in HISTORICAL_BASELINE.items():
        result.append({
            "month"            : month_n,
            "month_name"       : data["month_name"],
            "dominant_disease" : data["dominant_disease"],
            "dominant_display" : data["dominant_disease"].replace("_", " "),
            "severity"         : data["severity"],
            "recommendations"  : DISEASE_RECOMMENDATIONS.get(data["dominant_disease"], []),
        })
    return jsonify({"status": "ok", "calendar": result}), 200


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port, debug=False)