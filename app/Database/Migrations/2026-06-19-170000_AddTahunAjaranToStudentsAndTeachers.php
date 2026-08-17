<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTahunAjaranToStudentsAndTeachers extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_siswa ADD COLUMN tahun_ajaran_id INTEGER NULL");
        $this->db->query("ALTER TABLE tb_guru ADD COLUMN tahun_ajaran_id INTEGER NULL");

        $active = $this->db->table('tb_tahun_ajaran')->where('aktif', 1)->get()->getRowArray();
        $taId = $active ? $active['id'] : 1;
        $this->db->query("UPDATE tb_siswa SET tahun_ajaran_id = {$taId} WHERE tahun_ajaran_id IS NULL");
        $this->db->query("UPDATE tb_guru SET tahun_ajaran_id = {$taId} WHERE tahun_ajaran_id IS NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE tb_siswa DROP COLUMN tahun_ajaran_id");
        $this->db->query("ALTER TABLE tb_guru DROP COLUMN tahun_ajaran_id");
    }
}
