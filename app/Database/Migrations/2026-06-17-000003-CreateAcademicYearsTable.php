<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicYearsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tahun_ajaran' => ['type' => 'VARCHAR', 'constraint' => 20],
            'semester' => ['type' => 'VARCHAR', 'constraint' => 10],
            'aktif' => ['type' => 'SMALLINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_tahun_ajaran');
    }

    public function down()
    {
        $this->forge->dropTable('tb_tahun_ajaran');
    }
}
