<?php
$menu = service('uri')->getSegment(1) ?: 'dashboard';
$menu2 = service('uri')->getSegment(2) ?: '';
$role = $user['role'] ?? 'staff';
$sekolah = $user['sekolah'] ?? 'admin';
$settingModel = new \App\Models\SettingModel();
$baseName = $settingModel->getSetting('school_name') ?: 'Al-Jawahir Attarbawi';
$unitLabels = ['ra'=>'RA','sd'=>'SD IT','smp'=>'SMP IT'];
$unitLabel = $sekolah !== 'admin' ? ($unitLabels[$sekolah] ?? strtoupper($sekolah)) : '';

$isSuperAdmin = $role === 'superadmin';
$isYayasan = $role === 'admin';
$isSekolah = in_array($role, ['staff', 'kepala_sekolah']);

$pengajuanPending = 0;
if ($isSuperAdmin || $isYayasan) {
    $pengajuanPending = (int) \Config\Database::connect()->table('tb_pengajuan_dana')->where('status', 'pending')->countAllResults();
}
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="brand-logo">
      <div class="mark">
        <img src="/assets/images/logo-aljawahir.png" alt="Al-Jawahir" style="width:100%;height:100%;object-fit:contain">
      </div>
      <div class="brand-text">
        <span class="school-name"><?= $baseName ?></span>
        <span class="school-sub"><?php if ($unitLabel): ?><?= $unitLabel ?>·<?php endif; ?>Sistem Keuangan</span>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Menu Utama</div>

    <?php if (!$isYayasan): ?>
    <a class="nav-item <?= $menu === 'dashboard' ? 'active' : '' ?>" href="/dashboard">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
      <span>Dashboard</span>
    </a>
    <?php endif; ?>

    <?php if ($isSuperAdmin || $isYayasan): ?>
    <a class="nav-item <?= $menu === 'dashboard-yayasan' ? 'active' : '' ?>" href="/dashboard-yayasan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
      <span>Dashboard Yayasan</span>
    </a>
    <?php endif; ?>

    <?php if (!$isYayasan): ?>
    <div class="nav-section-label">Tabungan</div>

    <a class="nav-item <?= $menu === 'tabungan' && $menu2 !== 'rekap' ? 'active' : '' ?>" href="/tabungan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>
      <span>Tabungan</span>
    </a>

    <a class="nav-item <?= $menu === 'tabungan' && $menu2 === 'rekap' ? 'active' : '' ?>" href="/tabungan/rekap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      <span>Rekap Tabungan</span>
    </a>
    <?php endif; ?>

    <?php if ($isSuperAdmin || $isSekolah): ?>
    <div class="nav-section-label">Sekolah</div>

    <a class="nav-item <?= $menu === 'tagihan' ? 'active' : '' ?>" href="/tagihan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 15h6M9 11h6"/></svg>
      <span>Tagihan & SPP</span>
    </a>

    <a class="nav-item <?= $menu === 'kas-unit' && $menu2 === 'rekap' ? 'active' : '' ?>" href="/kas-unit/rekap">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M12 22V12"/><path d="m3.3 7 8.7 5 8.7-5"/></svg>
      <span>Rekap Tagihan</span>
    </a>

    <a class="nav-item <?= $menu === 'kas-unit' && $menu2 !== 'rekap' && $menu2 !== 'pengajuan' ? 'active' : '' ?>" href="/kas-unit">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 4v16"/><path d="M2 10h20"/></svg>
      <span>Kas Unit</span>
    </a>

    <a class="nav-item <?= $menu === 'rekap-harian' ? 'active' : '' ?>" href="/rekap-harian">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      <span>Rekap Harian</span>
    </a>

    <a class="nav-item <?= $menu === 'pembukuan' ? 'active' : '' ?>" href="/pembukuan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-4 4"/></svg>
      <span>Pembukuan</span>
    </a>
    <?php endif; ?>

    <?php if ($isSuperAdmin || $isYayasan): ?>
    <div class="nav-section-label">Yayasan</div>

    <a class="nav-item <?= $menu === 'tht' ? 'active' : '' ?>" href="/tht">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20"/><path d="M7 10l5 5 5-5"/></svg>
      <span>THT Guru</span>
    </a>

    <a class="nav-item <?= $menu === 'kas-yayasan' ? 'active' : '' ?>" href="/kas-yayasan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      <span>Kas Yayasan</span>
    </a>

    <?php if ($isYayasan): ?>
    <a class="nav-item <?= $menu === 'rekap-harian' ? 'active' : '' ?>" href="/rekap-harian">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      <span>Rekap Harian</span>
    </a>
    <?php endif; ?>

    <a class="nav-item <?= $menu === 'kas-unit' && $menu2 === 'pengajuan' ? 'active' : '' ?>" href="/kas-unit/pengajuan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
      <span>Pengajuan Dana</span>
      <?php if ($pengajuanPending > 0): ?><span class="nav-badge"><?= $pengajuanPending ?></span><?php endif; ?>
    </a>

    <a class="nav-item <?= $menu === 'rekap' && $menu2 === 'yayasan' ? 'active' : '' ?>" href="/rekap/yayasan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="18" rx="2"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
      <span>Rekap Yayasan</span>
    </a>

    <?php endif; ?>

    <div class="nav-section-label">Lainnya</div>

    <?php if ($isSuperAdmin || $isSekolah): ?>
    <a class="nav-item <?= $menu === 'siswaguru' && $menu2 === '' ? 'active' : '' ?>" href="/siswaguru">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
      <span>Data Siswa & Guru</span>
    </a>

    <a class="nav-item <?= $menu2 === 'kelas' ? 'active' : '' ?>" href="/siswaguru/kelas">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M12 22V12"/><path d="m3.3 7 8.7 5 8.7-5"/></svg>
      <span>Manajemen Kelas</span>
    </a>
    <?php endif; ?>

    <a class="nav-item <?= $menu === 'laporan' ? 'active' : '' ?>" href="/laporan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 17h8M8 13h5"/></svg>
      <span>Laporan</span>
    </a>

    <?php if ($isSuperAdmin): ?>
    <a class="nav-item <?= $menu === 'unit' ? 'active' : '' ?>" href="/unit">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      <span>Unit Sekolah</span>
    </a>
    <a class="nav-item <?= $menu === 'pengaturan' ? 'active' : '' ?>" href="/pengaturan">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l-.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9c.18-.59.06-1.27-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06c.45.39 1.13.51 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09c0 .69.4 1.32 1 1.51.59.18 1.27.06 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06c-.39.45-.51 1.13-.33 1.82V9c.2.6.82 1 1.51 1H21a2 2 0 0 1 0 4h-.09c-.69 0-1.32.4-1.51 1Z"/></svg>
      <span>Pengaturan</span>
    </a>
    <?php endif; ?>

  </nav>

  <div class="sidebar-footer">
    <form method="post" action="/auth/logout" style="display:contents"><?= csrf_field() ?>
      <button type="submit" class="nav-item" style="width:100%;margin-bottom:8px">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
        <span>Keluar</span>
      </button>
    </form>
    <div class="user-mini">
      <div class="avatar"><?= strtoupper(substr($user['nama'] ?? 'U', 0, 2)) ?></div>
      <div>
        <div class="name"><?= esc($user['nama'] ?? 'User') ?></div>
        <div class="role"><?= esc(ucfirst($role)) ?></div>
      </div>
    </div>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<script>
var nav = document.querySelector('.sidebar-nav');
var activeItem = nav && nav.querySelector('.nav-item.active');
if (nav && activeItem) {
  nav.scrollTop = activeItem.offsetTop - nav.offsetTop - nav.clientHeight / 2 + activeItem.clientHeight / 2;
}
</script>
