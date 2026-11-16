<?php
// =========================================================
// MODERASI ULASAN: sembunyikan, tampilkan lagi, atau hapus ulasan
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'sembunyikan' || $aksi === 'tampilkan') {
        q('UPDATE ulasan SET tampil = ? WHERE id = ?', [$aksi === 'tampilkan' ? 1 : 0, $id]);
        redirect('/admin/ulasan?ok=' . urlencode($aksi === 'tampilkan' ? 'Ulasan ditampilkan lagi.' : 'Ulasan disembunyikan dari website.'));
    }
    if ($aksi === 'hapus') {
        q('DELETE FROM ulasan WHERE id = ?', [$id]);
        redirect('/admin/ulasan?ok=' . urlencode('Ulasan dihapus.'));
    }
}

$daftar = q('SELECT u.*, w.nama AS wisata, r.kode FROM ulasan u JOIN wisata w ON w.id = u.wisata_id JOIN reservasi r ON r.id = u.reservasi_id ORDER BY u.id DESC');
$ringkas = q('SELECT w.nama, COUNT(u.id) AS n, AVG(u.bintang) AS rata FROM wisata w LEFT JOIN ulasan u ON u.wisata_id = w.id AND u.tampil = 1 GROUP BY w.id, w.nama ORDER BY w.id');

$judulAdmin = 'Ulasan';
$menuAdmin = 'ulasan';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Ulasan pengunjung</h2>
<p class="muted">Ulasan hanya bisa ditulis pengunjung yang reservasinya berstatus Selesai. Sembunyikan ulasan yang tidak pantas, misalnya berisi kata kasar atau iklan.</p>
<div class="stat-row">
  <?php foreach ($ringkas as $r): ?>
    <div class="stat"><span><?= e($r['nama']) ?></span><b><?= $r['n'] ? number_format((float) $r['rata'], 1, ',', '.') . ' ★' : '-' ?></b><span><?= (int) $r['n'] ?> ulasan tampil</span></div>
  <?php endforeach; ?>
</div>
<div class="grid">
  <?php foreach ($daftar as $u): ?>
    <div class="panel" style="<?= $u['tampil'] ? '' : 'opacity:.6' ?>">
      <div class="head-row" style="margin-bottom:6px">
        <b><?= e($u['nama']) ?> <span class="small muted">(<?= e($u['kode']) ?>, <?= e($u['wisata']) ?>)</span></b>
        <span class="small muted"><?= tgl(substr($u['dibuat_pada'], 0, 10)) ?></span>
      </div>
      <span class="bintang" aria-label="<?= (int) $u['bintang'] ?> dari 5 bintang"><?= bintang($u['bintang']) ?></span>
      <?php if (!$u['tampil']): ?><span class="status st-Dibatalkan">Disembunyikan</span><?php endif; ?>
      <p style="margin:8px 0"><?= nl2br(e($u['komentar'])) ?></p>
      <div class="actions">
        <form method="post" action="/admin/ulasan"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
          <input type="hidden" name="aksi" value="<?= $u['tampil'] ? 'sembunyikan' : 'tampilkan' ?>">
          <button class="btn btn-ghost btn-sm" type="submit"><?= $u['tampil'] ? 'Sembunyikan' : 'Tampilkan lagi' ?></button></form>
        <form method="post" action="/admin/ulasan" class="form-konfirmasi" data-pesan="Hapus ulasan dari <?= e($u['nama']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="aksi" value="hapus">
          <button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><p class="muted">Belum ada ulasan.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
