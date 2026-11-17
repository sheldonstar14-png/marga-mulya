<?php
// =========================================================
// GERBANG PRIVAT (mode pengembangan)
// Fungsinya sama dengan middleware Next.js: setiap permintaan diperiksa dulu sebelum halaman tampil.
//
// - Aktif hanya kalau Environment Variable SITE_PASSWORD diisi. Kalau dihapus, website terbuka untuk umum.
// - Password TIDAK disimpan di kode. Yang disimpan di cookie hanya tanda tangan HMAC (bukan password),
//   dengan kunci turunan SITE_PASSWORD + APP_SECRET. Kalau password diganti, semua yang sudah masuk otomatis keluar.
// - Selama aktif, semua halaman (termasuk CMS) diberi noindex,nofollow dan robots.txt menolak semua mesin pencari.
// =========================================================

const GERBANG_COOKIE = 'mm_gerbang';
const GERBANG_HARI = 7; // lama masuk sebelum harus memasukkan password lagi

function gerbang_aktif(): bool
{
    return (string) getenv('SITE_PASSWORD') !== '';
}

function gerbang_kunci(): string
{
    return hash_hmac('sha256', 'gerbang-situs|' . getenv('SITE_PASSWORD'), (string) getenv('APP_SECRET'));
}

function gerbang_sudah_masuk(): bool
{
    $isi = (string) ($_COOKIE[GERBANG_COOKIE] ?? '');
    if (!preg_match('/^(\d{10})\.([a-f0-9]{64})$/', $isi, $m)) return false;
    if ((int) $m[1] < time()) return false;
    return hash_equals(hash_hmac('sha256', $m[1], gerbang_kunci()), $m[2]);
}

function gerbang_pasang_cookie(int $habis): void
{
    $nilai = $habis > 0 ? $habis . '.' . hash_hmac('sha256', (string) $habis, gerbang_kunci()) : '';
    setcookie(GERBANG_COOKIE, $nilai, [
        'expires' => $habis > 0 ? $habis : time() - 3600,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Hanya izinkan kembali ke alamat di website ini sendiri (mencegah open redirect)
function gerbang_tujuan_aman(string $tujuan): string
{
    return preg_match('#^/(?!/)[^\s\\\\]*$#', $tujuan) ? $tujuan : '/';
}

/**
 * Dipanggil router sebelum memilih halaman. Mengembalikan true kalau permintaan sudah dijawab
 * (halaman login, logout, atau robots.txt) sehingga router harus berhenti.
 */
function gerbang_periksa(string $path): bool
{
    // robots.txt selalu dijawab di sini: menolak semua saat privat, mengizinkan saat publik
    if ($path === 'robots.txt') {
        header('Content-Type: text/plain; charset=utf-8');
        echo gerbang_aktif() ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nDisallow: /admin\n";
        return true;
    }
    if (!gerbang_aktif()) return false;

    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: private, no-store');

    // Jalur yang tetap boleh tanpa password: cron laporan (dilindungi CRON_SECRET sendiri) dan berkas PWA
    if (in_array($path, ['laporan-otomatis', 'sw.js', 'manifest.webmanifest'], true)) return false;

    if ($path === 'keluar-situs') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') gerbang_pasang_cookie(0);
        header('Location: /masuk-situs');
        http_response_code(303);
        return true;
    }

    $error = '';
    $tujuan = gerbang_tujuan_aman((string) ($_POST['lanjut'] ?? $_GET['lanjut'] ?? '/'));
    if ($path === 'masuk-situs') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kirim = (string) ($_POST['password'] ?? '');
            if ($kirim !== '' && hash_equals(hash('sha256', (string) getenv('SITE_PASSWORD')), hash('sha256', $kirim))) {
                gerbang_pasang_cookie(time() + GERBANG_HARI * 86400);
                header('Location: ' . $tujuan);
                http_response_code(303);
                return true;
            }
            sleep(2); // perlambat tebakan password
            $error = 'Password salah. Coba lagi.';
            http_response_code(401);
        } elseif (gerbang_sudah_masuk()) {
            header('Location: ' . $tujuan);
            http_response_code(303);
            return true;
        }
        require __DIR__ . '/../pages/masuk-situs.php';
        return true;
    }

    if (gerbang_sudah_masuk()) return false;

    // Belum masuk: arahkan ke halaman login, bawa alamat asal supaya bisa kembali
    $asal = $_SERVER['REQUEST_METHOD'] === 'GET' ? (string) ($_SERVER['REQUEST_URI'] ?? '/') : '/';
    header('Location: /masuk-situs?lanjut=' . rawurlencode(gerbang_tujuan_aman($asal)));
    http_response_code(303);
    return true;
}
