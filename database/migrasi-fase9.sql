-- =========================================================
-- MIGRASI FASE 9: privasi, layanan surat, wisata ramah semua.
-- AMAN dijalankan ulang (memakai IF NOT EXISTS dan INSERT IGNORE).
-- Lebih mudah: jalankan migrasi-semua.sql, yang sudah memuat file ini.
-- =========================================================

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
