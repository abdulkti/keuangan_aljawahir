<?php

namespace App\Controllers;

use App\Models\UnitModel;

class Unit extends BaseController
{
    public function index()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $unitModel = new UnitModel();

        $data = [
            'activeMenu' => 'unit',
            'unitList' => $unitModel->findAll(),
        ];

        return $this->render('superadmin/unit', $data);
    }

    public function tambah()
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/unit');
        }

        $unitModel = new UnitModel();
        $data = [
            'nama' => $this->request->getPost('nama'),
            'alamat' => $this->request->getPost('alamat'),
            'kepala_sekolah' => $this->request->getPost('kepala_sekolah'),
        ];

        if ($unitModel->insert($data)) {
            return redirect()->to('/unit')->with('success', 'Unit berhasil ditambahkan.');
        } else {
            return redirect()->to('/unit')->with('error', 'Gagal menambahkan unit.');
        }
    }

    public function edit($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/unit');
        }

        $unitModel = new UnitModel();
        $data = [
            'nama' => $this->request->getPost('nama'),
            'alamat' => $this->request->getPost('alamat'),
            'kepala_sekolah' => $this->request->getPost('kepala_sekolah'),
        ];

        if ($unitModel->update($id, $data)) {
            return redirect()->to('/unit')->with('success', 'Unit berhasil diupdate.');
        } else {
            return redirect()->to('/unit')->with('error', 'Gagal mengupdate unit.');
        }
    }

    public function hapus($id)
    {
        $redirect = $this->redirectIfNotRole(['admin', 'superadmin']);
        if ($redirect) return $redirect;

        $unitModel = new UnitModel();
        if ($unitModel->delete($id)) {
            return redirect()->to('/unit')->with('success', 'Unit berhasil dihapus.');
        } else {
            return redirect()->to('/unit')->with('error', 'Gagal menghapus unit.');
        }
    }

    public function getData($id)
    {
        $unitModel = new UnitModel();
        return $this->response->setJSON($unitModel->find($id));
    }
}
