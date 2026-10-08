<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApiKeyModel;

class ApiKeyController extends BaseController
{
    protected ApiKeyModel $apiKeyModel;

    public function __construct()
    {
        $this->apiKeyModel = new ApiKeyModel();
    }

    /**
     * GET /api-keys/list
     * Mengambil daftar API Key pengguna saat ini
     */
    public function listKeys()
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Silakan masuk terlebih dahulu.'
            ]);
        }

        $keys = $this->apiKeyModel->getUserKeys($userId);
        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_map(function ($k) {
                return [
                    'id'                    => (int) $k['id'],
                    'key_name'              => $k['key_name'],
                    'key_prefix'            => $k['key_prefix'],
                    'system_type'           => $k['system_type'],
                    'quota_limit'           => (int) $k['quota_limit'],
                    'quota_used'            => (int) $k['quota_used'],
                    'rate_limit_per_minute' => (int) $k['rate_limit_per_minute'],
                    'is_active'             => (bool) $k['is_active'],
                    'last_used_at'          => $k['last_used_at'],
                    'created_at'            => $k['created_at'],
                ];
            }, $keys)
        ]);
    }

    /**
     * POST /api-keys/generate
     * Membuat API Key baru
     */
    public function generate()
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Silakan masuk terlebih dahulu.'
            ]);
        }

        $keyName = trim($this->request->getPost('key_name') ?: '');
        if (empty($keyName)) {
            $keyName = 'API Key ' . date('d M Y');
        }

        $systemType = $this->request->getPost('system_type') ?: 'ojs';
        if (! in_array($systemType, ['ojs', 'web', 'cli', 'integration'])) {
            $systemType = 'ojs';
        }

        // Batasi maksimal 5 kunci aktif per pengguna
        $activeKeys = $this->apiKeyModel->where('user_id', $userId)->where('is_active', 1)->countAllResults();
        if ($activeKeys >= 5) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Anda telah mencapai batas maksimal 5 API Key aktif. Silakan cabut kunci yang tidak terpakai terlebih dahulu.'
            ]);
        }

        $userRole = session()->get('user_role');
        $isAdmin = ($userRole === 'admin');
        $defaultQuota = 100;
        $requestedQuota = (int) ($this->request->getPost('quota_limit') ?: $defaultQuota);
        $quotaLimit = $isAdmin ? min(10000, max(10, $requestedQuota)) : min(500, max(10, $requestedQuota));

        $res = $this->apiKeyModel->generateKey($userId, $keyName, $systemType, $quotaLimit, 60);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'API Key berhasil dibuat. Salin dan simpan kunci ini sekarang karena tidak akan ditampilkan kembali.',
            'data'    => $res
        ]);
    }

    /**
     * POST /api-keys/revoke/(:num)
     * Mencabut / menonaktifkan API Key
     */
    public function revoke($id)
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Silakan masuk terlebih dahulu.'
            ]);
        }

        $role = session()->get('user_role');
        $isAdmin = ($role === 'admin');

        $success = $this->apiKeyModel->revokeKey((int) $id, $isAdmin ? null : $userId);

        if ($success) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'API Key berhasil dinonaktifkan.'
            ]);
        }

        return $this->response->setStatusCode(400)->setJSON([
            'status'  => 'error',
            'message' => 'Gagal menonaktifkan API Key atau hak akses tidak sesuai.'
        ]);
    }
}
