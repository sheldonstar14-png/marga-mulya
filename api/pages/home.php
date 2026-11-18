<?php
// =========================================================
// HALAMAN BERANDA. Semua isi diambil dari database.
// =========================================================
$wisata = q('SELECT slug, nama, jenis, harga, kuota, ringkas, foto FROM wisata WHERE aktif = 1 ORDER BY id');
try {
    $agenda = q('SELECT id, judul, tanggal, jam, tempat, isi, relawan_kuota FROM agenda WHERE tanggal >= ? ORDER BY tanggal LIMIT 3', [date('Y-m-d')]);
} catch (Throwable $e) { // migrasi 12k belum dijalankan
    $agenda = q('SELECT id, judul, tanggal, jam, tempat, isi, 0 AS relawan_kuota FROM agenda WHERE tanggal >= ? ORDER BY tanggal LIMIT 3', [date('Y-m-d')]);
}
$gotongRoyong = agenda_relawan(1)[0] ?? null;
$umkm   = q('SELECT nama_usaha, produk, harga, satuan, foto FROM umkm ORDER BY id LIMIT 3');
$berita = q('SELECT id, judul, tanggal, isi, foto FROM berita ORDER BY tanggal DESC, id DESC LIMIT 3');

// Ringkasan keterbukaan desa (Fase 8)
$tahunApb = (int) (q1('SELECT MAX(tahun) AS t FROM apbdes')['t'] ?? 0);
$apb = $tahunApb ? q1("SELECT COALESCE(SUM(anggaran), 0) AS a, COALESCE(SUM(realisasi), 0) AS r FROM apbdes WHERE tahun = ? AND jenis = 'Belanja'", [$tahunApb]) : null;
$proyekBerjalan = (int) q1("SELECT COUNT(*) AS n FROM pembangunan WHERE status = 'Berjalan'")['n'];
$proyekSelesai = (int) q1("SELECT COUNT(*) AS n FROM pembangunan WHERE status = 'Selesai'")['n'];
$bulanSampah = q1('SELECT MAX(bulan) AS b FROM sampah')['b'] ?? null;
$kgSampah = $bulanSampah ? (float) q1('SELECT COALESCE(SUM(berat_kg), 0) AS n FROM sampah WHERE bulan > ?', [date('Y-m-d', strtotime(substr($bulanSampah, 0, 7) . '-01 -6 month'))])['n'] : 0;
$aspTotal = (int) q1('SELECT COUNT(*) AS n FROM aspirasi')['n'];
$aspSelesai = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status = 'Selesai'")['n'];

// Hero Fusi A (Fase 12): angka mengambang dan foto lingkaran
$penduduk = (int) (q1("SELECT COALESCE(SUM(nilai), 0) AS n FROM statistik WHERE kategori = 'Kependudukan' AND label IN ('Laki-laki', 'Perempuan')")['n'] ?? 0);
$kk = (int) (q1("SELECT nilai FROM statistik WHERE kategori = 'Kependudukan' AND label = 'Kepala keluarga'")['nilai'] ?? 0);
$jumlahUmkm = (int) q1('SELECT COUNT(*) AS n FROM umkm')['n'];
$fotoHero = setting('foto_hero') ?: (array_values(array_filter(array_column($wisata, 'foto')))[0] ?? '/assets/img/sawah.svg');
$darurat = q('SELECT nama, nomor, keterangan FROM darurat ORDER BY urutan, id LIMIT 4');
$agendaDekat = $agenda[0] ?? null;
// Kata pengunjung: ulasan terbaru wisata dan UMKM yang sudah tampil
$kataPengunjung = q("SELECT * FROM (
    SELECT u.nama, u.bintang, u.komentar, w.nama AS tempat, CONCAT('/wisata/', w.slug) AS url, u.dibuat_pada FROM ulasan u JOIN wisata w ON w.id = u.wisata_id WHERE u.tampil = 1
    UNION ALL
    SELECT nama, bintang, komentar, usaha AS tempat, '/umkm' AS url, dibuat_pada FROM ulasan_umkm WHERE tampil = 1
) x ORDER BY dibuat_pada DESC LIMIT 3");

$judul = setting('nama_desa');
$menu  = '';
$pakaiCuaca = true;
require __DIR__ . '/../inc/header.php';
?>
<?php
// Menu cepat: satu daftar, tampil di bawah pencarian (HP) atau sebaris penuh di bawah hero (laptop)
ob_start(); ?>
      <nav class="pintas-box" aria-label="Menu cepat">
        <h2 class="pintas-judul">Mau ke mana?</h2>
        <ul class="pintas-grid">
          <li><a class="pintas pintas-hutan" href="/data-desa?grup=Layanan+surat#surat"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg></span><span class="pintas-label">Ajukan surat</span></a></li>
          <li><a class="pintas pintas-air" href="/data-desa?grup=Layanan+surat#antrean"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h3M21 18v3"/></svg></span><span class="pintas-label">Ambil antrean</span></a></li>
          <li><a class="pintas pintas-jeruk" href="/reservasi"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M9 15l2 2 4-4"/></svg></span><span class="pintas-label">Pesan kunjungan</span></a></li>
          <li><a class="pintas pintas-lime" href="/wisata"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 20l6-9 4 5 3-4 5 8z"/><circle cx="16" cy="6" r="2"/></svg></span><span class="pintas-label">Wisata</span></a></li>
          <li><a class="pintas pintas-jeruk" href="/umkm"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16l-1.5 13h-13z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg></span><span class="pintas-label">Belanja UMKM</span></a></li>
          <li><a class="pintas pintas-hutan" href="/data-desa?grup=Pantau+bansos"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v9H4v-9M2 7h20v5H2zM12 21V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg></span><span class="pintas-label">Pantau bansos</span></a></li>
          <li><a class="pintas pintas-lime" href="/kontak#aspirasi"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/></svg></span><span class="pintas-label">Kirim aspirasi</span></a></li>
          <li><a class="pintas pintas-merah" href="/kontak#darurat"><span class="pintas-ikon"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg></span><span class="pintas-label">Nomor darurat</span></a></li>
        </ul>
      </nav>
<?php $pintasHtml = ob_get_clean(); ?>
<section class="hero-fusi" aria-labelledby="judul-hero">
  <span class="hiasan hiasan-daun h1" aria-hidden="true"></span><span class="hiasan hiasan-daun h2" aria-hidden="true"></span><span class="hiasan hiasan-daun h3" aria-hidden="true"></span>
  <span class="hiasan hiasan-titik t1" aria-hidden="true"></span><span class="hiasan hiasan-titik t2" aria-hidden="true"></span><span class="hiasan hiasan-titik t3" aria-hidden="true"></span><span class="hiasan hiasan-titik t4" aria-hidden="true"></span>
  <div class="wrap">
  <div class="hero-split">
    <div>
      <span class="hero-sapa"><?= e(setting('nama_desa')) ?>, <?= e(setting('kecamatan')) ?></span>
      <h1 id="judul-hero"><?= e(setting('hero_judul')) ?></h1>
      <p class="hero-desc"><?= e(setting('hero_teks')) ?></p>
      <div class="cari-box">
        <label for="cariCepat">Cari apa hari ini?</label>
        <form class="cari-form" id="cariForm" action="/data-desa" method="get" role="search">
          <input id="cariCepat" name="q" type="search" autocomplete="off" maxlength="200" placeholder="Contoh: syarat SKTM, harga tiket pantai">
          <button type="submit" aria-label="Cari"><svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2.4"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></button>
        </form>
        <div class="cari-chips" role="group" aria-label="Pencarian cepat">
          <button type="button" data-cari="Syarat SKTM">Syarat SKTM</button>
          <button type="button" data-cari="Cek status bansos">Cek bansos</button>
          <button type="button" data-cari="Harga tiket wisata">Harga tiket</button>
          <button type="button" data-cari="Nomor darurat">Nomor darurat</button>
        </div>
        <div class="cari-hasil" id="cariHasil" role="status" aria-live="polite" hidden></div>
      </div>
      <?= str_replace('pintas-box', 'pintas-box pintas-hp', $pintasHtml) ?>
    </div>
    <div class="fusi-a">
      <div class="fusi-a-halo" aria-hidden="true"></div>
      <div class="fusi-a-lingkar"><img src="<?= e($fotoHero) ?>" alt="Pemandangan <?= e(setting('nama_desa')) ?>" fetchpriority="high"></div>
      <div class="fusi-a-stat">
        <a class="float-stat fs-1" href="/data-desa"><span class="ic" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="7" r="4"/><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M16 3.1a4 4 0 0 1 0 7.8M23 21v-2a4 4 0 0 0-3-3.9"/></svg></span><span><b><?= angka($penduduk) ?></b><span>Penduduk</span></span></a>
        <a class="float-stat fs-2" href="/data-desa"><span class="ic" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 11l9-7 9 7v10H3z"/><path d="M9 21v-6h6v6"/></svg></span><span><b><?= angka($kk) ?></b><span>Kepala keluarga</span></span></a>
        <a class="float-stat fs-3" href="/wisata"><span class="ic" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 20l6-9 4 5 3-4 5 8z"/><circle cx="16" cy="6" r="2"/></svg></span><span><b><?= count($wisata) ?> destinasi</b><span>Wisata alam</span></span></a>
        <a class="float-stat fs-4" href="/umkm"><span class="ic" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16l-1.5 13h-13z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg></span><span><b><?= $jumlahUmkm ?> UMKM</b><span>Karya warga</span></span></a>
      </div>
    </div>
  </div>
  <?= str_replace('pintas-box', 'pintas-box pintas-desk', $pintasHtml) ?>
  <div class="bento">
    <a class="bento-kartu bento-hutan" href="/data-desa?grup=Layanan+surat#surat"><div><h3>Ajukan surat dari rumah</h3><p>Isi online, datang saat surat sudah siap.</p></div><span class="bulat" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" fill="none" stroke-linecap="round"/></svg></span></a>
    <?php if ($apb && $apb['a'] > 0): $pr = min(100, persen((float) $apb['r'], (float) $apb['a'])); $kel = 2 * 3.1416 * 30; ?>
    <a class="bento-kartu" href="/data-desa?grup=Transparansi">
      <div style="display:flex;align-items:center;gap:14px">
        <span class="cincin" aria-hidden="true"><svg width="76" height="76" viewBox="0 0 76 76"><circle cx="38" cy="38" r="30" fill="none" stroke="#E2EAD5" stroke-width="9"/><circle cx="38" cy="38" r="30" fill="none" stroke="#E8641E" stroke-width="9" stroke-linecap="round" stroke-dasharray="<?= round($kel * $pr / 100, 1) ?> <?= round($kel, 1) ?>" transform="rotate(-90 38 38)"/></svg><b><?= persen_id((float) $apb['r'], (float) $apb['a']) ?>%</b></span>
        <div><h3>Belanja desa <?= $tahunApb ?></h3><p><?= rupiah_ringkas((float) $apb['r']) ?> dari <?= rupiah_ringkas((float) $apb['a']) ?></p></div>
      </div>
      <span class="bulat" style="background:var(--paper)" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8" stroke="currentColor" stroke-width="2.4" fill="none" stroke-linecap="round"/></svg></span>
    </a>
    <?php endif; ?>
    <a class="bento-kartu bento-jeruk" href="<?= $agendaDekat ? '/agenda' : '/data-desa?grup=Pantau+bansos' ?>">
      <div><?php if ($agendaDekat): ?><h3><?= e($agendaDekat['judul']) ?></h3><p><?= tgl($agendaDekat['tanggal'], 'hari') ?>, <?= e($agendaDekat['jam']) ?></p><?php else: ?><h3>Pantau bansos</h3><p>Cek penyaluran bantuan per tahap.</p><?php endif; ?></div>
      <span class="bulat" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" fill="none" stroke-linecap="round"/></svg></span>
    </a>
  </div>
</div></section>

<div class="wrap"><div class="siaga" data-siaga data-lat="<?= e(setting('lat')) ?>" data-lng="<?= e(setting('lng')) ?>" hidden></div></div>



<section class="sec" id="layanan" aria-labelledby="judul-layanan"><div class="wrap">
  <div class="sec-head"><h2 id="judul-layanan">Semua urusan, satu tempat</h2><p>Pilih sesuai keperluan Bapak dan Ibu.</p></div>
  <div class="layanan-grid">
    <article class="layanan-item">
      <span class="ikon-bulat" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
      <h3>Surat online</h3>
      <p>Cek syarat, ajukan dari rumah, lalu ambil nomor antrean lewat QR di kantor desa.</p>
      <a class="btn btn-dark btn-sm" href="/data-desa?grup=Layanan+surat">Urus surat</a>
    </article>
    <article class="layanan-item jeruk">
      <span class="ikon-bulat" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 12v9H4v-9M2 7h20v5H2zM12 21V7M12 7H8a2.5 2.5 0 1 1 0-5c3 0 4 5 4 5zM12 7h4a2.5 2.5 0 1 0 0-5c-3 0-4 5-4 5z"/></svg></span>
      <h3>Pantau bansos</h3>
      <p>Lihat penyaluran per tahap, cek status dengan nama dan RT, laporkan bila salah sasaran.</p>
      <a class="btn btn-dark btn-sm" href="/data-desa?grup=Pantau+bansos">Pantau bantuan</a>
    </article>
    <article class="layanan-item air">
      <span class="ikon-bulat" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
      <h3>Aspirasi warga</h3>
      <p>Sampaikan usulan atau pengaduan, boleh tanpa nama. Pantau tindak lanjutnya dengan kode.</p>
      <a class="btn btn-dark btn-sm" href="/kontak#aspirasi">Kirim aspirasi</a>
    </article>
    <article class="layanan-item merah">
      <span class="ikon-bulat" aria-hidden="true"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg></span>
      <h3>Darurat</h3>
      <p>Nomor ambulans, posko desa, dan peta zona rawan banjir atau rob.</p>
      <a class="btn btn-dark btn-sm" href="/kontak#darurat">Lihat nomor</a>
    </article>
  </div>
</div></section>

<div class="sec"><div class="wrap">
  <div class="head-row"><h2>Destinasi di <?= e(str_replace('Desa ', '', setting('nama_desa'))) ?></h2><a href="/wisata">Semua destinasi</a></div>
  <div class="grid g4">
    <?php foreach ($wisata as $w): ?>
      <a class="card link-card" href="/wisata/<?= e($w['slug']) ?>">
        <div class="art"><img src="<?= e($w['foto'] ?: '/assets/img/sawah.svg') ?>" alt="<?= e($w['nama']) ?>" loading="lazy"></div>
        <div class="card-body">
          <span class="tag"><?= e($w['jenis']) ?></span>
          <h3 style="margin:4px 0 0"><?= e($w['nama']) ?></h3>
          <p><?= e($w['ringkas']) ?></p>
          <div class="card-foot"><span class="price"><?= rupiah($w['harga']) ?></span><span class="small muted">Kuota <?= (int) $w['kuota'] ?>/hari</span></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div></div>

<div class="sec tint"><div class="wrap cols-2">
  <div class="kolom-kiri">
  <aside class="wx" aria-live="polite" data-cuaca data-lat="<?= e(setting('lat')) ?>" data-lng="<?= e(setting('lng')) ?>" data-sub="Pusat desa">
    <h3>Prakiraan 7 hari</h3>
    <p class="wx-note">Memuat prakiraan cuaca...</p>
  </aside>
  <?php if ($kataPengunjung): ?>
  <section class="kata-pengunjung" aria-labelledby="judul-kata">
    <div class="head-row"><h2 id="judul-kata">Kata pengunjung</h2><a href="/wisata">Lihat destinasi</a></div>
    <?php foreach ($kataPengunjung as $k): ?>
      <figure class="kata-item">
        <blockquote><?= e(ringkas_teks($k['komentar'], 160)) ?></blockquote>
        <figcaption><span class="bintang" aria-hidden="true"><?= bintang((int) $k['bintang']) ?></span><span class="sr-only"><?= (int) $k['bintang'] ?> bintang, </span> <b><?= e($k['nama']) ?></b> tentang <a href="<?= e($k['url']) ?>"><?= e($k['tempat']) ?></a></figcaption>
      </figure>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>
  </div>
  <div>
    <div class="head-row"><h2>Agenda terdekat</h2><a href="/agenda">Semua agenda</a></div>
    <div class="agenda">
      <?php foreach ($agenda as $a): ?>
        <div class="ag">
          <div class="ag-date"><b><?= tgl($a['tanggal'], 'tanggal') ?></b><?= tgl($a['tanggal'], 'bulan') ?></div>
          <div><h3><?= e($a['judul']) ?></h3><p><?= e($a['tempat']) ?>, <?= e($a['jam']) ?></p><?php if ((int) $a['relawan_kuota'] > 0): ?><a class="tag tag-hijau" href="/agenda#agenda-<?= (int) $a['id'] ?>">Butuh relawan</a><?php endif; ?></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$agenda): ?><p class="muted">Belum ada agenda terdekat.</p><?php endif; ?>
    </div>
    <?php if ($darurat): ?>
    <div class="darurat-mini">
      <h3>Nomor darurat</h3>
      <?php foreach ($darurat as $dr): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $dr['nomor'])) ?>"><span><?= e($dr['nama']) ?></span><b><?= e($dr['nomor']) ?></b></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div></div>

<div class="sec"><div class="wrap">
  <div class="head-row"><h2>Produk warga</h2><a href="/umkm">Katalog UMKM</a></div>
  <div class="grid g3">
    <?php foreach ($umkm as $u): ?>
      <a class="card link-card" href="/umkm" style="flex-direction:row;align-items:center">
        <div class="art" style="width:96px;aspect-ratio:1;flex:none;margin:10px"><img src="<?= e($u['foto'] ?: '/assets/img/kirab.svg') ?>" alt="" loading="lazy"></div>
        <div class="card-body" style="padding:12px 16px 12px 4px">
          <b><?= e($u['produk']) ?></b>
          <span class="small muted"><?= e($u['nama_usaha']) ?></span>
          <span class="price"><?= rupiah($u['harga']) ?> <span class="small muted">/ <?= e($u['satuan']) ?></span></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</div></div>

<?php $d = ringkasan_dampak(); ?>
<div class="sec tint dampak-home"><div class="wrap">
  <div class="head-row"><h2>Dampak setiap kunjungan</h2><a href="/data-desa?grup=Dampak">Laporan dampak lengkap</a></div>
  <div class="dampak-utama">
    <a class="dampak-besar" href="/data-desa?grup=Dampak"><span>Emisi yang dihemat pengunjung</span><b><?= angka(round($d['kg_hemat'])) ?> kg CO2e</b><small>dibanding semua datang dengan mobil pribadi</small></a>
    <a class="dampak-besar" href="/umkm"><span>Belanja Tiket Hijau ke UMKM</span><b><?= rupiah($d['nilai_umkm']) ?></b><small><?= $d['kupon_dipakai'] ?> kupon ditukar pengunjung berskor hijau</small></a>
  </div>
  <div class="stat-row terbuka">
    <?php if ($apb && $apb['a'] > 0): ?><a class="stat" href="/data-desa?grup=Transparansi"><span>Belanja desa <?= $tahunApb ?> terealisasi</span><b><?= persen_id((float) $apb['r'], (float) $apb['a']) ?>%</b><small>dari <?= rupiah_ringkas((float) $apb['a']) ?></small></a><?php endif; ?>
    <a class="stat" href="/data-desa?grup=Pembangunan"><span>Proyek pembangunan</span><b><?= $proyekBerjalan ?> berjalan</b><small><?= $proyekSelesai ?> sudah selesai</small></a>
    <?php if ($kgSampah > 0): ?><a class="stat" href="/data-desa?grup=Bank+sampah"><span>Sampah dikelola bank sampah</span><b><?= angka(round($kgSampah / 1000, 1)) ?> ton</b><small>dalam 6 bulan terakhir</small></a><?php endif; ?>
    <a class="stat" href="/kontak#aspirasi"><span>Aspirasi warga ditindaklanjuti</span><b><?= $aspSelesai ?> dari <?= $aspTotal ?></b><small>kirim aspirasimu</small></a>
  </div>
  <?php $sdgHome = array_keys(kontribusi_sdg($d)); ?>
  <div class="sdg-strip <?= $gotongRoyong ? '' : 'tunggal' ?>">
    <?php if ($sdgHome): ?>
    <div class="sdg-ringkas">
      <h3>Bergerak untuk <?= count($sdgHome) ?> tujuan SDGs</h3>
      <p class="small muted">Setiap fitur di website ini terhubung ke Tujuan Pembangunan Berkelanjutan. Buktinya dihitung dari data desa.</p>
      <ul class="sdg-baris" aria-label="Tujuan SDGs yang digerakkan desa">
        <?php foreach ($sdgHome as $n): ?><li><?= sdg_bulat($n) ?><span class="sr-only"><?= e(SDG[$n][0]) ?></span></li><?php endforeach; ?>
      </ul>
      <a class="btn btn-ghost btn-sm" href="/data-desa?grup=Dampak#sdgs">Lihat bukti per tujuan</a>
    </div>
    <?php endif; ?>
    <?php if ($gotongRoyong): $kuotaG = (int) $gotongRoyong['relawan_kuota']; $ikutG = (int) $gotongRoyong['ikut']; ?>
    <div class="gotong-mini">
      <h3>Ayo gotong royong</h3>
      <p style="margin:0"><b><?= e($gotongRoyong['judul']) ?></b></p>
      <p class="small muted" style="margin:2px 0 10px"><?= tgl($gotongRoyong['tanggal'], 'hari') ?>, <?= e($gotongRoyong['jam']) ?>, <?= e($gotongRoyong['tempat']) ?></p>
      <div class="bar-track" role="img" aria-label="<?= $ikutG ?> dari <?= $kuotaG ?> relawan"><div class="bar-fill" style="width:<?= min(100, round($ikutG / max(1, $kuotaG) * 100)) ?>%"></div></div>
      <p class="small" style="margin:6px 0 12px"><b><?= $ikutG ?> dari <?= $kuotaG ?></b> relawan sudah ikut. Warga dan pengunjung sama-sama boleh bergabung.</p>
      <a class="btn btn-main btn-sm" href="/agenda#agenda-<?= (int) $gotongRoyong['id'] ?>">Saya ikut</a>
    </div>
    <?php endif; ?>
  </div>
</div></div>

<div class="sec"><div class="wrap">
  <div class="head-row"><h2>Kabar desa</h2><a href="/agenda">Semua berita</a></div>
  <div class="grid g3">
    <?php foreach ($berita as $b): ?>
      <article class="card">
        <?php if ($b['foto']): ?><div class="art"><img src="<?= e($b['foto']) ?>" alt="" loading="lazy"></div><?php endif; ?>
        <div class="card-body">
          <span class="small muted"><?= tgl($b['tanggal']) ?></span>
          <h3 style="margin:2px 0 4px"><?= e($b['judul']) ?></h3>
          <p><?= e(ringkas_teks($b['isi'])) ?></p>
          <a class="small" href="/agenda#berita-<?= (int) $b['id'] ?>">Baca selengkapnya<span class="sr-only">: <?= e($b['judul']) ?></span></a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div></div>
<?php
$scriptHalaman = <<<'JS'
<script>
// Cari cepat di beranda: memakai Asisten Desa yang sama (aturan kata kunci + data CMS, tanpa AI generatif)
(function () {
  const form = document.getElementById('cariForm'), input = document.getElementById('cariCepat'), hasil = document.getElementById('cariHasil');
  if (!form) return;
  const cari = teks => {
    if (!teks.trim()) return;
    hasil.hidden = false; hasil.textContent = 'Mencari...';
    fetch('/asisten', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'pesan=' + encodeURIComponent(teks) })
      .then(r => r.json()).then(d => {
        hasil.textContent = d.teks;
        if (d.tautan && d.tautan.length) {
          const aksi = document.createElement('div'); aksi.className = 'actions';
          d.tautan.slice(0, 2).forEach((t, i) => { const a = document.createElement('a'); a.href = t.url; a.textContent = t.label; a.className = 'btn btn-sm ' + (i ? 'btn-ghost' : 'btn-main'); aksi.appendChild(a); });
          hasil.appendChild(aksi);
        }
      }).catch(() => { hasil.textContent = 'Pencarian sedang tidak tersambung. Coba lagi, atau buka menu Informasi Desa.'; });
  };
  form.addEventListener('submit', e => { e.preventDefault(); cari(input.value); });
  document.querySelectorAll('[data-cari]').forEach(b => b.addEventListener('click', () => { input.value = b.dataset.cari; cari(b.dataset.cari); }));
})();
</script>
JS;
require __DIR__ . '/../inc/footer.php';
?>
