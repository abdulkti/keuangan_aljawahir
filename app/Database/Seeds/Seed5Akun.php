<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Seed5Akun extends Seeder
{
    public function run()
    {
        $this->db->query('TRUNCATE TABLE tb_users CASCADE');

        $akun = [
            ['nama' => 'Super Admin',   'email' => 'superadmin@aljawahir.sch.id', 'password' => password_hash('superadmin123', PASSWORD_BCRYPT), 'role' => 'superadmin',  'sekolah' => 'admin', 'aktif' => 1],
            ['nama' => 'Admin RA',      'email' => 'ra@aljawahir.sch.id',         'password' => password_hash('ra123', PASSWORD_BCRYPT),         'role' => 'staff',      'sekolah' => 'ra',    'aktif' => 1],
            ['nama' => 'Admin SD',      'email' => 'sd@aljawahir.sch.id',         'password' => password_hash('sd123', PASSWORD_BCRYPT),         'role' => 'staff',      'sekolah' => 'sd',    'aktif' => 1],
            ['nama' => 'Admin SMP',     'email' => 'smp@aljawahir.sch.id',        'password' => password_hash('smp123', PASSWORD_BCRYPT),        'role' => 'staff',      'sekolah' => 'smp',   'aktif' => 1],
            ['nama' => 'Yayasan',       'email' => 'yayasan@aljawahir.sch.id',    'password' => password_hash('yayasan123', PASSWORD_BCRYPT),    'role' => 'admin',      'sekolah' => 'admin', 'aktif' => 1],
        ];

        $this->db->table('tb_users')->insertBatch($akun);

        echo "5 akun berhasil dibuat:\n";
        foreach ($akun as $a) {
            echo "- {$a['email']} (role: {$a['role']}, sekolah: {$a['sekolah']})\n";
        }
    }
}
