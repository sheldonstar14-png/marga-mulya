<?php
// =========================================================
// HALAMAN KONTAK
//   - Nomor darurat (bisa langsung ditelepon dari HP)
//   - Pesan biasa -> tabel pesan
//   - Aspirasi/pengaduan warga dengan kode pelacakan -> tabel aspirasi
//     GET /kontak?adu=ADU-XXXXXX#aspirasi  -> cek perkembangan
// =========================================================
$topikValid = ['Pertanyaan umum', 'Wisata', 'Layanan administrasi'];
$kategoriAspirasi = ['Infrastruktur', 'Kebersihan dan lingkungan', 'Pelayanan administrasi', 'Kesehatan', 'Keamanan dan ketertiban', 'Usulan program', 'Bansos (salah sasaran atau usulan penerima)', 'Permohonan informasi publik', 'Lainnya'];
$jenisPermintaan = ['Salinan data', 'Perbaikan data', 'Penghapusan data', 'Tarik persetujuan'];
$isi = ['nama' => '', 'kontak' => '', 'topik' => $topikValid[0], 'isi' => ''];
$adu = ['nama' => '', 'wa' => '', 'email' => '', 'kategori' => $kategoriAspirasi[0], 'lokasi' => '', 'isi' => ''];
// Tombol dari tab Pantau bansos membawa ?kategori=Bansos supaya kategorinya langsung terpilih
if (($_GET['kategori'] ?? '') === 'Bansos') $adu['kategori'] = 'Bansos (salah sasaran atau usulan penerima)';
$errors = [];
$errorAdu = [];
$minta = ['jenis' => $jenisPermintaan[0], 'nama' => '', 'kontak' => '', 'kode_terkait' => '', 'rincian' => ''];
$errorMinta = [];
$anonim = !empty($_POST['anonim']);

// ---------- Aspirasi warga dikirim ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'aspirasi') {
    foreach ($adu as $k => $_) {
        $adu[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $wa = preg_replace('/\D/', '', $adu['wa']);
    if ($anonim) $adu['nama'] = 'Anonim';
    if (!empty($_POST['website'])) $errorAdu[] = 'Aspirasi tidak dapat diproses.';
    if (mb_strlen($adu['nama']) < 2 || mb_strlen($adu['nama']) > 150) $errorAdu[] = 'Isi nama (2 sampai 150 huruf), atau centang Kirim tanpa nama.';
    if (empty($_POST['setuju_data'])) $errorAdu[] = 'Centang persetujuan penggunaan data supaya aspirasimu bisa diproses.';
    if ($adu['email'] !== '' && (!filter_var($adu['email'], FILTER_VALIDATE_EMAIL) || strlen($adu['email']) > 150)) $errorAdu[] = 'Alamat email belum benar.';
    if ($wa !== '' && (strlen($wa) < 10 || strlen($wa) > 15)) $errorAdu[] = 'Nomor WhatsApp 10 sampai 15 digit.';
    if (!$anonim && $adu['email'] === '' && $wa === '') $errorAdu[] = 'Isi email atau nomor WhatsApp supaya petugas bisa mengabari perkembangannya, atau centang Kirim tanpa nama dan simpan kode lacaknya.';
    if (!in_array($adu['kategori'], $kategoriAspirasi, true)) $errorAdu[] = 'Pilih kategori dari daftar.';
    if (mb_strlen($adu['lokasi']) > 150) $errorAdu[] = 'Lokasi maksimal 150 huruf.';
    if (mb_strlen($adu['isi']) < 15 || mb_strlen($adu['isi']) > 2000) $errorAdu[] = 'Ceritakan aspirasimu 15 sampai 2.000 huruf.';

    if (!$errorAdu) {
        $kode = kode_unik('ADU', 'aspirasi');
        q('INSERT INTO aspirasi (kode, nama, wa, email, kategori, lokasi, isi) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$kode, $adu['nama'], $wa, $adu['email'], $adu['kategori'], $adu['lokasi'], $adu['isi']]);
        require_once __DIR__ . '/../inc/email.php';
        if ($adu['email'] !== '') email_aspirasi($kode);
        email_admin_aspirasi_baru($kode);
        redirect('/kontak?adu=' . urlencode($kode) . '&baru=1#aspirasi');
    }
}

// ---------- Pesan biasa dikirim ----------
// ---------- Permintaan data pribadi ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'data') {
    foreach ($minta as $k => $_) {
        $minta[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    $minta['kode_terkait'] = strtoupper($minta['kode_terkait']);
    if (!empty($_POST['website'])) $errorMinta[] = 'Permintaan tidak dapat diproses.';
    if (!in_array($minta['jenis'], $jenisPermintaan, true)) $errorMinta[] = 'Pilih jenis permintaan dari daftar.';
    if (mb_strlen($minta['nama']) < 2 || mb_strlen($minta['nama']) > 150) $errorMinta[] = 'Isi nama sesuai data yang pernah kamu kirim.';
    if (mb_strlen($minta['kontak']) < 5 || mb_strlen($minta['kontak']) > 150) $errorMinta[] = 'Isi email atau nomor WhatsApp yang dulu kamu pakai, supaya datanya bisa dicocokkan.';
    if ($minta['kode_terkait'] !== '' && !preg_match('/^(MM|ADU)-[A-Z0-9]{3,8}$/', $minta['kode_terkait'])) $errorMinta[] = 'Kode terkait diawali MM- (reservasi) atau ADU- (aspirasi).';
    if (mb_strlen($minta['rincian']) < 10 || mb_strlen($minta['rincian']) > 2000) $errorMinta[] = 'Jelaskan permintaanmu 10 sampai 2.000 huruf.';
    if (!$errorMinta) {
        $kode = kode_unik('DATA', 'permintaan_data');
        q('INSERT INTO permintaan_data (kode, jenis, nama, kontak, kode_terkait, rincian) VALUES (?, ?, ?, ?, ?, ?)',
            [$kode, $minta['jenis'], $minta['nama'], $minta['kontak'], $minta['kode_terkait'], $minta['rincian']]);
        require_once __DIR__ . '/../inc/email.php';
        email_permintaan_data($kode);
        redirect('/kontak?data=' . urlencode($kode) . '&baru=1#privasi');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($_POST['aksi'] ?? '', ['aspirasi', 'data'], true)) {
    foreach ($isi as $k => $_) {
        $isi[$k] = trim((string) ($_POST[$k] ?? ''));
    }
    if (!empty($_POST['website'])) $errors[] = 'Pesan tidak dapat diproses.';
    if (mb_strlen($isi['nama']) < 2 || mb_strlen($isi['nama']) > 150) $errors[] = 'Isi nama (2 sampai 150 huruf).';
    if (mb_strlen($isi['kontak']) < 5 || mb_strlen($isi['kontak']) > 150) $errors[] = 'Isi email atau nomor WhatsApp supaya kami bisa membalas.';
    if (!in_array($isi['topik'], $topikValid, true)) $errors[] = 'Pilih topik dari daftar.';
    if (mb_strlen($isi['isi']) < 5 || mb_strlen($isi['isi']) > 2000) $errors[] = 'Isi pesan 5 sampai 2.000 huruf.';
    if (empty($_POST['setuju_data'])) $errors[] = 'Centang persetujuan penggunaan data supaya kami bisa membalas.';

    if (!$errors) {
        q('INSERT INTO pesan (nama, kontak, topik, isi) VALUES (?, ?, ?, ?)', [$isi['nama'], $isi['kontak'], $isi['topik'], $isi['isi']]);
        redirect('/kontak?terkirim=1');
    }
}

$kodeData = strtoupper(trim((string) ($_GET['data'] ?? '')));
$hasilData = $kodeData !== '' ? q1('SELECT kode, jenis, status, tanggapan, dibuat_pada FROM permintaan_data WHERE kode = ?', [$kodeData]) : null;
$hariSimpan = max(30, (int) setting('simpan_data_hari'));
$darurat = q('SELECT nama, nomor, keterangan FROM darurat ORDER BY urutan, id');
$kodeCek = strtoupper(trim((string) ($_GET['adu'] ?? '')));
$hasilAdu = $kodeCek !== '' ? q1('SELECT kode, kategori, lokasi, isi, status, tanggapan, dibuat_pada, diperbarui_pada FROM aspirasi WHERE kode = ?', [$kodeCek]) : null;
$tindakLanjut = q("SELECT kategori, lokasi, isi, tanggapan, diperbarui_pada FROM aspirasi WHERE publik = 1 AND status = 'Selesai' ORDER BY diperbarui_pada DESC, id DESC LIMIT 4");
$jumlahSelesai = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status = 'Selesai'")['n'];
$jumlahAspirasi = (int) q1('SELECT COUNT(*) AS n FROM aspirasi')['n'];

$titik = [['lat' => (float) setting('lat'), 'lng' => (float) setting('lng'), 'nama' => 'Kantor ' . setting('nama_desa'), 'jenis' => 'pilih']];

$judul = 'Kontak - ' . setting('nama_desa');
$menu  = 'kontak';
$pakaiPeta = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Kontak dan layanan warga</h1>
  <p>Hubungi petugas desa, sampaikan aspirasi atau pengaduan, dan simpan nomor darurat yang bisa langsung ditelepon dari HP.</p>
</div>

<?php if ($darurat): ?>
<div class="wrap" id="darurat" style="padding-top:8px">
  <div class="darurat">
    <h2>Nomor darurat</h2>
    <div class="darurat-grid">
      <?php foreach ($darurat as $d): ?>
        <a class="darurat-item" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $d['nomor'])) ?>">
          <b><?= e($d['nomor']) ?></b>
          <span><?= e($d['nama']) ?></span>
          <?php if ($d['keterangan']): ?><small><?= e($d['keterangan']) ?></small><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="wrap sec split" style="padding-top:24px">
  <form class="panel" method="post" action="/kontak">
    <h2 style="font-size:1.35rem">Kirim pesan</h2>
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
      <div class="hint">Mau melapor masalah atau mengusulkan program? Pakai <a href="#aspirasi">form aspirasi warga</a> supaya bisa dilacak.</div>
    </div>
    <div class="field"><label for="isi">Pesan</label><textarea id="isi" name="isi" rows="5" maxlength="2000" required><?= e($isi['isi']) ?></textarea></div>
    <label class="cek-setuju"><input type="checkbox" name="setuju_data" value="1" required id="setuju-pesan"> <span>Saya setuju data ini dipakai desa untuk menanggapi permintaan saya, sesuai <a href="#privasi">kebijakan privasi</a>.</span></label>
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

<div class="sec tint" id="aspirasi"><div class="wrap">
  <div class="head-row" style="align-items:flex-end">
    <div>
      <h2>Aspirasi dan pengaduan warga</h2>
      <p class="muted" style="margin:4px 0 0">Setiap laporan mendapat kode untuk dilacak. Perkembangannya dikirim ke email atau WhatsApp kamu.</p>
    </div>
    <?php if ($jumlahAspirasi): ?><p class="small" style="margin:0"><b><?= $jumlahSelesai ?></b> dari <?= $jumlahAspirasi ?> aspirasi sudah selesai ditindaklanjuti</p><?php endif; ?>
  </div>

  <div class="split">
    <form class="panel" method="post" action="/kontak#aspirasi">
      <input type="hidden" name="aksi" value="aspirasi">
      <?php if ($errorAdu): ?>
        <div class="alert alert-err" role="alert"><b>Aspirasi belum terkirim:</b><ul><?php foreach ($errorAdu as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <div class="field"><label for="a-kategori">Kategori</label>
        <select id="a-kategori" name="kategori"><?php foreach ($kategoriAspirasi as $k): ?><option <?= $k === $adu['kategori'] ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?></select>
        <div class="hint">Ingin meminta salinan dokumen desa, misalnya rincian anggaran? Pilih <b>Permohonan informasi publik</b>.</div>
      </div>
      <div class="field"><label for="a-lokasi">Lokasi (opsional)</label><input id="a-lokasi" name="lokasi" value="<?= e($adu['lokasi']) ?>" maxlength="150" placeholder="Contoh: jalan depan SD, RW 03"></div>
      <div class="field"><label for="a-isi">Ceritakan aspirasi atau masalahnya</label><textarea id="a-isi" name="isi" rows="5" maxlength="2000" required placeholder="Apa yang terjadi, sejak kapan, dan apa harapanmu"><?= e($adu['isi']) ?></textarea></div>
      <label class="cek-setuju"><input type="checkbox" name="anonim" value="1" id="a-anonim" <?= $anonim ? 'checked' : '' ?>> <span><b>Kirim tanpa nama.</b> Kamu cukup menyimpan kode lacak. Cocok kalau kamu khawatir identitasmu diketahui.</span></label>
      <div class="field" id="a-identitas"><label for="a-nama">Nama</label><input id="a-nama" name="nama" value="<?= e($anonim ? '' : $adu['nama']) ?>" maxlength="150">
        <div class="hint">Nama dan kontakmu hanya terlihat oleh petugas desa, tidak ditampilkan di website.</div></div>
      <div class="grid g2" style="gap:12px">
        <div class="field"><label for="a-email">Email</label><input id="a-email" name="email" type="email" value="<?= e($adu['email']) ?>" maxlength="150" placeholder="nama@gmail.com"></div>
        <div class="field"><label for="a-wa">WhatsApp</label><input id="a-wa" name="wa" value="<?= e($adu['wa']) ?>" inputmode="tel" placeholder="08xxxxxxxxxx"></div>
      </div>
      <p class="hint" style="margin-top:-6px">Isi salah satu, email atau WhatsApp, supaya kabar perkembangan bisa dikirim. Boleh dikosongkan kalau mengirim tanpa nama.</p>
    <label class="cek-setuju"><input type="checkbox" name="setuju_data" value="1" required id="setuju-aspirasi"> <span>Saya setuju data ini dipakai desa untuk menanggapi permintaan saya, sesuai <a href="#privasi">kebijakan privasi</a>.</span></label>
      <div class="hp" aria-hidden="true"><label for="website2">Jangan diisi</label><input id="website2" name="website" tabindex="-1" autocomplete="off"></div>
      <button class="btn btn-main" type="submit">Kirim aspirasi</button>
    </form>

    <div>
      <div class="panel">
        <h3>Lacak aspirasi</h3>
        <form method="get" action="/kontak#aspirasi" class="grid" style="grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end">
          <div><label for="adu">Kode aspirasi</label><input id="adu" name="adu" value="<?= e($kodeCek) ?>" placeholder="Contoh: ADU-7KQ2PA"></div>
          <button class="btn btn-dark" type="submit">Lacak</button>
        </form>
        <?php if ($kodeCek !== ''): ?>
          <?php if ($hasilAdu):
            $tahap = $hasilAdu['status'] === 'Ditolak' ? ['Diterima', 'Ditolak'] : ['Diterima', 'Diproses', 'Selesai'];
            $posisi = array_search($hasilAdu['status'], $tahap, true); ?>
            <div class="hasil-adu" role="status">
              <?php if (isset($_GET['baru'])): ?>
                <div class="alert alert-ok">Aspirasi terkirim. Simpan kode <b><?= e($hasilAdu['kode']) ?></b> untuk melacak perkembangannya.</div>
              <?php endif; ?>
              <p style="margin:14px 0 4px"><b><?= e($hasilAdu['kode']) ?></b> <span class="tag"><?= e($hasilAdu['kategori']) ?></span></p>
              <p class="small muted" style="margin:0">Dikirim <?= tgl(substr((string) $hasilAdu['dibuat_pada'], 0, 10)) ?><?= $hasilAdu['lokasi'] ? ', ' . e($hasilAdu['lokasi']) : '' ?></p>
              <ol class="steps" aria-label="Tahap tindak lanjut">
                <?php foreach ($tahap as $i => $t): ?><li class="<?= $i <= $posisi ? 'done' : '' ?> <?= $t === 'Ditolak' ? 'bad' : '' ?>"><?= $t ?></li><?php endforeach; ?>
              </ol>
              <?php if ($hasilAdu['tanggapan']): ?>
                <div class="tanggapan"><b>Tanggapan petugas</b><p><?= nl2br(e($hasilAdu['tanggapan'])) ?></p></div>
              <?php else: ?>
                <p class="small muted">Belum ada tanggapan. Petugas biasanya meninjau dalam 3 hari kerja.</p>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-err" role="alert" style="margin-top:14px">Kode <?= e($kodeCek) ?> tidak ditemukan. Periksa kembali, kodenya diawali ADU-.</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if ($tindakLanjut): ?>
        <div class="panel" style="margin-top:16px">
          <h3>Sudah ditindaklanjuti</h3>
          <?php foreach ($tindakLanjut as $t): ?>
            <div class="tl-item">
              <span class="tag"><?= e($t['kategori']) ?></span><?php if ($t['lokasi']): ?> <span class="small muted"><?= e($t['lokasi']) ?></span><?php endif; ?>
              <p class="small" style="margin:6px 0 4px"><?= e(mb_strimwidth($t['isi'], 0, 140, '...')) ?></p>
              <p class="small tl-jawab"><?= e($t['tanggapan']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div></div>

<div class="sec" id="privasi"><div class="wrap">
  <h2>Privasi dan data pribadimu</h2>
  <p class="muted" style="max-width:70ch">Kami mengikuti Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi: data diambil secukupnya, dipakai sesuai tujuan, dan kamu berhak mengaturnya.</p>
  <div class="grid g3 privasi-poin">
    <div class="panel"><h3>Data yang kami simpan</h3><ul class="small">
      <li>Reservasi: nama, WhatsApp, email, jumlah orang, kendaraan, dan perkiraan jarak.</li>
      <li>Aspirasi dan pesan: nama dan kontak (boleh tanpa nama untuk aspirasi).</li>
      <li>Relawan gotong royong: nama dan WhatsApp, dihapus 30 hari setelah kegiatan.</li>
      <li>Kami tidak meminta KTP, KK, atau NIK lewat website.</li></ul></div>
    <div class="panel"><h3>Untuk apa dan berapa lama</h3><ul class="small">
      <li>Hanya untuk mengurus reservasi, membalas pesan, dan menindaklanjuti aspirasi.</li>
      <li>Tidak dijual dan tidak dibagikan untuk iklan.</li>
      <li>Nama dan kontak dihapus otomatis <?= $hariSimpan ?> hari setelah urusan selesai. Angka statistik tanpa nama tetap disimpan.</li></ul></div>
    <div class="panel"><h3>Hakmu</h3><ul class="small">
      <li>Meminta salinan data pribadimu.</li>
      <li>Meminta data yang salah diperbaiki.</li>
      <li>Meminta data dihapus atau menarik persetujuan.</li>
      <li>Penanggung jawab: <?= e(setting('kontak_privasi')) ?>.</li></ul></div>
  </div>

  <div class="split" style="margin-top:20px">
    <form class="panel" method="post" action="/kontak#privasi">
      <h3>Ajukan permintaan data pribadi</h3>
      <input type="hidden" name="aksi" value="data">
      <?php if ($errorMinta): ?>
        <div class="alert alert-err" role="alert"><b>Permintaan belum terkirim:</b><ul><?php foreach ($errorMinta as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <div class="field"><label for="d-jenis">Jenis permintaan</label>
        <select id="d-jenis" name="jenis"><?php foreach ($jenisPermintaan as $j): ?><option <?= $j === $minta['jenis'] ? 'selected' : '' ?>><?= e($j) ?></option><?php endforeach; ?></select></div>
      <div class="grid g2" style="gap:12px">
        <div class="field"><label for="d-nama">Nama</label><input id="d-nama" name="nama" value="<?= e($minta['nama']) ?>" maxlength="150" required></div>
        <div class="field"><label for="d-kontak">Email atau WhatsApp yang dulu dipakai</label><input id="d-kontak" name="kontak" value="<?= e($minta['kontak']) ?>" maxlength="150" required></div>
      </div>
      <div class="field"><label for="d-kode">Kode reservasi atau aspirasi (opsional)</label><input id="d-kode" name="kode_terkait" value="<?= e($minta['kode_terkait']) ?>" maxlength="20" placeholder="Contoh: MM-0427 atau ADU-7KQ2PA"></div>
      <div class="field"><label for="d-rincian">Jelaskan permintaanmu</label><textarea id="d-rincian" name="rincian" rows="3" maxlength="2000" required placeholder="Contoh: Tolong hapus nama dan nomor WhatsApp saya dari reservasi bulan lalu."><?= e($minta['rincian']) ?></textarea></div>
      <div class="hp" aria-hidden="true"><label for="website3">Jangan diisi</label><input id="website3" name="website" tabindex="-1" autocomplete="off"></div>
      <button class="btn btn-dark" type="submit">Kirim permintaan</button>
      <p class="hint">Petugas mencocokkan data lewat kontak dan kode yang kamu tulis, lalu mengabari hasilnya.</p>
    </form>
    <div class="panel">
      <h3>Lacak permintaan</h3>
      <form method="get" action="/kontak#privasi" class="grid" style="grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end">
        <div><label for="data">Kode permintaan</label><input id="data" name="data" value="<?= e($kodeData) ?>" placeholder="Contoh: DATA-4KX9PQ"></div>
        <button class="btn btn-dark" type="submit">Lacak</button>
      </form>
      <?php if ($kodeData !== ''): ?>
        <?php if ($hasilData): ?>
          <div role="status" style="margin-top:14px">
            <?php if (isset($_GET['baru'])): ?><div class="alert alert-ok">Permintaan terkirim. Simpan kode <b><?= e($hasilData['kode']) ?></b>.</div><?php endif; ?>
            <p style="margin:0"><b><?= e($hasilData['kode']) ?></b> <span class="tag"><?= e($hasilData['jenis']) ?></span> <span class="status st-<?= e(['Diterima' => 'Menunggu', 'Diproses' => 'Dikonfirmasi', 'Selesai' => 'Selesai', 'Ditolak' => 'Dibatalkan'][$hasilData['status']] ?? 'Menunggu') ?>"><?= e($hasilData['status']) ?></span></p>
            <?php if ($hasilData['tanggapan']): ?><div class="hasil-adu"><div class="tanggapan"><b>Tanggapan petugas</b><p><?= nl2br(e($hasilData['tanggapan'])) ?></p></div></div><?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-err" role="alert" style="margin-top:14px">Kode <?= e($kodeData) ?> tidak ditemukan.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div></div>
<?php
$scriptHalaman = <<<'JS'
<script>
// Kirim tanpa nama: sembunyikan kolom nama
(function () {
  const cek = document.getElementById('a-anonim'), kotak = document.getElementById('a-identitas');
  if (!cek || !kotak) return;
  const atur = () => { kotak.hidden = cek.checked; };
  cek.addEventListener('change', atur); atur();
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php'; ?>
