<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentShareModel extends Model
{
    protected $table = 'document_shares';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'shared_by_user_id',
        'shared_with_user_id',
        'recipient_email',
        'permission',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    /**
     * Mengambil daftar share untuk dokumen tertentu beserta data profil penerima
     */
    public function getSharesForDocument(int $documentId): array
    {
        $db = db_connect();
        return $db->table('document_shares ds')
            ->select('ds.*, u.name as recipient_name, u.institution as recipient_institution')
            ->join('users u', 'u.id = ds.shared_with_user_id', 'left')
            ->where('ds.document_id', $documentId)
            ->where('ds.status !=', 'revoked')
            ->orderBy('ds.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Mengambil dokumen-dokumen karya ilmiah yang dibagikan kepada seorang pengguna
     */
    public function getDocumentsSharedWithUser(int $userId, int $limit = 50): array
    {
        $db = db_connect();
        $shares = $db->table('document_shares ds')
            ->select('ds.id as share_id, ds.permission, ds.created_at as shared_at, 
                      d.id as document_id, d.title, d.original_filename, d.file_size, d.mime_type, 
                      d.status as document_status, d.created_at as document_created_at,
                      d.faculty, d.department,
                      u.name as owner_name, u.email as owner_email, u.institution as owner_institution')
            ->join('documents d', 'd.id = ds.document_id')
            ->join('users u', 'u.id = ds.shared_by_user_id')
            ->where('ds.shared_with_user_id', $userId)
            ->where('ds.status', 'active')
            ->orderBy('ds.id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $reportModel = new ReportModel();
        foreach ($shares as &$item) {
            $latestReport = $reportModel->where('document_id', $item['document_id'])
                                        ->orderBy('id', 'DESC')
                                        ->first();
            $item['latest_report'] = $latestReport;
        }
        unset($item);

        return $shares;
    }

    /**
     * Hitung jumlah dokumen yang dibagikan ke pengguna
     */
    public function countSharedWithUser(int $userId): int
    {
        return $this->where('shared_with_user_id', $userId)
                    ->where('status', 'active')
                    ->countAllResults();
    }

    /**
     * Berbagi naskah karya ilmiah ke alamat email tertentu
     */
    public function shareWithEmail(int $documentId, int $sharedByUserId, string $email, string $permission = 'view_all'): array
    {
        $email = strtolower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Alamat email penerima tidak valid.',
            ];
        }

        $userModel = new UserModel();
        $owner = $userModel->find($sharedByUserId);
        if ($owner && strtolower($owner['email']) === $email) {
            return [
                'success' => false,
                'message' => 'Anda tidak dapat membagikan karya ilmiah kepada email akun Anda sendiri.',
            ];
        }

        // Cek apakah calon penerima sudah terdaftar di AR Tilik
        $recipientUser = $userModel->where('email', $email)->first();
        $recipientUserId = $recipientUser ? (int) $recipientUser['id'] : null;
        $status = $recipientUser ? 'active' : 'pending_registration';

        $now = date('Y-m-d H:i:s');

        // Cek apakah share ke email ini sudah pernah dibuat sebelumnya
        $existing = $this->where('document_id', $documentId)
                         ->where('recipient_email', $email)
                         ->first();

        if ($existing) {
            $this->update($existing['id'], [
                'shared_with_user_id' => $recipientUserId,
                'permission'          => $permission,
                'status'              => $status,
                'updated_at'          => $now,
            ]);
            $shareId = (int) $existing['id'];
        } else {
            $shareId = $this->insert([
                'document_id'         => $documentId,
                'shared_by_user_id'   => $sharedByUserId,
                'shared_with_user_id' => $recipientUserId,
                'recipient_email'     => $email,
                'permission'          => $permission,
                'status'              => $status,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }

        return [
            'success'       => true,
            'share_id'      => $shareId,
            'is_registered' => (bool) $recipientUser,
            'recipient'     => $recipientUser ? [
                'id'          => $recipientUser['id'],
                'name'        => $recipientUser['name'],
                'email'       => $recipientUser['email'],
                'institution' => $recipientUser['institution'],
            ] : null,
            'message'       => $recipientUser 
                ? "Naskah berhasil dibagikan kepada {$recipientUser['name']} ({$email}). Naskah akan langsung muncul di dashboard mereka."
                : "Undangan berbagi dicatat untuk {$email}. Karena pengguna belum terdaftar, mereka harus mendaftar akun AR Tilik terlebih dahulu untuk membukanya. Begitu mendaftar, naskah akan otomatis tersambung ke akun mereka.",
        ];
    }

    /**
     * Klaim semua naskah pending yang sebelumnya dibagikan ke email pengguna saat mereka baru mendaftar / mengaktifkan akun
     */
    public function claimPendingSharesForUser(int $userId, string $email): int
    {
        $email = strtolower(trim($email));
        $now = date('Y-m-d H:i:s');

        $pending = $this->where('recipient_email', $email)
                        ->groupStart()
                            ->where('shared_with_user_id IS NULL', null, false)
                            ->orWhere('status', 'pending_registration')
                        ->groupEnd()
                        ->findAll();

        if (empty($pending)) {
            return 0;
        }

        $count = 0;
        foreach ($pending as $item) {
            $this->update($item['id'], [
                'shared_with_user_id' => $userId,
                'status'              => 'active',
                'updated_at'          => $now,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Cek apakah seorang pengguna memiliki hak akses terhadap dokumen ini melalui sharing
     */
    public function userHasAccess(int $documentId, int $userId, string $requiredPermission = 'view_report'): bool
    {
        $share = $this->where('document_id', $documentId)
                      ->where('shared_with_user_id', $userId)
                      ->where('status', 'active')
                      ->first();

        if (! $share) {
            return false;
        }

        if ($requiredPermission === 'view_all' && $share['permission'] !== 'view_all') {
            return false;
        }

        return true;
    }
}
