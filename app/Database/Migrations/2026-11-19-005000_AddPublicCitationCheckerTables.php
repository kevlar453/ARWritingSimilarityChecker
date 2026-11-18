<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPublicCitationCheckerTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('public_citation_cache')) {
            $this->forge->addField([
                'query_hash' => [
                    'type'       => 'CHAR',
                    'constraint' => 64,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['verified', 'doubtful', 'unverified'],
                    'default'    => 'unverified',
                ],
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                    'default'    => null,
                ],
                'matched_title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                    'default'    => null,
                ],
                'matched_authors' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                ],
                'matched_year' => [
                    'type'       => 'SMALLINT',
                    'constraint' => 5,
                    'null'       => true,
                    'default'    => null,
                ],
                'doi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                ],
                'openalex_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                ],
                'source_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                    'default'    => null,
                ],
                'reason' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                    'null'       => true,
                    'default'    => null,
                ],
                'expires_at' => [
                    'type' => 'DATETIME',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                ],
            ]);
            $this->forge->addKey('query_hash', true);
            $this->forge->addKey('expires_at');
            $this->forge->createTable('public_citation_cache', true);
        }

        if (! $this->db->tableExists('public_citation_daily_quota')) {
            $this->forge->addField([
                'check_date' => [
                    'type' => 'DATE',
                ],
                'ip_hash' => [
                    'type'       => 'CHAR',
                    'constraint' => 64,
                ],
                'checks_used' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                ],
            ]);
            $this->forge->addKey(['check_date', 'ip_hash'], true);
            $this->forge->createTable('public_citation_daily_quota', true);
        }

        if (! $this->db->tableExists('public_citation_check_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'check_date' => [
                    'type' => 'DATE',
                ],
                'ip_hash' => [
                    'type'       => 'CHAR',
                    'constraint' => 64,
                ],
                'citations_count' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'verified_count' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'doubtful_count' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'unverified_count' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'unavailable_count' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'cache_hits' => [
                    'type'       => 'TINYINT',
                    'constraint' => 3,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['check_date', 'ip_hash']);
            $this->forge->createTable('public_citation_check_logs', true);
        }

        if (! $this->db->tableExists('public_citation_funnel_stats')) {
            $this->forge->addField([
                'stat_date' => [
                    'type' => 'DATE',
                ],
                'utm_source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'cta_arrivals' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'registrations' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                ],
            ]);
            $this->forge->addKey(['stat_date', 'utm_source'], true);
            $this->forge->createTable('public_citation_funnel_stats', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('public_citation_funnel_stats', true);
        $this->forge->dropTable('public_citation_check_logs', true);
        $this->forge->dropTable('public_citation_daily_quota', true);
        $this->forge->dropTable('public_citation_cache', true);
    }
}
