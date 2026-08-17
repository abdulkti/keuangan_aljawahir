<?php

namespace App\Models;

use CodeIgniter\Model;

class SavingsAccountModel extends Model
{
    protected $table = 'tb_tabungan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['no_rekening', 'tipe', 'sekolah', 'siswa_id', 'guru_id', 'nasabah_id', 'saldo', 'aktif'];
    protected $useTimestamps = true;

    public function getStudentAccounts($sekolah = null, $filters = [])
    {
        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $taId = $activeTa ? $activeTa['id'] : 0;

        $this->select('tb_tabungan.*, tb_siswa.nama, tb_siswa.nis, tb_siswa.kelas_id, tb_kelas.nama_kelas, tb_tahun_ajaran.tahun_ajaran')
            ->join('tb_siswa', 'tb_siswa.id = tb_tabungan.siswa_id', 'left')
            ->join('tb_kelas', 'tb_kelas.id = tb_siswa.kelas_id', 'left')
            ->join('tb_tahun_ajaran', 'tb_tahun_ajaran.id = tb_siswa.tahun_ajaran_id', 'left')
            ->where('tb_tabungan.tipe', 'siswa')
            ->where('tb_tabungan.aktif', 1)
            ->where('tb_siswa.tahun_ajaran_id', $taId);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('tb_tabungan.sekolah', $sekolah);
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
        return $this->orderBy("CASE WHEN tb_siswa.sekolah = 'ra' THEN 1 WHEN tb_siswa.sekolah = 'sd' THEN 2 WHEN tb_siswa.sekolah = 'smp' THEN 3 ELSE 4 END", 'ASC', false)
            ->orderBy('LENGTH(tb_siswa.nis)', 'ASC')
            ->orderBy('tb_siswa.nis', 'ASC')->findAll();
    }

    public function getTeacherAccounts($sekolah = null, $filters = [])
    {
        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $taId = $activeTa ? $activeTa['id'] : 0;

        $this->select('tb_tabungan.*, tb_guru.nama, tb_guru.nip, tb_guru.bidang, tb_tahun_ajaran.tahun_ajaran')
            ->join('tb_guru', 'tb_guru.id = tb_tabungan.guru_id', 'left')
            ->join('tb_tahun_ajaran', 'tb_tahun_ajaran.id = tb_guru.tahun_ajaran_id', 'left')
            ->where('tb_tabungan.tipe', 'guru')
            ->where('tb_tabungan.aktif', 1)
            ->where('tb_guru.tahun_ajaran_id', $taId);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('tb_tabungan.sekolah', $sekolah);
            $this->where('tb_guru.sekolah', $sekolah);
        }
        if (!empty($filters['bidang'])) {
            $this->where('tb_guru.bidang', $filters['bidang']);
        }
        if (!empty($filters['search'])) {
            $this->groupStart()
                ->like('tb_guru.nama', $filters['search'])
                ->orLike('tb_guru.nip', $filters['search'])
            ->groupEnd();
        }
        return $this->orderBy('tb_guru.nip', 'ASC')->findAll();
    }

    public function getNasabahAccounts($sekolah = null, $filters = [])
    {
        $this->select('tb_tabungan.*, tb_nasabah.nama, tb_nasabah.no_telp')
            ->join('tb_nasabah', 'tb_nasabah.id = tb_tabungan.nasabah_id', 'left')
            ->where('tb_tabungan.tipe', 'nasabah')
            ->where('tb_tabungan.aktif', 1);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('tb_tabungan.sekolah', $sekolah);
        }
        if (!empty($filters['search'])) {
            $this->groupStart()
                ->like('tb_nasabah.nama', $filters['search'])
            ->groupEnd();
        }
        return $this->orderBy('tb_nasabah.nama', 'ASC')->findAll();
    }

    public function getTotalSaldo($sekolah = null)
    {
        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $taId = $activeTa ? (int)$activeTa['id'] : 0;

        $db = \Config\Database::connect();
        $sql = "SELECT COALESCE(SUM(saldo),0) as total FROM tb_tabungan
                WHERE aktif=1
                  AND (siswa_id IS NULL OR siswa_id IN (SELECT id FROM tb_siswa WHERE tahun_ajaran_id = ?))
                  AND (guru_id IS NULL OR guru_id IN (SELECT id FROM tb_guru WHERE tahun_ajaran_id = ?))";
        $params = [$taId, $taId];
        if ($sekolah && $sekolah !== 'admin') {
            $sql .= " AND sekolah = ?";
            $params[] = $sekolah;
        }
        return (float) $db->query($sql, $params)->getRow()->total ?? 0;
    }
}
