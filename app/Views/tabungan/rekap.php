<?php
  $pageTitle = 'Rekap Tabungan';
  $pageDesc = 'Rekap setoran & penarikan tabungan';
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
        <form method="GET" action="/tabungan/rekap" class="ku-toolbar-left" style="display:flex;gap:8px;flex-wrap:wrap;margin:0">
          <div class="ku-date-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" name="start" value="<?= esc($filterStart) ?>" class="ku-date-input" onchange="this.form.submit()">
          </div>
          <span style="color:var(--ku-slate-400);font-weight:600;font-size:13px">s/d</span>
          <div class="ku-date-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" name="end" value="<?= esc($filterEnd) ?>" class="ku-date-input" onchange="this.form.submit()">
          </div>
          <select name="tipe_akun" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Semua Akun</option>
            <option value="siswa" <?= $filterTipe === 'siswa' ? 'selected' : '' ?>>Siswa</option>
            <option value="guru" <?= $filterTipe === 'guru' ? 'selected' : '' ?>>Guru</option>
            <option value="nasabah" <?= $filterTipe === 'nasabah' ? 'selected' : '' ?>>Non Civitas</option>
          </select>
          <select name="jenis" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Semua Jenis</option>
            <option value="setor" <?= $filterJenis === 'setor' ? 'selected' : '' ?>>Setoran</option>
            <option value="tarik" <?= $filterJenis === 'tarik' ? 'selected' : '' ?>>Penarikan</option>
          </select>
          <?php if ($filterStart !== date('Y-m-01') || $filterEnd !== date('Y-m-d') || $filterTipe || $filterJenis): ?>
          <a href="/tabungan/rekap" class="ku-btn ku-btn-outline">Reset</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="ku-stats">
        <div class="ku-stat ku-stat-indigo" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Transaksi</div>
            <div class="ku-stat-value"><?= number_format($summary['total_transaksi'] ?? 0, 0, ',', '.') ?></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Akun aktif: <?= number_format($summary['total_akun'] ?? 0, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-green" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Setoran</div>
            <div class="ku-stat-value">Rp <?= number_format($summary['total_setor'] ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M6 19h12"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Penarikan</div>
            <div class="ku-stat-value">Rp <?= number_format($summary['total_tarik'] ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 4v16"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Selisih (Setor - Tarik)</div>
            <div class="ku-stat-value">Rp <?= number_format(($summary['total_setor'] ?? 0) - ($summary['total_tarik'] ?? 0), 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <?php if ($perMetode): ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
        <div class="ku-card">
          <div class="ku-card-header">
            <h3>Per Metode Pembayaran</h3>
          </div>
          <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0">
            <table class="ku-table">
              <thead>
                <tr>
                  <th style="text-align:left">Metode</th>
                  <th style="text-align:right">Jumlah</th>
                  <th style="text-align:right">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($perMetode as $m): ?>
                <tr>
                  <td style="font-weight:600;text-transform:capitalize"><?= esc($m['metode']) ?></td>
                  <td style="text-align:right;color:var(--ku-slate-600)"><?= number_format($m['jumlah'], 0, ',', '.') ?>x</td>
                  <td style="text-align:right;font-weight:600">Rp <?= number_format($m['total'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <div class="ku-card">
          <div class="ku-card-header">
            <h3>Per Tipe Akun</h3>
          </div>
          <div class="ku-table-wrap" style="border:none;box-shadow:none;border-radius:0">
            <table class="ku-table">
              <thead>
                <tr>
                  <th style="text-align:left">Tipe</th>
                  <th style="text-align:right">Jumlah</th>
                  <th style="text-align:right">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($perTipeAkun as $ta): ?>
                <tr>
                  <td style="font-weight:600;text-transform:capitalize"><?= esc($ta['tipe'] === 'siswa' ? 'Siswa' : ($ta['tipe'] === 'guru' ? 'Guru' : 'Non Civitas')) ?></td>
                  <td style="text-align:right;color:var(--ku-slate-600)"><?= number_format($ta['jumlah'], 0, ',', '.') ?>x</td>
                  <td style="text-align:right;font-weight:600">Rp <?= number_format($ta['total'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="ku-table-wrap">
        <div class="ku-table-header" style="padding:14px 16px;margin:0;border-bottom:1px solid var(--ku-slate-100)">
          <div>
            <h3>Detail Transaksi</h3>
            <p><?= number_format(count($rows), 0, ',', '.') ?> transaksi</p>
          </div>
          <div class="ku-table-export">
            <a href="/tabungan/rekap/export-excel?start=<?= urlencode($filterStart) ?>&end=<?= urlencode($filterEnd) ?>" class="ku-btn ku-btn-sm ku-btn-primary">
              Export Per Kelas
            </a>
          </div>
        </div>
        <table class="ku-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>No. Rekening</th>
              <th>Pemilik</th>
              <th style="text-align:center">Tipe</th>
              <th style="text-align:center">Metode</th>
              <th style="text-align:right">Nominal</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
            <tr>
              <td colspan="7">
                <div class="ku-empty">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  <p>Tidak ada transaksi pada periode ini</p>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $r): ?>
            <?php
              $warna = $r['tipe'] === 'setor' ? '#059669' : '#DC2626';
              $lbl = $r['tipe'] === 'setor' ? 'Setoran' : 'Penarikan';
              $metodeLabel = $r['metode'] === 'transfer' ? 'Transfer' : ($r['metode'] === 'tunai' ? 'Tunai' : '-');
              $akunTipeLabel = $r['akun_tipe'] === 'siswa' ? 'Siswa' : ($r['akun_tipe'] === 'guru' ? 'Guru' : 'Non Civitas');
            ?>
            <tr class="<?= $r['tipe'] === 'setor' ? 'ku-row-pemasukan' : 'ku-row-pengeluaran' ?>">
              <td style="white-space:nowrap;color:var(--ku-slate-600)"><?php $ca = $r['created_at'] ?? ''; ?><?= $ca ? date('d/m/Y H:i', strtotime($ca)) : '-' ?></td>
              <td style="font-weight:600;font-size:12px"><?= esc($r['no_rekening']) ?></td>
              <td>
                <?= esc($r['nama_pemilik'] ?? '-') ?>
                <span class="ku-badge ku-badge-slate" style="margin-left:6px"><?= $akunTipeLabel ?></span>
              </td>
              <td style="text-align:center">
                <span class="ku-badge <?= $r['tipe'] === 'setor' ? 'ku-badge-green' : 'ku-badge-red' ?>"><?= $lbl ?></span>
              </td>
              <td style="text-align:center;font-size:12px;color:var(--ku-slate-600)"><?= $metodeLabel ?></td>
              <td class="ku-td-jumlah <?= $r['tipe'] === 'setor' ? 'plus' : 'minus' ?>">
                <span class="ku-jumlah-sign"><?= $r['tipe'] === 'setor' ? '+' : '−' ?></span>
                Rp <?= number_format($r['nominal'], 0, ',', '.') ?>
              </td>
              <td style="font-size:12px;color:var(--ku-slate-500);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= esc($r['catatan'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
