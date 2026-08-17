<?php
  $pageTitle = 'Rekap Tagihan';
  $pageDesc = 'Rekap pembayaran SPP & Daftar Ulang';
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

      <form method="GET" action="/kas-unit/rekap" class="ku-toolbar">
        <div class="ku-toolbar-left">
          <div class="ku-date-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" name="tanggal" value="<?= esc($filterTanggal) ?>" class="ku-date-input" onchange="this.form.submit()">
          </div>
          <select name="metode" class="ku-filter-select">
            <option value="">Semua Metode</option>
            <option value="tunai" <?= $filterMetode === 'tunai' ? 'selected' : '' ?>>Tunai</option>
            <option value="transfer" <?= $filterMetode === 'transfer' ? 'selected' : '' ?>>Transfer</option>
          </select>
          <select name="jenis" class="ku-filter-select">
            <option value="">Semua Tagihan</option>
            <option value="SPP Bulanan" <?= $filterJenis === 'SPP Bulanan' ? 'selected' : '' ?>>SPP Bulanan</option>
            <option value="Daftar Ulang" <?= $filterJenis === 'Daftar Ulang' ? 'selected' : '' ?>>Daftar Ulang</option>
          </select>
        </div>
        <div class="ku-toolbar-right">
          <button type="submit" class="ku-btn ku-btn-primary">Filter</button>
          <a href="/kas-unit/rekap/export-excel?tanggal=<?= urlencode($filterTanggal) ?>&metode=<?= urlencode($filterMetode) ?>&jenis=<?= urlencode($filterJenis) ?>" class="ku-btn ku-btn-sm ku-btn-navy">Export Excel</a>
          <?php if ($filterTanggal || $filterMetode || $filterJenis): ?>
          <a href="/kas-unit/rekap" class="ku-btn ku-btn-ghost">Reset</a>
          <?php endif; ?>
        </div>
      </form>

      <div class="ku-stats" style="grid-template-columns:repeat(3,1fr)">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Tunai</div>
            <div class="ku-stat-value">Rp <?= number_format($totalPerMetode['tunai'], 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-blue" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Transfer</div>
            <div class="ku-stat-value" style="color:#2563EB">Rp <?= number_format($totalPerMetode['transfer'], 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-navy" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Grand Total</div>
            <div class="ku-stat-value">Rp <?= number_format($grandTotal, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <?php if (!empty($rekap)): ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-bottom:20px">
        <?php foreach ($rekap as $jenis => $r): ?>
        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <div>
              <h3><?= esc($jenis) ?></h3>
              <div class="ku-card-sub"><?= $r['count'] ?> pembayaran</div>
            </div>
            <div style="font-size:18px;font-weight:700;color:var(--ku-green)" class="money">Rp <?= number_format($r['total'], 0, ',', '.') ?></div>
          </div>
          <div class="ku-card-body" style="padding:16px 20px">
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--ku-slate-100)">
              <span style="font-size:13px;color:var(--ku-slate-600);display:flex;align-items:center;gap:6px">
                <span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block"></span> Tunai
              </span>
              <span style="font-size:14px;font-weight:600;color:var(--ku-green)" class="money">Rp <?= number_format($r['tunai'], 0, ',', '.') ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0">
              <span style="font-size:13px;color:var(--ku-slate-600);display:flex;align-items:center;gap:6px">
                <span style="width:8px;height:8px;border-radius:50%;background:#2563EB;display:inline-block"></span> Transfer
              </span>
              <span style="font-size:14px;font-weight:600;color:#2563EB" class="money">Rp <?= number_format($r['transfer'], 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="ku-card" style="text-align:center;padding:40px;color:var(--ku-slate-400)">
        <p style="font-size:14px;font-weight:600;margin-bottom:4px">Belum ada pembayaran</p>
        <p style="font-size:12px">Data pembayaran tagihan akan muncul di sini</p>
      </div>
      <?php endif; ?>

      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Detail Pembayaran</h3>
            <div class="ku-card-sub"><?= count($pembayaran) ?> transaksi</div>
          </div>
        </div>
        <div class="ku-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
          <table class="ku-table">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>Tanggal</th>
                <th>Siswa</th>
                <th>Kelas</th>
                <th>Tagihan</th>
                <th>Metode</th>
                <th style="text-align:right">Nominal</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($pembayaran)): ?>
                <?php $no = 1; foreach ($pembayaran as $p): ?>
                <tr>
                  <td class="ku-td-num"><?= $no++ ?></td>
                  <td>
                    <?php $ca = $p['created_at'] ?? ''; ?>
                    <div style="font-size:13px;font-weight:500;color:var(--ku-slate-700)"><?= $ca ? date('d M Y', strtotime($ca)) : '-' ?></div>
                    <div style="font-size:11px;color:var(--ku-slate-400)"><?= $ca ? date('H:i', strtotime($ca)) : '-' ?></div>
                  </td>
                  <td style="font-weight:500;color:var(--ku-slate-700)"><?= esc($p['nama_siswa'] ?? '-') ?></td>
                  <td><?= esc($p['nama_kelas'] ?? '-') ?></td>
                  <td><span class="ku-badge ku-badge-info"><?= esc($p['jenis_tagihan']) ?></span></td>
                  <td>
                    <?php if ($p['metode'] === 'transfer'): ?>
                    <span class="ku-badge ku-badge-info">Transfer</span>
                    <?php else: ?>
                    <span class="ku-badge ku-badge-green">Tunai</span>
                    <?php endif; ?>
                  </td>
                  <td class="ku-td-jumlah plus">+ Rp <?= number_format((float)$p['nominal_dibayar'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="7"><div class="ku-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg><p>Tidak ada data pembayaran</p></div></td></tr>
              <?php endif; ?>
            </tbody>
            <?php if (!empty($pembayaran)): ?>
            <tfoot>
              <tr style="font-weight:700;border-top:2px solid var(--ku-slate-200)">
                <td colspan="6" style="padding:11px 12px;font-size:13px">Total</td>
                <td class="ku-td-jumlah plus" style="font-size:14px">Rp <?= number_format($grandTotal, 0, ',', '.') ?></td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
