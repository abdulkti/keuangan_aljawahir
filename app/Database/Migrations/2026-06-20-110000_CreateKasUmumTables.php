<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKasUmumTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'tipe' => ['type' => 'VARCHAR', 'constraint' => 20],
            'sekolah' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tb_kategori');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
            'tipe' => ['type' => 'VARCHAR', 'constraint' => 20],
            'kategori_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'nominal' => ['type' => 'DECIMAL', 'constraint' => '15,0', 'default' => 0],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
            'siswa_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'guru_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'metode' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'tunai'],
            'sekolah' => ['type' => 'VARCHAR', 'constraint' => 50],
            'user_id' => ['type' => 'INT', 'constraint' => 11],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('kategori_id', 'tb_kategori', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('siswa_id', 'tb_siswa', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('guru_id', 'tb_guru', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'tb_users', 'id');
        $this->forge->createTable('tb_kas_yayasan');
    }

    public function down()
    {
        $this->forge->dropTable('tb_kas_yayasan');
        $this->forge->dropTable('tb_kategori');
    }
}
