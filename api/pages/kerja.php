<?php
// =========================================================
// HALAMAN KERJA DAN PELATIHAN (/kerja), Fase 12t
// 1. Kerja di desa  : peluang dari warga, UMKM, BUMDes, kelompok nelayan, Pokdarwis.
//                     Diajukan lewat form di bawah, tampil setelah diperiksa petugas.
// 2. Lowongan resmi : diteruskan petugas dari sumber pemerintah (Karirhub Kemnaker, Disnaker).
// 3. Pelatihan      : pelatihan kerja gratis dari pemerintah dan kegiatan desa.
// Pengaman: lowongan hilang otomatis setelah tanggal batas, website tidak menerima lamaran/CV,
// dan selalu ada peringatan bahwa lowongan asli tidak memungut biaya.
// =========================================================
const JENIS_LOKER = ['Kerja di desa', 'Lowongan resmi', 'Pelatihan'];
const WAKTU_KERJA = ['Harian', 'Paruh waktu', 'Akhir pekan', 'Musiman', 'Kontrak', 'Penuh waktu'];

$isiLk = ['jenis' => 'Kerja di desa', 'judul' => '', 'pemberi' => '', 'lokasi' => '', 'waktu_kerja' => 'Harian', 'upah' => '', 'kebutuhan' => '1', 'syarat' => '', 'wa' => '', 'batas' => ''];
$errorLk = [];
$batasMaks = date('Y-m-d', strtotime('+60 days'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'pasang') {
    foreach ($isiLk as $k => $_) $isiLk[$k] = trim((string) ($_POST[$k] ?? ''));
    $waLk = nomor_wa($isiLk['wa']);
    if (!empty($_POST['website'])) $errorLk[] = 'Pengajuan ditolak.';
    if (!in_array($isiLk['jenis'], ['Kerja di desa', 'Pelatihan'], true)) $errorLk[] = 'Pilih jenis peluang.';
    if (mb_strlen($isiLk['judul']) < 5 || mb_strlen($isiLk['judul']) > 150) $errorLk[] = 'Isi pekerjaan atau pelatihan yang ditawarkan (5 sampai 150 huruf).';
    if (mb_strlen($isiLk['pemberi']) < 3 || mb_strlen($isiLk['pemberi']) > 150) $errorLk[] = 'Isi nama usaha atau pemberi kerja beserta RT.';
    if (mb_strlen($isiLk['lokasi']) < 3 || mb_strlen($isiLk['lokasi']) > 150) $errorLk[] = 'Isi lokasi kerja.';
    if (!in_array($isiLk['waktu_kerja'], WAKTU_KERJA, true) && $isiLk['jenis'] === 'Kerja di desa') $errorLk[] = 'Pilih waktu kerja.';
    if (mb_strlen($isiLk['upah']) > 100) $errorLk[] = 'Keterangan upah maksimal 100 huruf.';
    if (!ctype_digit($isiLk['kebutuhan']) || (int) $isiLk['kebutuhan'] < 1 || (int) $isiLk['kebutuhan'] > 500) $errorLk[] = 'Jumlah orang yang dibutuhkan diisi angka 1 sampai 500.';
    if (mb_strlen($isiLk['syarat']) < 5 || mb_strlen($isiLk['syarat']) > 800) $errorLk[] = 'Tulis syarat atau keterangan singkat (5 sampai 800 huruf).';
    if (!preg_match('/^628\d{7,11}$/', $waLk)) $errorLk[] = 'Nomor WhatsApp belum benar. Contoh: 081234567890.';
    $tglBatas = DateTime::createFromFormat('Y-m-d', $isiLk['batas']);
    if (!$tglBatas || $isiLk['batas'] < date('Y-m-d') || $isiLk['batas'] > $batasMaks) $errorLk[] = 'Pilih batas pendaftaran antara hari ini dan 60 hari ke depan.';
    if (empty($_POST['tanpa_biaya'])) $errorLk[] = 'Centang pernyataan bahwa peluang ini tidak memungut biaya apa pun dari pelamar.';
    if (empty($_POST['setuju_data'])) $errorLk[] = 'Centang persetujuan supaya nomor WhatsApp bisa ditampilkan untuk pelamar.';
    // Satu nomor maksimal 3 pengajuan yang menunggu diperiksa
    if (!$errorLk && (int) q1("SELECT COUNT(*) AS n FROM loker WHERE wa = ? AND status = 'Menunggu'", [$waLk])['n'] >= 3) $errorLk[] = 'Nomor ini sudah punya 3 pengajuan yang menunggu diperiksa. Tunggu kabar dari petugas dulu, ya.';
    if (!$errorLk) {
        $kodeLk = kode_unik('LKR', 'loker');
        q('INSERT INTO loker (kode, jenis, judul, pemberi, lokasi, waktu_kerja, upah, kebutuhan, syarat, cara_daftar, wa, batas, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$kodeLk, $isiLk['jenis'], $isiLk['judul'], $isiLk['pemberi'], $isiLk['lokasi'], $isiLk['jenis'] === 'Pelatihan' ? 'Pelatihan' : $isiLk['waktu_kerja'], $isiLk['upah'], (int) $isiLk['kebutuhan'], $isiLk['syarat'], 'Hubungi lewat WhatsApp', $waLk, $isiLk['batas'], 'Menunggu']);
        redirect('/kerja?cek=' . urlencode($kodeLk) . '&baru=1#pasang');
    }
}
$kodeCek = strtoupper(trim((string) ($_GET['cek'] ?? '')));
$hasilCek = $kodeCek !== '' ? q1('SELECT kode, judul, status, catatan, batas FROM loker WHERE kode = ?', [$kodeCek]) : null;

// Hanya yang sudah diperiksa petugas dan batasnya belum lewat
$daftar = q("SELECT * FROM loker WHERE status = 'Tayang' AND (batas IS NULL OR batas >= ?) ORDER BY CASE jenis WHEN 'Kerja di desa' THEN 1 WHEN 'Lowongan resmi' THEN 2 ELSE 3 END, batas, id DESC", [date('Y-m-d')]);
$hitung = array_count_values(array_column($daftar, 'jenis'));
$pencari = q1("SELECT nilai FROM statistik WHERE label LIKE 'Mencari kerja%' ORDER BY id LIMIT 1");

$ikonJenis = [
    'Kerja di desa'  => ['<path d="M3 16h18l-2.5 4H5.5z"/><path d="M12 16V3l6 9h-6"/>', 'ik-desa'],
    'Lowongan resmi' => ['<path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-5h6v5"/>', 'ik-resmi'],
    'Pelatihan'      => ['<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>', 'ik-latih'],
];
$sisaHari = function (?string $batas): array {
    if (!$batas) return ['Tanpa batas waktu', ''];
    $hari = (int) floor((strtotime($batas) - strtotime(date('Y-m-d'))) / 86400);
    if ($hari <= 0) return ['Ditutup hari ini', 'mendesak'];
    if ($hari === 1) return ['Ditutup besok', 'mendesak'];
    if ($hari <= 7) return ['Ditutup ' . $hari . ' hari lagi', 'segera'];
    return ['Batas ' . tgl($batas), ''];
};

$judul = 'Kerja dan Pelatihan - ' . setting('nama_desa');
$menu  = 'kerja';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Kerja dan pelatihan</h1>
  <p>Peluang kerja dari warga dan usaha di <?= e(setting('nama_desa')) ?>, lowongan dari sumber resmi pemerintah, dan pelatihan gratis untuk menambah keterampilan.</p>
</div>

<div class="wrap sec" style="padding-top:22px">
  <div class="loker-waspada" role="note">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 3l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>
    <p><b>Lowongan asli tidak pernah memungut biaya.</b> Jangan transfer uang untuk "biaya administrasi", seragam, atau pelatihan. Website desa tidak menerima lamaran atau CV; hubungi pemberi kerja langsung. Menemukan yang mencurigakan? <a href="/kontak?kategori=Pertanyaan#aspirasi">Laporkan lewat Aspirasi</a>.</p>
  </div>

  <div class="loker-alat">
    <nav class="chips chips-geser" aria-label="Saring jenis" id="saringLoker">
      <button type="button" class="chip on" data-jenis="" aria-pressed="true">Semua (<?= count($daftar) ?>)</button>
      <?php foreach (JENIS_LOKER as $j): if (empty($hitung[$j])) continue; ?>
        <button type="button" class="chip" data-jenis="<?= e($j) ?>" aria-pressed="false"><?= e($j) ?> (<?= (int) $hitung[$j] ?>)</button>
      <?php endforeach; ?>
    </nav>
    <label class="sr-only" for="cariLoker">Cari pekerjaan atau pemberi kerja</label>
    <input type="search" id="cariLoker" class="loker-cari" placeholder="Cari pekerjaan, misalnya pemandu atau tambak" autocomplete="off">
  </div>

  <div class="loker-daftar" id="daftarLoker">
    <?php foreach ($daftar as $l): [$ik, $kelasIk] = $ikonJenis[$l['jenis']] ?? $ikonJenis['Kerja di desa']; [$teksBatas, $kelasBatas] = $sisaHari($l['batas']); [$jdlL, $ilusL] = pisah_ilustrasi($l['judul']); $ilusL = $ilusL || !empty($l['ilustrasi']); ?>
      <article class="loker" id="loker-<?= (int) $l['id'] ?>" data-jenis="<?= e($l['jenis']) ?>" data-cari="<?= e(mb_strtolower($l['judul'] . ' ' . $l['pemberi'] . ' ' . $l['lokasi'] . ' ' . $l['jenis'])) ?>">
        <span class="loker-ikon <?= $kelasIk ?>" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $ik ?></svg></span>
        <div class="loker-isi">
          <p class="loker-tag"><span class="tag-jenis"><?= e($l['jenis']) ?></span><span><?= e($l['waktu_kerja']) ?></span><?php if ($l['jenis'] === 'Kerja di desa'): ?><span class="tag-lokal">Untuk warga sekitar</span><?php endif; ?><?= tanda_ilustrasi($ilusL) ?></p>
          <h3><?= e($jdlL) ?></h3>
          <ul class="loker-meta">
            <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M3 21h18M5 21V8l7-4 7 4v13"/></svg><?= e($l['pemberi']) ?></li>
            <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="10" r="3"/><path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/></svg><?= e($l['lokasi']) ?></li>
            <li class="<?= $kelasBatas ?>"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e($teksBatas) ?></li>
          </ul>
          <details class="loker-detail">
            <summary>Lihat syarat dan cara daftar</summary>
            <dl>
              <?php if ($l['upah'] !== ''): ?><div><dt>Upah</dt><dd><?= e($l['upah']) ?></dd></div><?php endif; ?>
              <?php if ((int) $l['kebutuhan'] > 0): ?><div><dt>Dibutuhkan</dt><dd><?= (int) $l['kebutuhan'] ?> orang</dd></div><?php endif; ?>
              <div><dt>Cara daftar</dt><dd><?= e($l['cara_daftar']) ?></dd></div>
            </dl>
            <?php if (per_baris((string) $l['syarat'])): ?><ul class="loker-syarat"><?php foreach (per_baris((string) $l['syarat']) as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul><?php endif; ?>
          </details>
        </div>
        <div class="loker-aksi">
          <?php if ($l['tautan'] !== ''): ?>
            <a class="btn btn-ghost" href="<?= e($l['tautan']) ?>" target="_blank" rel="noopener">Lihat di sumber resmi</a>
            <small><?= e(parse_url($l['tautan'], PHP_URL_HOST)) ?></small>
          <?php elseif ($l['wa'] !== ''): ?>
            <a class="btn btn-wa" href="<?= e(wa_link($l['wa'], 'Halo, saya melihat info "' . $jdlL . '" di website ' . setting('nama_desa') . '. Apakah masih dibuka?')) ?>" target="_blank" rel="noopener">Tanya lewat WhatsApp</a>
          <?php else: ?>
            <a class="btn btn-ghost" href="<?= e(wa_link(setting('wa'), 'Halo petugas ' . setting('nama_desa') . ', saya ingin mendaftar "' . $jdlL . '".')) ?>" target="_blank" rel="noopener">Daftar lewat WA desa</a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    <p class="muted loker-kosong" id="lokerKosong" <?= $daftar ? 'hidden' : '' ?>>Belum ada peluang yang cocok. Coba kata lain, atau cek lagi minggu depan.</p>
  </div>

  <section class="loker-sumber" aria-labelledby="judul-sumber">
    <h2 id="judul-sumber">Cari lebih banyak di sumber resmi</h2>
    <div class="sumber-grid">
      <a href="https://karirhub.kemnaker.go.id" target="_blank" rel="noopener"><b>Karirhub Kemnaker</b><span>Lowongan resmi dari perusahaan di seluruh Indonesia, termasuk Kabupaten Tangerang. Melamar memakai akun SIAPkerja.</span><small>karirhub.kemnaker.go.id</small></a>
      <a href="https://skillhub.kemnaker.go.id" target="_blank" rel="noopener"><b>Skillhub Kemnaker</b><span>Pelatihan kerja gratis di balai latihan kerja (BLK). Pendaftaran dibuka per batch.</span><small>skillhub.kemnaker.go.id</small></a>
      <div><b>Disnaker Kabupaten Tangerang</b><span>Kartu pencari kerja (AK-1), bursa kerja, dan pembekalan kerja ke luar negeri. Kantor desa bisa membantu surat pengantar dan surat domisili.</span><small>Tanya petugas desa untuk alamat dan jadwalnya</small></div>
    </div>
    <?php if ($pencari): ?><p class="hint">Data desa mencatat sekitar <?= angka($pencari['nilai']) ?> warga sedang mencari kerja (<a href="/data-desa?grup=Penduduk">lihat data</a>). Setiap peluang kerja yang dibagikan di sini membantu mereka.</p><?php endif; ?>
  </section>
</div>

<section class="sec sec-putih" id="pasang" aria-labelledby="judul-pasang"><div class="wrap split">
  <div>
    <h2 id="judul-pasang">Butuh tenaga kerja? Pasang peluangnya di sini</h2>
    <p>Untuk pemilik tambak, nelayan, UMKM, BUMDes, dan kelompok warga di <?= e(setting('nama_desa')) ?>. Gratis.</p>
    <ol class="alur-langkah alur-aju">
      <li><b>Isi form</b><span>Pekerjaan, lokasi, upah, syarat, dan nomor WhatsApp. Kamu mendapat kode LKR-.</span></li>
      <li><b>Diperiksa petugas</b><span>Biasanya 1 hari kerja, supaya tidak ada lowongan palsu.</span></li>
      <li><b>Tampil sampai batas waktu</b><span>Pelamar menghubungimu langsung lewat WhatsApp.</span></li>
      <li><b>Hilang otomatis</b><span>Setelah tanggal batas lewat, peluang tidak tampil lagi.</span></li>
    </ol>
    <div class="panel" id="cek-pasang">
      <h3 style="margin-top:0">Cek pengajuan</h3>
      <form method="get" action="/kerja#cek-pasang" class="grid" style="grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end">
        <div><label for="cekLk">Kode pengajuan</label><input id="cekLk" name="cek" value="<?= e($kodeCek) ?>" placeholder="Contoh: LKR-4TQ8MA" maxlength="20"></div>
        <button class="btn btn-dark" type="submit">Cek</button>
      </form>
      <?php if ($kodeCek !== '' && !$hasilCek): ?><p class="alert alert-err" style="margin-top:12px">Kode <?= e($kodeCek) ?> tidak ditemukan.</p><?php endif; ?>
      <?php if ($hasilCek): ?>
        <div class="hasil-adu" style="margin-top:12px">
          <?php if (!empty($_GET['baru'])): ?><p class="alert alert-ok">Terima kasih! Pengajuanmu sudah masuk. Simpan kode ini.</p><?php endif; ?>
          <p><b><?= e($hasilCek['kode']) ?></b> <span class="tag"><?= e($hasilCek['status']) ?></span></p>
          <p class="small"><?= e($hasilCek['judul']) ?>, batas <?= tgl($hasilCek['batas']) ?></p>
          <?php if ($hasilCek['catatan'] !== ''): ?><p class="small"><b>Catatan petugas:</b> <?= e($hasilCek['catatan']) ?></p><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <form class="panel ajukan-form" method="post" action="/kerja#pasang" novalidate>
    <input type="hidden" name="aksi" value="pasang">
    <div class="hp" aria-hidden="true"><label>Jangan diisi<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <?php if ($errorLk): ?><div class="alert alert-err" role="alert"><ul><?php foreach ($errorLk as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <fieldset class="field pilih-kirim"><legend>Jenis peluang</legend>
      <label class="cek"><input type="radio" name="jenis" value="Kerja di desa" <?= $isiLk['jenis'] !== 'Pelatihan' ? 'checked' : '' ?>> Pekerjaan</label>
      <label class="cek"><input type="radio" name="jenis" value="Pelatihan" <?= $isiLk['jenis'] === 'Pelatihan' ? 'checked' : '' ?>> Pelatihan atau kursus</label>
    </fieldset>
    <div class="field"><label for="lk-judul">Pekerjaan atau pelatihan yang ditawarkan</label><input id="lk-judul" name="judul" value="<?= e($isiLk['judul']) ?>" maxlength="150" placeholder="Contoh: Buruh panen bandeng" required></div>
    <div class="field"><label for="lk-pemberi">Nama usaha atau pemberi kerja, dan RT</label><input id="lk-pemberi" name="pemberi" value="<?= e($isiLk['pemberi']) ?>" maxlength="150" placeholder="Contoh: Tambak Pak Udin, RT 04" required></div>
    <div class="field"><label for="lk-lokasi">Lokasi kerja</label><input id="lk-lokasi" name="lokasi" value="<?= e($isiLk['lokasi']) ?>" maxlength="150" placeholder="Contoh: Tambak pesisir utara" required></div>
    <div class="grid g2" style="gap:12px">
      <div class="field"><label for="lk-waktu">Waktu kerja</label><select id="lk-waktu" name="waktu_kerja"><?php foreach (WAKTU_KERJA as $wk): ?><option <?= $isiLk['waktu_kerja'] === $wk ? 'selected' : '' ?>><?= e($wk) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="lk-butuh">Dibutuhkan (orang)</label><input id="lk-butuh" name="kebutuhan" value="<?= e($isiLk['kebutuhan']) ?>" inputmode="numeric" maxlength="3"></div>
    </div>
    <div class="field"><label for="lk-upah">Upah (opsional)</label><input id="lk-upah" name="upah" value="<?= e($isiLk['upah']) ?>" maxlength="100" placeholder="Contoh: Rp120.000 per hari + makan"></div>
    <div class="field"><label for="lk-syarat">Syarat atau keterangan (satu baris satu syarat)</label><textarea id="lk-syarat" name="syarat" rows="4" maxlength="800" placeholder="Contoh: Sehat dan kuat bekerja di air"><?= e($isiLk['syarat']) ?></textarea></div>
    <div class="grid g2" style="gap:12px">
      <div class="field"><label for="lk-wa">Nomor WhatsApp</label><input id="lk-wa" name="wa" value="<?= e($isiLk['wa']) ?>" inputmode="tel" maxlength="20" placeholder="081234567890" required></div>
      <div class="field"><label for="lk-batas">Batas pendaftaran</label><input id="lk-batas" name="batas" type="date" value="<?= e($isiLk['batas']) ?>" min="<?= date('Y-m-d') ?>" max="<?= $batasMaks ?>" required></div>
    </div>
    <label class="cek-setuju"><input type="checkbox" name="tanpa_biaya" value="1" <?= !empty($_POST['tanpa_biaya']) ? 'checked' : '' ?>> <span>Saya menyatakan peluang ini <b>tidak memungut biaya apa pun</b> dari pelamar.</span></label>
    <label class="cek-setuju"><input type="checkbox" name="setuju_data" value="1" <?= !empty($_POST['setuju_data']) ? 'checked' : '' ?>> <span>Saya setuju nama usaha dan nomor WhatsApp ini ditampilkan sampai batas pendaftaran, lalu disembunyikan.</span></label>
    <button class="btn btn-main btn-penuh" type="submit">Kirim untuk diperiksa</button>
  </form>
</div></section>
<?php
$scriptHalaman = <<<'JS'
<script>
(function () {
  const nav = document.getElementById('saringLoker'), cari = document.getElementById('cariLoker'), kosong = document.getElementById('lokerKosong');
  let jenis = '';
  const saring = () => {
    const k = (cari.value || '').trim().toLowerCase(); let ada = 0;
    document.querySelectorAll('.loker[data-jenis]').forEach(el => {
      const tampil = (jenis === '' || el.dataset.jenis === jenis) && (k === '' || el.dataset.cari.includes(k));
      el.hidden = !tampil; if (tampil) ada++;
    });
    kosong.hidden = ada > 0;
  };
  nav.addEventListener('click', e => {
    const b = e.target.closest('[data-jenis]'); if (!b) return;
    jenis = b.dataset.jenis;
    nav.querySelectorAll('.chip').forEach(c => { c.classList.toggle('on', c === b); c.setAttribute('aria-pressed', c === b ? 'true' : 'false'); });
    saring();
  });
  cari.addEventListener('input', saring);
  // Pelatihan tidak butuh pilihan waktu kerja
  const waktu = document.getElementById('lk-waktu');
  document.querySelectorAll('input[name="jenis"]').forEach(r => r.addEventListener('change', () => { waktu.disabled = r.value === 'Pelatihan' && r.checked; }));
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php';
