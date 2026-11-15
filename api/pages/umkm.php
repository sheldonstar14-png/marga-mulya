<?php
// =========================================================
// HALAMAN UMKM. Filter kategori lewat alamat: /umkm?kategori=Makanan
// =========================================================
$kategoriAktif = $_GET['kategori'] ?? 'Semua';
$daftarKategori = array_column(q('SELECT DISTINCT kategori FROM umkm ORDER BY kategori'), 'kategori');
if ($kategoriAktif !== 'Semua' && !in_array($kategoriAktif, $daftarKategori, true)) {
    $kategoriAktif = 'Semua';
}
$umkm = $kategoriAktif === 'Semua'
    ? q('SELECT * FROM umkm ORDER BY id')
    : q('SELECT * FROM umkm WHERE kategori = ? ORDER BY id', [$kategoriAktif]);

$judul = 'Produk UMKM - ' . setting('nama_desa');
$menu  = 'umkm';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Produk UMKM warga</h1>
  <p>Pesan langsung ke pembuatnya lewat WhatsApp, tanpa perantara.</p>
</div>
<div class="wrap sec" style="padding-top:16px">
  <nav class="chips" aria-label="Kategori produk">
    <?php foreach (array_merge(['Semua'], $daftarKategori) as $k): ?>
      <a class="chip <?= $k === $kategoriAktif ? 'on' : '' ?>" href="/umkm<?= $k === 'Semua' ? '' : '?kategori=' . urlencode($k) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="grid g3">
    <?php foreach ($umkm as $u):
      $pesan = 'Halo ' . $u['nama_usaha'] . ', saya lihat di website ' . setting('nama_desa') . '. Saya ingin pesan ' . $u['produk'] . '.'; ?>
      <div class="card">
        <div class="art"><img src="<?= e($u['foto'] ?: '/assets/img/kirab.svg') ?>" alt="<?= e($u['produk']) ?>" loading="lazy"></div>
        <div class="card-body">
          <span class="tag"><?= e($u['kategori']) ?></span>
          <h3 style="margin:4px 0 0"><?= e($u['produk']) ?></h3>
          <p><?= e($u['nama_usaha']) ?>, <?= e($u['pemilik']) ?></p>
          <div class="card-foot">
            <span class="price"><?= rupiah($u['harga']) ?> <span class="small muted">/ <?= e($u['satuan']) ?></span></span>
            <a class="btn btn-wa btn-sm" href="<?= e(wa_link($u['wa'], $pesan)) ?>" target="_blank" rel="noopener">Pesan via WhatsApp</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$umkm): ?><p class="muted">Belum ada produk di kategori ini.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
