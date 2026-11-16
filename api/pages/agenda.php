<?php
// =========================================================
// HALAMAN AGENDA & BERITA
// =========================================================
// ---------- Unduh satu agenda sebagai file kalender (.ics), contoh /agenda?ics=3 ----------
// File .ics bisa dibuka Google Calendar, kalender iPhone, dan Outlook, lengkap dengan pengingat sehari sebelumnya.
if (isset($_GET['ics'])) {
    $a = q1('SELECT * FROM agenda WHERE id = ?', [(int) $_GET['ics']]);
    if (!$a) { http_response_code(404); echo 'Agenda tidak ditemukan.'; return; }
    $teksIcs = fn(string $t) => str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], $t);
    $tgl = str_replace('-', '', $a['tanggal']);
    $jam = jam_agenda($a['jam']);
    $waktu = $jam
        // Jam WIB diubah ke UTC (akhiran Z) supaya tepat di semua aplikasi kalender
        ? 'DTSTART:' . gmdate('Ymd\THis\Z', strtotime("{$a['tanggal']} " . substr($jam[0], 0, 2) . ':' . substr($jam[0], 2))) . "\r\n"
          . 'DTEND:' . gmdate('Ymd\THis\Z', strtotime("{$a['tanggal']} " . substr($jam[1], 0, 2) . ':' . substr($jam[1], 2)))
        : "DTSTART;VALUE=DATE:$tgl\r\nDTEND;VALUE=DATE:" . date('Ymd', strtotime($a['tanggal'] . ' +1 day'));
    $baris = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . setting('nama_desa') . '//Agenda//ID', 'CALSCALE:GREGORIAN',
        'BEGIN:VEVENT',
        'UID:agenda-' . (int) $a['id'] . '@' . ($_SERVER['HTTP_HOST'] ?? 'desa'),
        'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        $waktu,
        'SUMMARY:' . $teksIcs($a['judul']),
        'LOCATION:' . $teksIcs($a['tempat'] . ', ' . setting('nama_desa')),
        'DESCRIPTION:' . $teksIcs($a['isi'] . ' Info: ' . url_dasar() . '/agenda'),
        'BEGIN:VALARM', 'TRIGGER:-P1D', 'ACTION:DISPLAY', 'DESCRIPTION:' . $teksIcs('Besok: ' . $a['judul']), 'END:VALARM',
        'END:VEVENT', 'END:VCALENDAR',
    ];
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="agenda-' . (int) $a['id'] . '.ics"');
    echo implode("\r\n", $baris) . "\r\n";
    return;
}

$agenda = q('SELECT * FROM agenda WHERE tanggal >= ? ORDER BY tanggal', [date('Y-m-d')]);
$berita = q('SELECT * FROM berita ORDER BY tanggal DESC, id DESC');

$judul = 'Agenda & Berita - ' . setting('nama_desa');
$menu  = 'agenda';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Agenda dan berita</h1>
  <p>Kegiatan rutin warga dan kabar terbaru dari pemerintah desa.</p>
</div>
<div class="wrap sec grid g2" style="padding-top:16px">
  <div>
    <h2>Agenda kegiatan</h2>
    <div class="agenda">
      <?php foreach ($agenda as $a): ?>
        <div class="ag">
          <div class="ag-date"><b><?= tgl($a['tanggal'], 'tanggal') ?></b><?= tgl($a['tanggal'], 'bulan') ?></div>
          <div><h3><?= e($a['judul']) ?></h3><p><?= e($a['tempat']) ?>, <?= e($a['jam']) ?></p><p><?= e($a['isi']) ?></p><a class="small" href="/agenda?ics=<?= (int) $a['id'] ?>">Tambah ke kalender HP</a></div>
        </div>
      <?php endforeach; ?>
      <?php if (!$agenda): ?><p class="muted">Belum ada agenda yang akan datang.</p><?php endif; ?>
    </div>
  </div>
  <div>
    <h2>Berita dan pengumuman</h2>
    <div class="grid">
      <?php foreach ($berita as $b): ?>
        <article class="card">
          <?php if ($b['foto']): ?><div class="art"><img src="<?= e($b['foto']) ?>" alt="" loading="lazy"></div><?php endif; ?>
          <div class="card-body">
            <span class="small muted"><?= tgl($b['tanggal']) ?></span>
            <h3 style="margin:2px 0 4px"><?= e($b['judul']) ?></h3>
            <p><?= nl2br(e($b['isi'])) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$berita): ?><p class="muted">Belum ada berita.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
