<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGuruTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nip' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'jenis_kelamin' => ['type' => 'VARCHAR', 'constraint' => 1],
            'bidang' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sekolah' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'alamat' => ['type' => 'TEXT', 'null' => true],
            'no_telp' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif' => ['type' => 'SMALLINT', 'constraint' => 1, 'default' => 1],
            'tahun_ajaran_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'unit_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'saldo_awal' => ['type' => 'DECIMAL', 'constraint' => '15,0', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_guru');
    }

    public function down()
    {
        $this->forge->dropTable('tb_guru');
    }
}
