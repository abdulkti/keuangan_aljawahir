<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'siswa_id' => ['type' => 'INT', 'constraint' => 11],
            'jenis_tagihan' => ['type' => 'VARCHAR', 'constraint' => 100],
            'nominal' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'tahun_ajaran_id' => ['type' => 'INT', 'constraint' => 11],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'belum_bayar'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('siswa_id', 'tb_siswa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tahun_ajaran_id', 'tb_tahun_ajaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_tagihan_siswa');
    }

    public function down()
    {
        $this->forge->dropTable('tb_tagihan_siswa');
    }
}
