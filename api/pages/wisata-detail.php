<?php
// =========================================================
// HALAMAN DETAIL WISATA, contoh alamat: /wisata/curug
// ($slug diisi oleh router)
// =========================================================
$w = q1('SELECT * FROM wisata WHERE slug = ? AND aktif = 1', [$slug]);
if (!$w) {
    http_response_code(404);
    $path = 'wisata/' . $slug;
    require __DIR__ . '/404.php';
    return;
}
$fasilitas = array_filter(array_map('trim', explode(',', $w['fasilitas'])));
$titik = [['lat' => (float) $w['lat'], 'lng' => (float) $w['lng'], 'nama' => $w['nama'], 'jenis' => 'pilih']];

$judul = $w['nama'] . ' - ' . setting('nama_desa');
$menu  = 'wisata';
$pakaiPeta = true;
$pakaiCuaca = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap sec">
  <p><a href="/wisata">Semua destinasi</a></p>
  <div class="split">
    <div>
      <div class="art" style="border-radius:var(--r-l);aspect-ratio:16/9"><img src="<?= e($w['foto'] ?: '/assets/img/sawah.svg') ?>" alt="<?= e($w['nama']) ?>"></div>
      <h1 style="margin-top:24px"><?= e($w['nama']) ?></h1>
      <p><?= nl2br(e($w['deskripsi'])) ?></p>
      <div class="facts">
        <div class="fact"><span>Tiket per orang</span><b><?= rupiah($w['harga']) ?></b></div>
        <div class="fact"><span>Jam buka</span><b><?= e($w['jam_buka']) ?></b></div>
        <div class="fact"><span>Kuota harian</span><b><?= (int) $w['kuota'] ?> orang</b></div>
      </div>
      <?php if ($fasilitas): ?>
        <h3>Fasilitas</h3>
        <div class="chips"><?php foreach ($fasilitas as $f): ?><span class="tag"><?= e($f) ?></span><?php endforeach; ?></div>
      <?php endif; ?>
      <?php if ($w['tips']): ?><p class="cms-note">Tips pengelola: <?= e($w['tips']) ?></p><?php endif; ?>
    </div>

    <aside>
      <div class="wx" style="box-shadow:none;border:1px solid var(--line)"
           data-cuaca data-lat="<?= e($w['lat']) ?>" data-lng="<?= e($w['lng']) ?>"
           data-jenis="<?= e($w['jenis']) ?>" data-nama="<?= e($w['nama']) ?>"
           data-judul="Cuaca di lokasi" data-tombol="tombolPesan">
        <h3>Cuaca di lokasi</h3>
        <p class="wx-note">Memuat prakiraan cuaca...</p>
      </div>
      <a class="btn btn-main" style="margin-top:14px;width:100%" id="tombolPesan" href="/reservasi?w=<?= e($w['slug']) ?>">Pesan kunjungan</a>
      <div class="peta peta-kecil" style="margin-top:18px" data-titik="<?= titik_peta($titik) ?>" role="region" aria-label="Lokasi <?= e($w['nama']) ?>"></div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
