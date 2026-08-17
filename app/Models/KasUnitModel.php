<?php

namespace App\Models;

use CodeIgniter\Model;

class KasUnitModel extends Model
{
    protected $table            = 'tb_kas_unit';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields = ['unit_id', 'tanggal', 'keterangan', 'kategori', 'jumlah', 'jenis', 'metode', 'status_tutup', 'user_id', 'referensi_id', 'referensi_tipe'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByUnit($unitId)
    {
        return $this->where('unit_id', $unitId)->orderBy('tanggal', 'DESC')->findAll();
    }

    public function getByUnitBelumDitutup($unitId)
    {
        return $this->where('unit_id', $unitId)
                    ->where('status_tutup', 'belum')
                    ->orderBy('tanggal', 'DESC')
                    ->findAll();
    }

    public function getTotalByUnit($unitId, $jenis = null, $statusTutup = null)
    {
        $builder = $this->where('unit_id', $unitId);
        if ($jenis) $builder->where('jenis', $jenis);
        if ($statusTutup) $builder->where('status_tutup', $statusTutup);
        $builder->selectSum('jumlah');
        return $builder->get()->getRowArray()['jumlah'] ?? 0;
    }

    public function getSaldo($unitId)
    {
        $pemasukan = $this->getTotalByUnit($unitId, 'pemasukan');
        $pengeluaran = $this->getTotalByUnit($unitId, 'pengeluaran');
        return $pemasukan - $pengeluaran;
    }

    public function getRekapHarian($unitId, $tanggal)
    {
        return $this->where('unit_id', $unitId)
                    ->where('tanggal', $tanggal)
                    ->findAll();
    }

    public function getTanggalBelumDitutup($unitId)
    {
        $db = \Config\Database::connect();
        $result = $db->table('tb_kas_unit')
                     ->select('tanggal')
                     ->where('unit_id', $unitId)
                     ->where('status_tutup', 'belum')
                     ->groupBy('tanggal')
                     ->get()
                     ->getResultArray();
        return array_column($result, 'tanggal');
    }

    public function getAllWithUnit()
    {
        return $this->select('tb_kas_unit.*, tb_unit.nama as unit_nama')
                    ->join('tb_unit', 'tb_kas_unit.unit_id = tb_unit.id')
                    ->orderBy('tanggal', 'DESC')
                    ->findAll();
    }

    public function markAsTutup($unitId, $tanggal)
    {
        return $this->where('unit_id', $unitId)
                     ->where('tanggal', $tanggal)
                     ->set('status_tutup', 'tutup')
                     ->set('reopened_at', null)
                     ->update();
    }
}
