<?php
// =========================================================
// PENGAJUAN SURAT ONLINE
// Warga mengajukan dari Informasi Desa > Layanan surat dan mendapat kode SRT-.
// Petugas mengubah status; warga dikabari lewat email (kalau diisi) atau tombol WhatsApp di sini.
// =========================================================
$daftarStatus = ['Diterima', 'Diproses', 'Siap diambil', 'Selesai', 'Ditolak'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $kode = (string) ($_POST['kode'] ?? '');
    $s = q1('SELECT * FROM surat WHERE kode = ?', [$kode]);
    $kembali = '/admin/surat?buka=' . urlencode($kode);
    if (!$s) redirect('/admin/surat?gagal=' . urlencode('Pengajuan tidak ditemukan.'));
    $status = (string) ($_POST['status'] ?? '');
    $catatan = trim((string) ($_POST['catatan'] ?? ''));
    if (!in_array($status, $daftarStatus, true) || mb_strlen($catatan) > 2000) redirect($kembali . '&gagal=' . urlencode('Status atau catatan tidak valid.'));
    if ($status === 'Ditolak' && $catatan === '') redirect($kembali . '&gagal=' . urlencode('Tulis alasan penolakan di catatan supaya warga tahu yang harus dilengkapi.'));
    q('UPDATE surat SET status = ?, catatan = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE kode = ?', [$status, $catatan, $kode]);
    $hasil = 'tidak ada email';
    if ($status !== $s['status']) {
        require_once __DIR__ . '/../../inc/email.php';
        $hasil = email_surat($kode);
    }
    redirect($kembali . '&ok=' . urlencode("$kode disimpan." . ($hasil === true ? ' Email terkirim ke warga.' : ' Kabari warga lewat tombol WhatsApp.')));
}

$saring = (string) ($_GET['status'] ?? '');
$daftar = in_array($saring, $daftarStatus, true)
    ? q('SELECT * FROM surat WHERE status = ? ORDER BY id DESC LIMIT 200', [$saring])
    : q("SELECT * FROM surat ORDER BY CASE status WHEN 'Diterima' THEN 1 WHEN 'Diproses' THEN 2 WHEN 'Siap diambil' THEN 3 ELSE 4 END, id DESC LIMIT 200");
$jumlah = array_column(q('SELECT status, COUNT(*) AS n FROM surat GROUP BY status'), 'n', 'status');
$buka = (string) ($_GET['buka'] ?? '');
$warna = ['Diterima' => 'Menunggu', 'Diproses' => 'Dikonfirmasi', 'Siap diambil' => 'Dikonfirmasi', 'Selesai' => 'Selesai', 'Ditolak' => 'Dibatalkan'];
$judulAdmin = 'Pengajuan surat';
$menuAdmin = 'surat';
require __DIR__ . '/../../inc/admin-header.php';
?>
<div class="head-row"><h2>Pengajuan surat online</h2><a class="btn btn-main btn-sm" href="/admin/antrean">Buka antrean hari ini</a></div>
<p class="muted" style="max-width:75ch">Siapkan surat sebelum warga datang. Saat status diubah menjadi <b>Siap diambil</b>, warga mendapat email (kalau mengisinya). Cocokkan nama dengan KTP asli saat surat diserahkan.</p>
<nav class="chips" aria-label="Saring status">
  <a class="chip <?= $saring === '' ? 'on' : '' ?>" href="/admin/surat">Semua</a>
  <?php foreach ($daftarStatus as $st): ?><a class="chip <?= $saring === $st ? 'on' : '' ?>" href="/admin/surat?status=<?= urlencode($st) ?>"><?= $st ?> (<?= (int) ($jumlah[$st] ?? 0) ?>)</a><?php endforeach; ?>
</nav>
<div class="grid" style="gap:14px">
  <?php foreach ($daftar as $s): ?>
    <details class="panel aspirasi-item" <?= $buka === $s['kode'] || ($buka === '' && $s['status'] === 'Diterima') ? 'open' : '' ?>>
      <summary>
        <span><b><?= e($s['kode']) ?></b> <span class="tag"><?= e($s['layanan_nama']) ?></span></span>
        <span class="status st-<?= $warna[$s['status']] ?? 'Menunggu' ?>"><?= e($s['status']) ?></span>
      </summary>
      <p style="margin:12px 0 4px"><b><?= e($s['nama']) ?></b> untuk <?= e($s['keperluan']) ?></p>
      <p class="small muted">WA <?= e($s['wa']) ?><?= $s['email'] ? ', ' . e($s['email']) : '' ?> | Diajukan <?= tgl(substr((string) $s['dibuat_pada'], 0, 10)) ?></p>
      <form method="post" action="/admin/surat">
        <?= csrf_field() ?><input type="hidden" name="kode" value="<?= e($s['kode']) ?>">
        <div class="field"><label for="st-<?= e($s['kode']) ?>">Status</label>
          <select id="st-<?= e($s['kode']) ?>" name="status"><?php foreach ($daftarStatus as $st): ?><option <?= $s['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="ct-<?= e($s['kode']) ?>">Catatan untuk warga</label>
          <textarea id="ct-<?= e($s['kode']) ?>" name="catatan" rows="3" placeholder="Contoh: Surat sudah ditandatangani. Bawa KTP asli."><?= e((string) $s['catatan']) ?></textarea></div>
        <div class="actions">
          <button class="btn btn-dark btn-sm">Simpan</button>
          <a class="btn btn-wa btn-sm" target="_blank" rel="noopener" href="<?= e(wa_link($s['wa'], 'Halo ' . $s['nama'] . ', pengajuan ' . $s['layanan_nama'] . ' (kode ' . $s['kode'] . ') berstatus: ' . $s['status'] . '. Lacak di ' . url_dasar() . '/data-desa?grup=Layanan+surat&srt=' . $s['kode'])) ?>">Kabari lewat WhatsApp</a>
        </div>
      </form>
    </details>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><p class="muted">Belum ada pengajuan.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
