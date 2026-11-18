<?php
// =========================================================
// UNDUH DATA STATISTIK (/unduh-data) - terbuka untuk umum, hanya angka agregat (tanpa data pribadi)
//   /unduh-data                     -> semua kategori
//   /unduh-data?kategori=Sekolah    -> satu kategori
// Format CSV dengan BOM UTF-8 supaya huruf terbaca benar saat dibuka di Excel.
// =========================================================
$kategori = trim((string) ($_GET['kategori'] ?? ''));
try {
    $baris = $kategori !== ''
        ? q('SELECT kategori, label, nilai, satuan, ilustrasi FROM statistik WHERE kategori = ? ORDER BY urutan, id', [$kategori])
        : q('SELECT kategori, label, nilai, satuan, ilustrasi FROM statistik ORDER BY kategori, urutan, id');
} catch (Throwable $e) { // kolom ilustrasi belum ada
    $baris = $kategori !== ''
        ? q('SELECT kategori, label, nilai, satuan, 0 AS ilustrasi FROM statistik WHERE kategori = ? ORDER BY urutan, id', [$kategori])
        : q('SELECT kategori, label, nilai, satuan, 0 AS ilustrasi FROM statistik ORDER BY kategori, urutan, id');
}
if (!$baris) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kategori tidak ditemukan.';
    return;
}
$nama = 'statistik-' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', setting('nama_desa') . ($kategori !== '' ? '-' . $kategori : ''))) . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . trim($nama, '-') . '"');
header('Cache-Control: public, max-age=300');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
// Cegah rumus Excel berbahaya kalau ada teks diawali = + - @
$aman = fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
fputcsv($out, ['Kategori', 'Uraian', 'Nilai', 'Satuan', 'Keterangan'], ',', '"', '');
foreach ($baris as $r) {
    fputcsv($out, [$aman($r['kategori']), $aman($r['label']), $r['nilai'] + 0, $aman($r['satuan']), !empty($r['ilustrasi']) ? 'ilustrasi' : 'data asli'], ',', '"', '');
}
fputcsv($out, [], ',', '"', '');
fputcsv($out, ['Catatan', $aman(setting('catatan_data'))], ',', '"', '');
fputcsv($out, ['Sumber', setting('nama_desa') . ', diunduh ' . date('Y-m-d')], ',', '"', '');
fclose($out);
