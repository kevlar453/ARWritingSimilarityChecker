<?php

namespace App\Commands;

use App\Models\PaymentTransactionModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AutoExpirePayments extends BaseCommand
{
    protected $group       = 'Payment';
    protected $name        = 'payment:auto-expire';
    protected $description = 'Membatalkan atau menandai kedaluwarsa transaksi Midtrans pending yang telah melampaui batas waktu';
    protected $usage       = 'payment:auto-expire [options]';
    protected $arguments   = [];
    protected $options     = [
        '--hours'   => 'Batas usia transaksi pending dalam satuan jam (default: 24)',
        '--preview' => 'Hanya menampilkan transaksi yang akan dibatalkan/kedaluwarsa tanpa mengubah status',
    ];

    public function run(array $params)
    {
        date_default_timezone_set('Asia/Jakarta');
        $now = time();

        $hours = 24;
        $rawHours = CLI::getOption('hours');
        if ($rawHours === null) {
            foreach (CLI::getOptions() as $k => $v) {
                if (str_starts_with($k, 'hours=')) {
                    $rawHours = substr($k, strlen('hours='));
                    break;
                }
            }
        }
        if ($rawHours && is_numeric($rawHours)) {
            $hours = max(1, (int) $rawHours);
        }

        $isPreview = CLI::getOption('preview') !== null;
        if (!$isPreview) {
            foreach (CLI::getOptions() as $k => $v) {
                if (str_starts_with($k, 'preview')) {
                    $isPreview = true;
                    break;
                }
            }
        }

        CLI::write("==================================================", 'yellow');
        CLI::write("=== AR Tilik Payment Auto-Expire & Sync Engine ===", 'yellow');
        CLI::write("==================================================", 'yellow');
        CLI::write("Waktu Eksekusi: " . date('Y-m-d H:i:s', $now) . " WIB", 'cyan');
        CLI::write("Batas Usia Pending: > {$hours} jam (" . date('Y-m-d H:i:s', strtotime("-{$hours} hours", $now)) . ")", 'cyan');
        if ($isPreview) {
            CLI::write("[MODE SIMULASI / PREVIEW - Tidak ada perubahan data]", 'light_yellow');
        }

        $paymentModel = new PaymentTransactionModel();
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours", $now));

        $staleOrders = $paymentModel->where('transaction_status', 'pending')
                                    ->where('created_at <=', $cutoffTime)
                                    ->orderBy('id', 'ASC')
                                    ->findAll();

        $total = count($staleOrders);
        CLI::write("Ditemukan {$total} transaksi pending yang melebihi batas waktu.", $total > 0 ? 'white' : 'green');

        if ($total === 0) {
            CLI::write("Log transaksi bersih. Tidak ada transaksi tertumpuk!", 'green');
            return;
        }

        if ($isPreview) {
            foreach ($staleOrders as $idx => $order) {
                $num = $idx + 1;
                CLI::write("[{$num}/{$total}] Order: {$order['order_id']} | User ID: {$order['user_id']} | Dibuat: {$order['created_at']} | Nominal: Rp " . number_format($order['amount'], 0, ',', '.'), 'light_gray');
            }
            CLI::write("Simulasi selesai. Jalankan tanpa flag --preview untuk mengeksekusi.", 'cyan');
            return;
        }

        CLI::write("Memproses pembaruan dan sinkronisasi ke Midtrans...", 'white');
        $result = $paymentModel->autoExpirePendingTransactions($hours);

        CLI::newLine();
        CLI::write("Hasil Eksekusi:", 'green');
        CLI::write("• Total Transaksi Diperiksa : {$result['total_checked']}", 'white');
        CLI::write("• Transaksi Kedaluwarsa     : {$result['expired_count']}", 'yellow');
        CLI::write("• Transaksi Lunas Terverifikasi: {$result['settled_count']}", 'green');

        if (!empty($result['details'])) {
            CLI::newLine();
            CLI::write("Rincian Transaksi:", 'cyan');
            foreach ($result['details'] as $detail) {
                $badge = ($detail['status'] === 'settlement') ? '[LUNAS]' : '[KEDALUWARSA]';
                $color = ($detail['status'] === 'settlement') ? 'green' : 'light_gray';
                CLI::write("  {$badge} {$detail['order_id']}: {$detail['message']}", $color);
            }
        }

        CLI::newLine();
        CLI::write("Selesai! Log transaksi berhasil dibersihkan.", 'green');
    }
}
