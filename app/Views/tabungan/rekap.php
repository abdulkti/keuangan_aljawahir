<?php
  $pageTitle = 'Rekap Tabungan';
  $pageDesc = 'Rekap setoran & penarikan tabungan per kelas';
?>
<?= view('layout/header') ?>
<style>
  .rekap-wrap{overflow-x:auto}
  .rekap-table{width:100%;border-collapse:collapse;font-size:12px;min-width:max-content}
  .rekap-table th,.rekap-table td{padding:6px 10px;border:1px solid var(--ku-slate-200);white-space:nowrap}
  .rekap-table thead th{background:var(--ku-slate-600);color:#fff;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.03em;text-align:center;position:sticky;top:0;z-index:2}
  .rekap-table thead th.sub{background:var(--ku-slate-500);font-size:10px;letter-spacing:0}
  .rekap-table thead th.date-hdr{background:var(--ku-slate-700)}
  .rekap-table tbody td{color:var(--ku-slate-700)}
  .rekap-table tbody td.num{text-align:right;font-variant-numeric:tabular-nums}
  .rekap-table tbody td.center{text-align:center}
  .rekap-table tbody tr:nth-child(even){background:var(--ku-slate-50)}
  .rekap-table tbody tr:hover{background:rgba(99,102,241,.06)}
  .rekap-table tfoot td{background:var(--ku-slate-100);font-weight:700;color:var(--ku-slate-800)}
  .rekap-table .setor{color:var(--ku-emerald-600)}
  .rekap-table .tarik{color:var(--ku-red-600)}
  .kelas-header{display:flex;align-items:center;gap:10px;margin:28px 0 12px;padding:12px 16px;background:linear-gradient(135deg,var(--ku-indigo-50),var(--ku-slate-50));border-left:4px solid var(--ku-indigo-500);border-radius:0 10px 10px 0}
  .kelas-header h3{margin:0;font-size:15px;color:var(--ku-navy-900)}
  .kelas-header .badge{font-size:11px;padding:3px 10px;border-radius:100px;background:var(--ku-indigo-100);color:var(--ku-indigo-700);font-weight:600}
  .kelas-header .count{font-size:12px;color:var(--ku-slate-500);margin-left:auto}
  .freeze-col{position:sticky;left:0;z-index:1;background:inherit}
  .freeze-col-2{position:sticky;left:50px;z-index:1;background:inherit}
  .freeze-col-3{position:sticky;left:180px;z-index:1;background:inherit}
  .rekap-table thead th.freeze-col,.rekap-table thead th.freeze-col-2,.rekap-table thead th.freeze-col-3{z-index:3}
</style>
<div class="app-shell">
  <?= view('layout/sidebar', ['user' => $user ?? []]) ?>
  <div class="main-area">
    <?= view('layout/topbar', ['user' => $user ?? [], 'pageTitle' => $pageTitle, 'pageDesc' => $pageDesc]) ?>
    <div class="content">

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
          <?php if ($filterStart !== date('Y-m-01') || $filterEnd !== date('Y-m-d')): ?>
          <a href="/tabungan/rekap" class="ku-btn ku-btn-outline">Reset</a>
          <?php endif; ?>
          <div style="margin-left:auto">
            <a href="/tabungan/rekap/export-excel?start=<?= urlencode($filterStart) ?>&end=<?= urlencode($filterEnd) ?>" class="ku-btn ku-btn-sm ku-btn-primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Export Per Kelas
            </a>
          </div>
        </form>
      </div>

      <?php
        $grandSiswa = 0;
        $grandSetor = 0;
        $grandTarik = 0;
        foreach ($classGroups as $cg) {
          foreach ($cg['students'] as $s) {
            $grandSiswa++;
            $tabId = $s['tabungan_id'] ? (int)$s['tabungan_id'] : null;
            $ob = $tabId ? ($openingBalances[$tabId] ?? 0) : 0;
            foreach ($allDates as $d) {
              if ($tabId && isset($txByAkun[$tabId][$d])) {
                $grandSetor += $txByAkun[$tabId][$d]['setor'];
                $grandTarik += $txByAkun[$tabId][$d]['tarik'];
              }
            }
          }
        }
      ?>

      <div class="ku-stats">
        <div class="ku-stat ku-stat-indigo" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Siswa</div>
            <div class="ku-stat-value"><?= count($classGroups) ?></div>
            <div class="ku-stat-sub"><span class="ku-stat-chip"><?= $grandSiswa ?> siswa</span></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-green" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Setoran</div>
            <div class="ku-stat-value">Rp <?= number_format($grandSetor, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M6 19h12"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Penarikan</div>
            <div class="ku-stat-value">Rp <?= number_format($grandTarik, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 4v16"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Saldo Bersih</div>
            <div class="ku-stat-value">Rp <?= number_format($grandSetor - $grandTarik, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <?php if (empty($classGroups)): ?>
        <div class="ku-card">
          <div class="ku-empty" style="padding:60px 20px">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            <p>Tidak ada data siswa pada periode ini</p>
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($classGroups as $kelasId => $cg): ?>
          <?php
            $students = $cg['students'];
            $dateCount = count($allDates);
            $sumSetor = 0;
            $sumTarik = 0;
            $sumSaldoAwal = 0;
            $sumSaldoAkhir = 0;
            $dateTotals = [];
            foreach ($allDates as $d) $dateTotals[$d] = ['setor' => 0, 'tarik' => 0];
            foreach ($students as $s) {
              $tabId = $s['tabungan_id'] ? (int)$s['tabungan_id'] : null;
              foreach ($allDates as $d) {
                if ($tabId && isset($txByAkun[$tabId][$d])) {
                  $dateTotals[$d]['setor'] += $txByAkun[$tabId][$d]['setor'];
                  $dateTotals[$d]['tarik'] += $txByAkun[$tabId][$d]['tarik'];
                  $sumSetor += $txByAkun[$tabId][$d]['setor'];
                  $sumTarik += $txByAkun[$tabId][$d]['tarik'];
                }
              }
              $ob = $tabId ? ($openingBalances[$tabId] ?? 0) : 0;
              $sumSaldoAwal += $ob;
            }
            $sumSaldoAkhir = $sumSaldoAwal + $sumSetor - $sumTarik;
          ?>
          <div class="kelas-header">
            <h3><?= esc($cg['nama']) ?></h3>
            <span class="badge"><?= strtoupper(esc($cg['sekolah'])) ?></span>
            <span class="count"><?= count($students) ?> siswa  |  Periode: <?= date('d/m', strtotime($filterStart)) ?> - <?= date('d/m/Y', strtotime($filterEnd)) ?></span>
          </div>
          <div class="ku-card" style="margin-bottom:8px">
            <div class="rekap-wrap">
              <table class="rekap-table">
                <thead>
                  <tr>
                    <th class="freeze-col" rowspan="2" style="min-width:36px">No</th>
                    <th class="freeze-col-2" rowspan="2" style="min-width:160px">Nama</th>
                    <th class="freeze-col-3" rowspan="2" style="min-width:100px">NIS</th>
                    <th rowspan="2" style="min-width:90px">Saldo Awal</th>
                    <?php foreach ($allDates as $d): ?>
                    <th class="date-hdr" colspan="2"><?= date('d/m', strtotime($d)) ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2" style="min-width:90px">Total D</th>
                    <th rowspan="2" style="min-width:90px">Total K</th>
                    <th rowspan="2" style="min-width:100px">Saldo Akhir</th>
                  </tr>
                  <tr>
                    <?php foreach ($allDates as $d): ?>
                    <th class="sub" style="min-width:60px">D</th>
                    <th class="sub" style="min-width:60px">K</th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 0; foreach ($students as $s): ?>
                  <?php
                    $no++;
                    $tabId = $s['tabungan_id'] ? (int)$s['tabungan_id'] : null;
                    $saldoAwal = $tabId ? ($openingBalances[$tabId] ?? 0) : 0;
                    $totalD = 0;
                    $totalK = 0;
                  ?>
                  <tr>
                    <td class="center freeze-col"><?= $no ?></td>
                    <td class="freeze-col-2" style="font-weight:600"><?= esc($s['nama']) ?></td>
                    <td class="freeze-col-3" style="color:var(--ku-slate-500);font-size:11px"><?= esc($s['nis'] ?? '-') ?></td>
                    <td class="num" style="font-weight:600"><?= $saldoAwal > 0 ? 'Rp ' . number_format($saldoAwal, 0, ',', '.') : '<span style="color:var(--ku-slate-300)">-</span>' ?></td>
                    <?php foreach ($allDates as $d): ?>
                      <?php
                        $setor = ($tabId && isset($txByAkun[$tabId][$d])) ? $txByAkun[$tabId][$d]['setor'] : 0;
                        $tarik = ($tabId && isset($txByAkun[$tabId][$d])) ? $txByAkun[$tabId][$d]['tarik'] : 0;
                        $totalD += $setor;
                        $totalK += $tarik;
                      ?>
                      <td class="num setor" style="font-size:11px"><?= $setor > 0 ? number_format($setor, 0, ',', '.') : '' ?></td>
                      <td class="num tarik" style="font-size:11px"><?= $tarik > 0 ? number_format($tarik, 0, ',', '.') : '' ?></td>
                    <?php endforeach; ?>
                    <td class="num" style="font-weight:600;color:var(--ku-emerald-600);font-size:11px"><?= $totalD > 0 ? number_format($totalD, 0, ',', '.') : '' ?></td>
                    <td class="num" style="font-weight:600;color:var(--ku-red-600);font-size:11px"><?= $totalK > 0 ? number_format($totalK, 0, ',', '.') : '' ?></td>
                    <td class="num" style="font-weight:700"><?= 'Rp ' . number_format($saldoAwal + $totalD - $totalK, 0, ',', '.') ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" style="text-align:center;font-weight:700">TOTAL <?= esc($cg['nama']) ?></td>
                    <?php foreach ($allDates as $d): ?>
                    <td class="num" style="font-size:11px"><?= $dateTotals[$d]['setor'] > 0 ? number_format($dateTotals[$d]['setor'], 0, ',', '.') : '' ?></td>
                    <td class="num" style="font-size:11px"><?= $dateTotals[$d]['tarik'] > 0 ? number_format($dateTotals[$d]['tarik'], 0, ',', '.') : '' ?></td>
                    <?php endforeach; ?>
                    <td class="num" style="color:var(--ku-emerald-600)"><?= $sumSetor > 0 ? number_format($sumSetor, 0, ',', '.') : '' ?></td>
                    <td class="num" style="color:var(--ku-red-600)"><?= $sumTarik > 0 ? number_format($sumTarik, 0, ',', '.') : '' ?></td>
                    <td class="num">Rp <?= number_format($sumSaldoAkhir, 0, ',', '.') ?></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
