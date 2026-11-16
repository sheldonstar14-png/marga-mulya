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

// ---------- Aplikasi web (PWA): service worker dan manifest, bukan halaman ----------
if ($path === 'sw.js') {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: no-cache');
    header('Service-Worker-Allowed: /');
    readfile(__DIR__ . '/pwa/sw.js');
    return;
}
if ($path === 'manifest.webmanifest') {
    try { $namaApp = setting('nama_desa'); } catch (Throwable $e) { $namaApp = 'Desa Marga Mulya'; }
    header('Content-Type: application/manifest+json; charset=utf-8');
    echo json_encode([
        'name' => $namaApp, 'short_name' => mb_strimwidth(str_replace('Desa ', '', $namaApp), 0, 12, ''),
        'description' => 'Wisata, layanan warga, dan informasi ' . $namaApp,
        'lang' => 'id', 'start_url' => '/?pwa=1', 'scope' => '/', 'display' => 'standalone',
        'background_color' => '#F7F8F2', 'theme_color' => '#2B5A28',
        'icons' => [
            ['src' => '/assets/img/ikon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/assets/img/ikon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => '/assets/img/ikon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return;
}

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
    'asisten'   => 'asisten', // bukan halaman: jawaban Asisten Desa (JSON)
];

if ($depan === 'admin') {
    $halaman = 'admin/index';   // semua alamat /admin/... diatur oleh pages/admin/index.php
} elseif ($depan === 'wisata' && isset($parts[1])) {
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
} catch (Throwable $e) {
    // Database atau pengaturan bermasalah: buang halaman setengah jadi, tampilkan pesan yang jelas
    ob_end_clean();
    http_response_code(500);
    $pesanError = $e->getMessage();
    // Catat detail lengkap ke log server (Vercel: Project > Logs) tanpa menampilkannya ke pengunjung
    error_log('[marga-mulya] ' . get_class($e) . ': ' . $pesanError . ' di ' . $e->getFile() . ':' . $e->getLine());
    // Jenis gangguan yang aman ditampilkan, supaya tim tahu harus memperbaiki apa
    $jenisError = match (true) {
        (bool) preg_match("/doesn't exist|Unknown column|no such table|no such column|Base table or view not found/i", $pesanError) => 'struktur',
        (bool) preg_match('/APP_SECRET/', $pesanError) => 'secret',
        (bool) preg_match('/SQLSTATE\[HY000\] \[\d+\]|Connection refused|getaddrinfo|Access denied|timed out/i', $pesanError) => 'koneksi',
        default => 'lain',
    };
    require __DIR__ . '/pages/error-db.php';
}
