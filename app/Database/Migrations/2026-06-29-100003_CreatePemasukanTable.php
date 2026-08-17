<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePemasukanTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'unit_id' => ['type' => 'INT', 'constraint' => 11],
            'tanggal' => ['type' => 'DATE'],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255],
            'kategori' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'jumlah' => ['type' => 'DECIMAL', 'constraint' => '15,0'],
            'jenis' => ['type' => 'VARCHAR', 'constraint' => 20],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('unit_id', 'tb_unit', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tb_kas_yayasan');
    }

    public function down()
    {
        $this->forge->dropTable('tb_kas_yayasan');
    }
}
