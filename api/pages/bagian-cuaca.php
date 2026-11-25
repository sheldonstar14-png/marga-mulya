<?php
// =========================================================
// BAGIAN CUACA DARAT DAN LAUT (12w)
// Dulu halaman sendiri (/cuaca). Sekarang tab "Cuaca" di halaman Informasi desa
// (/data-desa?grup=Cuaca) supaya halaman publik tidak lebih dari 10.
// Data diambil langsung oleh browser dari Open-Meteo.
// =========================================================
$lat = (float) setting('lat');
$lng = (float) setting('lng');
$latLaut = $lat + 0.05; // titik perairan sekitar 5 km di utara desa
?>
<div class="cuaca-isi" id="cuaca">
  <div class="siaga" data-siaga data-lat="<?= e((string) $lat) ?>" data-lng="<?= e((string) $lng) ?>" hidden></div>

  <section class="cuaca-bagian" id="darat" aria-labelledby="judul-darat">
    <div class="kepala"><div><h2 id="judul-darat">Darat: sawah, kebun, dan kampung</h2><p><?= e(teks('intro_cuaca_darat')) ?></p></div></div>
    <div class="cuaca-hari" data-cuaca-darat data-lat="<?= e((string) $lat) ?>" data-lng="<?= e((string) $lng) ?>"><p class="muted">Memuat prakiraan cuaca darat...</p></div>
    <details class="cara-baca"><summary>Cara membaca saran untuk petani</summary>
      <ul class="small">
        <li><b>Cocok menjemur:</b> peluang hujan di bawah 30% dan curah hujan sangat sedikit.</li>
        <li><b>Jangan menyemprot:</b> angin di atas 20 km/jam membuat obat semprot terbawa angin, atau hujan akan membilasnya.</li>
        <li><b>Hujan lebat:</b> di atas 50 mm sehari. Periksa saluran air sawah dan tanggul tambak.</li>
      </ul>
    </details>
  </section>

  <section class="cuaca-bagian" id="laut" aria-labelledby="judul-laut">
    <div class="kepala"><div><h2 id="judul-laut">Laut: perairan utara <?= e(setting('nama_desa')) ?></h2><p><?= e(teks('intro_cuaca_laut')) ?></p></div></div>
    <div class="cuaca-hari" data-cuaca-laut data-lat="<?= e((string) $latLaut) ?>" data-lng="<?= e((string) $lng) ?>"><p class="muted">Memuat prakiraan gelombang...</p></div>
    <div class="ambang">
      <div class="ambang-aman"><b>Di bawah <?= str_replace('.', ',', (string) AMBANG_GELOMBANG[0]) ?> m</b><span>Aman untuk perahu kecil</span></div>
      <div class="ambang-waspada"><b><?= str_replace('.', ',', (string) AMBANG_GELOMBANG[0]) ?> sampai <?= str_replace('.', ',', (string) AMBANG_GELOMBANG[1]) ?> m</b><span>Berbahaya untuk perahu motor tempel</span></div>
      <div class="ambang-bahaya"><b>Di atas <?= str_replace('.', ',', (string) AMBANG_GELOMBANG[1]) ?> m</b><span>Perahu kecil sebaiknya tidak melaut</span></div>
    </div>
    <p class="hint">Patokan di atas mengikuti kategori gelombang yang umum dipakai BMKG. Prakiraan model bisa meleset, jadi tetap dengarkan kabar ketua kelompok nelayan dan peringatan resmi di <a href="https://maritim.bmkg.go.id" target="_blank" rel="noopener">BMKG Maritim</a>.</p>
  </section>

  <?php if (setting('waktu_kunjung') !== ''): ?>
  <div class="info-waktu" id="waktu-kunjung">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
    <p><b>Waktu terbaik berkunjung:</b> <?= e(setting('waktu_kunjung')) ?> <a href="/wisata">Lihat destinasi</a>.</p>
  </div>
  <?php endif; ?>
  <p class="wx-src" style="margin-top:16px">Sumber data: Open-Meteo (prakiraan cuaca dan Marine API, lisensi CC BY 4.0). Tanpa sinyal, halaman ini menampilkan salinan terakhir yang pernah dibuka.</p>
</div>
