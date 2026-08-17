<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nis' => ['type' => 'VARCHAR', 'constraint' => 30, 'unique' => true],
            'nisn' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'jenis_kelamin' => ['type' => 'VARCHAR', 'constraint' => 1],
            'kelas_id' => ['type' => 'INT', 'constraint' => 11],
            'alamat' => ['type' => 'TEXT', 'null' => true],
            'no_telp' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif' => ['type' => 'SMALLINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('kelas_id', 'tb_kelas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_siswa');
    }

    public function down()
    {
        $this->forge->dropTable('tb_siswa');
    }
}
