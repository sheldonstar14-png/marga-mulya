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
// ---------- Keranjang UMKM ----------
// Isi keranjang disimpan di browser. Saat dikirim, pesanan dicatat (satu pesanan per penjual),
// lalu pembeli menekan tombol untuk mengirim ringkasan yang sama ke WhatsApp penjual.
$errorPesan = [];
$isiPesan = ['nama' => '', 'wa' => '', 'pengambilan' => 'Ambil sendiri', 'alamat' => '', 'catatan' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'pesan') {
    foreach ($isiPesan as $k => $_) $isiPesan[$k] = trim((string) ($_POST[$k] ?? ''));
    $waPembeli = preg_replace('/\D/', '', $isiPesan['wa']);
    $keranjang = json_decode((string) ($_POST['keranjang'] ?? ''), true);
    $item = [];
    if (is_array($keranjang)) {
        foreach (array_slice($keranjang, 0, 30, true) as $id => $jml) {
            $jml = (int) $jml;
            $u = q1('SELECT id, nama_usaha, produk, harga, satuan, wa FROM umkm WHERE id = ?', [(int) $id]);
            if ($u && $jml >= 1 && $jml <= 99) $item[] = $u + ['jumlah' => $jml];
        }
    }
    if (!empty($_POST['website'])) $errorPesan[] = 'Pesanan tidak dapat diproses.';
    if (!$item) $errorPesan[] = 'Keranjang masih kosong.';
    if (mb_strlen($isiPesan['nama']) < 2 || mb_strlen($isiPesan['nama']) > 100) $errorPesan[] = 'Isi nama pemesan.';
    if (strlen($waPembeli) < 10 || strlen($waPembeli) > 15) $errorPesan[] = 'Isi nomor WhatsApp 10 sampai 15 digit supaya penjual bisa membalas.';
    if (!in_array($isiPesan['pengambilan'], ['Ambil sendiri', 'Diantar'], true)) $isiPesan['pengambilan'] = 'Ambil sendiri';
    if ($isiPesan['pengambilan'] === 'Diantar' && mb_strlen($isiPesan['alamat']) < 5) $errorPesan[] = 'Tulis alamat pengantaran.';
    if (mb_strlen($isiPesan['alamat']) > 255 || mb_strlen($isiPesan['catatan']) > 255) $errorPesan[] = 'Alamat dan catatan maksimal 255 huruf.';
    if (empty($_POST['setuju_data'])) $errorPesan[] = 'Centang persetujuan penggunaan data.';
    if (!$errorPesan) {
        $perPenjual = [];
        foreach ($item as $it) $perPenjual[$it['wa'] . '|' . $it['nama_usaha']][] = $it;
        $kode = [];
        foreach ($perPenjual as $daftar) {
            $k = kode_unik('UMK', 'pesanan_umkm');
            $total = array_sum(array_map(fn($it) => $it['harga'] * $it['jumlah'], $daftar));
            $ringkas = mb_substr(implode(', ', array_map(fn($it) => $it['produk'] . ' x' . $it['jumlah'], $daftar)), 0, 600);
            q('INSERT INTO pesanan_umkm (kode, usaha, wa_penjual, nama, wa, pengambilan, alamat, catatan, ringkasan, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$k, $daftar[0]['nama_usaha'], $daftar[0]['wa'], $isiPesan['nama'], $waPembeli, $isiPesan['pengambilan'], $isiPesan['alamat'], $isiPesan['catatan'], $ringkas, $total]);
            $pid = (int) q1('SELECT id FROM pesanan_umkm WHERE kode = ?', [$k])['id'];
            foreach ($daftar as $it) q('INSERT INTO pesanan_umkm_item (pesanan_id, umkm_id, produk, harga, satuan, jumlah) VALUES (?, ?, ?, ?, ?, ?)', [$pid, $it['id'], $it['produk'], $it['harga'], $it['satuan'], $it['jumlah']]);
            $kode[] = $k;
        }
        // Hanya browser pembeli ini yang bisa membuka ringkasan pesanannya (berisi nama dan nomor WA)
        setcookie('mm_pesan', implode(',', $kode), ['expires' => time() + 86400, 'path' => '/umkm', 'samesite' => 'Lax', 'httponly' => true, 'secure' => !empty($_SERVER['HTTPS'])]);
        redirect('/umkm?pesanan=' . urlencode(implode(',', $kode)) . '#pesanan');
    }
}
$pesananBaru = [];
if (isset($_GET['pesanan'])) {
    $milik = explode(',', (string) ($_COOKIE['mm_pesan'] ?? ''));
    foreach (array_slice(explode(',', (string) $_GET['pesanan']), 0, 10) as $k) {
        if (!in_array($k, $milik, true)) continue;
        $ps = q1('SELECT * FROM pesanan_umkm WHERE kode = ?', [$k]);
        if (!$ps) continue;
        $ps['item'] = q('SELECT produk, harga, satuan, jumlah FROM pesanan_umkm_item WHERE pesanan_id = ? ORDER BY id', [$ps['id']]);
        $teks = "Halo {$ps['usaha']}, saya pesan lewat website " . setting('nama_desa') . ".\nKode pesanan: {$ps['kode']}\n\n";
        foreach ($ps['item'] as $it) $teks .= "- {$it['produk']} x{$it['jumlah']} = " . rupiah($it['harga'] * $it['jumlah']) . "\n";
        $teks .= "\nTotal: " . rupiah((int) $ps['total']) . "\nNama: {$ps['nama']}\n" . ($ps['pengambilan'] === 'Diantar' ? "Diantar ke: {$ps['alamat']}" : 'Ambil sendiri') . ($ps['catatan'] ? "\nCatatan: {$ps['catatan']}" : '') . "\n\nMohon konfirmasi ketersediaan dan cara pembayarannya. Terima kasih.";
        $ps['wa_link'] = wa_link($ps['wa_penjual'], $teks);
        $pesananBaru[] = $ps;
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
  <?php if ($pesananBaru): ?>
    <div class="panel pesanan-ok" id="pesanan" role="status">
      <h2 style="font-size:1.4rem;margin:0 0 6px">Pesanan tercatat. Satu langkah lagi</h2>
      <p style="margin:0 0 14px">Tekan tombol hijau untuk mengirim pesanan ke WhatsApp penjual. Pesanannya sudah tersusun, kamu tinggal menekan kirim. Pembayaran langsung ke penjual.</p>
      <?php foreach ($pesananBaru as $ps): ?>
        <div class="pesanan-kartu">
          <div><b><?= e($ps['usaha']) ?></b> <span class="tag"><?= e($ps['kode']) ?></span>
            <ul class="small"><?php foreach ($ps['item'] as $it): ?><li><?= e($it['produk']) ?> x<?= (int) $it['jumlah'] ?> = <?= rupiah($it['harga'] * $it['jumlah']) ?></li><?php endforeach; ?></ul>
            <b>Total <?= rupiah((int) $ps['total']) ?></b></div>
          <a class="btn btn-wa" href="<?= e($ps['wa_link']) ?>" target="_blank" rel="noopener" data-kirim-wa>Kirim ke WhatsApp penjual</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if (($_GET['ulasan'] ?? '') === 'terkirim'): ?><div class="alert alert-ok" role="status">Terima kasih. Ulasanmu akan tampil setelah diperiksa petugas desa.</div><?php endif; ?>
  <nav class="chips" aria-label="Kategori produk">
    <?php foreach (array_merge(['Semua'], $daftarKategori) as $k): ?>
      <a class="chip <?= $k === $kategoriAktif ? 'on' : '' ?>" href="/umkm<?= $k === 'Semua' ? '' : '?kategori=' . urlencode($k) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="grid g3">
    <?php foreach ($umkm as $u):
      $pesan = 'Halo ' . $u['nama_usaha'] . ', saya lihat di website ' . setting('nama_desa') . '. Saya ingin pesan ' . $u['produk'] . '.'; ?>
      <div class="card" id="umkm-<?= (int) $u['id'] ?>" data-produk='<?= e(json_encode(['id' => (int) $u['id'], 'produk' => $u['produk'], 'usaha' => $u['nama_usaha'], 'harga' => (int) $u['harga'], 'satuan' => $u['satuan']], JSON_UNESCAPED_UNICODE)) ?>'>
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
            <button type="button" class="btn btn-main btn-sm" data-tambah="<?= (int) $u['id'] ?>">Tambah ke keranjang</button>
          </div>
          <a class="small tanya-wa" href="<?= e(wa_link($u['wa'], $pesan)) ?>" target="_blank" rel="noopener">Tanya dulu lewat WhatsApp<span class="sr-only"> ke <?= e($u['nama_usaha']) ?></span></a>
          <div>
          </div>
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
<!-- Keranjang -->
<button type="button" class="keranjang-bar" id="keranjangBar" hidden aria-haspopup="dialog">
  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M4 7h16l-1.5 13h-13z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
  <span id="keranjangRingkas">Keranjang</span><b>Lihat</b>
</button>
<dialog class="keranjang-dialog" id="keranjangDialog" aria-labelledby="judulKeranjang">
  <form method="post" action="/umkm#pesanan" class="keranjang-isi" id="formPesan">
    <div class="head-row" style="margin:0"><h2 id="judulKeranjang" style="font-size:1.4rem;margin:0">Keranjang</h2><button type="button" class="btn btn-ghost btn-sm" data-tutup>Tutup</button></div>
    <div id="daftarKeranjang"></div>
    <input type="hidden" name="aksi" value="pesan"><input type="hidden" name="keranjang" id="inputKeranjang">
    <?php if ($errorPesan): ?><div class="alert alert-err" role="alert"><?= implode('<br>', array_map('e', $errorPesan)) ?></div><?php endif; ?>
    <div class="grid g2" style="gap:12px">
      <div class="field"><label for="p-nama">Nama pemesan</label><input id="p-nama" name="nama" value="<?= e($isiPesan['nama']) ?>" maxlength="100" required autocomplete="name"></div>
      <div class="field"><label for="p-wa">WhatsApp</label><input id="p-wa" name="wa" value="<?= e($isiPesan['wa']) ?>" inputmode="tel" required placeholder="08xxxxxxxxxx"></div>
    </div>
    <fieldset class="pilihan-pil"><legend>Pengambilan</legend>
      <label><input type="radio" name="pengambilan" value="Ambil sendiri" <?= $isiPesan['pengambilan'] !== 'Diantar' ? 'checked' : '' ?>> Ambil sendiri</label>
      <label><input type="radio" name="pengambilan" value="Diantar" <?= $isiPesan['pengambilan'] === 'Diantar' ? 'checked' : '' ?>> Diantar (ongkir sesuai penjual)</label>
    </fieldset>
    <div class="field"><label for="p-alamat">Alamat (kalau diantar)</label><input id="p-alamat" name="alamat" value="<?= e($isiPesan['alamat']) ?>" maxlength="255"></div>
    <div class="field"><label for="p-catatan">Catatan untuk penjual (opsional)</label><input id="p-catatan" name="catatan" value="<?= e($isiPesan['catatan']) ?>" maxlength="255" placeholder="Contoh: level pedas sedang"></div>
    <label class="cek-setuju"><input type="checkbox" name="setuju_data" value="1" required> <span>Saya setuju nama, nomor WhatsApp, dan alamat diteruskan ke penjual untuk memproses pesanan, sesuai <a href="/kontak#privasi">kebijakan privasi</a>.</span></label>
    <div class="hp" aria-hidden="true"><label>Jangan diisi<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <button class="btn btn-main" type="submit" style="width:100%">Kirim pesanan</button>
    <p class="hint">Pesanan untuk beberapa penjual dipisah otomatis. Setelah ini kamu diarahkan mengirim pesanan ke WhatsApp masing-masing penjual.</p>
  </form>
</dialog>
<?php
$scriptHalaman = ($pesananBaru ? '<script>try{localStorage.removeItem("mm_keranjang")}catch(e){}</script>' : '') . '<script src="/assets/js/keranjang.js"></script>' . ($errorPesan ? '<script>document.addEventListener("DOMContentLoaded",function(){var d=document.getElementById("keranjangDialog");d&&d.showModal&&d.showModal()})</script>' : '');
require __DIR__ . '/../inc/footer.php';
?>
