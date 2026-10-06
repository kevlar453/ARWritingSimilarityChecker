<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    /**
     * Memverifikasi hak akses pengguna khusus Administrator
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId = $session->get('user_id');

        if (! $userId || ! $session->get('is_logged_in')) {
            if ($request->isAJAX() || stripos($request->getHeaderLine('Accept'), 'application/json') !== false) {
                return service('response')->setStatusCode(401)->setJSON([
                    'status'  => 'unauthorized',
                    'message' => 'Sesi login telah berakhir atau Anda belum masuk.',
                ]);
            }
            return redirect()->to(base_url('/?auth=login'))->with('auth_notice', 'Silakan masuk dengan akun Administrator untuk mengakses Portal Admin.');
        }

        // Validasi status dan role langsung dari basis data
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user || $user['status'] !== 'active' || $user['role'] !== 'admin') {
            if ($request->isAJAX() || stripos($request->getHeaderLine('Accept'), 'application/json') !== false) {
                return service('response')->setStatusCode(403)->setJSON([
                    'status'  => 'forbidden',
                    'message' => 'Akses ditolak. Fitur ini hanya dapat diakses oleh Administrator.',
                ]);
            }
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses ditolak. Anda tidak memiliki hak akses Administrator ke Portal Admin.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada tindakan pasca-proses
    }
}
