-- =========================================================
-- MIGRASI FASE 12: Pantau Bansos, surat online + antrean, peta desa berlapis.
-- AMAN dijalankan ulang. Sudah termasuk di migrasi-semua.sql.
-- =========================================================

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
