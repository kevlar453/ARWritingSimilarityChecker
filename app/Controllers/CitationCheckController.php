<?php

namespace App\Controllers;

use App\Controllers\Api\CitationCheckController as ApiCitationCheckController;
use App\Models\UserModel;

class CitationCheckController extends BaseController
{
    /**
     * Halaman alat gratis publik /cek-sitasi (tanpa login, tanpa menyimpan teks pengguna).
     */
    public function index(): string
    {
        $currentUser = null;
        $userId = session()->get('user_id');
        if ($userId) {
            $currentUser = (new UserModel())->find($userId);
        }

        $quota = ApiCitationCheckController::readDailyIpQuotaStatus(
            (string) $this->request->getIPAddress()
        );

        return view('tools/citation_checker', [
            'currentUser' => $currentUser,
            'quota'       => $quota,
        ]);
    }
}
