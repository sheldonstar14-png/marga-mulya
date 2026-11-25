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

// Dua isian sumber foto, dipakai di semua menu yang punya foto. Tampil otomatis di "Sumber dan kredit foto".
$fieldSumber = [
    ['sumber_foto', 'Sumber foto (siapa pemotret atau dari mana fotonya)', 'text', ['maks' => 150, 'contoh' => 'Dokumentasi tim, Pinterest akun @nama, atau izin Pak Udin']],
    ['sumber_url', 'Link sumber foto (opsional)', 'text', ['maks' => 500, 'contoh' => 'https://pin.it/...']],
];
$cekLink = fn(array $data) => ($data['sumber_url'] ?? '') !== '' && !preg_match('#^https?://#', $data['sumber_url']) ? 'Link sumber foto harus diawali https://.' : null;

$KATEGORI_STATISTIK = ['Kependudukan', 'Mata pencaharian', 'Angkatan kerja', 'Status perkawinan', 'Pernikahan dan perceraian setahun', 'Pemeluk agama', 'Tempat ibadah', 'Kelompok seni dan budaya',
    'Pemerintahan', 'Aparat desa menurut jenis kelamin', 'Aparat desa menurut pendidikan', 'Aparat desa menurut status', 'Aparat kecamatan',
    'Pendidikan penduduk', 'Sekolah', 'Sekolah menurut status', 'Murid per jenjang', 'Guru per jenjang', 'Ruang kelas per jenjang', 'Pendidikan nonformal',
    'Kesehatan', 'Fasilitas kesehatan', 'Tenaga kesehatan', 'Status gizi balita', 'Imunisasi balita', 'Keluarga berencana', 'Peserta KB menurut alat',
    'Lahan', 'Sumber air minum keluarga', 'Jamban keluarga', 'Jenis rumah', 'Bahan bakar memasak',
    'Perikanan', 'Pertanian', 'Pengairan sawah', 'Peternakan', 'Ekonomi', 'Industri menurut skala', 'Lembaga keuangan', 'Sarana perdagangan', 'Pajak dan listrik',
    'Jalan desa', 'Jalan menurut kelas', 'Kondisi jalan'];

$CRUD = [
    'wisata' => [
        'judul' => 'Kelola wisata', 'satu' => 'destinasi', 'tabel' => 'wisata', 'urut' => 'id',
        'kolom' => ['foto' => 'Foto', 'nama' => 'Destinasi', 'jenis' => 'Jenis', 'harga' => 'Harga', 'aktif' => 'Tampil'],
        'format' => ['foto' => 'foto', 'harga' => 'rupiah', 'aktif' => 'yatidak'],
        'field' => [
            ['nama', 'Nama destinasi', 'text', ['wajib' => true, 'maks' => 150]],
            ['jenis', 'Jenis', 'select', ['pilihan' => ['Alam', 'Budaya', 'Agrowisata', 'Edukasi', 'Kuliner', 'Religi'], 'bisaTambah' => true]],
            ['harga', 'Harga tiket per orang (Rp). Isi 0 kalau gratis', 'number', ['min' => 0]],
            ['jam_buka', 'Jam buka', 'text', ['wajib' => true, 'maks' => 60, 'contoh' => '08.00 sampai 16.00']],
            ['waktu_terbaik', 'Waktu terbaik berkunjung (opsional)', 'text', ['maks' => 255, 'contoh' => 'Pagi 06.00 sampai 09.00 saat air surut']],
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
            ...$fieldSumber,
            ['saat_hujan', 'Tetap nyaman saat hujan (kegiatan di dalam ruangan). Disarankan otomatis saat prakiraan hujan', 'check', []],
            ['aktif', 'Tampilkan di website', 'check', []],
        ],
        'baru' => ['jenis' => 'Alam', 'harga' => 10000, 'aktif' => 1, 'tingkat_jalur' => 'Mudah', 'ada_toilet' => 1],
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
            ? 'Link ulasan Google Maps harus diawali https://.' : $cekLink($data),
        'cekHapus' => fn(array $baris, array $admin) => q1('SELECT id FROM reservasi WHERE wisata_id = ?', [$baris['id']])
            ? 'Destinasi ini sudah punya data reservasi, jadi tidak bisa dihapus. Hilangkan centang "Tampilkan di website" lewat Edit untuk menyembunyikannya.'
            : null,
    ],

    'budaya' => [
        'judul' => 'Budaya dan kesenian', 'satu' => 'kegiatan budaya', 'tabel' => 'budaya', 'urut' => 'urutan, id',
        'catatan' => 'Tampil di halaman Wisata dan budaya serta beranda. Centang "Ilustrasi" untuk isi atau gambar contoh; lepas centangnya setelah diganti cerita dan foto asli dari warga.',
        'kolom' => ['foto' => 'Gambar', 'judul' => 'Nama', 'jenis' => 'Jenis', 'waktu' => 'Waktu'],
        'format' => ['foto' => 'foto'],
        'field' => [
            ['judul', 'Nama tradisi, kesenian, atau kuliner', 'text', ['wajib' => true, 'maks' => 150]],
            ['jenis', 'Jenis', 'select', ['pilihan' => ['Tradisi pesisir', 'Tradisi keagamaan', 'Kesenian musik', 'Kesenian religi', 'Bela diri dan adat', 'Gotong royong', 'Kuliner'], 'bisaTambah' => true]],
            ['waktu', 'Waktu pelaksanaan', 'text', ['wajib' => true, 'maks' => 100, 'contoh' => 'Setiap bulan Desember']],
            ['isi', 'Cerita singkat', 'textarea', ['wajib' => true]],
            ['foto', 'Foto atau gambar', 'foto', []],
            ...$fieldSumber,
            ['ilustrasi', 'Ilustrasi (isi atau gambar masih contoh)', 'check', []],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['jenis' => 'Tradisi pesisir', 'urutan' => 10, 'ilustrasi' => 0],
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => $cekLink($data),
    ],

    'umkm' => [
        'judul' => 'Kelola UMKM', 'satu' => 'produk UMKM', 'tabel' => 'umkm', 'urut' => 'id',
        'kolom' => ['foto' => 'Foto', 'produk' => 'Produk', 'nama_usaha' => 'Usaha', 'kategori' => 'Kategori', 'harga' => 'Harga'],
        'format' => ['foto' => 'foto', 'harga' => 'rupiah'],
        'field' => [
            ['nama_usaha', 'Nama usaha', 'text', ['wajib' => true, 'maks' => 150]],
            ['kategori', 'Kategori', 'select', ['pilihan' => KATEGORI_UMKM, 'bisaTambah' => true]],
            ['produk', 'Nama produk', 'text', ['wajib' => true, 'maks' => 150]],
            ['deskripsi', 'Cerita singkat usaha dan produk (opsional, tampil di kartu UMKM)', 'textarea', ['maks' => 600]],
            ['harga', 'Harga (Rp)', 'number', ['min' => 0]],
            ['satuan', 'Satuan', 'text', ['wajib' => true, 'maks' => 40, 'contoh' => '250 g, buah, botol']],
            ['pemilik', 'Pemilik dan alamat singkat', 'text', ['wajib' => true, 'maks' => 150]],
            ['wa', 'Nomor WhatsApp', 'text', ['wajib' => true, 'maks' => 20, 'contoh' => '081234567890']],
            ['izin', 'Izin usaha (opsional)', 'text', ['maks' => 150, 'contoh' => 'PIRT 1234567890123, halal dalam proses']],
            ['pengiriman', 'Cara pengiriman', 'text', ['maks' => 150, 'contoh' => 'Ambil di rumah, antar sekitar desa, atau kirim paket']],
            ['peta', 'Lokasi (klik peta)', 'peta', []],
            ['foto', 'Foto produk', 'foto', []],
            ...$fieldSumber,
        ],
        'baru' => ['kategori' => 'Makanan', 'harga' => 10000],
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => $cekLink($data),
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
        'catatan' => 'Isi "Butuh relawan" dengan angka lebih dari 0 kalau kegiatan ini mengajak warga atau pengunjung ikut gotong royong. Tombol "Saya ikut" lalu muncul di halaman Agenda dan beranda.',
        'kolom' => ['tanggal' => 'Tanggal', 'judul' => 'Kegiatan', 'tempat' => 'Tempat', 'relawan_kuota' => 'Butuh relawan'],
        'format' => ['tanggal' => 'tanggal'],
        'field' => [
            ['judul', 'Nama kegiatan', 'text', ['wajib' => true, 'maks' => 150]],
            ['tanggal', 'Tanggal', 'date', ['wajib' => true]],
            ['jam', 'Jam', 'text', ['wajib' => true, 'maks' => 60, 'contoh' => '08.00 sampai 11.00']],
            ['tempat', 'Tempat', 'text', ['wajib' => true, 'maks' => 150]],
            ['isi', 'Keterangan', 'textarea', ['wajib' => true]],
            ['relawan_kuota', 'Butuh relawan (orang). Isi 0 kalau tidak membuka pendaftaran relawan', 'number', ['min' => 0, 'max' => 1000]],
            ['perlengkapan', 'Yang perlu dibawa relawan (opsional)', 'text', ['maks' => 255, 'contoh' => 'Sepatu bot, topi, botol minum']],
        ],
        'baru' => ['relawan_kuota' => 0],
        'tombolTambahan' => ['/admin/relawan', 'Lihat daftar relawan'],
    ],

    'kreditfoto' => [
        'judul' => 'Sumber & kredit foto', 'satu' => 'sumber', 'tabel' => 'kredit_foto', 'urut' => 'urutan, id',
        'catatan' => 'Daftar ini tampil saat pengunjung menekan "Sumber dan kredit" di bagian bawah website. Wajib diisi untuk setiap foto atau data yang bukan milik tim, sesuai aturan lomba tentang atribusi aset.',
        'kolom' => ['judul' => 'Judul', 'pembuat' => 'Pembuat', 'lisensi' => 'Lisensi', 'urutan' => 'Urutan'],
        'field' => [
            ['judul', 'Judul foto atau aset', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Perahu nelayan di Pantai Pasir Putih']],
            ['pembuat', 'Pembuat atau pemilik', 'text', ['maks' => 150, 'contoh' => 'Nama fotografer']],
            ['sumber_url', 'Alamat sumber (link)', 'text', ['maks' => 500, 'contoh' => 'https://commons.wikimedia.org/wiki/File:...']],
            ['lisensi', 'Lisensi', 'text', ['maks' => 80, 'contoh' => 'CC BY-SA 4.0, karya sendiri, izin pemilik']],
            ['lisensi_url', 'Link lisensi (opsional)', 'text', ['maks' => 300]],
            ['keterangan', 'Keterangan (opsional)', 'text', ['maks' => 300]],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['urutan' => 10],
    ],

    'survei' => [
        'judul' => 'Survei kepuasan layanan', 'satu' => 'penilaian', 'tabel' => 'survei', 'urut' => 'id DESC',
        'catatan' => 'Penilaian warga setelah surat atau aspirasinya selesai (1 = sangat buruk, 5 = sangat baik). Rangkumannya tampil otomatis di website. Centang "Tampilkan komentar" untuk komentar yang pantas dibaca publik.',
        'kolom' => ['jenis' => 'Jenis', 'layanan' => 'Layanan', 'nilai' => 'Nilai', 'komentar' => 'Komentar', 'tampil' => 'Tampil'],
        'format' => ['tampil' => 'yatidak'],
        'field' => [
            ['tampil', 'Tampilkan komentar di website (tanpa nama)', 'check', []],
        ],
        'tanpaTambah' => true,
    ],

    'relawan' => [
        'judul' => 'Relawan gotong royong', 'satu' => 'relawan', 'tabel' => 'relawan', 'urut' => 'agenda_id DESC, id',
        'catatan' => 'Warga dan pengunjung yang mendaftar lewat tombol "Saya ikut" di halaman Agenda. Hubungi lewat WhatsApp sehari sebelum kegiatan. Data dihapus otomatis 30 hari setelah kegiatan lewat.',
        'saring' => 'agenda_judul', 'saringTurun' => false,
        'kolom' => ['agenda_judul' => 'Kegiatan', 'nama' => 'Nama', 'peran' => 'Bisa bantu', 'asal' => 'Asal', 'wa' => 'WhatsApp'],
        'field' => [
            ['nama', 'Nama', 'text', ['wajib' => true, 'maks' => 60]],
            ['peran', 'Bisa membantu sebagai', 'text', ['maks' => 60]],
            ['asal', 'Asal (RT, kelompok, atau pengunjung)', 'text', ['maks' => 60]],
            ['wa', 'Nomor WhatsApp', 'text', ['wajib' => true, 'maks' => 20]],
        ],
        'tanpaTambah' => true,
    ],

    'berita' => [
        'judul' => 'Kelola berita', 'satu' => 'berita', 'tabel' => 'berita', 'urut' => 'tanggal DESC, id DESC',
        'catatan' => 'Tulis berita dengan pola: apa yang terjadi, kapan dan di mana, siapa yang terlibat, lalu apa yang perlu dilakukan warga. Pisahkan paragraf dengan satu baris kosong; paragraf pertama tampil sebagai ringkasan.',
        'kolom' => ['foto' => 'Foto', 'tanggal' => 'Tanggal', 'judul' => 'Judul', 'kategori' => 'Kategori'],
        'format' => ['foto' => 'foto', 'tanggal' => 'tanggal'],
        'field' => [
            ['judul', 'Judul berita', 'text', ['wajib' => true, 'maks' => 200]],
            ['kategori', 'Kategori', 'select', ['pilihan' => ['Pemerintahan', 'Pelayanan', 'Perikanan', 'Pertanian', 'Kesehatan', 'Lingkungan', 'Budaya', 'UMKM', 'Wisata', 'Siaga bencana'], 'bisaTambah' => true]],
            ['tanggal', 'Tanggal terbit', 'date', ['wajib' => true]],
            ['isi', 'Isi berita', 'textarea', ['wajib' => true]],
            ['foto', 'Foto (opsional)', 'foto', []],
            ...$fieldSumber,
        ],
        'baru' => ['kategori' => 'Pemerintahan'],
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => $cekLink($data),
    ],

    'galeri' => [
        'judul' => 'Kelola galeri', 'satu' => 'foto', 'tabel' => 'galeri', 'urut' => 'id DESC',
        'kolom' => ['foto' => 'Foto', 'judul' => 'Keterangan', 'kategori' => 'Kategori'],
        'format' => ['foto' => 'foto'],
        'field' => [
            ['foto', 'File foto', 'foto', ['wajib' => true]],
            ['judul', 'Keterangan foto', 'text', ['wajib' => true, 'maks' => 200]],
            ['kategori', 'Kategori', 'select', ['pilihan' => ['Alam', 'Budaya', 'Kegiatan'], 'bisaTambah' => true]],
            ...$fieldSumber,
        ],
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => $cekLink($data),
    ],

    'statistik' => [
        'judul' => 'Statistik desa', 'satu' => 'angka statistik', 'tabel' => 'statistik', 'urut' => 'kategori, urutan, id',
        'saring' => 'kategori', // tampilkan tombol pilihan per kategori
        'tombolTambahan' => ['/admin/import-statistik', 'Import atau export CSV'],
        'catatan' => 'Semua angka di halaman Data Desa dan Profil diambil dari sini. Untuk persentase, isi satuan dengan %. Centang "Data ilustrasi" untuk angka contoh; lepas centangnya setelah diganti angka resmi desa.',
        'kolom' => ['kategori' => 'Kategori', 'label' => 'Label', 'nilai' => 'Nilai', 'satuan' => 'Satuan', 'urutan' => 'Urutan'],
        'format' => ['nilai' => 'angka'],
        'field' => [
            ['kategori', 'Kategori', 'select', ['pilihan' => $KATEGORI_STATISTIK, 'bisaTambah' => true]],
            ['label', 'Label', 'text', ['wajib' => true, 'maks' => 120, 'contoh' => 'Petani']],
            ['nilai', 'Nilai', 'number', ['min' => 0, 'desimal' => true]],
            ['satuan', 'Satuan', 'text', ['wajib' => true, 'maks' => 30, 'contoh' => 'jiwa, unit, %']],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
            ['ilustrasi', 'Data ilustrasi (belum data resmi)', 'check', []],
        ],
        'baru' => ['kategori' => 'Kependudukan', 'urutan' => 10, 'ilustrasi' => 0],
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
            ...$fieldSumber,
            ['peta', 'Titik lokasi di peta desa (opsional)', 'peta', []],
            ['sdg', 'Tujuan SDGs yang didukung (pilih 1 sampai 3)', 'sdg', []],
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
            ['kategori', 'Jenis program', 'select', ['pilihan' => ['Sosial', 'Pendidikan', 'Kesehatan', 'Nelayan', 'Petani', 'Usaha'], 'bisaTambah' => true]],
            ['deskripsi', 'Penjelasan singkat program', 'textarea', []],
            ['manfaat', 'Manfaat yang diterima', 'text', ['maks' => 255, 'contoh' => 'Uang tunai Rp300.000 per bulan']],
            ['sasaran', 'Sasaran penerima', 'text', ['wajib' => true, 'maks' => 255]],
            ['syarat', 'Syarat (satu baris satu syarat)', 'textarea', ['wajib' => true]],
            ['jadwal', 'Jadwal', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Pendaftaran Januari sampai Februari']],
            ['cara_daftar', 'Cara mendaftar', 'textarea', ['wajib' => true]],
            ['kuota', 'Kuota penerima di desa', 'number', ['min' => 0, 'max' => 100000]],
            ['jumlah_tahap', 'Jumlah tahap penyaluran', 'number', ['min' => 1, 'max' => 12]],
            ['selesai', 'Tanggal program selesai (kosongkan kalau masih berjalan)', 'date', []],
            ['tautan', 'Link situs resmi untuk cek kepesertaan (opsional)', 'text', ['maks' => 255, 'contoh' => 'https://cekbansos.kemensos.go.id']],
            ['sdg', 'Tujuan SDGs yang didukung (pilih 1 sampai 3)', 'sdg', []],
            ['ilustrasi', 'Ilustrasi (jadwal, kuota, atau besaran masih contoh)', 'check', []],
            ['aktif', 'Tampilkan di website', 'check', []],
        ],
        'baru' => ['aktif' => 1, 'kuota' => 0, 'jumlah_tahap' => 1, 'kategori' => 'Sosial'],
        'cekSimpan' => fn(array $data, ?int $id, array $admin) => ($data['tautan'] ?? '') !== '' && !str_starts_with($data['tautan'], 'https://') ? 'Link situs resmi harus diawali https://.' : null,
        'tombolTambahan' => ['/admin/bansos', 'Kelola penerima bansos'],
    ],

    'loker' => [
        'judul' => 'Kerja dan pelatihan', 'satu' => 'peluang', 'tabel' => 'loker', 'urut' => 'batas, id',
        'catatan' => 'Peluang kerja dari warga masuk dengan status Menunggu. Periksa dulu (hubungi nomornya, pastikan tidak memungut biaya), lalu ubah status menjadi Tayang. Lowongan perusahaan hanya boleh diteruskan dari sumber resmi seperti Karirhub Kemnaker atau Disnaker, dengan link sumbernya. Peluang hilang otomatis dari website setelah tanggal batas.',
        'saring' => 'status', 'saringTurun' => true,
        'kolom' => ['judul' => 'Peluang', 'jenis' => 'Jenis', 'pemberi' => 'Pemberi', 'batas' => 'Batas', 'status' => 'Status'],
        'format' => ['batas' => 'tanggal'],
        'field' => [
            ['status', 'Status', 'select', ['pilihan' => ['Menunggu', 'Tayang', 'Ditolak', 'Selesai']]],
            ['catatan', 'Catatan untuk pengaju (tampil di Cek pengajuan, wajib kalau ditolak)', 'text', ['maks' => 255]],
            ['jenis', 'Jenis', 'select', ['pilihan' => ['Kerja di desa', 'Lowongan resmi', 'Pelatihan']]],
            ['judul', 'Pekerjaan atau pelatihan', 'text', ['wajib' => true, 'maks' => 150]],
            ['pemberi', 'Pemberi kerja atau penyelenggara', 'text', ['wajib' => true, 'maks' => 150]],
            ['lokasi', 'Lokasi', 'text', ['wajib' => true, 'maks' => 150]],
            ['waktu_kerja', 'Waktu kerja', 'select', ['pilihan' => ['Harian', 'Paruh waktu', 'Akhir pekan', 'Musiman', 'Kontrak', 'Penuh waktu', 'Pelatihan']]],
            ['upah', 'Upah (opsional)', 'text', ['maks' => 100]],
            ['kebutuhan', 'Dibutuhkan (orang). Isi 0 kalau tidak dibatasi', 'number', ['min' => 0, 'max' => 10000]],
            ['syarat', 'Syarat (satu baris satu syarat)', 'textarea', []],
            ['cara_daftar', 'Cara daftar', 'text', ['wajib' => true, 'maks' => 255, 'contoh' => 'Hubungi lewat WhatsApp, atau lamar di Karirhub']],
            ['wa', 'Nomor WhatsApp pemberi kerja (kosongkan untuk lowongan resmi)', 'text', ['maks' => 20]],
            ['tautan', 'Link sumber resmi (wajib untuk lowongan resmi)', 'text', ['maks' => 255, 'contoh' => 'https://karirhub.kemnaker.go.id/...']],
            ['batas', 'Batas pendaftaran', 'date', ['wajib' => true]],
            ['ilustrasi', 'Ilustrasi (contoh untuk lomba)', 'check', []],
        ],
        'baru' => ['status' => 'Tayang', 'jenis' => 'Lowongan resmi', 'waktu_kerja' => 'Kontrak', 'kebutuhan' => 1, 'ilustrasi' => 0],
        'cekSimpan' => function (array $data, ?int $id, array $admin): ?string {
            if (($data['tautan'] ?? '') !== '' && !str_starts_with($data['tautan'], 'https://')) return 'Link sumber harus diawali https://.';
            if (($data['jenis'] ?? '') === 'Lowongan resmi' && ($data['tautan'] ?? '') === '') return 'Lowongan resmi wajib mencantumkan link sumber resminya.';
            if (($data['status'] ?? '') === 'Ditolak' && trim($data['catatan'] ?? '') === '') return 'Tulis alasan penolakan di kolom catatan supaya pengaju tahu.';
            if (($data['wa'] ?? '') !== '' && !preg_match('/^628\d{7,11}$/', nomor_wa($data['wa']))) return 'Nomor WhatsApp belum benar. Contoh: 081234567890.';
            return null;
        },
        'sebelumSimpan' => function (array &$data, ?int $id): void {
            if (($data['wa'] ?? '') !== '') $data['wa'] = nomor_wa($data['wa']);
            if ($id === null) $data['kode'] = kode_unik('LKR', 'loker');
        },
    ],

    'lingkungan' => [
        'judul' => 'Lingkungan pesisir', 'satu' => 'kartu lingkungan', 'tabel' => 'lingkungan', 'urut' => 'urutan, id',
        'catatan' => 'Kartu di bagian "Menjaga pesisir" pada beranda. Judul, teks pembuka, dan SDGs bagian ini diatur di Pengaturan > Teks halaman.',
        'kolom' => ['judul' => 'Judul', 'info' => 'Keterangan bawah', 'aktif' => 'Tampil'],
        'field' => [
            ['judul', 'Judul kartu', 'text', ['wajib' => true, 'maks' => 100]],
            ['isi', 'Isi singkat', 'textarea', ['wajib' => true, 'maks' => 300]],
            ['ikon', 'Ikon', 'select', ['pilihan' => ['Mangrove', 'Sampah', 'Ombak', 'Cuaca', 'Pohon', 'Air']]],
            ['tautan', 'Tautan saat kartu diklik (misalnya /agenda)', 'text', ['maks' => 150, 'contoh' => '/agenda']],
            ['info', 'Keterangan di bawah kartu', 'select', ['pilihan' => ['Teks bebas', 'Agenda lingkungan berikutnya', 'Jumlah kawasan rawan di peta', 'Sumber data cuaca']]],
            ['info_teks', 'Teks bebas (dipakai kalau keterangan bawah = Teks bebas, atau saat belum ada agenda)', 'text', ['maks' => 120]],
            ['aktif', 'Tampilkan di beranda', 'check', []],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['ikon' => 'Mangrove', 'info' => 'Teks bebas', 'aktif' => 1, 'urutan' => 10],
    ],

    'lembaga' => [
        'judul' => 'Lembaga desa', 'satu' => 'lembaga', 'tabel' => 'lembaga', 'urut' => 'urutan, id',
        'catatan' => 'Lembaga kemasyarakatan dan kelompok warga beserta kegiatan rutinnya. Tampil di halaman Profil.',
        'kolom' => ['nama' => 'Lembaga', 'jenis' => 'Jenis', 'jadwal' => 'Kegiatan rutin', 'anggota' => 'Anggota'],
        'field' => [
            ['nama', 'Nama lembaga atau kelompok', 'text', ['wajib' => true, 'maks' => 150]],
            ['jenis', 'Jenis', 'select', ['pilihan' => ['Lembaga pemerintahan desa', 'Lembaga kemasyarakatan', 'Kepemudaan', 'Kelompok usaha', 'Kesehatan dan keluarga', 'Keamanan', 'Keagamaan', 'Ekonomi desa', 'Pariwisata'], 'bisaTambah' => true]],
            ['kegiatan', 'Kegiatan utama', 'text', ['maks' => 255, 'wajib' => true]],
            ['jadwal', 'Jadwal kegiatan rutin', 'text', ['maks' => 150, 'contoh' => 'Setiap Sabtu malam']],
            ['anggota', 'Jumlah anggota (isi 0 kalau tidak tahu)', 'number', ['min' => 0, 'max' => 100000]],
            ['ilustrasi', 'Ilustrasi (belum dikonfirmasi ke lembaga)', 'check', []],
            ['urutan', 'Urutan tampil', 'number', ['min' => 0]],
        ],
        'baru' => ['jenis' => 'Lembaga kemasyarakatan', 'anggota' => 0, 'urutan' => 10, 'ilustrasi' => 0],
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
            ['nama', 'Nama zona', 'text', ['wajib' => true, 'maks' => 150, 'contoh' => 'Pesisir Pantai Pasir Putih']],
            ['jenis', 'Jenis bahaya', 'select', ['pilihan' => ['Banjir', 'Rob dan abrasi', 'Angin kencang', 'Longsor', 'Kebakaran lahan'], 'bisaTambah' => true]],
            ['radius_m', 'Radius area (meter)', 'number', ['min' => 30, 'max' => 2000]],
            ['keterangan', 'Apa yang perlu diwaspadai warga', 'text', ['maks' => 255, 'wajib' => true]],
            ['peta', 'Titik tengah zona (klik peta)', 'peta', []],
        ],
        'baru' => ['jenis' => 'Banjir', 'radius_m' => 150],
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
$CRUD['asisten'] = [
    'judul' => 'Asisten desa (menu pilihan)', 'satu' => 'pilihan', 'tabel' => 'asisten_menu', 'urut' => 'induk_id, urutan, id',
    'catatan' => 'Asisten Desa bekerja seperti layanan telepon: pengunjung menekan nomor pilihan, lalu muncul pilihan berikutnya atau jawaban. Pilihan dengan "Induk" = Menu awal tampil pertama. Isi "Jawaban" untuk pilihan terakhir; kosongkan kalau pilihan itu hanya membuka pilihan lain. Maksimal 7 pilihan per layar supaya mudah dibaca.',
    'kolom' => ['judul' => 'Pilihan', 'induk_id' => 'Induk', 'urutan' => 'Urutan', 'aktif' => 'Aktif'],
    'format' => ['induk_id' => 'induk', 'aktif' => 'yatidak'],
    'field' => [
        ['judul', 'Teks pilihan (singkat)', 'text', ['wajib' => true, 'maks' => 120, 'contoh' => 'Surat keterangan domisili']],
        ['induk_id', 'Induk (muncul setelah pilihan ini ditekan)', 'induk', ['catatan' => 'Pilih "Menu awal" untuk pilihan utama.']],
        ['pertanyaan', 'Pertanyaan lanjutan (opsional, tampil di atas pilihan berikutnya)', 'text', ['maks' => 200, 'contoh' => 'Surat apa yang Anda perlukan?']],
        ['jawaban', 'Jawaban (opsional, untuk pilihan terakhir)', 'textarea', []],
        ['tautan', 'Link tombol aksi (opsional)', 'text', ['maks' => 255, 'contoh' => '/data-desa?grup=Layanan+surat#surat']],
        ['label_tautan', 'Teks tombol aksi (opsional)', 'text', ['maks' => 60, 'contoh' => 'Ajukan surat sekarang']],
        ['urutan', 'Urutan', 'number', ['min' => 0, 'max' => 999]],
        ['aktif', 'Aktif', 'check', []],
    ],
    'baru' => ['urutan' => 10, 'aktif' => 1, 'induk_id' => 0],
];
