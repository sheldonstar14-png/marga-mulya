<?php
// =========================================================
// HALAMAN KELOLA KONTEN (dipakai semua menu di crud-config.php)
//   /admin/wisata                 -> daftar
//   /admin/wisata?aksi=tambah     -> form tambah
//   /admin/wisata?aksi=edit&id=3  -> form edit
//   POST aksi=simpan / aksi=hapus -> simpan / hapus
// =========================================================
$c = $CRUD[$kunciCrud];
if (!empty($c['superAdmin'])) {
    wajib_super_admin($admin);
}
$tabel = $c['tabel'];
$alamat = '/admin/' . $kunciCrud;
$errors = [];

// Pilihan dropdown: dari pengaturan + pilihan baru yang pernah ditambahkan admin
function pilihan_dropdown(array $c, string $nama, array $opsi): array
{
    $pilihan = $opsi['pilihan'];
    if (!empty($opsi['bisaTambah'])) {
        foreach (q("SELECT DISTINCT $nama AS v FROM {$c['tabel']} ORDER BY $nama") as $b) {
            if ($b['v'] !== null && $b['v'] !== '' && !in_array($b['v'], $pilihan, true)) $pilihan[] = $b['v'];
        }
    }
    return $pilihan;
}

// ---------- Ambil isian form dan periksa ----------
function ambil_isian(array $c, ?int $id, array &$errors): array
{
    $data = [];
    foreach ($c['field'] as [$nama, $label, $jenis, $opsi]) {
        $nilai = is_array($_POST[$nama] ?? null) ? '' : trim((string) ($_POST[$nama] ?? ''));
        $wajib = !empty($opsi['wajib']);

        switch ($jenis) {
            case 'check':
                $data[$nama] = isset($_POST[$nama]) ? 1 : 0;
                break;
            case 'number':
                if ($nilai === '' || !is_numeric($nilai)) { $errors[] = "\"$label\" harus berupa angka."; break; }
                $angka = !empty($opsi['desimal']) ? (float) $nilai : (int) $nilai;
                if (isset($opsi['min']) && $angka < $opsi['min']) $errors[] = "\"$label\" minimal {$opsi['min']}.";
                $data[$nama] = $angka;
                break;
            case 'date':
                $t = DateTime::createFromFormat('Y-m-d', $nilai);
                if (!$t || $t->format('Y-m-d') !== $nilai) { $errors[] = "\"$label\" tidak valid."; break; }
                $data[$nama] = $nilai;
                break;
            case 'select':
                if (!empty($opsi['bisaTambah']) && $nilai === '__baru__') {
                    $baru = trim(preg_replace('/\s+/', ' ', (string) ($_POST[$nama . '__baru'] ?? '')));
                    if (mb_strlen($baru) < 2 || mb_strlen($baru) > 40) { $errors[] = "Pilihan baru untuk \"$label\" harus 2 sampai 40 karakter."; break; }
                    $data[$nama] = $baru;
                    break;
                }
                if (!in_array($nilai, pilihan_dropdown($c, $nama, $opsi), true)) { $errors[] = "Pilih \"$label\" dari daftar."; break; }
                $data[$nama] = $nilai;
                break;
            case 'akses':
                $dipilih = array_intersect(array_keys(MENU_PETUGAS), (array) ($_POST['akses'] ?? []));
                $data[$nama] = implode(',', $dipilih);
                break;
            case 'peta':
                $lat = trim((string) ($_POST['lat'] ?? ''));
                $lng = trim((string) ($_POST['lng'] ?? ''));
                if ($lat === '' && $lng === '' && $c['tabel'] !== 'wisata') { $data['lat'] = null; $data['lng'] = null; break; }
                if (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) { $errors[] = 'Pilih lokasi dengan klik peta, atau isi lintang dan bujur dengan benar.'; break; }
                $data['lat'] = round((float) $lat, 6);
                $data['lng'] = round((float) $lng, 6);
                break;
            case 'foto':
                if ($nilai === '') {
                    if ($wajib) $errors[] = "\"$label\" wajib diisi. Unggah foto atau tempel alamat gambar.";
                    $data[$nama] = $wajib ? '' : null;
                    break;
                }
                if (!preg_match('#^(https://|/assets/)#', $nilai)) { $errors[] = 'Alamat foto harus diawali https:// atau /assets/.'; break; }
                $data[$nama] = mb_substr($nilai, 0, 255);
                break;
            case 'password':
                if ($nilai === '' && $id !== null) break; // saat edit: kosong = kata sandi tidak diubah
                if (strlen($nilai) < 8) { $errors[] = 'Kata sandi minimal 8 karakter.'; break; }
                $data[$nama] = password_hash($nilai, PASSWORD_DEFAULT);
                break;
            default: // text, textarea
                if ($wajib && $nilai === '') { $errors[] = "\"$label\" wajib diisi."; break; }
                if (isset($opsi['maks']) && mb_strlen($nilai) > $opsi['maks']) { $errors[] = "\"$label\" maksimal {$opsi['maks']} karakter."; break; }
                $data[$nama] = $nilai;
        }
    }
    return $data;
}

// ---------- Simpan atau hapus ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_csrf();
    $aksi = $_POST['aksi'] ?? '';
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;

    if ($aksi === 'hapus' && $id) {
        $baris = q1("SELECT * FROM $tabel WHERE id = ?", [$id]);
        $alasan = $baris && isset($c['cekHapus']) ? $c['cekHapus']($baris, $admin) : null;
        if (!$baris) redirect($alamat . '?gagal=' . urlencode('Data tidak ditemukan.'));
        if ($alasan) redirect($alamat . '?gagal=' . urlencode($alasan));
        q("DELETE FROM $tabel WHERE id = ?", [$id]);
        redirect($alamat . '?ok=' . urlencode(ucfirst($c['satu']) . ' dihapus.'));
    }

    if ($aksi === 'simpan') {
        $data = ambil_isian($c, $id, $errors);
        if (!$errors && isset($c['cekSimpan'])) {
            $pesan = $c['cekSimpan']($data, $id, $admin);
            if ($pesan) $errors[] = $pesan;
        }
        if (!$errors) {
            if (isset($c['sebelumSimpan'])) $c['sebelumSimpan']($data, $id);
            $kolom = array_keys($data);
            if ($id === null) {
                q("INSERT INTO $tabel (" . implode(', ', $kolom) . ') VALUES (' . implode(', ', array_fill(0, count($kolom), '?')) . ')', array_values($data));
            } else {
                q("UPDATE $tabel SET " . implode(', ', array_map(fn($k) => "$k = ?", $kolom)) . ' WHERE id = ?', [...array_values($data), $id]);
            }
            $kembali = !empty($c['saring']) && isset($data[$c['saring']]) ? $alamat . '?' . $c['saring'] . '=' . urlencode($data[$c['saring']]) . '&' : $alamat . '?';
            redirect($kembali . 'ok=' . urlencode(ucfirst($c['satu']) . ' disimpan.'));
        }
        // Ada error: form ditampilkan lagi dengan isian sebelumnya
        $_GET['aksi'] = $id === null ? 'tambah' : 'edit';
        $_GET['id'] = $id;
        $isianForm = array_merge($_POST, ['lat' => $_POST['lat'] ?? '', 'lng' => $_POST['lng'] ?? '']);
    }
}

$aksi = $_GET['aksi'] ?? '';
$pakaiPeta = in_array($aksi, ['tambah', 'edit'], true) && in_array('peta', array_column($c['field'], 2), true);
$judulAdmin = $c['judul'];
$menuAdmin = $kunciCrud;
require __DIR__ . '/../../inc/admin-header.php';

// ======================= FORM TAMBAH / EDIT =======================
if (in_array($aksi, ['tambah', 'edit'], true)):
    $id = $aksi === 'edit' ? (int) ($_GET['id'] ?? 0) : null;
    $baris = $id ? q1("SELECT * FROM $tabel WHERE id = ?", [$id]) : ($c['baru'] ?? []);
    if ($id && !$baris) {
        echo '<p>Data tidak ditemukan. <a href="' . $alamat . '">Kembali</a></p>';
        require __DIR__ . '/../../inc/admin-footer.php';
        return;
    }
    $nilai = $isianForm ?? $baris;
    ?>
    <p><a href="<?= $alamat ?>">Kembali ke daftar</a></p>
    <h2><?= $id ? 'Edit' : 'Tambah' ?> <?= e($c['satu']) ?></h2>
    <?php if ($errors): ?>
      <div class="alert alert-err" role="alert"><b>Belum tersimpan:</b><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form class="panel" method="post" action="<?= $alamat ?>" style="max-width:760px">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= $id ?: '' ?>">
      <?php foreach ($c['field'] as [$nama, $label, $jenis, $opsi]):
        $v = $nilai[$nama] ?? '';
        $contoh = isset($opsi['contoh']) ? ' placeholder="Contoh: ' . e($opsi['contoh']) . '"' : '';
        $wajib = !empty($opsi['wajib']) ? ' required' : ''; ?>
        <div class="field">
        <?php if ($jenis === 'textarea'): ?>
          <label for="f-<?= $nama ?>"><?= e($label) ?></label>
          <textarea id="f-<?= $nama ?>" name="<?= $nama ?>" rows="5"<?= $wajib ?>><?= e($v) ?></textarea>
        <?php elseif ($jenis === 'select'): ?>
          <label for="f-<?= $nama ?>"><?= e($label) ?></label>
          <select id="f-<?= $nama ?>" name="<?= $nama ?>" <?= !empty($opsi['bisaTambah']) ? 'class="bisa-tambah"' : '' ?>>
            <?php foreach (pilihan_dropdown($c, $nama, $opsi) as $p): ?><option <?= (string) $v === $p ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?>
            <?php if (!empty($opsi['bisaTambah'])): ?><option value="__baru__" <?= $v === '__baru__' ? 'selected' : '' ?>>+ Tambah pilihan baru...</option><?php endif; ?>
          </select>
          <?php if (!empty($opsi['bisaTambah'])): ?>
            <input id="f-<?= $nama ?>-baru" name="<?= $nama ?>__baru" class="pilihan-baru" value="<?= e($nilai[$nama . '__baru'] ?? '') ?>" placeholder="Ketik pilihan baru, contoh: Edukasi" maxlength="40" style="margin-top:8px" aria-label="Pilihan baru untuk <?= e($label) ?>">
          <?php endif; ?>
        <?php elseif ($jenis === 'akses'):
          $terpilih = $v === null ? array_keys(MENU_PETUGAS) : (is_array($v) ? $v : explode(',', (string) $v)); ?>
          <fieldset class="akses-box" style="border:0;padding:0;margin:0">
            <legend style="font-weight:700;margin-bottom:8px"><?= e($label) ?></legend>
            <div class="chips" style="margin-bottom:10px">
              <button type="button" class="chip preset-akses" data-akses="berita,galeri">Paket jurnalis</button>
              <button type="button" class="chip preset-akses" data-akses="statistik,profil,aparat">Paket pengelola data</button>
              <button type="button" class="chip preset-akses" data-akses="reservasi,pesan,wisata,budaya,umkm">Paket pengelola wisata</button>
              <button type="button" class="chip preset-akses" data-akses="">Kosongkan</button>
            </div>
            <div class="cek-grid">
              <?php foreach (MENU_PETUGAS as $kunciMenu => $labelMenu): ?>
                <label class="cek"><input type="checkbox" name="akses[]" value="<?= $kunciMenu ?>" <?= in_array($kunciMenu, $terpilih, true) ? 'checked' : '' ?>> <?= e($labelMenu) ?></label>
              <?php endforeach; ?>
            </div>
            <div class="hint">Super admin otomatis bisa membuka semua menu, termasuk Akun admin dan Pengaturan.</div>
          </fieldset>
        <?php elseif ($jenis === 'check'): ?>
          <label style="display:flex;gap:10px;align-items:center;font-weight:400">
            <input type="checkbox" name="<?= $nama ?>" value="1" style="width:22px;height:22px" <?= !empty($v) ? 'checked' : '' ?>> <?= e($label) ?>
          </label>
        <?php elseif ($jenis === 'peta'):
          $lat = $nilai['lat'] ?? ''; $lng = $nilai['lng'] ?? ''; ?>
          <label><?= e($label) ?></label>
          <div class="peta peta-kecil peta-pilih" data-lat="<?= e($lat) ?>" data-lng="<?= e($lng) ?>"
               data-pusat-lat="<?= e(setting('lat')) ?>" data-pusat-lng="<?= e(setting('lng')) ?>" role="region" aria-label="Peta untuk memilih lokasi"></div>
          <div class="grid g2" style="gap:12px;margin-top:10px">
            <div><label for="lat" class="small">Lintang (latitude)</label><input id="lat" name="lat" value="<?= e($lat) ?>" inputmode="decimal"></div>
            <div><label for="lng" class="small">Bujur (longitude)</label><input id="lng" name="lng" value="<?= e($lng) ?>" inputmode="decimal"></div>
          </div>
          <div class="hint">Klik peta untuk memindahkan titik. Cuaca destinasi mengikuti titik ini.</div>
        <?php elseif ($jenis === 'foto'): ?>
          <label for="f-<?= $nama ?>"><?= e($label) ?></label>
          <div class="img-prev" id="prev-<?= $nama ?>"><?php if ($v): ?><img src="<?= e($v) ?>" alt="Foto saat ini"><?php endif; ?></div>
          <input type="file" accept="image/jpeg,image/png,image/webp" class="unggah-foto" data-target="f-<?= $nama ?>" data-preview="prev-<?= $nama ?>" style="margin-top:10px" aria-label="Pilih file foto">
          <div class="hint unggah-status" id="status-f-<?= $nama ?>">Format JPG, PNG, atau WebP, maksimal 5 MB.</div>
          <input id="f-<?= $nama ?>" name="<?= $nama ?>" value="<?= e($v) ?>" placeholder="Atau tempel alamat gambar (https://...)" style="margin-top:8px">
        <?php elseif ($jenis === 'password'): ?>
          <label for="f-<?= $nama ?>"><?= e($label) ?></label>
          <input id="f-<?= $nama ?>" name="<?= $nama ?>" type="password" autocomplete="new-password" minlength="8" <?= $id ? '' : 'required' ?>>
          <div class="hint"><?= $id ? 'Kosongkan kalau kata sandi tidak diganti. ' : '' ?>Minimal 8 karakter.</div>
        <?php else: ?>
          <label for="f-<?= $nama ?>"><?= e($label) ?></label>
          <input id="f-<?= $nama ?>" name="<?= $nama ?>" type="<?= $jenis === 'number' ? 'number' : ($jenis === 'date' ? 'date' : 'text') ?>"
                 value="<?= e($v) ?>"<?= $contoh ?><?= $wajib ?><?= $jenis === 'number' ? ' step="' . (!empty($opsi['desimal']) ? 'any' : '1') . '"' : '' ?>
                 <?= isset($opsi['maks']) ? 'maxlength="' . $opsi['maks'] . '"' : '' ?>>
        <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <div class="actions"><button class="btn btn-dark" type="submit">Simpan</button><a class="btn btn-ghost" href="<?= $alamat ?>">Batal</a></div>
    </form>

<?php
// ======================= DAFTAR =======================
else:
    $saring = $c['saring'] ?? null;
    $pilihanSaring = $saring ? array_column(q("SELECT DISTINCT $saring FROM $tabel ORDER BY $saring"), $saring) : [];
    $nilaiSaring = $saring ? ($_GET[$saring] ?? ($pilihanSaring[0] ?? '')) : null;
    $daftar = $saring
        ? q("SELECT * FROM $tabel WHERE $saring = ? ORDER BY {$c['urut']}", [$nilaiSaring])
        : q("SELECT * FROM $tabel ORDER BY {$c['urut']}");
    $format = $c['format'] ?? [];
    ?>
    <div class="head-row"><h2><?= e($c['judul']) ?></h2><a class="btn btn-main btn-sm" href="<?= $alamat ?>?aksi=tambah">Tambah <?= e($c['satu']) ?></a></div>
    <?php if (!empty($c['catatan'])): ?><p class="muted"><?= e($c['catatan']) ?></p><?php endif; ?>
    <?php if ($saring): ?>
      <nav class="chips" aria-label="Saring">
        <?php foreach ($pilihanSaring as $p): ?><a class="chip <?= $p === $nilaiSaring ? 'on' : '' ?>" href="<?= $alamat ?>?<?= $saring ?>=<?= urlencode($p) ?>"><?= e($p) ?></a><?php endforeach; ?>
      </nav>
    <?php endif; ?>
    <div class="panel table-wrap">
      <table class="stack">
        <thead><tr><?php foreach ($c['kolom'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($daftar as $baris): ?>
          <tr>
            <?php foreach ($c['kolom'] as $kol => $label):
              $v = $baris[$kol] ?? '';
              $f = $format[$kol] ?? ''; ?>
              <td data-l="<?= e($label) ?>" class="<?= in_array($f, ['rupiah', 'angka'], true) ? 'num' : '' ?>">
                <?php if ($f === 'foto'): ?><div class="thumb"><?php if ($v): ?><img src="<?= e($v) ?>" alt=""><?php endif; ?></div>
                <?php elseif ($f === 'rupiah'): ?><?= rupiah($v) ?>
                <?php elseif ($f === 'angka'): ?><?= angka($v) ?>
                <?php elseif ($f === 'tanggal'): ?><?= tgl($v) ?>
                <?php elseif ($f === 'akses'): ?><?= $baris['peran'] === 'Super admin' ? 'Semua menu' : (e(implode(', ', array_map(fn($k) => MENU_PETUGAS[$k], akses_admin($baris)))) ?: '<span class="muted">Belum ada</span>') ?>
                <?php elseif ($f === 'yatidak'): ?><?= $v ? 'Ya' : '<span class="status st-Dibatalkan">Disembunyikan</span>' ?>
                <?php else: ?><?= e($v) ?><?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td data-l="Aksi">
              <div class="actions">
                <a class="btn btn-dark btn-sm" href="<?= $alamat ?>?aksi=edit&id=<?= (int) $baris['id'] ?>">Edit</a>
                <form method="post" action="<?= $alamat ?>" class="form-hapus" data-nama="<?= e($baris['nama'] ?? $baris['judul'] ?? $baris['produk'] ?? $baris['label'] ?? 'data ini') ?>">
                  <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int) $baris['id'] ?>">
                  <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?><tr><td colspan="<?= count($c['kolom']) + 1 ?>" class="muted">Belum ada <?= e($c['satu']) ?>. Klik "Tambah <?= e($c['satu']) ?>" untuk mulai.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
<?php endif;
require __DIR__ . '/../../inc/admin-footer.php';
