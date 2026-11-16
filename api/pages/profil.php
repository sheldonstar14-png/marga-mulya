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

// Fasilitas umum untuk peta (warna berbeda per kategori)
$fasilitas = q('SELECT nama, kategori, alamat, keterangan, lat, lng FROM fasilitas ORDER BY kategori, nama');
$kategoriFasilitas = array_values(array_unique(array_column($fasilitas, 'kategori')));
$paletFasilitas = ['#2B5A28', '#B3402E', '#2F6E95', '#E2A529', '#7A4E9C', '#3F7D3A', '#8A6A2F', '#1E2B22'];
$warnaKategori = [];
foreach ($kategoriFasilitas as $i => $k) $warnaKategori[$k] = $paletFasilitas[$i % count($paletFasilitas)];
$titikFasilitas = [];
foreach ($fasilitas as $f) {
    if ($f['lat'] === null || $f['lng'] === null) continue;
    $titikFasilitas[] = ['lat' => (float) $f['lat'], 'lng' => (float) $f['lng'], 'nama' => $f['nama'], 'kat' => $f['kategori'],
        'warna' => $warnaKategori[$f['kategori']], 'ket' => trim($f['kategori'] . ($f['keterangan'] ? '. ' . $f['keterangan'] : ''))];
}
$pakaiPeta = (bool) $titikFasilitas;

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
<?php if ($fasilitas): ?>
<div class="sec tint" id="fasilitas"><div class="wrap">
  <h2>Peta fasilitas umum</h2>
  <p class="muted">Kantor desa, layanan kesehatan, sekolah, dan fasilitas lingkungan di <?= e(setting('nama_desa')) ?>. Pilih kategori untuk menyaring.</p>
  <div class="chips saring-peta" data-peta="peta-fasilitas" role="group" aria-label="Saring kategori fasilitas">
    <button type="button" class="chip on" data-kat="" aria-pressed="true">Semua</button>
    <?php foreach ($kategoriFasilitas as $k): ?>
      <button type="button" class="chip" data-kat="<?= e($k) ?>" aria-pressed="false"><span class="titik-warna" style="background:<?= $warnaKategori[$k] ?>"></span><?= e($k) ?></button>
    <?php endforeach; ?>
  </div>
  <div class="split" style="margin-top:14px">
    <?php if ($titikFasilitas): ?><div class="peta peta-fasilitas" id="peta-fasilitas" data-titik="<?= titik_peta($titikFasilitas) ?>" role="region" aria-label="Peta fasilitas umum"></div><?php endif; ?>
    <ul class="daftar-fasilitas">
      <?php foreach ($fasilitas as $f): ?>
        <li data-kat-item="<?= e($f['kategori']) ?>">
          <span class="titik-warna" style="background:<?= $warnaKategori[$f['kategori']] ?>"></span>
          <div><b><?= e($f['nama']) ?></b><br><span class="small muted"><?= e(implode(', ', array_filter([$f['kategori'], $f['alamat'], $f['keterangan']]))) ?></span></div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div></div>
<?php endif; ?>
<?php require __DIR__ . '/../inc/footer.php'; ?>
