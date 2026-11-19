<?php
// =========================================================
// PENERIMA BANSOS
// Petugas mengisi nama dan RT penerima, lalu menandai tahap yang sudah tersalur.
// Di website nama selalu disamarkan (contoh "As** Sa******"). NIK tidak dicatat sama sekali.
// Setelah program selesai lebih dari 30 hari, nama penerima dihapus otomatis.
// =========================================================
$program = q('SELECT * FROM bantuan ORDER BY aktif DESC, id');
$pilih = (int) ($_GET['program'] ?? $_POST['program'] ?? ($program[0]['id'] ?? 0));
$prog = null;
foreach ($program as $p) if ((int) $p['id'] === $pilih) $prog = $p;
$kembali = '/admin/bansos?program=' . $pilih;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $prog) {
    cek_csrf();
    $aksi = (string) ($_POST['aksi'] ?? '');
    $maksTahap = max(1, (int) $prog['jumlah_tahap']);

    if ($aksi === 'tambah') {
        // Satu baris satu penerima: "Nama lengkap; RT; RW". RW boleh dikosongkan.
        $baris = per_baris((string) ($_POST['daftar'] ?? ''));
        $masuk = 0; $lewati = [];
        foreach ($baris as $no => $b) {
            $bagian = array_map('trim', preg_split('/[;\t,]/', $b));
            $nama = preg_replace('/\s+/', ' ', $bagian[0] ?? '');
            $rt = rt_baku($bagian[1] ?? '');
            $rw = rt_baku($bagian[2] ?? '');
            if (mb_strlen($nama) < 3 || mb_strlen($nama) > 150 || $rt === '' || preg_match('/\d{6,}/', $b)) {
                $lewati[] = $no + 1; // baris berisi deretan angka panjang (mungkin NIK) ikut ditolak
                continue;
            }
            if (q1('SELECT id FROM bansos_penerima WHERE bantuan_id = ? AND rt = ? AND LOWER(TRIM(nama)) = ?', [$pilih, $rt, nama_baku($nama)])) {
                $lewati[] = $no + 1;
                continue;
            }
            q('INSERT INTO bansos_penerima (bantuan_id, nama, rt, rw) VALUES (?, ?, ?, ?)', [$pilih, $nama, $rt, $rw]);
            $masuk++;
        }
        $pesan = "$masuk penerima ditambahkan." . ($lewati ? ' Baris dilewati (format salah, ada deretan angka, atau sudah terdaftar): ' . implode(', ', $lewati) . '.' : '');
        redirect($kembali . '&ok=' . urlencode($pesan));
    }

    $dipilih = array_map('intval', (array) ($_POST['id'] ?? []));
    if (!$dipilih) redirect($kembali . '&gagal=' . urlencode('Centang dulu penerima yang mau diubah.'));
    $tanda = implode(',', array_fill(0, count($dipilih), '?'));

    if ($aksi === 'salur') {
        $tahap = (int) ($_POST['tahap'] ?? 0);
        $tanggal = (string) ($_POST['tanggal'] ?? date('Y-m-d'));
        if ($tahap < 1 || $tahap > $maksTahap || !DateTime::createFromFormat('Y-m-d', $tanggal)) redirect($kembali . '&gagal=' . urlencode('Tahap atau tanggal tidak valid.'));
        q("UPDATE bansos_penerima SET tahap_tersalur = ?, terakhir_salur = ? WHERE bantuan_id = ? AND id IN ($tanda) AND tahap_tersalur < ?",
            [$tahap, $tanggal, $pilih, ...$dipilih, $tahap]);
        redirect($kembali . '&ok=' . urlencode(count($dipilih) . " penerima ditandai sudah menerima tahap $tahap."));
    }
    if ($aksi === 'batal') {
        q("UPDATE bansos_penerima SET tahap_tersalur = CASE WHEN tahap_tersalur > 0 THEN tahap_tersalur - 1 ELSE 0 END WHERE bantuan_id = ? AND id IN ($tanda)", [$pilih, ...$dipilih]);
        redirect($kembali . '&ok=' . urlencode('Tahap terakhir dibatalkan untuk ' . count($dipilih) . ' penerima.'));
    }
    if ($aksi === 'hapus') {
        q("DELETE FROM bansos_penerima WHERE bantuan_id = ? AND id IN ($tanda)", [$pilih, ...$dipilih]);
        redirect($kembali . '&ok=' . urlencode(count($dipilih) . ' penerima dihapus dari daftar.'));
    }
    redirect($kembali);
}

$cari = trim((string) ($_GET['cari'] ?? ''));
$rtSaring = rt_baku((string) ($_GET['rt'] ?? ''));
$penerima = [];
$ring = null;
if ($prog) {
    $syarat = ['bantuan_id = ?']; $nilai = [$pilih];
    if ($cari !== '') { $syarat[] = 'LOWER(nama) LIKE ?'; $nilai[] = '%' . mb_strtolower($cari) . '%'; }
    if ($rtSaring !== '') { $syarat[] = 'rt = ?'; $nilai[] = $rtSaring; }
    $penerima = q('SELECT * FROM bansos_penerima WHERE ' . implode(' AND ', $syarat) . ' ORDER BY rt, nama', $nilai);
    $ring = ringkas_bansos($prog);
}

$judulAdmin = 'Penerima bansos';
$menuAdmin = 'bansos';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Penerima bansos</h2>
<details class="cara-pakai"><summary>Cara pakai halaman ini</summary><p>Daftar ini dipakai untuk fitur Pantau bansos di website. Di website nama tampil disamarkan, dan warga hanya bisa mengecek dengan nama lengkap + RT. <b>Jangan menulis NIK, nomor KK, atau alamat lengkap.</b> Nama dihapus otomatis 30 hari setelah tanggal program selesai.</p></details>

<?php if (!$program): ?>
  <p>Belum ada program. <a href="/admin/bantuan?aksi=tambah">Tambah program bantuan dulu</a>.</p>
<?php else: ?>
<form method="get" class="filter-bar" style="margin-bottom:16px">
  <label for="pp" class="small"><b>Program</b></label>
  <select id="pp" name="program" onchange="this.form.submit()">
    <?php foreach ($program as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $pilih ? 'selected' : '' ?>><?= e($p['nama']) ?><?= $p['aktif'] ? '' : ' (tidak tampil)' ?></option><?php endforeach; ?>
  </select>
  <a class="btn btn-ghost btn-sm" href="/admin/bantuan?aksi=edit&id=<?= $pilih ?>">Ubah kuota dan tahap</a>
</form>

<?php if ($prog): ?>
<div class="stat-row" style="margin-bottom:18px">
  <div class="stat"><span>Penerima terdaftar</span><b><?= $ring['total'] ?></b><small>kuota <?= (int) $prog['kuota'] ?: '-' ?></small></div>
  <?php foreach ($ring['tahap'] as $t => $n): ?>
    <div class="stat"><span>Tahap <?= $t ?> tersalur</span><b><?= $n ?></b><small><?= persen_id((float) $n, (float) max(1, $ring['total'])) ?>%</small></div>
  <?php endforeach; ?>
</div>

<div class="split">
  <div>
    <form method="get" class="filter-bar" style="margin-bottom:10px">
      <input type="hidden" name="program" value="<?= $pilih ?>">
      <input name="cari" value="<?= e($cari) ?>" placeholder="Cari nama" aria-label="Cari nama">
      <input name="rt" value="<?= e($rtSaring) ?>" placeholder="RT" aria-label="Saring RT" style="max-width:80px" inputmode="numeric">
      <button class="btn btn-ghost btn-sm">Saring</button>
    </form>
    <form method="post" action="/admin/bansos">
      <?= csrf_field() ?><input type="hidden" name="program" value="<?= $pilih ?>">
      <div class="panel table-wrap"><table class="stack">
        <thead><tr><th><input type="checkbox" aria-label="Pilih semua" onclick="document.querySelectorAll('.pilih-penerima').forEach(c=>c.checked=this.checked)"></th><th>Nama</th><th>RT/RW</th><th>Tahap diterima</th><th>Terakhir</th></tr></thead>
        <tbody>
          <?php foreach ($penerima as $r): ?>
            <tr>
              <td><input type="checkbox" class="pilih-penerima" name="id[]" value="<?= (int) $r['id'] ?>" aria-label="Pilih <?= e($r['nama']) ?>"></td>
              <td data-l="Nama"><?= e($r['nama']) ?><br><span class="small muted">di web: <?= e(samarkan_nama($r['nama'])) ?></span></td>
              <td data-l="RT/RW"><?= e($r['rt']) ?><?= $r['rw'] ? '/' . e($r['rw']) : '' ?></td>
              <td data-l="Tahap diterima"><?= (int) $r['tahap_tersalur'] ?> dari <?= max(1, (int) $prog['jumlah_tahap']) ?></td>
              <td data-l="Terakhir"><?= $r['terakhir_salur'] ? tgl((string) $r['terakhir_salur']) : '-' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$penerima): ?><tr><td colspan="5" class="muted">Belum ada penerima<?= $cari || $rtSaring ? ' yang cocok' : '' ?>.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
      <div class="panel" style="margin-top:12px">
        <b>Untuk penerima yang dicentang:</b>
        <div class="filter-bar" style="margin-top:8px">
          <label for="tahap" class="small">Sudah menerima tahap</label>
          <select id="tahap" name="tahap"><?php for ($t = 1; $t <= max(1, (int) $prog['jumlah_tahap']); $t++): ?><option><?= $t ?></option><?php endfor; ?></select>
          <label for="tgl" class="small">pada</label>
          <input id="tgl" type="date" name="tanggal" value="<?= date('Y-m-d') ?>">
          <button class="btn btn-dark btn-sm" name="aksi" value="salur">Tandai tersalur</button>
        </div>
        <div class="actions" style="margin-top:10px">
          <button class="btn btn-ghost btn-sm" name="aksi" value="batal">Batalkan tahap terakhir</button>
          <button class="btn btn-ghost btn-sm" name="aksi" value="hapus" onclick="return confirm('Hapus penerima yang dicentang dari daftar?')">Hapus dari daftar</button>
        </div>
      </div>
    </form>
  </div>
  <form class="panel" method="post" action="/admin/bansos">
    <?= csrf_field() ?><input type="hidden" name="program" value="<?= $pilih ?>"><input type="hidden" name="aksi" value="tambah">
    <h3>Tambah penerima</h3>
    <p class="small muted">Satu baris satu penerima, dengan format <b>Nama lengkap; RT; RW</b>. Bisa ditempel langsung dari Excel (kolom nama, RT, RW). Baris yang berisi deretan angka panjang otomatis ditolak supaya NIK tidak ikut masuk.</p>
    <div class="field"><label for="daftar">Daftar penerima</label>
      <textarea id="daftar" name="daftar" rows="10" placeholder="Asep Saepudin; 01; 01&#10;Iis Rohayati; 01; 01"></textarea></div>
    <button class="btn btn-main">Tambahkan</button>
    <p class="hint">Daftar penerima harus sama dengan Keputusan Kepala Desa hasil musyawarah desa.</p>
  </form>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
