<?php
// Tampil untuk halaman yang sudah ada di menu tapi belum dibuat.
// File ini dihapus kalau semua halaman sudah jadi.
$judul = 'Segera hadir - ' . setting('nama_desa');
$menu  = $depan;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title sec">
  <h1>Halaman ini sedang dibuat</h1>
  <p>Halaman <b><?= e($path) ?></b> akan dibuat di fase berikutnya. Sementara itu, lihat <a href="/">Beranda</a> atau <a href="/profil">Profil desa</a>.</p>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
