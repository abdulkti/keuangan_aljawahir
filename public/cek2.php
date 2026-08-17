<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$conn = pg_connect("host=localhost port=5432 dbname=aljawahi_keuangan_aljawahir user=aljawahi_keunagan password=12082006A.b");
if (!$conn) { echo "CONN FAIL"; exit; }

// Search for RAUZA in all tables
$r = pg_query($conn, "SELECT id, nama from tb_siswa where lower(nama) like '%rauza%'");
while ($row = pg_fetch_assoc($r)) echo "SISWA: id={$row['id']} nama={$row['nama']}\n";

$r = pg_query($conn, "SELECT ts.id, ts.jenis_tagihan, ts.nominal, ts.status, ts.bulan, ts.tahun, ts.siswa_id, s.nama FROM tb_tagihan_siswa ts JOIN tb_siswa s ON s.id = ts.siswa_id WHERE lower(s.nama) like '%rauza%' ORDER by ts.id");
echo "\nTAGIHAN RAUZA:\n";
while ($row = pg_fetch_assoc($r)) echo "  #{$row['id']} {$row['nama']} {$row['jenis_tagihan']} bulan={$row['bulan']} thn={$row['tahun']} nominal=".number_format($row['nominal'],0,',','.')." status={$row['status']}\n";

// Check if any siswa has the substring "rau" or "haf"
$r = pg_query($conn, "SELECT count(*) from tb_siswa where lower(nama) like '%rau%' or lower(nama) like '%haf%'");
$cnt = pg_fetch_assoc($r)['count'];
echo "\nSISWA with 'rau' or 'haf': $cnt\n";

$r = pg_query($conn, "SELECT id, nama from tb_siswa where lower(nama) like '%rau%' or lower(nama) like '%harf%'");
while ($row = pg_fetch_assoc($r)) echo "  id={$row['id']} nama={$row['nama']}\n";

// Check last 20 tagihan
$r = pg_query($conn, "SELECT ts.id, ts.jenis_tagihan, ts.nominal, ts.status, ts.bulan, ts.tahun, s.nama FROM tb_tagihan_siswa ts JOIN tb_siswa s ON s.id = ts.siswa_id ORDER BY ts.id DESC LIMIT 20");
echo "\n20 TAGIHAN TERAKHIR:\n";
while ($row = pg_fetch_assoc($r)) echo "  #{$row['id']} {$row['nama']} {$row['jenis_tagihan']} nn=".number_format($row['nominal'],0,',','.')." sts={$row['status']} bln={$row['bulan']}\n";
