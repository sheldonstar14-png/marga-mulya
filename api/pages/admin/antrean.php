<?php
// =========================================================
// ANTREAN KANTOR DESA
// Warga memindai QR di meja layanan, lalu mendapat nomor di HP-nya.
// Petugas memanggil nomor berikutnya dari halaman ini. QR bisa dicetak sebagai poster.
// =========================================================
$hariIni = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = (string) ($_POST['aksi'] ?? '');
    if ($aksi === 'panggil') {
        // Nomor yang sedang dipanggil dianggap selesai, lalu panggil nomor menunggu paling kecil
        q("UPDATE antrean SET status = 'Selesai' WHERE tanggal = ? AND status = 'Dipanggil'", [$hariIni]);
        $berikut = q1("SELECT id, nomor FROM antrean WHERE tanggal = ? AND status = 'Menunggu' ORDER BY nomor LIMIT 1", [$hariIni]);
        if (!$berikut) redirect('/admin/antrean?ok=' . urlencode('Tidak ada lagi yang menunggu.'));
        q("UPDATE antrean SET status = 'Dipanggil', dipanggil_pada = CURRENT_TIMESTAMP WHERE id = ?", [$berikut['id']]);
        redirect('/admin/antrean?ok=' . urlencode('Memanggil nomor ' . sprintf('%03d', $berikut['nomor']) . '.'));
    }
    if (in_array($aksi, ['selesai', 'lewati'], true)) {
        q('UPDATE antrean SET status = ? WHERE id = ? AND tanggal = ?', [$aksi === 'selesai' ? 'Selesai' : 'Dilewati', (int) ($_POST['id'] ?? 0), $hariIni]);
        redirect('/admin/antrean');
    }
    if ($aksi === 'ganti-kode') {
        kode_antrean(true);
        redirect('/admin/antrean?ok=' . urlencode('Kode QR diganti. Cetak ulang poster QR; QR lama tidak berlaku lagi.'));
    }
    redirect('/admin/antrean');
}

$antre = q('SELECT * FROM antrean WHERE tanggal = ? ORDER BY nomor', [$hariIni]);
$kini = null;
foreach ($antre as $a) if ($a['status'] === 'Dipanggil') $kini = $a;
$menunggu = array_values(array_filter($antre, fn($a) => $a['status'] === 'Menunggu'));
$alamatQr = url_dasar() . '/data-desa?grup=Layanan+surat&antre=' . kode_antrean() . '#antrean';
$judulAdmin = 'Antrean hari ini';
$menuAdmin = 'surat';
$pakaiQr = true;
require __DIR__ . '/../../inc/admin-header.php';
?>
<div class="head-row"><h2>Antrean hari ini, <?= tgl($hariIni, 'hari') ?></h2><a class="btn btn-ghost btn-sm" href="/admin/surat">Pengajuan surat</a></div>
<div class="split">
  <div>
    <div class="panel antre-box">
      <div class="antre-kini"><span>Sedang dilayani</span><b><?= $kini ? sprintf('%03d', $kini['nomor']) : '-' ?></b><small><?= $kini ? e($kini['keperluan']) . ($kini['nama'] ? ', ' . e($kini['nama']) : '') : 'belum ada' ?></small></div>
      <form method="post" action="/admin/antrean">
        <?= csrf_field() ?>
        <p style="margin:0 0 12px"><b><?= count($menunggu) ?></b> orang menunggu.</p>
        <button class="btn btn-main" name="aksi" value="panggil" style="min-width:220px">Panggil nomor berikutnya</button>
        <p class="hint">Tombol ini menandai nomor sekarang selesai, lalu memanggil nomor berikutnya. Layar warga berganti dalam 30 detik.</p>
      </form>
    </div>
    <div class="panel table-wrap" style="margin-top:14px">
      <table class="stack">
        <thead><tr><th>No.</th><th>Keperluan</th><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($antre as $a): ?>
            <tr>
              <td data-l="No."><b><?= sprintf('%03d', $a['nomor']) ?></b></td>
              <td data-l="Keperluan"><?= e($a['keperluan']) ?></td>
              <td data-l="Nama"><?= e($a['nama']) ?: '-' ?></td>
              <td data-l="Status"><span class="status st-<?= ['Menunggu' => 'Menunggu', 'Dipanggil' => 'Dikonfirmasi', 'Selesai' => 'Selesai'][$a['status']] ?? 'Dibatalkan' ?>"><?= e($a['status']) ?></span></td>
              <td data-l="Aksi"><?php if (in_array($a['status'], ['Menunggu', 'Dipanggil'], true)): ?>
                <form method="post" action="/admin/antrean" class="actions"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                  <button class="btn btn-ghost btn-sm" name="aksi" value="selesai">Selesai</button>
                  <button class="btn btn-ghost btn-sm" name="aksi" value="lewati">Lewati</button>
                </form><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$antre): ?><tr><td colspan="5" class="muted">Belum ada yang mengambil nomor hari ini.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="panel qr-poster" id="poster">
    <h3>Poster QR antrean</h3>
    <p class="small muted">Cetak dan tempel di meja layanan. Warga memindai dengan kamera HP untuk mengambil nomor.</p>
    <div class="poster-isi">
      <b class="poster-judul">Ambil nomor antrean dari HP</b>
      <div id="qr-antre" class="qr-gambar" data-qr="<?= e($alamatQr) ?>" role="img" aria-label="Kode QR antrean"></div>
      <p>Pindai dengan kamera HP, pilih keperluan, lalu duduk menunggu. Nomormu dipanggil di layar HP.</p>
      <small><?= e(setting('nama_desa')) ?> | Jam layanan <?= e(setting('jam_layanan')) ?></small>
    </div>
    <div class="actions" style="margin-top:12px">
      <button type="button" class="btn btn-dark btn-sm" onclick="window.print()">Cetak poster</button>
      <form method="post" action="/admin/antrean" onsubmit="return confirm('Ganti kode QR? Poster lama tidak berlaku lagi.')"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" name="aksi" value="ganti-kode">Ganti kode QR</button></form>
    </div>
    <p class="hint">Ganti kode kalau foto poster tersebar di media sosial, supaya orang tidak mengambil nomor dari rumah.</p>
  </div>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
