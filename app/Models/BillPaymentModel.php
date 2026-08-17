<?php

namespace App\Models;

use CodeIgniter\Model;

class BillPaymentModel extends Model
{
    protected $table = 'tb_pembayaran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tagihan_id', 'nominal_dibayar', 'metode', 'no_kwitansi', 'user_id', 'catatan', 'created_at'];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
