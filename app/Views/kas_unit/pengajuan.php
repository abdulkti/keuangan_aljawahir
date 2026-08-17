<?php
  $pageTitle = 'Pengajuan Dana';
  $pageDesc = 'Kelola pengajuan dana dari unit sekolah';
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

      <?php
        $total = count($pengajuan);
        $pending = count(array_filter($pengajuan, fn($p) => $p['status'] === 'pending'));
        $disetujui = count(array_filter($pengajuan, fn($p) => $p['status'] === 'disetujui'));
        $ditolak = count(array_filter($pengajuan, fn($p) => $p['status'] === 'ditolak'));
      ?>

      <div class="ku-stats">
        <div class="ku-stat ku-stat-indigo" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Pengajuan</div>
            <div class="ku-stat-value"><?= $total ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Menunggu</div>
            <div class="ku-stat-value"><?= $pending ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-green" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Disetujui</div>
            <div class="ku-stat-value"><?= $disetujui ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Ditolak</div>
            <div class="ku-stat-value"><?= $ditolak ?></div>
          </div>
        </div>
      </div>

      <div class="ku-toolbar">
        <div class="ku-toolbar-left">
          <form method="GET" action="/kas-unit/pengajuan" style="display:flex;gap:8px;margin:0">
            <select name="status" class="ku-filter-select" onchange="this.form.submit()">
              <option value="">Semua Status</option>
              <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Menunggu</option>
              <option value="disetujui" <?= $filterStatus === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
              <option value="ditolak" <?= $filterStatus === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
            </select>
            <?php if ($filterStatus): ?>
            <a href="/kas-unit/pengajuan" class="ku-btn ku-btn-outline">Reset</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="ku-table-wrap">
        <table class="ku-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Unit</th>
              <th>Pengaju</th>
              <th>Keterangan</th>
              <th style="text-align:right">Jumlah</th>
              <th style="text-align:center">Status</th>
              <th style="text-align:center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($pengajuan)): ?>
            <tr>
              <td colspan="7">
                <div class="ku-empty">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                  <p>Belum ada pengajuan dana</p>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($pengajuan as $p): ?>
            <?php
              $statusClass = match($p['status']) {
                'disetujui' => 'ku-badge ku-badge-green',
                'ditolak' => 'ku-badge ku-badge-red',
                default => 'ku-badge ku-badge-amber',
              };
              $statusLabel = match($p['status']) {
                'disetujui' => 'Disetujui',
                'ditolak' => 'Ditolak',
                default => 'Menunggu',
              };
            ?>
            <tr style="border-bottom:1px solid var(--ku-slate-100)">
              <td style="white-space:nowrap;color:var(--ku-slate-600)"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
              <td style="font-weight:600"><?= esc($p['unit_nama']) ?></td>
              <td style="color:var(--ku-slate-600)"><?= esc($p['user_nama'] ?? '-') ?></td>
              <td style="max-width:250px">
                <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= esc($p['keterangan']) ?>"><?= esc($p['keterangan']) ?></div>
                <?php if (($p['referensi_tipe'] ?? '') === 'transaksi_tabungan'): ?>
                <span class="ku-badge ku-badge-info" style="margin-top:2px">Penarikan Tabungan</span>
                <?php endif; ?>
                <?php if ($p['status'] === 'ditolak' && $p['alasan_tolak']): ?>
                <div style="font-size:11px;color:var(--ku-red);margin-top:2px">Alasan: <?= esc($p['alasan_tolak']) ?></div>
                <?php endif; ?>
                <?php if ($p['status'] === 'disetujui' && $p['approved_nama']): ?>
                <div style="font-size:11px;color:var(--ku-green);margin-top:2px">Oleh: <?= esc($p['approved_nama']) ?></div>
                <?php endif; ?>
              </td>
              <td style="text-align:right;font-weight:600">Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></td>
              <td style="text-align:center"><span class="<?= $statusClass ?>"><?= $statusLabel ?></span></td>
              <td style="text-align:center">
                <?php if ($p['status'] === 'pending'): ?>
                <div style="display:flex;gap:6px;justify-content:center">
                  <form method="post" action="/kas-unit/setujui-pengajuan/<?= $p['id'] ?>" style="display:inline" onsubmit="return confirm('Setujui pengajuan ini?<?= (($p['referensi_tipe'] ?? '') === 'transaksi_tabungan') ? ' Dana dari Yayasan akan masuk ke Kas Unit dan penarikan tabungan akan dicatat sebagai pengeluaran.' : ' Dana akan otomatis masuk ke Kas Unit.' ?>')">
                    <?= csrf_field() ?>
                    <button type="submit" class="ku-btn ku-btn-primary" style="padding:4px 12px;font-size:11px">Setujui</button>
                  </form>
                  <button onclick="showTolak(<?= $p['id'] ?>)" class="ku-btn ku-btn-danger" style="padding:4px 12px;font-size:11px">Tolak</button>
                </div>
                <?php else: ?>
                <span style="color:var(--ku-slate-400);font-size:12px">-</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<div class="ku-modal-overlay" id="modalTolak">
  <div class="ku-modal-box" style="width:440px">
    <div class="ku-modal-head">
      <div>
        <h3>Tolak Pengajuan</h3>
        <p>Berikan alasan mengapa pengajuan ini ditolak.</p>
      </div>
      <button class="ku-modal-close" onclick="hideTolak()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <form method="post" action="" id="formTolak">
      <?= csrf_field() ?>
      <div class="ku-modal-body">
        <div class="ku-field">
          <textarea name="alasan_tolak" rows="3" placeholder="Alasan penolakan..." required></textarea>
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost" onclick="hideTolak()">Batal</button>
        <button type="submit" class="ku-btn ku-btn-danger">Tolak Pengajuan</button>
      </div>
    </form>
  </div>
</div>

<script>
function showTolak(id) {
  document.getElementById('formTolak').action = '/kas-unit/tolak-pengajuan/' + id;
  document.getElementById('modalTolak').classList.add('active');
}
function hideTolak() {
  document.getElementById('modalTolak').classList.remove('active');
}
document.getElementById('modalTolak')?.addEventListener('click', function(e) {
  if (e.target === this) hideTolak();
});
</script>

<?= view('layout/footer') ?>
