<?php

namespace App\Models;

use CodeIgniter\Model;

class GuruModel extends Model
{
    protected $table            = 'tb_guru';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['nip', 'nama', 'unit_id', 'saldo_awal', 'bidang', 'jenis_kelamin', 'sekolah', 'alamat', 'no_telp', 'aktif', 'tahun_ajaran_id'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getWithUnit()
    {
        $unitLabels = [
            'ra'  => 'RA IT Al-Jawahir',
            'sd'  => 'SD IT Al-Jawahir',
            'smp' => 'SMP IT Al-Jawahir',
        ];

        $rows = $this->select('tb_guru.*, tb_unit.nama as unit_nama')
                    ->join('tb_unit', 'tb_guru.unit_id = tb_unit.id', 'left')
                    ->orderBy('tb_guru.nama', 'ASC')
                    ->findAll();

        foreach ($rows as &$r) {
            if (empty($r['unit_nama'])) {
                $r['unit_nama'] = $unitLabels[$r['sekolah'] ?? ''] ?? 'Yayasan';
            }
        }

        return $rows;
    }

    public function getByActiveTa($sekolah = null, $filters = [])
    {
        if (!empty($filters['ta_id'])) {
            $taId = (int)$filters['ta_id'];
        } else {
            $taModel = new \App\Models\AcademicYearModel();
            $activeTa = $taModel->where('aktif', 1)->first();
            $taId = $activeTa ? $activeTa['id'] : 0;
        }

        $this->select('tb_guru.*, tb_tahun_ajaran.tahun_ajaran')
            ->join('tb_tahun_ajaran', 'tb_tahun_ajaran.id = tb_guru.tahun_ajaran_id', 'left')
            ->where('tb_guru.tahun_ajaran_id', $taId);
        if ($sekolah && $sekolah !== 'admin') {
            $this->where('tb_guru.sekolah', $sekolah);
        }
        if (!empty($filters['bidang'])) {
            $this->where('tb_guru.bidang', $filters['bidang']);
        }
        $this->orderBy('tb_guru.nip', 'ASC');
        return $this->findAll();
    }
}
