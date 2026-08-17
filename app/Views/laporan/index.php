<?php
  $pageTitle = 'Laporan Keuangan';
  $pageDesc = 'Analisis arus kas dan posisi keuangan sekolah';
  $dari = $dari ?? date('Y-01-01');
  $sampai = $sampai ?? date('Y-m-d');
  $jenis = $jenis ?? '';
  $isGlobal = $isGlobal ?? false;
  $unit = $unit ?? '';
  $unitOptions = $unitOptions ?? ['ra' => 'RA', 'sd' => 'SD IT', 'smp' => 'SMP IT'];
  $unitParam = $unit ? '&unit=' . $unit : '';
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
            <input type="date" name="dari" value="<?= $dari ?>" class="ku-date-input" onchange="window.location.href='/laporan?dari='+this.value+'&sampai=<?= $sampai ?><?= $jenis ? '&jenis='.$jenis : '' ?><?= $unitParam ?>'">
          </div>
          <span style="color:var(--ku-slate-400);font-weight:500">&mdash;</span>
          <div class="ku-date-group">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" name="sampai" value="<?= $sampai ?>" class="ku-date-input" onchange="window.location.href='/laporan?dari=<?= $dari ?>&sampai='+this.value+'<?= $jenis ? '&jenis='.$jenis : '' ?><?= $unitParam ?>'">
          </div>
          <select name="jenis" class="ku-filter-select" onchange="window.location.href='/laporan?dari=<?= $dari ?>&sampai=<?= $sampai ?>&jenis='+this.value+'<?= $unitParam ?>'">
            <option value="">Jenis: Semua Akun</option>
            <option value="pemasukan" <?= $jenis === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
            <option value="pengeluaran" <?= $jenis === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
          </select>
          <?php if ($isGlobal): ?>
          <select name="unit" class="ku-filter-select" onchange="window.location.href='/laporan?dari=<?= $dari ?>&sampai=<?= $sampai ?><?= $jenis ? '&jenis='.$jenis : '' ?>&unit='+this.value">
            <option value="">Unit: Semua</option>
            <?php foreach ($unitOptions as $uk => $uv): ?>
            <option value="<?= $uk ?>" <?= $unit === $uk ? 'selected' : '' ?>><?= $uv ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
        <div class="ku-toolbar-right">
          <span style="font-size:12px;color:var(--ku-slate-400)">Periode <?= date('d M Y', strtotime($dari)) ?> &ndash; <?= date('d M Y', strtotime($sampai)) ?></span>
          <a href="/laporan/export-csv?dari=<?= $dari ?>&sampai=<?= $sampai ?><?= $jenis ? '&jenis=' . $jenis : '' ?><?= $unitParam ?>" class="ku-btn ku-btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            Ekspor CSV
          </a>
          <a href="/laporan/export-csv?dari=<?= $dari ?>&sampai=<?= $sampai ?>&jenis=pemasukan<?= $unitParam ?>" class="ku-btn ku-btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
            Ekspor Pemasukan
          </a>
        </div>
      </div>

      <div class="ku-stats" style="grid-template-columns:repeat(4,1fr)">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Pemasukan</div>
            <div class="ku-stat-value">Rp <?= number_format($totalPemasukan ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Pengeluaran</div>
            <div class="ku-stat-value">Rp <?= number_format($totalPengeluaran ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-navy" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Saldo Bersih</div>
            <div class="ku-stat-value">Rp <?= number_format($saldoBersih ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Rasio Penagihan SPP</div>
            <div class="ku-stat-value"><?= $rasioPenagihan ?? 0 ?><span style="font-size:14px;font-weight:600;color:var(--ku-slate-400)">%</span></div>
          </div>
        </div>
      </div>

<?php
$maxVal = max(max($monthlyIncome), max($monthlyExpense));
$maxVal = $maxVal > 0 ? $maxVal : 1;
$chartW = 920;
$chartH = 260;
$padL = 50;
$padR = 30;
$padT = 20;
$padB = 40;
$plotW = $chartW - $padL - $padR;
$plotH = $chartH - $padT - $padB;

$months = range(1, 12);
$points = [];
$nMonths = 0;
foreach ($months as $m) {
    $inc = $monthlyIncome[$m] ?? 0;
    $exp = $monthlyExpense[$m] ?? 0;
    if ($inc > 0 || $exp > 0) $nMonths++;
}

$stepX = $nMonths > 1 ? $plotW / ($nMonths - 1) : $plotW;
$incPoints = [];
$expPoints = [];
$labelX = [];
$idx = 0;
foreach ($months as $m) {
    $inc = $monthlyIncome[$m] ?? 0;
    $exp = $monthlyExpense[$m] ?? 0;
    if ($inc == 0 && $exp == 0) continue;
    $x = $padL + $idx * $stepX;
    $yInc = $padT + $plotH - ($inc / $maxVal) * $plotH;
    $yExp = $padT + $plotH - ($exp / $maxVal) * $plotH;
    $incPoints[] = "$x,$yInc";
    $expPoints[] = "$x,$yExp";
    $labelX[] = ['x' => $x, 'label' => $bulanLabels[$m - 1]];
    $idx++;
}

$incPolyline = implode(' ', $incPoints);
$expPolyline = implode(' ', $expPoints);

// Area fill: need to close back to baseline
$firstInc = $incPoints[0] ?? "{$padL}," . ($padT + $plotH);
$lastInc = $incPoints[count($incPoints) - 1] ?? "{$padL}," . ($padT + $plotH);
$incArea = $incPolyline . " " . explode(',', $lastInc)[0] . "," . ($padT + $plotH) . " " . explode(',', $firstInc)[0] . "," . ($padT + $plotH);

// Y-axis grid lines (5 lines)
$gridLines = 4;
$yStep = $plotH / $gridLines;
$yLabels = [];
for ($i = 0; $i <= $gridLines; $i++) {
    $y = $padT + $i * $yStep;
    $val = $maxVal - ($i / $gridLines) * $maxVal;
    $yLabels[] = ['y' => $y, 'label' => 'Rp' . number_format($val, 0, ',', '.')];
}
?>
      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Tren Pemasukan vs Pengeluaran</h3>
            <div class="ku-card-sub">Perbandingan bulanan, <?= date('M Y', strtotime($dari)) ?> &ndash; <?= date('M Y', strtotime($sampai)) ?></div>
          </div>
          <div style="display:flex;gap:14px">
            <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--ku-slate-500)"><span style="width:8px;height:8px;border-radius:50%;background:#10B981;display:inline-block"></span> Pemasukan</div>
            <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--ku-slate-500)"><span style="width:8px;height:8px;border-radius:50%;background:#0F172A;display:inline-block"></span> Pengeluaran</div>
          </div>
        </div>
        <div class="ku-card-body">
          <svg style="width:100%;height:auto" viewBox="0 0 <?= $chartW ?> <?= $chartH ?>" xmlns="http://www.w3.org/2000/svg">
            <?php foreach ($yLabels as $yl): ?>
            <line x1="<?= $padL ?>" y1="<?= $yl['y'] ?>" x2="<?= $chartW - $padR ?>" y2="<?= $yl['y'] ?>" stroke="<?= $yl['y'] == $padT + $plotH ? '#E5E9F0' : '#F1F5F9' ?>" stroke-width="1"/>
            <?php endforeach; ?>
            <polygon points="<?= $incArea ?>" fill="#10B981" opacity="0.08"/>
            <polyline points="<?= $incPolyline ?>" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="<?= $expPolyline ?>" fill="none" stroke="#0F172A" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <?php foreach ($incPoints as $pt): list($cx, $cy) = explode(',', $pt); ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="4.5" fill="#10B981"/>
            <?php endforeach; ?>
            <?php foreach ($expPoints as $pt): list($cx, $cy) = explode(',', $pt); ?>
            <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="4" fill="#0F172A"/>
            <?php endforeach; ?>
            <?php foreach ($labelX as $lx): ?>
            <text x="<?= $lx['x'] ?>" y="<?= $chartH - 10 ?>" font-size="11" fill="#94A3B8" text-anchor="middle"><?= $lx['label'] ?></text>
            <?php endforeach; ?>
          </svg>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px">
<?php
$donutColors = ['#10B981', '#0F172A', '#F59E0B', '#3B82F6', '#8B5CF6', '#EC4899'];
$totalCat = array_sum(array_column($kategoriPemasukan, 'total'));
$totalCat = $totalCat > 0 ? $totalCat : 1;
$circumference = 2 * pi() * 60;
$offset = 0;
$donutSegments = [];
foreach ($kategoriPemasukan as $i => $cat) {
    $pct = $cat['total'] / $totalCat;
    $len = $circumference * $pct;
    $donutSegments[] = [
        'color' => $donutColors[$i % count($donutColors)],
        'dasharray' => round($len, 1) . ' ' . round($circumference - $len, 1),
        'offset' => -$offset,
        'label' => $cat['jenis_tagihan'],
        'pct' => round($pct * 100),
    ];
    $offset += $len;
}
if (empty($donutSegments)) {
    $donutSegments[] = [
        'color' => '#E5E9F0',
        'dasharray' => $circumference . ' ' . $circumference,
        'offset' => 0,
        'label' => 'Belum ada data',
        'pct' => 0,
    ];
}
?>
        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <div>
              <h3>Komposisi Sumber Pemasukan</h3>
              <div class="ku-card-sub">Periode berjalan</div>
            </div>
          </div>
          <div class="ku-card-body" style="display:flex;gap:24px;align-items:center">
            <svg width="160" height="160" viewBox="0 0 160 160">
              <circle cx="80" cy="80" r="60" fill="none" stroke="#E5E9F0" stroke-width="20"/>
              <?php foreach ($donutSegments as $seg): ?>
              <circle cx="80" cy="80" r="60" fill="none" stroke="<?= $seg['color'] ?>" stroke-width="20" stroke-dasharray="<?= $seg['dasharray'] ?>" stroke-dashoffset="<?= $seg['offset'] ?>" transform="rotate(-90 80 80)"/>
              <?php endforeach; ?>
              <text x="80" y="76" text-anchor="middle" font-size="20" font-weight="700" fill="#0F172A">Rp<?= number_format($totalPemasukanAll ?? 0, 0, ',', '.') ?></text>
              <text x="80" y="94" text-anchor="middle" font-size="10.5" fill="#94A3B8">Total Masuk</text>
            </svg>
            <div style="flex:1;display:flex;flex-direction:column;gap:10px">
              <?php foreach ($donutSegments as $seg): ?>
              <div style="display:flex;align-items:center;gap:10px"><span style="width:10px;height:10px;border-radius:3px;background:<?= $seg['color'] ?>;flex-shrink:0"></span><span style="font-size:12px;color:var(--ku-slate-600);flex:1"><?= esc($seg['label']) ?></span><span style="font-size:13px;font-weight:700;color:var(--ku-slate-800)"><?= $seg['pct'] ?>%</span></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <div>
              <h3>Rincian Saldo per Kategori</h3>
              <div class="ku-card-sub">Per <?= date('d M Y') ?></div>
            </div>
          </div>
          <div class="ku-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
            <table class="ku-table">
              <thead><tr><th>Kategori</th><th style="text-align:right">Saldo</th></tr></thead>
              <tbody>
                <tr><td>Kas SPP Bulan Ini</td><td class="ku-td-jumlah plus">Rp <?= number_format($totalPemasukanAll ?? 0, 0, ',', '.') ?></td></tr>
                <tr><td>Dana Operasional</td><td class="ku-td-jumlah">Rp 0</td></tr>
                <tr><td>Tunggakan Tercatat</td><td class="ku-td-jumlah minus">Rp <?= number_format($totalBelumBayar ?? 0, 0, ',', '.') ?></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Riwayat Transaksi</h3>
            <div class="ku-card-sub">20 transaksi terbaru periode <?= date('d M Y', strtotime($dari)) ?> &ndash; <?= date('d M Y', strtotime($sampai)) ?></div>
          </div>
        </div>
        <div class="ku-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Deskripsi</th>
                <th style="text-align:right">Nominal</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($transaksiList)): ?>
                <?php foreach ($transaksiList as $tx): ?>
                  <tr class="<?= $tx['tipe'] === 'Pemasukan' ? 'ku-row-pemasukan' : 'ku-row-pengeluaran' ?>">
                    <td><?= date('d/m/Y H:i', strtotime($tx['tanggal'])) ?></td>
                    <td>
                      <span class="ku-badge <?= $tx['tipe'] === 'Pemasukan' ? 'ku-badge-green' : 'ku-badge-red' ?>">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><?= $tx['tipe'] === 'Pemasukan' ? '<path d="M12 19V5M5 12l7-7 7 7"/>' : '<path d="M12 5v14M5 12l7 7 7-7"/>' ?></svg>
                        <?= $tx['tipe'] ?>
                      </span>
                    </td>
                    <td>
                      <div style="font-weight:500;color:var(--ku-slate-700)"><?= $tx['deskripsi'] ?></div>
                      <?php if ($tx['detail'] && $tx['detail'] !== '-'): ?>
                        <div style="font-size:12px;color:var(--ku-slate-400)"><?= $tx['detail'] ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="ku-td-jumlah <?= $tx['nominal'] < 0 ? 'minus' : 'plus' ?>">
                      <span class="ku-jumlah-sign"><?= $tx['nominal'] < 0 ? '−' : '+' ?></span>
                      Rp <?= number_format(abs($tx['nominal']), 0, ',', '.') ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="4"><div class="ku-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg><p>Belum ada transaksi</p><span>Pada periode ini</span></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div style="text-align:center;padding:20px 0 10px;font-size:11px;color:var(--ku-slate-400)">Laporan ini dihasilkan otomatis oleh sistem &middot; Dapat diaudit kapan saja</div>
    </div>
  </div>
</div>
<?= view('layout/footer') ?>
