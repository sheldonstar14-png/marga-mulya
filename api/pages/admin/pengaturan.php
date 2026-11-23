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
            'misi'           => ['Misi (satu baris satu misi). Tulis (ilustrasi) di depan kalau belum dari dokumen resmi desa', 'area'],
            'sambutan_kades' => ['Sambutan kepala desa (pisahkan paragraf dengan satu baris kosong)', 'area'],
            'sambutan_nama'  => ['Nama di bawah sambutan', 'text'],
            'foto_kades'     => ['Foto kepala desa (opsional). Kosongkan untuk memakai gambar ilustrasi', 'foto'],
            'foto_kades_sumber' => ['Sumber foto kepala desa', 'text'],
            'sambutan_ilustrasi' => ['Tandai sambutan dan foto sebagai ilustrasi (lepas centang setelah memakai sambutan dan foto asli kepala desa)', 'cek'],
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
            'mode_privat' => ['Siapa yang boleh membuka website?', 'saklar'],
            'nama_desa'   => ['Nama desa', 'text'],
            'kecamatan'   => ['Kecamatan', 'text'],
            'kabupaten'   => ['Kabupaten', 'text'],
            'label_status' => ['Label kecil di atas judul beranda (misalnya status ilustrasi untuk lomba)', 'text'],
            'hero_judul'  => ['Judul di beranda', 'text'],
            'hero_teks'   => ['Teks pembuka beranda', 'area'],
            'foto_hero'   => ['Foto beranda (opsional). Pakai foto landscape asli, misalnya pantai atau perahu nelayan. Kosongkan untuk memakai gambar ilustrasi', 'foto'],
            'foto_hero_sumber' => ['Sumber foto beranda (siapa pemotret atau dari mana)', 'text'],
            'foto_hero_url' => ['Link sumber foto beranda (opsional)', 'text'],
            'alamat'      => ['Alamat kantor desa', 'text'],
            'jam_layanan' => ['Jam layanan', 'text'],
            'email'       => ['Email desa', 'text'],
            'wa'          => ['Nomor WhatsApp desa', 'text'],
            'lat'         => ['Lintang kantor desa (latitude)', 'text'],
            'lng'         => ['Bujur kantor desa (longitude)', 'text'],
            'gmaps_mymaps' => ['Link peta Google My Maps (opsional). Kalau diisi, peta di Profil dan Wisata langsung menampilkan semua titik. Buat di google.com/maps/d, lalu Bagikan > Sematkan, salin alamat https://www.google.com/maps/d/embed?mid=...', 'text'],
            'kredit_foto' => ['Kredit foto lama berbentuk teks (opsional). Lebih rapi pakai menu Sumber & kredit foto', 'area'],
            'kode_etik_lengkap' => ['Kode etik pengunjung per kelompok (satu baris satu aturan, format: Kelompok|Aturan)', 'area'],
            'kode_etik_umum' => ['Kode etik cadangan (dipakai kalau kode etik per kelompok kosong)', 'area'],
            'waktu_kunjung' => ['Waktu terbaik berkunjung secara umum (tampil di Wisata dan Cuaca)', 'area'],
            'catatan_logo' => ['Catatan asal logo (tampil di kaki halaman dan daftar kredit)', 'text'],
            'catatan_data' => ['Catatan di bawah data statistik (sumber dan status data)', 'area'],
            'referensi_data_nama' => ['Nama referensi data (tampil di bawah statistik)', 'text'],
            'referensi_data_url' => ['Link referensi data, misalnya halaman publikasi BPS (tempel link lengkapnya)', 'text'],
            'email_laporan' => ['Email penerima laporan bulanan, misalnya kepala desa (boleh lebih dari satu, pisahkan koma)', 'text'],
            'simpan_data_hari' => ['Lama penyimpanan data pribadi (hari, minimal 30). Setelah itu nama dan kontak dihapus otomatis', 'text'],
            'kontak_privasi' => ['Penanggung jawab data pribadi (tampil di bagian Privasi)', 'text'],
            'kata_terlarang' => ['Kata kasar yang membuat ulasan ditahan untuk ditinjau (pisahkan dengan koma)', 'area'],
        ],
    ],
];
// Pengaturan dibagi per bagian yang bisa dibuka-tutup supaya tidak panjang di HP
$bagianPengaturan = [
    'Status website'       => ['mode_privat'],
    'Identitas desa'       => ['nama_desa', 'kecamatan', 'kabupaten'],
    'Beranda'              => ['label_status', 'hero_judul', 'hero_teks', 'foto_hero', 'foto_hero_sumber', 'foto_hero_url'],
    'Kontak dan lokasi'    => ['alamat', 'jam_layanan', 'email', 'wa', 'lat', 'lng', 'gmaps_mymaps'],
    'Wisata dan pengunjung' => ['kode_etik_lengkap', 'kode_etik_umum', 'waktu_kunjung'],
    'Data, sumber, dan kredit' => ['catatan_data', 'referensi_data_nama', 'referensi_data_url', 'catatan_logo', 'kredit_foto'],
    'Privasi dan laporan'  => ['email_laporan', 'simpan_data_hari', 'kontak_privasi', 'kata_terlarang'],
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
        if ($baru[$kunci] === '' && !in_array($kunci, ['kredit_foto', 'email_laporan', 'foto_hero', 'foto_kades', 'referensi_data_url', 'referensi_data_nama', 'foto_hero_sumber', 'foto_hero_url', 'foto_kades_sumber', 'catatan_logo', 'gmaps_mymaps', 'label_status', 'kode_etik_umum', 'waktu_kunjung', 'sambutan_kades', 'sambutan_ilustrasi'], true)) $errors[] = "\"$label\" wajib diisi.";
    }
    if (isset($baru['wa'])) $baru['wa'] = nomor_wa($baru['wa']);
    foreach ($g['kunci'] as $kunci => [, $jenisK]) if ($jenisK === 'cek') $baru[$kunci] = !empty($_POST[$kunci]) ? '1' : '0';
    if (!empty($baru['gmaps_mymaps']) && !preg_match('#^https://(www\.)?google\.com/maps/d/#', $baru['gmaps_mymaps'])) $errors[] = 'Link Google My Maps harus diawali https://www.google.com/maps/d/.';
    if (!empty($baru['foto_hero_url']) && !preg_match('#^https?://#', $baru['foto_hero_url'])) $errors[] = 'Link sumber foto beranda harus diawali https://.';
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
<p class="muted small"><?= e($g['catatan']) ?> Tekan judul bagian untuk membuka.</p>
<?php if ($errors): ?>
  <div class="alert alert-err" role="alert"><b>Belum tersimpan:</b><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php
$bagianTampil = $sub === 'pengaturan' ? $bagianPengaturan : [$g['judul'] => array_keys($g['kunci'])];
$sudahTampil = array_merge(...array_values($bagianTampil));
$sisa = array_diff(array_keys($g['kunci']), $sudahTampil);
if ($sisa) $bagianTampil['Lainnya'] = array_values($sisa);
$adaError = (bool) $errors;
?>
<form method="post" action="/admin/<?= $sub ?>" style="max-width:760px" class="form-bagian">
  <?= csrf_field() ?>
  <?php $nomorBagian = 0; foreach ($bagianTampil as $namaBagian => $kunciBagian): $nomorBagian++; ?>
  <details class="panel bagian-form" <?= $nomorBagian === 1 || $adaError || count($bagianTampil) === 1 ? 'open' : '' ?>>
    <summary><?= e($namaBagian) ?></summary>
  <?php foreach ($kunciBagian as $kunci): if (!isset($g['kunci'][$kunci])) continue; [$label, $jenis] = $g['kunci'][$kunci];
    $nilai = $_POST[$kunci] ?? str_replace('\n', "\n", setting($kunci)); ?>
    <div class="field">
      <?php if ($jenis === 'cek'): ?>
        <label class="cek-setuju"><input type="checkbox" name="<?= $kunci ?>" value="1" <?= $nilai === '1' ? 'checked' : '' ?>> <span><?= e($label) ?></span></label>
      <?php elseif ($jenis === 'saklar'): ?>
        <fieldset class="saklar-privat">
          <legend><?= e($label) ?></legend>
          <label class="saklar-opsi"><input type="radio" name="<?= $kunci ?>" value="0" <?= $nilai !== '1' ? 'checked' : '' ?>><span><b>Terbuka untuk publik</b><small>Siapa pun bisa membuka website. <b>Wajib dipilih sebelum dikirim ke panitia.</b></small></span></label>
          <label class="saklar-opsi saklar-privat-1"><input type="radio" name="<?= $kunci ?>" value="1" <?= $nilai === '1' ? 'checked' : '' ?>><span><b>Privat, hanya tim</b><small>Website hanya bisa dibuka tim yang login. Untuk masa pengembangan.</small></span></label>
        </fieldset>
      <?php else: ?>
      <label for="k-<?= $kunci ?>"><?= e($label) ?></label>
      <?php endif; ?>
      <?php if ($jenis === 'saklar' || $jenis === 'cek'): ?>
      <?php elseif ($jenis === 'foto'): ?>
        <div class="img-prev" id="prev-<?= $kunci ?>"><?php if ($nilai): ?><img src="<?= e($nilai) ?>" alt="Foto saat ini"><?php endif; ?></div>
        <input type="file" accept="image/jpeg,image/png,image/webp" class="unggah-foto" data-target="k-<?= $kunci ?>" data-preview="prev-<?= $kunci ?>" style="margin-top:10px" aria-label="Pilih file foto">
        <div class="hint unggah-status" id="status-k-<?= $kunci ?>">Format JPG, PNG, atau WebP, maksimal 5 MB.</div>
        <input id="k-<?= $kunci ?>" name="<?= $kunci ?>" value="<?= e($nilai) ?>" placeholder="Atau tempel alamat gambar (https://...)" style="margin-top:8px">
      <?php elseif ($jenis === 'area'): ?>
        <textarea id="k-<?= $kunci ?>" name="<?= $kunci ?>" rows="<?= in_array($kunci, ['misi', 'kode_etik_umum', 'kode_etik_lengkap'], true) ? 8 : ($kunci === 'sambutan_kades' ? 12 : 3) ?>"><?= e($nilai) ?></textarea>
      <?php else: ?>
        <input id="k-<?= $kunci ?>" name="<?= $kunci ?>" value="<?= e($nilai) ?>">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  </details>
  <?php endforeach; ?>
  <div class="actions aksi-lekat"><button class="btn btn-dark" type="submit">Simpan <?= e(strtolower($g['judul'])) ?></button></div>
</form>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
