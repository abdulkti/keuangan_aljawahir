<?php

namespace App\Controllers;

use App\Models\AcademicYearModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\ClassModel;
use App\Models\SavingsAccountModel;
use App\Models\NasabahModel;

class SiswaGuru extends BaseController
{
    private function getSekolah()
    {
        return $this->userData['sekolah'] ?? 'admin';
    }

    private function resolveUnitId(string $sekolah): ?int
    {
        $labels = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];
        $label = $labels[$sekolah] ?? null;
        if (!$label) return null;

        $unit = \Config\Database::connect()
            ->table('tb_unit')
            ->like('nama', $label . '%', 'after', false)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        if ($unit) return (int)$unit['id'];

        return ['ra' => 1, 'sd' => 2, 'smp' => 3][$sekolah] ?? null;
    }

    public function index()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $studentModel = new StudentModel();
        $teacherModel = new TeacherModel();
        $classModel = new ClassModel();
        $taModel = new AcademicYearModel();

        $filters = [];
        $kelasId = $this->request->getGet('kelas');
        $search = $this->request->getGet('search');
        $bidang = $this->request->getGet('bidang');
        $taFilter = $this->request->getGet('ta_id');
        if ($kelasId) $filters['kelas_id'] = $kelasId;
        if ($search) $filters['search'] = $search;
        if ($taFilter) $filters['ta_id'] = (int)$taFilter;

        $teachers = $teacherModel->getByActiveTa($sekolah, ['bidang' => $bidang, 'ta_id' => $filters['ta_id'] ?? null]);

        $nasabahModel = new NasabahModel();
        if ($sekolah && $sekolah !== 'admin') {
            $nasabahList = $nasabahModel->where('sekolah', $sekolah)->orderBy('nama', 'ASC')->findAll();
        } else {
            $nasabahList = $nasabahModel->orderBy('nama', 'ASC')->findAll();
        }

        $data = [
            'title' => 'Data Siswa & Guru',
            'students' => $studentModel->getWithClass($sekolah, $filters),
            'teachers' => $teachers,
            'nasabahList' => $nasabahList,
            'classes' => $classModel->getFiltered($sekolah),
            'academicYears' => $taModel->orderBy('tahun_ajaran', 'DESC')->findAll(),
            'activeTa' => $taModel->where('aktif', 1)->first(),
            'selectedTa' => $taFilter ?? '',
            'kelas' => $kelasId ?? '',
            'search' => $search ?? '',
            'bidang' => $bidang ?? '',
            'sekolahUser' => $sekolah,
        ];

        return $this->render('siswaguru/index', $data);
    }

    public function siswaStore()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $model = new StudentModel();

        $rules = [
            'nis' => 'permit_empty|max_length[30]|is_unique[tb_siswa.nis]',
            'nama' => 'required|min_length[3]|max_length[100]',
            'jenis_kelamin' => 'required|in_list[L,P]',
            'status' => 'permit_empty|in_list[aktif,lulus,pindah]',
            'nama_kelas' => 'required|max_length[50]',
            'sekolah' => 'required|in_list[ra,sd,smp]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $unit = $this->request->getPost('sekolah');
        $nis = trim($this->request->getPost('nis') ?? '');
        if (empty($nis)) {
            $nis = $this->generateNextNis($unit);
        }
        $sekolahSiswa = $sekolah !== 'admin' ? $sekolah : $unit;

        // Find or create class
        $namaKelas = trim($this->request->getPost('nama_kelas'));
        $kelasId = $this->findOrCreateClass($namaKelas, $unit);

        $status = $this->request->getPost('status') ?: 'aktif';

        $tanggalMasuk = $this->request->getPost('tanggal_masuk');
        if (empty($tanggalMasuk)) {
            $tanggalMasuk = date('Y-m-d');
        }

        $data = [
            'nis' => $nis,
            'nama' => $this->request->getPost('nama'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'status' => $status,
            'aktif' => $status === 'aktif' ? 1 : 0,
            'kelas_id' => $kelasId,
            'sekolah' => $sekolahSiswa,
            'nominal_spp' => (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', str_replace('.', '', $this->request->getPost('nominal_spp') ?: '0'))),
            'nominal_awal_tahun' => (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', str_replace('.', '', $this->request->getPost('nominal_awal_tahun') ?: '0'))),
            'tanggal_masuk' => $tanggalMasuk,
        ];

        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        if ($activeTa) $data['tahun_ajaran_id'] = $activeTa['id'];

        $model->save($data);
        $siswaId = $model->getInsertID();
        $this->autoGenerateStudentDaftarUlang($siswaId);
        $this->autoGenerateStudentSpp($siswaId);
        $this->autoCreateAccount('siswa', $siswaId, $data['sekolah']);
        return redirect()->to('/siswaguru')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function siswaUpdate()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $model = new StudentModel();
        $id = $this->request->getPost('id');

        if (!$id) {
            return redirect()->back()->with('error', 'ID siswa tidak valid.');
        }

        $siswa = $model->find($id);
        if (!$siswa) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $rules = [
            'nis' => "required|max_length[30]|is_unique[tb_siswa.nis,id,{$id}]",
            'nama' => 'required|min_length[3]|max_length[100]',
            'jenis_kelamin' => 'required|in_list[L,P]',
            'status' => 'permit_empty|in_list[aktif,lulus,pindah]',
            'nama_kelas' => 'required|max_length[50]',
            'sekolah' => 'required|in_list[ra,sd,smp]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $unit = $this->request->getPost('sekolah');
        $namaKelas = trim($this->request->getPost('nama_kelas'));
        $kelasId = $this->findOrCreateClass($namaKelas, $unit);
        $status = $this->request->getPost('status') ?: 'aktif';

        $sppBaru = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', str_replace('.', '', $this->request->getPost('nominal_spp') ?: '0')));
        $tagihanBaru = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', str_replace('.', '', $this->request->getPost('nominal_awal_tahun') ?: '0')));

        $sppLama = (float)($siswa['nominal_spp'] ?? 0);
        $tagihanLama = (float)($siswa['nominal_awal_tahun'] ?? 0);

        $tanggalMasuk = $this->request->getPost('tanggal_masuk');

        $data = [
            'nis' => $this->request->getPost('nis'),
            'nama' => $this->request->getPost('nama'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'status' => $status,
            'aktif' => $status === 'aktif' ? 1 : 0,
            'kelas_id' => $kelasId,
            'sekolah' => $sekolah !== 'admin' ? $sekolah : $unit,
            'nominal_spp' => $sppBaru,
            'nominal_awal_tahun' => $tagihanBaru,
        ];
        if (!empty($tanggalMasuk)) {
            $data['tanggal_masuk'] = $tanggalMasuk;
        }
        if (in_array($status, ['pindah', 'lulus'])) {
            $tglKeluar = $this->request->getPost('tanggal_keluar') ?: date('Y-m-d');
            $data['tanggal_keluar'] = $tglKeluar;
        }

        $model->update($id, $data);

        // Auto-update tagihan jika SPP atau Tagihan Awal berubah
        if ($sppBaru != $sppLama || $tagihanBaru != $tagihanLama) {
            $this->updateExistingBills($id, $sppBaru, $tagihanBaru);
        }

        // Hapus tagihan SPP yang belum dibayar setelah tanggal keluar
        if (in_array($status, ['pindah', 'lulus'])) {
            $this->cleanupBillsOnExit($id, $data['tanggal_keluar']);
        }

        return redirect()->to('/siswaguru')->with('success', 'Siswa berhasil diperbarui.');
    }

    private function updateExistingBills($siswaId, $sppBaru, $tagihanBaru)
    {
        $db = \Config\Database::connect();
        $taModel = new \App\Models\AcademicYearModel();
        $ta = $taModel->where('aktif', 1)->first();
        if (!$ta) return;

        // Update SPP bulanan yang masih belum bayar
        if ($sppBaru > 0) {
            $db->table('tb_tagihan_siswa')
                ->where('siswa_id', $siswaId)
                ->where('jenis_tagihan', 'SPP Bulanan')
                ->where('tahun_ajaran_id', $ta['id'])
                ->whereIn('status', ['belum_bayar', 'cicil'])
                ->update(['nominal' => $sppBaru]);
        }

        // Update Daftar Ulang yang masih belum bayar
        {
            $daftarUlang = $db->table('tb_tagihan_siswa')
                ->where('siswa_id', $siswaId)
                ->where('jenis_tagihan', 'Daftar Ulang')
                ->where('tahun_ajaran_id', $ta['id'])
                ->whereIn('status', ['belum_bayar', 'cicil'])
                ->get()
                ->getRowArray();

            if ($daftarUlang) {
                $nominalDu = $tagihanBaru;
                if ($nominalDu > 0) {
                    $db->table('tb_tagihan_siswa')
                        ->where('id', $daftarUlang['id'])
                        ->update(['nominal' => $nominalDu]);
                } else {
                    $db->table('tb_tagihan_siswa')
                        ->where('id', $daftarUlang['id'])
                        ->delete();
                }
            }
        }
    }

    private function cleanupBillsOnExit($siswaId, $tanggalKeluar)
    {
        $db = \Config\Database::connect();
        $taModel = new \App\Models\AcademicYearModel();
        $ta = $taModel->where('aktif', 1)->first();
        if (!$ta) return;

        // Hapus tagihan SPP yang bulan > bulan keluar (hanya yang belum dibayar)
        $bulanKeluar = (int)date('n', strtotime($tanggalKeluar));
        $tahunKeluar = (int)date('Y', strtotime($tanggalKeluar));
        $thnBulanKeluar = $tahunKeluar * 100 + $bulanKeluar;

        $bills = $db->table('tb_tagihan_siswa')
            ->where('siswa_id', $siswaId)
            ->where('jenis_tagihan', 'SPP Bulanan')
            ->where('tahun_ajaran_id', $ta['id'])
            ->whereIn('status', ['belum_bayar', 'cicil'])
            ->get()
            ->getResultArray();

        foreach ($bills as $bill) {
            $bulanBill = (int)date('n', strtotime($bill['created_at']));
            $tahunBill = (int)date('Y', strtotime($bill['created_at']));
            $thnBulanBill = $tahunBill * 100 + $bulanBill;

            if ($thnBulanBill > $thnBulanKeluar) {
                $db->table('tb_tagihan_siswa')->delete(['id' => $bill['id']]);
            }
        }
    }

    public function siswaImportExcel()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();

        $file = $this->request->getFile('file_excel');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Pilih file Excel terlebih dahulu.');
        }

        $ext = $file->getExtension();
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format file harus .xlsx atau .xls.');
        }

        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        if (!$activeTa) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }
        $taId = $activeTa['id'];

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext));
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getTempName());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        // Build header-index maps from all sheets
        $siswaRows = [];
        $guruRows = [];
        $tingkatEnum = ['A','B','1','2','3','4','5','6','7','8','9'];
        $unitLabels = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];

        for ($si = 0; $si < $spreadsheet->getSheetCount(); $si++) {
            $sheet = $spreadsheet->getSheet($si);
            $sheetName = strtolower($sheet->getTitle());
            $rows = $sheet->toArray();
            if (count($rows) < 2) continue;
            $headerRow = $rows[0] ?? [];
            $cleanHeaders = array_map(fn($c) => trim(strtoupper((string)$c)), $headerRow);

            // Map known column names to indices
            $colMap = [];
            foreach ($cleanHeaders as $i => $h) {
                if ($h === 'NIS') $colMap['nis'] = $i;
                elseif ($h === 'NIP') $colMap['nip'] = $i;
                elseif (in_array($h, ['NAMA', 'NAMA LENGKAP'])) $colMap['nama'] = $i;
                elseif (in_array($h, ['JENIS KELAMIN', 'JK', 'JENKEL', 'L/P'])) $colMap['jk'] = $i;
                elseif (in_array($h, ['STATUS'])) $colMap['status'] = $i;
                elseif (in_array($h, ['KELAS', 'NAMA KELAS', 'NAMA_KELAS'])) $colMap['kelas'] = $i;
                elseif (in_array($h, ['UNIT', 'SEKOLAH'])) $colMap['sekolah'] = $i;
                elseif (in_array($h, ['SPP PER BULAN', 'SPP', 'SPP / BULAN', 'NOMINAL_SPP'])) $colMap['spp'] = $i;
                elseif (in_array($h, ['TAGIHAN AWAL TAHUN', 'TAGIHAN', 'NOMINAL_AWAL_TAHUN'])) $colMap['tagihan'] = $i;
                elseif (in_array($h, ['TANGGAL MASUK', 'TANGGAL_MASUK', 'TGL MASUK', 'TGL_MASUK'])) $colMap['tanggal_masuk'] = $i;
            }

            $firstCellUpper = strtoupper(trim((string)($headerRow[0] ?? '')));
            $dataRows = array_slice($rows, 1);

            if ($firstCellUpper === 'NIS' || isset($colMap['nis'])) {
                foreach ($dataRows as $r) {
                    $r[] = json_encode($colMap); // store column map
                    $r[] = $sheetName;
                    $siswaRows[] = $r;
                }
            } elseif ($firstCellUpper === 'NIP' || isset($colMap['nip'])) {
                foreach ($dataRows as $r) {
                    $r[] = json_encode($colMap);
                    $r[] = $sheetName;
                    $guruRows[] = $r;
                }
            }
        }

        if (empty($siswaRows) && empty($guruRows)) {
            return redirect()->back()->with('error', 'File Excel kosong atau tidak memiliki data siswa (NIS) atau guru (NIP).');
        }

        $imported = 0;
        $updated = 0;
        $guruImported = 0;
        $errors = [];

        // ===== Process siswa sheets =====
        if (!empty($siswaRows)) {
            $classModel = new ClassModel();
            $siswaModel = new StudentModel();

            // Build existing class lookup
            $existingClasses = [];
            foreach ($classModel->findAll() as $c) {
                $existingClasses[$c['nama_kelas']] = $c['id'];
            }

            foreach ($siswaRows as $i => $row) {
                $rowCount = count($row);
                $colMap = json_decode($row[$rowCount - 2], true) ?: [];
                $sheetName = trim((string)$row[$rowCount - 1]);

                $nis = trim((string)($row[$colMap['nis']] ?? ''));
                $nama = trim((string)($row[$colMap['nama']] ?? ''));
                $jk = trim((string)($row[$colMap['jk']] ?? ''));
                $status = trim((string)($row[$colMap['status']] ?? ''));
                $namaKelas = trim((string)($row[$colMap['kelas']] ?? ''));
                $unitExcel = trim((string)($row[$colMap['sekolah']] ?? ''));

                // Parse SPP & Tagihan (handle "Rp" format)
                $sppRaw = $row[$colMap['spp']] ?? '0';
                $tagihanRaw = $row[$colMap['tagihan']] ?? '0';
                $spp = (float) str_replace(['Rp', '.', ',', ' '], ['', '', '.', ''], (string)$sppRaw);
                $tagihan = (float) str_replace(['Rp', '.', ',', ' '], ['', '', '.', ''], (string)$tagihanRaw);

                if (empty($nis)) { $errors[] = "Baris " . ($i + 1) . ": NIS kosong."; continue; }
                if (empty($nama)) { $errors[] = "Baris " . ($i + 1) . " (NIS: $nis): Nama kosong."; continue; }

                $jkUpper = strtoupper($jk);
                if ($jkUpper === 'LAKI-LAKI' || $jkUpper === 'LAKI' || $jkUpper === 'L') $jk = 'L';
                elseif ($jkUpper === 'PEREMPUAN' || $jkUpper === 'WANITA' || $jkUpper === 'CEWEK' || $jkUpper === 'P') $jk = 'P';
                if (!in_array($jk, ['L', 'P'])) {
                    $errors[] = "Baris " . ($i + 1) . " (NIS: $nis): Jenis kelamin harus L/P/Laki-laki/Perempuan.";
                    continue;
                }

                // Determine sekolah unit
                $unitFromSheet = strtolower(preg_replace('/\s+(siswa|guru)$/i', '', $sheetName));
                $unitMap = ['ra' => 'ra', 'sd' => 'sd', 'smp' => 'smp'];
                $unit = $unitMap[$unitFromSheet] ?? null;
                if (!$unit && !empty($unitExcel)) {
                    $unitExcelLower = strtolower($unitExcel);
                    if (in_array($unitExcelLower, ['ra', 'sd', 'smp'])) $unit = $unitExcelLower;
                    elseif ($unitExcelLower === 'ra') $unit = 'ra';
                    elseif ($unitExcelLower === 'sd') $unit = 'sd';
                    elseif ($unitExcelLower === 'smp') $unit = 'smp';
                }
                if (!$unit) $unit = 'smp';
                $sekolahSiswa = $sekolah !== 'admin' ? $sekolah : $unit;

                // Auto-create class from nama_kelas
                if (!empty($namaKelas)) {
                    if (!isset($existingClasses[$namaKelas])) {
                        // Extract tingkat
                        $tingkat = 'A';
                        if (preg_match('/^(\d+)-/', $namaKelas, $m)) {
                            $tingkat = $m[1];
                        } elseif (preg_match('/^RA\s+(.+)/i', $namaKelas, $m)) {
                            $tingkat = strtoupper(trim($m[1]));
                        } elseif (preg_match('/^([A-Z])\b/i', $namaKelas, $m)) {
                            $tingkat = strtoupper($m[1]);
                        }
                        if (!in_array($tingkat, $tingkatEnum, true)) $tingkat = 'A';

                        $classModel->save([
                            'nama_kelas' => $namaKelas,
                            'tingkat' => $tingkat,
                            'jurusan' => null,
                            'sekolah' => $unit,
                        ]);
                        $existingClasses[$namaKelas] = $classModel->getInsertID();
                    }
                    $kelasId = $existingClasses[$namaKelas];
                } else {
                    // Fallback: first class in unit
                    $firstForUnit = null;
                    foreach ($existingClasses as $nk => $id) {
                        $c = $classModel->find($id);
                        if ($c && $c['sekolah'] === $unit) { $firstForUnit = $id; break; }
                    }
                    if (!$firstForUnit) {
                        $defNk = $unitLabels[$unit] . ' A';
                        if (!isset($existingClasses[$defNk])) {
                            $classModel->save([
                                'nama_kelas' => $defNk, 'tingkat' => 'A',
                                'jurusan' => null, 'sekolah' => $unit,
                            ]);
                            $existingClasses[$defNk] = $classModel->getInsertID();
                        }
                        $firstForUnit = $existingClasses[$defNk];
                    }
                    $kelasId = $firstForUnit;
                }

                $statusDb = 'aktif';
                $statusUpper = strtoupper($status);
                if ($statusUpper === 'LULUS') $statusDb = 'lulus';
                elseif ($statusUpper === 'PINDAH') $statusDb = 'pindah';

                $data = [
                    'nis' => $nis,
                    'nama' => $nama,
                    'jenis_kelamin' => $jk,
                    'kelas_id' => $kelasId,
                    'sekolah' => $sekolahSiswa,
                    'nominal_spp' => $spp > 0 ? $spp : null,
                    'nominal_awal_tahun' => $tagihan > 0 ? $tagihan : null,
                    'tahun_ajaran_id' => $taId,
                    'status' => $statusDb,
                    'aktif' => $statusDb === 'aktif' ? 1 : 0,
                ];

                $tglMasukExcel = $colMap['tanggal_masuk'] ?? null ? trim((string)($row[$colMap['tanggal_masuk']] ?? '')) : '';
                if (!empty($tglMasukExcel)) {
                    $ts = strtotime($tglMasukExcel);
                    if ($ts) $data['tanggal_masuk'] = date('Y-m-d', $ts);
                }

                try {
                    $existing = $siswaModel->where('nis', $nis)->first();
                    if ($existing) {
                        // UPDATE existing student
                        $siswaModel->update($existing['id'], $data);
                        $siswaId = $existing['id'];
                        $updated++;
                        // Update existing bills to match new SPP/Tagihan
                        $this->updateExistingBills($siswaId, $spp, $tagihan);
                    } else {
                        // INSERT new student
                        $siswaModel->save($data);
                        $siswaId = $siswaModel->getInsertID();
                        $imported++;
                    }
                    // Generate missing bills for any month not yet created
                    $this->autoGenerateStudentDaftarUlang($siswaId);
                    $this->autoGenerateStudentSpp($siswaId);
                    // Auto-create savings account for new students
                    if (!$existing) {
                        $this->autoCreateAccount('siswa', $siswaId, $sekolahSiswa);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($i + 1) . " (NIS: $nis): " . $e->getMessage();
                }
            }
        }

        // ===== Process guru sheets =====
        if (!empty($guruRows)) {
            $guruModel = new TeacherModel();

            foreach ($guruRows as $i => $row) {
                $rowCount = count($row);
                $colMap = json_decode($row[$rowCount - 2], true) ?: [];
                $sheetName = trim((string)$row[$rowCount - 1]);

                $nip = trim((string)($row[$colMap['nip']] ?? ''));
                $nama = trim((string)($row[$colMap['nama']] ?? ''));
                $jk = trim((string)($row[$colMap['jk']] ?? ''));
                $bidang = trim((string)($row[$colMap['bidang'] ?? ''] ?? ''));
                $statusImport = trim((string)($row[$colMap['status']] ?? ''));

                if (empty($nip)) { $errors[] = "Baris guru " . ($i + 1) . ": NIP kosong."; continue; }
                if (empty($nama)) { $errors[] = "Baris guru " . ($i + 1) . " (NIP: $nip): Nama kosong."; continue; }

                $jkUpper = strtoupper($jk);
                if ($jkUpper === 'LAKI-LAKI' || $jkUpper === 'LAKI') $jk = 'L';
                elseif ($jkUpper === 'PEREMPUAN' || $jkUpper === 'WANITA' || $jkUpper === 'CEWEK') $jk = 'P';
                if (!in_array($jk, ['L', 'P'])) {
                    $errors[] = "Baris guru " . ($i + 1) . " (NIP: $nip): Jenis kelamin harus L/P/Laki-laki/Perempuan.";
                    continue;
                }

                $unitMap = ['ra' => 'ra', 'sd' => 'sd', 'smp' => 'smp', 'guru ra' => 'ra', 'guru sd' => 'sd', 'guru smp' => 'smp'];
                $sekolahGuruFinal = $sekolah !== 'admin' ? $sekolah : ($unitMap[$sheetName] ?? 'smp');

                $aktif = 1;
                $statusUpper = strtoupper($statusImport);
                if ($statusUpper === 'TIDAK AKTIF') $aktif = 0;

                $data = [
                    'nip' => $nip, 'nama' => $nama,
                    'jenis_kelamin' => $jk, 'bidang' => $bidang ?: null,
                    'sekolah' => $sekolahGuruFinal, 'unit_id' => $this->resolveUnitId($sekolahGuruFinal),
                    'tahun_ajaran_id' => $taId,
                    'aktif' => $aktif,
                ];

                try {
                    $existing = $guruModel->where('nip', $nip)->first();
                    if ($existing) {
                        $guruModel->update($existing['id'], $data);
                        $guruImported++;
                    } else {
                        $guruModel->save($data);
                        $guruId = $guruModel->getInsertID();
                        $this->autoCreateAccount('guru', $guruId, $sekolahGuruFinal);
                        $guruImported++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris guru " . ($i + 1) . " (NIP: $nip): " . $e->getMessage();
                }
            }
        }

        $parts = [];
        if ($imported > 0) $parts[] = "$imported siswa baru";
        if ($updated > 0) $parts[] = "$updated siswa diperbarui";
        if ($guruImported > 0) $parts[] = "$guruImported guru";
        $msg = (count($parts) > 0 ? implode(', ', $parts) : 'Tidak ada data') . ' berhasil diimport.';

        if (!empty($errors)) {
            $msg .= " " . count($errors) . " error:<br>" . implode('<br>', array_slice($errors, 0, 20));
            if (count($errors) > 20) $msg .= '<br>...dan ' . (count($errors) - 20) . ' error lainnya.';
            return redirect()->to('/siswaguru')->with('error', $msg);
        }

        return redirect()->to('/siswaguru')->with('success', $msg);
    }

    private function findOrCreateClass(string $namaKelas, string $sekolah): int
    {
        $classModel = new ClassModel();
        $existing = $classModel->where('nama_kelas', $namaKelas)->where('sekolah', $sekolah)->first();
        if ($existing) return $existing['id'];

        $tingkat = $this->detectTingkat($namaKelas, $sekolah);
        $classModel->save([
            'nama_kelas' => $namaKelas,
            'tingkat'    => $tingkat,
            'jurusan'    => null,
            'sekolah'    => $sekolah,
        ]);
        return $classModel->getInsertID();
    }

    public function siswaDelete()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $model = new StudentModel();
        $id = $this->request->getPost('id');

        if (!$id || !$model->find($id)) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Delete savings transactions
            $akun = $db->table('tb_tabungan')->where('siswa_id', $id)->get()->getRowArray();
            if ($akun) {
                $db->table('tb_transaksi_tabungan')->where('akun_id', $akun['id'])->delete();
                $db->table('tb_tabungan')->delete(['id' => $akun['id']]);
            }

            // Delete bills and payments
            $bills = $db->table('tb_tagihan_siswa')->where('siswa_id', $id)->get()->getResultArray();
            foreach ($bills as $bill) {
                $db->table('tb_pembayaran')->where('tagihan_id', $bill['id'])->delete();
            }
            $db->table('tb_tagihan_siswa')->where('siswa_id', $id)->delete();

            // Delete student
            $model->delete($id);

            $db->transCommit();
            return redirect()->to('/siswaguru')->with('success', 'Siswa dan seluruh data terkait berhasil dihapus.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Gagal menghapus siswa: ' . $e->getMessage());
        }
    }

    public function siswaDeleteBatch()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $ids = $this->request->getPost('ids');
        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Pilih siswa yang akan dihapus.');
        }

        $model = new StudentModel();
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            foreach ($ids as $id) {
                if (!$model->find($id)) continue;

                $akun = $db->table('tb_tabungan')->where('siswa_id', $id)->get()->getRowArray();
                if ($akun) {
                    $db->table('tb_transaksi_tabungan')->where('akun_id', $akun['id'])->delete();
                    $db->table('tb_tabungan')->delete(['id' => $akun['id']]);
                }

                $bills = $db->table('tb_tagihan_siswa')->where('siswa_id', $id)->get()->getResultArray();
                foreach ($bills as $bill) {
                    $db->table('tb_pembayaran')->where('tagihan_id', $bill['id'])->delete();
                }
                $db->table('tb_tagihan_siswa')->where('siswa_id', $id)->delete();

                $model->delete($id);
            }

            $db->transCommit();
            return redirect()->back()->with('success', count($ids) . ' siswa berhasil dihapus.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Gagal menghapus siswa: ' . $e->getMessage());
        }
    }

    public function siswaPindah()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $model = new StudentModel();
        $id = $this->request->getPost('id');
        $keterangan = $this->request->getPost('keterangan');

        if (!$id || !$model->find($id)) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $model->update($id, [
            'status' => 'pindah',
            'aktif'  => 0,
            'keterangan_pindah' => $keterangan,
            'tanggal_keluar' => date('Y-m-d'),
        ]);

        $this->cleanupBillsOnExit($id, date('Y-m-d'));

        return redirect()->to('/siswaguru')->with('success', 'Siswa ditandai sebagai pindah.');
    }

    public function siswaPindahBatch()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $ids = $this->request->getPost('ids');
        $keterangan = $this->request->getPost('keterangan');
        $tanggalKeluar = $this->request->getPost('tanggal_keluar') ?: date('Y-m-d');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Pilih siswa yang akan ditandai pindah.');
        }

        $model = new StudentModel();
        $count = 0;

        foreach ($ids as $id) {
            if ($model->find($id)) {
                $model->update($id, [
                    'status' => 'pindah',
                    'aktif'  => 0,
                    'keterangan_pindah' => $keterangan,
                    'tanggal_keluar' => $tanggalKeluar,
                ]);
                $this->cleanupBillsOnExit($id, $tanggalKeluar);
                $count++;
            }
        }

        return redirect()->back()->with('success', "{$count} siswa ditandai sebagai pindah.");
    }

    public function guruDeleteBatch()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $ids = $this->request->getPost('ids');
        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Pilih guru yang akan dihapus.');
        }

        $model = new TeacherModel();
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            foreach ($ids as $id) {
                $akun = $db->table('tb_tabungan')->where('guru_id', $id)->get()->getRowArray();
                if ($akun) {
                    $db->table('tb_transaksi_tabungan')->where('akun_id', $akun['id'])->delete();
                    $db->table('tb_tabungan')->delete(['id' => $akun['id']]);
                }
                $model->delete($id);
            }

            $db->transCommit();
            return redirect()->back()->with('success', count($ids) . ' guru berhasil dihapus.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Gagal menghapus guru: ' . $e->getMessage());
        }
    }

    public function siswaLulus()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $model = new StudentModel();
        $id = $this->request->getPost('id');

        if (!$id || !$model->find($id)) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $model->update($id, ['status' => 'lulus', 'aktif' => 0]);
        return redirect()->to('/siswaguru')->with('success', 'Siswa ditandai sebagai lulus.');
    }

    public function siswaPindahKelas()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $model = new StudentModel();
        $kelasId = $this->request->getPost('kelas_id');
        $ids = $this->request->getPost('siswa_ids');
        $singleId = $this->request->getPost('id');

        if (!$kelasId) {
            return redirect()->back()->with('error', 'Pilih kelas tujuan.');
        }

        if (!empty($ids)) {
            $first = $model->find($ids[0]);
            $sourceKelasId = $first ? $first['kelas_id'] : null;
            $count = 0;
            foreach ($ids as $id) {
                if ($model->find($id)) {
                    $model->update($id, ['kelas_id' => $kelasId]);
                    $count++;
                }
            }
            if ($count > 0) {
                return redirect()->to($sourceKelasId ? "/siswaguru/kelas/{$sourceKelasId}" : '/siswaguru')
                    ->with('success', "{$count} siswa berhasil dipindah kelas.");
            }
            return redirect()->back()->with('error', 'Tidak ada siswa yang dipilih.');
        }

        if (!$singleId || !$model->find($singleId)) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $model->update($singleId, ['kelas_id' => $kelasId]);
        return redirect()->to('/siswaguru')->with('success', 'Siswa berhasil dipindah kelas.');
    }

    public function siswaNaikKelas()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $model = new StudentModel();
        $classModel = new ClassModel();
        $taModel = new AcademicYearModel();
        $ta = $taModel->where('aktif', 1)->first();
        $taId = $ta['id'] ?? 0;

        $siswaIds = $this->request->getPost('siswa_ids');

        if (!empty($siswaIds)) {
            $students = $model->where('status', 'aktif')->where('tahun_ajaran_id', $taId)->whereIn('id', $siswaIds)->findAll();
        } else {
            $kelasId = $this->request->getPost('kelas_id');
            $students = $kelasId
                ? $model->where('status', 'aktif')->where('tahun_ajaran_id', $taId)->where('kelas_id', $kelasId)->findAll()
                : $model->where('status', 'aktif')->where('tahun_ajaran_id', $taId)->findAll();
        }
        $allClasses = $classModel->getFiltered($sekolah);

        $schoolLevels = [
            'ra'  => ['A', 'B'],
            'sd'  => ['1', '2', '3', '4', '5', '6'],
            'smp' => ['7', '8', '9'],
        ];

        $promoted = 0;

        foreach ($students as $s) {
            $class = null;
            foreach ($allClasses as $c) {
                if ($c['id'] == $s['kelas_id']) {
                    $class = $c;
                    break;
                }
            }
            if (!$class) continue;

            $levels = $schoolLevels[$class['sekolah']] ?? null;
            if (!$levels) continue;

            $idx = array_search($class['tingkat'], $levels);
            if ($idx === false) continue;

            if ($idx === count($levels) - 1) {
                $model->update($s['id'], ['status' => 'lulus']);
                $promoted++;
            } else {
                $nextTingkat = $levels[$idx + 1];
                $nextClass = null;
                foreach ($allClasses as $c) {
                    if ($c['sekolah'] === $class['sekolah']
                        && $c['tingkat'] === $nextTingkat
                        && $c['jurusan'] === $class['jurusan']) {
                        $nextClass = $c;
                        break;
                    }
                }
                if ($nextClass) {
                    $data = ['kelas_id' => $nextClass['id']];
                    // Also advance to next academic year if exists
                    $nextTa = $taModel->where('id >', $taId)->orderBy('id', 'ASC')->first();
                    if ($nextTa) $data['tahun_ajaran_id'] = $nextTa['id'];
                    $model->update($s['id'], $data);
                    $promoted++;
                }
            }
        }

        $redirectUrl = '/siswaguru';
        if (!empty($siswaIds)) {
            $s = $students[0] ?? null;
            if ($s) $redirectUrl = "/siswaguru/kelas/{$s['kelas_id']}";
        } elseif ($kelasId = $this->request->getPost('kelas_id')) {
            $redirectUrl = "/siswaguru/kelas/{$kelasId}";
        }
        return redirect()->to($redirectUrl)->with('success', "{$promoted} siswa berhasil naik kelas.");
    }

    public function siswaTurunKelas()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $model = new StudentModel();
        $classModel = new ClassModel();
        $taModel = new AcademicYearModel();
        $ta = $taModel->where('aktif', 1)->first();
        $taId = $ta['id'] ?? 0;

        $siswaIds = $this->request->getPost('siswa_ids');

        if (!empty($siswaIds)) {
            $students = $model->where('status', 'aktif')->where('tahun_ajaran_id', $taId)->whereIn('id', $siswaIds)->findAll();
        } else {
            $kelasId = $this->request->getPost('kelas_id');
            $students = $kelasId
                ? $model->where('status', 'aktif')->where('tahun_ajaran_id', $taId)->where('kelas_id', $kelasId)->findAll()
                : $model->where('status', 'aktif')->where('tahun_ajaran_id', $taId)->findAll();
        }
        $allClasses = $classModel->getFiltered($sekolah);

        $schoolLevels = [
            'ra'  => ['A', 'B'],
            'sd'  => ['1', '2', '3', '4', '5', '6'],
            'smp' => ['7', '8', '9'],
        ];

        $demoted = 0;

        foreach ($students as $s) {
            $class = null;
            foreach ($allClasses as $c) {
                if ($c['id'] == $s['kelas_id']) {
                    $class = $c;
                    break;
                }
            }
            if (!$class) continue;

            $levels = $schoolLevels[$class['sekolah']] ?? null;
            if (!$levels) continue;

            $idx = array_search($class['tingkat'], $levels);
            if ($idx === false || $idx === 0) continue;

            $prevTingkat = $levels[$idx - 1];
            $prevClass = null;
            foreach ($allClasses as $c) {
                if ($c['sekolah'] === $class['sekolah']
                    && $c['tingkat'] === $prevTingkat
                    && $c['jurusan'] === $class['jurusan']) {
                    $prevClass = $c;
                    break;
                }
            }
            if ($prevClass) {
                $data = ['kelas_id' => $prevClass['id']];
                // Also go back to previous academic year if exists
                $prevTa = $taModel->where('id <', $taId)->orderBy('id', 'DESC')->first();
                if ($prevTa) $data['tahun_ajaran_id'] = $prevTa['id'];
                $model->update($s['id'], $data);
                $demoted++;
            }
        }

        $redirectUrl = '/siswaguru';
        if (!empty($siswaIds)) {
            $s = $students[0] ?? null;
            if ($s) $redirectUrl = "/siswaguru/kelas/{$s['kelas_id']}";
        } elseif ($kelasId = $this->request->getPost('kelas_id')) {
            $redirectUrl = "/siswaguru/kelas/{$kelasId}";
        }
        return redirect()->to($redirectUrl)->with('success', "{$demoted} siswa berhasil turun kelas.");
    }

    public function kelas()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $classModel = new ClassModel();
        $studentModel = new StudentModel();

        $classes = $classModel->getFiltered($sekolah);

        // Count students per class
        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $taId = $activeTa ? $activeTa['id'] : 0;
        $counts = [];
        foreach ($classes as $c) {
            $counts[$c['id']] = $studentModel->where('kelas_id', $c['id'])->where('status', 'aktif')->where('tahun_ajaran_id', $taId)->countAllResults();
        }

        $data = [
            'title' => 'Manajemen Kelas',
            'classes' => $classes,
            'counts' => $counts,
            'sekolahUser' => $sekolah,
        ];

        return $this->render('siswaguru/kelas', $data);
    }

    public function kelasDetail($kelasId)
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $classModel = new ClassModel();
        $studentModel = new StudentModel();

        $class = null;
        $allClasses = $classModel->getFiltered($sekolah);
        foreach ($allClasses as $c) {
            if ($c['id'] == $kelasId) {
                $class = $c;
                break;
            }
        }

        if (!$class) {
            return redirect()->to('/siswaguru/kelas')->with('error', 'Kelas tidak ditemukan.');
        }

        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        $taId = $activeTa ? $activeTa['id'] : 0;
        $students = $studentModel->select('tb_siswa.*')
            ->where('tb_siswa.kelas_id', $kelasId)
            ->where('tb_siswa.status', 'aktif')
            ->where('tb_siswa.tahun_ajaran_id', $taId)
            ->findAll();

        $data = [
            'title' => 'Kelas ' . $class['nama_kelas'],
            'class' => $class,
            'students' => $students,
            'classes' => $allClasses,
            'sekolahUser' => $sekolah,
        ];

        return $this->render('siswaguru/kelas_detail', $data);
    }

    public function kelasStore()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        if ($sekolah !== 'admin') {
            return redirect()->back()->with('error', 'Hanya admin yang bisa menambah kelas.');
        }

        $rules = [
            'nama_kelas' => 'required|max_length[50]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $nama_kelas = trim($this->request->getPost('nama_kelas'));
        $unit = $this->request->getPost('sekolah');

        if (!in_array($unit, ['ra', 'sd', 'smp'], true)) {
            return redirect()->back()->with('error', 'Unit harus dipilih.');
        }

        $classModel = new ClassModel();
        $classModel->insert([
            'nama_kelas' => $nama_kelas,
            'tingkat'    => $this->detectTingkat($nama_kelas, $unit),
            'jurusan'    => null,
            'sekolah'    => $unit,
        ]);

        return redirect()->to('/siswaguru/kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function kelasUpdate()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        if ($sekolah !== 'admin') {
            return redirect()->back()->with('error', 'Hanya admin yang bisa mengubah kelas.');
        }

        $classModel = new ClassModel();
        $id = $this->request->getPost('id');

        if (!$id || !$classModel->find($id)) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        $rules = [
            'nama_kelas' => 'required|max_length[50]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $nama_kelas = trim($this->request->getPost('nama_kelas'));
        $unit = $this->request->getPost('sekolah');

        if (!in_array($unit, ['ra', 'sd', 'smp'], true)) {
            return redirect()->back()->with('error', 'Unit harus dipilih.');
        }

        $classModel->update($id, [
            'nama_kelas' => $nama_kelas,
            'tingkat'    => $this->detectTingkat($nama_kelas, $unit),
            'jurusan'    => null,
            'sekolah'    => $unit,
        ]);

        return redirect()->to('/siswaguru/kelas')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function kelasDelete()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        if ($sekolah !== 'admin') {
            return redirect()->back()->with('error', 'Hanya admin yang bisa menghapus kelas.');
        }

        $classModel = new ClassModel();
        $id = $this->request->getPost('id');

        if (!$id || !$classModel->find($id)) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        // Check if class has active students
        $studentModel = new StudentModel();
        $count = $studentModel->where('kelas_id', $id)->where('status', 'aktif')->countAllResults();
        if ($count > 0) {
            return redirect()->back()->with('error', "Kelas masih memiliki {$count} siswa aktif. Pindahkan siswa terlebih dahulu.");
        }

        $classModel->delete($id);
        return redirect()->to('/siswaguru/kelas')->with('success', 'Kelas berhasil dihapus.');
    }

    private function detectTingkat(string $nama_kelas, string $unit): string
    {
        if ($unit === 'ra') {
            $m = null;
            if (preg_match('/^RA\s+(.+)/i', $nama_kelas, $m)) {
                $t = strtoupper(trim($m[1]));
                if (in_array($t, ['A', 'B'], true)) return $t;
            }
            return 'A';
        }
        if (preg_match('/^(\d+)-/', $nama_kelas, $m)) {
            $num = (int)$m[1];
            return in_array($num, range(1, 9), true) ? (string)$num : '1';
        }
        return '1';
    }

    public function exportCsv()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $type = $this->request->getGet('type') ?: 'siswa';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem Keuangan Sekolah')
            ->setLastModifiedBy('Sistem Keuangan Sekolah')
            ->setTitle('Data ' . ($type === 'siswa' ? 'Siswa' : 'Guru'))
            ->setDescription('Backup data ' . ($type === 'siswa' ? 'siswa' : 'guru'));

        $sheetIndex = 0;

        if ($type === 'siswa') {
            $model = new StudentModel();
            $allStudents = $model->getWithClass($sekolah);

            $columns = ['NIS', 'Nama', 'Jenis Kelamin', 'Status', 'Kelas', 'Unit', 'SPP Per Bulan', 'Tagihan Awal Tahun'];
            $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            $unitLabelMap = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];

            $unitLabels = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];
            $unitColors = ['ra' => '6D28D9', 'sd' => '047857', 'smp' => '1E40AF'];
            $unitOrder = ['ra', 'sd', 'smp'];

            $units = ($sekolah && $sekolah !== 'admin')
                ? [$sekolah => $unitLabels[$sekolah] ?? $sekolah]
                : $unitLabels;

            foreach ($unitOrder as $unitKey) {
                if (!isset($units[$unitKey])) continue;

                $unitStudents = array_filter($allStudents, fn($s) => ($s['sekolah'] ?? '') === $unitKey);
                if (empty($unitStudents)) continue;
                $unitStudents = array_values($unitStudents);

                if ($sheetIndex === 0) {
                    $sheet = $spreadsheet->getActiveSheet();
                } else {
                    $sheet = $spreadsheet->createSheet();
                }
                $sheet->setTitle($units[$unitKey]);
                $sheetIndex++;

                foreach ($columns as $i => $col) {
                    $sheet->setCellValue($colLetters[$i] . '1', $col);
                }
                $lastHeaderCol = $colLetters[count($columns) - 1];
                $sheet->getStyle('A1:' . $lastHeaderCol . '1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $unitColors[$unitKey]]],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);
                $sheet->getRowDimension('1')->setRowHeight(20);

                $row = 2;
                foreach ($unitStudents as $s) {
                    $sheet->setCellValue('A' . $row, $s['nis']);
                    $sheet->setCellValue('B' . $row, $s['nama']);
                    $sheet->setCellValue('C' . $row, $s['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan');
                    $sheet->setCellValue('D' . $row, $s['status'] ?? 'aktif');
                    $sheet->setCellValue('E' . $row, $s['nama_kelas'] ?? '');
                    $sheet->setCellValue('F' . $row, $unitLabelMap[$s['sekolah']] ?? strtoupper($s['sekolah']));
                    $sheet->setCellValue('G' . $row, (float)($s['nominal_spp'] ?? 0));
                    $sheet->setCellValue('H' . $row, (float)($s['nominal_awal_tahun'] ?? 0));
                    $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
                    $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
                    $row++;
                }

                $dataRange = 'A2:H' . ($row - 1);
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(10);
                $sheet->getColumnDimension('G')->setWidth(16);
                $sheet->getColumnDimension('H')->setWidth(18);
            }
        } else {
            $teacherModel = new TeacherModel();
            $allTeachers = $teacherModel->getByActiveTa($sekolah);

            $guruColumns = ['NIP', 'Nama', 'Jenis Kelamin', 'Bidang', 'Sekolah', 'Status'];
            $guruLetters = ['A', 'B', 'C', 'D', 'E', 'F'];
            $guruColors = ['ra' => '7C3AED', 'sd' => '059669', 'smp' => '2563EB'];

            $unitGuruLabels = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];
            $unitOrder = ['ra', 'sd', 'smp'];

            $guruUnits = ($sekolah && $sekolah !== 'admin')
                ? [$sekolah => $unitGuruLabels[$sekolah] ?? $sekolah]
                : $unitGuruLabels;

            foreach ($unitOrder as $unitKey) {
                if (!isset($guruUnits[$unitKey])) continue;
                $unitTeachers = array_filter($allTeachers, fn($t) => ($t['sekolah'] ?? '') === $unitKey);
                if (empty($unitTeachers)) continue;

                if ($sheetIndex === 0) {
                    $sheet = $spreadsheet->getActiveSheet();
                } else {
                    $sheet = $spreadsheet->createSheet();
                }
                $sheet->setTitle($guruUnits[$unitKey]);
                $sheetIndex++;

                $lastGuruCol = $guruLetters[count($guruColumns) - 1];
                foreach ($guruColumns as $i => $col) {
                    $sheet->setCellValue($guruLetters[$i] . '1', $col);
                }
                $sheet->getStyle('A1:' . $lastGuruCol . '1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $guruColors[$unitKey]]],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);
                $sheet->getRowDimension('1')->setRowHeight(20);

                $row = 2;
                $unitLabelMap = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];
                foreach ($unitTeachers as $t) {
                    $sheet->setCellValue('A' . $row, $t['nip']);
                    $sheet->setCellValue('B' . $row, $t['nama']);
                    $sheet->setCellValue('C' . $row, $t['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan');
                    $sheet->setCellValue('D' . $row, $t['bidang'] ?? '');
                    $sheet->setCellValue('E' . $row, $unitLabelMap[$t['sekolah']] ?? strtoupper($t['sekolah']));
                    $sheet->setCellValue('F' . $row, $t['aktif'] ? 'Aktif' : 'Tidak Aktif');
                    $row++;
                }

                $dataRange = 'A2:' . $lastGuruCol . ($row - 1);
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(12);
            }
        }

        if ($sheetIndex > 0) {
            $defaultSheet = $spreadsheet->getSheetByName('Worksheet');
            if ($defaultSheet) {
                $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($defaultSheet));
            }
        } else {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Tidak ada data');
        }

        $filename = 'data-' . $type . '-' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function guruStore()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $model = new TeacherModel();

        $rules = [
            'nip' => 'required|max_length[30]|is_unique[tb_guru.nip]',
            'nama' => 'required|min_length[3]|max_length[100]',
            'jenis_kelamin' => 'required|in_list[L,P]',
            'sekolah' => 'required|in_list[ra,sd,smp]',
            'aktif' => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = [
            'nip' => $this->request->getPost('nip'),
            'nama' => $this->request->getPost('nama'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'bidang' => $this->request->getPost('bidang') ?: null,
            'sekolah' => $sekolah !== 'admin' ? $sekolah : $this->request->getPost('sekolah'),
            'unit_id' => $this->resolveUnitId($sekolah !== 'admin' ? $sekolah : $this->request->getPost('sekolah')),
            'aktif' => (int)($this->request->getPost('aktif') ?: 1),
        ];

        $taModel = new \App\Models\AcademicYearModel();
        $activeTa = $taModel->where('aktif', 1)->first();
        if ($activeTa) $data['tahun_ajaran_id'] = $activeTa['id'];

        $model->save($data);
        $guruId = $model->getInsertID();
        $this->autoCreateAccount('guru', $guruId, $data['sekolah']);

        $this->syncGuruToTht($data['nip'], $data['nama'], $data['sekolah']);

        return redirect()->to('/siswaguru')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function guruUpdate()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $sekolah = $this->getSekolah();
        $model = new TeacherModel();
        $id = $this->request->getPost('id');

        if (!$id) {
            return redirect()->back()->with('error', 'ID guru tidak valid.');
        }

        $guru = $model->find($id);
        if (!$guru) {
            return redirect()->back()->with('error', 'Guru tidak ditemukan.');
        }

        $rules = [
            'nip' => "required|max_length[30]|is_unique[tb_guru.nip,id,{$id}]",
            'nama' => 'required|min_length[3]|max_length[100]',
            'jenis_kelamin' => 'required|in_list[L,P]',
            'sekolah' => 'required|in_list[ra,sd,smp]',
            'aktif' => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = [
            'nip' => $this->request->getPost('nip'),
            'nama' => $this->request->getPost('nama'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
            'bidang' => $this->request->getPost('bidang') ?: null,
            'sekolah' => $sekolah !== 'admin' ? $sekolah : $this->request->getPost('sekolah'),
            'unit_id' => $this->resolveUnitId($sekolah !== 'admin' ? $sekolah : $this->request->getPost('sekolah')),
            'aktif' => (int)($this->request->getPost('aktif') ?: 1),
        ];

        $model->update($id, $data);

        $this->syncGuruToTht($data['nip'], $data['nama'], $data['sekolah']);

        return redirect()->to('/siswaguru')->with('success', 'Guru berhasil diperbarui.');
    }

    public function guruDelete()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $model = new TeacherModel();
        $id = $this->request->getPost('id');

        if (!$id || !$model->find($id)) {
            return redirect()->back()->with('error', 'Guru tidak ditemukan.');
        }

        $guru = $model->find($id);
        $nip = $guru['nip'] ?? null;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Hapus transaksi tabungan
            $akun = $db->table('tb_tabungan')->where('guru_id', $id)->get()->getRowArray();
            if ($akun) {
                $db->table('tb_transaksi_tabungan')->where('akun_id', $akun['id'])->delete();
                $db->table('tb_tabungan')->delete(['id' => $akun['id']]);
            }

            if ($nip) {
                $db->table('tb_transaksi_tht')->where('guru_id', function($q) use ($nip) {
                    $q->select('id')->from('tb_guru')->where('nip', $nip);
                })->delete();
                $db->table('tb_guru')->where('nip', $nip)->delete();
            }

            // Hapus guru
            $model->delete($id);

            $db->transCommit();
            return redirect()->to('/siswaguru')->with('success', 'Guru dan seluruh data terkait berhasil dihapus.');
        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Gagal menghapus guru: ' . $e->getMessage());
        }
    }

    private function autoGenerateStudentSpp($siswaId)
    {
        $siswaModel = new StudentModel();
        $classModel = new ClassModel();
        $billModel = new \App\Models\BillModel();
        $taModel = new AcademicYearModel();

        $siswa = $siswaModel->find($siswaId);
        if (!$siswa || $siswa['status'] !== 'aktif') return;

        $ta = $taModel->where('aktif', 1)->first();
        if (!$ta) return;

        $nominal = (float)($siswa['nominal_spp'] ?? 0);
        if ($nominal <= 0 && !empty($siswa['kelas_id'])) {
            $class = $classModel->find($siswa['kelas_id']);
            $nominal = (float)($class['nominal_spp'] ?? 0);
        }
        if ($nominal <= 0) return;

        // Check if student has Daftar Ulang - skip SPP for DU month
        $nominalDu = (float)($siswa['nominal_awal_tahun'] ?? 0);
        if ($nominalDu <= 0 && !empty($siswa['kelas_id'])) {
            $class = $classModel->find($siswa['kelas_id']);
            $nominalDu = (float)($class['nominal_awal_tahun'] ?? 0);
        }
        $hasDu = $nominalDu > 0;

        $bulanTA = $this->getBulanTA($ta['tahun_ajaran']);
        if (!$bulanTA) return;

        $db = \Config\Database::connect();

        $tglMasuk = $siswa['tanggal_masuk'] ?? $siswa['created_at'] ?? date('Y-m-d');
        $thnBulanMulai = (int)date('Y', strtotime($tglMasuk)) * 100 + (int)date('n', strtotime($tglMasuk));
        $duBulan = $hasDu ? (int)date('n', strtotime($tglMasuk)) : 7;

        foreach ($bulanTA as $bulan) {
            // Skip SPP for the month covered by Daftar Ulang
            if ($hasDu && (int)$bulan['bulan'] === $duBulan) continue;
            // Skip months before student's entry date
            $thnBulan = (int)$bulan['tahun'] * 100 + (int)$bulan['bulan'];
            if ($thnBulan < $thnBulanMulai) continue;
            $firstDay = "{$bulan['tahun']}-{$bulan['bulan']}-01";
            $lastDay = date('Y-m-t', strtotime($firstDay));

            $existing = $db->query("
                SELECT id FROM tb_tagihan_siswa
                WHERE siswa_id = ? AND jenis_tagihan = 'SPP Bulanan'
                AND EXTRACT(MONTH FROM created_at)::int = ? AND EXTRACT(YEAR FROM created_at)::int = ?
                LIMIT 1
            ", [$siswaId, (int)$bulan['bulan'], (int)$bulan['tahun']])->getResultArray();

            if (!empty($existing)) continue;

            $billModel->insert([
                'siswa_id' => $siswaId,
                'jenis_tagihan' => 'SPP Bulanan',
                'sekolah' => $siswa['sekolah'],
                'nominal' => $nominal,
                'jatuh_tempo' => $lastDay,
                'tahun_ajaran_id' => $ta['id'],
                'status' => 'belum_bayar',
                'created_at' => $firstDay . ' 00:00:00',
            ]);
        }
    }

    private function getBulanTA($tahunAjaran)
    {
        if (!preg_match('/^(\d{4})\/(\d{4})$/', $tahunAjaran, $m)) return null;
        $t1 = (int)$m[1];
        $t2 = (int)$m[2];
        $bulan = [];
        for ($b = 7; $b <= 12; $b++) $bulan[] = ['bulan' => str_pad($b, 2, '0', STR_PAD_LEFT), 'tahun' => $t1];
        for ($b = 1; $b <= 6; $b++) $bulan[] = ['bulan' => str_pad($b, 2, '0', STR_PAD_LEFT), 'tahun' => $t2];
        return $bulan;
    }

    private function autoGenerateStudentDaftarUlang($siswaId)
    {
        $siswaModel = new StudentModel();
        $billModel = new \App\Models\BillModel();
        $taModel = new AcademicYearModel();

        $siswa = $siswaModel->find($siswaId);
        if (!$siswa || $siswa['status'] !== 'aktif') return;

        $ta = $taModel->where('aktif', 1)->first();
        if (!$ta) return;

        $nominal = (float)($siswa['nominal_awal_tahun'] ?? 0);
        if ($nominal <= 0) return;

        $existing = $billModel->where('siswa_id', $siswaId)
            ->where('jenis_tagihan', 'Daftar Ulang')
            ->where('tahun_ajaran_id', $ta['id'])
            ->first();
        if ($existing) return;

        $t1 = (int) explode('/', $ta['tahun_ajaran'])[0];

        $tglMasuk = $siswa['tanggal_masuk'] ?? null;
        if ($tglMasuk) {
            $duBulan = date('m', strtotime($tglMasuk));
            $duTahun = date('Y', strtotime($tglMasuk));
        } else {
            $duBulan = '07';
            $duTahun = $t1;
        }
        $duCreatedAt = "{$duTahun}-{$duBulan}-01 00:00:00";
        $duJatuhTempo = date('Y-m-t', strtotime($duCreatedAt));

        $billModel->insert([
            'siswa_id' => $siswaId,
            'jenis_tagihan' => 'Daftar Ulang',
            'sekolah' => $siswa['sekolah'],
            'nominal' => $nominal,
            'jatuh_tempo' => $duJatuhTempo,
            'tahun_ajaran_id' => $ta['id'],
            'status' => 'belum_bayar',
            'created_at' => $duCreatedAt,
        ]);
    }

    public function nasabahStore()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/siswaguru');
        }

        $model = new NasabahModel();
        $sekolah = $this->getSekolah();
        $data = [
            'nama' => $this->request->getPost('nama'),
            'alamat' => $this->request->getPost('alamat'),
            'no_telp' => $this->request->getPost('no_telp'),
            'sekolah' => $sekolah !== 'admin' ? $sekolah : $this->request->getPost('sekolah'),
        ];

        if ($model->insert($data)) {
            session()->setFlashdata('success', 'Nasabah berhasil ditambahkan.');
        } else {
            session()->setFlashdata('error', 'Gagal menambahkan nasabah.');
        }

        return redirect()->to('/siswaguru');
    }

    public function nasabahUpdate()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/siswaguru');
        }

        $id = $this->request->getPost('id');
        if (!$id) {
            return redirect()->to('/siswaguru')->with('error', 'ID nasabah tidak ditemukan.');
        }

        $model = new NasabahModel();
        $sekolah = $this->getSekolah();
        $data = [
            'nama' => $this->request->getPost('nama'),
            'alamat' => $this->request->getPost('alamat'),
            'no_telp' => $this->request->getPost('no_telp'),
            'sekolah' => $sekolah !== 'admin' ? $sekolah : $this->request->getPost('sekolah'),
        ];

        if ($model->update($id, $data)) {
            session()->setFlashdata('success', 'Nasabah berhasil diupdate.');
        } else {
            session()->setFlashdata('error', 'Gagal mengupdate nasabah.');
        }

        return redirect()->to('/siswaguru');
    }

    public function nasabahDelete()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        if (!$this->request->is('post')) {
            return redirect()->to('/siswaguru');
        }

        $id = $this->request->getPost('id');
        if (!$id) {
            return redirect()->to('/siswaguru')->with('error', 'ID nasabah tidak ditemukan.');
        }

        $model = new NasabahModel();
        if ($model->delete($id)) {
            session()->setFlashdata('success', 'Nasabah berhasil dihapus.');
        } else {
            session()->setFlashdata('error', 'Gagal menghapus nasabah.');
        }

        return redirect()->to('/siswaguru');
    }

    public function nasabahGetData($id)
    {
        $model = new NasabahModel();
        return $this->response->setJSON($model->find($id));
    }

    private function autoCreateAccount($tipe, $orangId, $sekolah)
    {
        $savingsModel = new SavingsAccountModel();

        // Cek apakah sudah punya rekening
        $idField = $tipe === 'siswa' ? 'siswa_id' : ($tipe === 'nasabah' ? 'nasabah_id' : 'guru_id');
        $existing = $savingsModel->where('tipe', $tipe)
            ->where($idField, $orangId)
            ->first();
        if ($existing) return;

        $noRekening = 'SAV-' . date('Ymd') . '-' . str_pad($savingsModel->countAll() + 1, 4, '0', STR_PAD_LEFT);

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
        } else {
            $data['guru_id'] = $orangId;
        }

        $savingsModel->insert($data);
    }

    private function syncGuruToTht($nip, $nama, $sekolah)
    {
        if (!$nip) return;

        $unitMap = ['ra' => 1, 'sd' => 2, 'smp' => 3];
        $unitId = $unitMap[$sekolah] ?? 1;

        $guruModel = new \App\Models\GuruModel();
        $existing = $guruModel->where('nip', $nip)->first();

        if ($existing) {
            $guruModel->update($existing['id'], [
                'nama' => $nama,
                'unit_id' => $unitId,
            ]);
        } else {
            $guruModel->insert([
                'nip' => $nip,
                'nama' => $nama,
                'unit_id' => $unitId,
                'saldo_awal' => 0,
            ]);
        }
    }

    private function generateNextNis($unit)
    {
        $prefixMap = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];
        $prefix = $prefixMap[$unit] ?? 'SMP';
        $model = new StudentModel();

        $model->select("nis")->like('nis', $prefix, 'after')->orderBy('LENGTH(nis)', 'DESC')->orderBy('nis', 'DESC')->limit(1);
        $last = $model->first();

        if ($last && preg_match('/^' . $prefix . '(\d+)$/', $last['nis'], $m)) {
            $nextNum = (int)$m[1] + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }

    public function ajaxNextNis()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        $unit = $this->request->getGet('unit');
        if (!in_array($unit, ['ra', 'sd', 'smp'])) {
            return $this->response->setJSON(['error' => 'Unit tidak valid.']);
        }

        return $this->response->setJSON(['nis' => $this->generateNextNis($unit)]);
    }

    public function fixNisSmp()
    {
        $redirect = $this->redirectIfNotRole(['superadmin', 'staff', 'kepala_sekolah']);
        if ($redirect) return $redirect;

        if (($this->userData['sekolah'] ?? 'admin') !== 'admin') {
            return redirect()->back()->with('error', 'Hanya admin.');
        }

        $db = \Config\Database::connect();
        $students = $db->query("SELECT id, nis FROM tb_siswa WHERE sekolah = 'smp' AND nis LIKE 'SM%' AND nis NOT LIKE 'SMP%' ORDER BY nis")->getResultArray();
        $fixed = 0;
        $skipped = 0;

        foreach ($students as $s) {
            $newNis = 'SMP' . substr($s['nis'], 2);
            $exists = $db->table('tb_siswa')->where('nis', $newNis)->where('id !=', $s['id'])->countAllResults();
            if ($exists) {
                $skipped++;
                continue;
            }
            $db->table('tb_siswa')->where('id', $s['id'])->update(['nis' => $newNis]);
            $fixed++;
        }

        $msg = "$fixed siswa SMP diperbaiki NIS-nya (SM → SMP).";
        if ($skipped > 0) $msg .= " $skipped dilewati (NIS sudah ada).";
        return redirect()->to('/siswaguru')->with('success', $msg);
    }
}
