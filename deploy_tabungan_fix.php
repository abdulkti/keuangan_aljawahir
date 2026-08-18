<?php
/**
 * FIX TABUNGAN - Deploy ke production
 * 
 * Upload ke public_html/, buka sekali, lalu HAPUS.
 * 
 * Yang dilakukan:
 * 1. Fix data tb_tabungan.sekolah supaya match dengan tb_siswa.sekolah
 * 2. Patch SavingsAccountModel.php (filter pakai tb_siswa.sekolah)
 * 3. Patch Tabungan.php createAccount (ambil sekolah dari data siswa/guru)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== FIX TABUNGAN DEPLOY ===\n\n";

// === STEP 1: Fix database data ===
echo "--- STEP 1: Fix database data ---\n";
try {
    $db = \Config\Database::connect();
    
    // Fix tabungan siswa: sync sekolah dari tb_siswa
    $r1 = $db->query("
        UPDATE tb_tabungan t 
        SET sekolah = s.sekolah 
        FROM tb_siswa s 
        WHERE t.siswa_id = s.id 
          AND t.tipe = 'siswa' 
          AND t.sekolah != s.sekolah
    ");
    echo "Tabungan siswa: " . $db->affectedRows() . " rows diupdate\n";
    
    // Fix tabungan guru: sync sekolah dari tb_guru
    $r2 = $db->query("
        UPDATE tb_tabungan t 
        SET sekolah = g.sekolah 
        FROM tb_guru g 
        WHERE t.guru_id = g.id 
          AND t.tipe = 'guru' 
          AND t.sekolah != g.sekolah
    ");
    echo "Tabungan guru: " . $db->affectedRows() . " rows diupdate\n";
    
    echo "Database fixed!\n\n";
} catch (\Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n\n";
}

// === STEP 2: Patch SavingsAccountModel.php ===
echo "--- STEP 2: Patch SavingsAccountModel.php ---\n";
$modelFile = dirname(__DIR__) . '/app/Models/SavingsAccountModel.php';

if (!file_exists($modelFile)) {
    echo "File tidak ditemukan: {$modelFile}\n";
    echo "Coba path lain...\n";
    // Try common paths
    $paths = [
        dirname(__DIR__) . '/app/Models/SavingsAccountModel.php',
        dirname(dirname(__DIR__)) . '/app/Models/SavingsAccountModel.php',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) { $modelFile = $p; echo "Found: {$p}\n"; break; }
    }
}

if (file_exists($modelFile)) {
    $content = file_get_contents($modelFile);
    
    // Check if already fixed
    if (strpos($content, "tb_siswa.sekolah', \$sekolah)") !== false && 
        strpos($content, "tb_tabungan.sekolah', \$sekolah)") === false) {
        echo "Sudah di-patch, skip.\n\n";
    } else {
        // Fix 1: getStudentAccounts - ganti tb_tabungan.sekolah jadi tb_siswa.sekolah
        $content = str_replace(
            "\$this->where('tb_tabungan.sekolah', \$sekolah);\n            \$this->where('tb_siswa.sekolah', \$sekolah);",
            "\$this->where('tb_siswa.sekolah', \$sekolah);",
            $content
        );
        
        // Fix 2: getStudentAccounts - ganti tunggal tb_tabungan.sekolah jadi tb_siswa.sekolah
        $content = str_replace(
            "if ($sekolah && $sekolah !== 'admin') {\n            $this->where('tb_tabungan.sekolah', $sekolah);\n        }\n        if (!empty($filters['kelas_id']))",
            "if ($sekolah && $sekolah !== 'admin') {\n            $this->where('tb_siswa.sekolah', $sekolah);\n        }\n        if (!empty($filters['kelas_id']))",
            $content
        );
        
        // Fix 3: getTeacherAccounts - ganti tb_tabungan.sekolah jadi tb_guru.sekolah
        $content = str_replace(
            "\$this->where('tb_tabungan.sekolah', \$sekolah);\n            \$this->where('tb_guru.sekolah', \$sekolah);",
            "\$this->where('tb_guru.sekolah', \$sekolah);",
            $content
        );
        
        if (file_put_contents($modelFile, $content)) {
            echo "SavingsAccountModel.php updated!\n\n";
        } else {
            echo "GAGAL update file\n\n";
        }
    }
} else {
    echo "File tidak ditemukan. Patch manual diperlukan.\n\n";
}

// === STEP 3: Patch Tabungan.php createAccount ===
echo "--- STEP 3: Patch Tabungan.php createAccount ---\n";
$controllerFile = dirname(__DIR__) . '/app/Controllers/Tabungan.php';
if (!file_exists($controllerFile)) {
    $paths = [
        dirname(__DIR__) . '/app/Controllers/Tabungan.php',
        dirname(dirname(__DIR__)) . '/app/Controllers/Tabungan.php',
    ];
    foreach ($paths as $p) {
        if (file_exists($p)) { $controllerFile = $p; echo "Found: {$p}\n"; break; }
    }
}

if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    
    // Check if already fixed
    if (strpos($content, "siswa = \$siswaModel->find(\$orangId)") !== false) {
        echo "Sudah di-patch, skip.\n\n";
    } else {
        // Add model imports after $savingsModel = new SavingsAccountModel();
        $content = str_replace(
            "\$savingsModel = new SavingsAccountModel();\n        \$nasabahModel = new NasabahModel();",
            "\$savingsModel = new SavingsAccountModel();\n        \$nasabahModel = new NasabahModel();\n        \$siswaModel = new StudentModel();\n        \$guruModel = new TeacherModel();",
            $content
        );
        
        // Fix siswa: add sekolah from student data
        $content = str_replace(
            "if (\$tipe === 'siswa') {\n            \$data['siswa_id'] = \$orangId;\n        } elseif (\$tipe === 'nasabah') {",
            "if (\$tipe === 'siswa') {\n            \$data['siswa_id'] = \$orangId;\n            \$siswa = \$siswaModel->find(\$orangId);\n            if (\$siswa && !empty(\$siswa['sekolah'])) {\n                \$data['sekolah'] = \$siswa['sekolah'];\n            }\n        } elseif (\$tipe === 'nasabah') {",
            $content
        );
        
        // Fix guru: add sekolah from guru data
        $content = str_replace(
            "} else {\n            \$data['guru_id'] = \$orangId;\n        }",
            "} else {\n            \$data['guru_id'] = \$orangId;\n            \$guru = \$guruModel->find(\$orangId);\n            if (\$guru && !empty(\$guru['sekolah'])) {\n                \$data['sekolah'] = \$guru['sekolah'];\n            }\n        }",
            $content
        );
        
        if (file_put_contents($controllerFile, $content)) {
            echo "Tabungan.php updated!\n\n";
        } else {
            echo "GAGAL update file\n\n";
        }
    }
} else {
    echo "File tidak ditemukan. Patch manual diperlukan.\n\n";
}

echo "=== DONE! Hapus file ini sekarang! ===\n";
