<?php
// =========================================================
// KELOLA ASPIRASI DAN PENGADUAN WARGA
// Ubah status + tulis tanggapan -> warga otomatis menerima email.
// Laporan yang diizinkan pengirim tampil di Papan Aspirasi (tanpa nama) setelah petugas mencentang "Tampilkan".
// =========================================================
$daftarStatus = ['Diterima', 'Diproses', 'Selesai', 'Ditolak'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    // Hapus satu foto (misalnya memperlihatkan wajah orang atau tidak pantas)
    if (($_POST['aksi'] ?? '') === 'hapus_foto') {
        $fid = (int) ($_POST['foto'] ?? 0);
        $kodeF = (string) ($_POST['kode'] ?? '');
        q('DELETE FROM aspirasi_foto WHERE id = ?', [$fid]);
        redirect('/admin/aspirasi?' . http_build_query(['buka' => $kodeF, 'ok' => 'Foto dihapus.']));
    }
    $kode = (string) ($_POST['kode'] ?? '');
    $status = (string) ($_POST['status'] ?? '');
    $tanggapan = trim((string) ($_POST['tanggapan'] ?? ''));
    $publik = isset($_POST['publik']) ? 1 : 0;
    $lama = q1('SELECT * FROM aspirasi WHERE kode = ?', [$kode]);
    $kembali = '/admin/aspirasi?' . http_build_query(['status' => $_POST['saring'] ?? '', 'buka' => $kode]);
    if (!$lama || !in_array($status, $daftarStatus, true)) redirect($kembali . '&gagal=' . urlencode('Data tidak valid.'));
    if (mb_strlen($tanggapan) > 2000) redirect($kembali . '&gagal=' . urlencode('Tanggapan maksimal 2.000 huruf.'));
    if (in_array($status, ['Selesai', 'Ditolak'], true) && $tanggapan === '') redirect($kembali . '&gagal=' . urlencode('Tulis tanggapan untuk warga sebelum menandai Selesai atau Ditolak.'));
    if ($publik && empty($lama['izin_publik'])) $publik = 0; // pengirim tidak mengizinkan tampil

    q('UPDATE aspirasi SET status = ?, tanggapan = ?, publik = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE kode = ?', [$status, $tanggapan, $publik, $kode]);
    if ($status !== $lama['status']) catat_riwayat_aspirasi((int) $lama['id'], $status, $tanggapan !== '' ? $tanggapan : null);
    $pesan = "$kode disimpan.";
    if ($status !== $lama['status'] || $tanggapan !== (string) $lama['tanggapan']) {
        require_once __DIR__ . '/../../inc/email.php';
        $hasil = email_aspirasi($kode);
        $pesan .= $hasil === true ? ' Email pemberitahuan terkirim ke warga.' : " Email belum terkirim: $hasil.";
    }
    redirect($kembali . '&ok=' . urlencode($pesan));
}

$saring = in_array($_GET['status'] ?? '', $daftarStatus, true) ? $_GET['status'] : '';
$daftar = $saring ? q('SELECT * FROM aspirasi WHERE status = ? ORDER BY id DESC', [$saring]) : q('SELECT * FROM aspirasi ORDER BY id DESC');
$hitung = [];
foreach (q('SELECT status, COUNT(*) AS n FROM aspirasi GROUP BY status') as $h) $hitung[$h['status']] = (int) $h['n'];
$perKategori = q('SELECT kategori, COUNT(*) AS n FROM aspirasi GROUP BY kategori ORDER BY n DESC');
$buka = $_GET['buka'] ?? '';
$fotoAdmin = [];
try { foreach (q('SELECT id, aspirasi_id FROM aspirasi_foto ORDER BY id') as $f) $fotoAdmin[$f['aspirasi_id']][] = (int) $f['id']; } catch (Throwable $e) {}

$judulAdmin = 'Aspirasi warga';
$menuAdmin = 'aspirasi';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Aspirasi dan pengaduan warga</h2>
<details class="cara-pakai"><summary>Cara pakai halaman ini</summary><p>Setiap perubahan status atau tanggapan dikirim ke email warga dan tercatat di riwayat. Laporan yang diizinkan pengirim bisa ditampilkan di Papan Aspirasi (halaman Kontak) tanpa nama dan kontak. Periksa dulu isinya: jangan tampilkan laporan yang menyebut nama orang, berisi tuduhan, atau data pribadi.</p></details>
<nav class="chips" aria-label="Saring status">
  <a class="chip <?= $saring === '' ? 'on' : '' ?>" href="/admin/aspirasi">Semua (<?= array_sum($hitung) ?>)</a>
  <?php foreach ($daftarStatus as $s): ?>
    <a class="chip <?= $saring === $s ? 'on' : '' ?>" href="/admin/aspirasi?status=<?= urlencode($s) ?>"><?= $s ?> (<?= $hitung[$s] ?? 0 ?>)</a>
  <?php endforeach; ?>
</nav>
<?php if ($perKategori): ?>
  <p class="small muted">Paling banyak dilaporkan: <?= e(implode(', ', array_map(fn($k) => $k['kategori'] . ' (' . $k['n'] . ')', array_slice($perKategori, 0, 3)))) ?></p>
<?php endif; ?>

<div class="cari-daftar"><label class="sr-only" for="cariDaftar">Cari kode, nama, atau isi</label><input type="search" id="cariDaftar" data-cari-daftar="#daftarItem" placeholder="Cari kode, nama, atau isi" autocomplete="off"><span class="cari-info small muted" aria-live="polite"></span></div>
<div class="grid" style="gap:14px" id="daftarItem">
  <?php foreach ($daftar as $a): ?>
    <details data-cari-item class="panel aspirasi-item" <?= $buka === $a['kode'] || ($a['status'] === 'Diterima' && $buka === '') ? 'open' : '' ?>>
      <summary>
        <span><b><?= e($a['kode']) ?></b> <span class="tag"><?= e($a['kategori']) ?></span></span>
        <span class="status st-<?= e(['Diterima' => 'Menunggu', 'Diproses' => 'Dikonfirmasi', 'Selesai' => 'Selesai', 'Ditolak' => 'Dibatalkan'][$a['status']] ?? 'Menunggu') ?>"><?= e($a['status']) ?></span>
      </summary>
      <p style="margin:12px 0 6px"><?= nl2br(e($a['isi'])) ?></p>
      <p class="small muted" style="margin:0 0 12px">
        <?= e($a['nama']) ?><?= $a['wa'] ? ', WA ' . e($a['wa']) : '' ?><?= $a['email'] ? ', ' . e($a['email']) : '' ?>
        <?= $a['lokasi'] ? ' | Lokasi: ' . e($a['lokasi']) : '' ?> | Dikirim <?= tgl(substr((string) $a['dibuat_pada'], 0, 10)) ?>
      </p>
      <?php if (!empty($fotoAdmin[$a['id']])): ?>
        <div class="foto-adu" style="margin-bottom:12px">
          <?php foreach ($fotoAdmin[$a['id']] as $fid): ?>
            <figure style="margin:0">
              <a href="/foto-aspirasi?id=<?= $fid ?>" target="_blank"><img src="/foto-aspirasi?id=<?= $fid ?>" alt="Foto laporan" width="120" height="90" loading="lazy"></a>
              <form method="post" action="/admin/aspirasi" onsubmit="return confirm('Hapus foto ini?')"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus_foto"><input type="hidden" name="foto" value="<?= $fid ?>"><input type="hidden" name="kode" value="<?= e($a['kode']) ?>"><button class="btn btn-ghost btn-sm" type="submit" style="margin-top:4px">Hapus foto</button></form>
            </figure>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <form method="post" action="/admin/aspirasi">
        <?= csrf_field() ?>
        <input type="hidden" name="kode" value="<?= e($a['kode']) ?>"><input type="hidden" name="saring" value="<?= e($saring) ?>">
        <div class="grid g2" style="gap:12px">
          <div class="field" style="margin:0"><label for="st-<?= e($a['kode']) ?>">Status</label>
            <select id="st-<?= e($a['kode']) ?>" name="status"><?php foreach ($daftarStatus as $s): ?><option <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
          </div>
          <?php if (!empty($a['izin_publik'])): ?>
            <label class="cek" style="align-self:end"><input type="checkbox" name="publik" value="1" <?= $a['publik'] || $a['status'] === 'Diterima' ? 'checked' : '' ?>> Tampilkan di Papan Aspirasi (pengirim mengizinkan<?= (int) ($a['dukungan'] ?? 0) ? ', ' . (int) $a['dukungan'] . ' warga ikut mengalami' : '' ?>)</label>
          <?php else: ?>
            <p class="small muted" style="align-self:end;margin:0">Pengirim tidak mengizinkan laporan ini tampil di Papan Aspirasi.</p>
          <?php endif; ?>
        </div>
        <div class="field"><label for="tg-<?= e($a['kode']) ?>">Tanggapan untuk warga</label>
          <textarea id="tg-<?= e($a['kode']) ?>" name="tanggapan" rows="3" maxlength="2000" placeholder="Contoh: Sudah dicek petugas, perbaikan dijadwalkan minggu depan."><?= e($a['tanggapan'] ?? '') ?></textarea></div>
        <div class="actions">
          <button class="btn btn-dark btn-sm" type="submit">Simpan dan kabari warga</button>
          <?php if ($a['wa']): ?><a class="btn btn-wa btn-sm" target="_blank" rel="noopener" href="<?= e(wa_link(nomor_wa($a['wa']), "Halo {$a['nama']}, terkait aspirasi {$a['kode']}: ")) ?>">WhatsApp</a><?php endif; ?>
        </div>
      </form>
    </details>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><div class="panel muted">Belum ada aspirasi<?= $saring ? " berstatus $saring" : '' ?>.</div><?php endif; ?>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
