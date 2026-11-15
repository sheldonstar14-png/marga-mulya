<?php
// =========================================================
// HALAMAN PROFIL DESA
// =========================================================
$aparat = q('SELECT jabatan, nama FROM aparat ORDER BY urutan, id');
$batas = [
    'Utara'   => setting('batas_utara'),
    'Timur'   => setting('batas_timur'),
    'Selatan' => setting('batas_selatan'),
    'Barat'   => setting('batas_barat'),
];

$judul = 'Profil - ' . setting('nama_desa');
$menu  = 'profil';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Profil desa</h1>
  <p><?= e(setting('profil_ringkas')) ?></p>
</div>

<div class="wrap sec grid g2" style="padding-top:24px">
  <div class="panel">
    <h3>Sejarah singkat</h3>
    <p><?= e(setting('sejarah')) ?></p>
    <h3>Visi</h3>
    <p><?= e(setting('visi')) ?></p>
    <h3>Misi</h3>
    <ol style="padding-left:20px;margin:0">
      <?php foreach (per_baris(setting('misi')) as $misi): ?>
        <li><?= e($misi) ?></li>
      <?php endforeach; ?>
    </ol>
  </div>

  <div class="panel">
    <h3>Batas wilayah</h3>
    <div class="table-wrap">
      <table>
        <tr><th>Arah</th><th>Berbatasan dengan</th></tr>
        <?php foreach ($batas as $arah => $nama): ?>
          <tr><td><?= $arah ?></td><td><?= e($nama) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
    <h3 style="margin-top:22px">Penggunaan lahan</h3>
    <div class="bars"><?= bar_list(statistik('Lahan')) ?></div>
  </div>

  <div class="panel">
    <h3>Perangkat desa</h3>
    <div class="table-wrap">
      <table>
        <tr><th>Jabatan</th><th>Nama</th></tr>
        <?php foreach ($aparat as $a): ?>
          <tr><td><?= e($a['jabatan']) ?></td><td><?= e($a['nama']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div class="panel">
    <h3>Pemerintahan dan keamanan</h3>
    <div class="bars"><?= bar_list(statistik('Pemerintahan')) ?></div>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
