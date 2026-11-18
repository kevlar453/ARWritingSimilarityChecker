#!/usr/bin/env python3
"""
CLI entrypoint untuk Alat Publik /cek-sitasi (v1).
Membaca JSON {"text": "..."} dari stdin, tidak menyimpan teks ke disk/log.
"""

import argparse
import json
import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from worker.database import load_env
from worker.external_citation_verifier import (
    parse_public_bibliography_input,
    verify_public_citation_list,
)


def main() -> int:
    parser = argparse.ArgumentParser(description="AR Tilik Public Citation Checker CLI")
    parser.add_argument("--parse-only", action="store_true", help="Hanya memecah entri & menghitung jumlahnya")
    parser.add_argument("--stream", action="store_true", help="Keluarkan progres NDJSON baris demi baris")
    parser.add_argument("--max-items", type=int, default=10, help="Batas maksimal entri per cek")
    args = parser.parse_args()

    load_env()
    raw_stdin = sys.stdin.read()
    try:
        payload = json.loads(raw_stdin) if raw_stdin.strip() else {}
        text = str(payload.get("text") or "")
    except Exception:
        text = raw_stdin

    if args.parse_only:
        entries = parse_public_bibliography_input(text)
        print(json.dumps({"total_entries": len(entries)}, ensure_ascii=False))
        return 0

    if args.stream:
        parsed = parse_public_bibliography_input(text)
        if not parsed:
            print(json.dumps({
                "event": "error",
                "error_code": "empty",
                "message": "Tidak ada entri sitasi yang terdeteksi. Tempel minimal satu baris rujukan atau DOI.",
            }, ensure_ascii=False), flush=True)
            return 2
        if len(parsed) > args.max_items:
            print(json.dumps({
                "event": "error",
                "error_code": "too_many",
                "total_entries": len(parsed),
                "message": f"Maksimal {args.max_items} sitasi per pemeriksaan. Anda menempel {len(parsed)} entri — silakan kurangi menjadi maksimal {args.max_items} sitasi.",
            }, ensure_ascii=False), flush=True)
            return 2

        print(json.dumps({"event": "start", "total": len(parsed)}, ensure_ascii=False), flush=True)

        def on_progress(item_res, completed, total):
            print(json.dumps({
                "event": "progress",
                "completed": completed,
                "total": total,
                "item": item_res,
            }, ensure_ascii=False), flush=True)

        result = verify_public_citation_list(text, max_items=args.max_items, on_progress=on_progress)
        print(json.dumps({"event": "done", "data": result}, ensure_ascii=False), flush=True)
        return 0

    result = verify_public_citation_list(text, max_items=args.max_items)
    print(json.dumps(result, ensure_ascii=False))
    if result.get("error_code"):
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
