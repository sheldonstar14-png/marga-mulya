</main>
<footer>
  <div class="wrap grid g3">
    <div>
      <h3><?= e(setting('nama_desa')) ?></h3>
      <p><?= e(setting('alamat')) ?><br><?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></p>
    </div>
    <div>
      <h3>Jelajahi</h3>
      <p><a href="/wisata">Wisata</a><br><a href="/reservasi">Reservasi dan cek status</a><br><a href="/data-desa?grup=Layanan+surat">Layanan surat dan antrean</a><br><a href="/data-desa?grup=Pantau+bansos">Pantau bansos</a><br><a href="/kontak#privasi">Privasi dan data pribadi</a></p>
    </div>
    <div>
      <h3>Hubungi kami</h3>
      <p><?= e(setting('jam_layanan')) ?><br><?= e(setting('email')) ?><br>WhatsApp +<?= e(setting('wa')) ?></p>
      <button type="button" class="btn btn-sm pasang-btn" id="pasangBtn" hidden>Pasang di HP (bisa dibuka tanpa sinyal)</button>
    </div>
  </div>
  <div class="wrap footer-bawah">
    <p class="small">&copy; <?= date('Y') ?> Pemerintah <?= e(setting('nama_desa')) ?>. Isi website dikelola perangkat desa.</p>
    <?= kredit_foto_html(setting('kredit_foto')) ?>
  </div>
</footer>
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
    <input id="asistenInput" maxlength="300" autocomplete="off" placeholder="Contoh: berapa harga tiket curug?">
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
