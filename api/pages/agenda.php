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
$isiRelawan = ['agenda' => 0, 'nama' => '', 'wa' => '', 'asal' => '', 'peran' => ''];
$PERAN_RELAWAN = ['Tenaga lapangan', 'Dokumentasi foto', 'Konsumsi', 'Membawa perlengkapan', 'Pendamping anak-anak'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'relawan') {
    foreach ($isiRelawan as $k => $_) $isiRelawan[$k] = trim((string) ($_POST[$k] ?? ''));
    $ag = q1('SELECT a.*, (SELECT COUNT(*) FROM relawan r WHERE r.agenda_id = a.id) AS ikut FROM agenda a WHERE a.id = ? AND a.relawan_kuota > 0 AND a.tanggal >= ?', [(int) $isiRelawan['agenda'], date('Y-m-d')]);
    $waR = preg_replace('/\D/', '', $isiRelawan['wa']);
    if (!empty($_POST['website']) || !$ag) $errorRelawan[] = 'Pendaftaran untuk kegiatan ini sudah ditutup.';
    elseif ((int) $ag['ikut'] >= (int) $ag['relawan_kuota']) $errorRelawan[] = 'Kuota relawan sudah penuh. Terima kasih atas niat baiknya, kamu tetap boleh datang membantu.';
    if (mb_strlen($isiRelawan['nama']) < 2 || mb_strlen($isiRelawan['nama']) > 60) $errorRelawan[] = 'Isi nama 2 sampai 60 huruf (boleh nama kelompok).';
    if (!preg_match('/^(0|62)8\d{7,11}$/', $waR)) $errorRelawan[] = 'Nomor WhatsApp belum benar. Contoh: 081234567890.';
    if (mb_strlen($isiRelawan['asal']) > 60) $errorRelawan[] = 'Asal maksimal 60 huruf.';
    if ($isiRelawan['peran'] !== '' && !in_array($isiRelawan['peran'], $PERAN_RELAWAN, true)) $isiRelawan['peran'] = '';
    if (!$errorRelawan && q1('SELECT id FROM relawan WHERE agenda_id = ? AND wa = ?', [$ag['id'], $waR])) $errorRelawan[] = 'Nomor ini sudah terdaftar untuk kegiatan ini. Sampai jumpa di lokasi!';
    if (!$errorRelawan) {
        try {
            q('INSERT INTO relawan (agenda_id, agenda_judul, nama, wa, asal, peran) VALUES (?, ?, ?, ?, ?, ?)', [$ag['id'], $ag['judul'], $isiRelawan['nama'], $waR, mb_substr($isiRelawan['asal'], 0, 60), $isiRelawan['peran']]);
        } catch (Throwable $e) { // migrasi 12s belum dijalankan
            q('INSERT INTO relawan (agenda_id, agenda_judul, nama, wa, asal) VALUES (?, ?, ?, ?, ?)', [$ag['id'], $ag['judul'], $isiRelawan['nama'], $waR, mb_substr($isiRelawan['asal'], 0, 60)]);
        }
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

$katBerita = array_values(array_unique(array_filter(array_map(fn($b) => $b['kategori'] ?? '', $berita))));
$judul = 'Kabar Desa - ' . setting('nama_desa');
$menu  = 'agenda';
require __DIR__ . '/../inc/header.php';
?>
<div class="wrap page-title">
  <h1>Kabar desa</h1>
  <p>Berita dan pengumuman dari pemerintah desa, agenda kegiatan warga, dan ajakan menjadi relawan gotong royong.</p>
</div>
<div class="wrap sec kabar-dua" style="padding-top:28px">
  <div class="kolom-agenda">
    <h2>Agenda kegiatan</h2>
    <details class="cara-baca" style="margin-bottom:14px"><summary>Cara ikut jadi relawan</summary>
      <ol class="small" style="margin:8px 0 0;padding-left:1.2em"><li>Pilih kegiatan yang punya tombol <b>Saya ikut</b>.</li><li>Isi nama dan WhatsApp, pilih bantuan yang bisa kamu berikan.</li><li>Panitia mengabari lewat WhatsApp sehari sebelum kegiatan. Simpan jadwalnya ke kalender HP.</li><li>Datang tepat waktu dengan perlengkapan yang disebutkan. Pengunjung dari luar desa juga boleh ikut.</li></ol>
    </details>
    <div class="agd-daftar">
      <?php $hariPendek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']; foreach ($agenda as $a): [$jdlAg, $ilusAg] = pisah_ilustrasi($a['judul']); $tA = strtotime($a['tanggal']); ?>
        <article class="agd" id="agenda-<?= (int) $a['id'] ?>">
          <div class="agd-tgl" aria-hidden="true"><small><?= strtoupper(tgl($a['tanggal'], 'bulan')) ?></small><b><?= tgl($a['tanggal'], 'tanggal') ?></b><span><?= $hariPendek[(int) date('w', $tA)] ?></span></div>
          <div class="agd-isi">
            <h3><?= e($jdlAg) ?></h3>
            <ul class="agd-meta">
              <li><span class="sr-only">Tanggal: </span><?= tgl($a['tanggal'], 'hari') ?></li>
              <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e($a['jam']) ?></li>
              <li><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="10" r="3"/><path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/></svg><?= e($a['tempat']) ?></li>
            </ul>
            <p class="agd-teks"><?= e($a['isi']) ?></p>
            <p class="agd-aksi"><a href="/agenda?ics=<?= (int) $a['id'] ?>">Tambah ke kalender HP</a><?= tanda_ilustrasi($ilusAg) ?></p>
            <?php if ((int) $a['relawan_kuota'] > 0):
              $kuotaR = (int) $a['relawan_kuota']; $ikutR = (int) $a['ikut']; $penuh = $ikutR >= $kuotaR;
              $formIni = (int) $isiRelawan['agenda'] === (int) $a['id']; ?>
              <div class="relawan-kotak">
                <p class="relawan-judul"><b>Gotong royong:</b> <?= $ikutR ?> dari <?= $kuotaR ?> relawan sudah ikut</p>
                <?php if (!empty($a['perlengkapan'])): ?><p class="small" style="margin:4px 0 8px"><b>Bawa:</b> <?= e($a['perlengkapan']) ?></p><?php endif; ?>
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
                      <div class="field"><label for="rp-<?= (int) $a['id'] ?>">Bisa membantu sebagai (opsional)</label><select id="rp-<?= (int) $a['id'] ?>" name="peran"><option value="">Apa saja yang dibutuhkan</option><?php foreach ($PERAN_RELAWAN as $pr): ?><option <?= $formIni && $isiRelawan['peran'] === $pr ? 'selected' : '' ?>><?= e($pr) ?></option><?php endforeach; ?></select></div>
                      <div class="field"><label for="ra-<?= (int) $a['id'] ?>">Asal (opsional)</label><input id="ra-<?= (int) $a['id'] ?>" name="asal" maxlength="60" placeholder="Contoh: RT 03, Karang Taruna, atau pengunjung" value="<?= $formIni ? e($isiRelawan['asal']) : '' ?>"></div>
                      <button class="btn btn-dark btn-sm" type="submit">Daftar jadi relawan</button>
                      <p class="small muted" style="margin:8px 0 0">Nomor hanya dipakai panitia untuk mengabari kegiatan ini, lalu dihapus 30 hari setelah kegiatan.</p>
                    </form>
                  </details>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$agenda): ?><p class="muted">Belum ada agenda yang akan datang.</p><?php endif; ?>
    </div>
  </div>
  <div class="kolom-berita">
    <h2 id="berita">Berita dan pengumuman</h2>
    <?php if (count($katBerita) > 1): ?>
    <nav class="chips chips-geser" aria-label="Saring kategori berita" id="saringBerita">
      <button type="button" class="chip on" data-kat="">Semua</button>
      <?php foreach ($katBerita as $kb): ?><button type="button" class="chip" data-kat="<?= e($kb) ?>"><?= e($kb) ?></button><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <div class="grid">
      <?php foreach ($berita as $b): [$jdlB, $ilusB] = pisah_ilustrasi($b['judul']); $paragraf = paragraf_teks($b['isi']); $menit = max(1, (int) round(str_word_count(strip_tags($b['isi'])) / 180)); ?>
        <article class="card berita-kartu" id="berita-<?= (int) $b['id'] ?>" data-kat="<?= e($b['kategori'] ?? '') ?>">
          <?php if ($b['foto']): ?><figure class="art berita-foto" style="margin:0;position:relative"><img src="<?= e($b['foto']) ?>" alt="" loading="lazy" onerror="this.onerror=null;this.src='/assets/img/panorama.svg'"><?= tanda_ilustrasi($ilusB) ?><?php $sf = sumber_foto((string) $b['foto'], (string) ($b['sumber_foto'] ?? '')); if ($sf): ?><figcaption class="foto-sumber">Foto: <?= e($sf) ?></figcaption><?php endif; ?></figure><?php endif; ?>
          <div class="card-body">
            <p class="berita-meta"><?php if (!empty($b['kategori'])): ?><b class="berita-kat"><?= e($b['kategori']) ?></b><?php endif; ?><span><?= tgl($b['tanggal']) ?></span><span><?= $menit ?> menit baca</span><?= !$b['foto'] ? tanda_ilustrasi($ilusB) : '' ?></p>
            <h3 class="berita-judul"><?= e($jdlB) ?></h3>
            <p><?= nl2br(e($paragraf[0] ?? '')) ?></p>
            <?php if (count($paragraf) > 1): ?>
              <details class="berita-lanjut"><summary>Baca selengkapnya<span class="sr-only">: <?= e($jdlB) ?></span></summary>
                <?php foreach (array_slice($paragraf, 1) as $pr): ?><p><?= nl2br(e($pr)) ?></p><?php endforeach; ?>
                <p class="small"><a href="<?= e(wa_link('', $jdlB . ' - ' . url_dasar() . '/agenda#berita-' . (int) $b['id'])) ?>" target="_blank" rel="noopener">Bagikan lewat WhatsApp</a></p>
              </details>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$berita): ?><p class="muted">Belum ada berita.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php
$scriptHalaman = '<script>(function(){var n=document.getElementById("saringBerita");if(!n)return;n.addEventListener("click",function(e){var b=e.target.closest("[data-kat]");if(!b)return;n.querySelectorAll(".chip").forEach(function(c){c.classList.toggle("on",c===b)});document.querySelectorAll(".berita-kartu").forEach(function(k){k.hidden=b.dataset.kat!==""&&k.dataset.kat!==b.dataset.kat})})})();</script>';
require __DIR__ . '/../inc/footer.php'; ?>
