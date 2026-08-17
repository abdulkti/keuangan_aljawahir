<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePengeluaranTable extends Migration
{
    public function up()
    {
        // No-op: tb_kas_yayasan is already created by the pemasukan migration (100003)
        // with a 'jenis' ENUM column to distinguish pemasukan vs pengeluaran
    }

    public function down()
    {
        // No-op: see up()
    }
}
