<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixClassesForAllUnits extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE tb_kelas ALTER COLUMN tingkat TYPE VARCHAR(10)");
        $this->db->query("ALTER TABLE tb_kelas ALTER COLUMN tingkat SET NOT NULL");

        $this->db->table('tb_kelas')->insertBatch([
            ['nama_kelas' => 'RA A', 'tingkat' => 'A', 'jurusan' => null, 'sekolah' => 'ra'],
            ['nama_kelas' => 'RA B', 'tingkat' => 'B', 'jurusan' => null, 'sekolah' => 'ra'],
        ]);

        $this->db->table('tb_kelas')->insertBatch([
            ['nama_kelas' => '1-A', 'tingkat' => '1', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '1-B', 'tingkat' => '1', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '2-A', 'tingkat' => '2', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '2-B', 'tingkat' => '2', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '3-A', 'tingkat' => '3', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '3-B', 'tingkat' => '3', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '4-A', 'tingkat' => '4', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '4-B', 'tingkat' => '4', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '5-A', 'tingkat' => '5', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '5-B', 'tingkat' => '5', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '6-A', 'tingkat' => '6', 'jurusan' => null, 'sekolah' => 'sd'],
            ['nama_kelas' => '6-B', 'tingkat' => '6', 'jurusan' => null, 'sekolah' => 'sd'],
        ]);

        $this->db->table('tb_kelas')->insertBatch([
            ['nama_kelas' => '7-A', 'tingkat' => '7', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '7-B', 'tingkat' => '7', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '7-C', 'tingkat' => '7', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '8-A', 'tingkat' => '8', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '8-B', 'tingkat' => '8', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '8-C', 'tingkat' => '8', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '9-A', 'tingkat' => '9', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '9-B', 'tingkat' => '9', 'jurusan' => null, 'sekolah' => 'smp'],
            ['nama_kelas' => '9-C', 'tingkat' => '9', 'jurusan' => null, 'sekolah' => 'smp'],
        ]);

        $oldClasses = $this->db->query("SELECT id, tingkat FROM tb_kelas WHERE sekolah = 'sma'")->getResultArray();
        $tingkatMap = [];

        foreach ($oldClasses as $oc) {
            $newTingkat = $tingkatMap[$oc['tingkat']] ?? null;
            if (!$newTingkat) continue;

            $newClass = $this->db->query(
                "SELECT id FROM tb_kelas WHERE sekolah = 'smp' AND tingkat = ? ORDER BY id ASC LIMIT 1",
                [$newTingkat]
            )->getRowArray();

            if ($newClass) {
                $this->db->query(
                    "UPDATE tb_siswa SET kelas_id = ? WHERE kelas_id = ?",
                    [$newClass['id'], $oc['id']]
                );
            }
        }
    }

    public function down()
    {
        // Reverse not practical — keep forward migration only
    }
}
