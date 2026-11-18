<?php
// =========================================================
// FOTO LAPORAN WARGA (bukan halaman)
// /foto-aspirasi?id=12          -> tampil kalau laporannya sudah disetujui tampil di Papan Aspirasi
// /foto-aspirasi?id=12&k=ADU-.. -> pengirim yang memegang kode lacak
// Petugas yang sedang login di CMS selalu bisa melihat.
// =========================================================
require_once __DIR__ . '/../inc/auth.php';
$id = (int) ($_GET['id'] ?? 0);
$kode = strtoupper(trim((string) ($_GET['k'] ?? '')));
$f = $id ? q1('SELECT f.mime, f.data, a.kode, a.publik, a.izin_publik FROM aspirasi_foto f JOIN aspirasi a ON a.id = f.aspirasi_id WHERE f.id = ?', [$id]) : null;

$umum = $f && (int) $f['publik'] === 1 && (int) $f['izin_publik'] === 1;
$boleh = $f && ($umum || ($kode !== '' && hash_equals($f['kode'], $kode)) || admin_login());
if (!$boleh) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Foto tidak ditemukan.';
    return;
}
$data = is_resource($f['data']) ? stream_get_contents($f['data']) : (string) $f['data'];
header('Content-Type: ' . (in_array($f['mime'], ['image/jpeg', 'image/png', 'image/webp'], true) ? $f['mime'] : 'image/jpeg'));
header('Content-Length: ' . strlen($data));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: ' . ($umum ? 'public, max-age=86400' : 'private, no-store'));
echo $data;
