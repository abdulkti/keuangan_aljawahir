<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeachersTable extends Migration
{
    public function up()
    {
        // No-op: tb_guru is already created by CreateGuruTable migration (000001)
    }

    public function down()
    {
        // No-op: see up()
    }
}
