<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'parent_document_id',
        'revision_root_id',
        'revision_number',
        'revision_note',
        'title',
        'author',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_path',
        'status',
        'document_type',
        'corpus_visibility',
        'source_type',
        'oai_identifier',
        'source_repository',
        'source_url',
        'harvest_batch_id',
        'faculty',
        'department',
        'indexing_status',
        'benchmark_case_id',
        'benchmark_run_id',
        'page_count',
        'share_token',
        'is_shared',
        'created_at',
        'updated_at',
        'file_purged_at',
    ];
    protected $useTimestamps = false;

    /**
     * Memvalidasi dokumen induk revisi (v1 -> v2 -> ...) sebelum memotong kuota
     * dan menghitung metadata rantai revisi secara terpusat.
     */
    public function resolveRevisionMetadata(
        ?int $parentDocumentId,
        ?int $userId,
        bool $isAdmin = false,
        ?string $revisionNote = null
    ): array {
        $cleanNote = trim((string) ($revisionNote ?? ''));
        $cleanNote = $cleanNote !== '' ? mb_substr($cleanNote, 0, 500) : null;

        if (! $parentDocumentId || $parentDocumentId <= 0) {
            return [
                'parent_document_id' => null,
                'revision_root_id'   => null,
                'revision_number'    => 1,
                'revision_note'      => $cleanNote,
                'default_faculty'    => null,
                'default_department' => null,
            ];
        }

        $parent = $this->find($parentDocumentId);
        if (! $parent || ($parent['document_type'] ?? 'submission') !== 'submission') {
            throw new \InvalidArgumentException('Dokumen induk revisi tidak ditemukan.');
        }

        if (! $isAdmin && $userId && (int) $parent['user_id'] !== (int) $userId) {
            throw new \RuntimeException('Akses ditolak: Anda tidak berwenang menautkan revisi ke dokumen milik pengguna lain.');
        }

        $rootId = ! empty($parent['revision_root_id']) ? (int) $parent['revision_root_id'] : (int) $parent['id'];
        if (empty($parent['revision_root_id'])) {
            $this->update((int) $parent['id'], [
                'revision_root_id' => $rootId,
                'revision_number'  => max(1, (int) ($parent['revision_number'] ?? 1)),
            ]);
        }

        $maxRow = $this->db->table($this->table)
            ->selectMax('revision_number')
            ->where('revision_root_id', $rootId)
            ->get()
            ->getRowArray();

        $parentRev = max(1, (int) ($parent['revision_number'] ?? 1));
        $chainMax = max($parentRev, (int) ($maxRow['revision_number'] ?? $parentRev));

        return [
            'parent_document_id' => (int) $parent['id'],
            'revision_root_id'   => $rootId,
            'revision_number'    => $chainMax + 1,
            'revision_note'      => $cleanNote,
            'default_faculty'    => $parent['faculty'] ?? null,
            'default_department' => $parent['department'] ?? null,
        ];
    }
}
