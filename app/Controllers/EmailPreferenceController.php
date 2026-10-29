<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class EmailPreferenceController extends BaseController
{
    /**
     * Halaman Publik Konfirmasi Berhenti Berlangganan (Unsubscribe) & Manajemen Preferensi Surel
     * Mendukung One-Click Unsubscribe (RFC 8058) dan formulir interaktif
     */
    public function unsubscribe(): string|ResponseInterface
    {
        $token = trim((string) ($this->request->getGet('token') ?? $this->request->getPost('token') ?? ''));
        $type  = trim((string) ($this->request->getGet('type') ?? $this->request->getPost('type') ?? 'weekly'));

        $userModel = new UserModel();
        $user = $token !== '' ? $userModel->findByUnsubscribeToken($token) : null;

        // Penanganan One-Click Unsubscribe (RFC 8058 POST)
        if ($this->request->getMethod() === 'POST' && $this->request->getHeaderLine('List-Unsubscribe') === 'One-Click') {
            if ($user) {
                $updateData = [];
                if ($type === 'weekly' || $type === 'quota' || $type === 'all') {
                    $updateData['email_weekly_summary'] = 0;
                }
                if ($type === 'report' || $type === 'all') {
                    $updateData['email_notifications_enabled'] = 0;
                }
                if (!empty($updateData)) {
                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                    $userModel->update($user['id'], $updateData);
                }
            }
            return $this->response->setStatusCode(200)->setBody('Unsubscribed successfully');
        }

        $message = null;
        $success = false;

        // Pemrosesan Formulir Simpan Preferensi (POST dari form)
        if ($this->request->getMethod() === 'POST') {
            if (!$user) {
                return view('email/unsubscribe', [
                    'user'    => null,
                    'token'   => $token,
                    'type'    => $type,
                    'error'   => 'Tautan berhenti berlangganan tidak valid atau telah kedaluwarsa.',
                    'message' => null,
                ]);
            }

            $weeklyVal = $this->request->getPost('email_weekly_summary') ? 1 : 0;
            $reportVal = $this->request->getPost('email_notifications_enabled') ? 1 : 0;

            // Jika mengklik tombol pintas "Berhenti dari Semua"
            if ($this->request->getPost('action') === 'unsubscribe_all') {
                $weeklyVal = 0;
                $reportVal = 0;
            }

            $userModel->update($user['id'], [
                'email_weekly_summary'        => $weeklyVal,
                'email_notifications_enabled' => $reportVal,
                'updated_at'                  => date('Y-m-d H:i:s'),
            ]);

            // Ambil data terbaru
            $user = $userModel->find($user['id']);
            $success = true;
            $message = 'Preferensi pengiriman surel Anda berhasil diperbarui.';
        }

        // Tampilan antarmuka konfirmasi
        return view('email/unsubscribe', [
            'user'    => $user,
            'token'   => $token,
            'type'    => $type,
            'success' => $success,
            'message' => $message,
            'error'   => !$user && $token !== '' ? 'Tautan berhenti berlangganan tidak valid atau tidak ditemukan.' : null,
        ]);
    }
}
