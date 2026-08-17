<?php

namespace App\Models;

use CodeIgniter\Model;

class SavingsTransactionModel extends Model
{
    protected $table = 'tb_transaksi_tabungan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['akun_id', 'tipe', 'nominal', 'saldo_sebelum', 'saldo_sesudah', 'catatan', 'metode', 'user_id', 'created_at'];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = '';


}
