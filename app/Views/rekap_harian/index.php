<?php
  $pageTitle = 'Rekap Harian';
  $pageDesc = 'Rekap pemasukan & pengeluaran kas harian';
  $tanggal = $tanggal ?? date('Y-m-d');
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
            <input type="date" name="tanggal" value="<?= $tanggal ?>" class="ku-date-input" onchange="window.location.href='/rekap-harian?tanggal='+this.value">
          </div>
          <?php if (($user['role'] ?? '') === 'superadmin'): ?>
          <select name="unit" class="ku-filter-select" onchange="window.location.href='/rekap-harian?tanggal=<?= $tanggal ?>&unit='+this.value">
            <option value="">Semua Unit</option>
            <?php foreach ($unitList as $u): ?>
            <option value="<?= esc($u['nama']) ?>" <?= ($filterUnit ?? '') === $u['nama'] ? 'selected' : '' ?>><?= esc($u['nama']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php elseif (in_array($user['role'] ?? '', ['staff', 'kepala_sekolah'])): ?>
          <span class="ku-filter-static"><?= esc(strtoupper($user['sekolah'] ?? '')) ?></span>
          <?php endif; ?>
          <span style="font-size:12px;color:var(--ku-slate-400);font-weight:500"><?= date('l, d M Y', strtotime($tanggal)) ?></span>
        </div>
        <div class="ku-toolbar-right">
          <a href="/rekap-harian/export-excel?tanggal=<?= $tanggal ?><?= $filterUnit ? '&unit=' . urlencode($filterUnit) : '' ?>" class="ku-btn ku-btn-outline">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
            Export Excel
          </a>
        </div>
      </div>

      <div class="ku-stats" style="grid-template-columns:repeat(3,1fr)">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Pemasukan</div>
            <div class="ku-stat-value">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Pengeluaran</div>
            <div class="ku-stat-value">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-navy" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Saldo Bersih</div>
            <div class="ku-stat-value">Rp <?= number_format($saldoBersih, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:20px">
        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <h3>Rincian Pemasukan per Kategori</h3>
          </div>
          <div class="ku-card-body">
            <div style="margin-bottom:14px">
              <div style="font-size:11px;font-weight:700;color:var(--ku-slate-400);letter-spacing:0.5px;margin-bottom:8px;text-transform:uppercase;display:flex;align-items:center;gap:5px">
                <span style="width:7px;height:7px;border-radius:50%;background:#10B981;display:inline-block"></span> Tunai
              </div>
              <?php if (!empty($rekapPemasukanTunai)): ?>
                <?php foreach ($rekapPemasukanTunai as $kat => $jumlah): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--ku-slate-100)">
                  <span style="font-size:13px;color:var(--ku-slate-600)"><?= esc($kat) ?></span>
                  <span style="font-size:13px;font-weight:600;color:var(--ku-green)" class="money">Rp <?= number_format($jumlah, 0, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="font-size:13px;color:var(--ku-slate-400);padding:6px 0">-</div>
              <?php endif; ?>
              <div style="display:flex;justify-content:space-between;padding:8px 0 0;margin-top:4px;border-top:2px solid var(--ku-slate-200)">
                <span style="font-size:12px;font-weight:700;color:var(--ku-slate-600)">Subtotal Tunai</span>
                <span style="font-size:14px;font-weight:700;color:var(--ku-green)" class="money">Rp <?= number_format($pemasukanTunai, 0, ',', '.') ?></span>
              </div>
            </div>
            <div>
              <div style="font-size:11px;font-weight:700;color:var(--ku-slate-400);letter-spacing:0.5px;margin-bottom:8px;text-transform:uppercase;display:flex;align-items:center;gap:5px">
                <span style="width:7px;height:7px;border-radius:50%;background:#2563EB;display:inline-block"></span> Transfer
              </div>
              <?php if (!empty($rekapPemasukanTransfer)): ?>
                <?php foreach ($rekapPemasukanTransfer as $kat => $jumlah): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--ku-slate-100)">
                  <span style="font-size:13px;color:var(--ku-slate-600)"><?= esc($kat) ?></span>
                  <span style="font-size:13px;font-weight:600;color:#2563EB" class="money">Rp <?= number_format($jumlah, 0, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="font-size:13px;color:var(--ku-slate-400);padding:6px 0">-</div>
              <?php endif; ?>
              <div style="display:flex;justify-content:space-between;padding:8px 0 0;margin-top:4px;border-top:2px solid var(--ku-slate-200)">
                <span style="font-size:12px;font-weight:700;color:var(--ku-slate-600)">Subtotal Transfer</span>
                <span style="font-size:14px;font-weight:700;color:#2563EB" class="money">Rp <?= number_format($pemasukanTransfer, 0, ',', '.') ?></span>
              </div>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0 0;margin-top:12px;border-top:3px solid #10B981">
              <span style="font-size:13px;font-weight:700;color:var(--ku-slate-700)">TOTAL PEMASUKAN</span>
              <span style="font-size:16px;font-weight:700;color:var(--ku-green)" class="money">Rp <?= number_format($totalPemasukan, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>

        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <h3>Rincian Pengeluaran per Kategori</h3>
          </div>
          <div class="ku-card-body">
            <div style="margin-bottom:14px">
              <div style="font-size:11px;font-weight:700;color:var(--ku-slate-400);letter-spacing:0.5px;margin-bottom:8px;text-transform:uppercase;display:flex;align-items:center;gap:5px">
                <span style="width:7px;height:7px;border-radius:50%;background:#10B981;display:inline-block"></span> Tunai
              </div>
              <?php if (!empty($rekapPengeluaranTunai)): ?>
                <?php foreach ($rekapPengeluaranTunai as $kat => $jumlah): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--ku-slate-100)">
                  <span style="font-size:13px;color:var(--ku-slate-600)"><?= esc($kat) ?></span>
                  <span style="font-size:13px;font-weight:600;color:var(--ku-red)" class="money">Rp <?= number_format($jumlah, 0, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="font-size:13px;color:var(--ku-slate-400);padding:6px 0">-</div>
              <?php endif; ?>
              <div style="display:flex;justify-content:space-between;padding:8px 0 0;margin-top:4px;border-top:2px solid var(--ku-slate-200)">
                <span style="font-size:12px;font-weight:700;color:var(--ku-slate-600)">Subtotal Tunai</span>
                <span style="font-size:14px;font-weight:700;color:var(--ku-red)" class="money">Rp <?= number_format($pengeluaranTunai, 0, ',', '.') ?></span>
              </div>
            </div>
            <div>
              <div style="font-size:11px;font-weight:700;color:var(--ku-slate-400);letter-spacing:0.5px;margin-bottom:8px;text-transform:uppercase;display:flex;align-items:center;gap:5px">
                <span style="width:7px;height:7px;border-radius:50%;background:#2563EB;display:inline-block"></span> Transfer
              </div>
              <?php if (!empty($rekapPengeluaranTransfer)): ?>
                <?php foreach ($rekapPengeluaranTransfer as $kat => $jumlah): ?>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--ku-slate-100)">
                  <span style="font-size:13px;color:var(--ku-slate-600)"><?= esc($kat) ?></span>
                  <span style="font-size:13px;font-weight:600;color:var(--ku-red)" class="money">Rp <?= number_format($jumlah, 0, ',', '.') ?></span>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="font-size:13px;color:var(--ku-slate-400);padding:6px 0">-</div>
              <?php endif; ?>
              <div style="display:flex;justify-content:space-between;padding:8px 0 0;margin-top:4px;border-top:2px solid var(--ku-slate-200)">
                <span style="font-size:12px;font-weight:700;color:var(--ku-slate-600)">Subtotal Transfer</span>
                <span style="font-size:14px;font-weight:700;color:var(--ku-red)" class="money">Rp <?= number_format($pengeluaranTransfer, 0, ',', '.') ?></span>
              </div>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0 0;margin-top:12px;border-top:3px solid #EF4444">
              <span style="font-size:13px;font-weight:700;color:var(--ku-slate-700)">TOTAL PENGELUARAN</span>
              <span style="font-size:16px;font-weight:700;color:var(--ku-red)" class="money">Rp <?= number_format($totalPengeluaran, 0, ',', '.') ?></span>
            </div>
          </div>
        </div>

        <div class="ku-card" style="margin:0">
          <div class="ku-card-header">
            <h3>Ringkasan Saldo</h3>
          </div>
          <div class="ku-card-body">
            <?php
              $saldoTunai = $pemasukanTunai - $pengeluaranTunai;
              $saldoTransfer = $pemasukanTransfer - $pengeluaranTransfer;
            ?>
            <div style="padding:18px;background:linear-gradient(135deg,#1E293B,#334155);border-radius:10px;margin-bottom:14px;text-align:center">
              <div style="font-size:10px;color:#94A3B8;font-weight:700;letter-spacing:1px;margin-bottom:6px;text-transform:uppercase">TOTAL SELURUH DANA</div>
              <div class="money" style="font-size:22px;font-weight:700;color:#fff">Rp <?= number_format($saldoBersih, 0, ',', '.') ?></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
              <div style="padding:14px;border:1px solid var(--ku-slate-200);border-radius:10px;text-align:center">
                <div style="font-size:10px;color:var(--ku-slate-400);font-weight:700;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:6px">Saldo Tunai</div>
                <div class="money" style="font-size:15px;font-weight:700;color:<?= $saldoTunai < 0 ? '#DC2626' : '#059669' ?>">Rp <?= number_format($saldoTunai, 0, ',', '.') ?></div>
              </div>
              <div style="padding:14px;border:1px solid var(--ku-slate-200);border-radius:10px;text-align:center">
                <div style="font-size:10px;color:var(--ku-slate-400);font-weight:700;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:6px">Saldo Transfer</div>
                <div class="money" style="font-size:15px;font-weight:700;color:<?= $saldoTransfer < 0 ? '#DC2626' : '#059669' ?>">Rp <?= number_format($saldoTransfer, 0, ',', '.') ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="ku-card">
        <div class="ku-card-header">
          <div>
            <h3>Detail Transaksi</h3>
            <div class="ku-card-sub"><?= date('d M Y', strtotime($tanggal)) ?> &middot; <?= count($transaksiList) ?> transaksi</div>
          </div>
        </div>
        <div class="ku-table-wrap" style="border-radius:0;border-left:none;border-right:none;border-bottom:none;box-shadow:none">
          <table class="ku-table">
            <thead>
              <tr>
                <th>Waktu</th>
                <th>Tipe</th>
                <th>Keterangan</th>
                <th>Kategori</th>
                <th>Metode</th>
                <th style="text-align:right">Nominal</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($transaksiList)): ?>
                <?php foreach ($transaksiList as $tx): ?>
                  <tr class="<?= $tx['tipe'] === 'Pemasukan' ? 'ku-row-pemasukan' : 'ku-row-pengeluaran' ?>">
                    <td><?= date('H:i', strtotime($tx['waktu'])) ?></td>
                    <td>
                      <span class="ku-badge <?= $tx['tipe'] === 'Pemasukan' ? 'ku-badge-green' : 'ku-badge-red' ?>">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><?= $tx['tipe'] === 'Pemasukan' ? '<path d="M12 19V5M5 12l7-7 7 7"/>' : '<path d="M12 5v14M5 12l7 7 7-7"/>' ?></svg>
                        <?= $tx['tipe'] ?>
                      </span>
                    </td>
                    <td><span class="ku-td-text"><?= esc($tx['deskripsi']) ?></span></td>
                    <td><?= esc($tx['kategori']) ?></td>
                    <td>
                      <?php if (($tx['metode'] ?? 'tunai') === 'transfer'): ?>
                      <span class="ku-badge ku-badge-info">Transfer</span>
                      <?php else: ?>
                      <span class="ku-badge ku-badge-slate">Tunai</span>
                      <?php endif; ?>
                    </td>
                    <td class="ku-td-jumlah <?= $tx['nominal'] < 0 ? 'minus' : 'plus' ?>">
                      <span class="ku-jumlah-sign"><?= $tx['nominal'] < 0 ? '−' : '+' ?></span>
                      Rp <?= number_format(abs($tx['nominal']), 0, ',', '.') ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6">
                    <div class="ku-empty">
                      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                      <p>Tidak ada transaksi</p>
                      <span>Pada tanggal <?= date('d M Y', strtotime($tanggal)) ?></span>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
