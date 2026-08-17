<?php
  $pageTitle = 'Kas Yayasan';
  $pageDesc = 'Pemasukan & pengeluaran yayasan';
  $isSuperAdmin = ($user['role'] ?? '') === 'superadmin';
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
          <div class="ku-date-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" id="filterTanggal" value="<?= esc($filterTanggal) ?>" onchange="gantiTanggal()" class="ku-date-input">
          </div>
          <div class="ku-btn-group">
            <button class="ku-btn <?= !empty($viewSemua) ? 'ku-btn-primary' : 'ku-btn-ghost' ?>" onclick="window.location.href='<?= '/kas-yayasan' ?>'">Semua Tanggal</button>
            <button class="ku-btn ku-btn-ghost" onclick="setTanggal('hari')">Hari Ini</button>
            <button class="ku-btn ku-btn-ghost" onclick="setTanggal('kemarin')">Kemarin</button>
          </div>
        </div>
        <div class="ku-toolbar-right">
          <button class="ku-btn ku-btn-outline" onclick="openModal('modalTransfer');refreshSaldo()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
            Transfer Saldo
          </button>
          <button class="ku-btn ku-btn-primary" onclick="setLimitedEdit('modalMasuk',false);openModal('modalMasuk')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v16M6 12l6 6 6-6"/><path d="M4 20h16"/></svg>
            Pemasukan
          </button>
          <button class="ku-btn ku-btn-danger" onclick="setLimitedEdit('modalKeluar',false);openModal('modalKeluar')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22V6M6 12l6-6 6 6"/><path d="M4 4h16"/></svg>
            Pengeluaran
          </button>
        </div>
      </div>

      <div class="ku-stats">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M12 8v8M8 12l4-4 4 4"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Pemasukan</div>
            <div class="ku-stat-value">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Tunai: Rp <?= number_format($pemasukanTunai, 0, ',', '.') ?></span>
              <span class="ku-stat-chip">Transfer: Rp <?= number_format($pemasukanTransfer, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M12 16V8M8 12l4-4 4 4"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Pengeluaran</div>
            <div class="ku-stat-value">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Tunai: Rp <?= number_format($pengeluaranTunai, 0, ',', '.') ?></span>
              <span class="ku-stat-chip">Transfer: Rp <?= number_format($pengeluaranTransfer, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-indigo" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Saldo Kas (kumulatif)</div>
            <div class="ku-stat-value">Rp <?= number_format($saldoKumulatif, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip"><?= !empty($viewSemua) ? 'Semua Tanggal' : 's.d. ' . date('d M Y', strtotime($filterTanggal)) ?></span>
              <span class="ku-stat-chip">Tunai: Rp <?= number_format($saldoTunaiKumulatif, 0, ',', '.') ?></span>
              <span class="ku-stat-chip">Transfer: Rp <?= number_format($saldoTransferKumulatif, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-violet" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Saldo Tabungan</div>
            <div class="ku-stat-value">Rp <?= number_format($saldoTabungan, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Semua unit</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.4s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Transaksi</div>
            <div class="ku-stat-value"><?= $totalTransaksi ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip"><?= $totalHari ?> hari aktif</span>
              <span class="ku-stat-chip"><?= date('M Y') ?></span>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($rekapPerTanggal)): ?>
      <div class="ku-main">
          <?php if (!empty($transaksi)): ?>
          <div class="ku-table-header">
            <div>
              <h3>Transaksi <?php if (!empty($viewSemua)): ?><span class="ku-date-label">Semua Tanggal</span><?php else: ?><span class="ku-date-label"><?= date('d M Y', strtotime($filterTanggal)) ?></span><?php endif; ?></h3>
              <p><?= count($transaksi) ?> transaksi ditemukan</p>
            </div>
            <div class="ku-table-export">
              <div class="ku-search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="text" id="cariTransaksi" placeholder="Cari transaksi..." oninput="cariTransaksi()">
              </div>
              <span class="ku-summary-badge"><?= !empty($viewSemua) ? 'Saldo Total' : 'Saldo s.d. ' . date('d M Y', strtotime($filterTanggal)) ?>: <strong>Rp <?= number_format($saldoKumulatif, 0, ',', '.') ?></strong></span>
            </div>
          </div>
          <div class="ku-table-wrap">
            <table class="ku-table" id="tabelTransaksi">
              <thead>
                <tr>
                  <th style="width:36px">#</th>
                  <th style="width:104px">Tipe</th>
                  <th style="width:120px">Unit</th>
                  <th style="width:130px">Tanggal &amp; Jam</th>
                  <th>Keterangan</th>
                  <th style="width:80px">Kategori</th>
                  <th style="width:80px">Metode</th>
                  <th style="width:140px" class="num">Jumlah</th>
                  <th style="width:72px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                  <?php $no = 1; foreach ($transaksi as $t): ?>
                  <tr class="ku-row-<?= $t['tipe'] ?>">
                    <td class="ku-td-num"><?= $no++ ?></td>
                    <td>
                      <?php if ($t['tipe'] === 'pemasukan'): ?>
                      <span class="ku-badge ku-badge-green">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                        Masuk
                      </span>
                      <?php else: ?>
                      <span class="ku-badge ku-badge-red">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                        Keluar
                      </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php $un = $t['unit_nama'] ?? '-'; ?>
                      <?php if ($un !== '-' && $un !== ''): ?>
                      <span class="ku-unit-badge" style="--unit-color:<?= '#' . substr(md5($un), 0, 6) ?>"><?= esc($un) ?></span>
                      <?php else: ?>
                      <span class="ku-unit-badge" style="--unit-color:#94A3B8">Yayasan</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="ku-td-jam"><?= date('d M Y', strtotime($t['tanggal'])) ?></div>
                      <div style="font-size:10px;font-weight:600;color:var(--ku-slate-400);font-variant-numeric:tabular-nums"><?= date('H:i', strtotime($t['created_at'] ?? $t['tanggal'])) ?></div>
                    </td>
                    <td><div class="ku-td-text"><?= esc($t['keterangan']) ?></div></td>
                    <td>
                      <?php if (!empty($t['kategori'])): ?>
                      <span class="ku-badge ku-badge-purple"><?= esc($t['kategori']) ?></span>
                      <?php else: ?>
                      <span class="ku-badge ku-badge-slate">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (($t['metode'] ?? 'tunai') === 'transfer'): ?>
                      <span class="ku-badge ku-badge-info">Transfer</span>
                      <?php else: ?>
                      <span class="ku-badge ku-badge-slate">Tunai</span>
                      <?php endif; ?>
                    </td>
                    <td class="ku-td-jumlah <?= $t['tipe'] === 'pemasukan' ? 'plus' : 'minus' ?>">
                      <span class="ku-jumlah-sign"><?= $t['tipe'] === 'pemasukan' ? '+' : '−' ?></span>
                      Rp <?= number_format($t['jumlah'], 0, ',', '.') ?>
                    </td>
                    <td>
                      <div class="ku-actions">
                        <button onclick="editTransaksi(<?= $t['id'] ?>, '<?= $t['tipe'] ?>')" class="ku-action-btn" title="Edit">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <?php if ($isSuperAdmin): ?>
                        <form action="<?= '/kas-yayasan/hapus/' . $t['id'] ?>" method="post" onsubmit="return confirmHapus(this)" data-tutup="<?= ($t['referensi_tipe'] ?? null) === 'tutup_buku' ? '1' : '0' ?>" style="display:inline">
                          <input type="hidden" name="tipe" value="<?= $t['tipe'] ?>">
                          <?= csrf_field() ?>
                          <button type="submit" class="ku-action-btn ku-action-del" title="Hapus">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                          </button>
                        </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="ku-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><circle cx="9" cy="15" r="1" fill="currentColor"/><circle cx="15" cy="15" r="1" fill="currentColor"/></svg>
            <p>Belum ada transaksi</p>
            <span><?= !empty($viewSemua) ? 'Belum ada data kas yayasan' : 'Pada tanggal ' . date('d M Y', strtotime($filterTanggal)) ?></span>
          </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
      <div class="ku-empty-full">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        <h3>Belum ada data transaksi</h3>
        <p>Mulai catat pemasukan atau pengeluaran yayasan menggunakan tombol di atas.</p>
      </div>
      <?php endif; ?>

      <!-- Modal: Tambah Pemasukan -->
      <div class="ku-modal-overlay" id="modalMasuk">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3 id="modalMasukTitle">Tambah Pemasukan</h3>
              <p>Catat pemasukan ke kas yayasan</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalMasuk')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form id="formMasuk" action="<?= '/kas-yayasan/tambah' ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="tipe" value="pemasukan">
            <input type="hidden" name="id" id="masukId" value="">
            <div class="ku-modal-body">
              <div class="ku-limited-note" style="display:none">Hanya keterangan yang dapat diedit.</div>
              <div class="ku-field ku-fullonly">
                <label>Unit Sekolah <span class="ku-opt-label">(opsional)</span></label>
                <select name="unit_id" id="masukUnit">
                  <option value="">— Umum / Yayasan —</option>
                  <?php if (!empty($unitList)): ?>
                    <?php foreach ($unitList as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['nama']) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <div class="ku-field-row">
                <div class="ku-field ku-fullonly">
                  <label>Tanggal</label>
                  <input type="date" name="tanggal" id="masukTanggal" value="<?= esc($filterTanggal) ?>" required>
                </div>
                <div class="ku-field ku-fullonly">
                  <label>Kategori</label>
                  <select name="kategori">
                    <option value="SPP">SPP</option>
                    <option value="Daftar Ulang">Daftar Ulang</option>
                    <option value="Tabungan">Tabungan</option>
                    <option value="Donasi">Donasi</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
              </div>
              <div class="ku-field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" id="masukKeterangan" placeholder="Contoh: SPP Semester Genap" required>
              </div>
              <div class="ku-field-row">
              <div class="ku-field ku-fullonly">
                <label>Metode</label>
                <select name="metode" required>
                  <option value="tunai">Tunai</option>
                  <option value="transfer">Transfer</option>
                </select>
              </div>
              <div class="ku-field ku-fullonly">
                <label>Jumlah</label>
                <div class="input-wrap">
                  <span class="input-prefix">Rp</span>
                  <input type="number" name="jumlah" id="masukJumlah" min="0" required>
                </div>
              </div>
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalMasuk')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-primary">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Tambah Pengeluaran -->
      <div class="ku-modal-overlay" id="modalKeluar">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3 id="modalKeluarTitle">Tambah Pengeluaran</h3>
              <p>Catat pengeluaran yayasan</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalKeluar')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form id="formKeluar" action="<?= '/kas-yayasan/tambah' ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="tipe" value="pengeluaran">
            <input type="hidden" name="id" id="keluarId" value="">
            <div class="ku-modal-body">
              <div class="ku-limited-note" style="display:none">Hanya keterangan yang dapat diedit.</div>
              <div class="ku-field-row">
                <div class="ku-field ku-fullonly">
                  <label>Tanggal</label>
                  <input type="date" name="tanggal" id="keluarTanggal" value="<?= esc($filterTanggal) ?>" required>
                </div>
                <div class="ku-field ku-fullonly">
                  <label>Kategori</label>
                  <select name="kategori">
                    <option value="Gaji">Gaji</option>
                    <option value="Operasional">Operasional</option>
                    <option value="Tabungan Keluar">Tabungan Keluar</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
              </div>
              <div class="ku-field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" id="keluarKeterangan" placeholder="Contoh: Gaji Guru" required>
              </div>
              <div class="ku-field-row">
              <div class="ku-field ku-fullonly">
                <label>Metode</label>
                <select name="metode" required>
                  <option value="tunai">Tunai</option>
                  <option value="transfer">Transfer</option>
                </select>
              </div>
              <div class="ku-field ku-fullonly">
                <label>Jumlah</label>
                <div class="input-wrap">
                  <span class="input-prefix">Rp</span>
                  <input type="number" name="jumlah" id="keluarJumlah" min="0" required>
                </div>
              </div>
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalKeluar')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-danger">Simpan</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Transfer Saldo -->
      <div class="ku-modal-overlay" id="modalTransfer">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3>Transfer Saldo</h3>
              <p>Pindahkan dari Saldo Transfer ke Saldo Tunai</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalTransfer')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form action="<?= '/kas-yayasan/transfer-saldo' ?>" method="post">
            <?= csrf_field() ?>
            <div class="ku-modal-body">
              <div class="ku-saldo-card">
                <div class="ku-saldo-label">Saldo Transfer</div>
                <div class="ku-saldo-value" id="saldoTransferDisplay">Rp <?= number_format($saldoTransfer, 0, ',', '.') ?></div>
              </div>
              <div class="ku-field-row">
                <div class="ku-field">
                  <label>Tanggal</label>
                  <input type="date" name="tanggal" value="<?= esc($filterTanggal) ?>" required>
                </div>
                <div class="ku-field">
                  <label>Nominal (Rp)</label>
                  <input type="number" name="nominal" min="0" max="<?= $saldoTransfer ?>" required placeholder="Masukkan nominal">
                </div>
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalTransfer')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-accent">Transfer</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
var isSuperAdmin = <?= $isSuperAdmin ? 'true' : 'false' ?>;

function setLimitedEdit(modalId, limited) {
  var modal = document.getElementById(modalId);
  if (!modal) return;
  modal.querySelectorAll('.ku-fullonly input, .ku-fullonly select').forEach(function(i) { i.disabled = limited; });
  modal.querySelectorAll('.ku-fullonly').forEach(function(el) { el.style.opacity = limited ? '0.5' : '1'; });
  var note = modal.querySelector('.ku-limited-note');
  if (note) note.style.display = limited ? 'block' : 'none';
}

function gantiTanggal() {
  window.location.href = '<?= '/kas-yayasan' ?>?tanggal=' + document.getElementById('filterTanggal').value;
}
function setTanggal(mode) {
  var now = new Date();
  if (mode === 'kemarin') { now.setDate(now.getDate() - 1); }
  window.location.href = '<?= '/kas-yayasan' ?>?tanggal=' + now.toISOString().slice(0,10);
}

function editTransaksi(id, tipe) {
  fetch('/kas-yayasan/getData/' + id + '?tipe=' + tipe)
    .then(r => r.json())
    .then(d => {
      if (d.tipe === 'pemasukan') {
        document.getElementById('modalMasukTitle').textContent = 'Edit Pemasukan';
        document.getElementById('masukId').value = d.id;
        document.getElementById('masukUnit').value = d.unit_id || '';
        document.getElementById('masukTanggal').value = d.tanggal;
        document.getElementById('masukKeterangan').value = d.keterangan;
        document.getElementById('masukJumlah').value = d.jumlah;
        document.getElementById('formMasuk').action = '/kas-yayasan/edit/' + d.id;
        if (!isSuperAdmin) setLimitedEdit('modalMasuk', true);
        openModal('modalMasuk');
      } else {
        document.getElementById('modalKeluarTitle').textContent = 'Edit Pengeluaran';
        document.getElementById('keluarId').value = d.id;
        document.getElementById('keluarTanggal').value = d.tanggal;
        document.getElementById('keluarKeterangan').value = d.keterangan;
        document.getElementById('keluarJumlah').value = d.jumlah;
        document.getElementById('formKeluar').action = '/kas-yayasan/edit/' + d.id;
        if (!isSuperAdmin) setLimitedEdit('modalKeluar', true);
        openModal('modalKeluar');
      }
    });
}

// Reset form title on modal close
document.querySelectorAll('.ku-modal-overlay .ku-modal-close, .ku-modal-overlay .ku-btn-ghost').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var overlay = this.closest('.ku-modal-overlay');
    if (!overlay) return;
    if (overlay.id === 'modalMasuk') {
      setTimeout(function() {
        if (!overlay.classList.contains('active')) {
          document.getElementById('modalMasukTitle').textContent = 'Tambah Pemasukan';
          document.getElementById('masukId').value = '';
          document.getElementById('formMasuk').action = '<?= '/kas-yayasan/tambah' ?>';
          setLimitedEdit('modalMasuk', false);
        }
      }, 300);
    }
    if (overlay.id === 'modalKeluar') {
      setTimeout(function() {
        if (!overlay.classList.contains('active')) {
          document.getElementById('modalKeluarTitle').textContent = 'Tambah Pengeluaran';
          document.getElementById('keluarId').value = '';
          document.getElementById('formKeluar').action = '<?= '/kas-yayasan/tambah' ?>';
          setLimitedEdit('modalKeluar', false);
        }
      }, 300);
    }
  });
});

function refreshSaldo() {
  fetch('<?= '/kas-yayasan/saldo' ?>')
    .then(r => r.json())
    .then(d => {
      document.getElementById('saldoTransferDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(d.saldo_transfer);
      var maxInput = document.querySelector('#modalTransfer input[name="nominal"]');
      if (maxInput) maxInput.max = d.saldo_transfer;
    });
}

function cariTransaksi() {
  var q = document.getElementById('cariTransaksi').value.toLowerCase();
  var table = document.getElementById('tabelTransaksi');
  if (!table) return;
  var rows = table.querySelectorAll('tbody tr');
  rows.forEach(function (r) {
    var match = r.textContent.toLowerCase().indexOf(q) !== -1;
    r.style.display = match ? '' : 'none';
  });
}

function confirmHapus(form) {
  var pesan = 'Hapus data ini?';
  if (form.getAttribute('data-tutup') === '1') {
    pesan = 'Transaksi ini berasal dari tutup buku unit. Menghapus di sini akan membuat buku unit & yayasan tidak sinkron.\n\nUntuk koreksi yang benar, gunakan menu "Buka Kembali" di Kas Unit.\n\nLanjutkan hapus?';
  }
  return confirm(pesan);
}
</script>
<?= view('layout/footer') ?>