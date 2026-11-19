<?php
// Bagian atas setiap halaman. Sebelum memanggil file ini, halaman mengisi:
//   $judul = judul tab browser, $menu = menu yang sedang aktif
//   $pakaiPeta = true kalau halaman butuh peta, $pakaiCuaca = true kalau butuh cuaca
$judul = $judul ?? setting('nama_desa');
$menu  = $menu ?? '';
$daftarMenu = [
    ''          => 'Beranda',
    'profil'    => 'Profil',
    'layanan'   => 'Layanan',
    'data-desa' => 'Informasi',
    'wisata'    => 'Wisata',
    'umkm'      => 'UMKM',
    'agenda'    => 'Berita',
    'galeri'    => 'Galeri',
    'kontak'    => 'Kontak',
];
// "Layanan" membuka tab layanan surat di halaman Informasi desa
$grupBuka = (string) ($_GET['grup'] ?? '');
$menuAktif = $menu === 'data-desa' && in_array($grupBuka, ['Layanan surat', 'Pantau bansos'], true) ? 'layanan' : ($menu === 'reservasi' ? 'wisata' : $menu);
$alamatMenuKhusus = ['layanan' => '/data-desa?grup=Layanan+surat'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <?php if (function_exists('gerbang_aktif') && gerbang_aktif()): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judul) ?></title>
  <meta name="description" content="<?= e(setting('hero_teks')) ?>">
  <meta name="theme-color" content="#0B3C5D">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($judul) ?>">
  <meta property="og:description" content="<?= e(setting('hero_teks')) ?>">
  <link rel="icon" href="/assets/img/favicon.png" type="image/png">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <?php if (!empty($pakaiPeta)): ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <?php endif; ?>
  <link rel="stylesheet" href="/assets/css/style.css?v=12q">
</head>
<body>
<a class="skip" href="#isi">Langsung ke isi halaman</a>
<header class="top">
  <div class="top-in">
    <a class="brand" href="/">
      <img class="brand-logo" src="/assets/img/logo-mark.png?v=2" alt="" width="64" height="35">
      <span><?= e(setting('nama_desa')) ?><small><?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></small></span>
    </a>
    <nav class="nav" id="nav" aria-label="Menu utama">
      <?php foreach ($daftarMenu as $alamatMenu => $labelMenu): ?>
        <a href="<?= $alamatMenuKhusus[$alamatMenu] ?? '/' . $alamatMenu ?>" class="<?= $menuAktif === $alamatMenu ? 'on' : '' ?>" <?= $menuAktif === $alamatMenu ? 'aria-current="page"' : '' ?>><?= e($labelMenu) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="darurat-btn" href="/kontak#darurat"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>Darurat</a>
    <button class="menu-btn" aria-expanded="false" aria-controls="nav" id="menuBtn">Menu</button>
  </div>
</header>
<div class="penanda-offline" id="penandaOffline" hidden role="status">Kamu sedang offline. Yang tampil adalah salinan tersimpan, nomor darurat ada di halaman Kontak.</div>
<main id="isi">
