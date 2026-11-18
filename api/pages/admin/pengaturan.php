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
            'mode_privat' => ['Mode privat: isi 1 supaya website hanya bisa dibuka tim yang login (masa pengembangan), isi 0 untuk membuka ke publik. WAJIB 0 sebelum dikirim ke panitia', 'text'],
            'nama_desa'   => ['Nama desa', 'text'],
            'kecamatan'   => ['Kecamatan', 'text'],
            'kabupaten'   => ['Kabupaten', 'text'],
            'hero_judul'  => ['Judul di beranda', 'text'],
            'hero_teks'   => ['Teks pembuka beranda', 'area'],
            'foto_hero'   => ['Foto lingkaran di beranda (opsional). Pakai foto landscape desa asli; bagian tengah foto yang tampil di lingkaran', 'foto'],
            'alamat'      => ['Alamat kantor desa', 'text'],
            'jam_layanan' => ['Jam layanan', 'text'],
            'email'       => ['Email desa', 'text'],
            'wa'          => ['Nomor WhatsApp desa', 'text'],
            'lat'         => ['Lintang kantor desa (latitude)', 'text'],
            'lng'         => ['Bujur kantor desa (longitude)', 'text'],
            'kredit_foto' => ['Kredit foto lama berbentuk teks (opsional). Lebih rapi pakai menu Sumber & kredit foto', 'area'],
            'kode_etik_umum' => ['Kode etik pengunjung untuk semua destinasi (satu baris satu aturan)', 'area'],
            'info_bank_sampah' => ['Info bank sampah: tempat dan jadwal buka', 'area'],
            'harga_sampah' => ['Harga sampah (satu baris satu jenis, format: Jenis|Harga)', 'area'],
            'catatan_data' => ['Catatan di bawah data statistik (sumber dan status data)', 'area'],
            'referensi_data_nama' => ['Nama referensi data (tampil di bawah statistik)', 'text'],
            'referensi_data_url' => ['Link referensi data, misalnya halaman publikasi BPS (tempel link lengkapnya)', 'text'],
            'email_laporan' => ['Email penerima laporan bulanan, misalnya kepala desa (boleh lebih dari satu, pisahkan koma)', 'text'],
            'tiket_hijau_aktif' => ['Tiket Hijau aktif? Isi 1 untuk aktif, 0 untuk mati', 'text'],
            'tiket_hijau_skor' => ['Skor hijau yang mendapat kupon (pisahkan koma, contoh A,B)', 'text'],
            'tiket_hijau_nilai' => ['Nilai kupon dalam rupiah (angka saja, untuk laporan dampak)', 'text'],
            'tiket_hijau_syarat' => ['Syarat kupon Tiket Hijau (tampil di tiket dan email)', 'area'],
            'simpan_data_hari' => ['Lama penyimpanan data pribadi (hari, minimal 30). Setelah itu nama dan kontak dihapus otomatis', 'text'],
            'kontak_privasi' => ['Penanggung jawab data pribadi (tampil di bagian Privasi)', 'text'],
            'kata_terlarang' => ['Kata kasar yang membuat ulasan ditahan untuk ditinjau (pisahkan dengan koma)', 'area'],
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
        if ($baru[$kunci] === '' && !in_array($kunci, ['kredit_foto', 'email_laporan', 'foto_hero', 'foto_kades', 'referensi_data_url', 'referensi_data_nama'], true)) $errors[] = "\"$label\" wajib diisi.";
    }
    if (isset($baru['wa'])) $baru['wa'] = nomor_wa($baru['wa']);
    if (!empty($baru['email_laporan'])) foreach (array_map('trim', explode(',', $baru['email_laporan'])) as $em) if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $errors[] = "Email penerima laporan \"$em\" tidak valid.";
    if (!empty($baru['foto_kades']) && !preg_match('#^(https://|/assets/)#', $baru['foto_kades'])) $errors[] = 'Alamat foto kepala desa harus diawali https:// atau /assets/.';
    if (!empty($baru['foto_hero']) && !preg_match('#^(https://|/assets/)#', $baru['foto_hero'])) $errors[] = 'Alamat foto beranda harus diawali https:// atau /assets/.';
    if (isset($baru['mode_privat']) && !in_array($baru['mode_privat'], ['0', '1'], true)) $errors[] = 'Mode privat diisi 0 atau 1.';
    if (isset($baru['simpan_data_hari']) && (!ctype_digit($baru['simpan_data_hari']) || (int) $baru['simpan_data_hari'] < 30)) $errors[] = 'Lama penyimpanan data pribadi berupa angka hari, minimal 30.';
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
      <?php if ($jenis === 'foto'): ?>
        <div class="img-prev" id="prev-<?= $kunci ?>"><?php if ($nilai): ?><img src="<?= e($nilai) ?>" alt="Foto saat ini"><?php endif; ?></div>
        <input type="file" accept="image/jpeg,image/png,image/webp" class="unggah-foto" data-target="k-<?= $kunci ?>" data-preview="prev-<?= $kunci ?>" style="margin-top:10px" aria-label="Pilih file foto">
        <div class="hint unggah-status" id="status-k-<?= $kunci ?>">Format JPG, PNG, atau WebP, maksimal 5 MB. Kosongkan untuk memakai foto destinasi wisata pertama.</div>
        <input id="k-<?= $kunci ?>" name="<?= $kunci ?>" value="<?= e($nilai) ?>" placeholder="Atau tempel alamat gambar (https://...)" style="margin-top:8px">
      <?php elseif ($jenis === 'area'): ?>
        <textarea id="k-<?= $kunci ?>" name="<?= $kunci ?>" rows="<?= in_array($kunci, ['misi', 'kode_etik_umum', 'sambutan_kades'], true) ? 6 : 3 ?>"><?= e($nilai) ?></textarea>
      <?php else: ?>
        <input id="k-<?= $kunci ?>" name="<?= $kunci ?>" value="<?= e($nilai) ?>">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-dark" type="submit">Simpan <?= e(strtolower($g['judul'])) ?></button>
</form>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
