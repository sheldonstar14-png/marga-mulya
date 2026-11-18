</main>
<footer class="kaki">
  <div class="kaki-tepi" aria-hidden="true"></div>
  <div class="wrap kaki-isi">
    <div class="kaki-identitas">
      <img src="/assets/img/logo-mark.png?v=2" alt="" width="120" height="66" class="kaki-logo">
      <h3><?= e(setting('nama_desa')) ?></h3>
      <p>Desa tepi laut di <?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?>. Tempat nelayan, petani, dan pengrajin tumbuh bersama.</p>
      <img src="/assets/img/perahu.svg" alt="" width="180" height="98" class="kaki-perahu" loading="lazy">
    </div>
    <div>
      <h3>Jelajahi</h3>
      <ul class="kaki-tautan">
        <li><a href="/profil">Profil desa</a></li>
        <li><a href="/data-desa">Informasi desa</a></li>
        <li><a href="/wisata">Wisata pesisir</a></li>
        <li><a href="/umkm">Produk UMKM</a></li>
        <li><a href="/umkm#ajukan">Ajukan produk</a></li>
        <li><a href="/data-desa?grup=Layanan+surat">Layanan surat dan antrean</a></li>
        <li><a href="/data-desa?grup=Pantau+bansos">Pantau bansos</a></li>
        <li><a href="/kontak#papan">Papan aspirasi</a></li>
        <li><a href="/agenda">Agenda dan berita</a></li>
        <li><a href="/kontak#privasi">Privasi dan data pribadi</a></li>
      </ul>
    </div>
    <div>
      <h3>Hubungi kami</h3>
      <p><?= e(setting('alamat')) ?></p>
      <p><?= e(setting('jam_layanan')) ?><br><?= e(setting('email')) ?><br>WhatsApp +<?= e(setting('wa')) ?></p>
      <a class="btn btn-sm btn-garis" href="/kontak#darurat">Nomor darurat</a>
      <button type="button" class="btn btn-sm pasang-btn" id="pasangBtn" hidden>Pasang di HP (bisa dibuka tanpa sinyal)</button>
    </div>
  </div>
  <div class="wrap footer-bawah">
    <p class="small">&copy; <?= date('Y') ?> Pemerintah <?= e(setting('nama_desa')) ?>. Isi website dikelola perangkat desa.</p>
    <button type="button" class="kaki-kredit" id="kreditBtn" aria-haspopup="dialog">Sumber dan kredit foto</button>
    <?php if (setting('referensi_data_url') !== ''): ?><a class="small" href="<?= e(setting('referensi_data_url')) ?>" target="_blank" rel="noopener">Referensi data BPS</a><?php endif; ?>
  </div>
  <div class="wrap"><p class="kaki-catatan">Versi demonstrasi EcoQuest. Angka bertanda ilustrasi, nama usaha, dan agenda adalah contoh; data bertanda sumber diambil dari publikasi resmi.</p></div>
</footer>
<dialog class="kredit-dialog" id="kreditDialog" aria-labelledby="judulKredit">
  <div class="kredit-kepala"><h2 id="judulKredit">Sumber dan kredit</h2><button type="button" class="btn btn-ghost btn-sm" data-tutup aria-label="Tutup">&times;</button></div>
  <div class="kredit-isi">
    <p class="muted">Foto dan gambar bertanda ilustrasi bukan dokumentasi resmi Desa Marga Mulya. Setiap aset milik pihak lain dipakai sesuai lisensinya.</p>
    <?php $kreditList = daftar_kredit(); ?>
    <?php if ($kreditList): ?>
      <ul class="kredit-daftar">
        <?php foreach ($kreditList as $kf): ?>
          <li>
            <b><?= e($kf['judul']) ?></b>
            <?php if ($kf['pembuat']): ?><span class="muted"><?= e($kf['pembuat']) ?></span><?php endif; ?>
            <span class="kredit-tautan">
              <?php if ($kf['sumber_url']): ?><a href="<?= e($kf['sumber_url']) ?>" target="_blank" rel="noopener nofollow">Sumber</a><?php endif; ?>
              <?php if ($kf['lisensi']): ?><?= $kf['lisensi_url'] ? '<a href="' . e($kf['lisensi_url']) . '" target="_blank" rel="noopener nofollow">' . e($kf['lisensi']) . '</a>' : '<span>' . e($kf['lisensi']) . '</span>' ?><?php endif; ?>
            </span>
            <?php if ($kf['keterangan']): ?><small class="muted"><?= e($kf['keterangan']) ?></small><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?= kredit_foto_html(setting('kredit_foto')) ?>
  </div>
</dialog>
<script>
  (function () {
    var d = document.getElementById('kreditDialog'), b = document.getElementById('kreditBtn');
    if (!d || !b || !d.showModal) return;
    b.addEventListener('click', function () { d.showModal(); });
    d.addEventListener('click', function (e) { if (e.target === d || e.target.closest('[data-tutup]')) d.close(); });
  })();
</script>
<script>
  // Tombol menu untuk layar HP
  const tombol = document.getElementById('menuBtn'), nav = document.getElementById('nav');
  tombol.addEventListener('click', () => {
    nav.classList.toggle('open');
    tombol.setAttribute('aria-expanded', nav.classList.contains('open'));
  });
</script>
<button class="asisten-btn" id="asistenBtn" aria-expanded="false" aria-controls="asistenPanel">
  <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="9" cy="10.5" r="1.2" fill="currentColor"/><circle cx="12" cy="10.5" r="1.2" fill="currentColor"/><circle cx="15" cy="10.5" r="1.2" fill="currentColor"/></svg>
  <span>Tanya Asisten Desa</span>
</button>
<section class="asisten-panel" id="asistenPanel" hidden role="dialog" aria-label="Asisten <?= e(setting('nama_desa')) ?>">
  <div class="asisten-head">
    <div><b>Asisten <?= e(str_replace('Desa ', '', setting('nama_desa'))) ?></b><small>Jawaban otomatis dari data resmi desa</small></div>
    <button type="button" id="asistenTutup" aria-label="Tutup asisten">&times;</button>
  </div>
  <div class="asisten-log" id="asistenLog" aria-live="polite"></div>
  <div class="asisten-saran" id="asistenSaran"></div>
  <form class="asisten-form" id="asistenForm">
    <label class="sr-only" for="asistenInput">Tulis pertanyaan</label>
    <input id="asistenInput" maxlength="300" autocomplete="off" placeholder="Contoh: berapa harga tiket pantai?">
    <button class="btn btn-main btn-sm" type="submit">Kirim</button>
  </form>
  <p class="asisten-catatan">Asisten ini bukan manusia dan tidak menyimpan percakapan. Jangan tulis NIK atau data pribadi.</p>
</section>
<script src="/assets/js/akses.js"></script>
<script src="/assets/js/asisten.js"></script>
<?php if (!empty($pakaiPeta)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="/assets/js/peta.js"></script>
<?php endif; ?>
<?php if (!empty($pakaiCuaca)):
  // Destinasi yang tetap nyaman saat hujan, dipakai saran cuaca (centang di CMS > Wisata)
  $wisataHujan = array_map(fn($w) => ['nama' => $w['nama'], 'url' => '/wisata/' . $w['slug']], q('SELECT nama, slug FROM wisata WHERE aktif = 1 AND saat_hujan = 1 ORDER BY id')); ?>
<script>window.WISATA_HUJAN = <?= json_encode($wisataHujan, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="/assets/js/cuaca.js"></script>
<?php endif; ?>
<?= $scriptHalaman ?? '' ?>
<?php if (function_exists('gerbang_aktif') && gerbang_aktif()): ?>
<form method="post" action="/keluar-situs" class="gerbang-keluar"><button type="submit">Mode privat aktif. Keluar</button></form>
<?php endif; ?>
</body>
</html>
