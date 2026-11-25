<?php
// =========================================================
// HALAMAN WISATA: daftar destinasi, peta potensi, budaya
// =========================================================
$wisata = q('SELECT * FROM wisata WHERE aktif = 1 ORDER BY id');
$rating = [];
foreach (q('SELECT wisata_id, COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan WHERE tampil = 1 GROUP BY wisata_id') as $r) {
    $rating[$r['wisata_id']] = $r;
}
try { $budaya = q('SELECT * FROM budaya ORDER BY urutan, id'); } catch (Throwable $e) { $budaya = q('SELECT * FROM budaya ORDER BY id'); }
$jenisWisata = array_values(array_unique(array_column($wisata, 'jenis')));
$etik = kode_etik_kelompok();

// Lokasi untuk peta Google: wisata, UMKM, dan kantor desa
$grupPeta = ['Wisata' => [], 'UMKM' => [], 'Kantor desa' => []];
foreach ($wisata as $w) $grupPeta['Wisata'][] = ['nama' => $w['nama'], 'ket' => $w['jenis'] . ', ' . $w['jam_buka'], 'lat' => $w['lat'], 'lng' => $w['lng'], 'url' => '/wisata/' . $w['slug'], 'warna' => '#F08A3C'];
foreach (q('SELECT id, nama_usaha, produk, lat, lng FROM umkm WHERE lat IS NOT NULL AND lng IS NOT NULL ORDER BY id') as $u) $grupPeta['UMKM'][] = ['nama' => $u['nama_usaha'], 'ket' => $u['produk'], 'lat' => $u['lat'], 'lng' => $u['lng'], 'url' => '/umkm#umkm-' . (int) $u['id'], 'warna' => '#15607F'];
$grupPeta['Kantor desa'][] = ['nama' => 'Kantor ' . setting('nama_desa'), 'ket' => setting('jam_layanan'), 'lat' => setting('lat'), 'lng' => setting('lng'), 'url' => '/kontak', 'warna' => '#0B3C5D'];

$judul = 'Wisata dan Budaya - ' . setting('nama_desa');
$menu  = 'wisata';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Wisata dan budaya</h1>
  <p><?= e(teks('intro_wisata')) ?> Rombongan, KKN, dan penelitian <a href="/data-desa?grup=Layanan+surat&amp;izin=1#surat">mengajukan izin kunjungan</a> dulu.</p>
</div>
<div class="wrap sec" style="padding-top:22px">
  <?php if (count($jenisWisata) > 1): ?>
  <nav class="chips" aria-label="Saring jenis wisata" id="saringWisata">
    <button type="button" class="chip on" data-jenis="">Semua</button>
    <?php foreach ($jenisWisata as $j): ?><button type="button" class="chip" data-jenis="<?= e($j) ?>"><?= e($j) ?></button><?php endforeach; ?>
  </nav>
  <?php endif; ?>
  <div class="wisata-grid">
    <?php foreach ($wisata as $w): [$nmW, $ilusW] = pisah_ilustrasi($w['nama']); ?>
      <a class="w-kartu" href="/wisata/<?= e($w['slug']) ?>" data-jenis="<?= e($w['jenis']) ?>">
        <span class="w-foto"><img src="<?= e($w['foto'] ?: '/assets/img/panorama.svg') ?>" alt="<?= e($nmW) ?>" loading="lazy"><?= tanda_foto((string) $w['foto'], $ilusW) ?></span>
        <span class="w-isi">
          <span class="chip-kecil"><?= e($w['jenis']) ?></span>
          <b><?= e($nmW) ?></b>
          <?php if (isset($rating[$w['id']])): ?><span class="small"><span class="bintang" aria-hidden="true">★</span> <?= number_format((float) $rating[$w['id']]['rata'], 1, ',', '.') ?> (<?= (int) $rating[$w['id']]['n'] ?> ulasan)</span><?php endif; ?>
          <span><?= e($w['ringkas']) ?></span>
          <span class="w-fakta"><span><b>Jam</b> <?= e($w['jam_buka']) ?></span><span><b>Tiket</b> <?= (int) $w['harga'] ? rupiah($w['harga']) : 'Gratis' ?></span></span>
          <?php if (!empty($w['waktu_terbaik'])): ?><span class="w-waktu"><b>Waktu terbaik:</b> <?= e($w['waktu_terbaik']) ?></span><?php endif; ?>
          <span class="akses-label"><?php foreach (label_akses($w) as [$kelas, $teks]): ?><span class="<?= $kelas ?>"><?= e($teks) ?></span><?php endforeach; ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (setting('waktu_kunjung') !== ''): ?>
  <div class="info-waktu">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
    <p><b>Kapan sebaiknya datang?</b> <?= e(setting('waktu_kunjung')) ?> <a href="/data-desa?grup=Cuaca">Lihat cuaca dan gelombang hari ini</a>.</p>
  </div>
  <?php endif; ?>
</div>

<section class="sec sec-putih" id="budaya" aria-labelledby="judul-budaya"><div class="wrap">
  <div class="kepala"><div><h2 id="judul-budaya">Budaya dan kesenian</h2><p><?= e(teks('intro_budaya')) ?></p></div></div>
  <div class="budaya-grid">
    <?php foreach ($budaya as $b): [$jdlB, $ilusB] = pisah_ilustrasi($b['judul']); $ilusB = $ilusB || !empty($b['ilustrasi']); ?>
      <article class="budaya-kartu" id="budaya-<?= (int) $b['id'] ?>">
        <div class="budaya-foto"><img src="<?= e(($b['foto'] ?? '') ?: '/assets/img/budaya-sedekah-laut.svg') ?>" alt="" loading="lazy"><?= tanda_foto((string) ($b['foto'] ?? ''), $ilusB, 'Gambar ilustrasi') ?></div>
        <div class="budaya-isi">
          <span class="chip-kecil"><?= e($b['jenis'] ?? 'Budaya') ?></span>
          <h3><?= e($jdlB) ?></h3>
          <p class="small muted" style="margin:0 0 6px"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true" style="display:inline;vertical-align:-2px"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16"/></svg> <?= e($b['waktu']) ?></p>
          <p><?= e($b['isi']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="sec" id="kode-etik" aria-labelledby="judul-etik"><div class="wrap">
  <div class="kepala"><div><h2 id="judul-etik">Kode etik pengunjung</h2><p>Supaya alam tetap lestari, warga nyaman, dan kunjunganmu aman.</p></div></div>
  <div class="etik-grid">
    <?php $etikKhusus = []; foreach ($etik as $grupEtik => $aturan): if (stripos($grupEtik, 'rombongan') === 0) { $etikKhusus = $aturan; continue; } ?>
      <div class="etik-kartu"><h3><?= e($grupEtik) ?></h3><ul class="etik"><?php foreach ($aturan as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul></div>
    <?php endforeach; ?>
  </div>
  <?php if ($etikKhusus): ?>
  <div class="etik-rombongan">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 20c0-2 1-4 3-4s3 1.5 3 4"/></svg>
    <p><b>Datang rombongan, KKN, atau penelitian?</b> <?= e(implode(' ', $etikKhusus)) ?></p>
    <a class="btn btn-navy btn-sm" href="/data-desa?grup=Layanan+surat&amp;izin=1#surat">Ajukan izin kunjungan</a>
  </div>
  <?php endif; ?>
</div></section>

<div class="sec sec-putih"><div class="wrap">
  <div class="kepala"><div><h2 id="peta">Peta wisata dan UMKM</h2><p>Tekan nama tempat untuk melihatnya di Google Maps, atau tekan Rute untuk petunjuk arah dari lokasimu.</p></div></div>
  <?= peta_google('peta-wisata', $grupPeta, 'Peta Google wisata dan UMKM ' . setting('nama_desa')) ?>
</div></div>
<?php
$scriptHalaman = <<<'JS'
<script>
(function () {
  const nav = document.getElementById('saringWisata');
  if (!nav) return;
  nav.addEventListener('click', e => {
    const b = e.target.closest('[data-jenis]'); if (!b) return;
    nav.querySelectorAll('.chip').forEach(c => c.classList.toggle('on', c === b));
    document.querySelectorAll('.w-kartu[data-jenis]').forEach(k => { k.hidden = b.dataset.jenis !== '' && k.dataset.jenis !== b.dataset.jenis; });
  });
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php';
