<?php
  $pageTitle = 'Kas Unit';
  $pageDesc = 'Transaksi harian & tutup buku';
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
          <div class="ku-btn-group">
            <button class="ku-btn <?= $mode === 'hari' ? 'ku-btn-accent' : 'ku-btn-ghost' ?>" onclick="setMode('hari')">Per Hari</button>
            <button class="ku-btn <?= $mode === 'bulan' ? 'ku-btn-accent' : 'ku-btn-ghost' ?>" onclick="setMode('bulan')">Bulanan</button>
          </div>
          <?php if ($mode === 'hari'): ?>
          <div class="ku-date-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" id="filterTanggal" value="<?= esc($filterTanggal) ?>" onchange="gantiTanggal()" class="ku-date-input">
          </div>
          <div class="ku-btn-group">
            <button class="ku-btn ku-btn-ghost" onclick="setTanggal('hari')">Hari Ini</button>
            <button class="ku-btn ku-btn-ghost" onclick="setTanggal('kemarin')">Kemarin</button>
          </div>
          <?php else: ?>
          <div class="ku-date-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="month" id="filterBulan" value="<?= esc($filterBulan) ?>" onchange="gantiBulan()" class="ku-date-input" style="width:140px">
          </div>
          <div class="ku-btn-group">
            <button class="ku-btn ku-btn-ghost" onclick="geserBulan(-1)">&larr; Sebelumnya</button>
            <button class="ku-btn ku-btn-ghost" onclick="geserBulan(1)">Berikutnya &rarr;</button>
          </div>
          <?php endif; ?>
        </div>
        <div class="ku-toolbar-right">
          <button class="ku-btn ku-btn-primary" onclick="openModal('modalMasuk')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v16M6 12l6 6 6-6"/><path d="M4 20h16"/></svg>
            Pemasukan
          </button>
          <button class="ku-btn ku-btn-danger" onclick="openModal('modalKeluar')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22V6M6 12l6-6 6 6"/><path d="M4 4h16"/></svg>
            Pengeluaran
          </button>
          <button class="ku-btn ku-btn-accent" onclick="openModal('modalTutupBuku');loadRekapHarian()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Tutup Buku
          </button>
          <button class="ku-btn ku-btn-outline" onclick="openModal('modalAjukan')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>
            Ajukan Dana
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
            <div class="ku-stat-label">Saldo Total</div>
            <div class="ku-stat-value">Rp <?= number_format($saldo, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Tunai: Rp <?= number_format($saldoTunai, 0, ',', '.') ?></span>
              <span class="ku-stat-chip">Transfer: Rp <?= number_format($saldoTransfer, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.3s">
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
          <?php if ($mode === 'bulan'): ?>
          <div class="ku-table-header">
            <div>
              <h3>Transaksi Bulanan <span class="ku-date-label"><?= date('M Y', strtotime($filterBulan . '-01')) ?></span></h3>
              <p><?= count($filteredBulan) ?> transaksi ditemukan <?php if ($allUnit): ?>&mdash; Semua unit<?php endif; ?></p>
            </div>
            <div class="ku-table-export">
              <div class="ku-search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="text" id="cariTransaksi" placeholder="Cari transaksi..." oninput="cariTransaksi()">
              </div>
              <span class="ku-summary-badge">Saldo: <strong>Rp <?= number_format($totalBulanPemasukan - $totalBulanPengeluaran, 0, ',', '.') ?></strong></span>
            </div>
          </div>

          <?php if (!empty($filteredBulan)): ?>
          <div class="ku-table-wrap">
            <table class="ku-table" id="tabelTransaksi">
              <thead>
                <tr>
                  <th style="width:36px">#</th>
                  <th style="width:92px">Tanggal</th>
                  <th style="width:56px">Jam</th>
                  <th style="width:100px">Tipe</th>
                  <?php if ($allUnit): ?>
                  <th style="width:120px">Unit</th>
                  <?php endif; ?>
                  <th>Keterangan</th>
                  <th style="width:110px">Kategori</th>
                  <th style="width:80px">Metode</th>
                  <th style="width:120px" class="num">Masuk</th>
                  <th style="width:120px" class="num">Keluar</th>
                  <th style="width:130px" class="num">Saldo</th>
                  <th style="width:70px">Status</th>
                  <th style="width:72px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($filteredBulan as $t): ?>
                <tr class="ku-row-<?= $t['jenis'] ?> <?= $t['status_tutup'] === 'tutup' ? 'ku-row-tutup' : '' ?>">
                  <td class="ku-td-num"><?= $no++ ?></td>
                  <td class="ku-td-jam"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                  <td class="ku-td-jam"><?= !empty($t['created_at']) ? date('H:i', strtotime($t['created_at'])) : '-' ?></td>
                  <td>
                    <?php if ($t['jenis'] === 'pemasukan'): ?>
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
                  <?php if ($allUnit): ?>
                  <td><span class="ku-unit-badge" style="--unit-color:<?= '#' . substr(md5($t['unit_nama'] ?? ''), 0, 6) ?>"><?= esc($t['unit_nama'] ?? '-') ?></span></td>
                  <?php endif; ?>
                  <td><div class="ku-td-text"><?= esc($t['keterangan']) ?></div></td>
                  <td><?= esc($t['kategori'] ?? '-') ?></td>
                  <td>
                    <?php if (($t['metode'] ?? 'tunai') === 'transfer'): ?>
                    <span class="ku-badge ku-badge-info">Transfer</span>
                    <?php else: ?>
                    <span class="ku-badge ku-badge-slate">Tunai</span>
                    <?php endif; ?>
                  </td>
                  <td class="ku-td-jumlah plus"><?= $t['jenis'] === 'pemasukan' ? 'Rp ' . number_format($t['jumlah'], 0, ',', '.') : '' ?></td>
                  <td class="ku-td-jumlah minus"><?= $t['jenis'] === 'pengeluaran' ? 'Rp ' . number_format($t['jumlah'], 0, ',', '.') : '' ?></td>
                  <td class="ku-td-jumlah <?= ($t['saldo_berjalan'] ?? 0) >= 0 ? 'plus' : 'minus' ?>">Rp <?= number_format($t['saldo_berjalan'] ?? 0, 0, ',', '.') ?></td>
                  <td>
                    <?php if ($t['status_tutup'] === 'tutup'): ?>
                    <span class="ku-badge ku-badge-done">Tutup</span>
                    <?php else: ?>
                    <span class="ku-badge ku-badge-active">Aktif</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($t['status_tutup'] === 'belum'): ?>
                    <div class="ku-actions">
                      <button onclick="editTransaksi(<?= $t['id'] ?>)" class="ku-action-btn" title="Edit">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <?php if ($role === 'superadmin'): ?>
                      <form action="<?= base_url('kas-unit/hapus/' . $t['id']) ?>" method="post" onsubmit="return confirm('Hapus data ini?')" style="display:inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="ku-action-btn ku-action-del" title="Hapus">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                      </form>
                      <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <?php if ($role === 'superadmin'): ?>
                    <div class="ku-actions">
                      <form action="<?= base_url('kas-unit/buka-kembali-satu/' . $t['id']) ?>" method="post" onsubmit="return confirm('Buka kembali transaksi ini? Transaksi lain pada tanggal tersebut tidak terpengaruh.')" style="display:inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="ku-action-btn" title="Buka kembali (1 transaksi)">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                        </button>
                      </form>
                    </div>
                    <?php else: ?>
                    <span class="ku-dim">&mdash;</span>
                    <?php endif; ?>
                    <?php endif; ?>
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
            <span>Pada bulan <?= date('M Y', strtotime($filterBulan . '-01')) ?></span>
          </div>
          <?php endif; ?>

          <?php else: ?>

          <div class="ku-table-header">
            <div>
              <h3>Transaksi <span class="ku-date-label"><?= date('d M Y', strtotime($filterTanggal)) ?></span></h3>
              <p><?= count($transaksi) ?> transaksi ditemukan <?php if ($allUnit): ?>&mdash; Semua unit<?php endif; ?></p>
            </div>
            <div class="ku-table-export">
              <div class="ku-search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="text" id="cariTransaksi" placeholder="Cari transaksi..." oninput="cariTransaksi()">
              </div>
              <span class="ku-summary-badge">Total: <strong>Rp <?= number_format($totalPemasukan - $totalPengeluaran, 0, ',', '.') ?></strong></span>
            </div>
          </div>

          <?php if (!empty($transaksi)): ?>
          <div class="ku-table-wrap">
            <table class="ku-table" id="tabelTransaksi">
              <thead>
                <tr>
                  <th style="width:36px">#</th>
                  <th style="width:56px">Jam</th>
                  <th style="width:104px">Tipe</th>
                  <?php if ($allUnit): ?>
                  <th style="width:120px">Unit</th>
                  <?php endif; ?>
                  <th>Keterangan</th>
                  <th style="width:110px">Kategori</th>
                  <th style="width:80px">Metode</th>
                  <th style="width:140px" class="num">Jumlah</th>
                  <th style="width:70px">Status</th>
                  <th style="width:72px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($transaksi as $t): ?>
                <tr class="ku-row-<?= $t['jenis'] ?> <?= $t['status_tutup'] === 'tutup' ? 'ku-row-tutup' : '' ?>">
                  <td class="ku-td-num"><?= $no++ ?></td>
                  <td class="ku-td-jam"><?= !empty($t['created_at']) ? date('H:i', strtotime($t['created_at'])) : '-' ?></td>
                  <td>
                    <?php if ($t['jenis'] === 'pemasukan'): ?>
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
                  <?php if ($allUnit): ?>
                  <td><span class="ku-unit-badge" style="--unit-color:<?= '#' . substr(md5($t['unit_nama'] ?? ''), 0, 6) ?>"><?= esc($t['unit_nama'] ?? '-') ?></span></td>
                  <?php endif; ?>
                  <td><div class="ku-td-text"><?= esc($t['keterangan']) ?></div></td>
                  <td><?= esc($t['kategori'] ?? '-') ?></td>
                  <td>
                    <?php if (($t['metode'] ?? 'tunai') === 'transfer'): ?>
                    <span class="ku-badge ku-badge-info">Transfer</span>
                    <?php else: ?>
                    <span class="ku-badge ku-badge-slate">Tunai</span>
                    <?php endif; ?>
                  </td>
                  <td class="ku-td-jumlah <?= $t['jenis'] === 'pemasukan' ? 'plus' : 'minus' ?>">
                    <span class="ku-jumlah-sign"><?= $t['jenis'] === 'pemasukan' ? '+' : '−' ?></span>
                    Rp <?= number_format($t['jumlah'], 0, ',', '.') ?>
                  </td>
                  <td>
                    <?php if ($t['status_tutup'] === 'tutup'): ?>
                    <span class="ku-badge ku-badge-done">Tutup</span>
                    <?php else: ?>
                    <span class="ku-badge ku-badge-active">Aktif</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($t['status_tutup'] === 'belum'): ?>
                    <div class="ku-actions">
                      <button onclick="editTransaksi(<?= $t['id'] ?>)" class="ku-action-btn" title="Edit">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </button>
                      <?php if ($role === 'superadmin'): ?>
                      <form action="<?= base_url('kas-unit/hapus/' . $t['id']) ?>" method="post" onsubmit="return confirm('Hapus data ini?')" style="display:inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="ku-action-btn ku-action-del" title="Hapus">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                      </form>
                      <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <?php if ($role === 'superadmin'): ?>
                    <div class="ku-actions">
                      <form action="<?= base_url('kas-unit/buka-kembali-satu/' . $t['id']) ?>" method="post" onsubmit="return confirm('Buka kembali transaksi ini? Transaksi lain pada tanggal tersebut tidak terpengaruh.')" style="display:inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="ku-action-btn" title="Buka kembali (1 transaksi)">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                        </button>
                      </form>
                    </div>
                    <?php else: ?>
                    <span class="ku-dim">&mdash;</span>
                    <?php endif; ?>
                    <?php endif; ?>
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
            <span>Pada tanggal <?= date('d M Y', strtotime($filterTanggal)) ?></span>
          </div>
          <?php endif; ?>

          <?php endif; ?>
        </div>
      <?php else: ?>
      <div class="ku-empty-full">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        <h3>Belum ada data transaksi</h3>
        <p>Mulai catat pemasukan atau pengeluaran unit menggunakan tombol di atas.</p>
      </div>
      <?php endif; ?>

      <!-- Modal: Tambah Pemasukan -->
      <div class="ku-modal-overlay" id="modalMasuk">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3 id="modalMasukTitle">Tambah Pemasukan</h3>
              <p>Catat penerimaan kas unit</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalMasuk')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form id="formMasuk" action="<?= base_url('kas-unit/tambah') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="tipe" value="pemasukan">
            <input type="hidden" name="id" id="masukId" value="">
            <div class="ku-modal-body">
              <?php if ($allUnit): ?>
              <div class="ku-field">
                <label>Unit Sekolah</label>
                <select name="unit_id" id="masukUnit" required>
                  <option value="">Pilih Unit</option>
                  <?php if (!empty($unitList)): ?>
                    <?php foreach ($unitList as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['nama']) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="ku-field">
                <label>Tanggal</label>
                <input type="date" name="tanggal" id="masukTanggal" value="<?= esc($filterTanggal) ?>" required>
              </div>
              <div class="ku-field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" id="masukKeterangan" placeholder="Contoh: SPP Semester Genap" required>
              </div>
              <div class="ku-field-row">
                <div class="ku-field">
                  <label>Metode</label>
                  <select name="metode" id="masukMetode" required>
                    <option value="tunai">Tunai</option>
                    <option value="transfer">Transfer</option>
                  </select>
                </div>
                <div class="ku-field">
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
              <p>Catat pengeluaran kas unit</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalKeluar')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form id="formKeluar" action="<?= base_url('kas-unit/tambah') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="tipe" value="pengeluaran">
            <input type="hidden" name="id" id="keluarId" value="">
            <div class="ku-modal-body">
              <?php if ($allUnit): ?>
              <div class="ku-field">
                <label>Unit Sekolah</label>
                <select name="unit_id" id="keluarUnit" required>
                  <option value="">Pilih Unit</option>
                  <?php if (!empty($unitList)): ?>
                    <?php foreach ($unitList as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['nama']) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="ku-field">
                <label>Tanggal</label>
                <input type="date" name="tanggal" id="keluarTanggal" value="<?= esc($filterTanggal) ?>" required>
              </div>
              <div class="ku-field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" id="keluarKeterangan" placeholder="Contoh: Gaji Guru" required>
              </div>
              <div class="ku-field-row">
                <div class="ku-field">
                  <label>Metode</label>
                  <select name="metode" id="keluarMetode" required>
                    <option value="tunai">Tunai</option>
                    <option value="transfer">Transfer</option>
                  </select>
                </div>
                <div class="ku-field">
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

      <!-- Modal: Tutup Buku -->
      <div class="ku-modal-overlay" id="modalTutupBuku">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3>Tutup Buku Harian</h3>
              <p>Ringkasan transaksi masuk ke kas yayasan</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalTutupBuku')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form action="<?= base_url('kas-unit/tutup-buku') ?>" method="post">
            <?= csrf_field() ?>
            <div class="ku-modal-body">
              <?php if ($allUnit): ?>
              <div class="ku-field">
                <label>Unit Sekolah</label>
                <select name="unit_id" id="tutupUnit" required onchange="loadRekapHarian()">
                  <option value="">Pilih Unit</option>
                  <?php if (!empty($unitList)): ?>
                    <?php foreach ($unitList as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['nama']) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <?php else: ?>
              <input type="hidden" name="unit_id" value="<?= $unitId ?>">
              <?php endif; ?>
              <div class="ku-field">
                <label>Tanggal</label>
                <input type="date" name="tanggal" id="tutupTanggal" value="<?= esc($filterTanggal) ?>" required onchange="loadRekapHarian()">
              </div>
              <div id="rekapHarian">
                <div class="ku-rekap-placeholder">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                  <span>Pilih unit & tanggal untuk melihat rekap</span>
                </div>
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalTutupBuku')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-accent" id="btnTutupBuku" disabled>Tutup Buku</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Ajukan Dana -->
      <div class="ku-modal-overlay" id="modalAjukan">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3>Ajukan Dana ke Yayasan</h3>
              <p>Minta dana untuk kebutuhan unit</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalAjukan')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form action="<?= base_url('kas-unit/ajukan-dana') ?>" method="post">
            <?= csrf_field() ?>
            <div class="ku-modal-body">
              <?php if ($allUnit): ?>
              <div class="ku-field">
                <label>Unit</label>
                <select name="unit_id" required>
                  <option value="">Pilih Unit</option>
                  <?php foreach ($unitList as $u): ?>
                  <option value="<?= $u['id'] ?>"><?= esc($u['nama']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="ku-field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" placeholder="Contoh: Keperluan operasional bulanan" required>
              </div>
              <div class="ku-field">
                <label>Jumlah</label>
                <div class="input-wrap">
                  <span class="input-prefix">Rp</span>
                  <input type="number" name="jumlah" min="0" required>
                </div>
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalAjukan')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-primary">Kirim Pengajuan</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>



<script>
function gantiTanggal() {
  window.location.href = '<?= base_url('kas-unit') ?>?tanggal=' + document.getElementById('filterTanggal').value;
}
function setTanggal(mode) {
  var now = new Date();
  if (mode === 'kemarin') { now.setDate(now.getDate() - 1); }
  window.location.href = '<?= base_url('kas-unit') ?>?tanggal=' + now.toISOString().slice(0,10);
}
function setMode(m) {
  var params = 'mode=' + m;
  var t = document.getElementById('filterTanggal');
  var b = document.getElementById('filterBulan');
  if (t && t.value) params += '&tanggal=' + t.value;
  if (b && b.value) params += '&bulan=' + b.value;
  window.location.href = '<?= base_url('kas-unit') ?>?' + params;
}
function gantiBulan() {
  var b = document.getElementById('filterBulan').value;
  window.location.href = '<?= base_url('kas-unit') ?>?mode=bulan&bulan=' + b;
}
function geserBulan(offset) {
  var b = document.getElementById('filterBulan').value;
  if (!b) return;
  var d = new Date(b + '-01T00:00:00');
  d.setMonth(d.getMonth() + offset);
  var y = d.getFullYear(), m = String(d.getMonth() + 1).padStart(2, '0');
  window.location.href = '<?= base_url('kas-unit') ?>?mode=bulan&bulan=' + y + '-' + m;
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
function editTransaksi(id) {
  var isSuperAdmin = <?= ($role === 'superadmin') ? 'true' : 'false' ?>;
  fetch('<?= base_url('kas-unit/getData') ?>/' + id)
    .then(r => r.json())
    .then(d => {
      if (d.jenis === 'pemasukan') {
        document.getElementById('modalMasukTitle').textContent = 'Edit Pemasukan';
        document.getElementById('masukId').value = d.id;
        document.getElementById('masukTanggal').value = d.tanggal;
        document.getElementById('masukKeterangan').value = d.keterangan;
        document.getElementById('masukJumlah').value = d.jumlah;
        document.getElementById('masukTanggal').readOnly = !isSuperAdmin;
        document.getElementById('masukJumlah').readOnly = !isSuperAdmin;
        var masukMetode = document.getElementById('masukMetode');
        if (masukMetode) { masukMetode.disabled = !isSuperAdmin; masukMetode.value = d.metode; }
        document.getElementById('formMasuk').action = '<?= base_url('kas-unit/edit') ?>/' + d.id;
        openModal('modalMasuk');
      } else {
        document.getElementById('modalKeluarTitle').textContent = 'Edit Pengeluaran';
        document.getElementById('keluarId').value = d.id;
        document.getElementById('keluarTanggal').value = d.tanggal;
        document.getElementById('keluarKeterangan').value = d.keterangan;
        document.getElementById('keluarJumlah').value = d.jumlah;
        document.getElementById('keluarTanggal').readOnly = !isSuperAdmin;
        document.getElementById('keluarJumlah').readOnly = !isSuperAdmin;
        var keluarMetode = document.getElementById('keluarMetode');
        if (keluarMetode) { keluarMetode.disabled = !isSuperAdmin; keluarMetode.value = d.metode; }
        document.getElementById('formKeluar').action = '<?= base_url('kas-unit/edit') ?>/' + d.id;
        openModal('modalKeluar');
      }
    });
}

function loadRekapHarian() {
  <?php if ($allUnit): ?>
  var unitId = document.getElementById('tutupUnit').value;
  <?php else: ?>
  var unitId = '<?= $unitId ?>';
  <?php endif; ?>
  var tanggal = document.getElementById('tutupTanggal').value;
  var el = document.getElementById('rekapHarian');
  var btn = document.getElementById('btnTutupBuku');
  if (!unitId || !tanggal) {
    el.innerHTML = '<div class="ku-rekap-placeholder"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><span>Pilih unit & tanggal untuk melihat rekap</span></div>';
    btn.disabled = true; return;
  }
  el.innerHTML = '<div class="ku-rekap-placeholder" style="opacity:.5"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>Memuat rekap...</span></div>';
  fetch('<?= base_url('kas-unit/rekap-harian') ?>?unit_id=' + encodeURIComponent(unitId) + '&tanggal=' + encodeURIComponent(tanggal))
    .then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
    .then(function(d) {
      if (!d.transaksi || d.transaksi.length === 0) {
        el.innerHTML = '<div class="ku-rekap-placeholder"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 9v4M12 17v.01"/><circle cx="12" cy="12" r="10"/></svg><span>Tidak ada transaksi pada tanggal ini</span></div>';
        btn.disabled = true; return;
      }
      var h = '<div style="background:var(--ku-slate-50);border-radius:10px;padding:16px">';
      h += '<div style="font-size:10px;font-weight:700;color:var(--ku-slate-500);text-transform:uppercase;letter-spacing:.4px;margin-bottom:12px">Rekap ' + tanggal + '</div>';
      h += '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--ku-slate-200);font-size:13px"><span style="color:var(--ku-slate-600)">Total Pemasukan</span><span style="font-weight:700;color:#059669">Rp ' + new Intl.NumberFormat('id-ID').format(d.total_pemasukan) + '</span></div>';
      h += '<div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--ku-slate-200);font-size:13px"><span style="color:var(--ku-slate-600)">Total Pengeluaran</span><span style="font-weight:700;color:#DC2626">Rp ' + new Intl.NumberFormat('id-ID').format(d.total_pengeluaran) + '</span></div>';
      var selisih = d.selisih;
      var selisihColor = selisih >= 0 ? '#059669' : '#DC2626';
      h += '<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0 0;font-size:14px"><span style="font-weight:700;color:var(--ku-slate-700)">Selisih</span><span style="font-weight:800;color:' + selisihColor + '">Rp ' + new Intl.NumberFormat('id-ID').format(selisih) + '</span></div>';
      h += '</div>';
      el.innerHTML = h;
      btn.disabled = false;
    })
    .catch(function(e) {
      el.innerHTML = '<div class="ku-rekap-placeholder"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg><span>Gagal memuat rekap</span></div>';
      btn.disabled = true;
    });
}
</script>
<?= view('layout/footer') ?>
