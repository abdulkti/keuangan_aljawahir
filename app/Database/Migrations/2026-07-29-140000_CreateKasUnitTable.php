<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKasUnitTable extends Migration
{
    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS tb_kas_unit (
                id SERIAL PRIMARY KEY,
                unit_id INT NOT NULL REFERENCES tb_unit(id) ON DELETE CASCADE,
                tanggal DATE NOT NULL,
                keterangan VARCHAR(255) NOT NULL,
                kategori VARCHAR(50) DEFAULT NULL,
                jumlah DECIMAL(15,0) NOT NULL DEFAULT 0,
                jenis VARCHAR(20) NOT NULL,
                metode VARCHAR(20) DEFAULT 'tunai',
                status_tutup VARCHAR(10) DEFAULT 'belum',
                user_id INT DEFAULT NULL REFERENCES tb_users(id) ON DELETE SET NULL,
                referensi_id INT DEFAULT NULL,
                referensi_tipe VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down()
    {
        $this->forge->dropTable('tb_kas_unit', true);
    }
}
