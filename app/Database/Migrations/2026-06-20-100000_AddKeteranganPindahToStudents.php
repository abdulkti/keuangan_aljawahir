<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKeteranganPindahToStudents extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_siswa', [
            'keterangan_pindah' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_siswa', 'keterangan_pindah');
    }
}
