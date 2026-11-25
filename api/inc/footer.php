</main>
<footer class="kaki">
  <div class="kaki-ilu" aria-hidden="true"><img src="/assets/img/panorama-lebar.svg" alt="" loading="lazy" width="2400" height="240"></div>
  <div class="kaki-badan">
    <div class="wrap kaki-isi">
      <div class="kaki-identitas">
        <a class="brand brand-kaki" href="/"><span class="brand-kotak"><img class="brand-logo" src="/assets/img/logo-mark.png?v=2" alt="" width="64" height="35" loading="lazy"></span><span class="brand-teks"><b><?= e(str_replace('Desa ', '', setting('nama_desa'))) ?></b><small><?= e(nama_kabupaten()) ?></small></span></a>
        <p><?= e(teks('teks_kaki')) ?></p>
        <button type="button" class="btn btn-sm pasang-btn" id="pasangBtn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>Pasang di HP, bisa dibuka tanpa sinyal</button>
      </div>
      <div>
        <h3>Layanan warga</h3>
        <ul class="kaki-tautan">
          <li><a href="/data-desa?grup=Layanan+surat">Pengajuan surat</a></li>
          <li><a href="/data-desa?grup=Layanan+surat&amp;izin=1#surat">Izin kunjungan, KKN, penelitian</a></li>
          <li><a href="/data-desa?grup=Pantau+bansos">Bantuan sosial</a></li>
          <li><a href="/kontak#aspirasi">Aspirasi dan pertanyaan</a></li>
          <li><a href="/kontak#darurat">Nomor darurat</a></li>
        </ul>
      </div>
      <div>
        <h3>Informasi publik</h3>
        <ul class="kaki-tautan">
          <li><a href="/data-desa?grup=Transparansi">Anggaran desa (APBDes)</a></li>
          <li><a href="/data-desa">Data desa</a></li>
          <li><a href="/wisata">Wisata dan budaya</a></li>
          <li><a href="/umkm?bagian=kerja">Kerja dan pelatihan</a></li>
          <li><a href="/data-desa?grup=Cuaca">Cuaca darat dan laut</a></li>
        </ul>
      </div>
      <div>
        <h3>Kantor desa</h3>
        <p><?= e(setting('alamat')) ?></p>
        <p><?= e(setting('jam_layanan')) ?><br>WhatsApp +<?= e(setting('wa')) ?><?php if (setting('email') !== ''): ?><br><?= e(setting('email')) ?><?php endif; ?></p>
      </div>
    </div>
    <div class="wrap footer-bawah">
      <p>&copy; <?= date('Y') ?> Tim EcoQuest <?= e(setting('nama_desa')) ?>. <?= e(teks('teks_hak_cipta')) ?></p>
      <p class="kaki-kecil"><a href="/kontak#privasi">Privasi</a><button type="button" class="kaki-kredit" id="kreditBtn" aria-haspopup="dialog">Sumber dan kredit foto</button><?php if (setting('referensi_data_url') !== ''): ?><a href="<?= e(setting('referensi_data_url')) ?>" target="_blank" rel="noopener">Referensi data BPS</a><?php endif; ?></p>
    </div>
  </div>
</footer>
<dialog class="kredit-dialog" id="kreditDialog" aria-labelledby="judulKredit">
  <div class="kredit-kepala"><h2 id="judulKredit">Sumber dan kredit foto</h2><button type="button" class="btn btn-ghost btn-sm" data-tutup aria-label="Tutup">&times;</button></div>
  <div class="kredit-isi">
    <p class="muted">Sumber setiap foto dan gambar, dikelompokkan menurut halaman tempatnya tampil.</p>
    <?php if (semua_foto_ilustrasi()): ?><p class="kredit-ilus"><?= e(teks('teks_foto_ilustrasi')) ?></p><?php endif; ?>
    <?php foreach (kredit_per_halaman() as $namaHal => $daftarKf): ?>
      <details class="kredit-hal" <?= $namaHal === array_key_first(kredit_per_halaman()) ? 'open' : '' ?>>
        <summary><?= e($namaHal) ?> <span class="muted">(<?= count($daftarKf) ?>)</span></summary>
        <ul class="kredit-daftar">
          <?php foreach ($daftarKf as $kf): ?>
            <li>
              <span class="kredit-lokasi"><?= e($kf['lokasi']) ?></span>
              <b><?= e($kf['judul']) ?></b>
              <span class="kredit-tautan">
                <?php if (!empty($kf['ilus'])): ?><span class="tanda-ilus">Foto ilustrasi</span><?php endif; ?>
                <?php if ($kf['sumber'] !== ''): ?><span><?= e($kf['sumber']) ?></span><?php else: ?><span class="muted"><?= !empty($kf['ilus']) ? 'Foto dari internet, nama pemiliknya belum dicantumkan' : 'Sumber belum dicantumkan' ?></span><?php endif; ?>
                <?php if ($kf['url']): ?><a href="<?= e($kf['url']) ?>" target="_blank" rel="noopener nofollow">Buka sumber</a><?php endif; ?>
                <?php if (!empty($kf['lisensi'])): ?><?= !empty($kf['lisensi_url']) ? '<a href="' . e($kf['lisensi_url']) . '" target="_blank" rel="noopener nofollow">' . e($kf['lisensi']) . '</a>' : '<span>' . e($kf['lisensi']) . '</span>' ?><?php endif; ?>
              </span>
            </li>
          <?php endforeach; ?>
        </ul>
      </details>
    <?php endforeach; ?>
    <?= kredit_foto_html(setting('kredit_foto')) ?>
  </div>
</dialog>
<dialog class="kredit-dialog pasang-dialog" id="pasangDialog" aria-labelledby="judulPasang">
  <div class="kredit-kepala"><h2 id="judulPasang">Pasang website desa di HP</h2><button type="button" class="btn btn-ghost btn-sm" data-tutup aria-label="Tutup">&times;</button></div>
  <div class="kredit-isi">
    <div class="pasang-ios" hidden>
      <p>Di iPhone atau iPad, pemasangan dilakukan lewat <b>Safari</b>:</p>
      <ol class="pasang-langkah">
        <li>Buka website ini di Safari (bukan di aplikasi WhatsApp atau Instagram).</li>
        <li>Tekan tombol <b>Bagikan</b> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-label="ikon Bagikan" role="img" style="display:inline;vertical-align:-3px"><path d="M12 3v12M8 7l4-4 4 4M5 12v8h14v-8"/></svg> di bagian bawah layar.</li>
        <li>Geser ke bawah, pilih <b>Tambah ke Layar Utama</b>, lalu tekan <b>Tambah</b>.</li>
      </ol>
    </div>
    <div class="pasang-lain" hidden>
      <p>Di HP Android dengan Chrome:</p>
      <ol class="pasang-langkah">
        <li>Tekan tombol menu <b>&#8942;</b> di kanan atas.</li>
        <li>Pilih <b>Instal aplikasi</b> atau <b>Tambahkan ke layar utama</b>.</li>
        <li>Ikon desa muncul di layar HP seperti aplikasi biasa.</li>
      </ol>
    </div>
    <p class="hint">Setelah dipasang, beranda, layanan surat, nomor darurat, dan halaman yang pernah kamu buka tetap bisa dibaca saat sinyal hilang.</p>
  </div>
</dialog>
<script>
  (function () {
    var d = document.getElementById('kreditDialog'), b = document.getElementById('kreditBtn');
    if (!d || !b || !d.showModal) return;
    b.addEventListener('click', function () { d.showModal(); });
    document.querySelectorAll('.kredit-dialog').forEach(function (dl) {
      dl.addEventListener('click', function (e) { if (e.target === dl || e.target.closest('[data-tutup]')) dl.close(); });
    });
  })();
</script>
<script>
  // Tombol menu untuk layar HP
  const tombol = document.getElementById('menuBtn'), nav = document.getElementById('nav');
  tombol.addEventListener('click', () => {
    nav.classList.toggle('open');
    document.body.classList.toggle('menu-buka', nav.classList.contains('open'));
    tombol.setAttribute('aria-expanded', nav.classList.contains('open'));
    tombol.lastChild.textContent = nav.classList.contains('open') ? 'Tutup' : 'Menu';
  });
  nav.addEventListener('click', e => { if (e.target.closest('a') && nav.classList.contains('open')) { nav.classList.remove('open'); document.body.classList.remove('menu-buka'); tombol.setAttribute('aria-expanded', 'false'); tombol.lastChild.textContent = 'Menu'; } });
</script>
<button class="asisten-btn" id="asistenBtn" aria-expanded="false" aria-controls="asistenPanel">
  <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg>
  <span>Tanya asisten</span>
</button>
<section class="asisten-panel" id="asistenPanel" hidden role="dialog" aria-labelledby="asistenJudul">
  <div class="asisten-head">
    <div><b id="asistenJudul">Asisten Desa</b><small>Pilih nomor sesuai kebutuhan Anda</small></div>
    <button type="button" id="asistenTutup" aria-label="Tutup asisten">&times;</button>
  </div>
  <p class="asisten-jejak" id="asistenJejak">Menu awal</p>
  <div class="asisten-isi" id="asistenIsi" aria-live="polite"></div>
  <div class="asisten-kaki">
    <button type="button" id="asistenKembali" disabled>&larr; Kembali</button>
    <button type="button" id="asistenAwal">Menu awal</button>
  </div>
</section>
<script src="/assets/js/akses.js?v=12s"></script>
<script src="/assets/js/asisten.js?v=12s"></script>
<script src="/assets/js/peta.js?v=12s3" defer></script>
<?php if (!empty($pakaiCuaca)):
  // Destinasi yang tetap nyaman saat hujan, dipakai saran cuaca (centang di CMS > Wisata)
  $wisataHujan = array_map(fn($w) => ['nama' => $w['nama'], 'url' => '/wisata/' . $w['slug']], q('SELECT nama, slug FROM wisata WHERE aktif = 1 AND saat_hujan = 1 ORDER BY id')); ?>
<script>window.WISATA_HUJAN = <?= json_encode($wisataHujan, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="/assets/js/cuaca.js?v=12s3"></script>
<?php endif; ?>
<?= $scriptHalaman ?? '' ?>
<?php if (function_exists('gerbang_aktif') && gerbang_aktif()): ?>
<form method="post" action="/keluar-situs" class="gerbang-keluar"><button type="submit">Mode privat aktif. Keluar</button></form>
<?php endif; ?>
</body>
</html>
