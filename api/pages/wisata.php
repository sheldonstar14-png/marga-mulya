<?php
// =========================================================
// HALAMAN WISATA: daftar destinasi, peta potensi, budaya
// =========================================================
$wisata = q('SELECT id, slug, nama, jenis, harga, kuota, ringkas, foto, lat, lng FROM wisata WHERE aktif = 1 ORDER BY id');
$umkm   = q('SELECT nama_usaha, lat, lng FROM umkm WHERE lat IS NOT NULL AND lng IS NOT NULL');
$budaya = q('SELECT judul, waktu, isi FROM budaya ORDER BY id');

// Titik-titik untuk peta
$titik = [];
foreach ($wisata as $w) {
    $titik[] = ['lat' => (float) $w['lat'], 'lng' => (float) $w['lng'], 'nama' => $w['nama'], 'jenis' => 'wisata', 'url' => '/wisata/' . $w['slug']];
}
foreach ($umkm as $u) {
    $titik[] = ['lat' => (float) $u['lat'], 'lng' => (float) $u['lng'], 'nama' => $u['nama_usaha'], 'jenis' => 'umkm', 'url' => '/umkm'];
}
$titik[] = ['lat' => (float) setting('lat'), 'lng' => (float) setting('lng'), 'nama' => 'Kantor ' . setting('nama_desa'), 'jenis' => 'fasilitas', 'url' => '/kontak'];

$judul = 'Wisata - ' . setting('nama_desa');
$menu  = 'wisata';
$pakaiPeta = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Wisata dan budaya</h1>
  <p>Destinasi yang dikelola warga. Pilih destinasi untuk melihat cuaca, kuota harian, dan memesan kunjungan.</p>
</div>
<div class="wrap sec" style="padding-top:16px">
  <div class="grid g4">
    <?php foreach ($wisata as $w): ?>
      <a class="card link-card" href="/wisata/<?= e($w['slug']) ?>">
        <div class="art"><img src="<?= e($w['foto'] ?: '/assets/img/sawah.svg') ?>" alt="<?= e($w['nama']) ?>" loading="lazy"></div>
        <div class="card-body">
          <span class="tag"><?= e($w['jenis']) ?></span>
          <h3 style="margin:4px 0 0"><?= e($w['nama']) ?></h3>
          <p><?= e($w['ringkas']) ?></p>
          <div class="card-foot"><span class="price"><?= rupiah($w['harga']) ?></span><span class="small muted">Kuota <?= (int) $w['kuota'] ?>/hari</span></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="sec tint"><div class="wrap split">
  <div>
    <h2>Peta potensi desa</h2>
    <div class="peta" data-titik="<?= titik_peta($titik) ?>" role="region" aria-label="Peta destinasi, UMKM, dan kantor desa"></div>
    <div class="legend"><span style="--c:#E2A529">Wisata</span><span style="--c:#3F7D3A">UMKM</span><span style="--c:#1E2B22">Kantor desa</span></div>
  </div>
  <div>
    <h2>Budaya dan kesenian</h2>
    <div class="agenda">
      <?php foreach ($budaya as $b): ?>
        <div class="panel">
          <h3 style="margin-bottom:2px"><?= e($b['judul']) ?></h3>
          <span class="small muted"><?= e($b['waktu']) ?></span>
          <p style="margin:6px 0 0"><?= e($b['isi']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div></div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
