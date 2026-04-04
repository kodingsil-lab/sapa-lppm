<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityMemberRelations extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('activity_member_relations')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'activity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'activity_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'owner_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'member_user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'member_name' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['activity_type', 'activity_id'], false, false, 'idx_activity_member_lookup');
        $this->forge->addKey(['member_user_id', 'activity_type'], false, false, 'idx_activity_member_user');
        $this->forge->addKey(['owner_user_id', 'activity_type'], false, false, 'idx_activity_member_owner');

        $attributes = [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ];

        $this->forge->createTable('activity_member_relations', true, $attributes);
    }

    public function down()
    {
        $this->forge->dropTable('activity_member_relations', true);
    }
}
