<?php

namespace App\Models;

use CodeIgniter\Model;

class VisitorModel extends Model
{
    protected $table = 'landing_visitors';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'visitor_hash',
        'ip_address',
        'country_code',
        'country_name',
        'region_name',
        'city_name',
        'isp_name',
        'user_agent',
        'visit_date',
        'hits',
        'created_at',
        'updated_at',
    ];

    /**
     * Peta translasi nama negara ke Bahasa Indonesia baku
     */
    public static function normalizeCountryName(?string $countryCode, ?string $rawCountryName = null): string
    {
        $code = strtoupper(trim((string) $countryCode));
        $map = [
            'ID' => 'Indonesia',
            'US' => 'Amerika Serikat',
            'SG' => 'Singapura',
            'MY' => 'Malaysia',
            'AU' => 'Australia',
            'JP' => 'Jepang',
            'CN' => 'Tiongkok',
            'HK' => 'Hong Kong',
            'TW' => 'Taiwan',
            'KR' => 'Korea Selatan',
            'IN' => 'India',
            'GB' => 'Inggris Raya (UK)',
            'NL' => 'Belanda',
            'DE' => 'Jerman',
            'FR' => 'Prancis',
            'CA' => 'Kanada',
            'TL' => 'Timor Leste',
            'BN' => 'Brunei Darussalam',
            'PH' => 'Filipina',
            'TH' => 'Thailand',
            'VN' => 'Vietnam',
            'SA' => 'Arab Saudi',
            'AE' => 'Uni Emirat Arab',
            'TR' => 'Turki',
            'EG' => 'Mesir',
            'RU' => 'Rusia',
            'BR' => 'Brasil',
            'IE' => 'Irlandia',
            'SE' => 'Swedia',
            'FI' => 'Finlandia',
            'CH' => 'Swiss',
            'IT' => 'Italia',
            'ES' => 'Spanyol',
            'PL' => 'Polandia',
            'NZ' => 'Selandia Baru',
        ];

        if (isset($map[$code])) {
            return $map[$code];
        }

        $clean = trim((string) $rawCountryName);
        return $clean !== '' ? $clean : ($code !== '' && $code !== 'XX' ? $code : 'Tidak Diketahui');
    }

    /**
     * Normalisasi nama provinsi / wilayah ke Bahasa Indonesia baku
     */
    public static function normalizeRegionName(?string $countryCode, ?string $rawRegion = null, ?string $rawCity = null): string
    {
        $code = strtoupper(trim((string) $countryCode));
        $region = trim((string) $rawRegion);
        $city = trim((string) $rawCity);

        if ($code === 'ID') {
            $lower = strtolower($region);
            $idProvinceMap = [
                'jakarta'                            => 'DKI Jakarta',
                'special capital region of jakarta'  => 'DKI Jakarta',
                'daerah khusus ibukota jakarta'      => 'DKI Jakarta',
                'dki jakarta'                        => 'DKI Jakarta',
                'west java'                          => 'Jawa Barat',
                'jawa barat'                         => 'Jawa Barat',
                'central java'                       => 'Jawa Tengah',
                'jawa tengah'                        => 'Jawa Tengah',
                'east java'                          => 'Jawa Timur',
                'jawa timur'                         => 'Jawa Timur',
                'yogyakarta'                         => 'DI Yogyakarta',
                'special region of yogyakarta'       => 'DI Yogyakarta',
                'daerah istimewa yogyakarta'         => 'DI Yogyakarta',
                'di yogyakarta'                      => 'DI Yogyakarta',
                'banten'                             => 'Banten',
                'bali'                               => 'Bali',
                'north sumatra'                      => 'Sumatera Utara',
                'sumatera utara'                     => 'Sumatera Utara',
                'west sumatra'                       => 'Sumatera Barat',
                'sumatera barat'                     => 'Sumatera Barat',
                'south sumatra'                      => 'Sumatera Selatan',
                'sumatera selatan'                   => 'Sumatera Selatan',
                'riau'                               => 'Riau',
                'riau islands'                       => 'Kepulauan Riau',
                'kepulauan riau'                     => 'Kepulauan Riau',
                'lampung'                            => 'Lampung',
                'jambi'                              => 'Jambi',
                'bengkulu'                           => 'Bengkulu',
                'aceh'                               => 'Aceh',
                'bangka belitung islands'            => 'Kepulauan Bangka Belitung',
                'bangka–belitung islands'            => 'Kepulauan Bangka Belitung',
                'kepulauan bangka belitung'          => 'Kepulauan Bangka Belitung',
                'west kalimantan'                    => 'Kalimantan Barat',
                'kalimantan barat'                   => 'Kalimantan Barat',
                'central kalimantan'                 => 'Kalimantan Tengah',
                'kalimantan tengah'                  => 'Kalimantan Tengah',
                'south kalimantan'                   => 'Kalimantan Selatan',
                'kalimantan selatan'                 => 'Kalimantan Selatan',
                'east kalimantan'                    => 'Kalimantan Timur',
                'kalimantan timur'                   => 'Kalimantan Timur',
                'north kalimantan'                   => 'Kalimantan Utara',
                'kalimantan utara'                   => 'Kalimantan Utara',
                'south sulawesi'                     => 'Sulawesi Selatan',
                'sulawesi selatan'                   => 'Sulawesi Selatan',
                'north sulawesi'                     => 'Sulawesi Utara',
                'sulawesi utara'                     => 'Sulawesi Utara',
                'central sulawesi'                   => 'Sulawesi Tengah',
                'sulawesi tengah'                    => 'Sulawesi Tengah',
                'southeast sulawesi'                 => 'Sulawesi Tenggara',
                'sulawesi tenggara'                  => 'Sulawesi Tenggara',
                'west sulawesi'                      => 'Sulawesi Barat',
                'sulawesi barat'                     => 'Sulawesi Barat',
                'gorontalo'                          => 'Gorontalo',
                'west nusa tenggara'                 => 'Nusa Tenggara Barat',
                'nusa tenggara barat'                => 'Nusa Tenggara Barat',
                'east nusa tenggara'                 => 'Nusa Tenggara Timur',
                'nusa tenggara timur'                => 'Nusa Tenggara Timur',
                'maluku'                             => 'Maluku',
                'north maluku'                       => 'Maluku Utara',
                'maluku utara'                       => 'Maluku Utara',
                'papua'                              => 'Papua',
                'west papua'                         => 'Papua Barat',
                'papua barat'                        => 'Papua Barat',
                'south papua'                        => 'Papua Selatan',
                'central papua'                      => 'Papua Tengah',
                'highland papua'                     => 'Papua Pegunungan',
                'southwest papua'                    => 'Papua Barat Daya',
            ];

            if (isset($idProvinceMap[$lower])) {
                return $idProvinceMap[$lower];
            }
        }

        if ($region !== '') {
            return $region;
        }
        if ($city !== '') {
            return $city;
        }
        return 'Wilayah Tidak Spesifik';
    }

    /**
     * Menghasilkan emoji bendera Unicode dari kode negara ISO-3166-1 alpha-2
     */
    public static function countryCodeToFlag(?string $countryCode): string
    {
        $code = strtoupper(trim((string) $countryCode));
        if (strlen($code) !== 2 || ! ctype_alpha($code) || $code === 'XX') {
            return '🌐';
        }
        $first = mb_chr(ord($code[0]) - 65 + 0x1F1E6, 'UTF-8');
        $second = mb_chr(ord($code[1]) - 65 + 0x1F1E6, 'UTF-8');
        return $first . $second;
    }

    /**
     * Resolusi geolokasi IP pengunjung secara cepat (Cache DB -> Cloudflare Header -> ip-api non-blocking)
     */
    public function resolveGeoLocation(string $ip): array
    {
        $ip = trim($ip);
        $default = [
            'country_code' => 'ID',
            'country_name' => 'Indonesia',
            'region_name'  => 'Jaringan Internal / Lokal',
            'city_name'    => 'Peladen Lokal',
            'isp_name'     => 'Jaringan Internal',
        ];

        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
            return $default;
        }

        // Cek apakah IP merupakan jaringan privat / reservasi (RFC1918 / VPN internal)
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $default;
        }

        // 1. Cek cache dari tabel landing_visitors jika IP ini sudah pernah direkam sebelumnya
        try {
            $cached = $this->db->query(
                "SELECT country_code, country_name, region_name, city_name, isp_name
                 FROM landing_visitors
                 WHERE ip_address = ? AND country_code IS NOT NULL AND country_code != ''
                 LIMIT 1",
                [$ip]
            )->getRowArray();

            if ($cached && ! empty($cached['country_code'])) {
                return [
                    'country_code' => $cached['country_code'],
                    'country_name' => $cached['country_name'] ?: self::normalizeCountryName($cached['country_code']),
                    'region_name'  => $cached['region_name'] ?: 'Wilayah Tidak Spesifik',
                    'city_name'    => $cached['city_name'] ?: null,
                    'isp_name'     => $cached['isp_name'] ?: null,
                ];
            }
        } catch (\Throwable $e) {
            // Abaikan bila kolom belum dimigrasikan
        }

        // 2. Cek Header Cloudflare jika tersedia lengkap
        $cfCountry = strtoupper(trim((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
        $cfRegion  = trim((string) ($_SERVER['HTTP_CF_REGION'] ?? ''));
        $cfCity    = trim((string) ($_SERVER['HTTP_CF_IPCITY'] ?? ''));

        if ($cfCountry !== '' && $cfCountry !== 'XX' && $cfCountry !== 'T1' && $cfRegion !== '') {
            return [
                'country_code' => substr($cfCountry, 0, 10),
                'country_name' => substr(self::normalizeCountryName($cfCountry), 0, 100),
                'region_name'  => substr(self::normalizeRegionName($cfCountry, $cfRegion, $cfCity), 0, 120),
                'city_name'    => $cfCity !== '' ? substr($cfCity, 0, 120) : null,
                'isp_name'     => null,
            ];
        }

        // 3. Lookup ringan ke ip-api.com dengan batas waktu ketat (1.2 detik) agar tidak menahan render landing page
        try {
            $ctx = stream_context_create([
                'http' => [
                    'method'        => 'GET',
                    'timeout'       => 1.2,
                    'ignore_errors' => true,
                    'header'        => "User-Agent: ARTilik-GeoAnalytics/1.0\r\nAccept: application/json\r\n",
                ],
            ]);
            $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,country,countryCode,regionName,city,isp';
            $raw = @file_get_contents($url, false, $ctx);
            if (is_string($raw) && $raw !== '') {
                $data = json_decode($raw, true);
                if (is_array($data) && ($data['status'] ?? '') === 'success') {
                    $cCode = strtoupper(trim((string) ($data['countryCode'] ?? 'XX')));
                    $cName = self::normalizeCountryName($cCode, $data['country'] ?? null);
                    $rName = self::normalizeRegionName($cCode, $data['regionName'] ?? null, $data['city'] ?? null);
                    $city  = trim((string) ($data['city'] ?? ''));
                    $isp   = trim((string) ($data['isp'] ?? ''));

                    return [
                        'country_code' => substr($cCode ?: 'XX', 0, 10),
                        'country_name' => substr($cName, 0, 100),
                        'region_name'  => substr($rName, 0, 120),
                        'city_name'    => $city !== '' ? substr($city, 0, 120) : null,
                        'isp_name'     => $isp !== '' ? substr($isp, 0, 180) : null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Fallback ke header CF jika ada
        }

        if ($cfCountry !== '' && $cfCountry !== 'XX' && $cfCountry !== 'T1') {
            return [
                'country_code' => substr($cfCountry, 0, 10),
                'country_name' => substr(self::normalizeCountryName($cfCountry), 0, 100),
                'region_name'  => substr(self::normalizeRegionName($cfCountry, $cfRegion, $cfCity), 0, 120),
                'city_name'    => $cfCity !== '' ? substr($cfCity, 0, 120) : null,
                'isp_name'     => null,
            ];
        }

        return [
            'country_code' => null,
            'country_name' => null,
            'region_name'  => null,
            'city_name'    => null,
            'isp_name'     => null,
        ];
    }

    /**
     * Catat kunjungan unik pengunjung ke landing page beserta atribusi negara & wilayah
     */
    public function recordVisit(string $ip, ?string $userAgent = null): array
    {
        $db = $this->db;
        $ip = trim($ip);
        if ($ip === '') {
            $ip = '127.0.0.1';
        }

        // Hashing anonim untuk privasi data akademik (tidak menyimpan raw identity secara terbuka)
        $salt = env('VISITOR_SALT', 'artilik-visitor-counter-salt-2026');
        $visitorHash = hash('sha256', $ip . '|' . $salt);
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $uaTrimmed = substr((string) $userAgent, 0, 500);

        $geo = $this->resolveGeoLocation($ip);

        try {
            // Atomic UPSERT: hitungan bertambah jika IP yang sama berkunjung kembali di hari yang sama
            $sql = "INSERT INTO landing_visitors (
                        visitor_hash, ip_address, country_code, country_name, region_name, city_name, isp_name,
                        user_agent, visit_date, hits, created_at, updated_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        hits = hits + 1,
                        country_code = COALESCE(landing_visitors.country_code, VALUES(country_code)),
                        country_name = COALESCE(landing_visitors.country_name, VALUES(country_name)),
                        region_name  = COALESCE(landing_visitors.region_name, VALUES(region_name)),
                        city_name    = COALESCE(landing_visitors.city_name, VALUES(city_name)),
                        isp_name     = COALESCE(landing_visitors.isp_name, VALUES(isp_name)),
                        updated_at   = VALUES(updated_at)";
            $db->query($sql, [
                $visitorHash,
                $ip,
                $geo['country_code'],
                $geo['country_name'],
                $geo['region_name'],
                $geo['city_name'],
                $geo['isp_name'],
                $uaTrimmed,
                $today,
                $now,
                $now,
            ]);
        } catch (\Throwable $e) {
            // Fallback ke query tanpa kolom geo jika migrasi belum dijalankan
            try {
                $sqlFallback = "INSERT INTO landing_visitors (visitor_hash, ip_address, user_agent, visit_date, hits, created_at, updated_at)
                                VALUES (?, ?, ?, ?, 1, ?, ?)
                                ON DUPLICATE KEY UPDATE hits = hits + 1, updated_at = VALUES(updated_at)";
                $db->query($sqlFallback, [$visitorHash, $ip, $uaTrimmed, $today, $now, $now]);
            } catch (\Throwable $inner) {
                log_message('error', 'VisitorModel recordVisit error: ' . $inner->getMessage());
            }
        }

        return $this->getStats();
    }

    /**
     * Mengambil statistik ringkasan pengunjung unik
     */
    public function getStats(): array
    {
        $db = $this->db;
        $today = date('Y-m-d');

        try {
            $rowTotal = $db->query("SELECT COUNT(DISTINCT visitor_hash) AS total_unique, COALESCE(SUM(hits), 0) AS total_hits FROM landing_visitors")->getRowArray();
            $rowToday = $db->query("SELECT COUNT(*) AS today_unique, COALESCE(SUM(hits), 0) AS today_hits FROM landing_visitors WHERE visit_date = ?", [$today])->getRowArray();

            $totalUnique = (int) ($rowTotal['total_unique'] ?? 0);
            $totalHits   = (int) ($rowTotal['total_hits'] ?? 0);
            $todayUnique = (int) ($rowToday['today_unique'] ?? 0);
            $todayHits   = (int) ($rowToday['today_hits'] ?? 0);
        } catch (\Throwable $e) {
            log_message('error', 'VisitorModel getStats error: ' . $e->getMessage());
            $totalUnique = 1;
            $totalHits   = 1;
            $todayUnique = 1;
            $todayHits   = 1;
        }

        return [
            'total_unique' => $totalUnique,
            'today_unique' => $todayUnique,
            'total_hits'   => $totalHits,
            'today_hits'   => $todayHits,
        ];
    }

    /**
     * Mengambil analitik survei pengunjung berdasarkan Negara dan Wilayah/Provinsi
     */
    public function getGeoAnalytics(): array
    {
        $db = $this->db;
        $emptyResult = [
            'summary' => [
                'total_countries'         => 0,
                'total_regions'           => 0,
                'total_cities'            => 0,
                'domestic_unique'         => 0,
                'domestic_hits'           => 0,
                'domestic_share_pct'      => 0.0,
                'international_unique'    => 0,
                'international_hits'      => 0,
                'international_share_pct' => 0.0,
                'top_country'             => null,
                'top_region'              => null,
                'unresolved_ips'          => 0,
            ],
            'countries' => [],
            'regions'   => [],
        ];

        try {
            if (! $db->fieldExists('country_code', 'landing_visitors')) {
                return $emptyResult;
            }

            $totalsRow = $db->query("
                SELECT
                    COUNT(DISTINCT visitor_hash) AS total_unique,
                    COALESCE(SUM(hits), 0) AS total_hits,
                    COUNT(DISTINCT CASE WHEN country_code IS NULL OR country_code = '' THEN ip_address END) AS unresolved_ips
                FROM landing_visitors
            ")->getRowArray();

            $totalUniqueAll = max(1, (int) ($totalsRow['total_unique'] ?? 0));
            $totalHitsAll   = max(1, (int) ($totalsRow['total_hits'] ?? 0));
            $unresolvedIps  = (int) ($totalsRow['unresolved_ips'] ?? 0);

            // 1. Agregasi per Negara
            $countryRows = $db->query("
                SELECT
                    COALESCE(NULLIF(country_code, ''), 'XX') AS country_code,
                    COALESCE(NULLIF(country_name, ''), 'Tidak Diketahui') AS country_name,
                    COUNT(DISTINCT visitor_hash) AS unique_visitors,
                    COALESCE(SUM(hits), 0) AS total_hits,
                    COUNT(DISTINCT CASE WHEN region_name IS NOT NULL AND region_name != '' THEN region_name END) AS region_count,
                    COUNT(DISTINCT CASE WHEN city_name IS NOT NULL AND city_name != '' THEN city_name END) AS city_count,
                    MAX(visit_date) AS last_visit
                FROM landing_visitors
                GROUP BY COALESCE(NULLIF(country_code, ''), 'XX'), COALESCE(NULLIF(country_name, ''), 'Tidak Diketahui')
                ORDER BY unique_visitors DESC, total_hits DESC
            ")->getResultArray();

            $countries = [];
            $domesticUnique = 0;
            $domesticHits = 0;
            $intlUnique = 0;
            $intlHits = 0;
            $distinctCountriesCount = 0;

            foreach ($countryRows as $row) {
                $code = strtoupper((string) $row['country_code']);
                $name = self::normalizeCountryName($code, $row['country_name']);
                $u = (int) $row['unique_visitors'];
                $h = (int) $row['total_hits'];
                $shareU = round(($u / $totalUniqueAll) * 100, 1);
                $shareH = round(($h / $totalHitsAll) * 100, 1);
                $avg = $u > 0 ? round($h / $u, 2) : 1.00;

                if ($code !== 'XX') {
                    $distinctCountriesCount++;
                }

                if ($code === 'ID') {
                    $domesticUnique += $u;
                    $domesticHits += $h;
                } else {
                    $intlUnique += $u;
                    $intlHits += $h;
                }

                $countries[] = [
                    'country_code'     => $code,
                    'country_name'     => $name,
                    'flag'             => self::countryCodeToFlag($code),
                    'unique_visitors'  => $u,
                    'total_hits'       => $h,
                    'region_count'     => (int) $row['region_count'],
                    'city_count'       => (int) $row['city_count'],
                    'share_unique_pct' => $shareU,
                    'share_hits_pct'   => $shareH,
                    'avg_hits'         => number_format($avg, 2),
                    'last_visit'       => $row['last_visit'],
                    'is_domestic'      => ($code === 'ID'),
                ];
            }

            // 2. Agregasi per Wilayah / Provinsi & Kota
            $regionRows = $db->query("
                SELECT
                    COALESCE(NULLIF(country_code, ''), 'XX') AS country_code,
                    COALESCE(NULLIF(country_name, ''), 'Tidak Diketahui') AS country_name,
                    COALESCE(NULLIF(region_name, ''), 'Wilayah Tidak Spesifik') AS region_name,
                    GROUP_CONCAT(DISTINCT NULLIF(city_name, '') ORDER BY city_name ASC SEPARATOR ', ') AS cities_list,
                    SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT NULLIF(isp_name, '') ORDER BY hits DESC SEPARATOR '||'), '||', 1) AS top_isp,
                    COUNT(DISTINCT visitor_hash) AS unique_visitors,
                    COALESCE(SUM(hits), 0) AS total_hits,
                    MAX(visit_date) AS last_visit
                FROM landing_visitors
                GROUP BY
                    COALESCE(NULLIF(country_code, ''), 'XX'),
                    COALESCE(NULLIF(country_name, ''), 'Tidak Diketahui'),
                    COALESCE(NULLIF(region_name, ''), 'Wilayah Tidak Spesifik')
                ORDER BY unique_visitors DESC, total_hits DESC
                LIMIT 60
            ")->getResultArray();

            $regions = [];
            foreach ($regionRows as $r) {
                $code = strtoupper((string) $r['country_code']);
                $cName = self::normalizeCountryName($code, $r['country_name']);
                $rName = self::normalizeRegionName($code, $r['region_name']);
                $u = (int) $r['unique_visitors'];
                $h = (int) $r['total_hits'];
                $shareU = round(($u / $totalUniqueAll) * 100, 1);
                $shareH = round(($h / $totalHitsAll) * 100, 1);
                $avg = $u > 0 ? round($h / $u, 2) : 1.00;

                $citiesRaw = trim((string) ($r['cities_list'] ?? ''));
                if ($citiesRaw !== '') {
                    $cityParts = array_slice(array_filter(array_map('trim', explode(',', $citiesRaw))), 0, 4);
                    $citiesFormatted = implode(', ', $cityParts);
                } else {
                    $citiesFormatted = '-';
                }

                $regions[] = [
                    'country_code'     => $code,
                    'country_name'     => $cName,
                    'flag'             => self::countryCodeToFlag($code),
                    'region_name'      => $rName,
                    'top_cities'       => $citiesFormatted,
                    'top_isp'          => trim((string) ($r['top_isp'] ?? '')) ?: '-',
                    'unique_visitors'  => $u,
                    'total_hits'       => $h,
                    'share_unique_pct' => $shareU,
                    'share_hits_pct'   => $shareH,
                    'avg_hits'         => number_format($avg, 2),
                    'last_visit'       => $r['last_visit'],
                    'is_domestic'      => ($code === 'ID'),
                ];
            }

            $regionStatsRow = $db->query("
                SELECT
                    COUNT(DISTINCT CASE WHEN region_name IS NOT NULL AND region_name != '' THEN CONCAT(COALESCE(country_code,''), ':', region_name) END) AS total_regions,
                    COUNT(DISTINCT CASE WHEN city_name IS NOT NULL AND city_name != '' THEN CONCAT(COALESCE(country_code,''), ':', city_name) END) AS total_cities
                FROM landing_visitors
            ")->getRowArray();

            $denomUnique = max(1, $domesticUnique + $intlUnique);
            $domSharePct = round(($domesticUnique / $denomUnique) * 100, 1);
            $intlSharePct = round(($intlUnique / $denomUnique) * 100, 1);

            return [
                'summary' => [
                    'total_countries'         => max($distinctCountriesCount, count($countries)),
                    'total_regions'           => (int) ($regionStatsRow['total_regions'] ?? count($regions)),
                    'total_cities'            => (int) ($regionStatsRow['total_cities'] ?? 0),
                    'domestic_unique'         => $domesticUnique,
                    'domestic_hits'           => $domesticHits,
                    'domestic_share_pct'      => $domSharePct,
                    'international_unique'    => $intlUnique,
                    'international_hits'      => $intlHits,
                    'international_share_pct' => $intlSharePct,
                    'top_country'             => $countries[0] ?? null,
                    'top_region'              => $regions[0] ?? null,
                    'unresolved_ips'          => $unresolvedIps,
                ],
                'countries' => $countries,
                'regions'   => $regions,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'VisitorModel getGeoAnalytics error: ' . $e->getMessage());
            return $emptyResult;
        }
    }
}
