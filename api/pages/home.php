<?php
// =========================================================
// HALAMAN BERANDA (desain A "Samudra", Fase 12s). Semua isi diambil dari database.
// Susunan: panel judul + pencarian, kartu cuaca darat/laut dan kantor desa, akses cepat
// (warga dan pengunjung), sambutan kepala desa, angka desa, kabar dan agenda,
// wisata dan budaya, produk warga.
// =========================================================
$wisata = q('SELECT slug, nama, jenis, harga, ringkas, foto FROM wisata WHERE aktif = 1 ORDER BY id');
try {
    $agenda = q('SELECT id, judul, tanggal, jam, tempat, relawan_kuota FROM agenda WHERE tanggal >= ? ORDER BY tanggal LIMIT 3', [date('Y-m-d')]);
} catch (Throwable $e) {
    $agenda = q('SELECT id, judul, tanggal, jam, tempat, 0 AS relawan_kuota FROM agenda WHERE tanggal >= ? ORDER BY tanggal LIMIT 3', [date('Y-m-d')]);
}
$umkm   = q('SELECT id, nama_usaha, produk, harga, satuan, foto FROM umkm ORDER BY id LIMIT 4');
$berita = q('SELECT * FROM berita ORDER BY tanggal DESC, id DESC LIMIT 3');
$budaya = [];
try { $budaya = q('SELECT id, judul, jenis, waktu, foto FROM budaya ORDER BY urutan, id LIMIT 4'); } catch (Throwable $e) {}
[$kantorBuka, $kantorKet] = status_kantor();
$antreHariIni = 0;
try { $antreHariIni = (int) q1("SELECT COUNT(*) AS n FROM antrean WHERE tanggal = ? AND status = 'Menunggu'", [date('Y-m-d')])['n']; } catch (Throwable $e) {}

// Angka desa dari BPS (statistik tanpa tanda ilustrasi)
$penduduk = (int) (q1("SELECT COALESCE(SUM(nilai), 0) AS n FROM statistik WHERE kategori = 'Kependudukan' AND label IN ('Laki-laki', 'Perempuan')")['n'] ?? 0);
$luasKm = round((float) (q1("SELECT COALESCE(SUM(nilai), 0) AS n FROM statistik WHERE kategori = 'Lahan' AND satuan = 'ha'")['n'] ?? 0) / 100, 2);
$rw = (int) (q1("SELECT nilai FROM statistik WHERE kategori = 'Pemerintahan' AND label = 'RW'")['nilai'] ?? 0);
$rt = (int) (q1("SELECT nilai FROM statistik WHERE kategori = 'Pemerintahan' AND label = 'RT'")['nilai'] ?? 0);
$fotoHero = setting('foto_hero');
// 12u: Lingkungan pesisir (tema EcoQuest)
$agendaHijau = [];
try { $agendaHijau = q("SELECT id, judul, tanggal, jam, tempat FROM agenda WHERE tanggal >= ? AND (LOWER(judul) LIKE '%mangrove%' OR LOWER(judul) LIKE '%pantai%' OR LOWER(judul) LIKE '%sampah%' OR LOWER(judul) LIKE '%pohon%') ORDER BY tanggal LIMIT 2", [date('Y-m-d')]); } catch (Throwable $e) {}
$jumlahZona = 0;
try { $jumlahZona = (int) (q1('SELECT COUNT(*) AS n FROM zona_rawan')['n'] ?? 0); } catch (Throwable $e) {}
$bersihPantai = null;
try { $bersihPantai = q1("SELECT id, judul, waktu FROM budaya WHERE LOWER(judul) LIKE '%bersih pantai%' ORDER BY id LIMIT 1"); } catch (Throwable $e) {}

// Bahan pencarian cepat: halaman, layanan, wisata, produk, dan jawaban asisten.
// Dicari di browser, jadi hasilnya selalu sama dengan isi CMS dan tidak ada jawaban karangan.
$indeksCari = [
    ['Ajukan surat dari rumah', 'surat sktm domisili usaha pengantar nikah keterangan', '/data-desa?grup=Layanan+surat#surat'],
    ['Cek penerima bantuan sosial', 'bansos bantuan blt penerima cek', '/data-desa?grup=Pantau+bansos#cek-bansos'],
    ['Kirim aspirasi atau laporan', 'aspirasi lapor pengaduan keluhan jalan rusak lampu', '/kontak#aspirasi'],
    ['Papan aspirasi warga', 'aspirasi perkembangan status laporan', '/kontak#papan'],
    ['Nomor darurat', 'darurat polisi ambulans pemadam kebakaran 112 sar', '/kontak#darurat'],
    ['Izin kunjungan rombongan, KKN, atau penelitian', 'rombongan sekolah study tour reservasi pesan kunjungan kkn penelitian izin', '/data-desa?grup=Layanan+surat&izin=1#surat'],
    ['Kerja dan pelatihan', 'loker lowongan kerja pekerjaan pelatihan blk kursus panen tambak abk pemandu', '/kerja'],
    ['Cuaca darat dan gelombang laut', 'cuaca hujan gelombang laut angin melaut nelayan petani jemur gabah', '/cuaca'],
    ['Budaya dan kesenian', 'budaya kesenian sedekah laut marawis gambang kromong silat kuliner', '/wisata#budaya'],
    ['Kode etik pengunjung', 'aturan etika pengunjung sampah foto', '/wisata#kode-etik'],
    ['Pertanyaan kepada petugas desa', 'tanya pertanyaan petugas bertanya', '/kontak?kategori=Pertanyaan#aspirasi'],
    ['Agama dan tempat ibadah', 'agama masjid musala gereja vihara ibadah', '/data-desa?grup=Penduduk'],
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


$ikon = fn(string $d) => '<i aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg></i>';
// Judul: bagian nama desa diberi warna toska di baris kedua
$judulHero = setting('hero_judul');
$namaDesa = setting('nama_desa');
$posNama = mb_stripos($judulHero, $namaDesa);
// Akses cepat: satu kotak untuk warga dan pengunjung (12s4)
$aksesCepat = [
    ['Ajukan surat', '/data-desa?grup=Layanan+surat#surat', '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/>'],
    ['Bantuan sosial', '/data-desa?grup=Pantau+bansos', '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3.5"/>'],
    ['Aspirasi dan pertanyaan', '/kontak#aspirasi', '<path d="M4 5h16v11H9l-5 4z"/>'],
    ['Anggaran desa', '/data-desa?grup=Transparansi', '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
    ['Kerja dan pelatihan', '/kerja', '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>'],
    ['Nomor darurat', '/kontak#darurat', '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>', 'tile-darurat'],
    ['Wisata dan budaya', '/wisata', '<path d="M3 16h18l-2.5 4H5.5z"/><path d="M12 16V3l6 9h-6"/>'],
    ['Produk UMKM', '/umkm', '<path d="M5 8h14l-1 12H6z"/><path d="M9 8a3 3 0 0 1 6 0"/>'],
    ['Peta dan rute', '/profil#peta-desa', '<circle cx="12" cy="10" r="3"/><path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/>'],
    ['Agenda dan relawan', '/agenda', '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>'],
    ['Cuaca darat dan laut', '/cuaca', '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19"/>'],
    ['Kode etik pengunjung', '/wisata#kode-etik', '<path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/><path d="M9 12l2 2 4-4"/>'],
];
$bulanPendek = ['', 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
?>
<section class="hero-a" aria-labelledby="judul-hero">
  <div class="wrap">
    <div class="hero-panel<?= $fotoHero ? ' hero-berfoto' : '' ?>"<?= $fotoHero ? ' style="--foto-hero:url(\'' . e($fotoHero) . '\')"' : '' ?>>
      <div class="hero-teks">
        <span class="pill"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/></svg><?= e(teks('label_status')) ?></span>
        <h1 id="judul-hero"><?php if ($posNama !== false && $posNama > 0): ?><?= e(rtrim(mb_substr($judulHero, 0, $posNama))) ?> <em><?= e(mb_substr($judulHero, $posNama)) ?></em><?php else: ?><?= e($judulHero) ?><?php endif; ?></h1>
        <p class="hero-sub"><?= e(setting('hero_teks')) ?></p>
        <form class="cari" id="cariForm" role="search" action="/data-desa" method="get">
          <label class="sr-only" for="cariCepat">Cari di website desa</label>
          <input id="cariCepat" name="q" type="search" autocomplete="off" maxlength="100" placeholder="Cari layanan, misalnya syarat SKTM">
          <button type="submit">Cari</button>
        </form>
        <ul class="cari-hasil" id="cariHasil" hidden aria-live="polite"></ul>
      </div>
      <?php if ($fotoHero && semua_foto_ilustrasi()): ?><span class="hero-ilus"><?= e(teks('teks_foto_beranda')) ?></span><?php endif; ?>
      <?php if (!$fotoHero): ?><div class="hero-gambar" aria-hidden="true"><img src="/assets/img/panorama.svg" alt="" width="1200" height="240"></div><?php endif; ?>
    </div>
    <div class="info-a" data-info-cuaca data-lat="<?= e(setting('lat')) ?>" data-lng="<?= e(setting('lng')) ?>">
      <a class="info-blok" href="/cuaca#darat" data-darat>
        <span class="ib-ikon ib-darat" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19"/></svg></span>
        <span><small>Cuaca darat, sawah dan kebun</small><b data-isi="judul">Memuat prakiraan...</b><span data-isi="ket">Sumber: Open-Meteo</span></span>
      </a>
      <a class="info-blok" href="/cuaca#laut" data-laut>
        <span class="ib-ikon ib-laut" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 9c2.5-2 5-2 7.5 0s5 2 7.5 0 3-1.5 5-1M2 15c2.5-2 5-2 7.5 0s5 2 7.5 0 3-1.5 5-1"/></svg></span>
        <span><small>Cuaca laut, perairan <?= e(setting('kecamatan')) ?></small><b data-isi="judul">Memuat gelombang...</b><span data-isi="ket">Untuk nelayan dan wisata pantai</span></span>
      </a>
      <a class="info-blok" href="/data-desa?grup=Layanan+surat#antrean">
        <span class="ib-ikon ib-kantor" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6"/></svg></span>
        <span><small>Kantor desa</small><b><?= $kantorBuka === null ? 'Jam layanan' : ($kantorBuka ? 'Buka sekarang' : 'Sedang tutup') ?></b><span><?= e($kantorKet) ?><?= $kantorBuka ? ', antrean ' . $antreHariIni . ' orang' : '' ?></span></span>
      </a>
    </div>
    <div class="siaga" data-siaga data-lat="<?= e(setting('lat')) ?>" data-lng="<?= e(setting('lng')) ?>" hidden></div>
  </div>
</section>

<section class="sec sec-akses" aria-labelledby="judul-akses"><div class="wrap">
  <div class="akses-satu">
    <div class="akses-kepala"><h2 id="judul-akses">Akses cepat</h2><p>Layanan dan informasi yang paling sering dicari warga dan pengunjung.</p></div>
    <div class="tiles tiles-12"><?php foreach ($aksesCepat as $t): [$l, $u, $d] = $t; ?><a href="<?= e($u) ?>"<?= !empty($t[3]) ? ' class="' . $t[3] . '"' : '' ?>><?= $ikon($d) ?><span><?= e($l) ?></span></a><?php endforeach; ?></div>
  </div>
</div></section>

<section class="sec sec-tipis" aria-label="Sambutan kepala desa"><div class="wrap">
  <?= sambutan_html(true) ?>
  <div class="angka-desa">
    <div><b><?= angka($penduduk) ?></b><span>jiwa penduduk</span></div>
    <div><b><?= str_replace('.', ',', (string) $luasKm) ?> km²</b><span>luas wilayah</span></div>
    <div><b><?= $rw ?> RW, <?= $rt ?> RT</b><span>wilayah pemerintahan</span></div>
    <div><b>Laut Jawa</b><span>batas utara desa</span></div>
    <?= tautan_referensi() ?>
  </div>
</div></section>

<section class="sec eko" aria-labelledby="judul-eko"><div class="wrap">
  <div class="eko-kepala">
    <div>
      <h2 id="judul-eko">Menjaga pesisir Marga Mulya</h2>
      <p>Desa ini hidup dari laut, tambak, dan sawah. Abrasi, banjir rob, dan sampah plastik adalah tantangan sehari-hari, jadi warga menjaganya bersama.</p>
    </div>
    <div class="eko-sdg" aria-label="Tujuan SDGs yang didukung"><?php foreach ([13, 14, 15] as $n): ?><span style="background:<?= SDG[$n][1] ?>;color:<?= SDG[$n][2] ?>"><b><?= $n ?></b><?= e(SDG[$n][0]) ?></span><?php endforeach; ?></div>
  </div>
  <div class="eko-grid">
    <a class="eko-kartu" href="/wisata#budaya">
      <i aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22V12"/><path d="M12 12c-4 0-7-3-7-7 4 0 7 3 7 7zM12 14c4 0 7-3 7-7-4 0-7 3-7 7z"/><path d="M5 22h14"/></svg></i>
      <b>Tanam mangrove</b>
      <span>Akar mangrove menahan ombak dan abrasi, sekaligus jadi rumah kepiting dan ikan. Warga dan pengunjung menanam bibit bersama saat musim hujan.</span>
      <?php if ($agendaHijau): $ah = $agendaHijau[0]; ?><small>Berikutnya: <?= e(pisah_ilustrasi($ah['judul'])[0]) ?>, <?= tgl($ah['tanggal'], 'pendek') ?></small><?php else: ?><small>Lihat jadwal di Agenda</small><?php endif; ?>
    </a>
    <a class="eko-kartu" href="/agenda">
      <i aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/></svg></i>
      <b>Bersih pantai dan pilah sampah</b>
      <span>Sampah plastik di pesisir dipungut, dipilah, lalu dijual. Hasilnya masuk kas Karang Taruna.</span>
      <small><?= $bersihPantai && trim((string) $bersihPantai['waktu']) !== '' ? e($bersihPantai['waktu']) : 'Setiap bulan' ?>, terbuka untuk relawan</small>
    </a>
    <a class="eko-kartu" href="/profil#peta-desa">
      <i aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 16c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2M2 20c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2"/><path d="M12 3l4 7H8z"/></svg></i>
      <b>Waspada abrasi dan rob</b>
      <span>Peta desa menandai kawasan yang rawan terkena abrasi, rob, dan genangan, supaya warga dan pengunjung bisa berhati-hati.</span>
      <small><?= $jumlahZona ? $jumlahZona . ' kawasan rawan di peta' : 'Lihat peta desa' ?></small>
    </a>
    <a class="eko-kartu" href="/cuaca">
      <i aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 18a5 5 0 1 1 1-9.9A6 6 0 0 1 19 10a4 4 0 0 1 0 8z"/><path d="M8 21l1-2M12 21l1-2M16 21l1-2"/></svg></i>
      <b>Cuaca dan gelombang</b>
      <span>Prakiraan hujan dan tinggi gelombang membantu nelayan memutuskan melaut dan petani menjadwalkan tanam.</span>
      <small>Sumber data: Open-Meteo</small>
    </a>
  </div>
  <p class="eko-catatan">Kegiatan dan kawasan di atas adalah gambaran program lingkungan untuk desa pesisir.</p>
</div></section>

<section class="sec sec-putih" aria-labelledby="judul-kabar"><div class="wrap kabar-agenda">
  <div>
    <div class="kepala"><h2 id="judul-kabar">Kabar desa terbaru</h2><a href="/agenda#berita">Lihat semua</a></div>
    <div class="kabar-a">
      <?php foreach ($berita as $i => $b): [$jdl, $ilus] = pisah_ilustrasi($b['judul']); ?>
        <a class="kartu-a<?= $i === 0 ? ' kartu-utama' : '' ?>" href="/agenda#berita-<?= (int) $b['id'] ?>">
          <span class="kartu-foto"><img src="<?= e($b['foto'] ?: '/assets/img/panorama.svg') ?>" alt="" loading="lazy"><?= tanda_foto((string) $b['foto'], $ilus) ?></span>
          <span class="kartu-isi"><small><?= e($b['kategori'] ?? 'Kabar desa') ?>, <time datetime="<?= e($b['tanggal']) ?>"><?= tgl($b['tanggal']) ?></time></small><b><?= e($jdl) ?></b></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <aside class="agenda-a" aria-labelledby="judul-agenda">
    <h2 id="judul-agenda">Agenda desa</h2>
    <?php foreach ($agenda as $a): $t = strtotime($a['tanggal']); [$jdlA, $ilusA] = pisah_ilustrasi($a['judul']); ?>
      <div class="ag-a">
        <span class="tgl-a"><small><?= $bulanPendek[(int) date('n', $t)] ?></small><b><?= date('j', $t) ?></b></span>
        <div><p><?= e($jdlA) ?> <?= tanda_ilustrasi($ilusA) ?></p><span><?= e($a['jam']) ?>, <?= e($a['tempat']) ?></span>
          <?php if ((int) $a['relawan_kuota'] > 0): ?><a class="ag-ikut" href="/agenda#agenda-<?= (int) $a['id'] ?>">Ikut jadi relawan</a><?php endif; ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$agenda): ?><p class="muted" style="padding:0 22px">Belum ada agenda dalam waktu dekat.</p><?php endif; ?>
    <a class="agenda-semua" href="/agenda">Lihat semua agenda</a>
  </aside>
</div></section>

<section class="sec" aria-labelledby="judul-wisata"><div class="wrap">
  <div class="kepala"><div><h2 id="judul-wisata">Wisata dan budaya <?= e(str_replace('Desa ', '', $namaDesa)) ?></h2><p>Pantai dan mangrove, kampung nelayan, sawah, kerajinan, sampai tradisi dan kuliner warga.</p></div><a href="/wisata">Semua destinasi</a></div>
  <div class="wisata-grid">
    <?php foreach (array_slice($wisata, 0, 4) as $w): [$nmW, $ilusW] = pisah_ilustrasi($w['nama']); ?>
      <a class="w-kartu" href="/wisata/<?= e($w['slug']) ?>">
        <span class="w-foto"><img src="<?= e($w['foto'] ?: '/assets/img/panorama.svg') ?>" alt="" loading="lazy"><?= tanda_foto((string) $w['foto'], $ilusW) ?></span>
        <span class="w-isi"><span class="chip-kecil"><?= e($w['jenis']) ?></span><b><?= e($nmW) ?></b><span><?= e($w['ringkas']) ?></span><span class="w-harga"><?= (int) $w['harga'] ? rupiah($w['harga']) . ' per orang' : 'Gratis' ?></span></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if ($budaya): ?>
  <div class="budaya-strip">
    <h3>Budaya dan kesenian</h3>
    <div class="budaya-mini">
      <?php foreach ($budaya as $bd): [$jdlB, $ilusB] = pisah_ilustrasi($bd['judul']); ?>
        <a href="/wisata#budaya-<?= (int) $bd['id'] ?>"><img src="<?= e($bd['foto'] ?: '/assets/img/budaya-sedekah-laut.svg') ?>" alt="" loading="lazy" width="96" height="72"><span><small><?= e($bd['jenis'] ?? 'Budaya') ?></small><b><?= e($jdlB) ?></b><em><?= e($bd['waktu']) ?></em></span></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div></section>

<section class="sec sec-putih" aria-labelledby="judul-produk"><div class="wrap">
  <div class="kepala"><div><h2 id="judul-produk">Produk buatan warga</h2><p>Pesan langsung ke pembuatnya lewat WhatsApp, tanpa perantara.</p></div><a href="/umkm">Lihat semua produk</a></div>
  <div class="produk-grid">
    <?php foreach ($umkm as $u): [$usaha, $ilusU] = pisah_ilustrasi($u['nama_usaha']); ?>
      <a class="u-kartu" href="/umkm#umkm-<?= (int) $u['id'] ?>">
        <span class="u-foto"><img src="<?= e($u['foto'] ?: '/assets/img/ikan.svg') ?>" alt="" loading="lazy"><?= tanda_foto((string) $u['foto'], $ilusU) ?></span>
        <span class="u-isi"><b><?= e($u['produk']) ?></b><span><?= e($usaha) ?></span><span><b class="u-harga"><?= rupiah($u['harga']) ?></b> / <?= e($u['satuan']) ?></span></span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="ajak-umkm">Punya usaha di <?= e(str_replace('Desa ', '', $namaDesa)) ?>? <a href="/umkm#ajukan">Titipkan produkmu di website desa, gratis</a>.</p>
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
