<?php
// =========================================================
// ROUTER: semua alamat website masuk ke file ini (lihat vercel.json),
// lalu file ini memilih halaman mana yang ditampilkan.
// =========================================================
date_default_timezone_set('Asia/Jakarta'); // tanggal & jam mengikuti WIB, bukan jam server
require __DIR__ . '/config.php';
require __DIR__ . '/inc/helpers.php';

// Contoh: "/wisata/curug" -> ['wisata', 'curug']
$path  = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = $path === '' ? [] : explode('/', $path);
$depan = $parts[0] ?? '';

// Daftar alamat dan file halamannya (di folder pages/)
$rute = [
    ''          => 'home',
    'profil'    => 'profil',
    'data-desa' => 'data',
    'wisata'    => 'wisata',
    'reservasi' => 'reservasi',
    'umkm'      => 'umkm',
    'agenda'    => 'agenda',
    'galeri'    => 'galeri',
    'kontak'    => 'kontak',
    'kuota'     => 'kuota',   // bukan halaman: data sisa kuota (JSON) untuk form reservasi
];

if ($depan === 'wisata' && isset($parts[1])) {
    $halaman = 'wisata-detail';
    $slug = $parts[1];
} elseif (isset($rute[$depan]) && count($parts) <= 1) {
    $halaman = $rute[$depan];
} else {
    $halaman = '404';
}

$file = __DIR__ . '/pages/' . $halaman . '.php';
if (!is_file($file)) {
    $file = __DIR__ . '/pages/404.php';
}
if (basename($file) === '404.php') {
    http_response_code(404);
}

ob_start(); // tampung dulu hasil halaman, supaya bisa dibatalkan kalau ada error
try {
    require $file;
    ob_end_flush();
} catch (PDOException $e) {
    // Database bermasalah: buang halaman setengah jadi, tampilkan pesan yang jelas
    ob_end_clean();
    http_response_code(500);
    $pesanError = $e->getMessage();
    require __DIR__ . '/pages/error-db.php';
}
