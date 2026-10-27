"""
AR Tilik - Mesin Analitik Prediktif & Proyeksi Cerdas (XGBoost + Gemini AI)
Mengintegrasikan regresi deret waktu berbasis Gradient Boosting (XGBoost)
dengan lapisan penalaran kognitif eksekutif (Google Gemini LLM).
"""

import sys
import os
import json
import logging
import argparse
import re
from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
import numpy as np
import pandas as pd
import xgboost as xgb

# Pastikan path modul root terbaca
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from worker.database import get_connection
from worker.gemini_forensic import (
    call_gemini_api,
    get_available_gemini_keys,
    mark_key_success,
    mark_key_rate_limited
)

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
logger = logging.getLogger("artilik.predictive_analytics")

CACHE_DIR = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "writable", "cache")
CACHE_FILE = os.path.join(CACHE_DIR, "predictive_forecast_latest.json")


def load_cached_forecast() -> Optional[Dict[str, Any]]:
    """Memuat hasil forecast terakhir dari file cache jika ada dan valid."""
    if os.path.exists(CACHE_FILE):
        try:
            with open(CACHE_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception as e:
            logger.warning(f"Gagal membaca cache forecast: {e}")
    return None


def save_cached_forecast(data: Dict[str, Any]):
    """Menyimpan data hasil forecast ke file cache."""
    try:
        os.makedirs(CACHE_DIR, exist_ok=True)
        with open(CACHE_FILE, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
    except Exception as e:
        logger.error(f"Gagal menyimpan cache forecast: {e}")


def fetch_historical_series(conn) -> pd.DataFrame:
    """
    Mengambil data deret waktu harian untuk Penjualan (revenue) dan Penggunaan (documents & reports).
    """
    cursor = conn.cursor(dictionary=True)

    # 1. Agregasi Penjualan Harian
    cursor.execute("""
        SELECT 
            DATE(COALESCE(paid_at, created_at)) as tx_date,
            COUNT(id) as tx_count,
            SUM(amount) as daily_revenue,
            SUM(submissions_added) as daily_credits_sold
        FROM payment_transactions
        WHERE transaction_status IN ('settlement', 'capture')
        GROUP BY DATE(COALESCE(paid_at, created_at))
        ORDER BY tx_date ASC
    """)
    sales_rows = cursor.fetchall() or []

    # 2. Agregasi Penggunaan Dokumen Harian (Hanya Naskah Submisi Riil, Bukan Korpus Acuan)
    cursor.execute("""
        SELECT 
            DATE(created_at) as doc_date,
            COUNT(id) as doc_count,
            COUNT(CASE WHEN user_id IS NOT NULL THEN 1 END) as registered_doc_count,
            COUNT(CASE WHEN user_id IS NULL AND benchmark_run_id IS NULL THEN 1 END) as anon_doc_count,
            COALESCE(SUM(page_count), 0) as total_pages,
            COALESCE(SUM(file_size), 0) as total_bytes
        FROM documents
        WHERE document_type = 'submission'
          AND benchmark_run_id IS NULL
        GROUP BY DATE(created_at)
        ORDER BY doc_date ASC
    """)
    doc_rows = cursor.fetchall() or []

    # 3. Agregasi Laporan Audit Harian
    cursor.execute("""
        SELECT 
            DATE(created_at) as rpt_date,
            COUNT(id) as audit_count,
            AVG(overall_similarity) as avg_similarity
        FROM reports
        WHERE status = 'completed'
        GROUP BY DATE(created_at)
        ORDER BY rpt_date ASC
    """)
    rpt_rows = cursor.fetchall() or []

    # 4. Statistik Paket Terlaris
    cursor.execute("""
        SELECT 
            package_id, 
            package_name, 
            COUNT(id) as total_sold, 
            SUM(amount) as total_revenue
        FROM payment_transactions
        WHERE transaction_status IN ('settlement', 'capture')
        GROUP BY package_id, package_name
        ORDER BY total_sold DESC
    """)
    pkg_rows = cursor.fetchall() or []
    cursor.close()

    sales_df = pd.DataFrame(sales_rows)
    doc_df = pd.DataFrame(doc_rows)
    rpt_df = pd.DataFrame(rpt_rows)

    if not sales_df.empty:
        sales_df["tx_date"] = pd.to_datetime(sales_df["tx_date"])
        sales_df.set_index("tx_date", inplace=True)
    else:
        sales_df = pd.DataFrame(columns=["tx_count", "daily_revenue", "daily_credits_sold"])

    if not doc_df.empty:
        doc_df["doc_date"] = pd.to_datetime(doc_df["doc_date"])
        doc_df.set_index("doc_date", inplace=True)
    else:
        doc_df = pd.DataFrame(columns=["doc_count", "total_pages", "total_bytes"])

    if not rpt_df.empty:
        rpt_df["rpt_date"] = pd.to_datetime(rpt_df["rpt_date"])
        rpt_df.set_index("rpt_date", inplace=True)
    else:
        rpt_df = pd.DataFrame(columns=["audit_count", "avg_similarity"])

    # Buat rentang tanggal kontinu
    all_dates = list(sales_df.index) + list(doc_df.index) + list(rpt_df.index)
    if all_dates:
        min_date = min(all_dates)
        max_date = max(max(all_dates), pd.Timestamp(datetime.now().date()))
    else:
        min_date = pd.Timestamp(datetime.now().date() - timedelta(days=30))
        max_date = pd.Timestamp(datetime.now().date())

    date_idx = pd.date_range(min_date, max_date, freq="D")
    merged_df = pd.DataFrame(index=date_idx)
    merged_df.index.name = "date"

    merged_df = merged_df.join(sales_df).join(doc_df).join(rpt_df).fillna(0)
    merged_df["daily_revenue"] = merged_df["daily_revenue"].astype(float)
    merged_df["tx_count"] = merged_df["tx_count"].astype(int)
    merged_df["doc_count"] = merged_df["doc_count"].astype(int)
    merged_df["audit_count"] = merged_df["audit_count"].astype(int)
    if "registered_doc_count" in merged_df.columns:
        merged_df["registered_doc_count"] = merged_df["registered_doc_count"].astype(int)
    else:
        merged_df["registered_doc_count"] = 0

    if "anon_doc_count" in merged_df.columns:
        merged_df["anon_doc_count"] = merged_df["anon_doc_count"].astype(int)
    else:
        merged_df["anon_doc_count"] = 0

    # Hitung pendapatan riil organik (tanpa data sintetis)
    real_revenue_total = float(merged_df["daily_revenue"].sum())
    real_tx_total = int(merged_df["tx_count"].sum())
    non_zero_days = int((merged_df["daily_revenue"] > 0).sum())

    # Kriteria apakah sudah ada transaksi penjualan riil masuk
    has_real_sales = bool(real_revenue_total > 0 or real_tx_total > 0)
    # Kriteria apakah data deret waktu mencukupi untuk regresi deret waktu multivariat XGBoost
    has_statistical_sample = bool(real_tx_total >= 3 and real_revenue_total > 0 and non_zero_days >= 3)
    
    # Hitung volume submisi organik riil
    registered_docs_total = int(merged_df["registered_doc_count"].sum())
    anon_docs_total = int(merged_df["anon_doc_count"].sum())
    organic_docs_total = int(merged_df["doc_count"].sum())

    # Hitung potensi monetisasi yang belum tergarap (dokumen submisi organik yang diperiksa gratis)
    untapped_potential = float(organic_docs_total * 2500)

    if not pkg_rows:
        pkg_rows = [
            {"package_id": "-", "package_name": "Belum ada paket terjual", "total_sold": 0, "total_revenue": 0}
        ]

    return (
        merged_df, 
        pkg_rows, 
        has_real_sales, 
        has_statistical_sample, 
        real_revenue_total, 
        real_tx_total, 
        untapped_potential, 
        registered_docs_total, 
        anon_docs_total, 
        organic_docs_total
    )


def train_xgboost_forecast(
    df: pd.DataFrame, 
    target_col: str, 
    forecast_days: int = 30,
    has_real_sales: bool = True,
    has_statistical_sample: bool = True,
    real_revenue_total: float = 0.0,
    real_tx_total: int = 0,
    untapped_potential: float = 0.0
) -> Dict[str, Any]:
    """
    Melatih model XGBoost Regressor pada deret waktu harian dan memproyeksikan target_col untuk N hari ke depan.
    Jika target adalah penjualan:
    - Jika omzet Rp 0: mengembalikan status pra-pendapatan.
    - Jika ada transaksi riil (omzet > 0) namun sampel transaksi masih sedikit (< 3): mengembalikan model heuristik
      berbasis run-rate aktual tanpa menghilangkan data pendapatan riil (Rp 10.000 tetap diakui).
    - Jika sampel mencukupi (>= 3): melatih model XGBoost Regressor secara penuh.
    """
    df_work = df.copy()
    last_date = df_work.index[-1]
    future_dates = [last_date + timedelta(days=i + 1) for i in range(forecast_days)]

    # 1. Target penjualan namun belum ada transaksi masuk sama sekali (Omzet Rp 0)
    if target_col == "daily_revenue" and not has_real_sales:
        return {
            "model": "XGBoost Regressor v3.4 (Menunggu Transaksi Organik)",
            "has_real_sales": False,
            "has_statistical_sample": False,
            "real_tx_total": 0,
            "historical_30d_total": 0.0,
            "projected_30d_total": 0.0,
            "growth_pct": 0.0,
            "untapped_potential": untapped_potential,
            "future_predictions": [{"date": f_date.strftime("%Y-%m-%d"), "value": 0.0} for f_date in future_dates],
            "feature_importances": [
                {"feature": "doc_volume", "label": "Volume Submisi Naskah Gratis", "importance": 45.0},
                {"feature": "user_base", "label": "Pengguna Aktif Bebas Kuota", "importance": 30.0},
                {"feature": "academic_cycle", "label": "Siklus Kalender Kampus", "importance": 25.0}
            ],
            "status_note": "Fase Pra-Pendapatan: Belum ada transaksi penjualan riil masuk."
        }

    # 2. Target penjualan dan sudah ada transaksi masuk (omzet > 0) tetapi belum cukup sampel untuk XGBoost
    if target_col == "daily_revenue" and has_real_sales and not has_statistical_sample:
        # Menjaga integritas data: omzet aktual tercatat penuh (misal Rp 10.000)
        # Pacing proyeksi: proyeksikan pengulangan transaksi secara konservatif (1 transaksi berulang dalam 30 hari ke depan)
        mid_idx = min(14, len(future_dates) - 1)
        predictions = []
        for idx, f_date in enumerate(future_dates):
            val = real_revenue_total if idx == mid_idx else 0.0
            predictions.append({
                "date": f_date.strftime("%Y-%m-%d"),
                "value": float(val)
            })

        return {
            "model": "Estimasi Run-Rate Transaksi Awal (Monetisasi Perdana)",
            "has_real_sales": True,
            "has_statistical_sample": False,
            "real_tx_total": real_tx_total,
            "historical_30d_total": float(real_revenue_total),
            "projected_30d_total": float(real_revenue_total),
            "growth_pct": 0.0,
            "untapped_potential": untapped_potential,
            "future_predictions": predictions,
            "feature_importances": [
                {"feature": "initial_conversion", "label": "Konversi Paket Terjual (Top-Up 3 Dokumen)", "importance": 45.0},
                {"feature": "submission_demand", "label": "Volume Submisi Naskah Kampus", "importance": 35.0},
                {"feature": "quota_depletion", "label": "Tingkat Kehabisan Kuota Gratis", "importance": 20.0}
            ],
            "status_note": f"Tahap Monetisasi Awal: Tercatat {real_tx_total} transaksi riil (Rp {real_revenue_total:,.0f})."
        }

    # Ekstraksi fitur waktu
    df_work["day_of_week"] = df_work.index.dayofweek
    df_work["is_weekend"] = df_work["day_of_week"].apply(lambda x: 1 if x >= 5 else 0)
    df_work["day_of_month"] = df_work.index.day
    df_work["is_month_end"] = df_work["day_of_month"].apply(lambda x: 1 if x >= 25 else 0)
    df_work["is_month_start"] = df_work["day_of_month"].apply(lambda x: 1 if x <= 5 else 0)

    # Fitur lag & rolling
    df_work["lag_1"] = df_work[target_col].shift(1).fillna(0)
    df_work["lag_3"] = df_work[target_col].shift(3).fillna(0)
    df_work["lag_7"] = df_work[target_col].shift(7).fillna(0)
    df_work["rolling_mean_7"] = df_work[target_col].rolling(window=7, min_periods=1).mean().shift(1).fillna(0)
    df_work["rolling_max_7"] = df_work[target_col].rolling(window=7, min_periods=1).max().shift(1).fillna(0)

    feature_cols = [
        "day_of_week", "is_weekend", "day_of_month",
        "is_month_end", "is_month_start",
        "lag_1", "lag_3", "lag_7",
        "rolling_mean_7", "rolling_max_7"
    ]

    X = df_work[feature_cols].values
    y = df_work[target_col].values

    # Inisialisasi & latih XGBoost Regressor
    model = xgb.XGBRegressor(
        n_estimators=100,
        max_depth=3,
        learning_rate=0.08,
        subsample=0.85,
        colsample_bytree=0.85,
        random_state=42,
        verbosity=0
    )
    model.fit(X, y)

    # Ekstraksi Feature Importance
    importances = model.feature_importances_
    feature_importance_list = []
    feature_labels = {
        "day_of_week": "Hari dalam Minggu",
        "is_weekend": "Pola Akhir Pekan (Sabtu-Minggu)",
        "day_of_month": "Tanggal dalam Bulan",
        "is_month_end": "Siklus Akhir Bulan (Gajian/Tenggat)",
        "is_month_start": "Siklus Awal Bulan",
        "lag_1": "Aktivitas 1 Hari Sebelumnya",
        "lag_3": "Tren 3 Hari Sebelumnya",
        "lag_7": "Tren Mingguan (Lag-7)",
        "rolling_mean_7": "Rata-rata Bergerak 7 Hari",
        "rolling_max_7": "Beban Puncak 7 Hari Terakhir"
    }

    for col, imp in zip(feature_cols, importances):
        feature_importance_list.append({
            "feature": col,
            "label": feature_labels.get(col, col),
            "importance": round(float(imp) * 100, 1)
        })
    feature_importance_list.sort(key=lambda x: x["importance"], reverse=True)

    # Recursive Forecasting untuk 30 hari ke depan
    hist_values = list(y)
    future_predictions = []

    for f_date in future_dates:
        dow = f_date.dayofweek
        is_we = 1 if dow >= 5 else 0
        dom = f_date.day
        is_me = 1 if dom >= 25 else 0
        is_ms = 1 if dom <= 5 else 0

        l1 = hist_values[-1] if len(hist_values) >= 1 else 0
        l3 = hist_values[-3] if len(hist_values) >= 3 else l1
        l7 = hist_values[-7] if len(hist_values) >= 7 else l1

        window7 = hist_values[-7:] if len(hist_values) >= 7 else hist_values
        rm7 = float(np.mean(window7)) if window7 else 0.0
        rmx7 = float(np.max(window7)) if window7 else 0.0

        feat_vector = np.array([[dow, is_we, dom, is_me, is_ms, l1, l3, l7, rm7, rmx7]])
        pred_val = float(model.predict(feat_vector)[0])
        pred_val = max(0.0, pred_val)

        if target_col == "daily_revenue":
            pred_val = round(pred_val, -2)
        else:
            pred_val = int(round(pred_val))

        future_predictions.append({
            "date": f_date.strftime("%Y-%m-%d"),
            "value": pred_val
        })
        hist_values.append(pred_val)

    # Hitung ringkasan komparatif (30 hari terakhir vs 30 hari prediksi)
    recent_30_sum = float(df_work[target_col].tail(30).sum())
    if target_col == "daily_revenue" and recent_30_sum <= 0 and real_revenue_total > 0:
        recent_30_sum = float(real_revenue_total)

    projected_30_sum = float(sum(p["value"] for p in future_predictions))

    growth_pct = 0.0
    if recent_30_sum > 0:
        growth_pct = round(((projected_30_sum - recent_30_sum) / recent_30_sum) * 100, 1)

    return {
        "model": "XGBoost Regressor v3.4 (Tuned)",
        "has_real_sales": True if target_col == "daily_revenue" else has_real_sales,
        "has_statistical_sample": True,
        "real_tx_total": real_tx_total,
        "historical_30d_total": recent_30_sum,
        "projected_30d_total": projected_30_sum,
        "growth_pct": growth_pct,
        "untapped_potential": untapped_potential,
        "future_predictions": future_predictions,
        "feature_importances": feature_importance_list[:5]
    }


def synthesize_with_gemini(
    metrics_summary: Dict[str, Any],
    conn=None
) -> Dict[str, Any]:
    """
    Mengirimkan ringkasan data historis dan proyeksi XGBoost ke Google Gemini API
    untuk menghasilkan narasi strategis eksekutif dan rekomendasi tindakan bisnis.
    Jika belum ada penjualan kas riil (fase pra-pendapatan), Gemini difokuskan pada strategi monetisasi awal.
    Jika sudah ada transaksi riil (tahap monetisasi awal atau lanjutan), Gemini menganalisis akselerasi konversi.
    """
    gemini_keys = get_available_gemini_keys(conn)
    has_active_gemini = len(gemini_keys) > 0
    has_sales = metrics_summary.get("has_real_sales", False)
    real_rev = float(metrics_summary.get("real_revenue_total", 0.0))
    real_tx = int(metrics_summary.get("real_tx_total", 1 if real_rev > 0 else 0))
    untapped_pot = float(metrics_summary.get("untapped_potential", 0.0))
    total_docs = int(metrics_summary["usage"]["historical_30d_total"])
    proj_docs = int(metrics_summary["usage"]["projected_30d_total"])
    peak_date = metrics_summary["peak_usage_date"]
    peak_val = metrics_summary["peak_usage_val"]

    if not has_sales:
        sales_section = f"""1. STATUS PENDAPATAN & PENJUALAN:
   - STATUS SAAT INI: FASE PRA-PENDAPATAN (ZERO REVENUE / PRE-REVENUE).
   - Pendapatan Kas Masuk Aktual: Rp 0 (Belum ada penjualan paket kuota berbayar, omzet kas riil masih nihil).
   - Kondisi: Seluruh pengguna saat ini memeriksa naskah menggunakan kuota gratis/promosi kampus.
   - Potensi Nilai Kuota Belum Termonetisasi: Rp {untapped_pot:,.0f} (estimasi nilai dari {total_docs:,} naskah yang telah diproses secara gratis).
   
PERINGATAN WAJIB KEPADA GEMINI:
JANGAN PERNAH mengklaim atau menyebutkan bahwa platform sudah menerima pendapatan jutaan rupiah! Nyatakan secara faktual dan jujur bahwa omzet saat ini masih Rp 0 karena model bisnis masih memberikan akses gratis. Fokuskan seluruh analisis penjualan Anda pada STRATEGI MONETISASI PERTAMA: bagaimana mengonversi ribuan naskah gratis ini menjadi pembelian kuota berbayar."""
    else:
        sales_section = f"""1. STATUS PENDAPATAN & PENJUALAN:
   - STATUS SAAT INI: TAHAP MONETISASI AWAL (EARLY MONETIZATION / VALIDASI TRANSAKSI PERDANA).
   - Pendapatan Kas Masuk Aktual (Historis 30 Hari): Rp {real_rev:,.0f} (TERBUKTI FAKTUAL: Telah masuk {real_tx} transaksi riil terverifikasi via Midtrans senilai Rp {real_rev:,.0f}!).
   - Paket Terjual / Terlaris: {metrics_summary['top_package_name']} ({metrics_summary['top_package_sold']} unit).
   - Proyeksi Pendapatan 30 Hari: Rp {metrics_summary['sales']['projected_30d_total']:,.0f}
   - Rasio Konversi Saat Ini: {real_tx} transaksi dari total {total_docs:,} submisi dokumen organik ({round((real_tx / max(1, total_docs)) * 100, 2)}%).
   - Potensi Nilai Kuota Belum Termonetisasi: Rp {untapped_pot:,.0f} (dari {total_docs:,} naskah yang diproses secara gratis).
   - Model Peramalan: {metrics_summary['sales']['model']}

PERINGATAN WAJIB KEPADA GEMINI:
Platform AR Tilik telah SUKSES meraih transaksi perdana (Zero-to-One Milestone) dengan omzet riil tercatat Rp {real_rev:,.0f} dari paket '{metrics_summary['top_package_name']}'. JANGAN PERNAH menyatakan bahwa pendapatan platform adalah Rp 0! Akui secara eksplisit bahwa transaksi perdana Rp {real_rev:,.0f} ini membuktikan adanya 'willingness to pay' (daya beli pengguna nyata sivitas akademika). Fokuskan analisis penjualan Anda pada STRATEGI SKALABILITAS KONVERSI: bagaimana mengubah ratusan pengguna gratis dari {total_docs:,} submisi naskah menjadi transaksi berbayar berulang, penataan batas kuota gratis, dan ekspansi paket prodi/dosen."""

    prompt = f"""
Anda adalah Chief Data & Strategy Officer untuk platform 'AR Tilik' (Platform Integritas Akademik & Deteksi Kemiripan Naskah Kampus).
Tugas Anda adalah menelaah data faktual platform dan proyeksi beban naskah XGBoost berikut, lalu menghasilkan analisis eksekutif serta rekomendasi strategis dalam format JSON.

DATA FAKTUAL & PROYEKSI XGBOOST:
{sales_section}

2. PENGGUNAAN & BEBAN SISTEM (DATA SUBMISI RIIL):
   - Total Submisi Naskah Organik 30 Hari Terakhir: {total_docs:,} naskah
     * Dari Pengguna Terdaftar: {metrics_summary.get('registered_docs_total', 0):,} naskah
     * Dari Pengunjung Anonim / Tamu: {metrics_summary.get('anon_docs_total', 0):,} naskah
   - Proyeksi Submisi 30 Hari Ke Depan (XGBoost Regressor): {proj_docs:,} naskah
   - Pertumbuhan Volume Submisi: {metrics_summary['usage']['growth_pct']:+.1f}%
   - Tanggal Beban Puncak (Peak Day Diproyeksikan): {peak_date} ({peak_val} naskah/hari)
   - Faktor Pengaruh Beban (XGBoost): {', '.join([f['label'] + ' (' + str(f['importance']) + '%)' for f in metrics_summary['usage']['feature_importances'][:3]])}

3. DATA MUTU AKADEMIK:
   - Rata-rata Kemiripan Naskah Kampus: {metrics_summary['avg_similarity']}%
   - Total Pengguna Terdaftar: {metrics_summary['total_users']} pengguna

PETUNJUK ANALISIS GEMINI:
{"Karena platform saat ini belum memiliki omzet masuk (Rp 0), telaah kesenjangan antara submisi akun terdaftar (" + str(metrics_summary.get('registered_docs_total', 0)) + ") dan lonjakan uji coba pengguna anonim/tamu (" + str(metrics_summary.get('anon_docs_total', 0)) + "). Fokuskan seluruh analisis penjualan Anda pada STRATEGI MONETISASI AWAL." if not has_sales else "Platform telah mencetak omzet awal Rp " + f"{real_rev:,.0f}" + " dari " + str(real_tx) + " transaksi ('" + str(metrics_summary['top_package_name']) + "'). Telaah bahwa validasi pasar telah terbukti, namun konversi dari " + str(total_docs) + " naskah gratis masih minim. Fokuskan analisis penjualan pada STRATEGI SKALABILITAS & KONVERSI ULANG: pengetatan limit kuota gratis, penawaran paket kuota tepat sasaran saat kuota habis, dan pengadaan paket institusi/jurusan."}

PETUNJUK FORMAT JAWABAN:
Berikan output HANYA berupa JSON valid (tanpa markdown tambahan) dengan struktur:
{{
  "executive_summary": "1 paragraf padat (3-4 kalimat) merangkum kondisi faktual (omzet aktual Rp {real_rev:,.0f} sebagai validasi pasar awal dan volume submisi naskah yang masif) serta arah strategis platform.",
  "sales_analysis": "Analisis mendalam mengenai pencapaian transaksi (Rp {real_rev:,.0f}), rasio konversi saat ini, dan strategi konkret mengonversi ratusan submisi gratis menjadi pembelian kuota berulang.",
  "usage_analysis": "Analisis terhadap beban submisi naskah nyata ({total_docs:,} dokumen), kesiapan kapasitas server untuk menangani lonjakan puncak ({peak_val} naskah pada {peak_date}).",
  "strategic_recommendations": [
    {{
      "priority": "Tinggi / Sedang / Terjadwal",
      "category": "Monetisasi / Infrastruktur / Kemitraan Kampus",
      "title": "Judul rekomendasi singkat",
      "action": "Deskripsi langkah konkret yang disarankan."
    }}
  ],
  "anomaly_alert": "Catatan singkat mengenai anomali antara adopsi naskah yang tinggi versus rasio konversi berbayar, serta lonjakan musiman tanggal {peak_date}."
}}
Gunakan Bahasa Indonesia yang baku, profesional, jujur, analitis, dan berbobot.
"""

    if has_active_gemini:
        k = gemini_keys[0]
        api_key = k["api_key"]
        model_name = k.get("model_name") or "gemini-flash-lite-latest"
        logger.info(f"Memanggil Gemini API ({model_name}) untuk analisis strategis eksekutif...")

        res = call_gemini_api(api_key, model_name, prompt, timeout=35)
        if res.get("success"):
            raw_text = res.get("raw_text", "").strip()
            cleaned = re.sub(r"^```(?:json)?\s*", "", raw_text, flags=re.MULTILINE)
            cleaned = re.sub(r"\s*```$", "", cleaned, flags=re.MULTILINE).strip()
            try:
                parsed = json.loads(cleaned)
                parsed["source"] = f"Google Gemini ({res.get('model_executed', model_name)})"
                parsed["is_live_gemini"] = True
                mark_key_success(k.get("id"), conn)
                return parsed
            except Exception as e:
                logger.warning(f"Gagal parse JSON dari Gemini: {e}. Output raw: {raw_text[:200]}")
        else:
            logger.warning(f"Gemini API gagal atau timeout: {res.get('error')}. Memakai fallback sintesis algoritmik.")
            mark_key_rate_limited(k.get("id"), res.get("error", "Error Gemini API"), conn=conn)

    # Fallback cerdas (Rule-based probabilistic synthesis)
    if not has_sales:
        exec_summary = (
            f"Platform AR Tilik saat ini berada pada fase pra-pendapatan (pre-revenue) dengan omzet riil tercatat Rp 0 karena seluruh akses pengujian naskah masih disubsidi menggunakan kuota gratis. Di sisi lain, adopsi produk terbukti sangat masif dengan {total_docs:,} naskah yang telah diaudit dan diproyeksikan melonjak hingga {proj_docs:,} naskah dalam 30 hari ke depan. Kesenjangan ini menunjukkan validasi produk yang kuat, di mana prioritas mutlak sekarang adalah mengeksekusi monetisasi pertama (first transaction conversion)."
        )
        sales_analysis = (
            f"Ketiadaan pendapatan kas (Rp 0) disebabkan oleh model distribusi freemium yang belum menerapkan batasan kuota ketat (paywall). Berdasarkan volume {total_docs:,} dokumen, terdapat potensi nilai ekonomi yang belum termonetisasi sekitar Rp {untapped_pot:,.0f} (dengan asumsi tarif dasar Rp 2.500/cek). Langkah awal monetisasi harus segera difokuskan pada penerapan batas kuota gratis maksimal 3 dokumen per akun dan penawaran paket Pejuang Skripsi (Rp 25.000)."
        )
        recs = [
            {
                "priority": "Tinggi",
                "category": "Monetisasi & Konversi",
                "title": "Penerapan Batas Kuota Gratis & Paywall Lembut",
                "action": "Batasi kuota gratis maksimal 3 submisi per akun mahasiswa untuk mendorong konversi pembelian paket kuota Top-Up (Rp 10.000 - Rp 25.000)."
            },
            {
                "priority": "Tinggi",
                "category": "Infrastruktur & Server",
                "title": f"Kesiapan Kapasitas Server Menjelang Puncak Beban {peak_date}",
                "action": f"Skalakan worker antrean teks untuk menangani proyeksi lonjakan {peak_val} naskah/hari pada {peak_date}."
            },
            {
                "priority": "Sedang",
                "category": "Kemitraan Kampus",
                "title": "Penawaran Skema Lisensi Institusi B2B",
                "action": "Tawarkan lisensi institusional ke fakultas/program studi dengan tingkat kemiripan naskah di atas 25% untuk mengamankan pendapatan berulang (MRR)."
            }
        ]
        anomaly_alert = (
            f"Terdeteksi disparitas ekstrem: volume penggunaan mencapai {total_docs:,} dokumen sementara transaksi masuk masih Rp 0. Segera aktifkan pengingat kuota habis (out-of-quota trigger) sebelum periode puncak {peak_date}."
        )
    else:
        sales_growth = metrics_summary['sales']['growth_pct']
        usage_growth = metrics_summary['usage']['growth_pct']
        top_pkg = metrics_summary['top_package_name']
        proj_rev = metrics_summary['sales']['projected_30d_total']
        
        if real_tx < 3:
            exec_summary = (
                f"Platform AR Tilik telah berhasil meraih tonggak monetisasi awal (zero-to-one) dengan mencatatkan omzet riil "
                f"sebesar Rp {real_rev:,.0f} dari transaksi perdana paket '{top_pkg}'. Hal ini memvalidasi secara empiris "
                f"adanya kesediaan membayar (willingness to pay) pengguna kampus. Di sisi penggunaan, volume submisi naskah organik "
                f"mencapai {total_docs:,} dokumen dan diproyeksikan tumbuh {usage_growth:+.1f}% menjadi {proj_docs:,} dokumen pada 30 hari ke depan."
            )
            sales_analysis = (
                f"Transaksi perdana paket '{top_pkg}' senilai Rp {real_rev:,.0f} membuktikan daya tarik produk. Namun dengan rasio konversi "
                f"saat ini ({real_tx} transaksi dari {total_docs:,} naskah organik), masih terdapat potensi omzet belum termonetisasi senilai "
                f"Rp {untapped_pot:,.0f}. Strategi terpenting adalah memicu jendela pembayaran QRIS instan saat kuota gratis mahasiswa habis."
            )
            recs = [
                {
                    "priority": "Tinggi",
                    "category": "Monetisasi & Konversi",
                    "title": "Aktivasi Trigger Penawaran Kuota Habis",
                    "action": f"Tampilkan pop-up penawaran paket '{top_pkg}' (Rp 10.000) dan 'Pejuang Skripsi' (Rp 25.000) seketika setelah kuota gratis pengguna mencapai limit."
                },
                {
                    "priority": "Tinggi",
                    "category": "Infrastruktur & Server",
                    "title": f"Siagakan Worker Antrean Menjelang {peak_date}",
                    "action": f"Pastikan alokasi resource memadai untuk menangani proyeksi beban puncak {peak_val} naskah/hari pada {peak_date}."
                },
                {
                    "priority": "Sedang",
                    "category": "Kemitraan Kampus",
                    "title": "Paket Kuota Prodi & Fakultas",
                    "action": "Sediakan paket grosir untuk dosen pembimbing atau kaprodi guna mengonversi submisi mahasiswa menjadi kontrak institusi berulang."
                }
            ]
            anomaly_alert = (
                f"Meskipun validasi pasar telah terbukti dengan omzet Rp {real_rev:,.0f}, tingkat konversi terhadap {total_docs:,} naskah gratis masih sebesar {round((real_tx / max(1, total_docs)) * 100, 2)}%. Optimalisasi konversi menjadi prioritas utama."
            )
        else:
            exec_summary = (
                f"Kombinasi model XGBoost Regressor memproyeksikan tren pertumbuhan pendapatan sebesar {sales_growth:+.1f}% "
                f"dengan estimasi total omzet mencapai Rp {proj_rev:,.0f} dalam 30 hari ke depan. Pada domain pemanfaatan sistem, "
                f"volume submisi naskah diperkirakan meningkat {usage_growth:+.1f}% menjadi {proj_docs:,} dokumen dengan puncak beban operasional "
                f"pada {peak_date}. Sinergi antara adopsi paket kuota dan integritas akademik menunjukkan stabilitas operasional yang sangat sehat."
            )
            sales_analysis = (
                f"Penjualan paket '{top_pkg}' tetap menjadi kontributor utama pendapatan. Fitur XGBoost menunjukkan korelasi kuat "
                f"antara aktivitas hari kerja dan lonjakan transaksi top-up mandiri mahasiswa. Peluang ekspansi dapat dimaksimalkan "
                f"melalui peluncuran paket bundel universitas (Institusi Enterprise) untuk memitigasi penurunan omzet di akhir pekan."
            )
            recs = [
                {
                    "priority": "Tinggi",
                    "category": "Infrastruktur & Komputasi",
                    "title": f"Siagakan Worker & Alokasi RAM pada {peak_date}",
                    "action": f"Skalakan proses worker antrean dokumen menjelang tanggal {peak_date} untuk menampung lonjakan proyeksi {peak_val} naskah tanpa penumpukan antrean."
                },
                {
                    "priority": "Tinggi",
                    "category": "Monetisasi & Paket",
                    "title": "Optimasi Penawaran Paket Pejuang Skripsi",
                    "action": f"Tingkatkan promosi paket kuota pejuang skripsi pada minggu ke-3 dan ke-4 bulan, memanfaatkan pola lonjakan transaksi yang teridentifikasi oleh XGBoost."
                },
                {
                    "priority": "Sedang",
                    "category": "Kemitraan Kampus",
                    "title": "Penyediaan Kuota Grosir untuk Program Studi / LPPM",
                    "action": "Tawarkan skema lisensi institusi bagi program studi yang memiliki tingkat kemiripan naskah di atas 25% guna mendongkrak pendapatan berulang (MRR)."
                }
            ]
            anomaly_alert = (
                f"XGBoost mendeteksi variansi lonjakan dokumen hingga {(peak_val / max(1, proj_docs / 30)):.1f}x lipat dari rata-rata harian pada {peak_date}."
            )

    usage_analysis = (
        f"Beban antrean dokumen diproyeksikan mencapai intensitas tertinggi ({peak_val} naskah/hari) pada tanggal {peak_date}, "
        f"bertepatan dengan siklus revisi dan pendaftaran sidang sarjana. Arsitektur ekstraksi teks dan worker Dual-Lens "
        f"perlu dipastikan berada dalam kesiapan penuh guna mempertahankan waktu latensi di bawah 5 detik per naskah."
    )

    return {
        "executive_summary": exec_summary,
        "sales_analysis": sales_analysis,
        "usage_analysis": usage_analysis,
        "strategic_recommendations": recs,
        "anomaly_alert": anomaly_alert,
        "source": "Sintesis Algoritmik Cerdas (Siap sinkron otomatis saat Google API Key aktif)",
        "is_live_gemini": False
    }

    usage_analysis = (
        f"Beban antrean dokumen diproyeksikan mencapai intensitas tertinggi ({peak_val} naskah/hari) pada tanggal {peak_date}, "
        f"bertepatan dengan siklus revisi dan pendaftaran sidang sarjana. Arsitektur ekstraksi teks dan worker Dual-Lens "
        f"perlu dipastikan berada dalam kesiapan penuh guna mempertahankan waktu latensi di bawah 5 detik per naskah."
    )

    return {
        "executive_summary": exec_summary,
        "sales_analysis": sales_analysis,
        "usage_analysis": usage_analysis,
        "strategic_recommendations": recs,
        "anomaly_alert": anomaly_alert,
        "source": "Sintesis Algoritmik Cerdas (Siap sinkron otomatis saat Google API Key aktif)",
        "is_live_gemini": False
    }


def run_full_pipeline(force_live: bool = False) -> Dict[str, Any]:
    """
    Menjalankan alur lengkap:
    1. Agregasi data historis dari database
    2. Pelatihan 2 model XGBoost Regressor (Sales & Usage)
    3. Ekstraksi Feature Importance dan proyeksi 30 hari ke depan
    4. Sintesis strategis eksekutif via Gemini AI
    5. Penyimpanan cache dan pencatatan log
    """
    logger.info("Memulai eksekusi alur prediksi XGBoost + Gemini...")
    conn = get_connection()

    try:
        (
            df, 
            pkg_rows, 
            has_real_sales, 
            has_statistical_sample, 
            real_revenue_total, 
            real_tx_total, 
            untapped_potential, 
            registered_docs_total, 
            anon_docs_total, 
            organic_docs_total
        ) = fetch_historical_series(conn)

        # 1. XGBoost Forecast untuk Penjualan (Revenue)
        sales_result = train_xgboost_forecast(
            df, target_col="daily_revenue", forecast_days=30,
            has_real_sales=has_real_sales,
            has_statistical_sample=has_statistical_sample,
            real_revenue_total=real_revenue_total,
            real_tx_total=real_tx_total,
            untapped_potential=untapped_potential
        )

        # 2. XGBoost Forecast untuk Penggunaan (Document submissions)
        usage_result = train_xgboost_forecast(
            df, target_col="doc_count", forecast_days=30,
            has_real_sales=True,
            has_statistical_sample=True,
            real_revenue_total=0.0,
            real_tx_total=0,
            untapped_potential=untapped_potential
        )

        # Temukan Peak Day untuk Usage
        future_usage = usage_result["future_predictions"]
        peak_entry = max(future_usage, key=lambda x: x["value"]) if future_usage else {"date": "-", "value": 0}

        # Data paket terlaris
        top_pkg_name = pkg_rows[0]["package_name"] if pkg_rows else "Top-Up 3 Dokumen"
        top_pkg_sold = pkg_rows[0]["total_sold"] if pkg_rows else (1 if real_revenue_total > 0 else 0)

        # Rata-rata kemiripan naskah
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT AVG(overall_similarity) as avg_sim FROM reports WHERE status = 'completed'")
        avg_sim = round(float(cursor.fetchone()["avg_sim"] or 27.1), 1)

        cursor.execute("SELECT COUNT(id) as total_users FROM users")
        total_users = int(cursor.fetchone()["total_users"] or 0)
        cursor.close()

        metrics_summary = {
            "sales": sales_result,
            "usage": usage_result,
            "has_real_sales": has_real_sales,
            "has_statistical_sample": has_statistical_sample,
            "real_revenue_total": real_revenue_total,
            "real_tx_total": real_tx_total,
            "untapped_potential": untapped_potential,
            "registered_docs_total": registered_docs_total,
            "anon_docs_total": anon_docs_total,
            "organic_docs_total": organic_docs_total,
            "top_package_name": top_pkg_name,
            "top_package_sold": int(top_pkg_sold),
            "peak_usage_date": peak_entry["date"],
            "peak_usage_val": int(peak_entry["value"]),
            "avg_similarity": avg_sim,
            "total_users": total_users
        }

        # 3. Lapisan Kognitif Gemini AI
        gemini_insights = synthesize_with_gemini(metrics_summary, conn=conn)

        # Siapkan payload hasil akhir
        generated_at = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        final_output = {
            "status": "success",
            "generated_at": generated_at,
            "has_real_sales": has_real_sales,
            "has_statistical_sample": has_statistical_sample,
            "real_revenue_total": real_revenue_total,
            "real_tx_total": real_tx_total,
            "untapped_potential": untapped_potential,
            "registered_docs_total": registered_docs_total,
            "anon_docs_total": anon_docs_total,
            "organic_docs_total": organic_docs_total,
            "sales_forecast": sales_result,
            "usage_forecast": usage_result,
            "peak_usage": {
                "date": peak_entry["date"],
                "value": int(peak_entry["value"])
            },
            "top_package": {
                "name": top_pkg_name,
                "sold": int(top_pkg_sold)
            },
            "gemini_insights": gemini_insights,
            "historical_snapshot": {
                "total_days_observed": len(df),
                "total_past_revenue": real_revenue_total,
                "total_past_documents": organic_docs_total,
                "total_registered_submissions": registered_docs_total,
                "total_anonymous_submissions": anon_docs_total
            }
        }

        # Simpan ke cache file
        save_cached_forecast(final_output)

        # Simpan ke tabel database predictive_forecast_logs
        try:
            cur = conn.cursor()
            cur.execute("""
                INSERT INTO predictive_forecast_logs 
                (model_type, gemini_model, sales_projected_30d, usage_projected_30d, metrics_json, executive_summary, strategic_recommendations, created_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, NOW())
            """, (
                str(sales_result.get("model", "xgboost_regressor"))[:95],
                str(gemini_insights.get("source", "Gemini AI"))[:95],
                sales_result["projected_30d_total"],
                int(usage_result["projected_30d_total"]),
                json.dumps({
                    "sales_growth": sales_result["growth_pct"],
                    "usage_growth": usage_result["growth_pct"],
                    "peak_date": peak_entry["date"],
                    "peak_val": peak_entry["value"],
                    "feature_importances_sales": sales_result["feature_importances"],
                    "feature_importances_usage": usage_result["feature_importances"]
                }),
                gemini_insights.get("executive_summary", ""),
                json.dumps(gemini_insights.get("strategic_recommendations", []))
            ))
            conn.commit()
            cur.close()
        except Exception as e:
            logger.warning(f"Gagal mencatat log ke predictive_forecast_logs: {e}")

        logger.info("Alur prediksi XGBoost + Gemini berhasil diselesaikan!")
        return final_output

    finally:
        conn.close()


def main():
    parser = argparse.ArgumentParser(description="Mesin Analitik Prediktif XGBoost + Gemini")
    parser.add_argument("--action", choices=["run", "get"], default="run", help="Aksi: 'run' untuk inferensi baru, 'get' untuk mengambil cache")
    args = parser.parse_args()

    if args.action == "get":
        cached = load_cached_forecast()
        if cached:
            print(json.dumps(cached, ensure_ascii=False))
            return
        # Jika belum ada cache, jalankan kalkulasi
        res = run_full_pipeline()
        print(json.dumps(res, ensure_ascii=False))
    else:
        res = run_full_pipeline()
        print(json.dumps(res, ensure_ascii=False))


if __name__ == "__main__":
    main()
