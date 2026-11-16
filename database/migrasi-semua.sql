-- =========================================================
-- MIGRASI SEMUA (Fase 7A, 7B, 8, dan 9 sekaligus)
-- AMAN dijalankan berkali-kali: yang sudah ada dilewati, data tidak dihapus,
-- isian admin di CMS tidak ditimpa.
-- Pakai file ini kalau web menampilkan "Kode gangguan DB-STRUKTUR",
-- atau kalau ragu migrasi mana yang sudah dijalankan.
-- Untuk TiDB Cloud dan MariaDB (XAMPP).
-- =========================================================

-- ######### FASE 7A: akses petugas, kode etik #########
ALTER TABLE admin ADD COLUMN IF NOT EXISTS akses TEXT NULL;
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS kode_etik TEXT NULL;

-- Akun lama berperan Operator menjadi Petugas dengan akses yang sama seperti sebelumnya
UPDATE admin SET peran = 'Petugas', akses = 'reservasi,pesan,wisata,budaya,umkm,agenda,berita,galeri,statistik,profil,aparat'
WHERE peran = 'Operator';

-- Kode etik khusus tiap destinasi contoh
UPDATE wisata SET kode_etik = 'Berjalan hanya di pematang dan jalur yang disediakan, jangan menginjak tanaman padi.\nJangan menerbangkan drone di atas sawah tanpa izin pengelola.' WHERE slug = 'terasering' AND kode_etik IS NULL;
UPDATE wisata SET kode_etik = 'Dilarang memakai sabun, sampo, atau deterjen di kolam curug.\nIkuti petunjuk petugas saat jalur ditutup karena hujan.' WHERE slug = 'curug' AND kode_etik IS NULL;
UPDATE wisata SET kode_etik = 'Minta izin sebelum memotret pengrajin dan rumahnya.' WHERE slug = 'anyaman' AND kode_etik IS NULL;
UPDATE wisata SET kode_etik = 'Petik hanya buah yang ditunjuk petugas, jangan mematahkan ranting.' WHERE slug = 'jeruk' AND kode_etik IS NULL;

-- Kode etik umum (berlaku untuk semua destinasi, bisa diubah di CMS > Pengaturan)
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('kode_etik_umum', 'Bawa botol minum dan wadah makan sendiri untuk mengurangi sampah plastik.\nBawa pulang kembali semua sampahmu.\nJangan memetik, merusak, atau membawa pulang tanaman dan batu.\nHormati warga, adat, dan kegiatan setempat.');

-- Akun petugas contoh. Kata sandi: desa123 (WAJIB diganti sebelum submit)
INSERT IGNORE INTO admin (nama, username, password_hash, peran, akses) VALUES
('Jurnalis Desa', 'sayajurnalis', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'berita,galeri'),
('Pengelola Data Desa', 'datapublik', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'statistik,profil,aparat'),
('Pengelola Wisata', 'pengelolawisata', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'reservasi,pesan,wisata,budaya,umkm');

-- ######### FASE 7B: email, ulasan #########
ALTER TABLE reservasi ADD COLUMN IF NOT EXISTS email VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE wisata ADD COLUMN IF NOT EXISTS gmaps_ulasan VARCHAR(255) NULL;

CREATE TABLE IF NOT EXISTS ulasan (
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

-- Petugas yang boleh membuka Reservasi juga boleh membuka Ulasan
UPDATE admin SET akses = CONCAT(akses, ',ulasan') WHERE akses LIKE '%reservasi%' AND akses NOT LIKE '%ulasan%';

-- Contoh ulasan untuk reservasi contoh yang sudah selesai (dilewati kalau reservasinya tidak ada)
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Jeruknya manis dan petugasnya ramah. Anak-anak senang sekali memetik sendiri.' FROM reservasi WHERE kode = 'MM-0430';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Pemandangan pagi luar biasa, jalur pematang terawat dan pemandunya sabar.' FROM reservasi WHERE kode = 'MM-0301';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 4, 'Seru untuk rombongan sekolah. Parkir bus agak sempit, sebaiknya datang pagi.' FROM reservasi WHERE kode = 'MM-0322';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Airnya jernih dan jalan menuju curug sudah dibeton. Wajib bawa sandal gunung.' FROM reservasi WHERE kode = 'MM-0350';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 5, 'Belajar menganyam langsung dari pengrajin, pengalaman yang berharga untuk mahasiswa.' FROM reservasi WHERE kode = 'MM-0377';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, nama, 4, 'Datang pagi sesuai saran cuaca di website, ternyata cerah seharian.' FROM reservasi WHERE kode = 'MM-0402';

-- ######### FASE 8: layanan warga, transparansi, desa hijau #########
ALTER TABLE reservasi ADD COLUMN IF NOT EXISTS kendaraan VARCHAR(30) NOT NULL DEFAULT '';
ALTER TABLE reservasi ADD COLUMN IF NOT EXISTS jarak_km INT NOT NULL DEFAULT 0;
ALTER TABLE reservasi ADD COLUMN IF NOT EXISTS emisi_kg DECIMAL(8,2) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS aspirasi (
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

CREATE TABLE IF NOT EXISTS pembangunan (
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

CREATE TABLE IF NOT EXISTS apbdes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tahun INT NOT NULL,
  jenis VARCHAR(20) NOT NULL,
  uraian VARCHAR(150) NOT NULL,
  anggaran BIGINT NOT NULL DEFAULT 0,
  realisasi BIGINT NOT NULL DEFAULT 0,
  urutan INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS bantuan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  penyelenggara VARCHAR(150) NOT NULL,
  sasaran VARCHAR(255) NOT NULL,
  syarat TEXT NOT NULL,
  jadwal VARCHAR(150) NOT NULL,
  cara_daftar TEXT NOT NULL,
  aktif TINYINT NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS darurat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  nomor VARCHAR(30) NOT NULL,
  keterangan VARCHAR(150) NOT NULL DEFAULT '',
  urutan INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS fasilitas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  kategori VARCHAR(60) NOT NULL,
  alamat VARCHAR(150) NOT NULL DEFAULT '',
  keterangan VARCHAR(255) NOT NULL DEFAULT '',
  lat DECIMAL(9,6) NULL,
  lng DECIMAL(9,6) NULL
);

CREATE TABLE IF NOT EXISTS sampah (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bulan DATE NOT NULL,
  jenis VARCHAR(60) NOT NULL,
  berat_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  nilai_rupiah INT NOT NULL DEFAULT 0
);

INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('info_bank_sampah', 'Bank Sampah Mulya Lestari buka setiap Sabtu pukul 08.00 sampai 11.00 di Balai RW 02. Tabungan sampah bisa diambil tunai atau dipakai membayar iuran warga.'),
('harga_sampah', 'Botol plastik PET|Rp3.000 per kg\nKardus dan kertas|Rp1.500 per kg\nKaleng aluminium|Rp10.000 per kg\nMinyak jelantah|Rp4.000 per liter');

-- Akun pengelola data ikut mengelola data transparansi
UPDATE admin SET akses = CONCAT(akses, ',apbdes,pembangunan,bantuan,sampah,fasilitas') WHERE akses LIKE '%statistik%' AND akses NOT LIKE '%apbdes%';

-- ---------- Contoh isi (ganti dengan data asli desa lewat CMS) ----------
INSERT IGNORE INTO apbdes (id, tahun, jenis, uraian, anggaran, realisasi, urutan) VALUES
(1, 2026, 'Pendapatan', 'Dana Desa', 1050000000, 945000000, 1),
(2, 2026, 'Pendapatan', 'Alokasi Dana Desa', 420000000, 350000000, 2),
(3, 2026, 'Pendapatan', 'Pendapatan asli desa (wisata dan BUMDes)', 145000000, 118000000, 3),
(4, 2026, 'Pendapatan', 'Bagi hasil pajak dan retribusi', 62000000, 48000000, 4),
(5, 2026, 'Belanja', 'Penyelenggaraan pemerintahan desa', 520000000, 430000000, 1),
(6, 2026, 'Belanja', 'Pembangunan desa', 690000000, 505000000, 2),
(7, 2026, 'Belanja', 'Pembinaan kemasyarakatan', 140000000, 96000000, 3),
(8, 2026, 'Belanja', 'Pemberdayaan masyarakat', 260000000, 188000000, 4),
(9, 2026, 'Belanja', 'Penanggulangan bencana dan keadaan darurat', 50000000, 12000000, 5);

INSERT IGNORE INTO pembangunan (id, nama, lokasi, tahun, anggaran, sumber_dana, progres, status, deskripsi) VALUES
(1, 'Rabat beton jalan menuju Curug Cikaso', 'Dusun Cikaso, RW 04', 2026, 185000000, 'Dana Desa', 100, 'Selesai', 'Jalan tanah sepanjang 620 meter dibeton selebar 3 meter, sehingga pengunjung dan petani bisa lewat saat musim hujan.'),
(2, 'Rehabilitasi saluran irigasi Cikaso', 'Blok sawah terasering, RW 02 dan RW 03', 2026, 220000000, 'Dana Desa', 65, 'Berjalan', 'Perbaikan saluran air peninggalan pendiri desa yang mengairi sawah bertingkat, dikerjakan padat karya oleh warga.'),
(3, 'Gazebo dan toilet wisata terasering', 'Kawasan Terasering Sawah Sigarung', 2026, 95000000, 'Pendapatan asli desa', 40, 'Berjalan', 'Tempat istirahat dan toilet bersih untuk pengunjung, dibiayai dari hasil tiket wisata.'),
(4, 'Rumah pengolahan sampah TPS3R', 'Dekat Balai RW 02', 2026, 150000000, 'Bantuan provinsi', 0, 'Perencanaan', 'Tempat memilah sampah organik menjadi kompos dan sampah plastik untuk bank sampah.');

INSERT IGNORE INTO bantuan (id, nama, penyelenggara, sasaran, syarat, jadwal, cara_daftar, aktif) VALUES
(1, 'Bantuan Langsung Tunai Dana Desa', 'Pemerintah Desa Marga Mulya', 'Keluarga kurang mampu yang belum menerima bantuan sosial lain', 'Warga Desa Marga Mulya dibuktikan dengan KTP dan KK\nDitetapkan lewat musyawarah desa', 'Disalurkan setiap tiga bulan', 'Hubungi ketua RT atau datang ke kantor desa membawa KTP dan KK. Usulan dibahas di musyawarah desa.', 1),
(2, 'Bibit jeruk dan pupuk organik', 'Dinas Pertanian Kabupaten', 'Anggota kelompok tani', 'Terdaftar sebagai anggota kelompok tani\nMemiliki atau menggarap lahan kebun', 'Pendaftaran Januari sampai Februari', 'Daftar lewat ketua kelompok tani masing-masing.', 1),
(3, 'Pelatihan dan modal usaha anyaman', 'BUMDes Marga Mulya', 'Pengrajin dan pemuda yang ingin memulai usaha', 'Berusia 17 tahun ke atas\nBersedia mengikuti pelatihan 3 hari', 'Dibuka dua kali setahun', 'Isi formulir di kantor BUMDes atau kirim pesan lewat halaman Kontak dengan topik Layanan administrasi.', 1);

INSERT IGNORE INTO darurat (id, nama, nomor, keterangan, urutan) VALUES
(1, 'Panggilan darurat', '112', 'Nomor darurat nasional, bebas pulsa', 1),
(2, 'Ambulans dan gawat darurat', '119', 'Layanan kesehatan darurat', 2),
(3, 'Polisi', '110', '', 3),
(4, 'Pemadam kebakaran', '113', '', 4),
(5, 'Basarnas (pencarian dan pertolongan)', '115', 'Orang hilang atau kecelakaan di alam', 5);

INSERT IGNORE INTO fasilitas (id, nama, kategori, alamat, keterangan, lat, lng) VALUES
(1, 'Kantor Desa Marga Mulya', 'Pemerintahan', 'Jl. Raya Marga Mulya No. 1', 'Layanan administrasi Senin sampai Jumat', -7.205000, 107.570000),
(2, 'Puskesmas Pembantu Marga Mulya', 'Kesehatan', 'RW 01', 'Pemeriksaan umum dan imunisasi', -7.203600, 107.572100),
(3, 'Posyandu Melati', 'Kesehatan', 'RW 03', 'Penimbangan balita setiap tanggal 10', -7.208300, 107.566900),
(4, 'SD Negeri Marga Mulya 1', 'Pendidikan', 'RW 01', '', -7.204100, 107.568200),
(5, 'SMP Negeri Sukamaju 2', 'Pendidikan', 'RW 05', '', -7.200800, 107.574600),
(6, 'Balai Warga dan Lapangan', 'Olahraga dan ruang publik', 'RW 02', 'Lapangan voli dan tempat kegiatan warga', -7.206800, 107.571800),
(7, 'Bank Sampah Mulya Lestari', 'Lingkungan', 'Balai RW 02', 'Buka setiap Sabtu pagi', -7.207100, 107.572400),
(8, 'Pasar Desa', 'Ekonomi', 'Pertigaan jalan raya desa', 'Ramai setiap Senin dan Kamis pagi', -7.202500, 107.569500);

INSERT IGNORE INTO sampah (id, bulan, jenis, berat_kg, nilai_rupiah) VALUES
(1, '2026-05-01', 'Plastik', 410, 1230000),
(2, '2026-05-01', 'Kertas', 260, 390000),
(3, '2026-05-01', 'Logam', 38, 380000),
(4, '2026-05-01', 'Organik (kompos)', 980, 0),
(5, '2026-06-01', 'Plastik', 445, 1335000),
(6, '2026-06-01', 'Kertas', 248, 372000),
(7, '2026-06-01', 'Logam', 41, 410000),
(8, '2026-06-01', 'Organik (kompos)', 1040, 0),
(9, '2026-07-01', 'Plastik', 520, 1560000),
(10, '2026-07-01', 'Kertas', 301, 451500),
(11, '2026-07-01', 'Logam', 45, 450000),
(12, '2026-07-01', 'Organik (kompos)', 1120, 0),
(13, '2026-08-01', 'Plastik', 498, 1494000),
(14, '2026-08-01', 'Kertas', 287, 430500),
(15, '2026-08-01', 'Logam', 52, 520000),
(16, '2026-08-01', 'Organik (kompos)', 1185, 0),
(17, '2026-09-01', 'Plastik', 560, 1680000),
(18, '2026-09-01', 'Kertas', 310, 465000),
(19, '2026-09-01', 'Logam', 49, 490000),
(20, '2026-09-01', 'Organik (kompos)', 1230, 0),
(21, '2026-10-01', 'Plastik', 612, 1836000),
(22, '2026-10-01', 'Kertas', 334, 501000),
(23, '2026-10-01', 'Logam', 57, 570000),
(24, '2026-10-01', 'Organik (kompos)', 1310, 0);

INSERT IGNORE INTO aspirasi (kode, nama, wa, email, kategori, lokasi, isi, status, tanggapan, publik, diperbarui_pada) VALUES
('ADU-7KQ2PA', 'Warga RW 04', '', '', 'Infrastruktur', 'Jalan menuju Curug Cikaso', 'Jalan tanah ke curug licin dan berlumpur setiap hujan, motor petani sering terperosok.', 'Selesai', 'Sudah dibangun rabat beton 620 meter dari Dana Desa 2026. Terima kasih atas laporannya.', 1, CURRENT_TIMESTAMP),
('ADU-M3XT8B', 'Warga RW 02', '', '', 'Kebersihan dan lingkungan', 'Saluran dekat Balai RW 02', 'Banyak sampah plastik menyumbat saluran air setelah hari pasar.', 'Selesai', 'Kerja bakti bersama warga sudah dilakukan dan jadwal angkut sampah pasar ditambah menjadi dua kali seminggu.', 1, CURRENT_TIMESTAMP),
('ADU-R9HW4C', 'Warga RW 03', '', '', 'Usulan program', 'Balai warga', 'Usul ada kelas menganyam untuk remaja di hari Minggu.', 'Diproses', 'Sedang dibahas bersama BUMDes dan kelompok pengrajin.', 0, CURRENT_TIMESTAMP);

-- Contoh data perjalanan pengunjung untuk perkiraan jejak karbon
UPDATE reservasi SET kendaraan = 'Mobil pribadi', jarak_km = 60, emisi_kg = 20.40 WHERE kode = 'MM-0427' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Angkutan umum', jarak_km = 35, emisi_kg = 42.00 WHERE kode = 'MM-0428' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Bus atau elf rombongan', jarak_km = 40, emisi_kg = 78.00 WHERE kode = 'MM-0429' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Sepeda motor', jarak_km = 20, emisi_kg = 8.80 WHERE kode = 'MM-0430' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Kereta lalu ojek', jarak_km = 120, emisi_kg = 14.88 WHERE kode = 'MM-0431' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Bus atau elf rombongan', jarak_km = 80, emisi_kg = 237.12 WHERE kode = 'MM-0301' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Bus atau elf rombongan', jarak_km = 25, emisi_kg = 87.75 WHERE kode = 'MM-0322' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Sepeda atau jalan kaki', jarak_km = 30, emisi_kg = 0 WHERE kode = 'MM-0350' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Angkutan umum', jarak_km = 15, emisi_kg = 90.00 WHERE kode = 'MM-0377' AND kendaraan = '';
UPDATE reservasi SET kendaraan = 'Mobil pribadi', jarak_km = 50, emisi_kg = 187.00 WHERE kode = 'MM-0402' AND kendaraan = '';

-- ######### FASE 9: privasi, layanan surat, wisata ramah semua #########
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
