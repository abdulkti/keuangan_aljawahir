<?php
$conn = pg_connect("host=localhost port=5432 dbname=aljawahi_keuangan_aljawahir user=aljawahi_keunagan password=12082006A.b");
$x = pg_query($conn, "select id,nama from tb_siswa where lower(nama) like '%rauza%'");
echo "RAUZA: ".pg_num_rows($x)."\n";
while ($r = pg_fetch_assoc($x)) echo "siswa: id={$r['id']} nama={$r['nama']}\n";
$x = pg_query($conn, "select id,nama from tb_siswa where lower(nama) like '%alfaiha%'");
echo "ALFAIHA: ".pg_num_rows($x)."\n";
while ($r = pg_fetch_assoc($x)) echo "siswa: id={$r['id']} nama={$r['nama']}\n";
$x = pg_query($conn, "select id,nama from tb_siswa where lower(nama) like '%harfan%'");
echo "HARFAN: ".pg_num_rows($x)."\n";
while ($r = pg_fetch_assoc($x)) echo "siswa: id={$r['id']} nama={$r['nama']}\n";
$x = pg_query($conn, "select count(*) c from tb_siswa"); $r = pg_fetch_assoc($x);
echo "TOTAL SISWA: {$r['c']}\n";
$x = pg_query($conn, "select id,nama from tb_siswa order by id desc limit 5");
echo "5 SISWA TERAKHIR:\n";
while ($r = pg_fetch_assoc($x)) echo "  #{$r['id']} {$r['nama']}\n";
