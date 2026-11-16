<?php
// =========================================================
// ROUTER CMS: /admin, /admin/dashboard, /admin/wisata, dst.
// =========================================================
require __DIR__ . '/../../inc/auth.php';
require __DIR__ . '/../../inc/crud-config.php';

$sub = $parts[1] ?? '';

if ($sub === '') {
    require __DIR__ . '/login.php';
    return;
}
if ($sub === 'keluar') {
    akhiri_login();
    redirect('/admin?ok=' . urlencode('Kamu sudah keluar.'));
}

$admin = wajib_login(); // semua halaman di bawah ini wajib login

$halamanAdmin = [
    'dashboard'   => 'dashboard.php',
    'reservasi'   => 'reservasi.php',
    'pesan'       => 'pesan.php',
    'profil'      => 'pengaturan.php',
    'pengaturan'  => 'pengaturan.php',
    'upload-sign' => 'upload-sign.php',
];

// Cek hak akses menu ini (upload foto boleh bagi yang mengelola konten berfoto)
$bolehBuka = $sub === 'upload-sign'
    ? (boleh($admin, 'wisata') || boleh($admin, 'umkm') || boleh($admin, 'berita') || boleh($admin, 'galeri'))
    : boleh($admin, $sub);
if ((isset($halamanAdmin[$sub]) || isset($CRUD[$sub])) && !$bolehBuka) {
    http_response_code(403);
    if ($sub === 'upload-sign') {
        exit(json_encode(['error' => 'Akun ini tidak punya akses upload foto.']));
    }
    $judulAdmin = 'Tidak ada akses';
    require __DIR__ . '/../../inc/admin-header.php';
    echo '<h2>Menu ini tidak tersedia untuk akunmu</h2><p class="muted">Minta Super admin menambahkan akses lewat menu Akun admin kalau kamu memang perlu membukanya.</p><p><a class="btn btn-dark btn-sm" href="/admin/dashboard">Kembali ke dashboard</a></p>';
    require __DIR__ . '/../../inc/admin-footer.php';
    return;
}

if (isset($halamanAdmin[$sub])) {
    require __DIR__ . '/' . $halamanAdmin[$sub];
} elseif (isset($CRUD[$sub])) {
    $kunciCrud = $sub;
    require __DIR__ . '/crud.php';
} else {
    http_response_code(404);
    $judulAdmin = 'Tidak ditemukan';
    require __DIR__ . '/../../inc/admin-header.php';
    echo '<h2>Menu tidak ditemukan</h2><p><a href="/admin/dashboard">Kembali ke dashboard</a></p>';
    require __DIR__ . '/../../inc/admin-footer.php';
}
