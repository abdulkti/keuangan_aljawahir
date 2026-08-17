<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNominalColumnsToStudents extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_siswa ADD COLUMN nominal_spp DECIMAL(15,2) DEFAULT NULL");
        $this->db->query("ALTER TABLE tb_siswa ADD COLUMN nominal_awal_tahun DECIMAL(15,2) DEFAULT NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE tb_siswa DROP COLUMN nominal_awal_tahun");
        $this->db->query("ALTER TABLE tb_siswa DROP COLUMN nominal_spp");
    }
}
