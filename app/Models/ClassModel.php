<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassModel extends Model
{
    protected $table = 'tb_kelas';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama_kelas', 'tingkat', 'jurusan', 'sekolah', 'nominal_spp', 'nominal_awal_tahun'];
    protected $useTimestamps = true;

    public function getFiltered($sekolah = null)
    {
        $this->orderBy("CASE WHEN sekolah = 'ra' THEN 1 WHEN sekolah = 'sd' THEN 2 WHEN sekolah = 'smp' THEN 3 ELSE 4 END", '', false)
             ->orderBy('tingkat', 'ASC')
             ->orderBy('nama_kelas', 'ASC');
        if ($sekolah && $sekolah !== 'admin') {
            return $this->where('sekolah', $sekolah)->findAll();
        }
        return $this->findAll();
    }
}
