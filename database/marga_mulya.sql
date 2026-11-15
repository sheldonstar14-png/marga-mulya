-- =========================================================
-- Database Website Desa Marga Mulya (IIT EcoQuest 2026)
-- Isi: semua tabel + data contoh (dummy).
-- Cara pakai: jalankan seluruh file ini di database marga_mulya.
-- AMAN dijalankan ulang: tabel lama dihapus lalu dibuat lagi,
-- jadi semua data akan kembali ke data contoh.
-- =========================================================

DROP TABLE IF EXISTS reservasi;
DROP TABLE IF EXISTS tes_koneksi;
DROP TABLE IF EXISTS pengaturan;
DROP TABLE IF EXISTS aparat;
DROP TABLE IF EXISTS statistik;
DROP TABLE IF EXISTS wisata;
DROP TABLE IF EXISTS budaya;
DROP TABLE IF EXISTS umkm;
DROP TABLE IF EXISTS agenda;
DROP TABLE IF EXISTS berita;
DROP TABLE IF EXISTS galeri;
DROP TABLE IF EXISTS pesan;
DROP TABLE IF EXISTS admin;

-- ---------- Pengaturan umum & profil (kunci -> nilai) ----------
CREATE TABLE pengaturan (
  kunci VARCHAR(50) PRIMARY KEY,
  nilai TEXT NOT NULL
);

-- ---------- Perangkat desa ----------
CREATE TABLE aparat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jabatan VARCHAR(100) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  urutan INT NOT NULL DEFAULT 0
);

-- ---------- Satu tabel untuk semua angka statistik ----------
CREATE TABLE statistik (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori VARCHAR(60) NOT NULL,
  label VARCHAR(120) NOT NULL,
  nilai DECIMAL(12,2) NOT NULL,
  satuan VARCHAR(30) NOT NULL,
  urutan INT NOT NULL DEFAULT 0
);

-- ---------- Destinasi wisata ----------
CREATE TABLE wisata (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE,
  nama VARCHAR(150) NOT NULL,
  jenis VARCHAR(40) NOT NULL,
  harga INT NOT NULL DEFAULT 0,
  kuota INT NOT NULL DEFAULT 50,
  jam_buka VARCHAR(60) NOT NULL,
  ringkas VARCHAR(255) NOT NULL,
  deskripsi TEXT NOT NULL,
  fasilitas VARCHAR(255) NOT NULL DEFAULT '',
  tips VARCHAR(255) NOT NULL DEFAULT '',
  lat DECIMAL(9,6) NOT NULL,
  lng DECIMAL(9,6) NOT NULL,
  foto VARCHAR(255) NULL,
  aktif TINYINT NOT NULL DEFAULT 1
);

-- ---------- Budaya & kesenian ----------
CREATE TABLE budaya (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  waktu VARCHAR(100) NOT NULL,
  isi TEXT NOT NULL
);

-- ---------- UMKM & produk lokal ----------
CREATE TABLE umkm (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_usaha VARCHAR(150) NOT NULL,
  kategori VARCHAR(40) NOT NULL,
  produk VARCHAR(150) NOT NULL,
  harga INT NOT NULL DEFAULT 0,
  satuan VARCHAR(40) NOT NULL,
  pemilik VARCHAR(150) NOT NULL,
  wa VARCHAR(20) NOT NULL,
  lat DECIMAL(9,6) NULL,
  lng DECIMAL(9,6) NULL,
  foto VARCHAR(255) NULL
);

-- ---------- Agenda kegiatan ----------
CREATE TABLE agenda (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  tanggal DATE NOT NULL,
  jam VARCHAR(60) NOT NULL,
  tempat VARCHAR(150) NOT NULL,
  isi TEXT NOT NULL
);

-- ---------- Berita & pengumuman ----------
CREATE TABLE berita (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  tanggal DATE NOT NULL,
  isi TEXT NOT NULL,
  foto VARCHAR(255) NULL
);

-- ---------- Galeri foto ----------
CREATE TABLE galeri (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  kategori VARCHAR(40) NOT NULL,
  foto VARCHAR(255) NOT NULL
);

-- ---------- Reservasi kunjungan wisata ----------
CREATE TABLE reservasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  wisata_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jumlah INT NOT NULL,
  nama VARCHAR(150) NOT NULL,
  wa VARCHAR(20) NOT NULL,
  catatan VARCHAR(255) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'Menunggu',
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wisata_id) REFERENCES wisata(id)
);

-- ---------- Pesan dari halaman Kontak ----------
CREATE TABLE pesan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  kontak VARCHAR(150) NOT NULL,
  topik VARCHAR(60) NOT NULL,
  isi TEXT NOT NULL,
  dibaca TINYINT NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Akun admin CMS ----------
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  peran VARCHAR(20) NOT NULL DEFAULT 'Operator'
);

-- =========================================================
-- DATA CONTOH
-- =========================================================

INSERT INTO pengaturan (kunci, nilai) VALUES
('nama_desa', 'Desa Marga Mulya'),
('kecamatan', 'Kecamatan Sukamaju'),
('kabupaten', 'Kabupaten Harapan'),
('hero_judul', 'Rencanakan hari terbaikmu di Marga Mulya'),
('hero_teks', 'Desa agraris di lereng bukit dengan sawah bertingkat, curug, dan kampung anyaman bambu. Cek cuaca, pilih tanggal, lalu pesan kunjungan langsung dari sini.'),
('alamat', 'Jl. Raya Marga Mulya No. 1'),
('jam_layanan', 'Senin sampai Jumat, 08.00 sampai 15.00 WIB'),
('email', 'pemdes@margamulya.contoh.id'),
('wa', '6280000000100'),
('lat', '-7.205'),
('lng', '107.570'),
('profil_ringkas', 'Marga Mulya adalah desa agraris seluas 642 hektare, dihuni 4.812 jiwa dalam 1.436 kepala keluarga. Sebagian besar warganya bertani padi, berkebun jeruk, dan membuat kerajinan anyaman bambu.'),
('sejarah', 'Nama Marga Mulya berarti jalan menuju kemakmuran. Desa ini dibuka para petani pada awal abad ke-20 yang membangun sawah bertingkat mengikuti kontur bukit. Saluran air dari mata air Cikaso yang mereka buat masih dipakai sampai sekarang.'),
('visi', 'Terwujudnya Desa Marga Mulya yang mandiri, sehat, dan sejahtera melalui pertanian berkelanjutan dan wisata berbasis masyarakat.'),
('misi', 'Meningkatkan hasil dan nilai jual pertanian warga.\nMengembangkan desa wisata yang dikelola warga sendiri.\nMemperluas layanan kesehatan ibu dan anak di setiap RW.\nMembuka informasi dan layanan desa secara daring.'),
('batas_utara', 'Desa Sukamanah'),
('batas_timur', 'Desa Cikadu'),
('batas_selatan', 'Hutan lindung Gunung Pasir'),
('batas_barat', 'Desa Mekarjaya'),
('kredit_foto', '');

INSERT INTO aparat (jabatan, nama, urutan) VALUES
('Kepala Desa', 'Hj. Siti Rahmawati, S.Sos.', 1),
('Sekretaris Desa', 'Agus Setiawan, S.E.', 2),
('Kaur Keuangan', 'Rina Lestari', 3),
('Kaur Perencanaan', 'Dedi Kurniawan', 4),
('Kasi Pemerintahan', 'Bambang Wijaya', 5),
('Kasi Kesejahteraan', 'Nurul Hidayah', 6),
('Kasi Pelayanan', 'Yusuf Maulana', 7);

INSERT INTO statistik (kategori, label, nilai, satuan, urutan) VALUES
('Kependudukan', 'Laki-laki', 2431, 'jiwa', 1),
('Kependudukan', 'Perempuan', 2381, 'jiwa', 2),
('Kependudukan', 'Kepala keluarga', 1436, 'KK', 3),
('Kependudukan', 'Usia kerja (18-56 tahun)', 2904, 'jiwa', 4),
('Kependudukan', 'Pencari kerja terdaftar', 187, 'jiwa', 5),
('Mata pencaharian', 'Petani', 1124, 'jiwa', 1),
('Mata pencaharian', 'Buruh tani', 486, 'jiwa', 2),
('Mata pencaharian', 'Karyawan swasta', 341, 'jiwa', 3),
('Mata pencaharian', 'Pedagang', 263, 'jiwa', 4),
('Mata pencaharian', 'Pengrajin', 212, 'jiwa', 5),
('Mata pencaharian', 'Guru', 64, 'jiwa', 6),
('Mata pencaharian', 'PNS', 58, 'jiwa', 7),
('Mata pencaharian', 'TNI/Polri', 17, 'jiwa', 8),
('Mata pencaharian', 'Lainnya', 209, 'jiwa', 9),
('Pendidikan penduduk', 'Belum/tidak sekolah', 612, 'jiwa', 1),
('Pendidikan penduduk', 'SD/sederajat', 1538, 'jiwa', 2),
('Pendidikan penduduk', 'SMP/sederajat', 1104, 'jiwa', 3),
('Pendidikan penduduk', 'SMA/SMK', 1207, 'jiwa', 4),
('Pendidikan penduduk', 'Diploma', 143, 'jiwa', 5),
('Pendidikan penduduk', 'Sarjana ke atas', 208, 'jiwa', 6),
('Sekolah', 'PAUD/TK', 3, 'unit', 1),
('Sekolah', 'SD/MI', 4, 'unit', 2),
('Sekolah', 'SMP/MTs', 1, 'unit', 3),
('Sekolah', 'SMA/SMK', 1, 'unit', 4),
('Sekolah', 'Lembaga kursus', 2, 'unit', 5),
('Kesehatan', 'Imunisasi dasar lengkap', 94, '%', 1),
('Kesehatan', 'Balita gizi baik', 91, '%', 2),
('Kesehatan', 'KK dengan air bersih', 88, '%', 3),
('Kesehatan', 'KK dengan jamban sehat', 85, '%', 4),
('Fasilitas kesehatan', 'Posyandu', 6, 'unit', 1),
('Fasilitas kesehatan', 'Puskesmas pembantu', 1, 'unit', 2),
('Fasilitas kesehatan', 'Praktik bidan', 2, 'unit', 3),
('Fasilitas kesehatan', 'Apotek', 1, 'unit', 4),
('Fasilitas kesehatan', 'Bidan', 3, 'orang', 5),
('Fasilitas kesehatan', 'Perawat', 2, 'orang', 6),
('Pertanian', 'Padi', 1840, 'ton/tahun', 1),
('Pertanian', 'Jagung', 320, 'ton/tahun', 2),
('Pertanian', 'Buah jeruk', 260, 'ton/tahun', 3),
('Pertanian', 'Ketela', 210, 'ton/tahun', 4),
('Pertanian', 'Sayuran', 145, 'ton/tahun', 5),
('Peternakan', 'Ayam', 6200, 'ekor', 1),
('Peternakan', 'Itik', 840, 'ekor', 2),
('Peternakan', 'Kambing', 538, 'ekor', 3),
('Peternakan', 'Sapi', 214, 'ekor', 4),
('Ekonomi', 'UMKM makanan', 41, 'usaha', 1),
('Ekonomi', 'UMKM kerajinan', 28, 'usaha', 2),
('Ekonomi', 'UMKM olahan tani', 19, 'usaha', 3),
('Ekonomi', 'Koperasi', 2, 'unit', 4),
('Ekonomi', 'BUMDes', 1, 'unit', 5),
('Ekonomi', 'Pasar tradisional', 1, 'unit', 6),
('Jalan desa', 'Aspal kondisi baik', 8.4, 'km', 1),
('Jalan desa', 'Beton', 3.6, 'km', 2),
('Jalan desa', 'Makadam', 2.9, 'km', 3),
('Jalan desa', 'Aspal rusak', 2.1, 'km', 4),
('Jalan desa', 'Tanah', 1.7, 'km', 5),
('Lahan', 'Sawah', 268, 'ha', 1),
('Lahan', 'Perkebunan', 154, 'ha', 2),
('Lahan', 'Pemukiman', 118, 'ha', 3),
('Lahan', 'Pekarangan', 46, 'ha', 4),
('Lahan', 'Prasarana umum', 44, 'ha', 5),
('Lahan', 'Perkuburan', 6, 'ha', 6),
('Lahan', 'Taman', 4, 'ha', 7),
('Lahan', 'Perkantoran', 2, 'ha', 8),
('Pemerintahan', 'Aparat desa', 12, 'orang', 1),
('Pemerintahan', 'RW', 4, 'RW', 2),
('Pemerintahan', 'RT', 19, 'RT', 3),
('Pemerintahan', 'Anggota Linmas', 24, 'orang', 4),
('Pemerintahan', 'Pos kamling', 11, 'pos', 5);

INSERT INTO wisata (slug, nama, jenis, harga, kuota, jam_buka, ringkas, deskripsi, fasilitas, tips, lat, lng, foto, aktif) VALUES
('terasering', 'Terasering Sawah Sigarung', 'Alam', 10000, 120, '06.00 sampai 17.00', 'Sawah bertingkat dengan jalur jalan kaki 2 km dan gardu pandang.', 'Sawah bertingkat seluas 40 hektare yang menjadi ikon desa. Pengunjung bisa menyusuri pematang bersama petani, melihat sistem pengairan tradisional, dan menikmati matahari terbit dari gardu pandang di puncak.', 'Parkir, Toilet, Gardu pandang, Pemandu lokal', 'Datang sebelum jam 08.00 untuk kabut pagi dan cahaya terbaik.', -7.200, 107.559, '/assets/img/sawah.svg', 1),
('curug', 'Curug Cikaso Mulya', 'Alam', 15000, 80, '07.00 sampai 16.00', 'Air terjun setinggi 25 meter, 20 menit jalan kaki dari parkiran.', 'Air terjun yang bersumber dari mata air Cikaso, sumber pengairan sawah desa. Kolam di bawahnya dangkal dan jernih. Jalur ditutup sementara saat hujan deras demi keselamatan pengunjung.', 'Parkir, Toilet, Warung, Ruang bilas', 'Pakai alas kaki anti licin. Hindari datang saat peluang hujan tinggi.', -7.198, 107.579, '/assets/img/air.svg', 1),
('anyaman', 'Kampung Anyaman Bambu', 'Budaya', 35000, 30, '08.00 sampai 15.00', 'Belajar menganyam bersama pengrajin, hasil karya dibawa pulang.', 'Lebih dari 200 warga menjadi pengrajin anyaman. Di kelas 2 jam ini pengunjung belajar membuat tatakan atau keranjang kecil. Harga tiket sudah termasuk bahan.', 'Ruang workshop, Toilet, Galeri produk', 'Cocok untuk rombongan sekolah. Kegiatan di dalam ruangan, aman saat hujan.', -7.211, 107.569, '/assets/img/anyaman.svg', 1),
('jeruk', 'Agrowisata Petik Jeruk', 'Agrowisata', 25000, 60, '08.00 sampai 16.00', 'Petik jeruk langsung dari pohon, 1 kg pertama termasuk tiket.', 'Kebun jeruk milik kelompok tani Mulya Sejahtera. Pengunjung memetik sendiri dan bisa membeli tambahan dengan harga petani. Musim panen raya sekitar Juni sampai Agustus.', 'Parkir, Toilet, Gazebo, Kios oleh-oleh', 'Bawa tas belanja sendiri untuk mengurangi sampah plastik.', -7.212, 107.582, '/assets/img/jeruk.svg', 1);

INSERT INTO budaya (judul, waktu, isi) VALUES
('Merti Desa', 'Setiap bulan Desember', 'Syukuran hasil panen dengan kirab hasil bumi keliling desa dan makan bersama warga.'),
('Kesenian Jaranan', 'Pentas tiap malam Minggu pertama', 'Tari kuda kepang diiringi gamelan, dimainkan sanggar pemuda Marga Mulya.'),
('Tandur Bareng', 'Awal musim tanam', 'Wisatawan diajak menanam padi bersama petani di Terasering Sigarung.');

INSERT INTO umkm (nama_usaha, kategori, produk, harga, satuan, pemilik, wa, lat, lng, foto) VALUES
('Keripik Singkong Bu Darmi', 'Makanan', 'Keripik singkong pedas manis', 15000, '250 g', 'Bu Darmi, RT 03', '6280000000101', -7.204, 107.566, '/assets/img/kirab.svg'),
('Anyaman Rukun', 'Kerajinan', 'Keranjang bambu serbaguna', 65000, 'buah', 'Kelompok Rukun, RW 02', '6280000000102', -7.212, 107.570, '/assets/img/anyaman2.svg'),
('Sirup Jeruk Mulya', 'Olahan tani', 'Sirup jeruk murni tanpa pengawet', 28000, 'botol 500 ml', 'KWT Mulya Sejahtera', '6280000000103', -7.214, 107.581, '/assets/img/jeruk.svg'),
('Beras Sigarung', 'Olahan tani', 'Beras pandan wangi organik', 75000, '5 kg', 'Gapoktan Sigarung', '6280000000104', -7.203, 107.561, '/assets/img/sawah2.svg'),
('Tikar Pandan Mak Ijah', 'Kerajinan', 'Tikar pandan motif tradisional', 120000, 'lembar', 'Mak Ijah, RT 07', '6280000000105', -7.210, 107.563, '/assets/img/anyaman.svg'),
('Rengginang Kang Ujang', 'Makanan', 'Rengginang ketan terasi', 20000, '300 g', 'Kang Ujang, RT 11', '6280000000106', -7.204, 107.576, '/assets/img/kirab.svg');

-- Tanggal agenda dihitung dari hari ini, jadi agenda selalu terlihat "akan datang"
INSERT INTO agenda (judul, tanggal, jam, tempat, isi) VALUES
('Posyandu Melati', DATE_ADD(CURDATE(), INTERVAL 4 DAY), '08.00 sampai 11.00', 'Balai RW 01', 'Penimbangan balita, imunisasi, dan konsultasi gizi bersama bidan desa.'),
('Kerja bakti saluran irigasi', DATE_ADD(CURDATE(), INTERVAL 8 DAY), '07.00 sampai 10.00', 'Blok sawah Sigarung', 'Membersihkan saluran air sebelum musim tanam. Terbuka untuk semua warga.'),
('Pelatihan anyaman untuk pemuda', DATE_ADD(CURDATE(), INTERVAL 15 DAY), '13.00 sampai 16.00', 'Kampung Anyaman', 'Kelas gratis 4 pertemuan, kuota 20 peserta.'),
('Merti Desa 2026', DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'Mulai 07.00', 'Lapangan desa', 'Kirab hasil bumi, pentas jaranan, dan pasar produk UMKM.');

INSERT INTO berita (judul, tanggal, isi, foto) VALUES
('Jalan menuju Curug Cikaso selesai dibeton', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Ruas 1,2 km dari balai desa ke parkiran curug kini bisa dilalui minibus.', NULL),
('Cakupan imunisasi dasar capai 94 persen', DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Naik 6 poin dibanding tahun lalu berkat jadwal posyandu keliling di setiap RW.', NULL),
('Pendaftaran bantuan bibit jeruk dibuka', DATE_SUB(CURDATE(), INTERVAL 11 DAY), 'Kelompok tani bisa mendaftar di kantor desa sampai akhir bulan.', NULL);

INSERT INTO galeri (judul, kategori, foto) VALUES
('Kabut pagi di Terasering Sigarung', 'Alam', '/assets/img/sawah.svg'),
('Kolam jernih Curug Cikaso', 'Alam', '/assets/img/air.svg'),
('Pengrajin anyaman RW 02', 'Budaya', '/assets/img/anyaman.svg'),
('Kirab Merti Desa 2025', 'Kegiatan', '/assets/img/kirab.svg'),
('Posyandu Melati bulan lalu', 'Kegiatan', '/assets/img/kegiatan.svg'),
('Panen raya jeruk', 'Alam', '/assets/img/jeruk.svg'),
('Tandur bareng wisatawan', 'Kegiatan', '/assets/img/sawah2.svg'),
('Motif tikar pandan', 'Budaya', '/assets/img/anyaman2.svg');

INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status) VALUES
('MM-0426', 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, 'Rizky Hidayat', '081200000006', '', 'Dibatalkan'),
('MM-0427', 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 4, 'Dinda Pramesti', '081200000001', '', 'Dikonfirmasi'),
('MM-0428', 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 6, 'Rombongan SMA 2', '081200000002', 'Butuh pemandu', 'Menunggu'),
('MM-0429', 3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 25, 'SD Harapan Bangsa', '081200000003', 'Kelas 5, didampingi 3 guru', 'Menunggu'),
('MM-0430', 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 3, 'Fajar Nugroho', '081200000004', '', 'Selesai'),
('MM-0431', 1, DATE_ADD(CURDATE(), INTERVAL 6 DAY), 2, 'Maya Anggraini', '081200000005', '', 'Menunggu');

INSERT INTO pesan (nama, kontak, topik, isi, dibaca) VALUES
('Andi', 'andi@contoh.id', 'Wisata', 'Apakah curug bisa dikunjungi rombongan 40 orang?', 0),
('Bu Sri', '081200000007', 'Layanan administrasi', 'Syarat membuat surat keterangan usaha apa saja?', 1);

-- Akun demo. Kata sandi keduanya: desa123 (ganti sebelum website dipakai sungguhan)
INSERT INTO admin (nama, username, password_hash, peran) VALUES
('Hj. Siti Rahmawati', 'admin', '$2y$10$oDepwP6RccgOG1ORMWFG5.wkuxKKwL.yBOaKPZAL.fKWqual9rNNS', 'Super admin'),
('Yusuf Maulana', 'operator1', '$2y$10$oDepwP6RccgOG1ORMWFG5.wkuxKKwL.yBOaKPZAL.fKWqual9rNNS', 'Operator');
