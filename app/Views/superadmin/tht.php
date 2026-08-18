<?php
  $pageTitle = 'THT Guru';
  $pageDesc = 'Kelola dana THT (Tabungan Hari Tua) untuk seluruh guru';
  $totalGuru = count($saldoGuru ?? []);
  $totalDana = array_sum(array_column($saldoGuru ?? [], 'saldo'));
  $avatarColors = ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1','#06B6D4'];
  function avCol($n, $c) { return $c[crc32($n) % count($c)]; }
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
          <form method="GET" action="/tht" style="display:flex;gap:8px;margin:0">
            <div class="ku-search-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
              <input type="text" name="search" placeholder="Cari nama guru..." value="<?= esc($search ?? '') ?>" onchange="this.form.submit()">
            </div>
            <select name="tahun" class="ku-filter-select" onchange="this.form.submit()">
              <?php foreach ($tahunList as $t): ?>
              <option value="<?= $t ?>" <?= $tahunFilter == $t ? 'selected' : '' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
            <select name="bulan" class="ku-filter-select" onchange="this.form.submit()">
              <option value="">Semua Bulan</option>
              <?php foreach ($bulanList as $bk => $bv): ?>
              <option value="<?= $bk ?>" <?= ($bulanFilter ?? '') == $bk ? 'selected' : '' ?>><?= $bv ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <div class="ku-toolbar-right">
          <button class="ku-btn ku-btn-primary" onclick="openModal('modalSetorTHT')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Iuran THT
          </button>
          <button class="ku-btn ku-btn-danger" onclick="openModal('modalTarikTHT')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/></svg>
            Realisasi THT
          </button>
        </div>
      </div>

      <div class="ku-stats">
        <div class="ku-stat ku-stat-indigo" style="--delay:0s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Guru</div>
            <div class="ku-stat-value"><?= $totalGuru ?></div>
          </div>
        </div>
        <div class="ku-stat ku-stat-green" style="--delay:0.1s">
          <div class="ku-stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
          </div>
          <div class="ku-stat-body">
            <div class="ku-stat-label">Total Dana THT</div>
            <div class="ku-stat-value">Rp <?= number_format($totalDana, 0, ',', '.') ?></div>
          </div>
        </div>
      </div>

      <div class="ku-table-wrap">
        <table class="ku-table">
          <thead>
            <tr>
              <th>Nama Guru</th>
              <th>NIP</th>
              <th>Unit</th>
              <th style="text-align:right">Total Iuran</th>
              <th style="text-align:right">Total Realisasi</th>
              <th style="text-align:right">Saldo THT</th>
              <th style="text-align:right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($saldoGuru)): ?>
              <?php foreach ($saldoGuru as $s): ?>
              <tr>
                <td>
                  <div class="cell-person">
                    <div class="avatar-sm" style="background:<?= avCol($s['nama'] ?? '?', $avatarColors) ?>;color:#fff;font-weight:700"><?= strtoupper(substr($s['nama'] ?? '?', 0, 2)) ?></div>
                    <div>
                      <div class="p-name"><?= esc($s['nama']) ?></div>
                      <div class="p-sub">Guru</div>
                    </div>
                  </div>
                </td>
                <td class="num"><?= esc($s['nip'] ?? '-') ?></td>
                <td><?= esc($s['unit']) ?></td>
                <td style="text-align:right">Rp <?= number_format($s['total_setoran'], 0, ',', '.') ?></td>
                <td style="text-align:right">Rp <?= number_format($s['total_penarikan'], 0, ',', '.') ?></td>
                <td style="text-align:right;color:var(--ku-green);font-weight:700">Rp <?= number_format($s['saldo'], 0, ',', '.') ?></td>
                <td style="text-align:right;white-space:nowrap">
                  <button class="ku-action-btn tht-riwayat-btn" data-guru="<?= $s['id'] ?>" title="Riwayat transaksi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></svg>
                    <span>Riwayat</span>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" style="text-align:center;color:var(--ku-slate-400);padding:40px">Belum ada data guru</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="ku-table-header" style="padding:10px 16px;margin:0;border-top:1px solid var(--ku-slate-100)">
          <p>Menampilkan <?= count($saldoGuru) ?> guru</p>
        </div>
      </div>

      <div class="ku-modal-overlay" id="modalSetorTHT">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3>Input Iuran THT</h3>
              <p>Catat iuran THT untuk satu guru</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalSetorTHT')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form action="<?= base_url('tht/setor') ?>" method="post">
            <?= csrf_field() ?>
            <div class="ku-modal-body">
              <div class="ku-field">
                <label>Guru</label>
                <input type="text" name="guru_nama" id="setorGuruSearch" placeholder="Ketik nama guru..." autocomplete="off" list="setorGuruDatalist" required>
                <input type="hidden" name="guru_id" id="setorGuruIdHidden">
                <datalist id="setorGuruDatalist">
                  <?php foreach ($guruList as $g): ?>
                  <option value="<?= esc($g['nama']) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
              <div class="ku-field">
                <label>Jumlah Iuran (Rp)</label>
                <input type="number" name="jumlah" value="500000" min="0" required>
              </div>
              <div class="ku-field">
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="ku-field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" placeholder="Contoh: Iuran Bulanan">
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalSetorTHT')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-primary">Simpan Iuran</button>
            </div>
          </form>
        </div>
      </div>

      <div class="ku-modal-overlay" id="modalTarikTHT">
        <div class="ku-modal-box">
          <div class="ku-modal-head">
            <div>
              <h3>Realisasi THT</h3>
              <p>Penarikan dana THT per guru</p>
            </div>
            <button class="ku-modal-close" onclick="closeModal('modalTarikTHT')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
          </div>
          <form action="<?= base_url('tht/tarik') ?>" method="post">
            <?= csrf_field() ?>
            <div class="ku-modal-body">
              <div class="ku-field">
                <label>Guru</label>
                <input type="text" name="guru_nama" id="guruSearch" placeholder="Ketik nama guru..." autocomplete="off" list="guruDatalist">
                <input type="hidden" name="guru_id" id="guruIdHidden">
                <datalist id="guruDatalist">
                  <?php foreach ($guruList as $g): ?>
                  <option value="<?= esc($g['nama']) ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
              <script>
              var guruMap = {};
              <?php foreach ($guruList as $g): ?>
              guruMap["<?= esc($g['nama']) ?>"] = "<?= $g['id'] ?>";
              <?php endforeach; ?>
              document.getElementById('guruSearch')?.addEventListener('change', function() {
                document.getElementById('guruIdHidden').value = guruMap[this.value] || '';
              });
              </script>
              <div class="ku-field-row">
                <div class="ku-field">
                  <label>Jumlah Realisasi (Rp)</label>
                  <input type="number" name="jumlah" value="100000" min="0" required>
                </div>
                <div class="ku-field">
                  <label>Tanggal</label>
                  <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
              </div>
              <div class="ku-field">
                <label>Keterangan</label>
                <input type="text" name="keterangan" placeholder="Contoh: Pensiun">
              </div>
            </div>
            <div class="ku-modal-foot">
              <button type="button" class="ku-btn ku-btn-ghost" onclick="closeModal('modalTarikTHT')">Batal</button>
              <button type="submit" class="ku-btn ku-btn-primary">Proses Realisasi</button>
            </div>
          </form>
        </div>
      </div>

      <div class="ku-modal-overlay" id="modalRiwayatTHT">
        <div class="ku-modal-box" style="max-width:860px">
          <div class="ku-modal-head">
            <div>
              <h3>Riwayat THT</h3>
              <p id="riwayatTHTSub">Memuat...</p>
            </div>
            <button type="button" class="ku-modal-close">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="ku-modal-body" id="riwayatTHTBody">
            <p style="color:var(--slate-400); text-align:center; padding:20px 0">Memuat data...</p>
          </div>
          <div class="ku-modal-foot" style="justify-content:center">
            <button type="button" class="ku-btn ku-btn-ghost ku-modal-close">Tutup</button>
          </div>
        </div>
      </div>

      <input type="hidden" id="csrfToken" value="<?= csrf_hash() ?>">

      <script>
      var _thtRiwayatGuru = null;

      function loadThtRiwayat(guruId){
        var modal = document.getElementById('modalRiwayatTHT');
        var body = document.getElementById('riwayatTHTBody');
        var sub = document.getElementById('riwayatTHTSub');
        body.innerHTML = '<p style="color:var(--slate-400); text-align:center; padding:20px 0">Memuat data...</p>';
        sub.textContent = 'Mengambil riwayat transaksi...';
        modal.classList.add('active');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/tht/riwayat/' + guruId, true);
        xhr.onload = function(){
          if (xhr.status === 200) {
            try {
              var res = JSON.parse(xhr.responseText);
              if (res.success) {
                body.innerHTML = res.html;
                sub.textContent = 'Riwayat transaksi';
                _thtRiwayatGuru = guruId;
              } else {
                body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal memuat data</p>';
              }
            } catch(e) {
              body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal memproses respons</p>';
            }
          } else {
            body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal memuat data (HTTP ' + xhr.status + ')</p>';
          }
        };
        xhr.onerror = function(){
          body.innerHTML = '<p style="color:var(--red-700); text-align:center; padding:20px 0">Gagal terhubung ke server</p>';
        };
        xhr.send();
      }

      document.addEventListener('click', function(e){
        var btn = e.target.closest('.tht-riwayat-btn');
        if (btn) { loadThtRiwayat(btn.dataset.guru); return; }

        var del = e.target.closest('.tht-hapus-btn');
        if (del) {
          if (!confirm('Hapus transaksi THT ini?')) return;
          var id = del.dataset.id;
          var csrf = (document.getElementById('csrfToken') || {}).value || '';
          var xhr = new XMLHttpRequest();
          xhr.open('POST', '/tht/hapus/' + id, true);
          xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
          xhr.onload = function(){
            if (_thtRiwayatGuru) loadThtRiwayat(_thtRiwayatGuru);
          };
          xhr.send();
        }
      });

      document.getElementById('setorGuruSearch')?.addEventListener('change', function(){
        var map = {};
        <?php foreach ($guruList as $g): ?>
        map["<?= esc($g['nama']) ?>"] = "<?= $g['id'] ?>";
        <?php endforeach; ?>
        document.getElementById('setorGuruIdHidden').value = map[this.value] || '';
      });
      </script>

    </div>
  </div>
</div>
<?= view('layout/footer') ?>
