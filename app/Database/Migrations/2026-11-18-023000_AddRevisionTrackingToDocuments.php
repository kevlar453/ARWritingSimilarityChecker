<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRevisionTrackingToDocuments extends Migration
{
    public function up()
    {
        $cols = [];

        if (! $this->db->fieldExists('parent_document_id', 'documents')) {
            $cols['parent_document_id'] = [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'user_id',
            ];
        }

        if (! $this->db->fieldExists('revision_root_id', 'documents')) {
            $cols['revision_root_id'] = [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'parent_document_id',
            ];
        }

        if (! $this->db->fieldExists('revision_number', 'documents')) {
            $cols['revision_number'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 1,
                'after'      => 'revision_root_id',
            ];
        }

        if (! $this->db->fieldExists('revision_note', 'documents')) {
            $cols['revision_note'] = [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'revision_number',
            ];
        }

        if (! empty($cols)) {
            $this->forge->addColumn('documents', $cols);
        }

        $indexes = [
            'idx_documents_parent_id'     => 'parent_document_id',
            'idx_documents_revision_root' => 'revision_root_id',
        ];
        foreach ($indexes as $idxName => $colName) {
            $exists = $this->db->query("SHOW INDEX FROM documents WHERE Key_name = ?", [$idxName])->getResultArray();
            if (empty($exists)) {
                $this->db->query("CREATE INDEX {$idxName} ON documents ({$colName})");
            }
        }
    }

    public function down()
    {
        foreach (['idx_documents_parent_id', 'idx_documents_revision_root'] as $idxName) {
            $exists = $this->db->query("SHOW INDEX FROM documents WHERE Key_name = ?", [$idxName])->getResultArray();
            if (! empty($exists)) {
                $this->db->query("DROP INDEX {$idxName} ON documents");
            }
        }

        foreach (['revision_note', 'revision_number', 'revision_root_id', 'parent_document_id'] as $col) {
            if ($this->db->fieldExists($col, 'documents')) {
                $this->forge->dropColumn('documents', $col);
            }
        }
    }
}
