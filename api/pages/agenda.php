<?php
// =========================================================
// HALAMAN AGENDA & BERITA
// =========================================================
$agenda = q('SELECT * FROM agenda WHERE tanggal >= ? ORDER BY tanggal', [date('Y-m-d')]);
$berita = q('SELECT * FROM berita ORDER BY tanggal DESC, id DESC');

$judul = 'Agenda & Berita - ' . setting('nama_desa');
$menu  = 'agenda';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Agenda dan berita</h1>
  <p>Kegiatan rutin warga dan kabar terbaru dari pemerintah desa.</p>
</div>
<div class="wrap sec grid g2" style="padding-top:16px">
  <div>
    <h2>Agenda kegiatan</h2>
    <div class="agenda">
      <?php foreach ($agenda as $a): ?>
        <div class="ag">
          <div class="ag-date"><b><?= tgl($a['tanggal'], 'tanggal') ?></b><?= tgl($a['tanggal'], 'bulan') ?></div>
          <div><h3><?= e($a['judul']) ?></h3><p><?= e($a['tempat']) ?>, <?= e($a['jam']) ?></p><p><?= e($a['isi']) ?></p></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$agenda): ?><p class="muted">Belum ada agenda yang akan datang.</p><?php endif; ?>
    </div>
  </div>
  <div>
    <h2>Berita dan pengumuman</h2>
    <div class="grid">
      <?php foreach ($berita as $b): ?>
        <article class="card">
          <?php if ($b['foto']): ?><div class="art"><img src="<?= e($b['foto']) ?>" alt="" loading="lazy"></div><?php endif; ?>
          <div class="card-body">
            <span class="small muted"><?= tgl($b['tanggal']) ?></span>
            <h3 style="margin:2px 0 4px"><?= e($b['judul']) ?></h3>
            <p><?= nl2br(e($b['isi'])) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$berita): ?><p class="muted">Belum ada berita.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
