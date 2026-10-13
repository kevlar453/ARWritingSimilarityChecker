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
    url = f"{BASE_URL}/benchmark/dataset/targeted_80"
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
    url = f"{BASE_URL}/benchmark/dataset/v1_500"
    req = urllib.request.Request(url, method="GET")
    with urllib.request.urlopen(req, timeout=15) as resp:
        assert resp.getcode() == 200
        headers = dict(resp.headers)
        assert "application/json" in headers.get("Content-Type", "")
        assert "attachment" in headers.get("Content-Disposition", "")
        data = json.loads(resp.read().decode("utf-8"))
        assert isinstance(data, list)
        assert len(data) == 500
