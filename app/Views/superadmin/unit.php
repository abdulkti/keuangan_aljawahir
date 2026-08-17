<?php
  $pageTitle = 'Unit Sekolah';
  $pageDesc = 'Kelola unit sekolah di bawah naungan yayasan';
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
          <div style="font-size:14px;font-weight:600;color:var(--ku-slate-700)"><?= $pageDesc ?></div>
        </div>
        <div class="ku-toolbar-right">
          <button class="ku-btn ku-btn-accent" onclick="openModal('modalUnit')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Unit
          </button>
        </div>
      </div>

      <div class="ku-table-wrap">
        <table class="ku-table">
          <thead>
            <tr>
              <th>Nama Sekolah</th>
              <th>Alamat</th>
              <th>Kepala Sekolah</th>
              <th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($unitList)): ?>
              <?php foreach ($unitList as $u): ?>
              <tr>
                <td><strong><?= esc($u['nama']) ?></strong></td>
                <td><?= esc($u['alamat'] ?? '-') ?></td>
                <td><?= esc($u['kepala_sekolah'] ?? '-') ?></td>
                <td>
                  <div class="ku-actions" style="justify-content:center">
                    <button onclick="editUnit(<?= $u['id'] ?>)" class="ku-action-btn" title="Edit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <form action="<?= base_url('unit/hapus/' . $u['id']) ?>" method="post" onsubmit="return confirm('Hapus unit ini?')" style="display:inline">
                      <button type="submit" class="ku-action-btn ku-action-del" title="Hapus">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;color:var(--ku-slate-400);padding:40px">Belum ada unit sekolah</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="ku-modal-overlay" id="modalUnit">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3 id="modalUnitTitle">Tambah Unit Sekolah</h3>
              <p>Data dasar unit sekolah</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalUnit')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form id="formUnit" action="<?= base_url('unit/tambah') ?>" method="post">
            <input type="hidden" name="id" id="unitId" value="">
            <div class="ku-modal-body">
              <div class="ku-field">
                <label>Nama Sekolah</label>
                <input type="text" name="nama" id="unitNama" placeholder="Contoh: SD IT Al-Jawahir" required>
              </div>
              <div class="ku-field">
                <label>Alamat</label>
                <input type="text" name="alamat" id="unitAlamat" placeholder="Jl. Pendidikan No. 1">
              </div>
              <div class="ku-field">
                <label>Kepala Sekolah</label>
                <input type="text" name="kepala_sekolah" id="unitKepsek" placeholder="Nama Kepala Sekolah">
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalUnit')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-primary">Simpan</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>
<script>
function editUnit(id) {
  fetch('<?= base_url('unit/getData') ?>/' + id)
    .then(r => r.json())
    .then(d => {
      document.getElementById('modalUnitTitle').textContent = 'Edit Unit Sekolah';
      document.getElementById('unitId').value = d.id;
      document.getElementById('unitNama').value = d.nama;
      document.getElementById('unitAlamat').value = d.alamat || '';
      document.getElementById('unitKepsek').value = d.kepala_sekolah || '';
      document.getElementById('formUnit').action = '<?= base_url('unit/edit') ?>/' + d.id;
      openModal('modalUnit');
    });
}
document.querySelector('#modalUnit .ku-modal-close')?.addEventListener('click', function(){
  document.getElementById('modalUnitTitle').textContent = 'Tambah Unit Sekolah';
  document.getElementById('unitId').value = '';
  document.getElementById('formUnit').action = '<?= base_url('unit/tambah') ?>';
});
</script>
<?= view('layout/footer') ?>
