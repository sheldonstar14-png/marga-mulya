<?php
// =========================================================
// HALAMAN BERANDA (desain C, Fase 12q). Semua isi diambil dari database.
// Susunan: sambutan + pencarian, dua pintu (warga dan pengunjung), angka desa,
// kabar dan agenda, wisata pesisir, produk warga.
// =========================================================
$wisata = q('SELECT slug, nama, jenis, harga, ringkas, foto FROM wisata WHERE aktif = 1 ORDER BY id');
try {
    $agenda = q('SELECT id, judul, tanggal, jam, tempat, relawan_kuota FROM agenda WHERE tanggal >= ? ORDER BY tanggal LIMIT 3', [date('Y-m-d')]);
} catch (Throwable $e) {
    $agenda = q('SELECT id, judul, tanggal, jam, tempat, 0 AS relawan_kuota FROM agenda WHERE tanggal >= ? ORDER BY tanggal LIMIT 3', [date('Y-m-d')]);
}
$umkm   = q('SELECT id, nama_usaha, produk, harga, satuan, foto FROM umkm ORDER BY id LIMIT 4');
$berita = q('SELECT id, judul, tanggal, foto FROM berita ORDER BY tanggal DESC, id DESC LIMIT 3');

// Angka desa dari BPS (statistik tanpa tanda ilustrasi)
$penduduk = (int) (q1("SELECT COALESCE(SUM(nilai), 0) AS n FROM statistik WHERE kategori = 'Kependudukan' AND label IN ('Laki-laki', 'Perempuan')")['n'] ?? 0);
$luasKm = round((float) (q1("SELECT COALESCE(SUM(nilai), 0) AS n FROM statistik WHERE kategori = 'Lahan' AND satuan = 'ha'")['n'] ?? 0) / 100, 2);
$rw = (int) (q1("SELECT nilai FROM statistik WHERE kategori = 'Pemerintahan' AND label = 'RW'")['nilai'] ?? 0);
$rt = (int) (q1("SELECT nilai FROM statistik WHERE kategori = 'Pemerintahan' AND label = 'RT'")['nilai'] ?? 0);
$fotoHero = setting('foto_hero');

// Bahan pencarian cepat: halaman, layanan, wisata, produk, dan jawaban asisten.
// Dicari di browser, jadi hasilnya selalu sama dengan isi CMS dan tidak ada jawaban karangan.
$indeksCari = [
    ['Ajukan surat dari rumah', 'surat sktm domisili usaha pengantar nikah keterangan', '/data-desa?grup=Layanan+surat#surat'],
    ['Cek penerima bantuan sosial', 'bansos bantuan blt penerima cek', '/data-desa?grup=Pantau+bansos#cek-bansos'],
    ['Kirim aspirasi atau laporan', 'aspirasi lapor pengaduan keluhan jalan rusak lampu', '/kontak#aspirasi'],
    ['Papan aspirasi warga', 'aspirasi perkembangan status laporan', '/kontak#papan'],
    ['Nomor darurat', 'darurat polisi ambulans pemadam kebakaran 112 sar', '/kontak#darurat'],
    ['Kunjungan rombongan', 'rombongan sekolah study tour reservasi pesan kunjungan', '/reservasi'],
    ['Anggaran desa (APBDes)', 'apbdes anggaran dana desa belanja transparansi', '/data-desa?grup=Transparansi'],
    ['Pembangunan desa', 'pembangunan proyek jalan tambatan', '/data-desa?grup=Pembangunan'],
    ['Data penduduk dan pekerjaan', 'penduduk jumlah warga pekerjaan nelayan petani', '/data-desa?grup=Penduduk'],
    ['Perikanan dan pertanian', 'perikanan nelayan ikan tangkapan sawah padi kelapa', '/data-desa?grup=Perikanan+dan+pertanian'],
    ['Ajukan produk UMKM', 'titip jual produk ajukan umkm usaha', '/umkm#ajukan'],
    ['Agenda dan posyandu', 'agenda kegiatan posyandu jadwal', '/agenda'],
];
try { foreach (q('SELECT id, nama FROM layanan WHERE aktif = 1 ORDER BY urutan, id') as $l) $indeksCari[] = [$l['nama'], 'surat syarat ' . $l['nama'], '/data-desa?grup=Layanan+surat&l=' . (int) $l['id'] . '#surat']; } catch (Throwable $e) {}
foreach ($wisata as $w) $indeksCari[] = [$w['nama'], 'wisata harga tiket jam buka ' . $w['jenis'] . ' ' . $w['ringkas'], '/wisata/' . $w['slug']];
foreach (q('SELECT id, nama_usaha, produk, kategori FROM umkm') as $u) $indeksCari[] = [$u['produk'] . ' (' . preg_replace('/\s*\(ilustrasi\)/', '', $u['nama_usaha']) . ')', 'umkm beli pesan ' . $u['kategori'], '/umkm#umkm-' . (int) $u['id']];
try { foreach (q("SELECT judul, jawaban, tautan FROM asisten_menu WHERE aktif = 1 AND tautan <> ''") as $a) $indeksCari[] = [$a['judul'], (string) $a['jawaban'], $a['tautan']]; } catch (Throwable $e) {}

$judul = setting('nama_desa');
$menu  = '';
$pakaiCuaca = true;
require __DIR__ . '/../inc/header.php';

$ikon = fn(string $d) => '<i aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg></i>';
?>
<section class="beranda-hero" aria-labelledby="judul-hero">
  <div class="wrap">
    <div class="foto-hero<?= $fotoHero ? '' : ' foto-hero-ilustrasi' ?>">
      <?php if ($fotoHero): ?>
        <img src="<?= e($fotoHero) ?>" alt="Pemandangan <?= e(setting('nama_desa')) ?>" fetchpriority="high">
      <?php else: ?>
        <img class="perahu-hero" src="/assets/img/perahu.svg" alt="" width="300" height="164">
        <span class="ganti-foto">Gambar ilustrasi. Foto asli bisa dipasang lewat CMS &gt; Pengaturan &gt; Foto beranda</span>
      <?php endif; ?>
    </div>
    <div class="sambut">
      <p class="sambut-kecil">Desa tepi laut di <?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></p>
      <h1 id="judul-hero"><?= e(setting('hero_judul')) ?></h1>
      <p class="sambut-teks"><?= e(setting('hero_teks')) ?></p>
      <form class="cari" id="cariForm" role="search" action="/data-desa" method="get">
        <label class="sr-only" for="cariCepat">Cari di website desa</label>
        <input id="cariCepat" name="q" type="search" autocomplete="off" maxlength="100" placeholder="Cari: syarat SKTM, pantai, kerupuk ikan">
        <button class="btn btn-navy" type="submit">Cari</button>
      </form>
      <ul class="cari-hasil" id="cariHasil" hidden aria-live="polite"></ul>
    </div>
  </div>
</section>

<div class="wrap">
  <div class="pintu">
    <section class="p p-warga" aria-labelledby="judul-warga">
      <h2 id="judul-warga">Untuk warga</h2>
      <p>Urus surat dan keperluan desa dari HP.</p>
      <div class="pintu-daftar">
        <a href="/data-desa?grup=Layanan+surat#surat"><?= $ikon('<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/>') ?>Ajukan surat</a>
        <a href="/data-desa?grup=Pantau+bansos"><?= $ikon('<path d="M4 11l8-6 8 6v9H4z"/><path d="M10 20v-5h4v5"/>') ?>Pantau bansos</a>
        <a href="/kontak#aspirasi"><?= $ikon('<path d="M4 5h16v11H9l-5 4z"/>') ?>Kirim aspirasi</a>
        <a href="/data-desa?grup=Transparansi"><?= $ikon('<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>') ?>Data dan anggaran</a>
      </div>
    </section>
    <section class="p p-tamu" aria-labelledby="judul-tamu">
      <h2 id="judul-tamu">Untuk pengunjung</h2>
      <p>Liburan ke pesisir dan belanja produk warga.</p>
      <div class="pintu-daftar">
        <a href="/wisata"><?= $ikon('<path d="M3 15h18l-2.5 4.5H5.5z"/><path d="M12 15V4l6 7h-6"/>') ?>Wisata pesisir</a>
        <a href="/umkm"><?= $ikon('<path d="M5 8h14l-1 12H6z"/><path d="M9 8a3 3 0 0 1 6 0"/>') ?>Produk UMKM</a>
        <a href="/reservasi"><?= $ikon('<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 20c0-2 1-4 3-4s3 1.5 3 4"/>') ?>Kunjungan rombongan</a>
        <a href="/agenda"><?= $ikon('<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>') ?>Agenda desa</a>
      </div>
    </section>
  </div>
  <div class="siaga" data-siaga data-lat="<?= e(setting('lat')) ?>" data-lng="<?= e(setting('lng')) ?>" hidden></div>

  <section class="angka-desa" aria-label="Desa dalam angka">
    <div><b><?= angka($penduduk) ?></b><span>jiwa penduduk</span></div>
    <div><b><?= str_replace('.', ',', (string) $luasKm) ?> km²</b><span>luas wilayah</span></div>
    <div><b><?= $rw ?> RW, <?= $rt ?> RT</b><span>wilayah pemerintahan</span></div>
    <div><b>Laut Jawa</b><span>batas utara desa</span></div>
    <?= tautan_referensi() ?>
  </section>
</div>

<section class="sec" aria-labelledby="judul-kabar"><div class="wrap kabar-agenda">
  <div>
    <div class="kepala"><h2 id="judul-kabar">Kabar desa</h2><a href="/agenda#berita">Semua berita</a></div>
    <div class="kabar-daftar">
      <?php foreach ($berita as $b): ?>
        <a class="kabar-item" href="/agenda#berita-<?= (int) $b['id'] ?>">
          <img src="<?= e($b['foto'] ?: '/assets/img/perahu.svg') ?>" alt="" loading="lazy" width="150" height="106">
          <span><time datetime="<?= e($b['tanggal']) ?>"><?= tgl($b['tanggal']) ?></time><b><?= e($b['judul']) ?></b></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <aside class="agenda-dekat" aria-labelledby="judul-agenda">
    <h2 id="judul-agenda">Agenda terdekat</h2>
    <?php foreach ($agenda as $a): $t = strtotime($a['tanggal']); ?>
      <div class="ag">
        <div class="tgl"><b><?= date('j', $t) ?></b><small><?= ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][(int) date('n', $t)] ?></small></div>
        <div><p><?= e($a['judul']) ?></p><span><?= e($a['jam']) ?>, <?= e($a['tempat']) ?></span>
          <?php if ((int) $a['relawan_kuota'] > 0): ?><a class="ag-ikut" href="/agenda#agenda-<?= (int) $a['id'] ?>">Ikut jadi relawan</a><?php endif; ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$agenda): ?><p class="muted">Belum ada agenda dalam waktu dekat.</p><?php endif; ?>
    <a class="agenda-semua" href="/agenda">Lihat semua agenda</a>
  </aside>
</div></section>

<section class="sec sec-pasir" aria-labelledby="judul-wisata"><div class="wrap">
  <div class="kepala"><div><h2 id="judul-wisata">Jelajahi pesisir Marga Mulya</h2><p>Tempat yang bisa dikunjungi bersama keluarga atau rombongan.</p></div><a href="/wisata">Semua wisata</a></div>
  <div class="wisata-grid">
    <?php foreach (array_slice($wisata, 0, 4) as $w): ?>
      <a class="w-kartu" href="/wisata/<?= e($w['slug']) ?>">
        <span class="w-foto"><img src="<?= e($w['foto'] ?: '/assets/img/pantai.svg') ?>" alt="" loading="lazy"></span>
        <span class="w-isi"><span class="chip-kecil"><?= e($w['jenis']) ?></span><b><?= e($w['nama']) ?></b><span><?= e($w['ringkas']) ?></span><span class="w-harga"><?= (int) $w['harga'] ? rupiah($w['harga']) . ' per orang' : 'Gratis' ?></span></span>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="sec" aria-labelledby="judul-produk"><div class="wrap">
  <div class="kepala"><div><h2 id="judul-produk">Produk buatan warga</h2><p>Pesan langsung ke pembuatnya lewat WhatsApp.</p></div><a class="btn btn-main" href="/umkm">Lihat semua produk</a></div>
  <div class="produk-grid">
    <?php foreach ($umkm as $u): ?>
      <a class="u-kartu" href="/umkm#umkm-<?= (int) $u['id'] ?>">
        <span class="u-foto"><img src="<?= e($u['foto'] ?: '/assets/img/ikan.svg') ?>" alt="" loading="lazy"></span>
        <span class="u-isi"><b><?= e($u['produk']) ?></b><span><b class="u-harga"><?= rupiah($u['harga']) ?></b> / <?= e($u['satuan']) ?></span></span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="ajak-umkm">Punya usaha di Marga Mulya? <a href="/umkm#ajukan">Titipkan produkmu di website desa, gratis</a>.</p>
</div></section>
<?php
$scriptHalaman = '<script>window.indeksCari = ' . json_encode($indeksCari, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . ';</script>' . <<<'JS'
<script>
// Cari cepat di beranda: mencocokkan kata dengan daftar halaman dan isi CMS, lalu menampilkan tautan.
(function () {
  const form = document.getElementById('cariForm'), input = document.getElementById('cariCepat'), hasil = document.getElementById('cariHasil');
  if (!form || !window.indeksCari) return;
  const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9 ]/g, ' ');
  const data = window.indeksCari.map(([judul, kata, url]) => ({ judul, url, teks: norm(judul + ' ' + kata), jdl: norm(judul) }));
  function cari() {
    const kata = norm(input.value).split(/\s+/).filter(k => k.length > 1);
    hasil.innerHTML = '';
    if (!kata.length) { hasil.hidden = true; return; }
    const skor = data.map(d => ({ d, s: kata.reduce((n, k) => n + (d.jdl.includes(k) ? 3 : d.teks.includes(k) ? 1 : 0), 0) }))
      .filter(x => x.s > 0).sort((a, b) => b.s - a.s)
      .filter((x, i, arr) => arr.findIndex(y => y.d.jdl === x.d.jdl) === i).slice(0, 5);
    if (!skor.length) {
      const li = document.createElement('li'); li.className = 'kosong';
      li.textContent = 'Belum ketemu. Coba kata lain, atau tekan Tanya asisten di pojok kanan bawah.';
      hasil.appendChild(li);
    }
    skor.forEach(({ d }) => {
      const li = document.createElement('li'), a = document.createElement('a');
      a.href = d.url; a.textContent = d.judul; li.appendChild(a); hasil.appendChild(li);
    });
    hasil.hidden = false;
  }
  input.addEventListener('input', cari);
  form.addEventListener('submit', e => { e.preventDefault(); cari(); const a = hasil.querySelector('a'); if (a && skorTunggal()) location.href = a.href; });
  function skorTunggal() { return hasil.querySelectorAll('a').length === 1; }
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php';
?>
