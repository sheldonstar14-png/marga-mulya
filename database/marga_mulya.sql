-- =========================================================
-- Database Website Desa Marga Mulya (IIT EcoQuest 2026)
-- Isi: semua tabel + data contoh (dummy).
-- Cara pakai: jalankan seluruh file ini di database marga_mulya.
-- AMAN dijalankan ulang: tabel lama dihapus lalu dibuat lagi,
-- jadi semua data akan kembali ke data contoh.
-- =========================================================

DROP TABLE IF EXISTS pesanan_umkm_item;
DROP TABLE IF EXISTS pesanan_umkm;
DROP TABLE IF EXISTS ulasan_umkm;
DROP TABLE IF EXISTS bansos_penerima;
DROP TABLE IF EXISTS surat;
DROP TABLE IF EXISTS antrean;
DROP TABLE IF EXISTS zona_rawan;
DROP TABLE IF EXISTS faq;
DROP TABLE IF EXISTS permintaan_data;
DROP TABLE IF EXISTS layanan;
DROP TABLE IF EXISTS aspirasi;
DROP TABLE IF EXISTS pembangunan;
DROP TABLE IF EXISTS apbdes;
DROP TABLE IF EXISTS bantuan;
DROP TABLE IF EXISTS darurat;
DROP TABLE IF EXISTS fasilitas;
DROP TABLE IF EXISTS sampah;
DROP TABLE IF EXISTS ulasan;
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
  kode_etik TEXT NULL,
  gmaps_ulasan VARCHAR(255) NULL,
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
  email VARCHAR(150) NOT NULL DEFAULT '',
  catatan VARCHAR(255) NOT NULL DEFAULT '',
  kendaraan VARCHAR(30) NOT NULL DEFAULT '',
  jarak_km INT NOT NULL DEFAULT 0,
  emisi_kg DECIMAL(8,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'Menunggu',
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (wisata_id) REFERENCES wisata(id)
);

-- ---------- Ulasan pengunjung (hanya dari reservasi yang sudah Selesai) ----------
CREATE TABLE ulasan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reservasi_id INT NOT NULL UNIQUE,
  wisata_id INT NOT NULL,
  nama VARCHAR(150) NOT NULL,
  bintang TINYINT NOT NULL,
  komentar TEXT NOT NULL,
  tampil TINYINT NOT NULL DEFAULT 1,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reservasi_id) REFERENCES reservasi(id),
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

-- ---------- Tambahan Fase 8: layanan warga dan transparansi ----------
CREATE TABLE aspirasi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(12) NOT NULL UNIQUE,
  nama VARCHAR(150) NOT NULL,
  wa VARCHAR(20) NOT NULL DEFAULT '',
  email VARCHAR(150) NOT NULL DEFAULT '',
  kategori VARCHAR(60) NOT NULL,
  lokasi VARCHAR(150) NOT NULL DEFAULT '',
  isi TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Diterima',
  tanggapan TEXT NULL,
  publik TINYINT NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada TIMESTAMP NULL
);

CREATE TABLE pembangunan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  lokasi VARCHAR(150) NOT NULL,
  tahun INT NOT NULL,
  anggaran BIGINT NOT NULL DEFAULT 0,
  sumber_dana VARCHAR(60) NOT NULL,
  progres INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'Perencanaan',
  deskripsi TEXT NOT NULL,
  foto VARCHAR(255) NULL,
  foto_sesudah VARCHAR(255) NULL
);

CREATE TABLE apbdes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tahun INT NOT NULL,
  jenis VARCHAR(20) NOT NULL,
  uraian VARCHAR(150) NOT NULL,
  anggaran BIGINT NOT NULL DEFAULT 0,
  realisasi BIGINT NOT NULL DEFAULT 0,
  urutan INT NOT NULL DEFAULT 0
);

CREATE TABLE bantuan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  penyelenggara VARCHAR(150) NOT NULL,
  sasaran VARCHAR(255) NOT NULL,
  syarat TEXT NOT NULL,
  jadwal VARCHAR(150) NOT NULL,
  cara_daftar TEXT NOT NULL,
  aktif TINYINT NOT NULL DEFAULT 1
);

CREATE TABLE darurat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  nomor VARCHAR(30) NOT NULL,
  keterangan VARCHAR(150) NOT NULL DEFAULT '',
  urutan INT NOT NULL DEFAULT 0
);

CREATE TABLE fasilitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  kategori VARCHAR(60) NOT NULL,
  alamat VARCHAR(150) NOT NULL DEFAULT '',
  keterangan VARCHAR(255) NOT NULL DEFAULT '',
  lat DECIMAL(9,6) NULL,
  lng DECIMAL(9,6) NULL
);

CREATE TABLE sampah (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bulan DATE NOT NULL,
  jenis VARCHAR(60) NOT NULL,
  berat_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  nilai_rupiah INT NOT NULL DEFAULT 0
);

-- ---------- Akun admin CMS ----------
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  peran VARCHAR(20) NOT NULL DEFAULT 'Petugas',
  akses TEXT NULL
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
('kredit_foto', ''),
('kode_etik_umum', 'Bawa botol minum dan wadah makan sendiri untuk mengurangi sampah plastik.\nBawa pulang kembali semua sampahmu.\nJangan memetik, merusak, atau membawa pulang tanaman dan batu.\nHormati warga, adat, dan kegiatan setempat.');

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
('MM-0431', 1, DATE_ADD(CURDATE(), INTERVAL 6 DAY), 2, 'Maya Anggraini', '081200000005', '', 'Menunggu'),
('MM-0301', 1, DATE_SUB(CURDATE(), INTERVAL 150 DAY), 38, 'Rombongan PKK Sukamaju', '081200000011', '', 'Selesai'),
('MM-0322', 4, DATE_SUB(CURDATE(), INTERVAL 120 DAY), 45, 'SMP Negeri 3', '081200000012', '', 'Selesai'),
('MM-0350', 2, DATE_SUB(CURDATE(), INTERVAL 90 DAY), 50, 'Komunitas Pesepeda Harapan', '081200000013', '', 'Selesai'),
('MM-0377', 3, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 30, 'Mahasiswa KKN', '081200000014', '', 'Selesai'),
('MM-0402', 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 42, 'Keluarga Besar Wiryo', '081200000015', '', 'Selesai');

INSERT INTO pesan (nama, kontak, topik, isi, dibaca) VALUES
('Andi', 'andi@contoh.id', 'Wisata', 'Apakah curug bisa dikunjungi rombongan 40 orang?', 0),
('Bu Sri', '081200000007', 'Layanan administrasi', 'Syarat membuat surat keterangan usaha apa saja?', 1);

-- Akun demo. Kata sandi semuanya: desa123 (ganti sebelum website dipakai sungguhan)
INSERT INTO admin (nama, username, password_hash, peran, akses) VALUES
('Hj. Siti Rahmawati', 'admin', '$2y$10$oDepwP6RccgOG1ORMWFG5.wkuxKKwL.yBOaKPZAL.fKWqual9rNNS', 'Super admin', NULL),
('Yusuf Maulana', 'operator1', '$2y$10$oDepwP6RccgOG1ORMWFG5.wkuxKKwL.yBOaKPZAL.fKWqual9rNNS', 'Petugas', 'reservasi,pesan,ulasan,aspirasi,privasi,layanan,faq,laporan,bansos,surat,zona,ulasanumkm,pesananumkm,wisata,budaya,umkm,agenda,berita,galeri,statistik,profil,aparat,apbdes,pembangunan,bantuan,darurat,fasilitas,sampah'),
('Jurnalis Desa', 'sayajurnalis', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'berita,galeri'),
('Pengelola Data Desa', 'datapublik', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'statistik,profil,aparat,apbdes,pembangunan,bantuan,bansos,sampah,fasilitas,laporan,zona'),
('Pengelola Wisata', 'pengelolawisata', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'reservasi,pesan,ulasan,wisata,budaya,umkm');

-- Kode etik khusus destinasi
UPDATE wisata SET kode_etik = 'Berjalan hanya di pematang dan jalur yang disediakan, jangan menginjak tanaman padi.\nJangan menerbangkan drone di atas sawah tanpa izin pengelola.' WHERE slug = 'terasering';
UPDATE wisata SET kode_etik = 'Dilarang memakai sabun, sampo, atau deterjen di kolam curug.\nIkuti petunjuk petugas saat jalur ditutup karena hujan.' WHERE slug = 'curug';
UPDATE wisata SET kode_etik = 'Minta izin sebelum memotret pengrajin dan rumahnya.' WHERE slug = 'anyaman';
UPDATE wisata SET kode_etik = 'Petik hanya buah yang ditunjuk petugas, jangan mematahkan ranting.' WHERE slug = 'jeruk';

-- Contoh ulasan untuk reservasi contoh yang sudah selesai (dilewati kalau reservasinya tidak ada)
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Jeruknya manis dan petugasnya ramah. Anak-anak senang sekali memetik sendiri.' FROM reservasi WHERE kode = 'MM-0430';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Pemandangan pagi luar biasa, jalur pematang terawat dan pemandunya sabar.' FROM reservasi WHERE kode = 'MM-0301';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 4, 'Seru untuk rombongan sekolah. Parkir bus agak sempit, sebaiknya datang pagi.' FROM reservasi WHERE kode = 'MM-0322';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Airnya jernih dan jalan menuju curug sudah dibeton. Wajib bawa sandal gunung.' FROM reservasi WHERE kode = 'MM-0350';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Belajar menganyam langsung dari pengrajin, pengalaman yang berharga untuk mahasiswa.' FROM reservasi WHERE kode = 'MM-0377';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 4, 'Datang pagi sesuai saran cuaca di website, ternyata cerah seharian.' FROM reservasi WHERE kode = 'MM-0402';

-- ---------- Data contoh Fase 8 ----------
INSERT INTO pengaturan (kunci, nilai) VALUES
('info_bank_sampah', 'Bank Sampah Mulya Lestari buka setiap Sabtu pukul 08.00 sampai 11.00 di Balai RW 02. Tabungan sampah bisa diambil tunai atau dipakai membayar iuran warga.'),
('harga_sampah', 'Botol plastik PET|Rp3.000 per kg\nKardus dan kertas|Rp1.500 per kg\nKaleng aluminium|Rp10.000 per kg\nMinyak jelantah|Rp4.000 per liter');

INSERT INTO apbdes (tahun, jenis, uraian, anggaran, realisasi, urutan) VALUES
(2026, 'Pendapatan', 'Dana Desa', 1050000000, 945000000, 1),
(2026, 'Pendapatan', 'Alokasi Dana Desa', 420000000, 350000000, 2),
(2026, 'Pendapatan', 'Pendapatan asli desa (wisata dan BUMDes)', 145000000, 118000000, 3),
(2026, 'Pendapatan', 'Bagi hasil pajak dan retribusi', 62000000, 48000000, 4),
(2026, 'Belanja', 'Penyelenggaraan pemerintahan desa', 520000000, 430000000, 1),
(2026, 'Belanja', 'Pembangunan desa', 690000000, 505000000, 2),
(2026, 'Belanja', 'Pembinaan kemasyarakatan', 140000000, 96000000, 3),
(2026, 'Belanja', 'Pemberdayaan masyarakat', 260000000, 188000000, 4),
(2026, 'Belanja', 'Penanggulangan bencana dan keadaan darurat', 50000000, 12000000, 5);

INSERT INTO pembangunan (nama, lokasi, tahun, anggaran, sumber_dana, progres, status, deskripsi) VALUES
('Rabat beton jalan menuju Curug Cikaso', 'Dusun Cikaso, RW 04', 2026, 185000000, 'Dana Desa', 100, 'Selesai', 'Jalan tanah sepanjang 620 meter dibeton selebar 3 meter, sehingga pengunjung dan petani bisa lewat saat musim hujan.'),
('Rehabilitasi saluran irigasi Cikaso', 'Blok sawah terasering, RW 02 dan RW 03', 2026, 220000000, 'Dana Desa', 65, 'Berjalan', 'Perbaikan saluran air peninggalan pendiri desa yang mengairi sawah bertingkat, dikerjakan padat karya oleh warga.'),
('Gazebo dan toilet wisata terasering', 'Kawasan Terasering Sawah Sigarung', 2026, 95000000, 'Pendapatan asli desa', 40, 'Berjalan', 'Tempat istirahat dan toilet bersih untuk pengunjung, dibiayai dari hasil tiket wisata.'),
('Rumah pengolahan sampah TPS3R', 'Dekat Balai RW 02', 2026, 150000000, 'Bantuan provinsi', 0, 'Perencanaan', 'Tempat memilah sampah organik menjadi kompos dan sampah plastik untuk bank sampah.');

INSERT INTO bantuan (nama, penyelenggara, sasaran, syarat, jadwal, cara_daftar, aktif) VALUES
('Bantuan Langsung Tunai Dana Desa', 'Pemerintah Desa Marga Mulya', 'Keluarga kurang mampu yang belum menerima bantuan sosial lain', 'Warga Desa Marga Mulya dibuktikan dengan KTP dan KK\nDitetapkan lewat musyawarah desa', 'Disalurkan setiap tiga bulan', 'Hubungi ketua RT atau datang ke kantor desa membawa KTP dan KK. Usulan dibahas di musyawarah desa.', 1),
('Bibit jeruk dan pupuk organik', 'Dinas Pertanian Kabupaten', 'Anggota kelompok tani', 'Terdaftar sebagai anggota kelompok tani\nMemiliki atau menggarap lahan kebun', 'Pendaftaran Januari sampai Februari', 'Daftar lewat ketua kelompok tani masing-masing.', 1),
('Pelatihan dan modal usaha anyaman', 'BUMDes Marga Mulya', 'Pengrajin dan pemuda yang ingin memulai usaha', 'Berusia 17 tahun ke atas\nBersedia mengikuti pelatihan 3 hari', 'Dibuka dua kali setahun', 'Isi formulir di kantor BUMDes atau kirim pesan lewat halaman Kontak dengan topik Layanan administrasi.', 1);

INSERT INTO darurat (nama, nomor, keterangan, urutan) VALUES
('Panggilan darurat', '112', 'Nomor darurat nasional, bebas pulsa', 1),
('Ambulans dan gawat darurat', '119', 'Layanan kesehatan darurat', 2),
('Polisi', '110', '', 3),
('Pemadam kebakaran', '113', '', 4),
('Basarnas (pencarian dan pertolongan)', '115', 'Orang hilang atau kecelakaan di alam', 5);

INSERT INTO fasilitas (nama, kategori, alamat, keterangan, lat, lng) VALUES
('Kantor Desa Marga Mulya', 'Pemerintahan', 'Jl. Raya Marga Mulya No. 1', 'Layanan administrasi Senin sampai Jumat', -7.205000, 107.570000),
('Puskesmas Pembantu Marga Mulya', 'Kesehatan', 'RW 01', 'Pemeriksaan umum dan imunisasi', -7.203600, 107.572100),
('Posyandu Melati', 'Kesehatan', 'RW 03', 'Penimbangan balita setiap tanggal 10', -7.208300, 107.566900),
('SD Negeri Marga Mulya 1', 'Pendidikan', 'RW 01', '', -7.204100, 107.568200),
('SMP Negeri Sukamaju 2', 'Pendidikan', 'RW 05', '', -7.200800, 107.574600),
('Balai Warga dan Lapangan', 'Olahraga dan ruang publik', 'RW 02', 'Lapangan voli dan tempat kegiatan warga', -7.206800, 107.571800),
('Bank Sampah Mulya Lestari', 'Lingkungan', 'Balai RW 02', 'Buka setiap Sabtu pagi', -7.207100, 107.572400),
('Pasar Desa', 'Ekonomi', 'Pertigaan jalan raya desa', 'Ramai setiap Senin dan Kamis pagi', -7.202500, 107.569500);

INSERT INTO sampah (bulan, jenis, berat_kg, nilai_rupiah) VALUES
('2026-05-01', 'Plastik', 410, 1230000), ('2026-05-01', 'Kertas', 260, 390000), ('2026-05-01', 'Logam', 38, 380000), ('2026-05-01', 'Organik (kompos)', 980, 0),
('2026-06-01', 'Plastik', 445, 1335000), ('2026-06-01', 'Kertas', 248, 372000), ('2026-06-01', 'Logam', 41, 410000), ('2026-06-01', 'Organik (kompos)', 1040, 0),
('2026-07-01', 'Plastik', 520, 1560000), ('2026-07-01', 'Kertas', 301, 451500), ('2026-07-01', 'Logam', 45, 450000), ('2026-07-01', 'Organik (kompos)', 1120, 0),
('2026-08-01', 'Plastik', 498, 1494000), ('2026-08-01', 'Kertas', 287, 430500), ('2026-08-01', 'Logam', 52, 520000), ('2026-08-01', 'Organik (kompos)', 1185, 0),
('2026-09-01', 'Plastik', 560, 1680000), ('2026-09-01', 'Kertas', 310, 465000), ('2026-09-01', 'Logam', 49, 490000), ('2026-09-01', 'Organik (kompos)', 1230, 0),
('2026-10-01', 'Plastik', 612, 1836000), ('2026-10-01', 'Kertas', 334, 501000), ('2026-10-01', 'Logam', 57, 570000), ('2026-10-01', 'Organik (kompos)', 1310, 0);

INSERT INTO aspirasi (kode, nama, wa, email, kategori, lokasi, isi, status, tanggapan, publik, diperbarui_pada) VALUES
('ADU-7KQ2PA', 'Warga RW 04', '', '', 'Infrastruktur', 'Jalan menuju Curug Cikaso', 'Jalan tanah ke curug licin dan berlumpur setiap hujan, motor petani sering terperosok.', 'Selesai', 'Sudah dibangun rabat beton 620 meter dari Dana Desa 2026. Terima kasih atas laporannya.', 1, CURRENT_TIMESTAMP),
('ADU-M3XT8B', 'Warga RW 02', '', '', 'Kebersihan dan lingkungan', 'Saluran dekat Balai RW 02', 'Banyak sampah plastik menyumbat saluran air setelah hari pasar.', 'Selesai', 'Kerja bakti bersama warga sudah dilakukan dan jadwal angkut sampah pasar ditambah menjadi dua kali seminggu.', 1, CURRENT_TIMESTAMP),
('ADU-R9HW4C', 'Warga RW 03', '', '', 'Usulan program', 'Balai warga', 'Usul ada kelas menganyam untuk remaja di hari Minggu.', 'Diproses', 'Sedang dibahas bersama BUMDes dan kelompok pengrajin.', 0, CURRENT_TIMESTAMP);

-- Contoh data perjalanan pengunjung untuk perkiraan jejak karbon
UPDATE reservasi SET kendaraan = 'Mobil pribadi', jarak_km = 60, emisi_kg = 20.40 WHERE kode = 'MM-0427';
UPDATE reservasi SET kendaraan = 'Angkutan umum', jarak_km = 35, emisi_kg = 42.00 WHERE kode = 'MM-0428';
UPDATE reservasi SET kendaraan = 'Bus atau elf rombongan', jarak_km = 40, emisi_kg = 78.00 WHERE kode = 'MM-0429';
UPDATE reservasi SET kendaraan = 'Sepeda motor', jarak_km = 20, emisi_kg = 8.80 WHERE kode = 'MM-0430';
UPDATE reservasi SET kendaraan = 'Kereta lalu ojek', jarak_km = 120, emisi_kg = 14.88 WHERE kode = 'MM-0431';
UPDATE reservasi SET kendaraan = 'Bus atau elf rombongan', jarak_km = 80, emisi_kg = 237.12 WHERE kode = 'MM-0301';
UPDATE reservasi SET kendaraan = 'Bus atau elf rombongan', jarak_km = 25, emisi_kg = 87.75 WHERE kode = 'MM-0322';
UPDATE reservasi SET kendaraan = 'Sepeda atau jalan kaki', jarak_km = 30, emisi_kg = 0 WHERE kode = 'MM-0350';
UPDATE reservasi SET kendaraan = 'Angkutan umum', jarak_km = 15, emisi_kg = 90.00 WHERE kode = 'MM-0377';
UPDATE reservasi SET kendaraan = 'Mobil pribadi', jarak_km = 50, emisi_kg = 187.00 WHERE kode = 'MM-0402';

-- ---------- Fase 9: privasi, layanan surat, wisata ramah semua ----------
-- Permintaan data pribadi (hak warga menurut UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi)
CREATE TABLE IF NOT EXISTS permintaan_data (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(12) NOT NULL UNIQUE,
  jenis VARCHAR(30) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  kontak VARCHAR(150) NOT NULL,
  kode_terkait VARCHAR(20) NOT NULL DEFAULT '',
  rincian TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Diterima',
  tanggapan TEXT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada TIMESTAMP NULL
);

-- Pemandu layanan surat: syarat yang harus dibawa warga
CREATE TABLE IF NOT EXISTS layanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  syarat TEXT NOT NULL,
  lama_proses VARCHAR(100) NOT NULL,
  biaya VARCHAR(100) NOT NULL,
  tempat VARCHAR(150) NOT NULL,
  keterangan TEXT NULL,
  urutan INT NOT NULL DEFAULT 0,
  aktif TINYINT NOT NULL DEFAULT 1
);

-- Info aksesibilitas setiap destinasi
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS tingkat_jalur VARCHAR(20) NOT NULL DEFAULT 'Mudah';
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS ramah_kursi_roda TINYINT NOT NULL DEFAULT 0;
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS ramah_lansia TINYINT NOT NULL DEFAULT 0;
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS ada_toilet TINYINT NOT NULL DEFAULT 1;
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS catatan_akses VARCHAR(255) NOT NULL DEFAULT '';

INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('simpan_data_hari', '180'),
('kontak_privasi', 'Sekretaris Desa, lewat email desa atau WhatsApp desa'),
('kata_terlarang', 'bangsat,bajingan,brengsek,keparat,goblok,tolol,kampret,bego,tai');

-- Contoh info aksesibilitas (hanya kalau belum pernah diisi)
UPDATE wisata SET tingkat_jalur = 'Sedang', ramah_lansia = 1, ramah_kursi_roda = 0, ada_toilet = 1, catatan_akses = 'Jalur pematang sempit dan bertangga. Gazebo di titik pandang utama bisa dicapai lewat jalan beton 150 meter.' WHERE slug = 'terasering' AND catatan_akses = '';
UPDATE wisata SET tingkat_jalur = 'Menantang', ramah_lansia = 0, ramah_kursi_roda = 0, ada_toilet = 1, catatan_akses = 'Jalan beton sampai parkir, lalu jalan setapak menurun sekitar 400 meter dan licin setelah hujan.' WHERE slug = 'curug' AND catatan_akses = '';
UPDATE wisata SET tingkat_jalur = 'Mudah', ramah_lansia = 1, ramah_kursi_roda = 1, ada_toilet = 1, catatan_akses = 'Jalan kampung datar dan beraspal. Kegiatan menganyam dilakukan sambil duduk di balai.' WHERE slug = 'anyaman' AND catatan_akses = '';
UPDATE wisata SET tingkat_jalur = 'Mudah', ramah_lansia = 1, ramah_kursi_roda = 0, ada_toilet = 1, catatan_akses = 'Kebun berkontur landai dengan jalan tanah. Tersedia kursi istirahat di dekat pintu masuk.' WHERE slug = 'jeruk' AND catatan_akses = '';

-- Contoh layanan surat. Syarat dan biaya WAJIB dicek ulang ke kantor desa sebelum dipakai.
INSERT IGNORE INTO layanan (id, nama, syarat, lama_proses, biaya, tempat, keterangan, urutan, aktif) VALUES
(1, 'Surat keterangan domisili', 'KTP asli dan fotokopi\nKartu Keluarga (KK) asli dan fotokopi\nSurat pengantar dari ketua RT dan RW', '1 hari kerja', 'Gratis', 'Kantor desa, loket pelayanan', 'Dipakai untuk melamar kerja, membuka rekening, atau mendaftar sekolah.', 1, 1),
(2, 'Surat keterangan tidak mampu (SKTM)', 'KTP asli dan fotokopi\nKartu Keluarga (KK) asli dan fotokopi\nSurat pengantar dari ketua RT dan RW\nKeterangan keperluan, misalnya untuk sekolah atau berobat', '1 hari kerja', 'Gratis', 'Kantor desa, loket pelayanan', 'Petugas bisa melakukan kunjungan rumah untuk memastikan keadaan keluarga.', 2, 1),
(3, 'Surat pengantar SKCK', 'KTP asli dan fotokopi\nKartu Keluarga (KK) asli dan fotokopi\nSurat pengantar dari ketua RT dan RW\nPas foto 4x6 latar merah 2 lembar', '1 hari kerja', 'Gratis', 'Kantor desa, loket pelayanan', 'Setelah surat pengantar jadi, SKCK diurus di kantor polisi sesuai ketentuan yang berlaku.', 3, 1),
(4, 'Surat keterangan usaha', 'KTP asli dan fotokopi\nKartu Keluarga (KK) asli dan fotokopi\nFoto tempat usaha\nSurat pengantar dari ketua RT dan RW', '1 sampai 2 hari kerja', 'Gratis', 'Kantor desa, loket pelayanan', 'Sering dibutuhkan untuk mengajukan pinjaman modal usaha.', 4, 1),
(5, 'Pengantar perubahan Kartu Keluarga', 'Kartu Keluarga (KK) asli\nKTP semua anggota keluarga yang berubah\nDokumen pendukung, misalnya akta kelahiran, buku nikah, atau surat pindah', '1 hari kerja di desa, lalu diproses di Disdukcapil', 'Gratis', 'Kantor desa, loket pelayanan', 'Desa membuatkan surat pengantar. Penerbitan KK baru dilakukan oleh Disdukcapil.', 5, 1);

-- Posyandu tambahan supaya warga bisa mencoba fitur pengingat kalender
INSERT INTO agenda (judul, tanggal, jam, tempat, isi)
SELECT 'Posyandu Mawar', DATE_ADD(CURDATE(), INTERVAL 18 DAY), '08.00 sampai 11.00', 'Balai RW 03', 'Penimbangan balita, imunisasi, pemberian makanan tambahan, dan konsultasi gizi bersama bidan desa.'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM agenda WHERE judul = 'Posyandu Mawar');

-- Petugas yang mengurus aspirasi juga mengurus permintaan data pribadi dan layanan surat
UPDATE admin SET akses = CONCAT(akses, ',privasi,layanan') WHERE akses LIKE '%aspirasi%' AND akses NOT LIKE '%privasi%';

-- ---------- Fase 10: laporan dampak, tiket hijau, asisten desa ----------
-- Tiket Hijau: kupon UMKM untuk pengunjung berskor hijau A atau B
ALTER TABLE reservasi ADD COLUMN IF NOT EXISTS kupon VARCHAR(20) NULL;
ALTER TABLE reservasi ADD COLUMN IF NOT EXISTS kupon_dipakai_pada TIMESTAMP NULL;
ALTER TABLE reservasi ADD COLUMN IF NOT EXISTS kupon_umkm VARCHAR(150) NULL;
-- Kode tukar rahasia milik tiap UMKM (disimpan terenkripsi) untuk menandai kupon sudah dipakai
ALTER TABLE umkm ADD COLUMN IF NOT EXISTS pin_hash VARCHAR(255) NULL;

-- Asisten Desa: pertanyaan dan jawaban tambahan yang diisi admin
CREATE TABLE IF NOT EXISTS faq (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pertanyaan VARCHAR(200) NOT NULL,
  kata_kunci VARCHAR(255) NOT NULL,
  jawaban TEXT NOT NULL,
  urutan INT NOT NULL DEFAULT 0,
  aktif TINYINT NOT NULL DEFAULT 1
);

INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('tiket_hijau_aktif', '1'),
('tiket_hijau_skor', 'A,B'),
('tiket_hijau_nilai', '5000'),
('tiket_hijau_syarat', 'Potongan Rp5.000 untuk belanja minimal Rp25.000 di UMKM Desa Marga Mulya. Berlaku sampai 7 hari setelah tanggal kunjungan, satu kupon untuk satu kali belanja.');

INSERT IGNORE INTO faq (id, pertanyaan, kata_kunci, jawaban, urutan, aktif) VALUES
(1, 'Bagaimana cara membatalkan reservasi?', 'batal,membatalkan,pembatalan,cancel,ubah tanggal,ganti tanggal', 'Kirim pesan ke WhatsApp desa dengan menyebut kode reservasimu (diawali MM-). Petugas akan membatalkan atau memindahkan tanggalnya, lalu kabarnya dikirim ke emailmu.', 1, 1),
(2, 'Apakah tersedia pemandu wisata?', 'pemandu,guide,pendamping,didampingi', 'Tulis kebutuhan pemandu di kolom catatan saat reservasi, misalnya rombongan sekolah. Pengelola akan mengonfirmasi ketersediaannya lewat WhatsApp.', 2, 1),
(3, 'Bolehkah menerbangkan drone?', 'drone,foto udara', 'Drone tidak boleh diterbangkan di atas sawah dan kawasan warga tanpa izin pengelola. Mintalah izin dulu lewat WhatsApp desa.', 3, 1),
(4, 'Bagaimana cara membayar tiket?', 'bayar,pembayaran,transfer,qris,tunai,non tunai', 'Tiket dibayar di loket saat tiba di lokasi. Website tidak meminta pembayaran online.', 4, 1);

-- Kode tukar contoh untuk semua UMKM: tukar123 (WAJIB diganti di CMS > UMKM sebelum submit)
UPDATE umkm SET pin_hash = '$2y$10$qt2ZvgOvWH7UDax3hNeece8JBma.p6T0juK7RkVgXll8dWoh/Xib6' WHERE pin_hash IS NULL;

-- Contoh kupon untuk reservasi contoh berskor hijau
UPDATE reservasi SET kupon = 'HIJAU-0322', kupon_dipakai_pada = CURRENT_TIMESTAMP, kupon_umkm = 'Keripik Singkong Bu Darmi' WHERE kode = 'MM-0322' AND kupon IS NULL;
UPDATE reservasi SET kupon = 'HIJAU-0350', kupon_dipakai_pada = CURRENT_TIMESTAMP, kupon_umkm = 'Anyaman Rukun' WHERE kode = 'MM-0350' AND kupon IS NULL;
UPDATE reservasi SET kupon = 'HIJAU-0377' WHERE kode = 'MM-0377' AND kupon IS NULL;
UPDATE reservasi SET kupon = 'HIJAU-0429' WHERE kode = 'MM-0429' AND kupon IS NULL;

-- ---------- Fase 11: laporan bulanan ----------
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('email_laporan', ''),
('laporan_terakhir', '');

-- ---------- Fase 12 ----------
-- Pantau Bansos: kuota dan tahap penyaluran per program
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS kuota INT NOT NULL DEFAULT 0;
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS jumlah_tahap INT NOT NULL DEFAULT 1;
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS selesai DATE NULL;

-- Penerima bantuan. Nama tampil di web hanya dalam bentuk disamarkan.
CREATE TABLE IF NOT EXISTS bansos_penerima (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bantuan_id INT NOT NULL,
  nama VARCHAR(150) NOT NULL,
  rt VARCHAR(3) NOT NULL,
  rw VARCHAR(3) NOT NULL DEFAULT '',
  tahap_tersalur INT NOT NULL DEFAULT 0,
  terakhir_salur DATE NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pengajuan surat online
CREATE TABLE IF NOT EXISTS surat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(12) NOT NULL UNIQUE,
  layanan_id INT NOT NULL,
  layanan_nama VARCHAR(150) NOT NULL,
  nama VARCHAR(150) NOT NULL,
  wa VARCHAR(20) NOT NULL DEFAULT '',
  email VARCHAR(150) NOT NULL DEFAULT '',
  keperluan VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Diterima',
  catatan TEXT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada TIMESTAMP NULL
);

-- Antrean di kantor desa (ambil nomor lewat QR di meja layanan)
CREATE TABLE IF NOT EXISTS antrean (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  nomor INT NOT NULL,
  keperluan VARCHAR(150) NOT NULL,
  nama VARCHAR(60) NOT NULL DEFAULT '',
  token VARCHAR(16) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Menunggu',
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  dipanggil_pada TIMESTAMP NULL,
  UNIQUE (tanggal, nomor)
);

-- Peta desa berlapis: zona rawan bencana dan titik proyek pembangunan
CREATE TABLE IF NOT EXISTS zona_rawan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  jenis VARCHAR(40) NOT NULL,
  lat DECIMAL(9,6) NOT NULL,
  lng DECIMAL(9,6) NOT NULL,
  radius_m INT NOT NULL DEFAULT 150,
  keterangan VARCHAR(255) NOT NULL DEFAULT ''
);
ALTER TABLE pembangunan ADD COLUMN IF NOT EXISTS lat DECIMAL(9,6) NULL;
ALTER TABLE pembangunan ADD COLUMN IF NOT EXISTS lng DECIMAL(9,6) NULL;

INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('antrean_kode', ''),
('foto_hero', '');

-- ---------- Tambahan 12b: saran cuaca, deskripsi dan ulasan UMKM ----------
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS saat_hujan TINYINT NOT NULL DEFAULT 0;
UPDATE wisata SET saat_hujan = 1 WHERE slug = 'anyaman';
ALTER TABLE umkm ADD COLUMN IF NOT EXISTS deskripsi TEXT NULL;

-- Ulasan produk UMKM. Siapa pun boleh menulis, tapi baru tampil setelah disetujui petugas.
CREATE TABLE IF NOT EXISTS ulasan_umkm (
  id INT AUTO_INCREMENT PRIMARY KEY,
  umkm_id INT NOT NULL,
  usaha VARCHAR(150) NOT NULL DEFAULT '',
  nama VARCHAR(60) NOT NULL,
  bintang TINYINT NOT NULL,
  komentar VARCHAR(500) NOT NULL,
  tampil TINYINT NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------- Tambahan 12d: catatan data, sambutan kepala desa, keranjang UMKM ----------
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('catatan_data', 'Angka pada halaman ini adalah data simulasi yang mengacu pada format Data Desa Marga Mulya dari panitia IIT Challenge 2026. Perangkat desa dapat menggantinya dengan data resmi kapan saja melalui CMS.'),
('sambutan_kades', 'Selamat datang di website resmi Desa Marga Mulya. Melalui website ini, kami ingin pelayanan desa terasa lebih dekat, cepat, dan terbuka bagi seluruh warga, mulai dari pengurusan surat, informasi bantuan, sampai laporan anggaran desa.\n\nKepada para wisatawan, kami mengundang Bapak dan Ibu menikmati terasering, curug, kebun jeruk, dan karya warga kami. Mari bersama membangun Marga Mulya yang maju, hijau, dan sejahtera.'),
('foto_kades', '');

-- Pesanan dari keranjang UMKM. Satu pesanan untuk satu penjual; pembeli lalu mengirim ringkasannya ke WhatsApp penjual.
CREATE TABLE IF NOT EXISTS pesanan_umkm (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(12) NOT NULL UNIQUE,
  usaha VARCHAR(150) NOT NULL,
  wa_penjual VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  wa VARCHAR(20) NOT NULL,
  pengambilan VARCHAR(20) NOT NULL DEFAULT 'Ambil sendiri',
  alamat VARCHAR(255) NOT NULL DEFAULT '',
  catatan VARCHAR(255) NOT NULL DEFAULT '',
  ringkasan VARCHAR(600) NOT NULL DEFAULT '',
  total INT NOT NULL DEFAULT 0,
  status VARCHAR(20) NOT NULL DEFAULT 'Baru',
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS pesanan_umkm_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id INT NOT NULL,
  umkm_id INT NOT NULL,
  produk VARCHAR(150) NOT NULL,
  harga INT NOT NULL,
  satuan VARCHAR(40) NOT NULL DEFAULT '',
  jumlah INT NOT NULL
);

INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES ('mode_privat', '0');

-- ---------- Contoh isi (ganti dengan data asli lewat CMS) ----------
UPDATE bantuan SET kuota = 40, jumlah_tahap = 3 WHERE id = 1 AND kuota = 0;
UPDATE bantuan SET kuota = 60, jumlah_tahap = 1 WHERE id = 2 AND kuota = 0;
UPDATE bantuan SET kuota = 15, jumlah_tahap = 1 WHERE id = 3 AND kuota = 0;

-- Nama contoh di bawah ini KARANGAN, bukan warga sungguhan
INSERT IGNORE INTO bansos_penerima (id, bantuan_id, nama, rt, rw, tahap_tersalur, terakhir_salur) VALUES
(1, 1, 'Asep Saepudin', '01', '01', 2, '2026-10-14'), (2, 1, 'Iis Rohayati', '01', '01', 2, '2026-10-14'),
(3, 1, 'Dedi Kurniawan', '02', '01', 2, '2026-10-14'), (4, 1, 'Euis Komariah', '02', '01', 2, '2026-10-14'),
(5, 1, 'Ujang Sumarna', '03', '02', 2, '2026-10-14'), (6, 1, 'Enok Sumiati', '03', '02', 2, '2026-10-15'),
(7, 1, 'Cecep Hidayat', '04', '02', 2, '2026-10-15'), (8, 1, 'Nining Kurniasih', '04', '02', 2, '2026-10-15'),
(9, 1, 'Ade Suryana', '05', '03', 2, '2026-10-15'), (10, 1, 'Popon Saripah', '05', '03', 1, '2026-07-12'),
(11, 1, 'Mamat Rahmat', '06', '03', 2, '2026-10-15'), (12, 1, 'Titin Supriatin', '06', '03', 2, '2026-10-16'),
(13, 1, 'Yayan Sopyan', '07', '04', 2, '2026-10-16'), (14, 1, 'Entin Kartini', '07', '04', 2, '2026-10-16'),
(15, 1, 'Ohim Suhimat', '08', '04', 1, '2026-07-12'), (16, 1, 'Iyah Rokayah', '08', '04', 2, '2026-10-16'),
(17, 1, 'Dadang Sudrajat', '09', '05', 2, '2026-10-16'), (18, 1, 'Imas Masitoh', '09', '05', 2, '2026-10-16'),
(19, 1, 'Ujang Kosasih', '10', '05', 0, NULL), (20, 1, 'Eti Rosmiati', '10', '05', 2, '2026-10-17'),
(21, 2, 'Kelompok Tani Mekar Sari', '03', '02', 1, '2026-02-20'), (22, 2, 'Kelompok Tani Sauyunan', '06', '03', 1, '2026-02-20'),
(23, 2, 'Kelompok Tani Jeruk Mulya', '08', '04', 1, '2026-02-21');

INSERT IGNORE INTO zona_rawan (id, nama, jenis, lat, lng, radius_m, keterangan) VALUES
(1, 'Lereng menuju Curug Cikaso', 'Longsor', -7.211200, 107.574600, 260, 'Tanah labil setelah hujan deras lebih dari 2 jam. Jalur ditutup saat siaga cuaca.'),
(2, 'Bantaran Sungai Cikaso', 'Banjir', -7.208600, 107.567400, 220, 'Air bisa naik cepat saat hujan lebat di hulu. Jangan menyeberang sungai.'),
(3, 'Tebing jalan RW 04', 'Longsor', -7.201900, 107.575800, 160, 'Waspada batu jatuh saat hujan. Kendaraan besar lewat jalan alternatif.');

UPDATE pembangunan SET lat = -7.210400, lng = 107.573100 WHERE id = 1 AND lat IS NULL;
UPDATE pembangunan SET lat = -7.206900, lng = 107.568800 WHERE id = 2 AND lat IS NULL;
UPDATE pembangunan SET lat = -7.204300, lng = 107.566200 WHERE id = 3 AND lat IS NULL;
UPDATE pembangunan SET lat = -7.207500, lng = 107.572900 WHERE id = 4 AND lat IS NULL;

INSERT IGNORE INTO faq (id, pertanyaan, kata_kunci, jawaban, urutan, aktif) VALUES
(20, 'Bagaimana memperbesar tulisan atau membacakan halaman?', 'perbesar,huruf,tulisan kecil,zoom,dibacakan,suara,talkback,tunanetra,low vision', 'Website ini sudah memakai huruf besar dan kontras tinggi sejak awal. Kalau masih kurang, perbesar tampilan dengan mencubit layar HP atau menekan Ctrl dan + di komputer. Untuk dibacakan, nyalakan TalkBack (Android) atau VoiceOver (iPhone) di pengaturan HP.', 20, 1);

INSERT IGNORE INTO surat (id, kode, layanan_id, layanan_nama, nama, wa, email, keperluan, status, catatan) VALUES
(1, 'SRT-4KX9PQ', 1, 'Surat keterangan domisili', 'Rina Marlina', '081200000021', '', 'Melamar kerja di pabrik', 'Siap diambil', 'Surat sudah ditandatangani. Bawa KTP asli saat mengambil.'),
(2, 'SRT-7HN2WD', 2, 'Surat keterangan tidak mampu (SKTM)', 'Agus Setiawan', '081200000022', '', 'Keringanan biaya sekolah anak', 'Diproses', 'Menunggu kunjungan rumah petugas kesejahteraan.');
