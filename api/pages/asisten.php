<?php
// =========================================================
// ASISTEN DESA (MENU PILIHAN), JSON
// Bekerja seperti layanan telepon: pengunjung menekan nomor pilihan, lalu muncul pilihan
// berikutnya atau jawaban. Semua isi diatur petugas di CMS > Asisten desa, jadi asisten
// tidak pernah mengarang jawaban.
//   GET /asisten        -> menu awal
//   GET /asisten?id=12  -> pilihan nomor 12 beserta pilihan di bawahnya
// =========================================================
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$id = max(0, (int) ($_GET['id'] ?? 0));
$node = null;
if ($id > 0) {
    $node = q1('SELECT id, induk_id, judul, pertanyaan, jawaban, tautan, label_tautan FROM asisten_menu WHERE id = ? AND aktif = 1', [$id]);
    if (!$node) $id = 0;
}
$pilihan = q('SELECT id, judul FROM asisten_menu WHERE induk_id = ? AND aktif = 1 ORDER BY urutan, id LIMIT 7', [$id]);

// Jejak (Menu awal > Layanan surat > ...)
$jejak = [];
$cari = $node;
for ($i = 0; $cari && $i < 6; $i++) {
    array_unshift($jejak, ['id' => (int) $cari['id'], 'judul' => $cari['judul']]);
    $cari = (int) $cari['induk_id'] ? q1('SELECT id, induk_id, judul FROM asisten_menu WHERE id = ?', [(int) $cari['induk_id']]) : null;
}

// {jam_layanan}, {alamat}, {wa}, {email}, {nama_desa} diganti isi Pengaturan, jadi jawaban selalu sama dengan data terbaru
$isiSetting = fn(string $s) => preg_replace_callback('/\{(jam_layanan|alamat|wa|email|nama_desa)\}/', fn($m) => setting($m[1]), $s);
if ($node) $node['jawaban'] = $isiSetting((string) $node['jawaban']);

$tautan = '';
if ($node && $node['tautan'] !== '' && preg_match('#^(/|https://|tel:)#', (string) $node['tautan'])) $tautan = $node['tautan'];

echo json_encode([
    'id'         => $id,
    'induk'      => $node ? (int) $node['induk_id'] : null,
    'judul'      => $node['judul'] ?? 'Menu awal',
    'pertanyaan' => $node ? ($node['pertanyaan'] ?: ($pilihan ? 'Silakan pilih:' : '')) : 'Halo! Apa yang ingin Anda ketahui? Tekan salah satu nomor.',
    'jawaban'    => $node['jawaban'] ?? '',
    'tautan'     => $tautan,
    'label'      => $tautan ? ($node['label_tautan'] ?: 'Buka halaman') : '',
    'pilihan'    => array_map(fn($p) => ['id' => (int) $p['id'], 'judul' => $p['judul']], $pilihan),
    'jejak'      => $jejak,
    'wa'         => wa_link(setting('wa'), 'Halo petugas ' . setting('nama_desa') . ', saya ingin bertanya: '),
], JSON_UNESCAPED_UNICODE);
