<?php
// Bagian atas setiap halaman. Sebelum memanggil file ini, halaman mengisi:
//   $judul = judul tab browser, $menu = menu yang sedang aktif
//   $pakaiPeta = true kalau halaman butuh peta, $pakaiCuaca = true kalau butuh cuaca
$judul = $judul ?? setting('nama_desa');
$menu  = $menu ?? '';
$daftarMenu = [
    ''          => 'Beranda',
    'profil'    => 'Profil',
    'data-desa' => 'Informasi Desa',
    'wisata'    => 'Wisata',
    'reservasi' => 'Reservasi',
    'umkm'      => 'UMKM',
    'agenda'    => 'Agenda & Berita',
    'galeri'    => 'Galeri',
    'kontak'    => 'Kontak',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judul) ?></title>
  <meta name="description" content="<?= e(setting('hero_teks')) ?>">
  <meta name="theme-color" content="#1E4D2B">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($judul) ?>">
  <meta property="og:description" content="<?= e(setting('hero_teks')) ?>">
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <?php if (!empty($pakaiPeta)): ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <?php endif; ?>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<a class="skip" href="#isi">Langsung ke isi halaman</a>
<header class="top">
  <div class="top-in">
    <a class="brand" href="/">
      <svg width="38" height="38" viewBox="0 0 38 38" aria-hidden="true"><circle cx="19" cy="19" r="19" fill="#1E4D2B"/><path d="M7 27c6-3 18-3 24 0M7 21.5c6-3 18-3 24 0M9 16c5-2.5 15-2.5 20 0" stroke="#C4F536" stroke-width="2.4" fill="none" stroke-linecap="round"/><circle cx="27" cy="10" r="3.4" fill="#E8641E"/></svg>
      <span><?= e(setting('nama_desa')) ?><small><?= e(setting('kecamatan')) ?></small></span>
    </a>
    <div class="top-aksi">
      <a class="btn-header" href="/reservasi"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="M3 10h18M8 3v4M16 3v4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg><span>Pesan kunjungan</span></a>
      <button class="menu-btn" aria-expanded="false" aria-controls="nav" id="menuBtn">Menu</button>
    </div>
    <nav class="nav" id="nav" aria-label="Menu utama">
      <?php foreach ($daftarMenu as $alamatMenu => $labelMenu): ?>
        <a href="/<?= $alamatMenu ?>" class="<?= $menu === $alamatMenu ? 'on' : '' ?>"><?= e($labelMenu) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
<div class="penanda-offline" id="penandaOffline" hidden role="status">Kamu sedang offline. Yang tampil adalah salinan tersimpan, nomor darurat ada di halaman Kontak.</div>
<main id="isi">
