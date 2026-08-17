<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\StudentModel;
use App\Models\AcademicYearModel;
use App\Models\BillModel;

class GenerateSpp extends BaseCommand
{
    protected $group = 'SistemKeuangan';
    protected $name = 'spp:generate';
    protected $description = 'Generate SPP Bulanan bills for all active students';
    protected $usage = 'spp:generate [options]';
    protected $options = [
        '-m' => 'Month (1-12, default: current month)',
        '-y' => 'Year (default: current year)',
    ];

    public function run(array $params)
    {
        $bulan = CLI::getOption('m') ?: date('m');
        $tahun = CLI::getOption('y') ?: date('Y');
        $sekolah = CLI::getOption('s') ?: null;

        $taModel = new AcademicYearModel();
        $ta = $taModel->where('aktif', 1)->first();
        if (!$ta) {
            CLI::error('Tidak ada tahun ajaran yang aktif.');
            return EXIT_ERROR;
        }

        $siswaModel = new StudentModel();
        $query = $siswaModel->where('status', 'aktif');
        if ($sekolah) {
            $query->where('sekolah', $sekolah);
        }
        $siswaList = $query->findAll();

        $billModel = new BillModel();
        $db = \Config\Database::connect();
        $created = 0;
        $skipped = 0;

        $firstDay = "{$tahun}-{$bulan}-01";
        $lastDay = date('Y-m-t', strtotime($firstDay));

        $classModel = new \App\Models\ClassModel();

        foreach ($siswaList as $siswa) {
            $nominalSpp = (float)($siswa['nominal_spp'] ?? 0);
            if ($nominalSpp <= 0 && !empty($siswa['kelas_id'])) {
                $class = $classModel->find($siswa['kelas_id']);
                $nominalSpp = (float)($class['nominal_spp'] ?? 0);
            }
            if ($nominalSpp <= 0) {
                $skipped++;
                continue;
            }

            $existing = $db->query("
                SELECT id FROM tb_tagihan_siswa
                WHERE siswa_id = ? AND jenis_tagihan = 'SPP Bulanan'
                AND EXTRACT(MONTH FROM created_at) = ? AND EXTRACT(YEAR FROM created_at) = ?
                LIMIT 1
            ", [$siswa['id'], (int)$bulan, (int)$tahun])->getResultArray();

            if (!empty($existing)) {
                $skipped++;
                continue;
            }

            $db->table('tb_tagihan_siswa')->insert([
                'siswa_id' => $siswa['id'],
                'jenis_tagihan' => 'SPP Bulanan',
                'sekolah' => $siswa['sekolah'],
                'nominal' => $nominalSpp,
                'jatuh_tempo' => $lastDay,
                'tahun_ajaran_id' => $ta['id'],
                'status' => 'belum_bayar',
                'created_at' => $firstDay . ' 00:00:00',
            ]);
            $created++;
        }

        $monthName = \DateTime::createFromFormat('!m', $bulan)->format('F');
        CLI::write("SPP {$monthName} {$tahun}: {$created} tagihan dibuat, {$skipped} dilewati.", 'green');
        return EXIT_SUCCESS;
    }
}
