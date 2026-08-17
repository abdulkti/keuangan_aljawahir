<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $exists = $this->db->table('tb_users')->where('role', 'superadmin')->get()->getRow();
        if (!$exists) {
            $this->db->table('tb_users')->insert([
                'nama' => 'Super Admin',
                'email' => 'superadmin@aljawahir.sch.id',
                'password' => password_hash('superadmin123', PASSWORD_BCRYPT),
                'role' => 'superadmin',
                'aktif' => 1,
            ]);
            echo "Super admin user created: superadmin@aljawahir.sch.id / superadmin123\n";
        } else {
            echo "Super admin already exists (id={$exists->id})\n";
        }
    }
}
