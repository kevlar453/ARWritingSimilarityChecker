<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    /**
     * Mendaftarkan pengguna baru dengan email valid
     */
    public function register()
    {
        $data = $this->getRequestData();
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $institution = trim((string) ($data['institution'] ?? ''));

        if (empty($name) || empty($email) || empty($password)) {
            return $this->respondError('Nama, email, dan kata sandi wajib diisi.', 400);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respondError('Alamat email tidak valid. Harap gunakan format email yang benar.', 400);
        }

        if (strlen($password) < 6) {
            return $this->respondError('Kata sandi minimal 6 karakter demi keamanan akun Anda.', 400);
        }

        $userModel = new UserModel();
        $existing = $userModel->where('email', $email)->first();
        if ($existing) {
            return $this->respondError('Alamat email ini sudah terdaftar. Silakan langsung masuk.', 409);
        }

        $now = date('Y-m-d H:i:s');
        $resetAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $userId = $userModel->insert([
            'name'                            => $name,
            'email'                           => $email,
            'password_hash'                   => password_hash($password, PASSWORD_DEFAULT),
            'institution'                     => $institution ?: 'Institusi Akademik',
            'role'                            => 'user',
            'status'                          => 'active',
            'free_submissions_used_this_week' => 0,
            'week_reset_at'                   => $resetAt,
            'bonus_submissions'               => 0,
            'uploaded_corpus_count'           => 0,
            'last_login_at'                   => $now,
            'created_at'                      => $now,
            'updated_at'                      => $now,
        ]);

        if (! $userId) {
            return $this->respondError('Gagal mendaftarkan akun. Silakan coba kembali.', 500);
        }

        $user = $userModel->find($userId);
        $this->setUserSession($user);

        return $this->response->setStatusCode(201)->setJSON([
            'status'     => 'success',
            'message'    => "Pendaftaran berhasil! Selamat datang di AR Tilik, {$name}.",
            'user'       => [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'institution' => $user['institution'],
            ],
            'quota_info' => $userModel->getQuotaInfo($user),
            'redirect'   => base_url('dashboard'),
        ]);
    }

    /**
     * Masuk ke akun pengguna
     */
    public function login()
    {
        $data = $this->getRequestData();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if (empty($email) || empty($password)) {
            return $this->respondError('Email dan kata sandi wajib diisi.', 400);
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return $this->respondError('Email atau kata sandi tidak cocok. Silakan periksa kembali.', 401);
        }

        if ($user['status'] !== 'active') {
            return $this->respondError('Akun Anda dinonaktifkan. Silakan hubungi administrator.', 403);
        }

        // Perbarui masa kuota mingguan jika telah melewati 7 hari
        $user = $userModel->refreshWeeklyQuota($user);

        $now = date('Y-m-d H:i:s');
        $userModel->update($user['id'], [
            'last_login_at' => $now,
            'updated_at'    => $now,
        ]);

        $this->setUserSession($user);

        return $this->response->setJSON([
            'status'     => 'success',
            'message'    => "Selamat datang kembali, {$user['name']}!",
            'user'       => [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'institution' => $user['institution'],
            ],
            'quota_info' => $userModel->getQuotaInfo($user),
            'redirect'   => base_url('dashboard'),
        ]);
    }

    /**
     * Keluar dari sesi akun
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url())->with('message', 'Anda telah berhasil keluar.');
    }

    /**
     * Mengambil status autentikasi dan informasi kuota pengguna saat ini
     */
    public function status(): ResponseInterface
    {
        $userId = session()->get('user_id');
        if (! $userId) {
            return $this->response->setJSON([
                'authenticated' => false,
                'quota_info'    => (new UserModel())->getQuotaInfo(null),
            ]);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (! $user) {
            session()->destroy();
            return $this->response->setJSON([
                'authenticated' => false,
                'quota_info'    => $userModel->getQuotaInfo(null),
            ]);
        }

        return $this->response->setJSON([
            'authenticated' => true,
            'user'          => [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'email'       => $user['email'],
                'institution' => $user['institution'],
            ],
            'quota_info'    => $userModel->getQuotaInfo($user),
        ]);
    }

    private function setUserSession(array $user): void
    {
        session()->set([
            'user_id'          => (int) $user['id'],
            'user_name'        => $user['name'],
            'user_email'       => $user['email'],
            'user_institution' => $user['institution'],
            'user_role'        => $user['role'],
            'is_logged_in'     => true,
        ]);
    }

    private function getRequestData(): array
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (stripos($contentType, 'application/json') !== false) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    return $json;
                }
            } catch (\Throwable $e) {
                // Ignore and fall back to post
            }
        }

        return $this->request->getPost() ?: [];
    }

    private function respondError(string $message, int $statusCode): ResponseInterface
    {
        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') === 'application/json') {
            return $this->response->setStatusCode($statusCode)->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
