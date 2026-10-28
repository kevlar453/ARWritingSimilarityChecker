<?php

namespace App\Libraries;

/**
 * AR Tilik - Multi-Lingual Academic Language Detector & Benchmark Maturity Resolver
 * Mendeteksi bahasa dominan naskah (ID, EN, ZH, DE, NL, AR) dan memetakan status
 * kematangan empiris Lensa Semantik (Fase 3: Wilson 95% CI, N=10 per pasangan).
 */
class LanguageDetector
{
    /**
     * Metadata Kalibrasi Empiris per Pasangan Bahasa Ilmiah (Fase 3 & Benchmark Nasional)
     */
    public const MATURITY_REGISTRY = [
        'EN' => [
            'code'        => 'EN',
            'iso'         => 'eng',
            'name'        => 'Inggris (English)',
            'flag'        => '🇬🇧',
            'pair_code'   => 'ID-EN',
            'pair_label'  => 'Indonesia ↔ Inggris (ID-EN)',
            'status'      => 'Tervalidasi Terarah',
            'badge_label' => 'ID-EN: Tervalidasi Terarah (N=10, LB: 60%)',
            'badge_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'wilson_lb'   => '59,6%',
            'wilson_note' => 'Validasi Fase 3 Lintas Bahasa: Recall@0,75: 90,0% | Recall@0,70: 100,0% | Wilson 95% CI: [59,6%–98,2%] (LB: 60%) (N=10)',
            'is_foreign'  => true,
            'script_type' => 'Aksara Latin',
        ],
        'ZH' => [
            'code'        => 'ZH',
            'iso'         => 'zho',
            'name'        => 'Mandarin (中文)',
            'flag'        => '🇨🇳',
            'pair_code'   => 'ID-ZH',
            'pair_label'  => 'Indonesia ↔ Mandarin (ID-ZH)',
            'status'      => 'Tervalidasi Terarah',
            'badge_label' => 'ID-ZH: Tervalidasi Terarah (N=10, LB: 72%)',
            'badge_class' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            'wilson_lb'   => '72,2%',
            'wilson_note' => 'Validasi Fase 3 Lintas Bahasa: Recall@0,75: 100,0% | Recall@0,70: 100,0% | Wilson 95% CI: [72,2%–100,0%] (LB: 72%) (N=10)',
            'is_foreign'  => true,
            'script_type' => 'Aksara Hanzi Sederhana',
        ],
        'DE' => [
            'code'        => 'DE',
            'iso'         => 'deu',
            'name'        => 'Jerman (Deutsch)',
            'flag'        => '🇩🇪',
            'pair_code'   => 'ID-DE',
            'pair_label'  => 'Indonesia ↔ Jerman (ID-DE)',
            'status'      => 'Sensitif di Mode 0,70',
            'badge_label' => 'ID-DE: Sensitif di Mode 0,70 (N=10, LB: 31% @0.75 → 72% @0.70)',
            'badge_class' => 'bg-amber-50 text-amber-800 border-amber-300',
            'wilson_lb'   => '31,3% / 72,2%',
            'wilson_note' => 'Validasi Fase 3 Lintas Bahasa: Recall@0,75: 60,0% (CI: [31,3%–83,2%], LB: 31%) | Recall@0,70: 100,0% (CI: [72,2%–100,0%], LB: 72%) | Efek Komposita (N=10)',
            'is_foreign'  => true,
            'script_type' => 'Aksara Latin Majemuk',
        ],
        'NL' => [
            'code'        => 'NL',
            'iso'         => 'nld',
            'name'        => 'Belanda (Nederlands)',
            'flag'        => '🇳🇱',
            'pair_code'   => 'ID-NL',
            'pair_label'  => 'Indonesia ↔ Belanda (ID-NL)',
            'status'      => 'Indikasi Terarah',
            'badge_label' => 'ID-NL: Indikasi Terarah (N=10, LB: 40%)',
            'badge_class' => 'bg-slate-100 text-slate-700 border-slate-300',
            'wilson_lb'   => '39,7%',
            'wilson_note' => 'Validasi Fase 3 Lintas Bahasa: Recall@0,75: 70,0% | Recall@0,70: 80,0% | Wilson 95% CI: [39,7%–89,2%] (LB: 40%) (N=10)',
            'is_foreign'  => true,
            'script_type' => 'Aksara Latin Arkais',
        ],
        'AR' => [
            'code'        => 'AR',
            'iso'         => 'ara',
            'name'        => 'Arab (العربية)',
            'flag'        => '🇸🇦',
            'pair_code'   => 'ID-AR',
            'pair_label'  => 'Indonesia ↔ Arab (ID-AR)',
            'status'      => 'Indikasi Terarah',
            'badge_label' => 'ID-AR: Indikasi Terarah (N=10, LB: 40%)',
            'badge_class' => 'bg-slate-100 text-slate-700 border-slate-300',
            'wilson_lb'   => '39,7%',
            'wilson_note' => 'Validasi Fase 3 Lintas Bahasa: Recall@0,75: 70,0% | Recall@0,70: 80,0% | Wilson 95% CI: [39,7%–89,2%] (LB: 40%) (N=10)',
            'is_foreign'  => true,
            'script_type' => 'Aksara Hijaiyah Kanan-ke-Kiri',
        ],
        'ID' => [
            'code'        => 'ID',
            'iso'         => 'ind',
            'name'        => 'Indonesia (Bahasa Indonesia)',
            'flag'        => '🇮🇩',
            'pair_code'   => 'ID',
            'pair_label'  => 'Bahasa Indonesia (Utama)',
            'status'      => 'Terkalibrasi Nasional',
            'badge_label' => 'Terkalibrasi Nasional (F1: 0,85)',
            'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'wilson_lb'   => '100%',
            'wilson_note' => 'Dataset Terarah 80 Kasus: F1 0,8462 @0,70 / 0,7629 @0,75 | 0 Alarm Palsu pada 20 Kontrol Bersih',
            'is_foreign'  => false,
            'script_type' => 'Aksara Latin Bahasa Nasional',
        ],
    ];

    /**
     * Deteksi bahasa dominan naskah submisi secara deterministik dan cepat.
     *
     * @param string|null $text Sampel teks submisi
     * @param string|null $fallbackTitle Judul dokumen sebagai cadangan jika teks minim
     * @return array Metadata bahasa lengkap
     */
    public static function detect(?string $text, ?string $fallbackTitle = null): array
    {
        $sample = trim($text ?? '');
        if (mb_strlen($sample) < 30 && !empty($fallbackTitle)) {
            $sample .= ' ' . trim($fallbackTitle);
        }

        if (empty($sample)) {
            return self::MATURITY_REGISTRY['ID'];
        }

        // Batasi sampel ke 5.000 karakter pertama untuk performa sub-milidetik
        $sample = mb_substr($sample, 0, 5000);

        // 1. Cek Aksara Non-Latin: Hanzi (Mandarin)
        preg_match_all('/[\x{4e00}-\x{9fff}]/u', $sample, $zhMatches);
        if (count($zhMatches[0] ?? []) >= 10) {
            return self::MATURITY_REGISTRY['ZH'];
        }

        // 2. Cek Aksara Non-Latin: Hijaiyah (Arab)
        preg_match_all('/[\x{0600}-\x{06ff}]/u', $sample, $arMatches);
        if (count($arMatches[0] ?? []) >= 10) {
            return self::MATURITY_REGISTRY['AR'];
        }

        // 3. Ekstraksi Token Latin Kata Penuh
        preg_match_all('/\b[a-zA-Z]{2,}\b/', strtolower($sample), $wordMatches);
        $tokens = $wordMatches[0] ?? [];
        if (empty($tokens)) {
            return self::MATURITY_REGISTRY['ID'];
        }

        $tokenCount = count($tokens);

        // Stopwords khas berfrekuensi tinggi per bahasa
        $markers = [
            'ID' => [
                'yang', 'dan', 'di', 'dari', 'ini', 'dengan', 'untuk', 'pada', 'adalah',
                'sebagai', 'bahwa', 'dalam', 'tidak', 'akan', 'atau', 'oleh', 'tersebut',
                'dapat', 'penelitian', 'hasil', 'metode', 'secara', 'antara', 'juga'
            ],
            'EN' => [
                'the', 'and', 'of', 'in', 'to', 'is', 'that', 'for', 'with', 'as',
                'by', 'from', 'this', 'were', 'was', 'are', 'which', 'on', 'at',
                'an', 'be', 'study', 'research', 'between', 'their', 'have', 'has'
            ],
            'DE' => [
                'der', 'die', 'das', 'und', 'in', 'zu', 'den', 'nicht', 'von',
                'sie', 'ist', 'des', 'sich', 'mit', 'dem', 'dass', 'er', 'es',
                'ein', 'eine', 'einer', 'einem', 'einen', 'wird', 'sind', 'oder'
            ],
            'NL' => [
                'de', 'het', 'en', 'van', 'ik', 'te', 'dat', 'die', 'in',
                'een', 'hij', 'niet', 'zijn', 'is', 'was', 'op', 'aan', 'met',
                'als', 'voor', 'maar', 'er', 'om', 'over', 'door', 'ook'
            ],
        ];

        $counts = ['ID' => 0, 'EN' => 0, 'DE' => 0, 'NL' => 0];

        // Hitung frekuensi kemunculan
        $tokenFreq = array_count_values($tokens);
        foreach ($markers as $lang => $wordList) {
            foreach ($wordList as $w) {
                if (isset($tokenFreq[$w])) {
                    $counts[$lang] += $tokenFreq[$w];
                }
            }
        }

        // Evaluasi karakter khusus bahasa Jerman (ä, ö, ü, ß)
        preg_match_all('/[äöüß]/i', $sample, $umlautMatches);
        $counts['DE'] += count($umlautMatches[0] ?? []) * 2;

        // Evaluasi diftong khas bahasa Belanda (ij)
        preg_match_all('/\bij\b|\b\w*ij\w*\b/i', $sample, $ijMatches);
        $counts['NL'] += count($ijMatches[0] ?? []);

        // Penentuan Bahasa Dominan
        arsort($counts);
        $topLang = array_key_first($counts);
        $topScore = $counts[$topLang];

        // Ambang minimum keyakinan deteksi (minimal 4 kata penanda)
        if ($topScore >= 4) {
            // Jika persaingan ketat antara EN dan NL/DE (misal 'in', 'is', 'to'), pastikan EN memiliki margin
            if ($topLang === 'EN' && $topScore >= ($counts['ID'] ?? 0) * 1.5) {
                return self::MATURITY_REGISTRY['EN'];
            }
            if ($topLang === 'ID' && $topScore >= ($counts['EN'] ?? 0) * 1.3) {
                return self::MATURITY_REGISTRY['ID'];
            }
            if ($topLang === 'DE' && $topScore > ($counts['EN'] ?? 0) * 1.2) {
                return self::MATURITY_REGISTRY['DE'];
            }
            if ($topLang === 'NL' && $topScore > ($counts['EN'] ?? 0) * 1.2) {
                return self::MATURITY_REGISTRY['NL'];
            }
            return self::MATURITY_REGISTRY[$topLang] ?? self::MATURITY_REGISTRY['ID'];
        }

        return self::MATURITY_REGISTRY['ID'];
    }

    /**
     * Dapatkan metadata pasangan bahasa berdasarkan kode pasangan.
     */
    public static function getPairMeta(string $pairCode): array
    {
        $clean = strtoupper(trim($pairCode));
        foreach (self::MATURITY_REGISTRY as $code => $meta) {
            if ($code === $clean || $meta['pair_code'] === $clean) {
                return $meta;
            }
        }
        return self::MATURITY_REGISTRY['EN'];
    }
}
