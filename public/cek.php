<?php
$conn = pg_connect("host=localhost port=5432 dbname=aljawahi_keuangan_aljawahir user=aljawahi_keunagan password=12082006A.b");

$r = pg_query($conn, "SELECT id, nama FROM tb_siswa WHERE nama ILIKE '%rauza%'");
echo "=== SISWA 'rauza' ===\n";
while ($row = pg_fetch_assoc($r)) echo "  id={$row['id']} nama={$row['nama']}\n";

$r = pg_query($conn, "SELECT id, nama FROM tb_siswa WHERE nama ILIKE '%harfan%'");
echo "=== SISWA 'harfan' ===\n";
while ($row = pg_fetch_assoc($r)) echo "  id={$row['id']} nama={$row['nama']}\n";

$r = pg_query($conn, "SELECT id, nama FROM tb_siswa ORDER BY id DESC LIMIT 10");
echo "=== 10 SISWA TERAKHIR ===\n";
while ($row = pg_fetch_assoc($r)) echo "  id={$row['id']} nama={$row['nama']}\n";

$r = pg_query($conn, "SELECT COUNT(*) as cnt FROM tb_siswa");
echo "Total siswa: " . pg_fetch_assoc($r)['cnt'] . "\n";

$r = pg_query($conn, "SELECT id, nama, tanggal_lahir FROM tb_siswa WHERE id = 405");
echo "=== SISWA id=405 ===\n";
$row = pg_fetch_assoc($r); var_dump($row);

$r = pg_query($conn, "SELECT ts.id, ts.jenis_tagihan, ts.nominal, ts.status, ts.created_at, ts.bulan, ts.tahun, ts.siswa_id FROM tb_tagihan_siswa ts WHERE siswa_id = 405 ORDER BY ts.created_at");
echo "=== TAGIHAN id=405 ===\n";
while ($row = pg_fetch_assoc($r)) echo "  #{$row['id']} {$row['created_at']} {$row['jenis_tagihan']} bulan={$row['bulan']} thn={$row['tahun']} status={$row['status']} nominal=" . number_format($row['nominal'],0,',','.') . "\n";
