<?php
  $pageTitle = 'Manajemen Guru';
  $pageDesc = 'Data master guru dan saldo THT';
  $guruList = $guruList ?? [];
  $unitList = $unitList ?? [];
  $search = $search ?? '';
  $avatarColors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#06B6D4'];
  function avCol($n, $c) { return $c[crc32($n) % count($c)]; }
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

      <div class="ku-toolbar">
        <div class="ku-toolbar-left">
          <form method="GET" action="/guru" style="display:flex;gap:8px;margin:0">
            <div class="ku-search-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              <input type="text" name="search" placeholder="Cari nama atau NIP..." value="<?= esc($search) ?>">
            </div>
          </form>
        </div>
        <div class="ku-toolbar-right">
          <button class="ku-btn ku-btn-primary modal-trigger" data-modal="modal-guru" data-mode="add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5v14"/></svg>
            Tambah Guru
          </button>
        </div>
      </div>

      <div class="ku-table-wrap">
        <table class="ku-table">
          <thead>
            <tr>
              <th>NIP</th>
              <th>Nama</th>
              <th>Unit</th>
              <th style="text-align:right">Saldo THT</th>
              <th>Status</th>
              <th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($guruList)): ?>
            <?php foreach ($guruList as $g):
              $isActive = ($g['aktif'] ?? 1) == 1;
            ?>
            <tr style="<?= !$isActive ? 'opacity:0.6' : '' ?>">
              <td class="num"><?= esc($g['nip'] ?? '-') ?></td>
              <td>
                <div class="cell-person">
                  <div class="avatar-sm" style="background:<?= avCol($g['nama'] ?? '?', $avatarColors) ?>;color:#fff;font-weight:700"><?= strtoupper(substr($g['nama'] ?? '?', 0, 2)) ?></div>
                  <div>
                    <div class="p-name"><?= esc($g['nama']) ?></div>
                  </div>
                </div>
              </td>
              <td><?= esc($g['unit_nama'] ?? '-') ?></td>
              <td class="num" style="font-weight:600">Rp <?= number_format($g['saldo_tht'] ?? 0, 0, ',', '.') ?></td>
              <td>
                <?php if ($isActive): ?>
                <span class="ku-badge ku-badge-green">Aktif</span>
                <?php else: ?>
                <span class="ku-badge ku-badge-amber">Tidak Aktif</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="ku-actions" style="justify-content:center">
                  <button class="ku-action-btn modal-trigger" data-modal="modal-guru" data-mode="edit"
                    data-guru='<?= htmlspecialchars(json_encode([
                      'id' => $g['id'], 'nip' => $g['nip'] ?? '', 'nama' => $g['nama'] ?? '',
                      'unit_id' => $g['unit_id'] ?? '', 'aktif' => $g['aktif'] ?? 1,
                    ]), ENT_QUOTES, 'UTF-8') ?>'
                    title="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                  </button>
                  <form method="POST" action="/guru/hapus/<?= $g['id'] ?>" style="display:inline" onsubmit="return confirm('Yakin hapus <?= esc($g['nama'] ?? '') ?>? Semua data terkait akan ikut terhapus.')">
                    <button type="submit" class="ku-action-btn ku-action-del" title="Hapus">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--ku-slate-400)">Belum ada data guru</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- Modal Guru (Add / Edit) -->
<div class="ku-modal-overlay" id="modal-guru">
  <div class="ku-modal-box" style="max-width:480px">
    <form method="POST" action="/guru/tambah" id="form-guru">
      <div class="ku-modal-head">
        <div>
          <h3 id="modal-guru-title">Tambah Guru</h3>
          <p id="modal-guru-sub">Masukkan data guru baru</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="guru-id">
        <div class="ku-field-row">
          <div class="ku-field">
            <label>NIP</label>
            <input type="text" name="nip" id="guru-nip" placeholder="Nomor Induk Pegawai (opsional)">
          </div>
          <div class="ku-field">
            <label>Nama Lengkap <span style="color:var(--ku-red)">*</span></label>
            <input type="text" name="nama" id="guru-nama" placeholder="Nama lengkap guru" required>
          </div>
        </div>
        <div class="ku-field-row">
          <div class="ku-field">
            <label>Unit <span style="color:var(--ku-red)">*</span></label>
            <select name="unit_id" id="guru-unit" required>
              <option value="">— Pilih Unit —</option>
              <?php foreach ($unitList as $u): ?>
              <option value="<?= $u['id'] ?>"><?= esc($u['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ku-field">
            <label>Saldo Awal THT (Rp)</label>
            <input type="text" name="saldo_awal" id="guru-saldo" inputmode="numeric" placeholder="0">
          </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  function populateGuruModal(btn) {
    var mode = btn.getAttribute('data-mode');
    var form = document.getElementById('form-guru');
    var title = document.getElementById('modal-guru-title');
    var sub = document.getElementById('modal-guru-sub');

    if (mode === 'add') {
      form.action = '/guru/tambah';
      title.textContent = 'Tambah Guru';
      sub.textContent = 'Masukkan data guru baru';
      form.reset();
      document.getElementById('guru-id').value = '';
    } else {
      var d;
      try { d = JSON.parse(btn.getAttribute('data-guru')); } catch(e) { return; }
      if (!d) return;
      form.action = '/guru/edit/' + d.id;
      title.textContent = 'Edit Guru';
      sub.textContent = 'Ubah data guru';
      document.getElementById('guru-id').value = d.id || '';
      document.getElementById('guru-nip').value = d.nip || '';
      document.getElementById('guru-nama').value = d.nama || '';
      document.getElementById('guru-unit').value = d.unit_id || '';
      document.getElementById('guru-saldo').value = '';
    }
  }

  document.querySelectorAll('.modal-trigger[data-modal="modal-guru"]').forEach(function(btn) {
    btn.addEventListener('click', function() { populateGuruModal(btn); });
  });

  function formatNominalInput(el) {
    var val = el.value.replace(/[^0-9]/g, '');
    if (val) {
      el.value = 'Rp ' + Number(val).toLocaleString('id-ID');
    } else {
      el.value = '';
    }
  }
  document.getElementById('guru-saldo')?.addEventListener('input', function() { formatNominalInput(this); });
});
</script>
<?= view('layout/footer') ?>
