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

    public function getTotal($tahun = null, $bulan = null)
    {
        $this->selectSum('jumlah');
        $this->where('jenis', 'pemasukan');
        if ($tahun) $this->where('EXTRACT(YEAR FROM tanggal)::int =', $tahun, false);
        if ($bulan) $this->where('EXTRACT(MONTH FROM tanggal)::int =', $bulan, false);
        return $this->get()->getRowArray()['jumlah'] ?? 0;
    }

    public function getRekapPerUnit($tahun = null, $bulan = null)
    {
        $this->select('unit_id, tb_unit.nama as unit_nama, SUM(jumlah) as total')
            ->join('tb_unit', 'tb_kas_yayasan.unit_id = tb_unit.id')
            ->where('tb_kas_yayasan.jenis', 'pemasukan')
            ->groupBy('unit_id, tb_unit.nama');
        if ($tahun) $this->where('EXTRACT(YEAR FROM tb_kas_yayasan.tanggal)::int =', $tahun, false);
        if ($bulan) $this->where('EXTRACT(MONTH FROM tb_kas_yayasan.tanggal)::int =', $bulan, false);
        return $this->findAll();
    }

    public function getRekapPerKategori($tahun = null, $bulan = null)
    {
        $this->select('kategori, metode, SUM(jumlah) as total')
            ->where('jenis', 'pemasukan')
            ->groupBy('kategori, metode');
        if ($tahun) $this->where('EXTRACT(YEAR FROM tanggal)::int =', $tahun, false);
        if ($bulan) $this->where('EXTRACT(MONTH FROM tanggal)::int =', $bulan, false);
        return $this->findAll();
    }
}
