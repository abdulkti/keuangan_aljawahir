<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * @deprecated This model is not used by any controller. Kept for potential future use.
 */
class KasKategoriModel extends Model
{
    protected $table = 'tb_kategori';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama', 'tipe', 'sekolah'];
    protected $useTimestamps = false;
}
