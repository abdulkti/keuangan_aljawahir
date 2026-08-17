<?php
  $pageTitle = 'Detail Tagihan';
  $pageDesc = 'Informasi lengkap tagihan dan pembayaran';
  $tagihan = $tagihan ?? [];
  $payment = $payment ?? null;
  $kelasLabel = trim(($tagihan['tingkat'] ?? '') . ' ' . ($tagihan['jurusan'] ?? '')) . ' - ' . ($tagihan['nama_kelas'] ?? '');
  $isDaftarUlang = ($tagihan['jenis_tagihan'] ?? '') === 'Daftar Ulang';
  $bulanLabel = date('M Y', strtotime($tagihan['created_at'] ?? date('Y-m-d')));
  $allPayments = $allPayments ?? [];
  $totalDibayar = array_sum(array_column($allPayments, 'nominal_dibayar'));
  $sisaTagihan = (float)($tagihan['nominal'] ?? 0) - $totalDibayar;
  $jumlahCicilan = count($allPayments);
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

      <div style="margin-bottom:16px">
        <a href="/tagihan" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--ku-slate-500);text-decoration:none">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          Kembali ke Daftar Tagihan
        </a>
      </div>

      <div class="ku-card">
        <div class="ku-card-body" style="padding:24px">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px">
            <div>
              <h2 style="font-size:18px;font-weight:700;color:var(--ku-slate-800);margin:0"><?= esc($tagihan['nama'] ?? '') ?> &mdash; <?= esc($tagihan['jenis_tagihan'] ?? '') ?></h2>
              <div style="font-size:13px;color:var(--ku-slate-400);margin-top:4px">No. Tagihan #<?= $tagihan['id'] ?? '' ?></div>
            </div>
            <div>
              <?php if (($tagihan['status'] ?? '') === 'lunas'): ?>
              <span class="ku-badge ku-badge-active" style="font-size:13px;padding:6px 14px">Lunas</span>
              <?php elseif (($tagihan['status'] ?? '') === 'cicil'): ?>
              <span class="ku-badge ku-badge-amber" style="font-size:13px;padding:6px 14px">Cicil</span>
              <?php else: ?>
              <span class="ku-badge ku-badge-red" style="font-size:13px;padding:6px 14px">Belum Bayar</span>
              <?php endif; ?>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 32px">
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Siswa</span>
              <span style="font-size:15px;font-weight:600;color:var(--ku-slate-800)"><?= esc($tagihan['nama'] ?? '-') ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">NIS</span>
              <span style="font-size:15px;font-weight:600;color:var(--ku-slate-800)"><?= esc($tagihan['nis'] ?? '-') ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Kelas</span>
              <span style="font-size:15px;font-weight:600;color:var(--ku-slate-800)"><?= esc($kelasLabel) ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Jenis Tagihan</span>
              <span style="font-size:15px;font-weight:600;color:var(--ku-slate-800)"><?= esc($tagihan['jenis_tagihan'] ?? '-') ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Periode</span>
              <span style="font-size:15px;font-weight:600;color:var(--ku-slate-800)"><?= esc($bulanLabel) ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Tahun Ajaran</span>
              <span style="font-size:15px;font-weight:600;color:var(--ku-slate-800)"><?= esc($tagihan['tahun_ajaran'] ?? '-') ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Nominal</span>
              <span style="font-size:15px;font-weight:700;color:var(--ku-green)">Rp <?= number_format($tagihan['nominal'] ?? 0, 0, ',', '.') ?></span>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <span style="font-size:11px;color:var(--ku-slate-400);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Status</span>
              <span>
                <?php if (($tagihan['status'] ?? '') === 'lunas'): ?>
                <span class="ku-badge ku-badge-active">Lunas</span>
                <?php elseif (($tagihan['status'] ?? '') === 'cicil'): ?>
                <span class="ku-badge ku-badge-amber">Cicil (<?= $jumlahCicilan ?>/4)</span>
                <?php else: ?>
                <span class="ku-badge ku-badge-red">Belum Bayar</span>
                <?php endif; ?>
              </span>
            </div>
          </div>

          <?php if (!empty($allPayments)): ?>
          <div style="margin-top:24px">
            <h4 style="font-size:14px;font-weight:700;color:var(--ku-slate-800);margin-bottom:12px">
              Riwayat Pembayaran
              <?php if ($isDaftarUlang): ?>
              <span style="font-size:12px;color:var(--ku-slate-400);font-weight:500">(<?= $jumlahCicilan ?>/4 kali cicilan)</span>
              <?php endif; ?>
            </h4>
            <div class="ku-table-wrap" style="box-shadow:none;border:1px solid var(--ku-slate-200)">
              <table class="ku-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Tgl Bayar</th>
                    <th style="text-align:right">Nominal</th>
                    <th>Metode</th>
                    <th>Kwitansi</th>
                    <th>Petugas</th>
                    <th style="text-align:center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 1; ?>
                  <?php foreach ($allPayments as $p): ?>
                  <tr>
                    <td class="ku-td-num"><?= $no++ ?></td>
                    <td><?= $p['created_at'] ? date('d M Y, H.i', strtotime($p['created_at'])) : '-' ?></td>
                    <td class="ku-td-jumlah plus">Rp <?= number_format($p['nominal_dibayar'], 0, ',', '.') ?></td>
                    <td style="text-transform:capitalize">
                      <?php if ($p['metode'] === 'transfer'): ?>
                      <span class="ku-badge ku-badge-info">Transfer</span>
                      <?php else: ?>
                      <span class="ku-badge ku-badge-slate">Tunai</span>
                      <?php endif; ?>
                    </td>
                    <td><?= esc($p['no_kwitansi'] ?? '-') ?></td>
                    <td><?= esc($p['petugas_nama'] ?? '-') ?></td>
                    <td style="text-align:center">
                      <button type="button" class="ku-action-btn btn-kwitansi" title="Cetak Kwitansi"
                        data-nama="<?= esc($tagihan['nama'] ?? '') ?>"
                        data-kelas="<?= esc($kelasLabel) ?>"
                        data-jenis="<?= esc($tagihan['jenis_tagihan'] ?? '') ?>"
                        data-periode="<?= esc($bulanLabel) ?>"
                        data-nominal="<?= $p['nominal_dibayar'] ?>"
                        data-metode="<?= esc($p['metode'] ?? '') ?>"
                        data-kwitansi="<?= esc($p['no_kwitansi'] ?? '') ?>"
                        data-tanggal="<?= $p['created_at'] ?? '' ?>"
                        data-petugas="<?= esc($p['petugas_nama'] ?? '') ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M6 9V2h12v7"/><rect x="6" y="14" width="12" height="8"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/></svg>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($isDaftarUlang): ?>
            <div style="display:flex;justify-content:space-between;margin-top:12px;padding:12px 16px;background:var(--ku-slate-50);border-radius:8px;font-size:13px">
              <span>Total Dibayar: <strong>Rp <?= number_format($totalDibayar, 0, ',', '.') ?></strong></span>
              <span>Sisa Tagihan: <strong>Rp <?= number_format($sisaTagihan, 0, ',', '.') ?></strong></span>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if (($tagihan['status'] ?? '') !== 'lunas'): ?>
          <div style="margin-top:24px;padding:20px;background:var(--ku-slate-50);border-radius:10px;border:1px solid var(--ku-slate-200)">
            <h4 style="font-size:14px;font-weight:700;color:var(--ku-slate-800);margin:0 0 16px;display:flex;align-items:center;gap:6px">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              Proses Pembayaran
            </h4>
            <div style="font-size:13px;font-weight:700;color:var(--ku-slate-800);background:#fff;border:1px solid var(--ku-slate-200);border-radius:8px;padding:10px 14px;margin-bottom:12px">
              <?= esc($tagihan['jenis_tagihan'] ?? '') ?> &mdash; Periode <?= esc($bulanLabel) ?>
              <?php if (!empty($tagihan['tahun_ajaran'])): ?>
              <span style="color:var(--ku-slate-400);font-weight:600">&middot; Tahun Ajaran <?= esc($tagihan['tahun_ajaran']) ?></span>
              <?php endif; ?>
            </div>
            <?php if ($isDaftarUlang): ?>
            <div style="font-size:13px;color:var(--ku-slate-500);margin-bottom:12px">
              Tagihan Awal Tahun &mdash; cicilan ke-<?= $jumlahCicilan + 1 ?> (maks 4x).
              Sisa tagihan: <strong>Rp <?= number_format($sisaTagihan, 0, ',', '.') ?></strong>
            </div>
            <?php endif; ?>
            <form method="post" action="/tagihan/bayar" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
              <?= csrf_field() ?>
              <input type="hidden" name="tagihan_id" value="<?= $tagihan['id'] ?>">
              <div class="ku-field" style="flex:1;min-width:160px">
                <label>Metode Pembayaran</label>
                <select name="metode" required>
                  <option value="tunai">Tunai</option>
                  <option value="transfer">Transfer</option>
                </select>
              </div>
              <div class="ku-field" style="flex:1;min-width:160px">
                <label>Tanggal & Jam Bayar</label>
                <input type="datetime-local" name="tgl_bayar" value="<?= date('Y-m-d\TH:i') ?>">
              </div>
              <div class="ku-field" style="flex:1;min-width:160px">
                <label><?= $isDaftarUlang ? 'Jumlah Dibayar' : 'Nominal' ?></label>
                <input type="text" name="nominal_dibayar" inputmode="numeric" required
                  value="<?= 'Rp ' . number_format($isDaftarUlang ? $sisaTagihan : ($tagihan['nominal'] ?? 0), 0, ',', '.') ?>"
                  <?= $isDaftarUlang ? '' : 'readonly style="background:var(--ku-slate-100);color:var(--ku-slate-500);cursor:not-allowed"' ?>>
              </div>
              <button type="submit" class="ku-btn ku-btn-emerald" style="height:40px" onclick="return confirm('Konfirmasi pembayaran?\nRp ' + document.querySelector('[name=nominal_dibayar]').value + ' &mdash; ' + document.querySelector('[name=metode]').value)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                Konfirmasi & Bayar
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Kwitansi -->
<div class="ku-modal-overlay" id="modal-kwitansi-detail">
  <div class="ku-modal-box" style="max-width:380px">
    <div class="ku-modal-head">
      <div>
        <h3>Kwitansi Pembayaran</h3>
        <p id="kwitansi-detail-sub">Detail kwitansi</p>
      </div>
      <button type="button" class="ku-modal-close">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="ku-modal-body" id="kwitansi-detail-body">
      <p style="color:var(--ku-slate-400);text-align:center;padding:20px 0">Memuat...</p>
    </div>
    <div class="ku-modal-foot" style="justify-content:center">
      <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Tutup</button>
      <button type="button" class="ku-btn ku-btn-primary" onclick="printKwitansi()">Cetak</button>
    </div>
  </div>
</div>

<script>
function formatRp(n) {
  return 'Rp ' + Number(n).toLocaleString('id-ID');
}

function escHtml(s) { var d = document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }

document.querySelectorAll('.btn-kwitansi').forEach(function(btn){
  btn.addEventListener('click', function(){
    var d = {
      nama: this.getAttribute('data-nama'),
      kelas: this.getAttribute('data-kelas'),
      jenis: this.getAttribute('data-jenis'),
      periode: this.getAttribute('data-periode'),
      nominal: this.getAttribute('data-nominal'),
      metode: this.getAttribute('data-metode'),
      kwitansi: this.getAttribute('data-kwitansi'),
      tanggal: this.getAttribute('data-tanggal'),
      petugas: this.getAttribute('data-petugas'),
    };
    var body = document.getElementById('kwitansi-detail-body');
    var tgl = d.tanggal ? new Date(d.tanggal).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-';
    body.innerHTML =
      '<div style="text-align:center;border-bottom:2px dashed var(--ku-slate-200);padding-bottom:16px;margin-bottom:16px">' +
        '<h4 style="font-size:15px;font-weight:700;color:var(--ku-slate-800)">KWITANSI PEMBAYARAN</h4>' +
        '<p style="font-size:11px;color:var(--ku-slate-400)">No. ' + escHtml(d.kwitansi) + '</p>' +
      '</div>' +
      '<table style="width:100%;border-collapse:collapse;font-size:13px">' +
        '<tbody>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Siswa</td><td style="padding:6px 0;font-weight:600;text-align:right">' + escHtml(d.nama) + '</td></tr>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Kelas</td><td style="padding:6px 0;font-weight:600;text-align:right">' + escHtml(d.kelas) + '</td></tr>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Jenis</td><td style="padding:6px 0;font-weight:600;text-align:right">' + escHtml(d.jenis) + '</td></tr>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Periode</td><td style="padding:6px 0;font-weight:600;text-align:right">' + escHtml(d.periode || '-') + '</td></tr>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Nominal</td><td style="padding:6px 0;font-weight:700;text-align:right;color:var(--ku-green)">' + formatRp(d.nominal) + '</td></tr>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Metode</td><td style="padding:6px 0;font-weight:600;text-align:right;text-transform:capitalize">' + escHtml(d.metode) + '</td></tr>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Tanggal Bayar</td><td style="padding:6px 0;font-weight:600;text-align:right">' + tgl + '</td></tr>' +
        '<tr><td style="padding:6px 0;color:var(--ku-slate-500)">Petugas</td><td style="padding:6px 0;font-weight:600;text-align:right">' + escHtml(d.petugas) + '</td></tr>' +
        '</tbody>' +
      '</table>';
    document.getElementById('kwitansi-detail-sub').textContent = d.nama + ' - ' + d.jenis;
    document.getElementById('modal-kwitansi-detail').classList.add('active');
  });
});

function printKwitansi() {
  var src = document.getElementById('kwitansi-detail-body');
  var safeContent = src.innerText;
  var win = window.open('', '', 'width=400,height=600');
  win.document.write(
    '<html><head><title>Kwitansi</title>' +
    '<style>body{font-family:Inter,system-ui,sans-serif;padding:20px;font-size:13px;color:#0F172A}' +
    'table{width:100%;border-collapse:collapse}' +
    'td{padding:6px 0}' +
    '.num{text-align:right;font-weight:600;color:#0D9488}' +
    '</style></head><body>' + escHtml(safeContent) + '</body></html>'
  );
  win.document.close();
  win.print();
}

document.querySelector('[name="nominal_dibayar"]')?.addEventListener('input', function(){
  var val = this.value.replace(/[^0-9]/g, '');
  if (val) this.value = 'Rp ' + Number(val).toLocaleString('id-ID');
});

document.querySelectorAll('.ku-modal-close').forEach(function(el){
  el.addEventListener('click', function(){
    this.closest('.modal-overlay').classList.remove('active');
  });
});
</script>
<?= view('layout/footer') ?>
