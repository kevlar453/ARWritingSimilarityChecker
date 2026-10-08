-- Schema Database db_artilik for AR Tilik
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    institution VARCHAR(191) NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    subscription_tier ENUM('free', 'skripsi', 'dosen_pro') NOT NULL DEFAULT 'free',
    subscription_expires_at DATETIME NULL DEFAULT NULL,
    status ENUM('pending_verification', 'active', 'inactive') NOT NULL DEFAULT 'pending_verification',
    verification_token VARCHAR(128) NULL,
    verification_token_expires_at DATETIME NULL,
    email_verified_at DATETIME NULL,
    reset_password_token VARCHAR(128) NULL,
    reset_password_expires_at DATETIME NULL,
    free_submissions_used_this_week INT UNSIGNED NOT NULL DEFAULT 0,
    week_reset_at DATETIME NOT NULL,
    bonus_submissions INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_corpus_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_users_email (email),
    INDEX idx_users_status (status),
    INDEX idx_users_token (verification_token),
    INDEX idx_users_reset_token (reset_password_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    action_type ENUM('login', 'register', 'resend_verification', 'forgot_password', 'reset_password') NOT NULL,
    identifier VARCHAR(191) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 1,
    first_attempt_at DATETIME NOT NULL,
    last_attempt_at DATETIME NOT NULL,
    locked_until DATETIME NULL,
    INDEX idx_rate_lookup (action_type, identifier, ip_address),
    INDEX idx_rate_ip (ip_address, action_type),
    INDEX idx_locked (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    status ENUM('uploaded', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'uploaded',
    document_type ENUM('submission', 'corpus') NOT NULL DEFAULT 'submission',
    corpus_visibility ENUM('private', 'institution', 'corpus') NOT NULL DEFAULT 'private',
    source_type ENUM('student', 'journal', 'thesis', 'book', 'website', 'repository', 'other') NULL DEFAULT 'other',
    faculty VARCHAR(150) NULL DEFAULT NULL,
    department VARCHAR(150) NULL DEFAULT NULL,
    indexing_status ENUM('not_required', 'pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'not_required',
    benchmark_case_id BIGINT UNSIGNED NULL,
    benchmark_run_id BIGINT UNSIGNED NULL,
    page_count INT UNSIGNED NULL,
    share_token VARCHAR(64) NULL,
    is_shared TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_documents_status (status),
    INDEX idx_documents_user (user_id),
    INDEX idx_documents_type (document_type),
    INDEX idx_documents_faculty (faculty),
    INDEX idx_documents_department (department),
    INDEX idx_documents_benchmark_run (benchmark_run_id),
    INDEX idx_documents_share_token (share_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_shares (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    shared_by_user_id BIGINT UNSIGNED NOT NULL,
    shared_with_user_id BIGINT UNSIGNED NULL,
    recipient_email VARCHAR(191) NOT NULL,
    permission ENUM('view_all', 'view_report') NOT NULL DEFAULT 'view_all',
    status ENUM('active', 'pending_registration', 'revoked') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_doc_shares_doc (document_id),
    INDEX idx_doc_shares_user (shared_with_user_id),
    INDEX idx_doc_shares_email (recipient_email),
    INDEX idx_doc_shares_status (status),
    CONSTRAINT fk_doc_shares_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_doc_shares_by FOREIGN KEY (shared_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_doc_shares_with FOREIGN KEY (shared_with_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    page_number INT UNSIGNED NOT NULL,
    text LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_document_page (document_id, page_number),
    INDEX idx_pages_document (document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_chunks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    page_id BIGINT UNSIGNED NOT NULL,
    chunk_index INT UNSIGNED NOT NULL DEFAULT 0,
    text LONGTEXT NOT NULL,
    normalized_text LONGTEXT NOT NULL,
    word_count INT UNSIGNED NOT NULL,
    start_word_position INT UNSIGNED NOT NULL DEFAULT 0,
    end_word_position INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_chunks_document (document_id),
    INDEX idx_chunks_page (page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fingerprints (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    chunk_id BIGINT UNSIGNED NOT NULL,
    fingerprint BINARY(32) NOT NULL,
    position INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_fp_fingerprint (fingerprint),
    INDEX idx_fp_document (document_id),
    INDEX idx_fp_chunk (chunk_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fingerprint_documents (
    fingerprint BINARY(32) NOT NULL,
    document_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (fingerprint, document_id),
    INDEX idx_fd_document (document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fingerprint_stats (
    fingerprint BINARY(32) PRIMARY KEY,
    document_frequency INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    available_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_jobs_status (status, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    engine VARCHAR(50) NOT NULL DEFAULT 'hybrid',
    predicted_decision ENUM('match', 'review', 'no_match') NOT NULL DEFAULT 'no_match',
    evidence_pattern VARCHAR(50) NULL,
    evidence_agreement VARCHAR(50) NULL,
    sources_agree TINYINT(1) NOT NULL DEFAULT 0,
    rationale TEXT NULL,
    exact_similarity DECIMAL(5,2) NOT NULL DEFAULT 0,
    cosine_similarity DECIMAL(5,2) NOT NULL DEFAULT 0,
    shared_terms_count INT UNSIGNED NOT NULL DEFAULT 0,
    average_shared_term_idf DECIMAL(10,6) NOT NULL DEFAULT 0,
    evidence_data JSON NULL,
    overall_similarity DECIMAL(5,2) NOT NULL DEFAULT 0,
    source_count INT UNSIGNED NOT NULL DEFAULT 0,
    match_count INT UNSIGNED NOT NULL DEFAULT 0,
    longest_match_words INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    verification_code VARCHAR(64) NULL,
    error_message TEXT NULL,
    created_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_reports_document (document_id),
    INDEX idx_reports_status (status),
    INDEX idx_reports_verification (verification_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    source_document_id BIGINT UNSIGNED NOT NULL,
    matched_shingles INT UNSIGNED NOT NULL DEFAULT 0,
    matched_words INT UNSIGNED NOT NULL DEFAULT 0,
    similarity DECIMAL(5,2) NOT NULL DEFAULT 0,
    exact_similarity DECIMAL(5,2) NOT NULL DEFAULT 0,
    cosine_similarity DECIMAL(5,2) NOT NULL DEFAULT 0,
    source_rank INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_report_sources_report (report_id),
    INDEX idx_report_sources_source (source_document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id BIGINT UNSIGNED NOT NULL,
    source_document_id BIGINT UNSIGNED NOT NULL,
    document_page_id BIGINT UNSIGNED NULL,
    source_page_id BIGINT UNSIGNED NULL,
    submission_start INT UNSIGNED NOT NULL DEFAULT 0,
    submission_end INT UNSIGNED NOT NULL DEFAULT 0,
    source_start INT UNSIGNED NOT NULL DEFAULT 0,
    source_end INT UNSIGNED NOT NULL DEFAULT 0,
    word_count INT UNSIGNED NOT NULL,
    match_type ENUM('exact', 'fuzzy', 'semantic') NOT NULL DEFAULT 'exact',
    similarity DECIMAL(5,2) NOT NULL DEFAULT 100,
    average_idf DECIMAL(10,6) NULL,
    max_idf DECIMAL(10,6) NULL,
    significance DECIMAL(10,6) NULL,
    commonness VARCHAR(20) NULL,
    matched_text TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_matches_report (report_id),
    INDEX idx_matches_source (source_document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS test_cases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NULL,
    source_document_id BIGINT UNSIGNED NULL,
    submission_document_id BIGINT UNSIGNED NULL,
    expected_result ENUM('match', 'review', 'no_match') NOT NULL,
    expected_similarity_min DECIMAL(5,2) NULL,
    expected_similarity_max DECIMAL(5,2) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_test_cases_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benchmark_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    engine_version VARCHAR(50) NOT NULL,
    total_cases INT UNSIGNED NOT NULL DEFAULT 0,
    precision_score DECIMAL(6,4) NULL,
    recall_score DECIMAL(6,4) NULL,
    f1_score DECIMAL(6,4) NULL,
    macro_f1 DECIMAL(6,4) NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benchmark_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    benchmark_run_id BIGINT UNSIGNED NOT NULL,
    test_case_id BIGINT UNSIGNED NOT NULL,
    expected_result ENUM('match', 'review', 'no_match') NOT NULL,
    predicted_result ENUM('match', 'review', 'no_match') NOT NULL,
    similarity DECIMAL(6,3) NOT NULL DEFAULT 0,
    matched_words INT UNSIGNED NOT NULL DEFAULT 0,
    longest_match_words INT UNSIGNED NOT NULL DEFAULT 0,
    source_count INT UNSIGNED NOT NULL DEFAULT 0,
    match_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_type VARCHAR(30) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_benchmark_results_run (benchmark_run_id),
    INDEX idx_benchmark_results_case (test_case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS landing_visitors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visitor_hash VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    visit_date DATE NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_visitor_date (visitor_hash, visit_date),
    INDEX idx_visit_date (visit_date),
    INDEX idx_visitor_hash (visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id VARCHAR(100) NOT NULL UNIQUE,
    package_id VARCHAR(50) NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    submissions_added INT UNSIGNED NOT NULL DEFAULT 0,
    subscription_tier VARCHAR(50) NULL DEFAULT NULL,
    subscription_days INT UNSIGNED NOT NULL DEFAULT 0,
    payment_type VARCHAR(50) NULL DEFAULT NULL,
    transaction_status ENUM('pending', 'settlement', 'capture', 'deny', 'expire', 'cancel', 'refund', 'chargeback') NOT NULL DEFAULT 'pending',
    fraud_status VARCHAR(50) NULL DEFAULT NULL,
    snap_token VARCHAR(255) NULL DEFAULT NULL,
    snap_redirect_url TEXT NULL DEFAULT NULL,
    midtrans_response LONGTEXT NULL DEFAULT NULL,
    paid_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_trans_user (user_id),
    INDEX idx_trans_status (transaction_status),
    INDEX idx_trans_order (order_id),
    CONSTRAINT fk_trans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS harvest_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(50) NOT NULL COMMENT 'neliti, garuda, openalex, oai_repository',
    source_name VARCHAR(150) NOT NULL,
    query_or_set VARCHAR(255) NULL,
    items_found INT UNSIGNED NOT NULL DEFAULT 0,
    items_indexed INT UNSIGNED NOT NULL DEFAULT 0,
    items_skipped INT UNSIGNED NOT NULL DEFAULT 0,
    items_failed INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('running', 'completed', 'failed', 'partial') NOT NULL DEFAULT 'running',
    summary TEXT NULL,
    error_message TEXT NULL,
    duration_seconds DECIMAL(8,2) NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_harvest_source (source),
    INDEX idx_harvest_status (status),
    INDEX idx_harvest_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS harvest_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source ENUM('neliti', 'garuda', 'all') NOT NULL DEFAULT 'all',
    name VARCHAR(150) NOT NULL,
    keywords VARCHAR(255) NOT NULL DEFAULT 'informatika, pendidikan, ekonomi, hukum, sains',
    frequency ENUM('hourly', 'daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    limit_per_run INT UNSIGNED NOT NULL DEFAULT 15,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_run_at DATETIME NULL,
    next_run_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    key_name VARCHAR(150) NOT NULL,
    key_prefix VARCHAR(32) NOT NULL,
    api_key VARCHAR(128) NOT NULL UNIQUE,
    system_type ENUM('ojs', 'web', 'cli', 'integration') NOT NULL DEFAULT 'ojs',
    rate_limit_per_minute INT UNSIGNED NOT NULL DEFAULT 60,
    quota_limit INT UNSIGNED NOT NULL DEFAULT 1000,
    quota_used INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_used_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_api_user (user_id),
    INDEX idx_api_prefix (key_prefix),
    INDEX idx_api_key (api_key),
    CONSTRAINT fk_api_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(150) NOT NULL,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    provider VARCHAR(50) NOT NULL DEFAULT 'gemini',
    model_name VARCHAR(100) NOT NULL DEFAULT 'gemini-3.6-flash',
    status ENUM('active', 'rate_limited', 'exhausted', 'inactive') NOT NULL DEFAULT 'active',
    rate_limited_until DATETIME NULL,
    usage_count INT UNSIGNED NOT NULL DEFAULT 0,
    error_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_error_message TEXT NULL,
    last_used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_google_keys_status (status),
    INDEX idx_google_keys_last_used (last_used_at),
    INDEX idx_google_keys_cooldown (rate_limited_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
