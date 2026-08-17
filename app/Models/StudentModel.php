<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table = 'tb_siswa';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nis', 'nisn', 'nama', 'nominal_spp', 'nominal_awal_tahun', 'jenis_kelamin', 'kelas_id', 'sekolah', 'alamat', 'no_telp', 'aktif', 'status', 'keterangan_pindah', 'tahun_ajaran_id', 'tanggal_masuk', 'tanggal_keluar'];
    protected $useTimestamps = true;

    public function getWithClass($sekolah = null, $filters = [])
    {
        if (!empty($filters['ta_id'])) {
            $taId = (int)$filters['ta_id'];
        } else {
            $taModel = new \App\Models\AcademicYearModel();
            $activeTa = $taModel->where('aktif', 1)->first();
            $taId = $activeTa ? $activeTa['id'] : 0;
        }

        $this->select('tb_siswa.*, tb_kelas.nama_kelas, tb_kelas.tingkat, tb_kelas.jurusan, tb_tahun_ajaran.tahun_ajaran')
            ->join('tb_kelas', 'tb_kelas.id = tb_siswa.kelas_id')
            ->join('tb_tahun_ajaran', 'tb_tahun_ajaran.id = tb_siswa.tahun_ajaran_id', 'left')
            ->where('tb_siswa.tahun_ajaran_id', $taId);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('tb_siswa.sekolah', $sekolah);
        }
        if (!empty($filters['kelas_id'])) {
            $this->where('tb_siswa.kelas_id', $filters['kelas_id']);
        }
        if (!empty($filters['search'])) {
            $this->groupStart()
                ->like('tb_siswa.nama', $filters['search'])
                ->orLike('tb_siswa.nis', $filters['search'])
            ->groupEnd();
        }
        $this->orderBy('CASE WHEN tb_siswa.sekolah = \'ra\' THEN 1 WHEN tb_siswa.sekolah = \'sd\' THEN 2 WHEN tb_siswa.sekolah = \'smp\' THEN 3 ELSE 4 END', 'ASC', false)
             ->orderBy('LENGTH(tb_siswa.nis)', 'ASC')
             ->orderBy('tb_siswa.nis', 'ASC');
        return $this->findAll();
    }
}
