<?php
// =========================================================
// LAPORAN BULANAN UNTUK KEPALA DESA
// Dipakai halaman CMS > Laporan bulanan (lihat, cetak, kirim email)
// dan pengiriman otomatis tiap tanggal 1 lewat Vercel Cron (/laporan-otomatis).
// Isinya hanya angka ringkasan, TANPA nama atau kontak warga (aman dikirim lewat email).
// =========================================================

// Kumpulkan semua angka untuk satu bulan, $bulan berformat 'YYYY-MM'
function data_laporan(string $bulan): array
{
    $awal = $bulan . '-01';
    $akhir = date('Y-m-t', strtotime($awal));
    $bulanLalu = date('Y-m', strtotime($awal . ' -1 month'));
    $awalLalu = $bulanLalu . '-01';
    $akhirLalu = date('Y-m-t', strtotime($awalLalu));
    $hijau = "'Bus atau elf rombongan', 'Angkutan umum', 'Kereta lalu ojek', 'Sepeda atau jalan kaki'";
    $aktif = "status IN ('Dikonfirmasi', 'Selesai')";

    // Wisata: dihitung dari tanggal kunjungan di bulan itu
    $perWisata = q("SELECT w.nama, w.harga, COUNT(r.id) AS rombongan, COALESCE(SUM(r.jumlah), 0) AS orang
        FROM wisata w LEFT JOIN reservasi r ON r.wisata_id = w.id AND r.tanggal BETWEEN ? AND ? AND r.$aktif
        GROUP BY w.id, w.nama, w.harga ORDER BY orang DESC", [$awal, $akhir]);
    foreach ($perWisata as &$w) $w['pendapatan'] = (int) $w['orang'] * (int) $w['harga'];
    unset($w);
    $kunjungan = q1("SELECT COUNT(*) AS rombongan, COALESCE(SUM(jumlah), 0) AS orang, COALESCE(SUM(emisi_kg), 0) AS kg,
        COALESCE(SUM(CASE WHEN kendaraan <> '' THEN jumlah ELSE 0 END), 0) AS tercatat,
        COALESCE(SUM(CASE WHEN kendaraan IN ($hijau) THEN jumlah ELSE 0 END), 0) AS orang_hijau
        FROM reservasi WHERE tanggal BETWEEN ? AND ? AND $aktif", [$awal, $akhir]);
    $orangLalu = (int) q1("SELECT COALESCE(SUM(jumlah), 0) AS n FROM reservasi WHERE tanggal BETWEEN ? AND ? AND $aktif", [$awalLalu, $akhirLalu])['n'];
    $batal = (int) q1("SELECT COUNT(*) AS n FROM reservasi WHERE tanggal BETWEEN ? AND ? AND status = 'Dibatalkan'", [$awal, $akhir])['n'];
    $menunggu = (int) q1("SELECT COUNT(*) AS n FROM reservasi WHERE status = 'Menunggu'")['n'];
    $kupon = q1("SELECT COUNT(kupon) AS terbit, COUNT(kupon_dipakai_pada) AS dipakai FROM reservasi WHERE tanggal BETWEEN ? AND ?", [$awal, $akhir]);
    $ulasan = q1('SELECT COUNT(*) AS n, AVG(bintang) AS rata FROM ulasan WHERE dibuat_pada BETWEEN ? AND ?', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);

    // Layanan warga
    $rentang = [$awal . ' 00:00:00', $akhir . ' 23:59:59'];
    $aspirasiMasuk = q('SELECT kategori, COUNT(*) AS n FROM aspirasi WHERE dibuat_pada BETWEEN ? AND ? GROUP BY kategori ORDER BY n DESC', $rentang);
    $aspirasiSelesai = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status = 'Selesai' AND diperbarui_pada BETWEEN ? AND ?", $rentang)['n'];
    $aspirasiTerbuka = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status IN ('Diterima', 'Diproses')")['n'];
    $aspirasiLama = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE status = 'Diterima' AND dibuat_pada < ?", [date('Y-m-d H:i:s', strtotime('-3 days'))])['n'];
    $pesan = (int) q1('SELECT COUNT(*) AS n FROM pesan WHERE dibuat_pada BETWEEN ? AND ?', $rentang)['n'];
    $permintaanData = (int) q1('SELECT COUNT(*) AS n FROM permintaan_data WHERE dibuat_pada BETWEEN ? AND ?', $rentang)['n'];
    // Fase 12: surat online, antrean, bansos (angka saja, tanpa nama)
    $surat = q('SELECT layanan_nama, COUNT(*) AS n FROM surat WHERE dibuat_pada BETWEEN ? AND ? GROUP BY layanan_nama ORDER BY n DESC', $rentang);
    $suratBelum = (int) q1("SELECT COUNT(*) AS n FROM surat WHERE status IN ('Diterima', 'Diproses')")['n'];
    $antrean = q1("SELECT COUNT(*) AS n, COUNT(DISTINCT tanggal) AS hari FROM antrean WHERE tanggal BETWEEN ? AND ?", [$awal, $akhir]);
    $bansos = [];
    foreach (q('SELECT * FROM bantuan WHERE aktif = 1 ORDER BY id') as $b) {
        $rb = ringkas_bansos($b);
        if ($rb['total'] === 0) continue;
        $bansos[] = ['nama' => $b['nama'], 'kuota' => (int) $b['kuota'], 'total' => $rb['total'], 'tahap' => $rb['tahap'],
            'bulan_ini' => (int) q1('SELECT COUNT(*) AS n FROM bansos_penerima WHERE bantuan_id = ? AND terakhir_salur BETWEEN ? AND ?', [$b['id'], $awal, $akhir])['n']];
    }
    $pesananUmkm = q1("SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS nilai FROM pesanan_umkm WHERE status <> 'Dibatalkan' AND dibuat_pada BETWEEN ? AND ?", $rentang);
    $aduBansos = (int) q1("SELECT COUNT(*) AS n FROM aspirasi WHERE kategori LIKE 'Bansos%' AND dibuat_pada BETWEEN ? AND ?", $rentang)['n'];

    // Lingkungan dan pembangunan
    $sampah = q('SELECT jenis, SUM(berat_kg) AS kg, SUM(nilai_rupiah) AS rp FROM sampah WHERE bulan BETWEEN ? AND ? GROUP BY jenis ORDER BY kg DESC', [$awal, $akhir]);
    $proyek = q("SELECT nama, progres, status, anggaran FROM pembangunan WHERE status <> 'Selesai' OR tahun = ? ORDER BY CASE status WHEN 'Berjalan' THEN 1 WHEN 'Perencanaan' THEN 2 ELSE 3 END, id", [(int) substr($bulan, 0, 4)]);
    $tahunApb = (int) (q1('SELECT MAX(tahun) AS t FROM apbdes')['t'] ?? 0);
    $apb = $tahunApb ? q("SELECT jenis, COALESCE(SUM(anggaran), 0) AS a, COALESCE(SUM(realisasi), 0) AS r FROM apbdes WHERE tahun = ? GROUP BY jenis", [$tahunApb]) : [];

    return compact('bulan', 'awal', 'akhir', 'perWisata', 'kunjungan', 'orangLalu', 'batal', 'menunggu', 'kupon', 'ulasan',
        'aspirasiMasuk', 'aspirasiSelesai', 'aspirasiTerbuka', 'aspirasiLama', 'pesan', 'permintaanData', 'surat', 'suratBelum', 'antrean', 'bansos', 'aduBansos', 'pesananUmkm', 'sampah', 'proyek', 'tahunApb', 'apb');
}

// Hal yang perlu diperhatikan kepala desa, disusun otomatis dari angka
function catatan_laporan(array $d): array
{
    $c = [];
    $orang = (int) $d['kunjungan']['orang'];
    if ($d['orangLalu'] > 0) {
        $ubah = round(($orang - $d['orangLalu']) / $d['orangLalu'] * 100);
        $c[] = $ubah >= 0 ? "Pengunjung naik $ubah% dibanding bulan sebelumnya." : 'Pengunjung turun ' . abs($ubah) . '% dibanding bulan sebelumnya.';
    }
    if ($d['aspirasiLama'] > 0) $c[] = "Ada {$d['aspirasiLama']} aspirasi warga yang lebih dari 3 hari belum ditanggapi.";
    if ($d['menunggu'] > 0) $c[] = "Ada {$d['menunggu']} reservasi yang masih menunggu konfirmasi petugas.";
    if (($d['suratBelum'] ?? 0) > 0) $c[] = "Ada {$d['suratBelum']} pengajuan surat online yang belum siap.";
    if (($d['aduBansos'] ?? 0) > 0) $c[] = "Ada {$d['aduBansos']} laporan atau usulan warga soal penerima bansos. Bahas di musyawarah desa berikutnya.";
    foreach ($d['bansos'] ?? [] as $b) {
        $akhirTahap = (int) end($b['tahap']);
        if ($akhirTahap < $b['total'] && $akhirTahap > 0) $c[] = 'Bansos ' . $b['nama'] . ': ' . ($b['total'] - $akhirTahap) . ' penerima belum menerima tahap terakhir.';
    }
    $tercatat = (int) $d['kunjungan']['tercatat'];
    if ($tercatat > 0) {
        $p = persen((float) $d['kunjungan']['orang_hijau'], (float) $tercatat);
        $c[] = $p >= 50 ? 'Lebih dari separuh pengunjung datang dengan transport hijau (' . str_replace('.', ',', (string) $p) . '%).' : 'Baru ' . str_replace('.', ',', (string) $p) . '% pengunjung datang dengan transport hijau. Promosi Tiket Hijau ke sekolah dan komunitas bisa ditingkatkan.';
    }
    $lambat = array_filter($d['proyek'], fn($p) => $p['status'] === 'Berjalan' && (int) $p['progres'] < 50);
    if ($lambat) $c[] = count($lambat) . ' proyek pembangunan berjalan masih di bawah 50%: ' . implode(', ', array_column($lambat, 'nama')) . '.';
    if (!$c) $c[] = 'Tidak ada hal mendesak bulan ini.';
    return $c;
}

// Susun laporan sebagai HTML dengan gaya tertulis langsung (inline), supaya rapi di email dan saat dicetak
function html_laporan(array $d): string
{
    $th = 'style="text-align:left;padding:6px 8px;border-bottom:2px solid #0B3C5D;font-size:13px"';
    $td = 'style="padding:6px 8px;border-bottom:1px solid #E1E6D8;font-size:13px"';
    $tdr = 'style="padding:6px 8px;border-bottom:1px solid #E1E6D8;font-size:13px;text-align:right;white-space:nowrap"';
    $h3 = 'style="font-size:16px;margin:22px 0 8px;color:#0B3C5D"';
    $tabel = function (array $kepala, array $baris, array $kanan = []) use ($th, $td, $tdr) {
        $h = '<table style="width:100%;border-collapse:collapse;margin-bottom:6px"><tr>';
        foreach ($kepala as $i => $k) $h .= '<th ' . (in_array($i, $kanan, true) ? str_replace('text-align:left', 'text-align:right', $th) : $th) . '>' . e($k) . '</th>';
        $h .= '</tr>';
        foreach ($baris as $b) {
            $h .= '<tr>';
            foreach (array_values($b) as $i => $v) $h .= '<td ' . (in_array($i, $kanan, true) ? $tdr : $td) . '>' . e((string) $v) . '</td>';
            $h .= '</tr>';
        }
        return $h . '</table>';
    };
    $kotak = fn(string $label, string $nilai, string $ket = '') => '<td style="width:25%;padding:10px;background:#F2F5EC;border:4px solid #fff;vertical-align:top">'
        . '<div style="font-size:12px;color:#475663">' . e($label) . '</div><div style="font-size:20px;font-weight:bold;margin:2px 0">' . e($nilai) . '</div>'
        . ($ket ? '<div style="font-size:11px;color:#475663">' . e($ket) . '</div>' : '') . '</td>';

    $k = $d['kunjungan'];
    $orang = (int) $k['orang'];
    $tercatat = (int) $k['tercatat'];
    $pendapatan = array_sum(array_column($d['perWisata'], 'pendapatan'));
    $jumlahAspirasi = array_sum(array_column($d['aspirasiMasuk'], 'n'));
    $judulBulan = tgl($d['awal'], 'bulantahun');

    $h = '<div style="font-family:Arial,sans-serif;color:#0E1B24;line-height:1.5">';
    $h .= '<p style="margin:0 0 4px;font-size:13px;color:#475663">Laporan bulanan ' . e(setting('nama_desa')) . ', ' . e(setting('kecamatan')) . ', ' . e(setting('kabupaten')) . '</p>';
    $h .= '<h2 style="margin:0 0 4px;font-size:22px">' . e($judulBulan) . '</h2>';
    $h .= '<p style="margin:0 0 14px;font-size:12px;color:#475663">Disusun otomatis dari data website pada ' . e(tgl(date('Y-m-d'))) . ' pukul ' . date('H.i') . ' WIB. Tidak memuat nama atau kontak warga.</p>';

    $h .= '<table style="width:100%;border-collapse:collapse"><tr>'
        . $kotak('Pengunjung', angka($orang) . ' orang', (int) $k['rombongan'] . ' reservasi')
        . $kotak('Perkiraan pendapatan tiket', rupiah_ringkas($pendapatan), 'harga tiket x pengunjung')
        . $kotak('Aspirasi warga masuk', (string) $jumlahAspirasi, $d['aspirasiSelesai'] . ' selesai bulan ini')
        . $kotak('Transport hijau', $tercatat ? str_replace('.', ',', (string) persen((float) $k['orang_hijau'], (float) $tercatat)) . '%' : '-', 'dari pengunjung tercatat')
        . '</tr></table>';

    $h .= '<h3 ' . $h3 . '>Perlu perhatian</h3><ul style="margin:0;padding-left:20px;font-size:14px">';
    foreach (catatan_laporan($d) as $c) $h .= '<li style="margin-bottom:4px">' . e($c) . '</li>';
    $h .= '</ul>';

    $h .= '<h3 ' . $h3 . '>1. Wisata</h3>';
    $h .= $tabel(['Destinasi', 'Reservasi', 'Pengunjung', 'Perkiraan pendapatan'],
        array_map(fn($w) => [$w['nama'], $w['rombongan'], angka($w['orang']), rupiah($w['pendapatan'])], $d['perWisata']), [1, 2, 3]);
    $h .= '<p style="font-size:12px;color:#475663;margin:4px 0 0">Bulan sebelumnya: ' . angka($d['orangLalu']) . ' pengunjung. Dibatalkan bulan ini: ' . $d['batal'] . ' reservasi.'
        . ((int) $d['ulasan']['n'] ? ' Ulasan baru: ' . (int) $d['ulasan']['n'] . ', rata-rata ' . number_format((float) $d['ulasan']['rata'], 1, ',', '.') . ' dari 5.' : '') . '</p>';

    $h .= '<h3 ' . $h3 . '>2. Lingkungan</h3>';
    $h .= $tabel(['Ukuran', 'Nilai'], [
        ['Jejak karbon perjalanan pengunjung', angka(round((float) $k['kg'])) . ' kg CO2e'],
        ['Rata-rata per pengunjung', $tercatat ? angka(round((float) $k['kg'] / $tercatat, 1)) . ' kg CO2e' : '-'],
        ['Tiket Hijau terbit / ditukar di UMKM', (int) $d['kupon']['terbit'] . ' / ' . (int) $d['kupon']['dipakai']],
        ['Perkiraan belanja Tiket Hijau ke UMKM', rupiah((int) $d['kupon']['dipakai'] * (int) setting('tiket_hijau_nilai'))],
    ], [1]);
    if ($d['sampah']) {
        $h .= $tabel(['Bank sampah: jenis', 'Berat', 'Nilai tabungan warga'],
            array_map(fn($s) => [$s['jenis'], angka(round((float) $s['kg'])) . ' kg', rupiah((int) $s['rp'])], $d['sampah']), [1, 2]);
    } else {
        $h .= '<p style="font-size:13px;color:#475663">Data bank sampah bulan ini belum diisi.</p>';
    }

    $h .= '<h3 ' . $h3 . '>3. Layanan warga</h3>';
    $h .= $tabel(['Layanan', 'Jumlah'], [
        ['Aspirasi dan pengaduan masuk', $jumlahAspirasi],
        ['Aspirasi selesai ditindaklanjuti bulan ini', $d['aspirasiSelesai']],
        ['Aspirasi yang masih terbuka (semua bulan)', $d['aspirasiTerbuka']],
        ['Pesan dari halaman Kontak', $d['pesan']],
        ['Permintaan data pribadi', $d['permintaanData']],
    ], [1]);
    if ($d['aspirasiMasuk']) {
        $h .= '<p style="font-size:12px;color:#475663;margin:4px 0 0">Kategori aspirasi: ' . e(implode(', ', array_map(fn($a) => $a['kategori'] . ' (' . $a['n'] . ')', $d['aspirasiMasuk']))) . '.</p>';
    }

    $h .= '<h3 ' . $h3 . '>4. Surat, antrean, dan bansos</h3>';
    $h .= $tabel(['Layanan', 'Jumlah'], array_merge(
        array_map(fn($x) => ['Pengajuan ' . $x['layanan_nama'], (int) $x['n']], $d['surat']),
        [['Pengajuan surat yang belum siap (semua bulan)', $d['suratBelum']],
         ['Nomor antrean diambil di kantor desa', (int) $d['antrean']['n'] . ' dalam ' . (int) $d['antrean']['hari'] . ' hari layanan'],
         ['Laporan atau usulan soal penerima bansos', $d['aduBansos']],
         ['Pesanan produk UMKM lewat website', (int) ($d['pesananUmkm']['n'] ?? 0) . ' pesanan, ' . rupiah((int) ($d['pesananUmkm']['nilai'] ?? 0))]]
    ), [1]);
    if ($d['bansos']) {
        $h .= $tabel(['Program bansos', 'Penerima', 'Tersalur per tahap', 'Disalurkan bulan ini'],
            array_map(fn($b) => [$b['nama'], $b['total'] . ($b['kuota'] ? ' / kuota ' . $b['kuota'] : ''), implode(', ', array_map(fn($t, $n) => "T$t: $n", array_keys($b['tahap']), $b['tahap'])), $b['bulan_ini']], $d['bansos']), [1, 3]);
    }

    $h .= '<h3 ' . $h3 . '>5. Pembangunan dan anggaran</h3>';
    if ($d['proyek']) {
        $h .= $tabel(['Proyek', 'Status', 'Progres', 'Anggaran'], array_map(fn($p) => [$p['nama'], $p['status'], (int) $p['progres'] . '%', rupiah($p['anggaran'])], $d['proyek']), [2, 3]);
    }
    if ($d['apb']) {
        $h .= $tabel(['APBDes ' . $d['tahunApb'], 'Anggaran', 'Realisasi', 'Persen'],
            array_map(fn($a) => [$a['jenis'], rupiah($a['a']), rupiah($a['r']), persen_id((float) $a['r'], (float) $a['a']) . '%'], $d['apb']), [1, 2, 3]);
    }

    $h .= '<p style="font-size:12px;color:#475663;margin-top:22px">Detail lengkap ada di CMS website. Laporan ini bisa dicetak atau disimpan sebagai PDF dari halaman CMS > Laporan bulanan.</p></div>';
    return $h;
}

// Kirim laporan ke alamat di Pengaturan (bisa lebih dari satu, pisahkan koma). Hasil: true atau teks penyebab gagal.
function kirim_laporan(string $bulan): true|string
{
    require_once __DIR__ . '/email.php';
    $tujuan = array_filter(array_map('trim', explode(',', setting('email_laporan'))), fn($x) => filter_var($x, FILTER_VALIDATE_EMAIL));
    if (!$tujuan) return 'alamat email penerima laporan belum diisi di Pengaturan';
    $judul = 'Laporan bulanan ' . tgl($bulan . '-01', 'bulantahun');
    $html = bungkus_email($judul, html_laporan(data_laporan($bulan)));
    $gagal = [];
    foreach ($tujuan as $ke) {
        $hasil = kirim_email($ke, $judul . ' - ' . setting('nama_desa'), $html);
        if ($hasil !== true) $gagal[] = "$ke ($hasil)";
    }
    return $gagal ? 'gagal ke ' . implode(', ', $gagal) : true;
}
