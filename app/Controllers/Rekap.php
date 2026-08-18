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

        $tahun = $this->request->getGet('tahun') ?: date('Y');
        $bulan = $this->request->getGet('bulan');
        $tahunAjaran = $this->request->getGet('tahun_ajaran') ?? '';
        $bulanTht = $this->request->getGet('bulan_tht') ?? '';

        // --- Rekap Keuangan ---
        $allPemasukan = $pemasukanModel->where('jenis', 'pemasukan')->orderBy('tanggal', 'DESC')->findAll();
        $allPengeluaran = $pengeluaranModel->where('jenis', 'pengeluaran')->orderBy('tanggal', 'DESC')->findAll();

        $rekapPerUnitPemasukan = $pemasukanModel->getRekapPerUnit($tahun, $bulan);
        $totalPengeluaran = $pengeluaranModel->getTotal($tahun, $bulan);

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
        if ($tahun) $builder->where('EXTRACT(YEAR FROM tb_kas_yayasan.tanggal)::int =', $tahun, false);
        if ($bulan) $builder->where('EXTRACT(MONTH FROM tb_kas_yayasan.tanggal)::int =', $bulan, false);
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
        foreach ($pemasukanModel->getRekapPerKategori($tahun, $bulan) as $r) {
            $k = $r['kategori'] ?: '—';
            $m = $r['metode'] ?: '—';
            $key = $k . '::pemasukan';
            if (!isset($tempKategori[$key])) $tempKategori[$key] = ['kategori' => $k, 'tipe' => 'pemasukan', 'tunai' => 0, 'transfer' => 0, 'total' => 0];
            $tempKategori[$key][$m] += (float)$r['total'];
            $tempKategori[$key]['total'] += (float)$r['total'];
        }
        foreach ($pengeluaranModel->getRekapPerKategori($tahun, $bulan) as $r) {
            $k = $r['kategori'] ?: '—';
            $m = $r['metode'] ?: '—';
            $key = $k . '::pengeluaran';
            if (!isset($tempKategori[$key])) $tempKategori[$key] = ['kategori' => $k, 'tipe' => 'pengeluaran', 'tunai' => 0, 'transfer' => 0, 'total' => 0];
            $tempKategori[$key][$m] += (float)$r['total'];
            $tempKategori[$key]['total'] += (float)$r['total'];
        }
        $rekapKategori = array_values($tempKategori);

        // --- Rekap THT ---
        $rekapPerTahun = $thtModel->getRekapPerTahun($tahunAjaran, $bulanTht);
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

        $rekapPerGuru = $thtModel->getRekapPerGuru($tahunAjaran, $bulanTht);
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

        // Build THT academic year list
        $allThtTransaksi = $thtModel->findAll();
        $thtTahunList = [];
        foreach ($allThtTransaksi as $t) {
            $thn = (int) date('Y', strtotime($t['tanggal']));
            $bln = (int) date('m', strtotime($t['tanggal']));
            $ta = $bln >= 7 ? ($thn . '-' . ($thn + 1)) : (($thn - 1) . '-' . $thn);
            if (!in_array($ta, $thtTahunList)) {
                $thtTahunList[] = $ta;
            }
        }
        rsort($thtTahunList);
        if (empty($thtTahunList)) {
            $blnSkrg = (int) date('m');
            $thnSkrg = (int) date('Y');
            $taSkrg = $blnSkrg >= 7 ? ($thnSkrg . '-' . ($thnSkrg + 1)) : (($thnSkrg - 1) . '-' . $thnSkrg);
            $thtTahunList[] = $taSkrg;
        }
        if ($tahunAjaran && !in_array($tahunAjaran, $thtTahunList)) {
            $thtTahunList[] = $tahunAjaran;
            rsort($thtTahunList);
        }

        // Export
        if ($this->request->getGet('export') === 'keuangan') {
            return $this->exportKeuangan($rekapUnit, $rekapKategori, $allPemasukan, $allPengeluaran, $tahun, $bulan, $unitModel);
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
            'tahunTerpilih' => $tahun,
            'bulanTerpilih' => $bulan,
            'tahunList' => range(date('Y') - 5, date('Y') + 1),
            'bulanList' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
            'tahunAjaran' => $tahunAjaran,
            'thtTahunList' => $thtTahunList,
            'bulanTht' => $bulanTht,
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

    private function exportKeuangan($rekapUnit, $rekapKategori, $allPemasukan, $allPengeluaran, $tahun, $bulan, $unitModel)
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Per Unit
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Per Unit');
        $row = 1;
        $label = "REKAP YAYASAN" . ($bulan ? " - Bulan " . date('F', mktime(0,0,0,$bulan,1)) : '') . " - Tahun $tahun";
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
        $filename = 'rekap_yayasan_keuangan_' . $tahun . ($bulan ? '_' . $bulan : '') . '.xlsx';
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
            $sheet->mergeCells('A1:O1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $sheet->setCellValue('A2', 'T.P ' . str_replace('-', ' / ', $ta));
            $sheet->mergeCells('A2:O2');

            $sheet->setCellValue('A4', 'NO');
            $sheet->setCellValue('B4', 'NAMA');
            $sheet->setCellValue('C4', 'BULAN');
            $sheet->setCellValue('O4', 'Jumlah');
            $sheet->getStyle('A4:O4')->getFont()->setBold(true);
            $sheet->getStyle('A4:O4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->mergeCells('C4:N4');

            $ci = 0;
            foreach ($acadMonths as $m) {
                $col = Coordinate::stringFromColumnIndex(3 + $ci);
                $sheet->setCellValue($col . '5', $monthNames[$m]);
                $ci++;
            }
            $sheet->getStyle('A5:O5')->getFont()->setBold(true);
            $sheet->getStyle('A5:O5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row = 6;
            $no = 1;
            $monthTotals = array_fill(0, 12, 0);
            $data = $yearData[$ta] ?? [];

            foreach ($guruNames as $nama) {
                if (!isset($data[$nama])) {
                    $sheet->setCellValue('A' . $row, $no);
                    $sheet->setCellValue('B' . $row, $nama);
                    $no++;
                    $row++;
                    continue;
                }

                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, $nama);
                $total = 0;
                $ci = 0;
                foreach ($acadMonths as $m) {
                    $val = $data[$nama][$m];
                    $col = Coordinate::stringFromColumnIndex(3 + $ci);
                    if ($val > 0) {
                        $sheet->setCellValue($col . $row, $this->formatRp($val));
                        $total += $val;
                        $monthTotals[$ci] += $val;
                    }
                    $ci++;
                }
                if ($total > 0) {
                    $sheet->setCellValue('O' . $row, $this->formatRp($total));
                }
                $no++;
                $row++;
            }

            $sheet->setCellValue('B' . $row, 'Jumlah');
            $sheet->getStyle('A' . $row . ':O' . $row)->getFont()->setBold(true);
            $grand = 0;
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
            $lastRow = $row;

            $sheet->getStyle("A4:O$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(35);
            foreach (range('C', 'O') as $c) {
                $sheet->getColumnDimension($c)->setWidth(12);
            }
        }

        // REKAP-TAHUN
        $rekapTahunData = [];
        foreach ($yearData as $ta => $guruData) {
            $total = 0;
            foreach ($guruData as $nama => $monthly) {
                $total += array_sum($monthly);
            }
            $rekapTahunData[$ta] = $total;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('REKAP-TAHUN');

        $sheet->setCellValue('A4', 'NO');
        $sheet->setCellValue('B4', 'Tahun');
        $sheet->setCellValue('C4', 'Jumlah');
        $sheet->getStyle('A4:C4')->getFont()->setBold(true);

        $row = 5;
        $no = 1;
        foreach ($rekapTahunData as $ta => $total) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $ta);
            $sheet->setCellValue('C' . $row, $this->formatRp($total));
            $row++;
            $no++;
        }

        $sheet->setCellValue('B' . $row, 'Jumlah');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('C' . $row, $this->formatRp(array_sum($rekapTahunData)));
        $lastRow = $row;

        $sheet->getStyle("A4:C$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);

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
