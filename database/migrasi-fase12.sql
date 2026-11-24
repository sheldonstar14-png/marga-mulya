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
