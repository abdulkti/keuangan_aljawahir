<?php
  $class = $class ?? [];
  $students = $students ?? [];
  $classes = $classes ?? [];
  $sekolahUser = $sekolahUser ?? 'admin';
  $pageTitle = $class['nama_kelas'] ?? 'Kelas';
  $pageDesc = ($class['tingkat'] ?? '') . ($class['jurusan'] ? ' ' . $class['jurusan'] : '') . ' — ' . count($students) . ' siswa';
  $schoolColors = ['ra' => '#8B5CF6', 'sd' => '#10B981', 'smp' => '#3B82F6', 'sma' => '#F59E0B'];
  $schoolLabels = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA'];
  $color = $schoolColors[$class['sekolah']] ?? '#3B82F6';
?>
<?= view('layout/header') ?>
<style>
.student-grid { display: flex; flex-direction: column; margin-top: 16px; }
.student-row {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 14px; border-radius: 10px;
  border: 1px solid var(--slate-200); background: #fff;
  margin-bottom: 6px; transition: all .12s; cursor: pointer;
}
.student-row:hover { border-color: var(--slate-300); box-shadow: 0 1px 4px rgba(15,23,42,0.04); }
.student-row.selected { border-color: var(--navy-400); background: #F8FAFF; }
.student-row .cb {
  width: 18px; height: 18px; border-radius: 5px; border: 2px solid var(--slate-300);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  transition: all .12s; background: #fff;
}
.student-row.selected .cb { border-color: var(--navy-600); background: var(--navy-600); }
.student-row.selected .cb svg { display: block; }
.student-row .av {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.student-row .info { flex: 1; min-width: 0; }
.student-row .info .nm { font-size: 13px; font-weight: 600; color: var(--slate-900); }
.student-row .info .nis { font-size: 11px; color: var(--slate-400); }
.stud-actions { display: flex; gap: 2px; opacity: 0; transition: opacity .12s; }
.student-row:hover .stud-actions { opacity: 1; }
.stud-actions form button, .stud-actions .modal-trigger {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  border: none; cursor: pointer; background: transparent;
  color: var(--slate-400); transition: all .12s;
}
.stud-actions .move:hover { background: var(--slate-100); color: var(--navy-600); }
.stud-actions .grad:hover { background: #EEF2FF; color: var(--blue-500); }
.stud-actions .drop:hover { background: #FEF2F2; color: var(--red-500); }
.batch-bar {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 14px; background: var(--slate-50);
  border: 1px solid var(--slate-200); border-radius: 10px;
  margin-top: 12px; font-size: 13px; color: var(--slate-500);
  opacity: 0; pointer-events: none; transition: all .15s;
}
.batch-bar.show { opacity: 1; pointer-events: auto; }
.batch-bar .count { font-weight: 600; color: var(--slate-800); min-width: 60px; }
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

      <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
        <a href="/siswaguru/kelas" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;border:1px solid var(--slate-200);color:var(--slate-400);text-decoration:none;transition:all.12s" title="Kembali">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <div style="display:flex;align-items:center;gap:10px;flex:1">
          <div style="width:4px;height:28px;border-radius:4px;background:<?= $color ?>"></div>
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:16px;font-weight:700;color:var(--slate-900)"><?= esc($class['nama_kelas']) ?></span>
              <?php if ($sekolahUser === 'admin'): ?>
              <button class="modal-trigger" data-modal="modal-kelas" data-mode="edit"
                data-kelas='<?= htmlspecialchars(json_encode([
                  'id' => $class['id'], 'nama_kelas' => $class['nama_kelas'],
                  'tingkat' => $class['tingkat'], 'jurusan' => $class['jurusan'] ?? '',
                  'sekolah' => $class['sekolah'],
                ]), ENT_QUOTES, 'UTF-8') ?>'
                style="width:28px;height:28px;border-radius:6px;border:none;cursor:pointer;background:var(--slate-100);color:var(--slate-400);display:inline-flex;align-items:center;justify-content:center;transition:all.12s;flex-shrink:0"
                title="Edit Kelas">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--slate-400);cursor:pointer;user-select:none">
          <input type="checkbox" id="select-all" style="width:16px;height:16px;border-radius:4px">
          Pilih Semua
        </label>
      </div>

      <div class="student-grid">
        <?php if (!empty($students)): ?>
        <?php foreach ($students as $s): ?>
        <div class="student-row" data-id="<?= $s['id'] ?>" data-kelas="<?= $s['kelas_id'] ?>">
          <div class="cb">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;display:none"><path d="M20 6 9 17l-5-5"/></svg>
          </div>
          <div class="av" style="background:<?= $color ?>"><?= strtoupper(substr($s['nama'] ?? '?', 0, 2)) ?></div>
          <div class="info">
            <div class="nm"><?= esc($s['nama']) ?></div>
            <div class="nis">NIS <?= esc($s['nis']) ?></div>
          </div>
          <div class="stud-actions">
            <button type="button" class="move modal-trigger" data-modal="modal-pindah-kelas"
              data-id="<?= $s['id'] ?>" data-nama="<?= esc($s['nama']) ?>" data-kelas="<?= $s['kelas_id'] ?>"
              title="Pindah Kelas">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M12 22V12"/><path d="m3.3 7 8.7 5 8.7-5"/></svg>
            </button>
            <form method="POST" action="/siswaguru/siswa/lulus" onsubmit="return confirm('Tandai <?= esc($s['nama']) ?> sebagai lulus?')">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button type="submit" class="grad" title="Lulus">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
              </button>
            </form>
            <form method="POST" action="/siswaguru/siswa/delete" style="display:inline"
              onsubmit="var k=prompt('Keterangan pindah untuk <?= esc($s['nama']) ?>:');if(k===null)return false;var i=document.createElement('input');i.type='hidden';i.name='keterangan';i.value=k;this.appendChild(i);">
              <input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button type="submit" class="drop" title="Pindah">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div style="text-align:center;padding:40px 20px;color:var(--slate-400);font-size:14px">
          <div style="font-size:40px;margin-bottom:8px">📭</div>
          Belum ada siswa di kelas ini
        </div>
        <?php endif; ?>
      </div>

      <div class="batch-bar" id="batch-bar">
        <span class="count" id="sel-count">0 dipilih</span>
        <span style="flex:1"></span>
        <button type="button" class="ku-btn ku-btn-ghost" style="font-size:12px;height:32px" id="btn-batch-naik">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M18 15V6"/><path d="M6 18V9"/><path d="m15 3 3 3 3-3"/><path d="M6 21l3-3 3 3"/></svg>
          Naik Kelas
        </button>
        <button type="button" class="ku-btn ku-btn-ghost" style="font-size:12px;height:32px;color:var(--navy-600);border-color:var(--navy-200)" id="btn-batch-pindah">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M12 22V12"/><path d="m3.3 7 8.7 5 8.7-5"/></svg>
          Pindah Kelas
        </button>
        <button type="button" class="ku-btn ku-btn-ghost" style="font-size:12px;height:32px;color:var(--amber-500);border-color:var(--amber-200)" id="btn-batch-hapus">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          Pindah
        </button>
      </div>

    </div>
  </div>
</div>

<div class="ku-modal-overlay" id="modal-pindah-kelas">
  <div class="ku-modal-box" style="max-width:420px">
    <form method="POST" action="/siswaguru/siswa/pindah-kelas" id="form-pindah">
      <div class="ku-modal-head">
        <div>
          <h3 id="pindah-title">Pindah Kelas</h3>
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
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
var selected = new Set();
var kelasId = '<?= $class['id'] ?>';

document.querySelectorAll('.student-row').forEach(function(row){
  row.addEventListener('click', function(e){
    if (e.target.closest('.stud-actions') || e.target.closest('.modal-trigger')) return;
    var id = this.dataset.id;
    if (selected.has(id)) {
      selected.delete(id);
      this.classList.remove('selected');
    } else {
      selected.add(id);
      this.classList.add('selected');
    }
    updateBatch();
  });
});

document.getElementById('select-all').addEventListener('change', function(){
  var checked = this.checked;
  document.querySelectorAll('.student-row').forEach(function(row){
    var id = row.dataset.id;
    if (checked) {
      selected.add(id);
      row.classList.add('selected');
    } else {
      selected.delete(id);
      row.classList.remove('selected');
    }
  });
  updateBatch();
});

function updateBatch() {
  var bar = document.getElementById('batch-bar');
  var count = document.getElementById('sel-count');
  var n = selected.size;
  count.textContent = n + ' dipilih';
  bar.classList.toggle('show', n > 0);
}

function submitBatch(action) {
  if (selected.size === 0) { alert('Pilih siswa dulu.'); return; }
  if (!confirm(action + ' ' + selected.size + ' siswa?')) return;
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = action === 'naik' ? '/siswaguru/siswa/naik-kelas' : '/siswaguru/siswa/turun-kelas';
  selected.forEach(function(id){
    var inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = 'siswa_ids[]';
    inp.value = id;
    form.appendChild(inp);
  });
  document.body.appendChild(form);
  form.submit();
}

document.getElementById('btn-batch-naik').addEventListener('click', function(){ submitBatch('naik'); });

document.getElementById('btn-batch-hapus').addEventListener('click', function(){
  submitHapus();
});

function submitHapus() {
  if (selected.size === 0) { alert('Pilih siswa dulu.'); return; }
  var k = prompt('Keterangan pindah untuk ' + selected.size + ' siswa:');
  if (k === null) return;
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = '/siswaguru/siswa/delete-batch';
  var ki = document.createElement('input');
  ki.type = 'hidden'; ki.name = 'keterangan'; ki.value = k;
  form.appendChild(ki);
  selected.forEach(function(id){
    var inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = 'ids[]';
    inp.value = id;
    form.appendChild(inp);
  });
  document.body.appendChild(form);
  form.submit();
}

document.getElementById('btn-batch-pindah').addEventListener('click', function(){
  if (selected.size === 0) { alert('Pilih siswa dulu.'); return; }
  var form = document.getElementById('form-pindah');
  document.querySelectorAll('#form-pindah input[name="siswa_ids[]"]').forEach(function(e){ e.remove(); });
  document.getElementById('pindah-id').value = '';
  document.getElementById('pindah-nama').textContent = selected.size + ' siswa dipilih';
  document.getElementById('pindah-kelas').value = '';
  selected.forEach(function(id){
    var inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = 'siswa_ids[]';
    inp.value = id;
    form.appendChild(inp);
  });
  document.getElementById('modal-pindah-kelas').classList.add('active');
});

document.querySelectorAll('.modal-trigger[data-modal="modal-pindah-kelas"]').forEach(function(btn){
  btn.addEventListener('click', function(){
    var form = document.getElementById('form-pindah');
    document.querySelectorAll('#form-pindah input[name="siswa_ids[]"]').forEach(function(e){ e.remove(); });
    document.getElementById('pindah-id').value = this.dataset.id;
    document.getElementById('pindah-nama').textContent = this.dataset.nama;
    document.getElementById('pindah-kelas').value = this.dataset.kelas;
  });
});
</script>
<div class="ku-modal-overlay" id="modal-kelas">
  <div class="ku-modal-box" style="max-width:420px">
    <form method="POST" action="/siswaguru/kelas/store" id="form-kelas">
      <div class="ku-modal-head">
        <div>
          <h3 id="modal-kelas-title">Edit Kelas</h3>
          <p id="modal-kelas-sub">Ubah data kelas</p>
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
