<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Memverifikasi bahwa pengguna telah login dan akun aktif
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId = $session->get('user_id');

        if (! $userId || ! $session->get('is_logged_in')) {
            if ($request->isAJAX() || stripos($request->getHeaderLine('Accept'), 'application/json') !== false) {
                return service('response')->setStatusCode(401)->setJSON([
                    'status'  => 'unauthorized',
                    'message' => 'Silakan masuk terlebih dahulu untuk mengakses layanan ini.',
                ]);
            }
            return redirect()->to(base_url('/?auth=login'))->with('auth_notice', 'Silakan masuk ke akun Anda terlebih dahulu.');
        }

        // Cek apakah akun masih aktif di basis data
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user || $user['status'] !== 'active') {
            $session->destroy();
            if ($request->isAJAX() || stripos($request->getHeaderLine('Accept'), 'application/json') !== false) {
                return service('response')->setStatusCode(403)->setJSON([
                    'status'  => 'forbidden',
                    'message' => 'Akun Anda dinonaktifkan. Silakan hubungi pengelola layanan.',
                ]);
            }
            return redirect()->to(base_url('/?auth=login'))->with('error', 'Akun Anda tidak aktif atau dinonaktifkan.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak ada tindakan pasca-proses
    }
}
