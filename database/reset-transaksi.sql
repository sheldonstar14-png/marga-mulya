-- =========================================================
-- BERSIHKAN DATA UJI COBA (jalankan sehari sebelum submit)
-- Menghapus SEMUA reservasi, aspirasi, pesan, surat, antrean, pesanan UMKM, dan permintaan data
-- (termasuk data asli yang sempat dipakai untuk uji coba), lalu mengisi ulang contoh
-- dengan tanggal yang segar.
-- Ulasan dan aspirasi contoh juga diisi ulang. TIDAK menyentuh konten (wisata, UMKM, berita, galeri, foto, statistik,
-- pengaturan) dan TIDAK mengubah akun atau kata sandi admin.
-- =========================================================

DELETE FROM pesanan_umkm_item;
DELETE FROM pesanan_umkm;
DELETE FROM antrean;
DELETE FROM surat;
DELETE FROM permintaan_data;
DELETE FROM aspirasi;
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

-- Contoh kupon untuk reservasi contoh berskor hijau
UPDATE reservasi SET kupon = 'HIJAU-0322', kupon_dipakai_pada = CURRENT_TIMESTAMP, kupon_umkm = 'Keripik Singkong Bu Darmi' WHERE kode = 'MM-0322' AND kupon IS NULL;
UPDATE reservasi SET kupon = 'HIJAU-0350', kupon_dipakai_pada = CURRENT_TIMESTAMP, kupon_umkm = 'Anyaman Rukun' WHERE kode = 'MM-0350' AND kupon IS NULL;
UPDATE reservasi SET kupon = 'HIJAU-0377' WHERE kode = 'MM-0377' AND kupon IS NULL;
UPDATE reservasi SET kupon = 'HIJAU-0429' WHERE kode = 'MM-0429' AND kupon IS NULL;

-- ---------- Tambahan contoh (12i): semua nama dan nomor di bawah KARANGAN ----------
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, email, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0201', id, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 4, 'Keluarga Santoso', '081200000031', '', '', 'Selesai', 'Mobil pribadi', 35 FROM wisata WHERE slug = 'terasering';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, email, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0202', id, DATE_SUB(CURDATE(), INTERVAL 9 DAY), 22, 'Pramuka SMPN 1 Sukamaju', '081200000032', '', 'Didampingi 2 pembina', 'Selesai', 'Bus atau elf rombongan', 12 FROM wisata WHERE slug = 'anyaman';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, email, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0203', id, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 2, 'Anisa Putri', '081200000033', '', '', 'Selesai', 'Sepeda motor', 20 FROM wisata WHERE slug = 'curug';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, email, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0204', id, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 5, 'Arisan RT 02 Sukamaju', '081200000034', '', '', 'Selesai', 'Angkutan umum', 15 FROM wisata WHERE slug = 'jeruk';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, email, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0432', id, DATE_ADD(CURDATE(), INTERVAL 9 DAY), 12, 'Komunitas Fotografi Harapan', '081200000035', '', 'Ingin memotret matahari terbit', 'Dikonfirmasi', 'Mobil pribadi', 45 FROM wisata WHERE slug = 'terasering';
INSERT INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, email, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0433', id, DATE_ADD(CURDATE(), INTERVAL 11 DAY), 3, 'Dewi Lestari', '081200000036', '', 'Membawa lansia, mohon info jalur', 'Menunggu', 'Angkutan umum', 25 FROM wisata WHERE slug = 'jeruk';

INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, 'Keluarga Santoso', 5, 'Kami berangkat jam enam pagi sesuai saran website, kabutnya turun di antara petak sawah. Pemandu menjelaskan cara pengairan tradisional dengan sabar.' FROM reservasi WHERE kode = 'MM-0201';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, 'Kak Bima, pembina pramuka', 5, 'Hujan turun sejak siang tapi kelas anyaman tetap jalan di dalam ruangan. Setiap anak pulang membawa tatakan buatan sendiri.' FROM reservasi WHERE kode = 'MM-0202';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, 'Anisa', 4, 'Curugnya indah dan jalannya sudah bagus. Jalur setapak terakhir agak licin, pakai sandal gunung.' FROM reservasi WHERE kode = 'MM-0203';
INSERT INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar)
SELECT id, wisata_id, 'Bu Wati', 5, 'Petik jeruk sambil ngobrol dengan petaninya. Kami naik angkot dan dapat kupon Tiket Hijau untuk beli sirup jeruk.' FROM reservasi WHERE kode = 'MM-0204';

INSERT INTO aspirasi (kode, nama, wa, email, kategori, lokasi, isi, status, tanggapan, publik, diperbarui_pada) VALUES
('ADU-P4LN7D', '', '', '', 'Infrastruktur', 'Gang RT 05 dekat musala', 'Lampu jalan mati sejak dua minggu, gang jadi gelap saat warga pulang dari sawah.', 'Diproses', 'Petugas sudah mengecek, lampu baru dipasang pekan ini bersama Linmas.', 1, CURRENT_TIMESTAMP),
('ADU-Q8VB2E', 'Warga RT 07', '', '', 'Kesehatan', 'Posyandu Melati', 'Mohon jadwal posyandu ditempel juga di grup WhatsApp RT, banyak ibu yang lupa tanggalnya.', 'Selesai', 'Jadwal posyandu sekarang dibagikan setiap awal bulan ke semua grup RT dan bisa disimpan ke kalender HP dari website.', 1, CURRENT_TIMESTAMP),
('ADU-S2KM9F', '', '', '', 'Bansos (salah sasaran atau usulan penerima)', 'RT 10', 'Ada lansia hidup sendiri di RT 10 yang belum menerima bantuan, padahal kondisinya sangat membutuhkan.', 'Diproses', 'Sudah dicatat untuk dibahas di musyawarah desa penetapan penerima berikutnya.', 0, CURRENT_TIMESTAMP),
('ADU-T6HC3G', 'Warga RW 01', '', '', 'Pelayanan administrasi', 'Kantor desa', 'Antrean di kantor desa sering panjang di hari Senin pagi.', 'Selesai', 'Kini tersedia nomor antrean lewat QR di meja layanan dan pengajuan surat online, sehingga warga cukup datang saat surat siap.', 1, CURRENT_TIMESTAMP),
('ADU-W5ZR8H', '', '', '', 'Kebersihan dan lingkungan', 'Parkiran Curug Cikaso', 'Tempat sampah di parkiran curug penuh setiap akhir pekan.', 'Menunggu', NULL, 0, CURRENT_TIMESTAMP),
('ADU-X9DF4J', 'Pemuda Karang Taruna', '', '', 'Usulan program', 'Lapangan desa', 'Usul lomba foto terasering untuk promosi wisata desa di media sosial.', 'Menunggu', NULL, 0, CURRENT_TIMESTAMP);

INSERT INTO pesan (nama, kontak, topik, isi, dibaca)
SELECT 'Rombongan TK Pelangi', '081200000041', 'Wisata', 'Apakah kelas anyaman bisa untuk anak usia 5 tahun? Kami berencana datang 20 orang.', 0 FROM DUAL WHERE EXISTS (SELECT 1 FROM wisata);

INSERT INTO pesanan_umkm (kode, usaha, wa_penjual, nama, wa, pengambilan, alamat, catatan, ringkasan, total, status) VALUES
('UMK-8RT3KD', 'Keripik Singkong Bu Darmi', '6280000000101', 'Fajar', '081200000051', 'Ambil sendiri', '', 'Level pedas sedang', 'Keripik singkong pedas manis x3', 45000, 'Selesai'),
('UMK-9MW2QE', 'Sirup Jeruk Mulya', '6280000000103', 'Laras', '081200000052', 'Diantar', 'Perum Harapan Indah Blok C2', '', 'Sirup jeruk murni tanpa pengawet x2', 56000, 'Baru');

INSERT IGNORE INTO surat (id, kode, layanan_id, layanan_nama, nama, wa, email, keperluan, status, catatan) VALUES
(1, 'SRT-4KX9PQ', 1, 'Surat keterangan domisili', 'Rina Marlina', '081200000021', '', 'Melamar kerja di pabrik', 'Siap diambil', 'Surat sudah ditandatangani. Bawa KTP asli saat mengambil.'),
(2, 'SRT-7HN2WD', 2, 'Surat keterangan tidak mampu (SKTM)', 'Agus Setiawan', '081200000022', '', 'Keringanan biaya sekolah anak', 'Diproses', 'Menunggu kunjungan rumah petugas kesejahteraan.');
