<?php
// =========================================================
// EMAIL OTOMATIS lewat Gmail (SMTP), tanpa library tambahan.
// Butuh Environment Variables:
//   GMAIL_USER         = alamat Gmail desa, contoh wisatamargamulya@gmail.com
//   GMAIL_APP_PASSWORD = App Password 16 huruf dari akun Google
// Email tidak pernah membuat reservasi gagal: kalau pengiriman gagal,
// fungsi mengembalikan pesan error dan proses lain tetap jalan.
// =========================================================

// Kirim satu email HTML. Hasil: true kalau terkirim, atau teks penyebab gagal.
function kirim_email(string $ke, string $subjek, string $html): true|string
{
    $user = (string) getenv('GMAIL_USER');
    $pass = str_replace(' ', '', (string) getenv('GMAIL_APP_PASSWORD'));
    if ($user === '' || $pass === '') {
        return 'email belum diaktifkan (GMAIL_USER dan GMAIL_APP_PASSWORD belum diisi)';
    }
    if (!filter_var($ke, FILTER_VALIDATE_EMAIL)) {
        return 'alamat email penerima tidak valid';
    }

    $host = getenv('SMTP_HOST') ?: 'ssl://smtp.gmail.com';
    $port = (int) (getenv('SMTP_PORT') ?: 465);
    $s = @stream_socket_client("$host:$port", $errno, $errstr, 10);
    if (!$s) {
        return "tidak bisa terhubung ke server email ($errstr)";
    }
    stream_set_timeout($s, 15);

    // Baca balasan server (bisa beberapa baris), lalu cek kodenya
    $perintah = function (string $teks, string $kodeHarapan) use ($s): void {
        if ($teks !== '') {
            fwrite($s, $teks . "\r\n");
        }
        $balasan = '';
        while (($baris = fgets($s, 515)) !== false) {
            $balasan .= $baris;
            if (strlen($baris) < 4 || $baris[3] === ' ') break;
        }
        if (!str_starts_with($balasan, $kodeHarapan)) {
            throw new RuntimeException(trim($balasan) ?: 'server tidak membalas');
        }
    };

    try {
        $perintah('', '220');
        $perintah('EHLO marga-mulya', '250');
        $perintah('AUTH LOGIN', '334');
        $perintah(base64_encode($user), '334');
        $perintah(base64_encode($pass), '235');
        $perintah("MAIL FROM:<$user>", '250');
        $perintah("RCPT TO:<$ke>", '250');
        $perintah('DATA', '354');
        // Versi teks biasa untuk email yang sama: email yang hanya berisi HTML lebih sering dianggap spam
        $teks = html_entity_decode(strip_tags(preg_replace(['#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '#<(br|/p|/div|/h[1-6]|/tr|/li)[^>]*>#i'], ["$2 ($1)", "\n"], $html)), ENT_QUOTES, 'UTF-8');
        $teks = trim(preg_replace("/\n\s*\n+/", "\n\n", preg_replace('/[ \t]+/', ' ', $teks)));
        $batas = 'mm-' . bin2hex(random_bytes(8));
        $domain = substr(strrchr($user, '@'), 1) ?: 'gmail.com';
        $kepala = [
            'Date: ' . date('r'),
            'From: =?UTF-8?B?' . base64_encode(setting('nama_desa')) . "?= <$user>",
            "Reply-To: <$user>",
            "To: <$ke>",
            'Subject: =?UTF-8?B?' . base64_encode($subjek) . '?=',
            'Message-ID: <' . bin2hex(random_bytes(12)) . "@$domain>",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"$batas\"",
        ];
        $isi = "--$batas\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($teks))
             . "--$batas\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
             . "--$batas--\r\n";
        fwrite($s, implode("\r\n", $kepala) . "\r\n\r\n" . $isi . "\r\n.\r\n");
        $perintah('', '250');
        $perintah('QUIT', '221');
    } catch (Throwable $e) {
        fclose($s);
        return 'server email menolak: ' . $e->getMessage();
    }
    fclose($s);
    return true;
}

// Bungkus isi email dengan tampilan desa
function bungkus_email(string $judul, string $isiHtml): string
{
    $nama = e(setting('nama_desa'));
    return '<!doctype html><html><body style="margin:0;background:#F6F0E2;font-family:Arial,sans-serif;color:#0E1B24">'
        . '<div style="max-width:560px;margin:0 auto;padding:24px">'
        . '<div style="background:#0B3C5D;color:#F6F0E2;padding:18px 22px;border-radius:12px 12px 0 0;font-weight:bold;font-size:18px">' . $nama . '</div>'
        . '<div style="background:#ffffff;padding:22px;border-radius:0 0 12px 12px;border:1px solid #D9DECF;border-top:0">'
        . '<h2 style="margin:0 0 12px;font-size:20px">' . e($judul) . '</h2>' . $isiHtml . '</div>'
        . '<p style="font-size:12px;color:#475663;margin-top:14px">Email ini dikirim otomatis oleh website ' . $nama . '. '
        . 'Pertanyaan? Hubungi WhatsApp +' . e(setting('wa')) . '.</p></div></body></html>';
}

// Email ke pengunjung sesuai status reservasi. Hasil: true atau teks penyebab gagal.
function email_reservasi(string $kode): true|string
{
    $r = q1('SELECT r.*, w.nama AS nama_wisata, w.harga, w.gmaps_ulasan, w.kode_etik FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.kode = ?', [$kode]);
    if (!$r || $r['email'] === '') {
        return 'reservasi ini tidak punya alamat email';
    }
    $linkStatus = url_dasar() . '/reservasi?cek=' . urlencode($r['kode']);
    $tombol = fn(string $url, string $label) => '<p><a href="' . e($url) . '" style="display:inline-block;background:#F08A5D;color:#0E1B24;font-weight:bold;text-decoration:none;padding:12px 20px;border-radius:999px">' . e($label) . '</a></p>';
    $rincian = '<table style="width:100%;border-collapse:collapse;font-size:15px;margin:12px 0">'
        . '<tr><td style="padding:6px 0;color:#475663">Kode</td><td style="padding:6px 0"><b style="font-size:18px">' . e($r['kode']) . '</b></td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Destinasi</td><td>' . e($r['nama_wisata']) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Tanggal</td><td>' . tgl($r['tanggal'], 'hari') . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Jumlah</td><td>' . (int) $r['jumlah'] . ' orang</td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Bayar di lokasi</td><td>' . rupiah($r['harga'] * $r['jumlah']) . '</td></tr></table>';
    $halo = '<p>Halo ' . e($r['nama']) . ',</p>';

    switch ($r['status']) {
        case 'Menunggu':
            $subjek = "Reservasi {$r['kode']} diterima, menunggu konfirmasi";
            $etik = kode_etik($r);
            $isi = $halo . '<p>Terima kasih, reservasimu sudah kami terima. Pengelola akan mengonfirmasi paling lambat 1x24 jam, dan kabarnya akan dikirim lewat email ini.</p>'
                . $rincian . $tombol($linkStatus, 'Cek status reservasi')
                . ($etik ? '<p style="margin-bottom:4px"><b>Kode etik pengunjung:</b></p><ul style="margin-top:0">' . implode('', array_map(fn($a) => '<li>' . e($a) . '</li>', $etik)) . '</ul>' : '');
            break;
        case 'Dikonfirmasi':
            $subjek = "Reservasi {$r['kode']} dikonfirmasi";
            $isi = $halo . '<p>Kabar baik! Reservasimu sudah <b>dikonfirmasi</b>. Tunjukkan kode QR di halaman status kepada petugas loket saat tiba.</p>'
                . $rincian . $tombol($linkStatus, 'Buka tiket dan kode QR')
                . ($r['kupon'] ? '<div style="border:2px dashed #3F7D3A;border-radius:12px;padding:12px 14px;margin:12px 0;background:#EEF2E4"><b>Kamu mendapat Tiket Hijau: ' . e($r['kupon']) . '</b><br>'
                    . e(setting('tiket_hijau_syarat')) . ' Tunjukkan kode QR kupon di halaman status kepada penjual UMKM desa.</div>' : '')
                . '<p style="font-size:14px;color:#475663">Cek prakiraan cuaca di website sehari sebelum berangkat.</p>';
            break;
        case 'Dibatalkan':
            $subjek = "Reservasi {$r['kode']} tidak dapat diterima";
            $isi = $halo . '<p>Mohon maaf, reservasimu <b>tidak dapat kami terima</b>, misalnya karena kuota atau kondisi lokasi. Silakan pilih tanggal lain di website, atau hubungi kami lewat WhatsApp.</p>'
                . $rincian . $tombol(url_dasar() . '/reservasi', 'Pilih tanggal lain');
            break;
        case 'Selesai':
            $subjek = "Terima kasih sudah berkunjung ke {$r['nama_wisata']}";
            $isi = $halo . '<p>Terima kasih sudah berkunjung dan menjaga kelestarian ' . e(setting('nama_desa')) . '. Ceritakan pengalamanmu supaya pengunjung lain terbantu.</p>'
                . $tombol($linkStatus . '#ulasan', 'Beri ulasan')
                . ($r['gmaps_ulasan'] ? '<p>Kamu juga bisa memberi ulasan di <a href="' . e($r['gmaps_ulasan']) . '">Google Maps</a>.</p>' : '');
            break;
        default:
            return 'status tidak dikenal';
    }
    return kirim_email($r['email'], $subjek, bungkus_email($subjek, $isi));
}

// Pemberitahuan ke email desa setiap ada reservasi baru
function email_admin_reservasi_baru(string $kode): true|string
{
    $r = q1('SELECT r.*, w.nama AS nama_wisata FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.kode = ?', [$kode]);
    $tujuan = (string) getenv('GMAIL_USER');
    if (!$r || $tujuan === '') {
        return 'email desa belum diatur';
    }
    $subjek = "Reservasi baru {$r['kode']}: {$r['nama_wisata']}, " . tgl($r['tanggal']);
    $isi = '<p><b>' . e($r['nama']) . '</b> (' . e($r['wa']) . ', ' . e($r['email']) . ') memesan <b>' . e($r['nama_wisata']) . '</b> untuk '
        . tgl($r['tanggal'], 'hari') . ', ' . (int) $r['jumlah'] . ' orang.</p>'
        . ($r['catatan'] ? '<p>Catatan: ' . e($r['catatan']) . '</p>' : '')
        . '<p><a href="' . e(url_dasar() . '/admin/reservasi?status=Menunggu') . '">Buka CMS untuk konfirmasi</a></p>';
    return kirim_email($tujuan, $subjek, bungkus_email('Reservasi baru masuk', $isi));
}

// ---------- Tambahan Fase 8 ----------

// Email ke warga pengirim aspirasi setiap status berubah. Hasil: true atau teks penyebab gagal.
function email_aspirasi(string $kode): true|string
{
    $a = q1('SELECT * FROM aspirasi WHERE kode = ?', [$kode]);
    if (!$a || $a['email'] === '') {
        return 'aspirasi ini tidak punya alamat email';
    }
    $link = url_dasar() . '/kontak?adu=' . urlencode($a['kode']) . '#aspirasi';
    $tombol = '<p><a href="' . e($link) . '" style="display:inline-block;background:#F08A5D;color:#0E1B24;font-weight:bold;text-decoration:none;padding:12px 20px;border-radius:999px">Lihat perkembangan</a></p>';
    $kalimat = [
        'Diterima' => 'Aspirasimu sudah kami terima dan akan ditinjau petugas desa.',
        'Diproses' => 'Aspirasimu sedang <b>ditindaklanjuti</b> oleh pemerintah desa.',
        'Selesai'  => 'Aspirasimu sudah <b>selesai ditindaklanjuti</b>. Terima kasih sudah ikut membangun desa.',
        'Ditolak'  => 'Mohon maaf, aspirasimu <b>belum dapat ditindaklanjuti</b> saat ini.',
    ];
    $subjek = "Aspirasi {$a['kode']}: {$a['status']}";
    $isi = '<p>Halo ' . e($a['nama']) . ',</p><p>' . ($kalimat[$a['status']] ?? '') . '</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:15px;margin:12px 0">'
        . '<tr><td style="padding:6px 0;color:#475663">Kode</td><td><b style="font-size:18px">' . e($a['kode']) . '</b></td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Kategori</td><td>' . e($a['kategori']) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Status</td><td>' . e($a['status']) . '</td></tr></table>'
        . ($a['tanggapan'] ? '<p><b>Tanggapan petugas:</b><br>' . nl2br(e($a['tanggapan'])) . '</p>' : '')
        . $tombol;
    return kirim_email($a['email'], $subjek, bungkus_email($subjek, $isi));
}

// Pemberitahuan ke email desa setiap ada aspirasi baru
function email_admin_aspirasi_baru(string $kode): true|string
{
    $a = q1('SELECT * FROM aspirasi WHERE kode = ?', [$kode]);
    $tujuan = (string) getenv('GMAIL_USER');
    if (!$a || $tujuan === '') {
        return 'email desa belum diatur';
    }
    $subjek = "Aspirasi baru {$a['kode']}: {$a['kategori']}";
    $isi = '<p><b>' . e($a['nama']) . '</b> mengirim aspirasi kategori <b>' . e($a['kategori']) . '</b>'
        . ($a['lokasi'] ? ' di ' . e($a['lokasi']) : '') . ':</p><p>' . nl2br(e($a['isi'])) . '</p>'
        . '<p><a href="' . e(url_dasar() . '/admin/aspirasi?status=Diterima') . '">Buka CMS untuk menanggapi</a></p>';
    return kirim_email($tujuan, $subjek, bungkus_email('Aspirasi baru masuk', $isi));
}

// ---------- Tambahan Fase 9 ----------

// Email perkembangan permintaan data pribadi (kalau kontak berupa email)
function email_permintaan_data(string $kode): true|string
{
    $p = q1('SELECT * FROM permintaan_data WHERE kode = ?', [$kode]);
    if (!$p || !filter_var($p['kontak'], FILTER_VALIDATE_EMAIL)) {
        return 'kontak bukan alamat email';
    }
    $subjek = "Permintaan data pribadi {$p['kode']}: {$p['status']}";
    $isi = '<p>Halo ' . e($p['nama']) . ',</p><p>Permintaan <b>' . e(strtolower($p['jenis'])) . ' data pribadi</b> kamu sekarang berstatus <b>' . e($p['status']) . '</b>.</p>'
        . ($p['tanggapan'] ? '<p><b>Tanggapan petugas:</b><br>' . nl2br(e($p['tanggapan'])) . '</p>' : '')
        . '<p><a href="' . e(url_dasar() . '/kontak?data=' . urlencode($p['kode']) . '#privasi') . '">Lihat perkembangan</a></p>';
    return kirim_email($p['kontak'], $subjek, bungkus_email($subjek, $isi));
}

// ---------- Tambahan Fase 12 ----------

// Email ke warga setiap status pengajuan surat berubah (kalau ia mengisi email)
function email_surat(string $kode): true|string
{
    $s = q1('SELECT * FROM surat WHERE kode = ?', [$kode]);
    if (!$s || $s['email'] === '') {
        return 'pengajuan ini tidak punya alamat email';
    }
    $kalimat = [
        'Diterima'     => 'Pengajuan suratmu sudah kami terima. Petugas akan memeriksanya pada jam layanan.',
        'Diproses'     => 'Suratmu sedang <b>disiapkan</b> oleh petugas desa.',
        'Siap diambil' => 'Suratmu <b>sudah siap diambil</b> di kantor desa. Bawa KTP dan KK asli, lalu ambil nomor antrean di meja layanan.',
        'Selesai'      => 'Surat sudah kamu ambil. Terima kasih.',
        'Ditolak'      => 'Mohon maaf, pengajuan suratmu <b>belum dapat diproses</b>. Lihat catatan petugas di bawah.',
    ];
    $subjek = "Surat {$s['kode']}: {$s['status']}";
    $isi = '<p>Halo ' . e($s['nama']) . ',</p><p>' . ($kalimat[$s['status']] ?? '') . '</p>'
        . '<table style="width:100%;border-collapse:collapse;font-size:15px;margin:12px 0">'
        . '<tr><td style="padding:6px 0;color:#475663">Kode</td><td><b style="font-size:18px">' . e($s['kode']) . '</b></td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Jenis surat</td><td>' . e($s['layanan_nama']) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#475663">Status</td><td>' . e($s['status']) . '</td></tr></table>'
        . ($s['catatan'] ? '<p><b>Catatan petugas:</b><br>' . nl2br(e($s['catatan'])) . '</p>' : '')
        . '<p><a href="' . e(url_dasar() . '/data-desa?grup=Layanan+surat&srt=' . urlencode($s['kode']) . '#surat') . '" style="display:inline-block;background:#F08A5D;color:#0E1B24;font-weight:bold;text-decoration:none;padding:12px 20px;border-radius:999px">Lihat perkembangan</a></p>';
    return kirim_email($s['email'], $subjek, bungkus_email($subjek, $isi));
}

// Pemberitahuan ke email desa setiap ada pengajuan surat baru
function email_admin_surat_baru(string $kode): true|string
{
    $s = q1('SELECT * FROM surat WHERE kode = ?', [$kode]);
    $tujuan = (string) getenv('GMAIL_USER');
    if (!$s || $tujuan === '') {
        return 'email desa belum diatur';
    }
    $subjek = "Pengajuan surat {$s['kode']}: {$s['layanan_nama']}";
    $isi = '<p><b>' . e($s['nama']) . '</b> (WA ' . e($s['wa']) . ') mengajukan <b>' . e($s['layanan_nama']) . '</b> untuk ' . e($s['keperluan']) . '.</p>'
        . '<p><a href="' . e(url_dasar() . '/admin/surat?status=Diterima') . '">Buka CMS untuk memproses</a></p>';
    return kirim_email($tujuan, $subjek, bungkus_email('Pengajuan surat baru', $isi));
}
