<?php
// =========================================================
// TANDA TANGAN UPLOAD CLOUDINARY (JSON)
// Browser admin meminta "izin upload" ke sini, lalu mengirim foto
// langsung ke Cloudinary. API secret tidak pernah dikirim ke browser.
// =========================================================
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Gunakan POST.']);
    return;
}
cek_csrf();

$cloud  = getenv('CLOUDINARY_CLOUD_NAME');
$key    = getenv('CLOUDINARY_API_KEY');
$secret = getenv('CLOUDINARY_API_SECRET');
if (!$cloud || !$key || !$secret) {
    http_response_code(503);
    echo json_encode(['error' => 'Upload foto belum diaktifkan: isi CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, dan CLOUDINARY_API_SECRET. Sementara itu, tempel alamat gambar di kolom di bawahnya.']);
    return;
}

// Parameter yang ditandatangani (diurutkan sesuai abjad), lalu SHA-1 dengan API secret
$param = ['folder' => 'marga-mulya', 'timestamp' => time()];
ksort($param);
$teks = implode('&', array_map(fn($k, $v) => "$k=$v", array_keys($param), $param));
$tanda = sha1($teks . $secret);

echo json_encode([
    'url'       => "https://api.cloudinary.com/v1_1/$cloud/image/upload",
    'api_key'   => $key,
    'timestamp' => $param['timestamp'],
    'folder'    => $param['folder'],
    'signature' => $tanda,
]);
