<?php
// =========================================================
// PENGATURAN MENU KELOLA KONTEN
// Semua menu (wisata, UMKM, berita, dll) memakai satu halaman yang sama
// (pages/admin/crud.php). Yang berbeda hanya pengaturan di file ini:
//   tabel  = nama tabel database
//   kolom  = kolom yang tampil di daftar
//   field  = isian form: [nama kolom, label, jenis, pilihan tambahan]
// Jenis field: text, textarea, number, date, select, check, foto, peta, password
// =========================================================

$KATEGORI_STATISTIK = ['Kependudukan', 'Mata pencaharian', 'Pendidikan penduduk', 'Sekolah', 'Kesehatan', 'Fasilitas kesehatan', 'Pertanian', 'Peternakan', 'Ekonomi', 'Jalan desa', 'Lahan', 'Pemerintahan'];

$CRUD = [
    'wisata' => [
        'judul' => 'Kelola wisata', 'satu' => 'destinasi', 'tabel' => 'wisata', 'urut' => 'id',
        'kolom' => ['foto' => 'Foto', 'nama' => 'Destinasi', 'jenis' => 'Jenis', 'harga' => 'Harga', 'kuota' => 'Kuota/hari', 'aktif' => 'Tampil'],
        'format' => ['foto' => 'foto', 'harga' => 'rupiah', 'aktif' => 'yatidak'],
        'field' => [
            ['nama', 'Nama destinasi', 'text', ['wajib' => true, 'maks' => 150]],
            ['jenis', 'Jenis', 'select', ['pilihan' => ['Alam', 'Budaya', 'Agrowisata', 'Edukasi'], 'bisaTambah' => true]],
            ['harga', 'Harga tiket per orang (Rp)', 'number', ['min' => 0]],
            ['kuota', 'Kuota pengunjung per hari', 'number', ['min' => 1]],
            ['jam_buka', 'Jam buka', 'text', ['wajib' => true, 'maks' => 60, 'contoh' => '08.00 sampai 16.00']],
            ['ringkas', 'Ringkasan (tampil di kartu)', 'text', ['wajib' => true, 'maks' => 255]],
            ['deskripsi', 'Deskripsi lengkap', 'textarea', ['wajib' => true]],
            ['fasilitas', 'Fasilitas (pisahkan dengan koma)', 'text', ['maks' => 255, 'contoh' => 'Parkir, Toilet, Warung']],
            ['tips', 'Tips untuk pengunjung', 'text', ['maks' => 255]],
            ['kode_etik', 'Kode etik khusus destinasi ini (satu baris satu aturan, opsional)', 'textarea', ['contoh' => 'Dilarang memakai sabun di kolam']],
            ['peta', 'Lokasi (klik peta)', 'peta', []],
            ['tingkat_jalur', 'Tingkat kesulitan jalur', 'select', ['pilihan' => ['Mudah', 'Sedang', 'Menantang']]],
            ['ramah_lansia', 'Ramah lansia dan ibu hamil', 'check', []],
            ['ramah_kursi_roda', 'Bisa dicapai kursi roda', 'check', []],
            ['ada_toilet', 'Ada toilet', 'check', []],
            ['catatan_akses', 'Catatan aksesibilitas (jalur, tangga, jarak dari parkir)', 'text', ['maks' => 255]],
            ['gmaps_ulasan', 'Link ulasan Google Maps (opsional, kalau destinasi sudah terdaftar di Google Maps)', 'text', ['maks' => 255, 'contoh' => 'https://g.page/r/.../review']],
            ['foto', 'Foto utama', 'foto', []],
            ['saat_hujan', 'Tetap nyaman saat hujan (kegiatan di dalam ruangan). Disarankan otomatis saat prakiraan hujan', 'check', []],
            ['aktif', 'Tampilkan di website', 'check', []],
        ],
        'baru' => ['jenis' => 'Alam', 'harga' => 10000, 'kuota' => 50, 'aktif' => 1, 'tingkat_jalur' => 'Mudah', 'ada_toilet' => 1],
        // Sebelum disimpan: buat slug (alamat) dari nama untuk destinasi baru
        'sebelumSimpan' => function (array &$data, ?int $id): void {
            if ($id === null) {
                $dasar = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($data['nama'])), '-') ?: 'wisata';
                $slug = $dasar;
                for ($n = 2; q1('SELECT id FROM wisata WHERE slug = ?', [$slug]); $n++) {
                    $slug = $dasar . '-' . $n;
                }
                $data['slug'] = $slug;
            }
        },
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => ($data['gmaps_ulasan'] ?? '') !== '' && !str_starts_with($data['gmaps_ulasan'], 'https://')
            ? 'Link ulasan Google Maps harus diawali https://.' : null,
        'cekHapus' => fn(array $baris, array $admin) => q1('SELECT id FROM reservasi WHERE wisata_id = ?', [$baris['id']])
            ? 'Destinasi ini sudah punya data reservasi, jadi tidak bisa dihapus. Hilangkan centang "Tampilkan di website" lewat Edit untuk menyembunyikannya.'
            : null,
    ],

    'budaya' => [
        'judul' => 'Budaya dan kesenian', 'satu' => 'kegiatan budaya', 'tabel' => 'budaya', 'urut' => 'id',
        'kolom' => ['judul' => 'Nama', 'waktu' => 'Waktu'],
        'field' => [
            ['judul', 'Nama kegiatan', 'text', ['wajib' => true, 'maks' => 150]],
            ['waktu', 'Waktu pelaksanaan', 'text', ['wajib' => true, 'maks' => 100, 'contoh' => 'Setiap bulan Desember']],
            ['isi', 'Keterangan', 'textarea', ['wajib' => true]],
        ],
    ],

    'umkm' => [
        'judul' => 'Kelola UMKM', 'satu' => 'produk UMKM', 'tabel' => 'umkm', 'urut' => 'id',
        'kolom' => ['foto' => 'Foto', 'produk' => 'Produk', 'nama_usaha' => 'Usaha', 'kategori' => 'Kategori', 'harga' => 'Harga'],
        'format' => ['foto' => 'foto', 'harga' => 'rupiah'],
        'field' => [
            ['nama_usaha', 'Nama usaha', 'text', ['wajib' => true, 'maks' => 150]],
            ['kategori', 'Kategori', 'select', ['pilihan' => ['Makanan', 'Kerajinan', 'Olahan tani', 'Jasa'], 'bisaTambah' => true]],
            ['produk', 'Nama produk', 'text', ['wajib' => true, 'maks' => 150]],
            ['deskripsi', 'Cerita singkat usaha dan produk (opsional, tampil di kartu UMKM)', 'textarea', ['maks' => 600]],
            ['harga', 'Harga (Rp)', 'number', ['min' => 0]],
            ['satuan', 'Satuan', 'text', ['wajib' => true, 'maks' => 40, 'contoh' => '250 g, buah, botol']],
            ['pemilik', 'Pemilik dan alamat singkat', 'text', ['wajib' => true, 'maks' => 150]],
            ['wa', 'Nomor WhatsApp', 'text', ['wajib' => true, 'maks' => 20, 'contoh' => '081234567890']],
            ['peta', 'Lokasi (klik peta)', 'peta', []],
            ['foto', 'Foto produk', 'foto', []],
            ['pin_hash', 'Kode tukar Tiket Hijau', 'password', ['min' => 6, 'opsional' => true, 'catatan' => 'Berikan hanya ke pemilik usaha. Dipakai untuk menandai kupon Tiket Hijau sudah dipakai. Kosongkan kalau usaha ini tidak ikut Tiket Hijau.']],
        ],
        'baru' => ['kategori' => 'Makanan', 'harga' => 10000],
        'sebelumSimpan' => function (array &$data, ?int $id): void {
            $data['wa'] = nomor_wa($data['wa']); // simpan dalam format 62...
        },
    ],

    'ulasanumkm' => [
        'judul' => 'Ulasan UMKM', 'satu' => 'ulasan', 'tabel' => 'ulasan_umkm', 'urut' => 'tampil, id DESC',
        'catatan' => 'Ulasan dari pembeli produk UMKM. Ulasan baru belum tampil sampai dicentang "Tampilkan". Periksa apakah isinya wajar, tidak kasar, dan tidak berisi nomor HP atau alamat.',
        'kolom' => ['usaha' => 'Usaha', 'nama' => 'Nama', 'bintang' => 'Bintang', 'komentar' => 'Ulasan', 'tampil' => 'Tampil'],
        'format' => ['tampil' => 'yatidak'],
        'field' => [
            ['nama', 'Nama penulis', 'text', ['wajib' => true, 'maks' => 60]],
            ['bintang', 'Bintang (1 sampai 5)', 'number', ['min' => 1, 'max' => 5]],
            ['komentar', 'Ulasan', 'textarea', ['wajib' => true, 'maks' => 500]],
            ['tampil', 'Tampilkan di website', 'check', []],
        ],
        'tanpaTambah' => true,
    ],

    'agenda' => [
        'judul' => 'Kelola agenda', 'satu' => 'agenda', 'tabel' => 'agenda', 'urut' => 'tanggal DESC',
        'kolom' => ['tanggal' => 'Tanggal', 'judul' => 'Kegiatan', 'tempat' => 'Tempat'],
        'format' => ['tanggal' => 'tanggal'],
        'field' => [
            ['judul', 'Nama kegiatan', 'text', ['wajib' => true, 'maks' => 150]],
            ['tanggal', 'Tanggal', 'date', ['wajib' => true]],
            ['jam', 'Jam', 'text', ['wajib' => true, 'maks' => 60, 'contoh' => '08.00 sampai 11.00']],
            ['tempat', 'Tempat', 'text', ['wajib' => true, 'maks' => 150]],
            ['isi', 'Keterangan', 'textarea', ['wajib' => true]],
        ],
    ],

    'berita' => [
        'judul' => 'Kelola berita', 'satu' => 'berita', 'tabel' => 'berita', 'urut' => 'tanggal DESC, id DESC',
        'kolom' => ['foto' => 'Foto', 'tanggal' => 'Tanggal', 'judul' => 'Judul'],
        'format' => ['foto' => 'foto', 'tanggal' => 'tanggal'],
        'field' => [
            ['judul', 'Judul berita', 'text', ['wajib' => true, 'maks' => 200]],
            ['tanggal', 'Tanggal terbit', 'date', ['wajib' => true]],
            ['isi', 'Isi berita', 'textarea', ['wajib' => true]],
            ['foto', 'Foto (opsional)', 'foto', []],
        ],
    ],

    'galeri' => [
        'judul' => 'Kelola galeri', 'satu' => 'foto', 'tabel' => 'galeri', 'urut' => 'id DESC',
        'kolom' => ['foto' => 'Foto', 'judul' => 'Keterangan', 'kategori' => 'Kategori'],
        'format' => ['foto' => 'foto'],
        'field' => [
            ['foto', 'File foto', 'foto', ['wajib' => true]],
            ['judul', 'Keterangan foto', 'text', ['wajib' => true, 'maks' => 200]],
            ['kategori', 'Kategori', 'select', ['pilihan' => ['Alam', 'Budaya', 'Kegiatan'], 'bisaTambah' => true]],
        ],
    ],

    'statistik' => [
        'judul' => 'Statistik desa', 'satu' => 'angka statistik', 'tabel' => 'statistik', 'urut' => 'kategori, urutan, id',
        'saring' => 'kategori', // tampilkan tombol pilihan per kategori
        'tombolTambahan' => ['/admin/import-statistik', 'Import atau export CSV'],
        'catatan' => 'Semua angka di halaman Data Desa dan Profil diambil dari sini. Untuk persentase, isi satuan dengan %.',
        'kolom' => ['kategori' => 'Kategori', 'label' => 'Label', 'nilai' => 'Nilai', 'satuan' => 'Satuan', 'urutan' => 'Urutan'],
        'format' => ['nilai' => 'angka'],
        'field' => [
            ['kategori', 'Kategori', 'select', ['pilihan' => $KATEGORI_STATISTIK, 'bisaTambah' => true]],
            ['label', 'Label', 'text', ['wajib' => true, 'maks' => 120, 'contoh' => 'Petani']],
            ['nilai', 'Nilai', 'number', ['min' => 0, 'desimal' => true]],
            ['satuan', 'Satuan', 'text', ['wajib' => true, 'maks' => 30, 'contoh' => 'jiwa, unit, %']],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['kategori' => 'Kependudukan', 'urutan' => 10],
    ],

    'aparat' => [
        'judul' => 'Perangkat desa', 'satu' => 'perangkat desa', 'tabel' => 'aparat', 'urut' => 'urutan, id',
        'kolom' => ['urutan' => 'Urutan', 'jabatan' => 'Jabatan', 'nama' => 'Nama'],
        'field' => [
            ['jabatan', 'Jabatan', 'text', ['wajib' => true, 'maks' => 100]],
            ['nama', 'Nama lengkap', 'text', ['wajib' => true, 'maks' => 150]],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['urutan' => 10],
    ],

    'akun' => [
        'judul' => 'Akun admin', 'satu' => 'akun', 'tabel' => 'admin', 'urut' => 'id',
        'catatan' => 'Super admin bisa membuka semua menu. Petugas hanya bisa membuka menu yang dicentang, jadi setiap orang cukup melihat data yang ia perlukan. Kata sandi disimpan terenkripsi.',
        'kolom' => ['nama' => 'Nama', 'username' => 'Nama pengguna', 'peran' => 'Peran', 'akses' => 'Akses'],
        'format' => ['akses' => 'akses'],
        'field' => [
            ['nama', 'Nama lengkap', 'text', ['wajib' => true, 'maks' => 150]],
            ['username', 'Nama pengguna', 'text', ['wajib' => true, 'maks' => 50]],
            ['peran', 'Peran', 'select', ['pilihan' => ['Petugas', 'Super admin']]],
            ['akses', 'Menu yang boleh dibuka (untuk Petugas)', 'akses', []],
            ['password_hash', 'Kata sandi', 'password', []],
        ],
        'baru' => ['peran' => 'Petugas', 'akses' => ''],
        'sebelumSimpan' => function (array &$data, ?int $id): void {
            $data['username'] = strtolower($data['username']);
        },
        'cekSimpan' => function (array $data, ?int $id, array $admin): ?string {
            if ($id !== null && $data['peran'] !== 'Super admin' && q1('SELECT id FROM admin WHERE id = ? AND peran = ?', [$id, 'Super admin'])
                && (int) q1("SELECT COUNT(*) AS n FROM admin WHERE peran = 'Super admin'")['n'] <= 1) return 'Harus ada minimal satu Super admin.';
            if (!preg_match('/^[a-z0-9_.]{3,50}$/', $data['username'])) return 'Nama pengguna 3 sampai 50 karakter, hanya huruf kecil, angka, titik, atau garis bawah.';
            $sama = q1('SELECT id FROM admin WHERE username = ?', [$data['username']]);
            if ($sama && (int) $sama['id'] !== $id) return 'Nama pengguna sudah dipakai akun lain.';
            return null;
        },
        'cekHapus' => function (array $baris, array $admin): ?string {
            if ((int) $baris['id'] === (int) $admin['id']) return 'Akun yang sedang kamu pakai tidak bisa dihapus.';
            if ($baris['peran'] === 'Super admin' && (int) q1("SELECT COUNT(*) AS n FROM admin WHERE peran = 'Super admin'")['n'] <= 1) return 'Harus ada minimal satu Super admin.';
            return null;
        },
    ],
];

// ---------- Tambahan Fase 8: layanan warga dan transparansi ----------
$CRUD += [
    'apbdes' => [
        'judul' => 'APBDes', 'satu' => 'pos anggaran', 'tabel' => 'apbdes', 'urut' => 'jenis DESC, urutan, id',
        'saring' => 'tahun', 'saringTurun' => true,
        'catatan' => 'Anggaran Pendapatan dan Belanja Desa. Isi anggaran dan realisasi dalam rupiah tanpa titik. Halaman Informasi Desa menampilkan tahun terbaru.',
        'kolom' => ['jenis' => 'Jenis', 'uraian' => 'Uraian', 'anggaran' => 'Anggaran', 'realisasi' => 'Realisasi'],
        'format' => ['anggaran' => 'rupiah', 'realisasi' => 'rupiah'],
        'field' => [
            ['tahun', 'Tahun anggaran', 'number', ['min' => 2000, 'max' => 2100]],
            ['jenis', 'Jenis', 'select', ['pilihan' => ['Pendapatan', 'Belanja', 'Pembiayaan']]],
            ['uraian', 'Uraian', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Dana Desa']],
            ['anggaran', 'Anggaran (Rp)', 'number', ['min' => 0]],
            ['realisasi', 'Realisasi sampai saat ini (Rp)', 'number', ['min' => 0]],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['tahun' => (int) date('Y'), 'jenis' => 'Belanja', 'anggaran' => 0, 'realisasi' => 0, 'urutan' => 10],
    ],

    'pembangunan' => [
        'judul' => 'Pembangunan desa', 'satu' => 'proyek', 'tabel' => 'pembangunan', 'urut' => 'tahun DESC, id DESC',
        'catatan' => 'Proyek fisik desa beserta anggaran dan progresnya. Foto sebelum dan sesudah membuat warga bisa melihat hasilnya.',
        'kolom' => ['foto' => 'Foto', 'nama' => 'Proyek', 'tahun' => 'Tahun', 'anggaran' => 'Anggaran', 'progres' => 'Progres', 'status' => 'Status'],
        'format' => ['foto' => 'foto', 'anggaran' => 'rupiah', 'progres' => 'persen'],
        'field' => [
            ['nama', 'Nama proyek', 'text', ['wajib' => true, 'maks' => 150]],
            ['lokasi', 'Lokasi', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Dusun Cikaso, RW 04']],
            ['tahun', 'Tahun anggaran', 'number', ['min' => 2000, 'max' => 2100]],
            ['anggaran', 'Anggaran (Rp)', 'number', ['min' => 0]],
            ['sumber_dana', 'Sumber dana', 'select', ['pilihan' => ['Dana Desa', 'Alokasi Dana Desa', 'Pendapatan asli desa', 'Bantuan provinsi', 'Bantuan kabupaten', 'Swadaya warga'], 'bisaTambah' => true]],
            ['progres', 'Progres (%)', 'number', ['min' => 0, 'max' => 100]],
            ['status', 'Status', 'select', ['pilihan' => ['Perencanaan', 'Berjalan', 'Selesai']]],
            ['deskripsi', 'Keterangan', 'textarea', ['wajib' => true]],
            ['foto', 'Foto sebelum (opsional)', 'foto', []],
            ['foto_sesudah', 'Foto sesudah atau terkini (opsional)', 'foto', []],
            ['peta', 'Titik lokasi di peta desa (opsional)', 'peta', []],
        ],
        'baru' => ['tahun' => (int) date('Y'), 'anggaran' => 0, 'progres' => 0, 'status' => 'Perencanaan', 'sumber_dana' => 'Dana Desa'],
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => ($data['status'] ?? '') === 'Selesai' && (int) ($data['progres'] ?? 0) < 100
            ? 'Proyek berstatus Selesai harus berprogres 100%.' : null,
    ],

    'bantuan' => [
        'judul' => 'Program bantuan', 'satu' => 'program', 'tabel' => 'bantuan', 'urut' => 'aktif DESC, id',
        'catatan' => 'Isi jenis program, syarat, kuota, dan jumlah tahap. Nama penerima diisi di menu Penerima bansos; di website nama selalu disamarkan dan NIK tidak pernah diminta.',
        'kolom' => ['nama' => 'Program', 'penyelenggara' => 'Penyelenggara', 'jadwal' => 'Jadwal', 'aktif' => 'Tampil'],
        'format' => ['aktif' => 'yatidak'],
        'field' => [
            ['nama', 'Nama program', 'text', ['wajib' => true, 'maks' => 150]],
            ['penyelenggara', 'Penyelenggara', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Pemerintah Desa']],
            ['sasaran', 'Sasaran penerima', 'text', ['wajib' => true, 'maks' => 255]],
            ['syarat', 'Syarat (satu baris satu syarat)', 'textarea', ['wajib' => true]],
            ['jadwal', 'Jadwal', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Pendaftaran Januari sampai Februari']],
            ['cara_daftar', 'Cara mendaftar', 'textarea', ['wajib' => true]],
            ['kuota', 'Kuota penerima di desa', 'number', ['min' => 0, 'max' => 100000]],
            ['jumlah_tahap', 'Jumlah tahap penyaluran', 'number', ['min' => 1, 'max' => 12]],
            ['selesai', 'Tanggal program selesai (kosongkan kalau masih berjalan)', 'date', []],
            ['aktif', 'Tampilkan di website', 'check', []],
        ],
        'baru' => ['aktif' => 1, 'kuota' => 0, 'jumlah_tahap' => 1],
        'tombolTambahan' => ['/admin/bansos', 'Kelola penerima bansos'],
    ],

    'sampah' => [
        'judul' => 'Bank sampah', 'satu' => 'setoran', 'tabel' => 'sampah', 'urut' => 'bulan DESC, jenis',
        'catatan' => 'Isi satu baris per jenis sampah per bulan. Tanggal cukup diisi tanggal 1 bulan itu. Jadwal buka dan daftar harga diatur Super admin di menu Pengaturan.',
        'kolom' => ['bulan' => 'Bulan', 'jenis' => 'Jenis', 'berat_kg' => 'Berat (kg)', 'nilai_rupiah' => 'Nilai tabungan'],
        'format' => ['bulan' => 'bulan', 'berat_kg' => 'angka', 'nilai_rupiah' => 'rupiah'],
        'field' => [
            ['bulan', 'Bulan (pilih tanggal 1)', 'date', ['wajib' => true]],
            ['jenis', 'Jenis sampah', 'select', ['pilihan' => ['Plastik', 'Kertas', 'Logam', 'Organik (kompos)', 'Minyak jelantah'], 'bisaTambah' => true]],
            ['berat_kg', 'Berat (kg)', 'number', ['min' => 0, 'desimal' => true]],
            ['nilai_rupiah', 'Nilai tabungan warga (Rp)', 'number', ['min' => 0]],
        ],
        'baru' => ['bulan' => date('Y-m-01'), 'jenis' => 'Plastik', 'berat_kg' => 0, 'nilai_rupiah' => 0],
        'sebelumSimpan' => function (array &$data, ?int $id): void {
            $data['bulan'] = substr($data['bulan'], 0, 7) . '-01'; // selalu disimpan sebagai tanggal 1
        },
    ],

    'fasilitas' => [
        'judul' => 'Fasilitas umum', 'satu' => 'fasilitas', 'tabel' => 'fasilitas', 'urut' => 'kategori, nama',
        'saring' => 'kategori',
        'catatan' => 'Fasilitas yang punya titik lokasi tampil di peta halaman Profil.',
        'kolom' => ['nama' => 'Nama', 'kategori' => 'Kategori', 'alamat' => 'Alamat'],
        'field' => [
            ['nama', 'Nama fasilitas', 'text', ['wajib' => true, 'maks' => 150]],
            ['kategori', 'Kategori', 'select', ['pilihan' => ['Pemerintahan', 'Kesehatan', 'Pendidikan', 'Lingkungan', 'Olahraga dan ruang publik', 'Ekonomi'], 'bisaTambah' => true]],
            ['alamat', 'Alamat singkat', 'text', ['maks' => 150, 'contoh' => 'RW 02']],
            ['keterangan', 'Keterangan', 'text', ['maks' => 255, 'contoh' => 'Buka setiap Sabtu pagi']],
            ['peta', 'Lokasi (klik peta, opsional)', 'peta', []],
        ],
        'baru' => ['kategori' => 'Kesehatan'],
    ],

    'zona' => [
        'judul' => 'Zona rawan bencana', 'satu' => 'zona', 'tabel' => 'zona_rawan', 'urut' => 'jenis, nama',
        'catatan' => 'Tampil sebagai lingkaran di peta desa (halaman Profil, lapisan Rawan bencana). Saat siaga cuaca aktif, zona ini ikut disorot. Isi berdasarkan kajian BPBD atau musyawarah warga.',
        'kolom' => ['nama' => 'Nama zona', 'jenis' => 'Jenis', 'radius_m' => 'Radius (m)'],
        'format' => ['radius_m' => 'angka'],
        'field' => [
            ['nama', 'Nama zona', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Lereng menuju Curug Cikaso']],
            ['jenis', 'Jenis bahaya', 'select', ['pilihan' => ['Longsor', 'Banjir', 'Angin kencang', 'Kebakaran lahan'], 'bisaTambah' => true]],
            ['radius_m', 'Radius area (meter)', 'number', ['min' => 30, 'max' => 2000]],
            ['keterangan', 'Apa yang perlu diwaspadai warga', 'text', ['maks' => 255, 'wajib' => true]],
            ['peta', 'Titik tengah zona (klik peta)', 'peta', []],
        ],
        'baru' => ['jenis' => 'Longsor', 'radius_m' => 150],
    ],

    'darurat' => [
        'judul' => 'Nomor darurat', 'satu' => 'nomor', 'tabel' => 'darurat', 'urut' => 'urutan, id',
        'catatan' => 'Tampil di halaman Kontak dan bisa langsung ditekan untuk menelepon dari HP. Tambahkan nomor posko desa, Babinsa, atau Bhabinkamtibmas kalau ada.',
        'kolom' => ['urutan' => 'Urutan', 'nama' => 'Layanan', 'nomor' => 'Nomor'],
        'field' => [
            ['nama', 'Nama layanan', 'text', ['wajib' => true, 'maks' => 100]],
            ['nomor', 'Nomor telepon', 'text', ['wajib' => true, 'maks' => 30, 'contoh' => '112 atau 081234567890']],
            ['keterangan', 'Keterangan (opsional)', 'text', ['maks' => 150]],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['urutan' => 10],
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => !preg_match('/^[0-9+\- ]{3,30}$/', $data['nomor'] ?? '') ? 'Nomor hanya boleh berisi angka, spasi, tanda + atau -.' : null,
    ],
];

// ---------- Tambahan Fase 9 ----------
$CRUD['layanan'] = [
    'judul' => 'Layanan surat', 'satu' => 'layanan', 'tabel' => 'layanan', 'urut' => 'urutan, id',
    'catatan' => 'Daftar syarat yang tampil di pemandu "Mau mengurus apa?" (Informasi Desa > Layanan surat). Warga jadi tahu apa yang harus dibawa sebelum datang. Tulis satu syarat per baris.',
    'kolom' => ['urutan' => 'Urutan', 'nama' => 'Layanan', 'lama_proses' => 'Lama proses', 'biaya' => 'Biaya', 'aktif' => 'Tampil'],
    'format' => ['aktif' => 'yatidak'],
    'field' => [
        ['nama', 'Nama layanan', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Surat keterangan domisili']],
        ['syarat', 'Syarat yang dibawa (satu baris satu syarat)', 'textarea', ['wajib' => true]],
        ['lama_proses', 'Lama proses', 'text', ['wajib' => true, 'maks' => 100, 'contoh' => '1 hari kerja']],
        ['biaya', 'Biaya', 'text', ['wajib' => true, 'maks' => 100, 'contoh' => 'Gratis']],
        ['tempat', 'Tempat mengurus', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Kantor desa, loket pelayanan']],
        ['keterangan', 'Keterangan tambahan (opsional)', 'textarea', []],
        ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ['aktif', 'Tampilkan di website', 'check', []],
    ],
    'baru' => ['urutan' => 10, 'aktif' => 1, 'biaya' => 'Gratis', 'tempat' => 'Kantor desa, loket pelayanan'],
];

// ---------- Tambahan Fase 10 ----------
$CRUD['faq'] = [
    'judul' => 'Asisten desa (FAQ)', 'satu' => 'pertanyaan', 'tabel' => 'faq', 'urut' => 'urutan, id',
    'catatan' => 'Asisten Desa sudah otomatis menjawab soal wisata, harga, kuota, cuaca, reservasi, surat, nomor darurat, UMKM, agenda, dan anggaran dari data CMS. Tambahkan di sini pertanyaan lain yang sering ditanyakan warga atau pengunjung.',
    'kolom' => ['urutan' => 'Urutan', 'pertanyaan' => 'Pertanyaan', 'kata_kunci' => 'Kata kunci', 'aktif' => 'Aktif'],
    'format' => ['aktif' => 'yatidak'],
    'field' => [
        ['pertanyaan', 'Pertanyaan', 'text', ['wajib' => true, 'maks' => 200, 'contoh' => 'Apakah ada tempat parkir bus?']],
        ['kata_kunci', 'Kata kunci (pisahkan koma)', 'text', ['wajib' => true, 'maks' => 255, 'contoh' => 'parkir,bus,mobil,kendaraan']],
        ['jawaban', 'Jawaban', 'textarea', ['wajib' => true]],
        ['urutan', 'Urutan', 'number', ['min' => 0]],
        ['aktif', 'Aktif', 'check', []],
    ],
    'baru' => ['urutan' => 10, 'aktif' => 1],
];
