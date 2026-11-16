-- =========================================================
-- BERSIHKAN DATA UJI COBA (jalankan sehari sebelum submit)
-- Menghapus SEMUA reservasi dan pesan, lalu mengisi ulang contoh
-- dengan tanggal yang segar.
-- Ulasan contoh juga diisi ulang. TIDAK menyentuh konten (wisata, UMKM, berita, galeri, foto, statistik,
-- pengaturan) dan TIDAK mengubah akun atau kata sandi admin.
-- =========================================================

DELETE FROM ulasan;
DELETE FROM reservasi;
DELETE FROM pesan;

-- Contoh reservasi (hanya untuk destinasi yang masih ada)
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0426', id, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, 'Rizky Hidayat', '081200000006', '', 'Dibatalkan' FROM wisata WHERE slug = 'curug';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0427', id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 4, 'Dinda Pramesti', '081200000001', '', 'Dikonfirmasi' FROM wisata WHERE slug = 'terasering';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0428', id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 6, 'Rombongan SMA 2', '081200000002', 'Butuh pemandu', 'Menunggu' FROM wisata WHERE slug = 'curug';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0429', id, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 25, 'SD Harapan Bangsa', '081200000003', 'Kelas 5, didampingi 3 guru', 'Menunggu' FROM wisata WHERE slug = 'anyaman';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0430', id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 3, 'Fajar Nugroho', '081200000004', '', 'Selesai' FROM wisata WHERE slug = 'jeruk';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0431', id, DATE_ADD(CURDATE(), INTERVAL 6 DAY), 2, 'Maya Anggraini', '081200000005', '', 'Menunggu' FROM wisata WHERE slug = 'terasering';

-- Riwayat kunjungan bulan-bulan sebelumnya, supaya grafik dashboard tidak kosong saat demo
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0301', id, DATE_SUB(CURDATE(), INTERVAL 150 DAY), 38, 'Rombongan PKK Sukamaju', '081200000011', '', 'Selesai' FROM wisata WHERE slug = 'terasering';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0322', id, DATE_SUB(CURDATE(), INTERVAL 120 DAY), 45, 'SMP Negeri 3', '081200000012', '', 'Selesai' FROM wisata WHERE slug = 'jeruk';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0350', id, DATE_SUB(CURDATE(), INTERVAL 90 DAY), 50, 'Komunitas Pesepeda Harapan', '081200000013', '', 'Selesai' FROM wisata WHERE slug = 'curug';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0377', id, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 30, 'Mahasiswa KKN', '081200000014', '', 'Selesai' FROM wisata WHERE slug = 'anyaman';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status)
SELECT 'MM-0402', id, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 42, 'Keluarga Besar Wiryo', '081200000015', '', 'Selesai' FROM wisata WHERE slug = 'terasering';

INSERT INTO pesan (nama, kontak, topik, isi, dibaca) VALUES
('Andi', 'andi@contoh.id', 'Wisata', 'Apakah curug bisa dikunjungi rombongan 40 orang?', 0),
('Bu Sri', '081200000007', 'Layanan administrasi', 'Syarat membuat surat keterangan usaha apa saja?', 1);

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
