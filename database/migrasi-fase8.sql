-- =========================================================
-- MIGRASI FASE 8 (JALANKAN SEKALI SAJA, setelah migrasi-fase7b.sql)
-- Menambah: aspirasi warga, pembangunan, APBDes, program bantuan,
-- nomor darurat, fasilitas umum, bank sampah, dan jejak karbon reservasi.
-- Data lama tidak dihapus. Semua isi contoh di bawah boleh diganti lewat CMS.
-- Kalau muncul "Duplicate column name" atau "already exists",
-- migrasi sudah pernah dijalankan.
-- =========================================================

ALTER TABLE reservasi ADD COLUMN kendaraan VARCHAR(30) NOT NULL DEFAULT '';
ALTER TABLE reservasi ADD COLUMN jarak_km INT NOT NULL DEFAULT 0;
ALTER TABLE reservasi ADD COLUMN emisi_kg DECIMAL(8,2) NOT NULL DEFAULT 0;

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

INSERT INTO pengaturan (kunci, nilai) VALUES
('info_bank_sampah', 'Bank Sampah Mulya Lestari buka setiap Sabtu pukul 08.00 sampai 11.00 di Balai RW 02. Tabungan sampah bisa diambil tunai atau dipakai membayar iuran warga.'),
('harga_sampah', 'Botol plastik PET|Rp3.000 per kg\nKardus dan kertas|Rp1.500 per kg\nKaleng aluminium|Rp10.000 per kg\nMinyak jelantah|Rp4.000 per liter');

-- Akun pengelola data ikut mengelola data transparansi
UPDATE admin SET akses = CONCAT(akses, ',apbdes,pembangunan,bantuan,sampah,fasilitas') WHERE akses LIKE '%statistik%' AND akses NOT LIKE '%apbdes%';

-- ---------- Contoh isi (ganti dengan data asli desa lewat CMS) ----------
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
