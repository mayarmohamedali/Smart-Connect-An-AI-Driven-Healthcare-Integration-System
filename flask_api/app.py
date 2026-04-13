from flask import Flask, jsonify
import pymysql

app = Flask(__name__)

DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "smart_connect",
    "port": 3307,
    "cursorclass": pymysql.cursors.DictCursor
}

def get_connection():
    return pymysql.connect(**DB_CONFIG)

def get_latest_patient_record(patient_id):
    sql = """
    SELECT
        p.patient_id,
        p.full_name,
        p.gender,
        p.insurance_id,
        mr.record_id,
        mr.age,
        mr.bmi,
        mr.glucose,
        mr.cholesterol_level,
        mr.systolic_bp,
        mr.blood_creatinine1,
        mr.blood_creatinine2,
        mr.admission_count,
        mr.length_of_stay,
        mr.smoking_status,
        mr.physical_activity_level,
        mr.diet_quality,
        mr.sleep_hours,
        mr.stress_level,
        mr.family_history,
        mr.medications_count,
        mr.risk_score,
        mr.symptom_burden,
        mr.fever,
        mr.cough,
        mr.fatigue,
        mr.chest_pain,
        mr.shortness_of_breath,
        mr.headache,
        mr.diagnosis,
        mr.disease_category,
        mr.created_at
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
        return {
            "ok": False,
            "message": "No patient record found"
        }

    glucose = float(record.get("glucose") or 0)
    systolic_bp = float(record.get("systolic_bp") or 0)
    creat1 = float(record.get("blood_creatinine1") or 0)
    creat2 = float(record.get("blood_creatinine2") or 0)
    bmi = float(record.get("bmi") or 0)
    chest_pain = int(record.get("chest_pain") or 0)
    shortness = float(record.get("shortness_of_breath") or 0)
    headache = int(record.get("headache") or 0)
    fatigue = float(record.get("fatigue") or 0)
    fever = float(record.get("fever") or 0)
    cough = float(record.get("cough") or 0)

    active_alerts = []
    predicted_disease = "Healthy"
    disease_category = "General"
    risk_level = "LOW"
    short_term_measures = []
    long_term_measures = []

    # Rule 1: Kidney disease
    if creat2 >= 2.0 or creat1 >= 2.0:
        predicted_disease = "Kidney Disease"
        disease_category = "Renal"
        risk_level = "HIGH"
        active_alerts.append("Severely elevated creatinine levels")
        active_alerts.append("Kidney function requires urgent follow-up")
        short_term_measures = [
            "Repeat kidney function tests",
            "Increase hydration if medically allowed",
            "Avoid nephrotoxic medications without doctor advice"
        ]
        long_term_measures = [
            "Regular nephrology follow-up",
            "Renal-friendly diet plan",
            "Periodic creatinine and urea monitoring"
        ]

    # Rule 2: Heart disease
    elif chest_pain == 1 or shortness >= 1:
        predicted_disease = "Heart Disease"
        disease_category = "Cardiovascular"
        risk_level = "HIGH"
        active_alerts.append("Cardiovascular symptom pattern detected")
        if chest_pain == 1:
            active_alerts.append("Chest pain reported")
        if shortness >= 1:
            active_alerts.append("Shortness of breath reported")
        short_term_measures = [
            "Cardiology assessment",
            "Monitor blood pressure closely",
            "Avoid strenuous activity until reviewed"
        ]
        long_term_measures = [
            "Heart-healthy diet",
            "Regular cardiac follow-up",
            "Cholesterol and blood pressure control"
        ]

    # Rule 3: Diabetes
    elif glucose >= 126:
        predicted_disease = "Diabetes Mellitus"
        disease_category = "Metabolic"
        risk_level = "HIGH" if glucose >= 200 else "MODERATE"
        active_alerts.append("Elevated blood glucose detected")
        if bmi >= 30:
            active_alerts.append("Obesity-related metabolic risk")
        short_term_measures = [
            "Monitor fasting blood glucose weekly",
            "Reduce sugar and refined carbs",
            "Walk 30 minutes daily"
        ]
        long_term_measures = [
            "Follow diabetic-friendly diet plan",
            "Maintain healthy BMI",
            "Regular endocrinologist follow-up"
        ]

    # Rule 4: Hypertension
    elif systolic_bp >= 140:
        predicted_disease = "Hypertension"
        disease_category = "Cardiovascular"
        risk_level = "HIGH" if systolic_bp >= 180 else "MODERATE"
        active_alerts.append("Elevated blood pressure detected")
        short_term_measures = [
            "Reduce salt intake",
            "Monitor blood pressure daily",
            "Limit caffeine and stress"
        ]
        long_term_measures = [
            "Maintain healthy weight",
            "Regular exercise",
            "Routine blood pressure follow-up"
        ]

    # Rule 5: Headache
    elif headache == 1:
        predicted_disease = "Headache"
        disease_category = "Neurological"
        risk_level = "LOW" if fever == 0 and cough == 0 else "MODERATE"
        active_alerts.append("Headache symptom present")
        short_term_measures = [
            "Hydrate well",
            "Reduce screen strain",
            "Track headache frequency"
        ]
        long_term_measures = [
            "Improve sleep routine",
            "Manage stress",
            "Neurology follow-up if recurrent"
        ]

    # Rule 6: General fatigue / mild illness
    elif fatigue >= 1 or fever >= 1 or cough >= 1:
        predicted_disease = "General Illness"
        disease_category = "General"
        risk_level = "LOW"
        active_alerts.append("General symptom burden detected")
        short_term_measures = [
            "Rest and hydrate",
            "Monitor symptoms",
            "Seek medical advice if symptoms worsen"
        ]
        long_term_measures = [
            "Maintain healthy sleep schedule",
            "Balanced diet",
            "Routine health follow-up"
        ]

    else:
        predicted_disease = "Healthy"
        disease_category = "General"
        risk_level = "LOW"
        short_term_measures = [
            "Continue healthy habits",
            "Stay hydrated",
            "Maintain physical activity"
        ]
        long_term_measures = [
            "Routine annual checkups",
            "Balanced diet",
            "Regular exercise"
        ]

    return {
        "ok": True,
        "patient_id": record["patient_id"],
        "predicted_disease": predicted_disease,
        "disease_category": disease_category,
        "risk_level": risk_level,
        "active_alerts": active_alerts,
        "short_term_measures": short_term_measures,
        "long_term_measures": long_term_measures
    }

@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "ok": True,
        "message": "Flask API is running"
    })

@app.route("/predict/patient/<int:patient_id>", methods=["GET"])
def predict_patient(patient_id):
    try:
        record = get_latest_patient_record(patient_id)
        result = build_prediction(record)

        if not result.get("ok"):
            return jsonify(result), 404

        return jsonify(result)

    except Exception as e:
        return jsonify({
            "ok": False,
            "message": "Prediction failed",
            "error": str(e)
        }), 500

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)