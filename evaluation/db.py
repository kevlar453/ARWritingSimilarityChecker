"""
AR Tilik - Evaluation Database Management
Mengelola tabel evaluasi terisolasi (post_release_evaluations, evaluation_evidence, evaluation_labels)
tanpa mencemari tabel produksi (documents, reports, jobs).
Mendukung Failure Taxonomy, Konteks Sitasi, dan Interpretasi Manusia.
"""

from benchmark.database import get_connection
from typing import Optional, List, Dict, Any

def init_evaluation_tables():
    """
    Membuat atau memutakhirkan tabel evaluasi pasca-rilis secara terisolasi.
    """
    conn = get_connection()
    cursor = conn.cursor()
    
    # 1. Tabel Master Evaluasi
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS post_release_evaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_id VARCHAR(64) NOT NULL UNIQUE,
            case_type VARCHAR(32) NOT NULL,
            source_case VARCHAR(64) NULL,
            generation_method VARCHAR(64) NOT NULL,
            expected_class VARCHAR(32) NULL,
            expected_pattern VARCHAR(64) NULL,
            predicted_class VARCHAR(32) NOT NULL,
            predicted_pattern VARCHAR(64) NOT NULL,
            agreement VARCHAR(64) NOT NULL,
            exact_similarity DECIMAL(5,2) NOT NULL,
            cosine_similarity DECIMAL(5,2) NOT NULL,
            longest_match_words INT NOT NULL,
            matched_terms INT NOT NULL,
            avg_idf DECIMAL(8,4) NOT NULL,
            max_idf DECIMAL(8,4) NOT NULL,
            is_pass TINYINT(1) NOT NULL DEFAULT 1,
            rationale TEXT NOT NULL,
            submission_text MEDIUMTEXT NOT NULL,
            source_text MEDIUMTEXT NOT NULL,
            system_version VARCHAR(32) NOT NULL DEFAULT 'v1.0.0',
            content_hash VARCHAR(64) NULL,
            mutation_strength VARCHAR(32) NULL,
            failure_class VARCHAR(64) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_case_type (case_type),
            INDEX idx_predicted_class (predicted_class),
            INDEX idx_is_pass (is_pass),
            INDEX idx_content_hash (content_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # Tambahkan kolom baru jika tabel sudah ada sebelumnya
    for col, col_type in [
        ("content_hash", "VARCHAR(64) NULL"),
        ("mutation_strength", "VARCHAR(32) NULL"),
        ("failure_class", "VARCHAR(64) NULL")
    ]:
        try:
            cursor.execute(f"ALTER TABLE post_release_evaluations ADD COLUMN {col} {col_type}")
            conn.commit()
        except Exception:
            pass  # Kolom sudah ada

    # 2. Tabel Bukti Kuantitatif
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS evaluation_evidence (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_id VARCHAR(64) NOT NULL,
            evidence_lens VARCHAR(32) NOT NULL,
            metric_name VARCHAR(64) NOT NULL,
            metric_value VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_case_id (case_id),
            CONSTRAINT fk_eval_evidence_case FOREIGN KEY (case_id) 
                REFERENCES post_release_evaluations (case_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # 3. Tabel Anotasi Manusia
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS evaluation_labels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_id VARCHAR(64) NOT NULL,
            reviewer_code VARCHAR(32) NOT NULL,
            human_decision VARCHAR(32) NOT NULL,
            citation_context VARCHAR(64) NOT NULL DEFAULT 'NONE',
            human_interpretation VARCHAR(64) NOT NULL DEFAULT 'DIRECT_OVERLAP_RELEVANT',
            evidence_relevant TINYINT(1) NOT NULL DEFAULT 0,
            common_phrase TINYINT(1) NOT NULL DEFAULT 0,
            properly_cited TINYINT(1) NOT NULL DEFAULT 0,
            needs_investigation TINYINT(1) NOT NULL DEFAULT 0,
            false_positive TINYINT(1) NOT NULL DEFAULT 0,
            system_assessment ENUM('CORRECT', 'ACCEPTABLE', 'TOO_STRICT', 'TOO_LENIENT', 'UNCERTAIN') NOT NULL DEFAULT 'CORRECT',
            notes TEXT NULL,
            reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_label_case_id (case_id),
            INDEX idx_reviewer (reviewer_code),
            CONSTRAINT fk_eval_label_case FOREIGN KEY (case_id) 
                REFERENCES post_release_evaluations (case_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    """)

    # Tambahkan kolom baru pada evaluation_labels jika belum ada
    for col, col_type in [
        ("citation_context", "VARCHAR(64) NOT NULL DEFAULT 'NONE'"),
        ("human_interpretation", "VARCHAR(64) NOT NULL DEFAULT 'DIRECT_OVERLAP_RELEVANT'")
    ]:
        try:
            cursor.execute(f"ALTER TABLE evaluation_labels ADD COLUMN {col} {col_type}")
            conn.commit()
        except Exception:
            pass

    conn.commit()
    cursor.close()
    conn.close()

def save_evaluation_result(result_data: Dict[str, Any], case_data: Dict[str, Any]):
    """
    Menyimpan hasil evaluasi ke tabel post_release_evaluations dan evaluation_evidence.
    """
    conn = get_connection()
    cursor = conn.cursor()
    
    cursor.execute("""
        INSERT INTO post_release_evaluations (
            case_id, case_type, source_case, generation_method, expected_class,
            expected_pattern, predicted_class, predicted_pattern, agreement,
            exact_similarity, cosine_similarity, longest_match_words, matched_terms,
            avg_idf, max_idf, is_pass, rationale, submission_text, source_text, system_version,
            content_hash, mutation_strength, failure_class
        ) VALUES (
            %(case_id)s, %(case_type)s, %(source_case)s, %(generation_method)s, %(expected_class)s,
            %(expected_pattern)s, %(predicted_class)s, %(predicted_pattern)s, %(agreement)s,
            %(exact_similarity)s, %(cosine_similarity)s, %(longest_match_words)s, %(matched_terms)s,
            %(avg_idf)s, %(max_idf)s, %(is_pass)s, %(rationale)s, %(submission_text)s, %(source_text)s, %(system_version)s,
            %(content_hash)s, %(mutation_strength)s, %(failure_class)s
        )
        ON DUPLICATE KEY UPDATE
            predicted_class = VALUES(predicted_class),
            predicted_pattern = VALUES(predicted_pattern),
            agreement = VALUES(agreement),
            exact_similarity = VALUES(exact_similarity),
            cosine_similarity = VALUES(cosine_similarity),
            longest_match_words = VALUES(longest_match_words),
            matched_terms = VALUES(matched_terms),
            avg_idf = VALUES(avg_idf),
            max_idf = VALUES(max_idf),
            is_pass = VALUES(is_pass),
            rationale = VALUES(rationale),
            content_hash = VALUES(content_hash),
            mutation_strength = VALUES(mutation_strength),
            failure_class = VALUES(failure_class),
            updated_at = NOW()
    """, {
        "case_id": case_data["case_id"],
        "case_type": case_data["case_type"],
        "source_case": case_data.get("source_case"),
        "generation_method": case_data.get("generation_method", "UNKNOWN"),
        "expected_class": case_data.get("expected_class"),
        "expected_pattern": case_data.get("expected_pattern"),
        "predicted_class": result_data["predicted_class"],
        "predicted_pattern": result_data["predicted_pattern"],
        "agreement": result_data["agreement"],
        "exact_similarity": result_data["exact_similarity"],
        "cosine_similarity": result_data["cosine_similarity"],
        "longest_match_words": result_data["longest_match_words"],
        "matched_terms": result_data["matched_terms"],
        "avg_idf": result_data["avg_idf"],
        "max_idf": result_data["max_idf"],
        "is_pass": 1 if result_data["is_pass"] else 0,
        "rationale": result_data["rationale"],
        "submission_text": case_data.get("submission_text", ""),
        "source_text": case_data.get("source_text", ""),
        "system_version": case_data.get("system_version", "v1.0.0"),
        "content_hash": case_data.get("content_hash"),
        "mutation_strength": case_data.get("mutation_strength", "MEDIUM"),
        "failure_class": result_data.get("failure_class"),
    })
    
    # Simpan evidence kuantitatif
    cursor.execute("DELETE FROM evaluation_evidence WHERE case_id = %s", (case_data["case_id"],))
    evidence_rows = [
        (case_data["case_id"], "EXACT", "similarity", str(result_data["exact_similarity"])),
        (case_data["case_id"], "EXACT", "longest_match_words", str(result_data["longest_match_words"])),
        (case_data["case_id"], "EXACT", "max_idf", str(result_data["max_idf"])),
        (case_data["case_id"], "TFIDF", "cosine_similarity", str(result_data["cosine_similarity"])),
        (case_data["case_id"], "TFIDF", "matched_terms", str(result_data["matched_terms"])),
        (case_data["case_id"], "TFIDF", "avg_idf", str(result_data["avg_idf"])),
    ]
    cursor.executemany("""
        INSERT INTO evaluation_evidence (case_id, evidence_lens, metric_name, metric_value)
        VALUES (%s, %s, %s, %s)
    """, evidence_rows)
    
    conn.commit()
    cursor.close()
    conn.close()

def save_human_label(label_data: Dict[str, Any]):
    """
    Menyimpan label peninjauan manusia ke evaluation_labels lengkap dengan konteks sitasi & interpretasi.
    """
    conn = get_connection()
    cursor = conn.cursor()
    cursor.execute("""
        INSERT INTO evaluation_labels (
            case_id, reviewer_code, human_decision, citation_context,
            human_interpretation, evidence_relevant, common_phrase,
            properly_cited, needs_investigation, false_positive,
            system_assessment, notes, reviewed_at
        ) VALUES (
            %(case_id)s, %(reviewer_code)s, %(human_decision)s, %(citation_context)s,
            %(human_interpretation)s, %(evidence_relevant)s, %(common_phrase)s,
            %(properly_cited)s, %(needs_investigation)s, %(false_positive)s,
            %(system_assessment)s, %(notes)s, NOW()
        )
    """, {
        "case_id": label_data["case_id"],
        "reviewer_code": label_data.get("reviewer_code", "RV-001"),
        "human_decision": label_data["human_decision"],
        "citation_context": label_data.get("citation_context", "NONE"),
        "human_interpretation": label_data.get("human_interpretation", "DIRECT_OVERLAP_RELEVANT"),
        "evidence_relevant": 1 if label_data.get("evidence_relevant") else 0,
        "common_phrase": 1 if label_data.get("common_phrase") else 0,
        "properly_cited": 1 if label_data.get("properly_cited") else 0,
        "needs_investigation": 1 if label_data.get("needs_investigation") else 0,
        "false_positive": 1 if label_data.get("false_positive") else 0,
        "system_assessment": label_data.get("system_assessment", "CORRECT"),
        "notes": label_data.get("notes"),
    })
    conn.commit()
    cursor.close()
    conn.close()
