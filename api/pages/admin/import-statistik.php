<?php
// =========================================================
// IMPORT / EXPORT STATISTIK DESA LEWAT CSV (bisa dibuka dan disimpan dari Excel)
//   ?unduh=koma | ?unduh=titikkoma -> unduh data sekarang sebagai template
//   POST langkah=pratinjau          -> baca file, tampilkan pratinjau
//   POST langkah=simpan             -> simpan baris yang valid
// Baris dengan kategori + label yang sama akan DIPERBARUI, yang belum ada DITAMBAHKAN.
// =========================================================

// ---------- Unduh data sekarang (sekaligus template) ----------
if (isset($_GET['unduh'])) {
    $pemisah = $_GET['unduh'] === 'koma' ? ',' : ';';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="statistik-desa-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['kategori', 'label', 'nilai', 'satuan', 'urutan'], $pemisah);
    foreach (q('SELECT kategori, label, nilai, satuan, urutan FROM statistik ORDER BY kategori, urutan, id') as $r) {
        $nilai = rtrim(rtrim(number_format((float) $r['nilai'], 2, $pemisah === ';' ? ',' : '.', ''), '0'), '.,');
        fputcsv($out, [$r['kategori'], $r['label'], $nilai, $r['satuan'], $r['urutan']], $pemisah);
    }
    fclose($out);
    return;
}

// "1.234" -> 1234, "8,4" -> 8.4, "1.234,5" -> 1234.5
function baca_angka(string $teks): ?float
{
    $t = str_replace([' ', "\u{00A0}"], '', trim($teks));
    if ($t === '') return null;
    if (str_contains($t, '.') && str_contains($t, ',')) {
        $t = str_replace(',', '.', str_replace('.', '', $t));
    } elseif (str_contains($t, ',')) {
        $t = str_replace(',', '.', $t);
    } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $t)) {
        $t = str_replace('.', '', $t);
    }
    return is_numeric($t) ? (float) $t : null;
}

$pratinjau = null;
$errorFile = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();

    // ---------- Langkah 2: simpan ----------
    if (($_POST['langkah'] ?? '') === 'simpan') {
        $baris = json_decode((string) ($_POST['data'] ?? '[]'), true);
        $tambah = $ubah = 0;
        foreach (is_array($baris) ? $baris : [] as $b) {
            if (!isset($b['kategori'], $b['label'], $b['nilai'], $b['satuan'], $b['urutan'])) continue;
            $ada = q1('SELECT id FROM statistik WHERE kategori = ? AND label = ?', [$b['kategori'], $b['label']]);
            if ($ada) {
                q('UPDATE statistik SET nilai = ?, satuan = ?, urutan = ? WHERE id = ?', [(float) $b['nilai'], $b['satuan'], (int) $b['urutan'], $ada['id']]);
                $ubah++;
            } else {
                q('INSERT INTO statistik (kategori, label, nilai, satuan, urutan) VALUES (?, ?, ?, ?, ?)', [$b['kategori'], $b['label'], (float) $b['nilai'], $b['satuan'], (int) $b['urutan']]);
                $tambah++;
            }
        }
        redirect('/admin/statistik?ok=' . urlencode("Import selesai: $tambah angka baru ditambahkan, $ubah angka diperbarui."));
    }

    // ---------- Langkah 1: baca file dan pratinjau ----------
    $file = $_FILES['berkas'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errorFile = 'Pilih file CSV terlebih dahulu.';
    } elseif ($file['size'] > 1024 * 1024) {
        $errorFile = 'Ukuran file maksimal 1 MB.';
    } else {
        $isi = file_get_contents($file['tmp_name']);
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi); // buang penanda BOM dari Excel
        $barisPertama = strtok($isi, "\n");
        $pemisah = substr_count($barisPertama, ';') > substr_count($barisPertama, ',') ? ';' : ',';
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $isi);
        rewind($fh);
        $kepala = array_map(fn($k) => strtolower(trim((string) $k)), fgetcsv($fh, 0, $pemisah) ?: []);
        $posisi = array_flip($kepala);
        if (!isset($posisi['kategori'], $posisi['label'], $posisi['nilai'], $posisi['satuan'])) {
            $errorFile = 'Baris pertama file harus berisi judul kolom: kategori, label, nilai, satuan (urutan boleh ada). Unduh template di bawah supaya formatnya pas.';
        } else {
            $pratinjau = [];
            $nomor = 1;
            while (($kolom = fgetcsv($fh, 0, $pemisah)) !== false && count($pratinjau) < 500) {
                $nomor++;
                if (count(array_filter($kolom, fn($x) => trim((string) $x) !== '')) === 0) continue; // baris kosong
                $ambil = fn($k) => trim((string) ($kolom[$posisi[$k]] ?? ''));
                $b = ['baris' => $nomor, 'kategori' => $ambil('kategori'), 'label' => $ambil('label'), 'satuan' => $ambil('satuan'),
                      'nilai' => baca_angka($ambil('nilai')), 'urutan' => isset($posisi['urutan']) ? (int) baca_angka($ambil('urutan')) : 0, 'masalah' => ''];
                if (mb_strlen($b['kategori']) < 2 || mb_strlen($b['kategori']) > 60) $b['masalah'] = 'Kategori kosong atau terlalu panjang';
                elseif ($b['label'] === '' || mb_strlen($b['label']) > 120) $b['masalah'] = 'Label kosong atau terlalu panjang';
                elseif ($b['nilai'] === null || $b['nilai'] < 0) $b['masalah'] = 'Nilai bukan angka';
                elseif ($b['satuan'] === '' || mb_strlen($b['satuan']) > 30) $b['masalah'] = 'Satuan kosong atau terlalu panjang';
                if ($b['masalah'] === '') {
                    $lama = q1('SELECT nilai, satuan FROM statistik WHERE kategori = ? AND label = ?', [$b['kategori'], $b['label']]);
                    $b['status'] = $lama ? 'Diperbarui' : 'Baru';
                    $b['lama'] = $lama ? angka($lama['nilai']) . ' ' . $lama['satuan'] : '';
                }
                $pratinjau[] = $b;
            }
            fclose($fh);
            if (!$pratinjau) $errorFile = 'File tidak berisi data di bawah baris judul kolom.';
        }
    }
}

$judulAdmin = 'Import statistik';
$menuAdmin = 'statistik';
require __DIR__ . '/../../inc/admin-header.php';
?>
<p><a href="/admin/statistik">Kembali ke Statistik desa</a></p>
<h2>Import atau export statistik</h2>

<?php if ($pratinjau):
  $valid = array_values(array_filter($pratinjau, fn($b) => $b['masalah'] === ''));
  $simpan = array_map(fn($b) => ['kategori' => $b['kategori'], 'label' => $b['label'], 'nilai' => $b['nilai'], 'satuan' => $b['satuan'], 'urutan' => $b['urutan']], $valid); ?>
  <div class="alert <?= count($valid) === count($pratinjau) ? 'alert-ok' : 'alert-err' ?>">
    <?= count($valid) ?> dari <?= count($pratinjau) ?> baris siap disimpan
    (<?= count(array_filter($valid, fn($b) => $b['status'] === 'Baru')) ?> baru, <?= count(array_filter($valid, fn($b) => $b['status'] === 'Diperbarui')) ?> diperbarui).
    <?= count($valid) < count($pratinjau) ? 'Baris bermasalah tidak akan disimpan. Perbaiki di file lalu unggah ulang, atau lanjutkan tanpa baris itu.' : 'Periksa angkanya sekali lagi sebelum menyimpan.' ?>
  </div>
  <div class="panel table-wrap" style="margin-bottom:16px">
    <table class="stack">
      <thead><tr><th>Baris</th><th>Kategori</th><th>Label</th><th class="num">Nilai</th><th>Satuan</th><th>Hasil</th></tr></thead>
      <tbody>
      <?php foreach ($pratinjau as $b): ?>
        <tr>
          <td data-l="Baris"><?= (int) $b['baris'] ?></td>
          <td data-l="Kategori"><?= e($b['kategori']) ?></td>
          <td data-l="Label"><?= e($b['label']) ?></td>
          <td data-l="Nilai" class="num"><?= $b['nilai'] === null ? '-' : angka($b['nilai']) ?></td>
          <td data-l="Satuan"><?= e($b['satuan']) ?></td>
          <td data-l="Hasil">
            <?php if ($b['masalah']): ?><span class="status st-Dibatalkan"><?= e($b['masalah']) ?></span>
            <?php elseif ($b['status'] === 'Baru'): ?><span class="status st-Dikonfirmasi">Baru</span>
            <?php else: ?><span class="status st-Menunggu">Diperbarui</span> <span class="small muted">sebelumnya <?= e($b['lama']) ?></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($valid): ?>
    <form method="post" action="/admin/import-statistik" class="actions">
      <?= csrf_field() ?><input type="hidden" name="langkah" value="simpan">
      <input type="hidden" name="data" value="<?= e(json_encode($simpan, JSON_UNESCAPED_UNICODE)) ?>">
      <button class="btn btn-dark" type="submit">Simpan <?= count($valid) ?> angka</button>
      <a class="btn btn-ghost" href="/admin/import-statistik">Batal</a>
    </form>
  <?php endif; ?>

<?php else: ?>
  <?php if ($errorFile): ?><div class="alert alert-err" role="alert"><?= e($errorFile) ?></div><?php endif; ?>
  <div class="grid g2">
    <div class="panel">
      <h3>1. Unduh template</h3>
      <p class="muted">Template berisi semua angka statistik yang ada sekarang. Buka di Excel, ubah atau tambah baris, lalu simpan sebagai <b>CSV</b>.</p>
      <div class="actions">
        <a class="btn btn-dark btn-sm" href="/admin/import-statistik?unduh=titikkoma">Unduh untuk Excel (Indonesia)</a>
        <a class="btn btn-ghost btn-sm" href="/admin/import-statistik?unduh=koma">Unduh CSV biasa</a>
      </div>
      <p class="hint">Kolom: <b>kategori</b>, <b>label</b>, <b>nilai</b>, <b>satuan</b>, <b>urutan</b>. Angka desimal boleh pakai koma (8,4). Kategori baru otomatis muncul di halaman Data Desa.</p>
    </div>
    <div class="panel">
      <h3>2. Unggah file</h3>
      <form method="post" action="/admin/import-statistik" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="langkah" value="pratinjau">
        <div class="field"><label for="berkas">File CSV</label><input id="berkas" name="berkas" type="file" accept=".csv,text/csv" required></div>
        <button class="btn btn-main" type="submit">Lihat pratinjau</button>
      </form>
      <p class="hint">Belum ada yang tersimpan sampai kamu menekan tombol Simpan di halaman pratinjau. Di Excel, pilih File &rarr; Save As &rarr; CSV.</p>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
