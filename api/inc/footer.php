</main>
<footer>
  <div class="wrap grid g3">
    <div>
      <h3><?= e(setting('nama_desa')) ?></h3>
      <p><?= e(setting('alamat')) ?><br><?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></p>
    </div>
    <div>
      <h3>Jelajahi</h3>
      <p><a href="/wisata">Wisata</a><br><a href="/reservasi">Reservasi dan cek status</a><br><a href="/data-desa?grup=Layanan+surat">Syarat layanan surat</a><br><a href="/kontak#privasi">Privasi dan data pribadi</a></p>
    </div>
    <div>
      <h3>Hubungi kami</h3>
      <p><?= e(setting('jam_layanan')) ?><br><?= e(setting('email')) ?><br>WhatsApp +<?= e(setting('wa')) ?></p>
    </div>
  </div>
  <?php if (setting('kredit_foto') !== ''): ?>
    <div class="wrap"><p class="small" style="margin:24px 0 0;opacity:.8"><?= nl2br(e(setting('kredit_foto'))) ?></p></div>
  <?php endif; ?>
</footer>
<script>
  // Tombol menu untuk layar HP
  const tombol = document.getElementById('menuBtn'), nav = document.getElementById('nav');
  tombol.addEventListener('click', () => {
    nav.classList.toggle('open');
    tombol.setAttribute('aria-expanded', nav.classList.contains('open'));
  });
</script>
<script src="/assets/js/akses.js"></script>
<?php if (!empty($pakaiPeta)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="/assets/js/peta.js"></script>
<?php endif; ?>
<?php if (!empty($pakaiCuaca)): ?>
<script src="/assets/js/cuaca.js"></script>
<?php endif; ?>
<?= $scriptHalaman ?? '' ?>
</body>
</html>
