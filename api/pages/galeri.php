<?php
// =========================================================
// HALAMAN GALERI. Filter: /galeri?kategori=Alam. Klik foto untuk memperbesar.
// =========================================================
$kategoriAktif = $_GET['kategori'] ?? 'Semua';
$daftarKategori = array_column(q('SELECT DISTINCT kategori FROM galeri ORDER BY kategori'), 'kategori');
if ($kategoriAktif !== 'Semua' && !in_array($kategoriAktif, $daftarKategori, true)) {
    $kategoriAktif = 'Semua';
}
$foto = $kategoriAktif === 'Semua'
    ? q('SELECT * FROM galeri ORDER BY id DESC')
    : q('SELECT * FROM galeri WHERE kategori = ? ORDER BY id DESC', [$kategoriAktif]);

$judul = 'Galeri - ' . setting('nama_desa');
$menu  = 'galeri';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Galeri</h1>
  <p>Foto kegiatan dan sudut desa.</p>
</div>
<div class="wrap sec" style="padding-top:16px">
  <nav class="chips" aria-label="Kategori foto">
    <?php foreach (array_merge(['Semua'], $daftarKategori) as $k): ?>
      <a class="chip <?= $k === $kategoriAktif ? 'on' : '' ?>" href="/galeri<?= $k === 'Semua' ? '' : '?kategori=' . urlencode($k) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="gal">
    <?php foreach ($foto as $g): ?>
      <figure style="margin:0">
        <button type="button" class="buka-foto" data-src="<?= e($g['foto']) ?>" data-judul="<?= e($g['judul']) ?>" aria-label="Perbesar foto <?= e($g['judul']) ?>">
          <div class="art"><img src="<?= e($g['foto']) ?>" alt="<?= e($g['judul']) ?>" loading="lazy"></div>
        </button>
        <figcaption><?= e($g['judul']) ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
  <?php if (!$foto): ?><p class="muted">Belum ada foto di kategori ini.</p><?php endif; ?>
</div>

<dialog class="lb" id="lightbox" aria-label="Foto galeri">
  <img id="lbFoto" src="" alt="">
  <div class="lb-foot"><span id="lbJudul"></span><button class="btn btn-main btn-sm" id="lbTutup" type="button">Tutup</button></div>
</dialog>

<?php
$scriptHalaman = <<<'JS'
<script>
(function () {
  const dlg = document.getElementById('lightbox'), img = document.getElementById('lbFoto'), judul = document.getElementById('lbJudul');
  document.querySelectorAll('.buka-foto').forEach(b => b.addEventListener('click', () => {
    img.src = b.dataset.src; img.alt = b.dataset.judul; judul.textContent = b.dataset.judul;
    dlg.showModal();
  }));
  document.getElementById('lbTutup').addEventListener('click', () => dlg.close());
  dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php';
?>
