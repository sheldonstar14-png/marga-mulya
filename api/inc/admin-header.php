<?php
// Bagian atas halaman CMS: menu samping. Halaman mengisi $judulAdmin dan $menuAdmin.
// Nama variabel di file ini sengaja unik supaya tidak menimpa variabel milik halaman.
header('Cache-Control: no-store'); // halaman admin tidak boleh disimpan di cache browser
$superAdmin = $admin['peran'] === 'Super admin';
$jumlahMenunggu = (int) q1("SELECT COUNT(*) AS n FROM reservasi WHERE status = 'Menunggu'")['n'];
$jumlahPesanBaru = (int) q1('SELECT COUNT(*) AS n FROM pesan WHERE dibaca = 0')['n'];
$grupMenu = [
    'Layanan'   => ['dashboard' => 'Dashboard', 'reservasi' => 'Reservasi', 'pesan' => 'Pesan masuk'],
    'Konten'    => ['wisata' => 'Wisata', 'budaya' => 'Budaya', 'umkm' => 'UMKM', 'agenda' => 'Agenda', 'berita' => 'Berita', 'galeri' => 'Galeri'],
    'Data desa' => ['statistik' => 'Statistik desa', 'profil' => 'Profil desa', 'aparat' => 'Perangkat desa'],
];
if ($superAdmin) {
    $grupMenu['Sistem'] = ['akun' => 'Akun admin', 'pengaturan' => 'Pengaturan'];
}
$badge = ['reservasi' => $jumlahMenunggu, 'pesan' => $jumlahPesanBaru];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title><?= e($judulAdmin ?? 'CMS') ?> - CMS <?= e(setting('nama_desa')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
  <?php if (!empty($pakaiPeta)): ?><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""><?php endif; ?>
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="admin">
  <nav class="side" aria-label="Menu CMS">
    <h3>CMS <?= e(setting('nama_desa')) ?></h3>
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
