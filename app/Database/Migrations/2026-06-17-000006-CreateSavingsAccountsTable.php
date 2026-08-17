<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSavingsAccountsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'no_rekening' => ['type' => 'VARCHAR', 'constraint' => 30, 'unique' => true],
            'tipe' => ['type' => 'VARCHAR', 'constraint' => 20],
            'siswa_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'guru_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'saldo' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'aktif' => ['type' => 'SMALLINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('siswa_id', 'tb_siswa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('guru_id', 'tb_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_tabungan');
    }

    public function down()
    {
        $this->forge->dropTable('tb_tabungan');
    }
}
