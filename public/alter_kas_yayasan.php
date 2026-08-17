<?php
$conn = pg_connect("host=localhost port=5432 dbname=aljawahi_keuangan_aljawahir user=aljawahi_keunagan password=12082006A.b");
if (!$conn) { echo "Gagal konek: " . pg_last_error(); exit; }

$sql = "ALTER TABLE tb_kas_yayasan ALTER COLUMN unit_id DROP NOT NULL;";
$r = pg_query($conn, $sql);
if ($r) {
    echo "OK: unit_id jadi nullable";
} else {
    echo "Gagal: " . pg_last_error($conn);
}
pg_close($conn);
