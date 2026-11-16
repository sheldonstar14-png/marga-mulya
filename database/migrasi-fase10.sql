-- =========================================================
-- MIGRASI FASE 10: Laporan Dampak, Tiket Hijau, Asisten Desa.
-- AMAN dijalankan ulang. Sudah termasuk di migrasi-semua.sql.
-- =========================================================

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
