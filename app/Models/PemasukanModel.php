<?php

namespace App\Models;

use CodeIgniter\Model;

class PemasukanModel extends Model
{
    protected $table            = 'tb_kas_yayasan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['unit_id', 'tanggal', 'keterangan', 'kategori', 'metode', 'jumlah', 'jenis'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getWithUnit()
    {
        return $this->select('tb_kas_yayasan.*, tb_unit.nama as unit_nama')
                    ->join('tb_unit', 'tb_kas_yayasan.unit_id = tb_unit.id')
                    ->where('tb_kas_yayasan.jenis', 'pemasukan')
                    ->orderBy('tanggal', 'DESC')
                    ->findAll();
    }

    public function getTotal($tahunAjaran = null, $bulan = null)
    {
        $this->selectSum('jumlah');
        $this->where('jenis', 'pemasukan');
        $this->_filterByTahunAjaran($this, $tahunAjaran, $bulan);
        return $this->get()->getRowArray()['jumlah'] ?? 0;
    }

    public function getRekapPerUnit($tahunAjaran = null, $bulan = null)
    {
        $this->select('unit_id, tb_unit.nama as unit_nama, SUM(jumlah) as total')
            ->join('tb_unit', 'tb_kas_yayasan.unit_id = tb_unit.id')
            ->where('tb_kas_yayasan.jenis', 'pemasukan')
            ->groupBy('unit_id, tb_unit.nama');
        $this->_filterByTahunAjaran($this, $tahunAjaran, $bulan);
        return $this->findAll();
    }

    public function getRekapPerKategori($tahunAjaran = null, $bulan = null)
    {
        $this->select('kategori, metode, SUM(jumlah) as total')
            ->where('jenis', 'pemasukan')
            ->groupBy('kategori, metode');
        $this->_filterByTahunAjaran($this, $tahunAjaran, $bulan);
        return $this->findAll();
    }

    private function _filterByTahunAjaran($model, $tahunAjaran, $bulan)
    {
        if ($tahunAjaran) {
            $taParts = explode('-', $tahunAjaran);
            if (count($taParts) === 2) {
                $taStart = (int)$taParts[0];
                $taEnd = (int)$taParts[1];
                if ($bulan) {
                    $bulan = (int)$bulan;
                    $year = $bulan >= 7 ? $taStart : $taEnd;
                    $model->where('EXTRACT(YEAR FROM tanggal)::int =', $year, false);
                    $model->where('EXTRACT(MONTH FROM tanggal)::int =', $bulan, false);
                } else {
                    $model->where("(EXTRACT(MONTH FROM tanggal)::int >= 7 AND EXTRACT(YEAR FROM tanggal)::int = {$taStart}) OR (EXTRACT(MONTH FROM tanggal)::int <= 6 AND EXTRACT(YEAR FROM tanggal)::int = {$taEnd})", null, false);
                }
            }
        } elseif ($bulan) {
            $model->where('EXTRACT(MONTH FROM tanggal)::int =', $bulan, false);
        }
    }
}
