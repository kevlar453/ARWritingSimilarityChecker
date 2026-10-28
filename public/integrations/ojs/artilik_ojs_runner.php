<?php
/**
 * AR Tilik - Contoh Eksekutor Integrasi PKP OJS 3.x (artilik_ojs_runner.php)
 * 
 * Skrip eksekutor mandiri untuk menguji integrasi naskah OJS 3.x dengan
 * mesin Tiga Lensa AR Tilik dan modul Audit Forensik Cerdas AI v1.3.0.
 *
 * Cara menjalankan via CLI server OJS:
 *   php artilik_ojs_runner.php /path/to/manuscript.pdf "Judul Naskah" "Dr. Penulis" "sub_102"
 *
 * Dokumentasi: https://tilik.arsetontong.top/integrations/ojs/README.md
 */

require_once __DIR__ . '/ARTilikOJSConnector.php';

// =========================================================================
// 1. KONFIGURASI INTEGRASI (Sesuaikan dengan kredensial portal Anda)
// =========================================================================
$apiKey      = getenv('ARTILIK_API_KEY') ?: 'artilik_ojs_YOUR_API_KEY';
$apiEndpoint = getenv('ARTILIK_API_ENDPOINT') ?: 'https://tilik.arsetontong.top/api/v1';

// Parameter naskah (dapat dibaca dari argumen CLI OJS atau form penelaahan)
$filePath     = $argv[1] ?? __DIR__ . '/contoh_naskah.pdf';
$title        = $argv[2] ?? 'Analisis Algoritma Dual-Lens pada Integritas Akademik';
$author       = $argv[3] ?? 'Dr. Penulis Utama';
$submissionId = $argv[4] ?? 'submission_' . date('Ymd_His');

echo "=================================================================\n";
echo " AR Tilik OJS 3.x Integration Runner (v1.3.0 Ready)\n";
echo "=================================================================\n";
echo "Server Endpoint : {$apiEndpoint}\n";
echo "Judul Naskah    : {$title}\n";
echo "Penulis         : {$author}\n";
echo "ID Submisi OJS  : {$submissionId}\n";
echo "Berkas Naskah   : {$filePath}\n";
echo "-----------------------------------------------------------------\n";

// Validasi keberadaan berkas sebelum pengiriman
if (! file_exists($filePath)) {
    echo "[INFO] Berkas '{$filePath}' tidak ditemukan untuk pengujian lokal.\n";
    echo "       Membuat berkas dummy teks untuk mensimulasikan alur kerja...\n";
    $dummyPath = sys_get_temp_dir() . '/artilik_demo_manuscript.txt';
    file_put_contents(
        $dummyPath,
        "PENELITIAN INTEGRITAS ILMIAH DAN DETEKSI KESAMAAN TEKSTUAL PADA OJS 3.\n\n" .
        "Abstrak: Penelitian ini menganalisis kinerja algoritma komputasi Dual-Lens dalam membedakan " .
        "antara salin-tempel sekuensial verbatim dengan orientasi parafrase leksikal bertopik serupa. " .
        "Metode yang digunakan mencakup 5-gram shingling dan TF-IDF cosine vector space dengan isolasi teks substantif."
    );
    $filePath = $dummyPath;
}

try {
    // 1. Inisialisasi Konektor
    $tilik = new ARTilikOJSConnector($apiKey, $apiEndpoint);

    // 2. Kirim Naskah Submisi Penulis
    echo "[1/3] Mengunggah naskah ke antrean mesin AR Tilik...\n";
    $submitRes = $tilik->submitManuscript(
        $filePath,
        $title,
        $author,
        $submissionId
    );

    $docId    = $submitRes['data']['document_id'] ?? null;
    $reportId = $submitRes['data']['report_id'] ?? null;

    echo "      [✓] Naskah berhasil diterima!\n";
    echo "          Document ID : #{$docId}\n";
    echo "          Report ID   : #{$reportId}\n";

    // 3. Menunggu Hasil Komputasi (Polling Asinkron)
    echo "[2/3] Menunggu komputasi selesai (Mesin Tiga Lensa & Forensik Cerdas)...\n";
    $statusRes = $tilik->waitForCompletion($docId, 90, 3);

    $overallSim = $statusRes['data']['overall_similarity'] ?? 0;
    $decision   = $statusRes['data']['predicted_decision'] ?? 'completed';
    $certCode   = $statusRes['data']['verification_code'] ?? 'Tersedia di laporan';
    $webReport  = $statusRes['data']['web_report_url'] ?? '';
    $pdfExport  = $statusRes['data']['pdf_export_url'] ?? '';

    echo "      [✓] Komputasi Selesai!\n";
    echo "-----------------------------------------------------------------\n";
    echo " HASIL PENELAAHAN ORISINALITAS:\n";
    echo "   • Tingkat Kemiripan Total : {$overallSim}%\n";
    echo "   • Status Putusan Redaksi  : " . strtoupper($decision) . "\n";
    echo "   • Kode Verifikasi SHA-256 : {$certCode}\n";
    echo "   • Tautan Laporan Web      : {$webReport}\n";

    // 4. Membaca Hasil Audit Forensik Cerdas & Pertanyaan Telaah (v1.3.0)
    echo "-----------------------------------------------------------------\n";
    echo "[3/3] Mengambil Rincian Audit Forensik Cerdas AI & Telaah Redaksi...\n";
    if ($reportId) {
        $detailedReport = $tilik->getDetailedReport($reportId);
        $dualLens = $detailedReport['data']['dual_lens'] ?? [];
        $forensic = $detailedReport['data']['forensic_audit'] ?? null;
        $certUrl  = $detailedReport['data']['verification']['certificate_url'] ?? null;

        if ($dualLens) {
            $exactSim  = $dualLens['exact_matcher']['similarity'] ?? 0;
            $cosineSim = $dualLens['tfidf_vectorizer']['similarity'] ?? 0;
            echo "   • Dekomposisi Tiga Lensa   :\n";
            echo "     - Lensa 1 (5-Gram Exact Match)   : {$exactSim}%\n";
            echo "     - Lensa 2 (TF-IDF Cosine Vector) : {$cosineSim}%\n";
        }

        if ($forensic) {
            echo "   • Audit Forensik Cerdas AI :\n";
            echo "     - Model AI   : " . ($forensic['model'] ?? 'Gemini AI') . "\n";
            echo "     - Hasil      : " . ($forensic['verdict'] ?? 'Selesai') . "\n";
            if (! empty($forensic['summary'])) {
                echo "     - Ringkasan  : " . substr($forensic['summary'], 0, 120) . "...\n";
            }
            if (! empty($forensic['oral_defense_questions'])) {
                echo "   • Panduan Pertanyaan Telaah untuk Reviewer:\n";
                foreach (array_slice($forensic['oral_defense_questions'], 0, 3) as $idx => $q) {
                    $no = $idx + 1;
                    echo "     [{$no}] {$q}\n";
                }
            }
        }

        if ($certUrl) {
            echo "   • Tautan Sertifikat QR Resmi: {$certUrl}\n";
        }
    }

    echo "=================================================================\n";
    echo " Integrasi OJS Berhasil. Siap Disematkan ke Editorial Decision!\n";
    echo "=================================================================\n";

} catch (Exception $e) {
    echo "[ERROR] Terjadi kendala eksekusi: " . $e->getMessage() . "\n";
    exit(1);
}
