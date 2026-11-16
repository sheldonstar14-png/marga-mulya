<?php
// =========================================================
// DASHBOARD CMS
// =========================================================
$hariIni = date('Y-m-d');
$lihatReservasi = boleh($admin, 'reservasi');
$lihatPesan = boleh($admin, 'pesan');
$sepekan = date('Y-m-d', strtotime('+7 days'));

$menunggu = !$lihatReservasi ? [] : q("SELECT r.kode, r.nama, r.tanggal, r.jumlah, w.nama AS wisata FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.status = 'Menunggu' ORDER BY r.tanggal LIMIT 5");
$pengunjungSepekan = (int) q1("SELECT COALESCE(SUM(jumlah), 0) AS n FROM reservasi WHERE status IN ('Menunggu', 'Dikonfirmasi') AND tanggal BETWEEN ? AND ?", [$hariIni, $sepekan])['n'];
$jumlahMenunggu = (int) q1("SELECT COUNT(*) AS n FROM reservasi WHERE status = 'Menunggu'")['n'];
$pesanBaru = (int) q1('SELECT COUNT(*) AS n FROM pesan WHERE dibaca = 0')['n'];
$jumlahUmkm = (int) q1('SELECT COUNT(*) AS n FROM umkm')['n'];
$pesanTerbaru = $lihatPesan ? q('SELECT nama, topik, isi FROM pesan ORDER BY id DESC LIMIT 3') : [];

// Pengunjung per bulan (6 bulan terakhir), dari reservasi yang dikonfirmasi atau selesai
$bulanNama = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$perBulan = [];
for ($i = 5; $i >= 0; $i--) {
    $perBulan[date('Y-m', strtotime("first day of -$i month"))] = 0;
}
$awal = array_key_first($perBulan) . '-01';
foreach (q("SELECT tanggal, jumlah FROM reservasi WHERE status IN ('Dikonfirmasi', 'Selesai') AND tanggal >= ?", [$awal]) as $r) {
    $kunci = substr($r['tanggal'], 0, 7);
    if (isset($perBulan[$kunci])) $perBulan[$kunci] += (int) $r['jumlah'];
}
$maks = max(1, max($perBulan));

// Sisa kuota 7 hari ke depan per destinasi
$wisata = q('SELECT id, nama, kuota FROM wisata WHERE aktif = 1 ORDER BY id');
$tujuhHari = array_map(fn($i) => date('Y-m-d', strtotime("+$i day")), range(1, 7));
$terisi = [];
foreach (q("SELECT wisata_id, tanggal, SUM(jumlah) AS n FROM reservasi WHERE status <> 'Dibatalkan' AND tanggal BETWEEN ? AND ? GROUP BY wisata_id, tanggal", [$tujuhHari[0], end($tujuhHari)]) as $r) {
    $terisi[$r['wisata_id']][$r['tanggal']] = (int) $r['n'];
}

// Jejak karbon pengunjung (Fase 8): dari reservasi yang tidak dibatalkan dan punya data perjalanan
$emisi = q1("SELECT COALESCE(SUM(emisi_kg), 0) AS kg, COALESCE(SUM(jumlah), 0) AS orang,
    COALESCE(SUM(CASE WHEN kendaraan IN ('Bus atau elf rombongan', 'Angkutan umum', 'Kereta lalu ojek', 'Sepeda atau jalan kaki') THEN jumlah ELSE 0 END), 0) AS hijau
    FROM reservasi WHERE status <> 'Dibatalkan' AND kendaraan <> ''");
$aspirasiBaru = boleh($admin, 'aspirasi') ? (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status = 'Diterima'")['n'] : null;

$judulAdmin = 'Dashboard';
$menuAdmin = 'dashboard';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Halo, <?= e(explode(',', $admin['nama'])[0]) ?></h2>
<?php if (!$lihatReservasi): // akun petugas tanpa akses reservasi: tampilkan menu kerjanya saja ?>
  <p class="muted">Ini menu yang bisa kamu kelola:</p>
  <div class="stat-row">
    <?php foreach (akses_admin($admin) as $kunciMenu): if (!isset(MENU_PETUGAS[$kunciMenu])) continue; ?>
      <a class="stat" style="text-decoration:none;color:inherit" href="/admin/<?= $kunciMenu ?>"><span>Buka menu</span><b style="font-size:1.4rem"><?= e(MENU_PETUGAS[$kunciMenu]) ?></b></a>
    <?php endforeach; ?>
  </div>
  <?php if ($lihatPesan): ?>
    <div class="panel"><h3>Pesan terbaru</h3>
      <?php foreach ($pesanTerbaru as $p): ?><p style="margin:0 0 10px"><b><?= e($p['nama']) ?></b> <span class="tag"><?= e($p['topik']) ?></span><br><span class="small muted"><?= e(mb_strimwidth($p['isi'], 0, 120, '...')) ?></span></p><?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php else: ?>
<div class="stat-row">
  <div class="stat"><span>Reservasi menunggu</span><b><?= $jumlahMenunggu ?></b></div>
  <div class="stat"><span>Pengunjung 7 hari ke depan</span><b><?= $pengunjungSepekan ?></b></div>
  <div class="stat"><span>Pesan belum dibaca</span><b><?= $pesanBaru ?></b></div>
  <div class="stat"><span>Produk UMKM tampil</span><b><?= $jumlahUmkm ?></b></div>
</div>
<div class="stat-row">
  <div class="stat"><span>Rata-rata jejak karbon per pengunjung</span><b><?= $emisi['orang'] ? angka(round($emisi['kg'] / $emisi['orang'], 1)) : 0 ?> kg</b><small class="muted">CO2e pulang pergi</small></div>
  <div class="stat"><span>Datang dengan transport bersama atau tanpa emisi</span><b><?= persen((float) $emisi['hijau'], (float) $emisi['orang']) ?>%</b><small class="muted">bus, angkutan umum, kereta, sepeda</small></div>
  <?php if ($aspirasiBaru !== null): ?><a class="stat" style="text-decoration:none;color:inherit" href="/admin/aspirasi?status=Diterima"><span>Aspirasi warga belum ditanggapi</span><b><?= $aspirasiBaru ?></b><small class="muted">buka menu Aspirasi</small></a><?php endif; ?>
</div>

<div class="grid g2">
  <div class="panel">
    <h3>Perlu ditindaklanjuti</h3>
    <?php foreach ($menunggu as $r): ?>
      <p style="margin:0 0 10px"><b><?= e($r['kode']) ?></b> <?= e($r['nama']) ?><br>
      <span class="small muted"><?= e($r['wisata']) ?>, <?= tgl($r['tanggal'], 'pendek') ?>, <?= (int) $r['jumlah'] ?> orang</span></p>
    <?php endforeach; ?>
    <?php if (!$menunggu): ?><p class="muted">Semua reservasi sudah ditangani.</p><?php endif; ?>
    <a class="btn btn-dark btn-sm" href="/admin/reservasi?status=Menunggu">Buka reservasi</a>
  </div>
  <div class="panel">
    <h3>Pengunjung per bulan</h3>
    <div class="vbars">
      <?php $k = 0; foreach ($perBulan as $bulan => $n): $k++; ?>
        <div class="vbar <?= $k === count($perBulan) ? 'now' : '' ?>"><b><?= $n ?></b><div style="height:<?= round($n / $maks * 130) ?>px"></div><span><?= $bulanNama[(int) substr($bulan, 5)] ?></span></div>
      <?php endforeach; ?>
    </div>
    <p class="hint">Dari reservasi berstatus Dikonfirmasi atau Selesai. Bulan berjalan diberi arsiran.</p>
  </div>
</div>

<div class="panel" style="margin-top:20px">
  <h3>Sisa kuota 7 hari ke depan</h3>
  <div class="table-wrap">
    <table>
      <tr><th>Destinasi</th><?php foreach ($tujuhHari as $t): ?><th style="text-align:center"><?= tgl($t, 'pendek') ?></th><?php endforeach; ?></tr>
      <?php foreach ($wisata as $w): ?>
        <tr><td><?= e($w['nama']) ?></td>
          <?php foreach ($tujuhHari as $t):
            $pakai = $terisi[$w['id']][$t] ?? 0;
            $rasio = $pakai / max(1, $w['kuota']); ?>
            <td><div class="qcell <?= $rasio >= .9 ? 'q-full' : ($rasio >= .5 ? 'q-mid' : 'q-ok') ?>"><?= $w['kuota'] - $pakai ?></div></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <p class="hint">Hijau: masih longgar. Kuning: terisi setengah. Merah: hampir penuh.</p>
</div>

<?php if ($lihatPesan): ?>
<div class="panel" style="margin-top:20px">
  <h3>Pesan terbaru</h3>
  <?php foreach ($pesanTerbaru as $p): ?>
    <p style="margin:0 0 10px"><b><?= e($p['nama']) ?></b> <span class="tag"><?= e($p['topik']) ?></span><br><span class="small muted"><?= e(mb_strimwidth($p['isi'], 0, 120, '...')) ?></span></p>
  <?php endforeach; ?>
  <a class="btn btn-ghost btn-sm" href="/admin/pesan">Buka pesan masuk</a>
</div>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
