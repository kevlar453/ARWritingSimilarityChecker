<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class HarvestCorpus extends BaseCommand
{
    protected $group       = 'Corpus';
    protected $name        = 'corpus:harvest';
    protected $description = 'Menjalankan pemanen otomatis korpus karya ilmiah nasional (Neliti & GARUDA Dikti)';
    protected $usage       = 'corpus:harvest [options]';
    protected $arguments   = [];
    protected $options     = [
        '--source'    => 'Sumber portal (neliti, garuda, all). Default: all',
        '--query'     => 'Kata kunci pencarian naskah',
        '--limit'     => 'Maksimal jumlah naskah yang diindeks. Default: 10',
        '--scheduled' => 'Jalankan semua jadwal pemanenan korpus aktif dari basis data',
    ];

    public function run(array $params)
    {
        CLI::write("=== AR Tilik National Corpus Harvester ===", 'green');

        $source    = CLI::getOption('source') ?? 'all';
        $query     = CLI::getOption('query') ?? '';
        $limit     = (int) (CLI::getOption('limit') ?? 10);
        $scheduled = CLI::getOption('scheduled') !== null;

        $pythonBin = ROOTPATH . '.venv/bin/python';
        $rootPath  = escapeshellarg(rtrim(ROOTPATH, '/'));

        if (! file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        if ($scheduled) {
            CLI::write("[*] Menjalankan pemanenan terjadwal dari database...", 'yellow');
            $cmd = "cd {$rootPath} && PYTHONPATH={$rootPath} {$pythonBin} -m worker.national_harvester --scheduled 2>&1";
        } else {
            $escapedSource = escapeshellarg($source);
            $escapedQuery  = escapeshellarg($query);
            CLI::write("[*] Menjalankan pemanenan portal: {$source} (query: '{$query}', limit: {$limit})...", 'yellow');
            $cmd = "cd {$rootPath} && PYTHONPATH={$rootPath} {$pythonBin} -m worker.national_harvester --source {$escapedSource} --query {$escapedQuery} --limit {$limit} 2>&1";
        }

        passthru($cmd, $returnVar);

        if ($returnVar === 0) {
            CLI::write("[✓] Pemanenan korpus selesai dieksekusi.", 'green');
        } else {
            CLI::error("[!] Pemanenan korpus selesai dengan peringatan/galat (kode: {$returnVar}).");
        }
    }
}
