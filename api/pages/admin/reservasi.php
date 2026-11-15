<?php
// =========================================================
// KELOLA RESERVASI: cari, saring status, ubah status, kirim WA, export CSV
// =========================================================
$daftarStatus = ['Menunggu', 'Dikonfirmasi', 'Selesai', 'Dibatalkan'];

// ---------- Ubah status ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $statusBaru = $_POST['status'] ?? '';
    $kode = $_POST['kode'] ?? '';
    if (in_array($statusBaru, $daftarStatus, true) && q1('SELECT id FROM reservasi WHERE kode = ?', [$kode])) {
        q('UPDATE reservasi SET status = ? WHERE kode = ?', [$statusBaru, $kode]);
        redirect('/admin/reservasi?' . http_build_query(['status' => $_POST['saring'] ?? '', 'cari' => $_POST['cari'] ?? '', 'ok' => "$kode sekarang berstatus $statusBaru."]));
    }
    redirect('/admin/reservasi?gagal=' . urlencode('Status tidak valid.'));
}

// ---------- Saring dan cari ----------
$saring = in_array($_GET['status'] ?? '', $daftarStatus, true) ? $_GET['status'] : '';
$cari = trim($_GET['cari'] ?? '');
$sql = 'SELECT r.*, w.nama AS wisata FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE 1 = 1';
$param = [];
if ($saring) { $sql .= ' AND r.status = ?'; $param[] = $saring; }
if ($cari !== '') { $sql .= ' AND (r.kode LIKE ? OR r.nama LIKE ?)'; $param[] = "%$cari%"; $param[] = "%$cari%"; }
$sql .= ' ORDER BY r.id DESC';
$daftar = q($sql, $param);

// ---------- Export CSV (bisa dibuka di Excel) ----------
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reservasi-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // supaya huruf tampil benar di Excel
    fputcsv($out, ['Kode', 'Nama', 'WhatsApp', 'Destinasi', 'Tanggal kunjungan', 'Jumlah orang', 'Status', 'Catatan', 'Dibuat']);
    foreach ($daftar as $r) {
        fputcsv($out, [$r['kode'], $r['nama'], $r['wa'], $r['wisata'], $r['tanggal'], $r['jumlah'], $r['status'], $r['catatan'], $r['dibuat_pada']]);
    }
    fclose($out);
    return;
}

$hitung = [];
foreach (q('SELECT status, COUNT(*) AS n FROM reservasi GROUP BY status') as $h) {
    $hitung[$h['status']] = (int) $h['n'];
}

$judulAdmin = 'Reservasi';
$menuAdmin = 'reservasi';
require __DIR__ . '/../../inc/admin-header.php';
?>
<div class="head-row">
  <h2>Reservasi</h2>
  <a class="btn btn-ghost btn-sm" href="/admin/reservasi?<?= e(http_build_query(['status' => $saring, 'cari' => $cari, 'export' => 1])) ?>">Export ke Excel (CSV)</a>
</div>
<form method="get" action="/admin/reservasi" class="grid" style="grid-template-columns:minmax(0,360px) auto;gap:10px;align-items:end;margin-bottom:16px">
  <input type="hidden" name="status" value="<?= e($saring) ?>">
  <div><label for="cari">Cari kode atau nama</label><input id="cari" name="cari" value="<?= e($cari) ?>" placeholder="Contoh: MM-0428 atau Dinda"></div>
  <button class="btn btn-dark" type="submit">Cari</button>
</form>
<nav class="chips" aria-label="Saring status">
  <a class="chip <?= $saring === '' ? 'on' : '' ?>" href="/admin/reservasi?cari=<?= urlencode($cari) ?>">Semua (<?= array_sum($hitung) ?>)</a>
  <?php foreach ($daftarStatus as $s): ?>
    <a class="chip <?= $saring === $s ? 'on' : '' ?>" href="/admin/reservasi?status=<?= urlencode($s) ?>&cari=<?= urlencode($cari) ?>"><?= $s ?> (<?= $hitung[$s] ?? 0 ?>)</a>
  <?php endforeach; ?>
</nav>

<div class="panel table-wrap">
  <table class="stack">
    <thead><tr><th>Kode</th><th>Pemesan</th><th>Destinasi</th><th>Tanggal</th><th class="num">Orang</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $r):
      $teksWa = $r['status'] === 'Dibatalkan'
          ? "Halo {$r['nama']}, mohon maaf reservasi {$r['kode']} ke {$r['wisata']} tanggal " . tgl($r['tanggal']) . ' tidak dapat kami terima. Silakan hubungi kami untuk memilih tanggal lain.'
          : "Halo {$r['nama']}, reservasi {$r['kode']} ke {$r['wisata']} tanggal " . tgl($r['tanggal']) . " untuk {$r['jumlah']} orang sudah DIKONFIRMASI. Tunjukkan kode ini di loket. Terima kasih.";
      $tombol = fn(string $status, string $label, string $kelas) => '<form method="post" action="/admin/reservasi"' . ($status === 'Dibatalkan' ? ' class="form-konfirmasi" data-pesan="Tolak reservasi ' . e($r['kode']) . ' atas nama ' . e($r['nama']) . '? Kuota tanggal itu akan dikembalikan."' : '') . '>'
          . csrf_field() . '<input type="hidden" name="kode" value="' . e($r['kode']) . '"><input type="hidden" name="status" value="' . $status . '">'
          . '<input type="hidden" name="saring" value="' . e($saring) . '"><input type="hidden" name="cari" value="' . e($cari) . '">'
          . '<button class="btn ' . $kelas . ' btn-sm" type="submit">' . $label . '</button></form>'; ?>
      <tr>
        <td data-l="Kode"><b><?= e($r['kode']) ?></b></td>
        <td data-l="Pemesan"><?= e($r['nama']) ?><br><span class="small muted"><?= e($r['wa']) ?><?= $r['catatan'] ? '<br>Catatan: ' . e($r['catatan']) : '' ?></span></td>
        <td data-l="Destinasi"><?= e($r['wisata']) ?></td>
        <td data-l="Tanggal"><?= tgl($r['tanggal'], 'pendek') ?></td>
        <td data-l="Orang" class="num"><?= (int) $r['jumlah'] ?></td>
        <td data-l="Status"><span class="status st-<?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td data-l="Aksi"><div class="actions">
          <?php if ($r['status'] === 'Menunggu'): ?>
            <?= $tombol('Dikonfirmasi', 'Konfirmasi', 'btn-dark') ?><?= $tombol('Dibatalkan', 'Tolak', 'btn-danger') ?>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['Dikonfirmasi', 'Dibatalkan'], true)): ?>
            <a class="btn btn-wa btn-sm" href="<?= e(wa_link(nomor_wa($r['wa']), $teksWa)) ?>" target="_blank" rel="noopener">Kirim WA</a>
          <?php endif; ?>
          <?php if ($r['status'] === 'Dikonfirmasi'): ?><?= $tombol('Selesai', 'Tandai selesai', 'btn-ghost') ?><?php endif; ?>
        </div></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="7" class="muted">Tidak ada reservasi yang cocok.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
