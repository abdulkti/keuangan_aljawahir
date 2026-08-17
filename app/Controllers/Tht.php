<?php

namespace App\Controllers;

use App\Models\ThtTransaksiModel;
use App\Models\GuruModel;
use App\Models\PengeluaranModel;

class Tht extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $thtModel = new ThtTransaksiModel();
        $guruModel = new GuruModel();

        $search = $this->request->getGet('search') ?? '';
        $tahunFilter = $this->request->getGet('tahun') ?? '';
        $guruList = $guruModel->getWithUnit();
        $allTransaksi = $thtModel->getWithGuru();

        // Build academic year list from all transactions
        $tahunList = [];
        foreach ($allTransaksi as $t) {
            $thn = (int) date('Y', strtotime($t['tanggal']));
            $bln = (int) date('m', strtotime($t['tanggal']));
            $ta = $bln >= 7 ? ($thn . '-' . ($thn + 1)) : (($thn - 1) . '-' . $thn);
            if (!in_array($ta, $tahunList)) {
                $tahunList[] = $ta;
            }
        }
        rsort($tahunList);

        // Always include current academic year
        $blnSkrg = (int) date('m');
        $thnSkrg = (int) date('Y');
        $taSkrg = $blnSkrg >= 7 ? ($thnSkrg . '-' . ($thnSkrg + 1)) : (($thnSkrg - 1) . '-' . $thnSkrg);
        if (!in_array($taSkrg, $tahunList)) {
            $tahunList[] = $taSkrg;
        }
        rsort($tahunList);

        if (empty($tahunFilter)) {
            $tahunFilter = $tahunList[0];
        }

        // Filter transactions by academic year
        $transaksi = [];
        foreach ($allTransaksi as $t) {
            $thn = (int) date('Y', strtotime($t['tanggal']));
            $bln = (int) date('m', strtotime($t['tanggal']));
            $ta = $bln >= 7 ? ($thn . '-' . ($thn + 1)) : (($thn - 1) . '-' . $thn);
            if ($tahunFilter && $ta !== $tahunFilter) continue;
            $transaksi[] = $t;
        }

        // Recalculate per-guru totals filtered by year
        $saldoGuru = [];
        $guruTotals = [];
        foreach ($transaksi as $t) {
            $gid = $t['guru_id'];
            if (!isset($guruTotals[$gid])) {
                $guruTotals[$gid] = ['setoran' => 0, 'penarikan' => 0];
            }
            if ($t['tipe'] === 'setoran') {
                $guruTotals[$gid]['setoran'] += (float) $t['jumlah'];
            } else {
                $guruTotals[$gid]['penarikan'] += (float) $t['jumlah'];
            }
        }

        foreach ($guruList as $guru) {
            if ($search && stripos($guru['nama'], $search) === false) {
                continue;
            }
            $gid = $guru['id'];
            $saldoGuru[] = [
                'id' => $gid,
                'nip' => $guru['nip'],
                'nama' => $guru['nama'],
                'unit' => $guru['unit_nama'],
                'total_setoran' => $guruTotals[$gid]['setoran'] ?? 0,
                'total_penarikan' => $guruTotals[$gid]['penarikan'] ?? 0,
                'saldo' => (float)($guru['saldo_awal'] ?? 0) + ($guruTotals[$gid]['setoran'] ?? 0) - ($guruTotals[$gid]['penarikan'] ?? 0),
            ];
        }

        $data = [
            'activeMenu' => 'tht',
            'transaksi' => $transaksi,
            'saldoGuru' => $saldoGuru,
            'guruList' => $guruList,
            'search' => $search,
            'tahunFilter' => $tahunFilter,
            'tahunList' => $tahunList,
        ];

        return $this->render('superadmin/tht', $data);
    }

    public function riwayat($guruId)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $guruModel = new GuruModel();
        $guru = $guruModel->find($guruId);

        if (!$guru) {
            return $this->response->setJSON(['success' => false, 'message' => 'Guru tidak ditemukan.']);
        }

        $unitLabels = ['ra' => 'RA IT Al-Jawahir', 'sd' => 'SD IT Al-Jawahir', 'smp' => 'SMP IT Al-Jawahir'];
        $unitNama = $unitLabels[$guru['sekolah'] ?? ''] ?? 'Yayasan';
        if (!empty($guru['unit_id'])) {
            $unit = \Config\Database::connect()->table('tb_unit')->where('id', $guru['unit_id'])->get()->getRowArray();
            if ($unit) {
                $unitNama = $unit['nama'];
            }
        }

        $txs = (new ThtTransaksiModel())
            ->where('guru_id', $guruId)
            ->orderBy('tanggal', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $saldo = (float)($guru['saldo_awal'] ?? 0);
        $running = [];
        foreach ($txs as $tx) {
            $nom = (float) $tx['jumlah'];
            if ($tx['tipe'] === 'setoran') {
                $saldo += $nom;
            } else {
                $saldo -= $nom;
            }
            $running[$tx['id']] = $saldo;
        }

        $nama = esc($guru['nama'] ?? '-');
        $nip = esc($guru['nip'] ?? '-');
        $unitE = esc($unitNama);
        $saldoAkhir = $running ? end($running) : (float)($guru['saldo_awal'] ?? 0);

        $html = '<div style="padding:0">';
        $html .= '<div style="background:var(--slate-50); border-radius:10px; padding:14px 16px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center">';
        $html .= '<div><div style="font-size:11px; color:var(--slate-500); font-weight:600; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px">' . $nip . ' &middot; ' . $unitE . '</div>';
        $html .= '<div style="font-size:14px; font-weight:700; color:var(--navy-900)">' . $nama . '</div></div>';
        $html .= '<div style="text-align:right"><div style="font-size:11px; color:var(--slate-500); font-weight:600; margin-bottom:2px">Saldo THT</div>';
        $html .= '<div style="font-size:16px; font-weight:700; ' . ($saldoAkhir > 0 ? 'color:var(--emerald-600)' : 'color:var(--slate-400)') . '">Rp ' . number_format($saldoAkhir, 0, ',', '.') . '</div></div>';
        $html .= '</div>';

        if (empty($txs)) {
            $html .= '<div style="display:flex; flex-direction:column; align-items:center; padding:40px 20px">';
            $html .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;color:var(--slate-300);margin-bottom:12px"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
            $html .= '<p style="color:var(--slate-400); font-size:14px; font-weight:500">Belum ada transaksi THT</p>';
            $html .= '<p style="color:var(--slate-300); font-size:12px; margin-top:4px">Setoran atau realisasi guru ini akan muncul di sini</p>';
            $html .= '</div>';
        } else {
            $html .= '<div style="border:1px solid var(--slate-200); border-radius:10px; overflow-x:auto">';
            $html .= '<table style="width:100%; min-width:680px; border-collapse:collapse; font-size:13px">';
            $html .= '<thead><tr style="background:var(--slate-50)">';
            $html .= '<th style="padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Tanggal</th>';
            $html .= '<th style="padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Tipe</th>';
            $html .= '<th style="padding:10px 14px; text-align:right; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Jumlah</th>';
            $html .= '<th style="padding:10px 14px; text-align:right; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Saldo</th>';
            $html .= '<th style="padding:10px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Keterangan</th>';
            $html .= '<th style="padding:10px 14px; text-align:right; font-size:10.5px; font-weight:700; color:var(--slate-500); text-transform:uppercase; letter-spacing:0.05em">Aksi</th>';
            $html .= '</tr></thead><tbody>';

            $txsDesc = array_reverse($txs);
            foreach ($txsDesc as $tx) {
                $isSetor = $tx['tipe'] === 'setoran';
                $warna = $isSetor ? 'var(--emerald-600)' : 'var(--red-700)';
                $bg = $isSetor ? 'rgba(16,185,129,0.06)' : 'rgba(239,68,68,0.06)';
                $lbl = $isSetor ? 'Setoran' : 'Realisasi';
                $saldoTx = $running[$tx['id']];

                $html .= '<tr style="border-bottom:1px solid var(--slate-100)">';
                $html .= '<td style="padding:10px 14px; color:var(--slate-600)">' . date('d/m/Y', strtotime($tx['tanggal'])) . '</td>';
                $html .= '<td style="padding:10px 14px"><span style="display:inline-block; padding:2px 10px; border-radius:100px; font-size:11px; font-weight:600; color:' . $warna . '; background:' . $bg . '">' . $lbl . '</span></td>';
                $html .= '<td style="padding:10px 14px; text-align:right; font-weight:600; color:' . $warna . '">Rp ' . number_format((float)$tx['jumlah'], 0, ',', '.') . '</td>';
                $html .= '<td style="padding:10px 14px; text-align:right; color:var(--slate-700)">Rp ' . number_format($saldoTx, 0, ',', '.') . '</td>';
                $html .= '<td style="padding:10px 14px; color:var(--slate-500); font-size:12px">' . esc($tx['keterangan'] ?? '-') . '</td>';
                $html .= '<td style="padding:10px 14px; text-align:right; white-space:nowrap">';
                $html .= '<button type="button" class="ku-action-btn tht-hapus-btn" title="Hapus transaksi" data-id="' . (int)$tx['id'] . '">';
                $html .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
                $html .= '<span>Hapus</span>';
                $html .= '</button>';
                $html .= '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }

    public function setor()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/tht');
        }

        $thtModel = new ThtTransaksiModel();
        $guruModel = new GuruModel();

        $guruId = $this->request->getPost('guru_id');
        $jumlah = $this->request->getPost('jumlah');
        $tanggal = $this->request->getPost('tanggal') ?: date('Y-m-d');
        $keterangan = $this->request->getPost('keterangan');

        if (!$guruId || !$guruModel->find($guruId)) {
            return redirect()->to('/tht')->with('error', 'Guru tidak ditemukan.');
        }

        if ((float) $jumlah <= 0) {
            return redirect()->to('/tht')->with('error', 'Jumlah iuran harus lebih dari 0.');
        }

        $data = [
            'guru_id' => $guruId,
            'tipe' => 'setoran',
            'jumlah' => $jumlah,
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
        ];

        if ($thtModel->insert($data)) {
            return redirect()->to('/tht')->with('success', 'Iuran THT berhasil dicatat.');
        }

        return redirect()->to('/tht')->with('error', 'Gagal mencatat iuran THT.');
    }

    public function tarik()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/tht');
        }

        $thtModel = new ThtTransaksiModel();
        $pengeluaranModel = new PengeluaranModel();
        $guruModel = new GuruModel();

        $guruId = $this->request->getPost('guru_id');
        if (!$guruId) {
            $guruNama = $this->request->getPost('guru_nama');
            $guru = $guruModel->like('nama', $guruNama)->first();
            $guruId = $guru ? $guru['id'] : null;
        }

        if (!$guruId) {
            return redirect()->to('/tht')->with('error', 'Guru tidak ditemukan.');
        }

        $jumlah = $this->request->getPost('jumlah');
        $saldo = $thtModel->getSaldoGuru($guruId);

        if ($jumlah > $saldo) {
            return redirect()->to('/tht')->with('error', 'Saldo THT tidak mencukupi.');
        }

        $data = [
            'guru_id' => $guruId,
            'tipe' => 'penarikan',
            'jumlah' => $jumlah,
            'tanggal' => $this->request->getPost('tanggal') ?: date('Y-m-d'),
            'keterangan' => $this->request->getPost('keterangan'),
        ];

        if ($thtInsertId = $thtModel->insert($data)) {
            $pengeluaranModel->insert([
                'tanggal' => $data['tanggal'],
                'keterangan' => 'Realisasi THT - ' . $data['keterangan'] . ' (ID:' . $thtInsertId . ')',
                'kategori' => 'THT',
                'jumlah' => $jumlah,
                'jenis' => 'pengeluaran',
            ]);
            return redirect()->to('/tht')->with('success', 'Realisasi THT berhasil diproses.');
        } else {
            return redirect()->to('/tht')->with('error', 'Gagal memproses realisasi THT.');
        }
    }

    public function hapus($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $thtModel = new ThtTransaksiModel();
        $transaksi = $thtModel->find($id);
        $isAjax = $this->request->isAJAX() || $this->request->hasHeader('X-CSRF-TOKEN');

        if (!$transaksi) {
            if ($isAjax) {
                return $this->response->setJSON(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
            }
            return redirect()->to('/tht')->with('error', 'Transaksi tidak ditemukan.');
        }

        if ($thtModel->delete($id)) {
            if ($transaksi['tipe'] === 'penarikan') {
                $pengeluaranModel = new PengeluaranModel();
                $pengeluaranModel->where('jenis', 'pengeluaran')->like('keterangan', '(ID:' . $id . ')')->delete();
            }
            if ($isAjax) {
                return $this->response->setJSON(['success' => true, 'message' => 'Transaksi THT berhasil dihapus.']);
            }
            return redirect()->to('/tht')->with('success', 'Transaksi THT berhasil dihapus.');
        }

        if ($isAjax) {
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal menghapus transaksi THT.']);
        }
        return redirect()->to('/tht')->with('error', 'Gagal menghapus transaksi THT.');
    }
}
