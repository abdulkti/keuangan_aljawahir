<?php

namespace App\Models;

use CodeIgniter\Model;

class BillModel extends Model
{
    protected $table = 'tb_tagihan_siswa';
    protected $primaryKey = 'id';
    protected $allowedFields = ['siswa_id', 'jenis_tagihan', 'sekolah', 'nominal', 'tahun_ajaran_id', 'status', 'jatuh_tempo', 'created_at'];
    protected $useTimestamps = true;

    public function getWithStudent($sekolah = null, $filters = [], $limit = 0, $offset = 0)
    {
        $this->select('tb_tagihan_siswa.*, tb_siswa.nama, tb_siswa.nis, tb_kelas.nama_kelas, tb_tahun_ajaran.tahun_ajaran, tb_siswa.status as siswa_status')
            ->join('tb_siswa', 'tb_siswa.id = tb_tagihan_siswa.siswa_id')
            ->join('tb_kelas', 'tb_kelas.id = tb_siswa.kelas_id')
            ->join('tb_tahun_ajaran', 'tb_tahun_ajaran.id = tb_tagihan_siswa.tahun_ajaran_id')
            ->where('tb_tahun_ajaran.aktif', 1);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        if (!empty($filters['kelas_id'])) {
            $this->where('tb_siswa.kelas_id', $filters['kelas_id']);
        }
        if (!empty($filters['status'])) {
            $this->where('tb_tagihan_siswa.status', $filters['status']);
        }
        if (!empty($filters['jenis'])) {
            $this->where('tb_tagihan_siswa.jenis_tagihan', $filters['jenis']);
        }
        if (!empty($filters['search'])) {
            $this->like('tb_siswa.nama', $filters['search'], 'both', null, true);
        }
        if (!empty($filters['bulan'])) {
            $this->where('EXTRACT(MONTH FROM tb_tagihan_siswa.created_at)::int =', (int)$filters['bulan'], false);
        }
        if ($limit > 0) {
            $this->limit($limit, $offset);
        }
        return $this->orderBy('tb_tagihan_siswa.created_at', 'DESC')->findAll();
    }

    public function countFiltered($sekolah = null, $filters = [])
    {
        $this->join('tb_siswa', 'tb_siswa.id = tb_tagihan_siswa.siswa_id')
            ->join('tb_kelas', 'tb_kelas.id = tb_siswa.kelas_id')
            ->join('tb_tahun_ajaran', 'tb_tahun_ajaran.id = tb_tagihan_siswa.tahun_ajaran_id')
            ->where('tb_tahun_ajaran.aktif', 1);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        if (!empty($filters['kelas_id'])) {
            $this->where('tb_siswa.kelas_id', $filters['kelas_id']);
        }
        if (!empty($filters['status'])) {
            $this->where('tb_tagihan_siswa.status', $filters['status']);
        }
        if (!empty($filters['jenis'])) {
            $this->where('tb_tagihan_siswa.jenis_tagihan', $filters['jenis']);
        }
        if (!empty($filters['search'])) {
            $this->like('tb_siswa.nama', $filters['search'], 'both', null, true);
        }
        if (!empty($filters['bulan'])) {
            $this->where('EXTRACT(MONTH FROM tb_tagihan_siswa.created_at)::int =', (int)$filters['bulan'], false);
        }
        return $this->countAllResults();
    }

    public function getTotalCount($sekolah = null, $filters = [])
    {
        $db = \Config\Database::connect();

        $where = ["ta.aktif = 1"];
        $params = [];
        if ($sekolah && $sekolah !== 'admin') {
            $where[] = "b.sekolah = ?";
            $params[] = $sekolah;
        }
        if (!empty($filters['kelas_id'])) {
            $where[] = "s.kelas_id = ?";
            $params[] = (int)$filters['kelas_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "b.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['jenis'])) {
            $where[] = "b.jenis_tagihan = ?";
            $params[] = $filters['jenis'];
        }
        if (!empty($filters['search'])) {
            $where[] = "LOWER(s.nama) LIKE ?";
            $params[] = '%' . strtolower($filters['search']) . '%';
        }
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $where[] = "EXTRACT(MONTH FROM b.created_at)::int = ? AND EXTRACT(YEAR FROM b.created_at)::int = ?";
            $params[] = (int)$filters['bulan'];
            $params[] = (int)$filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            $where[] = "EXTRACT(MONTH FROM b.created_at)::int = ?";
            $params[] = (int)$filters['bulan'];
        }

        $whereStr = implode(' AND ', $where);
        $result = $db->query("
            SELECT COUNT(*) AS total FROM tb_tagihan_siswa b
            JOIN tb_siswa s ON s.id = b.siswa_id
            JOIN tb_tahun_ajaran ta ON ta.id = b.tahun_ajaran_id
            WHERE $whereStr
        ", $params)->getRowArray();

        return $result ? (int)$result['total'] : 0;
    }

    public function getSummary($sekolah = null, $filters = [])
    {
        $db = \Config\Database::connect();

        $where = ["ta.aktif = 1"];
        $params = [];
        if ($sekolah && $sekolah !== 'admin') {
            $where[] = "b.sekolah = ?";
            $params[] = $sekolah;
        }
        if (!empty($filters['kelas_id'])) {
            $where[] = "s.kelas_id = ?";
            $params[] = (int)$filters['kelas_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "b.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['jenis'])) {
            $where[] = "b.jenis_tagihan = ?";
            $params[] = $filters['jenis'];
        }
        if (!empty($filters['search'])) {
            $where[] = "s.nama ILIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['bulan']) && !empty($filters['tahun'])) {
            $where[] = "EXTRACT(MONTH FROM b.created_at)::int = ? AND EXTRACT(YEAR FROM b.created_at)::int = ?";
            $params[] = (int)$filters['bulan'];
            $params[] = (int)$filters['tahun'];
        } elseif (!empty($filters['bulan'])) {
            $where[] = "EXTRACT(MONTH FROM b.created_at)::int = ?";
            $params[] = (int)$filters['bulan'];
        }

        $whereStr = implode(' AND ', $where);

        $r = $db->query("
            SELECT
                COUNT(*) FILTER (WHERE b.status = 'lunas') AS lunas_count,
                COUNT(*) FILTER (WHERE b.status = 'cicil') AS cicil_count,
                COUNT(*) FILTER (WHERE b.status = 'belum_bayar') AS belum_bayar_count,
                COALESCE(SUM(b.nominal) FILTER (WHERE b.status = 'lunas'), 0) AS total_tertagih,
                COALESCE(SUM(b.nominal) FILTER (WHERE b.status IN ('belum_bayar','cicil')), 0) AS total_tertunggak,
                COUNT(DISTINCT b.siswa_id) AS siswa_count
            FROM tb_tagihan_siswa b
            JOIN tb_siswa s ON s.id = b.siswa_id
            JOIN tb_tahun_ajaran ta ON ta.id = b.tahun_ajaran_id
            WHERE $whereStr
        ", $params)->getRowArray();

        return $r ?: [
            'lunas_count' => 0, 'cicil_count' => 0, 'belum_bayar_count' => 0,
            'total_tertagih' => 0, 'total_tertunggak' => 0, 'siswa_count' => 0,
        ];
    }

    private function _applyFilters($filters)
    {
        $joined = false;
        if (!empty($filters['kelas_id']) || !empty($filters['search'])) {
            $this->join('tb_siswa', 'tb_siswa.id = tb_tagihan_siswa.siswa_id');
            $joined = true;
            if (!empty($filters['kelas_id'])) {
                $this->where('tb_siswa.kelas_id', $filters['kelas_id']);
            }
            if (!empty($filters['search'])) {
                $this->like('tb_siswa.nama', $filters['search'], 'both', null, true);
            }
        }
        if (!empty($filters['jenis'])) {
            $this->where('jenis_tagihan', $filters['jenis']);
        }
        if (!empty($filters['bulan'])) {
            $this->where('EXTRACT(MONTH FROM created_at)::int =', (int)$filters['bulan'], false);
        }
    }

    public function getLunasCount($sekolah = null, $taId = null, $filters = [])
    {
        $this->where('status', 'lunas');
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('sekolah', $sekolah);
        }
        if ($taId) {
            $this->where('tahun_ajaran_id', $taId);
        }
        $this->_applyFilters($filters);
        return $this->countAllResults();
    }

    public function getCicilCount($sekolah = null, $taId = null, $filters = [])
    {
        $this->where('status', 'cicil');
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('sekolah', $sekolah);
        }
        if ($taId) {
            $this->where('tahun_ajaran_id', $taId);
        }
        $this->_applyFilters($filters);
        return $this->countAllResults();
    }

    public function getBelumBayarCount($sekolah = null, $taId = null, $filters = [])
    {
        $this->where('status', 'belum_bayar');
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('sekolah', $sekolah);
        }
        if ($taId) {
            $this->where('tahun_ajaran_id', $taId);
        }
        $this->_applyFilters($filters);
        return $this->countAllResults();
    }

    public function getTotalTertagih($sekolah = null, $taId = null, $filters = [])
    {
        $this->selectSum('nominal')->where('status', 'lunas');
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('sekolah', $sekolah);
        }
        if ($taId) {
            $this->where('tahun_ajaran_id', $taId);
        }
        $this->_applyFilters($filters);
        return $this->get()->getRow()->nominal ?? 0;
    }

    public function getTotalTertunggak($sekolah = null, $taId = null, $filters = [])
    {
        $this->selectSum('nominal')->whereIn('status', ['belum_bayar', 'cicil']);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('sekolah', $sekolah);
        }
        if ($taId) {
            $this->where('tahun_ajaran_id', $taId);
        }
        $this->_applyFilters($filters);
        return $this->get()->getRow()->nominal ?? 0;
    }
}
