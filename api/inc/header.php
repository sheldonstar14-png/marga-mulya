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
  <meta name="theme-color" content="#2B5A28">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($judul) ?>">
  <meta property="og:description" content="<?= e(setting('hero_teks')) ?>">
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <script>try{['besar','kontras'].forEach(function(m){if(localStorage.getItem('mm-'+m)==='1')document.documentElement.classList.add('mode-'+m)})}catch(e){}</script>
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
      <svg width="38" height="38" viewBox="0 0 38 38" aria-hidden="true"><rect width="38" height="38" rx="10" fill="#2B5A28"/><path d="M6 28c6-3 20-3 26 0M6 22c6-3 20-3 26 0M8 16c5-2.6 17-2.6 22 0" stroke="#C9DC6B" stroke-width="2.4" fill="none" stroke-linecap="round"/><circle cx="27" cy="10" r="3.4" fill="#E2A529"/></svg>
      <span><?= e(setting('nama_desa')) ?><small><?= e(setting('kecamatan')) ?></small></span>
    </a>
    <div class="top-aksi">
      <button class="akses-btn" id="aksesBtn" aria-expanded="false" aria-controls="aksesPanel" title="Mode mudah: huruf besar, kontras, dan suara">
        <span aria-hidden="true">Aa</span><span class="akses-teks">Mode mudah</span>
      </button>
      <button class="menu-btn" aria-expanded="false" aria-controls="nav" id="menuBtn">Menu</button>
    </div>
    <div class="akses-panel" id="aksesPanel" hidden role="group" aria-label="Pengaturan tampilan dan suara">
      <button type="button" data-mode="besar" aria-pressed="false">Huruf besar</button>
      <button type="button" data-mode="kontras" aria-pressed="false">Kontras tinggi</button>
      <button type="button" id="dengarBtn" aria-pressed="false">Dengarkan halaman ini</button>
      <button type="button" id="pasangBtn" hidden>Pasang di HP (bisa dibuka tanpa sinyal)</button>
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
