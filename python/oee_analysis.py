import json
import sys

import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.decomposition import PCA
from sklearn.metrics import silhouette_score
from sklearn.preprocessing import MinMaxScaler


def main() -> None:
    payload = json.loads(sys.stdin.read() or "{}")
    rows = payload.get("rows", [])
    year = payload.get("year")

    # Alur mirip kode referensi, tetapi sumber data dari JSON Laravel, bukan file .xlsx langsung.
    df_all = pd.DataFrame(rows)

    required_cols = ["Bulan", "Proses", "POT", "Waktu_Beproduksi", "Total_Output", "Good_Output"]
    for col in required_cols:
        if col not in df_all.columns:
            df_all[col] = np.nan

    if df_all.empty:
        print(json.dumps({
            "ok": True,
            "year": year,
            "silhouette_score": 0.0,
            "analysis_results": [],
            "chart": {
                "optimal": [],
                "waspada": [],
                "kritis": [],
                "anomali": [],
                "annotations": [],
                "x_label": "Komponen Utama 1 (Varian Ketersediaan)",
                "y_label": "Komponen Utama 2 (Varian Mutu & Performa)",
            },
        }))
        return

    # Feature engineering A, P, Q
    numeric_cols = ["POT", "Waktu_Beproduksi", "Total_Output", "Good_Output"]
    for col in numeric_cols:
        df_all[col] = pd.to_numeric(df_all[col], errors="coerce")

    df_all["Availability"] = df_all["Waktu_Beproduksi"] / df_all["POT"]
    df_all["Quality"] = df_all["Good_Output"] / df_all["Total_Output"]

    df_all["Current_Rate"] = df_all["Total_Output"] / df_all["Waktu_Beproduksi"]
    df_all["Current_Rate"].replace([np.inf, -np.inf], np.nan, inplace=True)

    max_rate_per_machine = df_all.groupby("Proses")["Current_Rate"].transform("max")
    df_all["Performance"] = df_all["Current_Rate"] / max_rate_per_machine

    df_all.fillna(0, inplace=True)
    for col in ["Availability", "Performance", "Quality"]:
        df_all[col] = df_all[col].clip(upper=1.0)

    df_all["OEE_Score"] = df_all["Availability"] * df_all["Performance"] * df_all["Quality"]

    # K-Means
    fitur_ml = df_all[["Availability", "Performance", "Quality"]]
    scaler = MinMaxScaler()
    x_scaled = scaler.fit_transform(fitur_ml)

    kmeans = KMeans(n_clusters=3, random_state=42, n_init=10)
    df_all['Cluster'] = kmeans.fit_predict(x_scaled)

    # Dynamic mapping status
    rata2_oee_klaster = df_all.groupby('Cluster')['OEE_Score'].mean().sort_values()
    mapping_status = {
        rata2_oee_klaster.index[0]: 'Kritis',
        rata2_oee_klaster.index[1]: 'Waspada',
        rata2_oee_klaster.index[2]: 'Optimal'
    }
    df_all['Status'] = df_all['Cluster'].map(mapping_status)

    # PCA 2D
    if len(df_all) >= 2:
        pca = PCA(n_components=2)
        x_pca = pca.fit_transform(x_scaled)
        df_all["PCA1"] = x_pca[:, 0]
        df_all["PCA2"] = x_pca[:, 1]
    else:
        df_all["PCA1"] = 0.0
        df_all["PCA2"] = 0.0

    silhouette = 0.0
    if len(df_all) >= 3 and len(df_all["Cluster"].unique()) >= 2:
        silhouette = float(silhouette_score(x_scaled, df_all["Cluster"]))

    # Bentuk output chart + tabel
    chart = {
        "optimal": [],
        "waspada": [],
        "kritis": [],
        "anomali": [],
        "annotations": [],
        "x_label": "Komponen Utama 1 (Varian Ketersediaan)",
        "y_label": "Komponen Utama 2 (Varian Mutu & Performa)",
    }

    # Anomali disesuaikan dari rule yang sudah dipakai sebelumnya
    df_all["is_anomali"] = (df_all["Quality"] < 0.5) | (df_all["OEE_Score"] < 0.05)

    for _, row in df_all.iterrows():
        point = {
            "x": round(float(row["PCA1"]), 4),
            "y": round(float(row["PCA2"]), 4),
            "machine": str(row["Proses"]),
            "month": str(row["Bulan"]),
            "performance": round(float(row["Performance"]) * 100, 2),
            "oee": round(float(row["OEE_Score"]) * 100, 2),
            "status": str(row["Status"]),
        }

        if bool(row["is_anomali"]):
            chart["anomali"].append(point)
        elif row["Status"] == "Optimal":
            chart["optimal"].append(point)
        elif row["Status"] == "Waspada":
            chart["waspada"].append(point)
        elif row["Status"] == "Kritis":
            chart["kritis"].append(point)
        else:
            chart["anomali"].append(point)

    kritis_terparah = (
        df_all[df_all["Status"] == "Kritis"]
        .sort_values("OEE_Score")
        .head(5)
    )
    for _, row in kritis_terparah.iterrows():
        bulan = str(row["Bulan"])
        chart["annotations"].append({
            "label": f"{row['Proses']} ({bulan[:3]})",
            "x": round(float(row["PCA1"]), 4),
            "y": round(float(row["PCA2"]), 4),
        })

    analysis_results = df_all[
        [
            "Bulan",
            "Proses",
            "Availability",
            "Performance",
            "Quality",
            "OEE_Score",
            "Cluster",
            "Status",
            "PCA1",
            "PCA2",
            "is_anomali",
        ]
    ].to_dict(orient="records")

    print(json.dumps({
        "ok": True,
        "year": year,
        'min_max': x_scaled.tolist(),
        "silhouette_score": round(silhouette, 4),
        "analysis_results": analysis_results,
        "chart": chart,
    }))


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(json.dumps({"ok": False, "message": str(exc)}))
        sys.exit(1)
