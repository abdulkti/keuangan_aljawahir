<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClassesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama_kelas' => ['type' => 'VARCHAR', 'constraint' => 50],
            'tingkat' => ['type' => 'VARCHAR', 'constraint' => 10],
            'jurusan' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_kelas');
    }

    public function down()
    {
        $this->forge->dropTable('tb_kelas');
    }
}
