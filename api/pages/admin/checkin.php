<?php
// =========================================================
// CHECK-IN DI LOKET: halaman yang terbuka saat petugas memindai QR tiket
//   /admin/checkin?kode=MM-XXXXXX
// =========================================================
$kode = strtoupper(trim((string) ($_POST['kode'] ?? $_GET['kode'] ?? '')));
$sql = 'SELECT r.*, w.nama AS wisata, w.harga FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.kode = ?';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $r = q1($sql, [$kode]);
    if ($r && in_array($r['status'], ['Menunggu', 'Dikonfirmasi'], true)) {
        q("UPDATE reservasi SET status = 'Selesai' WHERE kode = ?", [$kode]);
        require_once __DIR__ . '/../../inc/email.php';
        $hasilEmail = email_reservasi($kode); // email terima kasih + ajakan memberi ulasan
        redirect('/admin/checkin?' . http_build_query(['kode' => $kode, 'ok' => 'Pengunjung tercatat hadir. ' . ($hasilEmail === true ? 'Email ajakan ulasan terkirim.' : "Email belum terkirim: $hasilEmail.")]));
    }
    redirect('/admin/checkin?' . http_build_query(['kode' => $kode, 'gagal' => 'Reservasi ini tidak bisa di-check-in.']));
}

$r = $kode !== '' ? q1($sql, [$kode]) : null;
$hariIni = date('Y-m-d');

$judulAdmin = 'Check-in';
$menuAdmin = 'reservasi';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Check-in pengunjung</h2>
<form method="get" action="/admin/checkin" class="grid" style="grid-template-columns:minmax(0,320px) auto;gap:10px;align-items:end;margin-bottom:18px">
  <div><label for="kode">Kode reservasi</label><input id="kode" name="kode" value="<?= e($kode) ?>" placeholder="Pindai QR atau ketik kode"></div>
  <button class="btn btn-dark" type="submit">Cari</button>
</form>

<?php if ($kode !== '' && !$r): ?>
  <div class="alert alert-err" role="alert">Kode <?= e($kode) ?> tidak ditemukan. Periksa lagi penulisannya.</div>
<?php elseif ($r): ?>
  <div class="panel" style="max-width:560px">
    <div class="head-row" style="margin-bottom:10px"><h3 style="margin:0"><?= e($r['kode']) ?></h3><span class="status st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></div>
    <table>
      <tr><th>Nama</th><td><?= e($r['nama']) ?></td></tr>
      <tr><th>Destinasi</th><td><?= e($r['wisata']) ?></td></tr>
      <tr><th>Tanggal</th><td><?= tgl($r['tanggal'], 'hari') ?></td></tr>
      <tr><th>Jumlah</th><td><?= (int) $r['jumlah'] ?> orang</td></tr>
      <tr><th>Bayar di loket</th><td><b><?= rupiah($r['harga'] * $r['jumlah']) ?></b></td></tr>
      <?php if ($r['catatan']): ?><tr><th>Catatan</th><td><?= e($r['catatan']) ?></td></tr><?php endif; ?>
    </table>

    <?php if ($r['tanggal'] !== $hariIni && in_array($r['status'], ['Menunggu', 'Dikonfirmasi'], true)): ?>
      <div class="alert alert-err" style="margin-top:14px">Perhatian: reservasi ini untuk <?= tgl($r['tanggal'], 'hari') ?>, bukan hari ini.</div>
    <?php endif; ?>

    <?php if (in_array($r['status'], ['Menunggu', 'Dikonfirmasi'], true)): ?>
      <form method="post" action="/admin/checkin" style="margin-top:16px">
        <?= csrf_field() ?><input type="hidden" name="kode" value="<?= e($r['kode']) ?>">
        <button class="btn btn-main" type="submit" style="width:100%">Tandai hadir</button>
      </form>
      <p class="hint">Status berubah menjadi Selesai dan pengunjung otomatis menerima email ajakan memberi ulasan.</p>
    <?php elseif ($r['status'] === 'Selesai'): ?>
      <p style="margin-top:14px">Pengunjung ini sudah tercatat hadir.</p>
    <?php else: ?>
      <p style="margin-top:14px" class="err">Reservasi ini sudah dibatalkan dan tidak bisa di-check-in.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
