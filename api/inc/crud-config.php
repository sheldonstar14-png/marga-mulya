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
            ['foto', 'Foto utama', 'foto', []],
            ['aktif', 'Tampilkan di website', 'check', []],
        ],
        'baru' => ['jenis' => 'Alam', 'harga' => 10000, 'kuota' => 50, 'aktif' => 1],
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
            ['harga', 'Harga (Rp)', 'number', ['min' => 0]],
            ['satuan', 'Satuan', 'text', ['wajib' => true, 'maks' => 40, 'contoh' => '250 g, buah, botol']],
            ['pemilik', 'Pemilik dan alamat singkat', 'text', ['wajib' => true, 'maks' => 150]],
            ['wa', 'Nomor WhatsApp', 'text', ['wajib' => true, 'maks' => 20, 'contoh' => '081234567890']],
            ['peta', 'Lokasi (klik peta)', 'peta', []],
            ['foto', 'Foto produk', 'foto', []],
        ],
        'baru' => ['kategori' => 'Makanan', 'harga' => 10000],
        'sebelumSimpan' => function (array &$data, ?int $id): void {
            $data['wa'] = nomor_wa($data['wa']); // simpan dalam format 62...
        },
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
