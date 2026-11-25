<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGoogleOAuthToUsers extends Migration
{
    public function up()
    {
        // Izinkan password_hash bernilai NULL bagi pengguna yang mendaftar/masuk via Google OAuth
        $this->forge->modifyColumn('users', [
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
        ]);

        $cols = [];

        if (! $this->db->fieldExists('google_id', 'users')) {
            $cols['google_id'] = [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'email',
            ];
        }

        if (! $this->db->fieldExists('avatar_url', 'users')) {
            $cols['avatar_url'] = [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'google_id',
            ];
        }

        if (! empty($cols)) {
            $this->forge->addColumn('users', $cols);
        }

        $exists = $this->db->query("SHOW INDEX FROM users WHERE Key_name = 'uniq_users_google_id'")->getResultArray();
        if (empty($exists)) {
            $this->db->query('CREATE UNIQUE INDEX uniq_users_google_id ON users (google_id)');
        }
    }

    public function down()
    {
        $exists = $this->db->query("SHOW INDEX FROM users WHERE Key_name = 'uniq_users_google_id'")->getResultArray();
        if (! empty($exists)) {
            $this->db->query('DROP INDEX uniq_users_google_id ON users');
        }

        foreach (['avatar_url', 'google_id'] as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }

        $this->db->query("UPDATE users SET password_hash = '' WHERE password_hash IS NULL");
        $this->forge->modifyColumn('users', [
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
        ]);
    }
}
