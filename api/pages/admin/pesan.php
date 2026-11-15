<?php
// =========================================================
// PESAN MASUK dari halaman Kontak
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['aksi'] ?? '') === 'dibaca') {
        q('UPDATE pesan SET dibaca = 1 WHERE id = ?', [$id]);
        redirect('/admin/pesan?ok=' . urlencode('Pesan ditandai sudah dibaca.'));
    }
    if (($_POST['aksi'] ?? '') === 'hapus') {
        q('DELETE FROM pesan WHERE id = ?', [$id]);
        redirect('/admin/pesan?ok=' . urlencode('Pesan dihapus.'));
    }
}
$daftar = q('SELECT * FROM pesan ORDER BY dibaca, id DESC');

$judulAdmin = 'Pesan masuk';
$menuAdmin = 'pesan';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Pesan masuk</h2>
<div class="grid">
  <?php foreach ($daftar as $p):
    $nomor = preg_replace('/\D/', '', $p['kontak']);
    $bisaWa = !str_contains($p['kontak'], '@') && strlen($nomor) >= 10; ?>
    <div class="panel" style="<?= $p['dibaca'] ? '' : 'border-left:4px solid var(--turmeric)' ?>">
      <div class="head-row" style="margin-bottom:6px">
        <b><?= e($p['nama']) ?> <span class="small muted">(<?= e($p['kontak']) ?>)</span></b>
        <span class="small muted"><?= tgl(substr($p['dibuat_pada'], 0, 10)) ?></span>
      </div>
      <span class="tag"><?= e($p['topik']) ?></span>
      <p style="margin:8px 0"><?= nl2br(e($p['isi'])) ?></p>
      <div class="actions">
        <?php if (!$p['dibaca']): ?>
          <form method="post" action="/admin/pesan"><?= csrf_field() ?><input type="hidden" name="aksi" value="dibaca"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Tandai sudah dibaca</button></form>
        <?php else: ?><span class="small muted">Sudah dibaca</span><?php endif; ?>
        <?php if ($bisaWa): ?>
          <a class="btn btn-wa btn-sm" href="<?= e(wa_link(nomor_wa($p['kontak']), 'Halo ' . $p['nama'] . ', terima kasih sudah menghubungi ' . setting('nama_desa') . '. ')) ?>" target="_blank" rel="noopener">Balas via WA</a>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="mailto:<?= e($p['kontak']) ?>">Balas via email</a>
        <?php endif; ?>
        <form method="post" action="/admin/pesan" class="form-konfirmasi" data-pesan="Hapus pesan dari <?= e($p['nama']) ?>?"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><p class="muted">Belum ada pesan.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
