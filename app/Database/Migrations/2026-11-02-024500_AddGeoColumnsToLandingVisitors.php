<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGeoColumnsToLandingVisitors extends Migration
{
    public function up()
    {
        $columns = [];

        if (! $this->db->fieldExists('country_code', 'landing_visitors')) {
            $columns['country_code'] = [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'default'    => null,
                'after'      => 'ip_address',
            ];
        }

        if (! $this->db->fieldExists('country_name', 'landing_visitors')) {
            $columns['country_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'country_code',
            ];
        }

        if (! $this->db->fieldExists('region_name', 'landing_visitors')) {
            $columns['region_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'default'    => null,
                'after'      => 'country_name',
            ];
        }

        if (! $this->db->fieldExists('city_name', 'landing_visitors')) {
            $columns['city_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'default'    => null,
                'after'      => 'region_name',
            ];
        }

        if (! $this->db->fieldExists('isp_name', 'landing_visitors')) {
            $columns['isp_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => true,
                'default'    => null,
                'after'      => 'city_name',
            ];
        }

        if (! empty($columns)) {
            $this->forge->addColumn('landing_visitors', $columns);
        }

        try {
            $this->db->query('CREATE INDEX idx_landing_visitors_geo ON landing_visitors (country_code, region_name)');
        } catch (\Throwable $e) {
            // Index mungkin sudah ada
        }
    }

    public function down()
    {
        $dropCols = [];
        foreach (['country_code', 'country_name', 'region_name', 'city_name', 'isp_name'] as $col) {
            if ($this->db->fieldExists($col, 'landing_visitors')) {
                $dropCols[] = $col;
            }
        }
        if (! empty($dropCols)) {
            $this->forge->dropColumn('landing_visitors', $dropCols);
        }
    }
}
