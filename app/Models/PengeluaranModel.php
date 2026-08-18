<?php

namespace App\Models;

use CodeIgniter\Model;

class PengeluaranModel extends Model
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
                    ->where('tb_kas_yayasan.jenis', 'pengeluaran')
                    ->orderBy('tanggal', 'DESC')
                    ->findAll();
    }

    public function getTotal($tahunAjaran = null, $bulan = null)
    {
        $this->selectSum('jumlah');
        $this->where('jenis', 'pengeluaran');
        $this->_filterByTahunAjaran($this, $tahunAjaran, $bulan);
        return $this->get()->getRowArray()['jumlah'] ?? 0;
    }

    public function getRekapPerKategori($tahunAjaran = null, $bulan = null)
    {
        $this->select('kategori, metode, SUM(jumlah) as total')
            ->groupBy('kategori, metode');
        $this->where('jenis', 'pengeluaran');
        $this->_filterByTahunAjaran($this, $tahunAjaran, $bulan);
        return $this->findAll();
    }

    private function _filterByTahunAjaran(&$model, $tahunAjaran, $bulan)
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
                    $model->groupStart()
                        ->where('EXTRACT(MONTH FROM tanggal)::int >=', 7)
                        ->where('EXTRACT(YEAR FROM tanggal)::int =', $taStart)
                        ->groupEnd()
                        ->orGroupStart()
                        ->where('EXTRACT(MONTH FROM tanggal)::int <=', 6)
                        ->where('EXTRACT(YEAR FROM tanggal)::int =', $taEnd)
                        ->orGroupEnd();
                }
            }
        } elseif ($bulan) {
            $model->where('EXTRACT(MONTH FROM tanggal)::int =', $bulan, false);
        }
    }
}
