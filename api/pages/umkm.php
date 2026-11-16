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
  <nav class="chips" aria-label="Kategori produk">
    <?php foreach (array_merge(['Semua'], $daftarKategori) as $k): ?>
      <a class="chip <?= $k === $kategoriAktif ? 'on' : '' ?>" href="/umkm<?= $k === 'Semua' ? '' : '?kategori=' . urlencode($k) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="grid g3">
    <?php foreach ($umkm as $u):
      $pesan = 'Halo ' . $u['nama_usaha'] . ', saya lihat di website ' . setting('nama_desa') . '. Saya ingin pesan ' . $u['produk'] . '.'; ?>
      <div class="card">
        <div class="art"><img src="<?= e($u['foto'] ?: '/assets/img/kirab.svg') ?>" alt="<?= e($u['produk']) ?>" loading="lazy"></div>
        <div class="card-body">
          <span class="tag"><?= e($u['kategori']) ?></span><?php if (!empty($u['pin_hash']) && setting('tiket_hijau_aktif') === '1'): ?> <span class="tag tag-hijau" title="Menerima kupon Tiket Hijau">Terima Tiket Hijau</span><?php endif; ?>
          <h3 style="margin:4px 0 0"><?= e($u['produk']) ?></h3>
          <p><?= e($u['nama_usaha']) ?>, <?= e($u['pemilik']) ?></p>
          <div class="card-foot">
            <span class="price"><?= rupiah($u['harga']) ?> <span class="small muted">/ <?= e($u['satuan']) ?></span></span>
            <a class="btn btn-wa btn-sm" href="<?= e(wa_link($u['wa'], $pesan)) ?>" target="_blank" rel="noopener">Pesan via WhatsApp</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$umkm): ?><p class="muted">Belum ada produk di kategori ini.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
