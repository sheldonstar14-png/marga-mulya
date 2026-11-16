<?php
// Tampil kalau database tidak bisa dihubungi atau query gagal.
// Halaman ini sengaja tidak memakai header.php, karena header juga butuh database.
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gangguan database</title>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
  <div class="wrap page-title sec">
    <h1>Website sedang mengalami gangguan</h1>
    <p>Data desa tidak bisa dimuat. Coba muat ulang halaman beberapa saat lagi.</p>
    <?php $saranError = [
        'struktur' => 'Kode gangguan DB-STRUKTUR: tabel atau kolom baru belum ada di database. Jalankan database/migrasi-semua.sql di TiDB.',
        'koneksi'  => 'Kode gangguan DB-KONEKSI: website tidak bisa terhubung ke database. Periksa DB_HOST, DB_USER, DB_PASS, dan DB_SSL di Vercel.',
        'secret'   => 'Kode gangguan APP-SECRET: APP_SECRET belum diisi di Environment Variables Vercel.',
        'lain'     => 'Kode gangguan LAIN: buka Vercel > Logs untuk melihat detailnya.',
    ][$jenisError ?? 'lain']; ?>
    <p class="small muted"><?= htmlspecialchars($saranError) ?></p>
    <?php if (getenv('DB_SSL') !== '1'): ?>
      <!-- Detail error hanya tampil di laptop, tidak di website online -->
      <p class="panel"><b>Detail untuk developer:</b><br><?= htmlspecialchars($pesanError) ?></p>
    <?php endif; ?>
  </div>
</body>
</html>
