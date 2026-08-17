<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillPaymentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tagihan_id' => ['type' => 'INT', 'constraint' => 11],
            'nominal_dibayar' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'metode' => ['type' => 'VARCHAR', 'constraint' => 20],
            'no_kwitansi' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11],
            'catatan' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tagihan_id', 'tb_tagihan_siswa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'tb_users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_pembayaran');
    }

    public function down()
    {
        $this->forge->dropTable('tb_pembayaran');
    }
}
