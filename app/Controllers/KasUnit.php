<?php

namespace App\Controllers;

use App\Models\KasUnitModel;
use App\Models\PengajuanDanaModel;
use App\Models\UnitModel;
use App\Models\AuditLogModel;

class KasUnit extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['staff', 'kepala_sekolah', 'superadmin']);
        if ($redirect) return $redirect;

        $this->autoTutup();

        $kasUnitModel = new KasUnitModel();
        $unitModel = new UnitModel();

        $sekolah = $this->userData['sekolah'];
        $role = $this->userData['role'];
        $filterTanggal = $this->request->getGet('tanggal') ?: date('Y-m-d');
        $filterBulan = $this->request->getGet('bulan') ?: date('Y-m');
        $mode = $this->request->getGet('mode') ?: 'hari';
        if (!in_array($mode, ['hari', 'bulan'], true)) $mode = 'hari';

        if ($role === 'superadmin') {
            $allData = $kasUnitModel->getAllWithUnit();
            $unitList = $unitModel->findAll();

            // Filter by tanggal
            $filtered = [];
            $totalPemasukan = 0;
            $totalPengeluaran = 0;
            $pemasukanTunai = 0;
            $pemasukanTransfer = 0;
            $pengeluaranTunai = 0;
            $pengeluaranTransfer = 0;

            foreach ($allData as $d) {
                $t = substr($d['tanggal'], 0, 10);
                if ($t === $filterTanggal) {
                    $filtered[] = $d;
                    if ($d['status_tutup'] === 'belum') {
                        $metode = $d['metode'] ?? 'tunai';
                        if ($d['jenis'] === 'pemasukan') {
                            $totalPemasukan += $d['jumlah'];
                            if ($metode === 'transfer') $pemasukanTransfer += $d['jumlah'];
                            else $pemasukanTunai += $d['jumlah'];
                        } else {
                            $totalPengeluaran += $d['jumlah'];
                            if ($metode === 'transfer') $pengeluaranTransfer += $d['jumlah'];
                            else $pengeluaranTunai += $d['jumlah'];
                        }
                    }
                }
            }

            // Urutkan transaksi hari ini berdasarkan jam
            usort($filtered, function ($a, $b) {
                $ta = strtotime($a['created_at'] ?? $a['tanggal']);
                $tb = strtotime($b['created_at'] ?? $b['tanggal']);
                if (!$ta) $ta = strtotime($a['tanggal']);
                if (!$tb) $tb = strtotime($b['tanggal']);
                return $ta <=> $tb;
            });

            $bulanan = $this->buildBulanan($allData, $filterBulan);

            // Rekap per tanggal
            $rekapPerTanggal = [];
            foreach ($allData as $d) {
                $t = substr($d['tanggal'], 0, 10);
                if (!isset($rekapPerTanggal[$t])) {
                    $rekapPerTanggal[$t] = ['pemasukan' => 0, 'pengeluaran' => 0, 'tutup' => 0, 'belum' => 0];
                }
                if ($d['jenis'] === 'pemasukan') $rekapPerTanggal[$t]['pemasukan'] += $d['jumlah'];
                else $rekapPerTanggal[$t]['pengeluaran'] += $d['jumlah'];
                if ($d['status_tutup'] === 'tutup') $rekapPerTanggal[$t]['tutup']++;
                else $rekapPerTanggal[$t]['belum']++;
            }
            krsort($rekapPerTanggal);

            $data = [
                'activeMenu' => 'kas-unit',
                'transaksi' => $filtered,
                'mode' => $mode,
                'filteredBulan' => $bulanan['list'],
                'totalBulanPemasukan' => $bulanan['totalMasuk'],
                'totalBulanPengeluaran' => $bulanan['totalKeluar'],
                'totalPemasukan' => $totalPemasukan,
                'totalPengeluaran' => $totalPengeluaran,
                'pemasukanTunai' => $pemasukanTunai,
                'pemasukanTransfer' => $pemasukanTransfer,
                'pengeluaranTunai' => $pengeluaranTunai,
                'pengeluaranTransfer' => $pengeluaranTransfer,
                'saldo' => $totalPemasukan - $totalPengeluaran,
                'saldoTunai' => $pemasukanTunai - $pengeluaranTunai,
                'saldoTransfer' => $pemasukanTransfer - $pengeluaranTransfer,
                'unitList' => $unitList,
                'allUnit' => true,
                'unitId' => null,
                'role' => $role,
                'filterTanggal' => $filterTanggal,
                'filterBulan' => $filterBulan,
                'rekapPerTanggal' => $rekapPerTanggal,
                'totalTransaksi' => count($filtered),
                'totalHari' => count($rekapPerTanggal),
            ];
        } else {
            $db = \Config\Database::connect();
            $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) = LOWER(?) LIMIT 1", [$sekolah])->getRowArray();
            if (!$unit) {
                $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) LIKE LOWER(?) LIMIT 1", ['%' . $sekolah . '%'])->getRowArray();
            }
            $unitId = $unit['id'] ?? null;
            $unitNama = $unit['nama'] ?? '-';

            $transaksi = $unitId ? $kasUnitModel->getByUnit($unitId) : [];

            // Filter by tanggal
            $filtered = [];
            $totalPemasukan = 0;
            $totalPengeluaran = 0;
            $pemasukanTunai = 0;
            $pemasukanTransfer = 0;
            $pengeluaranTunai = 0;
            $pengeluaranTransfer = 0;

            foreach ($transaksi as $t) {
                $tDate = substr($t['tanggal'], 0, 10);
                if ($tDate === $filterTanggal) {
                    $filtered[] = $t;
                    if ($t['status_tutup'] === 'belum') {
                        $metode = $t['metode'] ?? 'tunai';
                        if ($t['jenis'] === 'pemasukan') {
                            $totalPemasukan += $t['jumlah'];
                            if ($metode === 'transfer') $pemasukanTransfer += $t['jumlah'];
                            else $pemasukanTunai += $t['jumlah'];
                        } else {
                            $totalPengeluaran += $t['jumlah'];
                            if ($metode === 'transfer') $pengeluaranTransfer += $t['jumlah'];
                            else $pengeluaranTunai += $t['jumlah'];
                        }
                    }
                }
            }

            // Urutkan transaksi hari ini berdasarkan jam
            usort($filtered, function ($a, $b) {
                $ta = strtotime($a['created_at'] ?? $a['tanggal']);
                $tb = strtotime($b['created_at'] ?? $b['tanggal']);
                if (!$ta) $ta = strtotime($a['tanggal']);
                if (!$tb) $tb = strtotime($b['tanggal']);
                return $ta <=> $tb;
            });

            $bulanan = $this->buildBulanan($transaksi, $filterBulan);

            // Rekap per tanggal
            $rekapPerTanggal = [];
            foreach ($transaksi as $t) {
                $tDate = substr($t['tanggal'], 0, 10);
                if (!isset($rekapPerTanggal[$tDate])) {
                    $rekapPerTanggal[$tDate] = ['pemasukan' => 0, 'pengeluaran' => 0, 'tutup' => 0, 'belum' => 0];
                }
                if ($t['jenis'] === 'pemasukan') $rekapPerTanggal[$tDate]['pemasukan'] += $t['jumlah'];
                else $rekapPerTanggal[$tDate]['pengeluaran'] += $t['jumlah'];
                if ($t['status_tutup'] === 'tutup') $rekapPerTanggal[$tDate]['tutup']++;
                else $rekapPerTanggal[$tDate]['belum']++;
            }
            krsort($rekapPerTanggal);

            $data = [
                'activeMenu' => 'kas-unit',
                'transaksi' => $filtered,
                'mode' => $mode,
                'filteredBulan' => $bulanan['list'],
                'totalBulanPemasukan' => $bulanan['totalMasuk'],
                'totalBulanPengeluaran' => $bulanan['totalKeluar'],
                'totalPemasukan' => $totalPemasukan,
                'totalPengeluaran' => $totalPengeluaran,
                'pemasukanTunai' => $pemasukanTunai,
                'pemasukanTransfer' => $pemasukanTransfer,
                'pengeluaranTunai' => $pengeluaranTunai,
                'pengeluaranTransfer' => $pengeluaranTransfer,
                'saldo' => $totalPemasukan - $totalPengeluaran,
                'saldoTunai' => $pemasukanTunai - $pengeluaranTunai,
                'saldoTransfer' => $pemasukanTransfer - $pengeluaranTransfer,
                'unitList' => $unitModel->findAll(),
                'unitId' => $unitId,
                'unitNama' => $unitNama,
                'allUnit' => false,
                'role' => $role,
                'filterTanggal' => $filterTanggal,
                'filterBulan' => $filterBulan,
                'rekapPerTanggal' => $rekapPerTanggal,
                'totalTransaksi' => count($filtered),
                'totalHari' => count($rekapPerTanggal),
            ];
        }

        return $this->render('kas_unit/index', $data);
    }

    public function tambah()
    {
        $redirect = $this->redirectIfNotRole(['staff', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit');
        }

        $model = new KasUnitModel();
        $sekolah = $this->userData['sekolah'];
        $role = $this->userData['role'];

        if ($role === 'superadmin') {
            $unitId = $this->request->getPost('unit_id');
        } else {
            $db = \Config\Database::connect();
            $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) = LOWER(?) LIMIT 1", [$sekolah])->getRowArray();
            if (!$unit) {
                $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) LIKE LOWER(?) LIMIT 1", ['%' . $sekolah . '%'])->getRowArray();
            }
            $unitId = $unit['id'] ?? null;
        }

        if (!$unitId) {
            session()->setFlashdata('error', 'Unit tidak ditemukan.');
            return redirect()->to('/kas-unit');
        }

        $jumlah = (float) $this->request->getPost('jumlah');
        $keterangan = $this->request->getPost('keterangan');
        $tanggal = $this->request->getPost('tanggal');
        $metode = $this->request->getPost('metode') ?: 'tunai';
        $tipe = $this->request->getPost('tipe');

        if ($tanggal < date('Y-m-d') && $model->where('unit_id', $unitId)->where('tanggal', $tanggal)->where('status_tutup', 'tutup')->countAllResults() > 0) {
            session()->setFlashdata('error', 'Tanggal ' . date('d M Y', strtotime($tanggal)) . ' sudah ditutup buku. Buka kembali terlebih dahulu untuk menambah transaksi pada tanggal tersebut.');
            return redirect()->to('/kas-unit');
        }

        $data = [
            'unit_id' => $unitId,
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
            'jumlah' => $jumlah,
            'jenis' => $tipe,
            'metode' => $metode,
            'status_tutup' => 'belum',
            'user_id' => $this->userData['id'],
        ];

        if ($model->insert($data)) {
            $label = $tipe === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
            session()->setFlashdata('success', "{$label} berhasil ditambahkan.");
        } else {
            session()->setFlashdata('error', 'Gagal menambahkan data.');
        }

        return redirect()->to('/kas-unit');
    }

    public function edit($id)
    {
        $redirect = $this->redirectIfNotRole(['staff', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit');
        }

        $model = new KasUnitModel();
        $role = $this->userData['role'];

        $kas = $model->find($id);
        if (!$kas) {
            session()->setFlashdata('error', 'Data tidak ditemukan.');
            return redirect()->to('/kas-unit');
        }

        if ($role === 'superadmin') {
            if ($kas['status_tutup'] === 'tutup') {
                session()->setFlashdata('error', 'Transaksi ini masih tertutup buku. Buka kembali (1 transaksi) terlebih dahulu untuk mengedit.');
                return redirect()->to('/kas-unit');
            }
        }

        if ($role === 'superadmin') {
            $data = [
                'tanggal' => $this->request->getPost('tanggal'),
                'keterangan' => $this->request->getPost('keterangan'),
                'jumlah' => $this->request->getPost('jumlah'),
                'metode' => $this->request->getPost('metode'),
            ];
        } else {
            $data = [
                'keterangan' => $this->request->getPost('keterangan'),
            ];
        }

        $model->update($id, $data);

        if (($kas['referensi_tipe'] ?? '') === 'pembayaran' && !empty($kas['referensi_id'])) {
            $sync = [];
            if ($role === 'superadmin' && isset($data['metode'])) {
                $sync['metode'] = $data['metode'];
            }
            if (!empty($sync)) {
                $db = \Config\Database::connect();
                $db->table('tb_pembayaran')->where('id', $kas['referensi_id'])->update($sync);
            }
        }

        session()->setFlashdata('success', 'Data berhasil diupdate.');
        return redirect()->to('/kas-unit');
    }

    public function hapus($id)
    {
        $redirect = $this->redirectIfNotRole(['superadmin']);
        if ($redirect) return $redirect;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $model = new KasUnitModel();
            $kas = $model->find($id);

            if (!$kas) {
                session()->setFlashdata('error', 'Data tidak ditemukan.');
                return redirect()->to('/kas-unit');
            }

            if ($kas['referensi_tipe'] === 'pembayaran' && $kas['referensi_id']) {
                $payment = $db->table('tb_pembayaran')->where('id', $kas['referensi_id'])->get()->getRowArray();
                if ($payment) {
                    $tagihanId = $payment['tagihan_id'];

                    $db->table('tb_pembayaran')->delete(['id' => $kas['referensi_id']]);

                    $sisaPembayaran = $db->table('tb_pembayaran')->where('tagihan_id', $tagihanId)->countAllResults();
                    if ($sisaPembayaran > 0) {
                        $jumlahCicilan = $db->table('tb_pembayaran')->selectSum('nominal_dibayar')->where('tagihan_id', $tagihanId)->get()->getRowArray();
                        $totalDibayar = $jumlahCicilan['nominal_dibayar'] ?? 0;
                        $tagihan = $db->table('tb_tagihan_siswa')->where('id', $tagihanId)->get()->getRowArray();
                        if ($tagihan && $tagihan['jenis_tagihan'] === 'Daftar Ulang') {
                            $db->table('tb_tagihan_siswa')->where('id', $tagihanId)->update(['status' => 'cicil']);
                        } else {
                            $db->table('tb_tagihan_siswa')->where('id', $tagihanId)->update(['status' => 'belum_bayar']);
                        }
                    } else {
                        $db->table('tb_tagihan_siswa')->where('id', $tagihanId)->update(['status' => 'belum_bayar']);
                    }
                }
            }

            if ($kas['referensi_tipe'] === 'transaksi_tabungan' && $kas['referensi_id']) {
                $tx = $db->table('tb_transaksi_tabungan')->where('id', $kas['referensi_id'])->get()->getRowArray();
                if ($tx) {
                    $akunId = (int) $tx['akun_id'];

                    // Hapus juga pengajuan dana terkait beserta entry "Dana dari Yayasan" (alur tarik)
                    $pengajuan = $db->table('tb_pengajuan_dana')
                        ->where('referensi_tipe', 'transaksi_tabungan')
                        ->where('referensi_id', $kas['referensi_id'])
                        ->get()->getRowArray();
                    if ($pengajuan) {
                        $db->table('tb_kas_unit')->where('referensi_tipe', 'pengajuan_dana')->where('referensi_id', $pengajuan['id'])->delete();
                        $db->table('tb_pengajuan_dana')->where('id', $pengajuan['id'])->delete();
                    }

                    $db->table('tb_transaksi_tabungan')->where('id', $kas['referensi_id'])->delete();
                    $db->table('tb_kas_yayasan')->where('referensi_tipe', 'tutup_buku')->where('referensi_id', (int) $id)->delete();

                    $rows = $db->query("SELECT * FROM tb_transaksi_tabungan WHERE akun_id = ? ORDER BY created_at ASC, id ASC", [$akunId])->getResultArray();

                    $saldoAkhir = 0;
                    if (!empty($rows)) {
                        $running = (float) $rows[0]['saldo_sebelum'];
                        foreach ($rows as $r) {
                            $saldoSebelum = $running;
                            $running += ($r['tipe'] === 'setor') ? (float) $r['nominal'] : -((float) $r['nominal']);
                            $db->table('tb_transaksi_tabungan')->where('id', $r['id'])->update([
                                'saldo_sebelum' => $saldoSebelum,
                                'saldo_sesudah' => $running,
                            ]);
                        }
                        $saldoAkhir = $running;
                    }
                    $db->table('tb_tabungan')->where('id', $akunId)->update(['saldo' => $saldoAkhir]);

                    (new AuditLogModel())->log('hapus_transaksi_tabungan', 'tb_transaksi_tabungan', $kas['referensi_id'], $this->userData['id'] ?? null,
                        "Transaksi id {$kas['referensi_id']} (kas unit id {$id}) dihapus, saldo rekening id {$akunId} dihitung ulang");
                }
            }

            $model->delete($id);
            $db->transCommit();
            session()->setFlashdata('success', 'Data berhasil dihapus dan status tagihan dikembalikan.');
        } catch (\Exception $e) {
            $db->transRollback();
            session()->setFlashdata('error', 'Gagal menghapus: ' . $e->getMessage());
        }

        return redirect()->to('/kas-unit');
    }

    public function getData($id)
    {
        $redirect = $this->redirectIfNotRole(['staff', 'kepala_sekolah', 'superadmin']);
        if ($redirect) return $redirect;

        $model = new KasUnitModel();
        return $this->response->setJSON($model->find($id));
    }

    public function tutupBuku()
    {
        $redirect = $this->redirectIfNotRole(['staff', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit');
        }

        $role = $this->userData['role'];
        $kasUnitModel = new KasUnitModel();
        $unitModel = new UnitModel();

        if ($role === 'superadmin') {
            $unitId = $this->request->getPost('unit_id');
            $unit = $unitModel->find($unitId);
        } else {
            $sekolah = $this->userData['sekolah'];
            $db = \Config\Database::connect();
            $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) = LOWER(?) LIMIT 1", [$sekolah])->getRowArray();
            if (!$unit) {
                $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) LIKE LOWER(?) LIMIT 1", ['%' . $sekolah . '%'])->getRowArray();
            }
            $unitId = $unit['id'] ?? null;
        }
        $tanggal = $this->request->getPost('tanggal');

        if (!$unitId || !$tanggal) {
            session()->setFlashdata('error', 'Data tidak lengkap.');
            return redirect()->to('/kas-unit');
        }

        $transaksi = $kasUnitModel->getRekapHarian($unitId, $tanggal);
        if (empty($transaksi)) {
            session()->setFlashdata('error', 'Tidak ada transaksi pada tanggal tersebut.');
            return redirect()->to('/kas-unit');
        }

        $totalPemasukan = 0;
        $totalPengeluaran = 0;
        $adaBelum = false;
        foreach ($transaksi as $t) {
            if ($t['status_tutup'] === 'belum') {
                $adaBelum = true;
                if ($t['jenis'] === 'pemasukan') $totalPemasukan += $t['jumlah'];
                else $totalPengeluaran += $t['jumlah'];
            }
        }

        if (!$adaBelum) {
            session()->setFlashdata('error', 'Semua transaksi tanggal tersebut sudah ditutup.');
            return redirect()->to('/kas-unit');
        }

        $sudahTutup = $kasUnitModel->where('unit_id', $unitId)->where('tanggal', $tanggal)->where('status_tutup', 'tutup')->countAllResults();
        if ($sudahTutup > 0) {
            $belumTutup = $kasUnitModel->where('unit_id', $unitId)->where('tanggal', $tanggal)->where('status_tutup', 'belum')->countAllResults();
            if ($belumTutup == 0) {
                session()->setFlashdata('error', 'Tutup buku untuk tanggal ini sudah dilakukan.');
                return redirect()->to('/kas-unit');
            }
        }

        $selisih = $totalPemasukan - $totalPengeluaran;

        $db = \Config\Database::connect();
        $auditModel = new AuditLogModel();

        try {
            $db->transBegin();

            // Tutup buku: pindahkan transaksi ke kas yayasan (per item, bukan selisih)
            foreach ($transaksi as $t) {
                if ($t['status_tutup'] === 'belum') {
                    $db->table('tb_kas_yayasan')->insert([
                        'unit_id' => $unitId,
                        'tanggal' => $tanggal,
                        'keterangan' => $t['keterangan'],
                        'kategori' => $t['kategori'],
                        'metode' => $t['metode'] ?? 'tunai',
                        'jumlah' => $t['jumlah'],
                        'jenis' => $t['jenis'],
                        'status_tutup' => 'tutup',
                        'referensi_tipe' => 'tutup_buku',
                        'referensi_id' => $t['id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            $kasUnitModel->markAsTutup($unitId, $tanggal);

            $auditModel->log('tutup_buku', 'tb_kas_unit', null, $this->userData['id'],
                "Unit: {$unit['nama']} | Tanggal: {$tanggal} | Pemasukan: {$totalPemasukan} | Pengeluaran: {$totalPengeluaran} | Selisih: {$selisih}");

            $db->transCommit();
        } catch (\Exception $e) {
            $db->transRollback();
            session()->setFlashdata('error', 'Gagal tutup buku: ' . $e->getMessage());
            return redirect()->to('/kas-unit');
        }

        session()->setFlashdata('success', "Tutup buku tanggal " . date('d/m/Y', strtotime($tanggal)) . " berhasil!");
        return redirect()->to('/kas-unit');
    }

    public function bukaKembali()
    {
        $redirect = $this->redirectIfNotRole(['superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit');
        }

        $unitId = $this->request->getPost('unit_id');
        $tanggal = $this->request->getPost('tanggal');

        if (!$unitId || !$tanggal) {
            session()->setFlashdata('error', 'Data tidak lengkap.');
            return redirect()->to('/kas-unit');
        }

        $db = \Config\Database::connect();
        $kasUnitModel = new KasUnitModel();
        $auditModel = new AuditLogModel();
        $unitModel = new UnitModel();

        $unit = $unitModel->find($unitId);

        try {
            $db->transBegin();

            // 1. Buka kembali status transaksi kas unit
            $db->table('tb_kas_unit')
                ->where('unit_id', $unitId)
                ->where('DATE(tanggal)', $tanggal)
                ->where('status_tutup', 'tutup')
                ->update(['status_tutup' => 'belum', 'reopened_at' => date('Y-m-d H:i:s')]);

            // 2. Hapus entri kas yayasan yang dihasilkan dari tutup buku ini
            $db->table('tb_kas_yayasan')
                ->where('unit_id', $unitId)
                ->where('DATE(tanggal)', $tanggal)
                ->where('status_tutup', 'tutup')
                ->delete();

            // 3. Audit log
            $auditModel->log('buka_kembali_tutup_buku', 'tb_kas_unit', null, $this->userData['id'],
                "Unit: " . ($unit['nama'] ?? '-') . " | Tanggal: {$tanggal}");

            $db->transCommit();
        } catch (\Exception $e) {
            $db->transRollback();
            session()->setFlashdata('error', 'Gagal buka kembali: ' . $e->getMessage());
            return redirect()->to('/kas-unit');
        }

        session()->setFlashdata('success', "Tutup buku tanggal " . date('d/m/Y', strtotime($tanggal)) . " berhasil dibuka kembali.");
        return redirect()->to('/kas-unit');
    }

    public function bukaKembaliSatu($id)
    {
        $redirect = $this->redirectIfNotRole(['superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit');
        }

        $db = \Config\Database::connect();
        $kas = $db->table('tb_kas_unit')->where('id', $id)->get()->getRowArray();
        if (!$kas) {
            session()->setFlashdata('error', 'Data tidak ditemukan.');
            return redirect()->to('/kas-unit');
        }
        if ($kas['status_tutup'] !== 'tutup') {
            session()->setFlashdata('error', 'Transaksi ini belum ditutup buku.');
            return redirect()->to('/kas-unit');
        }

        try {
            $db->transBegin();

            $db->table('tb_kas_yayasan')
                ->where('referensi_tipe', 'tutup_buku')
                ->where('referensi_id', $id)
                ->delete();

            $db->table('tb_kas_unit')
                ->where('id', $id)
                ->update(['status_tutup' => 'belum', 'reopened_at' => date('Y-m-d H:i:s')]);

            $auditModel = new AuditLogModel();
            $auditModel->log('buka_kembali_satu', 'tb_kas_unit', $id, $this->userData['id'],
                "Keterangan: {$kas['keterangan']} | Jumlah: {$kas['jumlah']}");

            $db->transCommit();
        } catch (\Exception $e) {
            $db->transRollback();
            session()->setFlashdata('error', 'Gagal buka kembali: ' . $e->getMessage());
            return redirect()->to('/kas-unit');
        }

        session()->setFlashdata('success', '1 transaksi dibuka kembali. Perbaiki lalu tutup buku lagi.');
        return redirect()->to('/kas-unit');
    }

    public function getRekapHarian()
    {
        $redirect = $this->redirectIfNotRole(['staff', 'kepala_sekolah', 'superadmin']);
        if ($redirect) return $redirect;

        $unitId = $this->request->getGet('unit_id');
        $tanggal = $this->request->getGet('tanggal');

        $kasUnitModel = new KasUnitModel();
        $transaksi = $kasUnitModel->getRekapHarian($unitId, $tanggal);

        $totalPemasukan = 0;
        $totalPengeluaran = 0;
        foreach ($transaksi as $t) {
            if ($t['status_tutup'] === 'belum') {
                if ($t['jenis'] === 'pemasukan') $totalPemasukan += $t['jumlah'];
                else $totalPengeluaran += $t['jumlah'];
            }
        }

        return $this->response->setJSON([
            'transaksi' => $transaksi,
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
            'selisih' => $totalPemasukan - $totalPengeluaran,
        ]);
    }

    public function ajukanDana()
    {
        $redirect = $this->redirectIfNotRole(['staff', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit');
        }

        $model = new PengajuanDanaModel();
        $unitModel = new UnitModel();

        $role = $this->userData['role'];

        if ($role === 'superadmin') {
            $unitId = $this->request->getPost('unit_id');
        } else {
            $sekolah = $this->userData['sekolah'];
            $db = \Config\Database::connect();
            $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) = LOWER(?) LIMIT 1", [$sekolah])->getRowArray();
            if (!$unit) {
                $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) LIKE LOWER(?) LIMIT 1", ['%' . $sekolah . '%'])->getRowArray();
            }
            $unitId = $unit['id'] ?? null;
        }

        if (!$unitId) {
            session()->setFlashdata('error', 'Unit tidak ditemukan.');
            return redirect()->to('/kas-unit');
        }

        $data = [
            'unit_id' => $unitId,
            'user_id' => $this->userData['id'],
            'tanggal' => date('Y-m-d'),
            'keterangan' => $this->request->getPost('keterangan'),
            'jumlah' => $this->request->getPost('jumlah'),
            'status' => 'pending',
        ];

        if ($model->insert($data)) {
            session()->setFlashdata('success', 'Pengajuan dana berhasil dikirim. Menunggu persetujuan yayasan.');
        } else {
            session()->setFlashdata('error', 'Gagal mengajukan dana.');
        }

        return redirect()->to('/kas-unit');
    }

    public function rekap()
    {
        $redirect = $this->redirectIfNotRole(['staff', 'kepala_sekolah', 'superadmin']);
        if ($redirect) return $redirect;

        $db = \Config\Database::connect();
        $sekolah = $this->userData['sekolah'];
        $role = $this->userData['role'];
        $filterTanggal = $this->request->getGet('tanggal') ?: '';
        $filterMetode = $this->request->getGet('metode') ?: '';
        $filterJenis = $this->request->getGet('jenis') ?: '';

        $query = $db->table('tb_pembayaran p')
            ->select('p.*, ts.jenis_tagihan, ts.sekolah as tk_sekolah, ts.nominal as tagihan_nominal, s.nama as nama_siswa, k.nama_kelas')
            ->join('tb_tagihan_siswa ts', 'ts.id = p.tagihan_id')
            ->join('tb_siswa s', 's.id = ts.siswa_id', 'left')
            ->join('tb_kelas k', 'k.id = s.kelas_id', 'left')
            ->orderBy('p.created_at', 'DESC');

        if ($role !== 'superadmin') {
            $query->where('ts.sekolah', $sekolah);
        }
        if ($filterTanggal) {
            $query->where('DATE(p.created_at)', $filterTanggal);
        }
        if ($filterMetode) {
            $query->where('p.metode', $filterMetode);
        }
        if ($filterJenis) {
            $query->where('ts.jenis_tagihan', $filterJenis);
        }

        $pembayaran = $query->get()->getResultArray();

        $rekap = [];
        $grandTotal = 0;
        $totalPerMetode = ['tunai' => 0, 'transfer' => 0];

        foreach ($pembayaran as $p) {
            $jenis = $p['jenis_tagihan'];
            $metode = $p['metode'];
            $nominal = (float) $p['nominal_dibayar'];

            if (!isset($rekap[$jenis])) {
                $rekap[$jenis] = ['tunai' => 0, 'transfer' => 0, 'total' => 0, 'count' => 0];
            }
            $rekap[$jenis][$metode] = ($rekap[$jenis][$metode] ?? 0) + $nominal;
            $rekap[$jenis]['total'] += $nominal;
            $rekap[$jenis]['count']++;
            $grandTotal += $nominal;
            if (isset($totalPerMetode[$metode])) {
                $totalPerMetode[$metode] += $nominal;
            }
        }

        uasort($rekap, fn($a, $b) => $b['total'] - $a['total']);

        $data = [
            'activeMenu' => 'kas-unit-rekap',
            'pembayaran' => $pembayaran,
            'rekap' => $rekap,
            'grandTotal' => $grandTotal,
            'totalPerMetode' => $totalPerMetode,
            'filterTanggal' => $filterTanggal,
            'filterMetode' => $filterMetode,
            'filterJenis' => $filterJenis,
        ];

        return $this->render('kas_unit/rekap', $data);
    }

    public function pengajuan()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $model = new PengajuanDanaModel();
        $filterStatus = $this->request->getGet('status') ?: '';

        $filters = [];
        if ($filterStatus) $filters['status'] = $filterStatus;

        $data = [
            'activeMenu' => 'kas-unit-pengajuan',
            'pengajuan' => $model->getAllWithUnit($filters),
            'filterStatus' => $filterStatus,
        ];

        return $this->render('kas_unit/pengajuan', $data);
    }

    public function setujuiPengajuan($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit/pengajuan');
        }

        $model = new PengajuanDanaModel();
        $pengajuan = $model->find($id);
        if (!$pengajuan) {
            session()->setFlashdata('error', 'Pengajuan tidak ditemukan.');
            return redirect()->to('/kas-unit/pengajuan');
        }

        if ($pengajuan['status'] !== 'pending') {
            session()->setFlashdata('error', 'Pengajuan ini sudah diproses.');
            return redirect()->to('/kas-unit/pengajuan');
        }

        $db = \Config\Database::connect();
        $kasUnitModel = new KasUnitModel();

        try {
            $db->transBegin();

            $model->update($id, [
                'status' => 'disetujui',
                'approved_by' => $this->userData['id'],
            ]);

            // Pengajuan dari penarikan tabungan: dana masuk dari yayasan + penarikan dicatat sebagai pengeluaran
            if (($pengajuan['referensi_tipe'] ?? '') === 'transaksi_tabungan' && !empty($pengajuan['referensi_id'])) {
                $tx = $db->table('tb_transaksi_tabungan')->where('id', $pengajuan['referensi_id'])->get()->getRowArray();
                if (!$tx) {
                    throw new \Exception('Transaksi tabungan terkait tidak ditemukan.');
                }

                // Cegah double posting jika pengajuan ini sudah pernah diproses
                $sudahPosting = $db->table('tb_kas_unit')
                    ->where('referensi_tipe', 'transaksi_tabungan')
                    ->where('referensi_id', $pengajuan['referensi_id'])
                    ->countAllResults();
                if ($sudahPosting > 0) {
                    throw new \Exception('Penarikan ini sudah pernah diposting ke kas unit.');
                }

                $tanggalPosting = date('Y-m-d', strtotime($tx['created_at']));
                $metode = $tx['metode'] ?? 'tunai';

                // 1. Dana dari yayasan masuk ke kas unit (pemasukan)
                $kasUnitModel->insert([
                    'unit_id' => $pengajuan['unit_id'],
                    'tanggal' => $tanggalPosting,
                    'keterangan' => 'Dana dari Yayasan - ' . $pengajuan['keterangan'],
                    'kategori' => 'Dana dari Yayasan',
                    'jumlah' => $pengajuan['jumlah'],
                    'jenis' => 'pemasukan',
                    'metode' => $metode,
                    'status_tutup' => 'belum',
                    'user_id' => $pengajuan['user_id'],
                    'referensi_id' => $id,
                    'referensi_tipe' => 'pengajuan_dana',
                ]);

                // 2. Penarikan tabungan dicatat sebagai pengeluaran kas unit
                $kasUnitModel->insert([
                    'unit_id' => $pengajuan['unit_id'],
                    'tanggal' => $tanggalPosting,
                    'keterangan' => $pengajuan['keterangan'],
                    'kategori' => 'Tarik Tabungan',
                    'jumlah' => $pengajuan['jumlah'],
                    'jenis' => 'pengeluaran',
                    'metode' => $metode,
                    'status_tutup' => 'belum',
                    'user_id' => $pengajuan['user_id'],
                    'referensi_id' => $pengajuan['referensi_id'],
                    'referensi_tipe' => 'transaksi_tabungan',
                ]);

                $successMsg = 'Pengajuan dana disetujui. Dana dari yayasan masuk ke Kas Unit dan penarikan tabungan tercatat sebagai pengeluaran.';
            } else {
                // Pengajuan dana biasa: dana langsung masuk ke kas unit
                $kasUnitModel->insert([
                    'unit_id' => $pengajuan['unit_id'],
                    'tanggal' => date('Y-m-d'),
                    'keterangan' => 'Pengajuan dana disetujui: ' . $pengajuan['keterangan'],
                    'kategori' => 'Pengajuan Dana',
                    'jumlah' => $pengajuan['jumlah'],
                    'jenis' => 'pemasukan',
                    'metode' => 'tunai',
                    'status_tutup' => 'belum',
                    'user_id' => $pengajuan['user_id'],
                    'referensi_id' => $id,
                    'referensi_tipe' => 'pengajuan_dana',
                ]);

                $successMsg = 'Pengajuan dana disetujui. Dana otomatis masuk ke Kas Unit.';
            }

            $db->transCommit();
        } catch (\Exception $e) {
            $db->transRollback();
            session()->setFlashdata('error', 'Gagal menyetujui pengajuan: ' . $e->getMessage());
            return redirect()->to('/kas-unit/pengajuan');
        }

        session()->setFlashdata('success', $successMsg);
        return redirect()->to('/kas-unit/pengajuan');
    }

    public function tolakPengajuan($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/kas-unit/pengajuan');
        }

        $alasan = $this->request->getPost('alasan_tolak');
        if (!$alasan) {
            session()->setFlashdata('error', 'Alasan penolakan harus diisi.');
            return redirect()->to('/kas-unit/pengajuan');
        }

        $model = new PengajuanDanaModel();
        $pengajuan = $model->find($id);
        if (!$pengajuan) {
            session()->setFlashdata('error', 'Pengajuan tidak ditemukan.');
            return redirect()->to('/kas-unit/pengajuan');
        }

        if ($pengajuan['status'] !== 'pending') {
            session()->setFlashdata('error', 'Pengajuan ini sudah diproses.');
            return redirect()->to('/kas-unit/pengajuan');
        }

        $db = \Config\Database::connect();
        $msg = 'Pengajuan dana ditolak.';

        try {
            $db->transBegin();

            $model->update($id, [
                'status' => 'ditolak',
                'alasan_tolak' => $alasan,
                'approved_by' => $this->userData['id'],
            ]);

            // Jika pengajuan berasal dari penarikan tabungan, batalkan penarikan dan kembalikan saldo rekening
            if (($pengajuan['referensi_tipe'] ?? '') === 'transaksi_tabungan' && !empty($pengajuan['referensi_id'])) {
                $txId = (int) $pengajuan['referensi_id'];
                $tx = $db->table('tb_transaksi_tabungan')->where('id', $txId)->get()->getRowArray();
                if ($tx) {
                    $akunId = (int) $tx['akun_id'];

                    $db->table('tb_transaksi_tabungan')->where('id', $txId)->delete();

                    $rows = $db->query("SELECT * FROM tb_transaksi_tabungan WHERE akun_id = ? ORDER BY created_at ASC, id ASC", [$akunId])->getResultArray();

                    $saldoAkhir = 0;
                    if (!empty($rows)) {
                        $running = (float) $rows[0]['saldo_sebelum'];
                        foreach ($rows as $r) {
                            $saldoSebelum = $running;
                            $running += ($r['tipe'] === 'setor') ? (float) $r['nominal'] : -((float) $r['nominal']);
                            $db->table('tb_transaksi_tabungan')->where('id', $r['id'])->update([
                                'saldo_sebelum' => $saldoSebelum,
                                'saldo_sesudah' => $running,
                            ]);
                        }
                        $saldoAkhir = $running;
                    }
                    $db->table('tb_tabungan')->where('id', $akunId)->update(['saldo' => $saldoAkhir]);

                    (new AuditLogModel())->log('batalkan_penarikan_tabungan', 'tb_transaksi_tabungan', $txId, $this->userData['id'],
                        "Pengajuan dana id {$id} ditolak, penarikan id {$txId} dibatalkan, saldo rekening id {$akunId} dihitung ulang");

                    $msg = 'Pengajuan dana ditolak. Penarikan tabungan dibatalkan dan saldo rekening dikembalikan.';
                }
            }

            $db->transCommit();
        } catch (\Exception $e) {
            $db->transRollback();
            session()->setFlashdata('error', 'Gagal menolak pengajuan: ' . $e->getMessage());
            return redirect()->to('/kas-unit/pengajuan');
        }

        session()->setFlashdata('success', $msg);
        return redirect()->to('/kas-unit/pengajuan');
    }

    public function exportExcel()
    {
        $redirect = $this->redirectIfNotRole(['staff', 'kepala_sekolah', 'superadmin']);
        if ($redirect) return $redirect;

        $db = \Config\Database::connect();
        $sekolah = $this->userData['sekolah'];
        $role = $this->userData['role'];
        $filterTanggal = $this->request->getGet('tanggal') ?: '';
        $filterMetode = $this->request->getGet('metode') ?: '';
        $filterJenis = $this->request->getGet('jenis') ?: '';

        $query = $db->table('tb_pembayaran p')
            ->select('p.*, ts.jenis_tagihan, ts.sekolah as tk_sekolah, ts.nominal as tagihan_nominal, s.nama as nama_siswa, k.nama_kelas')
            ->join('tb_tagihan_siswa ts', 'ts.id = p.tagihan_id')
            ->join('tb_siswa s', 's.id = ts.siswa_id', 'left')
            ->join('tb_kelas k', 'k.id = s.kelas_id', 'left')
            ->orderBy('p.created_at', 'DESC');

        if ($role !== 'superadmin') {
            $query->where('ts.sekolah', $sekolah);
        }
        if ($filterTanggal) $query->where('DATE(p.created_at)', $filterTanggal);
        if ($filterMetode) $query->where('p.metode', $filterMetode);
        if ($filterJenis) $query->where('ts.jenis_tagihan', $filterJenis);

        $pembayaran = $query->get()->getResultArray();

        $rekap = [];
        $grandTotal = 0;
        $totalPerMetode = ['tunai' => 0, 'transfer' => 0];
        foreach ($pembayaran as $p) {
            $jenis = $p['jenis_tagihan'];
            $metode = $p['metode'];
            $nominal = (float) $p['nominal_dibayar'];
            if (!isset($rekap[$jenis])) $rekap[$jenis] = ['tunai' => 0, 'transfer' => 0, 'total' => 0, 'count' => 0];
            $rekap[$jenis][$metode] = ($rekap[$jenis][$metode] ?? 0) + $nominal;
            $rekap[$jenis]['total'] += $nominal;
            $rekap[$jenis]['count']++;
            $grandTotal += $nominal;
            if (isset($totalPerMetode[$metode])) $totalPerMetode[$metode] += $nominal;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Tagihan');

        $titleFont = ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]];
        $headerFont = ['font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $dataFont = ['font' => ['bold' => false, 'size' => 10], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $totalFont = ['font' => ['bold' => true, 'size' => 10], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'REKAP TAGIHAN' . ($filterTanggal ? ' - ' . $filterTanggal : ''));
        $sheet->getStyle('A1')->applyFromArray($titleFont);
        $sheet->getRowDimension('1')->setRowHeight(35);

        $row = 3;
        $sheet->setCellValue('A' . $row, 'Total Tunai: Rp ' . number_format($totalPerMetode['tunai'], 0, ',', '.'));
        $sheet->setCellValue('C' . $row, 'Total Transfer: Rp ' . number_format($totalPerMetode['transfer'], 0, ',', '.'));
        $sheet->setCellValue('E' . $row, 'Grand Total: Rp ' . number_format($grandTotal, 0, ',', '.'));
        $sheet->getStyle('A' . $row)->applyFromArray($totalFont);
        $sheet->getStyle('C' . $row)->applyFromArray($totalFont);
        $sheet->getStyle('E' . $row)->applyFromArray($totalFont);
        $row += 2;

        if (!empty($rekap)) {
            $sheet->setCellValue('A' . $row, 'REKAP PER JENIS TAGIHAN');
            $sheet->getStyle('A' . $row)->applyFromArray($titleFont);
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $row++;

            $rh = ['Jenis Tagihan', 'Tunai', 'Transfer', 'Total', 'Jumlah'];
            $rc = ['A', 'B', 'C', 'D', 'E'];
            foreach ($rh as $i => $h) {
                $sheet->getColumnDimension($rc[$i])->setWidth(18);
                $sheet->setCellValue($rc[$i] . $row, $h);
                $sheet->getStyle($rc[$i] . $row)->applyFromArray($headerFont);
            }
            $row++;

            foreach ($rekap as $jenis => $r) {
                $sheet->setCellValue('A' . $row, $jenis);
                $sheet->setCellValue('B' . $row, $r['tunai']);
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setCellValue('C' . $row, $r['transfer']);
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setCellValue('D' . $row, $r['total']);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setCellValue('E' . $row, $r['count'] . 'x');
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataFont);
                $row++;
            }
            $row += 2;
        }

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $widths = [18, 14, 24, 18, 16, 18, 14];
        $headers = ['Tanggal', 'Siswa', 'Kelas', 'Tagihan', 'Metode', 'Nominal', 'No. Kwitansi'];
        foreach ($headers as $i => $h) {
            $sheet->getColumnDimension($cols[$i])->setWidth($widths[$i]);
            $sheet->setCellValue($cols[$i] . $row, $h);
            $sheet->getStyle($cols[$i] . $row)->applyFromArray($headerFont);
        }
        $row++;

        foreach ($pembayaran as $p) {
            $nominal = (float) $p['nominal_dibayar'];
            $sheet->setCellValue('A' . $row, date('d/m/Y H:i', strtotime($p['created_at'])));
            $sheet->setCellValue('B' . $row, $p['nama_siswa'] ?? '-');
            $sheet->setCellValue('C' . $row, $p['nama_kelas'] ?? '-');
            $sheet->setCellValue('D' . $row, $p['jenis_tagihan']);
            $sheet->setCellValue('E' . $row, ucfirst($p['metode']));
            $sheet->setCellValue('F' . $row, $nominal);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->setCellValue('G' . $row, $p['no_kwitansi'] ?? '-');
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataFont);
            $row++;
        }

        if ($grandTotal > 0) {
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->setCellValue('F' . $row, $grandTotal);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($totalFont);
        }

        $filename = 'REKAP_TAGIHAN' . ($filterTanggal ? '_' . $filterTanggal : '') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }

    private function buildBulanan(array $rows, string $bulan): array
    {
        $rows = array_values(array_filter($rows, function ($d) use ($bulan) {
            return substr((string)($d['tanggal'] ?? ''), 0, 7) === $bulan;
        }));

        usort($rows, function ($a, $b) {
            $ta = strtotime($a['created_at'] ?? $a['tanggal']);
            $tb = strtotime($b['created_at'] ?? $b['tanggal']);
            if (!$ta) $ta = strtotime($a['tanggal']);
            if (!$tb) $tb = strtotime($b['tanggal']);
            return $ta <=> $tb;
        });

        $running = 0.0;
        $totalMasuk = 0.0;
        $totalKeluar = 0.0;
        foreach ($rows as &$d) {
            if ($d['status_tutup'] === 'belum') {
                if ($d['jenis'] === 'pemasukan') {
                    $running += (float) $d['jumlah'];
                    $totalMasuk += (float) $d['jumlah'];
                } else {
                    $running -= (float) $d['jumlah'];
                    $totalKeluar += (float) $d['jumlah'];
                }
            }
            $d['saldo_berjalan'] = $running;
        }
        unset($d);

        return ['list' => $rows, 'totalMasuk' => $totalMasuk, 'totalKeluar' => $totalKeluar];
    }
}
