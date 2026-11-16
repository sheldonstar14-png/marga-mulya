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
$grupKhusus = ['Transparansi', 'Pembangunan', 'Program bantuan', 'Bank sampah'];
$grup = $_GET['grup'] ?? 'Penduduk';
if (!isset($grupData[$grup]) && !in_array($grup, $grupKhusus, true)) {
    $grup = 'Penduduk';
}

// ---------- Data untuk tab khusus ----------
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
} elseif ($grup === 'Program bantuan') {
    $program = q('SELECT * FROM bantuan WHERE aktif = 1 ORDER BY id');
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

<?php if ($grup === 'Transparansi'): ?>
  <?php if (!$pos): ?>
    <p class="muted">Data APBDes belum diisi.</p>
  <?php else: ?>
    <h2 class="sub-judul">APBDes tahun <?= $tahun ?></h2>
    <div class="stat-row">
      <div class="stat"><span>Pendapatan desa</span><b><?= rupiah_ringkas($pendapatan) ?></b><small>terealisasi <?= persen($realisasiPendapatan, $pendapatan) ?>%</small></div>
      <div class="stat"><span>Belanja desa</span><b><?= rupiah_ringkas($belanja) ?></b><small>terealisasi <?= persen($realisasiBelanja, $belanja) ?>%</small></div>
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
              <div class="bar-track" role="img" aria-label="Terealisasi <?= $pr ?> persen"><div class="bar-fill" style="width:<?= $pr ?>%"></div></div>
              <small class="muted">Terealisasi <?= rupiah($p['realisasi']) ?> (<?= $pr ?>%)</small>
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

<?php elseif ($grup === 'Program bantuan'): ?>
  <h2 class="sub-judul">Program bantuan untuk warga</h2>
  <p class="muted">Informasi jenis bantuan, syarat, dan cara mendaftar. Daftar nama penerima tidak dipublikasikan untuk melindungi data pribadi warga.</p>
  <div class="grid g3">
    <?php foreach ($program as $b): ?>
      <article class="panel bantuan">
        <span class="tag"><?= e($b['penyelenggara']) ?></span>
        <h3 style="margin:8px 0 4px"><?= e($b['nama']) ?></h3>
        <p class="small muted" style="margin:0 0 10px">Untuk: <?= e($b['sasaran']) ?></p>
        <b class="small">Syarat</b>
        <ul class="small" style="margin:4px 0 10px;padding-left:18px"><?php foreach (per_baris($b['syarat']) as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul>
        <p class="small" style="margin:0 0 6px"><b>Jadwal:</b> <?= e($b['jadwal']) ?></p>
        <p class="small" style="margin:0 0 12px"><b>Cara daftar:</b> <?= e($b['cara_daftar']) ?></p>
        <a class="btn btn-ghost btn-sm" href="<?= e(wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya tentang program ' . $b['nama'] . '.')) ?>" target="_blank" rel="noopener">Tanya lewat WhatsApp</a>
      </article>
    <?php endforeach; ?>
    <?php if (!$program): ?><p class="muted">Belum ada program bantuan yang dibuka.</p><?php endif; ?>
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
<?php require __DIR__ . '/../inc/footer.php'; ?>
