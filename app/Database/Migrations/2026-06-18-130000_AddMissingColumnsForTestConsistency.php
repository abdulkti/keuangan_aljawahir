<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMissingColumnsForTestConsistency extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_tagihan_siswa ADD COLUMN jatuh_tempo DATE DEFAULT NULL");
        $this->db->query("ALTER TABLE tb_tagihan_siswa ADD COLUMN deleted_at DATETIME DEFAULT NULL");
        $this->db->query("ALTER TABLE tb_transaksi_tabungan ADD COLUMN metode VARCHAR(20) NOT NULL DEFAULT 'tunai'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE tb_tagihan_siswa DROP COLUMN jatuh_tempo");
        $this->db->query("ALTER TABLE tb_tagihan_siswa DROP COLUMN deleted_at");
        $this->db->query("ALTER TABLE tb_transaksi_tabungan DROP COLUMN metode");
    }
}
