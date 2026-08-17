<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKategoriToKasUmum extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tb_kas_yayasan', [
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->dropForeignKey('tb_kas_yayasan', 'tb_kas_yayasan_kategori_id_foreign');
    }

    public function down()
    {
        $this->forge->dropColumn('tb_kas_yayasan', 'kategori');
    }
}
