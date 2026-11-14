<?php
require __DIR__ . '/config.php';

try {
    $baris  = db()->query('SELECT pesan FROM tes_koneksi ORDER BY id DESC LIMIT 1')->fetch();
    $status = 'Terhubung ke database';
} catch (Throwable $e) {
    $baris  = null;
    $status = 'Gagal terhubung: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Desa Marga Mulya</title>
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
  <main class="kartu">
    <h1>Halo, Desa Marga Mulya!</h1>
    <p>Versi PHP: <strong><?= PHP_VERSION ?></strong></p>
    <p>Status: <strong><?= htmlspecialchars($status) ?></strong></p>
    <?php if ($baris): ?>
      <p>Pesan dari database: <em><?= htmlspecialchars($baris['pesan']) ?></em></p>
    <?php endif; ?>
  </main>
</body>
</html>