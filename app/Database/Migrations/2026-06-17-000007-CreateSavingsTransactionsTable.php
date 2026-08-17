<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSavingsTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'akun_id' => ['type' => 'INT', 'constraint' => 11],
            'tipe' => ['type' => 'VARCHAR', 'constraint' => 10],
            'nominal' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'saldo_sebelum' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'saldo_sesudah' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'catatan' => ['type' => 'TEXT', 'null' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('akun_id', 'tb_tabungan', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'tb_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_transaksi_tabungan');
    }

    public function down()
    {
        $this->forge->dropTable('tb_transaksi_tabungan');
    }
}
