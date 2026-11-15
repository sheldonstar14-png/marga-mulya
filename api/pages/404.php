<?php
$judul = 'Halaman tidak ditemukan - ' . setting('nama_desa');
$menu  = '';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title sec">
  <h1>Halaman tidak ditemukan</h1>
  <p>Alamat <b>/<?= e($path) ?></b> tidak ada. Periksa lagi penulisannya, atau kembali ke <a href="/">Beranda</a>.</p>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
