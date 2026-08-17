<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\SettingModel;
use App\Models\AuditLogModel;

class BaseController extends Controller
{
    protected $session;
    protected $userData;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = service('session');
        $this->userData = $this->session->get('user');

        if ($this->userData !== null) {
            $settingModel = new SettingModel();
            $this->userData['nama_sekolah'] = $settingModel->getSetting('school_name') ?: 'Al-Jawahir Attarbawi';
        }
    }

    protected function isLoggedIn(): bool
    {
        return $this->userData !== null;
    }

    protected function redirectIfNotLoggedIn()
    {
        if (!$this->isLoggedIn()) {
            return redirect()->to('/login');
        }
        return null;
    }

    protected function redirectIfNotRole(array $roles)
    {
        $check = $this->redirectIfNotLoggedIn();
        if ($check) return $check;

        if (!in_array($this->userData['role'] ?? '', $roles)) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }
        return null;
    }

    protected function userRoleFromDb(): ?string
    {
        $id = $this->userData['id'] ?? null;
        if (!$id) {
            return null;
        }

        try {
            $db = \Config\Database::connect();
            $row = $db->table('tb_users')->where('id', $id)->get()->getRow();
            return $row->role ?? null;
        } catch (\Throwable $e) {
            log_message('error', 'userRoleFromDb gagal: ' . $e->getMessage());
            return null;
        }
    }

    protected function render(string $view, array $data = [])
    {
        $data['user'] = $this->userData;
        return view($view, $data);
    }

    protected function autoTutup()
    {
        try {
            $db = \Config\Database::connect();

            $kelompok = $db->query("SELECT DISTINCT unit_id, tanggal FROM tb_kas_unit WHERE status_tutup = 'belum' AND tanggal < CURRENT_DATE AND reopened_at IS NULL")->getResultArray();
            if (empty($kelompok)) {
                return;
            }

            $db->transBegin();

            foreach ($kelompok as $k) {
                $unitId = $k['unit_id'];
                $tanggal = $k['tanggal'];

                $kas = $db->query("SELECT * FROM tb_kas_unit WHERE unit_id = ? AND tanggal = ? AND status_tutup = 'belum' AND reopened_at IS NULL", [$unitId, $tanggal])->getResultArray();
                if (empty($kas)) {
                    continue;
                }

                foreach ($kas as $t) {
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

                $db->table('tb_kas_unit')
                    ->where('unit_id', $unitId)
                    ->where('tanggal', $tanggal)
                    ->where('status_tutup', 'belum')
                    ->set('status_tutup', 'tutup')
                    ->update();

                (new AuditLogModel())->log('tutup_buku_otomatis', 'tb_kas_unit', null, $this->userData['id'] ?? null,
                    "Unit: {$unitId} | Tanggal: {$tanggal} | " . count($kas) . " transaksi ditutup otomatis");
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            if (isset($db)) {
                $db->transRollback();
            }
            log_message('error', 'autoTutup gagal: ' . $e->getMessage());
        }
    }
}
