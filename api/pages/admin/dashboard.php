<?php
// =========================================================
// DASHBOARD CMS (Fase 12s)
// Tugas hari ini di atas, lalu ringkasan dan daftar terbaru sesuai menu yang boleh dibuka akun ini.
// =========================================================
// Hapus otomatis nama dan kontak di data lama sesuai masa simpan (UU Pelindungan Data Pribadi)
$dataDibersihkan = bersihkan_data_lama();
$awalBulan = date('Y-m-01') . ' 00:00:00';
$lihatSurat = boleh($admin, 'surat');
$lihatAspirasi = boleh($admin, 'aspirasi');

$suratBulan = $lihatSurat ? (int) q1('SELECT COUNT(*) AS n FROM surat WHERE dibuat_pada >= ?', [$awalBulan])['n'] : 0;
$izinBulan = $lihatSurat ? (int) q1("SELECT COUNT(*) AS n FROM surat WHERE layanan_nama LIKE 'Izin kunjungan%' AND dibuat_pada >= ?", [$awalBulan])['n'] : 0;
$suratTerbaru = $lihatSurat ? q('SELECT kode, layanan_nama, status, dibuat_pada FROM surat ORDER BY id DESC LIMIT 5') : [];
$aspirasiTerbuka = $lihatAspirasi ? (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status IN ('Diterima', 'Diproses')")['n'] : 0;
$aspirasiTerbaru = $lihatAspirasi ? q('SELECT kode, kategori, status, isi FROM aspirasi ORDER BY id DESC LIMIT 5') : [];
$jumlahUmkm = (int) q1('SELECT COUNT(*) AS n FROM umkm')['n'];

$judulAdmin = 'Dashboard';
$menuAdmin = 'dashboard';
require __DIR__ . '/../../inc/admin-header.php';
?>
<h2>Halo, <?= e(explode(',', $admin['nama'])[0]) ?></h2>
<?php
// Tugas hari ini: semua hal yang menunggu tindakan, diambil dari angka di menu
$labelTugas = [
    'aspirasi' => ['aspirasi atau pertanyaan warga baru', '/admin/aspirasi?status=Diterima'],
    'surat' => ['pengajuan surat atau izin kunjungan baru', '/admin/surat'],
    'pengajuanumkm' => ['produk UMKM menunggu ditinjau', '/admin/pengajuanumkm'],
    'loker' => ['peluang kerja menunggu diperiksa', '/admin/loker?status=Menunggu'],
    'ulasanumkm' => ['ulasan UMKM menunggu diperiksa', '/admin/ulasanumkm'],
];
$tugas = array_filter($labelTugas, fn($k) => !empty($badge[$k]), ARRAY_FILTER_USE_KEY);
?>
<section class="tugas" aria-labelledby="judulTugas">
  <h3 id="judulTugas">Tugas hari ini</h3>
  <?php if ($tugas): ?>
    <ul>
      <?php foreach ($tugas as $k => [$teks, $url]): ?>
        <li><a href="<?= $url ?>"><b><?= (int) $badge[$k] ?></b><span><?= e($teks) ?></span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <p class="tugas-beres">Semua sudah ditangani. Terima kasih!</p>
  <?php endif; ?>
</section>

<h3 class="judul-ringkas">Ringkasan bulan ini</h3>
<div class="stat-mini">
  <?php if ($lihatSurat): ?>
    <div><b><?= $suratBulan ?></b><span>pengajuan surat</span></div>
    <div><b><?= $izinBulan ?></b><span>izin kunjungan, KKN, penelitian</span></div>
  <?php endif; ?>
  <?php if ($lihatAspirasi): ?><div><b><?= $aspirasiTerbuka ?></b><span>aspirasi masih terbuka</span></div><?php endif; ?>
  <div><b><?= $jumlahUmkm ?></b><span>produk UMKM tampil</span></div>
</div>

<div class="grid g2">
  <?php if ($lihatSurat): ?>
  <div class="panel">
    <h3>Surat terbaru</h3>
    <?php foreach ($suratTerbaru as $s): ?>
      <p style="margin:0 0 10px"><b><?= e($s['kode']) ?></b> <span class="tag"><?= e($s['status']) ?></span><br><span class="small muted"><?= e($s['layanan_nama']) ?>, <?= tgl(substr((string) $s['dibuat_pada'], 0, 10), 'pendek') ?></span></p>
    <?php endforeach; ?>
    <?php if (!$suratTerbaru): ?><p class="muted">Belum ada pengajuan surat.</p><?php endif; ?>
    <a class="btn btn-dark btn-sm" href="/admin/surat">Buka pengajuan surat</a>
  </div>
  <?php endif; ?>
  <?php if ($lihatAspirasi): ?>
  <div class="panel">
    <h3>Aspirasi dan pertanyaan terbaru</h3>
    <?php foreach ($aspirasiTerbaru as $a): ?>
      <p style="margin:0 0 10px"><b><?= e($a['kode']) ?></b> <span class="tag"><?= e($a['kategori']) ?></span><br><span class="small muted"><?= e(mb_strimwidth($a['isi'], 0, 110, '...')) ?></span></p>
    <?php endforeach; ?>
    <?php if (!$aspirasiTerbaru): ?><p class="muted">Belum ada aspirasi.</p><?php endif; ?>
    <a class="btn btn-ghost btn-sm" href="/admin/aspirasi">Buka aspirasi</a>
  </div>
  <?php endif; ?>
</div>

<h3 class="judul-ringkas" style="margin-top:22px">Menu yang bisa kamu kelola</h3>
<div class="stat-row">
  <?php foreach (akses_admin($admin) as $kunciMenu): if (!isset(MENU_PETUGAS[$kunciMenu])) continue; ?>
    <a class="stat" style="text-decoration:none;color:inherit" href="/admin/<?= $kunciMenu ?>"><span>Buka menu</span><b style="font-size:1.15rem"><?= e(MENU_PETUGAS[$kunciMenu]) ?></b></a>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../inc/admin-footer.php'; ?>
