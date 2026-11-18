-- =========================================================
-- MIGRASI SEMUA (Fase 7A sampai 12 sekaligus)
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

-- ######### FASE 10: laporan dampak, tiket hijau, asisten desa #########
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

-- ######### FASE 11: laporan bulanan #########
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('email_laporan', ''),
('laporan_terakhir', '');

-- Akun pengelola data ikut bisa membuka laporan bulanan
UPDATE admin SET akses = CONCAT(akses, ',laporan') WHERE akses LIKE '%statistik%' AND akses NOT LIKE '%laporan%';

-- ######### FASE 12: pantau bansos, surat online, antrean, peta berlapis #########
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

-- Petugas layanan warga ikut mengurus surat; pengelola program bantuan ikut mengurus penerima; pengelola fasilitas ikut zona rawan
UPDATE admin SET akses = CONCAT(akses, ',surat') WHERE akses LIKE '%aspirasi%' AND akses NOT LIKE '%surat%';
UPDATE admin SET akses = CONCAT(akses, ',bansos') WHERE akses LIKE '%bantuan%' AND akses NOT LIKE '%bansos%';
UPDATE admin SET akses = CONCAT(akses, ',zona') WHERE akses LIKE '%fasilitas%' AND akses NOT LIKE '%zona%';

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
UPDATE admin SET akses = CONCAT(akses, ',ulasanumkm') WHERE akses LIKE '%umkm%' AND akses NOT LIKE '%ulasanumkm%';

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
UPDATE admin SET akses = CONCAT(akses, ',pesananumkm') WHERE akses LIKE '%umkm%' AND akses NOT LIKE '%pesananumkm%';

-- Kalimat catatan data diperbarui (12f). Hanya mengganti kalimat lama, tidak menimpa kalimat yang sudah diubah sendiri lewat CMS.
UPDATE pengaturan SET nilai = 'Angka pada halaman ini adalah data simulasi yang mengacu pada format Data Desa Marga Mulya dari panitia IIT Challenge 2026. Perangkat desa dapat menggantinya dengan data resmi kapan saja melalui CMS.' WHERE kunci = 'catatan_data' AND nilai LIKE 'Data statistik pada halaman ini disusun mengikuti struktur%';

-- Mode privat: 1 = website hanya untuk tim yang login CMS (masa pengembangan). Ubah ke 0 di CMS > Pengaturan sebelum submit.
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES ('mode_privat', '1');

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

-- Contoh isi 12m: aspirasi contoh tampil di Papan Aspirasi
-- hanya aspirasi contoh; aspirasi asli tetap menunggu izin pengirimnya
UPDATE aspirasi SET izin_publik = 1 WHERE kode IN ('ADU-7KQ2PA','ADU-M3XT8B','ADU-R9HW4C','ADU-P4LN7D','ADU-Q8VB2E','ADU-T6HC3G','ADU-W5ZR8H','ADU-X9DF4J');
UPDATE aspirasi SET publik = 1 WHERE kode IN ('ADU-7KQ2PA','ADU-M3XT8B','ADU-R9HW4C','ADU-P4LN7D','ADU-Q8VB2E','ADU-T6HC3G','ADU-W5ZR8H','ADU-X9DF4J') AND status <> 'Diterima';
UPDATE aspirasi SET dukungan = 7 WHERE kode = 'ADU-P4LN7D' AND dukungan = 0;
UPDATE aspirasi SET dukungan = 12 WHERE kode = 'ADU-Q8VB2E' AND dukungan = 0;
UPDATE aspirasi SET dukungan = 4 WHERE kode = 'ADU-T6HC3G' AND dukungan = 0;

-- Contoh isi 12p: sumber dan kredit (ganti atau tambah lewat CMS > Kredit foto)
INSERT IGNORE INTO kredit_foto (id, judul, pembuat, sumber_url, lisensi, lisensi_url, keterangan, urutan) VALUES
(1, 'Logo Desa Marga Mulya', 'Tim EcoQuest Marga Mulya', '', 'Karya sendiri', '', 'Logo website, bukan logo resmi pemerintah desa.', 1),
(2, 'Ilustrasi perahu, ikan, pantai, mangrove, dan kelapa', 'Tim EcoQuest Marga Mulya', '', 'Karya sendiri', '', 'Gambar vektor yang dipakai bila foto asli belum tersedia.', 2),
(3, 'Data statistik desa', 'BPS Kabupaten Tangerang', 'https://tangerangkab.bps.go.id/id/publication', 'Publikasi resmi BPS', '', 'Kecamatan Mauk Dalam Angka 2025.', 3),
(4, 'Peta dasar', 'Kontributor OpenStreetMap', 'https://www.openstreetmap.org/copyright', 'ODbL', 'https://opendatacommons.org/licenses/odbl/', 'Ditampilkan lewat Leaflet.', 4),
(5, 'Prakiraan cuaca', 'Open-Meteo', 'https://open-meteo.com/', 'CC BY 4.0', 'https://creativecommons.org/licenses/by/4.0/', '', 5);
