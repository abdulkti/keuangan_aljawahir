<?php

namespace App\Controllers;

use App\Models\GuruModel;
use App\Models\UnitModel;
use App\Models\ThtTransaksiModel;

class Guru extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $guruModel = new GuruModel();
        $unitModel = new UnitModel();
        $thtModel = new ThtTransaksiModel();

        $guruList = $guruModel->getWithUnit();
        foreach ($guruList as &$g) {
            $g['saldo_tht'] = $thtModel->getSaldoGuru($g['id']);
        }

        $data = [
            'activeMenu' => 'guru',
            'guruList' => $guruList,
            'unitList' => $unitModel->findAll(),
        ];

        return $this->render('superadmin/guru', $data);
    }

    public function tambah()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) {
            log_message('error', '[Guru::tambah] redirectIfNotRole triggered');
            return $redirect;
        }

        $method = $this->request->getMethod();
        log_message('info', '[Guru::tambah] Method: ' . $method);

        if ($method !== 'post') {
            log_message('error', '[Guru::tambah] Not POST, redirecting');
            return redirect()->to('/guru');
        }

        $rules = [
            'nip' => 'permit_empty|max_length[50]',
            'nama' => 'required|min_length[2]|max_length[100]',
            'unit_id' => 'required|is_not_unique[tb_unit.id]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $guruModel = new GuruModel();

        $postNip = $this->request->getPost('nip');
        $postNama = $this->request->getPost('nama');
        $postUnit = $this->request->getPost('unit_id');
        $postSaldo = $this->request->getPost('saldo_awal');

        log_message('info', '[Guru::tambah] POST data: nip=' . ($postNip ?? 'null') . ', nama=' . ($postNama ?? 'null') . ', unit_id=' . ($postUnit ?? 'null') . ', saldo_awal=' . ($postSaldo ?? 'null'));

        $data = [
            'nip' => $postNip,
            'nama' => $postNama,
            'unit_id' => $postUnit,
            'saldo_awal' => 0,
        ];

        $saldoAwal = (int) ($postSaldo ?? 0);

        log_message('info', '[Guru::tambah] Calling insert with data: ' . json_encode($data));
        $guruId = $guruModel->insert($data);
        log_message('info', '[Guru::tambah] Insert result: ' . var_export($guruId, true));

        if ($guruId) {
            if ($saldoAwal > 0) {
                $thtModel = new ThtTransaksiModel();
                $thtData = [
                    'guru_id' => $guruId,
                    'tipe' => 'setoran',
                    'jumlah' => $saldoAwal,
                    'tanggal' => date('Y-m-d'),
                    'keterangan' => 'Saldo awal',
                ];
                log_message('info', '[Guru::tambah] Creating THT transaksi: ' . json_encode($thtData));
                $thtResult = $thtModel->insert($thtData);
                log_message('info', '[Guru::tambah] THT insert result: ' . var_export($thtResult, true));
            }
            return redirect()->to('/guru')->with('success', 'Guru berhasil ditambahkan.');
        } else {
            $errors = $guruModel->errors();
            log_message('error', '[Guru::tambah] Insert failed. Validation errors: ' . json_encode($errors));
            return redirect()->to('/guru')->with('error', 'Gagal menambahkan guru: ' . json_encode($errors));
        }
    }

    public function edit($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/guru');
        }

        $rules = [
            'nip' => "permit_empty|max_length[50]|is_unique[tb_guru.nip,id,{$id}]",
            'nama' => 'required|min_length[2]|max_length[100]',
            'unit_id' => 'required|is_not_unique[tb_unit.id]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $guruModel = new GuruModel();
        $data = [
            'nip' => $this->request->getPost('nip'),
            'nama' => $this->request->getPost('nama'),
            'unit_id' => $this->request->getPost('unit_id'),
            'saldo_awal' => $this->request->getPost('saldo_awal') ?: 0,
        ];

        if ($guruModel->update($id, $data)) {
            return redirect()->to('/guru')->with('success', 'Guru berhasil diupdate.');
        } else {
            return redirect()->to('/guru')->with('error', 'Gagal mengupdate guru.');
        }
    }

    public function hapus($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $db->table('tb_transaksi_tht')->where('guru_id', $id)->delete();

            $akun = $db->table('tb_tabungan')->where('guru_id', $id)->get()->getRowArray();
            if ($akun) {
                $db->table('tb_transaksi_tabungan')->where('akun_id', $akun['id'])->delete();
                $db->table('tb_tabungan')->delete(['id' => $akun['id']]);
            }

            $guruModel = new GuruModel();
            $guruModel->delete($id);

            $db->transCommit();
            return redirect()->to('/guru')->with('success', 'Guru dan seluruh data terkait berhasil dihapus.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->to('/guru')->with('error', 'Gagal menghapus guru: ' . $e->getMessage());
        }
    }

    public function getData($id)
    {
        $guruModel = new GuruModel();
        return $this->response->setJSON($guruModel->find($id));
    }
}
