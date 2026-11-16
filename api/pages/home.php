<?php
// =========================================================
// HALAMAN BERANDA. Semua isi diambil dari database.
// =========================================================
$wisata = q('SELECT slug, nama, jenis, harga, kuota, ringkas, foto FROM wisata WHERE aktif = 1 ORDER BY id');
$agenda = q('SELECT judul, tanggal, jam, tempat, isi FROM agenda WHERE tanggal >= ? ORDER BY tanggal LIMIT 3', [date('Y-m-d')]);
$umkm   = q('SELECT nama_usaha, produk, harga, satuan, foto FROM umkm ORDER BY id LIMIT 3');
$berita = q('SELECT judul, tanggal, isi, foto FROM berita ORDER BY tanggal DESC, id DESC LIMIT 3');

$judul = setting('nama_desa');
$menu  = '';
$pakaiCuaca = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="hero">
  <svg class="hero-art" viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <rect width="1200" height="600" fill="#2B5A28"/>
    <path d="M0 250 C200 210 420 230 600 260 S1000 300 1200 250 V600 H0Z" fill="#336A2F"/>
    <path d="M0 320 C220 285 430 300 620 330 S1010 365 1200 320 V600 H0Z" fill="#3A7535"/>
    <path d="M0 390 C230 355 440 372 640 400 S1020 430 1200 392 V600 H0Z" fill="#41813B"/>
    <path d="M0 460 C240 428 450 442 660 468 S1030 495 1200 462 V600 H0Z" fill="#4B8D43"/>
    <path d="M0 530 C250 500 460 512 680 535 S1040 560 1200 530 V600 H0Z" fill="#56984C"/>
  </svg>
  <div class="hero-in">
    <div>
      <h1><?= e(setting('hero_judul')) ?></h1>
      <p class="hero-lead"><?= e(setting('hero_teks')) ?></p>
      <div class="hero-actions">
        <a class="btn btn-main" href="/reservasi">Pesan kunjungan</a>
        <a class="btn btn-ghost" href="/wisata">Lihat destinasi</a>
      </div>
    </div>
    <aside class="wx" aria-live="polite" data-cuaca data-lat="<?= e(setting('lat')) ?>" data-lng="<?= e(setting('lng')) ?>" data-sub="Pusat desa">
      <h3>Cuaca 7 hari ke depan</h3>
      <p class="wx-note">Memuat prakiraan cuaca...</p>
    </aside>
  </div>
</div>

<div class="sec"><div class="wrap">
  <div class="head-row"><h2>Destinasi di <?= e(str_replace('Desa ', '', setting('nama_desa'))) ?></h2><a href="/wisata">Semua destinasi</a></div>
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
</div></div>

<div class="sec tint"><div class="wrap grid g2">
  <div>
    <div class="head-row"><h2>Agenda terdekat</h2><a href="/agenda">Semua agenda</a></div>
    <div class="agenda">
      <?php foreach ($agenda as $a): ?>
        <div class="ag">
          <div class="ag-date"><b><?= tgl($a['tanggal'], 'tanggal') ?></b><?= tgl($a['tanggal'], 'bulan') ?></div>
          <div><h3><?= e($a['judul']) ?></h3><p><?= e($a['tempat']) ?>, <?= e($a['jam']) ?></p><p><?= e($a['isi']) ?></p></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$agenda): ?><p class="muted">Belum ada agenda terdekat.</p><?php endif; ?>
    </div>
  </div>
  <div>
    <div class="head-row"><h2>Produk warga</h2><a href="/umkm">Katalog UMKM</a></div>
    <div class="grid">
      <?php foreach ($umkm as $u): ?>
        <a class="card link-card" href="/umkm" style="flex-direction:row;align-items:center">
          <div class="art" style="width:96px;aspect-ratio:1;flex:none"><img src="<?= e($u['foto'] ?: '/assets/img/kirab.svg') ?>" alt="" loading="lazy"></div>
          <div class="card-body" style="padding:12px 16px">
            <b><?= e($u['produk']) ?></b>
            <span class="small muted"><?= e($u['nama_usaha']) ?></span>
            <span class="price"><?= rupiah($u['harga']) ?> <span class="small muted">/ <?= e($u['satuan']) ?></span></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div></div>

<div class="sec"><div class="wrap">
  <div class="head-row"><h2>Kabar desa</h2><a href="/agenda">Semua berita</a></div>
  <div class="grid g3">
    <?php foreach ($berita as $b): ?>
      <article class="card">
        <?php if ($b['foto']): ?><div class="art"><img src="<?= e($b['foto']) ?>" alt="" loading="lazy"></div><?php endif; ?>
        <div class="card-body">
          <span class="small muted"><?= tgl($b['tanggal']) ?></span>
          <h3 style="margin:2px 0 4px"><?= e($b['judul']) ?></h3>
          <p><?= e($b['isi']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div></div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
