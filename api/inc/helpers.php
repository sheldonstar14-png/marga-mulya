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
