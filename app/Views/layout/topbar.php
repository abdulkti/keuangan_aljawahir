<?php
$nama = $user['nama'] ?? 'User';
$inits = strtoupper(substr($nama, 0, 1) . (strpos($nama, ' ') ? substr($nama, strpos($nama, ' ') + 1, 1) : substr($nama, 1, 1)));

$role = $user['role'] ?? '';
$isYayasanRole = in_array($role, ['admin', 'superadmin']);
$notifCount = 0;
$notifItems = [];
if ($isYayasanRole) {
    $db = \Config\Database::connect();
    $notifCount = (int) $db->table('tb_pengajuan_dana')->where('status', 'pending')->countAllResults();
    $notifItems = $db->table('tb_pengajuan_dana p')
        ->select('p.id, p.tanggal, p.keterangan, p.jumlah, p.unit_id, p.created_at, u.nama AS unit_nama')
        ->join('tb_unit u', 'u.id = p.unit_id', 'left')
        ->where('p.status', 'pending')
        ->orderBy('p.created_at', 'DESC')
        ->limit(8)
        ->get()->getResultArray();
}
?>
<?php if ($isYayasanRole): ?>
<style>
.notif-wrap{ position:relative; }
.notif-badge{
  position:absolute; top:4px; right:3px;
  min-width:17px; height:17px; padding:0 4px;
  background:var(--red-500); color:#fff;
  font-size:10px; font-weight:700; line-height:17px; text-align:center;
  border-radius:100px; border:2px solid #fff;
}
.notif-dropdown{
  position:absolute; right:0; top:46px; width:330px;
  background:#fff; border:1px solid var(--slate-200); border-radius:14px;
  box-shadow:var(--shadow-lg); overflow:hidden; display:none; z-index:60;
}
.notif-dropdown.open{ display:block; }
.notif-head{
  padding:14px 16px; border-bottom:1px solid var(--slate-100);
  display:flex; align-items:baseline; justify-content:space-between; gap:8px;
}
.notif-head strong{ font-size:13.5px; color:var(--navy-900); }
.notif-head span{ font-size:11px; color:var(--slate-400); white-space:nowrap; }
.notif-list{ max-height:340px; overflow-y:auto; }
.notif-item{
  display:block; padding:12px 16px; border-bottom:1px solid var(--slate-50);
  transition:background .12s;
}
.notif-item:hover{ background:var(--slate-50); }
.notif-item-title{ font-size:12.5px; font-weight:600; color:var(--navy-900); line-height:1.4; }
.notif-item-meta{ font-size:11px; color:var(--slate-400); margin-top:2px; }
.notif-empty{ padding:22px 16px; text-align:center; font-size:12.5px; color:var(--slate-400); }
.notif-foot{
  display:block; text-align:center; padding:11px; font-size:12px; font-weight:700;
  color:var(--emerald-600); border-top:1px solid var(--slate-100);
}
.notif-foot:hover{ background:var(--slate-50); }
</style>
<?php endif; ?>
<header class="topbar">
  <div style="display:flex;align-items:center;gap:4px">
    <button class="hamburger" id="hamburger-btn" aria-label="Toggle sidebar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
    </button>
    <div class="title-block">
      <h1><?= esc($pageTitle ?? 'Dashboard Keuangan') ?></h1>
      <div class="crumb"><?= esc($pageDesc ?? '') ?></div>
    </div>
  </div>
  <div class="topbar-actions">
    <?php if ($isYayasanRole): ?>
    <div class="notif-wrap" id="notif-wrap">
      <button class="icon-btn" id="notif-btn" type="button" aria-label="Notifikasi pengajuan dana">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <?php if ($notifCount > 0): ?><span class="notif-badge"><?= $notifCount > 99 ? '99+' : $notifCount ?></span><?php endif; ?>
      </button>
      <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-head">
          <strong>Pengajuan Dana</strong>
          <span><?= $notifCount ?> menunggu persetujuan</span>
        </div>
        <?php if (empty($notifItems)): ?>
        <div class="notif-empty">Tidak ada pengajuan tertunda.</div>
        <?php else: ?>
        <div class="notif-list">
          <?php foreach ($notifItems as $n): ?>
          <a class="notif-item" href="/kas-unit/pengajuan?status=pending">
            <div class="notif-item-title"><?= esc($n['keterangan']) ?></div>
            <div class="notif-item-meta"><?= esc($n['unit_nama'] ?? '-') ?> · Rp <?= number_format((float) $n['jumlah'], 0, ',', '.') ?> · <?= esc(date('d/m/Y', strtotime($n['tanggal']))) ?></div>
          </a>
          <?php endforeach; ?>
        </div>
        <a class="notif-foot" href="/kas-unit/pengajuan?status=pending">Lihat semua pengajuan →</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</header>
<?php if ($isYayasanRole): ?>
<script>
(function(){
  var wrap = document.getElementById('notif-wrap');
  var btn = document.getElementById('notif-btn');
  var dd = document.getElementById('notif-dropdown');
  if (!btn || !dd) return;
  btn.addEventListener('click', function(e){
    e.stopPropagation();
    dd.classList.toggle('open');
  });
  document.addEventListener('click', function(e){
    if (wrap && !wrap.contains(e.target)) dd.classList.remove('open');
  });
})();
</script>
<?php endif; ?>
