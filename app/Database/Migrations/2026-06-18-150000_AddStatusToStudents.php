<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToStudents extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_siswa', [
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'aktif',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_siswa', 'status');
    }
}
