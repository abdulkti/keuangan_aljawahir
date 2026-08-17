<?php
  $pageTitle = 'Manajemen Kelas';
  $pageDesc = 'Kelola siswa per kelas';
  $classes = $classes ?? [];
  $counts = $counts ?? [];
  $sekolahUser = $sekolahUser ?? 'admin';

  $schoolLabels = ['ra' => 'RA', 'sd' => 'SD IT', 'smp' => 'SMP IT', 'sma' => 'SMA IT'];
  $schoolColors = ['ra' => '#8B5CF6', 'sd' => '#10B981', 'smp' => '#3B82F6', 'sma' => '#F59E0B'];
  $grouped = [];
  foreach ($classes as $c) {
    $grouped[$c['sekolah']][] = $c;
  }
?>
<?= view('layout/header') ?>
<style>
.school-section { margin-bottom: 28px; }
.school-header {
  display: flex; align-items: center; gap: 10px; margin-bottom: 12px;
}
.school-header .dot {
  width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.school-header h2 { font-size: 15px; font-weight: 700; color: var(--slate-800); }
.school-header span { font-size: 12px; color: var(--slate-400); font-weight: 500; }
.class-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 10px;
}
.class-card {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; background: #fff; border: 1px solid var(--slate-200);
  border-radius: 10px; transition: all .15s; text-decoration: none; position: relative;
}
.class-card:hover {
  border-color: var(--navy-300); box-shadow: 0 2px 8px rgba(15,23,42,0.06);
  transform: translateY(-1px);
}
.class-card .name {
  font-size: 13px; font-weight: 600; color: var(--slate-900);
}
.class-card .sub {
  font-size: 11px; color: var(--slate-400); margin-top: 1px;
}
.class-card .badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 28px; height: 28px; padding: 0 8px;
  background: var(--slate-100); border-radius: 8px;
  font-size: 13px; font-weight: 700; color: var(--navy-700);
}
.class-card .actions {
  position: absolute; top: 6px; right: 6px; display: none; gap: 2px;
}
.class-card:hover .actions { display: flex; }
.class-card .actions button,
.class-card .actions form button {
  width: 26px; height: 26px; border-radius: 6px;
  display: inline-flex; align-items: center; justify-content: center;
  border: none; cursor: pointer; background: var(--slate-100);
  color: var(--slate-400); font-size: 12px; transition: all .12s;
}
.class-card .actions button:hover,
.class-card .actions form button:hover { background: var(--slate-200); }
.class-card .actions .edit:hover { color: var(--navy-700); }
.class-card .actions .del:hover { color: var(--red-500); }
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
          <h1>Manajemen Kelas</h1>
          <div class="desc"><?= count($classes) ?> kelas</div>
        </div>
        <?php if ($sekolahUser === 'admin'): ?>
        <div class="ku-toolbar-right">
          <button class="ku-btn ku-btn-primary modal-trigger" data-modal="modal-kelas" data-mode="add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M5 12h14M12 5v14"/></svg>
            Tambah Kelas
          </button>
        </div>
        <?php endif; ?>
      </div>

      <?php foreach (['ra', 'sd', 'smp'] as $sk): if (empty($grouped[$sk])) continue; ?>
      <div class="school-section">
        <div class="school-header">
          <div class="dot" style="background:<?= $schoolColors[$sk] ?>"></div>
          <h2><?= $schoolLabels[$sk] ?></h2>
          <span><?= count($grouped[$sk]) ?> kelas</span>
        </div>
        <div class="class-grid">
          <?php foreach ($grouped[$sk] as $c): ?>
          <a href="/siswaguru/kelas/<?= $c['id'] ?>" class="class-card">
            <div>
              <div class="name"><?= esc($c['nama_kelas']) ?></div>
            </div>
            <div class="badge"><?= $counts[$c['id']] ?? 0 ?></div>
            <?php if ($sekolahUser === 'admin'): ?>
            <div class="actions">
              <form method="POST" action="/siswaguru/kelas/delete" style="display:inline" onsubmit="event.stopPropagation();return confirm('Yakin hapus kelas <?= esc($c['nama_kelas']) ?>?')">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="submit" class="del" title="Hapus">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                </button>
              </form>
            </div>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</div>

<div class="ku-modal-overlay" id="modal-kelas">
  <div class="ku-modal-box" style="max-width:420px">
    <form method="POST" action="/siswaguru/kelas/store" id="form-kelas">
      <div class="ku-modal-head">
        <div>
          <h3 id="modal-kelas-title">Tambah Kelas</h3>
          <p id="modal-kelas-sub">Buat kelas baru</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <input type="hidden" name="id" id="kelas-id">
        <div class="ku-field">
          <label>Nama Kelas <span class="req">*</span></label>
          <input type="text" name="nama_kelas" id="kelas-nama" required>
        </div>
        <div class="ku-field">
          <label>Unit <span class="req">*</span></label>
          <select name="sekolah" id="kelas-sekolah" required>
            <option value="">-- Pilih Unit --</option>
            <option value="ra">RA</option>
            <option value="sd">SD IT</option>
            <option value="smp">SMP IT</option>
          </select>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.modal-trigger[data-modal="modal-kelas"]').forEach(function(btn){
  btn.addEventListener('click', function(){
    var mode = btn.getAttribute('data-mode');
    var form = document.getElementById('form-kelas');
    var title = document.getElementById('modal-kelas-title');
    var sub = document.getElementById('modal-kelas-sub');
    if (mode === 'add') {
      form.action = '/siswaguru/kelas/store';
      title.textContent = 'Tambah Kelas';
      sub.textContent = 'Buat kelas baru';
      form.reset();
      document.getElementById('kelas-id').value = '';
    } else {
      var d;
      try { d = JSON.parse(btn.getAttribute('data-kelas')); } catch(e) { return; }
      if (!d) return;
      form.action = '/siswaguru/kelas/update';
      title.textContent = 'Edit Kelas';
      sub.textContent = 'Ubah data kelas';
      document.getElementById('kelas-id').value = d.id || '';
      document.getElementById('kelas-nama').value = d.nama_kelas || '';
      document.getElementById('kelas-sekolah').value = d.sekolah || '';
    }
  });
});
</script>
<?= view('layout/footer') ?>
