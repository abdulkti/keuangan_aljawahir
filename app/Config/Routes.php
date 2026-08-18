<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login');
$routes->post('/auth/login', 'Auth::login');
$routes->post('/auth/logout', 'Auth::logout');

$routes->get('/dashboard', 'Dashboard::index');
$routes->get('/dashboard-yayasan', 'DashboardYayasan::index');
$routes->get('/tabungan', 'Tabungan::index');
$routes->post('/tabungan/transaksi', 'Tabungan::transaksi');
$routes->get('/tagihan', 'Tagihan::index');
$routes->get('/tagihan/detail/(:num)', 'Tagihan::detail/$1');
$routes->post('/tagihan/bayar', 'Tagihan::bayar');
$routes->get('/pembukuan', 'Pembukuan::index');
$routes->get('/pembukuan/export-excel', 'Pembukuan::exportExcel');
$routes->get('/rekap-harian', 'RekapHarian::index');
$routes->get('/rekap-harian/export-excel', 'RekapHarian::exportExcel');
$routes->post('/tabungan/create-account', 'Tabungan::createAccount');
$routes->get('/tabungan/riwayat/(:num)', 'Tabungan::riwayat/$1');
$routes->post('/tabungan/edit-transaksi', 'Tabungan::editTransaksi');
$routes->get('/tabungan/rekap', 'Tabungan::rekap');
$routes->get('/tabungan/rekap/export-excel', 'Tabungan::exportExcel');
$routes->get('/siswaguru', 'SiswaGuru::index');
$routes->post('/siswaguru/siswa/store', 'SiswaGuru::siswaStore');
$routes->post('/siswaguru/siswa/update', 'SiswaGuru::siswaUpdate');
$routes->post('/siswaguru/siswa/delete', 'SiswaGuru::siswaDelete');
$routes->post('/siswaguru/siswa/hapus-batch', 'SiswaGuru::siswaDeleteBatch');
$routes->post('/siswaguru/siswa/pindah', 'SiswaGuru::siswaPindah');
$routes->post('/siswaguru/siswa/delete-batch', 'SiswaGuru::siswaPindahBatch');
$routes->post('/siswaguru/siswa/lulus', 'SiswaGuru::siswaLulus');
$routes->post('/siswaguru/siswa/pindah-kelas', 'SiswaGuru::siswaPindahKelas');
$routes->post('/siswaguru/siswa/naik-kelas', 'SiswaGuru::siswaNaikKelas');
$routes->post('/siswaguru/siswa/turun-kelas', 'SiswaGuru::siswaTurunKelas');
$routes->get('/siswaguru/export-csv', 'SiswaGuru::exportCsv');
$routes->get('/siswaguru/next-nis', 'SiswaGuru::ajaxNextNis');
$routes->get('/siswaguru/fix-nis', 'SiswaGuru::fixNisSmp');
$routes->post('/siswaguru/siswa/import-excel', 'SiswaGuru::siswaImportExcel');
$routes->get('/siswaguru/kelas', 'SiswaGuru::kelas');
$routes->get('/siswaguru/kelas/(:num)', 'SiswaGuru::kelasDetail/$1');
$routes->post('/siswaguru/kelas/store', 'SiswaGuru::kelasStore');
$routes->post('/siswaguru/kelas/update', 'SiswaGuru::kelasUpdate');
$routes->post('/siswaguru/kelas/delete', 'SiswaGuru::kelasDelete');
$routes->post('/siswaguru/guru/store', 'SiswaGuru::guruStore');
$routes->post('/siswaguru/guru/update', 'SiswaGuru::guruUpdate');
$routes->post('/siswaguru/guru/delete', 'SiswaGuru::guruDelete');
$routes->post('/siswaguru/guru/delete-batch', 'SiswaGuru::guruDeleteBatch');
$routes->post('/siswaguru/nasabah/store', 'SiswaGuru::nasabahStore');
$routes->post('/siswaguru/nasabah/update', 'SiswaGuru::nasabahUpdate');
$routes->post('/siswaguru/nasabah/delete', 'SiswaGuru::nasabahDelete');
$routes->get('/siswaguru/nasabah/getData/(:num)', 'SiswaGuru::nasabahGetData/$1');
$routes->get('/laporan', 'Laporan::index');
$routes->get('/laporan/export-csv', 'Laporan::exportCsv');
$routes->get('/pengaturan', 'Pengaturan::index');
$routes->post('/pengaturan/store', 'Pengaturan::store');
$routes->post('/pengaturan/update', 'Pengaturan::update');
$routes->post('/pengaturan/delete', 'Pengaturan::delete');
$routes->post('/pengaturan/set-active-ta', 'Pengaturan::setActiveTa');
$routes->post('/pengaturan/add-ta', 'Pengaturan::addTa');
$routes->get('/pengaturan/export-backup', 'Pengaturan::exportBackup');
$routes->post('/pengaturan/import-backup', 'Pengaturan::importBackup');
$routes->post('/pengaturan/delete-all-data', 'Pengaturan::deleteAllData');

// Super Admin - Yayasan
$routes->get('/unit', 'Unit::index');
$routes->post('/unit/tambah', 'Unit::tambah');
$routes->post('/unit/edit', 'Unit::edit');
$routes->post('/unit/hapus/(:num)', 'Unit::hapus/$1');
$routes->get('/unit/getData', 'Unit::getData');

$routes->get('/tht', 'Tht::index');
$routes->get('/tht/riwayat/(:num)', 'Tht::riwayat/$1');
$routes->post('/tht/setor', 'Tht::setor');
$routes->post('/tht/tarik', 'Tht::tarik');
$routes->post('/tht/hapus/(:num)', 'Tht::hapus/$1');

$routes->get('/kas-yayasan', 'KasYayasan::index');
$routes->post('/kas-yayasan/tambah', 'KasYayasan::tambah');
$routes->post('/kas-yayasan/edit/(:num)', 'KasYayasan::edit/$1');
$routes->post('/kas-yayasan/hapus/(:num)', 'KasYayasan::hapus/$1');
$routes->get('/kas-yayasan/getData/(:num)', 'KasYayasan::getData/$1');
$routes->post('/kas-yayasan/transfer-saldo', 'KasYayasan::transferSaldo');
$routes->get('/kas-yayasan/saldo', 'KasYayasan::getSaldo');

$routes->get('/rekap/yayasan', 'Rekap::yayasan');
$routes->get('/rekap/tht', 'Rekap::rekapTht');

$routes->get('/kas-unit', 'KasUnit::index');
$routes->get('/kas-unit/rekap', 'KasUnit::rekap');
$routes->get('/kas-unit/rekap/export-excel', 'KasUnit::exportExcel');
$routes->post('/kas-unit/tambah', 'KasUnit::tambah');
$routes->post('/kas-unit/edit/(:num)', 'KasUnit::edit/$1');
$routes->post('/kas-unit/hapus/(:num)', 'KasUnit::hapus/$1');
$routes->get('/kas-unit/getData/(:num)', 'KasUnit::getData/$1');
$routes->post('/kas-unit/tutup-buku', 'KasUnit::tutupBuku');
$routes->post('/kas-unit/buka-kembali', 'KasUnit::bukaKembali');
$routes->post('/kas-unit/buka-kembali-satu/(:num)', 'KasUnit::bukaKembaliSatu/$1');
$routes->get('/kas-unit/rekap-harian', 'KasUnit::getRekapHarian');
$routes->post('/kas-unit/ajukan-dana', 'KasUnit::ajukanDana');
$routes->get('/kas-unit/pengajuan', 'KasUnit::pengajuan');
$routes->post('/kas-unit/setujui-pengajuan/(:num)', 'KasUnit::setujuiPengajuan/$1');
$routes->post('/kas-unit/tolak-pengajuan/(:num)', 'KasUnit::tolakPengajuan/$1');


