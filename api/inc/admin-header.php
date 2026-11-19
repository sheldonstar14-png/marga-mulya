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
$jumlahAjuUmkm = 0;
try { $jumlahAjuUmkm = boleh($admin, 'pengajuanumkm') ? (int) q1("SELECT COUNT(*) AS n FROM pengajuan_umkm WHERE status = 'Menunggu'")['n'] : 0; } catch (Throwable $e) {}
$jumlahSuratBaru = boleh($admin, 'surat') ? (int) q1("SELECT COUNT(*) AS n FROM surat WHERE status = 'Diterima'")['n'] : 0;
$grupMenu = [
    'Layanan'   => ['dashboard' => 'Dashboard', 'reservasi' => 'Kunjungan rombongan', 'pesan' => 'Pesan masuk', 'aspirasi' => 'Aspirasi warga', 'privasi' => 'Permintaan data pribadi', 'surat' => 'Pengajuan surat & antrean', 'layanan' => 'Jenis surat & syarat', 'asisten' => 'Asisten desa (menu)', 'ulasan' => 'Ulasan'],
    'Konten'    => ['wisata' => 'Wisata', 'budaya' => 'Budaya', 'umkm' => 'UMKM', 'pengajuanumkm' => 'Pengajuan produk UMKM', 'ulasanumkm' => 'Ulasan UMKM', 'agenda' => 'Agenda', 'relawan' => 'Relawan gotong royong', 'survei' => 'Survei kepuasan', 'berita' => 'Berita', 'galeri' => 'Galeri', 'kreditfoto' => 'Sumber & kredit foto'],
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
$badge = ['reservasi' => $jumlahMenunggu, 'pesan' => $jumlahPesanBaru, 'aspirasi' => $jumlahAspirasiBaru, 'privasi' => $jumlahPrivasiBaru, 'surat' => $jumlahSuratBaru, 'ulasanumkm' => $jumlahUlasanUmkm, 'pengajuanumkm' => $jumlahAjuUmkm];
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
  <link rel="stylesheet" href="/assets/css/style.css?v=12q">
</head>
<body class="cms">
<?php
// Menu bawah untuk HP: 4 menu yang paling sering dipakai petugas + tombol Semua menu
$menuBawah = array_filter([
    'dashboard' => ['Beranda', '<path d="M4 11l8-6 8 6v9H4z"/><path d="M10 20v-5h4v5"/>'],
    'aspirasi'  => ['Aspirasi', '<path d="M4 5h16v11H9l-5 4z"/>'],
    'surat'     => ['Surat', '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/>'],
    'reservasi' => ['Rombongan', '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 20c0-2 1-4 3-4s3 1.5 3 4"/>'],
], fn($k) => $k === 'dashboard' || boleh($admin, $k), ARRAY_FILTER_USE_KEY);
$labelMenuIni = '';
foreach ($grupMenu as $isiGrupMenu) if (isset($isiGrupMenu[$menuAdmin ?? ''])) $labelMenuIni = $isiGrupMenu[$menuAdmin];
?>
<div class="cms-atas">
  <img src="/assets/img/logo-mark.png?v=2" alt="" width="44" height="24">
  <span><small>CMS <?= e(setting('nama_desa')) ?></small><b><?= e($labelMenuIni ?: ($judulAdmin ?? 'CMS')) ?></b></span>
</div>
<div class="admin">
  <nav class="side" id="menuCms" aria-label="Menu CMS">
    <div class="side-kepala">
      <h3><span class="cms-logo"><img src="/assets/img/logo-mark.png?v=2" alt="" width="55" height="30"></span>CMS <?= e(setting('nama_desa')) ?></h3>
      <button type="button" class="side-tutup" id="menuCmsTutup" aria-label="Tutup menu">&times;</button>
    </div>
    <label class="sr-only" for="cariMenuCms">Cari menu</label>
    <input type="search" class="side-cari" id="cariMenuCms" placeholder="Cari menu, misalnya UMKM" autocomplete="off">
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
  <nav class="cms-bawah" aria-label="Menu cepat">
    <?php foreach ($menuBawah as $kunciBawah => [$labelBawah, $ikonBawah]): ?>
      <a href="/admin/<?= $kunciBawah ?>" class="<?= ($menuAdmin ?? '') === $kunciBawah ? 'on' : '' ?>"<?= ($menuAdmin ?? '') === $kunciBawah ? ' aria-current="page"' : '' ?>>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $ikonBawah ?></svg>
        <span><?= $labelBawah ?></span><?php if (!empty($badge[$kunciBawah])): ?><i class="badge"><?= $badge[$kunciBawah] ?></i><?php endif; ?>
      </a>
    <?php endforeach; ?>
    <button type="button" id="menuCmsBtn" aria-controls="menuCms" aria-expanded="false" class="<?= isset($menuBawah[$menuAdmin ?? '']) ? '' : 'on' ?>">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      <span>Semua menu</span><?php $sisaBadge = array_sum(array_diff_key($badge, $menuBawah)); if ($sisaBadge): ?><i class="badge"><?= $sisaBadge ?></i><?php endif; ?>
    </button>
  </nav>
  <main class="admin-main">
    <?php if (!empty($_GET['ok'])): ?><div class="alert alert-ok" role="status"><?= e($_GET['ok']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['gagal'])): ?><div class="alert alert-err" role="alert"><?= e($_GET['gagal']) ?></div><?php endif; ?>
