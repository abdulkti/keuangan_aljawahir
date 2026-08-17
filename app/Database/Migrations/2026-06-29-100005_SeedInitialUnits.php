<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedInitialUnits extends Migration
{
    public function up()
    {
        $this->db->table('tb_unit')->insertBatch([
            ['nama' => 'RA IT Al-Jawahir', 'alamat' => '', 'kepala_sekolah' => ''],
            ['nama' => 'SD IT Al-Jawahir', 'alamat' => '', 'kepala_sekolah' => ''],
            ['nama' => 'SMP IT Al-Jawahir', 'alamat' => '', 'kepala_sekolah' => ''],
        ]);

        $this->db->query("ALTER TABLE tb_users ALTER COLUMN role TYPE VARCHAR(30)");
        $this->db->query("ALTER TABLE tb_users ALTER COLUMN role SET DEFAULT 'staff'");
    }

    public function down()
    {
        $this->db->table('tb_unit')->truncate();
        $this->db->query("ALTER TABLE tb_users ALTER COLUMN role TYPE VARCHAR(30)");
        $this->db->query("ALTER TABLE tb_users ALTER COLUMN role SET DEFAULT 'staff'");
    }
}
