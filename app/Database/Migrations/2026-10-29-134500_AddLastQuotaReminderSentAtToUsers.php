<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastQuotaReminderSentAtToUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('last_quota_reminder_sent_at', 'users')) {
            $this->forge->addColumn('users', [
                'last_quota_reminder_sent_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'last_weekly_summary_sent_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('last_quota_reminder_sent_at', 'users')) {
            $this->forge->dropColumn('users', 'last_quota_reminder_sent_at');
        }
    }
}
