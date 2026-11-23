<?php
// =========================================================
// PENGAJUAN PRODUK UMKM DARI WARGA
// Warga mengisi form di halaman UMKM dan mendapat kode PRD-.
// Petugas memeriksa, lalu "Terbitkan" (masuk ke daftar UMKM) atau "Tolak" dengan alasan.
// =========================================================
$daftarStatus = ['Menunggu', 'Ditinjau', 'Diterbitkan', 'Ditolak'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $kode = (string) ($_POST['kode'] ?? '');
    $p = q1('SELECT * FROM pengajuan_umkm WHERE kode = ?', [$kode]);
    $kembali = '/admin/pengajuanumkm?buka=' . urlencode($kode);
    if (!$p) redirect('/admin/pengajuanumkm?gagal=' . urlencode('Pengajuan tidak ditemukan.'));
    $aksi = (string) ($_POST['aksi'] ?? '');
    $catatan = trim(mb_substr((string) ($_POST['catatan'] ?? ''), 0, 500));

    if ($aksi === 'terbitkan') {
        if ($p['status'] === 'Diterbitkan') redirect($kembali . '&gagal=' . urlencode('Produk ini sudah diterbitkan.'));
        // Foto utama + sampai 2 foto tambahan (12s). Foto baru bisa dilihat publik setelah diterbitkan.
        $idFoto = foto_pengajuan($p);
        foreach ($idFoto as $fid) q('UPDATE foto_unggah SET publik = 1 WHERE id = ?', [$fid]);
        $foto = $idFoto ? '/foto?id=' . $idFoto[0] : '';
        $fotoLain = implode(',', array_map(fn($fid) => '/foto?id=' . $fid, array_slice($idFoto, 1)));
        q('INSERT INTO umkm (nama_usaha, kategori, produk, harga, satuan, pemilik, wa, foto, deskripsi, izin, pengiriman, foto_lain, sumber_foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$p['nama_usaha'], $p['kategori'], $p['produk'], (int) $p['harga'], $p['satuan'], $p['pemilik'], $p['wa'], $foto ?: null, $p['deskripsi'], (string) ($p['izin'] ?? ''), (string) ($p['pengiriman'] ?? ''), $fotoLain, $idFoto ? 'Foto kiriman ' . $p['pemilik'] . ' (pemilik usaha)' : '']);
        $idBaru = (int) db()->lastInsertId();
        q("UPDATE pengajuan_umkm SET status = 'Diterbitkan', umkm_id = ?, catatan = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE kode = ?",
            [$idBaru, $catatan !== '' ? $catatan : 'Produkmu sudah tampil di halaman UMKM. Lokasi di peta bisa ditambahkan petugas.', $kode]);
        redirect($kembali . '&ok=' . urlencode("$kode diterbitkan. Tambahkan titik peta di menu UMKM kalau perlu."));
    }
    $status = (string) ($_POST['status'] ?? '');
    if (!in_array($status, ['Menunggu', 'Ditinjau', 'Ditolak'], true)) redirect($kembali . '&gagal=' . urlencode('Status tidak valid.'));
    if ($status === 'Ditolak' && $catatan === '') redirect($kembali . '&gagal=' . urlencode('Tulis alasan penolakan supaya warga tahu yang harus diperbaiki.'));
    q('UPDATE pengajuan_umkm SET status = ?, catatan = ?, diperbarui_pada = CURRENT_TIMESTAMP WHERE kode = ?', [$status, $catatan, $kode]);
    redirect($kembali . '&ok=' . urlencode("$kode disimpan. Kabari pemilik lewat tombol WhatsApp."));
}

$saring = (string) ($_GET['status'] ?? '');
$daftar = in_array($saring, $daftarStatus, true)
    ? q('SELECT * FROM pengajuan_umkm WHERE status = ? ORDER BY id DESC LIMIT 200', [$saring])
    : q("SELECT * FROM pengajuan_umkm ORDER BY CASE status WHEN 'Menunggu' THEN 1 WHEN 'Ditinjau' THEN 2 ELSE 3 END, id DESC LIMIT 200");
$jumlah = array_column(q('SELECT status, COUNT(*) AS n FROM pengajuan_umkm GROUP BY status'), 'n', 'status');
$buka = (string) ($_GET['buka'] ?? '');
$warna = ['Menunggu' => 'Menunggu', 'Ditinjau' => 'Dikonfirmasi', 'Diterbitkan' => 'Selesai', 'Ditolak' => 'Dibatalkan'];
$judulAdmin = 'Pengajuan produk UMKM';
$menuAdmin = 'pengajuanumkm';
require __DIR__ . '/../../inc/admin-header.php';
?>
<div class="head-row"><h2>Pengajuan produk UMKM</h2><a class="btn btn-ghost btn-sm" href="/admin/umkm">Daftar UMKM tayang</a></div>
<details class="cara-pakai"><summary>Cara pakai halaman ini</summary><p>Periksa apakah usaha benar milik warga Marga Mulya, harga wajar, dan foto pantas. Tekan <b>Terbitkan</b> supaya produk tampil di halaman UMKM. Foto baru bisa dilihat publik setelah diterbitkan.</p></details>
<nav class="chips" aria-label="Saring status">
  <a class="chip <?= $saring === '' ? 'on' : '' ?>" href="/admin/pengajuanumkm">Semua</a>
  <?php foreach ($daftarStatus as $st): ?><a class="chip <?= $saring === $st ? 'on' : '' ?>" href="/admin/pengajuanumkm?status=<?= urlencode($st) ?>"><?= $st ?> (<?= (int) ($jumlah[$st] ?? 0) ?>)</a><?php endforeach; ?>
</nav>
<div class="cari-daftar"><label class="sr-only" for="cariDaftar">Cari kode, nama, atau isi</label><input type="search" id="cariDaftar" data-cari-daftar="#daftarItem" placeholder="Cari kode, nama, atau isi" autocomplete="off"><span class="cari-info small muted" aria-live="polite"></span></div>
<div class="grid" style="gap:14px" id="daftarItem">
  <?php foreach ($daftar as $p): ?>
    <details data-cari-item class="panel aspirasi-item" <?= $buka === $p['kode'] || ($buka === '' && $p['status'] === 'Menunggu') ? 'open' : '' ?>>
      <summary>
        <span><b><?= e($p['kode']) ?></b> <?= e($p['produk']) ?> <span class="tag"><?= e($p['kategori']) ?></span></span>
        <span class="status st-<?= $warna[$p['status']] ?? 'Menunggu' ?>"><?= e($p['status']) ?></span>
      </summary>
      <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px">
        <?php foreach (foto_pengajuan($p) as $fid): ?><a href="/foto?id=<?= $fid ?>" target="_blank"><img src="/foto?id=<?= $fid ?>" alt="Foto produk" width="120" height="90" style="object-fit:cover;border-radius:12px"></a><?php endforeach; ?>
        <div style="flex:1;min-width:240px">
          <p style="margin:0"><b><?= e($p['nama_usaha']) ?></b>, <?= e($p['pemilik']) ?></p>
          <p class="small muted" style="margin:2px 0 6px"><?= rupiah((int) $p['harga']) ?> / <?= e($p['satuan']) ?> | WA <?= e($p['wa']) ?> | Diajukan <?= tgl(substr((string) $p['dibuat_pada'], 0, 10)) ?></p>
          <?php if ($p['deskripsi']): ?><p class="small" style="margin:0"><?= e($p['deskripsi']) ?></p><?php endif; ?>
          <?php if (!empty($p['izin']) || !empty($p['pengiriman'])): ?><p class="small" style="margin:6px 0 0"><?= !empty($p['izin']) ? '<b>Izin:</b> ' . e($p['izin']) . ' ' : '' ?><?= !empty($p['pengiriman']) ? '<b>Pengiriman:</b> ' . e($p['pengiriman']) : '' ?></p><?php endif; ?>
        </div>
      </div>
      <?php if ($p['status'] !== 'Diterbitkan'): ?>
      <form method="post" action="/admin/pengajuanumkm" style="margin-top:12px">
        <?= csrf_field() ?><input type="hidden" name="kode" value="<?= e($p['kode']) ?>">
        <div class="field"><label for="ct-<?= e($p['kode']) ?>">Catatan untuk pemilik</label>
          <textarea id="ct-<?= e($p['kode']) ?>" name="catatan" rows="2" placeholder="Contoh: Mohon kirim foto yang lebih terang."><?= e((string) $p['catatan']) ?></textarea></div>
        <div class="actions">
          <button class="btn btn-main btn-sm" name="aksi" value="terbitkan">Terbitkan ke halaman UMKM</button>
          <select name="status" aria-label="Ubah status"><?php foreach (['Menunggu', 'Ditinjau', 'Ditolak'] as $st): ?><option <?= $p['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?></select>
          <button class="btn btn-dark btn-sm" name="aksi" value="status">Simpan status</button>
          <a class="btn btn-wa btn-sm" target="_blank" rel="noopener" href="<?= e(wa_link($p['wa'], 'Halo ' . $p['pemilik'] . ', pengajuan produk ' . $p['produk'] . ' (kode ' . $p['kode'] . ') berstatus: ' . $p['status'] . '. Cek di ' . url_dasar() . '/umkm?cek=' . $p['kode'])) ?>">Kabari lewat WhatsApp</a>
        </div>
      </form>
      <?php else: ?>
        <p class="small" style="margin:12px 0 0">Sudah tampil. <a href="/admin/umkm?aksi=edit&amp;id=<?= (int) $p['umkm_id'] ?>">Edit produk di menu UMKM</a></p>
      <?php endif; ?>
    </details>
  <?php endforeach; ?>
  <?php if (!$daftar): ?><p class="muted">Belum ada pengajuan.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
