<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk - Sistem Keuangan Sekolah</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{height:100%}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;background:#f8fafc;color:#0f172a;-webkit-font-smoothing:antialiased;font-size:14px;line-height:1.5;display:flex;align-items:center;justify-content:center;padding:20px}
.login-wrap{width:100%;max-width:420px}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:36px}
.brand .logo{width:46px;height:46px;flex-shrink:0}
.brand .logo img{width:100%;height:100%;object-fit:contain;border-radius:8px}
.brand .brand-text{display:flex;flex-direction:column}
.brand .brand-text .name{font-size:16px;font-weight:700;color:#0f172a}
.brand .brand-text .sub{font-size:11.5px;color:#64748b;font-weight:500;margin-top:2px}
.card{background:#fff;border-radius:16px;box-shadow:0 12px 32px -8px rgba(15,23,42,0.14),0 4px 12px -4px rgba(15,23,42,0.08);border:1px solid #f1f5f9;padding:36px 32px}
.card h1{font-size:22px;font-weight:700;color:#0f172a;margin-bottom:6px}
.card .lede{font-size:13px;color:#64748b;margin-bottom:28px}
.alert{padding:11px 15px;border-radius:9px;font-size:13px;font-weight:600;margin-bottom:16px}
.alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
.alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.field{margin-bottom:18px}
.field label{display:block;font-size:12.5px;font-weight:600;color:#1e293b;margin-bottom:7px}
.field .input-wrap{position:relative;display:flex;align-items:center}
.field .input-wrap > svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:#94a3b8;pointer-events:none;z-index:2}
.field input{width:100%;padding:12px 14px 12px 42px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;font-family:inherit;color:#0f172a;background:#f8fafc;transition:all .15s ease}
.field input:focus{outline:none;border-color:#334155;background:#fff;box-shadow:0 0 0 3px rgba(15,23,42,0.06)}
.field .input-wrap input[type="password"]{padding-right:46px}
.toggle-pass{position:absolute;right:5px;top:50%;transform:translateY(-50%);background:transparent;border:none;cursor:pointer;padding:6px;border-radius:6px;color:#94a3b8;z-index:3;line-height:0;display:flex}
.toggle-pass:hover{background:#f1f5f9;color:#334155}
.toggle-pass svg{width:18px;height:18px}
.toggle-pass .eye-off{display:none}
.toggle-pass.shown .eye{display:none}
.toggle-pass.shown .eye-off{display:block}
.meta-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;font-size:12.5px}
.meta-row .check-row{display:flex;align-items:center;gap:7px;color:#475569;cursor:pointer}
.meta-row .check-row input{width:15px;height:15px;accent-color:#0f172a;cursor:pointer}
.meta-row a{color:#0d9488;font-weight:600;font-size:12.5px;text-decoration:none}
.meta-row a:hover{text-decoration:underline}
.btn{width:100%;background:#0f172a;color:#fff;font-size:14.5px;font-weight:600;padding:13px;border-radius:10px;border:none;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:all .15s ease;box-shadow:0 1px 2px rgba(15,23,42,0.1);font-family:inherit}
.btn:hover{background:#1e293b;transform:translateY(-1px);box-shadow:0 4px 12px rgba(15,23,42,0.12)}
.btn svg{width:17px;height:17px}
.security{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:26px;font-size:11.5px;color:#94a3b8;font-weight:500}
.security svg{width:13px;height:13px;color:#10b981}
.footnote{text-align:center;margin-top:22px;font-size:12px;color:#94a3b8}
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;z-index:200;padding:24px}
.modal-overlay.active{display:flex}
.modal-box{background:#fff;border-radius:16px;box-shadow:0 20px 60px -12px rgba(15,23,42,0.2);width:100%;max-width:380px;overflow:hidden}
.modal-head{padding:22px 24px 16px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:flex-start}
.modal-head h3{font-size:16px;font-weight:700;color:#0f172a}
.modal-head p{font-size:12px;color:#64748b;margin-top:3px}
.modal-head .close-btn{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#94a3b8;background:none;border:none;cursor:pointer}
.modal-head .close-btn:hover{background:#f1f5f9}
.modal-head .close-btn svg{width:16px;height:16px}
.modal-body{padding:20px 24px}
.modal-body p{font-size:13.5px;color:#475569;line-height:1.6}
.modal-foot{padding:16px 24px 22px;display:flex;gap:10px;border-top:1px solid #f1f5f9}
.modal-foot .btn{flex:1;justify-content:center;padding:11px}
@media(max-width:480px){.card{padding:28px 20px}.card h1{font-size:20px}.brand .logo{width:38px;height:38px}.brand{margin-bottom:28px}}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="brand">
    <div class="logo">
      <img src="/assets/images/logo-aljawahir.png" alt="Al-Jawahir">
    </div>
    <div class="brand-text">
      <div class="name"><?= esc($schoolName ?? 'Al-Jawahir Attarbawi') ?></div>
      <div class="sub">Sistem Keuangan Sekolah</div>
    </div>
  </div>

  <div class="card">
    <h1>Selamat Datang Kembali</h1>
    <p class="lede">Masuk untuk mengelola tabungan, tagihan, dan laporan keuangan sekolah.</p>

    <?php if (isset($error)): ?>
    <div class="alert alert-error"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="/auth/login">
      <?= csrf_field() ?>
      <div class="field">
        <label for="email">Email atau NIP</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
          <input id="email" name="email" type="text" placeholder="admin@aljawahir.sch.id" value="<?= old('email') ?>" required>
        </div>
      </div>

      <div class="field">
        <label for="password">Kata Sandi</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input id="password" name="password" type="password" placeholder="••••••••••" required>
          <button type="button" class="toggle-pass" aria-label="Tampilkan kata sandi">
            <svg class="eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <div class="meta-row">
        <label class="check-row">
          <input type="checkbox" checked> Ingat saya
        </label>
        <a href="javascript:void(0)" onclick="document.getElementById('modal-lupa').classList.add('active')">Lupa sandi?</a>
      </div>

      <button type="submit" class="btn">
        Masuk
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </button>
    </form>

    <div class="security">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
      Data terenkripsi & aman
    </div>
  </div>

  <p class="footnote">&copy; 2026 Sistem Keuangan Sekolah &middot; <?= esc($schoolName ?? 'Al-Jawahir Attarbawi') ?></p>
</div>

<div class="modal-overlay" id="modal-lupa">
  <div class="modal-box">
    <div class="modal-head">
      <div>
        <h3>Lupa Kata Sandi?</h3>
        <p>Jangan khawatir, admin bisa membantu</p>
      </div>
      <button type="button" class="close-btn" onclick="document.getElementById('modal-lupa').classList.remove('active')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p>Silakan hubungi admin untuk mereset kata sandi Anda.</p>
      <p style="margin-top:12px">Admin dapat mengatur ulang kata sandi melalui menu <strong>Pengaturan &rarr; Kelola Akun</strong>.</p>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn" onclick="document.getElementById('modal-lupa').classList.remove('active')">Mengerti</button>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.toggle-pass').forEach(function(btn){
  btn.addEventListener('click', function(){
    var inp = this.parentElement.querySelector('input');
    if(!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.classList.toggle('shown');
  });
});

document.getElementById('modal-lupa').addEventListener('click', function(e){
  if(e.target === this) this.classList.remove('active');
});
</script>
</body>
</html>
