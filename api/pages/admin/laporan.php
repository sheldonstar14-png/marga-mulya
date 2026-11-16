<?php
// =========================================================
// LAPORAN BULANAN: lihat, cetak/simpan PDF, dan kirim ke email kepala desa.
// Laporan bulan lalu juga dikirim otomatis setiap tanggal 1 (lihat /laporan-otomatis).
// =========================================================
require_once __DIR__ . '/../../inc/laporan.php';

$bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_GET['bulan'] ?? '') ? $_GET['bulan'] : date('Y-m', strtotime('first day of last month'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $bulanKirim = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $_POST['bulan'] ?? '') ? $_POST['bulan'] : $bulan;
    $hasil = kirim_laporan($bulanKirim);
    redirect('/admin/laporan?bulan=' . $bulanKirim . '&' . ($hasil === true ? 'ok=' . urlencode('Laporan ' . tgl($bulanKirim . '-01', 'bulantahun') . ' terkirim ke ' . setting('email_laporan') . '.') : 'gagal=' . urlencode('Laporan belum terkirim: ' . $hasil . '.')));
}

// Pilihan 12 bulan terakhir, termasuk bulan berjalan
$pilihanBulan = [];
for ($i = 0; $i < 12; $i++) $pilihanBulan[] = date('Y-m', strtotime("first day of -$i month"));

$judulAdmin = 'Laporan bulanan';
$menuAdmin = 'laporan';
require __DIR__ . '/../../inc/admin-header.php';
?>
<div class="laporan-alat">
  <h2>Laporan bulanan untuk kepala desa</h2>
  <p class="muted">Ringkasan otomatis dari semua data website. Tidak memuat nama atau kontak warga, jadi aman dicetak dan dikirim lewat email.
    Laporan bulan lalu dikirim otomatis ke <b><?= e(setting('email_laporan') ?: 'alamat yang belum diisi') ?></b> setiap tanggal 1.</p>
  <form method="get" action="/admin/laporan" class="actions" style="align-items:end">
    <div class="field" style="margin:0"><label for="bulan">Bulan</label>
      <select id="bulan" name="bulan" onchange="this.form.submit()">
        <?php foreach ($pilihanBulan as $b): ?><option value="<?= $b ?>" <?= $b === $bulan ? 'selected' : '' ?>><?= tgl($b . '-01', 'bulantahun') ?><?= $b === date('Y-m') ? ' (berjalan)' : '' ?></option><?php endforeach; ?>
      </select></div>
    <noscript><button class="btn btn-ghost btn-sm" type="submit">Tampilkan</button></noscript>
    <button type="button" class="btn btn-dark btn-sm" onclick="window.print()">Cetak atau simpan PDF</button>
  </form>
  <form method="post" action="/admin/laporan" class="actions" style="margin-top:10px">
    <?= csrf_field() ?><input type="hidden" name="bulan" value="<?= e($bulan) ?>">
    <button class="btn btn-main btn-sm" type="submit">Kirim laporan ini ke email kepala desa</button>
    <?php if ($admin['peran'] === 'Super admin'): ?><a class="small" href="/admin/pengaturan">Ubah alamat penerima</a><?php endif; ?>
  </form>
</div>
<div class="panel laporan-kertas"><?= html_laporan(data_laporan($bulan)) ?></div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
