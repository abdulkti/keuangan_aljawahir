<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'tb_users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama', 'email', 'password', 'role', 'sekolah', 'foto', 'aktif', 'last_login', 'session_token'];
    protected $useTimestamps = true;
    protected $useSoftDeletes = false;

    protected $validationRules = [
        'nama' => 'required|min_length[3]|max_length[100]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password' => 'required|min_length[6]',
    ];
}
