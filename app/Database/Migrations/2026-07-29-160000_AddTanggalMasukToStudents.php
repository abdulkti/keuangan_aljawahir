<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTanggalMasukToStudents extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_siswa ADD COLUMN IF NOT EXISTS tanggal_masuk DATE DEFAULT NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE tb_siswa DROP COLUMN IF EXISTS tanggal_masuk");
    }
}
