<?php
// =========================================================
// HALAMAN INFORMASI DESA (/data-desa)
//   Statistik      : dari tabel statistik, dikelompokkan per tombol
//   Transparansi   : APBDes tahun terbaru (tabel apbdes)
//   Pembangunan    : proyek desa (tabel pembangunan)
//   Program bantuan: tabel bantuan
//   Bank sampah    : tabel sampah + pengaturan info_bank_sampah dan harga_sampah
// Tab dipilih lewat alamat, contoh: /data-desa?grup=Transparansi
// =========================================================
$grupData = [
    'Penduduk'   => ['Kependudukan', 'Mata pencaharian'],
    'Pendidikan' => ['Pendidikan penduduk', 'Sekolah'],
    'Kesehatan'  => ['Kesehatan', 'Fasilitas kesehatan'],
    'Pertanian'  => ['Pertanian', 'Peternakan'],
    'Ekonomi'    => ['Ekonomi', 'Jalan desa'],
];
// Kategori baru yang ditambahkan admin (di luar daftar di atas) masuk ke tombol "Lainnya"
$sudahAda = [...array_merge(...array_values($grupData)), 'Lahan', 'Pemerintahan'];
$lainnya = array_values(array_diff(array_column(q('SELECT DISTINCT kategori FROM statistik ORDER BY kategori'), 'kategori'), $sudahAda));
if ($lainnya) {
    $grupData['Lainnya'] = $lainnya;
}
$grupKhusus = ['Dampak', 'Layanan surat', 'Pantau bansos', 'Transparansi', 'Pembangunan', 'Bank sampah'];
if (($_GET['grup'] ?? '') === 'Program bantuan') $_GET['grup'] = 'Pantau bansos'; // alamat lama tetap jalan
$grup = $_GET['grup'] ?? 'Penduduk';
if (!isset($grupData[$grup]) && !in_array($grup, $grupKhusus, true)) {
    $grup = 'Penduduk';
}

// ---------- Data untuk tab khusus ----------
if ($grup === 'Dampak') {
    $dampak = ringkasan_dampak();
    // Pengunjung dan emisi per orang, 6 bulan terakhir
    $bulanDampak = [];
    for ($i = 5; $i >= 0; $i--) $bulanDampak[date('Y-m', strtotime("first day of -$i month"))] = ['orang' => 0, 'kg' => 0.0, 'tercatat' => 0];
    foreach (q("SELECT tanggal, jumlah, emisi_kg, kendaraan FROM reservasi WHERE status IN ('Dikonfirmasi', 'Selesai') AND tanggal >= ?", [array_key_first($bulanDampak) . '-01']) as $r) {
        $k = substr($r['tanggal'], 0, 7);
        if (!isset($bulanDampak[$k])) continue;
        $bulanDampak[$k]['orang'] += (int) $r['jumlah'];
        if ($r['kendaraan'] !== '') { $bulanDampak[$k]['kg'] += (float) $r['emisi_kg']; $bulanDampak[$k]['tercatat'] += (int) $r['jumlah']; }
    }
    $maksOrang = max(1, max(array_column($bulanDampak, 'orang')));
    $moda = array_map(fn($r) => ['label' => $r['kendaraan'], 'nilai' => (int) $r['n'], 'satuan' => 'orang'],
        q("SELECT kendaraan, SUM(jumlah) AS n FROM reservasi WHERE status IN ('Dikonfirmasi', 'Selesai') AND kendaraan <> '' GROUP BY kendaraan ORDER BY n DESC"));
}
if ($grup === 'Layanan surat') {
    $layanan = q('SELECT * FROM layanan WHERE aktif = 1 ORDER BY urutan, id');
    $pilihLayanan = (int) ($_GET['l'] ?? ($layanan[0]['id'] ?? 0));
} elseif ($grup === 'Kesehatan') {
    $posyandu = q("SELECT id, judul, tanggal, jam, tempat FROM agenda WHERE judul LIKE '%osyandu%' AND tanggal >= ? ORDER BY tanggal LIMIT 3", [date('Y-m-d')]);
}
if ($grup === 'Transparansi') {
    $tahun = (int) (q1('SELECT MAX(tahun) AS t FROM apbdes')['t'] ?? 0);
    $pos = $tahun ? q('SELECT jenis, uraian, anggaran, realisasi FROM apbdes WHERE tahun = ? ORDER BY urutan, id', [$tahun]) : [];
    $jumlah = fn(string $jenis, string $kolom) => array_sum(array_map(fn($p) => (float) $p[$kolom], array_filter($pos, fn($p) => $p['jenis'] === $jenis)));
    $pendapatan = $jumlah('Pendapatan', 'anggaran');
    $belanja = $jumlah('Belanja', 'anggaran');
    $realisasiBelanja = $jumlah('Belanja', 'realisasi');
    $realisasiPendapatan = $jumlah('Pendapatan', 'realisasi');
} elseif ($grup === 'Pembangunan') {
    $proyek = q("SELECT * FROM pembangunan ORDER BY tahun DESC, CASE status WHEN 'Berjalan' THEN 1 WHEN 'Perencanaan' THEN 2 ELSE 3 END, id DESC");
    $totalProyek = array_sum(array_map(fn($p) => (float) $p['anggaran'], $proyek));
} elseif ($grup === 'Pantau bansos') {
    $program = q('SELECT * FROM bantuan WHERE aktif = 1 ORDER BY id');
    // Cek status penerima: nama lengkap + RT harus cocok persis. Nama tampil disamarkan.
    $cekNama = trim((string) ($_GET['nama'] ?? ''));
    $cekRt = rt_baku((string) ($_GET['rt'] ?? ''));
    $hasilCek = null;
    if ($cekNama !== '' || $cekRt !== '') {
        if (mb_strlen($cekNama) < 3 || $cekRt === '') {
            $hasilCek = 'kurang';
        } else {
            $hasilCek = q('SELECT p.nama, p.rt, p.rw, p.tahap_tersalur, p.terakhir_salur, b.nama AS program, b.jumlah_tahap, b.jadwal
                FROM bansos_penerima p JOIN bantuan b ON b.id = p.bantuan_id
                WHERE b.aktif = 1 AND p.rt = ? AND LOWER(TRIM(p.nama)) = ?', [$cekRt, nama_baku($cekNama)]);
            if (!$hasilCek) sleep(1); // perlambat percobaan menebak nama
        }
    }
} elseif ($grup === 'Bank sampah') {
    $akhir = q1('SELECT MAX(bulan) AS b FROM sampah')['b'] ?? null;
    $bulanan = [];
    $perJenis = [];
    $totalKg = $totalRp = 0;
    if ($akhir) {
        for ($i = 5; $i >= 0; $i--) {
            $bulanan[date('Y-m', strtotime(substr($akhir, 0, 7) . "-01 -$i month"))] = 0;
        }
        $awal = array_key_first($bulanan) . '-01';
        foreach (q('SELECT bulan, jenis, berat_kg, nilai_rupiah FROM sampah WHERE bulan >= ?', [$awal]) as $s) {
            $k = substr($s['bulan'], 0, 7);
            if (isset($bulanan[$k])) $bulanan[$k] += (float) $s['berat_kg'];
            $perJenis[$s['jenis']] = ($perJenis[$s['jenis']] ?? 0) + (float) $s['berat_kg'];
            $totalKg += (float) $s['berat_kg'];
            $totalRp += (int) $s['nilai_rupiah'];
        }
        arsort($perJenis);
    }
    $maksBulan = max(1, $bulanan ? max($bulanan) : 1);
}

// ---------- Layanan surat: pengajuan online dan antrean kantor desa ----------
$errorSurat = [];
$isiSurat = ['layanan_id' => (string) ($_GET['l'] ?? ''), 'nama' => '', 'wa' => '', 'email' => '', 'keperluan' => ''];
$kodeQr = (string) ($_GET['antre'] ?? $_POST['antre'] ?? '');
$qrSah = $kodeQr !== '' && hash_equals(kode_antrean(), $kodeQr);
$errorAntre = '';
if ($grup === 'Layanan surat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['aksi'] ?? '') === 'surat') {
        foreach ($isiSurat as $k => $_) $isiSurat[$k] = trim((string) ($_POST[$k] ?? ''));
        $wa = preg_replace('/\D/', '', $isiSurat['wa']);
        $lay = q1('SELECT id, nama FROM layanan WHERE id = ? AND aktif = 1', [(int) $isiSurat['layanan_id']]);
        if (!empty($_POST['website'])) $errorSurat[] = 'Pengajuan tidak dapat diproses.';
        if (!$lay) $errorSurat[] = 'Pilih jenis surat dari daftar.';
        if (mb_strlen($isiSurat['nama']) < 2 || mb_strlen($isiSurat['nama']) > 150) $errorSurat[] = 'Isi nama sesuai KTP (2 sampai 150 huruf).';
        if (strlen($wa) < 10 || strlen($wa) > 15) $errorSurat[] = 'Isi nomor WhatsApp 10 sampai 15 digit, supaya petugas bisa mengabari.';
        if ($isiSurat['email'] !== '' && !filter_var($isiSurat['email'], FILTER_VALIDATE_EMAIL)) $errorSurat[] = 'Alamat email belum benar.';
        if (mb_strlen($isiSurat['keperluan']) < 5 || mb_strlen($isiSurat['keperluan']) > 255) $errorSurat[] = 'Tulis keperluan surat (5 sampai 255 huruf).';
        if (empty($_POST['setuju_data'])) $errorSurat[] = 'Centang persetujuan penggunaan data.';
        if (!$errorSurat) {
            $kode = kode_unik('SRT', 'surat');
            q('INSERT INTO surat (kode, layanan_id, layanan_nama, nama, wa, email, keperluan) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$kode, $lay['id'], $lay['nama'], $isiSurat['nama'], $wa, $isiSurat['email'], $isiSurat['keperluan']]);
            require_once __DIR__ . '/../inc/email.php';
            if ($isiSurat['email'] !== '') email_surat($kode);
            email_admin_surat_baru($kode);
            redirect('/data-desa?grup=Layanan+surat&srt=' . urlencode($kode) . '&baru=1#surat');
        }
    } elseif (($_POST['aksi'] ?? '') === 'antre') {
        $keperluan = trim((string) ($_POST['keperluan'] ?? ''));
        $namaAntre = mb_substr(trim((string) ($_POST['nama'] ?? '')), 0, 60);
        if (!$qrSah) $errorAntre = 'Kode antrean tidak berlaku. Pindai ulang QR di meja layanan kantor desa.';
        elseif (mb_strlen($keperluan) < 3 || mb_strlen($keperluan) > 150) $errorAntre = 'Pilih keperluan.';
        else {
            $hariIni = date('Y-m-d');
            $token = bin2hex(random_bytes(6));
            for ($coba = 0; $coba < 5; $coba++) { // dua orang bisa menekan bersamaan: coba nomor berikutnya
                $nomor = (int) q1('SELECT COALESCE(MAX(nomor), 0) AS n FROM antrean WHERE tanggal = ?', [$hariIni])['n'] + 1;
                try {
                    q('INSERT INTO antrean (tanggal, nomor, keperluan, nama, token) VALUES (?, ?, ?, ?, ?)', [$hariIni, $nomor, $keperluan, $namaAntre, $token]);
                    break;
                } catch (PDOException $e) {
                    if ($coba === 4) throw $e;
                }
            }
            $id = (int) q1('SELECT id FROM antrean WHERE tanggal = ? AND nomor = ?', [$hariIni, $nomor])['id'];
            setcookie('mm_antre', $id . '-' . $token, ['expires' => strtotime('tomorrow'), 'path' => '/', 'samesite' => 'Lax', 'httponly' => true, 'secure' => !empty($_SERVER['HTTPS'])]);
            redirect('/data-desa?grup=Layanan+surat&tiket=' . $id . '-' . $token . '#antrean');
        }
    }
}
// Lacak pengajuan surat
$kodeSrt = strtoupper(trim((string) ($_GET['srt'] ?? '')));
$hasilSrt = $kodeSrt !== '' ? q1('SELECT kode, layanan_nama, keperluan, status, catatan, dibuat_pada FROM surat WHERE kode = ?', [$kodeSrt]) : null;
// Tiket antrean milik pengunjung ini (dari alamat atau cookie)
$tiketAntre = null;
$kunciTiket = (string) ($_GET['tiket'] ?? $_COOKIE['mm_antre'] ?? '');
if (preg_match('/^(\d+)-([a-f0-9]{12})$/', $kunciTiket, $mt)) {
    $tiketAntre = q1('SELECT * FROM antrean WHERE id = ? AND token = ? AND tanggal = ?', [(int) $mt[1], $mt[2], date('Y-m-d')]);
}
$dilayani = q1("SELECT nomor FROM antrean WHERE tanggal = ? AND status = 'Dipanggil' ORDER BY dipanggil_pada DESC, nomor DESC LIMIT 1", [date('Y-m-d')]);
$sisaAntre = (int) q1("SELECT COUNT(*) AS n FROM antrean WHERE tanggal = ? AND status = 'Menunggu'", [date('Y-m-d')])['n'];

$judul = 'Informasi Desa - ' . setting('nama_desa');
$menu  = 'data-desa';
require __DIR__ . '/../inc/header.php';
$warnaStatus = ['Selesai' => 'Dikonfirmasi', 'Berjalan' => 'Menunggu', 'Perencanaan' => 'Dibatalkan'];
?>
<div class="wrap page-title">
  <h1>Informasi desa</h1>
  <p>Data kependudukan, pendidikan, kesehatan, dan ekonomi <?= e(setting('nama_desa')) ?>, ditambah keterbukaan anggaran, pembangunan, program bantuan, dan bank sampah.</p>
</div>
<div class="wrap sec" style="padding-top:16px">
  <nav class="chips chips-info" aria-label="Kategori informasi">
    <?php foreach ([...array_keys($grupData), ...$grupKhusus] as $g): ?>
      <a class="chip <?= in_array($g, $grupKhusus, true) ? 'chip-khusus' : '' ?> <?= $g === $grup ? 'on' : '' ?>" href="/data-desa?grup=<?= urlencode($g) ?>" <?= $g === $grup ? 'aria-current="page"' : '' ?>><?= e($g) ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($grup === 'Dampak'): $d = $dampak; ?>
  <h2 class="sub-judul">Laporan dampak desa</h2>
  <p class="muted" style="max-width:70ch">Setiap kunjungan, setoran sampah, aspirasi, dan rupiah dana desa tercatat. Angka di bawah dihitung otomatis dari data yang diisi petugas, jadi selalu terbaru.</p>
  <div class="dampak-utama">
    <div class="dampak-besar"><span>Emisi yang dihemat pengunjung</span><b><?= angka(round($d['kg_hemat'])) ?> kg CO2e</b>
      <small>dibanding kalau semua pengunjung datang dengan mobil pribadi</small></div>
    <div class="dampak-besar"><span>Pengunjung datang dengan transport hijau</span><b><?= persen_id($d['orang_hijau'], $d['orang_tercatat']) ?>%</b>
      <small><?= angka($d['orang_hijau']) ?> dari <?= angka($d['orang_tercatat']) ?> orang: bus, angkutan umum, kereta, sepeda</small></div>
  </div>
  <div class="stat-row">
    <div class="stat"><span>Jejak karbon rata-rata</span><b><?= angka(round($d['kg'] / max(1, $d['orang_tercatat']), 1)) ?> kg</b><small>CO2e per pengunjung, pulang pergi</small></div>
    <div class="stat"><span>Tiket Hijau ditukar di UMKM</span><b><?= $d['kupon_dipakai'] ?> dari <?= $d['kupon_terbit'] ?></b><small>sekitar <?= rupiah($d['nilai_umkm']) ?> belanja berputar ke warga</small></div>
    <div class="stat"><span>Sampah dikelola 12 bulan</span><b><?= angka(round($d['sampah_kg'] / 1000, 1)) ?> ton</b><small>lewat bank sampah desa</small></div>
    <div class="stat"><span>Aspirasi warga selesai</span><b><?= persen_id($d['asp_selesai'], $d['asp_total']) ?>%</b><small><?= $d['asp_selesai'] ?> dari <?= $d['asp_total'] ?> laporan</small></div>
    <?php if ($d['apb_tahun']): ?><div class="stat"><span>Belanja desa <?= $d['apb_tahun'] ?> terealisasi</span><b><?= persen_id($d['apb_r'], $d['apb_a']) ?>%</b><small>dari <?= rupiah_ringkas($d['apb_a']) ?></small></div><?php endif; ?>
    <?php if ($d['ulasan_n']): ?><div class="stat"><span>Kepuasan pengunjung</span><b><?= number_format($d['ulasan_rata'], 1, ',', '.') ?> / 5</b><small>dari <?= $d['ulasan_n'] ?> ulasan terverifikasi</small></div><?php endif; ?>
  </div>
  <div class="grid g2">
    <div class="panel">
      <h3>Pengunjung per bulan</h3>
      <div class="vbars">
        <?php $k = 0; foreach ($bulanDampak as $bln => $b): $k++; $per = $b['tercatat'] ? $b['kg'] / $b['tercatat'] : null; ?>
          <div class="vbar <?= $k === count($bulanDampak) ? 'now' : '' ?>"><b><?= $b['orang'] ?></b><div style="height:<?= round($b['orang'] / $maksOrang * 130) ?>px"></div><span><?= tgl($bln . '-01', 'bulan') ?></span><small class="muted"><?= $per === null ? '-' : angka(round($per, 1)) . ' kg' ?></small></div>
        <?php endforeach; ?>
      </div>
      <p class="hint">Angka kecil di bawah bulan: rata-rata jejak karbon per orang. Makin kecil, makin hijau.</p>
    </div>
    <div class="panel">
      <h3>Cara pengunjung datang</h3>
      <div class="bars"><?= bar_list($moda) ?></div>
    </div>
  </div>
  <p class="hint">Cara menghitung: emisi perjalanan = faktor emisi kendaraan (UK DESNZ) x jarak pulang pergi. Data dari reservasi yang dikonfirmasi dan selesai. Nilai belanja UMKM = jumlah kupon ditukar x nilai kupon.</p>

<?php elseif ($grup === 'Layanan surat'): ?>
  <h2 class="sub-judul">Mau mengurus apa?</h2>
  <p class="muted" style="max-width:70ch">Pilih keperluanmu untuk melihat dokumen yang harus dibawa. Centang yang sudah siap, supaya cukup sekali datang ke kantor desa. Tidak perlu mengunggah KTP atau KK di sini.</p>
  <?php if (!$layanan): ?><p class="muted">Daftar layanan belum diisi.</p><?php else: ?>
  <div class="field" style="max-width:420px">
    <label for="pilih-layanan">Keperluan</label>
    <select id="pilih-layanan">
      <?php foreach ($layanan as $l): ?><option value="<?= (int) $l['id'] ?>" <?= (int) $l['id'] === $pilihLayanan ? 'selected' : '' ?>><?= e($l['nama']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <?php foreach ($layanan as $l): $syarat = per_baris($l['syarat']);
    $teksWa = "Persiapan {$l['nama']} di " . setting('nama_desa') . ":\n" . implode("\n", array_map(fn($x) => '- ' . $x, $syarat)) . "\nTempat: {$l['tempat']}. Lama proses: {$l['lama_proses']}. Biaya: {$l['biaya']}."; ?>
    <article class="panel layanan-kartu" id="layanan-<?= (int) $l['id'] ?>" data-layanan="<?= (int) $l['id'] ?>">
      <div class="head-row" style="margin-bottom:6px"><h3 style="margin:0"><?= e($l['nama']) ?></h3><span class="small muted"><span class="hitung-siap">0</span> dari <?= count($syarat) ?> siap</span></div>
      <div class="bar-track" aria-hidden="true"><div class="bar-fill siap-bar" style="width:0%"></div></div>
      <fieldset class="syarat-list"><legend class="small" style="margin:12px 0 6px;font-weight:700">Dokumen yang dibawa</legend>
        <?php foreach ($syarat as $i => $x): ?>
          <label class="cek"><input type="checkbox" class="syarat-cek"> <?= e($x) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <dl class="layanan-info">
        <div><dt>Tempat</dt><dd><?= e($l['tempat']) ?></dd></div>
        <div><dt>Lama proses</dt><dd><?= e($l['lama_proses']) ?></dd></div>
        <div><dt>Biaya</dt><dd><?= e($l['biaya']) ?></dd></div>
        <div><dt>Jam layanan</dt><dd><?= e(setting('jam_layanan')) ?></dd></div>
      </dl>
      <?php if ($l['keterangan']): ?><p class="small muted" style="margin:0 0 12px"><?= e($l['keterangan']) ?></p><?php endif; ?>
      <div class="actions">
        <a class="btn btn-wa btn-sm" href="https://wa.me/?text=<?= rawurlencode($teksWa) ?>" target="_blank" rel="noopener">Simpan daftar ke WhatsApp</a>
        <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">Cetak</button>
        <a class="btn btn-ghost btn-sm" href="<?= e(wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya tentang ' . $l['nama'] . '.')) ?>" target="_blank" rel="noopener">Tanya petugas</a>
      </div>
    </article>
  <?php endforeach; ?>
  <p class="hint">Syarat bisa berubah sesuai aturan terbaru. Kalau ragu, tanyakan dulu lewat WhatsApp desa.</p>
  <?php endif; ?>

  <div class="split" id="surat" style="margin-top:28px">
    <form class="panel" method="post" action="/data-desa?grup=Layanan+surat#surat">
      <h3>Ajukan surat online</h3>
      <p class="small muted">Petugas menyiapkan suratmu lebih dulu. Kamu tinggal datang membawa dokumen asli saat surat siap diambil. NIK dan foto KTP tidak diminta lewat website.</p>
      <input type="hidden" name="aksi" value="surat">
      <?php if ($errorSurat): ?><div class="alert alert-err" role="alert"><b>Pengajuan belum terkirim:</b><ul><?php foreach ($errorSurat as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <div class="field"><label for="s-layanan">Jenis surat</label>
        <select id="s-layanan" name="layanan_id"><?php foreach ($layanan ?? [] as $l): ?><option value="<?= (int) $l['id'] ?>" <?= (string) $l['id'] === $isiSurat['layanan_id'] ? 'selected' : '' ?>><?= e($l['nama']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="s-nama">Nama sesuai KTP</label><input id="s-nama" name="nama" value="<?= e($isiSurat['nama']) ?>" maxlength="150" required autocomplete="name"></div>
      <div class="grid g2" style="gap:12px">
        <div class="field"><label for="s-wa">WhatsApp</label><input id="s-wa" name="wa" value="<?= e($isiSurat['wa']) ?>" inputmode="tel" required placeholder="08xxxxxxxxxx"></div>
        <div class="field"><label for="s-email">Email (opsional)</label><input id="s-email" name="email" type="email" value="<?= e($isiSurat['email']) ?>" maxlength="150"></div>
      </div>
      <div class="field"><label for="s-perlu">Untuk keperluan apa?</label><input id="s-perlu" name="keperluan" value="<?= e($isiSurat['keperluan']) ?>" maxlength="255" required placeholder="Contoh: melamar kerja, daftar sekolah"></div>
      <label class="cek-setuju"><input type="checkbox" name="setuju_data" value="1" required> <span>Saya setuju nama dan kontak dipakai untuk mengurus surat ini, sesuai <a href="/kontak#privasi">kebijakan privasi</a>.</span></label>
      <div class="hp" aria-hidden="true"><label for="website4">Jangan diisi</label><input id="website4" name="website" tabindex="-1" autocomplete="off"></div>
      <button class="btn btn-main" type="submit">Kirim pengajuan</button>
    </form>
    <div class="panel">
      <h3>Lacak pengajuan surat</h3>
      <form method="get" action="/data-desa#surat" class="grid" style="grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end">
        <input type="hidden" name="grup" value="Layanan surat">
        <div><label for="srt">Kode pengajuan</label><input id="srt" name="srt" value="<?= e($kodeSrt) ?>" placeholder="Contoh: SRT-4KX9PQ"></div>
        <button class="btn btn-dark" type="submit">Lacak</button>
      </form>
      <?php if ($kodeSrt !== ''): ?>
        <?php if ($hasilSrt):
          $tahapSrt = $hasilSrt['status'] === 'Ditolak' ? ['Diterima', 'Ditolak'] : ['Diterima', 'Diproses', 'Siap diambil', 'Selesai'];
          $posSrt = array_search($hasilSrt['status'], $tahapSrt, true); ?>
          <div class="hasil-adu" role="status" style="margin-top:14px">
            <?php if (isset($_GET['baru'])): ?><div class="alert alert-ok">Pengajuan terkirim. Simpan kode <b><?= e($hasilSrt['kode']) ?></b>.</div><?php endif; ?>
            <p style="margin:0"><b><?= e($hasilSrt['kode']) ?></b> <span class="tag"><?= e($hasilSrt['layanan_nama']) ?></span></p>
            <p class="small muted" style="margin:2px 0 0">Untuk <?= e($hasilSrt['keperluan']) ?>, diajukan <?= tgl(substr((string) $hasilSrt['dibuat_pada'], 0, 10)) ?></p>
            <ol class="steps"><?php foreach ($tahapSrt as $i2 => $t): ?><li class="<?= $i2 <= $posSrt ? 'done' : '' ?> <?= $t === 'Ditolak' ? 'bad' : '' ?>"><?= $t ?></li><?php endforeach; ?></ol>
            <?php if ($hasilSrt['catatan']): ?><div class="tanggapan"><b>Catatan petugas</b><p><?= nl2br(e($hasilSrt['catatan'])) ?></p></div><?php endif; ?>
            <?php if ($hasilSrt['status'] === 'Siap diambil'): ?><p class="small" style="margin:10px 0 0"><b>Surat siap.</b> Datang di jam layanan (<?= e(setting('jam_layanan')) ?>) dengan membawa dokumen asli, lalu ambil nomor antrean di meja layanan.</p><?php endif; ?>
          </div>
        <?php else: ?>
          <div class="alert alert-err" role="alert" style="margin-top:14px">Kode <?= e($kodeSrt) ?> tidak ditemukan. Kode diawali SRT-.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="panel antre-box" id="antrean" style="margin-top:20px">
    <div class="antre-kini"><span>Sedang dilayani hari ini</span><b><?= $dilayani ? sprintf('%03d', (int) $dilayani['nomor']) : '-' ?></b><small><?= $sisaAntre ?> orang menunggu</small></div>
    <div>
      <h3>Antrean di kantor desa</h3>
      <?php if ($tiketAntre): $depan = (int) q1("SELECT COUNT(*) AS n FROM antrean WHERE tanggal = ? AND status = 'Menunggu' AND nomor < ?", [date('Y-m-d'), $tiketAntre['nomor']])['n']; ?>
        <div class="antre-tiket" role="status">
          <span>Nomor antreanmu</span><b><?= sprintf('%03d', (int) $tiketAntre['nomor']) ?></b>
          <small><?= e($tiketAntre['keperluan']) ?></small>
          <p style="margin:8px 0 0"><?= match ($tiketAntre['status']) {
              'Menunggu' => $depan ? "Ada $depan orang di depanmu. Halaman ini diperbarui otomatis." : 'Kamu yang berikutnya. Bersiap di dekat meja layanan.',
              'Dipanggil' => 'Giliranmu sekarang. Silakan ke meja layanan.',
              'Selesai' => 'Layanan selesai. Terima kasih.',
              default => 'Nomor ini dilewati. Ambil nomor baru kalau masih perlu dilayani.',
          } ?></p>
        </div>
      <?php elseif ($qrSah): ?>
        <form method="post" action="/data-desa?grup=Layanan+surat#antrean">
          <input type="hidden" name="aksi" value="antre"><input type="hidden" name="antre" value="<?= e($kodeQr) ?>">
          <?php if ($errorAntre): ?><div class="alert alert-err" role="alert"><?= e($errorAntre) ?></div><?php endif; ?>
          <div class="grid g2" style="gap:12px">
            <div class="field"><label for="a-perlu">Keperluan</label><select id="a-perlu" name="keperluan"><?php foreach ($layanan ?? [] as $l): ?><option><?= e($l['nama']) ?></option><?php endforeach; ?><option>Mengambil surat yang sudah siap</option><option>Konsultasi atau keperluan lain</option></select></div>
            <div class="field"><label for="a-nama">Nama panggilan (opsional)</label><input id="a-nama" name="nama" maxlength="60"></div>
          </div>
          <button class="btn btn-main" type="submit">Ambil nomor antrean</button>
        </form>
      <?php else: ?>
        <?php if ($errorAntre): ?><div class="alert alert-err" role="alert"><?= e($errorAntre) ?></div><?php endif; ?>
        <p class="small" style="margin:0">Datang ke kantor desa, lalu pindai QR di meja layanan untuk mengambil nomor antrean dari HP. Kamu bisa duduk menunggu tanpa berdiri di depan loket.</p>
      <?php endif; ?>
    </div>
  </div>

<?php elseif ($grup === 'Transparansi'): ?>
  <?php if (!$pos): ?>
    <p class="muted">Data APBDes belum diisi.</p>
  <?php else: ?>
    <h2 class="sub-judul">APBDes tahun <?= $tahun ?></h2>
    <div class="stat-row">
      <div class="stat"><span>Pendapatan desa</span><b><?= rupiah_ringkas($pendapatan) ?></b><small>terealisasi <?= persen_id($realisasiPendapatan, $pendapatan) ?>%</small></div>
      <div class="stat"><span>Belanja desa</span><b><?= rupiah_ringkas($belanja) ?></b><small>terealisasi <?= persen_id($realisasiBelanja, $belanja) ?>%</small></div>
      <div class="stat"><span><?= $pendapatan >= $belanja ? 'Surplus' : 'Defisit' ?> anggaran</span><b><?= rupiah_ringkas(abs($pendapatan - $belanja)) ?></b><small>pendapatan dikurangi belanja</small></div>
    </div>
    <div class="grid g2">
      <?php foreach (['Pendapatan', 'Belanja', 'Pembiayaan'] as $jenis):
        $baris = array_filter($pos, fn($p) => $p['jenis'] === $jenis); if (!$baris) continue; ?>
        <div class="panel">
          <h3><?= $jenis ?></h3>
          <?php foreach ($baris as $p): $pr = persen((float) $p['realisasi'], (float) $p['anggaran']); ?>
            <div class="anggaran-row">
              <div class="anggaran-atas"><span><?= e($p['uraian']) ?></span><b><?= rupiah_ringkas((float) $p['anggaran']) ?></b></div>
              <div class="bar-track" role="img" aria-label="Terealisasi <?= str_replace('.', ',', (string) $pr) ?> persen"><div class="bar-fill" style="width:<?= $pr ?>%"></div></div>
              <small class="muted">Terealisasi <?= rupiah($p['realisasi']) ?> (<?= str_replace('.', ',', (string) $pr) ?>%)</small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="hint">Batang hijau menunjukkan anggaran yang sudah terpakai atau diterima. Ada pertanyaan soal anggaran? Sampaikan lewat <a href="/kontak#aspirasi">aspirasi warga</a>.</p>
  <?php endif; ?>

<?php elseif ($grup === 'Pembangunan'): ?>
  <h2 class="sub-judul">Pembangunan desa</h2>
  <?php if ($proyek): ?>
    <p class="muted"><?= count($proyek) ?> proyek dengan total anggaran <?= rupiah_ringkas($totalProyek) ?>. Progres diperbarui petugas desa.</p>
  <?php endif; ?>
  <div class="grid g2">
    <?php foreach ($proyek as $p): ?>
      <article class="card proyek">
        <?php if ($p['foto'] || $p['foto_sesudah']): ?>
          <div class="proyek-foto <?= $p['foto'] && $p['foto_sesudah'] ? 'dua' : '' ?>">
            <?php if ($p['foto']): ?><figure><img src="<?= e($p['foto']) ?>" alt="Sebelum: <?= e($p['nama']) ?>" loading="lazy"><figcaption>Sebelum</figcaption></figure><?php endif; ?>
            <?php if ($p['foto_sesudah']): ?><figure><img src="<?= e($p['foto_sesudah']) ?>" alt="Sesudah: <?= e($p['nama']) ?>" loading="lazy"><figcaption><?= $p['status'] === 'Selesai' ? 'Sesudah' : 'Terkini' ?></figcaption></figure><?php endif; ?>
          </div>
        <?php endif; ?>
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:start">
            <h3 style="margin:0"><?= e($p['nama']) ?></h3>
            <span class="status st-<?= $warnaStatus[$p['status']] ?? 'Menunggu' ?>"><?= e($p['status']) ?></span>
          </div>
          <p class="small muted" style="margin:0"><?= e($p['lokasi']) ?>, tahun <?= (int) $p['tahun'] ?></p>
          <p style="margin:8px 0"><?= e($p['deskripsi']) ?></p>
          <div class="progres"><div class="bar-track" role="img" aria-label="Progres <?= (int) $p['progres'] ?> persen"><div class="bar-fill" style="width:<?= (int) $p['progres'] ?>%"></div></div><b><?= (int) $p['progres'] ?>%</b></div>
          <p class="small" style="margin:8px 0 0"><b><?= rupiah($p['anggaran']) ?></b> dari <?= e($p['sumber_dana']) ?></p>
        </div>
      </article>
    <?php endforeach; ?>
    <?php if (!$proyek): ?><p class="muted">Belum ada data pembangunan.</p><?php endif; ?>
  </div>

<?php elseif ($grup === 'Pantau bansos'): ?>
  <h2 class="sub-judul">Pantau bantuan sosial</h2>
  <p class="muted" style="max-width:70ch">Siapa pun bisa memantau apakah bantuan sudah tersalur. Warga juga bisa mengecek status dirinya, dan melaporkan kalau ada yang salah sasaran. Nama penerima di website selalu disamarkan; daftar lengkap ditempel di papan pengumuman kantor desa.</p>

  <div class="grid g2" style="margin-bottom:22px">
    <?php foreach ($program as $b): $rb = ringkas_bansos($b); ?>
      <article class="panel bansos-prog">
        <div class="head-row" style="margin-bottom:8px"><span class="tag"><?= e($b['penyelenggara']) ?></span><?php if ($b['selesai']): ?><span class="status st-Selesai">Program selesai</span><?php endif; ?></div>
        <h3 style="margin:0 0 4px"><?= e($b['nama']) ?></h3>
        <p class="small muted" style="margin:0 0 14px">Untuk: <?= e($b['sasaran']) ?></p>
        <?php if ($rb['total'] > 0): ?>
          <div class="bansos-angka">
            <div><b><?= $rb['total'] ?></b><span>penerima terdaftar<?= (int) $b['kuota'] ? ' dari kuota ' . (int) $b['kuota'] : '' ?></span></div>
            <?php $jalan = max(array_keys(array_filter($rb['tahap'])) ?: [1]); ?>
            <div><b><?= persen_id((float) $rb['tahap'][$jalan], (float) $rb['total']) ?>%</b><span>tersalur di tahap <?= $jalan ?> dari <?= count($rb['tahap']) ?></span></div>
          </div>
          <?php foreach ($rb['tahap'] as $t => $n): $pr = persen((float) $n, (float) $rb['total']); ?>
            <div class="anggaran-row">
              <div class="anggaran-atas"><span>Tahap <?= $t ?></span><b><?= $n ?> dari <?= $rb['total'] ?> tersalur</b></div>
              <div class="bar-track" role="img" aria-label="Tahap <?= $t ?> tersalur <?= str_replace('.', ',', (string) $pr) ?> persen"><div class="bar-fill" style="width:<?= $pr ?>%"></div></div>
            </div>
          <?php endforeach; ?>
          <p class="small" style="margin:12px 0 4px"><b>Penerima per RT</b></p>
          <div class="rt-chips"><?php foreach ($rb['per_rt'] as $r): ?><span>RT <?= e($r['rt']) ?>: <b><?= (int) $r['n'] ?></b></span><?php endforeach; ?></div>
        <?php else: ?>
          <p class="small muted">Daftar penerima belum diumumkan.</p>
        <?php endif; ?>
        <details class="bansos-detail"><summary>Syarat, jadwal, dan cara daftar</summary>
          <ul class="small" style="margin:8px 0;padding-left:18px"><?php foreach (per_baris($b['syarat']) as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul>
          <p class="small" style="margin:0 0 6px"><b>Jadwal:</b> <?= e($b['jadwal']) ?></p>
          <p class="small" style="margin:0"><b>Cara daftar:</b> <?= e($b['cara_daftar']) ?></p>
        </details>
      </article>
    <?php endforeach; ?>
    <?php if (!$program): ?><p class="muted">Belum ada program bantuan yang dibuka.</p><?php endif; ?>
  </div>

  <div class="split" id="cek-bansos">
    <div class="panel">
      <h3>Cek status penerima</h3>
      <p class="small muted">Tulis nama lengkap sesuai KTP dan pilih RT. Tidak perlu NIK.</p>
      <form method="get" action="/data-desa#cek-bansos" class="grid" style="grid-template-columns:minmax(0,1fr) 110px;gap:10px;align-items:end">
        <input type="hidden" name="grup" value="Pantau bansos">
        <div><label for="cb-nama">Nama lengkap</label><input id="cb-nama" name="nama" value="<?= e($cekNama) ?>" maxlength="150" autocomplete="name" required></div>
        <div><label for="cb-rt">RT</label><input id="cb-rt" name="rt" value="<?= e($cekRt) ?>" inputmode="numeric" maxlength="3" placeholder="03" required></div>
        <button class="btn btn-dark" type="submit" style="grid-column:1/-1">Cek status</button>
      </form>
      <?php if ($hasilCek === 'kurang'): ?>
        <div class="alert alert-err" role="alert" style="margin-top:14px">Isi nama lengkap (minimal 3 huruf) dan nomor RT.</div>
      <?php elseif (is_array($hasilCek) && $hasilCek): ?>
        <?php foreach ($hasilCek as $h): ?>
          <div class="hasil-adu" role="status" style="margin-top:14px">
            <p style="margin:0"><b><?= e(samarkan_nama($h['nama'])) ?></b>, RT <?= e($h['rt']) ?><?= $h['rw'] ? '/RW ' . e($h['rw']) : '' ?></p>
            <p class="small" style="margin:2px 0 8px">Terdaftar sebagai penerima <b><?= e($h['program']) ?></b>.</p>
            <ol class="steps"><?php for ($t = 1; $t <= max(1, (int) $h['jumlah_tahap']); $t++): ?><li class="<?= $t <= (int) $h['tahap_tersalur'] ? 'done' : '' ?>">Tahap <?= $t ?></li><?php endfor; ?></ol>
            <p class="small muted" style="margin:0"><?= (int) $h['tahap_tersalur'] ? 'Terakhir diterima ' . tgl((string) $h['terakhir_salur']) . '.' : 'Belum ada tahap yang disalurkan.' ?> Jadwal: <?= e($h['jadwal']) ?>.</p>
          </div>
        <?php endforeach; ?>
      <?php elseif (is_array($hasilCek)): ?>
        <div class="alert alert-err" role="status" style="margin-top:14px">Nama itu tidak ada di daftar penerima RT <?= e($cekRt) ?>. Periksa ejaannya. Kalau keluargamu merasa layak, ajukan usulan di samping.</div>
      <?php endif; ?>
    </div>
    <div class="panel">
      <h3>Ada yang salah sasaran?</h3>
      <p class="small">Penerima bantuan ditetapkan lewat musyawarah desa. Warga berhak memberi masukan kalau ada yang tidak layak menerima, atau keluarga yang layak tapi belum terdaftar.</p>
      <div class="actions" style="flex-direction:column;align-items:stretch">
        <a class="btn btn-main" href="/kontak?kategori=Bansos#aspirasi">Laporkan salah sasaran</a>
        <a class="btn btn-ghost" href="/kontak?kategori=Bansos#aspirasi">Usulkan keluarga yang layak</a>
      </div>
      <p class="hint">Laporan boleh dikirim tanpa nama. Kamu mendapat kode ADU- untuk melacak tindak lanjutnya.</p>
    </div>
  </div>

<?php elseif ($grup === 'Bank sampah'): ?>
  <h2 class="sub-judul">Bank sampah desa</h2>
  <?php if (!$akhir): ?>
    <p class="muted">Data bank sampah belum diisi.</p>
  <?php else: ?>
    <div class="stat-row">
      <div class="stat"><span>Sampah terkelola 6 bulan terakhir</span><b><?= angka(round($totalKg / 1000, 1)) ?> ton</b><small>tidak berakhir di sungai atau dibakar</small></div>
      <div class="stat"><span>Tabungan warga dari sampah</span><b><?= rupiah_ringkas($totalRp) ?></b><small>dalam 6 bulan terakhir</small></div>
      <div class="stat"><span>Jenis terbanyak</span><b style="font-size:1.4rem"><?= e(array_key_first($perJenis)) ?></b><small><?= angka(round(reset($perJenis))) ?> kg</small></div>
    </div>
    <div class="grid g2">
      <div class="panel">
        <h3>Sampah terkumpul per bulan (kg)</h3>
        <div class="vbars">
          <?php $k = 0; foreach ($bulanan as $bln => $kg): $k++; ?>
            <div class="vbar <?= $k === count($bulanan) ? 'now' : '' ?>"><b><?= angka(round($kg)) ?></b><div style="height:<?= round($kg / $maksBulan * 130) ?>px"></div><span><?= tgl($bln . '-01', 'bulan') ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="panel">
        <h3>Per jenis sampah</h3>
        <div class="bars"><?= bar_list(array_map(fn($j, $kg) => ['label' => $j, 'nilai' => round($kg), 'satuan' => 'kg'], array_keys($perJenis), $perJenis)) ?></div>
      </div>
      <div class="panel">
        <h3>Jadwal dan tempat</h3>
        <p><?= e(setting('info_bank_sampah')) ?></p>
      </div>
      <div class="panel">
        <h3>Harga beli sampah</h3>
        <div class="table-wrap"><table>
          <?php foreach (pasangan(setting('harga_sampah')) as [$jenisSampah, $harga]): ?><tr><td><?= e($jenisSampah) ?></td><td class="num"><b><?= e($harga) ?></b></td></tr><?php endforeach; ?>
        </table></div>
      </div>
    </div>
  <?php endif; ?>

<?php else: ?>
  <?php if ($grup === 'Kesehatan' && !empty($posyandu)): ?>
    <div class="panel posyandu-box" style="margin-bottom:16px">
      <h3>Jadwal posyandu terdekat</h3>
      <p class="small muted" style="margin-top:0">Simpan ke kalender HP supaya tidak lupa membawa balita menimbang dan imunisasi.</p>
      <?php foreach ($posyandu as $a): ?>
        <div class="posyandu-baris">
          <div><b><?= e($a['judul']) ?></b><br><span class="small muted"><?= tgl($a['tanggal'], 'hari') ?>, <?= e($a['jam']) ?>, <?= e($a['tempat']) ?></span></div>
          <a class="btn btn-ghost btn-sm" href="/agenda?ics=<?= (int) $a['id'] ?>">Tambah ke kalender</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="grid g2">
    <?php foreach ($grupData[$grup] as $kategori): ?>
      <div class="panel">
        <h3><?= e($kategori) ?></h3>
        <div class="bars"><?= bar_list(statistik($kategori)) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>
<?php
$scriptHalaman = ($tiketAntre && $tiketAntre['status'] === 'Menunggu' ? '<script>setTimeout(function(){location.reload()},30000)</script>' : '') . <<<'JS'
<script>
// Pemandu layanan: tampilkan satu layanan yang dipilih, hitung dokumen yang sudah siap
(function () {
  const pilih = document.getElementById('pilih-layanan');
  if (!pilih) return;
  const kartu = document.querySelectorAll('.layanan-kartu');
  const tampil = () => kartu.forEach(k => { k.hidden = k.dataset.layanan !== pilih.value; });
  pilih.addEventListener('change', () => { tampil(); history.replaceState(null, '', '?grup=Layanan+surat&l=' + pilih.value); });
  tampil();
  kartu.forEach(k => {
    const cek = k.querySelectorAll('.syarat-cek');
    const hitung = () => {
      const siap = [...cek].filter(c => c.checked).length;
      k.querySelector('.hitung-siap').textContent = siap;
      k.querySelector('.siap-bar').style.width = (siap / cek.length * 100) + '%';
    };
    cek.forEach(c => c.addEventListener('change', hitung));
  });
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php'; ?>
