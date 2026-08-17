<?php

namespace App\Controllers;

use App\Models\PemasukanModel;
use App\Models\PengeluaranModel;
use App\Models\UnitModel;
use App\Models\AuditLogModel;

class KasYayasan extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $this->autoTutup();

        $pemasukanModel = new PemasukanModel();
        $pengeluaranModel = new PengeluaranModel();
        $unitModel = new UnitModel();

        $tanggalParam = $this->request->getGet('tanggal');
        $viewSemua = empty($tanggalParam);
        $filterTanggal = $tanggalParam ?: date('Y-m-d');

        $pemasukan = $pemasukanModel->getWithUnit();
        $pengeluaran = $pengeluaranModel->where('jenis', 'pengeluaran')->orderBy('tanggal', 'DESC')->findAll();

        $combined = [];

        foreach ($pemasukan as $p) {
            $combined[] = [
                'id' => $p['id'],
                'tipe' => 'pemasukan',
                'tanggal' => $p['tanggal'],
                'unit_nama' => $p['unit_nama'],
                'keterangan' => $p['keterangan'],
                'kategori' => $p['kategori'],
                'metode' => $p['metode'] ?? 'tunai',
                'jumlah' => $p['jumlah'],
                'created_at' => $p['created_at'] ?? $p['tanggal'],
                'referensi_tipe' => $p['referensi_tipe'] ?? null,
                'referensi_id' => $p['referensi_id'] ?? null,
            ];
        }

        foreach ($pengeluaran as $p) {
            $combined[] = [
                'id' => $p['id'],
                'tipe' => 'pengeluaran',
                'tanggal' => $p['tanggal'],
                'unit_nama' => '-',
                'keterangan' => $p['keterangan'],
                'kategori' => $p['kategori'],
                'metode' => $p['metode'] ?? 'tunai',
                'jumlah' => $p['jumlah'],
                'created_at' => $p['created_at'] ?? $p['tanggal'],
                'referensi_tipe' => $p['referensi_tipe'] ?? null,
                'referensi_id' => $p['referensi_id'] ?? null,
            ];
        }

        usort($combined, function ($a, $b) {
            $d = strtotime($b['tanggal']) - strtotime($a['tanggal']);
            if ($d !== 0) return $d;
            $ta = strtotime($a['created_at'] ?? $a['tanggal']);
            $tb = strtotime($b['created_at'] ?? $b['tanggal']);
            if (!$ta) $ta = strtotime($a['tanggal']);
            if (!$tb) $tb = strtotime($b['tanggal']);
            return $tb - $ta;
        });

        // Filter by tanggal
        $filtered = [];
        $totalPemasukan = 0;
        $totalPengeluaran = 0;
        $pemasukanTunai = 0;
        $pemasukanTransfer = 0;
        $pengeluaranTunai = 0;
        $pengeluaranTransfer = 0;
        foreach ($combined as $t) {
            if (!$viewSemua && substr($t['tanggal'], 0, 10) !== $filterTanggal) continue;
            $filtered[] = $t;
            $metode = $t['metode'] ?? 'tunai';
                if ($t['tipe'] === 'pemasukan') {
                    $totalPemasukan += $t['jumlah'];
                    if ($metode === 'transfer') $pemasukanTransfer += $t['jumlah'];
                    else $pemasukanTunai += $t['jumlah'];
                } else {
                    $totalPengeluaran += $t['jumlah'];
                    if ($metode === 'transfer') $pengeluaranTransfer += $t['jumlah'];
                    else $pengeluaranTunai += $t['jumlah'];
                }
        }

        // Rekap per tanggal
        $rekapPerTanggal = [];
        foreach ($combined as $t) {
            $tDate = substr($t['tanggal'], 0, 10);
            if (!isset($rekapPerTanggal[$tDate])) {
                $rekapPerTanggal[$tDate] = ['pemasukan' => 0, 'pengeluaran' => 0, 'count' => 0, 'saldo' => 0];
            }
            if ($t['tipe'] === 'pemasukan') $rekapPerTanggal[$tDate]['pemasukan'] += $t['jumlah'];
            else $rekapPerTanggal[$tDate]['pengeluaran'] += $t['jumlah'];
            $rekapPerTanggal[$tDate]['count']++;
        }
        $running = 0;
        $tanggalAsc = array_keys($rekapPerTanggal);
        sort($tanggalAsc);
        foreach ($tanggalAsc as $d) {
            $running += $rekapPerTanggal[$d]['pemasukan'] - $rekapPerTanggal[$d]['pengeluaran'];
            $rekapPerTanggal[$d]['saldo'] = $running;
        }
        krsort($rekapPerTanggal);

        // Saldo kumulatif sampai tanggal terpilih
        $saldoKumulatif = 0;
        $saldoTunaiKumulatif = 0;
        $saldoTransferKumulatif = 0;
        foreach ($combined as $t) {
            if (!$viewSemua && substr($t['tanggal'], 0, 10) > $filterTanggal) continue;
            $metode = $t['metode'] ?? 'tunai';
            $net = $t['tipe'] === 'pemasukan' ? $t['jumlah'] : -$t['jumlah'];
            if ($metode === 'transfer') $saldoTransferKumulatif += $net;
            else $saldoTunaiKumulatif += $net;
            $saldoKumulatif += $net;
        }

        // Saldo tabungan semua unit
        $saldoTabungan = (new \App\Models\SavingsAccountModel())->getTotalSaldo();

        $data = [
            'activeMenu' => 'kas-yayasan',
            'transaksi' => $filtered,
            'allTransaksi' => $combined,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'pemasukanTunai' => $pemasukanTunai,
            'pemasukanTransfer' => $pemasukanTransfer,
            'pengeluaranTunai' => $pengeluaranTunai,
            'pengeluaranTransfer' => $pengeluaranTransfer,
            'saldo' => $totalPemasukan - $totalPengeluaran,
            'saldoTunai' => $pemasukanTunai - $pengeluaranTunai,
            'saldoTransfer' => $pemasukanTransfer - $pengeluaranTransfer,
            'saldoKumulatif' => $saldoKumulatif,
            'saldoTunaiKumulatif' => $saldoTunaiKumulatif,
            'saldoTransferKumulatif' => $saldoTransferKumulatif,
            'saldoTabungan' => $saldoTabungan,
            'unitList' => $unitModel->findAll(),
            'filterTanggal' => $filterTanggal,
            'viewSemua' => $viewSemua,
            'rekapPerTanggal' => $rekapPerTanggal,
            'totalTransaksi' => count($combined),
            'totalHari' => count($rekapPerTanggal),
        ];

        return $this->render('superadmin/kas_yayasan', $data);
    }

    public function tambah()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-yayasan');
        }

        $tipe = $this->request->getPost('tipe');

        if ($tipe === 'pemasukan') {
            $model = new PemasukanModel();
            $data = [
                'unit_id' => $this->request->getPost('unit_id'),
                'tanggal' => $this->request->getPost('tanggal'),
                'keterangan' => $this->request->getPost('keterangan'),
                'kategori' => $this->request->getPost('kategori') ?: 'Lainnya',
                'metode' => $this->request->getPost('metode') ?: 'tunai',
                'jumlah' => $this->request->getPost('jumlah'),
                'jenis' => 'pemasukan',
            ];
            if ($model->insert($data)) {
                session()->setFlashdata('success', 'Pemasukan berhasil ditambahkan.');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan pemasukan.');
            }
        } else {
            $model = new PengeluaranModel();
            $data = [
                'tanggal' => $this->request->getPost('tanggal'),
                'keterangan' => $this->request->getPost('keterangan'),
                'kategori' => $this->request->getPost('kategori') ?: 'Lainnya',
                'metode' => $this->request->getPost('metode') ?: 'tunai',
                'jumlah' => $this->request->getPost('jumlah'),
                'jenis' => 'pengeluaran',
            ];
            if ($model->insert($data)) {
                session()->setFlashdata('success', 'Pengeluaran berhasil ditambahkan.');
            } else {
                session()->setFlashdata('error', 'Gagal menambahkan pengeluaran.');
            }
        }

        return redirect()->to('/kas-yayasan');
    }

    public function edit($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-yayasan');
        }

        $tipe = $this->request->getPost('tipe');
        $isSuper = $this->userData['role'] === 'superadmin';

        if ($tipe === 'pemasukan') {
            $model = new PemasukanModel();
            $data = ['keterangan' => $this->request->getPost('keterangan')];
            if ($isSuper) {
                $data += [
                    'unit_id' => $this->request->getPost('unit_id'),
                    'tanggal' => $this->request->getPost('tanggal'),
                    'kategori' => $this->request->getPost('kategori') ?: 'Lainnya',
                    'metode' => $this->request->getPost('metode') ?: 'tunai',
                    'jumlah' => $this->request->getPost('jumlah'),
                ];
            }
            $model->update($id, $data);
            session()->setFlashdata('success', 'Pemasukan berhasil diupdate.');
        } else {
            $model = new PengeluaranModel();
            $data = ['keterangan' => $this->request->getPost('keterangan')];
            if ($isSuper) {
                $data += [
                    'tanggal' => $this->request->getPost('tanggal'),
                    'kategori' => $this->request->getPost('kategori') ?: 'Lainnya',
                    'metode' => $this->request->getPost('metode') ?: 'tunai',
                    'jumlah' => $this->request->getPost('jumlah'),
                ];
            }
            $model->update($id, $data);
            session()->setFlashdata('success', 'Pengeluaran berhasil diupdate.');
        }

        return redirect()->to('/kas-yayasan');
    }

    public function hapus($id)
    {
        $redirect = $this->redirectIfNotRole(['superadmin']);
        if ($redirect) return $redirect;

        $tipe = $this->request->getPost('tipe');
        $db = \Config\Database::connect();

        $row = $db->table('tb_kas_yayasan')->where('id', $id)->get()->getRowArray();

        $db->table('tb_kas_yayasan')->where('id', $id)->delete();

        $auditModel = new AuditLogModel();
        $auditModel->log('hapus_transaksi', 'tb_kas_yayasan', $id, $this->userData['id'] ?? null,
            "Tipe: {$tipe} | Keterangan: " . ($row['keterangan'] ?? '-') . " | Jumlah: " . ($row['jumlah'] ?? 0));

        $label = $tipe === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
        session()->setFlashdata('success', "{$label} berhasil dihapus.");
        return redirect()->to('/kas-yayasan');
    }

    public function getData($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $tipe = $this->request->getGet('tipe');

        if ($tipe === 'pemasukan') {
            $model = new PemasukanModel();
            $data = $model->find($id);
            if ($data) $data['tipe'] = 'pemasukan';
            return $this->response->setJSON($data);
        } else {
            $model = new PengeluaranModel();
            $data = $model->find($id);
            if ($data) $data['tipe'] = 'pengeluaran';
            return $this->response->setJSON($data);
        }
    }

    public function transferSaldo()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-yayasan');
        }

        $nominal = (float) $this->request->getPost('nominal');
        $tanggal = $this->request->getPost('tanggal') ?: date('Y-m-d');

        if ($nominal <= 0) {
            session()->setFlashdata('error', 'Nominal harus lebih dari 0.');
            return redirect()->to('/kas-yayasan');
        }

        $db = \Config\Database::connect();

        // Cek saldo transfer
        $saldoTransfer = $this->getSaldoTransfer($db);
        if ($saldoTransfer < $nominal) {
            session()->setFlashdata('error', 'Saldo transfer tidak mencukupi. Saldo transfer: Rp ' . number_format($saldoTransfer, 0, ',', '.'));
            return redirect()->to('/kas-yayasan');
        }

        try {
            $db->transBegin();

            // Pengeluaran: transfer keluar dari kas yayasan
            $db->table('tb_kas_yayasan')->insert([
                'unit_id' => null,
                'tanggal' => $tanggal,
                'keterangan' => 'Transfer ke Saldo Tunai',
                'kategori' => 'Transfer Saldo',
                'metode' => 'transfer',
                'jumlah' => $nominal,
                'jenis' => 'pengeluaran',
                'status_tutup' => 'tutup',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Pemasukan: masuk ke saldo tunai
            $db->table('tb_kas_yayasan')->insert([
                'unit_id' => null,
                'tanggal' => $tanggal,
                'keterangan' => 'Transfer dari Saldo Transfer',
                'kategori' => 'Transfer Saldo',
                'metode' => 'tunai',
                'jumlah' => $nominal,
                'jenis' => 'pemasukan',
                'status_tutup' => 'tutup',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();
            session()->setFlashdata('success', 'Transfer saldo berhasil. Rp ' . number_format($nominal, 0, ',', '.') . ' dipindahkan dari transfer ke tunai.');
        } catch (\Exception $e) {
            $db->transRollback();
            session()->setFlashdata('error', 'Gagal transfer saldo: ' . $e->getMessage());
        }

        return redirect()->to('/kas-yayasan');
    }

    public function getSaldo()
    {
        $db = \Config\Database::connect();
        $saldoTunai = $this->getSaldoTunai($db);
        $saldoTransfer = $this->getSaldoTransfer($db);

        return $this->response->setJSON([
            'saldo_tunai' => $saldoTunai,
            'saldo_transfer' => $saldoTransfer,
            'saldo_total' => $saldoTunai + $saldoTransfer,
        ]);
    }

    private function getSaldoTunai($db)
    {
        $pemasukan = $db->table('tb_kas_yayasan')
            ->selectSum('jumlah')
            ->where('jenis', 'pemasukan')
            ->where('metode', 'tunai')
            ->get()->getRowArray()['jumlah'] ?? 0;
        $pengeluaran = $db->table('tb_kas_yayasan')
            ->selectSum('jumlah')
            ->where('jenis', 'pengeluaran')
            ->where('metode', 'tunai')
            ->get()->getRowArray()['jumlah'] ?? 0;
        return $pemasukan - $pengeluaran;
    }

    private function getSaldoTransfer($db)
    {
        $pemasukan = $db->table('tb_kas_yayasan')
            ->selectSum('jumlah')
            ->where('jenis', 'pemasukan')
            ->where('metode', 'transfer')
            ->get()->getRowArray()['jumlah'] ?? 0;
        $pengeluaran = $db->table('tb_kas_yayasan')
            ->selectSum('jumlah')
            ->where('jenis', 'pengeluaran')
            ->where('metode', 'transfer')
            ->get()->getRowArray()['jumlah'] ?? 0;
        return $pemasukan - $pengeluaran;
    }
}
