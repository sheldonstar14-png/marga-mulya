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
