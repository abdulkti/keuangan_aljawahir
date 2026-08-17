<?php

namespace App\Controllers;

use App\Models\SavingsAccountModel;
use App\Models\AcademicYearModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        $sekolah = $this->userData['sekolah'] ?? 'admin';
        $savingsModel = new SavingsAccountModel();
        $taModel = new AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $tahunAjaran = $activeTa ? $activeTa['tahun_ajaran'] : '2025/2026';
        $db = \Config\Database::connect();

        // ====== Stat Cards ======
        $totalSaldoTabungan = $savingsModel->getTotalSaldo($sekolah);

        $taId = (int)($activeTa['id'] ?? 0);
        $bulanFilter = $this->request->getGet('bulan') ?: date('m');
        $tahunFilter = date('Y');

        // ====== Bulk stat cards (1 query) ======
        $escSekolah = $sekolah && $sekolah !== 'admin' ? $db->escapeString($sekolah) : '';
        $sf  = $escSekolah ? " AND sekolah='$escSekolah'" : '';
        $sfb = $escSekolah ? " AND b.sekolah='$escSekolah'" : '';
        $ym  = $tahunFilter . '-' . $bulanFilter;

        $stats = $db->query(
            "SELECT
              (SELECT COUNT(*) FROM tb_tabungan WHERE aktif=1
                AND (siswa_id IS NULL OR siswa_id IN (SELECT id FROM tb_siswa WHERE tahun_ajaran_id=?))
                AND (guru_id IS NULL OR guru_id IN (SELECT id FROM tb_guru WHERE tahun_ajaran_id=?)){$sf}
              ) as rekening_aktif,
              (SELECT COALESCE(SUM(bp.nominal_dibayar),0) FROM tb_pembayaran bp
                JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
                WHERE (b.jenis_tagihan='SPP' OR b.jenis_tagihan='SPP Bulanan')
                  AND TO_CHAR(bp.created_at,'YYYY-MM')=? AND b.tahun_ajaran_id=?{$sfb}
              ) as spp_bulan_ini,
              (SELECT COUNT(*) FROM tb_tagihan_siswa
                WHERE (jenis_tagihan='SPP' OR jenis_tagihan='SPP Bulanan')
                  AND status IN ('belum_bayar','cicil') AND tahun_ajaran_id=?
                  AND EXTRACT(MONTH FROM created_at)::int=?{$sf}
              ) as spp_belum_lunas,
              (SELECT COUNT(*) FROM tb_tagihan_siswa
                WHERE jenis_tagihan='Daftar Ulang'
                  AND status IN ('belum_bayar','cicil') AND tahun_ajaran_id=?{$sf}
              ) as du_belum_lunas,
              (SELECT COALESCE(SUM(nominal),0) FROM tb_tagihan_siswa
                WHERE jenis_tagihan='Daftar Ulang' AND tahun_ajaran_id=?{$sf}
              ) as du_target,
              (SELECT COALESCE(SUM(bp.nominal_dibayar),0) FROM tb_pembayaran bp
                JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
                WHERE b.jenis_tagihan='Daftar Ulang' AND b.tahun_ajaran_id=?{$sfb}
              ) as du_terbayar,
              (SELECT COALESCE(SUM(bp.nominal_dibayar),0) FROM tb_pembayaran bp
                JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
                WHERE b.jenis_tagihan='Daftar Ulang'
                  AND TO_CHAR(bp.created_at,'YYYY-MM')=? AND b.tahun_ajaran_id=?{$sfb}
              ) as du_bulan_ini,
              (SELECT COALESCE(SUM(st.nominal),0) FROM tb_transaksi_tabungan st" .
                ($escSekolah ? " JOIN tb_tabungan sa ON sa.id=st.akun_id AND sa.sekolah='$escSekolah'" : '') .
                " WHERE st.tipe='setor' AND TO_CHAR(st.created_at,'YYYY-MM')=?
              ) as tabungan_masuk,
              (SELECT COALESCE(SUM(nominal_spp),0) FROM tb_siswa
                WHERE status='aktif' AND tahun_ajaran_id=?{$sf}
              ) as spp_target
            ",
            [$taId, $taId, $ym, $taId, $taId, $bulanFilter, $taId, $taId,
             $taId, $ym, $taId, $ym, $taId]
        )->getRow();

        $rekeningAktif         = (int)$stats->rekening_aktif;
        $sppPaidThisMonth      = (float)$stats->spp_bulan_ini;
        $sppBelumLunas         = (int)$stats->spp_belum_lunas;
        $duBelumLunas          = (int)$stats->du_belum_lunas;
        $jumlahBelumLunas      = $sppBelumLunas + $duBelumLunas;
        $duTarget              = (float)$stats->du_target;
        $duTerbayar            = (float)$stats->du_terbayar;
        $daftarUlangBulanIni   = (float)$stats->du_bulan_ini;
        $tabunganMasukBulanIni = (float)$stats->tabungan_masuk;
        $totalSppTarget        = (float)$stats->spp_target;
        $totalPemasukanBulanIni = $sppPaidThisMonth + $daftarUlangBulanIni + $tabunganMasukBulanIni;

        $totalBayarHariIni = (float) $db->query(
            "SELECT COALESCE(SUM(nominal_dibayar),0) as total
             FROM tb_pembayaran
             WHERE created_at::date = ?",
            [date('Y-m-d')]
        )->getRow()->total ?? 0;

        // ====== Chart Data: 12 bulan berjalan ======
        $chartMonths = [];
        $now = new \DateTime();
        $start = (clone $now)->modify('-11 months');
        $since = $start->format('Y-m-d');
        for ($i = 0; $i < 12; $i++) {
            $m = (int)$start->format('m');
            $y = (int)$start->format('Y');
            $chartMonths[] = ['bulan' => $m, 'tahun' => $y, 'label' => $start->format('M')];
            $start->modify('+1 month');
        }

        $sekolahFilter = $sekolah && $sekolah !== 'admin' ? " AND b.sekolah='" . $db->escapeString($sekolah) . "'" : '';

        // Bulk fetch all tagihan payments for chart (1 query instead of 12)
        $tagihanChartRaw = $db->query(
            "SELECT EXTRACT(YEAR FROM b.created_at) as tahun, EXTRACT(MONTH FROM b.created_at) as bulan,
                    COALESCE(SUM(bp.nominal_dibayar),0) as total
             FROM tb_pembayaran bp
             JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
             WHERE (b.jenis_tagihan='SPP' OR b.jenis_tagihan='SPP Bulanan' OR b.jenis_tagihan='Daftar Ulang')
               AND b.created_at >= ?{$sekolahFilter}
             GROUP BY tahun, bulan",
            [$since]
        )->getResultArray();
        $tagihanChart = [];
        foreach ($tagihanChartRaw as $r) {
            $tagihanChart[(int)$r['bulan'] . '-' . (int)$r['tahun']] = (float)$r['total'];
        }

        // Bulk fetch all setoran for chart (1 query instead of 12)
        $akunJoin = $sekolah && $sekolah !== 'admin'
            ? " JOIN tb_tabungan sa ON sa.id = st.akun_id AND sa.sekolah='" . $db->escapeString($sekolah) . "'"
            : '';
        $setoranChartRaw = $db->query(
            "SELECT EXTRACT(YEAR FROM st.created_at) as tahun, EXTRACT(MONTH FROM st.created_at) as bulan,
                    COALESCE(SUM(st.nominal),0) as total
             FROM tb_transaksi_tabungan st{$akunJoin}
             WHERE st.tipe = 'setor' AND st.created_at >= ?
             GROUP BY tahun, bulan",
            [$since]
        )->getResultArray();
        $setoranChart = [];
        foreach ($setoranChartRaw as $r) {
            $setoranChart[(int)$r['bulan'] . '-' . (int)$r['tahun']] = (float)$r['total'];
        }

        // Bulk fetch all penarikan for chart (1 query instead of 12)
        $penarikanChartRaw = $db->query(
            "SELECT EXTRACT(YEAR FROM st.created_at) as tahun, EXTRACT(MONTH FROM st.created_at) as bulan,
                    COALESCE(SUM(st.nominal),0) as total
             FROM tb_transaksi_tabungan st{$akunJoin}
             WHERE st.tipe = 'tarik' AND st.created_at >= ?
             GROUP BY tahun, bulan",
            [$since]
        )->getResultArray();
        $penarikanChart = [];
        foreach ($penarikanChartRaw as $r) {
            $penarikanChart[(int)$r['bulan'] . '-' . (int)$r['tahun']] = (float)$r['total'];
        }

        $chartData = [];
        foreach ($chartMonths as $cm) {
            $key = $cm['bulan'] . '-' . $cm['tahun'];
            $tagihan = $tagihanChart[$key] ?? 0;
            $setoran = $setoranChart[$key] ?? 0;
            $penarikan = $penarikanChart[$key] ?? 0;
            $chartData[] = [
                'label'     => $cm['label'],
                'tagihan'   => $tagihan,
                'setoran'   => $setoran,
                'penarikan' => $penarikan,
                'total'     => $tagihan + $setoran - $penarikan,
            ];
        }

        $maxChart = max(array_column($chartData, 'total'));
        if ($maxChart <= 0) $maxChart = 1;

        // ====== Recent Transactions ======
        $recentPayments = $db->query(
            "SELECT bp.nominal_dibayar as nominal, bp.created_at,
                    b.jenis_tagihan, b.sekolah,
                    s.nama as nama_siswa, k.nama_kelas
             FROM tb_pembayaran bp
             JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
             JOIN tb_siswa s ON s.id = b.siswa_id
             LEFT JOIN tb_kelas k ON k.id = s.kelas_id" .
            ($sekolah && $sekolah !== 'admin' ? " WHERE b.sekolah='" . $db->escapeString($sekolah) . "'" : '') .
            " ORDER BY bp.created_at DESC LIMIT 5"
        )->getResultArray();

        $recentTx = $db->query(
            "SELECT st.*, sa.tipe as akun_tipe, COALESCE(s.nama, t.nama) as nama_pemilik
             FROM tb_transaksi_tabungan st
             JOIN tb_tabungan sa ON sa.id = st.akun_id
             LEFT JOIN tb_siswa s ON s.id = sa.siswa_id
             LEFT JOIN tb_guru t ON t.id = sa.guru_id" .
            ($sekolah && $sekolah !== 'admin' ? " WHERE sa.sekolah='" . $db->escapeString($sekolah) . "'" : '') .
            " ORDER BY st.created_at DESC LIMIT 5"
        )->getResultArray();
        // Merge & sort by created_at desc
        $recentTransactions = [];
        foreach ($recentPayments as $p) {
            $recentTransactions[] = [
                'tipe'       => 'pembayaran',
                'nominal'    => $p['nominal'],
                'created_at' => $p['created_at'],
                'nama'       => $p['nama_siswa'],
                'detail'     => $p['jenis_tagihan'] . ($p['nama_kelas'] ? ' — ' . $p['nama_kelas'] : ''),
                'sekolah'    => $p['sekolah'],
            ];
        }
        foreach ($recentTx as $tx) {
            $recentTransactions[] = [
                'tipe'       => $tx['tipe'],
                'nominal'    => $tx['nominal'],
                'created_at' => $tx['created_at'],
                'nama'       => $tx['nama_pemilik'],
                'detail'     => 'Tabungan ' . ($tx['akun_tipe'] === 'siswa' ? 'Siswa' : 'Guru'),
                'sekolah'    => '',
            ];
        }
        usort($recentTransactions, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        // ====== Per-unit breakdown (single CTE) ======
        $perUnitData = [];
        $grandTotalTabungan = 0;
        $grandTotalSpp = 0;
        $grandTotalAwalTahun = 0;
        $grandTotalAwalTahunTarget = 0;

        $unitFilter = ($sekolah && $sekolah !== 'admin') ? " AND sa.sekolah='" . $db->escapeString($sekolah) . "'" : '';
        $unitFilterB = ($sekolah && $sekolah !== 'admin') ? " AND b.sekolah='" . $db->escapeString($sekolah) . "'" : '';

        $perUnitRaw = $db->query(
            "WITH tab AS (
                SELECT COALESCE(sekolah,'admin') as unit, SUM(saldo) as total
                FROM tb_tabungan sa
                WHERE aktif=1
                  AND (siswa_id IS NULL OR siswa_id IN (SELECT id FROM tb_siswa WHERE tahun_ajaran_id=?))
                  AND (guru_id IS NULL OR guru_id IN (SELECT id FROM tb_guru WHERE tahun_ajaran_id=?)){$unitFilter}
                GROUP BY sekolah
            ), tag AS (
                SELECT b.sekolah as unit, b.jenis_tagihan, SUM(bp.nominal_dibayar) as total
                FROM tb_pembayaran bp
                JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
                WHERE b.tahun_ajaran_id=?{$unitFilterB}
                GROUP BY b.sekolah, b.jenis_tagihan
            ), du AS (
                SELECT sekolah as unit, COALESCE(SUM(nominal),0) as total
                FROM tb_tagihan_siswa sa
                WHERE jenis_tagihan='Daftar Ulang' AND tahun_ajaran_id=?{$unitFilter}
                GROUP BY sekolah
            )
            SELECT tab.unit, tab.total as tabungan, du.total as du_target,
                   COALESCE(tag_spp.total,0) as spp, COALESCE(tag_du.total,0) as du
            FROM tab
            LEFT JOIN du ON du.unit = tab.unit
            LEFT JOIN tag tag_spp ON tag_spp.unit = tab.unit AND tag_spp.jenis_tagihan IN ('SPP','SPP Bulanan')
            LEFT JOIN tag tag_du ON tag_du.unit = tab.unit AND tag_du.jenis_tagihan = 'Daftar Ulang'",
            [$taId, $taId, $taId, $taId]
        )->getResultArray();

        foreach ($perUnitRaw as $r) {
            $unit = $r['unit'];
            $tab = (float)$r['tabungan'];
            $spp = (float)$r['spp'];
            $awal = (float)$r['du'];
            $awalTarget = (float)$r['du_target'];
            $perUnitData[$unit] = [
                'tabungan'       => $tab,
                'spp'            => $spp,
                'awal_tahun'     => $awal,
                'awal_tahun_target' => $awalTarget,
                'awal_persen'    => $awalTarget > 0 ? round(($awal / $awalTarget) * 100) : 0,
            ];
            $grandTotalTabungan += $tab;
            $grandTotalSpp += $spp;
            $grandTotalAwalTahun += $awal;
            $grandTotalAwalTahunTarget += $awalTarget;
        }

        // ====== Daftar Ulang global progress (always shown) ======
        $duGlobalPersen = $grandTotalAwalTahunTarget > 0 ? round(($grandTotalAwalTahun / $grandTotalAwalTahunTarget) * 100, 1) : 0;

        // ====== Bills due within 7 days ======
        $dueSoonBills = $db->query(
            "SELECT b.id, b.jenis_tagihan, b.nominal, b.jatuh_tempo, b.status,
                    s.nama as nama_siswa, k.nama_kelas, b.sekolah
             FROM tb_tagihan_siswa b
             JOIN tb_siswa s ON s.id = b.siswa_id
             LEFT JOIN tb_kelas k ON k.id = s.kelas_id
             WHERE b.status IN ('belum_bayar','cicil')
               AND b.jatuh_tempo IS NOT NULL
               AND b.jatuh_tempo BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '7 days'
               AND b.tahun_ajaran_id = ?{$unitFilterB}
             ORDER BY b.jatuh_tempo ASC
             LIMIT 10",
            [$taId]
        )->getResultArray();

        $data = [
            'title'                => 'Dashboard',
            'totalSaldoTabungan'   => $totalSaldoTabungan,
            'totalSppTertagih'     => $sppPaidThisMonth,
            'totalBayarHariIni'    => $totalBayarHariIni,
            'jumlahBelumLunas'     => $jumlahBelumLunas,
            'sppBelumLunas'        => $sppBelumLunas,
            'duBelumLunas'         => $duBelumLunas,
            'rekeningAktif'        => $rekeningAktif,
            'totalSppTarget'       => $totalSppTarget,
            'duTarget'             => $duTarget,
            'duTerbayar'           => $duTerbayar,
            'totalPemasukanBulanIni' => $totalPemasukanBulanIni,
            'chartData'            => $chartData,
            'maxChart'             => $maxChart,
            'recentTransactions'   => $recentTransactions,
            'perUnitData'          => $perUnitData,
            'grandTotalTabungan'   => $grandTotalTabungan,
            'grandTotalSpp'        => $grandTotalSpp,
            'grandTotalAwalTahun'  => $grandTotalAwalTahun,
            'grandTotalAwalTahunTarget' => $grandTotalAwalTahunTarget,
            'duGlobalPersen'       => $duGlobalPersen,
            'dueSoonBills'         => $dueSoonBills,
            'tahunAjaran'          => $tahunAjaran,
            'bulanFilter'          => $bulanFilter,
            'tahunFilter'          => $tahunFilter,
        ];

        return $this->render('dashboard/index', $data);
    }
}
