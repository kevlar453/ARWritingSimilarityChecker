"""
AR Tilik - Evaluation CLI Interface
Menghubungkan perintah evaluasi ke CLI utama ./artilik:
  ./artilik eval generate
  ./artilik eval run
  ./artilik eval review
"""

import argparse
import sys
import json
from pathlib import Path

from evaluation.dataset_generator import generate_full_evaluation_dataset, DATASET_DIR
from evaluation.schema import EvaluationCase
from evaluation.evaluator import EvaluationRunner, print_evaluation_report
from evaluation.reviewer import (
    load_evaluation_data,
    select_informative_cases,
    save_queue_to_file,
    display_review_queue,
    inspect_single_case,
    tag_case_cli
)

def cmd_generate(args):
    print("[*] Menghasilkan 500 kasus Evaluation Dataset v1...")
    summary = generate_full_evaluation_dataset()
    print("\nEvaluation Dataset v1")
    print("---------------------")
    print(f"Seed cases:          {summary['seed_cases']:>3}")
    print(f"Mutation:           {summary['mutation_cases']:>3}")
    print(f"Boundary:           {summary['boundary_cases']:>3}")
    print(f"Adversarial:        {summary['adversarial_cases']:>3}")
    print("--------------------------------")
    print(f"Generated:          {summary['total_cases']:>3}")
    print(f"\nOutput:\n{summary['output_directory']}/\n")

def cmd_run(args):
    cases_file = DATASET_DIR / "cases.json"
    if not cases_file.exists():
        print("[!] Dataset evaluasi belum ditemukan. Menjalankan generator otomatis...")
        generate_full_evaluation_dataset()

    with open(cases_file, "r", encoding="utf-8") as f:
        raw_cases = json.load(f)

    cases = [EvaluationCase.from_dict(d) for d in raw_cases]
    runner = EvaluationRunner()
    report = runner.run_suite(cases, sync_db=(not args.no_db))
    print_evaluation_report(report)

def cmd_review(args):
    cases, results = load_evaluation_data()
    limit = args.limit or 40
    queue = select_informative_cases(cases, results, max_cases=limit)
    save_queue_to_file(queue)

    if args.inspect:
        inspect_single_case(args.inspect, queue)
        return

    if args.tag:
        if not args.decision or not args.label:
            print("[!] Harap sertakan --decision [match|review|no_match] dan --label [Relevant|Common phrase|Properly cited|Needs investigation|False positive]")
            sys.exit(1)
        tag_case_cli(
            case_id=args.tag,
            reviewer_code=args.reviewer or "RV-001",
            human_decision=args.decision.lower(),
            label=args.label,
            system_assessment=args.assessment or "CORRECT",
            notes=args.notes
        )
        return

    if args.interactive:
        print("\n=== MODE TELAAH INTERAKTIF MANUSIA ===")
        print(f"Menampilkan {len(queue)} kasus informatif antrean tertinggi.\n")
        for item in queue:
            inspect_single_case(item["case_id"], queue)
            print("Pilihan:")
            print(" [1] Relevant")
            print(" [2] Common phrase")
            print(" [3] Properly cited")
            print(" [4] Needs investigation")
            print(" [5] False positive")
            print(" [s] Lewati (Skip)")
            print(" [q] Keluar (Quit)")
            
            try:
                choice = input("\nPilihan Label [1-5/s/q]: ").strip()
            except (EOFError, KeyboardInterrupt):
                break
                
            if choice == "q":
                break
            elif choice == "s" or not choice:
                continue
                
            label_map = {
                "1": "Relevant",
                "2": "Common phrase",
                "3": "Properly cited",
                "4": "Needs investigation",
                "5": "False positive"
            }
            selected_label = label_map.get(choice)
            if selected_label:
                dec = input(f"Keputusan Rekomendasi Manusia [{item['predicted_class']}]: ").strip() or item["predicted_class"]
                assess = input("Penilaian Sistem [CORRECT/ACCEPTABLE/TOO_STRICT/TOO_LENIENT] (default CORRECT): ").strip().upper() or "CORRECT"
                notes = input("Catatan Tambahan (opsional): ").strip() or None
                tag_case_cli(
                    case_id=item["case_id"],
                    reviewer_code="RV-001",
                    human_decision=dec,
                    label=selected_label,
                    system_assessment=assess,
                    notes=notes
                )
                print("-" * 60)
        return

    # Tampilan antrean default
    display_review_queue(queue)

def main():
    parser = argparse.ArgumentParser(description="AR Tilik Evaluation Suite CLI")
    subparsers = parser.add_subparsers(dest="subcommand", help="Sub-command untuk evaluasi")

    # eval generate
    gen_parser = subparsers.add_parser("generate", help="Menghasilkan 500 kasus evaluasi terisolasi")
    gen_parser.set_defaults(func=cmd_generate)

    # eval run
    run_parser = subparsers.add_parser("run", help="Menjalankan evaluasi engine v1.0.0 pada 500 kasus")
    run_parser.add_argument("--no-db", action="store_true", help="Jangan simpan hasil ke MySQL")
    run_parser.set_defaults(func=cmd_run)

    # eval review
    rev_parser = subparsers.add_parser("review", help="Antrean peninjauan manusia untuk kasus paling informatif")
    rev_parser.add_argument("--inspect", type=str, help="Periksa rincian kasus spesifik")
    rev_parser.add_argument("--limit", type=int, default=35, help="Jumlah kasus antrean tertinggi (default: 35)")
    rev_parser.add_argument("--interactive", action="store_true", help="Mode telaah interaktif satu per satu")
    rev_parser.add_argument("--tag", type=str, help="ID kasus yang ingin diberi label anotasi")
    rev_parser.add_argument("--decision", type=str, choices=["match", "review", "no_match"], help="Keputusan manusia")
    rev_parser.add_argument("--label", type=str, choices=["Relevant", "Common phrase", "Properly cited", "Needs investigation", "False positive"], help="Label bukti")
    rev_parser.add_argument("--reviewer", type=str, default="RV-001", help="Kode anonim reviewer (default: RV-001)")
    rev_parser.add_argument("--assessment", type=str, choices=["CORRECT", "ACCEPTABLE", "TOO_STRICT", "TOO_LENIENT", "UNCERTAIN"], default="CORRECT", help="Penilaian sistem")
    rev_parser.add_argument("--notes", type=str, help="Catatan reviewer")
    rev_parser.set_defaults(func=cmd_review)

    args = parser.parse_args()
    if not hasattr(args, "func"):
        parser.print_help()
        sys.exit(1)

    args.func(args)

if __name__ == "__main__":
    main()
