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

// ---------- Tiket Hijau: cek dan tukar kupon ----------
//   GET  /umkm?kupon=HIJAU-XXXXXX  -> cek kupon (dibuka penjual dengan memindai QR di tiket pengunjung)
//   POST aksi=tukar                -> penjual memilih usahanya, memasukkan kode tukar, kupon ditandai dipakai
$kodeKupon = strtoupper(trim((string) ($_POST['kupon'] ?? $_GET['kupon'] ?? '')));
$hasilKupon = $kodeKupon !== '' ? cek_kupon($kodeKupon) : null;
$errorTukar = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tukar') {
    $usaha = q1('SELECT id, nama_usaha, pin_hash FROM umkm WHERE id = ?', [(int) ($_POST['umkm'] ?? 0)]);
    if (!$hasilKupon || !$hasilKupon['ok']) $errorTukar = $hasilKupon['teks'] ?? 'Kupon tidak valid.';
    elseif (!$usaha || !$usaha['pin_hash'] || !password_verify((string) ($_POST['pin'] ?? ''), $usaha['pin_hash'])) {
        sleep(1); // perlambat tebakan kode tukar
        $errorTukar = 'Kode tukar salah. Kode tukar diberikan pengelola desa ke setiap UMKM.';
    } else {
        q('UPDATE reservasi SET kupon_dipakai_pada = CURRENT_TIMESTAMP, kupon_umkm = ? WHERE kupon = ? AND kupon_dipakai_pada IS NULL', [$usaha['nama_usaha'], $kodeKupon]);
        redirect('/umkm?kupon=' . urlencode($kodeKupon) . '&ditukar=1#kupon');
    }
}
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
$nilaiUmkm = [];
foreach (q('SELECT umkm_id, COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan_umkm WHERE tampil = 1 GROUP BY umkm_id') as $r) $nilaiUmkm[(int) $r['umkm_id']] = $r;
$ulasanTerbaru = [];
foreach (q('SELECT umkm_id, nama, bintang, komentar FROM ulasan_umkm WHERE tampil = 1 ORDER BY id DESC') as $r) $ulasanTerbaru[(int) $r['umkm_id']][] = $r;

$umkmPenukar = q('SELECT id, nama_usaha FROM umkm WHERE pin_hash IS NOT NULL ORDER BY nama_usaha');

$judul = 'Produk UMKM - ' . setting('nama_desa');
$menu  = 'umkm';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Produk UMKM warga</h1>
  <p>Pesan langsung ke pembuatnya lewat WhatsApp, tanpa perantara.</p>
</div>
<div class="wrap" style="padding-top:8px" id="kupon">
  <?php if ($hasilKupon): ?>
    <div class="panel kupon-cek <?= isset($_GET['ditukar']) ? 'kupon-ok' : ($hasilKupon['ok'] ? 'kupon-ok' : 'kupon-tidak') ?>" role="status">
      <h2 style="font-size:1.3rem">Kupon <?= e($kodeKupon) ?></h2>
      <?php if (isset($_GET['ditukar'])): ?>
        <p><b>Berhasil ditukar.</b> <?= e($hasilKupon['teks']) ?> Terima kasih sudah mendukung wisata hijau.</p>
      <?php else: ?>
        <p><b><?= $hasilKupon['ok'] ? 'Berlaku.' : 'Tidak bisa dipakai.' ?></b> <?= e($hasilKupon['ok'] ? setting('tiket_hijau_syarat') : $hasilKupon['teks']) ?></p>
        <?php if ($hasilKupon['r']): ?><p class="small muted">Pengunjung <?= e($hasilKupon['r']['wisata']) ?>, <?= tgl($hasilKupon['r']['tanggal']) ?>, <?= (int) $hasilKupon['r']['jumlah'] ?> orang.</p><?php endif; ?>
        <?php if ($hasilKupon['ok']): ?>
          <form method="post" action="/umkm#kupon" class="grid g3" style="gap:10px;align-items:end">
            <input type="hidden" name="aksi" value="tukar"><input type="hidden" name="kupon" value="<?= e($kodeKupon) ?>">
            <?php if ($errorTukar): ?><div class="alert alert-err" role="alert" style="grid-column:1/-1;margin:0"><?= e($errorTukar) ?></div><?php endif; ?>
            <div><label for="k-umkm">Usaha penukar</label><select id="k-umkm" name="umkm" required><?php foreach ($umkmPenukar as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['nama_usaha']) ?></option><?php endforeach; ?></select></div>
            <div><label for="k-pin">Kode tukar usaha</label><input id="k-pin" name="pin" type="password" autocomplete="off" required></div>
            <button class="btn btn-main" type="submit">Tandai kupon dipakai</button>
          </form>
          <p class="hint">Hanya penjual UMKM yang punya kode tukar. Pengunjung cukup menunjukkan QR kupon.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php elseif (setting('tiket_hijau_aktif') === '1'): ?>
    <div class="panel tiket-hijau-info">
      <b>Tiket Hijau:</b> pengunjung yang datang dengan bus, angkutan umum, kereta, sepeda, atau berbagi kendaraan mendapat kupon belanja <?= rupiah((int) setting('tiket_hijau_nilai')) ?> di UMKM bertanda <b>Terima Tiket Hijau</b>. <a href="/reservasi">Pesan kunjungan</a>
    </div>
  <?php endif; ?>
</div>
<div class="wrap sec" style="padding-top:16px">
  <?php if (($_GET['ulasan'] ?? '') === 'terkirim'): ?><div class="alert alert-ok" role="status">Terima kasih. Ulasanmu akan tampil setelah diperiksa petugas desa.</div><?php endif; ?>
  <nav class="chips" aria-label="Kategori produk">
    <?php foreach (array_merge(['Semua'], $daftarKategori) as $k): ?>
      <a class="chip <?= $k === $kategoriAktif ? 'on' : '' ?>" href="/umkm<?= $k === 'Semua' ? '' : '?kategori=' . urlencode($k) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="grid g3">
    <?php foreach ($umkm as $u):
      $pesan = 'Halo ' . $u['nama_usaha'] . ', saya lihat di website ' . setting('nama_desa') . '. Saya ingin pesan ' . $u['produk'] . '.'; ?>
      <div class="card" id="umkm-<?= (int) $u['id'] ?>">
        <div class="art"><img src="<?= e($u['foto'] ?: '/assets/img/kirab.svg') ?>" alt="<?= e($u['produk']) ?>" loading="lazy"></div>
        <div class="card-body">
          <span class="tag"><?= e($u['kategori']) ?></span><?php if (!empty($u['pin_hash']) && setting('tiket_hijau_aktif') === '1'): ?> <span class="tag tag-hijau" title="Menerima kupon Tiket Hijau">Terima Tiket Hijau</span><?php endif; ?>
          <h3 style="margin:4px 0 0"><?= e($u['produk']) ?></h3>
          <p style="margin-bottom:6px"><?= e($u['nama_usaha']) ?>, <?= e($u['pemilik']) ?></p>
          <?php $nu = $nilaiUmkm[(int) $u['id']] ?? null; ?>
          <p class="small" style="margin:0 0 8px"><?php if ($nu): ?><span class="bintang" aria-hidden="true"><?= bintang((int) round($nu['rata'])) ?></span> <b><?= str_replace('.', ',', (string) round((float) $nu['rata'], 1)) ?></b> <span class="muted">dari <?= (int) $nu['n'] ?> ulasan</span><span class="sr-only">Nilai <?= round((float) $nu['rata'], 1) ?> dari 5</span><?php else: ?><span class="muted">Belum ada ulasan</span><?php endif; ?></p>
          <?php if (!empty($u['deskripsi'])): ?><p class="small"><?= e($u['deskripsi']) ?></p><?php endif; ?>
          <?php foreach (array_slice($ulasanTerbaru[(int) $u['id']] ?? [], 0, 2) as $ul): ?>
            <blockquote class="ulasan-mini"><span class="bintang" aria-label="<?= (int) $ul['bintang'] ?> bintang"><?= bintang((int) $ul['bintang']) ?></span> <?= e($ul['komentar']) ?> <cite><?= e($ul['nama']) ?></cite></blockquote>
          <?php endforeach; ?>
          <div class="card-foot">
            <span class="price"><?= rupiah($u['harga']) ?> <span class="small muted">/ <?= e($u['satuan']) ?></span></span>
            <a class="btn btn-wa btn-sm" href="<?= e(wa_link($u['wa'], $pesan)) ?>" target="_blank" rel="noopener">Pesan via WhatsApp</a>
          </div>
          <details class="beri-ulasan" <?= $ulasanUntuk === (int) $u['id'] && $errorUlasan ? 'open' : '' ?>>
            <summary>Sudah pernah beli? Beri ulasan</summary>
            <form method="post" action="/umkm#umkm-<?= (int) $u['id'] ?>">
              <input type="hidden" name="aksi" value="ulasan"><input type="hidden" name="umkm" value="<?= (int) $u['id'] ?>">
              <?php if ($ulasanUntuk === (int) $u['id'] && $errorUlasan): ?><div class="alert alert-err" role="alert"><?= implode('<br>', array_map('e', $errorUlasan)) ?></div><?php endif; ?>
              <div class="field"><label for="un-<?= (int) $u['id'] ?>">Nama panggilan</label><input id="un-<?= (int) $u['id'] ?>" name="nama" maxlength="60" required></div>
              <fieldset class="pilih-bintang"><legend>Bintang</legend>
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
<?php require __DIR__ . '/../inc/footer.php'; ?>
