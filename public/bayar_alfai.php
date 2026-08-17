<?php
$envFile = '/home/aljawahi/public_html/.env';
$lines = file($envFile);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
    if (strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

$dsn = "host={$env['database.default.hostname']} port={$env['database.default.port']} dbname={$env['database.default.database']} user={$env['database.default.username']} password={$env['database.default.password']}";
$conn = pg_connect($dsn);
if (!$conn) die("ERROR: " . pg_last_error());

$siswaId = 74; // RAUZA ALFAIHA HARFAN
$unitId = 1;   // RA IT Al-Jawahir
$metode = 'transfer';
$userId = 1;

// Get unpaid SPP tagihan for Alfai
$q = pg_query_params($conn,
    "SELECT ts.id, ts.nominal, ts.jenis_tagihan, s.nama
     FROM tb_tagihan_siswa ts
     JOIN tb_siswa s ON s.id = ts.siswa_id
     WHERE ts.siswa_id = $1
       AND ts.jenis_tagihan = 'SPP Bulanan'
       AND ts.tahun_ajaran_id = 2
       AND ts.status = 'belum_bayar'
     ORDER BY ts.id",
    [$siswaId]
);

$count = 0;
while ($row = pg_fetch_assoc($q)) {
    $tagihanId = $row['id'];
    $nominal = (int)$row['nominal'];
    $siswaNama = $row['nama'];
    $jenis = $row['jenis_tagihan'];
    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $noKwitansi = 'KW-' . $today . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);

    pg_query($conn, "BEGIN");

    $r = pg_query_params($conn,
        "INSERT INTO tb_pembayaran (tagihan_id, nominal_dibayar, metode, no_kwitansi, user_id, created_at)
         VALUES ($1, $2, $3, $4, $5, $6) RETURNING id",
        [$tagihanId, $nominal, $metode, $noKwitansi, $userId, $now]
    );
    if (!$r) { pg_query($conn, "ROLLBACK"); echo "FAIL insert pembayaran tagihan_id=$tagihanId: " . pg_last_error($conn) . "\n"; continue; }
    $payId = pg_fetch_result($r, 0, 0);

    $r2 = pg_query_params($conn,
        "UPDATE tb_tagihan_siswa SET status = 'lunas' WHERE id = $1",
        [$tagihanId]
    );
    if (!$r2) { pg_query($conn, "ROLLBACK"); echo "FAIL update tagihan id=$tagihanId: " . pg_last_error($conn) . "\n"; continue; }

    $r3 = pg_query_params($conn,
        "INSERT INTO tb_kas_unit (unit_id, tanggal, keterangan, kategori, jumlah, jenis, metode, status_tutup, user_id, referensi_id, referensi_tipe, created_at)
         VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)",
        [$unitId, $today, "$jenis - $siswaNama", $jenis, $nominal, 'pemasukan', $metode, 'belum', $userId, $payId, 'pembayaran', $now]
    );
    if (!$r3) { pg_query($conn, "ROLLBACK"); echo "FAIL insert kas_unit tagihan_id=$tagihanId: " . pg_last_error($conn) . "\n"; continue; }

    pg_query($conn, "COMMIT");
    echo "OK: tagihan_id=$tagihanId pay_id=$payId nominal=$nominal\n";
    $count++;
}

echo "Selesai: $count tagihan diproses\n";

// Verification
echo "\n=== VERIFIKASI ===\n";
$v = pg_query_params($conn,
    "SELECT ts.id, ts.status, p.id as pay_id, p.metode, ku.id as ku_id
     FROM tb_tagihan_siswa ts
     LEFT JOIN tb_pembayaran p ON p.tagihan_id = ts.id
     LEFT JOIN tb_kas_unit ku ON ku.referensi_id = p.id AND ku.referensi_tipe = 'pembayaran'
     WHERE ts.siswa_id = $1 AND ts.tahun_ajaran_id = 2
     ORDER BY ts.id",
    [$siswaId]
);
while ($r = pg_fetch_assoc($v)) {
    echo "tagihan_id={$r['id']} status={$r['status']} pay_id={$r['pay_id']} metode={$r['metode']} ku_id={$r['ku_id']}\n";
}

pg_close($conn);
echo "DONE\n";
