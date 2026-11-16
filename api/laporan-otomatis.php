<?php
// =========================================================
// FUNCTION KHUSUS UNTUK VERCEL CRON: /laporan-otomatis
// vercel.json mengarahkan /laporan-otomatis langsung ke file ini (rute dipasang SEBELUM rute umum),
// jadi pengiriman laporan tidak bergantung pada router halaman (api/index.php).
// Tanpa header "Authorization: Bearer <CRON_SECRET>" yang cocok, jawabannya 401 "Tidak diizinkan".
// =========================================================
date_default_timezone_set('Asia/Jakarta');
require __DIR__ . '/config.php';
require __DIR__ . '/inc/helpers.php';
try {
    require __DIR__ . '/pages/laporan-otomatis.php';
} catch (Throwable $e) {
    error_log('[marga-mulya] laporan otomatis gagal: ' . get_class($e) . ': ' . $e->getMessage() . ' di ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['ok' => false, 'pesan' => 'Gagal menyusun laporan. Lihat Vercel > Logs.']);
}
