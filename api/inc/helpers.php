<?php
// Fungsi-fungsi kecil yang dipakai di semua halaman.

// Amankan teks sebelum ditampilkan di HTML (mencegah serangan XSS)
function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

// Jalankan query dan ambil semua baris. Contoh: q('SELECT * FROM wisata WHERE aktif = ?', [1])
function q(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

// Jalankan query dan ambil satu baris saja (atau null kalau tidak ada)
function q1(string $sql, array $params = []): ?array
{
    $baris = q($sql, $params);
    return $baris[0] ?? null;
}

// Ambil nilai dari tabel pengaturan. Contoh: setting('nama_desa')
function setting(string $kunci, string $bawaan = ''): string
{
    static $semua = null;
    if ($semua === null) {
        $semua = db()->query('SELECT kunci, nilai FROM pengaturan')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $semua[$kunci] ?? $bawaan;
}

// 15000 -> "Rp15.000"
function rupiah($angka): string
{
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

// 1436 -> "1.436", 8.4 -> "8,4"
function angka($n): string
{
    $n = (float) $n;
    return number_format($n, floor($n) == $n ? 0 : 1, ',', '.');
}

// "2026-11-18" -> "18 November 2026" (format: panjang, pendek, hari, tanggal, bulan)
function tgl(string $tanggal, string $format = 'panjang'): string
{
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $hari  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $t = strtotime($tanggal);
    $d = (int) date('j', $t);
    $m = (int) date('n', $t);
    $y = date('Y', $t);
    return match ($format) {
        'pendek'  => $d . ' ' . substr($bulan[$m], 0, 3),
        'hari'    => $hari[(int) date('w', $t)] . ', ' . $d . ' ' . $bulan[$m] . ' ' . $y,
        'tanggal' => (string) $d,
        'bulan'   => substr($bulan[$m], 0, 3),
        'bulantahun' => $bulan[$m] . ' ' . $y,
        default   => $d . ' ' . $bulan[$m] . ' ' . $y,
    };
}

// Link WhatsApp dengan pesan yang sudah terisi
function wa_link(string $nomor, string $pesan): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', $nomor) . '?text=' . rawurlencode($pesan);
}

// Baris-baris grafik batang dari tabel statistik
function bar_list(array $rows): string
{
    // Grafik batang berwarna. Warna mengikuti URUTAN baris (tetap, tidak diacak) dan sudah diuji
    // aman untuk buta warna. Baris ke-7 dan seterusnya abu-abu. Kalau semua baris satuannya sama
    // (misalnya jiwa), di atasnya tampil satu batang komposisi 100% supaya proporsinya terlihat.
    if (!$rows) {
        return '<p class="muted">Belum ada data.</p>';
    }
    $nilai = array_map(fn($r) => (float) $r['nilai'], $rows);
    $satuan = array_values(array_unique(array_column($rows, 'satuan')));
    // 12u: baris pertama bisa berupa jumlah keseluruhan (misalnya "Penduduk usia kerja" = Bekerja + Mencari kerja + Lainnya).
    // Baris seperti itu tidak ikut dihitung sebagai bagian; persen baris lain dihitung terhadapnya.
    $adaInduk = count($rows) >= 3 && count($satuan) === 1 && $satuan[0] !== '%'
        && abs($nilai[0] - array_sum(array_slice($nilai, 1))) <= max(1, $nilai[0] * 0.005) && $nilai[0] > 0;
    $bagian = $adaInduk ? array_slice($rows, 1) : $rows;
    $nilaiBagian = array_map(fn($r) => (float) $r['nilai'], $bagian);
    $max = max($nilaiBagian) ?: 1;
    $komposisi = count($bagian) >= 2 && count($satuan) === 1 && $satuan[0] !== '%' && array_sum($nilaiBagian) > 0;
    $total = $adaInduk ? $nilai[0] : array_sum($nilaiBagian);
    $warna = fn(int $i) => $i < 6 ? 'var(--seri-' . ($i + 1) . ')' : 'var(--seri-lain)';
    $html = '';
    // Kalau semua baris ilustrasi, cukup satu tanda di atas (tidak diulang per baris)
    $semuaIlus = count(array_filter($rows, fn($r) => !empty($r['ilustrasi']))) === count($rows);
    if ($semuaIlus) $html .= '<p class="ilus-semua"><span class="tanda-ilus">Ilustrasi</span> Angka contoh, menunggu data resmi kantor desa.</p>';
    if ($adaInduk) {
        $r = $rows[0];
        $html .= '<p class="bar-induk"><span>' . e($r['label']) . '</span><b>' . angka($r['nilai']) . ' ' . e($r['satuan']) . '</b><small>Rincian di bawah dihitung dari jumlah ini.</small></p>';
    }
    if ($komposisi) {
        $html .= '<div class="komposisi" role="img" aria-label="Komposisi: ' . e(implode(', ', array_map(fn($r) => $r['label'] . ' ' . persen_id((float) $r['nilai'], $total) . ' persen', $bagian))) . '">';
        foreach ($bagian as $i => $r) {
            $pr = (float) $r['nilai'] / $total * 100;
            if ($pr <= 0) continue;
            $html .= '<span style="width:' . round($pr, 2) . '%;background:' . $warna($i) . '" title="' . e($r['label'] . ': ' . angka($r['nilai']) . ' ' . $r['satuan'] . ' (' . persen_id((float) $r['nilai'], $total) . '%)') . '"></span>';
        }
        $html .= '</div>';
    }
    foreach ($bagian as $i => $r) {
        $lebar = $r['satuan'] === '%' ? (float) $r['nilai'] : (float) $r['nilai'] / $max * 100;
        $tanda = !empty($r['ilustrasi']) && !$semuaIlus ? ' <span class="ilus" title="Data contoh, bukan data resmi">(ilustrasi)</span>' : '';
        $html .= '<div class="bar-row"><span><i class="swatch" style="background:' . $warna($i) . '"></i>' . e($r['label']) . $tanda . '</span>'
            . '<div class="bar-track"><div class="bar-fill" style="width:' . round($lebar, 1) . '%;background:' . $warna($i) . '"></div></div>'
            . '<b>' . angka($r['nilai']) . ($r['satuan'] === '%' ? '%' : ' ' . e($r['satuan'])) . ($komposisi ? ' <small>' . persen_id((float) $r['nilai'], $total) . '%</small>' : '') . '</b></div>';
    }
    return $html;
}

// Ambil statistik satu kategori, urut sesuai kolom urutan
function statistik(string $kategori): array
{
    try {
        return q('SELECT label, nilai, satuan, ilustrasi FROM statistik WHERE kategori = ? ORDER BY urutan, id', [$kategori]);
    } catch (Throwable $e) { // kolom ilustrasi belum ada (migrasi 12n belum dijalankan)
        return q('SELECT label, nilai, satuan, 0 AS ilustrasi FROM statistik WHERE kategori = ? ORDER BY urutan, id', [$kategori]);
    }
}

// Pisahkan teks per baris (dipakai untuk daftar misi)
function per_baris(string $teks): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\\\\n/', $teks))));
}

// ---------- Tambahan Fase 3 ----------

// Pindah ke halaman lain (dipakai setelah form berhasil dikirim)
function redirect(string $alamat): never
{
    header('Location: ' . $alamat, true, 303);
    exit;
}

// Ubah daftar titik peta menjadi teks JSON yang aman ditaruh di atribut HTML
function titik_peta(array $titik): string
{
    return e(json_encode($titik, JSON_UNESCAPED_UNICODE));
}

// Jumlah pengunjung yang sudah memesan untuk satu destinasi di satu tanggal
function terpakai(int $wisataId, string $tanggal): int
{
    $baris = q1(
        "SELECT COALESCE(SUM(jumlah), 0) AS total FROM reservasi
         WHERE wisata_id = ? AND tanggal = ? AND status <> 'Dibatalkan'",
        [$wisataId, $tanggal]
    );
    return (int) $baris['total'];
}

// Kode reservasi acak yang belum pernah dipakai, contoh: MM-K7Q2PX
function kode_reservasi_baru(): string
{
    $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa O, 0, I, 1 supaya tidak tertukar
    do {
        $kode = 'MM-';
        for ($i = 0; $i < 6; $i++) {
            $kode .= $huruf[random_int(0, strlen($huruf) - 1)];
        }
    } while (q1('SELECT id FROM reservasi WHERE kode = ?', [$kode]));
    return $kode;
}

// Nomor HP Indonesia ke format WhatsApp: 0812... -> 62812...
function nomor_wa(string $nomor): string
{
    $angka = preg_replace('/\D/', '', $nomor);
    return str_starts_with($angka, '0') ? '62' . substr($angka, 1) : $angka;
}

// ---------- Tambahan Fase 7A ----------

// Kode etik pengunjung: aturan khusus destinasi + aturan umum dari Pengaturan
function kode_etik(array $wisata): array
{
    return array_values(array_unique([...per_baris((string) ($wisata['kode_etik'] ?? '')), ...per_baris(setting('kode_etik_umum'))]));
}

// ---------- Tambahan Fase 7B ----------

// Alamat website lengkap, contoh https://marga-mulya.vercel.app (untuk link di email dan QR)
function url_dasar(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

// Bintang rating, contoh bintang(4) -> ★★★★☆
// Potong teks panjang di batas kata (untuk ringkasan berita)
function ringkas_teks(string $teks, int $maks = 170): string
{
    $teks = trim(preg_replace('/\s+/', ' ', $teks));
    if (mb_strlen($teks) <= $maks) return $teks;
    $potong = mb_substr($teks, 0, $maks);
    return rtrim(mb_substr($potong, 0, (int) mb_strrpos($potong, ' ')), ' ,.;:') . '...';
}

function bintang(float $nilai): string
{
    $penuh = (int) round($nilai);
    return str_repeat('★', $penuh) . str_repeat('☆', 5 - $penuh);
}

// ---------- Tambahan Fase 8 ----------

// Kode acak dengan awalan tertentu, dicek supaya belum dipakai di tabel itu
function kode_unik(string $awalan, string $tabel): string
{
    $huruf = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $kode = $awalan . '-';
        for ($i = 0; $i < 6; $i++) {
            $kode .= $huruf[random_int(0, strlen($huruf) - 1)];
        }
    } while (q1("SELECT id FROM $tabel WHERE kode = ?", [$kode]));
    return $kode;
}

// Faktor emisi perjalanan (kg CO2e per km). Sumber: UK Government GHG Conversion Factors (DESNZ/DEFRA).
// 'per' = kendaraan: emisi satu kendaraan dibagi jumlah penumpang (maks 'isi' orang).
// 'per' = orang: emisi per penumpang (angkutan umum, sudah memperhitungkan rata-rata penumpang).
const KENDARAAN = [
    'Mobil pribadi'         => ['faktor' => 0.17,  'per' => 'kendaraan', 'isi' => 4],
    'Sepeda motor'          => ['faktor' => 0.11,  'per' => 'kendaraan', 'isi' => 2],
    'Bus atau elf rombongan'=> ['faktor' => 0.039, 'per' => 'orang'],
    'Angkutan umum'         => ['faktor' => 0.10,  'per' => 'orang'],
    'Kereta lalu ojek'      => ['faktor' => 0.031, 'per' => 'orang'],
    'Sepeda atau jalan kaki'=> ['faktor' => 0,     'per' => 'orang'],
];

// Perkiraan emisi pulang pergi untuk seluruh rombongan (kg CO2e)
function emisi_kunjungan(string $kendaraan, int $jarakKm, int $orang): float
{
    $k = KENDARAAN[$kendaraan] ?? null;
    if (!$k || $jarakKm <= 0 || $orang <= 0) return 0.0;
    $pp = $jarakKm * 2;
    if ($k['per'] === 'kendaraan') {
        $jumlahKendaraan = (int) ceil($orang / $k['isi']);
        return round($k['faktor'] * $pp * $jumlahKendaraan, 2);
    }
    return round($k['faktor'] * $pp * $orang, 2);
}

// Persen dengan batas 0 sampai 100 (untuk lebar bar)
function persen(float $bagian, float $total): float
{
    return $total > 0 ? max(0, min(100, round($bagian / $total * 100, 1))) : 0;
}

// Pecah teks "Nama|Nilai" per baris, contoh harga sampah
function pasangan(string $teks): array
{
    return array_map(fn($b) => array_map('trim', explode('|', $b, 2)) + [1 => ''], per_baris($teks));
}

// Rupiah singkat untuk angka besar: 1680000000 -> Rp1,68 miliar
function rupiah_ringkas(float $n): string
{
    if ($n >= 1e9) return 'Rp' . rtrim(rtrim(number_format($n / 1e9, 2, ',', '.'), '0'), ',') . ' miliar';
    if ($n >= 1e6) return 'Rp' . rtrim(rtrim(number_format($n / 1e6, 1, ',', '.'), '0'), ',') . ' juta';
    return rupiah($n);
}

// ---------- Tambahan Fase 9 ----------

// Cek kata kasar dari daftar di Pengaturan (dipisah koma). Dipakai untuk menahan ulasan publik agar ditinjau dulu.
function ada_kata_terlarang(string $teks): bool
{
    $daftar = array_filter(array_map(fn($k) => mb_strtolower(trim($k)), explode(',', setting('kata_terlarang'))));
    $teks = mb_strtolower($teks);
    foreach ($daftar as $kata) {
        if (preg_match('/(^|[^\p{L}])' . preg_quote($kata, '/') . '($|[^\p{L}])/u', $teks)) return true;
    }
    return false;
}

// Masa simpan data pribadi (UU PDP: data disimpan secukupnya sesuai tujuan).
// Reservasi yang sudah lewat, aspirasi yang sudah selesai, dan pesan lama dianonimkan:
// nama dan kontak dihapus, angka statistik (jumlah orang, emisi, kategori) tetap ada.
function bersihkan_data_lama(): int
{
    $hari = max(30, (int) setting('simpan_data_hari'));
    $batas = date('Y-m-d', strtotime("-$hari days"));
    $jalankan = function (string $sql) use ($batas): int { $st = db()->prepare($sql); $st->execute([$batas]); return $st->rowCount(); };
    $n = $jalankan("UPDATE reservasi SET nama = '(dihapus)', wa = '', email = '', catatan = '' WHERE tanggal < ? AND status IN ('Selesai', 'Dibatalkan') AND nama <> '(dihapus)'");
    $n += $jalankan("UPDATE aspirasi SET nama = '(dihapus)', wa = '', email = '' WHERE COALESCE(diperbarui_pada, dibuat_pada) < ? AND status IN ('Selesai', 'Ditolak') AND nama <> '(dihapus)'");
    $n += $jalankan("UPDATE pesan SET nama = '(dihapus)', kontak = '' WHERE dibuat_pada < ? AND dibaca = 1 AND nama <> '(dihapus)'");
    // Fase 12: surat yang sudah selesai/ditolak, dan antrean yang sudah lewat
    $n += $jalankan("UPDATE surat SET nama = '(dihapus)', wa = '', email = '' WHERE COALESCE(diperbarui_pada, dibuat_pada) < ? AND status IN ('Selesai', 'Ditolak') AND nama <> '(dihapus)'");
    $n += $jalankan("DELETE FROM antrean WHERE tanggal < ?");
    // Kerja dan pelatihan (12t): nomor WhatsApp pemberi kerja dihapus 30 hari setelah batas pendaftaran lewat
    try {
        $stL = db()->prepare("UPDATE loker SET wa = '', status = 'Selesai' WHERE batas < ? AND status IN ('Tayang', 'Menunggu', 'Ditolak')");
        $stL->execute([date('Y-m-d', strtotime('-30 days'))]);
        $n += $stL->rowCount();
    } catch (Throwable $e) {}
    $n += $jalankan("UPDATE pesanan_umkm SET nama = '(dihapus)', wa = '', alamat = '' WHERE dibuat_pada < ? AND nama <> '(dihapus)'");
    // Data relawan dihapus 30 hari setelah kegiatannya lewat (jumlah relawan di laporan tidak hilang karena dihitung saat itu)
    $stR = db()->prepare('DELETE FROM relawan WHERE agenda_id IN (SELECT id FROM agenda WHERE tanggal < ?)');
    $stR->execute([date('Y-m-d', strtotime('-30 days'))]);
    // Foto laporan yang tidak tampil di Papan Aspirasi ikut dihapus setelah laporan selesai lewat masa simpan
    try {
        $n += $jalankan("DELETE FROM aspirasi_foto WHERE aspirasi_id IN (SELECT id FROM aspirasi WHERE publik = 0 AND status IN ('Selesai', 'Ditolak') AND COALESCE(diperbarui_pada, dibuat_pada) < ?)");
    } catch (Throwable $e) {}
    $n += $stR->rowCount();
    // Nama penerima bansos dihapus 30 hari setelah program ditandai selesai (angka per RT tetap ada di laporan)
    $st = db()->prepare('DELETE FROM bansos_penerima WHERE bantuan_id IN (SELECT id FROM bantuan WHERE selesai IS NOT NULL AND selesai < ?)');
    $st->execute([date('Y-m-d', strtotime('-30 days'))]);
    return $n + $st->rowCount();
}

// Jam dari teks "08.00 sampai 11.00" -> ['0800', '1100'], atau null kalau tidak terbaca
function jam_agenda(string $teks): ?array
{
    if (!preg_match_all('/(\d{1,2})[.:](\d{2})/', $teks, $m) || !$m[0]) return null;
    $jam = array_map(fn($h, $i) => sprintf('%02d%02d', (int) $h, (int) $i), $m[1], $m[2]);
    return [$jam[0], $jam[1] ?? sprintf('%04d', min(2359, (int) $jam[0] + 100))];
}

// Label aksesibilitas destinasi untuk kartu dan detail wisata
function label_akses(array $w): array
{
    $label = [['jalur-' . strtolower($w['tingkat_jalur'] ?? 'Mudah'), 'Jalur ' . strtolower($w['tingkat_jalur'] ?? 'Mudah')]];
    if (!empty($w['ramah_lansia'])) $label[] = ['akses-ya', 'Ramah lansia'];
    if (!empty($w['ramah_kursi_roda'])) $label[] = ['akses-ya', 'Bisa kursi roda'];
    if (!empty($w['ada_toilet'])) $label[] = ['akses-netral', 'Ada toilet'];
    return $label;
}

// ---------- Tambahan Fase 10 ----------

// Ringkasan dampak desa untuk Beranda dan tab Dampak (dari data yang sudah tercatat)
function ringkasan_dampak(): array
{
    $kunjungan = q1("SELECT COALESCE(SUM(jumlah), 0) AS orang FROM reservasi WHERE status IN ('Dikonfirmasi', 'Selesai')");
    $sampahAkhir = q1('SELECT MAX(bulan) AS b FROM sampah')['b'] ?? null;
    $sampahKg = $sampahAkhir ? (float) q1('SELECT COALESCE(SUM(berat_kg), 0) AS n FROM sampah WHERE bulan > ?', [date('Y-m-d', strtotime(substr($sampahAkhir, 0, 7) . '-01 -12 month'))])['n'] : 0;
    $asp = q1("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END), 0) AS selesai FROM aspirasi");
    $tahunApb = (int) (q1('SELECT MAX(tahun) AS t FROM apbdes')['t'] ?? 0);
    $apb = $tahunApb ? q1("SELECT COALESCE(SUM(anggaran), 0) AS a, COALESCE(SUM(realisasi), 0) AS r FROM apbdes WHERE tahun = ? AND jenis = 'Belanja'", [$tahunApb]) : ['a' => 0, 'r' => 0];
    $ulasan = q1('SELECT COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan WHERE tampil = 1');
    return [
        'orang' => (int) $kunjungan['orang'],
        'sampah_kg' => $sampahKg, 'asp_total' => (int) $asp['total'], 'asp_selesai' => (int) $asp['selesai'],
        'apb_tahun' => $tahunApb, 'apb_a' => (float) $apb['a'], 'apb_r' => (float) $apb['r'],
        'ulasan_n' => (int) $ulasan['n'], 'ulasan_rata' => (float) $ulasan['rata'],
    ];
}

// Persen untuk ditampilkan dengan koma desimal Indonesia: 74.2 -> "74,2"
function persen_id(float $bagian, float $total): string
{
    return str_replace('.', ',', (string) persen($bagian, $total));
}

// ---------- Tambahan Fase 12 ----------

// Samarkan nama untuk tampilan publik: "Asep Saepudin" -> "As** Sa******"
function samarkan_nama(string $nama): string
{
    return implode(' ', array_map(fn($k) => mb_substr($k, 0, 2) . str_repeat('*', max(1, mb_strlen($k) - 2)), preg_split('/\s+/', trim($nama))));
}

// Bentuk nama untuk dicocokkan: huruf kecil, spasi rapi
function nama_baku(string $nama): string
{
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $nama)));
}

// RT/RW selalu dua digit: "3" -> "03"
function rt_baku(string $rt): string
{
    $rt = preg_replace('/\D/', '', $rt);
    return $rt === '' ? '' : str_pad(substr($rt, -3), 2, '0', STR_PAD_LEFT);
}

// Kode rahasia di QR antrean. Dibuat sekali, bisa diganti petugas di CMS > Surat & antrean.
function kode_antrean(bool $buatBaru = false): string
{
    $kode = setting('antrean_kode');
    if ($kode === '' || $buatBaru) {
        $kode = substr(bin2hex(random_bytes(6)), 0, 10);
        if (q1("SELECT kunci FROM pengaturan WHERE kunci = 'antrean_kode'")) {
            q("UPDATE pengaturan SET nilai = ? WHERE kunci = 'antrean_kode'", [$kode]);
        } else {
            q("INSERT INTO pengaturan (kunci, nilai) VALUES ('antrean_kode', ?)", [$kode]);
        }
    }
    return $kode;
}

// Ringkasan penyaluran satu program bansos: penerima terdaftar dan jumlah tersalur per tahap
function ringkas_bansos(array $b): array
{
    $total = (int) q1('SELECT COUNT(*) AS n FROM bansos_penerima WHERE bantuan_id = ?', [$b['id']])['n'];
    $tahap = [];
    for ($t = 1; $t <= max(1, (int) $b['jumlah_tahap']); $t++) {
        $tahap[$t] = (int) q1('SELECT COUNT(*) AS n FROM bansos_penerima WHERE bantuan_id = ? AND tahap_tersalur >= ?', [$b['id'], $t])['n'];
    }
    $perRt = q('SELECT rt, COUNT(*) AS n FROM bansos_penerima WHERE bantuan_id = ? GROUP BY rt ORDER BY rt', [$b['id']]);
    return ['total' => $total, 'tahap' => $tahap, 'per_rt' => $perRt];
}


// Kartu sambutan kepala desa. $ringkas = true: hanya paragraf pertama (untuk beranda)
function kartu_sambutan(bool $ringkas): string
{
    $teks = trim(str_replace('\n', "\n", setting('sambutan_kades')));
    if ($teks === '') return '';
    $kades = q1("SELECT nama FROM aparat WHERE jabatan LIKE 'Kepala Desa%' ORDER BY urutan, id LIMIT 1");
    $nama = $kades['nama'] ?? 'Kepala Desa';
    $paragraf = preg_split('/\n\s*\n/', $teks);
    if ($ringkas) $paragraf = [$paragraf[0]];
    $foto = setting('foto_kades');
    $inisial = implode('', array_map(fn($k) => mb_substr($k, 0, 1), array_slice(array_values(array_filter(preg_split('/\s+/', preg_replace('/\b(Hj?|H|Dr|Ir)\.\s*/', '', $nama)), fn($k) => $k !== '' && !str_contains($k, '.'))), 0, 2)));
    $h = '<figure class="sambutan"><div class="sambutan-foto">' . ($foto ? '<img src="' . e($foto) . '" alt="Foto ' . e($nama) . '" loading="lazy">' : '<span aria-hidden="true">' . e($inisial) . '</span>') . '</div><div>';
    $h .= '<blockquote>' . implode('', array_map(fn($p) => '<p>' . nl2br(e(trim($p))) . '</p>', $paragraf)) . '</blockquote>';
    $h .= '<figcaption><b>' . e($nama) . '</b><span>Kepala ' . e(setting('nama_desa')) . '</span></figcaption>';
    if ($ringkas && count(preg_split('/\n\s*\n/', $teks)) > 1) $h .= '<a class="small" href="/profil#sambutan">Baca sambutan lengkap</a>';
    return $h . '</div></figure>';
}

// ---------- Tambahan 12k: SDGs, relawan, kredit foto ----------
// 17 Tujuan Pembangunan Berkelanjutan (SDGs). Warna mengikuti warna resmi PBB;
// 'teks' dipilih supaya kontras angka di lingkaran minimal 4,5:1.
const SDG = [
    1  => ['Tanpa kemiskinan', '#E5243B', '#fff'],
    2  => ['Tanpa kelaparan', '#DDA63A', '#131A13'],
    3  => ['Kehidupan sehat dan sejahtera', '#4C9F38', '#131A13'],
    4  => ['Pendidikan berkualitas', '#C5192D', '#fff'],
    5  => ['Kesetaraan gender', '#FF3A21', '#131A13'],
    6  => ['Air bersih dan sanitasi', '#26BDE2', '#131A13'],
    7  => ['Energi bersih dan terjangkau', '#FCC30B', '#131A13'],
    8  => ['Pekerjaan layak dan ekonomi', '#A21942', '#fff'],
    9  => ['Industri, inovasi, dan infrastruktur', '#FD6925', '#131A13'],
    10 => ['Berkurangnya kesenjangan', '#DD1367', '#fff'],
    11 => ['Permukiman berkelanjutan', '#FD9D24', '#131A13'],
    12 => ['Konsumsi dan produksi bertanggung jawab', '#BF8B2E', '#131A13'],
    13 => ['Penanganan perubahan iklim', '#3F7E44', '#fff'],
    14 => ['Ekosistem lautan', '#0A97D9', '#131A13'],
    15 => ['Ekosistem daratan', '#56C02B', '#131A13'],
    16 => ['Perdamaian, keadilan, dan kelembagaan', '#00689D', '#fff'],
    17 => ['Kemitraan untuk mencapai tujuan', '#19486A', '#fff'],
];

// '6, 2' -> [6, 2] (hanya nomor 1 sampai 17, tanpa duplikat)
function sdg_daftar(?string $teks): array
{
    $hasil = [];
    foreach (preg_split('/\D+/', (string) $teks, -1, PREG_SPLIT_NO_EMPTY) as $n) {
        $n = (int) $n;
        if (isset(SDG[$n]) && !in_array($n, $hasil, true)) $hasil[] = $n;
    }
    return $hasil;
}

// Lingkaran nomor SDG berwarna. $denganNama = true menampilkan nama tujuan di sebelahnya.
function sdg_bulat(int $n, string $kelas = ''): string
{
    [$nama, $warna, $teks] = SDG[$n];
    return '<span class="sdg-bulat ' . $kelas . '" style="background:' . $warna . ';color:' . $teks . '" title="SDG ' . $n . ': ' . e($nama) . '">' . $n . '</span>';
}

function sdg_lencana(?string $teks): string
{
    $daftar = sdg_daftar($teks);
    if (!$daftar) return '';
    $html = '<ul class="sdg-lencana" aria-label="Mendukung tujuan SDGs">';
    foreach ($daftar as $n) {
        $html .= '<li>' . sdg_bulat($n) . '<span>' . e(SDG[$n][0]) . '</span></li>';
    }
    return $html . '</ul>';
}

// Agenda yang membuka pendaftaran relawan, beserta jumlah yang sudah ikut
function agenda_relawan(int $batas = 10): array
{
    try {
        return q('SELECT a.*, (SELECT COUNT(*) FROM relawan r WHERE r.agenda_id = a.id) AS ikut
                  FROM agenda a WHERE a.relawan_kuota > 0 AND a.tanggal >= ? ORDER BY a.tanggal LIMIT ' . (int) $batas, [date('Y-m-d')]);
    } catch (Throwable $e) {
        return []; // tabel relawan belum dibuat (migrasi 12k belum dijalankan)
    }
}

// Kredit foto dari Pengaturan, dirapikan menjadi tautan pendek.
// Setiap baris boleh berisi "Keterangan: https://..." atau hanya alamat. Baris tanpa alamat menjadi judul.
function kredit_foto_html(string $teks): string
{
    $judul = 'Kredit foto';
    $item = [];
    $sudah = [];
    foreach (preg_split('/\R/', $teks) as $baris) {
        $baris = trim($baris);
        if ($baris === '') continue;
        if (!preg_match('#https?://\S+#', $baris, $m)) {
            if (!$item) { $judul = rtrim($baris, ': '); } else { $item[] = [$baris, null]; }
            continue;
        }
        $url = rtrim($m[0], '.,;)');
        if (isset($sudah[$url])) continue; // alamat yang sama cukup sekali
        $sudah[$url] = true;
        $ket = trim(rtrim(str_replace($m[0], '', $baris), ' :-'));
        if ($ket === '') {
            $host = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));
            $ket = match (true) {
                str_contains($host, 'pin.it'), str_contains($host, 'pinterest') => 'Pinterest',
                str_contains($host, 'unsplash') => 'Unsplash',
                str_contains($host, 'pexels') => 'Pexels',
                str_contains($host, 'freepik') => 'Freepik',
                str_contains($host, 'wikimedia') => 'Wikimedia',
                default => $host,
            };
            $ket .= ' #' . (count(array_filter($item, fn($i) => $i[1] !== null && str_starts_with($i[0], $ket))) + 1);
        }
        $item[] = [$ket, $url];
    }
    if (!$item) return '';
    $html = '<div class="kredit"><b>' . e($judul) . '</b><ul>';
    foreach ($item as [$ket, $url]) {
        $html .= '<li>' . ($url ? '<a href="' . e($url) . '" target="_blank" rel="noopener nofollow">' . e($ket) . '</a>' : e($ket)) . '</li>';
    }
    return $html . '</ul></div>';
}


// Kontribusi desa per tujuan SDGs, dihitung otomatis dari data yang sudah diisi petugas.
// Hasil: [nomor SDG => ['angka' => [[nilai, keterangan], ...], 'terkait' => [nama proyek/program, ...]]]
function kontribusi_sdg(?array $d = null): array
{
    $d ??= ringkasan_dampak();
    $k = [];
    $tambah = function (int $n, string $nilai, string $ket) use (&$k) { $k[$n]['angka'][] = [$nilai, $ket]; };
    $aman = function (callable $f) { try { return $f(); } catch (Throwable $e) { return null; } };

    $penerima = (int) ($aman(fn() => q1('SELECT COUNT(*) AS n FROM bansos_penerima p JOIN bantuan b ON b.id = p.bantuan_id WHERE b.aktif = 1')['n']) ?? 0);
    if ($penerima) $tambah(1, angka($penerima), 'penerima bantuan dipantau terbuka per tahap, nama disamarkan');

    $sehat = q1("SELECT label, nilai, satuan FROM statistik WHERE kategori = 'Kesehatan' ORDER BY urutan, id LIMIT 1");
    if ($sehat) $tambah(3, angka($sehat['nilai']) . ($sehat['satuan'] === '%' ? '%' : ' ' . $sehat['satuan']), mb_strtolower($sehat['label']));
    $posyandu = (int) q1("SELECT COUNT(*) AS n FROM agenda WHERE judul LIKE '%osyandu%' AND tanggal >= ?", [date('Y-m-d')])['n'];
    if ($posyandu) $tambah(3, (string) $posyandu, 'jadwal posyandu bisa disimpan ke kalender HP');

    $jUmkm = (int) q1('SELECT COUNT(*) AS n FROM umkm')['n'];
    if ($jUmkm) $tambah(8, (string) $jUmkm, 'usaha warga dijual langsung tanpa perantara');

    $zona = (int) ($aman(fn() => q1('SELECT COUNT(*) AS n FROM zona_rawan')['n']) ?? 0);
    if ($zona) $tambah(11, (string) $zona, 'zona rawan bencana dipetakan dan disorot saat siaga cuaca');


    $mangrove = (int) q1("SELECT COUNT(*) AS n FROM agenda WHERE judul LIKE '%angrove%'")['n'];
    if ($mangrove) $tambah(14, (string) $mangrove, 'kegiatan tanam mangrove untuk menahan abrasi pesisir');

    if ($d['asp_total']) $tambah(16, persen_id($d['asp_selesai'], $d['asp_total']) . '%', 'aspirasi warga selesai ditindaklanjuti');
    if ($d['apb_tahun']) $tambah(16, persen_id($d['apb_r'], $d['apb_a']) . '%', 'belanja desa ' . $d['apb_tahun'] . ' terealisasi dan dibuka ke publik');
    $sv = survei_ringkas();
    if ($sv['n']) $tambah(16, str_replace('.', ',', (string) $sv['rata']) . ' dari 5', 'indeks kepuasan warga terhadap layanan desa');

    $rel = $aman(fn() => q1('SELECT COUNT(*) AS n, COUNT(DISTINCT agenda_id) AS kegiatan FROM relawan'));
    if ($rel && (int) $rel['n']) $tambah(17, (string) (int) $rel['n'], 'relawan ikut gotong royong di ' . (int) $rel['kegiatan'] . ' kegiatan');
    $mitra = (int) q1('SELECT COUNT(DISTINCT penyelenggara) AS n FROM bantuan WHERE aktif = 1')['n'];
    if ($mitra) $tambah(17, (string) $mitra, 'lembaga penyelenggara program bantuan bekerja sama dengan desa');

    // Label SDGs dari proyek pembangunan dan program bantuan
    foreach ([['pembangunan', 'nama', ''], ['bantuan', 'nama', ' WHERE aktif = 1']] as [$tabel, $kolom, $syarat]) {
        foreach ($aman(fn() => q("SELECT $kolom AS nama, sdg FROM $tabel$syarat")) ?? [] as $r) {
            foreach (sdg_daftar($r['sdg']) as $n) $k[$n]['terkait'][] = $r['nama'];
        }
    }
    ksort($k);
    return $k;
}

// ---------- Tambahan 12m: foto laporan, riwayat, dan dukungan aspirasi ----------
const FOTO_ASPIRASI_MAKS = 3;

// Periksa foto unggahan warga. Kembalikan [daftar foto siap simpan, daftar error].
// Foto diubah ulang menjadi JPEG maks. 1280 px supaya ringan dan data lokasi (GPS) di dalam foto terhapus.
function siapkan_foto_aspirasi(array $berkas): array
{
    $hasil = []; $error = [];
    $nama = (array) ($berkas['name'] ?? []);
    foreach ($nama as $i => $n) {
        $kode = (int) ($berkas['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($kode === UPLOAD_ERR_NO_FILE) continue;
        if (count($hasil) >= FOTO_ASPIRASI_MAKS) { $error[] = 'Maksimal ' . FOTO_ASPIRASI_MAKS . ' foto.'; break; }
        if ($kode !== UPLOAD_ERR_OK) { $error[] = 'Foto "' . $n . '" gagal diunggah. Coba foto yang lebih kecil.'; continue; }
        $isi = (string) file_get_contents($berkas['tmp_name'][$i]);
        $info = @getimagesizefromstring($isi);
        if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) { $error[] = 'Berkas "' . $n . '" bukan foto JPG, PNG, atau WEBP.'; continue; }
        if (strlen($isi) > 8 * 1024 * 1024) { $error[] = 'Foto "' . $n . '" lebih dari 8 MB.'; continue; }
        if (function_exists('imagecreatefromstring')) {
            $gb = @imagecreatefromstring($isi);
            if (!$gb) { $error[] = 'Foto "' . $n . '" tidak bisa dibaca.'; continue; }
            [$w, $h] = [imagesx($gb), imagesy($gb)];
            $skala = min(1, 1280 / max($w, $h));
            $w2 = max(1, (int) round($w * $skala)); $h2 = max(1, (int) round($h * $skala));
            $baru = imagecreatetruecolor($w2, $h2);
            imagefill($baru, 0, 0, imagecolorallocate($baru, 255, 255, 255));
            imagecopyresampled($baru, $gb, 0, 0, 0, 0, $w2, $h2, $w, $h);
            ob_start(); imagejpeg($baru, null, 78); $isi = (string) ob_get_clean();
            $hasil[] = ['mime' => 'image/jpeg', 'lebar' => $w2, 'tinggi' => $h2, 'data' => $isi];
        } else {
            // Tanpa GD: hanya terima foto yang sudah diperkecil di browser (kanvas browser juga membuang data GPS)
            if (strlen($isi) > 1024 * 1024) { $error[] = 'Foto "' . $n . '" terlalu besar.'; continue; }
            $hasil[] = ['mime' => $info['mime'], 'lebar' => (int) $info[0], 'tinggi' => (int) $info[1], 'data' => $isi];
        }
    }
    return [$hasil, $error];
}

function simpan_foto_aspirasi(int $aspirasiId, array $foto): void
{
    $st = db()->prepare('INSERT INTO aspirasi_foto (aspirasi_id, mime, lebar, tinggi, data) VALUES (?, ?, ?, ?, ?)');
    foreach ($foto as $f) {
        $st->bindValue(1, $aspirasiId, PDO::PARAM_INT);
        $st->bindValue(2, $f['mime']);
        $st->bindValue(3, $f['lebar'], PDO::PARAM_INT);
        $st->bindValue(4, $f['tinggi'], PDO::PARAM_INT);
        $st->bindValue(5, $f['data'], PDO::PARAM_LOB);
        $st->execute();
    }
}

function catat_riwayat_aspirasi(int $aspirasiId, string $status, ?string $catatan = null): void
{
    try {
        q('INSERT INTO aspirasi_riwayat (aspirasi_id, status, catatan) VALUES (?, ?, ?)', [$aspirasiId, $status, $catatan]);
    } catch (Throwable $e) { /* tabel belum dibuat: migrasi 12m belum dijalankan */ }
}

// Urutan tahap untuk ditampilkan: dari tabel riwayat, atau disusun dari status sekarang untuk laporan lama
function riwayat_aspirasi(array $a): array
{
    $baris = [];
    try { $baris = q('SELECT status, catatan, dibuat_pada FROM aspirasi_riwayat WHERE aspirasi_id = ? ORDER BY dibuat_pada, id', [(int) $a['id']]); } catch (Throwable $e) {}
    if (!$baris) {
        $baris[] = ['status' => 'Diterima', 'catatan' => null, 'dibuat_pada' => $a['dibuat_pada']];
        if ($a['status'] !== 'Diterima') $baris[] = ['status' => $a['status'], 'catatan' => $a['tanggapan'], 'dibuat_pada' => $a['diperbarui_pada'] ?: $a['dibuat_pada']];
    }
    return $baris;
}

// Penanda perangkat untuk tombol dukungan (alamat IP di-hash, tidak disimpan mentah)
function sidik_pengunjung(): string
{
    $ip = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''))[0]);
    $rahasia = getenv('APP_SECRET') ?: (defined('APP_SECRET') ? APP_SECRET : 'marga-mulya');
    return hash_hmac('sha256', $ip . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''), $rahasia);
}

// ---------- Tambahan 12n: survei kepuasan layanan ----------
const SURVEI_LABEL = [1 => 'Sangat buruk', 2 => 'Buruk', 3 => 'Cukup', 4 => 'Baik', 5 => 'Sangat baik'];

function survei_ada(string $jenis, string $kode): ?array
{
    try { return q1('SELECT nilai, komentar FROM survei WHERE jenis = ? AND kode = ?', [$jenis, $kode]); } catch (Throwable $e) { return null; }
}

// Ringkasan untuk ditampilkan ke publik: jumlah penilaian, rata-rata (1-5), dan persen yang puas (nilai 4-5)
function survei_ringkas(?string $jenis = null): array
{
    try {
        $r = $jenis ? q1('SELECT COUNT(*) AS n, AVG(nilai) AS rata, SUM(CASE WHEN nilai >= 4 THEN 1 ELSE 0 END) AS puas FROM survei WHERE jenis = ?', [$jenis])
                    : q1('SELECT COUNT(*) AS n, AVG(nilai) AS rata, SUM(CASE WHEN nilai >= 4 THEN 1 ELSE 0 END) AS puas FROM survei');
        $n = (int) $r['n'];
        return ['n' => $n, 'rata' => $n ? round((float) $r['rata'], 1) : 0, 'puas' => $n ? (int) round((int) $r['puas'] / $n * 100) : 0];
    } catch (Throwable $e) {
        return ['n' => 0, 'rata' => 0, 'puas' => 0];
    }
}

// Simpan penilaian. Hanya untuk surat/aspirasi yang statusnya Selesai, dan satu kode sekali.
// Mengembalikan pesan error, atau null kalau berhasil.
function simpan_survei(string $jenis, string $kode, int $nilai, string $komentar): ?string
{
    $komentar = trim(mb_substr($komentar, 0, 500));
    if (!isset(SURVEI_LABEL[$nilai])) return 'Pilih nilai 1 sampai 5.';
    if ($komentar !== '' && ada_kata_terlarang($komentar)) return 'Komentar mengandung kata yang tidak pantas.';
    $data = $jenis === 'surat'
        ? q1("SELECT layanan_nama AS layanan FROM surat WHERE kode = ? AND status = 'Selesai'", [$kode])
        : q1("SELECT kategori AS layanan FROM aspirasi WHERE kode = ? AND status = 'Selesai'", [$kode]);
    if (!$data) return 'Penilaian bisa diberikan setelah statusnya Selesai.';
    if (survei_ada($jenis, $kode)) return 'Kode ini sudah memberi penilaian. Terima kasih!';
    q('INSERT INTO survei (jenis, kode, layanan, nilai, komentar) VALUES (?, ?, ?, ?, ?)', [$jenis, $kode, $data['layanan'], $nilai, $komentar]);
    return null;
}

// Form penilaian (dipakai di hasil lacak surat dan aspirasi)
function survei_form(string $jenis, string $kode, string $aksi, ?string $error = null): string
{
    $sudah = survei_ada($jenis, $kode);
    if ($sudah) {
        return '<div class="survei survei-selesai" role="status"><b>Terima kasih atas penilaianmu.</b> Kamu memberi nilai ' . (int) $sudah['nilai'] . ' dari 5 (' . e(SURVEI_LABEL[(int) $sudah['nilai']]) . '). Penilaian warga membantu desa memperbaiki pelayanan.</div>';
    }
    $id = 'sv-' . preg_replace('/\W/', '', $kode);
    $html = '<form class="survei" method="post" action="' . e($aksi) . '">'
        . '<input type="hidden" name="aksi" value="survei"><input type="hidden" name="kode" value="' . e($kode) . '">'
        . '<fieldset><legend>Bagaimana pelayanan desa untuk urusan ini?</legend>';
    if ($error) $html .= '<div class="alert alert-err" role="alert">' . e($error) . '</div>';
    $html .= '<div class="survei-nilai">';
    foreach (SURVEI_LABEL as $n => $label) {
        $html .= '<label><input type="radio" name="nilai" value="' . $n . '" required><span><b>' . $n . '</b>' . e($label) . '</span></label>';
    }
    $html .= '</div></fieldset>'
        . '<label for="' . $id . '" class="small" style="font-weight:700">Saran untuk petugas (opsional)</label>'
        . '<textarea id="' . $id . '" name="komentar" rows="2" maxlength="500" placeholder="Apa yang sudah baik, atau apa yang perlu diperbaiki?"></textarea>'
        . '<button class="btn btn-dark btn-sm" type="submit">Kirim penilaian</button>'
        . '<p class="small muted" style="margin:6px 0 0">Penilaian tanpa nama. Rangkumannya ditampilkan terbuka sebagai indeks kepuasan layanan desa.</p>'
        . '</form>';
    return $html;
}

// ---------- Tambahan 12p: foto unggahan warga + pengajuan produk ----------
function simpan_foto_unggah(array $f): int
{
    $st = db()->prepare('INSERT INTO foto_unggah (mime, lebar, tinggi, data) VALUES (?, ?, ?, ?)');
    $st->bindValue(1, $f['mime']);
    $st->bindValue(2, $f['lebar'], PDO::PARAM_INT);
    $st->bindValue(3, $f['tinggi'], PDO::PARAM_INT);
    $st->bindValue(4, $f['data'], PDO::PARAM_LOB);
    $st->execute();
    return (int) db()->lastInsertId();
}

function daftar_kredit(): array
{
    try { return q('SELECT * FROM kredit_foto ORDER BY urutan, id'); } catch (Throwable $e) { return []; }
}

const KATEGORI_UMKM = ['Olahan hasil laut', 'Olahan kelapa', 'Makanan', 'Kerajinan', 'Olahan tani', 'Jasa'];

// Tautan "Lihat referensi" di akhir data statistik (alamat diatur di CMS > Pengaturan)
function tautan_referensi(): string
{
    $url = setting('referensi_data_url');
    if ($url === '') return '';
    return '<p class="referensi"><span>Sumber: ' . e(setting('referensi_data_nama')) . '</span> <a href="' . e($url) . '" target="_blank" rel="noopener">Lihat referensi<span class="sr-only"> (membuka situs BPS)</span></a></p>';
}


// ---------- Tambahan 12s: kredit foto per halaman, sambutan, cuaca, lembaga ----------

// Sumber foto: kalau kolom sumber kosong, isi otomatis untuk gambar bawaan dan foto kiriman warga
function sumber_foto(string $foto, string $sumber = ''): string
{
    if (trim($sumber) !== '') return trim($sumber);
    if (str_starts_with($foto, '/assets/img/') && str_ends_with($foto, '.svg')) return 'Ilustrasi vektor buatan tim website (bukan foto asli)';
    if (str_starts_with($foto, '/foto?')) return 'Foto kiriman pemilik usaha lewat form Ajukan produk';
    return '';
}

// 12u: semua foto dianggap ilustrasi (tim tidak memiliki foto asli desa). Bisa dimatikan di Pengaturan.
function semua_foto_ilustrasi(): bool
{
    return setting('semua_foto_ilustrasi', '1') === '1';
}

// Foto (jpg/png dari internet atau unggahan), bukan gambar vektor buatan tim
function foto_bukan_vektor(string $foto): bool
{
    $foto = trim($foto);
    return $foto !== '' && !(str_starts_with($foto, '/assets/img/') && str_ends_with($foto, '.svg'));
}

// Tanda ilustrasi di pojok foto. $lama = tanda "(ilustrasi)" dari judul atau kolom ilustrasi.
function tanda_foto(string $foto, bool $lama = false, string $teksVektor = 'Ilustrasi'): string
{
    if (foto_bukan_vektor($foto)) return ($lama || semua_foto_ilustrasi()) ? tanda_ilustrasi(true, 'Foto ilustrasi') : '';
    return tanda_ilustrasi($lama || semua_foto_ilustrasi(), $teksVektor);
}

// Daftar kredit dikelompokkan per halaman, sesuai tempat foto tampil. Dipakai dialog "Sumber dan kredit foto".
function kredit_per_halaman(): array
{
    static $hasil = null;
    if ($hasil !== null) return $hasil;
    $h = [];
    $aman = function (callable $f) { try { return $f(); } catch (Throwable $e) { return []; } };
    $tambah = function (string $hal, string $lokasi, string $judul, string $foto, string $sumber = '', string $url = '') use (&$h) {
        if (trim($foto) === '') return;
        $h[$hal][] = ['lokasi' => $lokasi, 'judul' => $judul, 'sumber' => sumber_foto($foto, $sumber), 'url' => preg_match('#^https?://#', $url) ? $url : '', 'ilus' => foto_bukan_vektor($foto) && semua_foto_ilustrasi()];
    };
    // Semua halaman
    $h['Semua halaman'][] = ['lokasi' => 'Kiri atas dan kaki halaman', 'judul' => 'Logo ' . setting('nama_desa'), 'sumber' => setting('catatan_logo', 'Dibuat tim dengan bantuan AI, bukan logo resmi pemerintah desa'), 'url' => ''];
    $h['Semua halaman'][] = ['lokasi' => 'Bagian atas beranda dan kaki halaman', 'judul' => 'Ilustrasi panorama laut, mangrove, dan sawah', 'sumber' => sumber_foto('/assets/img/panorama.svg'), 'url' => ''];
    // Beranda
    $tambah('Beranda', 'Panel judul beranda', 'Foto utama beranda', setting('foto_hero'), setting('foto_hero_sumber'), setting('foto_hero_url'));
    $tambah('Beranda dan Profil', 'Sambutan kepala desa', 'Foto kepala desa', setting('foto_kades'), setting('foto_kades_sumber'));
    // Wisata dan budaya
    foreach ($aman(fn() => q('SELECT nama, foto, sumber_foto, sumber_url FROM wisata WHERE aktif = 1 ORDER BY id')) as $r)
        $tambah('Wisata dan budaya', 'Kartu dan halaman destinasi', $r['nama'], (string) $r['foto'], $r['sumber_foto'], $r['sumber_url']);
    foreach ($aman(fn() => q('SELECT judul, foto, sumber_foto, sumber_url FROM budaya ORDER BY urutan, id')) as $r)
        $tambah('Wisata dan budaya', 'Bagian Budaya dan kesenian', $r['judul'], (string) $r['foto'], $r['sumber_foto'], $r['sumber_url']);
    // UMKM
    foreach ($aman(fn() => q('SELECT produk, nama_usaha, foto, foto_lain, sumber_foto, sumber_url FROM umkm ORDER BY id')) as $r) {
        $tambah('Produk UMKM', 'Kartu produk', $r['produk'] . ' (' . $r['nama_usaha'] . ')', (string) $r['foto'], $r['sumber_foto'], $r['sumber_url']);
    }
    // Kabar desa
    foreach ($aman(fn() => q('SELECT judul, foto, sumber_foto, sumber_url FROM berita ORDER BY tanggal DESC, id DESC')) as $r)
        $tambah('Kabar desa (berita)', 'Foto berita', $r['judul'], (string) $r['foto'], $r['sumber_foto'], $r['sumber_url']);
    foreach ($aman(fn() => q('SELECT judul, kategori, foto, sumber_foto, sumber_url FROM galeri ORDER BY id DESC')) as $r)
        $tambah('Galeri', 'Galeri, kategori ' . $r['kategori'], $r['judul'], (string) $r['foto'], $r['sumber_foto'], $r['sumber_url']);
    foreach ($aman(fn() => q('SELECT nama, foto, foto_sesudah, sumber_foto, sumber_url FROM pembangunan ORDER BY tahun DESC, id DESC')) as $r) {
        $tambah('Informasi: pembangunan', 'Foto sebelum', $r['nama'], (string) $r['foto'], $r['sumber_foto'], $r['sumber_url']);
        $tambah('Informasi: pembangunan', 'Foto sesudah atau terkini', $r['nama'], (string) $r['foto_sesudah'], $r['sumber_foto'], $r['sumber_url']);
    }
    // Sumber lain dari menu Sumber & kredit foto (data, peta, cuaca, dsb.)
    foreach (daftar_kredit() as $k) {
        $h['Data, peta, dan aset lain'][] = ['lokasi' => $k['keterangan'] ?: 'Seluruh website', 'judul' => $k['judul'], 'sumber' => $k['pembuat'], 'url' => $k['sumber_url'], 'lisensi' => $k['lisensi'], 'lisensi_url' => $k['lisensi_url']];
    }
    return $hasil = $h;
}

// Pisahkan tanda "(ilustrasi)" dari teks: ['teks bersih', true kalau bertanda ilustrasi]
function pisah_ilustrasi(string $teks): array
{
    $ada = (bool) preg_match('/\(ilustrasi\)/i', $teks);
    return [trim(preg_replace('/\s*\(ilustrasi\)\s*/i', ' ', $teks)), $ada];
}

function tanda_ilustrasi(bool $ada, string $teks = 'Ilustrasi'): string
{
    return $ada ? '<span class="tanda-ilus" title="Contoh, bukan data atau dokumentasi resmi desa">' . e($teks) . '</span>' : '';
}

// Paragraf dari teks CMS (mengenali baris kosong, baik baris baru asli maupun tulisan \n)
function paragraf_teks(string $teks): array
{
    $teks = str_replace(['\\n', "\r"], ["\n", ''], trim($teks));
    return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', $teks))));
}

// Kartu sambutan kepala desa (desain 12s). $ringkas = hanya paragraf pembuka dan kalimat penutup.
function sambutan_html(bool $ringkas): string
{
    $par = paragraf_teks(setting('sambutan_kades'));
    if (!$par) return '';
    $nama = setting('sambutan_nama', 'Kepala ' . setting('nama_desa'));
    $ilus = setting('sambutan_ilustrasi', '1') === '1';
    $foto = setting('foto_kades');
    $tampil = $ringkas ? array_slice($par, 1, 2) : $par;
    if (!$tampil) $tampil = [$par[0]];
    $h = '<article class="sambutan-a" id="sambutan">';
    $h .= '<div class="sambutan-foto">' . ($foto ? '<img src="' . e($foto) . '" alt="' . ($ilus ? 'Gambar ilustrasi kepala desa' : 'Foto ' . e($nama)) . '" loading="lazy" width="280" height="280">' : '') . ($ilus ? '<small>Gambar ilustrasi</small>' : '') . '</div>';
    $h .= '<div class="sambutan-isi"><span class="label-kecil">Sambutan Kepala Desa</span><blockquote>';
    foreach ($tampil as $i => $p) $h .= '<p>' . ($i === 0 ? '&ldquo;' : '') . nl2br(e($p)) . ($i === count($tampil) - 1 ? '&rdquo;' : '') . '</p>';
    $h .= '</blockquote><p class="sambutan-nama"><b>' . e($nama) . '</b>' . ($ilus ? ' <span class="muted">(ilustrasi)</span>' : '') . '</p>';
    if ($ringkas) $h .= '<p class="sambutan-aksi"><a href="/profil#sambutan">Baca sambutan lengkap</a><a href="/profil#visi">Visi dan misi desa</a></p>';
    return $h . '</div></article>';
}

// Ambang gelombang yang dipakai kelompok nelayan (dipakai juga di cuaca.js)
const AMBANG_GELOMBANG = [1.25, 2.5];

function lembaga_desa(): array
{
    try { return q('SELECT * FROM lembaga ORDER BY urutan, id'); } catch (Throwable $e) { return []; }
}

// Kode etik lengkap per kelompok: ['Jaga alam' => [...], ...]. Aturan khusus destinasi masuk kelompok "Khusus di sini".
function kode_etik_kelompok(?array $wisata = null): array
{
    $k = [];
    if ($wisata) foreach (per_baris((string) ($wisata['kode_etik'] ?? '')) as $a) $k['Khusus di ' . pisah_ilustrasi($wisata['nama'])[0]][] = $a;
    foreach (per_baris(setting('kode_etik_lengkap')) as $baris) {
        [$grup, $aturan] = array_pad(explode('|', $baris, 2), 2, '');
        if ($aturan === '') { $aturan = $grup; $grup = 'Aturan umum'; }
        $k[trim($grup)][] = trim($aturan);
    }
    if (count($k) <= ($wisata && !empty($wisata['kode_etik']) ? 1 : 0)) foreach (per_baris(setting('kode_etik_umum')) as $a) $k['Aturan umum'][] = $a;
    return $k;
}

// Kantor desa buka atau tutup sekarang (perkiraan dari teks jam layanan, contoh "Senin sampai Jumat, 08.00 sampai 15.00")
function status_kantor(): array
{
    $jam = jam_agenda(setting('jam_layanan'));
    $hari = (int) date('N');
    $kerja = $hari <= 5;
    if (!$jam) return [null, setting('jam_layanan')];
    $kini = date('Hi');
    $buka = $kerja && $kini >= $jam[0] && $kini < $jam[1];
    $tutup = substr($jam[1], 0, 2) . '.' . substr($jam[1], 2);
    $bukaJam = substr($jam[0], 0, 2) . '.' . substr($jam[0], 2);
    return [$buka, $buka ? 'Tutup pukul ' . $tutup . ' WIB' : ($kerja && $kini < $jam[0] ? 'Buka hari ini pukul ' . $bukaJam . ' WIB' : 'Buka lagi hari kerja pukul ' . $bukaJam . ' WIB')];
}

// Semua id foto sebuah pengajuan produk (foto_ids baru 12s, atau foto_id lama)
function foto_pengajuan(array $p): array
{
    $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($p['foto_ids'] ?? '')))));
    if (!$ids && !empty($p['foto_id'])) $ids = [(int) $p['foto_id']];
    return array_slice($ids, 0, 3);
}

// Nama kabupaten untuk logo: "Tangerang", "Kab. Tangerang", atau "Kabupaten Tangerang" -> "Kabupaten Tangerang"
function nama_kabupaten(): string
{
    $k = trim(setting('kabupaten'));
    if (preg_match('/^(kota)\s/i', $k)) return $k;
    return 'Kabupaten ' . trim(preg_replace('/^(kabupaten|kab\.?)\s*/i', '', $k));
}

// ---------- Tambahan 12s3: semua peta memakai Google Maps ----------
// Alamat sematan Google Maps untuk satu titik (tanpa API key)
function gmaps_embed(float $lat, float $lng, int $zoom = 16): string
{
    return 'https://maps.google.com/maps?q=' . rawurlencode($lat . ',' . $lng) . '&z=' . $zoom . '&output=embed';
}
function gmaps_rute(float $lat, float $lng): string
{
    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($lat . ',' . $lng);
}

// Peta Google + daftar lokasi. Menekan nama lokasi memindahkan peta ke titik itu.
// $grup = ['Wisata' => [['nama'=>..,'ket'=>..,'lat'=>..,'lng'=>..,'url'=>..,'warna'=>..,'zona'=>bool], ...], ...]
// Kalau Pengaturan "gmaps_mymaps" diisi (peta Google My Maps buatan tim), peta awal menampilkan semua titik sekaligus.
function peta_google(string $id, array $grup, string $judul): string
{
    $grup = array_filter($grup);
    $mymaps = setting('gmaps_mymaps');
    $awal = preg_match('#^https://(www\.)?google\.com/maps/d/(u/\d+/)?(embed|viewer)\?mid=#', $mymaps)
        ? str_replace('/viewer?', '/embed?', $mymaps)
        : gmaps_embed((float) setting('lat'), (float) setting('lng'), 14);
    $h = '<div class="gpeta" id="' . e($id) . '">';
    $h .= '<div class="gpeta-peta"><iframe title="' . e($judul) . '" src="' . e($awal) . '" data-awal="' . e($awal) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>'
        . '<p class="gpeta-pilihan" aria-live="polite">Menampilkan: wilayah ' . e(setting('nama_desa')) . '</p></div>';
    $h .= '<div class="gpeta-daftar">';
    if (count($grup) > 1) {
        $h .= '<div class="chips chips-geser gpeta-lapis" role="group" aria-label="Saring lokasi"><button type="button" class="chip on" data-lapis="" aria-pressed="true">Semua</button>';
        foreach ($grup as $nama => $isi) $h .= '<button type="button" class="chip" data-lapis="' . e($nama) . '" aria-pressed="false">' . e($nama) . ' <span class="small">(' . count($isi) . ')</span></button>';
        $h .= '</div>';
    }
    foreach ($grup as $nama => $isi) {
        $h .= '<section data-lapis-item="' . e($nama) . '"><h3 class="gpeta-grup">' . e($nama) . '</h3><ul>';
        foreach ($isi as $t) {
            [$namaT] = pisah_ilustrasi($t['nama']);
            $h .= '<li' . (!empty($t['zona']) ? ' class="gpeta-zona"' : '') . '><button type="button" class="gpeta-titik" data-src="' . e(gmaps_embed((float) $t['lat'], (float) $t['lng'], !empty($t['zona']) ? 16 : 17)) . '" data-nama="' . e($namaT) . '">'
                . '<i style="background:' . e($t['warna'] ?? '#1E8C93') . '" aria-hidden="true"></i><span><b>' . e($namaT) . '</b>' . ($t['ket'] ? '<small>' . e($t['ket']) . '</small>' : '') . '</span></button>'
                . '<span class="gpeta-aksi">' . (!empty($t['url']) ? '<a href="' . e($t['url']) . '">Detail</a>' : '') . '<a href="' . e(gmaps_rute((float) $t['lat'], (float) $t['lng'])) . '" target="_blank" rel="noopener">Rute</a></span></li>';
        }
        $h .= '</ul></section>';
    }
    return $h . '</div></div>';
}
