<?php
// =========================================================
// ASISTEN DESA (JSON), dipanggil kotak obrolan di setiap halaman.
// POST /asisten  pesan=...   -> {"teks": "...", "tautan": [{label, url}], "saran": ["..."]}
//
// Cara kerja: BUKAN AI generatif. Asisten mengenali maksud pertanyaan lewat kata kunci,
// lalu menjawab dengan data terbaru dari database (yang diisi lewat CMS). Karena itu:
//   - jawabannya selalu sesuai data resmi desa dan tidak mengarang,
//   - gratis, tanpa API berbayar, dan tidak mengirim pertanyaan warga ke pihak lain,
//   - pertanyaan tidak disimpan (tidak ada riwayat obrolan di server).
// Pertanyaan tambahan bisa diisi admin di CMS > Asisten desa (FAQ).
// =========================================================
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$pesan = trim((string) ($_POST['pesan'] ?? ''));
$pesan = mb_substr($pesan, 0, 300);
$kecil = mb_strtolower($pesan);
$kata = preg_split('/[^\p{L}\p{N}-]+/u', $kecil, -1, PREG_SPLIT_NO_EMPTY);

$SARAN_UTAMA = ['Harga dan jam buka wisata', 'Cuaca beberapa hari ke depan', 'Cara reservasi', 'Syarat mengurus surat', 'Nomor darurat'];
$jawab = function (string $teks, array $tautan = [], ?array $saran = null) use ($SARAN_UTAMA) {
    echo json_encode(['teks' => $teks, 'tautan' => $tautan, 'saran' => $saran ?? $SARAN_UTAMA], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};
$ada = fn(array $daftar) => (bool) array_filter($daftar, fn($k) => str_contains($kecil, $k));

if ($pesan === '') {
    $jawab('Halo! Saya Asisten ' . setting('nama_desa') . '. Saya bisa membantu soal wisata, reservasi, cuaca, layanan surat, nomor darurat, produk UMKM, dan informasi desa. Mau tanya apa?');
}

// ---------- Kode reservasi, aspirasi, atau kupon (tanpa menampilkan data pribadi) ----------
if (preg_match('/\b(MM|ADU|HIJAU|SRT)-[A-Z0-9]{3,8}\b/i', $pesan, $m)) {
    $kode = strtoupper($m[0]);
    if (str_starts_with($kode, 'MM-')) {
        $r = q1('SELECT r.status, r.tanggal, r.jumlah, w.nama FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.kode = ?', [$kode]);
        if ($r) $jawab("Reservasi $kode ke {$r['nama']} untuk " . tgl($r['tanggal'], 'hari') . " ({$r['jumlah']} orang) berstatus {$r['status']}.", [['label' => 'Buka tiket dan detail', 'url' => '/reservasi?cek=' . urlencode($kode)]]);
    } elseif (str_starts_with($kode, 'SRT-')) {
        $s = q1('SELECT status, layanan_nama FROM surat WHERE kode = ?', [$kode]);
        if ($s) $jawab("Pengajuan $kode ({$s['layanan_nama']}) berstatus {$s['status']}." . ($s['status'] === 'Siap diambil' ? ' Ambil di kantor desa dengan membawa KTP dan KK asli.' : ''), [['label' => 'Lihat catatan petugas', 'url' => '/data-desa?grup=Layanan+surat&srt=' . urlencode($kode) . '#surat']]);
    } elseif (str_starts_with($kode, 'ADU-')) {
        $a = q1('SELECT status, kategori FROM aspirasi WHERE kode = ?', [$kode]);
        if ($a) $jawab("Aspirasi $kode (kategori {$a['kategori']}) berstatus {$a['status']}.", [['label' => 'Lihat tanggapan petugas', 'url' => '/kontak?adu=' . urlencode($kode) . '#aspirasi']]);
    } else {
        $k = cek_kupon($kode);
        if ($k['r']) $jawab("Kupon $kode: " . $k['teks'], [['label' => 'Buka halaman kupon', 'url' => '/umkm?kupon=' . urlencode($kode) . '#kupon']]);
    }
    $jawab("Kode $kode tidak ditemukan. Periksa lagi penulisannya, termasuk tanda hubung.");
}

// ---------- Pertanyaan tambahan dari admin (FAQ) ----------
$faqTerbaik = null; $skorFaq = 0;
foreach (q('SELECT pertanyaan, kata_kunci, jawaban FROM faq WHERE aktif = 1 ORDER BY urutan, id') as $f) {
    $skor = 0;
    foreach (array_filter(array_map(fn($k) => mb_strtolower(trim($k)), explode(',', $f['kata_kunci']))) as $k) {
        if (str_contains($kecil, $k)) $skor += mb_strlen($k) > 4 ? 2 : 1;
    }
    if ($skor > $skorFaq) { $skorFaq = $skor; $faqTerbaik = $f; }
}

// ---------- Kenali maksud pertanyaan ----------
$maksud = [
    'darurat'   => ['darurat', 'ambulans', 'polisi', 'kebakaran', 'kecelakaan', 'bahaya', 'banjir', 'longsor', 'hilang', '112'],
    'cuaca'     => ['cuaca', 'hujan', 'panas', 'cerah', 'mendung', 'suhu', 'badai'],
    'kupon'     => ['tiket hijau', 'kupon', 'diskon', 'potongan', 'voucher'],
    'emisi'     => ['karbon', 'emisi', 'skor hijau', 'ramah lingkungan'],
    'reservasi' => ['reservasi', 'pesan tiket', 'booking', 'memesan', 'daftar kunjungan', 'cara pesan', 'pesan kunjungan', 'mau pesan', 'ingin pesan', 'kuota', 'penuh', 'sisa'],
    'wisata'    => ['wisata', 'harga', 'tiket', 'jam buka', 'buka jam', 'destinasi', 'curug', 'air terjun', 'sawah', 'terasering', 'jeruk', 'anyaman', 'liburan', 'jalan-jalan', 'kursi roda', 'lansia', 'toilet'],
    'surat'     => ['surat', 'syarat', 'sktm', 'domisili', 'skck', 'kartu keluarga', 'ktp', 'kk', 'administrasi', 'mengurus', 'keterangan usaha', 'pengantar'],
    'lokasi'    => ['lokasi', 'alamat', 'arah', 'rute', 'maps', 'peta', 'dimana', 'di mana', 'menuju', 'ke desa'],
    'kontak'    => ['kontak', 'whatsapp', 'wa ', 'telepon', 'nomor desa', 'email', 'jam layanan', 'kantor'],
    'umkm'      => ['umkm', 'oleh-oleh', 'oleh oleh', 'produk', 'beli', 'belanja', 'kerajinan', 'makanan', 'keripik', 'sirup'],
    'agenda'    => ['agenda', 'acara', 'kegiatan', 'posyandu', 'imunisasi', 'jadwal', 'festival', 'merti'],
    'sampah'    => ['sampah', 'bank sampah', 'daur ulang', 'kompos', 'jelantah'],
    'anggaran'  => ['anggaran', 'apbdes', 'dana desa', 'keuangan', 'pembangunan', 'proyek', 'transparansi'],
    'bantuan'   => ['bantuan', 'blt', 'bansos', 'subsidi', 'modal usaha', 'penerima', 'salah sasaran', 'pkh', 'sembako'],
    'relawan'   => ['relawan', 'gotong royong', 'kerja bakti', 'sukarela', 'volunteer', 'ikut membantu', 'mau bantu'],
    'sdgs'      => ['sdg', 'sdgs', 'berkelanjutan', 'pembangunan berkelanjutan', 'tpb'],
    'antrean'   => ['antre', 'antri', 'nomor antrean', 'nomor antrian', 'giliran', 'loket'],
    'aspirasi'  => ['aspirasi', 'lapor', 'pengaduan', 'keluhan', 'usul', 'saran', 'informasi publik'],
    'privasi'   => ['privasi', 'data pribadi', 'hapus data', 'data saya'],
    'salam'     => ['halo', 'hai', 'hi ', 'pagi', 'siang', 'sore', 'malam', 'assalamualaikum', 'permisi', 'terima kasih', 'makasih'],
];
$skorMaksud = [];
foreach ($maksud as $nama => $kunci) {
    // Kata kunci yang lebih panjang lebih spesifik, jadi bobotnya lebih besar
    foreach ($kunci as $k) if (str_contains(' ' . $kecil . ' ', $k)) $skorMaksud[$nama] = ($skorMaksud[$nama] ?? 0) + (mb_strlen($k) > 5 ? 2 : 1);
}
arsort($skorMaksud);
$utama = array_key_first($skorMaksud);

// FAQ admin didahulukan kalau cocoknya kuat
if ($faqTerbaik && $skorFaq >= 2 && ($skorMaksud[$utama] ?? 0) <= $skorFaq) {
    $jawab($faqTerbaik['jawaban'], [], $SARAN_UTAMA);
}

switch ($utama) {
    case 'darurat':
        $nomor = q('SELECT nama, nomor FROM darurat ORDER BY urutan, id LIMIT 6');
        $teks = "Dalam keadaan darurat, segera telepon:\n" . implode("\n", array_map(fn($d) => "- {$d['nama']}: {$d['nomor']}", $nomor));
        $jawab($teks, array_map(fn($d) => ['label' => 'Telepon ' . $d['nomor'] . ' (' . $d['nama'] . ')', 'url' => 'tel:' . preg_replace('/[^0-9+]/', '', $d['nomor'])], array_slice($nomor, 0, 2)), ['Lokasi kantor desa', 'Cuaca beberapa hari ke depan']);

    case 'cuaca':
        // Ambil prakiraan Open-Meteo dari server (gratis, tanpa kunci). Kalau gagal, arahkan ke panel cuaca.
        $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . urlencode(setting('lat')) . '&longitude=' . urlencode(setting('lng'))
            . '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Asia%2FJakarta&forecast_days=3';
        $json = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 3]]));
        $data = $json ? json_decode($json, true) : null;
        if (!empty($data['daily']['time'])) {
            $namaCuaca = fn(int $k) => match (true) { $k === 0 => 'cerah', $k <= 3 => 'berawan', $k <= 48 => 'berkabut', $k <= 67 => 'hujan', $k <= 82 => 'hujan lokal', default => 'badai petir' };
            $baris = [];
            foreach ($data['daily']['time'] as $i => $t) {
                $baris[] = '- ' . tgl($t, 'hari') . ': ' . $namaCuaca((int) $data['daily']['weather_code'][$i]) . ', ' . round($data['daily']['temperature_2m_min'][$i]) . ' sampai ' . round($data['daily']['temperature_2m_max'][$i]) . '°C, peluang hujan ' . (int) $data['daily']['precipitation_probability_max'][$i] . '%';
            }
            $jawab("Prakiraan cuaca di pusat desa (sumber Open-Meteo):\n" . implode("\n", $baris) . "\nUntuk kunjungan ke curug, hindari hari dengan peluang hujan tinggi.", [['label' => 'Lihat cuaca 7 hari dan hari terbaik', 'url' => '/']], ['Cara reservasi', 'Harga dan jam buka wisata']);
        }
        $jawab('Prakiraan cuaca belum bisa dimuat saat ini. Lihat panel cuaca 7 hari di Beranda, lengkap dengan rekomendasi hari terbaik berkunjung.', [['label' => 'Buka Beranda', 'url' => '/']]);

    case 'kupon':
        $jawab('Tiket Hijau adalah kupon belanja di UMKM desa untuk pengunjung yang datang dengan perjalanan rendah emisi, misalnya bus rombongan, angkutan umum, kereta, sepeda, atau berbagi kendaraan. Saat reservasi, isi kendaraan dan jarak: kalau skor hijaumu ' . setting('tiket_hijau_skor') . ', kupon langsung muncul di tiket. ' . setting('tiket_hijau_syarat'),
            [['label' => 'Pesan kunjungan', 'url' => '/reservasi'], ['label' => 'Lihat UMKM penerima kupon', 'url' => '/umkm']], ['Cara reservasi', 'Produk UMKM']);

    case 'emisi':
        $d = ringkasan_dampak();
        $jawab('Setiap reservasi menghitung perkiraan jejak karbon perjalanan dari jenis kendaraan dan jaraknya, memakai faktor emisi resmi pemerintah Inggris (DESNZ). Sejauh ini rata-rata pengunjung menghasilkan ' . angka(round($d['kg'] / max(1, $d['orang_tercatat']), 1)) . ' kg CO2e per orang, dan ' . persen_id($d['orang_hijau'], $d['orang_tercatat']) . '% datang dengan transport bersama atau tanpa emisi.',
            [['label' => 'Lihat laporan dampak desa', 'url' => '/data-desa?grup=Dampak']], ['Tiket Hijau', 'Cara reservasi']);

    case 'reservasi':
        $besok = date('Y-m-d', strtotime('+1 day'));
        $sisa = array_map(fn($w) => "- {$w['nama']}: sisa " . max(0, $w['kuota'] - terpakai((int) $w['id'], $besok)) . " dari {$w['kuota']} orang", q('SELECT id, nama, kuota FROM wisata WHERE aktif = 1 ORDER BY id'));
        $jawab("Cara reservasi: buka halaman Reservasi, pilih destinasi dan tanggal, isi jumlah orang, kontak, serta kendaraan, lalu kirim. Kamu langsung mendapat kode MM- dan QR untuk check-in. Bayar di loket saat tiba.\nSisa kuota besok (" . tgl($besok, 'pendek') . "):\n" . implode("\n", $sisa),
            [['label' => 'Pesan sekarang', 'url' => '/reservasi']], ['Harga dan jam buka wisata', 'Cuaca beberapa hari ke depan', 'Tiket Hijau']);

    case 'wisata':
        $semua = q('SELECT slug, nama, harga, jam_buka, tingkat_jalur, ramah_lansia, ramah_kursi_roda, catatan_akses FROM wisata WHERE aktif = 1 ORDER BY id');
        $kataDesa = preg_split('/\s+/', mb_strtolower(setting('nama_desa'))); // "marga", "mulya" tidak dipakai untuk mencocokkan destinasi
        $cocok = array_values(array_filter($semua, fn($w) => (bool) array_filter(preg_split('/\s+/', mb_strtolower($w['nama'] . ' ' . $w['slug'])), fn($k) => mb_strlen($k) > 3 && !in_array($k, $kataDesa, true) && in_array($k, $kata, true))));
        if (count($cocok) === 1) {
            $w = $cocok[0];
            $tanyaAkses = $ada(['kursi roda', 'lansia', 'difabel', 'disabilitas', 'akses', 'jalur', 'hamil']);
            $akses = $tanyaAkses
                ? ($w['ramah_kursi_roda'] ? ' Bisa dilalui kursi roda.' : ' Belum bisa dilalui kursi roda.') . ($w['ramah_lansia'] ? ' Ramah lansia.' : ' Kurang cocok untuk lansia.')
                : ($w['ramah_lansia'] ? ' Ramah lansia.' : '') . ($w['ramah_kursi_roda'] ? ' Bisa kursi roda.' : '');
            $jawab("{$w['nama']}: tiket " . rupiah($w['harga']) . " per orang, buka {$w['jam_buka']}. Jalur " . strtolower($w['tingkat_jalur']) . '.' . $akses . ($w['catatan_akses'] ? ' ' . $w['catatan_akses'] : ''),
                [['label' => 'Detail, cuaca, dan kuota', 'url' => '/wisata/' . $w['slug']], ['label' => 'Pesan kunjungan', 'url' => '/reservasi?w=' . $w['slug']]], ['Cara reservasi', 'Cuaca beberapa hari ke depan']);
        }
        $jawab("Destinasi di " . setting('nama_desa') . ":\n" . implode("\n", array_map(fn($w) => "- {$w['nama']}: " . rupiah($w['harga']) . ", buka {$w['jam_buka']}, jalur " . strtolower($w['tingkat_jalur']), $semua)) . "\nSebut nama destinasinya untuk info lebih lengkap.",
            [['label' => 'Lihat semua destinasi', 'url' => '/wisata']], array_map(fn($w) => 'Info ' . $w['nama'], array_slice($semua, 0, 4)));

    case 'surat':
        $layanan = q('SELECT id, nama, syarat, lama_proses, biaya FROM layanan WHERE aktif = 1 ORDER BY urutan, id');
        foreach ($layanan as $l) {
            $kunci = array_filter(preg_split('/[\s()]+/', mb_strtolower($l['nama'])), fn($k) => mb_strlen($k) > 3 && !in_array($k, ['surat', 'keterangan', 'pengantar'], true));
            if (array_filter($kunci, fn($k) => str_contains($kecil, $k))) {
                $jawab("Syarat {$l['nama']}:\n" . implode("\n", array_map(fn($s) => "- $s", per_baris($l['syarat']))) . "\nLama proses {$l['lama_proses']}, biaya {$l['biaya']}. Jam layanan: " . setting('jam_layanan') . '.',
                    [['label' => 'Ajukan surat ini online', 'url' => '/data-desa?grup=Layanan+surat&l=' . (int) ($l['id'] ?? 0) . '#surat'], ['label' => 'Daftar syarat yang bisa dicentang', 'url' => '/data-desa?grup=Layanan+surat']], ['Lokasi kantor desa', 'Antrean kantor desa']);
            }
        }
        $jawab("Layanan surat yang tersedia:\n" . implode("\n", array_map(fn($l) => "- {$l['nama']} ({$l['lama_proses']}, {$l['biaya']})", $layanan)) . "\nSurat bisa diajukan online dulu, lalu diambil saat siap. Sebut nama suratnya untuk melihat syaratnya.",
            [['label' => 'Ajukan surat online', 'url' => '/data-desa?grup=Layanan+surat#surat'], ['label' => 'Pemandu layanan surat', 'url' => '/data-desa?grup=Layanan+surat']], array_map(fn($l) => 'Syarat ' . $l['nama'], array_slice($layanan, 0, 3)));

    case 'lokasi':
        $jawab('Kantor desa ada di ' . setting('alamat') . ', ' . setting('kecamatan') . ', ' . setting('kabupaten') . '. Jam layanan: ' . setting('jam_layanan') . '.',
            [['label' => 'Petunjuk arah di Google Maps', 'url' => 'https://www.google.com/maps/dir/?api=1&destination=' . setting('lat') . ',' . setting('lng')], ['label' => 'Peta fasilitas umum', 'url' => '/profil#fasilitas']], ['Kontak desa', 'Harga dan jam buka wisata']);

    case 'kontak':
        $jawab('Hubungi desa lewat WhatsApp +' . setting('wa') . ' atau email ' . setting('email') . '. Jam layanan: ' . setting('jam_layanan') . '.',
            [['label' => 'Chat WhatsApp desa', 'url' => wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya.')], ['label' => 'Halaman kontak', 'url' => '/kontak']], ['Lokasi kantor desa', 'Sampaikan aspirasi']);

    case 'umkm':
        $produk = q('SELECT produk, nama_usaha, harga, satuan FROM umkm ORDER BY id LIMIT 5');
        $jawab("Beberapa produk warga:\n" . implode("\n", array_map(fn($u) => "- {$u['produk']} dari {$u['nama_usaha']}: " . rupiah($u['harga']) . " per {$u['satuan']}", $produk)) . "\nPesan langsung ke pembuatnya lewat WhatsApp, tanpa perantara.",
            [['label' => 'Katalog UMKM', 'url' => '/umkm']], ['Tiket Hijau', 'Harga dan jam buka wisata']);

    case 'agenda':
        $khusus = $ada(['posyandu', 'imunisasi']) ? " AND judul LIKE '%osyandu%'" : '';
        $agenda = q("SELECT id, judul, tanggal, jam, tempat FROM agenda WHERE tanggal >= ?$khusus ORDER BY tanggal LIMIT 4", [date('Y-m-d')]);
        if (!$agenda) $jawab('Belum ada agenda yang akan datang.', [['label' => 'Agenda dan berita', 'url' => '/agenda']]);
        $jawab("Agenda terdekat:\n" . implode("\n", array_map(fn($a) => '- ' . tgl($a['tanggal'], 'pendek') . ": {$a['judul']}, {$a['jam']}, {$a['tempat']}", $agenda)),
            [['label' => 'Simpan ' . $agenda[0]['judul'] . ' ke kalender', 'url' => '/agenda?ics=' . (int) $agenda[0]['id']], ['label' => 'Semua agenda', 'url' => '/agenda']]);

    case 'sampah':
        $jawab(setting('info_bank_sampah') . "\nHarga beli:\n" . implode("\n", array_map(fn($p) => "- {$p[0]}: {$p[1]}", pasangan(setting('harga_sampah')))),
            [['label' => 'Data bank sampah', 'url' => '/data-desa?grup=Bank+sampah']]);

    case 'anggaran':
        $d = ringkasan_dampak();
        $proyek = (int) q1("SELECT COUNT(*) AS n FROM pembangunan WHERE status = 'Berjalan'")['n'];
        $jawab($d['apb_tahun'] ? "Belanja desa tahun {$d['apb_tahun']} sebesar " . rupiah_ringkas($d['apb_a']) . ', sudah terealisasi ' . persen_id($d['apb_r'], $d['apb_a']) . "%. Saat ini ada $proyek proyek pembangunan yang sedang berjalan." : 'Data anggaran belum diisi.',
            [['label' => 'Transparansi APBDes', 'url' => '/data-desa?grup=Transparansi'], ['label' => 'Pembangunan desa', 'url' => '/data-desa?grup=Pembangunan']], ['Sampaikan aspirasi', 'Program bantuan']);

    case 'bantuan':
        $program = q('SELECT nama, jadwal FROM bantuan WHERE aktif = 1 ORDER BY id');
        if ($ada(['salah sasaran', 'tidak layak', 'tidak tepat'])) {
            $jawab('Kalau ada penerima bantuan yang tidak layak, atau keluarga layak yang belum terdaftar, laporkan lewat form aspirasi kategori Bansos. Boleh tanpa nama, dan kamu mendapat kode ADU- untuk melacak tindak lanjutnya.',
                [['label' => 'Laporkan salah sasaran', 'url' => '/kontak?kategori=Bansos#aspirasi']]);
        }
        $jawab("Program bantuan di desa:\n" . implode("\n", array_map(fn($b) => "- {$b['nama']} ({$b['jadwal']})", $program)) . "\nDi Pantau bansos kamu bisa melihat penyaluran per tahap dan mengecek status dengan nama lengkap dan RT, tanpa NIK.",
            [['label' => 'Cek status penerima', 'url' => '/data-desa?grup=Pantau+bansos#cek-bansos'], ['label' => 'Penyaluran per tahap', 'url' => '/data-desa?grup=Pantau+bansos']], ['Laporkan salah sasaran', 'Syarat mengurus surat']);

    case 'relawan':
        $kegiatan = agenda_relawan(3);
        if (!$kegiatan) {
            $jawab('Saat ini belum ada kegiatan yang membuka pendaftaran relawan. Pantau halaman Agenda, kegiatan gotong royong baru diumumkan di sana.', [['label' => 'Lihat agenda', 'url' => '/agenda']], ['Agenda terdekat', 'Kirim aspirasi']);
        }
        $jawab("Kegiatan yang sedang mencari relawan:\n" . implode("\n", array_map(fn($a) => '- ' . $a['judul'] . ', ' . tgl($a['tanggal']) . ' (' . (int) $a['ikut'] . ' dari ' . (int) $a['relawan_kuota'] . ' relawan)', $kegiatan)) . "\nWarga dan pengunjung boleh ikut. Cukup isi nama dan nomor WhatsApp.",
            [['label' => 'Daftar jadi relawan', 'url' => '/agenda#agenda-' . (int) $kegiatan[0]['id']]], ['Agenda terdekat', 'Lokasi kantor desa']);

    case 'sdgs':
        $sdgJawab = kontribusi_sdg();
        $jawab('Desa ini sudah bergerak untuk ' . count($sdgJawab) . " tujuan SDGs, antara lain:\n" . implode("\n", array_map(fn($n) => '- SDG ' . $n . ': ' . SDG[$n][0], array_slice(array_keys($sdgJawab), 0, 6))) . "\nBukti tiap tujuan dihitung otomatis dari data desa.",
            [['label' => 'Lihat kontribusi SDGs', 'url' => '/data-desa?grup=Dampak#sdgs']], ['Ikut gotong royong', 'Bank sampah']);

    case 'antrean':
        $kini = q1("SELECT nomor FROM antrean WHERE tanggal = ? AND status = 'Dipanggil' ORDER BY dipanggil_pada DESC LIMIT 1", [date('Y-m-d')]);
        $tunggu = (int) q1("SELECT COUNT(*) AS n FROM antrean WHERE tanggal = ? AND status = 'Menunggu'", [date('Y-m-d')])['n'];
        $jawab('Ambil nomor antrean dengan memindai QR di meja layanan kantor desa. ' . ($kini ? 'Saat ini nomor ' . sprintf('%03d', $kini['nomor']) . " sedang dilayani, $tunggu orang menunggu." : 'Hari ini belum ada antrean yang dipanggil.') . ' Jam layanan: ' . setting('jam_layanan') . '.',
            [['label' => 'Lihat antrean', 'url' => '/data-desa?grup=Layanan+surat#antrean']], ['Syarat mengurus surat', 'Lokasi kantor desa']);

    case 'aspirasi':
        $jawab('Sampaikan aspirasi, pengaduan, atau permohonan informasi publik lewat form di halaman Kontak. Kamu mendapat kode ADU- untuk melacak tanggapannya, dan boleh mengirim tanpa nama.',
            [['label' => 'Kirim aspirasi', 'url' => '/kontak#aspirasi']], ['Nomor darurat', 'Transparansi anggaran']);

    case 'privasi':
        $jawab('Data pribadimu hanya dipakai untuk mengurus reservasi, pesan, dan aspirasi, lalu dihapus otomatis setelah ' . max(30, (int) setting('simpan_data_hari')) . ' hari. Kamu bisa meminta salinan, perbaikan, atau penghapusan data kapan saja.',
            [['label' => 'Privasi dan permintaan data', 'url' => '/kontak#privasi']]);

    case 'salam':
        $jawab(str_contains($kecil, 'terima kasih') || str_contains($kecil, 'makasih') ? 'Sama-sama! Semoga kunjunganmu menyenangkan. Ada lagi yang bisa saya bantu?' : 'Halo! Saya Asisten ' . setting('nama_desa') . '. Mau tanya soal wisata, reservasi, cuaca, surat, atau informasi desa?');
}

// FAQ dengan kecocokan lemah tetap lebih baik daripada tidak menjawab
if ($faqTerbaik && $skorFaq >= 1) {
    $jawab($faqTerbaik['jawaban']);
}
$jawab('Maaf, saya belum bisa menjawab pertanyaan itu. Saya bisa membantu soal wisata, reservasi, cuaca, layanan surat, nomor darurat, UMKM, agenda, dan anggaran desa. Untuk pertanyaan lain, hubungi petugas desa lewat WhatsApp.',
    [['label' => 'Chat WhatsApp desa', 'url' => wa_link(setting('wa'), 'Halo ' . setting('nama_desa') . ', saya ingin bertanya: ' . $pesan)]]);
