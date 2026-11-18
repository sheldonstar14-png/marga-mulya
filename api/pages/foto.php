<?php
// =========================================================
// FOTO UNGGAHAN WARGA (bukan halaman): /foto?id=12
// Tampil ke publik hanya bila sudah disetujui petugas (publik = 1). Petugas yang login selalu bisa melihat.
// =========================================================
require_once __DIR__ . '/../inc/auth.php';
$f = q1('SELECT mime, data, publik FROM foto_unggah WHERE id = ?', [(int) ($_GET['id'] ?? 0)]);
if (!$f || (!(int) $f['publik'] && !admin_login())) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Foto tidak ditemukan.';
    return;
}
$data = is_resource($f['data']) ? stream_get_contents($f['data']) : (string) $f['data'];
header('Content-Type: ' . (in_array($f['mime'], ['image/jpeg', 'image/png', 'image/webp'], true) ? $f['mime'] : 'image/jpeg'));
header('Content-Length: ' . strlen($data));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: ' . ((int) $f['publik'] ? 'public, max-age=604800' : 'private, no-store'));
echo $data;
