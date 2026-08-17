<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicYearModel extends Model
{
    protected $table = 'tb_tahun_ajaran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tahun_ajaran', 'semester', 'aktif'];
    protected $useTimestamps = true;
}
