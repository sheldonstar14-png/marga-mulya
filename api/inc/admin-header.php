<?php
// Bagian atas halaman CMS: menu samping. Halaman mengisi $judulAdmin dan $menuAdmin.
// Nama variabel di file ini sengaja unik supaya tidak menimpa variabel milik halaman.
header('Cache-Control: no-store'); // halaman admin tidak boleh disimpan di cache browser
$superAdmin = $admin['peran'] === 'Super admin';
$jumlahMenunggu = boleh($admin, 'reservasi') ? (int) q1("SELECT COUNT(*) AS n FROM reservasi WHERE status = 'Menunggu'")['n'] : 0;
$jumlahPesanBaru = boleh($admin, 'pesan') ? (int) q1('SELECT COUNT(*) AS n FROM pesan WHERE dibaca = 0')['n'] : 0;
$jumlahPrivasiBaru = boleh($admin, 'privasi') ? (int) q1("SELECT COUNT(*) AS n FROM permintaan_data WHERE status = 'Diterima'")['n'] : 0;
$jumlahAspirasiBaru = boleh($admin, 'aspirasi') ? (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status = 'Diterima'")['n'] : 0;
$jumlahUlasanUmkm = boleh($admin, 'ulasanumkm') ? (int) q1('SELECT COUNT(*) AS n FROM ulasan_umkm WHERE tampil = 0')['n'] : 0;
$jumlahPesananUmkm = boleh($admin, 'pesananumkm') ? (int) q1("SELECT COUNT(*) AS n FROM pesanan_umkm WHERE status = 'Baru'")['n'] : 0;
$jumlahSuratBaru = boleh($admin, 'surat') ? (int) q1("SELECT COUNT(*) AS n FROM surat WHERE status = 'Diterima'")['n'] : 0;
$grupMenu = [
    'Layanan'   => ['dashboard' => 'Dashboard', 'reservasi' => 'Reservasi', 'pesan' => 'Pesan masuk', 'aspirasi' => 'Aspirasi warga', 'privasi' => 'Permintaan data pribadi', 'surat' => 'Pengajuan surat & antrean', 'layanan' => 'Jenis surat & syarat', 'faq' => 'Asisten desa (FAQ)', 'ulasan' => 'Ulasan'],
    'Konten'    => ['wisata' => 'Wisata', 'budaya' => 'Budaya', 'umkm' => 'UMKM', 'ulasanumkm' => 'Ulasan UMKM', 'pesananumkm' => 'Pesanan UMKM', 'agenda' => 'Agenda', 'relawan' => 'Relawan gotong royong', 'berita' => 'Berita', 'galeri' => 'Galeri'],
    'Data desa' => ['statistik' => 'Statistik desa', 'profil' => 'Profil desa', 'aparat' => 'Perangkat desa', 'fasilitas' => 'Fasilitas umum', 'zona' => 'Zona rawan bencana', 'darurat' => 'Nomor darurat'],
    'Laporan' => ['laporan' => 'Laporan bulanan'],
    'Transparansi' => ['apbdes' => 'APBDes', 'pembangunan' => 'Pembangunan', 'bantuan' => 'Program bantuan', 'bansos' => 'Penerima bansos', 'sampah' => 'Bank sampah'],
];
$grupMenu['Sistem'] = ['akun' => 'Akun admin', 'pengaturan' => 'Pengaturan'];
// Sembunyikan menu yang tidak boleh dibuka akun ini
foreach ($grupMenu as $namaGrupMenu => $isiGrupMenu) {
    $grupMenu[$namaGrupMenu] = array_filter($isiGrupMenu, fn($kunciMenu) => boleh($admin, $kunciMenu), ARRAY_FILTER_USE_KEY);
    if (!$grupMenu[$namaGrupMenu]) unset($grupMenu[$namaGrupMenu]);
}
$badge = ['reservasi' => $jumlahMenunggu, 'pesan' => $jumlahPesanBaru, 'aspirasi' => $jumlahAspirasiBaru, 'privasi' => $jumlahPrivasiBaru, 'surat' => $jumlahSuratBaru, 'ulasanumkm' => $jumlahUlasanUmkm, 'pesananumkm' => $jumlahPesananUmkm];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <?php if (function_exists('gerbang_aktif') && gerbang_aktif()): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <link rel="icon" href="/assets/img/favicon.png" type="image/png">
  <title><?= e($judulAdmin ?? 'CMS') ?> - CMS <?= e(setting('nama_desa')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <?php if (!empty($pakaiPeta)): ?><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""><?php endif; ?>
  <link rel="stylesheet" href="/assets/css/style.css?v=12k">
</head>
<body class="cms">
<div class="admin">
  <nav class="side" aria-label="Menu CMS">
    <h3><span class="cms-logo"><img src="/assets/img/logo-mark.png" alt="" width="54" height="30"></span>CMS <?= e(setting('nama_desa')) ?></h3>
    <?php foreach ($grupMenu as $namaGrupMenu => $isiGrupMenu): ?>
      <h4><?= e($namaGrupMenu) ?></h4>
      <?php foreach ($isiGrupMenu as $alamatMenu => $labelMenu): ?>
        <a class="side-link <?= ($menuAdmin ?? '') === $alamatMenu ? 'on' : '' ?>" href="/admin/<?= $alamatMenu ?>">
          <span><?= e($labelMenu) ?></span><?php if (!empty($badge[$alamatMenu])): ?><span class="badge"><?= $badge[$alamatMenu] ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <h4>Akun</h4>
    <a class="side-link" href="/" target="_blank" rel="noopener"><span>Lihat website</span></a>
    <a class="side-link" href="/admin/keluar"><span>Keluar (<?= e($admin['username']) ?>)</span></a>
  </nav>
  <main class="admin-main">
    <?php if (!empty($_GET['ok'])): ?><div class="alert alert-ok" role="status"><?= e($_GET['ok']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['gagal'])): ?><div class="alert alert-err" role="alert"><?= e($_GET['gagal']) ?></div><?php endif; ?>
