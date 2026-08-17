<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateThtTransaksiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'guru_id' => ['type' => 'INT', 'constraint' => 11],
            'tipe' => ['type' => 'VARCHAR', 'constraint' => 20],
            'jumlah' => ['type' => 'DECIMAL', 'constraint' => '15,0'],
            'tanggal' => ['type' => 'DATE'],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('guru_id', 'tb_guru', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_transaksi_tht');
    }

    public function down()
    {
        $this->forge->dropTable('tb_transaksi_tht');
    }
}
