import json
import sys

import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.decomposition import PCA
from sklearn.metrics import silhouette_score
from sklearn.preprocessing import MinMaxScaler


class OEEAnalyzer:
    def __init__(self, json_payload: str):
        self.payload = json.loads(json_payload or "{}")
        self.rows = self.payload.get("rows", [])
        self.year = self.payload.get("year")
        self.df = pd.DataFrame(self.rows)
        
        # Inisialisasi atribut yang akan diisi nanti
        self.x_scaled = None
        self.silhouette = 0.0

    def validate_data(self) -> bool:
        """Memastikan kolom yang dibutuhkan tersedia dan DataFrame tidak kosong."""
        required_cols = ["Bulan", "Proses", "POT", "Waktu_Beproduksi", "Total_Output", "Good_Output"]
        for col in required_cols:
            if col not in self.df.columns:
                self.df[col] = np.nan

        return not self.df.empty

    def calculate_oee(self) -> None:
        """Feature engineering untuk menghitung Availability, Performance, Quality, dan OEE."""
        numeric_cols = ["POT", "Waktu_Beproduksi", "Total_Output", "Good_Output", "Running_Time", "Idle_Time", "PPT"]
        for col in numeric_cols:
            if col in self.df.columns:
                self.df[col] = pd.to_numeric(self.df[col], errors="coerce")

        self.df["Availability"] = self.df["Running_Time"] / self.df["PPT"]
        self.df["Quality"] = self.df["Good_Output"] / self.df["Total_Output"]

        self.df["Current_Rate"] = self.df["Total_Output"] / self.df["Availability"]
        self.df["Current_Rate"].replace([np.inf, -np.inf], np.nan, inplace=True)

        max_rate_per_machine = self.df.groupby("Proses")["Current_Rate"].transform("max")
        self.df["Performance"] = self.df["Current_Rate"] / max_rate_per_machine

        self.df.fillna(0, inplace=True)
        for col in ["Availability", "Performance", "Quality"]:
            self.df[col] = self.df[col].clip(upper=1.0)

        self.df["OEE_Score"] = self.df["Availability"] * self.df["Performance"] * self.df["Quality"]

    def perform_clustering(self) -> None:
        """Melakukan clustering K-Means dan ekstraksi fitur menggunakan PCA."""
        fitur_ml = self.df[["Availability", "Performance", "Quality"]]
        scaler = MinMaxScaler()
        self.x_scaled = scaler.fit_transform(fitur_ml)

        kmeans = KMeans(n_clusters=3, random_state=42, n_init=10)
        self.df['Cluster'] = kmeans.fit_predict(self.x_scaled)

        # Dynamic mapping status
        rata2_oee_klaster = self.df.groupby('Cluster')['OEE_Score'].mean().sort_values()
        mapping_status = {
            rata2_oee_klaster.index[0]: 'Kritis',
            rata2_oee_klaster.index[1]: 'Waspada',
            rata2_oee_klaster.index[2]: 'Optimal'
        }
        self.df['Status'] = self.df['Cluster'].map(mapping_status)

        # PCA 2D
        if len(self.df) >= 2:
            pca = PCA(n_components=2)
            x_pca = pca.fit_transform(self.x_scaled)
            self.df["PCA1"] = x_pca[:, 0]
            self.df["PCA2"] = x_pca[:, 1]
        else:
            self.df["PCA1"] = 0.0
            self.df["PCA2"] = 0.0

        # Silhouette Score
        if len(self.df) >= 3 and len(self.df["Cluster"].unique()) >= 2:
            self.silhouette = float(silhouette_score(self.x_scaled, self.df["Cluster"]))

    def format_output(self) -> dict:
        """Menyusun hasil analisis ke dalam format dictionary (JSON-ready)."""
        chart = {
            "optimal": [], "waspada": [], "kritis": [], "anomali": [], "annotations": [],
            "x_label": "Komponen Utama 1 (Varian Ketersediaan)",
            "y_label": "Komponen Utama 2 (Varian Mutu & Performa)",
        }

        self.df["is_anomali"] = (self.df["Quality"] < 0.5) | (self.df["OEE_Score"] < 0.05)

        for _, row in self.df.iterrows():
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
            self.df[self.df["Status"] == "Kritis"]
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

        analysis_results = self.df[[
            "Bulan", "Proses", "Availability", "Performance", "Quality",
            "OEE_Score", "Cluster", "Status", "PCA1", "PCA2", "is_anomali"
        ]].to_dict(orient="records")

        return {
            "ok": True,
            "year": self.year,
            "min_max": self.x_scaled.tolist() if self.x_scaled is not None else [],
            "silhouette_score": round(self.silhouette, 4),
            "analysis_results": analysis_results,
            "chart": chart,
        }

    def get_empty_response(self) -> dict:
        """Mengembalikan response default jika data kosong."""
        return {
            "ok": True,
            "year": self.year,
            "silhouette_score": 0.0,
            "analysis_results": [],
            "chart": {
                "optimal": [], "waspada": [], "kritis": [], "anomali": [], "annotations": [],
                "x_label": "Komponen Utama 1 (Varian Ketersediaan)",
                "y_label": "Komponen Utama 2 (Varian Mutu & Performa)",
            },
        }

    def analyze(self) -> dict:
        """Metode utama untuk menjalankan semua proses."""
        if not self.validate_data():
            return self.get_empty_response()

        self.calculate_oee()
        self.perform_clustering()
        return self.format_output()


def main() -> None:
    try:
        payload_data = sys.stdin.read()
        analyzer = OEEAnalyzer(payload_data)
        result = analyzer.analyze()
        
        print(json.dumps(result))
        
    except Exception as exc:
        print(json.dumps({"ok": False, "message": str(exc)}))
        sys.exit(1)


if __name__ == "__main__":
    main()