<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSekolahToNasabah extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_nasabah', [
            'sekolah' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_nasabah', 'sekolah');
    }
}
