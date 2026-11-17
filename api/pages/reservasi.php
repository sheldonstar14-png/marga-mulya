<?php
// =========================================================
// HALAMAN RESERVASI + CEK STATUS
//   GET  /reservasi?w=curug&t=2026-11-20  -> form terisi otomatis
//   POST /reservasi                       -> simpan reservasi baru
//   GET  /reservasi?baru=MM-XXXXXX        -> tampilkan tiket setelah berhasil
//   GET  /reservasi?cek=MM-XXXXXX         -> cek status
// =========================================================
$daftarWisata = q('SELECT id, slug, nama, jenis, harga, kuota, lat, lng, kode_etik FROM wisata WHERE aktif = 1 ORDER BY id');
$besok   = date('Y-m-d', strtotime('+1 day'));
$batasAkhir = date('Y-m-d', strtotime('+60 days'));
$errors  = [];

// Nilai awal form (dari alamat, atau dari isian sebelumnya kalau ada error)
$isi = [
    'wisata'  => $_GET['w'] ?? ($daftarWisata[0]['slug'] ?? ''),
    'tanggal' => (isset($_GET['t']) && $_GET['t'] >= $besok) ? $_GET['t'] : $besok,
    'jumlah'  => 2,
    'nama'    => '',
    'wa'      => '',
    'email'   => '',
    'kendaraan' => '',
    'jarak_km'  => '',
    'catatan' => '',
];

// ---------- Ulasan dari pengunjung yang sudah berkunjung ----------
$errorUlasan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ulasan') {
    $kodeUlasan = strtoupper(trim((string) ($_POST['kode'] ?? '')));
    $res = q1("SELECT id, wisata_id, nama FROM reservasi WHERE kode = ? AND status = 'Selesai'", [$kodeUlasan]);
    $nilaiBintang = (int) ($_POST['bintang'] ?? 0);
    $komentar = trim((string) ($_POST['komentar'] ?? ''));
    if (!$res) $errorUlasan = 'Ulasan hanya bisa diberikan untuk reservasi yang sudah selesai berkunjung.';
    elseif (q1('SELECT id FROM ulasan WHERE reservasi_id = ?', [$res['id']])) $errorUlasan = 'Reservasi ini sudah pernah memberi ulasan. Terima kasih!';
    elseif ($nilaiBintang < 1 || $nilaiBintang > 5) $errorUlasan = 'Pilih jumlah bintang 1 sampai 5.';
    elseif (mb_strlen($komentar) < 5 || mb_strlen($komentar) > 1000) $errorUlasan = 'Tulis ulasan 5 sampai 1.000 huruf.';
    if ($errorUlasan === '') {
        // Ulasan dengan kata kasar tidak langsung tampil, tetapi ditinjau petugas dulu
        $ditahan = ada_kata_terlarang($komentar);
        q('INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar, tampil) VALUES (?, ?, ?, ?, ?, ?)', [$res['id'], $res['wisata_id'], $res['nama'], $nilaiBintang, $komentar, $ditahan ? 0 : 1]);
        redirect('/reservasi?cek=' . urlencode($kodeUlasan) . '&ulasan=' . ($ditahan ? 'tinjau' : 'terima') . '#ulasan');
    }
    $_GET['cek'] = $kodeUlasan;
}

// ---------- Form dikirim ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') !== 'ulasan') {
    foreach ($isi as $kunci => $_) {
        $isi[$kunci] = trim((string) ($_POST[$kunci] ?? ''));
    }
    // Kolom jebakan bot: manusia tidak melihat kolom ini, jadi harus kosong
    if (!empty($_POST['website'])) {
        $errors[] = 'Reservasi tidak dapat diproses.';
    }

    $w = q1('SELECT id, nama, harga, kuota FROM wisata WHERE slug = ? AND aktif = 1', [$isi['wisata']]);
    $tglValid = DateTime::createFromFormat('Y-m-d', $isi['tanggal']);
    $jumlah = (int) $isi['jumlah'];
    $wa = preg_replace('/\D/', '', $isi['wa']);

    if (!$w) $errors[] = 'Pilih destinasi dari daftar.';
    if (!$tglValid || $tglValid->format('Y-m-d') !== $isi['tanggal']) $errors[] = 'Tanggal kunjungan tidak valid.';
    elseif ($isi['tanggal'] < $besok) $errors[] = 'Reservasi paling cepat untuk besok.';
    elseif ($isi['tanggal'] > $batasAkhir) $errors[] = 'Reservasi paling lambat 60 hari dari sekarang.';
    if ($jumlah < 1 || $jumlah > 50) $errors[] = 'Jumlah orang antara 1 sampai 50. Untuk rombongan lebih besar, hubungi desa lewat WhatsApp.';
    if (mb_strlen($isi['nama']) < 2 || mb_strlen($isi['nama']) > 150) $errors[] = 'Isi nama pemesan (2 sampai 150 huruf).';
    if (strlen($wa) < 10 || strlen($wa) > 15) $errors[] = 'Nomor WhatsApp 10 sampai 15 digit, contoh 081234567890.';
    if (!filter_var($isi['email'], FILTER_VALIDATE_EMAIL) || strlen($isi['email']) > 150) $errors[] = 'Isi alamat email yang benar, contoh nama@gmail.com. Kabar reservasi dikirim ke email ini.';
    if (!isset(KENDARAAN[$isi['kendaraan']])) $errors[] = 'Pilih kendaraan yang akan dipakai menuju desa.';
    $jarak = (int) $isi['jarak_km'];
    if (!ctype_digit($isi['jarak_km']) || $jarak < 1 || $jarak > 2000) $errors[] = 'Isi perkiraan jarak dari rumah ke desa dalam kilometer (1 sampai 2.000).';
    if (mb_strlen($isi['catatan']) > 255) $errors[] = 'Catatan maksimal 255 huruf.';
    if (empty($_POST['setuju'])) $errors[] = 'Centang persetujuan kode etik pengunjung sebelum mengirim.';
    if (empty($_POST['setuju_data'])) $errors[] = 'Centang persetujuan penggunaan data pribadi untuk mengurus reservasi.';

    if (!$errors) {
        $sisa = $w['kuota'] - terpakai($w['id'], $isi['tanggal']);
        if ($jumlah > $sisa) {
            $errors[] = "Kuota {$w['nama']} pada " . tgl($isi['tanggal']) . " tinggal {$sisa} orang. Kurangi jumlah orang atau pilih tanggal lain.";
        }
    }

    if (!$errors) {
        $kode = kode_reservasi_baru();
        q(
            'INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, email, kendaraan, jarak_km, emisi_kg, catatan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$kode, $w['id'], $isi['tanggal'], $jumlah, $isi['nama'], $wa, $isi['email'], $isi['kendaraan'], $jarak, emisi_kunjungan($isi['kendaraan'], $jarak, $jumlah), $isi['catatan'], 'Menunggu']
        );
        // Tiket Hijau: pengunjung berskor hijau mendapat kupon belanja UMKM (aktif setelah dikonfirmasi)
        if (berhak_tiket_hijau(emisi_kunjungan($isi['kendaraan'], $jarak, $jumlah), $jumlah, $isi['kendaraan'])) {
            q('UPDATE reservasi SET kupon = ? WHERE kode = ?', ['HIJAU-' . substr($kode, 3), $kode]);
        }
        // Email ke pengunjung dan ke desa (kalau gagal, reservasi tetap tersimpan)
        require_once __DIR__ . '/../inc/email.php';
        $terkirim = email_reservasi($kode) === true;
        email_admin_reservasi_baru($kode);
        // Pindah ke halaman tiket, supaya reservasi tidak terkirim dua kali kalau halaman dimuat ulang
        redirect('/reservasi?baru=' . urlencode($kode) . '&surel=' . ($terkirim ? '1' : '0'));
    }
}

// ---------- Tiket baru dan cek status ----------
$sqlDetail = 'SELECT r.*, w.nama AS nama_wisata, w.harga, w.gmaps_ulasan FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.kode = ?';
$tiket = isset($_GET['baru']) ? q1($sqlDetail, [strtoupper(trim($_GET['baru']))]) : null;
$kodeCek = strtoupper(trim($_GET['cek'] ?? ($tiket['kode'] ?? '')));
$hasilCek = $kodeCek !== '' && isset($_GET['cek']) ? q1($sqlDetail, [$kodeCek]) : null;

$keteranganStatus = [
    'Menunggu'     => 'Pengelola akan menghubungi lewat WhatsApp paling lambat 1x24 jam.',
    'Dikonfirmasi' => 'Reservasi disetujui. Tunjukkan kode ini di loket.',
    'Selesai'      => 'Terima kasih sudah berkunjung.',
    'Dibatalkan'   => 'Reservasi tidak dapat diterima. Hubungi desa bila ada pertanyaan.',
];

// Kotak jejak karbon perjalanan untuk tiket dan cek status
function kotak_emisi(array $r, bool $gelap): string
{
    if ($r['kendaraan'] === '' || (int) $r['jarak_km'] <= 0) return '';
    $total = (float) $r['emisi_kg'];
    $perOrang = $total / max(1, (int) $r['jumlah']);
    [$huruf, $label] = skor_hijau($perOrang);
    $bus = emisi_kunjungan('Bus atau elf rombongan', (int) $r['jarak_km'], (int) $r['jumlah']);
    $saran = match (true) {
        $r['kendaraan'] === 'Sepeda atau jalan kaki' => 'Perjalananmu tanpa emisi. Terima kasih sudah memberi contoh!',
        $huruf <= 'B' => 'Pilihan perjalanan yang hemat emisi. Bawa botol minum sendiri supaya kunjunganmu makin hijau.',
        $bus < $total * 0.7 => 'Naik bus atau elf bersama bisa memangkas sekitar ' . angka(round($total - $bus)) . ' kg. Ajak teman berbagi kendaraan.',
        default => 'Berbagi kendaraan dan membawa botol minum sendiri membantu menjaga desa tetap lestari.',
    };
    return '<div class="eco' . ($gelap ? ' eco-gelap' : '') . '"><span class="eco-skor skor-' . $huruf . '" aria-label="Skor hijau ' . $huruf . '">' . $huruf . '</span>'
        . '<div><b>Jejak karbon perjalanan: ' . angka(round($total, 1)) . ' kg CO2e</b> <span class="small">(' . angka(round($perOrang, 1)) . ' kg per orang, ' . $label . ')</span>'
        . '<br><span class="small">' . e($r['kendaraan']) . ', ' . (int) $r['jarak_km'] . ' km sekali jalan, dihitung pulang pergi. ' . e($saran) . '</span></div></div>';
}

// Kotak Tiket Hijau di tiket dan cek status
function kotak_kupon(array $r): string
{
    if (empty($r['kupon'])) return '';
    $status = $r['kupon_dipakai_pada'] ? 'Sudah dipakai di ' . $r['kupon_umkm'] : ($r['status'] === 'Menunggu' ? 'Aktif setelah reservasi dikonfirmasi' : 'Tunjukkan ke penjual UMKM desa');
    return '<div class="kupon"><div class="kupon-atas"><span class="kupon-label">Tiket Hijau</span><b>' . e($r['kupon']) . '</b></div>'
        . '<p class="small" style="margin:6px 0">Terima kasih memilih perjalanan rendah emisi. ' . e(setting('tiket_hijau_syarat')) . '</p>'
        . '<div class="qr-box qr-terang"><div class="qr" data-qr="' . e(url_dasar() . '/umkm?kupon=' . urlencode($r['kupon'])) . '"></div>'
        . '<span class="small"><b>' . e($status) . '.</b> Penjual memindai kode ini untuk menukar kupon.</span></div></div>';
}

$judul = 'Reservasi - ' . setting('nama_desa');
$menu  = 'reservasi';
$pakaiCuaca = true;
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Reservasi kunjungan</h1>
  <p>Pilih destinasi dan tanggal. Prakiraan cuaca dan sisa kuota langsung muncul. Pembayaran dilakukan di lokasi.</p>
</div>

<div class="wrap sec split" style="padding-top:16px">
  <form class="panel" method="post" action="/reservasi" id="formReservasi">
    <?php if ($errors): ?>
      <div class="alert alert-err" role="alert"><b>Reservasi belum terkirim:</b><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="field">
      <label for="wisata">Destinasi</label>
      <select id="wisata" name="wisata">
        <?php foreach ($daftarWisata as $w): ?>
          <option value="<?= e($w['slug']) ?>" <?= $w['slug'] === $isi['wisata'] ? 'selected' : '' ?>
            data-lat="<?= e($w['lat']) ?>" data-lng="<?= e($w['lng']) ?>" data-jenis="<?= e($w['jenis']) ?>" data-nama="<?= e($w['nama']) ?>" data-harga="<?= (int) $w['harga'] ?>">
            <?= e($w['nama']) ?> (<?= rupiah($w['harga']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="grid g2" style="gap:12px">
      <div class="field">
        <label for="tanggal">Tanggal kunjungan</label>
        <input type="date" id="tanggal" name="tanggal" value="<?= e($isi['tanggal']) ?>" min="<?= $besok ?>" max="<?= $batasAkhir ?>" required>
      </div>
      <div class="field">
        <label for="jumlah">Jumlah orang</label>
        <input type="number" id="jumlah" name="jumlah" value="<?= e($isi['jumlah']) ?>" min="1" max="50" required>
      </div>
    </div>
    <div id="infoCuaca"></div>
    <p class="small" id="infoKuota"></p>

    <div class="field">
      <label for="nama">Nama pemesan</label>
      <input id="nama" name="nama" value="<?= e($isi['nama']) ?>" autocomplete="name" maxlength="150" required>
    </div>
    <div class="field">
      <label for="wa">Nomor WhatsApp</label>
      <input id="wa" name="wa" value="<?= e($isi['wa']) ?>" inputmode="tel" placeholder="08xxxxxxxxxx" required>
      <div class="hint">Konfirmasi dari pengelola dikirim lewat WhatsApp.</div>
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= e($isi['email']) ?>" autocomplete="email" maxlength="150" placeholder="nama@gmail.com" required>
      <div class="hint">Tiket, konfirmasi, dan setiap perubahan status dikirim ke email ini.</div>
    </div>
    <fieldset class="field perjalanan">
      <legend>Perjalanan ke desa</legend>
      <div class="grid g2" style="gap:12px">
        <div><label for="kendaraan">Kendaraan</label>
          <select id="kendaraan" name="kendaraan" required data-faktor="<?= e(json_encode(KENDARAAN)) ?>" data-hijau="<?= setting('tiket_hijau_aktif') === '1' ? e(strtoupper(setting('tiket_hijau_skor'))) : '' ?>" data-hadiah="<?= e(rupiah((int) setting('tiket_hijau_nilai'))) ?>">
            <option value="">Pilih kendaraan</option>
            <?php foreach (array_keys(KENDARAAN) as $k): ?><option <?= $isi['kendaraan'] === $k ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?>
          </select></div>
        <div><label for="jarak_km">Jarak dari rumah (km)</label>
          <input id="jarak_km" name="jarak_km" type="number" min="1" max="2000" inputmode="numeric" value="<?= e($isi['jarak_km']) ?>" placeholder="Contoh: 40" required></div>
      </div>
      <div id="infoEmisi" class="hint" aria-live="polite">Kami menghitung perkiraan jejak karbon perjalananmu untuk memantau dampak wisata terhadap lingkungan desa.</div>
    </fieldset>
    <div class="field">
      <label for="catatan">Catatan (opsional)</label>
      <textarea id="catatan" name="catatan" rows="2" maxlength="255" placeholder="Contoh: rombongan sekolah, butuh pemandu"><?= e($isi['catatan']) ?></textarea>
    </div>
    <div class="hp" aria-hidden="true"><label for="website">Jangan diisi</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>

    <?php foreach ($daftarWisata as $w): $aturan = kode_etik($w); if (!$aturan) continue; ?>
      <div class="etik-box kode-etik" data-wisata="<?= e($w['slug']) ?>" <?= $w['slug'] === $isi['wisata'] ? '' : 'hidden' ?>>
        <h3 style="font-size:1.05rem">Kode etik pengunjung <?= e($w['nama']) ?></h3>
        <ul class="etik small"><?php foreach ($aturan as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endforeach; ?>
    <div class="field">
      <label style="display:flex;gap:10px;align-items:flex-start;font-weight:400">
        <input type="checkbox" name="setuju" value="1" required style="width:22px;height:22px;flex:none;margin-top:2px" <?= !empty($_POST['setuju']) ? 'checked' : '' ?>>
        <span>Saya sudah membaca dan akan mematuhi kode etik pengunjung, termasuk menjaga kebersihan dan kelestarian alam desa.</span>
      </label>
      <label class="cek-setuju"><input type="checkbox" name="setuju_data" value="1" required <?= !empty($_POST['setuju_data']) ? 'checked' : '' ?>>
        <span>Saya setuju nama, kontak, dan data perjalanan dipakai untuk mengurus reservasi ini. Data dihapus otomatis <?= max(30, (int) setting('simpan_data_hari')) ?> hari setelah kunjungan. <a href="/kontak#privasi" target="_blank" rel="noopener">Baca kebijakan privasi</a></span></label>
    </div>

    <div class="card-foot" style="padding-top:0">
      <span>Total di lokasi: <b class="price" id="total">-</b></span>
      <button class="btn btn-main" type="submit">Kirim reservasi</button>
    </div>
  </form>

  <div>
    <?php if ($tiket): ?>
      <div class="ticket" role="status">
        <span class="small">Reservasi terkirim. Simpan kode ini untuk cek status:</span>
        <div class="code"><?= e($tiket['kode']) ?></div>
        <dl>
          <dt>Destinasi</dt><dd><?= e($tiket['nama_wisata']) ?></dd>
          <dt>Tanggal</dt><dd><?= tgl($tiket['tanggal'], 'hari') ?></dd>
          <dt>Jumlah</dt><dd><?= (int) $tiket['jumlah'] ?> orang</dd>
          <dt>Bayar di lokasi</dt><dd><?= rupiah($tiket['harga'] * $tiket['jumlah']) ?></dd>
          <dt>Status</dt><dd><?= e($tiket['status']) ?></dd>
        </dl>
        <p class="small" style="margin:14px 0 0;color:#C9D3C3">
          <?= ($_GET['surel'] ?? '') === '1' ? 'Rincian tiket juga sudah dikirim ke ' . e($tiket['email']) . '. Cek folder Spam kalau belum masuk.' : 'Simpan kode ini. Email konfirmasi belum bisa dikirim, tapi reservasimu sudah tersimpan.' ?>
        </p>
        <?= kotak_emisi($tiket, true) ?>
        <?= kotak_kupon($tiket) ?>
        <div class="qr-box"><div class="qr" data-qr="<?= e(url_dasar() . '/admin/checkin?kode=' . urlencode($tiket['kode'])) ?>"></div>
          <span class="small">Tunjukkan kode QR ini ke petugas loket saat tiba.</span></div>
      </div>
      <?php if (isset($_GET['surel'])): $pengirim = (string) getenv('GMAIL_USER'); ?>
      <dialog class="dialog-surel" id="dialogSurel" aria-labelledby="judulSurel">
        <div class="isi">
          <span class="dialog-ikon" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></span>
          <?php if ($_GET['surel'] === '1'): ?>
            <h2 id="judulSurel">Reservasi terkirim ke email</h2>
            <p>Rincian tiket <b><?= e($tiket['kode']) ?></b> sudah dikirim ke <b><?= e($tiket['email']) ?></b>.</p>
            <p style="margin-bottom:4px"><b>Belum masuk dalam 5 menit?</b></p>
            <ol class="small">
              <li>Buka folder <b>Spam</b> atau tab <b>Promosi</b> di Gmail.</li>
              <li>Kalau ketemu, tekan <b>Bukan spam</b> supaya email berikutnya masuk ke Kotak Masuk.</li>
              <?php if ($pengirim !== ''): ?><li>Simpan <b><?= e($pengirim) ?></b> ke kontak.</li><?php endif; ?>
            </ol>
          <?php else: ?>
            <h2 id="judulSurel">Reservasi tersimpan</h2>
            <p>Email konfirmasi belum bisa dikirim, tapi reservasimu sudah tercatat. Simpan kode <b><?= e($tiket['kode']) ?></b> atau foto layar ini.</p>
          <?php endif; ?>
          <p class="small muted">Status bisa dicek kapan saja di halaman ini dengan kode reservasi.</p>
          <div class="actions">
            <button type="button" class="btn btn-main" data-tutup>Mengerti</button>
            <button type="button" class="btn btn-ghost" data-salin="<?= e($tiket['kode']) ?>">Salin kode</button>
          </div>
        </div>
      </dialog>
      <?php endif; ?>
    <?php endif; ?>

    <div class="panel" style="margin-top:20px">
      <h3>Cek status reservasi</h3>
      <form method="get" action="/reservasi" class="grid" style="grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end">
        <div><label for="cek">Kode reservasi</label><input id="cek" name="cek" value="<?= e($kodeCek) ?>" placeholder="Contoh: MM-0427"></div>
        <button class="btn btn-dark" type="submit">Cek</button>
      </form>

      <?php if ($errorUlasan !== '' && !($hasilCek && $hasilCek['status'] === 'Selesai' && !q1('SELECT id FROM ulasan WHERE reservasi_id = ?', [$hasilCek['id']]))): ?>
        <div class="alert alert-err" role="alert" style="margin-top:14px"><?= e($errorUlasan) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['cek']) && $kodeCek !== ''): ?>
        <?php if ($hasilCek):
          $urutan = ['Menunggu', 'Dikonfirmasi', 'Selesai'];
          $posisi = array_search($hasilCek['status'], $urutan, true); ?>
          <div style="margin-top:16px">
            <b><?= e($hasilCek['kode']) ?></b>, <?= e($hasilCek['nama_wisata']) ?><br>
            <span class="small muted"><?= tgl($hasilCek['tanggal'], 'hari') ?>, <?= (int) $hasilCek['jumlah'] ?> orang, atas nama <?= e($hasilCek['nama']) ?></span>
            <ol class="steps">
              <?php if ($hasilCek['status'] === 'Dibatalkan'): ?>
                <li class="done">Menunggu</li><li class="done bad">Dibatalkan</li>
              <?php else: foreach ($urutan as $i => $s): ?>
                <li class="<?= $i <= $posisi ? 'done' : '' ?>"><?= $s ?></li>
              <?php endforeach; endif; ?>
            </ol>
            <span class="small"><?= e($keteranganStatus[$hasilCek['status']] ?? '') ?></span>
            <?= $hasilCek['status'] !== 'Dibatalkan' ? kotak_emisi($hasilCek, false) . kotak_kupon($hasilCek) : '' ?>

            <?php if (in_array($hasilCek['status'], ['Menunggu', 'Dikonfirmasi'], true)): ?>
              <div class="qr-box qr-terang"><div class="qr" data-qr="<?= e(url_dasar() . '/admin/checkin?kode=' . urlencode($hasilCek['kode'])) ?>"></div>
                <span class="small">Kode QR untuk check-in di loket.</span></div>
            <?php endif; ?>

            <?php if ($hasilCek['status'] === 'Selesai'):
              $ulasanAda = q1('SELECT bintang, komentar FROM ulasan WHERE reservasi_id = ?', [$hasilCek['id']]); ?>
              <div id="ulasan" class="etik-box">
                <?php if (($_GET['ulasan'] ?? '') === 'terima'): ?><div class="alert alert-ok" role="status">Terima kasih, ulasanmu sudah tersimpan.</div><?php endif; ?>
                <?php if (($_GET['ulasan'] ?? '') === 'tinjau'): ?><div class="alert alert-ok" role="status">Terima kasih. Ulasanmu akan tampil setelah ditinjau petugas, karena ada kata yang perlu diperiksa.</div><?php endif; ?>
                <?php if ($ulasanAda): ?>
                  <h3 style="font-size:1.05rem">Ulasanmu</h3>
                  <p style="margin:0"><span class="bintang" aria-label="<?= (int) $ulasanAda['bintang'] ?> dari 5 bintang"><?= bintang($ulasanAda['bintang']) ?></span><br><?= e($ulasanAda['komentar']) ?></p>
                <?php else: ?>
                  <h3 style="font-size:1.05rem">Bagaimana kunjunganmu?</h3>
                  <?php if ($errorUlasan): ?><div class="alert alert-err" role="alert"><?= e($errorUlasan) ?></div><?php endif; ?>
                  <form method="post" action="/reservasi">
                    <input type="hidden" name="aksi" value="ulasan">
                    <input type="hidden" name="kode" value="<?= e($hasilCek['kode']) ?>">
                    <fieldset class="pilih-bintang" style="border:0;padding:0;margin:0 0 12px">
                      <legend class="small" style="margin-bottom:6px">Beri bintang</legend>
                      <?php for ($b = 5; $b >= 1; $b--): ?>
                        <input type="radio" id="b<?= $b ?>" name="bintang" value="<?= $b ?>" <?= (int) ($_POST['bintang'] ?? 0) === $b ? 'checked' : '' ?> required><label for="b<?= $b ?>" title="<?= $b ?> bintang">★</label>
                      <?php endfor; ?>
                    </fieldset>
                    <div class="field"><label for="komentar">Ceritakan pengalamanmu</label><div class="hint" style="margin:-2px 0 6px">Nama depanmu akan tampil bersama ulasan.</div><textarea id="komentar" name="komentar" rows="3" maxlength="1000" required><?= e($_POST['komentar'] ?? '') ?></textarea></div>
                    <button class="btn btn-main btn-sm" type="submit">Kirim ulasan</button>
                  </form>
                <?php endif; ?>
                <?php if ($hasilCek['gmaps_ulasan']): ?>
                  <p class="small" style="margin:12px 0 0">Mau membantu lebih banyak orang? <a href="<?= e($hasilCek['gmaps_ulasan']) ?>" target="_blank" rel="noopener">Beri ulasan juga di Google Maps</a>.</p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <p class="err">Kode <?= e($kodeCek) ?> tidak ditemukan. Periksa lagi penulisannya.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
// JavaScript halaman ini: cuaca di tanggal terpilih, sisa kuota, dan total harga
$scriptHalaman = <<<'JS'
<script>
// Pop-up setelah reservasi: kabar email terkirim dan cara cek folder Spam
(function () {
  const d = document.getElementById('dialogSurel');
  if (!d || typeof d.showModal !== 'function') return;
  d.showModal();
  d.querySelector('[data-tutup]').addEventListener('click', () => d.close());
  const salin = d.querySelector('[data-salin]');
  salin.addEventListener('click', () => {
    navigator.clipboard && navigator.clipboard.writeText(salin.dataset.salin).then(() => { salin.textContent = 'Kode disalin'; });
  });
  d.addEventListener('click', e => { if (e.target === d) d.close(); });
})();
</script>
<script>
(function () {
  const pilih = document.getElementById('wisata'), tanggal = document.getElementById('tanggal'), jumlah = document.getElementById('jumlah');
  const infoCuacaEl = document.getElementById('infoCuaca'), infoKuota = document.getElementById('infoKuota'), total = document.getElementById('total');
  const rupiah = n => 'Rp' + n.toLocaleString('id-ID');

  function perbarui() {
    const opsi = pilih.selectedOptions[0];
    if (!opsi) return;
    total.textContent = rupiah(Number(opsi.dataset.harga) * (Number(jumlah.value) || 0));
    if (!tanggal.value) return;

    // Sisa kuota dari server
    fetch('/kuota?w=' + encodeURIComponent(pilih.value) + '&t=' + tanggal.value)
      .then(r => r.json())
      .then(d => {
        if (d.error) { infoKuota.textContent = d.error; return; }
        infoKuota.innerHTML = 'Sisa kuota tanggal ini: <span class="quota' + (d.sisa < 10 ? ' low' : '') + '">' + d.sisa + ' dari ' + d.kuota + ' orang</span>';
      })
      .catch(() => { infoKuota.textContent = ''; });

    // Prakiraan cuaca di lokasi destinasi
    ambilCuaca(opsi.dataset.lat, opsi.dataset.lng).then(data => {
      const h = data.hari.find(x => x.tgl === tanggal.value);
      if (!h) { infoCuacaEl.innerHTML = '<div class="fdate">Prakiraan cuaca untuk tanggal ini baru tersedia paling cepat 16 hari sebelumnya.</div>'; return; }
      const [nama, jenis] = infoCuaca(h.kode);
      infoCuacaEl.innerHTML = '<div class="fdate">' + ikonCuaca(jenis) + '<div><b>' + nama + ', ' + h.min + '-' + h.maks + '°C, peluang hujan ' + h.hujan + '%</b><br><span class="small">' + saranCuaca(h, opsi.dataset.jenis, opsi.dataset.nama) + '</span></div></div>';
    }).catch(() => { infoCuacaEl.innerHTML = ''; });
  }

  // Tampilkan kode etik destinasi yang dipilih
  pilih.addEventListener('input', () => {
    document.querySelectorAll('.kode-etik').forEach(k => { k.hidden = k.dataset.wisata !== pilih.value; });
  });

  [pilih, tanggal, jumlah].forEach(el => el.addEventListener('input', perbarui));
  perbarui();

  // Perkiraan jejak karbon langsung saat kendaraan dan jarak diisi (rumus sama dengan server)
  const kendaraan = document.getElementById('kendaraan'), jarak = document.getElementById('jarak_km'), infoEmisi = document.getElementById('infoEmisi');
  const faktor = JSON.parse(kendaraan.dataset.faktor);
  const awalEmisi = infoEmisi.textContent;
  function hitungEmisi(nama, km, orang) {
    const k = faktor[nama];
    if (!k) return 0;
    return k.per === 'kendaraan' ? k.faktor * km * 2 * Math.ceil(orang / k.isi) : k.faktor * km * 2 * orang;
  }
  function perbaruiEmisi() {
    const km = Number(jarak.value), orang = Number(jumlah.value) || 1;
    if (!kendaraan.value || !km) { infoEmisi.textContent = awalEmisi; return; }
    const total = hitungEmisi(kendaraan.value, km, orang);
    const bus = hitungEmisi('Bus atau elf rombongan', km, orang);
    let teks = 'Perkiraan jejak karbon pulang pergi: <b>' + total.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' kg CO2e</b> (' + (total / orang).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' kg per orang).';
    if (bus < total * 0.7) teks += ' Dengan bus atau elf bersama, emisinya sekitar ' + bus.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' kg.';
    if (total === 0) teks = 'Perjalanan tanpa emisi. Keren!';
    const per = total / orang, huruf = per <= 2 ? 'A' : per <= 5 ? 'B' : per <= 10 ? 'C' : per <= 20 ? 'D' : 'E';
    const hijau = (kendaraan.dataset.hijau || '').split(',').map(x => x.trim()).filter(Boolean);
    if (hijau.length) {
      teks += hijau.includes(huruf)
        ? ' <span class="eco-hadiah">Skor ' + huruf + ': kamu mendapat Tiket Hijau, kupon belanja ' + kendaraan.dataset.hadiah + ' di UMKM desa.</span>'
        : ' <span class="small">Skor ' + huruf + '. Datang dengan bus, angkutan umum, kereta, atau berbagi kendaraan untuk mendapat Tiket Hijau.</span>';
    }
    infoEmisi.innerHTML = teks;
  }
  [kendaraan, jarak, jumlah].forEach(el => el.addEventListener('input', perbaruiEmisi));
  perbaruiEmisi();
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
<script>
// Gambar kode QR untuk check-in. Kalau library gagal dimuat, kode reservasi tetap bisa dibacakan.
document.querySelectorAll('[data-qr]').forEach(el => {
  if (typeof qrcode === 'undefined') { el.closest('.qr-box').hidden = true; return; }
  const qr = qrcode(0, 'M');
  qr.addData(el.dataset.qr);
  qr.make();
  el.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
});
</script>
JS;
require __DIR__ . '/../inc/footer.php';
?>
