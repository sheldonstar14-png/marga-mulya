<?php
// =========================================================
// HALAMAN KONTAK
//   - Nomor darurat (bisa langsung ditelepon dari HP)
//   - (12s) Form "Kirim pesan" dan "Permintaan data pribadi" dihapus: pertanyaan memakai
//     aspirasi kategori Pertanyaan umum, hak data pribadi lewat WhatsApp atau email desa.
//   - Aspirasi/pengaduan warga dengan kode pelacakan -> tabel aspirasi
//     GET /kontak?adu=ADU-XXXXXX#aspirasi  -> cek perkembangan
// =========================================================
$kategoriAspirasi = ['Pertanyaan umum', 'Infrastruktur', 'Kebersihan dan lingkungan', 'Pelayanan administrasi', 'Kesehatan', 'Keamanan dan ketertiban', 'Usulan program', 'Bansos (salah sasaran atau usulan penerima)', 'Permohonan informasi publik', 'Lainnya'];
$adu = ['nama' => '', 'wa' => '', 'email' => '', 'kategori' => $kategoriAspirasi[1], 'lokasi' => '', 'isi' => ''];
// Tombol dari tab Pantau bansos membawa ?kategori=Bansos supaya kategorinya langsung terpilih
if (($_GET['kategori'] ?? '') === 'Bansos') $adu['kategori'] = 'Bansos (salah sasaran atau usulan penerima)';
if (($_GET['kategori'] ?? '') === 'Pertanyaan') $adu['kategori'] = 'Pertanyaan umum';
$errorAdu = [];
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

    [$fotoAdu, $errorFoto] = siapkan_foto_aspirasi($_FILES['foto'] ?? []);
    $errorAdu = array_merge($errorAdu, $errorFoto);
    $izinPublik = !empty($_POST['izin_publik']) ? 1 : 0;

    if (!$errorAdu) {
        $kode = kode_unik('ADU', 'aspirasi');
        try {
            q('INSERT INTO aspirasi (kode, nama, wa, email, kategori, lokasi, isi, izin_publik) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$kode, $adu['nama'], $wa, $adu['email'], $adu['kategori'], $adu['lokasi'], $adu['isi'], $izinPublik]);
        } catch (Throwable $e) { // migrasi 12m belum dijalankan
            q('INSERT INTO aspirasi (kode, nama, wa, email, kategori, lokasi, isi) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$kode, $adu['nama'], $wa, $adu['email'], $adu['kategori'], $adu['lokasi'], $adu['isi']]);
        }
        $idBaru = (int) q1('SELECT id FROM aspirasi WHERE kode = ?', [$kode])['id'];
        catat_riwayat_aspirasi($idBaru, 'Diterima');
        if ($fotoAdu) { try { simpan_foto_aspirasi($idBaru, $fotoAdu); } catch (Throwable $e) {} }
        require_once __DIR__ . '/../inc/email.php';
        if ($adu['email'] !== '') email_aspirasi($kode);
        email_admin_aspirasi_baru($kode);
        redirect('/kontak?adu=' . urlencode($kode) . '&baru=1#aspirasi');
    }
}

// ---------- Penilaian kepuasan setelah aspirasi selesai ----------
$errorSurveiAdu = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'survei') {
    $kodeSv = strtoupper(trim((string) ($_POST['kode'] ?? '')));
    $errorSurveiAdu = simpan_survei('aspirasi', $kodeSv, (int) ($_POST['nilai'] ?? 0), (string) ($_POST['komentar'] ?? ''));
    if (!$errorSurveiAdu) redirect('/kontak?adu=' . urlencode($kodeSv) . '#aspirasi');
    $_GET['adu'] = $kodeSv;
}

// ---------- "Saya juga mengalami" di Papan Aspirasi ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'dukung') {
    $idDukung = (int) ($_POST['id'] ?? 0);
    $target = q1('SELECT id FROM aspirasi WHERE id = ? AND publik = 1 AND izin_publik = 1', [$idDukung]);
    if ($target && empty($_POST['website'])) {
        try {
            q('INSERT INTO aspirasi_dukung (aspirasi_id, sidik) VALUES (?, ?)', [$idDukung, sidik_pengunjung()]);
            q('UPDATE aspirasi SET dukungan = dukungan + 1 WHERE id = ?', [$idDukung]);
        } catch (Throwable $e) { /* perangkat ini sudah pernah mendukung */ }
    }
    $balik = array_intersect_key($_GET, array_flip(['papan', 'kat', 'hal']));
    redirect('/kontak?' . http_build_query($balik + ['didukung' => $idDukung]) . '#adu-' . $idDukung);
}

$hariSimpan = max(30, (int) setting('simpan_data_hari'));
$darurat = q('SELECT nama, nomor, keterangan FROM darurat ORDER BY urutan, id');
$kodeCek = strtoupper(trim((string) ($_GET['adu'] ?? '')));
$hasilAdu = $kodeCek !== '' ? q1('SELECT id, kode, kategori, lokasi, isi, status, tanggapan, dibuat_pada, diperbarui_pada FROM aspirasi WHERE kode = ?', [$kodeCek]) : null;

// ---------- Papan Aspirasi: semua laporan yang diizinkan pengirim dan sudah diperiksa petugas ----------
$statusPapan = ['Diterima', 'Diproses', 'Selesai', 'Ditolak'];
$papanStatus = in_array($_GET['papan'] ?? '', $statusPapan, true) ? $_GET['papan'] : '';
$papanKat = in_array($_GET['kat'] ?? '', $kategoriAspirasi, true) ? $_GET['kat'] : '';
$papanHal = max(1, (int) ($_GET['hal'] ?? 1));
$perHal = 6;
$papan = []; $papanTotal = 0; $hitungPapan = []; $menungguPeriksa = 0; $rataHari = null; $fotoPapan = [];
try {
    $syarat = 'publik = 1 AND izin_publik = 1'; $arg = [];
    if ($papanStatus) { $syarat .= ' AND status = ?'; $arg[] = $papanStatus; }
    if ($papanKat) { $syarat .= ' AND kategori = ?'; $arg[] = $papanKat; }
    $papanTotal = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE $syarat", $arg)['n'];
    $papan = q("SELECT id, kategori, lokasi, isi, status, tanggapan, dukungan, dibuat_pada, diperbarui_pada FROM aspirasi WHERE $syarat ORDER BY COALESCE(diperbarui_pada, dibuat_pada) DESC, id DESC LIMIT $perHal OFFSET " . (($papanHal - 1) * $perHal), $arg);
    foreach (q('SELECT status, COUNT(*) AS n FROM aspirasi WHERE publik = 1 AND izin_publik = 1 GROUP BY status') as $h) $hitungPapan[$h['status']] = (int) $h['n'];
    $menungguPeriksa = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE izin_publik = 1 AND publik = 0 AND status = 'Diterima'")['n'];
    $lamaSelesai = [];
    foreach (q("SELECT dibuat_pada, diperbarui_pada FROM aspirasi WHERE status = 'Selesai' AND diperbarui_pada IS NOT NULL") as $r) {
        $lamaSelesai[] = max(0, (strtotime($r['diperbarui_pada']) - strtotime($r['dibuat_pada'])) / 86400);
    }
    if ($lamaSelesai) $rataHari = max(1, (int) round(array_sum($lamaSelesai) / count($lamaSelesai)));
    if ($papan) {
        $idPapan = implode(',', array_map(fn($r) => (int) $r['id'], $papan));
        foreach (q("SELECT id, aspirasi_id FROM aspirasi_foto WHERE aspirasi_id IN ($idPapan) ORDER BY id") as $f) $fotoPapan[$f['aspirasi_id']][] = (int) $f['id'];
    }
} catch (Throwable $e) { /* migrasi 12m belum dijalankan: papan disembunyikan */ }
$fotoLacak = [];
if ($hasilAdu) { try { $fotoLacak = array_map(fn($r) => (int) $r['id'], q('SELECT id FROM aspirasi_foto WHERE aspirasi_id = ? ORDER BY id', [$hasilAdu['id']])); } catch (Throwable $e) {} }
$didukung = (int) ($_GET['didukung'] ?? 0);
$urlPapan = function (array $ubah) use ($papanStatus, $papanKat): string {
    $q = array_filter(array_merge(['papan' => $papanStatus, 'kat' => $papanKat], $ubah), fn($v) => $v !== '' && $v !== null && $v !== 1);
    return '/kontak' . ($q ? '?' . http_build_query($q) : '') . '#papan';
};
$jumlahSelesai = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status = 'Selesai'")['n'];
$jumlahAspirasi = (int) q1('SELECT COUNT(*) AS n FROM aspirasi')['n'];

$titik = [['lat' => (float) setting('lat'), 'lng' => (float) setting('lng'), 'nama' => 'Kantor ' . setting('nama_desa'), 'jenis' => 'pilih']];

$judul = 'Kontak - ' . setting('nama_desa');
$menu  = 'kontak';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Kontak dan layanan warga</h1>
  <p>Hubungi petugas desa, sampaikan aspirasi atau pengaduan, dan simpan nomor darurat yang bisa langsung ditelepon dari HP.</p>
</div>

<?php if ($darurat): ?>
<section class="wrap darurat-a" id="darurat" aria-labelledby="judul-darurat">
  <div class="darurat-kepala">
    <h2 id="judul-darurat">Nomor darurat</h2>
    <p>Tekan kartu untuk langsung menelepon dari HP. Nomor 112 bebas pulsa untuk semua keadaan darurat. Halaman ini tetap bisa dibuka tanpa internet setelah website dipasang di HP.</p>
  </div>
  <div class="darurat-kartu">
    <?php foreach ($darurat as $d): ?>
      <a class="darurat-item" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $d['nomor'])) ?>">
        <span class="darurat-nomor"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg><?= e($d['nomor']) ?></span>
        <b><?= e($d['nama']) ?></b>
        <small><?= e($d['keterangan'] ?: 'Tekan untuk menelepon') ?></small>
      </a>
    <?php endforeach; ?>
    <a class="darurat-item darurat-desa" href="<?= e(wa_link(setting('wa'), 'Halo petugas ' . setting('nama_desa') . ', saya perlu bantuan segera: ')) ?>" target="_blank" rel="noopener">
      <span class="darurat-nomor"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/></svg>WA desa</span>
      <b>Petugas <?= e(setting('nama_desa')) ?></b>
      <small>Banjir, rob, pohon tumbang, atau warga butuh bantuan. Di luar jam kantor, tetap hubungi 112.</small>
    </a>
  </div>
</section>
<?php endif; ?>

<div class="wrap sec split" style="padding-top:24px" id="peta">
  <div class="panel">
    <h3>Kantor <?= e(setting('nama_desa')) ?></h3>
    <p><?= e(setting('alamat')) ?><br><?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></p>
    <p><b>Jam layanan</b><br><?= e(setting('jam_layanan')) ?></p>
    <p><b>Email</b><br><?= e(setting('email')) ?></p>
    <p><b>WhatsApp</b><br>+<?= e(setting('wa')) ?></p>
    <div class="actions">
      <a class="btn btn-wa" href="<?= e(wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya.')) ?>" target="_blank" rel="noopener">Chat WhatsApp desa</a>
      <a class="btn btn-ghost" href="/kontak?kategori=Pertanyaan#aspirasi">Tulis pertanyaan</a>
    </div>
    <p class="hint">Pertanyaan yang perlu jawaban tertulis bisa dikirim lewat form aspirasi dengan kategori <b>Pertanyaan umum</b>. Kamu mendapat kode lacak dan jawabannya dikirim ke WhatsApp atau email.</p>
  </div>
  <div class="panel">
    <h3>Lokasi kantor desa</h3>
    <?php $koordinat = setting('lat') . ',' . setting('lng'); ?>
    <iframe class="peta-google" title="Peta lokasi kantor <?= e(setting('nama_desa')) ?> di Google Maps" src="https://maps.google.com/maps?q=<?= urlencode($koordinat) ?>&amp;z=15&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    <a class="btn btn-ghost btn-sm" style="margin-top:10px" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= urlencode($koordinat) ?>" target="_blank" rel="noopener">Petunjuk arah di Google Maps</a>
    <p class="hint">Titik peta adalah perkiraan. Petugas bisa menggesernya lewat CMS &gt; Pengaturan.</p>
  </div>
</div>

<div class="sec tint" id="aspirasi"><div class="wrap">
  <div class="head-row" style="align-items:flex-end">
    <div>
      <h2>Aspirasi, pertanyaan, dan pengaduan warga</h2>
      <p class="muted" style="margin:4px 0 0">Laporan bisa dilengkapi foto. Perkembangannya tampil terbuka di <a href="#papan">Papan Aspirasi</a> dan dikirim ke email atau WhatsApp kamu.</p>
    </div>
    <?php if ($jumlahAspirasi): ?><p class="small" style="margin:0"><b><?= $jumlahSelesai ?></b> dari <?= $jumlahAspirasi ?> aspirasi sudah selesai ditindaklanjuti</p><?php endif; ?>
  </div>

  <div class="split">
    <form class="panel" method="post" action="/kontak#aspirasi" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="aspirasi">
      <?php if ($errorAdu): ?>
        <div class="alert alert-err" role="alert"><b>Aspirasi belum terkirim:</b><ul><?php foreach ($errorAdu as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
      <div class="field"><label for="a-kategori">Kategori</label>
        <select id="a-kategori" name="kategori"><?php foreach ($kategoriAspirasi as $k): ?><option <?= $k === $adu['kategori'] ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?></select>
        <div class="hint">Ingin bertanya kepada petugas? Pilih <b>Pertanyaan umum</b>. Ingin meminta salinan dokumen desa, misalnya rincian anggaran? Pilih <b>Permohonan informasi publik</b>.</div>
      </div>
      <div class="field"><label for="a-lokasi">Lokasi (opsional)</label><input id="a-lokasi" name="lokasi" value="<?= e($adu['lokasi']) ?>" maxlength="150" placeholder="Contoh: jalan depan SD, RW 03"></div>
      <div class="field"><label for="a-isi">Ceritakan aspirasi atau masalahnya</label><textarea id="a-isi" name="isi" rows="5" maxlength="2000" required placeholder="Apa yang terjadi, sejak kapan, dan apa harapanmu"><?= e($adu['isi']) ?></textarea></div>
      <div class="field foto-lapor">
        <label for="a-foto">Foto pendukung (opsional, maksimal 3)</label>
        <input id="a-foto" name="foto[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
        <div class="hint">Foto jalan rusak, sampah menumpuk, atau lampu mati membantu petugas bertindak lebih cepat. Foto diperkecil otomatis dan data lokasi di dalam foto dihapus. Hindari memotret wajah orang.</div>
        <div class="foto-pratinjau" id="fotoPratinjau" aria-live="polite"></div>
      </div>
      <label class="cek-setuju"><input type="checkbox" name="anonim" value="1" id="a-anonim" <?= $anonim ? 'checked' : '' ?>> <span><b>Kirim tanpa nama.</b> Kamu cukup menyimpan kode lacak. Cocok kalau kamu khawatir identitasmu diketahui.</span></label>
      <div class="field" id="a-identitas"><label for="a-nama">Nama</label><input id="a-nama" name="nama" value="<?= e($anonim ? '' : $adu['nama']) ?>" maxlength="150">
        <div class="hint">Nama dan kontakmu hanya terlihat oleh petugas desa, tidak ditampilkan di website.</div></div>
      <div class="grid g2" style="gap:12px">
        <div class="field"><label for="a-email">Email</label><input id="a-email" name="email" type="email" value="<?= e($adu['email']) ?>" maxlength="150" placeholder="nama@gmail.com"></div>
        <div class="field"><label for="a-wa">WhatsApp</label><input id="a-wa" name="wa" value="<?= e($adu['wa']) ?>" inputmode="tel" placeholder="08xxxxxxxxxx"></div>
      </div>
      <p class="hint" style="margin-top:-6px">Isi salah satu, email atau WhatsApp, supaya kabar perkembangan bisa dikirim. Boleh dikosongkan kalau mengirim tanpa nama.</p>
      <label class="cek-setuju"><input type="checkbox" name="izin_publik" value="1" <?= $_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($_POST['izin_publik']) ? 'checked' : '' ?>> <span><b>Tampilkan di Papan Aspirasi</b> supaya warga lain bisa ikut memantau. Nama dan kontakmu tidak pernah ditampilkan, dan petugas memeriksa isinya dulu.</span></label>
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
              <ul class="jejak" aria-label="Riwayat laporan">
                <?php foreach (riwayat_aspirasi($hasilAdu) as $r): ?>
                  <li><b><?= e($r['status']) ?></b> <span class="muted"><?= tgl(substr((string) $r['dibuat_pada'], 0, 10)) ?></span></li>
                <?php endforeach; ?>
              </ul>
              <?php if ($hasilAdu['status'] === 'Selesai'): ?><?= survei_form('aspirasi', $hasilAdu['kode'], '/kontak#aspirasi', $errorSurveiAdu) ?><?php endif; ?>
              <?php if ($fotoLacak): ?><div class="foto-adu"><?php foreach ($fotoLacak as $fid): ?><a href="/foto-aspirasi?id=<?= $fid ?>&amp;k=<?= urlencode($hasilAdu['kode']) ?>" target="_blank"><img src="/foto-aspirasi?id=<?= $fid ?>&amp;k=<?= urlencode($hasilAdu['kode']) ?>" alt="Foto laporan" loading="lazy" width="96" height="72"></a><?php endforeach; ?></div><?php endif; ?>
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

    </div>
  </div>

  <?php if ($hitungPapan || $menungguPeriksa): ?>
  <section class="papan" id="papan" aria-labelledby="judul-papan">
    <div class="head-row" style="align-items:flex-end">
      <div>
        <h2 id="judul-papan">Papan Aspirasi</h2>
        <p class="muted" style="margin:4px 0 0;max-width:68ch">Semua orang bisa melihat laporan warga dan sampai mana tindak lanjutnya, tanpa perlu kode. Nama dan kontak pelapor tidak pernah ditampilkan.</p>
      </div>
    </div>
    <div class="papan-ringkas">
      <div><b><?= ($hitungPapan['Diterima'] ?? 0) + $menungguPeriksa ?></b><span>baru masuk</span></div>
      <div><b><?= $hitungPapan['Diproses'] ?? 0 ?></b><span>sedang diproses</span></div>
      <div><b><?= $hitungPapan['Selesai'] ?? 0 ?></b><span>selesai</span></div>
      <div><b><?= $rataHari !== null ? $rataHari . ' hari' : '-' ?></b><span>rata-rata sampai selesai</span></div>
      <?php $indeksAdu = survei_ringkas('aspirasi'); if ($indeksAdu['n']): ?><div><b><?= str_replace('.', ',', (string) $indeksAdu['rata']) ?> / 5</b><span>kepuasan pelapor (<?= $indeksAdu['n'] ?> penilaian)</span></div><?php endif; ?>
    </div>
    <?php if ($menungguPeriksa): ?><p class="small muted" style="margin:0 0 14px"><?= $menungguPeriksa ?> laporan baru sedang diperiksa petugas sebelum tampil di papan.</p><?php endif; ?>

    <nav class="chips" aria-label="Saring status">
      <a class="chip <?= $papanStatus === '' ? 'on' : '' ?>" href="<?= e($urlPapan(['papan' => '', 'hal' => 1])) ?>">Semua</a>
      <?php foreach (['Diterima', 'Diproses', 'Selesai'] as $s): ?>
        <a class="chip <?= $papanStatus === $s ? 'on' : '' ?>" href="<?= e($urlPapan(['papan' => $s, 'hal' => 1])) ?>"><?= $s ?></a>
      <?php endforeach; ?>
    </nav>
    <form method="get" action="/kontak#papan" class="papan-saring">
      <?php if ($papanStatus): ?><input type="hidden" name="papan" value="<?= e($papanStatus) ?>"><?php endif; ?>
      <label for="papan-kat">Kategori</label>
      <select id="papan-kat" name="kat" onchange="this.form.submit()">
        <option value="">Semua kategori</option>
        <?php foreach ($kategoriAspirasi as $k): ?><option <?= $papanKat === $k ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?>
      </select>
      <noscript><button class="btn btn-ghost btn-sm" type="submit">Terapkan</button></noscript>
    </form>

    <?php if (!$papan): ?>
      <div class="panel muted">Belum ada laporan <?= $papanStatus ? 'berstatus ' . e($papanStatus) : '' ?><?= $papanKat ? ' untuk kategori ' . e($papanKat) : '' ?>.</div>
    <?php endif; ?>
    <div class="papan-grid">
      <?php foreach ($papan as $a):
        $tahapP = $a['status'] === 'Ditolak' ? ['Diterima', 'Ditolak'] : ['Diterima', 'Diproses', 'Selesai'];
        $posP = (int) array_search($a['status'], $tahapP, true); ?>
        <article class="papan-kartu" id="adu-<?= (int) $a['id'] ?>">
          <div class="judul-status">
            <span class="tag"><?= e($a['kategori']) ?></span>
            <span class="status st-<?= e(['Diterima' => 'Menunggu', 'Diproses' => 'Dikonfirmasi', 'Selesai' => 'Selesai', 'Ditolak' => 'Dibatalkan'][$a['status']] ?? 'Menunggu') ?>"><?= e($a['status']) ?></span>
          </div>
          <p class="small muted" style="margin:8px 0 0"><?= $a['lokasi'] ? e($a['lokasi']) . ', ' : '' ?>dilaporkan <?= tgl(substr((string) $a['dibuat_pada'], 0, 10)) ?></p>
          <p class="papan-isi"><?= e(mb_strimwidth($a['isi'], 0, 220, '...')) ?></p>
          <?php if (!empty($fotoPapan[$a['id']])): ?>
            <div class="foto-adu"><?php foreach ($fotoPapan[$a['id']] as $fid): ?><a href="/foto-aspirasi?id=<?= $fid ?>" target="_blank"><img src="/foto-aspirasi?id=<?= $fid ?>" alt="Foto laporan <?= e($a['kategori']) ?>" loading="lazy" width="96" height="72"></a><?php endforeach; ?></div>
          <?php endif; ?>
          <ol class="steps steps-kecil" aria-label="Tahap tindak lanjut">
            <?php foreach ($tahapP as $i => $tp): ?><li class="<?= $i <= $posP ? 'done' : '' ?> <?= $tp === 'Ditolak' ? 'bad' : '' ?>"><?= $tp ?></li><?php endforeach; ?>
          </ol>
          <?php if ($a['tanggapan']): ?>
            <div class="tanggapan"><b>Tanggapan petugas<?= $a['diperbarui_pada'] ? ', ' . tgl(substr((string) $a['diperbarui_pada'], 0, 10)) : '' ?></b><p><?= nl2br(e($a['tanggapan'])) ?></p></div>
          <?php endif; ?>
          <form method="post" action="<?= e($urlPapan(['hal' => $papanHal])) ?>" class="dukung">
            <input type="hidden" name="aksi" value="dukung"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <div class="hp" aria-hidden="true"><label>Jangan diisi<input name="website" tabindex="-1" autocomplete="off"></label></div>
            <?php if ($a['status'] === 'Selesai'): ?>
              <?php if ((int) $a['dukungan']): ?><span class="small muted"><?= (int) $a['dukungan'] ?> warga ikut mengalami masalah ini</span><?php endif; ?>
            <?php else: ?>
              <button type="submit" class="btn btn-ghost btn-sm" aria-describedby="dk-<?= (int) $a['id'] ?>">Saya juga mengalami</button>
              <span class="small muted" id="dk-<?= (int) $a['id'] ?>"><?= (int) $a['dukungan'] ? (int) $a['dukungan'] . ' warga ikut mengalami' : 'Belum ada yang ikut mendukung' ?><?= $didukung === (int) $a['id'] ? '. Terima kasih, dukunganmu tercatat.' : '' ?></span>
            <?php endif; ?>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
    <?php $jmlHal = (int) ceil($papanTotal / $perHal); if ($jmlHal > 1): ?>
      <nav class="halaman" aria-label="Halaman papan aspirasi">
        <?php if ($papanHal > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($urlPapan(['hal' => $papanHal - 1])) ?>">Sebelumnya</a><?php endif; ?>
        <span class="small">Halaman <?= $papanHal ?> dari <?= $jmlHal ?></span>
        <?php if ($papanHal < $jmlHal): ?><a class="btn btn-ghost btn-sm" href="<?= e($urlPapan(['hal' => $papanHal + 1])) ?>">Berikutnya</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div></div>

<div class="sec" id="privasi"><div class="wrap">
  <h2>Privasi dan data pribadimu</h2>
  <p class="muted" style="max-width:70ch">Kami mengikuti Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi: data diambil secukupnya, dipakai sesuai tujuan, dan kamu berhak mengaturnya.</p>
  <div class="grid g3 privasi-poin">
    <div class="panel"><h3>Data yang kami simpan</h3><ul class="small">
      <li>Pengajuan surat dan izin kunjungan: nama, WhatsApp, email (opsional), dan keperluan.</li>
      <li>Aspirasi dan pertanyaan: nama dan kontak (boleh tanpa nama untuk aspirasi), serta foto laporan kalau dilampirkan.</li>
      <li>Relawan gotong royong: nama dan WhatsApp, dihapus 30 hari setelah kegiatan.</li>
      <li>Kami tidak meminta KTP, KK, atau NIK lewat website.</li></ul></div>
    <div class="panel"><h3>Untuk apa dan berapa lama</h3><ul class="small">
      <li>Hanya untuk mengurus surat, menjawab pertanyaan, dan menindaklanjuti aspirasi.</li>
      <li>Tidak dijual dan tidak dibagikan untuk iklan.</li>
      <li>Nama dan kontak dihapus otomatis <?= $hariSimpan ?> hari setelah urusan selesai. Angka statistik tanpa nama tetap disimpan.</li></ul></div>
    <div class="panel"><h3>Hakmu</h3><ul class="small">
      <li>Meminta salinan data pribadimu.</li>
      <li>Meminta data yang salah diperbaiki.</li>
      <li>Meminta data dihapus atau menarik persetujuan.</li>
      <li>Penanggung jawab: <?= e(setting('kontak_privasi')) ?>.</li></ul></div>
  </div>

  <div class="panel hak-data" style="margin-top:20px">
    <h3>Cara memakai hak atas data pribadimu</h3>
    <p>Kirim permintaanmu lewat <a href="<?= e(wa_link(setting('wa'), 'Halo petugas ' . setting('nama_desa') . ', saya ingin mengajukan permintaan data pribadi: (salinan / perbaikan / penghapusan / tarik persetujuan). Kode terkait: ')) ?>" target="_blank" rel="noopener">WhatsApp desa</a><?php if (setting('email') !== ''): ?> atau email <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a><?php endif; ?>. Sebutkan jenis permintaan (salinan, perbaikan, penghapusan, atau tarik persetujuan), nama yang dulu dipakai, dan kode terkait bila ada, misalnya kode surat SRT- atau aspirasi ADU-.</p>
    <p style="margin:0">Petugas mencocokkan data lewat nomor atau email yang sama dengan yang dulu kamu pakai, lalu menindaklanjutinya sesuai UU Pelindungan Data Pribadi dan mengabari hasilnya. Kamu juga bisa datang langsung ke kantor desa di jam layanan.</p>
  </div>
</div></div>
<?php
$scriptHalaman = '<script src="/assets/js/foto-lapor.js" defer></script>' . <<<'JS'
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
