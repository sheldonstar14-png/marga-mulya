-- =========================================================
-- Database Website Desa Marga Mulya (IIT EcoQuest 2026)
-- Isi: semua tabel + data contoh (dummy).
-- Cara pakai: jalankan seluruh file ini di database marga_mulya.
-- AMAN dijalankan ulang: tabel lama dihapus lalu dibuat lagi,
-- jadi semua data akan kembali ke data contoh.
-- =========================================================

DROP TABLE IF EXISTS relawan;
DROP TABLE IF EXISTS lembaga;
DROP TABLE IF EXISTS loker;
DROP TABLE IF EXISTS lingkungan;
DROP TABLE IF EXISTS kredit_foto;
DROP TABLE IF EXISTS asisten_menu;
DROP TABLE IF EXISTS foto_unggah;
DROP TABLE IF EXISTS pengajuan_umkm;
DROP TABLE IF EXISTS survei;
DROP TABLE IF EXISTS aspirasi_riwayat;
DROP TABLE IF EXISTS aspirasi_foto;
DROP TABLE IF EXISTS aspirasi_dukung;
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

-- ---------- Tambahan 12k: label SDGs dan relawan gotong royong ----------
-- Kolom sdg berisi nomor tujuan SDGs, dipisah koma. Contoh: '6,2' untuk irigasi.
ALTER TABLE pembangunan ADD COLUMN IF NOT EXISTS sdg VARCHAR(40) NULL;
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS sdg VARCHAR(40) NULL;
-- 0 = agenda tidak membuka pendaftaran relawan
ALTER TABLE agenda ADD COLUMN IF NOT EXISTS relawan_kuota INT NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS relawan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  agenda_id INT NOT NULL,
  agenda_judul VARCHAR(150) NOT NULL DEFAULT '',
  nama VARCHAR(60) NOT NULL,
  wa VARCHAR(20) NOT NULL,
  asal VARCHAR(60) NOT NULL DEFAULT '',
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT relawan_unik UNIQUE (agenda_id, wa)
);
UPDATE admin SET akses = CONCAT(akses, ',relawan') WHERE akses LIKE '%agenda%' AND akses NOT LIKE '%relawan%';

-- Contoh isi 12k: label SDGs dan relawan (hanya mengisi yang masih kosong)
UPDATE pembangunan SET sdg = '9,11' WHERE id = 1 AND sdg IS NULL;
UPDATE pembangunan SET sdg = '6,2' WHERE id = 2 AND sdg IS NULL;
UPDATE pembangunan SET sdg = '6,8' WHERE id = 3 AND sdg IS NULL;
UPDATE pembangunan SET sdg = '12,13' WHERE id = 4 AND sdg IS NULL;
UPDATE bantuan SET sdg = '1,10' WHERE id = 1 AND sdg IS NULL;
UPDATE bantuan SET sdg = '2,15' WHERE id = 2 AND sdg IS NULL;
UPDATE bantuan SET sdg = '8,5' WHERE id = 3 AND sdg IS NULL;
UPDATE agenda SET relawan_kuota = 40 WHERE id = 2 AND relawan_kuota = 0;
UPDATE agenda SET relawan_kuota = 25 WHERE id = 4 AND relawan_kuota = 0;
INSERT IGNORE INTO relawan (id, agenda_id, agenda_judul, nama, wa, asal) VALUES
(1, 2, 'Kerja bakti saluran irigasi', 'Ujang Sumarna', '081200000301', 'RT 03'),
(2, 2, 'Kerja bakti saluran irigasi', 'Dedi Kurniawan', '081200000302', 'RT 02'),
(3, 2, 'Kerja bakti saluran irigasi', 'Karang Taruna RW 02', '081200000303', 'RW 02'),
(4, 2, 'Kerja bakti saluran irigasi', 'Rizky Pratama', '081200000304', 'Mahasiswa KKN'),
(5, 2, 'Kerja bakti saluran irigasi', 'Nining Kurniasih', '081200000305', 'RT 04'),
(6, 2, 'Kerja bakti saluran irigasi', 'Yayan Sopyan', '081200000306', 'RT 07'),
(7, 2, 'Kerja bakti saluran irigasi', 'Salsa Aulia', '081200000307', 'Pengunjung'),
(8, 2, 'Kerja bakti saluran irigasi', 'Ade Suryana', '081200000308', 'RT 05'),
(9, 4, 'Merti Desa 2026', 'Sanggar Seni Sauyunan', '081200000309', 'RW 03'),
(10, 4, 'Merti Desa 2026', 'Imas Masitoh', '081200000310', 'RT 09'),
(11, 4, 'Merti Desa 2026', 'Fajar Nugraha', '081200000311', 'Pemuda RW 01');

-- ---------- Tambahan 12m: papan aspirasi terbuka + foto laporan ----------
-- izin_publik = pengirim mengizinkan laporannya tampil di Papan Aspirasi (tanpa nama dan kontak)
-- publik      = petugas sudah memeriksa dan menyetujui tampil
ALTER TABLE aspirasi ADD COLUMN IF NOT EXISTS izin_publik TINYINT NOT NULL DEFAULT 0;
ALTER TABLE aspirasi ADD COLUMN IF NOT EXISTS dukungan INT NOT NULL DEFAULT 0;
UPDATE aspirasi SET izin_publik = 1 WHERE publik = 1 AND izin_publik = 0;

-- Jejak perubahan status, supaya warga melihat kapan laporan diterima, diproses, dan selesai
CREATE TABLE IF NOT EXISTS aspirasi_riwayat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aspirasi_id INT NOT NULL,
  status VARCHAR(20) NOT NULL,
  catatan TEXT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Foto laporan disimpan di database (sudah diperkecil dan dibersihkan dari data lokasi GPS)
CREATE TABLE IF NOT EXISTS aspirasi_foto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aspirasi_id INT NOT NULL,
  mime VARCHAR(20) NOT NULL DEFAULT 'image/jpeg',
  lebar INT NOT NULL DEFAULT 0,
  tinggi INT NOT NULL DEFAULT 0,
  data MEDIUMBLOB NOT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Satu perangkat hanya bisa menekan "Saya juga mengalami" sekali per laporan
CREATE TABLE IF NOT EXISTS aspirasi_dukung (
  id INT AUTO_INCREMENT PRIMARY KEY,
  aspirasi_id INT NOT NULL,
  sidik VARCHAR(64) NOT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT dukung_unik UNIQUE (aspirasi_id, sidik)
);

-- Contoh isi 12m: aspirasi contoh tampil di Papan Aspirasi
-- hanya aspirasi contoh; aspirasi asli tetap menunggu izin pengirimnya
UPDATE aspirasi SET izin_publik = 1 WHERE kode IN ('ADU-7KQ2PA','ADU-M3XT8B','ADU-R9HW4C','ADU-P4LN7D','ADU-Q8VB2E','ADU-T6HC3G','ADU-W5ZR8H','ADU-X9DF4J');
UPDATE aspirasi SET publik = 1 WHERE kode IN ('ADU-7KQ2PA','ADU-M3XT8B','ADU-R9HW4C','ADU-P4LN7D','ADU-Q8VB2E','ADU-T6HC3G','ADU-W5ZR8H','ADU-X9DF4J') AND status <> 'Diterima';
UPDATE aspirasi SET dukungan = 7 WHERE kode = 'ADU-P4LN7D' AND dukungan = 0;
UPDATE aspirasi SET dukungan = 12 WHERE kode = 'ADU-Q8VB2E' AND dukungan = 0;
UPDATE aspirasi SET dukungan = 4 WHERE kode = 'ADU-T6HC3G' AND dukungan = 0;

-- ---------- Tambahan 12n: tanda data ilustrasi + survei kepuasan layanan ----------
ALTER TABLE statistik ADD COLUMN IF NOT EXISTS ilustrasi TINYINT NOT NULL DEFAULT 0;

-- Penilaian warga setelah surat atau aspirasinya selesai (1 sampai 5). Satu kode hanya bisa menilai sekali.
CREATE TABLE IF NOT EXISTS survei (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jenis VARCHAR(20) NOT NULL,
  kode VARCHAR(20) NOT NULL,
  layanan VARCHAR(150) NOT NULL DEFAULT '',
  nilai TINYINT NOT NULL,
  komentar VARCHAR(500) NOT NULL DEFAULT '',
  tampil TINYINT NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT survei_unik UNIQUE (jenis, kode)
);
UPDATE admin SET akses = CONCAT(akses, ',survei') WHERE (akses LIKE '%surat%' OR akses LIKE '%aspirasi%') AND akses NOT LIKE '%survei%';

-- ---------- Tambahan 12p: kredit foto, referensi data, pengajuan produk UMKM ----------
CREATE TABLE IF NOT EXISTS kredit_foto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(150) NOT NULL,
  pembuat VARCHAR(150) NOT NULL DEFAULT '',
  sumber_url VARCHAR(500) NOT NULL DEFAULT '',
  lisensi VARCHAR(80) NOT NULL DEFAULT '',
  lisensi_url VARCHAR(300) NOT NULL DEFAULT '',
  keterangan VARCHAR(300) NOT NULL DEFAULT '',
  urutan INT NOT NULL DEFAULT 10
);

-- Foto yang diunggah warga (misalnya foto produk UMKM). Tampil ke publik hanya setelah disetujui petugas.
CREATE TABLE IF NOT EXISTS foto_unggah (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mime VARCHAR(20) NOT NULL DEFAULT 'image/jpeg',
  lebar INT NOT NULL DEFAULT 0,
  tinggi INT NOT NULL DEFAULT 0,
  data MEDIUMBLOB NOT NULL,
  publik TINYINT NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pengajuan_umkm (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama_usaha VARCHAR(150) NOT NULL,
  kategori VARCHAR(60) NOT NULL,
  produk VARCHAR(150) NOT NULL,
  harga INT NOT NULL DEFAULT 0,
  satuan VARCHAR(40) NOT NULL DEFAULT '',
  pemilik VARCHAR(150) NOT NULL,
  wa VARCHAR(20) NOT NULL,
  deskripsi VARCHAR(600) NOT NULL DEFAULT '',
  foto_id INT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Menunggu',
  catatan VARCHAR(500) NOT NULL DEFAULT '',
  umkm_id INT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  diperbarui_pada TIMESTAMP NULL
);

INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('referensi_data_nama', 'Kecamatan Mauk Dalam Angka 2025, BPS Kabupaten Tangerang (Katalog 1102001.3603160)'),
('referensi_data_url', 'https://tangerangkab.bps.go.id/id/publication');
UPDATE admin SET akses = CONCAT(akses, ',pengajuanumkm') WHERE akses LIKE '%umkm%' AND akses NOT LIKE '%pengajuanumkm%';
UPDATE admin SET akses = CONCAT(akses, ',kreditfoto') WHERE akses LIKE '%galeri%' AND akses NOT LIKE '%kreditfoto%';

-- Contoh isi 12p: sumber dan kredit (ganti atau tambah lewat CMS > Kredit foto)
INSERT IGNORE INTO kredit_foto (id, judul, pembuat, sumber_url, lisensi, lisensi_url, keterangan, urutan) VALUES
(1, 'Logo Desa Marga Mulya', 'Tim EcoQuest Marga Mulya', '', 'Karya sendiri', '', 'Logo website, bukan logo resmi pemerintah desa.', 1),
(2, 'Ilustrasi perahu, ikan, pantai, mangrove, dan kelapa', 'Tim EcoQuest Marga Mulya', '', 'Karya sendiri', '', 'Gambar vektor yang dipakai bila foto asli belum tersedia.', 2),
(3, 'Data statistik desa', 'BPS Kabupaten Tangerang', 'https://tangerangkab.bps.go.id/id/publication', 'Publikasi resmi BPS', '', 'Kecamatan Mauk Dalam Angka 2025.', 3),
(4, 'Peta dasar', 'Kontributor OpenStreetMap', 'https://www.openstreetmap.org/copyright', 'ODbL', 'https://opendatacommons.org/licenses/odbl/', 'Ditampilkan lewat Leaflet.', 4),
(5, 'Prakiraan cuaca', 'Open-Meteo', 'https://open-meteo.com/', 'CC BY 4.0', 'https://creativecommons.org/licenses/by/4.0/', '', 5);

-- ---------- Tambahan 12q: asisten desa bermenu, Tiket Hijau dihentikan ----------
CREATE TABLE IF NOT EXISTS asisten_menu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  induk_id INT NOT NULL DEFAULT 0,
  judul VARCHAR(120) NOT NULL,
  pertanyaan VARCHAR(200) NOT NULL DEFAULT '',
  jawaban TEXT,
  tautan VARCHAR(255) NOT NULL DEFAULT '',
  label_tautan VARCHAR(60) NOT NULL DEFAULT '',
  urutan INT NOT NULL DEFAULT 0,
  aktif TINYINT NOT NULL DEFAULT 1
);
UPDATE pengaturan SET nilai = '0' WHERE kunci = 'tiket_hijau_aktif';
UPDATE admin SET akses = REPLACE(akses, 'faq', 'asisten') WHERE akses LIKE '%faq%';
UPDATE admin SET akses = REPLACE(REPLACE(akses, ',pesananumkm', ''), 'pesananumkm,', '') WHERE akses LIKE '%pesananumkm%';

-- ---------- Tambahan 12s: desain baru, sumber foto per konten, budaya, bantuan, lembaga desa ----------
-- Sumber foto per konten: tampil otomatis di "Sumber dan kredit foto", dikelompokkan per halaman
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS waktu_terbaik VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS sumber_foto VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS sumber_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE umkm ADD COLUMN IF NOT EXISTS sumber_foto VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE umkm ADD COLUMN IF NOT EXISTS sumber_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE umkm ADD COLUMN IF NOT EXISTS izin VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE umkm ADD COLUMN IF NOT EXISTS pengiriman VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE umkm ADD COLUMN IF NOT EXISTS foto_lain VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE berita ADD COLUMN IF NOT EXISTS kategori VARCHAR(40) NOT NULL DEFAULT 'Pemerintahan';
ALTER TABLE berita ADD COLUMN IF NOT EXISTS sumber_foto VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE berita ADD COLUMN IF NOT EXISTS sumber_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE galeri ADD COLUMN IF NOT EXISTS sumber_foto VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE galeri ADD COLUMN IF NOT EXISTS sumber_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE pembangunan ADD COLUMN IF NOT EXISTS sumber_foto VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE pembangunan ADD COLUMN IF NOT EXISTS sumber_url VARCHAR(500) NOT NULL DEFAULT '';
-- Budaya dan kesenian: jenis, gambar, dan tanda ilustrasi
ALTER TABLE budaya ADD COLUMN IF NOT EXISTS jenis VARCHAR(40) NOT NULL DEFAULT 'Tradisi';
ALTER TABLE budaya ADD COLUMN IF NOT EXISTS foto VARCHAR(255) NULL;
ALTER TABLE budaya ADD COLUMN IF NOT EXISTS sumber_foto VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE budaya ADD COLUMN IF NOT EXISTS sumber_url VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE budaya ADD COLUMN IF NOT EXISTS ilustrasi TINYINT NOT NULL DEFAULT 0;
ALTER TABLE budaya ADD COLUMN IF NOT EXISTS urutan INT NOT NULL DEFAULT 10;
-- Program bantuan: penjelasan lengkap per program
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS kategori VARCHAR(40) NOT NULL DEFAULT 'Sosial';
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS deskripsi TEXT NULL;
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS manfaat VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS tautan VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE bantuan ADD COLUMN IF NOT EXISTS ilustrasi TINYINT NOT NULL DEFAULT 0;
-- Pengajuan produk: sampai 3 foto, izin usaha, dan cara pengiriman
ALTER TABLE pengajuan_umkm ADD COLUMN IF NOT EXISTS foto_ids VARCHAR(60) NOT NULL DEFAULT '';
ALTER TABLE pengajuan_umkm ADD COLUMN IF NOT EXISTS izin VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE pengajuan_umkm ADD COLUMN IF NOT EXISTS pengiriman VARCHAR(150) NOT NULL DEFAULT '';
-- Relawan: keahlian atau bantuan yang ditawarkan
ALTER TABLE relawan ADD COLUMN IF NOT EXISTS peran VARCHAR(60) NOT NULL DEFAULT '';
ALTER TABLE agenda ADD COLUMN IF NOT EXISTS perlengkapan VARCHAR(255) NOT NULL DEFAULT '';
-- Lembaga desa dan kegiatan rutin (Data EcoQuest bagian L)
CREATE TABLE IF NOT EXISTS lembaga (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  jenis VARCHAR(60) NOT NULL DEFAULT 'Lembaga kemasyarakatan',
  kegiatan VARCHAR(255) NOT NULL DEFAULT '',
  jadwal VARCHAR(150) NOT NULL DEFAULT '',
  anggota INT NOT NULL DEFAULT 0,
  ilustrasi TINYINT NOT NULL DEFAULT 0,
  urutan INT NOT NULL DEFAULT 10
);
-- Menu yang dihentikan: kunjungan rombongan, pesan masuk, permintaan data pribadi, bank sampah, check-in
UPDATE admin SET akses = CONCAT(akses, ',lembaga') WHERE akses IS NOT NULL AND akses LIKE '%statistik%' AND akses NOT LIKE '%lembaga%';

-- ---------- Tambahan 12t: Kerja dan pelatihan ----------
-- Peluang kerja di desa (diajukan warga/UMKM, diperiksa petugas), lowongan resmi yang diteruskan
-- dari sumber pemerintah, dan info pelatihan. Lowongan otomatis hilang setelah tanggal batas.
CREATE TABLE IF NOT EXISTS loker (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL DEFAULT '',
  jenis VARCHAR(30) NOT NULL DEFAULT 'Kerja di desa',
  judul VARCHAR(150) NOT NULL,
  pemberi VARCHAR(150) NOT NULL DEFAULT '',
  lokasi VARCHAR(150) NOT NULL DEFAULT '',
  waktu_kerja VARCHAR(30) NOT NULL DEFAULT 'Harian',
  upah VARCHAR(100) NOT NULL DEFAULT '',
  kebutuhan INT NOT NULL DEFAULT 1,
  syarat TEXT NULL,
  cara_daftar VARCHAR(255) NOT NULL DEFAULT '',
  wa VARCHAR(20) NOT NULL DEFAULT '',
  tautan VARCHAR(255) NOT NULL DEFAULT '',
  batas DATE NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Menunggu',
  catatan VARCHAR(255) NOT NULL DEFAULT '',
  ilustrasi TINYINT NOT NULL DEFAULT 0,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
UPDATE admin SET akses = CONCAT(akses, ',loker') WHERE akses IS NOT NULL AND akses LIKE '%pengajuanumkm%' AND akses NOT LIKE '%loker%';

-- ---------- Tambahan 12w: Lingkungan pesisir ----------
-- Kartu bagian "Menjaga pesisir" di beranda, diatur lewat CMS > Lingkungan pesisir.
CREATE TABLE IF NOT EXISTS lingkungan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(100) NOT NULL,
  isi VARCHAR(300) NOT NULL DEFAULT '',
  ikon VARCHAR(20) NOT NULL DEFAULT 'Mangrove',
  tautan VARCHAR(150) NOT NULL DEFAULT '',
  info VARCHAR(40) NOT NULL DEFAULT 'Teks bebas',
  info_teks VARCHAR(120) NOT NULL DEFAULT '',
  aktif TINYINT NOT NULL DEFAULT 1,
  urutan INT NOT NULL DEFAULT 10
);
UPDATE admin SET akses = CONCAT(akses, ',lingkungan') WHERE akses IS NOT NULL AND akses LIKE '%lembaga%' AND akses NOT LIKE '%lingkungan%';

-- ===== Data asli Margamulya (sama dengan data-asli-margamulya.sql) =====
-- =========================================================
-- DATA DESA MARGAMULYA, KECAMATAN MAUK, KABUPATEN TANGERANG (BANTEN)
-- Menggantikan data contoh lama (desa perbukitan fiktif) dengan data desa yang asli.
--
-- Data ASLI (bersumber, tanpa tanda):
--   - Kode wilayah 36.03.08.2008, Kecamatan Mauk, Kabupaten Tangerang, Banten
--   - BPS, Kecamatan Mauk Dalam Angka 2025 (data 2024): luas 6,14 km2 (13,66% kecamatan), jarak 4 km ke kantor
--     kecamatan dan 32 km ke ibu kota kabupaten, 5 RW dan 25 RT, penduduk 8.449 jiwa (4.359 laki-laki, 4.090 perempuan),
--     kepadatan 1.376,17 jiwa/km2, rasio jenis kelamin 106,58, 8,94% penduduk kecamatan
--   - Peta wilayah BPS 2025: Marga Mulya berbatasan langsung dengan laut di utara, Desa Ketapang di barat,
--     Desa Tanjung Anom dan Kecamatan Sukadiri di timur, Desa Tegal Kunir Lor di selatan
--   - Arti nama "Marga" (kelompok/keturunan) + "Mulya" (baik), dipilih lewat musyawarah (BKKBN)
--   - Kampung KB Bebulak di RW 03, Dusun Bebulak (BKKBN)
--   - SD Negeri Margamulya di Kp. Bebulak, berdiri 1983 (Dapodik Kemendikdasmen)
--   - Sawah sekitar 330 ha, Kelompok Tani Jamblang, percontohan benih padi Cakrabuana (Jejak Kata, Okt 2023)
--   - Potensi pesisir: kelapa, mangrove, hasil laut, kerajinan batok kelapa, Pantai Pasir Putih (Universitas
--     Pembangunan Jaya, program SENANDUNG 2025)
--   - Nomor darurat nasional 112, 119, 110, 113, 115
-- Data ILUSTRASI: semua yang diberi tanda "(ilustrasi)" atau kolom statistik.ilustrasi = 1.
-- Ganti dengan data resmi desa lewat CMS kapan saja.
--
-- Jalankan SETELAH migrasi-semua.sql dan SEBELUM reset-transaksi.sql. Aman dijalankan ulang.
-- =========================================================

-- ---------- Pengaturan umum ----------
UPDATE pengaturan SET nilai = 'Desa Marga Mulya' WHERE kunci = 'nama_desa';
UPDATE pengaturan SET nilai = 'Kecamatan Mauk' WHERE kunci = 'kecamatan';
UPDATE pengaturan SET nilai = 'Kabupaten Tangerang' WHERE kunci = 'kabupaten';
UPDATE pengaturan SET nilai = 'Kantor Desa Margamulya, Kecamatan Mauk, Kabupaten Tangerang, Banten 15531' WHERE kunci = 'alamat';
UPDATE pengaturan SET nilai = 'Selamat datang di Desa Marga Mulya' WHERE kunci = 'hero_judul';
UPDATE pengaturan SET nilai = 'Desa tepi laut di utara Kabupaten Tangerang. Warganya nelayan, petani, dan pengrajin. Urus keperluan warga, atau kenali pantai, mangrove, dan produk buatan warga kami.' WHERE kunci = 'hero_teks';
-- Titik pusat peta: tengah wilayah Margamulya menurut peta BPS 2025 (perkiraan). Geser ke titik kantor desa yang tepat lewat CMS > Pengaturan.
UPDATE pengaturan SET nilai = '-6.0450' WHERE kunci = 'lat';
UPDATE pengaturan SET nilai = '106.5380' WHERE kunci = 'lng';
UPDATE pengaturan SET nilai = 'Marga Mulya (Margamulya) adalah desa di Kecamatan Mauk, Kabupaten Tangerang, Banten, dengan kode wilayah 36.03.08.2008. Menurut BPS (Kecamatan Mauk Dalam Angka 2025), luasnya 6,14 km² dan dihuni 8.449 jiwa pada 2024, terdiri dari 4.359 laki-laki dan 4.090 perempuan di 5 RW dan 25 RT. Desa ini berbatasan langsung dengan laut di sebelah utara, berjarak 4 km dari kantor Kecamatan Mauk dan 32 km dari ibu kota Kabupaten Tangerang. Sebagian besar warga bekerja sebagai petani, nelayan, wiraswasta, dan pekerja lepas. Potensi desa meliputi sawah sekitar 330 hektare, kelapa, mangrove, hasil laut, serta kerajinan batok kelapa.' WHERE kunci = 'profil_ringkas';
UPDATE pengaturan SET nilai = 'Nama Margamulya lahir dari musyawarah seluruh komponen desa. "Marga" berarti kelompok atau keturunan, dan "Mulya" berarti baik. Warga berharap Margamulya menjadi desa yang baik untuk ditempati dan ditinggali.' WHERE kunci = 'sejarah';
UPDATE pengaturan SET nilai = '(ilustrasi) Terwujudnya Desa Marga Mulya yang guyub, sejahtera, dan tangguh, dengan pesisir dan pertanian yang lestari.' WHERE kunci = 'visi';
UPDATE pengaturan SET nilai = '(ilustrasi) Meningkatkan kesejahteraan nelayan dan pengolah hasil laut.\n(ilustrasi) Melestarikan mangrove dan pesisir sebagai pelindung desa dari rob dan abrasi.\n(ilustrasi) Mengembangkan UMKM olahan hasil laut, kelapa, dan kerajinan batok kelapa.\n(ilustrasi) Menjaga lahan pertanian pangan dan meningkatkan hasil panen petani.\n(ilustrasi) Memberikan pelayanan desa yang cepat, terbuka, dan mudah dijangkau warga.' WHERE kunci = 'misi';
UPDATE pengaturan SET nilai = 'Laut Jawa' WHERE kunci = 'batas_utara';
UPDATE pengaturan SET nilai = 'Desa Tanjung Anom dan Kecamatan Sukadiri' WHERE kunci = 'batas_timur';
UPDATE pengaturan SET nilai = 'Desa Tegal Kunir Lor' WHERE kunci = 'batas_selatan';
UPDATE pengaturan SET nilai = 'Desa Ketapang' WHERE kunci = 'batas_barat';
UPDATE pengaturan SET nilai = 'Bawa botol minum dan wadah makan sendiri.\nJangan menginjak atau mencabut bibit mangrove.\nBuang sampah di tempatnya, terutama plastik di pantai.\nMinta izin sebelum memotret warga atau naik ke perahu nelayan.' WHERE kunci = 'kode_etik_umum';
UPDATE pengaturan SET nilai = '(ilustrasi) Bank Sampah Margamulya buka setiap Sabtu pukul 08.00 sampai 11.00 di Balai RW 03. Sampah ditimbang, dicatat di buku tabungan, dan bisa ditukar uang atau sembako.' WHERE kunci = 'info_bank_sampah';
UPDATE pengaturan SET nilai = 'Data asli: luas wilayah, jarak, jumlah RW dan RT, serta penduduk menurut jenis kelamin dari BPS (Kecamatan Mauk Dalam Angka 2025, data 2024); luas sawah dari pemberitaan 2023. Angka bertanda ilustrasi adalah data contoh yang mengikuti struktur Data EcoQuest IIT Challenge 2026. Perangkat desa dapat menggantinya dengan data resmi kapan saja melalui CMS.' WHERE kunci = 'catatan_data';
UPDATE pengaturan SET nilai = '' WHERE kunci IN ('sambutan_kades', 'foto_kades');

-- ---------- Statistik (struktur mengikuti Data EcoQuest A sampai M) ----------
DELETE FROM statistik;
INSERT INTO statistik (kategori, label, nilai, satuan, urutan, ilustrasi) VALUES
-- A. Kondisi geografis: luas menurut penggunaan (total 614 ha = 6,14 km2 BPS; sawah 330 ha dari berita 2023)
('Lahan', 'Persawahan', 330, 'ha', 1, 0),
('Lahan', 'Pemukiman', 150, 'ha', 2, 1),
('Lahan', 'Pekarangan', 48, 'ha', 3, 1),
('Lahan', 'Prasarana umum lainnya', 34, 'ha', 4, 1),
('Lahan', 'Perkebunan kelapa', 30, 'ha', 5, 1),
('Lahan', 'Mangrove dan sempadan', 12, 'ha', 6, 1),
('Lahan', 'Perkuburan', 6, 'ha', 7, 1),
('Lahan', 'Perkantoran', 2, 'ha', 8, 1),
('Lahan', 'Taman', 2, 'ha', 9, 1),
-- B. Pemerintahan desa
('Pemerintahan', 'RW', 5, 'RW', 1, 0),
('Pemerintahan', 'RT', 25, 'RT', 2, 0),
('Pemerintahan', 'Aparat desa', 6, 'orang', 3, 0),
('Pemerintahan', 'Anggota Linmas', 25, 'orang', 4, 1),
('Pemerintahan', 'Pos kamling', 12, 'pos', 5, 1),
('Pemerintahan', 'Pos polisi', 0, 'pos', 6, 1),
-- C. Kependudukan dan angkatan kerja (BPS 2024: 4.359 laki-laki + 4.090 perempuan = 8.449 jiwa)
('Kependudukan', 'Laki-laki', 4359, 'jiwa', 1, 0),
('Kependudukan', 'Perempuan', 4090, 'jiwa', 2, 0),
('Kependudukan', 'Kepadatan penduduk', 1376.17, 'jiwa/km²', 6, 0),
('Kependudukan', 'Rasio jenis kelamin (laki-laki per 100 perempuan)', 106.58, 'rasio', 7, 0),
('Kependudukan', 'Rumah tangga (KK)', 2470, 'KK', 3, 1),
('Kependudukan', 'Usia kerja (18-56 tahun)', 5120, 'jiwa', 4, 1),
('Kependudukan', 'Pencari kerja terdaftar', 310, 'jiwa', 5, 1),
('Mata pencaharian', 'Nelayan dan buruh nelayan', 1240, 'jiwa', 1, 1),
('Mata pencaharian', 'Petani dan buruh tani', 1080, 'jiwa', 2, 1),
('Mata pencaharian', 'Karyawan swasta dan buruh pabrik', 870, 'jiwa', 3, 1),
('Mata pencaharian', 'Wiraswasta dan pedagang', 690, 'jiwa', 4, 1),
('Mata pencaharian', 'Pengolah hasil laut', 260, 'jiwa', 5, 1),
('Mata pencaharian', 'Pengrajin batok kelapa', 140, 'jiwa', 6, 1),
('Mata pencaharian', 'Peternak', 110, 'jiwa', 7, 1),
('Mata pencaharian', 'Guru', 70, 'jiwa', 8, 1),
('Mata pencaharian', 'PNS', 45, 'jiwa', 9, 1),
('Mata pencaharian', 'TNI/Polri', 15, 'jiwa', 10, 1),
('Mata pencaharian', 'Pekerja lepas dan lainnya', 600, 'jiwa', 11, 1),
-- E. Pendidikan (total 8.449)
('Pendidikan penduduk', 'Belum/tidak sekolah', 1390, 'jiwa', 1, 1),
('Pendidikan penduduk', 'SD/sederajat', 3020, 'jiwa', 2, 1),
('Pendidikan penduduk', 'SMP/sederajat', 1840, 'jiwa', 3, 1),
('Pendidikan penduduk', 'SMA/SMK', 1760, 'jiwa', 4, 1),
('Pendidikan penduduk', 'Diploma', 160, 'jiwa', 5, 1),
('Pendidikan penduduk', 'Sarjana ke atas', 279, 'jiwa', 6, 1),
('Sekolah', 'PAUD/TK', 2, 'unit', 1, 1),
('Sekolah', 'SD/MI', 3, 'unit', 2, 1),
('Sekolah', 'SMP/MTs', 1, 'unit', 3, 1),
('Sekolah', 'Kursus menjahit dan komputer', 2, 'lembaga', 4, 1),
('Sekolah', 'Kejar Paket A, B, C', 1, 'kelompok', 5, 1),
-- F. Kesehatan
('Kesehatan', 'Imunisasi dasar lengkap', 92, '%', 1, 1),
('Kesehatan', 'Balita gizi baik', 89, '%', 2, 1),
('Kesehatan', 'KK dengan air bersih', 84, '%', 3, 1),
('Kesehatan', 'KK dengan jamban sehat', 81, '%', 4, 1),
('Kesehatan', 'Peserta KB aktif', 1310, 'pasangan', 5, 1),
('Fasilitas kesehatan', 'Posyandu', 5, 'unit', 1, 1),
('Fasilitas kesehatan', 'Polindes/Poskesdes', 1, 'unit', 2, 1),
('Fasilitas kesehatan', 'Praktik bidan', 2, 'unit', 3, 1),
('Fasilitas kesehatan', 'Bidan', 2, 'orang', 4, 1),
('Fasilitas kesehatan', 'Kader posyandu', 25, 'orang', 5, 1),
-- G. Pertanian, perikanan, peternakan
('Pertanian', 'Padi', 3600, 'ton/tahun', 1, 1),
('Pertanian', 'Kelapa', 180, 'ton/tahun', 2, 1),
('Pertanian', 'Sayuran', 90, 'ton/tahun', 3, 1),
('Pertanian', 'Kelompok tani', 4, 'kelompok', 4, 1),
('Perikanan', 'Hasil tangkapan laut', 210, 'ton/tahun', 1, 1),
('Perikanan', 'Bandeng tambak', 75, 'ton/tahun', 2, 1),
('Perikanan', 'Kelompok nelayan', 3, 'kelompok', 3, 1),
('Perikanan', 'Perahu motor tempel', 140, 'unit', 4, 1),
('Perikanan', 'Terasi dan ikan asin olahan warga', 18, 'ton/tahun', 5, 1),
('Peternakan', 'Ayam', 7800, 'ekor', 1, 1),
('Peternakan', 'Itik', 1900, 'ekor', 2, 1),
('Peternakan', 'Puyuh', 600, 'ekor', 3, 1),
('Peternakan', 'Kambing', 420, 'ekor', 4, 1),
('Peternakan', 'Sapi', 60, 'ekor', 5, 1),
('Peternakan', 'Kerbau', 35, 'ekor', 6, 1),
-- H. Ekonomi
('Ekonomi', 'Industri rumah tangga makanan', 38, 'usaha', 1, 1),
('Ekonomi', 'Kerajinan batok kelapa', 12, 'usaha', 2, 1),
('Ekonomi', 'Olahan hasil laut', 15, 'usaha', 3, 1),
('Ekonomi', 'Koperasi', 1, 'unit', 4, 1),
('Ekonomi', 'BUMDes', 1, 'unit', 5, 1),
('Ekonomi', 'Pasar tanpa bangunan', 1, 'unit', 6, 1),
-- I. Infrastruktur
('Jalan desa', 'Aspal', 5.2, 'km', 1, 1),
('Jalan desa', 'Beton', 4.1, 'km', 2, 1),
('Jalan desa', 'Tanah', 2.3, 'km', 3, 1),
('Jalan desa', 'Makadam', 1.8, 'km', 4, 1);

-- ---------- Aparat desa (Wikipedia mencatat 6 aparat; kepala desa perlu dikonfirmasi ke kantor desa) ----------
DELETE FROM aparat;
INSERT INTO aparat (id, jabatan, nama, urutan) VALUES
(1, 'Kepala Desa', 'Abu Bakar', 1),
(2, 'Sekretaris Desa', 'Nama sekretaris (ilustrasi)', 2),
(3, 'Kaur Keuangan', 'Nama kaur keuangan (ilustrasi)', 3),
(4, 'Kaur Perencanaan', 'Nama kaur perencanaan (ilustrasi)', 4),
(5, 'Kasi Pemerintahan', 'Nama kasi pemerintahan (ilustrasi)', 5),
(6, 'Kasi Pelayanan', 'Nama kasi pelayanan (ilustrasi)', 6);

-- ---------- Wisata (4 baris lama diganti isinya; id tetap supaya reservasi lama tidak rusak) ----------
UPDATE wisata SET slug = 'pantai-pasir-putih', nama = 'Pantai Pasir Putih Margamulya', jenis = 'Alam', harga = 5000, kuota = 300, jam_buka = '06.00 sampai 18.00',
  ringkas = 'Pantai warga dengan warung dan bale yang direvitalisasi bersama mahasiswa pada 2025.',
  deskripsi = 'Kawasan pesisir Margamulya tempat warga dan pengunjung bersantai sambil menikmati angin laut. Pada 2025, warung dan bale di Pantai Pasir Putih direvitalisasi lewat program pemberdayaan Universitas Pembangunan Jaya, termasuk 14 warung pantai. Harga tiket, kuota, dan jam buka di halaman ini adalah ilustrasi.',
  fasilitas = 'Warung pantai, Bale, Parkir (ilustrasi)', tips = 'Datang pagi atau menjelang sore supaya tidak terlalu panas. Hindari bermain air saat ombak tinggi atau angin kencang.',
  kode_etik = 'Bawa pulang sampahmu, jangan dibuang ke laut.\nBelanja di warung warga untuk mendukung ekonomi pesisir.', lat = -6.0300, lng = 106.5330,
  foto = '/assets/img/pantai.svg', tingkat_jalur = 'Mudah', ramah_kursi_roda = 0, ramah_lansia = 1, ada_toilet = 1,
  catatan_akses = '(ilustrasi) Jalan desa sampai area parkir, lalu berjalan di pasir sekitar 100 meter.', saat_hujan = 0
WHERE id = 2;
UPDATE wisata SET slug = 'kampung-nelayan', nama = 'Wisata Kampung Nelayan (ilustrasi)', jenis = 'Edukasi', harga = 15000, kuota = 60, jam_buka = '07.00 sampai 11.00',
  ringkas = 'Melihat perahu nelayan bersandar dan belajar mengolah ikan asin serta terasi bersama warga.',
  deskripsi = 'Pagi hari, perahu nelayan Margamulya kembali dari melaut membawa ikan dan udang rebon. Pengunjung diajak melihat hasil tangkapan, belajar menjemur ikan asin, dan mencoba membuat terasi bersama ibu-ibu pengolah hasil laut. Paket kegiatan, harga, dan jam adalah ilustrasi.',
  fasilitas = 'Pemandu warga, Area jemur ikan, Warung (ilustrasi)', tips = 'Datang pagi saat perahu kembali dari melaut. Pakai alas kaki yang boleh basah.',
  kode_etik = 'Minta izin sebelum naik ke perahu atau memotret nelayan.\nJangan menginjak ikan yang sedang dijemur.', lat = -6.0320, lng = 106.5360,
  foto = '/assets/img/perahu.svg', tingkat_jalur = 'Mudah', ramah_kursi_roda = 0, ramah_lansia = 1, ada_toilet = 1,
  catatan_akses = '(ilustrasi) Jalan desa sampai dermaga kecil, lalu jalan kaki sekitar 50 meter.', saat_hujan = 0
WHERE id = 1;
UPDATE wisata SET slug = 'batok-kelapa', nama = 'Kelas Kerajinan Batok Kelapa (ilustrasi)', jenis = 'Budaya', harga = 25000, kuota = 30, jam_buka = '09.00 sampai 15.00',
  ringkas = 'Membuat mangkuk, gantungan kunci, atau briket dari batok kelapa bersama pengrajin warga.',
  deskripsi = 'Kelapa melimpah di Margamulya, dan pengrajin warga mengolah batoknya menjadi kerajinan serta briket arang, yang juga dikembangkan lewat program pemberdayaan Universitas Pembangunan Jaya pada 2025. Kelas berlangsung di dalam ruangan sehingga tetap berjalan saat hujan. Nama kelas, harga, dan kuota adalah ilustrasi.',
  fasilitas = 'Ruang kelas, Alat dan bahan, Hasil karya dibawa pulang (ilustrasi)', tips = 'Cocok untuk rombongan sekolah dan keluarga. Anak di bawah 10 tahun didampingi orang dewasa saat memakai amplas dan alat bor.',
  kode_etik = 'Ikuti arahan pengrajin saat memakai alat.\nSisa batok dikumpulkan untuk dijadikan briket.', lat = -6.0450, lng = 106.5410,
  foto = '/assets/img/kelapa.svg', tingkat_jalur = 'Mudah', ramah_kursi_roda = 1, ramah_lansia = 1, ada_toilet = 1,
  catatan_akses = '(ilustrasi) Ruang kelas di lantai dasar dan bisa dilalui kursi roda.', saat_hujan = 1
WHERE id = 3;
UPDATE wisata SET slug = 'mangrove', nama = 'Edukasi Mangrove Pesisir Margamulya', jenis = 'Alam', harga = 10000, kuota = 80, jam_buka = '07.00 sampai 16.00',
  ringkas = 'Mengenal hutan bakau dan ikut menanam bibit mangrove di pesisir desa.',
  deskripsi = 'Ekosistem mangrove di pesisir Margamulya bernilai ekonomi dan ekologis: menahan abrasi, menjadi tempat ikan bertelur, dan bahan olahan warga. Pengunjung bisa belajar mengenal jenis bakau lalu menanam bibit bersama kelompok warga. Paket kegiatan, harga, dan kuota di halaman ini adalah ilustrasi.',
  fasilitas = 'Bibit mangrove, Pemandu warga, Tempat cuci kaki (ilustrasi)', tips = 'Pakai sepatu yang boleh basah. Periksa jadwal pasang surut, menanam paling mudah saat air surut.',
  kode_etik = 'Jangan mencabut atau mematahkan akar bakau.\nTanam bibit hanya di titik yang ditunjuk pemandu.', lat = -6.0330, lng = 106.5290,
  foto = '/assets/img/mangrove.svg', tingkat_jalur = 'Sedang', ramah_kursi_roda = 0, ramah_lansia = 0, ada_toilet = 0,
  catatan_akses = '(ilustrasi) Jalan setapak berlumpur menuju titik tanam, sekitar 300 meter dari parkir.', saat_hujan = 0
WHERE id = 4;

-- ---------- UMKM (jenis produk asli dari laporan UPJ 2025; nama usaha dan harga ilustrasi) ----------
UPDATE umkm SET nama_usaha = 'Kerupuk Ikan Bu Darmi (ilustrasi)', kategori = 'Olahan hasil laut', produk = 'Kerupuk ikan siap goreng', harga = 15000, satuan = '250 g', pemilik = 'Bu Darmi, RT 03 (ilustrasi)', lat = -6.0420, lng = 106.5370,
  deskripsi = 'Kerupuk dari ikan tangkapan nelayan pesisir, dijemur di bawah matahari dan dikemas ulang supaya awet. Contoh olahan hasil laut yang menjadi salah satu potensi UMKM Margamulya.' WHERE id = 1;
UPDATE umkm SET nama_usaha = 'Kerajinan Batok Rukun (ilustrasi)', kategori = 'Kerajinan', produk = 'Mangkuk dan gelas batok kelapa', harga = 25000, satuan = 'buah', pemilik = 'Kelompok Rukun, RW 02 (ilustrasi)', lat = -6.0450, lng = 106.5410, foto = '/assets/img/kelapa.svg',
  deskripsi = 'Batok kelapa dihaluskan, dipernis, lalu dibentuk menjadi mangkuk, gelas, dan gantungan kunci. Kerajinan batok kelapa adalah salah satu produk unggulan warga Margamulya.' WHERE id = 2;
UPDATE umkm SET nama_usaha = 'Minyak Kelapa Mulya (ilustrasi)', kategori = 'Olahan kelapa', produk = 'Minyak kelapa murni (VCO)', harga = 35000, satuan = 'botol 250 ml', pemilik = 'Kelompok Wanita Tani, RW 04 (ilustrasi)', lat = -6.0480, lng = 106.5440, foto = '/assets/img/kelapa.svg',
  deskripsi = 'Minyak kelapa murni yang dibuat dari kelapa kebun warga tanpa pemanasan tinggi. Bisa untuk memasak atau perawatan kulit.' WHERE id = 3;
UPDATE umkm SET nama_usaha = 'Bandeng Presto Bu Nur (ilustrasi)', kategori = 'Olahan hasil laut', produk = 'Bandeng presto duri lunak', harga = 45000, satuan = '2 ekor (500 g)', pemilik = 'Bu Nur, RT 05 (ilustrasi)', lat = -6.0360, lng = 106.5350, foto = '/assets/img/ikan.svg',
  deskripsi = 'Bandeng dari tambak pesisir dimasak presto sampai durinya lunak, jadi aman untuk anak dan lansia. Tahan dua hari di suhu ruang dan seminggu di kulkas.' WHERE id = 4;
UPDATE umkm SET nama_usaha = 'Briket Batok Mak Ijah (ilustrasi)', kategori = 'Kerajinan', produk = 'Briket arang batok kelapa', harga = 20000, satuan = 'kg', pemilik = 'Mak Ijah, RT 07 (ilustrasi)', lat = -6.0400, lng = 106.5400, foto = '/assets/img/kegiatan.svg',
  deskripsi = 'Briket dari sisa batok kelapa, panasnya tahan lama dan asapnya sedikit. Cocok untuk bakar ikan dan sate. Pembuatan briket batok kelapa dikembangkan warga sejak program pemberdayaan 2025.' WHERE id = 5;
UPDATE umkm SET nama_usaha = 'Rengginang Kang Ujang (ilustrasi)', kategori = 'Makanan', produk = 'Rengginang ketan rebon', harga = 20000, satuan = '300 g', pemilik = 'Kang Ujang, RT 11 (ilustrasi)', lat = -6.0500, lng = 106.5360,
  deskripsi = 'Rengginang ketan dengan udang rebon dari pesisir, dijemur alami. Produk pangan khas rumahan yang biasa dijadikan oleh-oleh.' WHERE id = 6;
UPDATE ulasan_umkm SET usaha = (SELECT nama_usaha FROM umkm WHERE umkm.id = ulasan_umkm.umkm_id);
UPDATE ulasan_umkm SET komentar = 'Kerupuknya gurih dan rasa ikannya terasa. Langsung pesan lagi untuk oleh-oleh kantor.' WHERE id = 101;
UPDATE ulasan_umkm SET komentar = 'Mangkuk batoknya halus dan unik, saya pakai untuk hampers. Ibu-ibunya ramah dan mau menyesuaikan ukuran.' WHERE id = 103;
UPDATE ulasan_umkm SET komentar = 'Minyaknya jernih dan wangi kelapa. Saya pakai untuk memasak dan untuk kulit anak.' WHERE id = 104;
UPDATE ulasan_umkm SET komentar = 'Bandengnya gurih dan durinya benar-benar lunak. Anak saya suka.' WHERE id = 105;
UPDATE ulasan_umkm SET komentar = 'Briketnya awet, dipakai bakar ikan satu sore masih menyala. Asapnya juga tidak banyak.' WHERE id = 106;
UPDATE ulasan_umkm SET komentar = 'Rengginangnya mekar besar waktu digoreng dan rebonnya terasa. Cocok untuk teman makan.' WHERE id = 107;

-- ---------- Fasilitas (titik peta = perkiraan, ilustrasi; geser lewat CMS) ----------
DELETE FROM fasilitas;
INSERT INTO fasilitas (id, nama, kategori, alamat, keterangan, lat, lng) VALUES
(1, 'Kantor Desa Margamulya', 'Pemerintahan', 'Desa Margamulya, Kecamatan Mauk', 'Layanan administrasi Senin sampai Jumat (jam layanan ilustrasi). Titik peta perkiraan.', -6.0450, 106.5380),
(2, 'SD Negeri Margamulya', 'Pendidikan', 'Kp. Bebulak', 'Sekolah dasar negeri, berdiri 1983 (Dapodik). Titik peta perkiraan.', -6.0490, 106.5340),
(3, 'Kampung KB Bebulak', 'Kesehatan', 'RW 03, Dusun Bebulak', 'Kampung Keluarga Berkualitas binaan BKKBN. Titik peta perkiraan.', -6.0495, 106.5350),
(4, 'Posyandu RW 01 (ilustrasi)', 'Kesehatan', 'RW 01', 'Penimbangan balita dan imunisasi setiap bulan (ilustrasi).', -6.0420, 106.5420),
(5, 'Balai Desa dan Lapangan (ilustrasi)', 'Olahraga dan ruang publik', 'Dekat kantor desa', 'Tempat musyawarah dan kegiatan warga (ilustrasi).', -6.0455, 106.5370),
(6, 'Bank Sampah Margamulya (ilustrasi)', 'Lingkungan', 'Balai RW 03', 'Buka setiap Sabtu pagi (ilustrasi).', -6.0480, 106.5350),
(7, 'Warung Pantai Pasir Putih', 'Ekonomi', 'Pesisir Margamulya', '14 warung pantai direvitalisasi pada 2025 (UPJ). Titik peta perkiraan.', -6.0305, 106.5335),
(8, 'Pasar kaget Minggu pagi (ilustrasi)', 'Ekonomi', 'Jalan desa utama', 'Pasar tanpa bangunan, ramai Minggu pagi (ilustrasi).', -6.0470, 106.5400);

-- ---------- Zona rawan bencana (ilustrasi; wilayah pantura Tangerang rawan banjir, rob, dan abrasi) ----------
DELETE FROM zona_rawan;
INSERT INTO zona_rawan (id, nama, jenis, lat, lng, radius_m, keterangan) VALUES
(1, 'Pesisir Pantai Pasir Putih (ilustrasi)', 'Rob dan abrasi', -6.0290, 106.5330, 350, 'Air pasang bisa masuk ke daratan saat bulan purnama dan angin kencang. Jangan bermain air saat ombak tinggi.'),
(2, 'Dataran sawah dekat saluran Cikait (ilustrasi)', 'Banjir', -6.0560, 106.5395, 300, 'Air bisa meluap saat hujan lebat lebih dari 2 jam. Jauhi saluran yang arusnya deras.'),
(3, 'Kawasan mangrove dan tambak (ilustrasi)', 'Rob dan abrasi', -6.0330, 106.5290, 250, 'Jalan setapak tergenang saat pasang tinggi. Datang saat air surut dan bersama pemandu.');

-- ---------- Pembangunan (ilustrasi; saluran Cikait/Cikuil disebut dinormalisasi dalam berita 2023) ----------
UPDATE pembangunan SET nama = 'Perbaikan tambatan perahu nelayan (ilustrasi)', lokasi = 'Pesisir utara Margamulya', tahun = 2026, anggaran = 185000000, sumber_dana = 'Dana Desa', progres = 100, status = 'Selesai',
  deskripsi = 'Tambatan perahu diperkuat supaya perahu nelayan aman saat air pasang dan angin kencang.', lat = -6.0315, lng = 106.5355 WHERE id = 1;
UPDATE pembangunan SET nama = 'Pemeliharaan saluran Cikait dan Cikuil (ilustrasi)', lokasi = 'Area persawahan Margamulya', tahun = 2026, anggaran = 220000000, sumber_dana = 'Dana Desa', progres = 65, status = 'Berjalan',
  deskripsi = 'Melanjutkan normalisasi saluran pembuang supaya sawah tidak tergenang dan air tetap lancar saat musim tanam.', lat = -6.0550, lng = 106.5390 WHERE id = 2;
UPDATE pembangunan SET nama = 'Penataan bale dan warung Pantai Pasir Putih (ilustrasi)', lokasi = 'Pesisir Margamulya', tahun = 2026, anggaran = 95000000, sumber_dana = 'Pendapatan asli desa', progres = 40, status = 'Berjalan',
  deskripsi = 'Melanjutkan revitalisasi warung pantai tahun 2025 dengan tempat sampah terpilah dan toilet umum.', lat = -6.0300, lng = 106.5330 WHERE id = 3;
UPDATE pembangunan SET nama = 'Rumah pengolahan sampah TPS3R (ilustrasi)', lokasi = 'Dekat Balai RW 03', tahun = 2026, anggaran = 150000000, sumber_dana = 'Bantuan provinsi', progres = 0, status = 'Perencanaan',
  deskripsi = 'Tempat memilah sampah organik menjadi kompos dan plastik untuk bank sampah, supaya sampah tidak berakhir di laut.', lat = -6.0480, lng = 106.5350 WHERE id = 4;

-- ---------- Program bantuan (ilustrasi; id tetap karena data penerima menempel) ----------
UPDATE bantuan SET nama = 'Bantuan Langsung Tunai Dana Desa (ilustrasi)', penyelenggara = 'Pemerintah Desa Marga Mulya' WHERE id = 1;
UPDATE bantuan SET nama = 'Bantuan alat keselamatan melaut (ilustrasi)', penyelenggara = 'Dinas Perikanan Kabupaten Tangerang', sasaran = 'Nelayan kecil yang terdaftar di kelompok nelayan', syarat = 'Terdaftar sebagai anggota kelompok nelayan\nMemiliki Kartu KUSUKA (Kartu Pelaku Usaha Kelautan dan Perikanan)', jadwal = 'Pendataan melalui ketua kelompok nelayan (ilustrasi)' WHERE id = 2;
UPDATE bantuan SET nama = 'Pelatihan dan modal usaha olahan kelapa (ilustrasi)', penyelenggara = 'BUMDes Marga Mulya', sasaran = 'Pengrajin batok kelapa, pengolah hasil laut, dan pemuda yang ingin memulai usaha', syarat = 'Berusia 17 tahun ke atas\nBersedia mengikuti pelatihan 3 hari', jadwal = 'Dibuka dua kali setahun', cara_daftar = 'Isi formulir di kantor BUMDes atau kirim pesan lewat halaman Kontak dengan topik Layanan administrasi.' WHERE id = 3;

-- ---------- Budaya dan kegiatan rutin (ilustrasi) ----------
DELETE FROM budaya;
INSERT INTO budaya (id, judul, waktu, isi) VALUES
(1, 'Sedekah laut (ilustrasi)', 'Setahun sekali', 'Syukuran nelayan atas hasil laut, diisi doa bersama, arak-arakan perahu, dan makan bersama warga.'),
(2, 'Gotong royong bersih pantai (ilustrasi)', 'Minggu pertama setiap bulan', 'Warga, pemuda, dan pengunjung memungut sampah plastik di pesisir lalu menyetorkannya ke bank sampah.'),
(3, 'Tanam mangrove bersama (ilustrasi)', 'Musim hujan', 'Warga dan pengunjung menanam bibit bakau di pesisir untuk menahan abrasi.');

-- ---------- Agenda (id 2 dan 4 dipakai data relawan) ----------
DELETE FROM agenda WHERE id NOT IN (2, 4);
UPDATE agenda SET judul = 'Tanam mangrove bersama (ilustrasi)', tempat = 'Pesisir utara Margamulya', jam = '07.00 sampai 10.00', isi = 'Menanam bibit bakau di titik yang rawan abrasi. Terbuka untuk warga, pelajar, dan pengunjung. Datang saat air surut.' WHERE id = 2;
UPDATE agenda SET judul = 'Gotong royong bersih pantai (ilustrasi)', tempat = 'Pantai Pasir Putih Margamulya', jam = '06.30 sampai 09.30', isi = 'Memungut sampah plastik di pesisir dan menanam bibit mangrove. Sarung tangan dan karung disediakan.' WHERE id = 4;
INSERT INTO agenda (id, judul, tanggal, jam, tempat, isi, relawan_kuota) VALUES
(1, 'Posyandu RW 01 (ilustrasi)', '2026-11-21', '08.00 sampai 11.00', 'Posyandu RW 01', 'Penimbangan balita, imunisasi, dan pemberian makanan tambahan.', 0),
(5, 'Posyandu Kampung KB Bebulak (ilustrasi)', '2026-12-05', '08.00 sampai 11.00', 'RW 03, Dusun Bebulak', 'Penimbangan balita dan penyuluhan KB.', 0),
(101, 'Musyawarah desa penetapan penerima bantuan (ilustrasi)', '2026-11-27', '13.00 sampai 15.00', 'Balai desa', 'Pembahasan usulan penerima bantuan bersama ketua RT, RW, dan BPD.', 0),
(102, 'Penyuluhan keselamatan melaut (ilustrasi)', '2026-12-02', '09.00 sampai 12.00', 'Balai desa', 'Penggunaan pelampung, membaca prakiraan gelombang, dan pendataan Kartu KUSUKA untuk nelayan.', 0),
(103, 'Pelatihan olahan ikan untuk ibu-ibu nelayan (ilustrasi)', '2026-12-09', '09.00 sampai 14.00', 'Balai desa', 'Membuat bandeng presto, abon ikan, dan kemasan yang menarik untuk dijual.', 0);
UPDATE relawan SET agenda_judul = (SELECT judul FROM agenda WHERE agenda.id = relawan.agenda_id);

-- ---------- Berita (ilustrasi, kecuali yang menyebut sumber) ----------
DELETE FROM berita;
INSERT INTO berita (id, judul, tanggal, isi, foto) VALUES
(1, 'Warung dan bale Pantai Pasir Putih direvitalisasi bersama mahasiswa', '2026-11-15', 'Pada 2025, mahasiswa Universitas Pembangunan Jaya lewat program SENANDUNG merevitalisasi warung dan bale di Pantai Pasir Putih Margamulya, termasuk 14 warung pantai, serta melatih pelaku UMKM tentang kewirausahaan dan pemasaran digital.\n\nLangkah ini menjadi awal penataan pesisir sebagai tujuan wisata warga. (Rencana lanjutan pada berita ini adalah ilustrasi.)', '/assets/img/pantai.svg'),
(2, 'Pendataan Kartu KUSUKA untuk nelayan dibuka (ilustrasi)', '2026-11-11', 'Nelayan Margamulya yang belum memiliki Kartu Pelaku Usaha Kelautan dan Perikanan (KUSUKA) bisa mendaftar lewat ketua kelompok nelayan. Kartu ini menjadi syarat berbagai bantuan perikanan.\n\n(Jadwal dan teknis pendataan adalah ilustrasi.)', '/assets/img/perahu.svg'),
(3, 'Ibu-ibu nelayan belajar membuat bandeng presto (ilustrasi)', '2026-11-06', 'Pelatihan olahan ikan diikuti ibu-ibu dari kampung nelayan. Hasilnya dijual di warung Pantai Pasir Putih dan dititipkan lewat halaman UMKM.', '/assets/img/ikan.svg'),
(101, 'Website desa kini bisa dipakai mengajukan surat dari rumah', '2026-11-16', 'Warga Margamulya bisa mengajukan surat keterangan dari HP, memantau statusnya, lalu datang ke kantor desa saat surat sudah siap. (Tanggal peluncuran adalah ilustrasi.)', '/assets/img/foto/47c5aaa5.jpg'),
(102, 'Pemuda belajar membuat briket dari batok kelapa (ilustrasi)', '2026-11-08', 'Sisa batok kelapa yang biasanya dibuang kini diolah menjadi briket arang. Pelatihan diikuti pemuda dan ibu-ibu dari lima RW.', '/assets/img/kegiatan.svg'),
(103, 'Waspada rob saat bulan purnama (ilustrasi)', '2026-11-04', 'Air pasang tinggi bisa masuk ke kawasan pesisir saat bulan purnama dan angin kencang. Warga dan pengunjung diminta tidak bermain air saat ombak tinggi dan memantau peta zona rawan di website.', '/assets/img/pantai.svg'),
(104, 'Bank sampah kumpulkan 11,9 ton sampah (ilustrasi)', '2026-10-28', 'Sampah plastik yang dikumpulkan warga tidak lagi berakhir di saluran dan pantai, tetapi ditabung di bank sampah dan bisa ditukar uang.', '/assets/img/kegiatan.svg');

-- ---------- Galeri ----------
DELETE FROM galeri;
INSERT INTO galeri (id, judul, kategori, foto) VALUES
(105, 'Perahu nelayan Margamulya (gambar ilustrasi)', 'Alam', '/assets/img/perahu.svg'),
(106, 'Edukasi di posyandu (foto ilustrasi)', 'Kegiatan', '/assets/img/foto/47c5aaa5.jpg'),
(107, 'Pantai Pasir Putih (gambar ilustrasi)', 'Alam', '/assets/img/pantai.svg'),
(108, 'Rengginang rebon buatan warga (foto ilustrasi)', 'Kegiatan', '/assets/img/foto/64cc8c9f.jpg'),
(109, 'Mangrove pesisir (gambar ilustrasi)', 'Alam', '/assets/img/mangrove.svg'),
(110, 'Kerajinan batok kelapa (foto ilustrasi)', 'Kegiatan', '/assets/img/foto/70c97b14.jpg');

-- ---------- FAQ ----------
UPDATE faq SET jawaban = 'Ada. Kelas Kerajinan Batok Kelapa berlangsung di dalam ruangan, jadi tetap berjalan saat hujan. Untuk pantai dan mangrove, sebaiknya tunda kunjungan saat hujan deras atau angin kencang.' WHERE id = 21;
UPDATE faq SET jawaban = 'Hewan peliharaan boleh dibawa ke pantai asal memakai tali dan kotorannya dibersihkan. Hewan tidak boleh masuk ke petak sawah dan area tanam mangrove.' WHERE id = 22;
UPDATE faq SET pertanyaan = 'Apakah ada tempat makan dan toilet?', jawaban = 'Warung warga tersedia di Pantai Pasir Putih. Toilet tersedia di area pantai dan balai desa (ilustrasi).' WHERE id = 23;
UPDATE faq SET jawaban = 'Drone tidak boleh diterbangkan di atas sawah dan permukiman tanpa izin pengelola. Minta izin ke petugas desa lebih dulu.' WHERE id = 3;

-- ---------- Asisten desa: menu pilihan (ubah lewat CMS > Asisten desa) ----------
-- Tulisan {jam_layanan}, {alamat}, {wa}, dan {email} diganti otomatis dengan isi Pengaturan.
DELETE FROM asisten_menu;
INSERT INTO asisten_menu (id, induk_id, judul, pertanyaan, jawaban, tautan, label_tautan, urutan, aktif) VALUES
(1, 0, 'Layanan surat', 'Surat apa yang Anda perlukan?', '', '', '', 1, 1),
(2, 0, 'Bantuan sosial', 'Apa yang ingin Anda ketahui soal bantuan?', '', '', '', 2, 1),
(3, 0, 'Wisata dan kunjungan rombongan', 'Pilih informasi wisata:', '', '', '', 3, 1),
(4, 0, 'Produk UMKM warga', 'Mau membeli atau menitipkan produk?', '', '', '', 4, 1),
(5, 0, 'Aspirasi dan pengaduan', 'Pilih yang Anda perlukan:', '', '', '', 5, 1),
(6, 0, 'Nomor darurat', '', 'Dalam keadaan darurat, langsung telepon:\n112 untuk semua keadaan darurat\n110 Polisi\n113 Pemadam kebakaran\n119 Ambulans\nNomor lengkap ada di halaman Kontak.', '/kontak#darurat', 'Lihat semua nomor darurat', 6, 1),
(7, 0, 'Jam layanan dan alamat kantor desa', '', 'Kantor desa buka {jam_layanan}.\nAlamat: {alamat}\nWhatsApp: +{wa}', '/kontak', 'Buka halaman Kontak', 7, 1),
(11, 1, 'Surat keterangan tidak mampu (SKTM)', '', 'Ajukan lewat website supaya petugas bisa menyiapkannya lebih dulu. Saat mengambil surat, bawa surat pengantar RT dan RW, KTP, dan Kartu Keluarga asli. Syarat lengkap tertulis di halaman Layanan surat.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 1, 1),
(12, 1, 'Surat keterangan domisili', '', 'Ajukan lewat website, lalu ambil di kantor desa dengan membawa surat pengantar RT dan RW serta KTP asli. Syarat lengkap tertulis di halaman Layanan surat.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 2, 1),
(13, 1, 'Surat keterangan usaha', '', 'Cocok untuk nelayan, pedagang, atau pengrajin yang butuh surat untuk pinjaman atau bantuan usaha. Ajukan lewat website, lalu bawa KTP dan foto tempat usaha saat mengambil surat.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 3, 1),
(14, 1, 'Surat pengantar nikah', '', 'Ajukan lewat website, lalu datang bersama calon pengantin membawa KTP, Kartu Keluarga, dan pas foto. Petugas akan menjelaskan langkah berikutnya ke KUA.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 4, 1),
(15, 1, 'Cek status surat saya', '', 'Masukkan kode pengajuan yang diawali SRT- di halaman Layanan surat untuk melihat apakah surat sudah siap diambil.', '/data-desa?grup=Layanan+surat&srt=#surat', 'Cek status surat', 5, 1),
(21, 2, 'Cara cek apakah saya penerima bantuan', '', 'Buka halaman Pantau bansos, lalu ketik nama dan RT. Demi privasi, nama penerima hanya ditampilkan sebagian.', '/data-desa?grup=Pantau+bansos#cek-bansos', 'Cek penerima bantuan', 1, 1),
(22, 2, 'Cara mengusulkan warga yang layak dibantu', '', 'Sampaikan usulan ke ketua RT atau lewat aspirasi warga dengan kategori Bantuan sosial. Usulan dibahas di musyawarah desa.', '/kontak#aspirasi', 'Kirim usulan', 2, 1),
(23, 2, 'Bantuan untuk nelayan', '', 'Bantuan perikanan biasanya mensyaratkan Kartu KUSUKA dan keanggotaan kelompok nelayan. Tanyakan pendataan ke ketua kelompok nelayan atau kantor desa.', '/data-desa?grup=Pantau+bansos', 'Lihat program bantuan', 3, 1),
(31, 3, 'Tempat wisata, harga, dan jam buka', '', 'Ada Pantai Pasir Putih, kawasan mangrove, kampung nelayan, dan kelas kerajinan batok kelapa. Harga dan jam buka tiap tempat ada di halaman Wisata.', '/wisata', 'Lihat semua wisata', 1, 1),
(32, 3, 'Pesan kunjungan rombongan', '', 'Rombongan minimal 10 orang (sekolah, kampus, komunitas) sebaiknya memesan dulu supaya pemandu dan tempat disiapkan. Pengunjung perorangan dan keluarga cukup datang langsung.', '/reservasi', 'Pesan kunjungan rombongan', 2, 1),
(33, 3, 'Waktu terbaik berkunjung dan info rob', '', 'Datang pagi atau sore supaya tidak terlalu panas. Hindari bermain air saat ombak tinggi atau bulan purnama karena air pasang bisa naik. Prakiraan cuaca ada di halaman Wisata.', '/wisata', 'Lihat prakiraan cuaca', 3, 1),
(41, 4, 'Cara memesan produk', '', 'Pilih produk di halaman UMKM, lalu tekan Pesan lewat WhatsApp. Pesan langsung terkirim ke pembuatnya. Harga dan pembayaran disepakati dengan penjual.', '/umkm', 'Lihat produk UMKM', 1, 1),
(42, 4, 'Cara menitipkan produk saya', '', 'Isi form Ajukan produk di halaman UMKM. Petugas desa meninjau 1 sampai 2 hari kerja, lalu produk tampil di website. Gratis.', '/umkm#ajukan', 'Ajukan produk', 2, 1),
(51, 5, 'Kirim aspirasi atau laporan', '', 'Tulis masalahnya, misalnya jalan rusak atau lampu mati, dan lampirkan foto bila ada. Anda mendapat kode untuk memantau tindak lanjutnya.', '/kontak#aspirasi', 'Kirim aspirasi', 1, 1),
(52, 5, 'Lihat perkembangan aspirasi warga', '', 'Semua aspirasi yang sudah diperiksa petugas tampil di Papan Aspirasi beserta statusnya.', '/kontak#papan', 'Buka Papan Aspirasi', 2, 1);
