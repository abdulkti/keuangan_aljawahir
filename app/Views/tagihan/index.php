<?php
  $pageTitle = 'Tagihan & SPP';
  $pageDesc = 'Pantau status pembayaran SPP seluruh siswa';
  $avatarColors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#06B6D4'];
  function avCol($n, $c) { return $c[crc32($n) % count($c)]; }
  $lunasCount = $lunasCount ?? 0;
  $cicilCount = $cicilCount ?? 0;
  $belumBayarCount = $belumBayarCount ?? 0;
  $totalTertagih = $totalTertagih ?? 0;
  $totalTertunggak = $totalTertunggak ?? 0;
  $bills = $bills ?? [];
  $classes = $classes ?? [];
  $students = $students ?? [];
  $payments = $payments ?? [];
  $billCicilan = $billCicilan ?? [];
  $siswaCount = $siswaCount ?? 0;
  $kelas = $kelas ?? '';
  $status = $status ?? '';
  $jenis = $jenis ?? '';
  $search = $search ?? '';
  $bulan = $bulan ?? '';
  $pager = $pager ?? null;
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

      <div class="ku-stats" style="grid-template-columns:repeat(3,1fr)">
        <div class="ku-stat ku-stat-green" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Lunas</div>
            <div class="ku-stat-value"><?= $lunasCount ?> <span style="font-size:13px;font-weight:600;color:var(--ku-slate-400)">siswa</span></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Rp <?= number_format($totalTertagih, 0, ',', '.') ?> terkumpul</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-blue" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Cicil</div>
            <div class="ku-stat-value"><?= $cicilCount ?> <span style="font-size:13px;font-weight:600;color:var(--ku-slate-400)">siswa</span></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Tagihan awal tahun dicicil</span>
            </div>
          </div>
        </div>
        <div class="ku-stat ku-stat-red" style="--delay:0.2s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Belum Bayar</div>
            <div class="ku-stat-value"><?= $belumBayarCount ?> <span style="font-size:13px;font-weight:600;color:var(--ku-slate-400)">siswa</span></div>
            <div class="ku-stat-sub">
              <span class="ku-stat-chip">Rp <?= number_format($totalTertunggak, 0, ',', '.') ?> tertunggak</span>
            </div>
          </div>
        </div>
      </div>

      <form method="GET" action="/tagihan" class="ku-toolbar">
        <input type="hidden" name="page" value="1">
        <div class="ku-toolbar-left">
          <div class="ku-search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" placeholder="Cari nama siswa..." value="<?= esc($search ?? '') ?>">
          </div>
          <select name="kelas" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Kelas: Semua</option>
            <?php foreach ($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($kelas ?? '') == $c['id'] ? 'selected' : '' ?>><?= esc($c['tingkat'] . ' ' . $c['jurusan'] . ' - ' . $c['nama_kelas']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="status" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Status: Semua</option>
            <option value="lunas" <?= ($status ?? '') === 'lunas' ? 'selected' : '' ?>>Lunas</option>
            <option value="cicil" <?= ($status ?? '') === 'cicil' ? 'selected' : '' ?>>Cicil</option>
            <option value="belum_bayar" <?= ($status ?? '') === 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
          </select>
          <select name="jenis" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Jenis: Semua</option>
            <option value="SPP Bulanan" <?= ($jenis ?? '') === 'SPP Bulanan' ? 'selected' : '' ?>>SPP Bulanan</option>
            <option value="Daftar Ulang" <?= ($jenis ?? '') === 'Daftar Ulang' ? 'selected' : '' ?>>Daftar Ulang</option>
          </select>
          <select name="bulan" class="ku-filter-select" onchange="this.form.submit()">
            <option value="">Bulan: Semua</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($bulan ?? '') == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="ku-toolbar-right">
          <span style="font-size:12px;color:var(--ku-slate-400);font-weight:500"><?= $bulan ? date('F', mktime(0, 0, 0, $bulan, 1)) : 'Semua Bulan' ?> &middot; <?= $siswaCount ?> siswa</span>
        </div>
      </form>

      <div class="ku-table-wrap">
        <table class="ku-table">
          <thead>
            <tr>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th>Jenis Tagihan</th>
              <th>Bulan</th>
              <th>Tahun Ajaran</th>
              <th style="text-align:right">Nominal</th>
              <th>Status Bayar</th>
              <th>Status Siswa</th>
              <th style="text-align:right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($bills)): ?>
            <?php foreach ($bills as $b):
              $payment = $payments[$b['id']] ?? null;
              $_cData = $billCicilan[$b['id']] ?? ['total' => 0, 'count' => 0];
              $_sisa = (float)$b['nominal'] - $_cData['total'];
            ?>
            <tr>
              <td><div class="cell-person"><div class="avatar-sm" style="background:<?= avCol($b['nama'] ?? '?', $avatarColors) ?>;color:#fff;font-weight:700"><?= strtoupper(substr($b['nama'] ?? '?', 0, 2)) ?></div><span class="p-name"><?= esc($b['nama']) ?></span></div></td>
              <td><?= esc($b['nama_kelas']) ?></td>
              <td><?= esc($b['jenis_tagihan']) ?></td>
              <td><?= date('M Y', strtotime($b['created_at'])) ?></td>
              <td><?= esc($b['tahun_ajaran'] ?? '-') ?></td>
              <td class="ku-td-jumlah <?= $_sisa <= 0 ? 'plus' : ($b['status'] === 'cicil' ? 'minus' : '') ?>">
                Rp <?= number_format($b['nominal'], 0, ',', '.') ?>
                <?php if ($b['status'] === 'cicil'): ?>
                <br><span style="font-size:10px;font-weight:600;color:#DC2626">sisa Rp <?= number_format($_sisa, 0, ',', '.') ?></span>
                <?php elseif ($b['status'] === 'belum_bayar'): ?>
                <br><span style="font-size:10px;font-weight:600;color:#DC2626">belum dibayar</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($b['status'] === 'lunas'): ?>
                <span class="ku-badge ku-badge-active">Lunas</span>
                <?php elseif ($b['status'] === 'cicil'): ?>
                <span class="ku-badge ku-badge-amber">Cicil</span>
                <?php else: ?>
                <span class="ku-badge ku-badge-red">Belum Bayar</span>
                <?php endif; ?>
              </td>
              <td><?php if (($b['siswa_status'] ?? 'aktif') !== 'aktif'): ?><span class="ku-badge ku-badge-amber" style="font-size:10px;padding:2px 8px"><?= esc($b['siswa_status']) ?></span><?php else: ?><span class="ku-badge ku-badge-green" style="font-size:10px;padding:2px 8px">aktif</span><?php endif; ?></td>
              <td>
                <div class="ku-actions" style="gap:4px">
                  <?php if ($b['status'] !== 'lunas'): ?>
                  <button class="modal-trigger" data-modal="modal-bayar"
                      data-bill='<?= htmlspecialchars(json_encode([
                        'id' => $b['id'],
                        'nama' => $b['nama'],
                        'nominal' => $b['nominal'],
                        'status' => $b['status'],
                        'jenis_tagihan' => $b['jenis_tagihan'],
                        'bulan' => date('M Y', strtotime($b['created_at'])),
                        'tahun_ajaran' => $b['tahun_ajaran'] ?? '',
                        'total_dibayar' => $_cData['total'],
                        'sisa' => max(0, $_sisa),
                        'cicilan' => $_cData['count'],
                      ]), ENT_QUOTES, 'UTF-8') ?>'
                    title="Bayar" class="ku-action-btn" style="background:#ECFDF5;color:#059669">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  </button>
                  <?php endif; ?>
                  <button class="modal-trigger ku-action-btn" data-modal="modal-kwitansi"
                      data-bill='<?= htmlspecialchars(json_encode([
                        'id' => $b['id'],
                        'nama' => $b['nama'],
                        'nama_kelas' => $b['nama_kelas'],
                        'jenis_tagihan' => $b['jenis_tagihan'],
                        'bulan' => date('M Y', strtotime($b['created_at'])),
                        'tahun_ajaran' => $b['tahun_ajaran'] ?? '',
                        'nominal' => $b['nominal'],
                        'status' => $b['status'],
                        'kwitansi' => $payment ? $payment['no_kwitansi'] : null,
                        'metode' => $payment ? $payment['metode'] : null,
                        'tgl_bayar' => $payment ? $payment['created_at'] : null,
                      ]), ENT_QUOTES, 'UTF-8') ?>'
                    title="Cetak Kwitansi" style="background:#EFF6FF;color:#2563EB">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M6 9V2h12v7"/><rect x="6" y="14" width="12" height="8"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/></svg>
                  </button>
                  <a href="/tagihan/detail/<?= $b['id'] ?>" title="Detail" class="ku-action-btn" style="background:var(--ku-slate-50);color:var(--ku-slate-600)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="9"><div class="ku-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg><p>Belum ada tagihan</p><span>Filter mungkin terlalu ketat</span></div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <?php if ($pager && $pager->getPageCount() > 1): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid var(--ku-slate-100);font-size:12px;color:var(--ku-slate-400)">
          <span>Menampilkan <?= count($bills) ?> tagihan</span>
          <div class="ku-pagination">
            <?php
              $qs = $_GET;
              $currentPage = (int)($qs['page'] ?? 1);
              $lastPage = $pager->getPageCount();
            ?>
            <?php if ($currentPage > 1): ?>
              <?php $qs['page'] = $currentPage - 1; ?>
              <a href="?<?= http_build_query($qs) ?>">&lsaquo;</a>
            <?php endif; ?>
            <span class="info"><?= $currentPage ?> / <?= $lastPage ?></span>
            <?php if ($currentPage < $lastPage): ?>
              <?php $qs['page'] = $currentPage + 1; ?>
              <a href="?<?= http_build_query($qs) ?>">&rsaquo;</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Kwitansi -->
<div class="ku-modal-overlay" id="modal-kwitansi">
  <div class="ku-modal-box" style="max-width:380px">
    <div class="ku-modal-head">
      <div>
        <h3>Kwitansi Pembayaran</h3>
        <p id="kwitansi-sub">Detail kwitansi</p>
      </div>
      <button type="button" class="ku-modal-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="ku-modal-body" id="kwitansi-body">
      <p style="color:var(--ku-slate-400);text-align:center;padding:20px 0">Memuat...</p>
    </div>
    <div class="ku-modal-foot" style="justify-content:center">
      <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Tutup</button>
      <button type="button" class="ku-btn ku-btn-primary" onclick="window.print()">Cetak</button>
    </div>
  </div>
</div>

<!-- Modal Bayar Cepat -->
<div class="ku-modal-overlay" id="modal-bayar">
  <div class="ku-modal-box" style="max-width:400px">
    <form method="post" action="/tagihan/bayar" id="form-bayar-cepat">
      <?= csrf_field() ?>
      <input type="hidden" name="tagihan_id" id="bayar-id">
      <input type="hidden" name="nominal_dibayar" id="bayar-nominal-hidden">
      <div class="ku-modal-head">
        <div>
          <h3>Bayar Tagihan</h3>
          <p id="bayar-sub">Konfirmasi pembayaran</p>
        </div>
        <button type="button" class="ku-modal-close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="ku-modal-body">
        <div style="text-align:center;padding:12px 0 16px">
          <p style="font-size:15px;font-weight:600;color:var(--ku-slate-900)" id="bayar-nama"></p>
          <p style="font-size:12.5px;font-weight:600;color:var(--ku-slate-500);margin-top:2px" id="bayar-periode"></p>
          <p style="font-size:24px;font-weight:700;color:var(--ku-green);margin-top:4px" id="bayar-nominal"></p>
        </div>
        <div id="bayar-cicilan-info" style="display:none;font-size:13px;padding:12px;background:var(--navy-50);border-radius:8px;margin-bottom:12px"></div>
        <div id="bayar-nominal-field" class="ku-field" style="display:none">
          <label>Jumlah Bayar</label>
          <input type="text" id="bayar-nominal-input" placeholder="Rp 0" autocomplete="off">
        </div>
        <div class="ku-field">
          <label>Metode Pembayaran</label>
          <select name="metode" required>
            <option value="tunai">Tunai</option>
            <option value="transfer">Transfer</option>
          </select>
        </div>
        <div class="ku-field">
          <label>Tanggal & Jam Bayar</label>
          <input type="datetime-local" name="tgl_bayar" value="<?= date('Y-m-d\TH:i') ?>">
        </div>
      </div>
      <div class="ku-modal-foot">
        <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Batal</button>
        <button type="submit" class="ku-btn ku-btn-emerald">Konfirmasi & Bayar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Detail Tagihan -->
<div class="ku-modal-overlay" id="modal-detail-tagihan">
  <div class="ku-modal-box" style="max-width:420px">
    <div class="ku-modal-head">
      <div>
        <h3>Detail Tagihan</h3>
        <p id="detail-sub">Informasi lengkap tagihan</p>
      </div>
      <button type="button" class="ku-modal-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="ku-modal-body" id="detail-body">
      <p style="color:var(--ku-slate-400);text-align:center;padding:20px 0">Memuat...</p>
    </div>
    <div class="ku-modal-foot" style="justify-content:center">
      <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Tutup</button>
    </div>
  </div>
</div>

<script>
function formatRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

function buildBillData(btn) {
  try {
    return JSON.parse(btn.getAttribute('data-bill'));
  } catch(e) {
    return null;
  }
}

document.querySelectorAll('.modal-trigger[data-modal="modal-kwitansi"]').forEach(function(btn){
  btn.addEventListener('click', function(){
    var d = buildBillData(this);
    if (!d) return;
    var body = document.getElementById('kwitansi-body');
    if (d.status === 'lunas' && d.kwitansi) {
      body.innerHTML =
        '<div style="text-align:center;border-bottom:2px dashed var(--ku-slate-200);padding-bottom:16px;margin-bottom:16px">' +
          '<h4 style="font-size:15px;font-weight:700;color:var(--ku-slate-800)">KWITANSI PEMBAYARAN</h4>' +
          '<p style="font-size:11px;color:var(--ku-slate-400)">No. ' + d.kwitansi + '</p>' +
        '</div>' +
        '<table style="width:100%;border-collapse:collapse;font-size:13px">' +
          '<tbody>' +
          '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Siswa</td><td style="padding:6px 0;font-weight:600;text-align:right">' + d.nama + '</td></tr>' +
          '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Kelas</td><td style="padding:6px 0;font-weight:600;text-align:right">' + d.nama_kelas + '</td></tr>' +
          '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Jenis</td><td style="padding:6px 0;font-weight:600;text-align:right">' + d.jenis_tagihan + '</td></tr>' +
          '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Periode</td><td style="padding:6px 0;font-weight:600;text-align:right">' + d.bulan + (d.tahun_ajaran ? ' (' + d.tahun_ajaran + ')' : '') + '</td></tr>' +
          '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Nominal</td><td style="padding:6px 0;font-weight:700;text-align:right;color:var(--ku-green)">' + formatRp(d.nominal) + '</td></tr>' +
          '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Metode</td><td style="padding:6px 0;font-weight:600;text-align:right;text-transform:capitalize">' + d.metode + '</td></tr>' +
          '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Tanggal Bayar</td><td style="padding:6px 0;font-weight:600;text-align:right">' + (d.tgl_bayar ? new Date(d.tgl_bayar).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'}) : '-') + '</td></tr>' +
          '</tbody>' +
        '</table>';
    } else {
      body.innerHTML = '<div style="text-align:center;padding:24px 0"><svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="var(--ku-slate-300)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg><p style="color:var(--ku-slate-400);margin-top:12px;font-size:14px">Tagihan ini belum dibayar</p><p style="color:var(--ku-slate-400);font-size:12px">Kwitansi akan tersedia setelah pembayaran</p></div>';
    }
    document.getElementById('kwitansi-sub').textContent = d.nama + ' - ' + d.jenis_tagihan;
  });
});

document.querySelectorAll('.modal-trigger[data-modal="modal-detail-tagihan"]').forEach(function(btn){
  btn.addEventListener('click', function(){
    var d = buildBillData(this);
    if (!d) return;
    var body = document.getElementById('detail-body');
    var statusHtml = '';
    if (d.status === 'lunas') statusHtml = '<span class="ku-badge ku-badge-active">Lunas</span>';
    else if (d.status === 'cicil') statusHtml = '<span class="ku-badge ku-badge-amber">Cicil</span>';
    else statusHtml = '<span class="ku-badge ku-badge-red">Belum Bayar</span>';
    var extraRows = '';
    if (d.status === 'lunas' && d.kwitansi) {
      extraRows =
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">No. Kwitansi</td><td style="padding:8px 0;font-weight:600;text-align:right">' + d.kwitansi + '</td></tr>' +
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">Metode</td><td style="padding:8px 0;font-weight:600;text-align:right;text-transform:capitalize">' + d.metode + '</td></tr>' +
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">Tgl Bayar</td><td style="padding:8px 0;font-weight:600;text-align:right">' + (d.tgl_bayar ? new Date(d.tgl_bayar).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-') + '</td></tr>';
    }
    body.innerHTML =
      '<table style="width:100%;border-collapse:collapse;font-size:13px">' +
        '<tbody>' +
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">Siswa</td><td style="padding:8px 0;font-weight:600;text-align:right">' + d.nama + '</td></tr>' +
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">Kelas</td><td style="padding:8px 0;font-weight:600;text-align:right">' + d.nama_kelas + '</td></tr>' +
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">Jenis</td><td style="padding:8px 0;font-weight:600;text-align:right">' + d.jenis_tagihan + '</td></tr>' +
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">Nominal</td><td style="padding:8px 0;font-weight:700;text-align:right;color:var(--ku-green)">' + formatRp(d.nominal) + '</td></tr>' +
        '<tr><td style="padding:8px 0;color:var(--ku-slate-500)">Status</td><td style="padding:8px 0;text-align:right">' + statusHtml + '</td></tr>' +
        extraRows +
        '</tbody>' +
      '</table>';
    document.getElementById('detail-sub').textContent = d.nama + ' - ' + d.jenis_tagihan;
  });
});

function formatNominalInput(el) {
  var val = el.value.replace(/[^0-9]/g, '');
  if (val) {
    el.value = 'Rp ' + Number(val).toLocaleString('id-ID');
  } else {
    el.value = '';
  }
  return val;
}

document.getElementById('bayar-nominal-input').addEventListener('input', function() {
  var raw = formatNominalInput(this);
  document.getElementById('bayar-nominal-hidden').value = raw;
});

document.querySelectorAll('.modal-trigger[data-modal="modal-bayar"]').forEach(function(btn){
  btn.addEventListener('click', function(){
    var d = buildBillData(this);
    if (!d) return;
    document.getElementById('bayar-id').value = d.id;
    document.getElementById('bayar-nama').textContent = d.nama;
    document.getElementById('bayar-periode').textContent = d.jenis_tagihan + ' — ' + (d.bulan || '-') + (d.tahun_ajaran ? ' (' + d.tahun_ajaran + ')' : '');
    document.getElementById('bayar-nominal').textContent = formatRp(d.nominal);
    document.getElementById('bayar-sub').textContent = d.nama + ' — ' + d.jenis_tagihan;

    var cicilanInfo = document.getElementById('bayar-cicilan-info');
    var nominalField = document.getElementById('bayar-nominal-field');
    var nominalInput = document.getElementById('bayar-nominal-input');

    if (d.jenis_tagihan === 'Daftar Ulang') {
      cicilanInfo.style.display = 'block';
      cicilanInfo.innerHTML =
        '<div style="display:flex;justify-content:space-between;margin-bottom:4px">' +
          '<span style="color:var(--ku-slate-500)">Sudah dibayar</span>' +
          '<span style="font-weight:600">' + formatRp(d.total_dibayar) + ' (' + d.cicilan + 'x)</span>' +
        '</div>' +
        '<div style="display:flex;justify-content:space-between;padding-top:4px;border-top:1px solid var(--ku-slate-200)">' +
          '<span style="color:var(--ku-slate-500)">Sisa tagihan</span>' +
          '<span style="font-weight:700;color:var(--ku-green)">' + formatRp(d.sisa) + '</span>' +
        '</div>';
      var sisaValue = d.sisa || d.nominal;
      nominalInput.value = 'Rp ' + Number(sisaValue).toLocaleString('id-ID');
      document.getElementById('bayar-nominal-hidden').value = sisaValue;
      nominalField.style.display = 'block';
    } else {
      cicilanInfo.style.display = 'none';
      nominalField.style.display = 'none';
      document.getElementById('bayar-nominal-hidden').value = d.nominal;
    }
  });
});
</script>
<?= view('layout/footer') ?>
