<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWebhookSupportToApiKeysAndDeliveries extends Migration
{
    public function up()
    {
        $apiKeyCols = [];

        if (! $this->db->fieldExists('webhook_secret', 'api_keys')) {
            $apiKeyCols['webhook_secret'] = [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'api_key',
            ];
        }

        if (! $this->db->fieldExists('webhook_secret_fingerprint', 'api_keys')) {
            $apiKeyCols['webhook_secret_fingerprint'] = [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'default'    => null,
                'after'      => 'webhook_secret',
            ];
        }

        if (! empty($apiKeyCols)) {
            $this->forge->addColumn('api_keys', $apiKeyCols);
        }

        if (! $this->db->tableExists('webhook_deliveries')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'job_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'report_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'document_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'api_key_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'external_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                    'default'    => null,
                ],
                'callback_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 1000,
                ],
                'event_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'default'    => 'similarity_check.completed',
                ],
                'delivery_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'payload_sha256' => [
                    'type'       => 'CHAR',
                    'constraint' => 64,
                ],
                'secret_fingerprint' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                    'default'    => null,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['pending', 'delivered', 'failed', 'rejected_ssrf'],
                    'default'    => 'pending',
                ],
                'attempt_count' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'max_attempts' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 3,
                ],
                'http_status' => [
                    'type'       => 'SMALLINT',
                    'constraint' => 5,
                    'null'       => true,
                    'default'    => null,
                ],
                'response_excerpt' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                    'default'    => null,
                ],
                'last_error' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                ],
                'delivered_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('report_id');
            $this->forge->addKey('api_key_id');
            $this->forge->addKey('status');
            $this->forge->addKey('delivery_id');
            $this->forge->createTable('webhook_deliveries', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('webhook_deliveries')) {
            $this->forge->dropTable('webhook_deliveries', true);
        }

        $dropCols = [];
        foreach (['webhook_secret', 'webhook_secret_fingerprint'] as $col) {
            if ($this->db->fieldExists($col, 'api_keys')) {
                $dropCols[] = $col;
            }
        }
        if (! empty($dropCols)) {
            $this->forge->dropColumn('api_keys', $dropCols);
        }
    }
}
