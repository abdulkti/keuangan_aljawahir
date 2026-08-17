<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSekolahToTables extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_users ADD COLUMN sekolah VARCHAR(20) NOT NULL DEFAULT 'admin'");
        $this->db->query("ALTER TABLE tb_siswa ADD COLUMN sekolah VARCHAR(20) NOT NULL DEFAULT 'smp'");
        $this->db->query("ALTER TABLE tb_guru ADD COLUMN sekolah VARCHAR(20) NOT NULL DEFAULT 'smp'");
        $this->db->query("ALTER TABLE tb_kelas ADD COLUMN sekolah VARCHAR(20) NOT NULL DEFAULT 'smp'");
        $this->db->query("ALTER TABLE tb_tabungan ADD COLUMN sekolah VARCHAR(20) NOT NULL DEFAULT 'smp'");
        $this->db->query("ALTER TABLE tb_tagihan_siswa ADD COLUMN sekolah VARCHAR(20) NOT NULL DEFAULT 'smp'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE tb_users DROP COLUMN sekolah");
        $this->db->query("ALTER TABLE tb_siswa DROP COLUMN sekolah");
        $this->db->query("ALTER TABLE tb_guru DROP COLUMN sekolah");
        $this->db->query("ALTER TABLE tb_kelas DROP COLUMN sekolah");
        $this->db->query("ALTER TABLE tb_tabungan DROP COLUMN sekolah");
        $this->db->query("ALTER TABLE tb_tagihan_siswa DROP COLUMN sekolah");
    }
}
