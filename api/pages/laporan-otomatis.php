<?php
// =========================================================
// PENGIRIMAN LAPORAN OTOMATIS (bukan halaman)
// Dipanggil Vercel Cron setiap tanggal 1 pukul 01.00 UTC (08.00 WIB), lihat "crons" di vercel.json.
// Vercel mengirim header "Authorization: Bearer <CRON_SECRET>". Tanpa CRON_SECRET yang cocok, permintaan ditolak,
// jadi orang lain tidak bisa memicu pengiriman email.
// Aman terpanggil dua kali: bulan yang sudah terkirim dicatat di pengaturan "laporan_terakhir".
// =========================================================
header('Content-Type: application/json; charset=utf-8');
$rahasia = (string) getenv('CRON_SECRET');
$masuk = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if ($rahasia === '' || !hash_equals('Bearer ' . $rahasia, $masuk)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'pesan' => 'Tidak diizinkan.']);
    return;
}
require_once __DIR__ . '/../inc/laporan.php';
$bulan = date('Y-m', strtotime('first day of last month'));
if (setting('laporan_terakhir') === $bulan) {
    echo json_encode(['ok' => true, 'pesan' => "Laporan $bulan sudah pernah dikirim."]);
    return;
}
$hasil = kirim_laporan($bulan);
if ($hasil === true) {
    q('UPDATE pengaturan SET nilai = ? WHERE kunci = ?', [$bulan, 'laporan_terakhir']);
}
error_log('[marga-mulya] laporan otomatis ' . $bulan . ': ' . ($hasil === true ? 'terkirim' : $hasil));
echo json_encode(['ok' => $hasil === true, 'pesan' => $hasil === true ? "Laporan $bulan terkirim." : $hasil]);
