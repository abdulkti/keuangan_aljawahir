<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterSavingsAccountsAddNasabah extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $db->query("ALTER TABLE tb_tabungan ALTER COLUMN tipe TYPE VARCHAR(20)");

        $db->query("ALTER TABLE tb_tabungan ADD COLUMN nasabah_id INTEGER NULL");

        $db->query("ALTER TABLE tb_tabungan ADD CONSTRAINT tb_tabungan_nasabah_id_foreign FOREIGN KEY (nasabah_id) REFERENCES tb_nasabah(id) ON DELETE CASCADE");
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $db->query("ALTER TABLE tb_tabungan DROP CONSTRAINT tb_tabungan_nasabah_id_foreign");

        $db->query("ALTER TABLE tb_tabungan DROP COLUMN nasabah_id");

        $db->query("ALTER TABLE tb_tabungan ALTER COLUMN tipe TYPE VARCHAR(20)");
    }
}
