<?php
// =========================================================
// HALAMAN KONTAK. Pesan disimpan ke tabel pesan, dibaca admin di CMS.
// =========================================================
$topikValid = ['Pertanyaan umum', 'Wisata', 'Layanan administrasi', 'Saran dan aspirasi'];
$isi = ['nama' => '', 'kontak' => '', 'topik' => $topikValid[0], 'isi' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($isi as $k => $_) {
        $isi[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if (!empty($_POST['website'])) $errors[] = 'Pesan tidak dapat diproses.';
    if (mb_strlen($isi['nama']) < 2 || mb_strlen($isi['nama']) > 150) $errors[] = 'Isi nama (2 sampai 150 huruf).';
    if (mb_strlen($isi['kontak']) < 5 || mb_strlen($isi['kontak']) > 150) $errors[] = 'Isi email atau nomor WhatsApp supaya kami bisa membalas.';
    if (!in_array($isi['topik'], $topikValid, true)) $errors[] = 'Pilih topik dari daftar.';
    if (mb_strlen($isi['isi']) < 5 || mb_strlen($isi['isi']) > 2000) $errors[] = 'Isi pesan 5 sampai 2.000 huruf.';

    if (!$errors) {
        q('INSERT INTO pesan (nama, kontak, topik, isi) VALUES (?, ?, ?, ?)', [$isi['nama'], $isi['kontak'], $isi['topik'], $isi['isi']]);
        redirect('/kontak?terkirim=1');
    }
}

$titik = [['lat' => (float) setting('lat'), 'lng' => (float) setting('lng'), 'nama' => 'Kantor ' . setting('nama_desa'), 'jenis' => 'pilih']];

$judul = 'Kontak - ' . setting('nama_desa');
$menu  = 'kontak';
$pakaiPeta = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Kontak</h1>
  <p>Ada pertanyaan, saran, atau aspirasi? Kirim pesan dan petugas desa akan membalas di jam layanan.</p>
</div>
<div class="wrap sec split" style="padding-top:16px">
  <form class="panel" method="post" action="/kontak">
    <?php if (isset($_GET['terkirim'])): ?>
      <div class="alert alert-ok" role="status">Pesan terkirim. Petugas desa akan membalas di jam layanan.</div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-err" role="alert"><b>Pesan belum terkirim:</b><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <div class="field"><label for="nama">Nama</label><input id="nama" name="nama" value="<?= e($isi['nama']) ?>" maxlength="150" required></div>
    <div class="field"><label for="kontak">Email atau WhatsApp</label><input id="kontak" name="kontak" value="<?= e($isi['kontak']) ?>" maxlength="150" required></div>
    <div class="field"><label for="topik">Topik</label>
      <select id="topik" name="topik">
        <?php foreach ($topikValid as $t): ?><option <?= $t === $isi['topik'] ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label for="isi">Pesan</label><textarea id="isi" name="isi" rows="5" maxlength="2000" required><?= e($isi['isi']) ?></textarea></div>
    <div class="hp" aria-hidden="true"><label for="website">Jangan diisi</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
    <button class="btn btn-main" type="submit">Kirim pesan</button>
  </form>

  <div class="panel">
    <h3>Kantor <?= e(setting('nama_desa')) ?></h3>
    <p><?= e(setting('alamat')) ?><br><?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></p>
    <p><b>Jam layanan</b><br><?= e(setting('jam_layanan')) ?></p>
    <p><b>Email</b><br><?= e(setting('email')) ?></p>
    <p><b>WhatsApp</b><br>+<?= e(setting('wa')) ?></p>
    <a class="btn btn-wa" href="<?= e(wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya.')) ?>" target="_blank" rel="noopener">Chat WhatsApp desa</a>
    <div class="peta peta-kecil" style="margin-top:18px" data-titik="<?= titik_peta($titik) ?>" role="region" aria-label="Lokasi kantor desa"></div>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
