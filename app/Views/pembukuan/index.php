<?php
  $pageTitle = 'Pembukuan';
  $pageDesc = 'Rekap tagihan per kelas dan status pembayaran';
  $buku = $buku ?? 'semua';
  $kelasData = $kelasData ?? [];
  $tahunAjaran = $tahunAjaran ?? (date('Y') . '/' . (date('Y')+1));
  $months = $months ?? ['Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'];
?>
<?= view('layout/header') ?>
<style>
.pembukuan-table{ width:100%; border-collapse:collapse; font-size:12px; min-width:1200px; }
.pembukuan-table th,
.pembukuan-table td{ border:1px solid var(--ku-slate-200); padding:5px 7px; text-align:left; white-space:nowrap; }
.pembukuan-table thead th{ background:var(--navy-800); color:#fff; font-weight:600; text-align:center; font-size:11px; position:sticky; top:0; z-index:2; }
.pembukuan-table thead th.sub{ background:var(--slate-600); font-size:10px; }
.pembukuan-table thead th.spp-month{ min-width:50px; }
.pembukuan-table tbody td{ vertical-align:middle; }
.pembukuan-table tbody tr:nth-child(even){ background:var(--ku-slate-50); }
.pembukuan-table tbody tr:hover{ background:var(--ku-slate-100); }
.pembukuan-table .num{ text-align:right; font-variant-numeric:tabular-nums; }
.pembukuan-table .paid{ color:var(--ku-green); font-weight:700; }
.pembukuan-table .unpaid{ color:var(--ku-slate-300); }
.pembukuan-table .total-row{ background:var(--ku-slate-100) !important; font-weight:700; }
.pembukuan-table .total-row td{ border-top:2px solid var(--navy-700); }
.table-scroll{ overflow-x:auto; margin-bottom:24px; }
.class-title{ font-size:16px; font-weight:700; color:var(--ku-slate-800); margin-bottom:12px; padding:10px 14px; background:var(--ku-slate-50); border:1px solid var(--ku-slate-200); border-radius:var(--ku-radius); }
</style>
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
          <form method="GET" action="/pembukuan" style="display:contents">
            <select name="buku" class="ku-filter-select" onchange="this.form.submit()">
              <option value="semua" <?= $buku === 'semua' ? 'selected' : '' ?>>Semua Buku</option>
              <option value="tabungan" <?= $buku === 'tabungan' ? 'selected' : '' ?>>Buku Tabungan</option>
              <option value="tagihan" <?= $buku === 'tagihan' ? 'selected' : '' ?>>Buku Tagihan</option>
            </select>
          </form>
        </div>
        <div class="ku-toolbar-right">
          <?php $unitLabels = ['ra' => 'RA', 'sd' => 'SD', 'smp' => 'SMP']; ?>
          <?php foreach ($availableUnits as $u): ?>
          <a href="/pembukuan/export-excel?sekolah=<?= $u ?>&buku=<?= $buku ?>" class="ku-btn ku-btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
            Export <?= $unitLabels[$u] ?? strtoupper($u) ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="ku-stats" style="grid-template-columns:repeat(4,1fr)">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Pemasukan Kas</div>
            <div class="ku-stat-value">Rp <?= number_format($totalKas ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-amber" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Tertagih Bulan Ini</div>
            <div class="ku-stat-value">Rp <?= number_format($totalTertagih ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Belum Bayar</div>
            <div class="ku-stat-value">Rp <?= number_format($totalBelumBayar ?? 0, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-indigo" style="--delay:0.3s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Semua Tagihan</div>
            <div class="ku-stat-value">Rp <?= number_format(($totalTertagih ?? 0) + ($totalBelumBayar ?? 0), 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <?php if ($buku === 'semua' || $buku === 'tagihan'): ?>
        <?php if (!empty($kelasData)): ?>
          <?php foreach ($kelasData as $kd): ?>
            <?php if (empty($kd['students'])) continue; ?>
            <div class="table-scroll" style="margin-bottom:24px">
              <div class="class-title">KELAS <?= esc($kd['kelas_label']) ?> T.P <?= esc($tahunAjaran ?? date('Y') . '/' . (date('Y')+1)) ?></div>
              <div class="ku-table-wrap">
                <table class="pembukuan-table ku-table">
                  <thead>
                    <tr>
                      <th rowspan="2" style="width:40px">NO</th>
                      <th rowspan="2" style="min-width:180px">NAMA</th>
                      <th rowspan="2" style="min-width:100px">TAGIHAN AWAL TAHUN</th>
                      <th colspan="4" class="sub" style="background:var(--navy-700)">CICILAN</th>
                      <th rowspan="2" style="min-width:90px">TOTAL DIBAYAR</th>
                      <th rowspan="2" style="min-width:70px">SISA</th>
                      <th rowspan="2" style="min-width:100px">KETERANGAN</th>
                      <th rowspan="2" style="min-width:60px">SPP/<br>BULAN</th>
                      <th colspan="12" class="sub spp-month" style="background:var(--navy-700)">BULAN SPP</th>
                      <th rowspan="2" style="min-width:80px">TOTAL SPP</th>
                      <th rowspan="2" style="min-width:130px">STATUS</th>
                    </tr>
                    <tr>
                      <th class="sub" style="min-width:60px;background:var(--slate-600)">1</th>
                      <th class="sub" style="min-width:60px;background:var(--slate-600)">2</th>
                      <th class="sub" style="min-width:60px;background:var(--slate-600)">3</th>
                      <th class="sub" style="min-width:60px;background:var(--slate-600)">4</th>
                      <?php foreach ($months as $m): ?>
                      <th class="sub" style="min-width:42px;background:var(--slate-600)"><?= strtoupper(substr($m,0,3)) ?></th>
                      <?php endforeach; ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $no = 1;
                      $gtAwal = 0; $gtDibayar = 0; $gtSpp = 0;
                    ?>
                    <?php foreach ($kd['students'] as $row): ?>
                    <?php
                      $gtAwal += $row['total_awal_tahun'];
                      $gtDibayar += $row['total_dibayar'];
                      $gtSpp += $row['total_spp'];
                    ?>
                    <tr>
                      <td class="num"><?= $no++ ?></td>
                      <td style="font-weight:500;color:var(--ku-slate-700)"><?= esc($row['nama']) ?></td>
                      <td class="num"><?= $row['total_awal_tahun'] ? number_format($row['total_awal_tahun'],0,',','.') : '' ?></td>
                      <?php for ($c=0; $c<4; $c++): ?>
                        <td class="num"><?= isset($row['cicilan'][$c]) ? number_format($row['cicilan'][$c],0,',','.') : '' ?></td>
                      <?php endfor; ?>
                      <td class="num"><?= $row['total_dibayar'] ? number_format($row['total_dibayar'],0,',','.') : '' ?></td>
                      <td class="num"><?= $row['total_awal_tahun'] ? number_format($row['sisa'],0,',','.') : '' ?></td>
                      <td><?= esc($row['keterangan']) ?></td>
                      <td class="num"><?= $row['spp_per_bulan'] ? number_format($row['spp_per_bulan'],0,',','.') : '' ?></td>
                      <?php for ($m=0; $m<12; $m++): ?>
                        <td class="num" style="text-align:center">
                          <?php if (isset($row['spp_paid_months'][$m])): ?>
                            <span class="paid">&#10003;</span>
                          <?php else: ?>
                            <span class="unpaid">-</span>
                          <?php endif; ?>
                        </td>
                      <?php endfor; ?>
                      <td class="num"><?= $row['total_spp'] ? number_format($row['total_spp'],0,',','.') : '' ?></td>
                      <td class="num" style="font-size:11px;color:<?= $row['status'] === 'pindah' ? '#B45309' : ($row['status'] === 'lulus' ? '#1D4ED8' : 'inherit') ?>"><?= esc($row['status_keterangan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                      <td colspan="2"><strong>TOTAL</strong></td>
                      <td class="num"><?= $gtAwal ? number_format($gtAwal,0,',','.') : '' ?></td>
                      <?php for ($c=0; $c<4; $c++): ?><td></td><?php endfor; ?>
                      <td class="num"><?= $gtDibayar ? number_format($gtDibayar,0,',','.') : '' ?></td>
                      <td class="num"><?= ($gtAwal - $gtDibayar) ? number_format($gtAwal - $gtDibayar,0,',','.') : '' ?></td>
                      <td></td>
                      <td></td>
                      <?php for ($m=0; $m<12; $m++): ?><td></td><?php endfor; ?>
                      <td class="num"><?= $gtSpp ? number_format($gtSpp,0,',','.') : '' ?></td>
                      <td></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="ku-card" style="margin-top:20px">
            <div class="ku-card-header"><div><h3>Rekap Tagihan Per Kelas</h3><div class="ku-card-sub">Belum ada data tagihan</div></div></div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($buku === 'semua' || $buku === 'tabungan'): ?>
      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Buku Tabungan</h3>
            <div class="ku-card-sub">Riwayat setor & tarik tabungan siswa dan guru</div>
          </div>
        </div>
        <div class="ku-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Pemilik</th>
                <th>Jenis</th>
                <th>Tipe</th>
                <th style="text-align:right">Nominal</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($transaksiTabungan)): ?>
                <?php foreach ($transaksiTabungan as $tx): ?>
                  <tr>
                    <td><?php $ca = $tx['created_at'] ?? ''; ?><?= $ca ? date('d/m/Y H:i', strtotime($ca)) : '-' ?></td>
                    <td>
                      <div style="font-weight:500;color:var(--ku-slate-700)"><?= esc($tx['nama_pemilik']) ?></div>
                      <div style="font-size:12px;color:var(--ku-slate-400)"><?= ucfirst($tx['akun_tipe']) ?></div>
                    </td>
                    <td><?= $tx['akun_tipe'] === 'siswa' ? 'Siswa' : 'Guru' ?></td>
                    <td>
                      <span class="ku-badge <?= $tx['tipe'] === 'setor' ? 'ku-badge-green' : 'ku-badge-red' ?>">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><?= $tx['tipe'] === 'setor' ? '<path d="M12 19V5M5 12l7-7 7 7"/>' : '<path d="M12 5v14M5 12l7 7 7-7"/>' ?></svg>
                        <?= ucfirst($tx['tipe']) ?>
                      </span>
                    </td>
                    <td class="ku-td-jumlah <?= $tx['tipe'] === 'tarik' ? 'minus' : 'plus' ?>">
                      <span class="ku-jumlah-sign"><?= $tx['tipe'] === 'tarik' ? '−' : '+' ?></span>
                      Rp <?= number_format($tx['nominal'], 0, ',', '.') ?>
                    </td>
                    <td style="color:var(--ku-slate-500)"><?= esc($tx['catatan'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6"><div class="ku-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg><p>Belum ada transaksi tabungan</p></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

      <div style="text-align:center;padding:20px 0 10px;font-size:11px;color:var(--ku-slate-400)">Pembukuan ini dihasilkan otomatis oleh sistem</div>
    </div>
  </div>
</div>
<?= view('layout/footer') ?>
