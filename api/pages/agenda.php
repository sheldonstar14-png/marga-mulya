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

// ---------- Daftar relawan gotong royong ----------
// Warga atau pengunjung menekan "Saya ikut" pada agenda yang membuka relawan (relawan_kuota > 0).
// Cukup nama dan WhatsApp supaya panitia bisa mengabari. Satu nomor hanya sekali per kegiatan.
$errorRelawan = [];
$isiRelawan = ['agenda' => 0, 'nama' => '', 'wa' => '', 'asal' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'relawan') {
    foreach ($isiRelawan as $k => $_) $isiRelawan[$k] = trim((string) ($_POST[$k] ?? ''));
    $ag = q1('SELECT a.*, (SELECT COUNT(*) FROM relawan r WHERE r.agenda_id = a.id) AS ikut FROM agenda a WHERE a.id = ? AND a.relawan_kuota > 0 AND a.tanggal >= ?', [(int) $isiRelawan['agenda'], date('Y-m-d')]);
    $waR = preg_replace('/\D/', '', $isiRelawan['wa']);
    if (!empty($_POST['website']) || !$ag) $errorRelawan[] = 'Pendaftaran untuk kegiatan ini sudah ditutup.';
    elseif ((int) $ag['ikut'] >= (int) $ag['relawan_kuota']) $errorRelawan[] = 'Kuota relawan sudah penuh. Terima kasih atas niat baiknya, kamu tetap boleh datang membantu.';
    if (mb_strlen($isiRelawan['nama']) < 2 || mb_strlen($isiRelawan['nama']) > 60) $errorRelawan[] = 'Isi nama 2 sampai 60 huruf (boleh nama kelompok).';
    if (!preg_match('/^(0|62)8\d{7,11}$/', $waR)) $errorRelawan[] = 'Nomor WhatsApp belum benar. Contoh: 081234567890.';
    if (mb_strlen($isiRelawan['asal']) > 60) $errorRelawan[] = 'Asal maksimal 60 huruf.';
    if (!$errorRelawan && q1('SELECT id FROM relawan WHERE agenda_id = ? AND wa = ?', [$ag['id'], $waR])) $errorRelawan[] = 'Nomor ini sudah terdaftar untuk kegiatan ini. Sampai jumpa di lokasi!';
    if (!$errorRelawan) {
        q('INSERT INTO relawan (agenda_id, agenda_judul, nama, wa, asal) VALUES (?, ?, ?, ?, ?)', [$ag['id'], $ag['judul'], $isiRelawan['nama'], $waR, mb_substr($isiRelawan['asal'], 0, 60)]);
        redirect('/agenda?ikut=' . (int) $ag['id'] . '#agenda-' . (int) $ag['id']);
    }
}
$ikutBaru = (int) ($_GET['ikut'] ?? 0);

try {
    $agenda = q('SELECT a.*, (SELECT COUNT(*) FROM relawan r WHERE r.agenda_id = a.id) AS ikut FROM agenda a WHERE a.tanggal >= ? ORDER BY a.tanggal', [date('Y-m-d')]);
} catch (Throwable $e) { // migrasi 12k belum dijalankan
    $agenda = q('SELECT *, 0 AS relawan_kuota, 0 AS ikut FROM agenda WHERE tanggal >= ? ORDER BY tanggal', [date('Y-m-d')]);
}
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
        <div class="ag" id="agenda-<?= (int) $a['id'] ?>">
          <div class="ag-date"><b><?= tgl($a['tanggal'], 'tanggal') ?></b><?= tgl($a['tanggal'], 'bulan') ?></div>
          <div><h3><?= e($a['judul']) ?></h3><p><?= e($a['tempat']) ?>, <?= e($a['jam']) ?></p><p><?= e($a['isi']) ?></p><a class="small" href="/agenda?ics=<?= (int) $a['id'] ?>">Tambah ke kalender HP</a>
            <?php if ((int) $a['relawan_kuota'] > 0):
              $kuotaR = (int) $a['relawan_kuota']; $ikutR = (int) $a['ikut']; $penuh = $ikutR >= $kuotaR;
              $formIni = (int) $isiRelawan['agenda'] === (int) $a['id']; ?>
              <div class="relawan-kotak">
                <p class="relawan-judul"><b>Gotong royong:</b> <?= $ikutR ?> dari <?= $kuotaR ?> relawan sudah ikut</p>
                <div class="bar-track" role="img" aria-label="<?= $ikutR ?> dari <?= $kuotaR ?> relawan"><div class="bar-fill" style="width:<?= min(100, round($ikutR / $kuotaR * 100)) ?>%"></div></div>
                <?php if ($ikutBaru === (int) $a['id']): ?>
                  <div class="alert alert-ok" role="status" style="margin:10px 0 0">Terima kasih sudah ikut! Panitia akan mengabari lewat WhatsApp sehari sebelum kegiatan. Jangan lupa <a href="/agenda?ics=<?= (int) $a['id'] ?>">simpan ke kalender HP</a>.</div>
                <?php elseif ($penuh): ?>
                  <p class="small muted" style="margin:8px 0 0">Kuota relawan sudah penuh. Kamu tetap boleh datang membantu.</p>
                <?php else: ?>
                  <details class="relawan-form" <?= $formIni && $errorRelawan ? 'open' : '' ?>>
                    <summary class="btn btn-main btn-sm">Saya ikut</summary>
                    <form method="post" action="/agenda#agenda-<?= (int) $a['id'] ?>">
                      <?php if ($formIni && $errorRelawan): ?><div class="alert alert-err" role="alert"><?= e(implode(' ', $errorRelawan)) ?></div><?php endif; ?>
                      <input type="hidden" name="aksi" value="relawan"><input type="hidden" name="agenda" value="<?= (int) $a['id'] ?>">
                      <div class="hp" aria-hidden="true"><label>Jangan diisi<input name="website" tabindex="-1" autocomplete="off"></label></div>
                      <div class="grid g2" style="gap:12px">
                        <div class="field"><label for="rn-<?= (int) $a['id'] ?>">Nama atau nama kelompok</label><input id="rn-<?= (int) $a['id'] ?>" name="nama" required maxlength="60" value="<?= $formIni ? e($isiRelawan['nama']) : '' ?>" autocomplete="name"></div>
                        <div class="field"><label for="rw-<?= (int) $a['id'] ?>">Nomor WhatsApp</label><input id="rw-<?= (int) $a['id'] ?>" name="wa" required inputmode="tel" maxlength="20" placeholder="0812..." value="<?= $formIni ? e($isiRelawan['wa']) : '' ?>" autocomplete="tel"></div>
                      </div>
                      <div class="field"><label for="ra-<?= (int) $a['id'] ?>">Asal (opsional)</label><input id="ra-<?= (int) $a['id'] ?>" name="asal" maxlength="60" placeholder="Contoh: RT 03, Karang Taruna, atau pengunjung" value="<?= $formIni ? e($isiRelawan['asal']) : '' ?>"></div>
                      <button class="btn btn-dark btn-sm" type="submit">Daftar jadi relawan</button>
                      <p class="small muted" style="margin:8px 0 0">Nomor hanya dipakai panitia untuk mengabari kegiatan ini, lalu dihapus 30 hari setelah kegiatan.</p>
                    </form>
                  </details>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$agenda): ?><p class="muted">Belum ada agenda yang akan datang.</p><?php endif; ?>
    </div>
  </div>
  <div>
    <h2>Berita dan pengumuman</h2>
    <div class="grid">
      <?php foreach ($berita as $b): ?>
        <article class="card" id="berita-<?= (int) $b['id'] ?>">
          <?php if ($b['foto']): ?><div class="art"><img src="<?= e($b['foto']) ?>" alt="" loading="lazy"></div><?php endif; ?>
          <div class="card-body">
            <span class="small muted"><?= tgl($b['tanggal']) ?></span>
            <h3 style="margin:2px 0 4px"><?= e($b['judul']) ?></h3>
            <?php $paragraf = preg_split('/\n\s*\n/', trim($b['isi'])); ?>
            <p><?= nl2br(e($paragraf[0])) ?></p>
            <?php if (count($paragraf) > 1): ?>
              <details class="berita-lanjut"><summary>Baca selengkapnya<span class="sr-only">: <?= e($b['judul']) ?></span></summary>
                <?php foreach (array_slice($paragraf, 1) as $pr): ?><p><?= nl2br(e($pr)) ?></p><?php endforeach; ?>
              </details>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$berita): ?><p class="muted">Belum ada berita.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
