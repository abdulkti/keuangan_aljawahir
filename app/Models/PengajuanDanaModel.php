<?php

namespace App\Models;

use CodeIgniter\Model;

class PengajuanDanaModel extends Model
{
    protected $table            = 'tb_pengajuan_dana';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields = ['unit_id', 'user_id', 'tanggal', 'keterangan', 'jumlah', 'status', 'alasan_tolak', 'approved_by', 'referensi_tipe', 'referensi_id'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAllWithUnit($filters = [])
    {
        $builder = $this->db->table($this->table . ' p')
            ->select('p.*, u.nama as unit_nama, us.nama as user_nama, us2.nama as approved_nama')
            ->join('tb_unit u', 'u.id = p.unit_id')
            ->join('tb_users us', 'us.id = p.user_id', 'left')
            ->join('tb_users us2', 'us2.id = p.approved_by', 'left')
            ->orderBy('p.created_at', 'DESC');

        if (!empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }
        if (!empty($filters['unit_id'])) {
            $builder->where('p.unit_id', $filters['unit_id']);
        }

        return $builder->get()->getResultArray();
    }

    public function getByUnit($unitId)
    {
        return $this->where('unit_id', $unitId)->orderBy('created_at', 'DESC')->findAll();
    }
}
