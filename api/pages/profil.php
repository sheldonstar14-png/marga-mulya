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

// ---------- Peta desa berlapis (Fase 12) ----------
// Lima lapisan bisa dinyalakan atau dimatikan: wisata, UMKM, fasilitas, pembangunan, rawan bencana.
$LAPISAN = [
    'wisata'      => ['Wisata', '#F08A5D'],
    'umkm'        => ['UMKM', '#15607F'],
    'fasilitas'   => ['Fasilitas umum', '#1F6F8B'],
    'pembangunan' => ['Pembangunan', '#6B4FA0'],
    'rawan'       => ['Rawan bencana', '#B3261E'],
];
$titikPeta = [];
$daftarPeta = [];
$tambah = function (string $lapis, array $r, string $nama, string $ket, string $url = '', int $radius = 0) use (&$titikPeta, &$daftarPeta, $LAPISAN) {
    if ($r['lat'] === null || $r['lng'] === null) return;
    $t = ['lat' => (float) $r['lat'], 'lng' => (float) $r['lng'], 'nama' => $nama, 'kat' => $lapis, 'warna' => $LAPISAN[$lapis][1], 'ket' => $ket];
    if ($url) $t['url'] = $url;
    if ($radius) $t['radius'] = $radius;
    $titikPeta[] = $t;
    $daftarPeta[$lapis][] = ['nama' => $nama, 'ket' => $ket, 'url' => $url];
};
foreach (q('SELECT slug, nama, jenis, lat, lng FROM wisata WHERE aktif = 1 ORDER BY id') as $r) $tambah('wisata', $r, $r['nama'], $r['jenis'], '/wisata/' . $r['slug']);
foreach (q('SELECT nama_usaha, produk, lat, lng FROM umkm ORDER BY id') as $r) $tambah('umkm', $r, $r['nama_usaha'], $r['produk'], '/umkm');
foreach (q('SELECT nama, kategori, keterangan, lat, lng FROM fasilitas ORDER BY kategori, nama') as $r) $tambah('fasilitas', $r, $r['nama'], trim($r['kategori'] . ($r['keterangan'] ? '. ' . $r['keterangan'] : '')));
foreach (q('SELECT nama, status, progres, lat, lng FROM pembangunan ORDER BY tahun DESC, id DESC') as $r) $tambah('pembangunan', $r, $r['nama'], $r['status'] . ', progres ' . (int) $r['progres'] . '%', '/data-desa?grup=Pembangunan');
foreach (q('SELECT nama, jenis, keterangan, radius_m, lat, lng FROM zona_rawan ORDER BY jenis, nama') as $r) $tambah('rawan', $r, $r['nama'], 'Rawan ' . strtolower($r['jenis']) . '. ' . $r['keterangan'], '', (int) $r['radius_m']);
$pakaiPeta = (bool) $titikPeta;
$pakaiCuaca = !empty($daftarPeta['rawan']); // siaga cuaca menyorot zona rawan

$judul = 'Profil - ' . setting('nama_desa');
$menu  = 'profil';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Profil desa</h1>
  <p><?= e(setting('profil_ringkas')) ?></p>
</div>


<div class="wrap sec" style="padding-top:28px">
  <?= sambutan_html(false) ?>
</div>

<div class="wrap sec profil-grid" style="padding-top:0">
  <div class="panel" id="visi">
    <?php [$visi, $ilusVisi] = pisah_ilustrasi(setting('visi'));
      $misiList = array_map(fn($m) => pisah_ilustrasi($m), per_baris(setting('misi')));
      $ilusMisi = $ilusVisi || array_filter(array_column($misiList, 1)); ?>
    <div class="head-row" style="margin-bottom:6px"><h2 style="margin:0;font-size:1.5rem">Visi dan misi</h2><?= tanda_ilustrasi((bool) $ilusMisi, 'Ilustrasi, menunggu dokumen RPJMDes') ?></div>
    <p class="visi-teks">&ldquo;<?= e($visi) ?>&rdquo;</p>
    <h3>Misi</h3>
    <ol class="misi-daftar">
      <?php foreach ($misiList as [$misi]): ?><li><?= e($misi) ?></li><?php endforeach; ?>
    </ol>
  </div>
  <div class="panel">
    <h2 style="font-size:1.5rem">Sejarah singkat</h2>
    <?php foreach (paragraf_teks(setting('sejarah')) as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
    <h3 style="margin-top:18px">Batas wilayah</h3>
    <div class="table-wrap">
      <table>
        <tr><th>Arah</th><th>Berbatasan dengan</th></tr>
        <?php foreach ($batas as $arah => $nama): ?>
          <tr><td><?= $arah ?></td><td><?= e($nama) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div class="panel">
    <h3>Penggunaan lahan</h3>
    <div class="bars"><?= bar_list(statistik('Lahan')) ?></div>
    <?= tautan_referensi() ?>
  </div>

  <div class="panel" id="perangkat">
    <h3>Perangkat desa</h3>
    <div class="table-wrap">
      <table>
        <tr><th>Jabatan</th><th>Nama</th></tr>
        <?php foreach ($aparat as $a): ?>
          <tr><td><?= e($a['jabatan']) ?></td><td><?= e($a['nama']) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
    <h3 style="margin-top:20px">Pemerintahan dan keamanan</h3>
    <div class="bars"><?= bar_list(statistik('Pemerintahan')) ?></div>
    <p class="small" style="margin:10px 0 0"><a href="/data-desa?grup=Pemerintahan">Data aparat desa dan kecamatan lengkap</a></p>
  </div>
</div>

<?php $lembaga = lembaga_desa(); if ($lembaga): ?>
<section class="sec sec-putih" id="lembaga" aria-labelledby="judul-lembaga"><div class="wrap">
  <div class="kepala"><div><h2 id="judul-lembaga">Lembaga desa dan kegiatan rutin</h2><p>Kelompok warga yang menggerakkan kegiatan desa sehari-hari.</p></div></div>
  <div class="lembaga-grid">
    <?php foreach ($lembaga as $l): ?>
      <article class="lembaga-kartu">
        <span class="chip-kecil"><?= e($l['jenis']) ?></span>
        <h3><?= e($l['nama']) ?> <?= tanda_ilustrasi((bool) $l['ilustrasi']) ?></h3>
        <p><?= e($l['kegiatan']) ?></p>
        <dl><div><dt>Jadwal</dt><dd><?= e($l['jadwal']) ?></dd></div><?php if ((int) $l['anggota']): ?><div><dt>Anggota</dt><dd><?= angka($l['anggota']) ?> orang</dd></div><?php endif; ?></dl>
      </article>
    <?php endforeach; ?>
  </div>
</div></section>
<?php endif; ?>
<?php if ($titikPeta): ?>
<div class="sec tint" id="peta-desa"><div class="wrap">
  <h2 id="fasilitas">Peta desa</h2>
  <p class="muted" style="max-width:70ch">Nyalakan lapisan yang kamu perlukan. Lingkaran merah adalah zona rawan banjir, rob, atau abrasi; saat ada siaga cuaca, zona ini disorot dan sebaiknya dihindari.</p>
  <div class="siaga" data-siaga data-lat="<?= e(setting('lat')) ?>" data-lng="<?= e(setting('lng')) ?>" hidden></div>
  <div class="chips lapisan-peta" data-peta="peta-desa-isi" role="group" aria-label="Lapisan peta">
    <?php foreach ($LAPISAN as $k => [$label, $warna]): if (empty($daftarPeta[$k])) continue; ?>
      <button type="button" class="chip on" data-lapis="<?= $k ?>" aria-pressed="true"><span class="titik-warna" style="background:<?= $warna ?>"></span><?= $label ?> <span class="small">(<?= count($daftarPeta[$k]) ?>)</span></button>
    <?php endforeach; ?>
  </div>
  <div class="split peta-split" style="margin-top:14px">
    <div class="peta peta-fasilitas" id="peta-desa-isi" data-titik="<?= titik_peta($titikPeta) ?>" role="region" aria-label="Peta desa berlapis"></div>
    <div class="daftar-peta">
      <?php foreach ($LAPISAN as $k => [$label, $warna]): if (empty($daftarPeta[$k])) continue; ?>
        <section data-lapis-item="<?= $k ?>">
          <h3 class="small"><span class="titik-warna" style="background:<?= $warna ?>"></span><?= $label ?></h3>
          <ul class="daftar-fasilitas">
            <?php foreach ($daftarPeta[$k] as $t): ?>
              <li><div><?= $t['url'] ? '<a href="' . e($t['url']) . '"><b>' . e($t['nama']) . '</b></a>' : '<b>' . e($t['nama']) . '</b>' ?><br><span class="small muted"><?= e($t['ket']) ?></span></div></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endforeach; ?>
    </div>
  </div>
</div></div>
<?php endif; ?>
<section class="sec" aria-labelledby="judul-prinsip"><div class="wrap">
  <div class="sec-head"><h2 id="judul-prinsip">Prinsip website desa ini</h2><p>Dibuat supaya teknologi membantu semua warga, bukan hanya yang sudah terbiasa dengan internet.</p></div>
  <div class="prinsip-grid">
    <article class="prinsip">
      <h3>Inovatif, tetap sederhana</h3>
      <p>Surat online, asisten desa bermenu yang jawabannya disusun petugas, dan peta yang menyorot zona rob saat siaga cuaca. Semua nyaman di HP dan tetap bisa dibuka tanpa sinyal setelah dipasang.</p>
      <a href="/data-desa?grup=Layanan+surat">Coba surat online</a>
    </article>
    <article class="prinsip">
      <h3>Tumbuh bersama</h3>
      <p>Warga dan pengunjung bisa ikut tanam mangrove dan bersih pantai, menyampaikan aspirasi yang perkembangannya terbuka, dan menitipkan produk UMKM sendiri di website desa.</p>
      <a href="/agenda">Ikut gotong royong</a>
    </article>
    <article class="prinsip">
      <h3>Peduli pada yang paling membutuhkan</h3>
      <p>Huruf besar dan kontras tinggi untuk lansia, bahasa sehari-hari, aspirasi boleh tanpa nama, dan nama penerima bansos selalu disamarkan. Website ini tidak pernah meminta KTP, KK, atau NIK.</p>
      <a href="/kontak#privasi">Baca kebijakan privasi</a>
    </article>
  </div>
  <div class="sdg-ringkas" id="sdgs">
    <p><b>Mendukung Tujuan Pembangunan Berkelanjutan (SDGs):</b></p>
    <ul>
      <?php foreach ([1 => 'bantuan sosial yang terbuka', 8 => 'UMKM warga tanpa perantara', 11 => 'peta zona rawan rob dan banjir', 14 => 'mangrove dan pesisir yang lestari', 16 => 'aspirasi dan anggaran yang transparan'] as $n => $ket): ?>
        <li><?= sdg_bulat($n) ?><span><?= e(SDG[$n][0]) ?>: <?= e($ket) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div></section>
<?php require __DIR__ . '/../inc/footer.php'; ?>
