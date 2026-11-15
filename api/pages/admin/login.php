<?php
// =========================================================
// HALAMAN LOGIN CMS
// =========================================================
if (admin_login()) {
    redirect('/admin/dashboard');
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $akun = q1('SELECT id, password_hash FROM admin WHERE username = ?', [$username]);

    // password_verify membandingkan dengan hash, password asli tidak pernah disimpan
    if ($akun && password_verify($password, $akun['password_hash'])) {
        mulai_login((int) $akun['id']);
        redirect('/admin/dashboard');
    }
    sleep(1); // memperlambat orang yang mencoba menebak password berkali-kali
    $error = 'Nama pengguna atau kata sandi salah.';
}
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title>Masuk CMS - <?= e(setting('nama_desa')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background:#EEF1E6">
  <form class="login" method="post" action="/admin">
    <h2>Masuk ke CMS</h2>
    <p class="muted">Untuk perangkat desa yang mengelola isi website <?= e(setting('nama_desa')) ?>.</p>
    <?php if (!empty($_GET['ok'])): ?><div class="alert alert-ok" role="status"><?= e($_GET['ok']) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-err" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="field"><label for="username">Nama pengguna</label><input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus></div>
    <div class="field"><label for="password">Kata sandi</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="btn btn-dark" type="submit" style="width:100%">Masuk</button>
    <p class="hint" style="margin-top:14px"><a href="/">Kembali ke website</a></p>
  </form>
</body>
</html>
