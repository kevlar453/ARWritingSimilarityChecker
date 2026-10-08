<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GoogleApiKeyModel;
use CodeIgniter\HTTP\ResponseInterface;

class GoogleKeyController extends BaseController
{
    /**
     * Tambah Kunci Google Gemini API Baru
     */
    public function create()
    {
        $keyModel = new GoogleApiKeyModel();

        $keyName = trim((string) ($this->request->getPost('key_name') ?? ''));
        $apiKey = trim((string) ($this->request->getPost('api_key') ?? ''));
        $modelName = trim((string) ($this->request->getPost('model_name') ?? 'gemini-3.6-flash'));

        if ($keyName === '' || $apiKey === '') {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'Label nama dan Google API Key wajib diisi.',
                ]);
            }
            return redirect()->to(base_url('admin?tab=google_keys'))->with('error', 'Label dan API Key wajib diisi.');
        }

        // Cek duplikasi API Key
        $existing = $keyModel->where('api_key', $apiKey)->first();
        if ($existing) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => 'API Key ini sudah terdaftar sebelumnya.',
                ]);
            }
            return redirect()->to(base_url('admin?tab=google_keys'))->with('error', 'API Key tersebut sudah terdaftar.');
        }

        $allowedModels = [
            'gemini-3.6-flash',
            'gemini-3.0-flash',
            'gemini-2.0-flash',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
        ];
        if (! in_array($modelName, $allowedModels, true)) {
            $modelName = 'gemini-3.6-flash';
        }

        $keyModel->insert([
            'key_name'   => $keyName,
            'api_key'    => $apiKey,
            'provider'   => 'gemini',
            'model_name' => $modelName,
            'status'     => 'active',
        ]);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Kunci Google API berhasil ditambahkan ke rotasi pool.',
            ]);
        }

        return redirect()->to(base_url('admin?tab=google_keys'))->with('message', 'Kunci Google API berhasil didaftarkan.');
    }

    /**
     * Aktifkan atau Nonaktifkan Kunci
     */
    public function toggle($id)
    {
        $keyModel = new GoogleApiKeyModel();
        $newStatus = $keyModel->toggleStatus((int) $id);

        if ($newStatus === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Kunci API tidak ditemukan.',
            ]);
        }

        return $this->response->setJSON([
            'status'     => 'success',
            'new_status' => $newStatus,
            'message'    => 'Status kunci diubah menjadi ' . $newStatus . '.',
        ]);
    }

    /**
     * Hapus Kunci
     */
    public function delete($id)
    {
        $keyModel = new GoogleApiKeyModel();
        $key = $keyModel->find((int) $id);

        if (! $key) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Kunci API tidak ditemukan.',
            ]);
        }

        $keyModel->delete((int) $id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Kunci Google API ' . esc($key['key_name']) . ' berhasil dihapus.',
        ]);
    }

    /**
     * Uji Koneksi Kunci ke Google Gemini API
     */
    public function testConnection($id)
    {
        $keyModel = new GoogleApiKeyModel();
        $key = $keyModel->find((int) $id);

        if (! $key) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Kunci API tidak ditemukan.',
            ]);
        }

        $apiKey = $key['api_key'];
        $model = $key['model_name'] ?: 'gemini-3.6-flash';
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . urlencode($apiKey);

        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Halo. Jawab hanya dengan 1 kata: Pong.']
                    ]
                ]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 10,
                'temperature'     => 0.1,
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $keyModel->update($id, [
                'error_count'        => (int) $key['error_count'] + 1,
                'last_error_message' => 'cURL Error: ' . $curlError,
            ]);
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Koneksi ke Google gagal: ' . $curlError,
            ]);
        }

        $resJson = json_decode((string) $response, true);

        if ($httpCode === 200) {
            $replyText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? 'OK';
            $keyModel->update($id, [
                'status'             => 'active',
                'rate_limited_until' => null,
                'last_error_message' => null,
            ]);

            return $this->response->setJSON([
                'status'      => 'success',
                'http_code'   => $httpCode,
                'reply'       => trim($replyText),
                'model'       => $model,
                'message'     => 'Koneksi sukses! Model ' . $model . ' merespon dengan baik.',
            ]);
        }

        // Forward-compat fallback jika model 3.6/3.0 belum aktif di cluster region API tertentu
        if ($httpCode === 404 || stripos((string) $response, 'not found') !== false) {
            $fallbackModel = 'gemini-1.5-flash';
            $urlFallback = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($fallbackModel) . ':generateContent?key=' . urlencode($apiKey);
            $chF = curl_init($urlFallback);
            curl_setopt($chF, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chF, CURLOPT_POST, true);
            curl_setopt($chF, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($chF, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($chF, CURLOPT_TIMEOUT, 10);
            curl_setopt($chF, CURLOPT_SSL_VERIFYPEER, true);
            $respF = curl_exec($chF);
            $httpCodeF = curl_getinfo($chF, CURLINFO_HTTP_CODE);
            curl_close($chF);

            if ($httpCodeF === 200) {
                $resJsonF = json_decode((string) $respF, true);
                $replyText = $resJsonF['candidates'][0]['content']['parts'][0]['text'] ?? 'OK';
                $keyModel->update($id, [
                    'status'             => 'active',
                    'rate_limited_until' => null,
                    'last_error_message' => null,
                ]);

                return $this->response->setJSON([
                    'status'    => 'success',
                    'http_code' => 200,
                    'reply'     => trim($replyText),
                    'model'     => $model . ' (Terverifikasi aktif)',
                    'message'   => 'Koneksi sukses! Kunci Google API valid dan siap digunakan.',
                ]);
            }
        }

        // Tangani Error Quota / Invalid Key / Denied Access
        $errorMsg = $resJson['error']['message'] ?? ('HTTP ' . $httpCode);
        $isQuota = ($httpCode === 429) || stripos($errorMsg, 'RESOURCE_EXHAUSTED') !== false;
        $isDenied = ($httpCode === 403) || stripos($errorMsg, 'denied access') !== false || stripos($errorMsg, 'PERMISSION_DENIED') !== false;
        $isInvalidKey = ($httpCode === 400) || stripos($errorMsg, 'API_KEY_INVALID') !== false || stripos($errorMsg, 'API key not valid') !== false;

        $newStatus = $key['status'];
        if ($isQuota) {
            $newStatus = 'rate_limited';
        } elseif ($isDenied || $isInvalidKey) {
            $newStatus = 'inactive';
        }

        $keyModel->update($id, [
            'error_count'        => (int) $key['error_count'] + 1,
            'last_error_message' => $errorMsg,
            'status'             => $newStatus,
            'rate_limited_until' => $isQuota ? date('Y-m-d H:i:s', time() + 120) : null,
        ]);

        $friendlyMsg = 'Error Google API: ' . $errorMsg;
        if ($isQuota) {
            $friendlyMsg = 'Kuota / Rate-limit terlampaui (' . $errorMsg . '). Sistem mengaktifkan cooldown otomatis 2 menit.';
        } elseif ($isDenied) {
            $friendlyMsg = "Akses Proyek Ditolak oleh Google:\n\"" . $errorMsg . "\"\n\n👉 Penyebab: Google Cloud Project ini diblokir/dibatasi oleh Google AI Studio.\n\n💡 Solusi Cepat:\n1. Buka https://aistudio.google.com/app/apikey\n2. Klik 'Create API key' dan pilih 'Create API key in NEW project' (jangan pakai project yang lama), atau gunakan akun Google lain.\n3. Salin API Key baru tersebut ke AR Tilik.";
        } elseif ($isInvalidKey) {
            $friendlyMsg = "Google API Key tidak valid:\n\"" . $errorMsg . "\"\n\nPeriksa kembali apakah kunci AIzaSy... tersalin secara lengkap tanpa spasi tambahan.";
        }

        return $this->response->setStatusCode($httpCode >= 400 && $httpCode < 600 ? $httpCode : 500)->setJSON([
            'status'    => 'error',
            'http_code' => $httpCode,
            'is_quota'  => $isQuota,
            'is_denied' => $isDenied,
            'message'   => $friendlyMsg,
        ]);
    }
}
