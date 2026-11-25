<?php
// Bagian atas setiap halaman (desain A "Samudra", Fase 12s). Sebelum memanggil file ini, halaman mengisi:
//   $judul = judul tab browser, $menu = menu yang sedang aktif
//   $pakaiCuaca = true kalau butuh cuaca
$judul = $judul ?? setting('nama_desa');
$menu  = $menu ?? '';
// Menu utama. Setiap menu punya submenu yang muncul saat disentuh atau diarahkan kursor.
$daftarMenu = [
    ''          => ['Beranda', '/', []],
    'profil'    => ['Profil Desa', '/profil', [
        ['Profil dan sejarah', '/profil'],
        ['Sambutan, visi, dan misi', '/profil#sambutan'],
        ['Perangkat dan lembaga desa', '/profil#lembaga'],
        ['Peta desa', '/profil#peta-desa'],
    ]],
    'layanan'   => ['Layanan', '/data-desa?grup=Layanan+surat', [
        ['Ajukan surat online', '/data-desa?grup=Layanan+surat#surat'],
        ['Alur pengajuan surat', '/data-desa?grup=Layanan+surat#alur'],
        ['Izin kunjungan, KKN, penelitian', '/data-desa?grup=Layanan+surat&izin=1#surat'],
        ['Bantuan sosial', '/data-desa?grup=Pantau+bansos'],
        ['Aspirasi dan pertanyaan', '/kontak#aspirasi'],
    ]],
    'data-desa' => ['Informasi', '/data-desa', [
        ['Data desa', '/data-desa'],
        ['Anggaran desa (APBDes)', '/data-desa?grup=Transparansi'],
        ['Pembangunan', '/data-desa?grup=Pembangunan'],
        ['Cuaca darat dan laut', '/cuaca'],
    ]],
    'wisata'    => ['Wisata & Budaya', '/wisata', [
        ['Destinasi wisata', '/wisata'],
        ['Budaya dan kesenian', '/wisata#budaya'],
        ['Kode etik pengunjung', '/wisata#kode-etik'],
    ]],
    'umkm'      => ['Ekonomi', '/umkm', [
        ['Produk UMKM warga', '/umkm'],
        ['Ajukan produk', '/umkm#ajukan'],
        ['Kerja dan pelatihan', '/kerja'],
        ['Pasang peluang kerja', '/kerja#pasang'],
    ]],
    'agenda'    => ['Kabar Desa', '/agenda', [
        ['Berita', '/agenda#berita'],
        ['Agenda dan relawan', '/agenda'],
        ['Galeri', '/galeri'],
    ]],
    'kontak'    => ['Kontak', '/kontak', []],
];
// Menu aktif: tab layanan dan bansos di halaman Informasi dihitung sebagai "Layanan"
$grupBuka = (string) ($_GET['grup'] ?? '');
$menuAktif = match (true) {
    $menu === 'data-desa' && in_array($grupBuka, ['Layanan surat', 'Pantau bansos'], true) => 'layanan',
    $menu === 'galeri' => 'agenda',
    $menu === 'cuaca' => 'data-desa',
    $menu === 'kerja' => 'umkm',
    default => $menu,
};
$panah = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <?php if (function_exists('gerbang_aktif') && gerbang_aktif()): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= e($judul) ?></title>
  <meta name="description" content="<?= e(setting('hero_teks')) ?>">
  <meta name="theme-color" content="#0B3C5D">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($judul) ?>">
  <meta property="og:description" content="<?= e(setting('hero_teks')) ?>">
  <link rel="icon" href="/assets/img/favicon.png" type="image/png">
  <link rel="manifest" href="/manifest.webmanifest">
  <!-- iPhone dan iPad: bisa dipasang lewat Safari > Bagikan > Tambah ke Layar Utama -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="<?= e(str_replace('Desa ', '', setting('nama_desa'))) ?>">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css?v=12v">
</head>
<body class="publik">
<a class="skip" href="#isi">Langsung ke isi halaman</a>
<div class="util">
  <div class="util-in">
    <?php if (setting('email') !== ''): ?><a href="mailto:<?= e(setting('email')) ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg><?= e(setting('email')) ?></a><?php endif; ?>
    <a href="<?= e(wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya.')) ?>" target="_blank" rel="noopener"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>WhatsApp +<?= e(setting('wa')) ?></a>
    <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e(setting('jam_layanan')) ?></span>
    <a class="util-darurat" href="/kontak#darurat"><i aria-hidden="true"></i>Nomor darurat</a>
  </div>
</div>
<header class="top">
  <div class="top-in">
    <a class="brand" href="/" aria-label="<?= e(setting('nama_desa')) ?>, ke beranda">
      <span class="brand-kotak"><img class="brand-logo" src="/assets/img/logo-mark.png?v=2" alt="" width="64" height="35"></span>
      <span class="brand-teks"><b><?= e(str_replace('Desa ', '', setting('nama_desa'))) ?></b><small><?= e(nama_kabupaten()) ?></small></span>
    </a>
    <nav class="nav" id="nav" aria-label="Menu utama">
      <?php foreach ($daftarMenu as $kunci => [$label, $alamat, $sub]): $aktif = $menuAktif === $kunci; ?>
        <div class="nav-item<?= $sub ? ' punya-sub' : '' ?>">
          <a class="nav-link<?= $aktif ? ' on' : '' ?>" href="<?= e($alamat) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>><?= e($label) ?><?= $sub ? $panah : '' ?></a>
          <?php if ($sub): ?>
            <div class="nav-sub">
              <?php foreach ($sub as [$lSub, $aSub]): ?><a href="<?= e($aSub) ?>"><?= e($lSub) ?></a><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <a class="btn btn-navy nav-cta-hp" href="/data-desa?grup=Layanan+surat#surat">Ajukan surat</a>
    </nav>
    <a class="top-cta" href="/data-desa?grup=Layanan+surat#surat">Ajukan Surat</a>
    <a class="darurat-btn" href="/kontak#darurat" aria-label="Nomor darurat"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg><span>Darurat</span></a>
    <button class="menu-btn" aria-expanded="false" aria-controls="nav" id="menuBtn"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>Menu</button>
  </div>
</header>
<div class="penanda-offline" id="penandaOffline" hidden role="status"><b>Kamu sedang offline.</b> Yang tampil adalah salinan tersimpan. Form tidak bisa dikirim sampai sinyal kembali. <a href="/kontak#darurat">Nomor darurat</a></div>
<main id="isi">
