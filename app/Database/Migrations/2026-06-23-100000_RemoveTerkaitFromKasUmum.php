<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveTerkaitFromKasUmum extends Migration
{
    public function up()
    {
        $this->forge->dropForeignKey('tb_kas_yayasan', 'tb_kas_yayasan_siswa_id_foreign');
        $this->forge->dropForeignKey('tb_kas_yayasan', 'tb_kas_yayasan_guru_id_foreign');
        $this->forge->dropColumn('tb_kas_yayasan', 'siswa_id');
        $this->forge->dropColumn('tb_kas_yayasan', 'guru_id');
        $this->db->query("ALTER TABLE tb_kas_yayasan ALTER COLUMN metode TYPE VARCHAR(20)");
        $this->db->query("ALTER TABLE tb_kas_yayasan ALTER COLUMN metode SET DEFAULT 'tunai'");
    }

    public function down()
    {
        $this->forge->addColumn('tb_kas_yayasan', [
            'siswa_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'guru_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addForeignKey('siswa_id', 'tb_siswa', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('guru_id', 'tb_guru', 'id', 'SET NULL', 'CASCADE');
        $this->db->query("ALTER TABLE tb_kas_yayasan ALTER COLUMN metode TYPE VARCHAR(20)");
        $this->db->query("ALTER TABLE tb_kas_yayasan ALTER COLUMN metode SET DEFAULT 'tunai'");
    }
}
