<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true,
            ],
            'key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('tb_pengaturan');

        $this->db->table('tb_pengaturan')->insertBatch([
            ['key' => 'school_name', 'value' => 'Al-Jawahir Attarbawi'],
            ['key' => 'school_address', 'value' => ''],
            ['key' => 'school_phone', 'value' => ''],
            ['key' => 'school_email', 'value' => ''],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('tb_pengaturan');
    }
}
