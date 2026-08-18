<?php
  $pageTitle = 'Rekap Yayasan';
  $pageDesc = 'Rekap keuangan dan THT';
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
        <form method="GET" action="/rekap/yayasan" class="ku-toolbar-left" style="display:flex;gap:8px;flex-wrap:wrap;margin:0">
          <select name="tahun_ajaran" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Semua Tahun Ajaran</option>
            <?php foreach ($thtTahunList as $ta): ?>
            <option value="<?= $ta ?>" <?= ($tahunAjaran ?? '') == $ta ? 'selected' : '' ?>><?= $ta ?></option>
            <?php endforeach; ?>
          </select>
          <select name="bulan" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Semua Bulan</option>
            <?php foreach ($bulanList as $b => $bl): ?>
            <option value="<?= $b ?>" <?= $bulanTerpilih == $b ? 'selected' : '' ?>><?= $bl ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <div class="ku-toolbar-right">
          <a href="/rekap/yayasan?export=keuangan&bulan=<?= $bulanTerpilih ?>&tahun_ajaran=<?= $tahunAjaran ?>" class="ku-btn ku-btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Export Keuangan
          </a>
          <a href="/rekap/yayasan?export=tht&tahun_ajaran=<?= $tahunAjaran ?>&bulan=<?= $bulanTerpilih ?>" class="ku-btn ku-btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Export THT
          </a>
        </div>
      </div>

      <div class="ku-stats">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Pemasukan</div>
            <div class="ku-stat-value">Rp <?= number_format(array_sum(array_column($rekapUnit, 'pemasukan')), 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M6 19h12"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Pengeluaran</div>
            <div class="ku-stat-value">Rp <?= number_format(array_sum(array_column($rekapUnit, 'pengeluaran')), 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-indigo" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Saldo THT</div>
            <div class="ku-stat-value">Rp <?= number_format($grandTotalTHT ?? 0, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip"><?= count($rekapGuru) ?> guru</span>
            </div>
          </div>
        </div>
      </div>

      <div class="ku-card">
        <div class="ku-card-header">
          <h3>Rekap Keuangan per Unit</h3>
        </div>
        <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0">
          <table class="ku-table">
            <thead>
              <tr><th>Unit</th><th style="text-align:right">Pemasukan</th><th style="text-align:right">Pengeluaran</th><th style="text-align:right">Saldo</th></tr>
            </thead>
            <tbody>
              <?php $grandPemasukan = 0; $grandPengeluaran = 0; $grandSaldo = 0; ?>
              <?php if (!empty($rekapUnit)): ?>
                <?php foreach ($rekapUnit as $u): ?>
                  <?php $grandPemasukan += $u['pemasukan']; $grandPengeluaran += $u['pengeluaran']; $grandSaldo += $u['saldo']; ?>
                  <tr>
                    <td><strong><?= esc($u['unit']) ?></strong></td>
                    <td style="text-align:right;color:var(--ku-green)">Rp <?= number_format($u['pemasukan'], 0, ',', '.') ?></td>
                    <td style="text-align:right;color:var(--ku-red)">Rp <?= number_format($u['pengeluaran'], 0, ',', '.') ?></td>
                    <td style="text-align:right;font-weight:700">Rp <?= number_format($u['saldo'], 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr style="background:var(--ku-slate-50);font-weight:700">
                  <td>GRAND TOTAL</td>
                  <td style="text-align:right;color:var(--ku-green)">Rp <?= number_format($grandPemasukan, 0, ',', '.') ?></td>
                  <td style="text-align:right;color:var(--ku-red)">Rp <?= number_format($grandPengeluaran, 0, ',', '.') ?></td>
                  <td style="text-align:right">Rp <?= number_format($grandSaldo, 0, ',', '.') ?></td>
                </tr>
              <?php else: ?>
                <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--ku-slate-400)">Belum ada data</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="ku-card">
        <div class="ku-card-header">
          <h3>Rekap THT per Tahun Ajaran</h3>
        </div>
        <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0">
          <table class="ku-table">
            <thead>
              <tr><th>Tahun</th><th style="text-align:right">Total Setoran</th><th style="text-align:right">Total Penarikan</th><th style="text-align:right">Saldo</th></tr>
            </thead>
            <tbody>
              <?php $grandSetoran = 0; $grandPenarikan = 0; $grandSaldoTHT = 0; ?>
              <?php if (!empty($rekapTahun)): ?>
                <?php foreach ($rekapTahun as $t): ?>
                  <?php $grandSetoran += $t['total_setoran']; $grandPenarikan += $t['total_penarikan']; $grandSaldoTHT += $t['saldo']; ?>
                  <tr>
                    <td><strong><?= $t['tahun'] ?></strong></td>
                    <td style="text-align:right">Rp <?= number_format($t['total_setoran'], 0, ',', '.') ?></td>
                    <td style="text-align:right">Rp <?= number_format($t['total_penarikan'], 0, ',', '.') ?></td>
                    <td style="text-align:right;font-weight:700">Rp <?= number_format($t['saldo'], 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr style="background:var(--ku-slate-50);font-weight:700">
                  <td>GRAND TOTAL</td>
                  <td style="text-align:right">Rp <?= number_format($grandSetoran, 0, ',', '.') ?></td>
                  <td style="text-align:right">Rp <?= number_format($grandPenarikan, 0, ',', '.') ?></td>
                  <td style="text-align:right">Rp <?= number_format($grandSaldoTHT, 0, ',', '.') ?></td>
                </tr>
              <?php else: ?>
                <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--ku-slate-400)">Belum ada data</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="ku-card">
        <div class="ku-card-header">
          <h3>Rekap THT per Guru</h3>
        </div>
        <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0">
          <table class="ku-table">
            <thead>
              <tr><th>Nama Guru</th><th>Unit</th><th style="text-align:right">Total Setoran</th><th style="text-align:right">Total Penarikan</th><th style="text-align:right">Saldo</th></tr>
            </thead>
            <tbody>
              <?php if (!empty($rekapGuru)): ?>
                <?php foreach ($rekapGuru as $g): ?>
                  <tr>
                    <td><?= esc($g['nama']) ?></td>
                    <td><?= esc($g['unit']) ?></td>
                    <td style="text-align:right">Rp <?= number_format($g['total_setoran'], 0, ',', '.') ?></td>
                    <td style="text-align:right">Rp <?= number_format($g['total_penarikan'], 0, ',', '.') ?></td>
                    <td style="text-align:right;font-weight:700">Rp <?= number_format($g['saldo'], 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--ku-slate-400)">Belum ada data guru</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
