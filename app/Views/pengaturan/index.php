<?php
  $pageTitle = 'Pengaturan';
  $pageDesc = 'Konfigurasi sistem dan manajemen pengguna';
  $avatarColors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#06B6D4'];
  function avCol($n, $c) { return $c[crc32($n) % count($c)]; }
  $users = $users ?? [];
  $sekolahUser = $sekolahUser ?? 'admin';
  $academicYears = $academicYears ?? [];
  $activeTa = $activeTa ?? null;
?>
<?= view('layout/header') ?>
<div class="app-shell">
  <?= view('layout/sidebar', ['user' => $user ?? []]) ?>
  <div class="main-area">
    <?= view('layout/topbar', ['user' => $user ?? [], 'pageTitle' => $pageTitle, 'pageDesc' => $pageDesc]) ?>
    <div class="content">

      <?php if ($msg = session()->getFlashdata('success')): ?>
      <div class="ku-notif ku-notif-success">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
        <span><?= esc($msg) ?></span>
        <button onclick="this.parentElement.remove()">&times;</button>
      </div>
      <?php endif; ?>
      <?php if ($msg = session()->getFlashdata('error')): ?>
      <div class="ku-notif ku-notif-error">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
        <span><?= esc($msg) ?></span>
        <button onclick="this.parentElement.remove()">&times;</button>
      </div>
      <?php endif; ?>

      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Profil Sekolah</h3>
            <div class="ku-card-sub">Informasi identitas sekolah</div>
          </div>
        </div>
        <div class="ku-card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
            <div class="ku-field">
              <label>Nama Sekolah</label>
              <input type="text" value="<?= esc($schoolName ?? 'Al-Jawahir Attarbawi') ?>" readonly style="background:var(--ku-slate-50)">
            </div>
            <div class="ku-field">
              <label>Tahun Ajaran Aktif</label>
              <form method="POST" action="/pengaturan/set-active-ta" style="display:flex;gap:8px">
                <select name="tahun_ajaran_id" class="ku-filter-select" style="flex:1">
                  <?php foreach ($academicYears as $ta): ?>
                  <option value="<?= $ta['id'] ?>" <?= $activeTa && $activeTa['id'] == $ta['id'] ? 'selected' : '' ?>><?= esc($ta['tahun_ajaran']) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if ($sekolahUser === 'admin'): ?>
                <button type="submit" class="ku-btn ku-btn-primary" style="white-space:nowrap">Simpan</button>
                <button type="button" class="ku-btn ku-btn-outline modal-trigger" data-modal="modal-ta">+</button>
                <?php endif; ?>
              </form>
            </div>
          </div>
        </div>
      </div>

      <?php if ($sekolahUser === 'admin'): ?>
      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Manajemen Data</h3>
            <div class="ku-card-sub">Backup atau hapus seluruh data</div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="/pengaturan/export-backup" class="ku-btn ku-btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
              Backup Data
            </a>
            <button type="button" class="ku-btn ku-btn-accent modal-trigger" data-modal="modal-restore-backup">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
              Restore Backup
            </button>
            <button class="ku-btn ku-btn-danger modal-trigger" data-modal="modal-hapus-data">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
              Hapus Data
            </button>
          </div>
        </div>
        <div class="ku-card-body" style="padding-top:0;font-size:13px;color:var(--ku-slate-500);line-height:1.5">
          <strong>Backup:</strong> Download semua data (siswa, guru, tagihan, pembayaran, tabungan, kas umum) ke Excel.<br>
          <strong>Restore Backup:</strong> Upload file backup untuk mengembalikan seluruh data.<br>
          <strong>Hapus:</strong> Hapus semua data transaksional. Data pengguna, tahun ajaran, dan pengaturan tetap aman.
        </div>
      </div>
      <?php endif; ?>

      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Pengguna Sistem</h3>
            <div class="ku-card-sub">Akun yang terdaftar di aplikasi</div>
          </div>
          <?php if ($sekolahUser === 'admin'): ?>
          <button class="ku-btn ku-btn-primary modal-trigger" data-modal="modal-user" data-mode="add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            Tambah Pengguna
          </button>
          <?php endif; ?>
        </div>
        <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Sekolah</th>
                <th>Terakhir Login</th>
                <?php if ($sekolahUser === 'admin'): ?>
                <th style="text-align:right">Aksi</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><div class="cell-person"><div class="avatar-sm" style="background:<?= avCol($u['nama'] ?? '?', $avatarColors) ?>;color:#fff;font-weight:700"><?= strtoupper(substr($u['nama'] ?? '?', 0, 2)) ?></div><div class="p-name"><?= esc($u['nama']) ?></div></div></td>
                <td><?= esc($u['email']) ?></td>
                <td><span class="ku-badge <?= $u['role'] === 'admin' ? 'ku-badge-green' : 'ku-badge-amber' ?>"><?= ucfirst($u['role']) ?></span></td>
                <td><?= esc($u['sekolah']) ?></td>
                <td><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : '-' ?></td>
                <?php if ($sekolahUser === 'admin'): ?>
                <td>
                  <div class="ku-actions">
                    <button class="ku-action-btn modal-trigger" data-modal="modal-user" data-mode="edit"
                      data-user='<?= htmlspecialchars(json_encode([
                        'id' => $u['id'],
                        'nama' => $u['nama'],
                        'email' => $u['email'],
                        'role' => $u['role'],
                        'sekolah' => $u['sekolah'],
                      ]), ENT_QUOTES, 'UTF-8') ?>'
                      title="Edit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    <form method="POST" action="/pengaturan/delete" style="display:inline" onsubmit="return confirm('Yakin hapus akun <?= esc($u['nama']) ?>?')">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <button type="submit" class="ku-action-btn ku-action-del" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal User (Add / Edit) - Admin Only -->
<div class="ku-modal-overlay" id="modal-user">
  <div class="ku-modal-box" style="max-width:460px">
    <form method="POST" action="/pengaturan/store" id="form-user">
      <div class="ku-modal-head">
        <div>
          <h3 id="modal-user-title">Tambah Pengguna</h3>
          <p id="modal-user-sub">Buat akun baru untuk staf</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="user-id">
        <div class="ku-field">
          <label>Nama Lengkap <span style="color:var(--ku-red)">*</span></label>
          <input type="text" name="nama" id="user-nama" placeholder="Nama pengguna" required>
        </div>
        <div class="ku-field-row">
          <div class="ku-field">
            <label>Email <span style="color:var(--ku-red)">*</span></label>
            <input type="email" name="email" id="user-email" placeholder="email@sekolah.sch.id" required>
          </div>
          <div class="ku-field">
            <label>Role <span style="color:var(--ku-red)">*</span></label>
            <select name="role" id="user-role" required>
              <option value="">— Pilih —</option>
              <option value="admin">Admin</option>
              <option value="staff">Staff</option>
              <option value="kepala_sekolah">Kepala Sekolah</option>
            </select>
          </div>
        </div>
        <div class="ku-field">
          <label>Password <span style="color:var(--ku-red)" id="pw-req-label">*</span></label>
          <input type="password" name="password" id="user-password" placeholder="Minimal 6 karakter" autocomplete="new-password">
          <div style="font-size:11px;color:var(--ku-slate-400);margin-top:5px" id="pw-hint">Kosongkan jika tidak ingin mengubah password</div>
        </div>
        <div class="ku-field">
          <label>Sekolah</label>
          <select name="sekolah" id="user-sekolah">
            <option value="admin">Admin (Semua Sekolah)</option>
            <option value="smp">SMP</option>
            <option value="sd">SD</option>
            <option value="ra">RA</option>
          </select>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M20 6 9 17l-5-5"/></svg>
          Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Hapus Data -->
<div class="ku-modal-overlay" id="modal-hapus-data">
  <div class="ku-modal-box" style="max-width:440px">
    <form method="POST" action="/pengaturan/delete-all-data">
      <div class="ku-modal-head">
        <div>
          <h3 style="color:var(--ku-red)">Hapus Semua Data</h3>
          <p style="color:var(--ku-slate-500)">Tindakan ini tidak bisa dibatalkan</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <div style="padding:12px 14px;background:#FEF2F2;border-radius:8px;font-size:12px;color:#991B1B;line-height:1.6;margin-bottom:16px">
          <strong>Data yang akan dihapus:</strong><br>
          Siswa, Guru, Tagihan, Pembayaran, Tabungan, Transaksi Tabungan, Kas Umum, dan Kelas.<br><br>
          <strong>Data yang tetap aman:</strong><br>
          Pengguna, Tahun Ajaran, dan Pengaturan.
        </div>
        <div class="ku-field">
          <label>Ketik <strong style="color:var(--ku-red)">HAPUS SEMUA</strong> untuk konfirmasi</label>
          <input type="text" name="confirm" placeholder="HAPUS SEMUA" required style="width:100%;padding:10px 12px;border:1.5px solid var(--ku-red);border-radius:8px;font-size:14px;font-weight:600;text-align:center;letter-spacing:0.05em">
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
          Hapus Semua Data
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tahun Ajaran -->
<div class="ku-modal-overlay" id="modal-ta">
  <div class="ku-modal-box" style="max-width:420px">
    <div class="ku-modal-head">
      <div>
        <h3>Tambah Tahun Ajaran</h3>
        <p>Buat tahun ajaran baru</p>
      </div>
      <button class="ku-modal-close">&times;</button>
    </div>
    <form method="POST" action="/pengaturan/add-ta">
      <div class="ku-modal-body">
        <div class="ku-field">
          <label>Tahun Ajaran <span style="color:var(--ku-red)">*</span></label>
          <input type="text" name="tahun_ajaran" placeholder="contoh: 2026/2027" required>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Import Data Siswa -->
<div class="ku-modal-overlay" id="modal-import-siswa">
  <div class="ku-modal-box" style="max-width:520px">
    <form method="POST" action="/siswaguru/siswa/import-excel" enctype="multipart/form-data">
      <div class="ku-modal-head">
        <div>
          <h3>Import Data Siswa dari Excel</h3>
          <p>Upload file .xlsx atau .xls</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <div style="margin-bottom:16px;padding:12px 14px;background:#FEF3C7;border-radius:8px;font-size:12px;color:#92400E;line-height:1.5">
          <strong>Format kolom Siswa (baris pertama = header):</strong><br>
          NIS, Nama, Jenis Kelamin (L/P/Laki-laki/Perempuan), Status (Aktif/Lulus/Pindah), Kelas, Unit (RA/SD/SMP), SPP Per Bulan, Tagihan Awal Tahun<br>
          <strong>Format kolom Guru (baris pertama = header):</strong><br>
          NIP, Nama, Jenis Kelamin (L/P/Laki-laki/Perempuan), Bidang, Sekolah, Status (Aktif/Tidak Aktif)<br>
          <span style="font-size:11px;color:#B45309">Nama sheet = unit. Gunakan file Backup contoh format.</span>
        </div>
        <div class="ku-field">
          <label>Pilih File Excel</label>
          <input type="file" name="file_excel" accept=".xlsx,.xls" required style="width:100%;padding:10px;border:1.5px dashed var(--ku-slate-300);border-radius:8px;background:var(--ku-slate-50);font-size:13px">
        </div>
        <div style="text-align:center;margin-top:8px">
          <a href="/siswaguru/export-csv" style="font-size:12px;color:var(--navy);text-decoration:underline">Download contoh format (Backup Excel)</a>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
          Import
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Restore Backup -->
<div class="ku-modal-overlay" id="modal-restore-backup">
  <div class="ku-modal-box" style="max-width:520px">
    <form method="POST" action="/pengaturan/import-backup" enctype="multipart/form-data">
      <div class="ku-modal-head">
        <div>
          <h3>Restore Backup Data</h3>
          <p>Upload file backup .xlsx untuk mengembalikan seluruh data</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <div style="margin-bottom:16px;padding:12px 14px;background:#FEF3C7;border-radius:8px;font-size:12px;color:#92400E;line-height:1.5">
          <strong>Perhatian:</strong> Semua data yang ada saat ini akan <strong>diganti</strong> dengan data dari file backup.<br>
          Gunakan file hasil <strong>Backup Data</strong> (download dari menu Backup atau Pengaturan).
        </div>
        <div class="ku-field">
          <label>Pilih File Backup (.xlsx)</label>
          <input type="file" name="file_backup" accept=".xlsx,.xls" required style="width:100%;padding:10px;border:1.5px dashed var(--ku-slate-300);border-radius:8px;background:var(--ku-slate-50);font-size:13px">
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-accent">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
          Restore
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.modal-trigger[data-modal="modal-user"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var mode = btn.getAttribute('data-mode');
      var form = document.getElementById('form-user');
      var title = document.getElementById('modal-user-title');
      var sub = document.getElementById('modal-user-sub');
      var pwField = document.getElementById('user-password');
      var pwReq = document.getElementById('pw-req-label');
      var pwHint = document.getElementById('pw-hint');

      if (mode === 'add') {
        form.action = '/pengaturan/store';
        title.textContent = 'Tambah Pengguna';
        sub.textContent = 'Buat akun baru untuk staf';
        form.reset();
        document.getElementById('user-id').value = '';
        pwField.required = true;
        pwReq.style.display = '';
        pwHint.textContent = 'Minimal 6 karakter';
      } else {
        var d;
        try { d = JSON.parse(btn.getAttribute('data-user')); } catch(e) { return; }
        if (!d) return;
        form.action = '/pengaturan/update';
        title.textContent = 'Edit Pengguna';
        sub.textContent = 'Ubah data akun';
        document.getElementById('user-id').value = d.id || '';
        document.getElementById('user-nama').value = d.nama || '';
        document.getElementById('user-email').value = d.email || '';
        document.getElementById('user-role').value = d.role || '';
        document.getElementById('user-sekolah').value = d.sekolah || 'admin';
        pwField.required = false;
        pwReq.style.display = 'none';
        pwHint.textContent = 'Kosongkan jika tidak ingin mengubah password';
        pwField.value = '';
      }
    });
  });
});
</script>
<?= view('layout/footer') ?>
