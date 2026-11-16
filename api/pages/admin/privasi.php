<?php
// =========================================================
// PERMINTAAN DATA PRIBADI (hak warga menurut UU Pelindungan Data Pribadi)
// Warga bisa meminta salinan, perbaikan, atau penghapusan datanya, atau menarik persetujuan.
// Tombol "Hapus data terkait" menganonimkan reservasi, aspirasi, dan pesan milik warga itu.
// =========================================================
$daftarStatus = ['Diterima', 'Diproses', 'Selesai', 'Ditolak'];

// Cari data yang terhubung dengan permintaan: lewat kode reservasi/aspirasi atau kontak yang sama
function data_terkait(array $p): array
{
    $kontak = trim($p['kontak']);
    $wa = preg_replace('/\D/', '', $kontak);
    $waBaku = $wa !== '' ? nomor_wa($wa) : '-';
    $hasil = ['reservasi' => [], 'aspirasi' => [], 'pesan' => []];
    $hasil['reservasi'] = q("SELECT kode, nama, tanggal FROM reservasi WHERE nama <> '(dihapus)' AND (kode = ? OR (email <> '' AND email = ?) OR (wa <> '' AND (wa = ? OR wa = ?)))", [$p['kode_terkait'], $kontak, $wa ?: '-', $waBaku]);
    $hasil['aspirasi'] = q("SELECT kode, nama, kategori FROM aspirasi WHERE nama <> '(dihapus)' AND (kode = ? OR (email <> '' AND email = ?) OR (wa <> '' AND (wa = ? OR wa = ?)))", [$p['kode_terkait'], $kontak, $wa ?: '-', $waBaku]);
    $hasil['pesan'] = q("SELECT id, nama, topik FROM pesan WHERE nama <> '(dihapus)' AND kontak <> '' AND kontak = ?", [$kontak]);
    return $hasil;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $kode = (string) ($_POST['kode'] ?? '');
    $p = q1('SELECT * FROM permintaan_data WHERE kode = ?', [$kode]);
    $kembali = '/admin/privasi?buka=' . urlencode($kode);
    if (!$p) redirect('/admin/privasi?gagal=' . urlencode('Data tidak ditemukan.'));

    if (($_POST['aksi'] ?? '') === 'hapus-data') {
        $t = data_terkait($p);
        foreach ($t['reservasi'] as $r) q("UPDATE reservasi SET nama = '(dihapus)', wa = '', email = '', catatan = '' WHERE kode = ?", [$r['kode']]);
        foreach ($t['aspirasi'] as $a) q("UPDATE aspirasi SET nama = '(dihapus)', wa = '', email = '' WHERE kode = ?", [$a['kode']]);
        foreach ($t['pesan'] as $m) q("UPDATE pesan SET nama = '(dihapus)', kontak = '' WHERE id = ?", [$m['id']]);
        $jumlah = count($t['reservasi']) + count($t['aspirasi']) + count($t['pesan']);
        q("UPDATE permintaan_data SET status = 'Selesai', tanggapan = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE kode = ?",
            ["Data pribadimu di $jumlah catatan sudah kami hapus. Angka statistik tanpa nama tetap disimpan.", $kode]);
        require_once __DIR__ . '/../../inc/email.php';
        email_permintaan_data($kode);
        redirect($kembali . '&ok=' . urlencode("Nama dan kontak di $jumlah catatan sudah dihapus. Permintaan ditandai Selesai."));
    }

    $status = (string) ($_POST['status'] ?? '');
    $tanggapan = trim((string) ($_POST['tanggapan'] ?? ''));
    if (!in_array($status, $daftarStatus, true) || mb_strlen($tanggapan) > 2000) redirect($kembali . '&gagal=' . urlencode('Status atau tanggapan tidak valid.'));
    if (in_array($status, ['Selesai', 'Ditolak'], true) && $tanggapan === '') redirect($kembali . '&gagal=' . urlencode('Tulis tanggapan sebelum menandai Selesai atau Ditolak.'));
    q('UPDATE permintaan_data SET status = ?, tanggapan = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE kode = ?', [$status, $tanggapan, $kode]);
    require_once __DIR__ . '/../../inc/email.php';
    $hasil = email_permintaan_data($kode);
    redirect($kembali . '&ok=' . urlencode("$kode disimpan." . ($hasil === true ? ' Email terkirim ke warga.' : ' Kabari warga lewat kontak yang ia tulis.')));
}

$daftar = q('SELECT * FROM permintaan_data ORDER BY id DESC');
$buka = $_GET['buka'] ?? '';
$judulAdmin = 'Permintaan data pribadi';
$menuAdmin = 'privasi';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Permintaan data pribadi</h2>
<p class="muted">Warga berhak meminta salinan, perbaikan, atau penghapusan data pribadinya, dan menarik persetujuan. Tanggapi secepatnya. Data lama juga dihapus otomatis setelah <?= (int) setting('simpan_data_hari') ?> hari (atur di Pengaturan).</p>
<div class="grid" style="gap:14px">
  <?php foreach ($daftar as $p): $t = data_terkait($p); ?>
    <details class="panel aspirasi-item" <?= $buka === $p['kode'] || ($p['status'] === 'Diterima' && $buka === '') ? 'open' : '' ?>>
      <summary>
        <span><b><?= e($p['kode']) ?></b> <span class="tag"><?= e($p['jenis']) ?></span></span>
        <span class="status st-<?= e(['Diterima' => 'Menunggu', 'Diproses' => 'Dikonfirmasi', 'Selesai' => 'Selesai', 'Ditolak' => 'Dibatalkan'][$p['status']] ?? 'Menunggu') ?>"><?= e($p['status']) ?></span>
      </summary>
      <p style="margin:12px 0 6px"><?= nl2br(e($p['rincian'])) ?></p>
      <p class="small muted"><?= e($p['nama']) ?>, <?= e($p['kontak']) ?><?= $p['kode_terkait'] ? ' | Kode terkait: ' . e($p['kode_terkait']) : '' ?> | Dikirim <?= tgl(substr((string) $p['dibuat_pada'], 0, 10)) ?></p>
      <div class="etik-box small" style="margin-bottom:12px">
        <b>Data yang ditemukan:</b>
        <?= count($t['reservasi']) ?> reservasi<?= $t['reservasi'] ? ' (' . e(implode(', ', array_column($t['reservasi'], 'kode'))) . ')' : '' ?>,
        <?= count($t['aspirasi']) ?> aspirasi<?= $t['aspirasi'] ? ' (' . e(implode(', ', array_column($t['aspirasi'], 'kode'))) . ')' : '' ?>,
        <?= count($t['pesan']) ?> pesan.
        <span class="muted">Dicocokkan lewat kode terkait dan kontak yang sama. Pastikan identitas warga sesuai sebelum memberikan salinan atau menghapus.</span>
      </div>
      <form method="post" action="/admin/privasi">
        <?= csrf_field() ?><input type="hidden" name="kode" value="<?= e($p['kode']) ?>">
        <div class="field"><label for="st-<?= e($p['kode']) ?>">Status</label>
          <select id="st-<?= e($p['kode']) ?>" name="status"><?php foreach ($daftarStatus as $s): ?><option <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="tg-<?= e($p['kode']) ?>">Tanggapan untuk warga</label>
          <textarea id="tg-<?= e($p['kode']) ?>" name="tanggapan" rows="3" maxlength="2000"><?= e($p['tanggapan'] ?? '') ?></textarea></div>
        <div class="actions"><button class="btn btn-dark btn-sm" type="submit">Simpan dan kabari warga</button></div>
      </form>
      <?php if (in_array($p['jenis'], ['Penghapusan data', 'Tarik persetujuan'], true) && $p['status'] !== 'Selesai' && array_sum(array_map('count', $t)) > 0): ?>
        <form method="post" action="/admin/privasi" class="form-konfirmasi" data-pesan="Hapus nama dan kontak di semua data terkait <?= e($p['kode']) ?>? Tindakan ini tidak bisa dibatalkan." style="margin-top:10px">
          <?= csrf_field() ?><input type="hidden" name="kode" value="<?= e($p['kode']) ?>"><input type="hidden" name="aksi" value="hapus-data">
          <button class="btn btn-danger btn-sm" type="submit">Hapus data terkait</button>
        </form>
      <?php endif; ?>
    </details>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><div class="panel muted">Belum ada permintaan data pribadi.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
