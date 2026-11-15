<?php
// =========================================================
// HALAMAN RESERVASI + CEK STATUS
//   GET  /reservasi?w=curug&t=2026-11-20  -> form terisi otomatis
//   POST /reservasi                       -> simpan reservasi baru
//   GET  /reservasi?baru=MM-XXXXXX        -> tampilkan tiket setelah berhasil
//   GET  /reservasi?cek=MM-XXXXXX         -> cek status
// =========================================================
$daftarWisata = q('SELECT id, slug, nama, jenis, harga, kuota, lat, lng FROM wisata WHERE aktif = 1 ORDER BY id');
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
    'catatan' => '',
];

// ---------- Form dikirim ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    if (mb_strlen($isi['catatan']) > 255) $errors[] = 'Catatan maksimal 255 huruf.';

    if (!$errors) {
        $sisa = $w['kuota'] - terpakai($w['id'], $isi['tanggal']);
        if ($jumlah > $sisa) {
            $errors[] = "Kuota {$w['nama']} pada " . tgl($isi['tanggal']) . " tinggal {$sisa} orang. Kurangi jumlah orang atau pilih tanggal lain.";
        }
    }

    if (!$errors) {
        $kode = kode_reservasi_baru();
        q(
            'INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$kode, $w['id'], $isi['tanggal'], $jumlah, $isi['nama'], $wa, $isi['catatan'], 'Menunggu']
        );
        // Pindah ke halaman tiket, supaya reservasi tidak terkirim dua kali kalau halaman dimuat ulang
        redirect('/reservasi?baru=' . urlencode($kode));
    }
}

// ---------- Tiket baru dan cek status ----------
$sqlDetail = 'SELECT r.*, w.nama AS nama_wisata, w.harga FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.kode = ?';
$tiket = isset($_GET['baru']) ? q1($sqlDetail, [strtoupper(trim($_GET['baru']))]) : null;
$kodeCek = strtoupper(trim($_GET['cek'] ?? ($tiket['kode'] ?? '')));
$hasilCek = $kodeCek !== '' && isset($_GET['cek']) ? q1($sqlDetail, [$kodeCek]) : null;

$keteranganStatus = [
    'Menunggu'     => 'Pengelola akan menghubungi lewat WhatsApp paling lambat 1x24 jam.',
    'Dikonfirmasi' => 'Reservasi disetujui. Tunjukkan kode ini di loket.',
    'Selesai'      => 'Terima kasih sudah berkunjung.',
    'Dibatalkan'   => 'Reservasi tidak dapat diterima. Hubungi desa bila ada pertanyaan.',
];

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
      <label for="catatan">Catatan (opsional)</label>
      <textarea id="catatan" name="catatan" rows="2" maxlength="255" placeholder="Contoh: rombongan sekolah, butuh pemandu"><?= e($isi['catatan']) ?></textarea>
    </div>
    <div class="hp" aria-hidden="true"><label for="website">Jangan diisi</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>

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
      </div>
    <?php endif; ?>

    <div class="panel" style="margin-top:20px">
      <h3>Cek status reservasi</h3>
      <form method="get" action="/reservasi" class="grid" style="grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end">
        <div><label for="cek">Kode reservasi</label><input id="cek" name="cek" value="<?= e($kodeCek) ?>" placeholder="Contoh: MM-0427"></div>
        <button class="btn btn-dark" type="submit">Cek</button>
      </form>

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
      infoCuacaEl.innerHTML = '<div class="fdate">' + ikonCuaca(jenis) + '<div><b>' + nama + ', ' + h.min + '-' + h.maks + '°C, peluang hujan ' + h.hujan + '%</b><br><span class="small">' + saranCuaca(h.hujan, opsi.dataset.jenis, opsi.dataset.nama) + '</span></div></div>';
    }).catch(() => { infoCuacaEl.innerHTML = ''; });
  }

  [pilih, tanggal, jumlah].forEach(el => el.addEventListener('input', perbarui));
  perbarui();
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php';
?>
