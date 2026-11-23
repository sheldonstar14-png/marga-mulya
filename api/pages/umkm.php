<?php
// =========================================================
// HALAMAN UMKM. Filter kategori lewat alamat: /umkm?kategori=Makanan
// =========================================================
$kategoriAktif = $_GET['kategori'] ?? 'Semua';
$daftarKategori = array_column(q('SELECT DISTINCT kategori FROM umkm ORDER BY kategori'), 'kategori');
if ($kategoriAktif !== 'Semua' && !in_array($kategoriAktif, $daftarKategori, true)) {
    $kategoriAktif = 'Semua';
}
$umkm = $kategoriAktif === 'Semua'
    ? q('SELECT * FROM umkm ORDER BY id')
    : q('SELECT * FROM umkm WHERE kategori = ? ORDER BY id', [$kategoriAktif]);

// ---------- Ulasan produk UMKM ----------
// Siapa pun boleh menulis ulasan, tapi baru tampil setelah disetujui petugas (CMS > Ulasan UMKM).
$errorUlasan = []; $ulasanUntuk = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ulasan') {
    $ulasanUntuk = (int) ($_POST['umkm'] ?? 0);
    $u = q1('SELECT id, nama_usaha FROM umkm WHERE id = ?', [$ulasanUntuk]);
    $namaU = mb_substr(trim((string) ($_POST['nama'] ?? '')), 0, 60);
    $bintangU = (int) ($_POST['bintang'] ?? 0);
    $komentarU = trim((string) ($_POST['komentar'] ?? ''));
    if (!empty($_POST['website']) || !$u) $errorUlasan[] = 'Ulasan tidak dapat diproses.';
    if (mb_strlen($namaU) < 2) $errorUlasan[] = 'Isi nama (boleh nama panggilan).';
    if ($bintangU < 1 || $bintangU > 5) $errorUlasan[] = 'Pilih jumlah bintang.';
    if (mb_strlen($komentarU) < 10 || mb_strlen($komentarU) > 500) $errorUlasan[] = 'Tulis ulasan 10 sampai 500 huruf.';
    if (preg_match('/\d{9,}/', preg_replace('/[\s.-]/', '', $komentarU))) $errorUlasan[] = 'Jangan menulis nomor HP atau nomor identitas di ulasan.';
    if (!$errorUlasan) {
        q('INSERT INTO ulasan_umkm (umkm_id, usaha, nama, bintang, komentar) VALUES (?, ?, ?, ?, ?)', [$u['id'], $u['nama_usaha'], $namaU, $bintangU, $komentarU]);
        redirect('/umkm?ulasan=terkirim#umkm-' . $u['id']);
    }
}
// ---------- Pengajuan produk baru oleh warga ----------
// Warga mengisi form, petugas meninjau di CMS > Pengajuan produk, lalu menerbitkannya ke daftar UMKM.
$errorAju = [];
$isiAju = ['nama_usaha' => '', 'kategori' => KATEGORI_UMKM[0], 'produk' => '', 'harga' => '', 'satuan' => '', 'pemilik' => '', 'wa' => '', 'deskripsi' => '', 'izin' => ''];
$PILIHAN_KIRIM = ['Ambil di rumah penjual', 'Antar sekitar desa', 'Kirim paket ke luar desa', 'Titip di warung pantai'];
$kirimAju = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ajukan') {
    foreach ($isiAju as $k => $_) $isiAju[$k] = trim((string) ($_POST[$k] ?? ''));
    $waAju = nomor_wa($isiAju['wa']);
    if (!empty($_POST['website'])) $errorAju[] = 'Pengajuan ditolak.';
    if (mb_strlen($isiAju['nama_usaha']) < 3 || mb_strlen($isiAju['nama_usaha']) > 150) $errorAju[] = 'Isi nama usaha (3 sampai 150 huruf).';
    if (!in_array($isiAju['kategori'], KATEGORI_UMKM, true)) $errorAju[] = 'Pilih kategori produk.';
    if (mb_strlen($isiAju['produk']) < 3 || mb_strlen($isiAju['produk']) > 150) $errorAju[] = 'Isi nama produk (3 sampai 150 huruf).';
    if (!ctype_digit($isiAju['harga']) || (int) $isiAju['harga'] < 500 || (int) $isiAju['harga'] > 50000000) $errorAju[] = 'Harga diisi angka saja, contoh 15000.';
    if ($isiAju['satuan'] === '' || mb_strlen($isiAju['satuan']) > 40) $errorAju[] = 'Isi satuan, contoh 250 g atau per bungkus.';
    if (mb_strlen($isiAju['pemilik']) < 3 || mb_strlen($isiAju['pemilik']) > 150) $errorAju[] = 'Isi nama pemilik dan RT.';
    if (!preg_match('/^628\d{7,11}$/', $waAju)) $errorAju[] = 'Nomor WhatsApp belum benar. Contoh: 081234567890.';
    if (mb_strlen($isiAju['deskripsi']) > 600) $errorAju[] = 'Cerita produk maksimal 600 huruf.';
    if (mb_strlen($isiAju['izin']) > 150) $errorAju[] = 'Keterangan izin usaha maksimal 150 huruf.';
    $kirimAju = array_values(array_intersect($PILIHAN_KIRIM, (array) ($_POST['kirim'] ?? [])));
    if (!$kirimAju) $errorAju[] = 'Pilih minimal satu cara pembeli menerima produk.';
    if (empty($_POST['setuju_data'])) $errorAju[] = 'Centang persetujuan supaya nomor WhatsApp bisa ditampilkan untuk pembeli.';
    [$fotoAju, $errFoto] = siapkan_foto_aspirasi($_FILES['foto'] ?? []);
    $errorAju = array_merge($errorAju, $errFoto);
    if (!$errorAju) {
        // Sampai 3 foto (diperkecil dan dibersihkan dari data lokasi), baru tampil setelah diterbitkan petugas
        $idFoto = array_map(fn($f) => simpan_foto_unggah($f), $fotoAju);
        $kodeAju = kode_unik('PRD', 'pengajuan_umkm');
        q('INSERT INTO pengajuan_umkm (kode, nama_usaha, kategori, produk, harga, satuan, pemilik, wa, deskripsi, foto_id, foto_ids, izin, pengiriman) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$kodeAju, $isiAju['nama_usaha'], $isiAju['kategori'], $isiAju['produk'], (int) $isiAju['harga'], $isiAju['satuan'], $isiAju['pemilik'], $waAju, $isiAju['deskripsi'], $idFoto[0] ?? null, implode(',', $idFoto), $isiAju['izin'], implode(', ', $kirimAju)]);
        redirect('/umkm?cek=' . urlencode($kodeAju) . '&baru=1#ajukan');
    }
}
$kodeCekAju = strtoupper(trim((string) ($_GET['cek'] ?? '')));
$hasilAju = null;
if ($kodeCekAju !== '') { try { $hasilAju = q1('SELECT kode, nama_usaha, produk, status, catatan, umkm_id, dibuat_pada FROM pengajuan_umkm WHERE kode = ?', [$kodeCekAju]); } catch (Throwable $e) {} }

$nilaiUmkm = [];
foreach (q('SELECT umkm_id, COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan_umkm WHERE tampil = 1 GROUP BY umkm_id') as $r) $nilaiUmkm[(int) $r['umkm_id']] = $r;
$ulasanTerbaru = [];
foreach (q('SELECT umkm_id, nama, bintang, komentar FROM ulasan_umkm WHERE tampil = 1 ORDER BY id DESC') as $r) $ulasanTerbaru[(int) $r['umkm_id']][] = $r;


$judul = 'Produk UMKM - ' . setting('nama_desa');
$menu  = 'umkm';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title page-title-aksi">
  <div>
    <h1>Produk UMKM warga</h1>
    <p>Olahan hasil laut, kelapa, dan kerajinan buatan warga. Pesan langsung ke pembuatnya lewat WhatsApp, tanpa perantara.</p>
  </div>
  <a class="btn btn-dark" href="#ajukan"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Ajukan produk</a>
</div>
<div class="wrap"><div class="info-lapak"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 10h16l-1-5H5zM5 10v9h14v-9M9 19v-5h6v5"/></svg><p>Setiap produk ditinjau petugas desa sebelum tampil. Ketersediaan dan pembayaran disepakati langsung dengan penjual.</p><a href="#cek-ajuan">Cek pengajuan</a></div></div>
<div class="wrap sec" style="padding-top:16px">
  <?php if (($_GET['ulasan'] ?? '') === 'terkirim'): ?><div class="alert alert-ok" role="status">Terima kasih. Ulasanmu akan tampil setelah diperiksa petugas desa.</div><?php endif; ?>
  <nav class="chips" aria-label="Kategori produk">
    <?php foreach (array_merge(['Semua'], $daftarKategori) as $k): ?>
      <a class="chip <?= $k === $kategoriAktif ? 'on' : '' ?>" href="/umkm<?= $k === 'Semua' ? '' : '?kategori=' . urlencode($k) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="grid-produk">
    <?php foreach ($umkm as $u):
      $pesan = 'Halo ' . $u['nama_usaha'] . ', saya lihat di website ' . setting('nama_desa') . '. Saya ingin pesan ' . $u['produk'] . ' (' . rupiah((int) $u['harga']) . ' / ' . $u['satuan'] . '). Apakah masih tersedia?'; ?>
      <div class="card" id="umkm-<?= (int) $u['id'] ?>">
        <?php [$usahaU, $ilusU] = pisah_ilustrasi($u['nama_usaha']); $fotoLain = array_filter(explode(',', (string) ($u['foto_lain'] ?? ''))); ?>
        <div class="art" style="position:relative"><img src="<?= e($u['foto'] ?: '/assets/img/ikan.svg') ?>" alt="<?= e($u['produk']) ?>" loading="lazy"><?= tanda_ilustrasi($ilusU) ?></div>
        <?php if ($fotoLain): ?><div class="foto-lain"><?php foreach (array_merge([$u['foto']], $fotoLain) as $fl): ?><button type="button" data-ganti-foto="<?= e($fl) ?>" aria-label="Lihat foto lain <?= e($u['produk']) ?>"><img src="<?= e($fl) ?>" alt="" loading="lazy" width="56" height="42"></button><?php endforeach; ?></div><?php endif; ?>
        <div class="card-body">
          <span class="tag"><?= e($u['kategori']) ?></span>
          <h3 style="margin:4px 0 0"><?= e($u['produk']) ?></h3>
          <p class="umkm-pemilik"><?= e($usahaU) ?>, <?= e(pisah_ilustrasi($u['pemilik'])[0]) ?></p>
          <?php $nu = $nilaiUmkm[(int) $u['id']] ?? null; ?>
          <p class="small" style="margin:0 0 8px"><?php if ($nu): ?><span class="bintang" aria-hidden="true"><?= bintang((int) round($nu['rata'])) ?></span> <b><?= str_replace('.', ',', (string) round((float) $nu['rata'], 1)) ?></b> <span class="muted">dari <?= (int) $nu['n'] ?> ulasan</span><span class="sr-only">Nilai <?= round((float) $nu['rata'], 1) ?> dari 5</span><?php else: ?><span class="muted">Belum ada ulasan</span><?php endif; ?></p>
          <?php if (!empty($u['deskripsi'])): ?><p class="small"><?= e($u['deskripsi']) ?></p><?php endif; ?>
          <?php if (!empty($u['izin']) || !empty($u['pengiriman'])): ?>
            <ul class="umkm-info">
              <?php if (!empty($u['izin'])): ?><li><b>Izin:</b> <?= e($u['izin']) ?></li><?php endif; ?>
              <?php if (!empty($u['pengiriman'])): ?><li><b>Pengiriman:</b> <?= e($u['pengiriman']) ?></li><?php endif; ?>
            </ul>
          <?php endif; ?>
          <?php foreach (array_slice($ulasanTerbaru[(int) $u['id']] ?? [], 0, 2) as $ul): ?>
            <blockquote class="ulasan-mini"><span class="bintang" aria-label="<?= (int) $ul['bintang'] ?> bintang"><?= bintang((int) $ul['bintang']) ?></span> <?= e($ul['komentar']) ?> <cite><?= e($ul['nama']) ?></cite></blockquote>
          <?php endforeach; ?>
          <div class="card-foot">
            <span class="price"><?= rupiah($u['harga']) ?> <span class="small muted">/ <?= e($u['satuan']) ?></span></span>
          </div>
          <a class="btn btn-wa btn-penuh" href="<?= e(wa_link($u['wa'], $pesan)) ?>" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3z"/></svg>Pesan lewat WhatsApp<span class="sr-only"> ke <?= e($u['nama_usaha']) ?></span></a>
          <details class="beri-ulasan" <?= $ulasanUntuk === (int) $u['id'] && $errorUlasan ? 'open' : '' ?>>
            <summary>Sudah pernah beli? Beri ulasan</summary>
            <form method="post" action="/umkm#umkm-<?= (int) $u['id'] ?>">
              <input type="hidden" name="aksi" value="ulasan"><input type="hidden" name="umkm" value="<?= (int) $u['id'] ?>">
              <?php if ($ulasanUntuk === (int) $u['id'] && $errorUlasan): ?><div class="alert alert-err" role="alert"><?= implode('<br>', array_map('e', $errorUlasan)) ?></div><?php endif; ?>
              <div class="field"><label for="un-<?= (int) $u['id'] ?>">Nama panggilan</label><input id="un-<?= (int) $u['id'] ?>" name="nama" maxlength="60" required></div>
              <fieldset class="pilihan-pil"><legend>Bintang</legend>
                <?php for ($b = 5; $b >= 1; $b--): ?><label><input type="radio" name="bintang" value="<?= $b ?>" <?= $b === 5 ? 'checked' : '' ?>> <?= $b ?></label><?php endfor; ?>
              </fieldset>
              <div class="field"><label for="uk-<?= (int) $u['id'] ?>">Ulasan</label><textarea id="uk-<?= (int) $u['id'] ?>" name="komentar" rows="3" maxlength="500" required placeholder="Rasa, kualitas, atau pelayanannya bagaimana?"></textarea></div>
              <div class="hp" aria-hidden="true"><label>Jangan diisi<input name="website" tabindex="-1" autocomplete="off"></label></div>
              <button class="btn btn-dark btn-sm" type="submit">Kirim ulasan</button>
              <p class="hint">Ulasan diperiksa petugas sebelum tampil.</p>
            </form>
          </details>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$umkm): ?><p class="muted">Belum ada produk di kategori ini.</p><?php endif; ?>
</div>
<section class="wrap ajukan" id="ajukan" aria-labelledby="judul-ajukan">
  <div class="ajukan-kiri">
    <h2 id="judul-ajukan">Punya produk? Ajukan di sini</h2>
    <p>Nelayan, pengolah ikan, pengrajin batok, atau pembuat kue rumahan di Marga Mulya bisa menitipkan produknya di website desa, gratis.</p>
    <ol class="alur-langkah alur-aju">
      <li><b>Siapkan bahan</b><span>Nama produk, harga, satuan, nomor WhatsApp, dan 1 sampai 3 foto yang terang.</span></li>
      <li><b>Isi form di samping</b><span>Kamu langsung mendapat kode PRD- untuk mengecek status.</span></li>
      <li><b>Ditinjau petugas</b><span>Biasanya 1 sampai 2 hari kerja. Kalau ada yang kurang, catatannya muncul di Cek pengajuan.</span></li>
      <li><b>Tampil di website</b><span>Pesanan masuk langsung ke WhatsApp-mu. Gratis, tanpa potongan.</span></li>
    </ol>
    <details class="tips-aju"><summary>Tips foto produk yang menarik</summary>
      <ul class="small"><li>Foto di dekat jendela pada siang hari, tanpa lampu kilat.</li><li>Pakai alas polos, misalnya kain atau papan.</li><li>Foto pertama: produk utuh dengan kemasan. Foto kedua dan ketiga: isi, ukuran, atau saat dipakai.</li><li>Bersihkan lensa kamera HP dan jangan memotret wajah orang lain tanpa izin.</li></ul>
    </details>
    <div class="panel" id="cek-ajuan">
      <h3 style="margin:0 0 8px">Cek pengajuan</h3>
      <form method="get" action="/umkm#cek-ajuan" class="grid" style="grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end">
        <div><label for="cek-kode">Kode pengajuan</label><input id="cek-kode" name="cek" value="<?= e($kodeCekAju) ?>" placeholder="Contoh: PRD-4TQ8MA"></div>
        <button class="btn btn-dark" type="submit">Cek</button>
      </form>
      <?php if ($kodeCekAju !== ''): ?>
        <?php if ($hasilAju): $tahapAju = $hasilAju['status'] === 'Ditolak' ? ['Diterima', 'Ditolak'] : ['Diterima', 'Ditinjau', 'Diterbitkan'];
          $posAju = ['Menunggu' => 0, 'Ditinjau' => 1, 'Diterbitkan' => 2, 'Ditolak' => 1][$hasilAju['status']] ?? 0; ?>
          <div class="hasil-adu" role="status" style="margin-top:14px">
            <?php if (isset($_GET['baru'])): ?><div class="alert alert-ok">Pengajuan terkirim. Simpan kode <b><?= e($hasilAju['kode']) ?></b> untuk mengecek statusnya.</div><?php endif; ?>
            <p style="margin:0"><b><?= e($hasilAju['produk']) ?></b>, <?= e($hasilAju['nama_usaha']) ?></p>
            <ol class="steps steps-kecil"><?php foreach ($tahapAju as $i => $tp): ?><li class="<?= $i <= $posAju ? 'done' : '' ?> <?= $tp === 'Ditolak' ? 'bad' : '' ?>"><?= $tp ?></li><?php endforeach; ?></ol>
            <?php if ($hasilAju['catatan']): ?><div class="tanggapan"><b>Catatan petugas</b><p><?= nl2br(e($hasilAju['catatan'])) ?></p></div><?php endif; ?>
            <?php if ($hasilAju['status'] === 'Diterbitkan' && $hasilAju['umkm_id']): ?><p class="small" style="margin:10px 0 0"><a href="/umkm#umkm-<?= (int) $hasilAju['umkm_id'] ?>">Lihat produkmu di halaman UMKM</a></p><?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-err" role="alert" style="margin-top:14px">Kode <?= e($kodeCekAju) ?> tidak ditemukan. Kode diawali PRD-.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <form class="panel ajukan-form" method="post" action="/umkm#ajukan" enctype="multipart/form-data">
    <input type="hidden" name="aksi" value="ajukan">
    <?php if ($errorAju): ?><div class="alert alert-err" role="alert"><b>Pengajuan belum terkirim:</b><ul><?php foreach ($errorAju as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <div class="grid g2" style="gap:12px">
      <div class="field"><label for="aj-usaha">Nama usaha</label><input id="aj-usaha" name="nama_usaha" value="<?= e($isiAju['nama_usaha']) ?>" maxlength="150" required placeholder="Contoh: Ikan Asin Bu Romlah"></div>
      <div class="field"><label for="aj-kat">Kategori</label><select id="aj-kat" name="kategori"><?php foreach (KATEGORI_UMKM as $k): ?><option <?= $isiAju['kategori'] === $k ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="field"><label for="aj-produk">Nama produk</label><input id="aj-produk" name="produk" value="<?= e($isiAju['produk']) ?>" maxlength="150" required placeholder="Contoh: Ikan asin teri jengki"></div>
    <div class="grid g2" style="gap:12px">
      <div class="field"><label for="aj-harga">Harga (Rp)</label><input id="aj-harga" name="harga" value="<?= e($isiAju['harga']) ?>" inputmode="numeric" pattern="[0-9]*" required placeholder="25000"></div>
      <div class="field"><label for="aj-satuan">Per satuan</label><input id="aj-satuan" name="satuan" value="<?= e($isiAju['satuan']) ?>" maxlength="40" required placeholder="250 g, bungkus, buah"></div>
    </div>
    <div class="grid g2" style="gap:12px">
      <div class="field"><label for="aj-pemilik">Pemilik dan RT</label><input id="aj-pemilik" name="pemilik" value="<?= e($isiAju['pemilik']) ?>" maxlength="150" required placeholder="Bu Romlah, RT 02"></div>
      <div class="field"><label for="aj-wa">WhatsApp untuk pesanan</label><input id="aj-wa" name="wa" value="<?= e($isiAju['wa']) ?>" inputmode="tel" required placeholder="08xxxxxxxxxx"></div>
    </div>
    <div class="field"><label for="aj-desk">Cerita singkat produk (opsional)</label><textarea id="aj-desk" name="deskripsi" rows="3" maxlength="600" placeholder="Bahan dari mana, cara membuat, keunggulannya"><?= e($isiAju['deskripsi']) ?></textarea></div>
    <div class="field"><label for="aj-izin">Izin usaha (opsional)</label><input id="aj-izin" name="izin" value="<?= e($isiAju['izin']) ?>" maxlength="150" placeholder="Contoh: PIRT 1234567890123, sertifikat halal, atau belum ada"></div>
    <fieldset class="field pilih-kirim"><legend>Cara pembeli menerima produk</legend>
      <?php foreach ($PILIHAN_KIRIM as $pk): ?><label class="cek"><input type="checkbox" name="kirim[]" value="<?= e($pk) ?>" <?= in_array($pk, $kirimAju, true) || (!$kirimAju && $pk === $PILIHAN_KIRIM[0]) ? 'checked' : '' ?>> <?= e($pk) ?></label><?php endforeach; ?>
    </fieldset>
    <div class="field foto-lapor"><label for="a-foto">Foto produk (opsional, sampai 3 foto)</label><input id="a-foto" name="foto[]" type="file" accept="image/jpeg,image/png,image/webp" multiple><div class="hint">Foto pertama menjadi foto utama. Foto diperkecil otomatis dan data lokasi di dalamnya dihapus.</div><div class="foto-pratinjau" id="fotoPratinjau" aria-live="polite"></div></div>
    <label class="cek-setuju"><input type="checkbox" name="setuju_data" value="1" required> <span>Saya pemilik usaha ini dan setuju nama usaha serta nomor WhatsApp ditampilkan untuk pembeli setelah diterbitkan.</span></label>
    <div class="hp" aria-hidden="true"><label>Jangan diisi<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <button class="btn btn-main" type="submit">Kirim pengajuan</button>
  </form>
</section>
<?php
$scriptHalaman = '<script src="/assets/js/foto-lapor.js" defer></script>' . ($errorAju ? '<script>document.addEventListener("DOMContentLoaded",function(){var a=document.getElementById("ajukan");a&&a.scrollIntoView()})</script>' : '')
  . '<script>document.querySelectorAll("[data-ganti-foto]").forEach(function(b){b.addEventListener("click",function(){var k=b.closest(".card").querySelector(".art img");k.src=b.dataset.gantiFoto;})});</script>';
require __DIR__ . '/../inc/footer.php';
?>
