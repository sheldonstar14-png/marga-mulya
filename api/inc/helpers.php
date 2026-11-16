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
    if (!$rows) {
        return '<p class="muted">Belum ada data.</p>';
    }
    $max = max(array_map(fn($r) => (float) $r['nilai'], $rows)) ?: 1;
    $html = '';
    foreach ($rows as $r) {
        $lebar = $r['satuan'] === '%' ? (float) $r['nilai'] : (float) $r['nilai'] / $max * 100;
        $html .= '<div class="bar-row"><span>' . e($r['label']) . '</span>'
            . '<div class="bar-track"><div class="bar-fill" style="width:' . round($lebar, 1) . '%"></div></div>'
            . '<b>' . angka($r['nilai']) . ' ' . e($r['satuan']) . '</b></div>';
    }
    return $html;
}

// Ambil statistik satu kategori, urut sesuai kolom urutan
function statistik(string $kategori): array
{
    return q('SELECT label, nilai, satuan FROM statistik WHERE kategori = ? ORDER BY urutan, id', [$kategori]);
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

// Skor hijau dari emisi per orang: A (paling hemat) sampai E
function skor_hijau(float $kgPerOrang): array
{
    return match (true) {
        $kgPerOrang <= 2  => ['A', 'Sangat hijau'],
        $kgPerOrang <= 5  => ['B', 'Hijau'],
        $kgPerOrang <= 10 => ['C', 'Sedang'],
        $kgPerOrang <= 20 => ['D', 'Tinggi'],
        default           => ['E', 'Sangat tinggi'],
    };
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
    return $n;
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
