<?php
  $pageTitle = 'Dashboard Yayasan';
  $pageDesc = 'Rekap saldo otomatis semua unit';
?>
<?= view('layout/header') ?>
<div class="app-shell">
  <?= view('layout/sidebar', ['user' => $user ?? []]) ?>
  <div class="main-area">
    <?= view('layout/topbar', ['user' => $user ?? [], 'pageTitle' => $pageTitle, 'pageDesc' => $pageDesc]) ?>
    <div class="content">

      <?php
        $totalTabunganGrand = array_sum(array_column($perUnitData ?? [], 'tabungan'));
      ?>
      <div class="ku-stats">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Saldo Yayasan</div>
            <div class="ku-stat-value">Rp <?= number_format($saldoYayasan ?? 0, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Pemasukan: Rp <?= number_format($totalPemasukan ?? 0, 0, ',', '.') ?></span>
              <span class="ku-stat-chip">Pengeluaran: Rp <?= number_format($totalPengeluaran ?? 0, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-navy" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Tabungan</div>
            <div class="ku-stat-value">Rp <?= number_format($totalTabunganGrand, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Gabungan semua unit</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-indigo" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">THT Guru</div>
            <div class="ku-stat-value">Rp <?= number_format($totalSaldoTHT ?? 0, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip"><?= $jumlahGuruTHT ?> guru</span>
              <span class="ku-stat-chip">Setoran: Rp <?= number_format($totalSetoranTHT ?? 0, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Unit</div>
            <div class="ku-stat-value"><?= count($perUnitData) ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">RA · SD IT · SMP IT</span>
            </div>
          </div>
        </div>
      </div>

      <div class="ku-card">
        <div class="ku-card-header">
          <h3>Rekap Saldo per Unit</h3>
        </div>
        <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0">
          <table class="ku-table">
            <thead>
              <tr><th>Unit</th><th style="text-align:right">Tabungan</th><th style="text-align:right">SPP & Daftar Ulang</th><th style="text-align:right">Lainnya</th><th style="text-align:right">Total Saldo</th></tr>
            </thead>
            <tbody>
              <?php $grandTab = 0; $grandSpp = 0; $grandLainnya = 0; $grandAll = 0; ?>
              <?php if (!empty($perUnitData)): ?>
                <?php foreach ($perUnitData as $u): ?>
                  <?php $grandTab += $u['tabungan']; $grandSpp += $u['spp']; $grandLainnya += $u['lainnya']; $grandAll += $u['total']; ?>
                  <tr>
                    <td><strong><?= esc($u['nama']) ?></strong></td>
                    <td style="text-align:right">Rp <?= number_format($u['tabungan'], 0, ',', '.') ?></td>
                    <td style="text-align:right">Rp <?= number_format($u['spp'], 0, ',', '.') ?></td>
                    <td style="text-align:right"><?= $u['lainnya'] > 0 ? 'Rp ' . number_format($u['lainnya'], 0, ',', '.') : '<span style="color:var(--ku-slate-300)">—</span>' ?></td>
                    <td style="text-align:right;color:var(--ku-green);font-weight:700">Rp <?= number_format($u['total'], 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr style="background:var(--ku-slate-50);font-weight:700">
                  <td>GRAND TOTAL</td>
                  <td style="text-align:right">Rp <?= number_format($grandTab, 0, ',', '.') ?></td>
                  <td style="text-align:right">Rp <?= number_format($grandSpp, 0, ',', '.') ?></td>
                  <td style="text-align:right">Rp <?= number_format($grandLainnya, 0, ',', '.') ?></td>
                  <td style="text-align:right;color:var(--ku-green)">Rp <?= number_format($grandAll, 0, ',', '.') ?></td>
                </tr>
              <?php else: ?>
                <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--ku-slate-400)">Belum ada data unit</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px">
        <div class="ku-card">
          <div class="ku-card-header">
            <h3>Pemasukan Yayasan Terbaru</h3>
          </div>
          <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0;font-size:13px">
            <table class="ku-table">
              <thead><tr><th>Tanggal</th><th>Keterangan</th><th style="text-align:right">Jumlah</th></tr></thead>
              <tbody>
                <?php foreach ($recentPemasukan as $p): ?>
                <tr>
                  <td><?= $p['tanggal'] ?></td>
                  <td><?= esc($p['keterangan']) ?></td>
                  <td style="text-align:right;color:var(--ku-green)">Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentPemasukan)): ?><tr><td colspan="3" style="padding:20px;text-align:center;color:var(--ku-slate-400)">Belum ada data</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="ku-card">
          <div class="ku-card-header">
            <h3>Pengeluaran Yayasan Terbaru</h3>
          </div>
          <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0;font-size:13px">
            <table class="ku-table">
              <thead><tr><th>Tanggal</th><th>Keterangan</th><th style="text-align:right">Jumlah</th></tr></thead>
              <tbody>
                <?php foreach ($recentPengeluaran as $p): ?>
                <tr>
                  <td><?= $p['tanggal'] ?></td>
                  <td><?= esc($p['keterangan']) ?></td>
                  <td style="text-align:right;color:var(--ku-red)">Rp <?= number_format($p['jumlah'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentPengeluaran)): ?><tr><td colspan="3" style="padding:20px;text-align:center;color:var(--ku-slate-400)">Belum ada data</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="ku-card">
        <div class="ku-card-header">
          <h3>Saldo THT per Guru</h3>
        </div>
        <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0;font-size:13px">
          <table class="ku-table">
            <thead><tr><th>Nama Guru</th><th>Unit</th><th style="text-align:right">Saldo THT</th></tr></thead>
            <tbody>
              <?php foreach ($thtGuruData as $g): ?>
              <tr>
                <td><?= esc($g['nama']) ?></td>
                <td><?= esc($g['unit']) ?></td>
                <td style="text-align:right;color:var(--ku-green)">Rp <?= number_format($g['saldo'], 0, ',', '.') ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($thtGuruData)): ?><tr><td colspan="3" style="padding:20px;text-align:center;color:var(--ku-slate-400)">Belum ada data guru</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
