<?php

namespace App\Controllers;

use App\Models\SavingsAccountModel;
use App\Models\SavingsTransactionModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\ClassModel;
use App\Models\NasabahModel;
use App\Models\AuditLogModel;

class Tabungan extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        $sekolah = $this->userData['sekolah'] ?? 'admin';
        $savingsModel = new SavingsAccountModel();
        $classModel = new ClassModel();
        $siswaModel = new StudentModel();
        $guruModel = new TeacherModel();

        $filters = [];
        $kelasId = $this->request->getGet('kelas');
        $search = $this->request->getGet('search');
        $bidang = $this->request->getGet('bidang');
        if ($kelasId) $filters['kelas_id'] = $kelasId;
        if ($search) $filters['search'] = $search;
        if ($bidang) $filters['bidang'] = $bidang;

        // For "Tambah Rekening" dropdowns: get students/teachers without accounts
        $allSiswa = $siswaModel->getWithClass($sekolah);
        $allGuru = $guruModel->getByActiveTa($sekolah);
        $nasabahModel = new NasabahModel();
        if ($sekolah && $sekolah !== 'admin') {
            $allNasabah = $nasabahModel->where('sekolah', $sekolah)->orderBy('nama', 'ASC')->findAll();
        } else {
            $allNasabah = $nasabahModel->orderBy('nama', 'ASC')->findAll();
        }

        $db = \Config\Database::connect();
        $kasPerUnit = [];
        foreach ($db->query("SELECT unit_id, COALESCE(SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE -jumlah END), 0) AS saldo FROM tb_kas_unit WHERE status_tutup = 'belum' GROUP BY unit_id")->getResultArray() as $r) {
            $kasPerUnit[(int) $r['unit_id']] = (float) $r['saldo'];
        }
        $unitIdByNama = [];
        foreach ($db->table('tb_unit')->select('id, LOWER(nama) as nama_lower')->get()->getResultArray() as $u) {
            $unitIdByNama[$u['nama_lower']] = (int) $u['id'];
        }

        $data = [
            'title' => 'Tabungan Guru & Siswa',
            'studentAccounts' => $savingsModel->getStudentAccounts($sekolah, $filters),
            'teacherAccounts' => $savingsModel->getTeacherAccounts($sekolah, $filters),
            'nasabahAccounts' => $savingsModel->getNasabahAccounts($sekolah, $filters),
            'totalSaldo' => $savingsModel->getTotalSaldo($sekolah),
            'kasPerUnit' => $kasPerUnit,
            'unitIdByNama' => $unitIdByNama,
            'classes' => $classModel->getFiltered($sekolah),
            'allSiswa' => $allSiswa,
            'allGuru' => $allGuru,
            'allNasabah' => $allNasabah,
            'kelas' => $kelasId ?? '',
            'search' => $search ?? '',
            'bidang' => $bidang ?? '',
        ];

        return $this->render('tabungan/index', $data);
    }

    public function transaksi()
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        $rules = [
            'akun_id' => 'required|is_not_unique[tb_tabungan.id]',
            'tipe' => 'required|in_list[setor,tarik]',
            'nominal' => 'required',
        ];

        $tab = $this->request->getPost('tab') ?: 'siswa';
        $search = $this->request->getPost('search');
        $kelas = $this->request->getPost('kelas');
        $bidang = $this->request->getPost('bidang');
        $redirectUrl = '/tabungan?tab=' . $tab;
        if ($search) $redirectUrl .= '&search=' . urlencode($search);
        if ($kelas) $redirectUrl .= '&kelas=' . urlencode($kelas);
        if ($bidang) $redirectUrl .= '&bidang=' . urlencode($bidang);

        if (!$this->validate($rules)) {
            $msg = implode(' ', $this->validator->getErrors());
            if ($this->request->getPost('akun_id')) {
                session()->setFlashdata('scroll_to_akun', $this->request->getPost('akun_id'));
                return redirect()->to($redirectUrl)->with('error', $msg);
            }
            return redirect()->to($redirectUrl)->with('error', $msg);
        }

        $akunId = $this->request->getPost('akun_id');
        $tipe = $this->request->getPost('tipe');
        $nominal = preg_replace('/[^0-9.,]/', '', $this->request->getPost('nominal'));
        $nominal = (float) str_replace(',', '.', str_replace('.', '', $nominal));

        if ($nominal <= 0) {
            session()->setFlashdata('scroll_to_akun', $akunId);
            return redirect()->to($redirectUrl)->with('error', 'Nominal harus lebih dari 0.');
        }
        $catatan = $this->request->getPost('catatan');
        $metode = $this->request->getPost('metode') ?: 'tunai';
        $tglTransaksi = $this->request->getPost('tgl_transaksi');

        $savingsModel = new SavingsAccountModel();
        $akun = $savingsModel->find($akunId);

        if (!$akun) {
            session()->setFlashdata('scroll_to_akun', $akunId);
            return redirect()->to($redirectUrl)->with('error', 'Akun tidak ditemukan.');
        }

        if ($tipe === 'tarik' && $akun['saldo'] < $nominal) {
            session()->setFlashdata('scroll_to_akun', $akunId);
            return redirect()->to($redirectUrl)->with('error', 'Saldo tidak mencukupi.');
        }

        $saldoSebelum = $akun['saldo'];
        $saldoSesudah = ($tipe === 'setor') ? $saldoSebelum + $nominal : $saldoSebelum - $nominal;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $savingsModel->update($akunId, ['saldo' => $saldoSesudah]);

            $txModel = new SavingsTransactionModel();
            $txModel->insert([
                'akun_id' => $akunId,
                'tipe' => $tipe,
                'nominal' => $nominal,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'catatan' => $catatan,
                'metode' => $metode,
                'user_id' => $this->userData['id'],
                'created_at' => $tglTransaksi ?: date('Y-m-d H:i:s'),
            ]);
            $txId = $db->insertID();

            $akunSekolah = $akun['sekolah'] ?? '';
            $successMsg = 'Transaksi berhasil dicatat.';
            if ($akunSekolah) {
                $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) = LOWER(?) LIMIT 1", [$akunSekolah])->getRowArray();
                if (!$unit) {
                    $unit = $db->query("SELECT * FROM tb_unit WHERE LOWER(nama) LIKE LOWER(?) LIMIT 1", ['%' . $akunSekolah . '%'])->getRowArray();
                }
                if ($unit) {
                    $pemilikNama = '';
                    if ($akun['tipe'] === 'siswa' && $akun['siswa_id']) {
                        $siswa = $db->table('tb_siswa')->where('id', $akun['siswa_id'])->get()->getRowArray();
                        if ($siswa) $pemilikNama = $siswa['nama'] ?? '';
                    } elseif ($akun['tipe'] === 'guru' && $akun['guru_id']) {
                        $guru = $db->table('tb_guru')->where('id', $akun['guru_id'])->get()->getRowArray();
                        if ($guru) $pemilikNama = $guru['nama'] ?? '';
                    } elseif ($akun['tipe'] === 'nasabah' && $akun['nasabah_id']) {
                        $nasabah = $db->table('tb_nasabah')->where('id', $akun['nasabah_id'])->get()->getRowArray();
                        if ($nasabah) $pemilikNama = $nasabah['nama'] ?? '';
                    }
                    $keterangan = ($tipe === 'setor' ? 'Setor Tabungan' : 'Tarik Tabungan') . ' - ' . $pemilikNama . ($catatan ? ' (' . $catatan . ')' : '');

                    if ($tipe === 'setor') {
                        $db->table('tb_kas_unit')->insert([
                            'unit_id' => $unit['id'],
                            'tanggal' => $tglTransaksi ?: date('Y-m-d'),
                            'keterangan' => $keterangan,
                            'kategori' => 'Setor Tabungan',
                            'jumlah' => $nominal,
                            'jenis' => 'pemasukan',
                            'metode' => $metode,
                            'status_tutup' => 'belum',
                            'user_id' => $this->userData['id'],
                            'referensi_id' => $txId,
                            'referensi_tipe' => 'transaksi_tabungan',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    } else {
                        // Tarik: jika kas unit tersedia cukup, dicatat langsung sebagai pengeluaran kas unit.
                        // Jika kas tidak mencukupi, dibuat pengajuan dana ke yayasan; setelah disetujui,
                        // dana masuk ke kas unit (pemasukan) lalu penarikan dicatat (pengeluaran).
                        $kasTersedia = (float) $db->query(
                            "SELECT COALESCE(SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE -jumlah END), 0) AS saldo
                               FROM tb_kas_unit
                              WHERE unit_id = ? AND status_tutup = 'belum'",
                            [$unit['id']]
                        )->getRowArray()['saldo'];

                        if ($kasTersedia >= $nominal) {
                            $db->table('tb_kas_unit')->insert([
                                'unit_id' => $unit['id'],
                                'tanggal' => $tglTransaksi ?: date('Y-m-d'),
                                'keterangan' => $keterangan,
                                'kategori' => 'Tarik Tabungan',
                                'jumlah' => $nominal,
                                'jenis' => 'pengeluaran',
                                'metode' => $metode,
                                'status_tutup' => 'belum',
                                'user_id' => $this->userData['id'],
                                'referensi_id' => $txId,
                                'referensi_tipe' => 'transaksi_tabungan',
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                            $successMsg = 'Penarikan dicatat langsung sebagai pengeluaran Kas Unit (saldo kas tersedia mencukupi).';
                        } else {
                            $db->table('tb_pengajuan_dana')->insert([
                                'unit_id' => $unit['id'],
                                'user_id' => $this->userData['id'],
                                'tanggal' => date('Y-m-d'),
                                'keterangan' => $keterangan,
                                'jumlah' => $nominal,
                                'status' => 'pending',
                                'referensi_tipe' => 'transaksi_tabungan',
                                'referensi_id' => $txId,
                            ]);
                            $successMsg = 'Penarikan dicatat pada rekening. Pengajuan dana ke yayasan telah dibuat dan menunggu persetujuan. Kas unit hanya terpengaruh setelah pengajuan disetujui.';
                        }
                    }
                }
            }

            $db->transCommit();
            session()->setFlashdata('scroll_to_akun', $akunId);
            return redirect()->to($redirectUrl)->with('success', $successMsg);
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->to($redirectUrl)->with('error', 'Gagal memproses transaksi.');
        }
    }

    public function editTransaksi()
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        if ($this->userRoleFromDb() !== 'superadmin') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya Super Admin yang dapat mengakses fitur ini.']);
        }

        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Metode tidak valid.']);
        }

        $txId = (int) $this->request->getPost('id');
        $nominal = preg_replace('/[^0-9.,]/', '', $this->request->getPost('nominal'));
        $nominal = (float) str_replace(',', '.', str_replace('.', '', $nominal));
        $metode = $this->request->getPost('metode') ?: 'tunai';
        $tglTransaksi = $this->request->getPost('tgl_transaksi');
        $catatan = $this->request->getPost('catatan');

        if ($nominal <= 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Nominal harus lebih dari 0.']);
        }

        $db = \Config\Database::connect();
        $tx = $db->table('tb_transaksi_tabungan')->where('id', $txId)->get()->getRowArray();
        if (!$tx) {
            return $this->response->setJSON(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
        }

        $akunId = (int) $tx['akun_id'];
        $akun = $db->table('tb_tabungan')->where('id', $akunId)->get()->getRowArray();
        if (!$akun) {
            return $this->response->setJSON(['success' => false, 'message' => 'Rekening tidak ditemukan.']);
        }

        $tglBaru = $tglTransaksi ? date('Y-m-d H:i:s', strtotime($tglTransaksi)) : $tx['created_at'];

        // Muat semua transaksi rekening secara kronologis untuk hitung ulang saldo
        $rows = $db->query("SELECT * FROM tb_transaksi_tabungan WHERE akun_id = ? ORDER BY created_at ASC, id ASC", [$akunId])->getResultArray();
        $baseline = isset($rows[0]) ? (float) $rows[0]['saldo_sebelum'] : 0;

        // Validasi: saldo tidak boleh negatif setelah koreksi
        $running = $baseline;
        foreach ($rows as $r) {
            $nom = ((int) $r['id'] === $txId) ? $nominal : (float) $r['nominal'];
            $running += ($r['tipe'] === 'setor') ? $nom : -$nom;
            if ($running < 0) {
                return $this->response->setJSON(['success' => false, 'message' => 'Koreksi gagal: saldo rekening menjadi negatif pada salah satu transaksi.']);
            }
        }

        $db->transBegin();
        try {
            // 1. Update transaksi + hitung ulang saldo berjalan
            $running = $baseline;
            foreach ($rows as $r) {
                $isEdited = ((int) $r['id'] === $txId);
                $nom = $isEdited ? $nominal : (float) $r['nominal'];
                $saldoSebelum = $running;
                $running += ($r['tipe'] === 'setor') ? $nom : -$nom;
                $saldoSesudah = $running;

                if ($isEdited) {
                    $db->table('tb_transaksi_tabungan')->where('id', $txId)->update([
                        'nominal' => $nominal,
                        'saldo_sebelum' => $saldoSebelum,
                        'saldo_sesudah' => $saldoSesudah,
                        'metode' => $metode,
                        'catatan' => $catatan,
                        'created_at' => $tglBaru,
                    ]);
                } else {
                    $db->table('tb_transaksi_tabungan')->where('id', $r['id'])->update([
                        'saldo_sebelum' => $saldoSebelum,
                        'saldo_sesudah' => $saldoSesudah,
                    ]);
                }
            }

            // 2. Update saldo rekening
            $db->table('tb_tabungan')->where('id', $akunId)->update(['saldo' => $running]);

            // 3. Update kas unit (referensi transaksi tabungan)
            $pemilikNama = '';
            if ($akun['tipe'] === 'siswa' && $akun['siswa_id']) {
                $siswa = $db->table('tb_siswa')->where('id', $akun['siswa_id'])->get()->getRowArray();
                if ($siswa) $pemilikNama = $siswa['nama'] ?? '';
            } elseif ($akun['tipe'] === 'guru' && $akun['guru_id']) {
                $guru = $db->table('tb_guru')->where('id', $akun['guru_id'])->get()->getRowArray();
                if ($guru) $pemilikNama = $guru['nama'] ?? '';
            } elseif ($akun['tipe'] === 'nasabah' && $akun['nasabah_id']) {
                $nasabah = $db->table('tb_nasabah')->where('id', $akun['nasabah_id'])->get()->getRowArray();
                if ($nasabah) $pemilikNama = $nasabah['nama'] ?? '';
            }
            $keterangan = ($tx['tipe'] === 'setor' ? 'Setor Tabungan' : 'Tarik Tabungan') . ' - ' . $pemilikNama . ($catatan ? ' (' . $catatan . ')' : '');

            $kasUnit = $db->table('tb_kas_unit')
                ->where('referensi_tipe', 'transaksi_tabungan')
                ->where('referensi_id', $txId)
                ->get()->getRowArray();

            if ($kasUnit) {
                $db->table('tb_kas_unit')->where('id', $kasUnit['id'])->update([
                    'tanggal' => date('Y-m-d', strtotime($tglBaru)),
                    'keterangan' => $keterangan,
                    'metode' => $metode,
                    'jumlah' => $nominal,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // 4. Update kas yayasan hasil tutup buku (jika sudah ditutup)
                $db->table('tb_kas_yayasan')
                    ->where('referensi_tipe', 'tutup_buku')
                    ->where('referensi_id', $kasUnit['id'])
                    ->update([
                        'tanggal' => date('Y-m-d', strtotime($tglBaru)),
                        'keterangan' => $keterangan,
                        'metode' => $metode,
                        'jumlah' => $nominal,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            // 4b. Sinkronkan pengajuan dana (alur tarik: auto-pengajuan ke yayasan)
            $pengajuan = $db->table('tb_pengajuan_dana')
                ->where('referensi_tipe', 'transaksi_tabungan')
                ->where('referensi_id', $txId)
                ->get()->getRowArray();
            if ($pengajuan) {
                $db->table('tb_pengajuan_dana')->where('id', $pengajuan['id'])->update([
                    'keterangan' => $keterangan,
                    'jumlah' => $nominal,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // Jika sudah disetujui & diposting, ikutkan entry "Dana dari Yayasan" (pemasukan)
                $danaMasuk = $db->table('tb_kas_unit')
                    ->where('referensi_tipe', 'pengajuan_dana')
                    ->where('referensi_id', $pengajuan['id'])
                    ->get()->getRowArray();
                if ($danaMasuk) {
                    $db->table('tb_kas_unit')->where('id', $danaMasuk['id'])->update([
                        'tanggal' => date('Y-m-d', strtotime($tglBaru)),
                        'keterangan' => 'Dana dari Yayasan - ' . $keterangan,
                        'metode' => $metode,
                        'jumlah' => $nominal,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    $db->table('tb_kas_yayasan')
                        ->where('referensi_tipe', 'tutup_buku')
                        ->where('referensi_id', $danaMasuk['id'])
                        ->update([
                            'tanggal' => date('Y-m-d', strtotime($tglBaru)),
                            'keterangan' => 'Dana dari Yayasan - ' . $keterangan,
                            'metode' => $metode,
                            'jumlah' => $nominal,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                }
            }

            // 5. Audit log
            (new AuditLogModel())->log('koreksi_transaksi_tabungan', 'tb_transaksi_tabungan', $txId, $this->userData['id'],
                "Rekening: {$akun['no_rekening']} | Nominal lama: " . $tx['nominal'] . " -> baru: " . $nominal . " | Saldo akhir: " . $running);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal menyimpan koreksi: ' . $e->getMessage()]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Transaksi berhasil dikoreksi dan saldo diperbarui.']);
    }

    public function createAccount()
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        $rules = [
            'tipe' => 'required|in_list[siswa,guru,nasabah]',
            'orang_id' => 'required|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $sekolah = $this->userData['sekolah'] ?? 'admin';
        $tipe = $this->request->getPost('tipe');
        $orangId = $this->request->getPost('orang_id');

        if ($sekolah === 'admin') {
            $sekolah = $this->request->getPost('sekolah') ?: 'admin';
        }

        $savingsModel = new SavingsAccountModel();
        $nasabahModel = new NasabahModel();
        $noRekening = 'SAV-' . date('Ymd') . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);

        $data = [
            'no_rekening' => $noRekening,
            'tipe' => $tipe,
            'sekolah' => $sekolah,
            'saldo' => 0,
            'aktif' => 1,
        ];

        if ($tipe === 'siswa') {
            $data['siswa_id'] = $orangId;
        } elseif ($tipe === 'nasabah') {
            $data['nasabah_id'] = $orangId;
            $nasabah = $nasabahModel->find($orangId);
            if ($nasabah && !empty($nasabah['sekolah'])) {
                $data['sekolah'] = $nasabah['sekolah'];
            }
        } else {
            $data['guru_id'] = $orangId;
        }

        $label = $tipe === 'siswa' ? 'siswa' : ($tipe === 'nasabah' ? 'nasabah' : 'guru');
        try {
            $savingsModel->insert($data);
            return redirect()->to('/tabungan?tab=' . $tipe)->with('success', "Rekening {$noRekening} berhasil dibuat untuk {$label}.");
        } catch (\Exception $e) {
            return redirect()->to('/tabungan?tab=' . $tipe)->with('error', 'Gagal membuat rekening.');
        }
    }

    public function rekap()
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        $sekolah = $this->userData['sekolah'] ?? 'admin';
        $db = \Config\Database::connect();

        $filterStart = $this->request->getGet('start') ?: date('Y-m-01');
        $filterEnd = $this->request->getGet('end') ?: date('Y-m-d');
        $filterTipe = $this->request->getGet('tipe_akun') ?: '';
        $filterJenis = $this->request->getGet('jenis') ?: '';

        $where = "WHERE t.created_at::date BETWEEN '{$filterStart}' AND '{$filterEnd}'";
        if ($filterTipe) {
            $where .= " AND a.tipe = '{$filterTipe}'";
        }
        if ($filterJenis) {
            $where .= " AND t.tipe = '{$filterJenis}'";
        }
        if ($sekolah && $sekolah !== 'admin') {
            $where .= " AND a.sekolah = '" . $db->escapeString($sekolah) . "'";
        }

        // Detail transaksi
        $rows = $db->query("
            SELECT t.*, a.no_rekening, a.tipe as akun_tipe, a.sekolah,
                   COALESCE(s.nama, g.nama, n.nama) as nama_pemilik
            FROM tb_transaksi_tabungan t
            JOIN tb_tabungan a ON a.id = t.akun_id
            LEFT JOIN tb_siswa s ON s.id = a.siswa_id
            LEFT JOIN tb_guru g ON g.id = a.guru_id
            LEFT JOIN tb_nasabah n ON n.id = a.nasabah_id
            {$where}
            ORDER BY t.created_at DESC
        ")->getResultArray();

        // Rekapitulasi
        $summary = $db->query("
            SELECT
                COUNT(*) as total_transaksi,
                COUNT(DISTINCT a.id) as total_akun,
                COALESCE(SUM(CASE WHEN t.tipe = 'setor' THEN t.nominal ELSE 0 END), 0) as total_setor,
                COALESCE(SUM(CASE WHEN t.tipe = 'tarik' THEN t.nominal ELSE 0 END), 0) as total_tarik,
                COALESCE(SUM(t.nominal), 0) as total_keseluruhan
            FROM tb_transaksi_tabungan t
            JOIN tb_tabungan a ON a.id = t.akun_id
            {$where}
        ")->getRowArray();

        // Per metode
        $perMetode = $db->query("
            SELECT
                t.metode,
                COUNT(*) as jumlah,
                COALESCE(SUM(t.nominal), 0) as total
            FROM tb_transaksi_tabungan t
            JOIN tb_tabungan a ON a.id = t.akun_id
            {$where}
            GROUP BY t.metode
            ORDER BY t.metode
        ")->getResultArray();

        // Per tipe akun
        $perTipeAkun = $db->query("
            SELECT
                a.tipe,
                COUNT(*) as jumlah,
                COALESCE(SUM(t.nominal), 0) as total
            FROM tb_transaksi_tabungan t
            JOIN tb_tabungan a ON a.id = t.akun_id
            {$where}
            GROUP BY a.tipe
            ORDER BY a.tipe
        ")->getResultArray();

        $data = [
            'activeMenu' => 'tabungan-rekap',
            'rows' => $rows,
            'summary' => $summary,
            'perMetode' => $perMetode,
            'perTipeAkun' => $perTipeAkun,
            'filterStart' => $filterStart,
            'filterEnd' => $filterEnd,
            'filterTipe' => $filterTipe,
            'filterJenis' => $filterJenis,
        ];

        return $this->render('tabungan/rekap', $data);
    }

    public function riwayat($akunId)
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        $txModel = new SavingsTransactionModel();
        $txs = $txModel->select('tb_transaksi_tabungan.*, tb_tabungan.tipe as akun_tipe')
            ->join('tb_tabungan', 'tb_tabungan.id = tb_transaksi_tabungan.akun_id')
            ->where('tb_transaksi_tabungan.akun_id', $akunId)
            ->orderBy('tb_transaksi_tabungan.created_at', 'DESC')
            ->findAll();

        $savingsModel = new SavingsAccountModel();
        $akun = $savingsModel->select("tb_tabungan.*, COALESCE(tb_siswa.nama, tb_guru.nama, tb_nasabah.nama) as nama_pemilik, tb_siswa.nis, tb_guru.nip, tb_nasabah.no_telp")
            ->join('tb_siswa', 'tb_siswa.id = tb_tabungan.siswa_id', 'left')
            ->join('tb_guru', 'tb_guru.id = tb_tabungan.guru_id', 'left')
            ->join('tb_nasabah', 'tb_nasabah.id = tb_tabungan.nasabah_id', 'left')
            ->find($akunId);

        $saldo = (float)($akun['saldo'] ?? 0);
        $rek = esc($akun['no_rekening'] ?? '');
        $pemilik = esc($akun['nama_pemilik'] ?? '');
        $canEdit = $this->userRoleFromDb() === 'superadmin';

        $html = '<div style="padding:0">';
        $html .= '<div style="background:var(--slate-50); border-radius:10px; padding:14px 16px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center">';
        $html .= '<div><div style="font-size:11px; color:var(--slate-500); font-weight:600; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px">' . $rek . '</div>';
        $html .= '<div style="font-size:14px; font-weight:700; color:var(--navy-900)">' . $pemilik . '</div></div>';
        $html .= '<div style="text-align:right"><div style="font-size:11px; color:var(--slate-500); font-weight:600; margin-bottom:2px">Saldo</div>';
        $html .= '<div style="font-size:16px; font-weight:700; ' . ($saldo > 0 ? 'color:var(--emerald-600)' : 'color:var(--slate-400)') . '">Rp ' . number_format($saldo, 0, ',', '.') . '</div></div>';
        $html .= '</div>';

        if (empty($txs)) {
            $html .= '<div style="display:flex; flex-direction:column; align-items:center; padding:40px 20px">';
            $html .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:var(--slate-300);margin-bottom:12px"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
            $html .= '<p style="color:var(--slate-400); font-size:14px; font-weight:500">Belum ada transaksi</p>';
            $html .= '<p style="color:var(--slate-300); font-size:12px; margin-top:4px">Transaksi setor atau tarik akan muncul di sini</p>';
            $html .= '</div>';
        } else {
            $html .= '<div style="border:1px solid var(--slate-200); border-radius:10px; overflow-x:auto">';
            $html .= '<table style="width:100%; min-width:660px; border-collapse:collapse; font-size:13px">';
            $html .= '<thead><tr style="background:var(--slate-50)">';
            $html .= '<th style="padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Tanggal</th>';
            $html .= '<th style="padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Tipe</th>';
            $html .= '<th style="padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Metode</th>';
            $html .= '<th style="padding:10px 14px; text-align:right; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Nominal</th>';
            $html .= '<th style="padding:10px 14px; text-align:right; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Saldo</th>';
            $html .= '<th style="padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Catatan</th>';
            if ($canEdit) {
                $html .= '<th style="padding:10px 14px; text-align:right; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Aksi</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($txs as $tx) {
                $warna = $tx['tipe'] === 'setor' ? 'var(--emerald-600)' : 'var(--red-700)';
                $bg = $tx['tipe'] === 'setor' ? 'rgba(16,185,129,0.06)' : 'rgba(239,68,68,0.06)';
                $lblTipe = $tx['tipe'] === 'setor' ? 'Setoran' : 'Penarikan';
                $metodeLabel = $tx['metode'] === 'transfer' ? 'Transfer' : ($tx['metode'] === 'tunai' ? 'Tunai' : '-');
                $html .= '<tr style="border-bottom:1px solid var(--slate-100)">';
                $html .= '<td style="padding:10px 14px; color:var(--slate-600)">' . date('d/m/Y H:i', strtotime($tx['created_at'])) . '</td>';
                $html .= '<td style="padding:10px 14px"><span style="display:inline-block; padding:2px 10px; border-radius:100px; font-size:11px; font-weight:600; color:' . $warna . '; background:' . $bg . '">' . $lblTipe . '</span></td>';
                $html .= '<td style="padding:10px 14px; color:var(--slate-600)">' . $metodeLabel . '</td>';
                $html .= '<td style="padding:10px 14px; text-align:right; font-weight:600; color:' . $warna . '">Rp ' . number_format($tx['nominal'], 0, ',', '.') . '</td>';
                $html .= '<td style="padding:10px 14px; text-align:right; color:var(--slate-700)">Rp ' . number_format($tx['saldo_sesudah'], 0, ',', '.') . '</td>';
                $html .= '<td style="padding:10px 14px; color:var(--slate-500); font-size:12px">' . esc($tx['catatan'] ?? '-') . '</td>';
                if ($canEdit) {
                    $html .= '<td style="padding:10px 14px; text-align:right; white-space:nowrap">';
                    $html .= '<button type="button" class="ku-action-btn edit-tx-btn" title="Koreksi transaksi" data-id="' . (int)$tx['id'] . '" data-tipe="' . esc($tx['tipe']) . '" data-nominal="' . (float)$tx['nominal'] . '" data-metode="' . esc($tx['metode'] ?? 'tunai') . '" data-tgl="' . date('Y-m-d\TH:i', strtotime($tx['created_at'])) . '" data-catatan="' . esc($tx['catatan'] ?? '') . '">';
                    $html .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>';
                    $html .= '<span>Koreksi</span>';
                    $html .= '</button>';
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }

    public function exportExcel()
    {
        $redirect = $this->redirectIfNotLoggedIn();
        if ($redirect) return $redirect;

        $sekolah = $this->userData['sekolah'] ?? 'admin';
        $db = \Config\Database::connect();

        $filterStart = $this->request->getGet('start') ?: date('Y-m-01');
        $filterEnd = $this->request->getGet('end') ?: date('Y-m-d');
        $filterTipe = $this->request->getGet('tipe_akun') ?: '';
        $filterJenis = $this->request->getGet('jenis') ?: '';

        $where = "WHERE t.created_at::date BETWEEN '{$filterStart}' AND '{$filterEnd}'";
        if ($filterTipe) $where .= " AND a.tipe = '{$filterTipe}'";
        if ($filterJenis) $where .= " AND t.tipe = '{$filterJenis}'";
        if ($sekolah && $sekolah !== 'admin') $where .= " AND a.sekolah = '" . $db->escapeString($sekolah) . "'";

        $rows = $db->query("
            SELECT t.*, a.no_rekening, a.tipe as akun_tipe, a.sekolah,
                   COALESCE(s.nama, g.nama, n.nama) as nama_pemilik
            FROM tb_transaksi_tabungan t
            JOIN tb_tabungan a ON a.id = t.akun_id
            LEFT JOIN tb_siswa s ON s.id = a.siswa_id
            LEFT JOIN tb_guru g ON g.id = a.guru_id
            LEFT JOIN tb_nasabah n ON n.id = a.nasabah_id
            {$where}
            ORDER BY t.created_at DESC
        ")->getResultArray();

        $summary = $db->query("
            SELECT
                COUNT(*) as total_transaksi,
                COALESCE(SUM(CASE WHEN t.tipe = 'setor' THEN t.nominal ELSE 0 END), 0) as total_setor,
                COALESCE(SUM(CASE WHEN t.tipe = 'tarik' THEN t.nominal ELSE 0 END), 0) as total_tarik
            FROM tb_transaksi_tabungan t
            JOIN tb_tabungan a ON a.id = t.akun_id
            {$where}
        ")->getRowArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Tabungan');

        $titleFont = ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]];
        $headerFont = ['font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $dataFont = ['font' => ['bold' => false, 'size' => 10], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $totalFont = ['font' => ['bold' => true, 'size' => 10], 'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'REKAP TABUNGAN ' . $filterStart . ' s/d ' . $filterEnd);
        $sheet->getStyle('A1')->applyFromArray($titleFont);
        $sheet->getRowDimension('1')->setRowHeight(35);

        $row = 3;
        $sheet->setCellValue('A' . $row, 'Total Transaksi: ' . number_format($summary['total_transaksi'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('C' . $row, 'Total Setoran: Rp ' . number_format($summary['total_setor'] ?? 0, 0, ',', '.'));
        $sheet->setCellValue('E' . $row, 'Total Penarikan: Rp ' . number_format($summary['total_tarik'] ?? 0, 0, ',', '.'));
        $sheet->getStyle('A' . $row)->applyFromArray($totalFont);
        $sheet->getStyle('C' . $row)->applyFromArray($totalFont);
        $sheet->getStyle('E' . $row)->applyFromArray($totalFont);
        $row += 2;

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $widths = [18, 16, 22, 14, 14, 16, 30];
        $headers = ['Tanggal', 'No. Rekening', 'Pemilik', 'Tipe', 'Metode', 'Nominal', 'Catatan'];
        foreach ($headers as $i => $h) {
            $sheet->getColumnDimension($cols[$i])->setWidth($widths[$i]);
            $sheet->setCellValue($cols[$i] . $row, $h);
            $sheet->getStyle($cols[$i] . $row)->applyFromArray($headerFont);
        }
        $row++;

        foreach ($rows as $r) {
            $tipe = $r['tipe'] === 'setor' ? 'Setoran' : 'Penarikan';
            $metode = $r['metode'] === 'transfer' ? 'Transfer' : ($r['metode'] === 'tunai' ? 'Tunai' : '-');
            $nominal = $r['tipe'] === 'setor' ? (float)$r['nominal'] : -(float)$r['nominal'];
            $akunTipe = $r['akun_tipe'] === 'siswa' ? 'Siswa' : ($r['akun_tipe'] === 'guru' ? 'Guru' : 'Non Civitas');

            $sheet->setCellValue('A' . $row, date('d/m/Y H:i', strtotime($r['created_at'])));
            $sheet->setCellValue('B' . $row, $r['no_rekening']);
            $sheet->setCellValue('C' . $row, ($r['nama_pemilik'] ?? '-') . ' (' . $akunTipe . ')');
            $sheet->setCellValue('D' . $row, $tipe);
            $sheet->setCellValue('E' . $row, $metode);
            $sheet->setCellValue('F' . $row, $nominal);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->setCellValue('G' . $row, $r['catatan'] ?? '-');
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataFont);
            $row++;
        }

        $filename = 'REKAP_TABUNGAN_' . $filterStart . '_sd_' . $filterEnd . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }
}
