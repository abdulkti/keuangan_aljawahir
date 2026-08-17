<?php

namespace App\Controllers;

use App\Models\KasUnitModel;

class RekapHarian extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'admin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->userData['sekolah'] ?? 'admin';
        $tanggal = $this->request->getGet('tanggal') ?: date('Y-m-d');
        $filterUnit = $this->request->getGet('unit') ?: '';
        $role = $this->userData['role'];
        $unitModel = new \App\Models\UnitModel();
        $unitList = $unitModel->findAll();

        if ($role !== 'superadmin') {
            $filterUnit = '';
        }

        $kasUnitModel = new KasUnitModel();

        // Query berdasarkan role
        if ($role === 'superadmin' || $role === 'admin') {
            $allData = $kasUnitModel->getAllWithUnit();
        } else {
            $unit = null;
            if ($sekolah) {
                $unit = \Config\Database::connect()->query("SELECT * FROM tb_unit WHERE LOWER(nama) LIKE LOWER(?) LIMIT 1", ['%' . $sekolah . '%'])->getRowArray();
            }
            $allData = $unit ? $kasUnitModel->getRekapHarian($unit['id'], $tanggal) : [];
            if (!is_array($allData)) $allData = [];
        }

        // Filter by tanggal & unit
        $filtered = array_filter($allData, function ($d) use ($tanggal, $filterUnit) {
            if (substr($d['tanggal'], 0, 10) !== $tanggal) return false;
            if ($filterUnit && ($d['unit_nama'] ?? '') !== $filterUnit) return false;
            return true;
        });

        // Breakdown per kategori per metode
        $rekapPemasukanTunai = [];
        $rekapPemasukanTransfer = [];
        $rekapPengeluaranTunai = [];
        $rekapPengeluaranTransfer = [];
        $totalPemasukan = 0;
        $totalPengeluaran = 0;
        $pemasukanTunai = 0;
        $pemasukanTransfer = 0;
        $pengeluaranTunai = 0;
        $pengeluaranTransfer = 0;

        foreach ($filtered as $d) {
            $metode = strtolower($d['metode'] ?? 'tunai');
            $kategori = $d['kategori'] ?? 'Lainnya';
            $jumlah = (float) $d['jumlah'];

            if ($d['jenis'] === 'pemasukan') {
                $totalPemasukan += $jumlah;
                if ($metode === 'transfer') {
                    $pemasukanTransfer += $jumlah;
                    $rekapPemasukanTransfer[$kategori] = ($rekapPemasukanTransfer[$kategori] ?? 0) + $jumlah;
                } else {
                    $pemasukanTunai += $jumlah;
                    $rekapPemasukanTunai[$kategori] = ($rekapPemasukanTunai[$kategori] ?? 0) + $jumlah;
                }
            } else {
                $totalPengeluaran += $jumlah;
                if ($metode === 'transfer') {
                    $pengeluaranTransfer += $jumlah;
                    $rekapPengeluaranTransfer[$kategori] = ($rekapPengeluaranTransfer[$kategori] ?? 0) + $jumlah;
                } else {
                    $pengeluaranTunai += $jumlah;
                    $rekapPengeluaranTunai[$kategori] = ($rekapPengeluaranTunai[$kategori] ?? 0) + $jumlah;
                }
            }
        }

        // Sort kategori by jumlah desc
        uasort($rekapPemasukanTunai, fn($a, $b) => $b - $a);
        uasort($rekapPemasukanTransfer, fn($a, $b) => $b - $a);
        uasort($rekapPengeluaranTunai, fn($a, $b) => $b - $a);
        uasort($rekapPengeluaranTransfer, fn($a, $b) => $b - $a);

        // Detail transaksi
        $transaksiList = [];
        foreach ($filtered as $d) {
            $transaksiList[] = [
                'waktu' => $d['created_at'],
                'tipe' => $d['jenis'] === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran',
                'deskripsi' => $d['keterangan'] ?? '-',
                'kategori' => $d['kategori'] ?? '-',
                'metode' => $d['metode'] ?? 'tunai',
                'nominal' => $d['jenis'] === 'pemasukan' ? (float) $d['jumlah'] : -(float) $d['jumlah'],
            ];
        }

        usort($transaksiList, function ($a, $b) {
            return strtotime($a['waktu']) - strtotime($b['waktu']);
        });

        // Per-unit breakdown
        $rekapPerUnit = [];
        foreach ($filtered as $d) {
            $unit = $d['unit_nama'] ?? 'Yayasan';
            if (!isset($rekapPerUnit[$unit])) {
                $rekapPerUnit[$unit] = ['pemasukan' => 0, 'pengeluaran' => 0, 'count' => 0];
            }
            $jumlah = (float) $d['jumlah'];
            if ($d['jenis'] === 'pemasukan') {
                $rekapPerUnit[$unit]['pemasukan'] += $jumlah;
            } else {
                $rekapPerUnit[$unit]['pengeluaran'] += $jumlah;
            }
            $rekapPerUnit[$unit]['count']++;
        }
        uasort($rekapPerUnit, fn($a, $b) => ($b['pemasukan'] + $b['pengeluaran']) - ($a['pemasukan'] + $a['pengeluaran']));

        $data = [
            'title' => 'Rekap Harian',
            'tanggal' => $tanggal,
            'filterUnit' => $filterUnit,
            'unitList' => $unitList,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldoBersih' => $totalPemasukan - $totalPengeluaran,
            'pemasukanTunai' => $pemasukanTunai,
            'pemasukanTransfer' => $pemasukanTransfer,
            'pengeluaranTunai' => $pengeluaranTunai,
            'pengeluaranTransfer' => $pengeluaranTransfer,
            'rekapPemasukanTunai' => $rekapPemasukanTunai,
            'rekapPemasukanTransfer' => $rekapPemasukanTransfer,
            'rekapPengeluaranTunai' => $rekapPengeluaranTunai,
            'rekapPengeluaranTransfer' => $rekapPengeluaranTransfer,
            'rekapPerUnit' => $rekapPerUnit,
            'transaksiList' => $transaksiList,
        ];

        return $this->render('rekap_harian/index', $data);
    }

    public function exportExcel()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'admin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->userData['sekolah'] ?? 'admin';
        $tanggal = $this->request->getGet('tanggal') ?: date('Y-m-d');
        $role = $this->userData['role'];

        $kasUnitModel = new KasUnitModel();

        if ($role === 'superadmin' || $role === 'admin') {
            $allData = $kasUnitModel->getAllWithUnit();
        } else {
            $unit = null;
            if ($sekolah) {
                $unit = \Config\Database::connect()->query("SELECT * FROM tb_unit WHERE LOWER(nama) LIKE LOWER(?) LIMIT 1", ['%' . $sekolah . '%'])->getRowArray();
            }
            $allData = $unit ? $kasUnitModel->getRekapHarian($unit['id'], $tanggal) : [];
            if (!is_array($allData)) $allData = [];
        }

        $filtered = array_filter($allData, function ($d) use ($tanggal) {
            return substr($d['tanggal'], 0, 10) === $tanggal;
        });

        // Hitung totals
        $pemasukanTunai = 0;
        $pemasukanTransfer = 0;
        $pengeluaranTunai = 0;
        $pengeluaranTransfer = 0;

        foreach ($filtered as $d) {
            $metode = strtolower($d['metode'] ?? 'tunai');
            $jumlah = (float) $d['jumlah'];
            if ($d['jenis'] === 'pemasukan') {
                if ($metode === 'transfer') $pemasukanTransfer += $jumlah;
                else $pemasukanTunai += $jumlah;
            } else {
                if ($metode === 'transfer') $pengeluaranTransfer += $jumlah;
                else $pengeluaranTunai += $jumlah;
            }
        }

        $totalPemasukan = $pemasukanTunai + $pemasukanTransfer;
        $totalPengeluaran = $pengeluaranTunai + $pengeluaranTransfer;

        // Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Harian');

        $titleFont = ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]];
        $headerFont = ['font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $dataFont = ['font' => ['bold' => false, 'size' => 10], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $totalFont = ['font' => ['bold' => true, 'size' => 10], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $moneyFmt = '#,##0';

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'REKAP HARIAN ' . date('d M Y', strtotime($tanggal)));
        $sheet->getStyle('A1')->applyFromArray($titleFont);
        $sheet->getRowDimension('1')->setRowHeight(35);

        $row = 3;
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);

        $secFont = ['font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $itemFont = ['font' => ['bold' => false, 'size' => 10], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];

        $sheet->setCellValue('A' . $row, 'PEMASUKAN');
        $sheet->setCellValue('B' . $row, 'PENGELUARAN');
        $sheet->setCellValue('C' . $row, 'SALDO');
        $sheet->getStyle('A' . $row)->applyFromArray($secFont);
        $sheet->getStyle('B' . $row)->applyFromArray($secFont);
        $sheet->getStyle('C' . $row)->applyFromArray($secFont);
        $row++;

        $sheet->setCellValue('A' . $row, 'Tunai: ' . number_format($pemasukanTunai, 0, ',', '.'));
        $sheet->setCellValue('B' . $row, 'Tunai: ' . number_format($pengeluaranTunai, 0, ',', '.'));
        $sheet->setCellValue('C' . $row, 'Tunai: ' . number_format($pemasukanTunai - $pengeluaranTunai, 0, ',', '.'));
        $sheet->getStyle('A' . $row)->applyFromArray($itemFont);
        $sheet->getStyle('B' . $row)->applyFromArray($itemFont);
        $sheet->getStyle('C' . $row)->applyFromArray($itemFont);
        $row++;

        $sheet->setCellValue('A' . $row, 'Transfer: ' . number_format($pemasukanTransfer, 0, ',', '.'));
        $sheet->setCellValue('B' . $row, 'Transfer: ' . number_format($pengeluaranTransfer, 0, ',', '.'));
        $sheet->setCellValue('C' . $row, 'Transfer: ' . number_format($pemasukanTransfer - $pengeluaranTransfer, 0, ',', '.'));
        $sheet->getStyle('A' . $row)->applyFromArray($itemFont);
        $sheet->getStyle('B' . $row)->applyFromArray($itemFont);
        $sheet->getStyle('C' . $row)->applyFromArray($itemFont);
        $row++;

        $sheet->setCellValue('A' . $row, 'Total: ' . number_format($totalPemasukan, 0, ',', '.'));
        $sheet->setCellValue('B' . $row, 'Total: ' . number_format($totalPengeluaran, 0, ',', '.'));
        $sheet->setCellValue('C' . $row, 'Total: ' . number_format($totalPemasukan - $totalPengeluaran, 0, ',', '.'));
        $sheet->getStyle('A' . $row)->applyFromArray($totalFont);
        $sheet->getStyle('B' . $row)->applyFromArray($totalFont);
        $sheet->getStyle('C' . $row)->applyFromArray($totalFont);
        $row += 2;

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(16);

        $headers = ['Waktu', 'Tipe', 'Deskripsi', 'Kategori', 'Metode', 'Nominal'];
        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($colLetters[$i] . $row, $h);
            $sheet->getStyle($colLetters[$i] . $row)->applyFromArray($headerFont);
        }
        $row++;

        usort($filtered, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));

        foreach ($filtered as $d) {
            $tipe = $d['jenis'] === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
            $nominal = $d['jenis'] === 'pemasukan' ? (float) $d['jumlah'] : -(float) $d['jumlah'];
            $sheet->setCellValueExplicit('A' . $row, $d['created_at'] ?? $d['tanggal'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $tipe);
            $sheet->setCellValue('C' . $row, $d['keterangan'] ?? '-');
            $sheet->setCellValue('D' . $row, $d['kategori'] ?? '-');
            $sheet->setCellValue('E' . $row, ucfirst($d['metode'] ?? 'tunai'));
            $sheet->setCellValue('F' . $row, $nominal);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode($moneyFmt);
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataFont);
            $row++;
        }

        $filename = 'REKAP_HARIAN_' . $tanggal . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }
}
