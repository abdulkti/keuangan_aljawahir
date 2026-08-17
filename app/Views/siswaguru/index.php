<?php
  $pageTitle = 'Data Siswa & Guru';
  $pageDesc = 'Data master siswa dan guru';
  $students = $students ?? [];
  $teachers = $teachers ?? [];
  $nasabahList = $nasabahList ?? [];
  $classes = $classes ?? [];
  $kelas = $kelas ?? '';
  $search = $search ?? '';
  $sekolahUser = $sekolahUser ?? 'admin';
  $academicYears = $academicYears ?? [];
  $activeTa = $activeTa ?? null;
  $selectedTa = $selectedTa ?? '';
  $unitLabels = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP'];
  $avatarColors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#06B6D4'];
  function avCol($n, $c) { return $c[crc32($n) % count($c)]; }
?>
<?= view('layout/header') ?>
<style>
.btn-icon {
  width: 28px; height: 28px; border-radius: 6px;
  display: inline-flex; align-items: center; justify-content: center;
  border: none; cursor: pointer; background: var(--slate-100);
  color: var(--slate-400); font-size: 12px; transition: all .12s;
}
.btn-icon:hover { background: var(--slate-200); }
.btn-icon.del:hover { color: var(--red-500); background: var(--red-50); }
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

      <div class="ku-toolbar">
        <div class="ku-toolbar-left">
          <h1>Data Master</h1>
          <div class="desc"><?= count($students) ?> siswa aktif · <?= count($teachers) ?> guru aktif · <?= count($nasabahList) ?> non civitas</div>
        </div>
      </div>

      <div class="tabs-switch">
        <button class="active" data-tab="siswa">Data Siswa</button>
        <button data-tab="guru">Data Guru</button>
        <button data-tab="nasabah">Data Non Civitas</button>
      </div>

      <form method="GET" action="/siswaguru" class="ku-toolbar" id="filter-form">
        <input type="hidden" name="tab" id="tab-input" value="siswa">
        <select name="ta_id" class="select-filter" onchange="this.form.submit()">
          <option value="">Tahun Ajaran: Semua</option>
          <?php foreach ($academicYears as $ta): ?>
          <option value="<?= $ta['id'] ?>" <?= $selectedTa == $ta['id'] ? 'selected' : '' ?>><?= esc($ta['tahun_ajaran']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="ku-search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" name="search" placeholder="Cari nama atau NIS..." value="<?= esc($search) ?>" onkeydown="if(event.key==='Enter'){event.preventDefault();this.form.submit();}">
        </div>
        <select name="kelas" class="select-filter tab-filter" data-tab="siswa" onchange="this.form.submit()">
          <option value="">Kelas: Semua</option>
          <?php foreach ($classes as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $kelas == $c['id'] ? 'selected' : '' ?>><?= esc($c['tingkat'] . ' ' . $c['jurusan'] . ' - ' . $c['nama_kelas']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="bidang" class="select-filter tab-filter" data-tab="guru" onchange="this.form.submit()" style="display:none">
          <option value="">Bidang: Semua</option>
          <?php
          $bidangList = array_unique(array_filter(array_column($teachers, 'bidang')));
          sort($bidangList);
          foreach ($bidangList as $b): ?>
          <option value="<?= esc($b) ?>" <?= ($bidang ?? '') === $b ? 'selected' : '' ?>><?= esc($b) ?></option>
          <?php endforeach; ?>
        </select>
        <div style="display:flex;gap:6px;margin-left:auto">
          <a href="/siswaguru/export-csv?type=siswa" class="btn btn-sm ku-btn ku-btn-ghost" id="btn-backup" style="font-size:12px;padding:6px 12px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
            Backup
          </a>
          <button type="button" class="btn btn-sm ku-btn ku-btn-ghost modal-trigger" data-modal="modal-import-excel" style="font-size:12px;padding:6px 12px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
            Upload
          </button>
        </div>
      </form>

      <div class="tab-content" id="tab-siswa">
        <div style="display:flex;justify-content:flex-end;align-items:center;margin-bottom:8px;gap:6px;flex-wrap:wrap">
          <button type="button" class="btn ku-btn ku-btn-ghost" id="btn-siswa-naik-kelas" disabled style="opacity:0.4;font-size:13px;padding:8px 14px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M18 15V6"/><path d="M6 18V9"/><path d="m15 3 3 3 3-3"/><path d="M6 21l3-3 3 3"/></svg>
            Naik Kelas
          </button>
          <button type="button" class="btn ku-btn ku-btn-ghost" id="btn-siswa-turun-kelas" disabled style="opacity:0.4;font-size:13px;padding:8px 14px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M6 9l6 6 6-6"/></svg>
            Turun Kelas
          </button>
          <form method="POST" action="/siswaguru/siswa/delete-batch" id="form-siswa-batch-pindah" style="display:inline"><?= csrf_field() ?>
            <button type="button" class="btn ku-btn ku-btn-ghost" id="btn-siswa-batch-pindah" disabled style="opacity:0.4;font-size:13px;padding:8px 14px;color:var(--amber-500);border-color:var(--amber-200)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              Pindah
            </button>
          </form>
          <form method="POST" action="/siswaguru/siswa/hapus-batch" id="form-siswa-batch-hapus" style="display:inline"><?= csrf_field() ?>
            <button type="button" class="btn ku-btn ku-btn-ghost" id="btn-siswa-batch-hapus" disabled style="opacity:0.4;font-size:13px;padding:8px 14px;color:var(--red-500);border-color:var(--red-200)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
              Hapus
            </button>
          </form>
          <button class="btn ku-btn ku-btn-primary modal-trigger" data-modal="modal-siswa" data-mode="add" style="font-size:13px;padding:8px 16px">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M5 12h14M12 5v14"/></svg>
            Tambah Siswa
          </button>
        </div>
        <?php
          $totalSiswa = count($students);
          $totalL = count(array_filter($students, fn($s) => ($s['jenis_kelamin'] ?? '') === 'L'));
          $totalP = count(array_filter($students, fn($s) => ($s['jenis_kelamin'] ?? '') === 'P'));
        ?>
        <div style="display:flex;gap:16px;padding:10px 14px;margin-bottom:8px;background:var(--slate-50);border-radius:8px;font-size:13px;flex-wrap:wrap">
          <span><strong><?= $totalSiswa ?></strong> Total Siswa</span>
          <span style="color:var(--blue-600)"><strong><?= $totalL ?></strong> Laki-laki</span>
          <span style="color:var(--pink-600)"><strong><?= $totalP ?></strong> Perempuan</span>
        </div>
        <div class="ku-table-wrap">
          <table class="ku-table">
            <thead>
              <tr>
                <th style="width:32px"><input type="checkbox" id="select-all-siswa" style="width:16px;height:16px;border-radius:4px"></th>
                <th>NIS</th>
                <th>Nama</th>
                <th>Jenis Kelamin</th>
                <th>Status</th>
                <th>Kelas</th>
                <th>SPP / Bulan</th>
                <th>Tagihan Awal Tahun</th>
                <th style="width:60px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($students)): ?>
              <?php foreach ($students as $s):
                $status = $s['status'] ?? 'aktif';
              ?>
              <tr style="<?= $status !== 'aktif' ? 'opacity:0.6' : '' ?>">
                <td><input type="checkbox" class="cb-siswa" value="<?= $s['id'] ?>" style="width:16px;height:16px;border-radius:4px"></td>
                <td class="num"><?= esc($s['nis']) ?></td>
                <td><div class="cell-person"><div class="avatar-sm" style="background:<?= avCol($s['nama'] ?? '?', $avatarColors) ?>;color:#fff;font-weight:700"><?= strtoupper(substr($s['nama'] ?? '?', 0, 2)) ?></div><div class="p-name"><?= esc($s['nama']) ?></div></div></td>
                <td><?= $s['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                <td>
                  <?php if ($status === 'aktif'): ?>
                  <span class="badge success"><span class="bdot"></span>Aktif</span>
                  <?php elseif ($status === 'lulus'): ?>
                  <span class="badge" style="background:#DBEAFE;color:#1D4ED8"><span class="bdot" style="background:#1D4ED8"></span>Lulus</span>
                  <?php elseif ($status === 'pindah'): ?>
                  <span class="badge" style="background:#FEF3C7;color:#B45309"><span class="bdot" style="background:#B45309"></span>Pindah</span>
                  <?php endif; ?>
                </td>
                <td><a href="/siswaguru/kelas/<?= $s['kelas_id'] ?>" style="color:var(--navy-700);text-decoration:none"><?= esc($s['nama_kelas'] ?? '-') ?></a></td>
                <td class="num money"><?= ($s['nominal_spp'] ?? 0) > 0 ? number_format($s['nominal_spp'], 0, ',', '.') : '-' ?></td>
                <td class="num money"><?= ($s['nominal_awal_tahun'] ?? 0) > 0 ? number_format($s['nominal_awal_tahun'], 0, ',', '.') : '-' ?></td>
                <td>
                  <div style="display:flex;gap:2px">
                    <button class="btn-icon modal-trigger" data-modal="modal-siswa" data-mode="edit"
                      data-siswa='<?= htmlspecialchars(json_encode([
                        'id' => $s['id'], 'nis' => $s['nis'], 'nama' => $s['nama'],
                        'jenis_kelamin' => $s['jenis_kelamin'], 'status' => $s['status'],
                        'nama_kelas' => $s['nama_kelas'] ?? '', 'sekolah' => $s['sekolah'],
                        'nominal_spp' => $s['nominal_spp'] ?? 0, 'nominal_awal_tahun' => $s['nominal_awal_tahun'] ?? 0,
                        'tanggal_masuk' => $s['tanggal_masuk'] ?? '',
                        'tanggal_keluar' => $s['tanggal_keluar'] ?? '',
                      ]), ENT_QUOTES, 'UTF-8') ?>'
                      title="Edit" onclick="event.stopPropagation()">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    <form method="POST" action="/siswaguru/siswa/delete" style="display:inline" onsubmit="event.stopPropagation();return confirm('Yakin hapus <?= esc($s['nama']) ?>?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $s['id'] ?>">
                      <button type="submit" class="btn-icon del" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--slate-400)">Belum ada data siswa</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="tab-content" id="tab-guru" style="display:none">
        <?php
          $totalGuru = count($teachers);
          $totalGL = count(array_filter($teachers, fn($t) => ($t['jenis_kelamin'] ?? '') === 'L'));
          $totalGP = count(array_filter($teachers, fn($t) => ($t['jenis_kelamin'] ?? '') === 'P'));
        ?>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:8px;flex-wrap:wrap">
          <form method="POST" action="/siswaguru/guru/delete-batch" id="form-guru-batch-delete" onsubmit="return confirm('Yakin hapus ' + document.querySelectorAll('.cb-guru:checked').length + ' guru?')"><?= csrf_field() ?>
            <button type="button" class="btn ku-btn ku-btn-ghost" id="btn-guru-delete-batch" disabled style="color:var(--red-500);border-color:var(--red-200);opacity:0.4">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
              Hapus
            </button>
          </form>
          <button class="btn ku-btn ku-btn-primary modal-trigger" data-modal="modal-guru" data-mode="add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M5 12h14M12 5v14"/></svg>
            Tambah Guru
          </button>
        </div>
        <div style="display:flex;gap:16px;padding:10px 14px;margin-bottom:8px;background:var(--slate-50);border-radius:8px;font-size:13px;flex-wrap:wrap">
          <span><strong><?= $totalGuru ?></strong> Total Guru</span>
          <span style="color:var(--blue-600)"><strong><?= $totalGL ?></strong> Laki-laki</span>
          <span style="color:var(--pink-600)"><strong><?= $totalGP ?></strong> Perempuan</span>
        </div>
        <div class="ku-table-wrap">
          <table class="ku-table">
            <thead>
              <tr>
                <th style="width:32px"><input type="checkbox" id="select-all-guru" style="width:16px;height:16px;border-radius:4px"></th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Sekolah</th>
                <th>Jenis Kelamin</th>
                <th>Bidang</th>
                <th>Status</th>
                <th style="width:60px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($teachers)): ?>
              <?php foreach ($teachers as $t): ?>
              <tr>
                <td><input type="checkbox" class="cb-guru" value="<?= $t['id'] ?>" style="width:16px;height:16px;border-radius:4px"></td>
                <td class="num"><?= esc($t['nip']) ?></td>
                <td><div class="cell-person"><div class="avatar-sm" style="background:<?= avCol($t['nama'] ?? '?', $avatarColors) ?>;color:#fff;font-weight:700"><?= strtoupper(substr($t['nama'] ?? '?', 0, 2)) ?></div><div class="p-name"><?= esc($t['nama']) ?></div></div></td>
                <td><?= $unitLabels[$t['sekolah']] ?? strtoupper($t['sekolah']) ?></td>
                <td><?= $t['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                <td><?= esc($t['bidang'] ?? '-') ?></td>
                <td>
                  <?php if ($t['aktif'] ?? 1): ?>
                  <span class="badge success"><span class="bdot"></span>Aktif</span>
                  <?php else: ?>
                  <span class="badge" style="background:#FEF3C7;color:#B45309"><span class="bdot" style="background:#B45309"></span>Tidak Aktif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:2px">
                    <button class="btn-icon modal-trigger" data-modal="modal-guru" data-mode="edit"
                      data-guru='<?= htmlspecialchars(json_encode([
                        'id' => $t['id'], 'nip' => $t['nip'], 'nama' => $t['nama'],
                        'bidang' => $t['bidang'] ?? '', 'jenis_kelamin' => $t['jenis_kelamin'],
                        'sekolah' => $t['sekolah'], 'aktif' => $t['aktif'] ?? 1,
                      ]), ENT_QUOTES, 'UTF-8') ?>'
                      title="Edit" onclick="event.stopPropagation()">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    <form method="POST" action="/siswaguru/guru/delete" style="display:inline" onsubmit="event.stopPropagation();return confirm('Yakin hapus <?= esc($t['nama']) ?>?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <button type="submit" class="btn-icon del" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--slate-400)">Belum ada data guru</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab Nasabah -->
      <div class="tab-content" id="tab-nasabah" style="display:none">
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:8px;flex-wrap:wrap">
          <button class="btn ku-btn ku-btn-primary modal-trigger" data-modal="modal-nasabah" data-mode="add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M5 12h14M12 5v14"/></svg>
            Tambah Non Civitas
          </button>
        </div>
        <div style="display:flex;gap:16px;padding:10px 14px;margin-bottom:8px;background:var(--slate-50);border-radius:8px;font-size:13px;flex-wrap:wrap">
          <span><strong><?= count($nasabahList) ?></strong> Total Non Civitas</span>
        </div>
        <div class="ku-table-wrap">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Alamat</th>
                <th>No. Telp</th>
                <th>Unit</th>
                <th style="width:60px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($nasabahList)): ?>
              <?php foreach ($nasabahList as $n): ?>
              <tr>
                <td><strong><?= esc($n['nama']) ?></strong></td>
                <td><?= esc($n['alamat'] ?? '-') ?></td>
                <td><?= esc($n['no_telp'] ?? '-') ?></td>
                <td><span class="ku-badge ku-badge-slate"><?= $unitLabels[$n['sekolah'] ?? ''] ?? strtoupper($n['sekolah'] ?? '-') ?></span></td>
                <td>
                  <div style="display:flex;gap:2px">
                    <button class="btn-icon modal-trigger" data-modal="modal-nasabah" data-mode="edit"
                      data-nasabah='<?= htmlspecialchars(json_encode([
                        'id' => $n['id'], 'nama' => $n['nama'],
                        'alamat' => $n['alamat'] ?? '', 'no_telp' => $n['no_telp'] ?? '',
                        'sekolah' => $n['sekolah'] ?? '',
                      ]), ENT_QUOTES, 'UTF-8') ?>'
                      title="Edit" onclick="event.stopPropagation()">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    <form method="POST" action="/siswaguru/nasabah/delete" style="display:inline" onsubmit="event.stopPropagation();return confirm('Yakin hapus <?= esc($n['nama']) ?>?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= $n['id'] ?>">
                      <button type="submit" class="btn-icon del" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--slate-400)">Belum ada data non civitas</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Siswa (Add / Edit) -->
<div class="ku-modal-overlay" id="modal-siswa">
  <div class="ku-modal-box" style="max-width:480px">
    <form method="POST" action="/siswaguru/siswa/store" id="form-siswa"><?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3 id="modal-siswa-title">Tambah Siswa</h3>
          <p id="modal-siswa-sub">Masukkan data siswa baru</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="siswa-id">
        <div class="form-row">
          <div class="ku-field">
            <label>NIS <span class="req">*</span></label>
            <input type="text" name="nis" id="siswa-nis" placeholder="Nomor Induk Siswa" required>
          </div>
          <div class="ku-field">
            <label>Nama Lengkap <span class="req">*</span></label>
            <input type="text" name="nama" id="siswa-nama" placeholder="Nama lengkap siswa" required>
          </div>
        </div>
        <div class="form-row">
          <div class="ku-field">
            <label>Jenis Kelamin <span class="req">*</span></label>
            <select name="jenis_kelamin" id="siswa-jk" required>
              <option value="">— Pilih —</option>
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>
          <div class="ku-field">
            <label>Status</label>
            <select name="status" id="siswa-status">
              <option value="aktif">Aktif</option>
              <option value="lulus">Lulus</option>
              <option value="pindah">Pindah</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="ku-field">
            <label>Kelas <span class="req">*</span></label>
            <select name="nama_kelas" id="siswa-kelas" required>
              <option value="">— Pilih Kelas —</option>
              <?php foreach ($classes as $c): ?>
              <option value="<?= esc($c['nama_kelas']) ?>"><?= esc($c['tingkat'] . ' ' . $c['jurusan'] . ' - ' . $c['nama_kelas']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ku-field">
            <label>Unit <span class="req">*</span></label>
            <select name="sekolah" id="siswa-sekolah" required>
              <option value="">— Pilih —</option>
              <option value="ra">RA</option>
              <option value="sd">SD IT</option>
              <option value="smp">SMP IT</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="ku-field">
            <label>SPP Per Bulan (Rp)</label>
            <input type="text" name="nominal_spp" id="siswa-spp" inputmode="numeric" placeholder="0">
          </div>
          <div class="ku-field">
            <label>Tagihan Awal Tahun (Rp)</label>
            <input type="text" name="nominal_awal_tahun" id="siswa-awal" inputmode="numeric" placeholder="0">
          </div>
        </div>
        <div class="form-row">
          <div class="ku-field">
            <label>Tanggal Masuk</label>
            <input type="date" name="tanggal_masuk" id="siswa-tanggal-masuk" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="ku-field" style="padding:10px 14px;background:var(--navy-50);border-radius:8px;text-align:center;font-size:13px;color:var(--navy-600);margin-top:22px">
            Tahun Ajaran: <strong><?= esc($activeTa['tahun_ajaran'] ?? '2025/2026') ?></strong>
          </div>
        </div>
        <div class="form-row" id="row-tanggal-keluar" style="display:none">
          <div class="ku-field">
            <label>Tanggal Keluar</label>
            <input type="date" name="tanggal_keluar" id="siswa-tanggal-keluar">
          </div>
          <div class="ku-field">
            <label>&nbsp;</label>
            <div style="padding:10px 14px;background:#FEF3C7;border-radius:8px;font-size:12px;color:#B45309">
              Tagihan SPP setelah tanggal ini akan dihapus
            </div>
          </div>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="btn ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="btn ku-btn ku-btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M20 6 9 17l-5-5"/></svg>
          Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Batch Pindah -->
<div class="ku-modal-overlay" id="modal-batch-pindah">
  <div class="ku-modal-box" style="max-width:420px">
    <div class="ku-modal-head">
      <div>
        <h3>Tandai Pindah</h3>
        <p id="modal-batch-pindah-count"></p>
      </div>
      <button type="button" class="ku-modal-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="ku-modal-body">
      <div class="ku-field">
        <label>Tanggal Keluar</label>
        <input type="date" id="batch-pindah-tanggal" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="ku-field">
        <label>Keterangan</label>
        <input type="text" id="batch-pindah-keterangan" placeholder="Alasan pindah" style="width:100%">
      </div>
      <div style="padding:10px 14px;background:#FEF3C7;border-radius:8px;font-size:12px;color:#B45309">
        Tagihan SPP setelah tanggal keluar akan dihapus. Pembayaran yang sudah lunas tetap aman.
      </div>
    </div>
    <div class="ku-modal-foot">
      <button type="button" class="btn ku-btn ku-btn-ghost ku-modal-close">Batal</button>
      <button type="button" class="btn ku-btn ku-btn-primary" id="btn-batch-pindah-konfirm">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        Konfirmasi Pindah
      </button>
    </div>
  </div>
</div>

<!-- Modal Pindah Kelas -->
<div class="ku-modal-overlay" id="modal-pindah-kelas">
  <div class="ku-modal-box" style="max-width:420px">
    <form method="POST" action="/siswaguru/siswa/pindah-kelas"><?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3>Pindah Kelas</h3>
          <p id="pindah-sub">Pilih kelas tujuan</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="pindah-id">
        <div style="font-size:14px;font-weight:600;color:var(--slate-900);margin-bottom:12px" id="pindah-nama"></div>
        <div class="ku-field">
          <label>Kelas Tujuan</label>
          <select name="kelas_id" id="pindah-kelas" required>
            <option value="">— Pilih Kelas —</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= esc($c['tingkat'] . ' ' . $c['jurusan'] . ' - ' . $c['nama_kelas']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="btn ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="btn ku-btn ku-btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Guru (Add / Edit) -->
<div class="ku-modal-overlay" id="modal-guru">
  <div class="ku-modal-box" style="max-width:480px">
    <form method="POST" action="/siswaguru/guru/store" id="form-guru"><?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3 id="modal-guru-title">Tambah Guru</h3>
          <p id="modal-guru-sub">Masukkan data guru baru</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="guru-id">
        <div class="form-row">
          <div class="ku-field">
            <label>NIP <span class="req">*</span></label>
            <input type="text" name="nip" id="guru-nip" placeholder="Nomor Induk Pegawai" required>
          </div>
          <div class="ku-field">
            <label>Nama Lengkap <span class="req">*</span></label>
            <input type="text" name="nama" id="guru-nama" placeholder="Nama lengkap guru" required>
          </div>
        </div>
        <div class="form-row">
          <div class="ku-field">
            <label>Jenis Kelamin <span class="req">*</span></label>
            <select name="jenis_kelamin" id="guru-jk" required>
              <option value="">— Pilih —</option>
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>
          <div class="ku-field">
            <label>Status</label>
            <select name="aktif" id="guru-status">
              <option value="1">Aktif</option>
              <option value="0">Tidak Aktif</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="ku-field">
            <label>Bidang</label>
            <input type="text" name="bidang" id="guru-bidang" placeholder="(opsional)">
          </div>
          <div class="ku-field">
            <label>Sekolah <span class="req">*</span></label>
            <select name="sekolah" id="guru-sekolah" required>
              <option value="">— Pilih —</option>
              <option value="ra">RA</option>
              <option value="sd">SD IT</option>
              <option value="smp">SMP IT</option>
            </select>
          </div>
        </div>
        <div class="ku-field" style="padding:10px 14px;background:var(--navy-50);border-radius:8px;text-align:center;font-size:13px;color:var(--navy-600)">
          Tahun Ajaran: <strong><?= esc($activeTa['tahun_ajaran'] ?? '2025/2026') ?></strong>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="btn ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="btn ku-btn ku-btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M20 6 9 17l-5-5"/></svg>
          Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Nasabah (Add / Edit) -->
<div class="ku-modal-overlay" id="modal-nasabah">
  <div class="ku-modal-box" style="max-width:480px">
    <form method="POST" action="/siswaguru/nasabah/store" id="form-nasabah"><?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3 id="modal-nasabah-title">Tambah Non Civitas</h3>
          <p id="modal-nasabah-sub">Masukkan data non civitas baru</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="nasabah-id">
        <div class="ku-field">
          <label>Nama Lengkap <span class="req">*</span></label>
          <input type="text" name="nama" id="nasabah-nama" placeholder="Nama lengkap" required>
        </div>
        <div class="ku-field" id="nasabah-field-unit" <?= $sekolahUser !== 'admin' ? 'style="display:none"' : '' ?>>
          <label>Unit <span class="req">*</span></label>
          <select name="sekolah" id="nasabah-sekolah" <?= $sekolahUser === 'admin' ? 'required' : '' ?>>
            <option value="">— Pilih Unit —</option>
            <option value="ra">RA</option>
            <option value="sd">SD</option>
            <option value="smp">SMP</option>
          </select>
        </div>
        <div class="ku-field">
          <label>Alamat</label>
          <textarea name="alamat" id="nasabah-alamat" placeholder="Alamat lengkap (opsional)"></textarea>
        </div>
        <div class="ku-field">
          <label>No. Telepon</label>
          <input type="text" name="no_telp" id="nasabah-telp" placeholder="08xxxxxxxxxx (opsional)">
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="btn ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="btn ku-btn ku-btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M20 6 9 17l-5-5"/></svg>
          Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Import Excel -->
<div class="ku-modal-overlay" id="modal-import-excel">
  <div class="ku-modal-box" style="max-width:520px">
    <form method="POST" action="/siswaguru/siswa/import-excel" enctype="multipart/form-data"><?= csrf_field() ?>
      <div class="ku-modal-head">
        <div>
          <h3>Import Data Siswa dari Excel</h3>
          <p>Upload file .xlsx atau .xls</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <div style="margin-bottom:16px;padding:12px 14px;background:#FEF3C7;border-radius:8px;font-size:12px;color:#92400E;line-height:1.5">
          <strong>Format kolom Siswa (baris pertama = header):</strong><br>
          NIS, Nama, Jenis Kelamin (L/P/Laki-laki/Perempuan), Status (Aktif/Lulus/Pindah), Kelas, Unit (RA/SD/SMP), SPP Per Bulan, Tagihan Awal Tahun<br>
          <span style="font-size:11px;color:#B45309">NIS yang sudah ada akan diperbarui datanya. NIS baru akan ditambahkan.</span><br>
          <strong>Format kolom Guru (baris pertama = header):</strong><br>
          NIP, Nama, Jenis Kelamin (L/P/Laki-laki/Perempuan), Bidang, Sekolah, Status (Aktif/Tidak Aktif)<br>
          <span style="font-size:11px;color:#B45309">Gunakan file hasil Backup sebagai contoh format.</span>
        </div>
        <div class="ku-field">
          <label>Pilih File Excel</label>
          <input type="file" name="file_excel" accept=".xlsx,.xls" required style="width:100%;padding:10px;border:1.5px dashed var(--slate-300);border-radius:8px;background:var(--slate-50);font-size:13px">
        </div>
        <div style="text-align:center;margin-top:8px">
          <a href="/siswaguru/export-csv" style="font-size:12px;color:var(--navy-600);text-decoration:underline">Download contoh format (Backup Excel)</a>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="btn ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="btn ku-btn ku-btn-primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
          Import
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Activate tab from GET param or default to siswa
  var activeTab = new URLSearchParams(window.location.search).get('tab') || 'siswa';
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
  var si = document.querySelector('#filter-form .ku-search-box input');
  var placeholders = {'siswa':'Cari nama atau NIS...','guru':'Cari nama atau NIP...'};
  if (si) si.placeholder = placeholders[activeTab] || 'Cari...';
  var backupBtn = document.getElementById('btn-backup');
  if (backupBtn) {
    backupBtn.style.display = activeTab === 'nasabah' ? 'none' : '';
    if (activeTab !== 'nasabah') backupBtn.href = '/siswaguru/export-csv?type=' + activeTab;
  }

  // Tab switching
  document.querySelectorAll('.tabs-switch button').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.tabs-switch button').forEach(function(b) { b.classList.remove('active'); });
      btn.classList.add('active');
      document.querySelectorAll('.tab-content').forEach(function(t) { t.style.display = 'none'; });
      var tab = document.getElementById('tab-' + btn.getAttribute('data-tab'));
      if (tab) tab.style.display = '';
      var tabName = btn.getAttribute('data-tab');
      document.querySelectorAll('.tab-filter').forEach(function(f) {
        f.style.display = f.getAttribute('data-tab') === tabName ? '' : 'none';
      });
      document.getElementById('tab-input').value = tabName;
      var searchInput = document.querySelector('#filter-form .ku-search-box input');
      var placeholders = {'siswa':'Cari nama atau NIS...','guru':'Cari nama atau NIP...'};
      if (searchInput) {
        searchInput.placeholder = placeholders[tabName] || 'Cari...';
      }
      var backupBtn = document.getElementById('btn-backup');
      if (backupBtn) {
        backupBtn.style.display = tabName === 'nasabah' ? 'none' : '';
        if (tabName !== 'nasabah') backupBtn.href = '/siswaguru/export-csv?type=' + tabName;
      }
    });
  });

  function populateSiswaModal(btn) {
    var mode = btn.getAttribute('data-mode');
    var form = document.getElementById('form-siswa');
    var title = document.getElementById('modal-siswa-title');
    var sub = document.getElementById('modal-siswa-sub');

    if (mode === 'add') {
      form.action = '/siswaguru/siswa/store';
      title.textContent = 'Tambah Siswa';
      sub.textContent = 'Masukkan data siswa baru';
      form.reset();
      document.getElementById('siswa-id').value = '';
      document.getElementById('siswa-nis')._userEdited = false;
      window._siswaAddMode = true;
      document.getElementById('siswa-tanggal-masuk').value = new Date().toISOString().split('T')[0];
      document.getElementById('row-tanggal-keluar').style.display = 'none';
      var unit = document.getElementById('siswa-sekolah').value;
      if (unit) fetchNextNis(unit);
    } else {
      window._siswaAddMode = false;
      var d;
      try { d = JSON.parse(btn.getAttribute('data-siswa')); } catch(e) { return; }
      if (!d) return;
      form.action = '/siswaguru/siswa/update';
      title.textContent = 'Edit Siswa';
      sub.textContent = 'Ubah data siswa';
      document.getElementById('siswa-id').value = d.id || '';
      document.getElementById('siswa-nis').value = d.nis || '';
      document.getElementById('siswa-nama').value = d.nama || '';
      document.getElementById('siswa-jk').value = d.jenis_kelamin || '';
      document.getElementById('siswa-status').value = d.status || 'aktif';
      document.getElementById('siswa-kelas').value = d.nama_kelas || '';
      document.getElementById('siswa-sekolah').value = d.sekolah || '';
      document.getElementById('siswa-spp').value = d.nominal_spp ? 'Rp ' + Number(d.nominal_spp).toLocaleString('id-ID') : '';
      document.getElementById('siswa-awal').value = d.nominal_awal_tahun ? 'Rp ' + Number(d.nominal_awal_tahun).toLocaleString('id-ID') : '';
      document.getElementById('siswa-tanggal-masuk').value = d.tanggal_masuk || '';
      var isKeluar = d.status === 'pindah' || d.status === 'lulus';
      document.getElementById('row-tanggal-keluar').style.display = isKeluar ? 'flex' : 'none';
      document.getElementById('siswa-tanggal-keluar').value = d.tanggal_keluar || new Date().toISOString().split('T')[0];
    }
  }

  function populateGuruModal(btn) {
    var mode = btn.getAttribute('data-mode');
    var form = document.getElementById('form-guru');
    var title = document.getElementById('modal-guru-title');
    var sub = document.getElementById('modal-guru-sub');

    if (mode === 'add') {
      form.action = '/siswaguru/guru/store';
      title.textContent = 'Tambah Guru';
      sub.textContent = 'Masukkan data guru baru';
      form.reset();
      document.getElementById('guru-id').value = '';
    } else {
      var d;
      try { d = JSON.parse(btn.getAttribute('data-guru')); } catch(e) { return; }
      if (!d) return;
      form.action = '/siswaguru/guru/update';
      title.textContent = 'Edit Guru';
      sub.textContent = 'Ubah data guru';
      document.getElementById('guru-id').value = d.id || '';
      document.getElementById('guru-nip').value = d.nip || '';
      document.getElementById('guru-nama').value = d.nama || '';
      document.getElementById('guru-jk').value = d.jenis_kelamin || '';
      document.getElementById('guru-status').value = d.aktif !== undefined ? d.aktif : '1';
      document.getElementById('guru-bidang').value = d.bidang || '';
      document.getElementById('guru-sekolah').value = d.sekolah || '';
    }
  }

  function fetchNextNis(unit) {
    fetch('/siswaguru/next-nis?unit=' + unit)
      .then(function(r) { return r.json(); })
      .then(function(d) {
        var el = document.getElementById('siswa-nis');
        if (d.nis && el && !el._userEdited) el.value = d.nis;
      })
      .catch(function() {});
  }

  document.getElementById('siswa-status').addEventListener('change', function() {
    var isKeluar = this.value === 'pindah' || this.value === 'lulus';
    document.getElementById('row-tanggal-keluar').style.display = isKeluar ? 'flex' : 'none';
    if (isKeluar && !document.getElementById('siswa-tanggal-keluar').value) {
      document.getElementById('siswa-tanggal-keluar').value = new Date().toISOString().split('T')[0];
    }
  });

  document.getElementById('siswa-sekolah').addEventListener('change', function() {
    if (window._siswaAddMode && this.value) fetchNextNis(this.value);
  });
  document.getElementById('siswa-nis').addEventListener('input', function() {
    this._userEdited = true;
  });

  document.querySelectorAll('.modal-trigger[data-modal="modal-siswa"]').forEach(function(btn) {
    btn.addEventListener('click', function() { populateSiswaModal(btn); });
  });
  document.querySelectorAll('.modal-trigger[data-modal="modal-guru"]').forEach(function(btn) {
    btn.addEventListener('click', function() { populateGuruModal(btn); });
  });

  // Pindah Kelas modal
  document.querySelectorAll('.modal-trigger[data-modal="modal-pindah-kelas"]').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.getElementById('pindah-id').value = this.dataset.id;
      document.getElementById('pindah-nama').textContent = this.dataset.nama;
      document.getElementById('pindah-kelas').value = this.dataset.kelas;
    });
  });

  // Siswa batch select
  document.getElementById('select-all-siswa')?.addEventListener('change', function() {
    document.querySelectorAll('.cb-siswa').forEach(function(cb) { cb.checked = this.checked; }, this);
    updateSiswaDeleteBtn();
  });
  document.querySelectorAll('.cb-siswa').forEach(function(cb) {
    cb.addEventListener('change', updateSiswaDeleteBtn);
  });
  function updateSiswaDeleteBtn() {
    var checked = document.querySelectorAll('.cb-siswa:checked').length;
    var enable = checked > 0;
    ['btn-siswa-batch-pindah','btn-siswa-batch-hapus','btn-siswa-naik-kelas','btn-siswa-turun-kelas'].forEach(function(id){
      var el = document.getElementById(id);
      if (!el) return;
      el.disabled = !enable;
      el.style.opacity = enable ? '1' : '0.4';
      el.style.cursor = enable ? 'pointer' : 'default';
    });
  }
  document.getElementById('btn-siswa-naik-kelas')?.addEventListener('click', function() {
    var checked = document.querySelectorAll('.cb-siswa:checked');
    if (checked.length === 0) { alert('Pilih siswa dulu.'); return; }
    if (!confirm('Naikkan ' + checked.length + ' siswa ke kelas berikutnya?')) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/siswaguru/siswa/naik-kelas';
    var ci = document.createElement('input');
    ci.type = 'hidden'; ci.name = '<?= csrf_token() ?>'; ci.value = '<?= csrf_hash() ?>';
    form.appendChild(ci);
    checked.forEach(function(cb) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'siswa_ids[]';
      inp.value = cb.value;
      form.appendChild(inp);
    });
    document.body.appendChild(form);
    form.submit();
  });

  document.getElementById('btn-siswa-turun-kelas')?.addEventListener('click', function() {
    var checked = document.querySelectorAll('.cb-siswa:checked');
    if (checked.length === 0) { alert('Pilih siswa dulu.'); return; }
    if (!confirm('Turunkan ' + checked.length + ' siswa ke kelas sebelumnya?')) return;
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/siswaguru/siswa/turun-kelas';
    var ci = document.createElement('input');
    ci.type = 'hidden'; ci.name = '<?= csrf_token() ?>'; ci.value = '<?= csrf_hash() ?>';
    form.appendChild(ci);
    checked.forEach(function(cb) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'siswa_ids[]';
      inp.value = cb.value;
      form.appendChild(inp);
    });
    document.body.appendChild(form);
    form.submit();
  });

  document.getElementById('btn-siswa-batch-pindah')?.addEventListener('click', function() {
    var checked = document.querySelectorAll('.cb-siswa:checked');
    if (checked.length === 0) { alert('Pilih siswa dulu.'); return; }
    document.getElementById('modal-batch-pindah-count').textContent = checked.length + ' siswa';
    document.getElementById('modal-batch-pindah').classList.add('active');
  });

  document.getElementById('btn-batch-pindah-konfirm')?.addEventListener('click', function() {
    var checked = document.querySelectorAll('.cb-siswa:checked');
    var k = document.getElementById('batch-pindah-keterangan').value;
    var tgl = document.getElementById('batch-pindah-tanggal').value;
    var form = document.getElementById('form-siswa-batch-pindah');
    document.querySelectorAll('#form-siswa-batch-pindah input[name="ids[]"], #form-siswa-batch-pindah input[name="keterangan"], #form-siswa-batch-pindah input[name="tanggal_keluar"]').forEach(function(e) { e.remove(); });
    var ki = document.createElement('input');
    ki.type = 'hidden'; ki.name = 'keterangan'; ki.value = k;
    form.appendChild(ki);
    var ti = document.createElement('input');
    ti.type = 'hidden'; ti.name = 'tanggal_keluar'; ti.value = tgl;
    form.appendChild(ti);
    checked.forEach(function(cb) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'ids[]';
      inp.value = cb.value;
      form.appendChild(inp);
    });
    document.getElementById('modal-batch-pindah').classList.remove('active');
    form.submit();
  });

  document.getElementById('btn-siswa-batch-hapus')?.addEventListener('click', function() {
    var checked = document.querySelectorAll('.cb-siswa:checked');
    if (checked.length === 0) { alert('Pilih siswa dulu.'); return; }
    if (!confirm('Yakin hapus ' + checked.length + ' siswa? Data terkait (tabungan, tagihan) juga akan dihapus.')) return;
    var form = document.getElementById('form-siswa-batch-hapus');
    document.querySelectorAll('#form-siswa-batch-hapus input[name="ids[]"]').forEach(function(e) { e.remove(); });
    checked.forEach(function(cb) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'ids[]';
      inp.value = cb.value;
      form.appendChild(inp);
    });
    form.submit();
  });

  // Guru batch select
  document.getElementById('select-all-guru')?.addEventListener('change', function() {
    document.querySelectorAll('.cb-guru').forEach(function(cb) { cb.checked = this.checked; }, this);
    updateGuruDeleteBtn();
  });
  document.querySelectorAll('.cb-guru').forEach(function(cb) {
    cb.addEventListener('change', updateGuruDeleteBtn);
  });
  function updateGuruDeleteBtn() {
    var checked = document.querySelectorAll('.cb-guru:checked').length;
    var btn = document.getElementById('btn-guru-delete-batch');
    btn.disabled = checked === 0;
    btn.style.opacity = checked === 0 ? '0.4' : '1';
    btn.style.cursor = checked === 0 ? 'default' : 'pointer';
  }
  document.getElementById('btn-guru-delete-batch')?.addEventListener('click', function() {
    var checked = document.querySelectorAll('.cb-guru:checked');
    if (checked.length === 0) { alert('Pilih guru dulu.'); return; }
    if (!confirm('Yakin hapus ' + checked.length + ' guru?')) return;
    var form = document.getElementById('form-guru-batch-delete');
    document.querySelectorAll('#form-guru-batch-delete input[name="ids[]"]').forEach(function(e) { e.remove(); });
    checked.forEach(function(cb) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'ids[]';
      inp.value = cb.value;
      form.appendChild(inp);
    });
    form.submit();
  });

  // Format nominal fields with thousand separators and Rp prefix
  function formatNominalInput(el) {
    var val = el.value.replace(/[^0-9]/g, '');
    if (val) {
      el.value = 'Rp ' + Number(val).toLocaleString('id-ID');
    } else {
      el.value = '';
    }
  }

  document.getElementById('siswa-spp')?.addEventListener('input', function() { formatNominalInput(this); });
  document.getElementById('siswa-awal')?.addEventListener('input', function() { formatNominalInput(this); });

  function populateNasabahModal(btn) {
    var mode = btn.getAttribute('data-mode');
    var form = document.getElementById('form-nasabah');
    var title = document.getElementById('modal-nasabah-title');
    var sub = document.getElementById('modal-nasabah-sub');

    if (mode === 'add') {
      form.action = '/siswaguru/nasabah/store';
      title.textContent = 'Tambah Non Civitas';
      sub.textContent = 'Masukkan data non civitas baru';
      document.getElementById('nasabah-id').value = '';
      document.getElementById('nasabah-nama').value = '';
      document.getElementById('nasabah-alamat').value = '';
      document.getElementById('nasabah-telp').value = '';
      if (document.getElementById('nasabah-sekolah')) document.getElementById('nasabah-sekolah').value = '';
    } else {
      var d;
      try { d = JSON.parse(btn.getAttribute('data-nasabah')); } catch(e) { return; }
      if (!d) return;
      form.action = '/siswaguru/nasabah/update';
      title.textContent = 'Edit Non Civitas';
      sub.textContent = 'Ubah data non civitas';
      document.getElementById('nasabah-id').value = d.id || '';
      document.getElementById('nasabah-nama').value = d.nama || '';
      document.getElementById('nasabah-alamat').value = d.alamat || '';
      document.getElementById('nasabah-telp').value = d.no_telp || '';
      if (document.getElementById('nasabah-sekolah')) document.getElementById('nasabah-sekolah').value = d.sekolah || '';
    }
  }

  document.querySelectorAll('.modal-trigger[data-modal="modal-nasabah"]').forEach(function(btn) {
    btn.addEventListener('click', function() { populateNasabahModal(btn); });
  });


});
</script>
<?= view('layout/footer') ?>
