<?php
// Halaman login gerbang privat. Sengaja tanpa database supaya tetap tampil walau DB bermasalah.
// Variabel dari gerbang.php: $error, $tujuan
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Masuk - Website dalam pengembangan</title>
<link rel="icon" href="/assets/img/favicon.png" type="image/png">
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:20px;background:#F6F0E2 url(/assets/img/pola-kontur.svg);font:18px/1.6 "Atkinson Hyperlegible","Segoe UI",system-ui,sans-serif;color:#0E1B24}
main{width:100%;max-width:420px;background:#fff;border-radius:28px;padding:36px 30px;box-shadow:0 14px 34px -14px rgba(14,27,36,.22)}
.logo{display:block;width:150px;height:auto;margin:0 auto 14px}
h1{font:800 1.5rem/1.25 "Plus Jakarta Sans","Segoe UI",sans-serif;margin:0 0 6px;text-align:center}
p{color:#475663;margin:0 0 22px;text-align:center;font-size:.95rem}
label{display:block;font-weight:700;margin-bottom:6px}
.kolom{position:relative}
input{width:100%;min-height:52px;border:1.5px solid #DDD5C3;border-radius:14px;padding:0 16px;font:inherit}
input:focus{outline:3px solid #0E1B24;outline-offset:2px;box-shadow:0 0 0 6px #7FD8D0}
button{width:100%;min-height:52px;margin-top:16px;border:0;border-radius:999px;background:#F08A5D;color:#0E1B24;font:700 1rem "Plus Jakarta Sans","Segoe UI",sans-serif;cursor:pointer}
button:hover{background:#F29A73}
button:focus-visible{outline:3px solid #0E1B24;outline-offset:2px}
.lihat{position:absolute;right:6px;top:6px;width:auto;min-height:40px;margin:0;padding:0 12px;background:#F6F0E2;font-size:.85rem}
.salah{background:#FBEBEB;color:#7A1F17;border-radius:12px;padding:10px 14px;margin-bottom:14px;font-size:.95rem}
small{display:block;text-align:center;color:#475663;margin-top:16px;font-size:.82rem}
</style>
</head>
<body>
<main>
  <img class="logo" src="/assets/img/logo-desa.png?v=2" alt="Logo Desa Marga Mulya" width="600" height="396">
  <h1>Website sedang dalam pengembangan</h1>
  <p><?= gerbang_mode() === 'cms' ? 'Masuk dengan akun CMS tim untuk melihat website.' : 'Masukkan password tim untuk melihat website.' ?></p>
  <?php if ($error): ?><div class="salah" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <form method="post" action="/masuk-situs">
    <input type="hidden" name="lanjut" value="<?= htmlspecialchars($tujuan, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (gerbang_mode() === 'cms'): ?>
      <label for="username">Nama pengguna</label>
      <input id="username" name="username" required autofocus autocomplete="username" value="<?= htmlspecialchars((string) ($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="margin-bottom:14px">
    <?php endif; ?>
    <label for="password">Password</label>
    <div class="kolom">
      <input id="password" name="password" type="password" required <?= gerbang_mode() === 'cms' ? '' : 'autofocus' ?> autocomplete="current-password">
      <button type="button" class="lihat" aria-controls="password" aria-pressed="false" onclick="var i=document.getElementById('password'),t=i.type==='password';i.type=t?'text':'password';this.setAttribute('aria-pressed',t);this.textContent=t?'Sembunyikan':'Lihat'">Lihat</button>
    </div>
    <button type="submit">Masuk</button>
  </form>
  <small><?= gerbang_mode() === 'cms' ? 'Gunakan akun yang sama dengan login CMS.' : 'Akses tetap tersimpan selama 7 hari di perangkat ini.' ?></small>
</main>
</body>
</html>
