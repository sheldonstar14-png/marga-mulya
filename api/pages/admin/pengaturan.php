<?php
// =========================================================
// PROFIL DESA (/admin/profil) dan PENGATURAN UMUM (/admin/pengaturan)
// Keduanya mengubah tabel pengaturan (kunci -> nilai).
// =========================================================
$grup = [
    'profil' => [
        'judul' => 'Profil desa',
        'catatan' => 'Isi halaman Profil. Daftar perangkat desa diatur di menu Perangkat desa, angka lahan dan pemerintahan di menu Statistik desa.',
        'kunci' => [
            'profil_ringkas' => ['Ringkasan desa (paragraf pembuka)', 'area'],
            'sejarah'        => ['Sejarah singkat', 'area'],
            'visi'           => ['Visi', 'area'],
            'misi'           => ['Misi (satu baris satu misi)', 'area'],
            'batas_utara'    => ['Batas utara', 'text'],
            'batas_timur'    => ['Batas timur', 'text'],
            'batas_selatan'  => ['Batas selatan', 'text'],
            'batas_barat'    => ['Batas barat', 'text'],
        ],
    ],
    'pengaturan' => [
        'judul' => 'Pengaturan',
        'catatan' => 'Teks umum yang tampil di seluruh website. Perubahan langsung terlihat setelah disimpan.',
        'kunci' => [
            'nama_desa'   => ['Nama desa', 'text'],
            'kecamatan'   => ['Kecamatan', 'text'],
            'kabupaten'   => ['Kabupaten', 'text'],
            'hero_judul'  => ['Judul di beranda', 'text'],
            'hero_teks'   => ['Teks pembuka beranda', 'area'],
            'alamat'      => ['Alamat kantor desa', 'text'],
            'jam_layanan' => ['Jam layanan', 'text'],
            'email'       => ['Email desa', 'text'],
            'wa'          => ['Nomor WhatsApp desa', 'text'],
            'lat'         => ['Lintang kantor desa (latitude)', 'text'],
            'lng'         => ['Bujur kantor desa (longitude)', 'text'],
        ],
    ],
];
$g = $grup[$sub];
if ($sub === 'pengaturan') {
    wajib_super_admin($admin);
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $baru = [];
    foreach ($g['kunci'] as $kunci => [$label]) {
        $baru[$kunci] = trim((string) ($_POST[$kunci] ?? ''));
        if ($baru[$kunci] === '') $errors[] = "\"$label\" wajib diisi.";
    }
    if (isset($baru['wa'])) $baru['wa'] = nomor_wa($baru['wa']);
    if (isset($baru['lat']) && (!is_numeric($baru['lat']) || !is_numeric($baru['lng']))) $errors[] = 'Lintang dan bujur harus berupa angka, contoh -7.205 dan 107.570.';

    if (!$errors) {
        foreach ($baru as $kunci => $nilai) {
            // Perbarui kalau kunci sudah ada, tambahkan kalau belum
            if (q1('SELECT kunci FROM pengaturan WHERE kunci = ?', [$kunci])) {
                q('UPDATE pengaturan SET nilai = ? WHERE kunci = ?', [$nilai, $kunci]);
            } else {
                q('INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?)', [$kunci, $nilai]);
            }
        }
        redirect('/admin/' . $sub . '?ok=' . urlencode($g['judul'] . ' disimpan.'));
    }
}

$judulAdmin = $g['judul'];
$menuAdmin = $sub;
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2><?= e($g['judul']) ?></h2>
<p class="muted"><?= e($g['catatan']) ?></p>
<?php if ($errors): ?>
  <div class="alert alert-err" role="alert"><b>Belum tersimpan:</b><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form class="panel" method="post" action="/admin/<?= $sub ?>" style="max-width:760px">
  <?= csrf_field() ?>
  <?php foreach ($g['kunci'] as $kunci => [$label, $jenis]):
    $nilai = $_POST[$kunci] ?? str_replace('\n', "\n", setting($kunci)); ?>
    <div class="field">
      <label for="k-<?= $kunci ?>"><?= e($label) ?></label>
      <?php if ($jenis === 'area'): ?>
        <textarea id="k-<?= $kunci ?>" name="<?= $kunci ?>" rows="<?= $kunci === 'misi' ? 5 : 3 ?>"><?= e($nilai) ?></textarea>
      <?php else: ?>
        <input id="k-<?= $kunci ?>" name="<?= $kunci ?>" value="<?= e($nilai) ?>">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-dark" type="submit">Simpan <?= e(strtolower($g['judul'])) ?></button>
</form>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
