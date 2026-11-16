<?php
// =========================================================
// HALAMAN DETAIL WISATA, contoh alamat: /wisata/curug
// ($slug diisi oleh router)
// =========================================================
$w = q1('SELECT * FROM wisata WHERE slug = ? AND aktif = 1', [$slug]);
if (!$w) {
    http_response_code(404);
    $path = 'wisata/' . $slug;
    require __DIR__ . '/404.php';
    return;
}
$fasilitas = array_filter(array_map('trim', explode(',', $w['fasilitas'])));
$titik = [['lat' => (float) $w['lat'], 'lng' => (float) $w['lng'], 'nama' => $w['nama'], 'jenis' => 'pilih']];
$aturan = kode_etik($w);
$ringkasUlasan = q1('SELECT COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan WHERE wisata_id = ? AND tampil = 1', [$w['id']]);
$daftarUlasan = q('SELECT nama, bintang, komentar, dibuat_pada FROM ulasan WHERE wisata_id = ? AND tampil = 1 ORDER BY id DESC LIMIT 6', [$w['id']]);
$linkArah = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($w['lat'] . ',' . $w['lng']);

// Kalender ketersediaan 14 hari mulai besok
$hariKalender = array_map(fn($i) => date('Y-m-d', strtotime("+$i day")), range(1, 14));
$terisi = [];
foreach (q("SELECT tanggal, SUM(jumlah) AS n FROM reservasi WHERE wisata_id = ? AND status <> 'Dibatalkan' AND tanggal BETWEEN ? AND ? GROUP BY tanggal", [$w['id'], $hariKalender[0], end($hariKalender)]) as $r) {
    $terisi[$r['tanggal']] = (int) $r['n'];
}
$namaHariPendek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

$judul = $w['nama'] . ' - ' . setting('nama_desa');
$menu  = 'wisata';
$pakaiPeta = true;
$pakaiCuaca = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap sec">
  <p><a href="/wisata">Semua destinasi</a></p>
  <div class="split">
    <div>
      <div class="art" style="border-radius:var(--r-l);aspect-ratio:16/9"><img src="<?= e($w['foto'] ?: '/assets/img/sawah.svg') ?>" alt="<?= e($w['nama']) ?>"></div>
      <h1 style="margin-top:24px;margin-bottom:6px"><?= e($w['nama']) ?></h1>
      <?php if ($ringkasUlasan['n'] > 0): ?>
        <p style="margin:0 0 14px"><a href="#ulasan-pengunjung" style="text-decoration:none;color:inherit"><span class="bintang" aria-hidden="true"><?= bintang((float) $ringkasUlasan['rata']) ?></span> <b><?= number_format((float) $ringkasUlasan['rata'], 1, ',', '.') ?></b> <span class="muted">dari <?= (int) $ringkasUlasan['n'] ?> ulasan pengunjung</span></a></p>
      <?php endif; ?>
      <p><?= nl2br(e($w['deskripsi'])) ?></p>
      <div class="facts">
        <div class="fact"><span>Tiket per orang</span><b><?= rupiah($w['harga']) ?></b></div>
        <div class="fact"><span>Jam buka</span><b><?= e($w['jam_buka']) ?></b></div>
        <div class="fact"><span>Kuota harian</span><b><?= (int) $w['kuota'] ?> orang</b></div>
      </div>
      <?php if ($fasilitas): ?>
        <h3>Fasilitas</h3>
        <div class="chips"><?php foreach ($fasilitas as $f): ?><span class="tag"><?= e($f) ?></span><?php endforeach; ?></div>
      <?php endif; ?>
      <?php if ($w['tips']): ?><p class="cms-note">Tips pengelola: <?= e($w['tips']) ?></p><?php endif; ?>

      <h2 style="margin-top:32px">Pilih tanggal kunjungan</h2>
      <p class="muted">Sisa kuota 14 hari ke depan. Klik tanggal untuk langsung memesan. Ikon cuaca muncul untuk tanggal yang sudah ada prakiraannya.</p>
      <div class="kalender" id="kalender" data-lat="<?= e($w['lat']) ?>" data-lng="<?= e($w['lng']) ?>">
        <?php foreach ($hariKalender as $t):
          $sisa = max(0, $w['kuota'] - ($terisi[$t] ?? 0));
          $rasio = 1 - $sisa / max(1, $w['kuota']);
          $kelas = $sisa === 0 ? 'q-full' : ($rasio >= .5 ? 'q-mid' : 'q-ok');
          $isi = '<span>' . $namaHariPendek[(int) date('w', strtotime($t))] . '</span><b>' . tgl($t, 'tanggal') . '</b><span>' . tgl($t, 'bulan') . '</span>'
               . '<span class="kal-cuaca" data-tgl="' . $t . '"></span><span>' . ($sisa === 0 ? 'Penuh' : 'Sisa ' . $sisa) . '</span>'; ?>
          <?php if ($sisa === 0): ?>
            <div class="kal <?= $kelas ?>" aria-label="<?= tgl($t, 'hari') ?>, kuota penuh"><?= $isi ?></div>
          <?php else: ?>
            <a class="kal <?= $kelas ?>" href="/reservasi?w=<?= e($w['slug']) ?>&amp;t=<?= $t ?>" aria-label="Pesan untuk <?= tgl($t, 'hari') ?>, sisa <?= $sisa ?> orang"><?= $isi ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div class="legend-kal"><span style="--c:#DCEFD8">Masih longgar</span><span style="--c:#FCEFCF">Terisi lebih dari separuh</span><span style="--c:#F6DCD6">Penuh</span></div>

      <h2 id="ulasan-pengunjung" style="margin-top:32px">Ulasan pengunjung</h2>
      <?php if ($daftarUlasan): ?>
        <div class="grid">
          <?php foreach ($daftarUlasan as $u): ?>
            <div class="panel" style="padding:16px 18px">
              <span class="bintang" aria-label="<?= (int) $u['bintang'] ?> dari 5 bintang"><?= bintang($u['bintang']) ?></span>
              <p style="margin:6px 0"><?= nl2br(e($u['komentar'])) ?></p>
              <span class="small muted"><?= e(explode(' ', trim($u['nama']))[0]) ?>, <?= tgl(substr($u['dibuat_pada'], 0, 10)) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="muted">Belum ada ulasan. Pengunjung bisa memberi ulasan setelah berkunjung, lewat kode reservasinya.</p>
      <?php endif; ?>

      <?php if ($aturan): ?>
        <div class="etik-box">
          <h3>Kode etik pengunjung</h3>
          <p class="small muted">Kuota harian <?= (int) $w['kuota'] ?> orang adalah batas daya tampung, supaya alam dan warga di sekitar <?= e($w['nama']) ?> tetap terjaga.</p>
          <ul class="etik"><?php foreach ($aturan as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
    </div>

    <aside>
      <div class="wx" style="box-shadow:none;border:1px solid var(--line)"
           data-cuaca data-lat="<?= e($w['lat']) ?>" data-lng="<?= e($w['lng']) ?>"
           data-jenis="<?= e($w['jenis']) ?>" data-nama="<?= e($w['nama']) ?>"
           data-judul="Cuaca di lokasi" data-tombol="tombolPesan">
        <h3>Cuaca di lokasi</h3>
        <p class="wx-note">Memuat prakiraan cuaca...</p>
      </div>
      <a class="btn btn-main" style="margin-top:14px;width:100%" id="tombolPesan" href="/reservasi?w=<?= e($w['slug']) ?>">Pesan kunjungan</a>
      <a class="btn btn-ghost" style="margin-top:10px;width:100%" href="<?= e($linkArah) ?>" target="_blank" rel="noopener">Petunjuk arah di Google Maps</a>
      <?php if ($w['gmaps_ulasan']): ?><p class="small" style="text-align:center;margin:8px 0 0"><a href="<?= e($w['gmaps_ulasan']) ?>" target="_blank" rel="noopener">Lihat ulasan di Google Maps</a></p><?php endif; ?>
      <div class="peta peta-kecil" style="margin-top:18px" data-titik="<?= titik_peta($titik) ?>" role="region" aria-label="Lokasi <?= e($w['nama']) ?>"></div>
    </aside>
  </div>
</div>
<?php
// Tambahkan ikon cuaca ke kalender untuk tanggal yang sudah ada prakiraannya
$scriptHalaman = <<<'JS'
<script>
(function () {
  const kal = document.getElementById('kalender');
  if (!kal || typeof ambilCuaca === 'undefined') return;
  ambilCuaca(kal.dataset.lat, kal.dataset.lng).then(data => {
    kal.querySelectorAll('[data-tgl]').forEach(el => {
      const h = data.hari.find(x => x.tgl === el.dataset.tgl);
      if (!h) return;
      const [nama, jenis] = infoCuaca(h.kode);
      el.innerHTML = ikonCuaca(jenis);
      el.title = nama + ', peluang hujan ' + h.hujan + '%';
    });
  }).catch(() => {});
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php';
?>
