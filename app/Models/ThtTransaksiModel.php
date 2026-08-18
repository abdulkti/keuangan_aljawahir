<?php

namespace App\Models;

use CodeIgniter\Model;

class ThtTransaksiModel extends Model
{
    protected $table            = 'tb_transaksi_tht';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['guru_id', 'tipe', 'jumlah', 'tanggal', 'keterangan'];

    protected $createdField  = 'created_at';

    public function getSaldoGuru($guru_id)
    {
        $guruModel = new \App\Models\GuruModel();
        $guru = $guruModel->select('saldo_awal')->where('id', $guru_id)->first();
        $saldoAwal = (float)($guru['saldo_awal'] ?? 0);

        $result = $this->select("
            COALESCE(SUM(CASE WHEN tipe = 'setoran' THEN jumlah ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN tipe = 'penarikan' THEN jumlah ELSE 0 END), 0) as saldo
        ")
        ->where('guru_id', $guru_id)
        ->first();

        return $saldoAwal + (float)($result['saldo'] ?? 0);
    }

    public function getWithGuru()
    {
        return $this->select("
            tb_transaksi_tht.*, tb_guru.nama as guru_nama, tb_guru.nip as guru_nip,
            COALESCE(tb_unit.nama, CASE tb_guru.sekolah
                WHEN 'ra'  THEN 'RA IT Al-Jawahir'
                WHEN 'sd'  THEN 'SD IT Al-Jawahir'
                WHEN 'smp' THEN 'SMP IT Al-Jawahir'
                ELSE 'Yayasan'
            END) as unit_nama
        ")
            ->join('tb_guru', 'tb_transaksi_tht.guru_id = tb_guru.id')
            ->join('tb_unit', 'tb_guru.unit_id = tb_unit.id', 'left')
            ->orderBy('tb_transaksi_tht.created_at', 'DESC')
            ->findAll();
    }

    public function getRekapPerTahun($tahunAjaran = null, $bulan = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tb_transaksi_tht');
        $builder->select("
            CASE
                WHEN EXTRACT(MONTH FROM tanggal)::int >= 7 THEN EXTRACT(YEAR FROM tanggal)::int || '-' || (EXTRACT(YEAR FROM tanggal)::int + 1)
                ELSE (EXTRACT(YEAR FROM tanggal)::int - 1) || '-' || EXTRACT(YEAR FROM tanggal)::int
            END as tahun,
            SUM(CASE WHEN tipe = 'setoran' THEN jumlah ELSE 0 END) as total_setoran,
            SUM(CASE WHEN tipe = 'penarikan' THEN jumlah ELSE 0 END) as total_penarikan
        ");
        $builder->groupBy("CASE
            WHEN EXTRACT(MONTH FROM tanggal)::int >= 7 THEN EXTRACT(YEAR FROM tanggal)::int || '-' || (EXTRACT(YEAR FROM tanggal)::int + 1)
            ELSE (EXTRACT(YEAR FROM tanggal)::int - 1) || '-' || EXTRACT(YEAR FROM tanggal)::int
        END");
        $builder->orderBy("MIN(tanggal)", "ASC");

        if ($tahunAjaran) {
            $taParts = explode('-', $tahunAjaran);
            if (count($taParts) === 2) {
                $taStart = (int)$taParts[0];
                $taEnd = (int)$taParts[1];
                $builder->where("(EXTRACT(MONTH FROM tanggal)::int >= 7 AND EXTRACT(YEAR FROM tanggal)::int = {$taStart}) OR (EXTRACT(MONTH FROM tanggal)::int <= 6 AND EXTRACT(YEAR FROM tanggal)::int = {$taEnd})", null, false);
            }
        }

        if ($bulan) {
            $builder->where('EXTRACT(MONTH FROM tanggal)::int =', (int)$bulan);
        }

        return $builder->get()->getResultArray();
    }

    public function getRekapPerGuru($tahunAjaran = null, $bulan = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tb_transaksi_tht');
        $builder->select("
            tb_transaksi_tht.guru_id, tb_guru.nama as guru_nama, tb_guru.nip as guru_nip,
            SUM(CASE WHEN tipe = 'setoran' THEN jumlah ELSE 0 END) as total_setoran,
            SUM(CASE WHEN tipe = 'penarikan' THEN jumlah ELSE 0 END) as total_penarikan
        ");
        $builder->join('tb_guru', 'tb_transaksi_tht.guru_id = tb_guru.id');
        $builder->groupBy('tb_transaksi_tht.guru_id, tb_guru.nama, tb_guru.nip');

        if ($tahunAjaran) {
            $taParts = explode('-', $tahunAjaran);
            if (count($taParts) === 2) {
                $taStart = (int)$taParts[0];
                $taEnd = (int)$taParts[1];
                $builder->where("(EXTRACT(MONTH FROM tanggal)::int >= 7 AND EXTRACT(YEAR FROM tanggal)::int = {$taStart}) OR (EXTRACT(MONTH FROM tanggal)::int <= 6 AND EXTRACT(YEAR FROM tanggal)::int = {$taEnd})", null, false);
            }
        }

        if ($bulan) {
            $builder->where('EXTRACT(MONTH FROM tanggal)::int =', (int)$bulan);
        }

        return $builder->get()->getResultArray();
    }
}
