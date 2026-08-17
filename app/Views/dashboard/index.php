<?php
  $pageTitle = 'Dashboard Keuangan';
  $pageDesc = 'Ringkasan aktivitas keuangan sekolah';
  $totalSaldoTabungan = isset($totalSaldoTabungan) ? number_format($totalSaldoTabungan, 0, ',', '.') : '0';
  $totalSppTertagih = isset($totalSppTertagih) ? $totalSppTertagih : 0;
  $totalSppTertagihFmt = number_format((float)$totalSppTertagih, 0, ',', '.');
  $totalBayarHariIni = isset($totalBayarHariIni) ? number_format($totalBayarHariIni, 0, ',', '.') : '0';
  $jumlahBelumLunas = $jumlahBelumLunas ?? 0;
  $sppBelumLunas = $sppBelumLunas ?? 0;
  $duBelumLunas = $duBelumLunas ?? 0;
  $rekeningAktif = $rekeningAktif ?? 0;
  $totalSppTarget = $totalSppTarget ?? 0;
  $duTarget = $duTarget ?? 0;
  $duTerbayar = $duTerbayar ?? 0;
  $totalPemasukanBulanIni = isset($totalPemasukanBulanIni) ? number_format($totalPemasukanBulanIni, 0, ',', '.') : '0';
  $chartData = $chartData ?? [];
  $maxChart = $maxChart ?? 1;
  $recentTransactions = $recentTransactions ?? [];
  $perUnitData = $perUnitData ?? [];
  $grandTotalTabungan = $grandTotalTabungan ?? 0;
  $grandTotalSpp = $grandTotalSpp ?? 0;
  $grandTotalAwalTahun = $grandTotalAwalTahun ?? 0;
  $grandTotalAwalTahunTarget = $grandTotalAwalTahunTarget ?? 0;
  $duGlobalPersen = $duGlobalPersen ?? 0;
  $dueSoonBills = $dueSoonBills ?? [];
  $bulanFilter = $bulanFilter ?? date('m');
  $labelUnit = ['ra'=>'RA','sd'=>'SD IT','smp'=>'SMP IT'];
  $fmtPct = function($v) { return $v > 0 && $v < 1 ? '&lt;1' : (int)$v; };
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
            <select name="bulan" class="ku-date-input" style="width:140px;cursor:pointer" onchange="window.location.href='/dashboard?bulan='+this.value">
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($bulanFilter ?? date('m')) == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <span style="font-size:12px;color:var(--ku-slate-400)">Tahun Ajaran <?= esc($tahunAjaran ?? '') ?></span>
        </div>
      </div>

      <div class="ku-stats" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Saldo Tabungan</div>
            <div class="ku-stat-value">Rp <?= $totalSaldoTabungan ?></div>
            <div class="ku-stat-sub"><span class="ku-stat-chip"><?= number_format($rekeningAktif, 0, ',', '.') ?> rekening aktif</span></div>
          </div>
        </div>

        <div class="ku-stat ku-stat-navy" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">SPP &mdash; <?= date('F', mktime(0, 0, 0, (int)($bulanFilter ?? date('m')), 1)) ?></div>
            <?php $sppPct = $totalSppTarget > 0 ? round(($totalSppTertagih / $totalSppTarget) * 100, 1) : 0; ?>
            <div style="margin:6px 0 4px;height:6px;border-radius:3px;background:var(--ku-slate-100);overflow:hidden">
              <div style="height:100%;border-radius:3px;background:linear-gradient(90deg,#059669,#10B981);width:<?= min($sppPct, 100) ?>%"></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:baseline">
              <span style="font-size:18px;font-weight:800;color:var(--ku-slate-900)">Rp <?= $totalSppTertagihFmt ?></span>
              <span style="font-size:11px;color:var(--ku-slate-400)">target Rp <?= number_format($totalSppTarget, 0, ',', '.') ?> (<?= $sppPct ?>%)</span>
            </div>
          </div>
        </div>

        <div class="ku-stat ku-stat-amber" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Daftar Ulang <?= esc($tahunAjaran ?? '') ?></div>
            <?php $duPct = $duTarget > 0 ? round(($duTerbayar / $duTarget) * 100, 1) : 0; ?>
            <div style="margin:6px 0 4px;height:6px;border-radius:3px;background:var(--ku-slate-100);overflow:hidden">
              <div style="height:100%;border-radius:3px;background:linear-gradient(90deg,#D97706,#F59E0B);width:<?= min($duPct, 100) ?>%"></div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:baseline">
              <span style="font-size:18px;font-weight:800;color:var(--ku-slate-900)">Rp <?= number_format($duTerbayar, 0, ',', '.') ?></span>
              <span style="font-size:11px;color:var(--ku-slate-400)">target Rp <?= number_format($duTarget, 0, ',', '.') ?> (<?= $duPct ?>%)</span>
            </div>
          </div>
        </div>

        <div class="ku-stat ku-stat-red" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4M12 17h.01"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Tagihan Belum Lunas</div>
            <div class="ku-stat-value"><?= $jumlahBelumLunas ?> <span style="font-size:13px;font-weight:600;color:var(--ku-slate-400)">tagihan</span></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">SPP <?= $sppBelumLunas ?></span>
              <span class="ku-stat-chip">Daftar Ulang <?= $duBelumLunas ?></span>
              <span class="ku-stat-chip">per <?= date('F Y') ?></span>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($dueSoonBills)): ?>
      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Tagihan Jatuh Tempo (7 Hari)</h3>
            <div class="ku-card-sub">Tagihan yang mendekati tenggat waktu</div>
          </div>
        </div>
        <div class="ku-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Siswa</th>
                <th>Jenis</th>
                <th style="text-align:right">Nominal</th>
                <th>Jatuh Tempo</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dueSoonBills as $b): ?>
              <tr>
                <td><span style="font-weight:600;color:var(--ku-slate-700)"><?= esc($b['nama_siswa']) ?></span></td>
                <td><?= esc($b['jenis_tagihan']) ?></td>
                <td class="ku-td-jumlah plus">Rp <?= number_format($b['nominal'], 0, ',', '.') ?></td>
                <td style="color:<?= $b['jatuh_tempo'] <= date('Y-m-d') ? '#DC2626' : '#D97706' ?>;font-weight:600"><?= date('d M Y', strtotime($b['jatuh_tempo'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px;margin-bottom:20px">
        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <div>
              <h3>Transaksi Terbaru</h3>
              <div class="ku-card-sub">Aktivitas pembayaran & tabungan terkini</div>
            </div>
          </div>
          <div style="padding:12px 16px;display:flex;flex-direction:column;gap:2px">
            <?php if (!empty($recentTransactions)): ?>
            <?php foreach ($recentTransactions as $tx):
              $initial = strtoupper(substr($tx['nama'], 0, 1));
              $colors = ['#0EA5E9','#8B5CF6','#F59E0B','#10B981','#EF4444','#EC4899','#14B8A6','#F97316'];
              $ci = abs(crc32($tx['nama'])) % count($colors);
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:11px 4px;border-bottom:1px solid var(--ku-slate-100)">
              <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0;font-size:14px;background:<?= $colors[$ci] ?>"><?= $initial ?></div>
              <div style="flex:1;min-width:0">
                <div style="font-size:13px;font-weight:600;color:var(--ku-slate-800)"><?= esc($tx['nama']) ?></div>
                <div style="font-size:11px;color:var(--ku-slate-400)">
                  <span class="ku-badge" style="font-size:9px;padding:1px 6px;<?= $tx['tipe'] === 'pembayaran' ? 'background:#EFF6FF;color:#2563EB' : ($tx['tipe'] === 'setor' ? 'background:#ECFDF5;color:#059669' : 'background:#FEF2F2;color:#DC2626') ?>"><?= $tx['tipe'] === 'pembayaran' ? 'Tagihan' : ($tx['tipe'] === 'setor' ? 'Setoran' : 'Tarik') ?></span>
                  <?php $ca = $tx['created_at'] ?? ''; ?><?= esc($tx['detail']) ?> &middot; <?= $ca ? date('d M H:i', strtotime($ca)) : '-' ?>
                </div>
              </div>
              <div style="font-size:13px;font-weight:700;flex-shrink:0;<?= $tx['tipe'] === 'tarik' ? 'color:var(--ku-red)' : 'color:var(--ku-green)' ?>"><?= ($tx['tipe'] === 'tarik' ? '−' : '+') ?>Rp <?= number_format($tx['nominal'], 0, ',', '.') ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="ku-empty" style="padding:48px 20px">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
              <p>Belum ada transaksi</p>
            </div>
            <?php endif; ?>
          </div>
          <a href="/rekap-harian" style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:600;color:var(--ku-slate-600);padding:10px;margin:0 16px 12px;border-radius:9px;border:1px solid var(--ku-slate-200);text-decoration:none;transition:all .15s">
            Lihat semua transaksi
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:13px;height:13px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </a>
        </div>

        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <div>
              <h3>Tabungan Per Bulan</h3>
              <div class="ku-card-sub">Total setoran & penarikan 12 bulan</div>
            </div>
          </div>
          <div class="ku-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
            <table class="ku-table">
              <thead>
                <tr>
                  <th>Unit</th>
                  <th style="text-align:right">Setoran</th>
                  <th style="text-align:right">Penarikan</th>
                  <th style="text-align:right">Saldo</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($chartData as $c): ?>
                <tr>
                  <td style="font-weight:600;color:var(--ku-slate-700)"><?= $c['label'] ?></td>
                  <td class="ku-td-jumlah plus">Rp <?= number_format($c['setoran'], 0, ',', '.') ?></td>
                  <td class="ku-td-jumlah minus">Rp <?= number_format($c['penarikan'], 0, ',', '.') ?></td>
                  <td class="ku-td-jumlah plus">Rp <?= number_format($c['setoran'] - $c['penarikan'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <a href="/tabungan" style="display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:600;color:var(--ku-slate-600);padding:10px;margin:0 16px 12px;border-radius:9px;border:1px solid var(--ku-slate-200);text-decoration:none;transition:all .15s">
            Kelola Tabungan
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:13px;height:13px"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
