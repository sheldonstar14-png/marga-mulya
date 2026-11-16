<?php
// KHUSUS DI LAPTOP: meniru aturan vercel.json.
// File di /assets/ disajikan apa adanya, sisanya ke api/index.php.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/assets/') && is_file(__DIR__ . $path)) {
    return false;
}
if ($path === '/laporan-otomatis') { // sama seperti rute khusus di vercel.json
    require __DIR__ . '/api/laporan-otomatis.php';
    return true;
}
require __DIR__ . '/api/index.php';
