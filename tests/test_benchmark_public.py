import os
import urllib.request
import json
import pytest

BASE_URL = os.getenv("APP_BASE_URL", "http://localhost:8085")

def test_public_benchmark_page_renders():
    url = f"{BASE_URL}/benchmark"
    req = urllib.request.Request(url, method="GET")
    with urllib.request.urlopen(req, timeout=15) as resp:
        assert resp.getcode() == 200
        html = resp.read().decode("utf-8")
        assert "Benchmark Terbuka Naskah Akademik Bahasa Indonesia" in html
        assert "Deteksi Konteks Sitasi (Layer B)" in html
        assert "Catatan Kejujuran Metrik: Memahami Skor 100% vs 14 Mismatch" in html
        assert "dokumentasi resmi Turnitin" in html
        assert "14 Kasus Mismatch" in html
        assert "Methodology Boilerplate" in html
        assert "Sentence Burstiness" in html
        assert "Dual-Reader" in html

def test_public_benchmark_alternate_route_renders():
    url = f"{BASE_URL}/riset/benchmark-naskah-indonesia"
    req = urllib.request.Request(url, method="GET")
    with urllib.request.urlopen(req, timeout=15) as resp:
        assert resp.getcode() == 200
        html = resp.read().decode("utf-8")
        assert "Benchmark Terbuka Naskah Akademik Bahasa Indonesia" in html

def test_download_targeted_80_dataset():
    for endpoint in ["/benchmark/dataset/targeted_80", "/benchmark/dataset/targeted_80.json"]:
        url = f"{BASE_URL}{endpoint}"
        req = urllib.request.Request(url, method="GET")
        with urllib.request.urlopen(req, timeout=15) as resp:
            assert resp.getcode() == 200
            headers = dict(resp.headers)
            assert "application/json" in headers.get("Content-Type", "")
            assert "attachment" in headers.get("Content-Disposition", "")
            data = json.loads(resp.read().decode("utf-8"))
            assert isinstance(data, list)
            assert len(data) == 80
            assert data[0]["case_id"] == "EV11-UNC-001"

def test_download_v1_500_dataset():
    for endpoint in ["/benchmark/dataset/v1_500", "/benchmark/dataset/v1_500.json"]:
        url = f"{BASE_URL}{endpoint}"
        req = urllib.request.Request(url, method="GET")
        with urllib.request.urlopen(req, timeout=15) as resp:
            assert resp.getcode() == 200
            headers = dict(resp.headers)
            assert "application/json" in headers.get("Content-Type", "")
            assert "attachment" in headers.get("Content-Disposition", "")
            data = json.loads(resp.read().decode("utf-8"))
            assert isinstance(data, list)
            assert len(data) == 500

def test_download_static_dataset_files():
    for path, expected_count in [
        ("/datasets/artilik_indonesia_targeted_contextual_80_cases.json", 80),
        ("/datasets/artilik_indonesia_academic_benchmark_500_cases.json", 500),
    ]:
        url = f"{BASE_URL}{path}"
        req = urllib.request.Request(url, method="GET")
        with urllib.request.urlopen(req, timeout=15) as resp:
            assert resp.getcode() == 200
            data = json.loads(resp.read().decode("utf-8"))
            assert isinstance(data, list)
            assert len(data) == expected_count
