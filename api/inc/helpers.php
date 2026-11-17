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
    $max = max($nilai) ?: 1;
    $satuan = array_unique(array_column($rows, 'satuan'));
    $komposisi = count($rows) >= 2 && count($satuan) === 1 && $satuan[0] !== '%' && array_sum($nilai) > 0;
    $total = array_sum($nilai);
    $warna = fn(int $i) => $i < 6 ? 'var(--seri-' . ($i + 1) . ')' : 'var(--seri-lain)';
    $html = '';
    if ($komposisi) {
        $html .= '<div class="komposisi" role="img" aria-label="Komposisi: ' . e(implode(', ', array_map(fn($r) => $r['label'] . ' ' . persen_id((float) $r['nilai'], $total) . ' persen', $rows))) . '">';
        foreach ($rows as $i => $r) {
            $pr = (float) $r['nilai'] / $total * 100;
            if ($pr <= 0) continue;
            $html .= '<span style="width:' . round($pr, 2) . '%;background:' . $warna($i) . '" title="' . e($r['label'] . ': ' . angka($r['nilai']) . ' ' . $r['satuan'] . ' (' . persen_id((float) $r['nilai'], $total) . '%)') . '"></span>';
        }
        $html .= '</div>';
    }
    foreach ($rows as $i => $r) {
        $lebar = $r['satuan'] === '%' ? (float) $r['nilai'] : (float) $r['nilai'] / $max * 100;
        $html .= '<div class="bar-row"><span><i class="swatch" style="background:' . $warna($i) . '"></i>' . e($r['label']) . '</span>'
            . '<div class="bar-track"><div class="bar-fill" style="width:' . round($lebar, 1) . '%;background:' . $warna($i) . '"></div></div>'
            . '<b>' . angka($r['nilai']) . ' ' . e($r['satuan']) . ($komposisi ? ' <small>' . persen_id((float) $r['nilai'], $total) . '%</small>' : '') . '</b></div>';
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
    // Fase 12: surat yang sudah selesai/ditolak, dan antrean yang sudah lewat
    $n += $jalankan("UPDATE surat SET nama = '(dihapus)', wa = '', email = '' WHERE COALESCE(diperbarui_pada, dibuat_pada) < ? AND status IN ('Selesai', 'Ditolak') AND nama <> '(dihapus)'");
    $n += $jalankan("DELETE FROM antrean WHERE tanggal < ?");
    $n += $jalankan("UPDATE pesanan_umkm SET nama = '(dihapus)', wa = '', alamat = '' WHERE dibuat_pada < ? AND nama <> '(dihapus)'");
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

// Apakah reservasi berhak Tiket Hijau (kupon UMKM)? Dilihat dari skor hijau per orang.
function berhak_tiket_hijau(float $emisiTotal, int $orang, string $kendaraan): bool
{
    if (setting('tiket_hijau_aktif') !== '1' || $kendaraan === '') return false;
    [$huruf] = skor_hijau($emisiTotal / max(1, $orang));
    $skorBoleh = array_map('trim', explode(',', strtoupper(setting('tiket_hijau_skor'))));
    return in_array($huruf, $skorBoleh, true);
}

// Status kupon: ['ok' => bool, 'teks' => keterangan, 'r' => baris reservasi]
function cek_kupon(string $kode): array
{
    $r = q1('SELECT r.kode, r.kupon, r.kupon_dipakai_pada, r.kupon_umkm, r.tanggal, r.status, r.jumlah, w.nama AS wisata FROM reservasi r JOIN wisata w ON w.id = r.wisata_id WHERE r.kupon = ?', [strtoupper(trim($kode))]);
    if (!$r) return ['ok' => false, 'teks' => 'Kupon tidak ditemukan.', 'r' => null];
    if ($r['kupon_dipakai_pada']) return ['ok' => false, 'teks' => 'Kupon sudah dipakai di ' . $r['kupon_umkm'] . ' pada ' . tgl(substr($r['kupon_dipakai_pada'], 0, 10)) . '.', 'r' => $r];
    if ($r['status'] === 'Dibatalkan' || $r['status'] === 'Menunggu') return ['ok' => false, 'teks' => 'Kupon belum aktif. Kupon berlaku setelah reservasi dikonfirmasi.', 'r' => $r];
    $hariIni = date('Y-m-d');
    if ($hariIni < $r['tanggal']) return ['ok' => false, 'teks' => 'Kupon baru bisa dipakai mulai tanggal kunjungan, ' . tgl($r['tanggal']) . '.', 'r' => $r];
    if ($hariIni > date('Y-m-d', strtotime($r['tanggal'] . ' +7 days'))) return ['ok' => false, 'teks' => 'Masa berlaku kupon sudah lewat (7 hari setelah kunjungan).', 'r' => $r];
    return ['ok' => true, 'teks' => 'Kupon berlaku. ' . setting('tiket_hijau_syarat'), 'r' => $r];
}

// Ringkasan dampak desa untuk Beranda dan tab Dampak (dari data yang sudah tercatat)
function ringkasan_dampak(): array
{
    $tahun = date('Y');
    $hijau = "'Bus atau elf rombongan', 'Angkutan umum', 'Kereta lalu ojek', 'Sepeda atau jalan kaki'";
    $kunjungan = q1("SELECT COALESCE(SUM(jumlah), 0) AS orang, COALESCE(SUM(emisi_kg), 0) AS kg,
        COALESCE(SUM(CASE WHEN kendaraan IN ($hijau) THEN jumlah ELSE 0 END), 0) AS orang_hijau,
        COALESCE(SUM(CASE WHEN kendaraan <> '' THEN jumlah ELSE 0 END), 0) AS orang_tercatat
        FROM reservasi WHERE status IN ('Dikonfirmasi', 'Selesai')");
    // Emisi kalau semua pengunjung yang tercatat datang dengan mobil pribadi (pembanding)
    $semuaMobil = 0.0;
    foreach (q("SELECT jarak_km, jumlah FROM reservasi WHERE status IN ('Dikonfirmasi', 'Selesai') AND kendaraan <> ''") as $r) {
        $semuaMobil += emisi_kunjungan('Mobil pribadi', (int) $r['jarak_km'], (int) $r['jumlah']);
    }
    $kupon = q1("SELECT COUNT(kupon) AS terbit, COUNT(kupon_dipakai_pada) AS dipakai FROM reservasi WHERE kupon IS NOT NULL");
    $sampahAkhir = q1('SELECT MAX(bulan) AS b FROM sampah')['b'] ?? null;
    $sampahKg = $sampahAkhir ? (float) q1('SELECT COALESCE(SUM(berat_kg), 0) AS n FROM sampah WHERE bulan > ?', [date('Y-m-d', strtotime(substr($sampahAkhir, 0, 7) . '-01 -12 month'))])['n'] : 0;
    $asp = q1("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END), 0) AS selesai FROM aspirasi");
    $tahunApb = (int) (q1('SELECT MAX(tahun) AS t FROM apbdes')['t'] ?? 0);
    $apb = $tahunApb ? q1("SELECT COALESCE(SUM(anggaran), 0) AS a, COALESCE(SUM(realisasi), 0) AS r FROM apbdes WHERE tahun = ? AND jenis = 'Belanja'", [$tahunApb]) : ['a' => 0, 'r' => 0];
    $ulasan = q1('SELECT COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan WHERE tampil = 1');
    return [
        'orang' => (int) $kunjungan['orang'], 'kg' => (float) $kunjungan['kg'],
        'orang_tercatat' => (int) $kunjungan['orang_tercatat'], 'orang_hijau' => (int) $kunjungan['orang_hijau'],
        'kg_hemat' => max(0, $semuaMobil - (float) $kunjungan['kg']),
        'kupon_terbit' => (int) $kupon['terbit'], 'kupon_dipakai' => (int) $kupon['dipakai'],
        'nilai_umkm' => (int) $kupon['dipakai'] * (int) setting('tiket_hijau_nilai'),
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
