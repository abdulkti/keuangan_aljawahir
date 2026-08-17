<?php

namespace App\Controllers;

use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\UnitModel;
use App\Models\GuruModel;
use App\Models\ThtTransaksiModel;

class DashboardYayasan extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $this->autoTutup();

        $db = \Config\Database::connect();
        $pemasukanModel = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();
        $unitModel = new UnitModel();
        $guruModel = new GuruModel();
        $thtModel = new ThtTransaksiModel();

        $units = $unitModel->findAll();
        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $taId = (int)($activeTa['id'] ?? 0);

        // Saldo Tabungan per unit
        $tabPerUnit = [];
        $tabRes = $db->query(
            "SELECT COALESCE(sekolah,'admin') as unit, SUM(saldo) as total
             FROM tb_tabungan
              WHERE aktif=1
                AND (siswa_id IS NULL OR siswa_id IN (SELECT id FROM tb_siswa WHERE tahun_ajaran_id = ?))
                AND (guru_id IS NULL OR guru_id IN (SELECT id FROM tb_guru WHERE tahun_ajaran_id = ?))
             GROUP BY sekolah",
            [$taId, $taId]
        );
        foreach ($tabRes->getResultArray() as $r) {
            $tabPerUnit[$r['unit']] = (float)$r['total'];
        }

        // SPP paid per unit
        $sppPerUnit = [];
        $sppRes = $db->query(
             "SELECT b.sekolah as unit, SUM(bp.nominal_dibayar) as total
              FROM tb_pembayaran bp
              JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
             WHERE b.tahun_ajaran_id = ?
             GROUP BY b.sekolah",
            [$taId]
        );
        foreach ($sppRes->getResultArray() as $r) {
            $sppPerUnit[$r['unit']] = (float)$r['total'];
        }

        // Other income per unit (kas_unit, non-tabungan & non-pembayaran)
        $otherPerUnit = [];
        $otherRes = $db->query(
            "SELECT unit_id, COALESCE(SUM(jumlah), 0) as total
             FROM tb_kas_unit
             WHERE jenis = 'pemasukan'
               AND (referensi_tipe NOT IN ('pembayaran', 'transaksi_tabungan') OR referensi_tipe IS NULL)
             GROUP BY unit_id"
        );
        foreach ($otherRes->getResultArray() as $r) {
            $otherPerUnit[(int)$r['unit_id']] = (float)$r['total'];
        }

        // Pemasukan & Pengeluaran Yayasan
        $totalPemasukan = $pemasukanModel->getTotal();
        $totalPengeluaran = $pengeluaranModel->getTotal();

        // THT
        $totalSetoranTHT = (float)$db->query("SELECT COALESCE(SUM(jumlah),0) as total FROM tb_transaksi_tht WHERE tipe='setoran'")->getRow()->total;
        $totalPenarikanTHT = (float)$db->query("SELECT COALESCE(SUM(jumlah),0) as total FROM tb_transaksi_tht WHERE tipe='penarikan'")->getRow()->total;
        $totalSaldoTHT = $totalSetoranTHT - $totalPenarikanTHT;

        // Jumlah guru THT
        $jumlahGuruTHT = $guruModel->countAll();

        // Per-unit breakdown
        $perUnitData = [];
        $unitMap = ['ra' => 1, 'sd' => 2, 'smp' => 3];
        $unitLabels = ['ra' => 'RA', 'sd' => 'SD IT', 'smp' => 'SMP IT'];
        foreach ($units as $unit) {
            $key = strtolower(explode(' ', $unit['nama'])[0]);
            $perUnitData[] = [
                'nama' => $unit['nama'],
                'label' => $unitLabels[$key] ?? $unit['nama'],
                'tabungan' => $tabPerUnit[$key] ?? 0,
                'spp' => $sppPerUnit[$key] ?? 0,
                'lainnya' => $otherPerUnit[$unit['id']] ?? 0,
                'total' => ($tabPerUnit[$key] ?? 0) + ($sppPerUnit[$key] ?? 0) + ($otherPerUnit[$unit['id']] ?? 0),
            ];
        }

        // Transaksi Yayasan terbaru
        $recentPemasukan = $pemasukanModel->where('jenis', 'pemasukan')->orderBy('created_at', 'DESC')->findAll(5);
        $recentPengeluaran = $pengeluaranModel->where('jenis', 'pengeluaran')->orderBy('created_at', 'DESC')->findAll(5);

        // THT per guru
        $guruList = $guruModel->getWithUnit();
        $thtGuruData = [];
        foreach ($guruList as $g) {
            $thtGuruData[] = [
                'nama' => $g['nama'],
                'unit' => $g['unit_nama'],
                'saldo' => $thtModel->getSaldoGuru($g['id']),
            ];
        }

        $data = [
            'activeMenu' => 'dashboard-yayasan',
            'perUnitData' => $perUnitData,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldoYayasan' => $totalPemasukan - $totalPengeluaran,
            'totalSetoranTHT' => $totalSetoranTHT,
            'totalPenarikanTHT' => $totalPenarikanTHT,
            'totalSaldoTHT' => $totalSaldoTHT,
            'jumlahGuruTHT' => $jumlahGuruTHT,
            'recentPemasukan' => $recentPemasukan,
            'recentPengeluaran' => $recentPengeluaran,
            'thtGuruData' => $thtGuruData,
        ];

        return $this->render('superadmin/dashboard_yayasan', $data);
    }
}
