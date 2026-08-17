<?php
$conn = pg_connect("host=localhost port=5432 dbname=aljawahi_keuangan_aljawahir user=aljawahi_keunagan password=12082006A.b");
$x = pg_query($conn, "SELECT id,nama FROM tb_siswa WHERE lower(nama) LIKE '%rauza%' OR lower(nama) LIKE '%alfaiha%' OR lower(nama) LIKE '%harfan%'");
echo "CNT: " . pg_num_rows($x) . "\n";
while ($r = pg_fetch_assoc($x)) echo "id={$r['id']} nama={$r['nama']}\n";
$x = pg_query($conn, "SELECT count(*) c FROM tb_siswa"); $r = pg_fetch_assoc($x);
echo "ALL: {$r['c']}\n";
$x = pg_query($conn, "SELECT id, nama FROM tb_siswa ORDER BY id DESC LIMIT 3");
while ($r = pg_fetch_assoc($x)) echo "last: #{$r['id']} {$r['nama']}\n";
