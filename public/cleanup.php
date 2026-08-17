<?php
$files = ['cek_siswa.php', 'bayar_alfai.php', 'backfill_kas_unit.php', 'cleanup.php'];
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        unlink($path);
        echo "Deleted: $f\n";
    } else {
        echo "Not found: $f\n";
    }
}
echo "Cleanup done\n";
