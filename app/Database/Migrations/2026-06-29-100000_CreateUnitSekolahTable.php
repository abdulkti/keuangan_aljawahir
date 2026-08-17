<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUnitSekolahTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'alamat' => ['type' => 'TEXT', 'null' => true],
            'kepala_sekolah' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_unit');
    }

    public function down()
    {
        $this->forge->dropTable('tb_unit');
    }
}
