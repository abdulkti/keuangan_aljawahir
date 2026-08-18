<?php

namespace App\Controllers;

use App\Models\ThtTransaksiModel;
use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\UnitModel;
use App\Models\GuruModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Rekap extends BaseController
{
    private function formatRp($angka)
    {
        return 'Rp ' . number_format((float)$angka, 0, '.', '.');
    }

    public function yayasan()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $pemasukanModel = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();
        $unitModel = new UnitModel();
        $thtModel = new ThtTransaksiModel();
        $guruModel = new GuruModel();

        $tahunAjaran = $this->request->getGet('tahun_ajaran') ?? '';
        $bulan = $this->request->getGet('bulan') ?? '';

        // --- Rekap Keuangan ---
        $allPemasukan = $pemasukanModel->where('jenis', 'pemasukan')->orderBy('tanggal', 'DESC')->findAll();
        $allPengeluaran = $pengeluaranModel->where('jenis', 'pengeluaran')->orderBy('tanggal', 'DESC')->findAll();

        $rekapPerUnitPemasukan = $pemasukanModel->getRekapPerUnit($tahunAjaran, $bulan);
        $totalPengeluaran = $pengeluaranModel->getTotal($tahunAjaran, $bulan);

        $rekapUnit = [];
        foreach ($rekapPerUnitPemasukan as $rp) {
            $unit = $unitModel->find($rp['unit_id']);
            $rekapUnit[] = [
                'unit' => $unit ? $unit['nama'] : 'Unknown',
                'pemasukan' => (float)$rp['total'],
                'pengeluaran' => 0,
                'saldo' => (float)$rp['total'],
            ];
        }
        $rekapUnit[] = [
            'unit' => 'Yayasan (Pengeluaran)',
            'pemasukan' => 0,
            'pengeluaran' => (float)$totalPengeluaran,
            'saldo' => (float)-$totalPengeluaran,
        ];

        // --- Rekap Harian Yayasan ---
        $db = \Config\Database::connect();
        $builder = $db->table('tb_kas_yayasan');
        $builder->select('tb_kas_yayasan.*, tb_unit.nama as unit_nama')
            ->join('tb_unit', 'tb_kas_yayasan.unit_id = tb_unit.id', 'left')
            ->orderBy('tanggal', 'ASC')
            ->orderBy('id', 'ASC');
        if ($tahunAjaran) {
            $taParts = explode('-', $tahunAjaran);
            if (count($taParts) === 2) {
                $taStart = (int)$taParts[0];
                $taEnd = (int)$taParts[1];
                if ($bulan) {
                    $bulanInt = (int)$bulan;
                    $year = $bulanInt >= 7 ? $taStart : $taEnd;
                    $builder->where('EXTRACT(YEAR FROM tb_kas_yayasan.tanggal)::int =', $year, false);
                    $builder->where('EXTRACT(MONTH FROM tb_kas_yayasan.tanggal)::int =', $bulanInt, false);
                } else {
                    $builder->groupStart()
                        ->where('EXTRACT(MONTH FROM tb_kas_yayasan.tanggal)::int >=', 7)
                        ->where('EXTRACT(YEAR FROM tb_kas_yayasan.tanggal)::int =', $taStart)
                        ->groupEnd()
                        ->orGroupStart()
                        ->where('EXTRACT(MONTH FROM tb_kas_yayasan.tanggal)::int <=', 6)
                        ->where('EXTRACT(YEAR FROM tb_kas_yayasan.tanggal)::int =', $taEnd)
                        ->orGroupEnd();
                }
            }
        }
        $transactions = $builder->get()->getResultArray();

        $rekapHarian = [];
        $saldo = 0;
        foreach ($transactions as $t) {
            $jml = (float) $t['jumlah'];
            if ($t['jenis'] === 'pemasukan') {
                $saldo += $jml;
                $rekapHarian[] = [
                    'tanggal' => $t['tanggal'],
                    'keterangan' => $t['keterangan'] ?: '—',
                    'kategori' => $t['kategori'],
                    'unit' => $t['unit_nama'] ?? 'Yayasan',
                    'pemasukan' => $jml,
                    'pengeluaran' => 0,
                    'saldo' => $saldo,
                ];
            } else {
                $saldo -= $jml;
                $rekapHarian[] = [
                    'tanggal' => $t['tanggal'],
                    'keterangan' => $t['keterangan'] ?: '—',
                    'kategori' => $t['kategori'],
                    'unit' => $t['unit_nama'] ?? 'Yayasan',
                    'pemasukan' => 0,
                    'pengeluaran' => $jml,
                    'saldo' => $saldo,
                ];
            }
        }

        // Keep rekapKategori for export
        $rekapKategori = [];
        $tempKategori = [];
        foreach ($pemasukanModel->getRekapPerKategori($tahunAjaran, $bulan) as $r) {
            $k = $r['kategori'] ?: '—';
            $m = $r['metode'] ?: '—';
            $key = $k . '::pemasukan';
            if (!isset($tempKategori[$key])) $tempKategori[$key] = ['kategori' => $k, 'tipe' => 'pemasukan', 'tunai' => 0, 'transfer' => 0, 'total' => 0];
            $tempKategori[$key][$m] += (float)$r['total'];
            $tempKategori[$key]['total'] += (float)$r['total'];
        }
        foreach ($pengeluaranModel->getRekapPerKategori($tahunAjaran, $bulan) as $r) {
            $k = $r['kategori'] ?: '—';
            $m = $r['metode'] ?: '—';
            $key = $k . '::pengeluaran';
            if (!isset($tempKategori[$key])) $tempKategori[$key] = ['kategori' => $k, 'tipe' => 'pengeluaran', 'tunai' => 0, 'transfer' => 0, 'total' => 0];
            $tempKategori[$key][$m] += (float)$r['total'];
            $tempKategori[$key]['total'] += (float)$r['total'];
        }
        $rekapKategori = array_values($tempKategori);

        // --- Rekap THT ---
        $rekapPerTahun = $thtModel->getRekapPerTahun($tahunAjaran, $bulan);
        $rekapTahun = [];
        $grandTotalTHT = 0;
        foreach ($rekapPerTahun as $r) {
            $saldo = $r['total_setoran'] - $r['total_penarikan'];
            $rekapTahun[] = [
                'tahun' => $r['tahun'],
                'total_setoran' => (float)$r['total_setoran'],
                'total_penarikan' => (float)$r['total_penarikan'],
                'saldo' => $saldo,
            ];
            $grandTotalTHT += $saldo;
        }

        $rekapPerGuru = $thtModel->getRekapPerGuru($tahunAjaran, $bulan);
        $rekapGuru = [];
        foreach ($rekapPerGuru as $r) {
            $guru = $guruModel->find($r['guru_id']);
            $unit = $guru ? $unitModel->find($guru['unit_id']) : null;
            $rekapGuru[] = [
                'nama' => $r['guru_nama'],
                'unit' => $unit ? $unit['nama'] : '-',
                'total_setoran' => (float)$r['total_setoran'],
                'total_penarikan' => (float)$r['total_penarikan'],
                'saldo' => $thtModel->getSaldoGuru($r['guru_id']),
            ];
        }

        // Build academic year list from all transactions (keuangan + THT)
        $allTaList = [];
        $allDates = array_merge(
            array_column($allPemasukan, 'tanggal'),
            array_column($allPengeluaran, 'tanggal'),
            array_column($thtModel->findAll(), 'tanggal')
        );
        foreach ($allDates as $dt) {
            $thn = (int) date('Y', strtotime($dt));
            $bln = (int) date('m', strtotime($dt));
            $ta = $bln >= 7 ? ($thn . '-' . ($thn + 1)) : (($thn - 1) . '-' . $thn);
            if (!in_array($ta, $allTaList)) {
                $allTaList[] = $ta;
            }
        }
        rsort($allTaList);
        if (empty($allTaList)) {
            $blnSkrg = (int) date('m');
            $thnSkrg = (int) date('Y');
            $taSkrg = $blnSkrg >= 7 ? ($thnSkrg . '-' . ($thnSkrg + 1)) : (($thnSkrg - 1) . '-' . $thnSkrg);
            $allTaList[] = $taSkrg;
        }
        if ($tahunAjaran && !in_array($tahunAjaran, $allTaList)) {
            $allTaList[] = $tahunAjaran;
            rsort($allTaList);
        }
        $thtTahunList = $allTaList;

        // Export
        if ($this->request->getGet('export') === 'keuangan') {
            return $this->exportKeuangan($rekapUnit, $rekapKategori, $allPemasukan, $allPengeluaran, $tahunAjaran, $bulan, $unitModel);
        }
        if ($this->request->getGet('export') === 'tht') {
            return $this->exportTHT($rekapTahun, $rekapGuru, $grandTotalTHT, $thtModel, $guruModel, $unitModel);
        }

        $data = [
            'activeMenu' => 'rekap-yayasan',
            'rekapUnit' => $rekapUnit,
            'rekapKategori' => $rekapKategori,
            'rekapHarian' => $rekapHarian,
            'rekapTahun' => $rekapTahun,
            'rekapGuru' => $rekapGuru,
            'grandTotalTHT' => $grandTotalTHT,
            'tahunAjaran' => $tahunAjaran,
            'bulanTerpilih' => $bulan,
            'thtTahunList' => $thtTahunList,
            'bulanList' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
        ];

        return $this->render('superadmin/rekap_yayasan', $data);
    }

    public function tht()
    {
        return redirect()->to('/rekap/yayasan');
    }

    public function keuangan()
    {
        return redirect()->to('/rekap/yayasan');
    }

    private function exportKeuangan($rekapUnit, $rekapKategori, $allPemasukan, $allPengeluaran, $tahunAjaran, $bulan, $unitModel)
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Per Unit
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Per Unit');
        $row = 1;
        $label = "REKAP YAYASAN" . ($bulan ? " - Bulan " . date('F', mktime(0,0,0,$bulan,1)) : '') . ($tahunAjaran ? " - T.P $tahunAjaran" : '');
        $sheet->setCellValue("A$row", $label);
        $sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(14);
        $row += 2;
        $sheet->setCellValue("A$row", 'Unit');
        $sheet->setCellValue("B$row", 'Pemasukan');
        $sheet->setCellValue("C$row", 'Pengeluaran');
        $sheet->setCellValue("D$row", 'Saldo');
        $sheet->getStyle("A$row:D$row")->getFont()->setBold(true);
        $hRow = $row; $row++; $sRow = $row;
        foreach ($rekapUnit as $r) {
            $sheet->setCellValue("A$row", $r['unit']);
            $sheet->setCellValue("B$row", $r['pemasukan']);
            $sheet->setCellValue("C$row", $r['pengeluaran']);
            $sheet->setCellValue("D$row", $r['saldo']);
            $row++;
        }
        $eRow = $row - 1;
        $sheet->getStyle("A$hRow:D$eRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($eRow >= $sRow) $sheet->getStyle("B$sRow:D$eRow")->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A','D') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        // Sheet 2: Per Kategori
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Per Kategori');
        $row = 1;
        $sheet2->setCellValue("A$row", 'Kategori');
        $sheet2->setCellValue("B$row", 'Tipe');
        $sheet2->setCellValue("C$row", 'Tunai');
        $sheet2->setCellValue("D$row", 'Transfer');
        $sheet2->setCellValue("E$row", 'Total');
        $sheet2->getStyle("A$row:E$row")->getFont()->setBold(true);
        $hRow = $row; $row++; $sRow = $row;
        foreach ($rekapKategori as $r) {
            $sheet2->setCellValue("A$row", $r['kategori']);
            $sheet2->setCellValue("B$row", $r['tipe']);
            $sheet2->setCellValue("C$row", $r['tunai'] ?? 0);
            $sheet2->setCellValue("D$row", $r['transfer'] ?? 0);
            $sheet2->setCellValue("E$row", $r['total']);
            $row++;
        }
        $eRow = $row - 1;
        $sheet2->getStyle("A$hRow:E$eRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        if ($eRow >= $sRow) $sheet2->getStyle("C$sRow:E$eRow")->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A','E') as $col) $sheet2->getColumnDimension($col)->setAutoSize(true);

        // Sheet 3: Detail Pemasukan
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Detail Pemasukan');
        $row = 1;
        $sheet3->setCellValue("A$row", 'Tanggal');
        $sheet3->setCellValue("B$row", 'Unit');
        $sheet3->setCellValue("C$row", 'Keterangan');
        $sheet3->setCellValue("D$row", 'Kategori');
        $sheet3->setCellValue("E$row", 'Jumlah');
        $sheet3->getStyle("A$row:E$row")->getFont()->setBold(true);
        $hRow = $row; $row++; $sRow = $row;
        foreach ($allPemasukan as $p) {
            $unit = $unitModel->find($p['unit_id']);
            $sheet3->setCellValue("A$row", $p['tanggal']);
            $sheet3->setCellValue("B$row", $unit ? $unit['nama'] : '-');
            $sheet3->setCellValue("C$row", $p['keterangan']);
            $sheet3->setCellValue("D$row", $p['kategori']);
            $sheet3->setCellValue("E$row", $p['jumlah']);
            $row++;
        }
        $eRow = $row - 1;
        $sheet3->getStyle("A$hRow:E$eRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet3->getStyle("A$hRow:E$eRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        if ($eRow >= $sRow) $sheet3->getStyle("E$sRow:E$eRow")->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A','E') as $col) $sheet3->getColumnDimension($col)->setAutoSize(true);

        // Sheet 4: Detail Pengeluaran
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Detail Pengeluaran');
        $row = 1;
        $sheet4->setCellValue("A$row", 'Tanggal');
        $sheet4->setCellValue("B$row", 'Keterangan');
        $sheet4->setCellValue("C$row", 'Kategori');
        $sheet4->setCellValue("D$row", 'Jumlah');
        $sheet4->getStyle("A$row:D$row")->getFont()->setBold(true);
        $hRow = $row; $row++; $sRow = $row;
        foreach ($allPengeluaran as $p) {
            $sheet4->setCellValue("A$row", $p['tanggal']);
            $sheet4->setCellValue("B$row", $p['keterangan']);
            $sheet4->setCellValue("C$row", $p['kategori']);
            $sheet4->setCellValue("D$row", $p['jumlah']);
            $row++;
        }
        $eRow = $row - 1;
        $sheet4->getStyle("A$hRow:D$eRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet4->getStyle("A$hRow:D$eRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        if ($eRow >= $sRow) $sheet4->getStyle("D$sRow:D$eRow")->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A','D') as $col) $sheet4->getColumnDimension($col)->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $filename = 'rekap_yayasan_keuangan_' . ($tahunAjaran ?? 'semua') . ($bulan ? '_' . $bulan : '') . '.xlsx';
        $tmpFile = tempnam(sys_get_temp_dir(), 'rky');
        $writer->save($tmpFile);
        return $this->response->download($tmpFile, null)->setFileName($filename);
    }

    private function exportTHT($rekapTahun, $rekapGuru, $grandTotalTHT, $thtModel, $guruModel, $unitModel)
    {
        $spreadsheet = new Spreadsheet();

        $allGuru = $guruModel->getWithUnit();
        $guruInfo = [];
        foreach ($allGuru as $g) {
            $guruInfo[$g['nama']] = $g['unit_nama'];
        }
        $guruNames = array_keys($guruInfo);
        sort($guruNames);

        $transactions = $thtModel->select('tb_transaksi_tht.*, tb_guru.nama as guru_nama')
            ->join('tb_guru', 'tb_transaksi_tht.guru_id = tb_guru.id')
            ->orderBy('tb_guru.nama, tanggal', 'ASC')
            ->findAll();

        $yearData = [];
        $yearDataPenarikan = [];
        $allYears = [];

        foreach ($transactions as $t) {
            $thn = (int) date('Y', strtotime($t['tanggal']));
            $bln = (int) date('m', strtotime($t['tanggal']));
            $ta = $bln >= 7 ? ($thn . '-' . ($thn + 1)) : (($thn - 1) . '-' . $thn);
            $nama = $t['guru_nama'];

            if (!in_array($ta, $allYears)) {
                $allYears[] = $ta;
            }

            $target = $t['tipe'] === 'setoran' ? $yearData : $yearDataPenarikan;

            if (!isset($target[$ta])) {
                $target[$ta] = [];
            }
            if (!isset($target[$ta][$nama])) {
                $target[$ta][$nama] = array_fill(1, 12, 0);
            }
            $target[$ta][$nama][$bln] += (float) $t['jumlah'];
        }
        sort($allYears);

        $monthNames = [1 => 'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI', 'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOPEMBER', 'DESEMBER'];
        $acadMonths = [7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6];

        $isFirst = true;
        if (empty($allYears)) {
            $allYears[] = date('Y') . '-' . (date('Y') + 1);
        }

        foreach ($allYears as $ta) {
            $sheet = $isFirst ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $isFirst = false;
            $sheet->setTitle($ta);

            $sheet->setCellValue('A1', 'LAPORAN TABUNGAN HARI TUA  (THT) GURU SDIT AL JAWAHIR');
            $sheet->mergeCells('A1:P1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $sheet->setCellValue('A2', 'T.P ' . str_replace('-', ' / ', $ta));
            $sheet->mergeCells('A2:P2');

            $sheet->setCellValue('A4', 'NO');
            $sheet->setCellValue('B4', 'NAMA');
            $sheet->setCellValue('C4', 'BULAN');
            $sheet->setCellValue('O4', 'Jumlah');
            $sheet->setCellValue('P4', 'Saldo');
            $sheet->getStyle('A4:P4')->getFont()->setBold(true);
            $sheet->getStyle('A4:P4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('C4:N4');

            $ci = 0;
            foreach ($acadMonths as $m) {
                $col = Coordinate::stringFromColumnIndex(3 + $ci);
                $sheet->setCellValue($col . '5', $monthNames[$m]);
                $ci++;
            }
            $sheet->getStyle('A5:P5')->getFont()->setBold(true);
            $sheet->getStyle('A5:P5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row = 6;
            $no = 1;
            $monthTotals = array_fill(0, 12, 0);
            $data = $yearData[$ta] ?? [];
            $dataPenarikan = $yearDataPenarikan[$ta] ?? [];

            foreach ($guruNames as $nama) {
                $setoran = $data[$nama] ?? array_fill(1, 12, 0);
                $penarikan = $dataPenarikan[$nama] ?? array_fill(1, 12, 0);
                $totalSetoran = array_sum($setoran);
                $totalPenarikan = array_sum($penarikan);

                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $nama);
                $ci = 0;
                foreach ($acadMonths as $m) {
                    $val = $setoran[$m] ?? 0;
                    $col = Coordinate::stringFromColumnIndex(3 + $ci);
                    if ($val > 0) {
                        $sheet->setCellValue($col . $row, $this->formatRp($val));
                        $monthTotals[$ci] += $val;
                    }
                    $ci++;
                }
                if ($totalSetoran > 0) {
                    $sheet->setCellValue('O' . $row, $this->formatRp($totalSetoran));
                }
                $saldo = $totalSetoran - $totalPenarikan;
                if ($saldo != 0) {
                    $sheet->setCellValue('P' . $row, $this->formatRp($saldo));
                }
                $no++;
                $row++;
            }

            // Jumlah row
            $sheet->setCellValue('B' . $row, 'Jumlah');
            $sheet->getStyle('A' . $row . ':P' . $row)->getFont()->setBold(true);
            $grand = 0;
            $grandPenarikan = 0;
            $ci = 0;
            foreach ($acadMonths as $m) {
                $col = Coordinate::stringFromColumnIndex(3 + $ci);
                $val = $monthTotals[$ci];
                if ($val > 0) {
                    $sheet->setCellValue($col . $row, $this->formatRp($val));
                    $grand += $val;
                }
                $ci++;
            }
            $sheet->setCellValue('O' . $row, $this->formatRp($grand));
            $grandSaldo = $grand;
            foreach ($dataPenarikan as $nama => $monthly) {
                $grandSaldo -= array_sum($monthly);
            }
            if ($grandSaldo != 0) {
                $sheet->setCellValue('P' . $row, $this->formatRp($grandSaldo));
            }
            $lastRow = $row;

            $sheet->getStyle("A4:P$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(35);
            foreach (range('C', 'P') as $c) {
                $sheet->getColumnDimension($c)->setWidth(12);
            }
        }

        // REKAP-TAHUN
        $rekapTahunData = [];
        $rekapTahunPenarikan = [];
        foreach ($yearData as $ta => $guruData) {
            $total = 0;
            foreach ($guruData as $nama => $monthly) {
                $total += array_sum($monthly);
            }
            $rekapTahunData[$ta] = $total;
        }
        foreach ($yearDataPenarikan as $ta => $guruData) {
            $total = 0;
            foreach ($guruData as $nama => $monthly) {
                $total += array_sum($monthly);
            }
            $rekapTahunPenarikan[$ta] = $total;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('REKAP-TAHUN');

        $sheet->setCellValue('A4', 'NO');
        $sheet->setCellValue('B4', 'Tahun');
        $sheet->setCellValue('C4', 'Setoran');
        $sheet->setCellValue('D4', 'Penarikan');
        $sheet->setCellValue('E4', 'Saldo');
        $sheet->getStyle('A4:E4')->getFont()->setBold(true);

        $row = 5;
        $no = 1;
        $grandSet = 0;
        $grandPen = 0;
        foreach ($rekapTahunData as $ta => $total) {
            $pen = $rekapTahunPenarikan[$ta] ?? 0;
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $ta);
            $sheet->setCellValue('C' . $row, $this->formatRp($total));
            $sheet->setCellValue('D' . $row, $this->formatRp($pen));
            $sheet->setCellValue('E' . $row, $this->formatRp($total - $pen));
            $grandSet += $total;
            $grandPen += $pen;
            $row++;
            $no++;
        }

        $sheet->setCellValue('B' . $row, 'Jumlah');
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('C' . $row, $this->formatRp($grandSet));
        $sheet->setCellValue('D' . $row, $this->formatRp($grandPen));
        $sheet->setCellValue('E' . $row, $this->formatRp($grandSet - $grandPen));
        $lastRow = $row;

        $sheet->getStyle("A4:E$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);

        // REKAP-GURU
        $guruYearMatrix = [];
        $guruYearPenarikan = [];
        foreach ($yearData as $ta => $guruData) {
            foreach ($guruData as $nama => $monthly) {
                $total = array_sum($monthly);
                if ($total > 0) {
                    $guruYearMatrix[$ta][$nama] = $total;
                }
            }
        }
        foreach ($yearDataPenarikan as $ta => $guruData) {
            foreach ($guruData as $nama => $monthly) {
                $total = array_sum($monthly);
                if ($total > 0) {
                    $guruYearPenarikan[$ta][$nama] = $total;
                }
            }
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('REKAP-GURU');

        $sheet->setCellValue('A1', 'TUNJANGAN HARI TUA (THT)');
        $sheet->mergeCells('A1:Z1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', 'T H T');
        $sheet->mergeCells('A2:Z2');
        $sheet->setCellValue('A3', 'N A M A');
        $sheet->mergeCells('A3:Z3');

        $sheet->setCellValue('A4', 'TP');
        $sheet->getStyle('A4')->getFont()->setBold(true);

        $colIdx = 2;
        foreach ($guruNames as $nama) {
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($col . '4', strtoupper($nama));
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $colIdx++;
        }
        $maxColIdx = $colIdx;

        $row = 5;
        $grandIuran = [];
        $grandRealisasi = [];
        foreach ($allYears as $ta) {
            // Baris Iuran
            $sheet->setCellValue('A' . $row, $ta);
            $sheet->setCellValue('B' . $row, 'Iuran');
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('B' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('006633'));
            $colIdx = 3;
            foreach ($guruNames as $nama) {
                $val = $guruYearMatrix[$ta][$nama] ?? 0;
                $col = Coordinate::stringFromColumnIndex($colIdx);
                if ($val > 0) {
                    $sheet->setCellValue($col . $row, $this->formatRp($val));
                    $grandIuran[$nama] = ($grandIuran[$nama] ?? 0) + $val;
                } else {
                    $sheet->setCellValue($col . $row, '-');
                }
                $colIdx++;
            }
            $row++;

            // Baris Realisasi
            $sheet->setCellValue('B' . $row, 'Realisasi');
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('B' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CC3333'));
            $colIdx = 3;
            foreach ($guruNames as $nama) {
                $val = $guruYearPenarikan[$ta][$nama] ?? 0;
                $col = Coordinate::stringFromColumnIndex($colIdx);
                if ($val > 0) {
                    $sheet->setCellValue($col . $row, $this->formatRp($val));
                    $grandRealisasi[$nama] = ($grandRealisasi[$nama] ?? 0) + $val;
                } else {
                    $sheet->setCellValue($col . $row, '-');
                }
                $colIdx++;
            }
            $row++;
        }

        $lastDataRow = $row - 1;

        // Saldo row
        $sheet->setCellValue('B' . $row, 'Saldo');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('0066CC'));
        $colIdx = 3;
        $grandTotalSaldo = 0;
        foreach ($guruNames as $nama) {
            $iuran = $grandIuran[$nama] ?? 0;
            $realisasi = $grandRealisasi[$nama] ?? 0;
            $saldo = $iuran - $realisasi;
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($col . $row, $this->formatRp($saldo));
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $grandTotalSaldo += $saldo;
            $colIdx++;
        }
        $col = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($col . $row, $this->formatRp($grandTotalSaldo));
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $row++;

        // Grand total rows
        $sheet->setCellValue('A' . $row, 'JUMLAH');
        $sheet->getStyle('A' . $row . ':A' . ($row + 1))->getFont()->setBold(true);
        $sheet->setCellValue('B' . $row, 'Iuran');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $colIdx = 3;
        $grandTotalAll = 0;
        foreach ($guruNames as $nama) {
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($col . $row, $this->formatRp($grandIuran[$nama] ?? 0));
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $grandTotalAll += ($grandIuran[$nama] ?? 0);
            $colIdx++;
        }
        $col = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($col . $row, $this->formatRp($grandTotalAll));
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('B' . $row, 'Realisasi');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true);
        $colIdx = 3;
        $grandTotalAllR = 0;
        foreach ($guruNames as $nama) {
            $col = Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue($col . $row, $this->formatRp($grandRealisasi[$nama] ?? 0));
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $grandTotalAllR += ($grandRealisasi[$nama] ?? 0);
            $colIdx++;
        }
        $col = Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue($col . $row, $this->formatRp($grandTotalAllR));
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $lastRow = $row;

        $sheet->getStyle("A4:" . Coordinate::stringFromColumnIndex($maxColIdx) . "$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(12);
        for ($c = 3; $c <= $maxColIdx; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $filename = 'THT_tabungan hari tua.xlsx';
        $tmpFile = tempnam(sys_get_temp_dir(), 'tht');
        $writer->save($tmpFile);
        return $this->response->download($tmpFile, null)->setFileName($filename);
    }
}
