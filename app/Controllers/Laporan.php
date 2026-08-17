<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\BillPaymentModel;
use App\Models\SavingsAccountModel;
use App\Models\SavingsTransactionModel;

class Laporan extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'admin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $role = $this->userData['role'] ?? 'staff';
        $userSekolah = $this->userData['sekolah'] ?? 'admin';
        $isGlobal = in_array($role, ['superadmin', 'admin']);
        $unit = '';
        if ($isGlobal) {
            $unit = $this->request->getGet('unit');
            if (!in_array($unit, ['ra', 'sd', 'smp'])) {
                $unit = '';
            }
        }
        $sekolah = $isGlobal ? $unit : $userSekolah;

        $billModel = new BillModel();
        $savingsModel = new SavingsAccountModel();
        $paymentModel = new BillPaymentModel();
        $txModel = new SavingsTransactionModel();
        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $taId = (int)($activeTa['id'] ?? 0);

        $dari = $this->request->getGet('dari') ?: date('Y-01-01');
        $sampai = $this->request->getGet('sampai') ?: date('Y-m-d');
        $jenis = $this->request->getGet('jenis');

        $pemasukan = $paymentModel->selectSum('nominal_dibayar')
            ->where('tb_pembayaran.created_at >=', $dari . ' 00:00:00')
            ->where('tb_pembayaran.created_at <=', $sampai . ' 23:59:59');
        if ($sekolah && $sekolah !== 'admin') {
            $pemasukan->join('tb_tagihan_siswa', 'tb_tagihan_siswa.id = tb_pembayaran.tagihan_id')
                ->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        $totalPemasukan = $pemasukan->get()->getRow()->nominal_dibayar ?? 0;

        $pengeluaran = $txModel->selectSum('tb_transaksi_tabungan.nominal')
            ->where('tb_transaksi_tabungan.tipe', 'tarik')
            ->where('tb_transaksi_tabungan.created_at >=', $dari . ' 00:00:00')
            ->where('tb_transaksi_tabungan.created_at <=', $sampai . ' 23:59:59');
        if ($sekolah && $sekolah !== 'admin') {
            $pengeluaran->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')
                ->where('tb_tabungan.sekolah', $sekolah);
        }
        $totalPengeluaran = $pengeluaran->get()->getRow()->nominal ?? 0;

        // Recent transactions list for this period
        $transaksiList = [];

        $recentPayments = $paymentModel->select('tb_pembayaran.*, tb_tagihan_siswa.jenis_tagihan, tb_siswa.nama, tb_siswa.nis, tb_kelas.nama_kelas')
            ->join('tb_tagihan_siswa', 'tb_tagihan_siswa.id = tb_pembayaran.tagihan_id')
            ->join('tb_siswa', 'tb_siswa.id = tb_tagihan_siswa.siswa_id')
            ->join('tb_kelas', 'tb_kelas.id = tb_siswa.kelas_id')
            ->where('tb_pembayaran.created_at >=', $dari . ' 00:00:00')
            ->where('tb_pembayaran.created_at <=', $sampai . ' 23:59:59');
        if ($sekolah && $sekolah !== 'admin') {
            $recentPayments->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        foreach ($recentPayments->orderBy('tb_pembayaran.created_at', 'DESC')->findAll(20) as $p) {
            $transaksiList[] = [
                'tanggal' => $p['created_at'],
                'tipe' => 'Pemasukan',
                'deskripsi' => $p['jenis_tagihan'] . ' - ' . $p['nama'],
                'detail' => $p['nama_kelas'],
                'nominal' => $p['nominal_dibayar'],
            ];
        }

        $recentTarik = $txModel->select('tb_transaksi_tabungan.*, COALESCE(tb_siswa.nama, tb_guru.nama) as nama_pemilik')
            ->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')
            ->join('tb_siswa', 'tb_siswa.id = tb_tabungan.siswa_id', 'left')
            ->join('tb_guru', 'tb_guru.id = tb_tabungan.guru_id', 'left')
            ->where('tb_transaksi_tabungan.tipe', 'tarik')
            ->where('tb_transaksi_tabungan.created_at >=', $dari . ' 00:00:00')
            ->where('tb_transaksi_tabungan.created_at <=', $sampai . ' 23:59:59');
        if ($sekolah && $sekolah !== 'admin') {
            $recentTarik->where('tb_tabungan.sekolah', $sekolah);
        }
        foreach ($recentTarik->orderBy('tb_transaksi_tabungan.created_at', 'DESC')->findAll(20) as $tx) {
            $transaksiList[] = [
                'tanggal' => $tx['created_at'],
                'tipe' => 'Pengeluaran',
                'deskripsi' => 'Tarik - ' . $tx['nama_pemilik'],
                'detail' => $tx['catatan'] ?? '-',
                'nominal' => -$tx['nominal'],
            ];
        }

        usort($transaksiList, function ($a, $b) {
            return strtotime($b['tanggal']) - strtotime($a['tanggal']);
        });
        $transaksiList = array_slice($transaksiList, 0, 20);

        $tabunganSiswa = $savingsModel->selectSum('saldo')
            ->where('tipe', 'siswa')
            ->where('aktif', 1);
        if ($sekolah && $sekolah !== 'admin') {
            $tabunganSiswa->where('sekolah', $sekolah);
        }
        $totalTabunganSiswa = $tabunganSiswa->get()->getRow()->saldo ?? 0;

        $tabunganGuru = $savingsModel->selectSum('saldo')
            ->where('tipe', 'guru')
            ->where('aktif', 1);
        if ($sekolah && $sekolah !== 'admin') {
            $tabunganGuru->where('sekolah', $sekolah);
        }
        $totalTabunganGuru = $tabunganGuru->get()->getRow()->saldo ?? 0;

        $totalPemasukanAll = $billModel->getTotalTertagih($sekolah, $taId);
        $totalBelumBayar = $billModel->selectSum('nominal')
            ->where('status', 'belum_bayar');
        if ($sekolah && $sekolah !== 'admin') {
            $totalBelumBayar->where('sekolah', $sekolah);
        }
        if ($taId) {
            $totalBelumBayar->where('tahun_ajaran_id', $taId);
        }
        $totalBelumBayar = $totalBelumBayar->get()->getRow()->nominal ?? 0;

        $tagihanCount = $billModel;
        if ($sekolah && $sekolah !== 'admin') {
            $tagihanCount->where('sekolah', $sekolah);
        }
        if ($taId) {
            $tagihanCount->where('tahun_ajaran_id', $taId);
        }
        $tagihanCount = $tagihanCount->countAllResults();
        $lunasCount = $billModel->getLunasCount($sekolah, $taId);

        // Monthly chart data
        $monthlyIncome = [];
        $monthlyExpense = [];
        $bulanLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $startMonth = (int)date('m', strtotime($dari));
        $endMonth = (int)date('m', strtotime($sampai));
        $startYear = (int)date('Y', strtotime($dari));

        for ($m = 1; $m <= 12; $m++) {
            $monthlyIncome[$m] = 0;
            $monthlyExpense[$m] = 0;
        }

        // Income per month from payments
        $rawIncome = $paymentModel->select("EXTRACT(MONTH FROM tb_pembayaran.created_at)::int as bulan, SUM(nominal_dibayar) as total")
            ->join('tb_tagihan_siswa', 'tb_tagihan_siswa.id = tb_pembayaran.tagihan_id')
            ->where('tb_pembayaran.created_at >=', $dari . ' 00:00:00')
            ->where('tb_pembayaran.created_at <=', $sampai . ' 23:59:59')
            ->groupBy('bulan')
            ->orderBy('bulan');
        if ($sekolah && $sekolah !== 'admin') {
            $rawIncome->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        foreach ($rawIncome->findAll() as $r) {
            $monthlyIncome[(int)$r['bulan']] = (float)$r['total'];
        }

        // Expense per month from withdrawals
        $rawExpense = $txModel->select("EXTRACT(MONTH FROM tb_transaksi_tabungan.created_at)::int as bulan, SUM(tb_transaksi_tabungan.nominal) as total")
            ->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')
            ->where('tb_transaksi_tabungan.tipe', 'tarik')
            ->where('tb_transaksi_tabungan.created_at >=', $dari . ' 00:00:00')
            ->where('tb_transaksi_tabungan.created_at <=', $sampai . ' 23:59:59')
            ->groupBy('bulan')
            ->orderBy('bulan');
        if ($sekolah && $sekolah !== 'admin') {
            $rawExpense->where('tb_tabungan.sekolah', $sekolah);
        }
        foreach ($rawExpense->findAll() as $r) {
            $monthlyExpense[(int)$r['bulan']] = (float)$r['total'];
        }

        // Category breakdown (donut)
        $catRaw = $paymentModel->select('tb_tagihan_siswa.jenis_tagihan, SUM(nominal_dibayar) as total')
            ->join('tb_tagihan_siswa', 'tb_tagihan_siswa.id = tb_pembayaran.tagihan_id')
            ->where('tb_pembayaran.created_at >=', $dari . ' 00:00:00')
            ->where('tb_pembayaran.created_at <=', $sampai . ' 23:59:59');
        if ($sekolah && $sekolah !== 'admin') {
            $catRaw->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        $kategoriPemasukan = $catRaw->groupBy('tb_tagihan_siswa.jenis_tagihan')->findAll();

        $data = [
            'title' => 'Laporan Keuangan',
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldoBersih' => $totalPemasukan - $totalPengeluaran,
            'rasioPenagihan' => $tagihanCount > 0
                ? round(($lunasCount / $tagihanCount) * 100)
                : 0,
            'totalTabunganSiswa' => $totalTabunganSiswa,
            'totalTabunganGuru' => $totalTabunganGuru,
            'totalPemasukanAll' => $totalPemasukanAll,
            'totalBelumBayar' => $totalBelumBayar,
            'transaksiList' => $transaksiList,
            'dari' => $dari,
            'sampai' => $sampai,
            'jenis' => $jenis,
            'bulanLabels' => $bulanLabels,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'kategoriPemasukan' => $kategoriPemasukan,
            'isGlobal' => $isGlobal,
            'unit' => $unit,
            'unitOptions' => ['ra' => 'RA', 'sd' => 'SD IT', 'smp' => 'SMP IT'],
        ];

        return $this->render('laporan/index', $data);
    }

    public function exportCsv()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'admin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $role = $this->userData['role'] ?? 'staff';
        $userSekolah = $this->userData['sekolah'] ?? 'admin';
        $isGlobal = in_array($role, ['superadmin', 'admin']);
        $unit = '';
        if ($isGlobal) {
            $unit = $this->request->getGet('unit');
            if (!in_array($unit, ['ra', 'sd', 'smp'])) {
                $unit = '';
            }
        }
        $sekolah = $isGlobal ? $unit : $userSekolah;
        $dari = $this->request->getGet('dari') ?: date('Y-01-01');
        $sampai = $this->request->getGet('sampai') ?: date('Y-m-d');
        $jenis = $this->request->getGet('jenis');

        $paymentModel = new BillPaymentModel();
        $txModel = new SavingsTransactionModel();

        $csv = "Tanggal,Tipe,Deskripsi,Nominal\n";
        $total = 0;

        if (!$jenis || $jenis === 'pemasukan') {
            $payments = $paymentModel->select('tb_pembayaran.*, tb_tagihan_siswa.jenis_tagihan, tb_siswa.nama')
                ->join('tb_tagihan_siswa', 'tb_tagihan_siswa.id = tb_pembayaran.tagihan_id')
                ->join('tb_siswa', 'tb_siswa.id = tb_tagihan_siswa.siswa_id')
                ->where('tb_pembayaran.created_at >=', $dari . ' 00:00:00')
                ->where('tb_pembayaran.created_at <=', $sampai . ' 23:59:59');
            if ($sekolah && $sekolah !== 'admin') {
                $payments->where('tb_tagihan_siswa.sekolah', $sekolah);
            }
            foreach ($payments->findAll() as $p) {
                $csv .= '"' . $p['created_at'] . '",Pemasukan,"' . $p['jenis_tagihan'] . ' - ' . $p['nama'] . '",' . $p['nominal_dibayar'] . "\n";
                $total += $p['nominal_dibayar'];
            }
        }

        if (!$jenis || $jenis === 'pengeluaran') {
            $txs = $txModel->select('tb_transaksi_tabungan.*, tb_tabungan.tipe as akun_tipe,
                    COALESCE(tb_siswa.nama, tb_guru.nama) as nama_pemilik')
                ->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')
                ->join('tb_siswa', 'tb_siswa.id = tb_tabungan.siswa_id', 'left')
                ->join('tb_guru', 'tb_guru.id = tb_tabungan.guru_id', 'left')
                ->where('tb_transaksi_tabungan.tipe', 'tarik')
                ->where('tb_transaksi_tabungan.created_at >=', $dari . ' 00:00:00')
                ->where('tb_transaksi_tabungan.created_at <=', $sampai . ' 23:59:59');
            if ($sekolah && $sekolah !== 'admin') {
                $txs->where('tb_tabungan.sekolah', $sekolah);
            }
            foreach ($txs->findAll() as $tx) {
                $csv .= '"' . $tx['created_at'] . '",Pengeluaran,"Tarik - ' . $tx['nama_pemilik'] . '",-' . $tx['nominal'] . "\n";
                $total -= $tx['nominal'];
            }
        }

        $csv .= ",,\"Total\"," . $total . "\n";

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="laporan-keuangan-' . $dari . '-to-' . $sampai . '.csv"')
            ->setBody($csv);
    }
}
