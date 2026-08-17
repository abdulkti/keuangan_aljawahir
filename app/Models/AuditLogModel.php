<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'tb_audit_log';
    protected $primaryKey       = 'id_log';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['id_user', 'aksi', 'tabel', 'id_data', 'keterangan', 'waktu', 'ip_address', 'created_at'];

    protected $useTimestamps = false;

    public function log($aksi, $tabel = null, $idData = null, $idUser = null, $keterangan = null)
    {
        $db = \Config\Database::connect();
        return $db->table($this->table)->insert([
            'id_user'    => $idUser,
            'aksi'       => $aksi,
            'tabel'      => $tabel,
            'id_data'    => $idData,
            'keterangan' => $keterangan,
            'waktu'      => date('Y-m-d H:i:s'),
            'ip_address' => \Config\Services::request()->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
