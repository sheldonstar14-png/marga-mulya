<?php
// =========================================================
// DATA SISA KUOTA (JSON), dipanggil JavaScript form reservasi.
// Contoh: /kuota?w=curug&t=2026-11-20
//   -> {"kuota":80,"terpakai":6,"sisa":74}
// =========================================================
header('Content-Type: application/json; charset=utf-8');

$w = q1('SELECT id, kuota FROM wisata WHERE slug = ? AND aktif = 1', [$_GET['w'] ?? '']);
$t = $_GET['t'] ?? '';
$tglValid = DateTime::createFromFormat('Y-m-d', $t);

if (!$w || !$tglValid || $tglValid->format('Y-m-d') !== $t) {
    http_response_code(400);
    echo json_encode(['error' => 'Destinasi atau tanggal tidak valid.']);
    return;
}

$pakai = terpakai($w['id'], $t);
echo json_encode(['kuota' => (int) $w['kuota'], 'terpakai' => $pakai, 'sisa' => max(0, $w['kuota'] - $pakai)]);
