<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$conn = pg_connect("host=localhost port=5432 dbname=aljawahi_keuangan_aljawahir user=aljawahi_keunagan password=12082006A.b");
if (!$conn) { echo "CONN FAIL: " . pg_last_error(); exit; }
$r = pg_query($conn, "SELECT id, nama FROM tb_siswa WHERE nama ILIKE '%rauza%'");
if (!$r) { echo "QUERY FAIL: " . pg_last_error($conn); exit; }
echo "RAUZA: " . pg_num_rows($r) . " rows\n";
while ($row = pg_fetch_assoc($r)) echo "  id={$row['id']} nama={$row['nama']}\n";
echo "TOTAL SISWA: " . pg_fetch_assoc(pg_query($conn, "SELECT count(*) c FROM tb_siswa"))['c'] . "\n";
pg_close($conn);
