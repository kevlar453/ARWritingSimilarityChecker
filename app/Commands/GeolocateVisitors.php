<?php

namespace App\Commands;

use App\Models\VisitorModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GeolocateVisitors extends BaseCommand
{
    protected $group       = 'Analytics';
    protected $name        = 'visitors:geolocate';
    protected $description = 'Menyelesaikan geolokasi negara dan wilayah/provinsi untuk seluruh data IP pengunjung di landing_visitors';
    protected $usage       = 'visitors:geolocate [options]';
    protected $arguments   = [];
    protected $options     = [
        '--force' => 'Paksa resolusi ulang seluruh data pengunjung termasuk yang sudah memiliki geolokasi',
        '--limit' => 'Batasi jumlah IP unik yang diproses (default: semua)',
    ];

    public function run(array $params)
    {
        $db = db_connect();
        $force = CLI::getOption('force') !== null;
        $limit = (int) (CLI::getOption('limit') ?? 0);

        CLI::write("=========================================================", 'green');
        CLI::write("=== AR Tilik - Geolocation Resolver Pengunjung Web ======", 'green');
        CLI::write("=========================================================", 'green');

        if (! $db->fieldExists('country_code', 'landing_visitors')) {
            CLI::error("Kolom country_code belum ada pada tabel landing_visitors. Jalankan 'php spark migrate' terlebih dahulu.");
            return;
        }

        $builder = $db->table('landing_visitors')->select('ip_address')->distinct();
        if (! $force) {
            $builder->groupStart()
                ->where('country_code IS NULL', null, false)
                ->orWhere('country_code', '')
                ->groupEnd();
        }

        if ($limit > 0) {
            $builder->limit($limit);
        }

        $rows = $builder->get()->getResultArray();
        $totalIps = count($rows);

        if ($totalIps === 0) {
            CLI::write("[✓] Seluruh IP pengunjung sudah memiliki atribut negara & wilayah lengkap.", 'cyan');
            return;
        }

        CLI::write("Ditemukan {$totalIps} IP pengunjung unik yang perlu disurvei geolokasinya...", 'yellow');

        $publicIps = [];
        $internalCount = 0;

        foreach ($rows as $r) {
            $ip = trim((string) $r['ip_address']);
            if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1' || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $db->table('landing_visitors')
                    ->where('ip_address', $ip)
                    ->update([
                        'country_code' => 'ID',
                        'country_name' => 'Indonesia',
                        'region_name'  => 'Jaringan Internal / Lokal',
                        'city_name'    => 'Peladen Lokal',
                        'isp_name'     => 'Jaringan Internal',
                    ]);
                $internalCount++;
            } else {
                $publicIps[] = $ip;
            }
        }

        CLI::write(" -> {$internalCount} IP lokal/privat ditandai sebagai Jaringan Internal (ID).", 'white');

        $chunks = array_chunk($publicIps, 100);
        $resolvedCount = 0;

        foreach ($chunks as $chunkIdx => $chunk) {
            $batchPayload = array_map(function ($ip) {
                return ['query' => $ip];
            }, $chunk);

            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json\r\nUser-Agent: ARTilik-BatchGeo/1.0\r\n",
                    'content' => json_encode($batchPayload),
                    'timeout' => 5.0,
                ],
            ]);

            $url = 'http://ip-api.com/batch?fields=status,query,country,countryCode,regionName,city,isp';
            $raw = @file_get_contents($url, false, $ctx);
            if (! $raw) {
                CLI::error("Gagal menghubungi ip-api untuk chunk #" . ($chunkIdx + 1));
                continue;
            }

            $items = json_decode($raw, true);
            if (! is_array($items)) {
                CLI::error("Respons JSON tidak valid untuk chunk #" . ($chunkIdx + 1));
                continue;
            }

            foreach ($items as $item) {
                $ip = $item['query'] ?? null;
                if (! $ip) {
                    continue;
                }

                $status = $item['status'] ?? 'fail';
                if ($status === 'success') {
                    $cCode = strtoupper(trim((string) ($item['countryCode'] ?? 'XX')));
                    $cName = VisitorModel::normalizeCountryName($cCode, $item['country'] ?? null);
                    $rName = VisitorModel::normalizeRegionName($cCode, $item['regionName'] ?? null, $item['city'] ?? null);
                    $city  = trim((string) ($item['city'] ?? ''));
                    $isp   = trim((string) ($item['isp'] ?? ''));

                    $db->table('landing_visitors')
                        ->where('ip_address', $ip)
                        ->update([
                            'country_code' => substr($cCode, 0, 10),
                            'country_name' => substr($cName, 0, 100),
                            'region_name'  => substr($rName, 0, 120),
                            'city_name'    => $city !== '' ? substr($city, 0, 120) : null,
                            'isp_name'     => $isp !== '' ? substr($isp, 0, 180) : null,
                        ]);
                    $resolvedCount++;
                } else {
                    $db->table('landing_visitors')
                        ->where('ip_address', $ip)
                        ->update([
                            'country_code' => 'XX',
                            'country_name' => 'Tidak Diketahui',
                            'region_name'  => 'Wilayah Tidak Spesifik',
                        ]);
                }
            }

            CLI::write(" -> Memproses chunk " . ($chunkIdx + 1) . "/" . count($chunks) . " (" . count($chunk) . " IP)", 'white');
            if ($chunkIdx < count($chunks) - 1) {
                usleep(300000); // 300ms jeda antar batch
            }
        }

        CLI::write("=========================================================", 'green');
        CLI::write("[✓] Sukses! Sebanyak {$resolvedCount} IP publik berhasil disurvei geolokasinya.", 'green');

        $visitorModel = new VisitorModel();
        $analytics = $visitorModel->getGeoAnalytics();
        $summary = $analytics['summary'];

        CLI::write("Ringkasan Survei Pengunjung:", 'cyan');
        CLI::write(" - Total Negara: " . $summary['total_countries'], 'white');
        CLI::write(" - Total Wilayah: " . $summary['total_regions'], 'white');
        CLI::write(" - Domestik (Indonesia): " . $summary['domestic_unique'] . " unik (" . $summary['domestic_share_pct'] . "%)", 'white');
        CLI::write(" - Mancanegara: " . $summary['international_unique'] . " unik (" . $summary['international_share_pct'] . "%)", 'white');
    }
}
