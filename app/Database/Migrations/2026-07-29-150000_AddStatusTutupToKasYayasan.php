<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusTutupToKasYayasan extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_kas_yayasan ADD COLUMN IF NOT EXISTS status_tutup VARCHAR(10) DEFAULT 'belum'");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE tb_kas_yayasan DROP COLUMN IF EXISTS status_tutup");
    }
}
