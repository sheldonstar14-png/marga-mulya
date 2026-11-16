<?php
// =========================================================
// LOGIN ADMIN
// Vercel tidak menyimpan session PHP antar permintaan, jadi tanda login
// disimpan di cookie yang ditandatangani (HMAC). Cookie tidak bisa dipalsukan
// tanpa APP_SECRET, kunci rahasia yang hanya ada di server.
// =========================================================

const NAMA_COOKIE = 'mm_admin';
const LAMA_LOGIN  = 8 * 3600; // 8 jam

function app_secret(): string
{
    $rahasia = getenv('APP_SECRET');
    if ($rahasia) {
        return $rahasia;
    }
    if (getenv('DB_SSL') === '1') {
        // Di website online, APP_SECRET wajib diisi di Environment Variables Vercel
        throw new RuntimeException('APP_SECRET belum diatur di Environment Variables Vercel.');
    }
    return 'kunci-lokal-hanya-untuk-laptop';
}

function pakai_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

// Buat cookie login untuk admin dengan id tertentu
function mulai_login(int $idAdmin): void
{
    $isi = base64_encode(json_encode(['id' => $idAdmin, 'exp' => time() + LAMA_LOGIN]));
    $token = $isi . '.' . hash_hmac('sha256', $isi, app_secret());
    setcookie(NAMA_COOKIE, $token, [
        'expires'  => time() + LAMA_LOGIN,
        'path'     => '/',
        'httponly' => true,      // tidak bisa dibaca JavaScript
        'secure'   => pakai_https(),
        'samesite' => 'Lax',     // tidak ikut terkirim dari situs lain
    ]);
    $_COOKIE[NAMA_COOKIE] = $token;
}

function akhiri_login(): void
{
    setcookie(NAMA_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'secure' => pakai_https(), 'samesite' => 'Lax']);
    unset($_COOKIE[NAMA_COOKIE]);
}

// Admin yang sedang login (array), atau null kalau belum login
function admin_login(): ?array
{
    static $sudahDicek = false, $admin = null;
    if ($sudahDicek) {
        return $admin;
    }
    $sudahDicek = true;
    $token = $_COOKIE[NAMA_COOKIE] ?? '';
    if (substr_count($token, '.') !== 1) {
        return null;
    }
    [$isi, $tanda] = explode('.', $token);
    if (!hash_equals(hash_hmac('sha256', $isi, app_secret()), $tanda)) {
        return null; // cookie diubah orang: tolak
    }
    $data = json_decode(base64_decode($isi), true);
    if (!is_array($data) || ($data['exp'] ?? 0) < time()) {
        return null; // sudah kedaluwarsa
    }
    $admin = q1('SELECT id, nama, username, peran, akses FROM admin WHERE id = ?', [(int) ($data['id'] ?? 0)]);
    return $admin;
}

function wajib_login(): array
{
    $admin = admin_login();
    if (!$admin) {
        redirect('/admin');
    }
    return $admin;
}

function wajib_super_admin(array $admin): void
{
    if ($admin['peran'] !== 'Super admin') {
        http_response_code(403);
        exit('Menu ini hanya untuk Super admin. <a href="/admin/dashboard">Kembali ke dashboard</a>');
    }
}

// ---------- Hak akses per menu ----------
// Menu yang bisa diberikan ke petugas. Akun admin dan Pengaturan khusus Super admin.
const MENU_PETUGAS = [
    'reservasi' => 'Reservasi',
    'pesan'     => 'Pesan masuk',
    'wisata'    => 'Wisata',
    'budaya'    => 'Budaya',
    'umkm'      => 'UMKM',
    'agenda'    => 'Agenda',
    'berita'    => 'Berita',
    'galeri'    => 'Galeri',
    'statistik' => 'Statistik desa',
    'profil'    => 'Profil desa',
    'aparat'    => 'Perangkat desa',
];

// Daftar menu yang boleh dibuka seorang admin
function akses_admin(array $admin): array
{
    if ($admin['peran'] === 'Super admin') {
        return [...array_keys(MENU_PETUGAS), 'akun', 'pengaturan'];
    }
    if ($admin['akses'] === null) {
        return array_keys(MENU_PETUGAS); // akun lama sebelum Fase 7A: semua menu petugas
    }
    return array_values(array_intersect(array_keys(MENU_PETUGAS), explode(',', $admin['akses'])));
}

function boleh(array $admin, string $menu): bool
{
    return $menu === 'dashboard' || in_array($menu, akses_admin($admin), true);
}

// ---------- Perlindungan CSRF ----------
// Setiap form admin membawa token ini. Situs lain tidak bisa membuatnya,
// jadi form palsu dari situs lain akan ditolak.
function csrf_token(): string
{
    return hash_hmac('sha256', 'csrf|' . ($_COOKIE[NAMA_COOKIE] ?? ''), app_secret());
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function cek_csrf(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Sesi formulir sudah tidak berlaku. <a href="javascript:history.back()">Kembali</a>, muat ulang halaman, lalu coba lagi.');
    }
}
