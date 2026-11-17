<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOaiProvenanceToDocuments extends Migration
{
    public function up()
    {
        $cols = [];

        if (! $this->db->fieldExists('oai_identifier', 'documents')) {
            $cols['oai_identifier'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'source_type',
            ];
        }

        if (! $this->db->fieldExists('source_repository', 'documents')) {
            $cols['source_repository'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'oai_identifier',
            ];
        }

        if (! $this->db->fieldExists('source_url', 'documents')) {
            $cols['source_url'] = [
                'type'       => 'VARCHAR',
                'constraint' => 768,
                'null'       => true,
                'default'    => null,
                'after'      => 'source_repository',
            ];
        }

        if (! $this->db->fieldExists('harvest_batch_id', 'documents')) {
            $cols['harvest_batch_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'source_url',
            ];
        }

        if (! empty($cols)) {
            $this->forge->addColumn('documents', $cols);
        }

        $indexes = [
            'idx_documents_oai_identifier' => 'oai_identifier',
            'idx_documents_harvest_batch'  => 'harvest_batch_id',
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
        foreach (['idx_documents_oai_identifier', 'idx_documents_harvest_batch'] as $idxName) {
            $exists = $this->db->query("SHOW INDEX FROM documents WHERE Key_name = ?", [$idxName])->getResultArray();
            if (! empty($exists)) {
                $this->db->query("DROP INDEX {$idxName} ON documents");
            }
        }

        foreach (['harvest_batch_id', 'source_url', 'source_repository', 'oai_identifier'] as $col) {
            if ($this->db->fieldExists($col, 'documents')) {
                $this->forge->dropColumn('documents', $col);
            }
        }
    }
}
