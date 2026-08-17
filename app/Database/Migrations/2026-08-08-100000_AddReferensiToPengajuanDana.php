<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReferensiToPengajuanDana extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_pengajuan_dana ADD COLUMN IF NOT EXISTS referensi_tipe VARCHAR(50) DEFAULT NULL");
        $this->db->query("ALTER TABLE tb_pengajuan_dana ADD COLUMN IF NOT EXISTS referensi_id INT DEFAULT NULL");
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_pengajuan_referensi ON tb_pengajuan_dana (referensi_id, referensi_tipe)");
    }

    public function down()
    {
        $this->db->query("DROP INDEX IF EXISTS idx_pengajuan_referensi");
        $this->db->query("ALTER TABLE tb_pengajuan_dana DROP COLUMN IF EXISTS referensi_tipe");
        $this->db->query("ALTER TABLE tb_pengajuan_dana DROP COLUMN IF EXISTS referensi_id");
    }
}
