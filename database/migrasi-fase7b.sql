-- =========================================================
-- MIGRASI FASE 7B (JALANKAN SEKALI SAJA, setelah migrasi-fase7a.sql)
-- Menambah: email di reservasi, link ulasan Google Maps di wisata,
-- tabel ulasan, menu Ulasan untuk petugas yang mengurus reservasi,
-- dan contoh ulasan. Data lama tidak dihapus.
-- Kalau muncul "Duplicate column name" atau "already exists",
-- migrasi sudah pernah dijalankan.
-- =========================================================

ALTER TABLE reservasi ADD COLUMN email VARCHAR(150) NOT NULL DEFAULT '';
ALTER TABLE wisata ADD COLUMN gmaps_ulasan VARCHAR(255) NULL;

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

-- Petugas yang boleh membuka Reservasi juga boleh membuka Ulasan
UPDATE admin SET akses = CONCAT(akses, ',ulasan') WHERE akses LIKE '%reservasi%' AND akses NOT LIKE '%ulasan%';

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
