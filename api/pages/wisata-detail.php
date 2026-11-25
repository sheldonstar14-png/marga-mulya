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
$ringkasUlasan = q1('SELECT COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan WHERE wisata_id = ? AND tampil = 1', [$w['id']]);
$daftarUlasan = q('SELECT nama, bintang, komentar, dibuat_pada FROM ulasan WHERE wisata_id = ? AND tampil = 1 ORDER BY id DESC LIMIT 6', [$w['id']]);
$linkArah = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($w['lat'] . ',' . $w['lng']);

[$namaW, $ilusW] = pisah_ilustrasi($w['nama']);
$etik = kode_etik_kelompok($w);

$judul = $w['nama'] . ' - ' . setting('nama_desa');
$menu  = 'wisata';
$pakaiCuaca = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap sec">
  <p><a href="/wisata">Semua destinasi</a></p>
  <div class="split">
    <div>
      <div class="art detail-foto" style="border-radius:var(--r-l);aspect-ratio:16/9;position:relative"><img src="<?= e($w['foto'] ?: '/assets/img/panorama.svg') ?>" alt="<?= e($namaW) ?>"><?= tanda_foto((string) $w['foto'], $ilusW) ?></div>
      <span class="chip-kecil" style="margin-top:22px;display:inline-block"><?= e($w['jenis']) ?></span>
      <h1 style="margin-top:8px;margin-bottom:6px"><?= e($namaW) ?></h1>
      <?php if ($ringkasUlasan['n'] > 0): ?>
        <p style="margin:0 0 14px"><a href="#ulasan-pengunjung" style="text-decoration:none;color:inherit"><span class="bintang" aria-hidden="true"><?= bintang((float) $ringkasUlasan['rata']) ?></span> <b><?= number_format((float) $ringkasUlasan['rata'], 1, ',', '.') ?></b> <span class="muted">dari <?= (int) $ringkasUlasan['n'] ?> ulasan pengunjung</span></a></p>
      <?php endif; ?>
      <p><?= nl2br(e($w['deskripsi'])) ?></p>
      <div class="facts">
        <div class="fact"><span>Tiket per orang</span><b><?= rupiah($w['harga']) ?></b></div>
        <div class="fact"><span>Jam buka</span><b><?= e($w['jam_buka']) ?></b></div>
      </div>
      <?php if (!empty($w['waktu_terbaik'])): ?><div class="info-waktu"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg><p><b>Waktu terbaik berkunjung:</b> <?= e($w['waktu_terbaik']) ?></p></div><?php endif; ?>
      <?php if ($fasilitas): ?>
        <h3>Fasilitas</h3>
        <div class="chips"><?php foreach ($fasilitas as $f): ?><span class="tag"><?= e($f) ?></span><?php endforeach; ?></div>
      <?php endif; ?>
      <?php if ($w['tips']): ?><p class="cms-note">Tips pengelola: <?= e($w['tips']) ?></p><?php endif; ?>
      <div class="akses-box">
        <h3>Aksesibilitas</h3>
        <div class="akses-label"><?php foreach (label_akses($w) as [$kelas, $teks]): ?><span class="<?= $kelas ?>"><?= e($teks) ?></span><?php endforeach; ?></div>
        <?php if ($w['catatan_akses']): ?><p class="small" style="margin:8px 0 0"><?= e($w['catatan_akses']) ?></p><?php endif; ?>
        <p class="small muted" style="margin:6px 0 0">Butuh bantuan khusus, misalnya pendamping atau kursi roda? Hubungi WhatsApp desa sebelum datang, atau tulis di permohonan izin kunjungan rombongan.</p>
      </div>

      <?php if ($daftarUlasan): ?>
      <h2 id="ulasan-pengunjung" style="margin-top:32px">Ulasan pengunjung</h2>
        <div class="grid">
          <?php foreach ($daftarUlasan as $u): ?>
            <div class="panel" style="padding:16px 18px">
              <span class="bintang" aria-label="<?= (int) $u['bintang'] ?> dari 5 bintang"><?= bintang($u['bintang']) ?></span>
              <p style="margin:6px 0"><?= nl2br(e($u['komentar'])) ?></p>
              <span class="small muted"><?= e(explode(' ', trim($u['nama']))[0]) ?>, <?= tgl(substr($u['dibuat_pada'], 0, 10)) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="etik-box" id="kode-etik">
        <h2 style="margin:0 0 6px;font-size:1.4rem">Kode etik pengunjung</h2>
        <p class="small muted">Bantu menjaga <?= e($namaW) ?> tetap lestari dan warga sekitar tetap nyaman.</p>
        <div class="etik-grid etik-kecil">
          <?php foreach ($etik as $grupEtik => $aturan): if (stripos($grupEtik, 'rombongan') === 0) continue; ?><div><h3><?= e($grupEtik) ?></h3><ul class="etik"><?php foreach ($aturan as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?>
        </div>
      </div>
    </div>

    <aside>
      <div class="wx" style="box-shadow:none;border:1px solid var(--line)"
           data-cuaca data-lat="<?= e($w['lat']) ?>" data-lng="<?= e($w['lng']) ?>"
           data-jenis="<?= e($w['jenis']) ?>" data-nama="<?= e($w['nama']) ?>"
           data-judul="Cuaca di lokasi">
        <h3>Cuaca di lokasi</h3>
        <p class="wx-note">Memuat prakiraan cuaca...</p>
      </div>
      <div class="panel datang-box">
        <h3>Mau datang?</h3>
        <p class="small">Perorangan dan keluarga cukup datang langsung di jam buka. Rombongan 10 orang atau lebih, KKN, dan penelitian ajukan izin kunjungan dulu supaya pemandu disiapkan.</p>
        <a class="btn btn-main btn-penuh" href="/data-desa?grup=Layanan+surat&amp;izin=1#surat">Ajukan izin kunjungan rombongan</a>
        <a class="btn btn-ghost btn-penuh" href="<?= e(wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya tentang kunjungan ke ' . $namaW . '.')) ?>" target="_blank" rel="noopener">Tanya lewat WhatsApp</a>
      </div>
      <a class="btn btn-ghost" style="margin-top:10px;width:100%" href="<?= e($linkArah) ?>" target="_blank" rel="noopener">Petunjuk arah di Google Maps</a>
      <?php if ($w['gmaps_ulasan']): ?><p class="small" style="text-align:center;margin:8px 0 0"><a href="<?= e($w['gmaps_ulasan']) ?>" target="_blank" rel="noopener">Lihat ulasan di Google Maps</a></p><?php endif; ?>
      <iframe class="peta-google" title="Lokasi <?= e($namaW) ?> di Google Maps" src="<?= e(gmaps_embed((float) $w['lat'], (float) $w['lng'])) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
