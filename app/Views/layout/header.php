<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title ?? 'Sistem Keuangan Sekolah') ?></title>
<style>
:root{
  --navy-900:#0F172A;
  --navy-800:#16213A;
  --navy-700:#1E293B;
  --navy-600:#334155;
  --navy-100:#E2E8F0;

  --emerald-700:#047857;
  --emerald-600:#0D9488;
  --emerald-500:#10B981;
  --emerald-400:#34D399;
  --emerald-100:#D1FAE5;
  --emerald-50:#ECFDF5;

  --amber-500:#F59E0B;
  --amber-100:#FEF3C7;
  --amber-700:#B45309;

  --red-500:#EF4444;
  --red-100:#FEE2E2;
  --red-700:#B91C1C;

  --slate-900:#0F172A;
  --slate-700:#334155;
  --slate-500:#64748B;
  --slate-400:#94A3B8;
  --slate-200:#E5E9F0;
  --slate-100:#F1F5F9;
  --slate-50:#F8FAFC;
  --white:#FFFFFF;

  --r-sm:8px;
  --r-md:12px;
  --r-lg:16px;
  --shadow-sm: 0 1px 2px rgba(15,23,42,0.04), 0 1px 1px rgba(15,23,42,0.03);
  --shadow-md: 0 2px 8px rgba(15,23,42,0.06), 0 1px 3px rgba(15,23,42,0.04);
  --shadow-lg: 0 12px 32px -8px rgba(15,23,42,0.14), 0 4px 12px -4px rgba(15,23,42,0.08);
  --shadow-card: 0 1px 3px rgba(15,23,42,0.05), 0 1px 1px rgba(15,23,42,0.03), inset 0 0 0 1px rgba(15,23,42,0.04);

  --sidebar-w: 264px;
}

*{ margin:0; padding:0; box-sizing:border-box; }

html,body{
  font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background:var(--slate-50);
  color:var(--slate-900);
  -webkit-font-smoothing:antialiased;
  font-size:14px;
  line-height:1.5;
}

.money, .num, td.num{
  font-feature-settings:"tnum" 1, "lnum" 1;
  font-variant-numeric: tabular-nums lining-nums;
  letter-spacing:-0.01em;
}

a{ color:inherit; text-decoration:none; }
button{ font-family:inherit; cursor:pointer; border:none; background:none; }
ul{ list-style:none; }
img{ max-width:100%; display:block; }

button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible{
  outline:2px solid var(--emerald-500);
  outline-offset:2px;
  border-radius:4px;
}

.brand-logo{
  display:flex;
  align-items:center;
  gap:10px;
}
.brand-logo .mark{
  width:38px; height:38px;
  display:flex; align-items:center; justify-content:center;
  flex-shrink:0;
}
.brand-logo .mark svg{ width:21px; height:21px; }
.brand-logo .brand-text{ display:flex; flex-direction:column; line-height:1.15; }
.brand-logo .brand-text .school-name{ font-size:13.5px; font-weight:700; color:var(--white); }
.brand-logo .brand-text .school-unit{ font-size:13.5px; font-weight:700; color:var(--white); }
.brand-logo .brand-text .school-sub{ font-size:10.5px; color:var(--slate-400); font-weight:500; letter-spacing:0.02em; }

.brand-logo.on-light .brand-text .school-name{ color:var(--navy-900); }
.brand-logo.on-light .brand-text .school-sub{ color:var(--slate-500); }

.login-screen{
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  background:var(--white);
  padding:24px;
}

.login-left{
  width:100%;
  max-width:420px;
}
.login-card-wrap{ width:100%; max-width:400px; position:relative; z-index:2; }

.login-brand-block{ display:flex; align-items:center; gap:12px; margin-bottom:40px; }
.login-brand-block .mark{
  width:46px; height:46px;
  display:flex; align-items:center; justify-content:center;
}
.login-brand-block .mark svg{ width:24px; height:24px; }
.login-brand-block .name{ font-size:16px; font-weight:700; color:var(--navy-900); }
.login-brand-block .sub{ font-size:11.5px; color:var(--slate-500); font-weight:500; }

.login-card{
  background:var(--white);
  border-radius:var(--r-lg);
  box-shadow:var(--shadow-lg);
  border:1px solid var(--slate-100);
  padding:40px 36px;
}
.login-card h1{ font-size:22px; font-weight:700; color:var(--navy-900); margin-bottom:6px; }
.login-card p.lede{ font-size:13px; color:var(--slate-500); margin-bottom:30px; }

.field{ margin-bottom:18px; }
.field label{
  display:block; font-size:12.5px; font-weight:600; color:var(--navy-700);
  margin-bottom:7px; letter-spacing:0.01em;
}
.field .input-wrap{ position:relative; display:flex; align-items:center; }
.field .input-wrap > svg{
  position:absolute; left:14px; top:50%; transform:translateY(-50%);
  width:17px; height:17px; color:var(--slate-400); pointer-events:none; z-index:2;
}
.field input, .field select{
  width:100%;
  padding:12px 14px 12px 41px;
  border:1.5px solid var(--slate-200);
  border-radius:var(--r-sm);
  font-size:14px;
  font-family:inherit;
  color:var(--navy-900);
  background:var(--slate-50);
  transition:all .15s ease;
}
.input-wrap input[type="password"],
.input-wrap input[type="text"] { padding-right: 46px; }
.field input:focus, .field select:focus{
  outline:none;
  border-color:var(--navy-700);
  background:var(--white);
  box-shadow:0 0 0 3px rgba(15,23,42,0.06);
}
.field .toggle-pass{
  position:absolute; right:6px; top:50%; transform:translateY(-50%);
  color:var(--slate-400); padding:0; border-radius:6px; z-index:3;
  background:transparent; border:none; cursor:pointer;
  line-height:0;
}
.field .toggle-pass:hover{ color:var(--navy-700); background:var(--slate-100); }
.field .toggle-pass svg{ width:18px; height:18px; }
.toggle-pass .icon-eye{ display:block; }
.toggle-pass .icon-eye-off{ display:none; }
.toggle-pass.show-pass .icon-eye{ display:none; }
.toggle-pass.show-pass .icon-eye-off{ display:block; }

.login-meta-row{
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:24px; font-size:12.5px;
}
.checkbox-row{ display:flex; align-items:center; gap:7px; color:var(--slate-600); }
.checkbox-row input{ width:15px; height:15px; accent-color:var(--navy-900); }
.login-meta-row a{ color:var(--emerald-600); font-weight:600; }

.btn-primary-navy{
  width:100%;
  background:var(--navy-900);
  color:var(--white);
  font-size:14.5px;
  font-weight:600;
  padding:13px;
  border-radius:var(--r-sm);
  display:flex; align-items:center; justify-content:center; gap:8px;
  transition:all .15s ease;
  box-shadow:0 1px 2px rgba(15,23,42,0.1);
}
.btn-primary-navy:hover{ background:var(--navy-700); transform:translateY(-1px); box-shadow:var(--shadow-md); }
.btn-primary-navy svg{ width:17px; height:17px; }

.login-footnote{
  text-align:center; margin-top:22px; font-size:12px; color:var(--slate-400);
}
.security-strip{
  display:flex; align-items:center; justify-content:center; gap:6px;
  margin-top:28px; font-size:11.5px; color:var(--slate-400); font-weight:500;
}
.security-strip svg{ width:13px; height:13px; color:var(--emerald-500); }

.app-shell{ display:flex; min-height:100vh; }

.sidebar{
  width:var(--sidebar-w);
  background:var(--navy-900);
  flex-shrink:0;
  position:fixed;
  top:0; bottom:0; left:0;
  display:flex;
  flex-direction:column;
  z-index:50;
  border-right:1px solid rgba(255,255,255,0.06);
  overflow:hidden;
}
.sidebar-nav{ flex:1; overflow-y:auto; padding:16px 12px; min-height:0; }
.sidebar-footer{ flex-shrink:0; padding:14px 20px 20px; border-top:1px solid rgba(255,255,255,0.07); }
.sidebar-logo{
  padding:22px 20px;
  border-bottom:1px solid rgba(255,255,255,0.07);
}
.nav-section-label{
  font-size:10.5px; font-weight:700; color:var(--slate-500);
  text-transform:uppercase; letter-spacing:0.07em;
  padding:20px 12px 8px; margin-top:4px;
  border-top:1px solid rgba(255,255,255,0.06);
}
.nav-section-label:first-child{ border-top:none; padding-top:0; margin-top:0; }
.nav-item{
  display:flex; align-items:center; gap:11px;
  padding:10px 12px;
  border-radius:9px;
  color:var(--slate-400);
  font-size:13.5px; font-weight:500;
  margin-bottom:2px;
  transition:all .14s ease;
  position:relative;
}
.nav-item svg{ width:18px; height:18px; flex-shrink:0; }
.nav-item:hover{ background:rgba(255,255,255,0.05); color:var(--white); }
.nav-item.active{ background:var(--emerald-700); color:var(--white); }
.nav-item.active svg{ color:var(--white); }
.nav-item .nav-badge{
  margin-left:auto; background:var(--red-500); color:var(--white);
  font-size:10px; font-weight:700; padding:1px 6px; border-radius:100px;
}

.user-mini{ display:flex; align-items:center; gap:10px; }
.user-mini .avatar{
  width:36px; height:36px; border-radius:9px; background:var(--navy-700);
  display:flex; align-items:center; justify-content:center;
  color:var(--emerald-400); font-weight:700; font-size:13px;
  border:1px solid rgba(255,255,255,0.1);
}
.user-mini .name{ font-size:12.5px; font-weight:600; color:var(--white); }
.user-mini .role{ font-size:11px; color:var(--slate-500); }

.main-area{ margin-left:var(--sidebar-w); flex:1; min-width:0; }

.topbar{
  height:68px;
  background:var(--white);
  border-bottom:1px solid var(--slate-200);
  display:flex; align-items:center; justify-content:space-between;
  padding:0 28px;
  position:sticky; top:0; z-index:40;
}
.topbar .title-block h1{ font-size:17px; font-weight:700; color:var(--navy-900); }
.topbar .title-block .crumb{ font-size:11.5px; color:var(--slate-400); margin-top:1px; }
.topbar-actions{ display:flex; align-items:center; gap:16px; }
.icon-btn{
  width:38px; height:38px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  color:var(--slate-500); position:relative;
  transition:all .15s ease;
}
.icon-btn:hover{ background:var(--slate-100); color:var(--navy-900); }
.icon-btn svg{ width:19px; height:19px; }
.icon-btn .dot-alert{
  position:absolute; top:7px; right:8px; width:7px; height:7px;
  background:var(--red-500); border-radius:50%; border:2px solid var(--white);
}
.topbar-user{ display:flex; align-items:center; gap:10px; padding-left:14px; border-left:1px solid var(--slate-200); }
.topbar-user .avatar{
  width:36px; height:36px; border-radius:50%; background:var(--navy-100);
  display:flex; align-items:center; justify-content:center; color:var(--navy-700); font-weight:700; font-size:12.5px;
}
.topbar-user .name{ font-size:12.5px; font-weight:600; color:var(--navy-900); }
.topbar-user .role{ font-size:10.5px; color:var(--slate-400); }

.content{ padding:28px; max-width:1440px; }

.stat-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:18px; margin-bottom:24px; }
@media (max-width:1200px){ .stat-grid{ grid-template-columns:repeat(2,1fr); } }

.stat-card{
  background:var(--white);
  border-radius:var(--r-md);
  box-shadow:var(--shadow-card);
  padding:20px 20px 20px 22px;
  position:relative;
  overflow:hidden;
  transition:transform .15s ease, box-shadow .15s ease;
}
.stat-card:hover{ transform:translateY(-2px); box-shadow:var(--shadow-md); }
.stat-card::before{
  content:''; position:absolute; left:0; top:14px; bottom:14px; width:3px;
  border-radius:0 4px 4px 0;
  background:var(--ledger-color, var(--emerald-500));
}
.stat-card .stat-top{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; }
.stat-card .stat-icon{
  width:38px; height:38px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  background:var(--icon-bg, var(--emerald-50));
  color:var(--icon-color, var(--emerald-600));
}
.stat-card .stat-icon svg{ width:19px; height:19px; }
.stat-card .stat-trend{
  font-size:11px; font-weight:700; display:flex; align-items:center; gap:3px;
  padding:3px 8px; border-radius:100px;
}
.stat-trend.up{ background:var(--emerald-50); color:var(--emerald-600); }
.stat-trend.down{ background:var(--red-100); color:var(--red-700); }
.stat-trend svg{ width:10px; height:10px; }
.stat-card .stat-label{ font-size:12px; color:var(--slate-500); font-weight:500; margin-bottom:6px; }
.stat-card .stat-value{ font-size:25px; font-weight:700; color:var(--navy-900); }
.stat-card .stat-value .unit{ font-size:14px; font-weight:600; color:var(--slate-400); margin-left:2px; }
.stat-card .stat-sub{ font-size:11.5px; color:var(--slate-400); margin-top:6px; }

.menu-cards{ display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:24px; }
@media (max-width:900px){ .menu-cards{ grid-template-columns:1fr; } }
.menu-card{
  border-radius:var(--r-md);
  padding:26px;
  position:relative;
  overflow:hidden;
  cursor:pointer;
  transition:transform .18s ease, box-shadow .18s ease;
  box-shadow:var(--shadow-card);
}
.menu-card:hover{ transform:translateY(-3px); box-shadow:var(--shadow-lg); }
.menu-card.savings{ background:linear-gradient(135deg, var(--navy-900) 0%, var(--navy-700) 100%); color:var(--white); }
.menu-card.billing{ background:var(--white); border:1px solid var(--slate-200); }
.menu-card .mc-icon{
  width:46px; height:46px; border-radius:12px;
  display:flex; align-items:center; justify-content:center; margin-bottom:18px;
}
.menu-card.savings .mc-icon{ background:rgba(16,185,129,0.18); color:var(--emerald-400); }
.menu-card.billing .mc-icon{ background:var(--emerald-50); color:var(--emerald-600); }
.menu-card .mc-icon svg{ width:23px; height:23px; }
.menu-card h3{ font-size:16.5px; font-weight:700; margin-bottom:6px; }
.menu-card.billing h3{ color:var(--navy-900); }
.menu-card p{ font-size:12.5px; opacity:0.75; margin-bottom:20px; max-width:280px; }
.menu-card.billing p{ color:var(--slate-500); opacity:1; }
.menu-card .mc-footer{ display:flex; align-items:center; justify-content:space-between; }
.menu-card .mc-link{ font-size:12.5px; font-weight:700; display:flex; align-items:center; gap:5px; }
.menu-card.savings .mc-link{ color:var(--emerald-400); }
.menu-card.billing .mc-link{ color:var(--emerald-600); }
.menu-card .mc-link svg{ width:14px; height:14px; }
.menu-card .mc-stat{ font-size:11px; opacity:0.6; }
.menu-card::after{
  content:''; position:absolute; width:160px; height:160px; border-radius:50%;
  background:radial-gradient(circle, rgba(16,185,129,0.15), transparent 70%);
  top:-60px; right:-60px;
}

.panel-grid{ display:grid; grid-template-columns:1.6fr 1fr; gap:18px; margin-bottom:24px; align-items:stretch; }
@media (max-width:1100px){ .panel-grid{ grid-template-columns:1fr; } }

.panel{
  background:var(--white);
  border-radius:var(--r-md);
  box-shadow:var(--shadow-card);
  padding:22px;
}
.panel-head{ display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
.panel-head h3{ font-size:14.5px; font-weight:700; color:var(--navy-900); }
.panel-head .panel-sub{ font-size:11.5px; color:var(--slate-400); margin-top:2px; }
.seg-control{ display:flex; background:var(--slate-100); border-radius:8px; padding:3px; }
.seg-control button{
  font-size:11.5px; font-weight:600; padding:6px 12px; border-radius:6px; color:var(--slate-500);
}
.seg-control button.active{ background:var(--white); color:var(--navy-900); box-shadow:var(--shadow-sm); }

.legend-row{ display:flex; gap:18px; margin-bottom:4px; }
.legend-item{ display:flex; align-items:center; gap:6px; font-size:11.5px; color:var(--slate-500); font-weight:500; }
.legend-dot{ width:8px; height:8px; border-radius:50%; }

.chart-area{ height:220px; display:flex; align-items:flex-end; gap:14px; padding-top:10px; }
.chart-col{ flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; }
.chart-col .bars-wrap{ width:100%; display:flex; gap:4px; align-items:flex-end; height:170px; }
.chart-col .bar{ flex:1; border-radius:5px 5px 2px 2px; transition:opacity .15s; }
.chart-col .bar.income{ background:var(--emerald-500); }
.chart-col .bar.deposit{ background:var(--amber-400, #F59E0B); }
.chart-col .bar.withdrawal{ background:var(--red-500, #EF4444); }
.chart-col .bar.expense{ background:var(--navy-200, #CBD5E1); }
.chart-col:hover .bar{ opacity:0.85; }
.chart-col .month-label{ font-size:10.5px; color:var(--slate-400); font-weight:600; }
.chart-col .chart-total{ font-size:9.5px; color:var(--slate-500); font-weight:700; white-space:nowrap; }
.chart-col .chart-val{ font-size:8px; font-weight:600; line-height:1.2; white-space:nowrap; }
.chart-col .income-val{ color:var(--emerald-600, #059669); }
.chart-col .deposit-val{ color:var(--amber-500, #D97706); }
.chart-col .withdrawal-val{ color:var(--red-500, #EF4444); }

.progress-bar-wrap{ display:flex; align-items:center; gap:8px; }
.progress-track{ width:100px; height:8px; background:var(--slate-100); border-radius:4px; overflow:hidden; flex-shrink:0; }
.progress-track .progress-bar{ height:100%; border-radius:4px; transition:width .3s; min-width:0; }
.progress-bar-wrap .progress-label{ font-size:11px; font-weight:600; color:var(--slate-500); min-width:32px; white-space:nowrap; }

.stat-mini-row{ margin-top:6px; font-size:12px; }
.stat-mini-row .mini-label{ font-weight:600; color:var(--slate-500); min-width:24px; display:inline; }
.stat-mini-row .mini-amount{ font-weight:700; color:var(--slate-700); white-space:nowrap; font-size:12px; }
.stat-mini-row .mini-target{ font-weight:400; color:var(--slate-400); font-size:11px; }

.activity-list{ display:flex; flex-direction:column; gap:4px; }
.activity-item .a-avatar{ width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:#fff; flex-shrink:0; }
.a-badge{ display:inline-block; font-size:9.5px; font-weight:700; padding:1px 7px; border-radius:4px; margin-right:4px; text-transform:uppercase; letter-spacing:.3px; }
.a-badge.badge-bill{ background:#EFF6FF; color:#2563EB; }
.a-badge.badge-deposit{ background:#ECFDF5; color:#059669; }
.a-badge.badge-withdraw{ background:#FEF2F2; color:#DC2626; }
.top-rek-list{ display:flex; flex-direction:column; gap:2px; }
.top-rek-item{ display:flex; align-items:center; gap:10px; padding:9px 0; border-bottom:1px solid var(--slate-50); }
.top-rek-item:last-child{ border-bottom:none; }
.top-rank{ width:20px; font-size:14px; font-weight:800; text-align:center; flex-shrink:0; }
.top-rek-body{ flex:1; min-width:0; }
.top-rek-name{ font-size:13px; font-weight:600; color:var(--navy-900); }
.top-rek-meta{ font-size:10.5px; color:var(--slate-400); }
.top-rek-saldo{ font-size:13px; font-weight:700; color:var(--emerald-600); white-space:nowrap; }
.activity-item{
  display:flex; align-items:center; gap:12px; padding:11px 4px;
  border-bottom:1px solid var(--slate-100);
}
.activity-item:last-child{ border-bottom:none; }
.activity-item:hover{ background:var(--slate-50); border-radius:8px; margin:0 -4px; padding:11px 8px; }
.activity-item:hover + .activity-item{ border-top-color:transparent; }
.activity-item .a-icon{
  width:36px; height:36px; border-radius:10px; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
}
.activity-item .a-icon svg{ width:17px; height:17px; }
.activity-item .a-icon.in{ background:var(--emerald-50); color:var(--emerald-600); }
.activity-item .a-icon.out{ background:var(--red-100); color:var(--red-700); }
.activity-item .a-body{ flex:1; min-width:0; }
.activity-item .a-name{ font-size:13px; font-weight:600; color:var(--navy-900); }
.activity-item .a-meta{ font-size:11px; color:var(--slate-400); }
.activity-item .a-amount{ font-size:13px; font-weight:700; flex-shrink:0; }
.activity-item .a-amount.pos{ color:var(--emerald-600); }
.activity-item .a-amount.neg{ color:var(--red-700); }

.see-all-link{
  display:flex; align-items:center; justify-content:center; gap:6px;
  font-size:12.5px; font-weight:600; color:var(--navy-700);
  padding:10px; margin-top:8px; border-radius:9px; border:1px solid var(--slate-200);
  transition:all .15s;
}
.see-all-link:hover{ background:var(--slate-50); }
.see-all-link svg{ width:13px; height:13px; }

.page-header{ display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:22px; flex-wrap:wrap; gap:14px; }
.page-header h1{ font-size:20px; font-weight:700; color:var(--navy-900); margin-bottom:4px; }
.page-header .desc{ font-size:12.5px; color:var(--slate-500); }
.btn-row{ display:flex; gap:10px; }

.btn{
  display:inline-flex; align-items:center; gap:7px;
  font-size:13px; font-weight:600; padding:10px 16px; border-radius:9px;
  transition:all .15s ease;
}
.btn svg{ width:15px; height:15px; }
.btn-navy{ background:var(--navy-900); color:var(--white); box-shadow:0 1px 2px rgba(15,23,42,0.1); }
.btn-navy:hover{ background:var(--navy-700); transform:translateY(-1px); }
.btn-emerald{ background:var(--emerald-500); color:var(--white); }
.btn-emerald:hover{ background:var(--emerald-600); transform:translateY(-1px); }
.btn-outline{ background:var(--white); color:var(--navy-700); border:1.5px solid var(--slate-200); }
.btn-outline:hover{ border-color:var(--navy-700); background:var(--slate-50); }
.btn-red{ background:var(--red-100); color:var(--red-700); }
.btn-red:hover{ background:#FECACA; }
.btn-sm{ padding:7px 12px; font-size:12px; }

.toolbar{
  display:flex; align-items:center; gap:12px; margin-bottom:18px; flex-wrap:wrap;
  background:var(--white); padding:14px 16px; border-radius:var(--r-md); box-shadow:var(--shadow-card);
}
.search-box{
  display:flex; align-items:center; gap:9px; background:var(--slate-50);
  border:1.5px solid var(--slate-200); border-radius:9px; padding:9px 13px; flex:1; min-width:220px; max-width:340px;
}
.search-box svg{ width:16px; height:16px; color:var(--slate-400); flex-shrink:0; }
.search-box input{ border:none; background:none; outline:none; font-size:13px; width:100%; font-family:inherit; }
.select-filter{
  display:flex; align-items:center; gap:8px; background:var(--slate-50); border:1.5px solid var(--slate-200);
  border-radius:9px; padding:9px 12px; font-size:12.5px; font-weight:500; color:var(--navy-700);
}
.select-filter svg{ width:13px; height:13px; color:var(--slate-400); }

.tabs-switch{ display:flex; background:var(--slate-100); border-radius:10px; padding:4px; width:fit-content; margin-bottom:18px; }
.tabs-switch button{
  font-size:13px; font-weight:600; color:var(--slate-500); padding:9px 22px; border-radius:8px;
  transition:all .15s;
}
.tabs-switch button.active{ background:var(--white); color:var(--navy-900); box-shadow:var(--shadow-sm); }

.data-table-wrap{
  background:var(--white); border-radius:var(--r-md); box-shadow:var(--shadow-card); overflow:hidden;
}
table.data-table{ width:100%; border-collapse:collapse; }
table.data-table thead th{
  text-align:left; font-size:11px; font-weight:700; color:var(--slate-500);
  text-transform:uppercase; letter-spacing:0.04em;
  padding:13px 18px; background:var(--slate-50); border-bottom:1px solid var(--slate-200);
}
table.data-table tbody td{
  padding:14px 18px; font-size:13px; color:var(--navy-900); border-bottom:1px solid var(--slate-100);
  vertical-align:middle;
}
table.data-table tbody tr:last-child td{ border-bottom:none; }
table.data-table tbody tr{ transition:background .12s; }
table.data-table tbody tr:hover{ background:var(--slate-50); }
table.data-table td.num{ font-weight:700; }
.cell-person{ display:flex; align-items:center; gap:10px; }
.cell-person .avatar-sm{
  width:32px; height:32px; border-radius:8px; background:var(--navy-100); color:var(--navy-700);
  display:flex; align-items:center; justify-content:center; font-weight:700; font-size:11.5px; flex-shrink:0;
}
.cell-person .p-name{ font-weight:600; }
.cell-person .p-sub{ font-size:11px; color:var(--slate-400); }

.badge{
  display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700;
  padding:4px 10px; border-radius:100px;
}
.badge .bdot{ width:6px; height:6px; border-radius:50%; }
.badge.success{ background:var(--emerald-100); color:#047857; }
.badge.success .bdot{ background:#047857; }
.badge.warning{ background:var(--amber-100); color:var(--amber-700); }
.badge.warning .bdot{ background:var(--amber-700); }
.badge.danger{ background:var(--red-100); color:var(--red-700); }
.badge.danger .bdot{ background:var(--red-700); }

.row-actions{ display:flex; gap:6px; }
.row-actions button{
  width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center;
  color:var(--slate-500); transition:all .15s;
}
.row-actions button:hover{ background:var(--slate-100); color:var(--navy-900); }
.row-actions button svg{ width:15px; height:15px; }

.table-footer{
  display:flex; justify-content:space-between; align-items:center; padding:14px 18px;
  border-top:1px solid var(--slate-100); font-size:12px; color:var(--slate-500);
}
.pagination{ display:flex; gap:4px; }
.pagination button{
  width:30px; height:30px; border-radius:7px; font-size:12px; font-weight:600; color:var(--slate-500);
}
.pagination button.active{ background:var(--navy-900); color:var(--white); }
.pagination button:hover:not(.active){ background:var(--slate-100); }

.summary-trio{ display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-bottom:24px; }
@media (max-width:800px){ .summary-trio{ grid-template-columns:1fr; } }

.modal-overlay{
  position:fixed; inset:0; background:rgba(15,23,42,0.55); backdrop-filter:blur(3px);
  display:none; align-items:center; justify-content:center; z-index:200; padding:24px;
}
.modal-overlay.active{ display:flex; }
.modal-box{
  background:var(--white); border-radius:var(--r-lg); box-shadow:var(--shadow-lg);
  width:100%; max-width:460px; overflow:hidden;
}
.modal-head{ padding:22px 24px 18px; border-bottom:1px solid var(--slate-100); display:flex; justify-content:space-between; align-items:flex-start; }
.modal-head h3{ font-size:16px; font-weight:700; color:var(--navy-900); }
.modal-head p{ font-size:12px; color:var(--slate-500); margin-top:3px; }
.modal-close{ width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--slate-400); }
.modal-close:hover{ background:var(--slate-100); }
.modal-close svg{ width:16px; height:16px; }
.modal-body{ padding:22px 24px; }
.modal-foot{ padding:18px 24px 22px; display:flex; gap:10px; border-top:1px solid var(--slate-100); }
.modal-foot .btn{ flex:1; justify-content:center; }

.tx-type-toggle{ display:flex; gap:10px; margin-bottom:20px; }
.tx-type-toggle button{
  flex:1; padding:12px; border-radius:10px; border:1.5px solid var(--slate-200);
  font-size:13px; font-weight:600; color:var(--slate-500); display:flex; align-items:center; justify-content:center; gap:7px;
}
.tx-type-toggle button svg{ width:16px; height:16px; }
.tx-type-toggle button.active.deposit{ border-color:var(--emerald-500); background:var(--emerald-50); color:var(--emerald-600); }
.tx-type-toggle button.active.withdraw{ border-color:var(--red-500); background:var(--red-100); color:var(--red-700); }

.amount-input-wrap{ position:relative; }
.amount-input-wrap .prefix{
  position:absolute; left:14px; top:50%; transform:translateY(-50%);
  font-weight:700; color:var(--slate-500); font-size:14px;
}
.ku-field .amount-input-wrap input{ padding-left:42px; font-weight:700; font-size:16px; }
.field textarea{
  width:100%; padding:12px 14px; border:1.5px solid var(--slate-200); border-radius:var(--r-sm);
  font-family:inherit; font-size:13px; resize:vertical; min-height:70px; background:var(--slate-50);
}
.field textarea:focus{ outline:none; border-color:var(--navy-700); background:var(--white); }
.balance-preview{
  background:var(--slate-50); border:1px solid var(--slate-200); border-radius:10px; padding:14px 16px;
  display:flex; justify-content:space-between; align-items:center; margin-top:4px;
}
.balance-preview .bp-label{ font-size:11.5px; color:var(--slate-500); }
.balance-preview .bp-value{ font-size:15px; font-weight:700; color:var(--navy-900); }

.report-summary-row{ display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:24px; }
@media (max-width:1200px){ .report-summary-row{ grid-template-columns:repeat(2,1fr); } }

.date-filter-bar{
  display:flex; align-items:center; gap:12px; background:var(--white); padding:14px 16px;
  border-radius:var(--r-md); box-shadow:var(--shadow-card); margin-bottom:20px; flex-wrap:wrap;
}
.date-filter-bar .df-group{ display:flex; align-items:center; gap:8px; background:var(--slate-50); border:1.5px solid var(--slate-200); border-radius:9px; padding:9px 13px; }
.date-filter-bar .df-group svg{ width:15px; height:15px; color:var(--slate-400); }
.date-filter-bar .df-group span{ font-size:12.5px; font-weight:500; color:var(--navy-700); }
.date-filter-bar .divider-line{ width:1px; height:22px; background:var(--slate-200); }

.bigchart-panel{ background:var(--white); border-radius:var(--r-md); box-shadow:var(--shadow-card); padding:24px; margin-bottom:24px; }
.line-chart-svg{ width:100%; height:auto; }

.dual-panel{ display:grid; grid-template-columns:1fr 1fr; gap:18px; }
@media (max-width:1000px){ .dual-panel{ grid-template-columns:1fr; } }

.donut-legend{ display:flex; flex-direction:column; gap:12px; margin-top:18px; }
.donut-legend-item{ display:flex; align-items:center; gap:10px; }
.donut-legend-item .dl-dot{ width:10px; height:10px; border-radius:3px; flex-shrink:0; }
.donut-legend-item .dl-label{ font-size:12.5px; color:var(--slate-600); flex:1; }
.donut-legend-item .dl-value{ font-size:12.5px; font-weight:700; color:var(--navy-900); }

.footer-note{ text-align:center; padding:30px 0 10px; font-size:11.5px; color:var(--slate-400); }
.text-center{ text-align:center; }
.text-emerald{ color:var(--emerald-600); }
.text-red{ color:var(--red-700); }
.mb-4{ margin-bottom:18px; }
.mt-4{ margin-top:18px; }

.alert{
  padding:12px 16px; border-radius:9px; font-size:13px; font-weight:600; margin-bottom:18px;
}
.alert-error{ background:var(--red-100); color:var(--red-700); border:1px solid rgba(239,68,68,0.2); }
.alert-success{ background:var(--emerald-100); color:#047857; border:1px solid rgba(16,185,129,0.2); }

.toolbar select.select-filter,
.date-filter-bar select.select-filter {
  appearance: auto;
  -webkit-appearance: auto;
  -moz-appearance: auto;
  display:flex; align-items:center; gap:8px; background:var(--slate-50); border:1.5px solid var(--slate-200);
  border-radius:9px; padding:9px 12px; font-size:12.5px; font-weight:500; color:var(--navy-700);
  font-family:inherit; cursor:pointer; min-width:140px;
}
.toolbar select.select-filter:focus,
.date-filter-bar select.select-filter:focus {
  outline:none; border-color:var(--navy-700); box-shadow:0 0 0 3px rgba(15,23,42,0.06);
}
input.df-input {
  border:none; background:none; outline:none; font-size:12.5px; font-weight:500; color:var(--navy-700);
  font-family:inherit; width:140px;
}
input.df-input:focus { outline:none; }

.hamburger{ display:none; }

@media (max-width:1024px){
  .sidebar{ width:60px; }
  .sidebar .brand-text, .sidebar .nav-item span, .sidebar .nav-section-label, .sidebar .user-mini .name, .sidebar .user-mini .role{ display:none; }
  .sidebar .nav-item{ justify-content:center; padding:10px; }
  .sidebar .nav-item.active::before{ display:none; }
  .sidebar .sidebar-logo{ padding:18px 10px; }
  .sidebar .sidebar-footer{ padding:12px 10px; }
  .sidebar .user-mini{ justify-content:center; }
  .main-area{ margin-left:60px; }
}

@media (max-width:768px){
  .sidebar{ transform:translateX(-100%); width:280px; transition:transform .25s ease; }
  .sidebar.open{ transform:translateX(0); }
  .sidebar .brand-text, .sidebar .nav-item span, .sidebar .nav-section-label, .sidebar .user-mini .name, .sidebar .user-mini .role{ display:block; }
  .sidebar .nav-item{ justify-content:flex-start; padding:10px 12px; }
  .sidebar .sidebar-logo{ padding:22px 20px; }
  .sidebar .sidebar-footer{ padding:14px 20px 20px; }
  .sidebar .user-mini{ justify-content:flex-start; }
  .main-area{ margin-left:0; }
  .sidebar-overlay{ display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:49; }
  .sidebar-overlay.open{ display:block; }

  .topbar{ padding:0 16px; height:58px; }
  .topbar .title-block h1{ font-size:15px; }
  .topbar-user .avatar{ width:30px; height:30px; font-size:11px; }
  .topbar-user .name{ font-size:11px; }

  .hamburger{ display:flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; color:var(--slate-600); margin-right:8px; }
  .hamburger svg{ width:20px; height:20px; }
  .hamburger:hover{ background:var(--slate-100); }
  .hamburger:not(.topbar .hamburger){ display:none; }

  .content{ padding:16px; }
  .stat-grid{ grid-template-columns:1fr; gap:12px; }
  .page-header{ flex-direction:column; align-items:stretch; gap:12px; }
  .page-header .btn-row{ justify-content:stretch; }
  .page-header .btn-row .btn{ flex:1; justify-content:center; }

  .tabs-switch{ width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
  .tabs-switch button{ padding:9px 16px; font-size:12px; white-space:nowrap; }

  .toolbar{ flex-direction:column; align-items:stretch; gap:10px; padding:12px; }
  .search-box{ max-width:none; min-width:0; width:100%; }

  table.data-table{ min-width:600px; }
  .data-table-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
  table.data-table thead th{ padding:10px 12px; font-size:10px; }
  table.data-table tbody td{ padding:10px 12px; font-size:12px; }
  .cell-person .avatar-sm{ width:26px; height:26px; font-size:10px; }
  .row-actions button{ width:28px; height:28px; }
  .badge{ font-size:10px; padding:3px 8px; }
  .table-footer{ flex-direction:column; gap:8px; text-align:center; }
  .stat-card{ padding:16px; }
  .stat-card .stat-value{ font-size:22px; }

  .modal-overlay{ padding:12px; align-items:flex-end; }
  .modal-box{ max-width:none; border-radius:var(--r-lg) var(--r-lg) 0 0; max-height:90vh; overflow-y:auto; }
  .modal-head{ padding:18px 20px 14px; }
  .modal-head h3{ font-size:15px; }
  .modal-body{ padding:18px 20px; }
  .modal-foot{ padding:14px 20px 18px; }
  .tx-type-toggle{ gap:8px; }
  .tx-type-toggle button{ padding:10px; font-size:12px; }
  .field input, .field select{ padding:11px 14px; font-size:13px; }

  .summary-trio{ grid-template-columns:1fr; gap:12px; }
  .report-summary-row{ grid-template-columns:1fr 1fr; gap:12px; }
  .menu-cards{ gap:12px; }
  .panel-grid{ gap:12px; }
  .panel{ padding:16px; }

  .date-filter-bar{ flex-direction:column; align-items:stretch; gap:10px; padding:12px; }
  .date-filter-bar .df-group{ width:100%; }
  .date-filter-bar .divider-line{ display:none; }
  .dual-panel{ gap:12px; }

  .alert{ font-size:12px; padding:10px 14px; }
  .page-header .desc{ font-size:11px; }
  .activity-item{ padding:9px 4px; }
  .activity-item .a-name{ font-size:12px; }
  .activity-item .a-amount{ font-size:12px; }
  .brand-logo .brand-text .school-name{ font-size:12px; }
  .brand-logo .brand-text .school-sub{ font-size:9px; }
}

@media (max-width:480px){
  .content{ padding:12px; }
  .stat-grid{ gap:10px; }
  table.data-table thead th{ padding:8px 10px; font-size:9px; }
  table.data-table tbody td{ padding:8px 10px; font-size:11px; }
  .cell-person .avatar-sm{ width:22px; height:22px; font-size:9px; }
  .badge{ font-size:9px; padding:2px 6px; }
  .stat-card .stat-value{ font-size:20px; }
  .tabs-switch button{ padding:8px 12px; font-size:11px; }
  .search-box{ padding:7px 10px; }
  .search-box input{ font-size:12px; }
  .select-filter{ font-size:11px; padding:7px 10px; }
}
</style>
<link rel="stylesheet" href="/assets/css/ku.css?<?= date('YmdHi') ?>">
</head>
<body>
