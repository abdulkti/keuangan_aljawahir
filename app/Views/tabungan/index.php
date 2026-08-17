<?php
  $pageTitle = 'Tabungan';
  $pageDesc = 'Kelola setoran, penarikan, dan riwayat tabungan';
  $totalSaldo = $totalSaldo ?? 0;
  $tabunganSiswa = $studentAccounts ?? [];
  $tabunganGuru = $teacherAccounts ?? [];
  $siswaAktif = count($tabunganSiswa);
  $guruAktif = count($tabunganGuru);
  $tabunganNasabah = $nasabahAccounts ?? [];
  $nasabahAktif = count($tabunganNasabah);
  $totalRekening = $siswaAktif + $guruAktif + $nasabahAktif;
  $classes = $classes ?? [];
  $allSiswa = $allSiswa ?? [];
  $allGuru = $allGuru ?? [];
  $allNasabah = $allNasabah ?? [];
  $kelas = $kelas ?? '';
  $search = $search ?? '';
  $bidang = $bidang ?? '';
  $kasPerUnit = $kasPerUnit ?? [];
  $unitIdByNama = $unitIdByNama ?? [];
  $kasTersediaAkun = function($a) use ($kasPerUnit, $unitIdByNama) {
    $nama = strtolower((string)($a['sekolah'] ?? ''));
    $unitId = $unitIdByNama[$nama] ?? null;
    return $unitId ? (float)($kasPerUnit[$unitId] ?? 0) : 0.0;
  };
  $avatarColors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#06B6D4'];
  function avatarColor($name, $colors) { return $colors[crc32($name) % count($colors)]; }
?>
<?= view('layout/header') ?>
<style>
.metode-toggle{ display:flex; gap:8px; margin-top:4px; }
.metode-toggle button{ flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:10px 16px; border:2px solid var(--slate-200); border-radius:var(--r-sm); background:#fff; cursor:pointer; font-size:14px; font-weight:600; color:var(--slate-500); transition:all .15s; }
.metode-toggle button.active{ border-color:var(--navy-600); background:var(--navy-50); color:var(--navy-800); }
.metode-toggle button svg{ width:18px; height:18px; }
.metode-toggle button:not(.active):hover{ border-color:var(--slate-300); background:var(--slate-50); }
</style>
<div class="app-shell">
  <?= view('layout/sidebar', ['user' => $user ?? []]) ?>
  <div class="main-area">
    <?= view('layout/topbar', ['user' => $user ?? [], 'pageTitle' => $pageTitle, 'pageDesc' => $pageDesc]) ?>
    <div class="content">

      <?php if ($msg = session()->getFlashdata('success')): ?>
      <div class="ku-notif ku-notif-success"><?= esc($msg) ?></div>
      <?php endif; ?>
      <?php if ($msg = session()->getFlashdata('error')): ?>
      <div class="ku-notif ku-notif-error"><?= esc($msg) ?></div>
      <?php endif; ?>

      <div class="ku-stats" style="margin-bottom:22px">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0-4 0"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Rekening</div>
            <div class="ku-stat-value"><?= $totalRekening ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Aktif</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-indigo" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Saldo</div>
            <div class="ku-stat-value">Rp <?= number_format($totalSaldo, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Semua rekening</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-blue" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Siswa</div>
            <div class="ku-stat-value"><?= $siswaAktif ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Rekening aktif</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Guru</div>
            <div class="ku-stat-value"><?= $guruAktif ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Rekening aktif</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-indigo" style="--delay:0.4s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Non Civitas</div>
            <div class="ku-stat-value"><?= $nasabahAktif ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Rekening aktif</span>
            </div>
          </div>
        </div>
      </div>

      <div class="ku-toolbar" style="margin-bottom:16px">
        <div class="ku-toolbar-left">
          <div style="font-size:15px;font-weight:700;color:var(--ku-slate-800)">Data Tabungan</div>
          <div style="font-size:12px;color:var(--ku-slate-500)">Total <?= $totalRekening ?> rekening dengan saldo Rp <?= number_format($totalSaldo, 0, ',', '.') ?></div>
        </div>
        <div class="ku-toolbar-right">
          <button class="ku-btn ku-btn-ghost modal-trigger" data-modal="modal-create-akun">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Rekening
          </button>
        </div>
      </div>

      <div class="tabs-switch">
        <button class="active" data-tab="siswa">Data Siswa</button>
        <button data-tab="guru">Data Guru</button>
        <button data-tab="nasabah">Data Non Civitas</button>
      </div>

      <form method="GET" action="/tabungan" class="ku-toolbar" id="filter-form">
        <input type="hidden" name="tab" id="tab-input" value="siswa">
        <div class="ku-search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" name="search" placeholder="Cari nama atau nomor induk..." value="<?= esc($search) ?>" onkeydown="if(event.key==='Enter')this.form.submit()">
        </div>
        <select name="kelas" class="ku-filter-select tab-filter" data-tab="siswa" onchange="this.form.submit()">
          <option value="">Kelas: Semua</option>
          <?php foreach ($classes as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $kelas == $c['id'] ? 'selected' : '' ?>><?= esc($c['tingkat'] . ' ' . $c['jurusan'] . ' - ' . $c['nama_kelas']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="bidang" class="ku-filter-select tab-filter" data-tab="guru" onchange="this.form.submit()" style="display:none">
          <option value="">Bidang: Semua</option>
          <?php
          $bidangList = array_unique(array_filter(array_column($allGuru, 'bidang')));
          sort($bidangList);
          foreach ($bidangList as $b): ?>
          <option value="<?= esc($b) ?>" <?= ($bidang ?? '') === $b ? 'selected' : '' ?>><?= esc($b) ?></option>
          <?php endforeach; ?>
        </select>
      </form>

      <!-- Tab Siswa -->
      <div class="tab-content" id="tab-siswa">
        <div class="ku-table-wrap">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Tahun Ajaran</th>
                <th>Saldo</th>
                <th style="text-align:right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($tabunganSiswa)): ?>
              <?php foreach ($tabunganSiswa as $a):
                $ac = avatarColor($a['nama'] ?? '?', $avatarColors);
                $saldo = (float)$a['saldo'];
              ?>
              <tr id="akun-<?= $a['id'] ?>">
                <td><div class="ku-cell-person"><div class="ku-avatar-sm" style="background:<?= $ac ?>;color:#fff;font-weight:700"><?= strtoupper(substr($a['nama'] ?? '?', 0, 2)) ?></div><div><div class="ku-person-name"><?= esc($a['nama']) ?></div><div class="ku-person-sub">Siswa</div></div></div></td>
                <td class="ku-td-num"><?= esc($a['nis']) ?></td>
                <td><?= esc($a['nama_kelas'] ?? '-') ?></td>
                <td><?= esc($a['tahun_ajaran'] ?? '-') ?></td>
                <td><span class="ku-badge <?= $saldo > 0 ? 'ku-badge-green' : 'ku-badge-slate' ?>">Rp <?= number_format($saldo, 0, ',', '.') ?></span></td>
                <td style="text-align:right">
                  <div class="ku-actions">
                    <button class="ku-action-btn modal-trigger" data-modal="modal-transaksi" data-akun="<?= $a['id'] ?>" data-nama="<?= esc($a['nama']) ?>" data-nis="<?= esc($a['nis']) ?>" data-tipe="siswa" data-kelas="<?= esc($a['nama_kelas'] ?? '') ?>" data-saldo="<?= $saldo ?>" data-kas="<?= $kasTersediaAkun($a) ?>" title="Setor"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8"/><path d="M8 12h8"/></svg><span>Setor</span></button>
                    <button class="ku-action-btn modal-trigger" data-modal="modal-transaksi" data-akun="<?= $a['id'] ?>" data-nama="<?= esc($a['nama']) ?>" data-nis="<?= esc($a['nis']) ?>" data-tipe="siswa" data-kelas="<?= esc($a['nama_kelas'] ?? '') ?>" data-saldo="<?= $saldo ?>" data-kas="<?= $kasTersediaAkun($a) ?>" title="Tarik"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg><span>Tarik</span></button>
                    <button class="ku-action-btn riwayat-btn" data-akun="<?= $a['id'] ?>" title="Riwayat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></svg><span>Riwayat</span></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="6">
                <div class="ku-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  <p>Belum ada data tabungan siswa</p>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="ku-pagination" style="padding:10px 14px;justify-content:space-between;flex-wrap:wrap">
            <span class="info">Menampilkan 1–<?= count($tabunganSiswa) ?: 0 ?> dari <?= count($tabunganSiswa) ?> rekening</span>
            <span>Hal 1 dari 1</span>
          </div>
        </div>
      </div>

      <!-- Tab Guru -->
      <div class="tab-content" id="tab-guru" style="display:none">
        <div class="ku-table-wrap">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>NIP</th>
                <th>Bidang</th>
                <th>Tahun Ajaran</th>
                <th>Saldo</th>
                <th style="text-align:right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($tabunganGuru)): ?>
              <?php foreach ($tabunganGuru as $a):
                $ac = avatarColor($a['nama'] ?? '?', $avatarColors);
                $saldo = (float)$a['saldo'];
              ?>
              <tr id="akun-<?= $a['id'] ?>">
                <td><div class="ku-cell-person"><div class="ku-avatar-sm" style="background:<?= $ac ?>;color:#fff;font-weight:700"><?= strtoupper(substr($a['nama'] ?? '?', 0, 2)) ?></div><div><div class="ku-person-name"><?= esc($a['nama']) ?></div><div class="ku-person-sub">Guru</div></div></div></td>
                <td class="ku-td-num"><?= esc($a['nip']) ?></td>
                <td><?= esc($a['bidang'] ?? '-') ?></td>
                <td><?= esc($a['tahun_ajaran'] ?? '-') ?></td>
                <td><span class="ku-badge <?= $saldo > 0 ? 'ku-badge-green' : 'ku-badge-slate' ?>">Rp <?= number_format($saldo, 0, ',', '.') ?></span></td>
                <td style="text-align:right">
                  <div class="ku-actions">
                    <button class="ku-action-btn modal-trigger" data-modal="modal-transaksi" data-akun="<?= $a['id'] ?>" data-nama="<?= esc($a['nama']) ?>" data-nis="<?= esc($a['nip']) ?>" data-tipe="guru" data-saldo="<?= $a['saldo'] ?>" data-kas="<?= $kasTersediaAkun($a) ?>" title="Setor"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8"/><path d="M8 12h8"/></svg><span>Setor</span></button>
                    <button class="ku-action-btn modal-trigger" data-modal="modal-transaksi" data-akun="<?= $a['id'] ?>" data-nama="<?= esc($a['nama']) ?>" data-nis="<?= esc($a['nip']) ?>" data-tipe="guru" data-saldo="<?= $a['saldo'] ?>" data-kas="<?= $kasTersediaAkun($a) ?>" title="Tarik"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg><span>Tarik</span></button>
                    <button class="ku-action-btn riwayat-btn" data-akun="<?= $a['id'] ?>" title="Riwayat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></svg><span>Riwayat</span></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="6">
                <div class="ku-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  <p>Belum ada data tabungan guru</p>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="ku-pagination" style="padding:10px 14px;justify-content:space-between;flex-wrap:wrap">
            <span class="info">Menampilkan 1–<?= count($tabunganGuru) ?: 0 ?> dari <?= count($tabunganGuru) ?> rekening</span>
            <span>Hal 1 dari 1</span>
          </div>
        </div>
      </div>

      <!-- Tab Nasabah -->
      <div class="tab-content" id="tab-nasabah" style="display:none">
        <div class="ku-table-wrap">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>No. Telp</th>
                <th>Saldo</th>
                <th style="text-align:right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($tabunganNasabah)): ?>
              <?php foreach ($tabunganNasabah as $a):
                $ac = avatarColor($a['nama'] ?? '?', $avatarColors);
                $saldo = (float)$a['saldo'];
              ?>
              <tr id="akun-<?= $a['id'] ?>">
                <td><div class="ku-cell-person"><div class="ku-avatar-sm" style="background:<?= $ac ?>;color:#fff;font-weight:700"><?= strtoupper(substr($a['nama'] ?? '?', 0, 2)) ?></div><div><div class="ku-person-name"><?= esc($a['nama']) ?></div>                    <div class="ku-person-sub">Non Civitas</div></div></div></td>
                <td><?= esc($a['no_telp'] ?? '-') ?></td>
                <td><span class="ku-badge <?= $saldo > 0 ? 'ku-badge-green' : 'ku-badge-slate' ?>">Rp <?= number_format($saldo, 0, ',', '.') ?></span></td>
                <td style="text-align:right">
                  <div class="ku-actions">
                    <button class="ku-action-btn modal-trigger" data-modal="modal-transaksi" data-akun="<?= $a['id'] ?>" data-nama="<?= esc($a['nama']) ?>" data-nis="" data-tipe="nasabah" data-saldo="<?= $a['saldo'] ?>" data-kas="<?= $kasTersediaAkun($a) ?>" title="Setor"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8"/><path d="M8 12h8"/></svg><span>Setor</span></button>
                    <button class="ku-action-btn modal-trigger" data-modal="modal-transaksi" data-akun="<?= $a['id'] ?>" data-nama="<?= esc($a['nama']) ?>" data-nis="" data-tipe="nasabah" data-saldo="<?= $a['saldo'] ?>" data-kas="<?= $kasTersediaAkun($a) ?>" title="Tarik"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg><span>Tarik</span></button>
                    <button class="ku-action-btn riwayat-btn" data-akun="<?= $a['id'] ?>" title="Riwayat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></svg><span>Riwayat</span></button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="4">
                <div class="ku-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  <p>Belum ada data tabungan non civitas</p>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
          <div class="ku-pagination" style="padding:10px 14px;justify-content:space-between;flex-wrap:wrap">
            <span class="info">Menampilkan 1–<?= count($tabunganNasabah) ?: 0 ?> dari <?= count($tabunganNasabah) ?> rekening</span>
            <span>Hal 1 dari 1</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Transaksi (setor/tarik) -->
<div class="ku-modal-overlay" id="modal-transaksi">
  <div class="ku-modal-box">
    <form method="post" action="/tabungan/transaksi">
      <?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3>Transaksi Tabungan</h3>
          <p id="modal-info">Pilih jenis transaksi</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="akun_id" id="modal-akun-id" value="">
        <input type="hidden" name="tipe" id="modal-tipe" value="setor">
        <input type="hidden" name="tab" id="modal-tab" value="siswa">
        <input type="hidden" name="search" id="modal-search" value="">
        <input type="hidden" name="kelas" id="modal-kelas" value="">
        <input type="hidden" name="bidang" id="modal-bidang" value="">

        <div id="modal-saldo-info" style="text-align:center;padding:8px 0 12px;font-size:14px;color:var(--slate-500)">
          Saldo saat ini: <strong id="modal-saldo" style="color:var(--navy-800)">Rp 0</strong>
        </div>

        <div id="modal-tarik-info" style="display:none;margin-bottom:12px;padding:10px 12px;border-radius:8px;background:#eff6ff;border:1px solid #bfdbfe;font-size:12px;line-height:1.5;color:#1e40af">
          <strong>Perhatian:</strong> <span id="modal-tarik-msg"></span>
        </div>

        <div class="tx-type-toggle">
          <button type="button" class="active deposit" data-tx="setor">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
            Setor
          </button>
          <button type="button" class="withdraw" data-tx="tarik">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>
            Tarik
          </button>
        </div>

        <div class="ku-field">
          <label>Nominal</label>
          <div class="amount-input-wrap">
            <span class="prefix">Rp</span>
            <input type="text" name="nominal" value="" inputmode="numeric" required placeholder="0">
          </div>
        </div>

        <div class="ku-field">
          <label>Metode Pembayaran</label>
          <div class="metode-toggle">
            <button type="button" class="active" data-metode="tunai">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0-4 0"/></svg>
              Tunai
            </button>
            <button type="button" data-metode="transfer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8h20"/><path d="M8 16h8"/></svg>
              Transfer
            </button>
          </div>
          <input type="hidden" name="metode" id="metode-value" value="tunai">
        </div>

        <div class="ku-field">
          <label>Tanggal &amp; Jam Transaksi</label>
          <input type="datetime-local" name="tgl_transaksi" value="<?= date('Y-m-d\TH:i') ?>" style="width:100%;padding:10px 14px;border:1px solid var(--slate-300);border-radius:var(--r-sm);font-size:14px">
        </div>

        <div class="ku-field">
          <label>Catatan (opsional)</label>
          <textarea name="catatan" placeholder="Contoh: Setoran rutin minggu ke-3"></textarea>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary" id="btn-konfirmasi">Konfirmasi Transaksi</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tambah Rekening -->
<div class="ku-modal-overlay" id="modal-create-akun">
  <div class="ku-modal-box">
    <form method="post" action="/tabungan/create-account">
      <?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3>Tambah Rekening Baru</h3>
          <p>Pilih tipe dan pemilik rekening</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <div class="ku-field">
          <label>Tipe Rekening</label>
          <select name="tipe" id="tipe-rekening" required>
            <option value="siswa">Siswa</option>
            <option value="guru">Guru</option>
            <option value="nasabah">Non Civitas</option>
          </select>
        </div>
        <div class="ku-field" id="field-orang">
          <label>Pemilik Rekening</label>
          <select name="orang_id" id="orang-select" required>
            <option value="">— Pilih Siswa —</option>
            <?php foreach ($allSiswa as $s): ?>
            <option value="<?= $s['id'] ?>" data-tipe="siswa"><?= esc($s['nama']) ?> (<?= esc($s['nis']) ?>)</option>
            <?php endforeach; ?>
            <?php foreach ($allGuru as $g): ?>
            <option value="<?= $g['id'] ?>" data-tipe="guru" style="display:none"><?= esc($g['nama']) ?> (<?= esc($g['nip']) ?>)</option>
            <?php endforeach; ?>
            <?php foreach ($allNasabah as $n): ?>
            <option value="<?= $n['id'] ?>" data-tipe="nasabah" style="display:none"><?= esc($n['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary">Buat Rekening</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Riwayat -->
<div class="ku-modal-overlay" id="modal-riwayat">
  <div class="ku-modal-box" style="max-width:860px">
    <div class="ku-modal-head">
      <div>
        <h3>Riwayat Transaksi</h3>
        <p id="riwayat-sub">Memuat...</p>
      </div>
      <button type="button" class="ku-modal-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="ku-modal-body" id="riwayat-body">
      <p style="color:var(--slate-400); text-align:center; padding:20px 0">Memuat data...</p>
    </div>
    <div class="ku-modal-foot" style="justify-content:center">
      <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Tutup</button>
    </div>
  </div>
</div>

<!-- Modal Koreksi Transaksi -->
<div class="ku-modal-overlay" id="modal-edit-tx">
  <div class="ku-modal-box" style="max-width:500px">
    <form id="form-edit-tx" method="post" action="/tabungan/edit-transaksi">
      <?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3>Koreksi Transaksi</h3>
          <p id="edit-tx-info">Ubah nominal atau keterangan transaksi</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="edit-tx-id" value="">
        <div id="edit-tx-saldo-info" style="text-align:center;padding:4px 0 12px;font-size:13px;color:var(--slate-500)"></div>

        <div class="ku-field">
          <label>Nominal</label>
          <div class="amount-input-wrap">
            <span class="prefix">Rp</span>
            <input type="text" name="nominal" id="edit-tx-nominal" value="" inputmode="numeric" required placeholder="0">
          </div>
        </div>

        <div class="ku-field">
          <label>Metode Pembayaran</label>
          <div class="metode-toggle" id="edit-tx-metode-toggle">
            <button type="button" data-metode="tunai">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0-4 0"/></svg>
              Tunai
            </button>
            <button type="button" data-metode="transfer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 8h20"/><path d="M8 16h8"/></svg>
              Transfer
            </button>
          </div>
          <input type="hidden" name="metode" id="edit-tx-metode" value="tunai">
        </div>

        <div class="ku-field">
          <label>Tanggal &amp; Jam Transaksi</label>
          <input type="datetime-local" name="tgl_transaksi" id="edit-tx-tgl" value="" style="width:100%;padding:10px 14px;border:1px solid var(--slate-300);border-radius:var(--r-sm);font-size:14px">
        </div>

        <div class="ku-field">
          <label>Catatan (opsional)</label>
          <textarea name="catatan" id="edit-tx-catatan" placeholder="Catatan transaksi"></textarea>
        </div>

        <div style="margin-top:6px;padding:10px 12px;border-radius:8px;background:#fffbeb;border:1px solid #fde68a;font-size:12px;color:#92400e">
          <strong>Perhatian:</strong> Mengubah transaksi ini akan menghitung ulang saldo berjalan rekening serta menyesuaikan kas unit dan kas yayasan (termasuk yang sudah tutup buku).
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary" id="btn-simpan-koreksi">Simpan Koreksi</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Scroll ke baris yang baru ditransaksi
  <?php $scrollId = session()->getFlashdata('scroll_to_akun'); ?>
  <?php if ($scrollId): ?>
  setTimeout(function() {
    var el = document.getElementById('akun-<?= $scrollId ?>');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }, 150);
  <?php endif; ?>

  // Activate tab from GET param or default to siswa
  var urlParams = new URLSearchParams(window.location.search);
  var activeTab = urlParams.get('tab') || 'siswa';
  document.querySelectorAll('.tabs-switch button').forEach(function(b) {
    b.classList.toggle('active', b.getAttribute('data-tab') === activeTab);
  });
  document.querySelectorAll('.tab-content').forEach(function(t) {
    t.style.display = t.id === 'tab-' + activeTab ? '' : 'none';
  });
  document.querySelectorAll('.tab-filter').forEach(function(f) {
    f.style.display = f.getAttribute('data-tab') === activeTab ? '' : 'none';
  });
  document.getElementById('tab-input').value = activeTab;
  // Preserve filter params for modal form
  document.getElementById('modal-search').value = urlParams.get('search') || '';
  document.getElementById('modal-kelas').value = urlParams.get('kelas') || '';
  document.getElementById('modal-bidang').value = urlParams.get('bidang') || '';

document.querySelectorAll('.tabs-switch button').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.tabs-switch button').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    document.querySelectorAll('.tab-content').forEach(function(c){ c.style.display = 'none'; });
    document.getElementById('tab-' + this.dataset.tab).style.display = 'block';
    var tabName = this.dataset.tab;
    document.querySelectorAll('.tab-filter').forEach(function(f) {
      f.style.display = f.getAttribute('data-tab') === tabName ? '' : 'none';
    });
    document.getElementById('tab-input').value = tabName;
  });
});

var modalKasTersedia = 0;
function updateTarikInfo(){
  var info = document.getElementById('modal-tarik-info');
  if (!info) return;
  var isTarik = document.getElementById('modal-tipe').value === 'tarik';
  if (!isTarik) { info.style.display = 'none'; return; }
  var msg = document.getElementById('modal-tarik-msg');
  var nominal = parseFloat((document.querySelector('[name="nominal"]').value || '').replace(/[^0-9]/g, '')) || 0;
  var direct = 'Penarikan akan langsung dicatat sebagai pengeluaran Kas Unit (kas tersedia Rp ' + Number(modalKasTersedia).toLocaleString('id-ID') + '), tanpa pengajuan dana ke Yayasan.';
  var pengajuan = 'Penarikan akan diproses setelah pengajuan dana ke Yayasan disetujui. Saat disetujui, dana dari Yayasan masuk ke Kas Unit (pemasukan), lalu penarikan dicatat sebagai pengeluaran. Jika pengajuan ditolak, penarikan dibatalkan dan saldo rekening dikembalikan.';
  var cukup = modalKasTersedia > 0 && (nominal === 0 || nominal <= modalKasTersedia);
  if (cukup) {
    msg.textContent = direct;
    info.style.background = '#ecfdf5';
    info.style.border = '1px solid #a7f3d0';
    info.style.color = '#065f46';
  } else {
    msg.textContent = pengajuan;
    info.style.background = '#eff6ff';
    info.style.border = '1px solid #bfdbfe';
    info.style.color = '#1e40af';
  }
  info.style.display = 'block';
}

document.querySelectorAll('.tx-type-toggle button').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.tx-type-toggle button').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    document.getElementById('modal-tipe').value = this.dataset.tx;
    var isSetor = this.dataset.tx === 'setor';
    document.getElementById('btn-konfirmasi').textContent = isSetor ? 'Konfirmasi Setoran' : 'Konfirmasi Penarikan';
    document.getElementById('btn-konfirmasi').className = 'ku-btn ' + (isSetor ? 'ku-btn-primary' : 'ku-btn-danger');
    updateTarikInfo();
  });
});

// Metode toggle
document.querySelectorAll('.metode-toggle button').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.metode-toggle button').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    document.getElementById('metode-value').value = this.dataset.metode;
  });
});

document.querySelectorAll('.modal-trigger[data-akun]').forEach(function(btn){
  btn.addEventListener('click', function(e){
    var modal = document.getElementById('modal-transaksi');
    modal.classList.add('active');
    document.getElementById('modal-akun-id').value = this.dataset.akun;
    var label = this.dataset.nama + ' · ' + this.dataset.nis;
    if(this.dataset.kelas) label += ' · ' + this.dataset.kelas;
    document.getElementById('modal-info').textContent = label;
    var saldo = parseFloat(this.dataset.saldo) || 0;
    document.getElementById('modal-saldo').textContent = 'Rp ' + Number(saldo).toLocaleString('id-ID');
    var isSetor = this.title === 'Setor';
    var curTab = document.getElementById('tab-input').value || 'siswa';
    document.getElementById('modal-tab').value = curTab;
    document.querySelectorAll('.tx-type-toggle button').forEach(function(b){ b.classList.remove('active'); });
    var targetBtn = isSetor ? document.querySelector('.tx-type-toggle .deposit') : document.querySelector('.tx-type-toggle .withdraw');
    if (targetBtn) targetBtn.classList.add('active');
    document.getElementById('modal-tipe').value = isSetor ? 'setor' : 'tarik';
    document.getElementById('btn-konfirmasi').textContent = isSetor ? 'Konfirmasi Setoran' : 'Konfirmasi Penarikan';
    document.getElementById('btn-konfirmasi').className = 'ku-btn ' + (isSetor ? 'ku-btn-primary' : 'ku-btn-danger');
    modalKasTersedia = parseFloat(this.dataset.kas) || 0;
    updateTarikInfo();
  });
});

// Confirm withdrawal
document.getElementById('btn-konfirmasi')?.addEventListener('click', function(e){
  if (document.getElementById('modal-tipe').value === 'tarik') {
    var nominal = document.querySelector('[name="nominal"]').value.replace(/[^0-9]/g, '');
    var saldo = document.getElementById('modal-saldo').textContent.replace(/[^0-9]/g, '');
    if (parseInt(nominal) > parseInt(saldo)) {
      e.preventDefault();
      alert('Saldo tidak mencukupi! Saldo saat ini: ' + document.getElementById('modal-saldo').textContent);
    } else {
      var msgConfirm = 'Yakin menarik Rp ' + Number(nominal).toLocaleString('id-ID') + '? ';
      msgConfirm += (modalKasTersedia >= parseInt(nominal) && modalKasTersedia > 0)
        ? 'Penarikan akan langsung dicatat sebagai pengeluaran Kas Unit (kas tersedia Rp ' + Number(modalKasTersedia).toLocaleString('id-ID') + ').'
        : 'Pengajuan dana ke yayasan akan dibuat dan menunggu persetujuan.';
      if (!confirm(msgConfirm)) {
        e.preventDefault();
      }
    }
  }
});

// Format nominal input with thousand separators
document.querySelector('[name="nominal"]')?.addEventListener('input', function(){
  var val = this.value.replace(/[^0-9]/g, '');
  if (val) this.value = Number(val).toLocaleString('id-ID');
  else this.value = '';
  updateTarikInfo();
});

// Tipe rekening toggle
document.getElementById('tipe-rekening').addEventListener('change', function(){
  var tipe = this.value;
  var labels = {'siswa':'Pilih Siswa','guru':'Pilih Guru','nasabah':'Pilih Non Civitas'};
  document.querySelector('#field-orang label').textContent = labels[tipe] || 'Pilih Pemilik';
  document.querySelectorAll('#orang-select option').forEach(function(opt){
    if (opt.value === '') return;
    opt.style.display = opt.dataset.tipe === tipe ? '' : 'none';
  });
  document.getElementById('orang-select').value = '';
});

// Riwayat AJAX modal
var _riwayatAkun = null;
function loadRiwayat(akunId){
  var modal = document.getElementById('modal-riwayat');
  var body = document.getElementById('riwayat-body');
  var sub = document.getElementById('riwayat-sub');
  body.innerHTML = '<p style="color:var(--slate-400); text-align:center; padding:20px 0">Memuat data...</p>';
  sub.textContent = 'Mengambil riwayat transaksi...';
  modal.classList.add('active');

  var xhr = new XMLHttpRequest();
  xhr.open('GET', '/tabungan/riwayat/' + akunId, true);
  xhr.onload = function(){
    if (xhr.status === 200) {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.success) {
          body.innerHTML = res.html;
          sub.textContent = 'Riwayat transaksi';
          _riwayatAkun = akunId;
        } else {
          body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal memuat data</p>';
        }
      } catch(e) {
        body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal memproses respons</p>';
      }
    } else {
      body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal memuat data (HTTP ' + xhr.status + ')</p>';
    }
  };
  xhr.onerror = function(){
    body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal terhubung ke server</p>';
  };
  xhr.send();
}

document.querySelectorAll('.riwayat-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    loadRiwayat(this.dataset.akun);
  });
});

// Koreksi transaksi: buka modal (delegated karena konten riwayat dimuat via AJAX)
document.addEventListener('click', function(e){
  var btn = e.target.closest('.edit-tx-btn');
  if (!btn) return;
  document.getElementById('edit-tx-id').value = btn.dataset.id;
  document.getElementById('edit-tx-nominal').value = Number(btn.dataset.nominal).toLocaleString('id-ID');
  document.getElementById('edit-tx-catatan').value = btn.dataset.catatan;
  document.getElementById('edit-tx-tgl').value = btn.dataset.tgl;
  var metode = btn.dataset.metode || 'tunai';
  document.getElementById('edit-tx-metode').value = metode;
  var tipe = btn.dataset.tipe === 'setor' ? 'Setoran' : 'Penarikan';
  document.getElementById('edit-tx-info').textContent = tipe + ' - ID transaksi #' + btn.dataset.id;
  document.querySelectorAll('#edit-tx-metode-toggle button').forEach(function(b){
    b.classList.toggle('active', b.dataset.metode === metode);
  });
  document.getElementById('modal-edit-tx').classList.add('active');
});

// Format nominal input koreksi
document.getElementById('edit-tx-nominal')?.addEventListener('input', function(){
  var val = this.value.replace(/[^0-9]/g, '');
  if (val) this.value = Number(val).toLocaleString('id-ID');
  else this.value = '';
});

// Metode toggle koreksi
document.querySelectorAll('#edit-tx-metode-toggle button').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('#edit-tx-metode-toggle button').forEach(function(b){ b.classList.remove('active'); });
    this.classList.add('active');
    document.getElementById('edit-tx-metode').value = this.dataset.metode;
  });
});

// Submit koreksi via AJAX
document.getElementById('form-edit-tx')?.addEventListener('submit', function(e){
  e.preventDefault();
  var form = this;
  var btn = document.getElementById('btn-simpan-koreksi');
  btn.disabled = true;
  btn.textContent = 'Menyimpan...';
  var data = new FormData(form);
  var xhr = new XMLHttpRequest();
  xhr.open('POST', form.action, true);
  xhr.onload = function(){
    btn.disabled = false;
    btn.textContent = 'Simpan Koreksi';
    try {
      var res = JSON.parse(xhr.responseText);
      if (res.success) {
        document.getElementById('modal-edit-tx').classList.remove('active');
        alert(res.message || 'Transaksi berhasil dikoreksi.');
        if (_riwayatAkun) loadRiwayat(_riwayatAkun);
      } else {
        alert(res.message || 'Koreksi gagal disimpan.');
      }
    } catch(err) {
      alert('Gagal memproses respons server.');
    }
  };
  xhr.onerror = function(){
    btn.disabled = false;
    btn.textContent = 'Simpan Koreksi';
    alert('Gagal terhubung ke server.');
  };
  xhr.send(data);
});
</script>
<?= view('layout/footer') ?>
