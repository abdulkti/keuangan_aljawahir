<?php

namespace App\Controllers;

use App\Models\AcademicYearModel;
use App\Models\BillModel;
use App\Models\BillPaymentModel;
use App\Models\SavingsAccountModel;
use App\Models\SavingsTransactionModel;

class Pembukuan extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->userData['sekolah'] ?? 'admin';

        $taModel = new AcademicYearModel();
        $ta = $taModel->where('aktif', 1)->first();

        $billModel = new BillModel();
        $savingsModel = new SavingsAccountModel();
        $paymentModel = new BillPaymentModel();
        $txModel = new SavingsTransactionModel();

        $buku = $this->request->getGet('buku') ?: 'semua';

        $totalKas = $paymentModel->selectSum('nominal_dibayar');
        if ($sekolah && $sekolah !== 'admin') {
            $totalKas->join('tb_tagihan_siswa', 'tb_tagihan_siswa.id = tb_pembayaran.tagihan_id')
                ->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        $totalKas = $totalKas->get()->getRow()->nominal_dibayar ?? 0;

        $totalTarik = $txModel->selectSum('tb_transaksi_tabungan.nominal')
            ->where('tb_transaksi_tabungan.tipe', 'tarik');
        if ($sekolah && $sekolah !== 'admin') {
            $totalTarik->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')
                ->where('tb_tabungan.sekolah', $sekolah);
        }
        $totalTarik = $totalTarik->get()->getRow()->nominal ?? 0;

        $totalSaldoTabungan = $savingsModel->selectSum('saldo')
            ->where('aktif', 1);
        if ($sekolah && $sekolah !== 'admin') {
            $totalSaldoTabungan->where('sekolah', $sekolah);
        }
        $totalSaldoTabungan = $totalSaldoTabungan->get()->getRow()->saldo ?? 0;

        $totalTertagih = $billModel->getTotalTertagih($sekolah);
        $totalBelumBayar = $billModel->selectSum('nominal')
            ->where('status', 'belum_bayar');
        if ($sekolah && $sekolah !== 'admin') {
            $totalBelumBayar->where('sekolah', $sekolah);
        }
        $totalBelumBayar = $totalBelumBayar->get()->getRow()->nominal ?? 0;

        $transaksiTabungan = [];
        if ($buku === 'semua' || $buku === 'tabungan') {
            $txQuery = $txModel->select('tb_transaksi_tabungan.*, tb_tabungan.tipe as akun_tipe, tb_tabungan.sekolah as akun_sekolah, COALESCE(tb_siswa.nama, tb_guru.nama) as nama_pemilik')
                ->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')
                ->join('tb_siswa', 'tb_siswa.id = tb_tabungan.siswa_id', 'left')
                ->join('tb_guru', 'tb_guru.id = tb_tabungan.guru_id', 'left');
            if ($sekolah && $sekolah !== 'admin') {
                $txQuery->where('tb_tabungan.sekolah', $sekolah);
            }
            $transaksiTabungan = $txQuery->orderBy('tb_transaksi_tabungan.created_at', 'DESC')->findAll(100);
        }

        // Per-class tagihan data
        $kelasData = [];
        if ($buku === 'semua' || $buku === 'tagihan') {
            $db = \Config\Database::connect();
            $classWhere = ($sekolah && $sekolah !== 'admin') ? "WHERE sekolah='{$db->escapeString($sekolah)}'" : "WHERE sekolah IN ('ra','sd','smp')";
            $classes = $db->query("SELECT id, tingkat, jurusan, nama_kelas FROM tb_kelas {$classWhere} ORDER BY CASE sekolah WHEN 'ra' THEN 1 WHEN 'sd' THEN 2 WHEN 'smp' THEN 3 END, tingkat, nama_kelas")->getResultArray();

            $months = ['Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'];

            foreach ($classes as $cls) {
                $students = $db->query("
                    SELECT s.id, s.nama, s.nis, s.status, s.keterangan_pindah
                    FROM tb_siswa s
                    WHERE s.kelas_id = ? AND s.tahun_ajaran_id = ?
                    ORDER BY s.nama
                ", [$cls['id'], $ta['id'] ?? 0])->getResultArray();

                $studentRows = [];
                foreach ($students as $s) {
                    // Daftar Ulang bills
                    $duBills = $db->query("
                        SELECT id, nominal, status FROM tb_tagihan_siswa
                        WHERE siswa_id = ? AND jenis_tagihan = 'Daftar Ulang'
                    ", [$s['id']])->getResultArray();

                    $totalAwalTahun = 0;
                    $duPayments = [];
                    foreach ($duBills as $b) {
                        $totalAwalTahun += (float)$b['nominal'];
                        $pays = $db->query("
                            SELECT nominal_dibayar, created_at FROM tb_pembayaran
                            WHERE tagihan_id = ? ORDER BY created_at ASC
                        ", [$b['id']])->getResultArray();
                        foreach ($pays as $p) {
                            if (count($duPayments) < 4) {
                                $duPayments[] = (float)$p['nominal_dibayar'];
                            }
                        }
                    }

                    $totalDibayar = array_sum($duPayments);
                    $sisa = $totalAwalTahun - $totalDibayar;
                    $keterangan = $sisa <= 0 && $totalAwalTahun > 0 ? 'Lunas' : ($totalDibayar > 0 ? 'Angsuran' : '');

                    // SPP — monthly payment tracking
                    $sppBills = $db->query("
                        SELECT id, nominal, status, created_at FROM tb_tagihan_siswa
                        WHERE siswa_id = ? AND jenis_tagihan = 'SPP Bulanan'
                    ", [$s['id']])->getResultArray();

                    $sppPerBulan = 0;
                    $sppPaidMonths = [];
                    foreach ($sppBills as $sb) {
                        $sppNominal = (float)$sb['nominal'];
                        if ($sppPerBulan === 0) $sppPerBulan = $sppNominal;

                        // Determine month from the bill's created_at, not the payment date
                        $billMonthIdx = (int)date('n', strtotime($sb['created_at'])) - 1;
                        $billMonthIndex = ($billMonthIdx + 6) % 12;

                        $sppPays = $db->query("
                            SELECT id FROM tb_pembayaran
                            WHERE tagihan_id = ?
                        ", [$sb['id']])->getResultArray();

                        if (!empty($sppPays)) {
                            $sppPaidMonths[$billMonthIndex] = true;
                        }
                    }

                    // If no SPP bills found, check if student has any SPP payments directly
                    if (empty($sppBills) && $sppPerBulan === 0) {
                        $anySppPay = $db->query("
                            SELECT bp.nominal_dibayar, bp.created_at, b.jenis_tagihan
                            FROM tb_pembayaran bp
                            JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id
                            WHERE b.siswa_id = ? AND b.jenis_tagihan = 'SPP Bulanan'
                            LIMIT 1
                        ", [$s['id']])->getResultArray();
                        if (!empty($anySppPay)) {
                            $sppPerBulan = (float)$anySppPay[0]['nominal_dibayar'];
                        }
                    }

                    // Auto-check July SPP if Daftar Ulang has any payment (cicil/lunas)
                    if ($keterangan === 'Lunas' || $keterangan === 'Angsuran') {
                        $sppPaidMonths[0] = true;
                    }

                    // SPP total based on paid months
                    $totalSpp = $sppPerBulan * count($sppPaidMonths);

                    $statusLabel = '';
                    if (($s['status'] ?? 'aktif') === 'pindah') {
                        $ket = !empty($s['keterangan_pindah']) ? ': ' . $s['keterangan_pindah'] : '';
                        $statusLabel = 'Pindah' . $ket;
                    } elseif (($s['status'] ?? 'aktif') === 'lulus') {
                        $statusLabel = 'Lulus';
                    }

                    $studentRows[] = [
                        'id' => $s['id'],
                        'nama' => $s['nama'],
                        'nis' => $s['nis'],
                        'status' => $s['status'] ?? 'aktif',
                        'status_keterangan' => $statusLabel,
                        'total_awal_tahun' => $totalAwalTahun,
                        'cicilan' => $duPayments,
                        'total_dibayar' => $totalDibayar,
                        'sisa' => $sisa,
                        'keterangan' => $keterangan,
                        'spp_per_bulan' => $sppPerBulan,
                        'spp_paid_months' => $sppPaidMonths,
                        'total_spp' => $totalSpp,
                        'saldo_tabungan' => (float) $db->query(
                            "SELECT COALESCE(saldo,0) as saldo FROM tb_tabungan WHERE (siswa_id=? OR guru_id=?) AND aktif=1 LIMIT 1",
                            [$s['id'], $s['id']]
                        )->getRow()->saldo,
                    ];
                }

                $kelasData[] = [
                    'kelas_label' => $cls['tingkat'] . ($cls['jurusan'] ? ' ' . $cls['jurusan'] : '') . ' - ' . $cls['nama_kelas'],
                    'students' => $studentRows,
                ];
            }
        }

        $data = [
            'title' => 'Pembukuan',
            'totalKas' => $totalKas,
            'totalTarik' => $totalTarik,
            'saldoBersih' => $totalKas - $totalTarik,
            'totalSaldoTabungan' => $totalSaldoTabungan,
            'totalTertagih' => $totalTertagih,
            'totalBelumBayar' => $totalBelumBayar,
            'transaksiTabungan' => $transaksiTabungan,
            'kelasData' => $kelasData,
            'buku' => $buku,
            'months' => $months ?? ['Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'],
            'availableUnits' => ($sekolah && $sekolah !== 'admin') ? [$sekolah] : ['ra', 'sd', 'smp'],
            'tahunAjaran'    => $ta['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y')+1)),
        ];

        return $this->render('pembukuan/index', $data);
    }

    public function exportExcel()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $userUnit = $this->userData['sekolah'] ?? null;
        $sekolah = $this->request->getGet('sekolah') ?: $userUnit;
        if ($sekolah === 'admin' || !$sekolah) $sekolah = null;
        $buku = $this->request->getGet('buku') ?: 'semua';
        $months = ['Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'];

        $taModel = new AcademicYearModel();
        $ta = $taModel->where('aktif', 1)->first();
        $db = \Config\Database::connect();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem Keuangan Sekolah')
            ->setLastModifiedBy('Sistem Keuangan Sekolah')
            ->setTitle('Rekap Pembukuan')
            ->setDescription('Rekap pembukuan keuangan sekolah');

        $titleFont = ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]];
        $headerFont = ['font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $subHeaderFont = ['font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '64748B']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $dataStyle = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $moneyFormat = '"Rp" #,##0';
        $lastCol = 'Y';

        // ========== BUKU TABUNGAN (per-kelas) ==========
        if ($buku === 'tabungan') {
            $unitFilter = $sekolah ? " AND sa.sekolah='" . $db->escapeString($sekolah) . "'" : '';

            // Get all classes + a pseudo "Guru" group
            if ($sekolah) {
                $kelasList = $db->query("SELECT id, tingkat, jurusan, nama_kelas FROM tb_kelas WHERE sekolah = ? ORDER BY tingkat, nama_kelas", [$sekolah])->getResultArray();
            } else {
                $kelasList = $db->query("SELECT id, tingkat, jurusan, nama_kelas FROM tb_kelas WHERE sekolah IN ('ra','sd','smp') ORDER BY CASE sekolah WHEN 'ra' THEN 1 WHEN 'sd' THEN 2 WHEN 'smp' THEN 3 END, tingkat, nama_kelas")->getResultArray();
            }

            // Get all active savings accounts
            $allAccounts = $db->query("
                SELECT sa.id, sa.saldo, sa.tipe as akun_tipe, sa.siswa_id,
                       COALESCE(s.nama, t.nama) as nama_pemilik,
                       s.kelas_id
                FROM tb_tabungan sa
                LEFT JOIN tb_siswa s ON s.id = sa.siswa_id
                LEFT JOIN tb_guru t ON t.id = sa.guru_id
                WHERE sa.aktif = 1
                  AND (sa.siswa_id IS NULL OR s.id IS NOT NULL)
                  AND (sa.guru_id IS NULL OR t.id IS NOT NULL)
                  {$unitFilter}
                ORDER BY COALESCE(s.nama, t.nama)
            ")->getResultArray();

            // Group accounts by kelas_id (null for teachers/orphans)
            $grouped = [];
            $teacherAccounts = [];
            foreach ($allAccounts as $a) {
                if ($a['akun_tipe'] === 'guru' || !$a['kelas_id']) {
                    $teacherAccounts[] = $a;
                } else {
                    $gid = $a['kelas_id'];
                    if (!isset($grouped[$gid])) $grouped[$gid] = [];
                    $grouped[$gid][] = $a;
                }
            }

            $sheetIndex = 0;
            foreach ($kelasList as $kls) {
                $kelasId = $kls['id'];
                $kelasLabel = $kls['tingkat'] . ($kls['jurusan'] ? ' ' . $kls['jurusan'] : '') . ' - ' . $kls['nama_kelas'];
                $sheetLabel = substr(str_replace(['/', '\\', '*', '?', ':', '[', ']'], '', $kelasLabel), 0, 31);

                if ($sheetIndex === 0) {
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle($sheetLabel);
                } else {
                    $sheet = $spreadsheet->createSheet();
                    $sheet->setTitle($sheetLabel);
                }
                $sheetIndex++;

                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', 'KELAS ' . $kelasLabel . ($ta ? ' T.P ' . $ta['tahun_ajaran'] : ''));
                $sheet->getStyle('A1')->applyFromArray($titleFont);
                $sheet->getRowDimension('1')->setRowHeight(35);

                $sheet->setCellValue('A2', 'NO');
                $sheet->setCellValue('B2', 'NAMA');
                $sheet->setCellValue('C2', 'TIPE');
                $sheet->setCellValue('D2', 'SALDO');
                foreach (['A','B','C','D'] as $col) {
                    $sheet->getStyle($col . '2')->applyFromArray($headerFont);
                }
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('C')->setWidth(8);
                $sheet->getColumnDimension('D')->setWidth(18);

                $row = 3;
                $no = 1;
                $gtSaldo = 0;
                $accounts = $grouped[$kelasId] ?? [];
                foreach ($accounts as $a) {
                    $gtSaldo += (float)$a['saldo'];
                    $sheet->setCellValue('A' . $row, $no++);
                    $sheet->setCellValue('B' . $row, $a['nama_pemilik']);
                    $sheet->setCellValue('C' . $row, 'Siswa');
                    $sheet->setCellValue('D' . $row, (float)$a['saldo']);
                    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode($moneyFormat);
                    $row++;
                }
                // Total
                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('B' . $row, 'TOTAL');
                $sheet->setCellValue('D' . $row, $gtSaldo);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode($moneyFormat);
                $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);
                if ($row > 3) {
                    $sheet->getStyle('A2:D' . $row)->applyFromArray($dataStyle);
                }
            }

            // Sheet for teacher accounts
            if (!empty($teacherAccounts)) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle('Guru');
                $sheetIndex++;

                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', 'GURU' . ($ta ? ' T.P ' . $ta['tahun_ajaran'] : ''));
                $sheet->getStyle('A1')->applyFromArray($titleFont);
                $sheet->getRowDimension('1')->setRowHeight(35);

                $sheet->setCellValue('A2', 'NO');
                $sheet->setCellValue('B2', 'NAMA');
                $sheet->setCellValue('C2', 'TIPE');
                $sheet->setCellValue('D2', 'SALDO');
                foreach (['A','B','C','D'] as $col) {
                    $sheet->getStyle($col . '2')->applyFromArray($headerFont);
                }
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('C')->setWidth(8);
                $sheet->getColumnDimension('D')->setWidth(18);

                $row = 3;
                $no = 1;
                $gtSaldo = 0;
                foreach ($teacherAccounts as $a) {
                    $gtSaldo += (float)$a['saldo'];
                    $sheet->setCellValue('A' . $row, $no++);
                    $sheet->setCellValue('B' . $row, $a['nama_pemilik']);
                    $sheet->setCellValue('C' . $row, 'Guru');
                    $sheet->setCellValue('D' . $row, (float)$a['saldo']);
                    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode($moneyFormat);
                    $row++;
                }
                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('B' . $row, 'TOTAL');
                $sheet->setCellValue('D' . $row, $gtSaldo);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode($moneyFormat);
                $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);
                $sheet->getStyle('A2:D' . $row)->applyFromArray($dataStyle);
            }

            $unitLabel = $sekolah ? strtoupper($sekolah) : 'ALL';
            $filename = 'TABUNGAN_' . $unitLabel . '_' . date('Y-m-d') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();
        }

        // ========== BUKU TAGIHAN / SEMUA ==========
        $sheetIndex = 0;

        if ($sekolah) {
            $classes = $db->query("SELECT id, tingkat, jurusan, nama_kelas FROM tb_kelas WHERE sekolah = ? ORDER BY tingkat, nama_kelas", [$sekolah])->getResultArray();
        } else {
            $classes = $db->query("SELECT id, tingkat, jurusan, nama_kelas FROM tb_kelas WHERE sekolah IN ('ra','sd','smp') ORDER BY CASE sekolah WHEN 'ra' THEN 1 WHEN 'sd' THEN 2 WHEN 'smp' THEN 3 END, tingkat, nama_kelas")->getResultArray();
        }

        foreach ($classes as $cls) {
            $kelasLabel = $cls['tingkat'] . ($cls['jurusan'] ? ' ' . $cls['jurusan'] : '') . ' - ' . $cls['nama_kelas'];
            $sheetLabel = substr(str_replace(['/', '\\', '*', '?', ':', '[', ']'], '', $kelasLabel), 0, 31);

            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle($sheetLabel);
            } else {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle($sheetLabel);
            }
            $sheetIndex++;

            // Build per-student data
            $students = $db->query("
                SELECT s.id, s.nama, s.nis, s.status, s.keterangan_pindah
                FROM tb_siswa s
                WHERE s.kelas_id = ?
                ORDER BY s.nama
            ", [$cls['id']])->getResultArray();

            $rows = [];
            foreach ($students as $s) {
                // Daftar Ulang bills + payments
                $duBills = $db->query("SELECT id, nominal, status FROM tb_tagihan_siswa WHERE siswa_id = ? AND jenis_tagihan = 'Daftar Ulang'", [$s['id']])->getResultArray();
                $totalAwalTahun = 0;
                $duPayments = [];
                foreach ($duBills as $b) {
                    $totalAwalTahun += (float)$b['nominal'];
                    $pays = $db->query("SELECT nominal_dibayar, created_at FROM tb_pembayaran WHERE tagihan_id = ? ORDER BY created_at ASC", [$b['id']])->getResultArray();
                    foreach ($pays as $p) {
                        if (count($duPayments) < 4) $duPayments[] = (float)$p['nominal_dibayar'];
                    }
                }
                $totalDibayar = array_sum($duPayments);
                $sisa = $totalAwalTahun - $totalDibayar;
                $keterangan = $sisa <= 0 && $totalAwalTahun > 0 ? 'Lunas' : ($totalDibayar > 0 ? 'Angsuran' : '');

                // SPP
                $sppBills = $db->query("SELECT id, nominal, status, created_at FROM tb_tagihan_siswa WHERE siswa_id = ? AND jenis_tagihan = 'SPP Bulanan'", [$s['id']])->getResultArray();
                $sppPerBulan = 0;
                $sppPaidMonths = [];
                foreach ($sppBills as $sb) {
                    $sppNominal = (float)$sb['nominal'];
                    if ($sppPerBulan === 0) $sppPerBulan = $sppNominal;

                    // Determine month from the bill's created_at, not the payment date
                    $billMonthIdx = (int)date('n', strtotime($sb['created_at'])) - 1;
                    $billMonthIndex = ($billMonthIdx + 6) % 12;

                    $sppPays = $db->query("SELECT id FROM tb_pembayaran WHERE tagihan_id = ?", [$sb['id']])->getResultArray();
                    if (!empty($sppPays)) {
                        $sppPaidMonths[$billMonthIndex] = true;
                    }
                }
                if (empty($sppBills) && $sppPerBulan === 0) {
                    $anySppPay = $db->query("SELECT bp.nominal_dibayar FROM tb_pembayaran bp JOIN tb_tagihan_siswa b ON b.id = bp.tagihan_id WHERE b.siswa_id = ? AND b.jenis_tagihan = 'SPP Bulanan' LIMIT 1", [$s['id']])->getResultArray();
                    if (!empty($anySppPay)) $sppPerBulan = (float)$anySppPay[0]['nominal_dibayar'];
                }

                // Auto-check July SPP if Daftar Ulang has any payment (cicil/lunas)
                if ($keterangan === 'Lunas' || $keterangan === 'Angsuran') {
                    $sppPaidMonths[0] = true;
                }

                $totalSpp = $sppPerBulan * count($sppPaidMonths);

                $statusLabel = '';
                if (($s['status'] ?? 'aktif') === 'pindah') {
                    $ket = !empty($s['keterangan_pindah']) ? ': ' . $s['keterangan_pindah'] : '';
                    $statusLabel = 'Pindah' . $ket;
                } elseif (($s['status'] ?? 'aktif') === 'lulus') {
                    $statusLabel = 'Lulus';
                }

                $rows[] = [
                    'nama' => $s['nama'],
                    'nis' => $s['nis'],
                    'status' => $s['status'] ?? 'aktif',
                    'status_keterangan' => $statusLabel,
                    'total_awal_tahun' => $totalAwalTahun,
                    'cicilan' => $duPayments,
                    'total_dibayar' => $totalDibayar,
                    'sisa' => $sisa,
                    'keterangan' => $keterangan,
                    'spp_per_bulan' => $sppPerBulan,
                    'spp_paid_months' => $sppPaidMonths,
                    'total_spp' => $totalSpp,
                        'saldo_tabungan' => (float) $db->query(
                        "SELECT COALESCE(saldo,0) as saldo FROM tb_tabungan WHERE (siswa_id=? OR guru_id=?) AND aktif=1 LIMIT 1",
                        [$s['id'], $s['id']]
                    )->getRow()->saldo,
                ];
            }

            // Title row
            $sheet->mergeCells('A1:' . $lastCol . '1');
            $sheet->setCellValue('A1', 'KELAS ' . $kelasLabel . ' T.P ' . ($ta['tahun_ajaran'] ?? ''));
            $sheet->getStyle('A1')->applyFromArray($titleFont);
            $sheet->getRowDimension('1')->setRowHeight(35);

            // Row 2: Column headers (merged for cicilan and SPP months)
            $sheet->mergeCells('A2:A3');
            $sheet->setCellValue('A2', 'NO');
            $sheet->getStyle('A2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('A')->setWidth(5);

            $sheet->mergeCells('B2:B3');
            $sheet->setCellValue('B2', 'NAMA');
            $sheet->getStyle('B2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('B')->setWidth(30);

            $sheet->mergeCells('C2:C3');
            $sheet->setCellValue('C2', 'TAGIHAN AWAL TAHUN');
            $sheet->getStyle('C2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('C')->setWidth(16);

            $sheet->mergeCells('D2:G2');
            $sheet->setCellValue('D2', 'CICILAN');
            $sheet->getStyle('D2')->applyFromArray($headerFont);

            $sheet->mergeCells('H2:H3');
            $sheet->setCellValue('H2', 'TOTAL DIBAYAR');
            $sheet->getStyle('H2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('H')->setWidth(14);

            $sheet->mergeCells('I2:I3');
            $sheet->setCellValue('I2', 'SISA');
            $sheet->getStyle('I2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('I')->setWidth(14);

            $sheet->mergeCells('J2:J3');
            $sheet->setCellValue('J2', 'KET.');
            $sheet->getStyle('J2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('J')->setWidth(14);

            $sheet->mergeCells('K2:K3');
            $sheet->setCellValue('K2', 'SPP/ BULAN');
            $sheet->getStyle('K2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('K')->setWidth(12);

            $sheet->mergeCells('L2:W2');
            $sheet->setCellValue('L2', 'BULAN SPP');
            $sheet->getStyle('L2')->applyFromArray($headerFont);

            $sheet->mergeCells('X2:X3');
            $sheet->setCellValue('X2', 'TOTAL SPP');
            $sheet->getStyle('X2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('X')->setWidth(12);

            $sheet->mergeCells('Y2:Y3');
            $sheet->setCellValue('Y2', 'STATUS');
            $sheet->getStyle('Y2')->applyFromArray($headerFont);
            $sheet->getColumnDimension('Y')->setWidth(25);

            // Row 3: Sub-headers (cicilan 1-4, month abbreviations)
            $cicilanCols = ['D', 'E', 'F', 'G'];
            foreach ($cicilanCols as $i => $col) {
                $sheet->setCellValue($col . '3', ($i + 1));
                $sheet->getStyle($col . '3')->applyFromArray($subHeaderFont);
                $sheet->getColumnDimension($col)->setWidth(10);
            }

            foreach ($months as $i => $m) {
                $col = chr(ord('L') + $i);
                $sheet->setCellValue($col . '3', strtoupper(substr($m, 0, 3)));
                $sheet->getStyle($col . '3')->applyFromArray($subHeaderFont);
                $sheet->getColumnDimension($col)->setWidth(7);
            }

            // Data rows
            $row = 4;
            $no = 1;
            $gtAwal = 0;
            $gtDibayar = 0;
            $gtSpp = 0;

            foreach ($rows as $r) {
                $gtAwal += $r['total_awal_tahun'];
                $gtDibayar += $r['total_dibayar'];
                $gtSpp += $r['total_spp'];

                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $r['nama']);
                $sheet->setCellValue('C' . $row, $r['total_awal_tahun'] ?: '');
                if ($r['total_awal_tahun']) $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode($moneyFormat);

                for ($c = 0; $c < 4; $c++) {
                    $col = $cicilanCols[$c];
                    $val = isset($r['cicilan'][$c]) ? $r['cicilan'][$c] : '';
                    $sheet->setCellValue($col . $row, $val);
                    if ($val !== '') $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode($moneyFormat);
                }

                $sheet->setCellValue('H' . $row, $r['total_dibayar'] ?: '');
                $sheet->setCellValue('I' . $row, $r['total_awal_tahun'] ? ($r['sisa'] >= 0 ? $r['sisa'] : 0) : '');
                $sheet->setCellValue('J' . $row, $r['keterangan']);

                if ($r['total_dibayar']) $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode($moneyFormat);
                if ($r['total_awal_tahun']) $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode($moneyFormat);

                $sheet->setCellValue('K' . $row, $r['spp_per_bulan'] ?: '');
                if ($r['spp_per_bulan']) $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode($moneyFormat);

                for ($m = 0; $m < 12; $m++) {
                    $col = chr(ord('L') + $m);
                    $sheet->setCellValue($col . $row, isset($r['spp_paid_months'][$m]) ? '✓' : '');
                    $sheet->getStyle($col . $row)->applyFromArray(['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]]);
                }

                $sheet->setCellValue('X' . $row, $r['total_spp'] ?: '');
                if ($r['total_spp']) $sheet->getStyle('X' . $row)->getNumberFormat()->setFormatCode($moneyFormat);

                $sheet->setCellValue('Y' . $row, $r['status_keterangan'] ?: '');

                $no++;
                $row++;
            }

            // Total row
            $row++;
            $totalStyle = ['font' => ['bold' => true, 'size' => 11], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];

            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, 'TOTAL');
            $sheet->setCellValue('C' . $row, $gtAwal ?: '');
            for ($c = 0; $c < 4; $c++) $sheet->setCellValue($cicilanCols[$c] . $row, '');
            $sheet->setCellValue('H' . $row, $gtDibayar ?: '');
            $sheet->setCellValue('I' . $row, ($gtAwal - $gtDibayar) ?: '');
            $sheet->setCellValue('J' . $row, '');
            $sheet->setCellValue('K' . $row, '');
            for ($m = 0; $m < 12; $m++) $sheet->setCellValue(chr(ord('L') + $m) . $row, '');
            $sheet->setCellValue('X' . $row, $gtSpp ?: '');
            $sheet->setCellValue('Y' . $row, '');

            $sheet->getStyle('B' . $row . ':' . $lastCol . $row)->applyFromArray($totalStyle);
            foreach (['C', 'H', 'I', 'X'] as $col) {
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode($moneyFormat);
            }

            // Apply borders to all data
            if ($row > 4) {
                $sheet->getStyle('A3:' . $lastCol . $row)->applyFromArray($dataStyle);
            }

            // ===== Tabungan section below (hanya untuk Buku Semua) =====
            if ($buku === 'semua') {
            $row += 2;
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->setCellValue('A' . $row, 'DATA TABUNGAN');
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            ]);
            $row++;
            $sheet->setCellValue('A' . $row, 'NO');
            $sheet->setCellValue('B' . $row, 'NAMA');
            $sheet->setCellValue('C' . $row, 'SALDO TABUNGAN');
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($headerFont);
            $row++;
            $gtSaldo = 0;
            foreach ($rows as $ri => $r) {
                $gtSaldo += $r['saldo_tabungan'];
                $sheet->setCellValue('A' . $row, $ri + 1);
                $sheet->setCellValue('B' . $row, $r['nama']);
                $sheet->setCellValue('C' . $row, $r['saldo_tabungan'] ?: '');
                if ($r['saldo_tabungan']) $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode($moneyFormat);
                $row++;
            }
            // Total saldo row
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, 'TOTAL');
            $sheet->setCellValue('C' . $row, $gtSaldo ?: '');
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($totalStyle);
            if ($gtSaldo) $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode($moneyFormat);
            $sheet->getStyle('A' . ($row - count($rows) - 1) . ':C' . $row)->applyFromArray($dataStyle);
            }
        }

        // Summary sheet
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Ringkasan');
        $summarySheet->setCellValue('A1', 'RINGKASAN PEMBUKUAN');
        $summarySheet->mergeCells('A1:D1');
        $summarySheet->getStyle('A1')->applyFromArray($titleFont);
        $summarySheet->getColumnDimension('A')->setWidth(25);
        $summarySheet->getColumnDimension('B')->setWidth(20);

        $paymentModel = new BillPaymentModel();
        $txModel = new SavingsTransactionModel();
        $savingsModel = new SavingsAccountModel();
        $billModel = new BillModel();

        $totalPemasukan = $paymentModel->selectSum('nominal_dibayar');
        if ($sekolah && $sekolah !== 'admin') {
            $totalPemasukan->join('tb_tagihan_siswa', 'tb_tagihan_siswa.id = tb_pembayaran.tagihan_id')->where('tb_tagihan_siswa.sekolah', $sekolah);
        }
        $totalPemasukan = $totalPemasukan->get()->getRow()->nominal_dibayar ?? 0;

        $totalPenarikan = $txModel->selectSum('tb_transaksi_tabungan.nominal')->where('tb_transaksi_tabungan.tipe', 'tarik');
        if ($sekolah && $sekolah !== 'admin') {
            $totalPenarikan->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')->where('tb_tabungan.sekolah', $sekolah);
        }
        $totalPenarikan = $totalPenarikan->get()->getRow()->nominal ?? 0;

        $totalTabungan = $savingsModel->selectSum('saldo')->where('aktif', 1);
        if ($sekolah && $sekolah !== 'admin') {
            $totalTabungan->where('sekolah', $sekolah);
        }
        $totalTabungan = $totalTabungan->get()->getRow()->saldo ?? 0;

        $totalTertagih = $billModel->getTotalTertagih($sekolah);
        $totalBelum = $billModel->selectSum('nominal')->where('status', 'belum_bayar');
        if ($sekolah && $sekolah !== 'admin') {
            $totalBelum->where('sekolah', $sekolah);
        }
        $totalBelum = $totalBelum->get()->getRow()->nominal ?? 0;

        $summaryData = [
            ['Total Pemasukan Kas', $totalPemasukan],
            ['Total Tagihan Tertagih', $totalTertagih],
            ['Total Tagihan Belum Bayar', $totalBelum],
        ];
        if ($buku !== 'tagihan') {
            $summaryData = [
                ['Total Pemasukan Kas', $totalPemasukan],
                ['Total Penarikan Tabungan', $totalPenarikan],
                ['Saldo Bersih Kas', $totalPemasukan - $totalPenarikan],
                ['Total Saldo Tabungan', $totalTabungan],
                ['Total Tagihan Tertagih', $totalTertagih],
                ['Total Tagihan Belum Bayar', $totalBelum],
            ];
        }

        foreach ($summaryData as $i => $d) {
            $r = $i + 3;
            $summarySheet->setCellValue('A' . $r, $d[0]);
            $summarySheet->setCellValue('B' . $r, $d[1]);
            $summarySheet->getStyle('B' . $r)->getNumberFormat()->setFormatCode('#,##0');
            if ($i === 0) {
                $summarySheet->getStyle('A' . $r . ':B' . $r)
                    ->applyFromArray(['font' => ['bold' => true]]);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $unitLabel = $sekolah ? strtoupper($sekolah) : 'ALL';
        $prefix = $buku === 'tagihan' ? 'TAGIHAN' : 'PEMBUKUAN';
        $filename = $prefix . '_' . $unitLabel . '_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }
}
