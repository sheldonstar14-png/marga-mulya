<?php
// =========================================================
// HALAMAN DATA DESA. Semua angka dari tabel statistik.
// Kategori dipilih lewat alamat, contoh: /data-desa?grup=Kesehatan
// =========================================================
$grupData = [
    'Penduduk'   => ['Kependudukan', 'Mata pencaharian'],
    'Pendidikan' => ['Pendidikan penduduk', 'Sekolah'],
    'Kesehatan'  => ['Kesehatan', 'Fasilitas kesehatan'],
    'Pertanian'  => ['Pertanian', 'Peternakan'],
    'Ekonomi'    => ['Ekonomi', 'Jalan desa'],
];
$grup = $_GET['grup'] ?? 'Penduduk';
if (!isset($grupData[$grup])) {
    $grup = 'Penduduk';
}

$judul = 'Data Desa - ' . setting('nama_desa');
$menu  = 'data-desa';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Data desa</h1>
  <p>Angka kependudukan, pendidikan, kesehatan, pertanian, ekonomi, dan infrastruktur <?= e(setting('nama_desa')) ?>. Pilih kategori untuk melihat rinciannya.</p>
</div>
<div class="wrap sec" style="padding-top:16px">
  <nav class="chips" aria-label="Kategori data">
    <?php foreach (array_keys($grupData) as $g): ?>
      <a class="chip <?= $g === $grup ? 'on' : '' ?>" href="/data-desa?grup=<?= urlencode($g) ?>" <?= $g === $grup ? 'aria-current="page"' : '' ?>><?= e($g) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="grid g2">
    <?php foreach ($grupData[$grup] as $kategori): ?>
      <div class="panel">
        <h3><?= e($kategori) ?></h3>
        <div class="bars"><?= bar_list(statistik($kategori)) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
