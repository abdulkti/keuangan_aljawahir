<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKategoriToKasUnit extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_kas_unit', [
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tb_kas_unit', ['kategori']);
    }
}
