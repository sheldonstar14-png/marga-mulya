-- =========================================================
-- MIGRASI FASE 7A (JALANKAN SEKALI SAJA)
-- Menambah kolom baru tanpa menghapus data yang sudah ada:
--   admin.akses     = daftar menu yang boleh dibuka petugas
--   wisata.kode_etik = kode etik khusus tiap destinasi
-- Lalu menambah aturan kode etik umum dan 3 akun petugas contoh.
-- Kalau muncul error "Duplicate column name", berarti migrasi
-- sudah pernah dijalankan: tidak perlu diulang.
-- =========================================================

ALTER TABLE admin ADD COLUMN akses TEXT NULL;
ALTER TABLE wisata ADD COLUMN kode_etik TEXT NULL;

-- Akun lama berperan Operator menjadi Petugas dengan akses yang sama seperti sebelumnya
UPDATE admin SET peran = 'Petugas', akses = 'reservasi,pesan,wisata,budaya,umkm,agenda,berita,galeri,statistik,profil,aparat'
WHERE peran = 'Operator';

-- Kode etik khusus tiap destinasi contoh
UPDATE wisata SET kode_etik = 'Berjalan hanya di pematang dan jalur yang disediakan, jangan menginjak tanaman padi.\nJangan menerbangkan drone di atas sawah tanpa izin pengelola.' WHERE slug = 'terasering';
UPDATE wisata SET kode_etik = 'Dilarang memakai sabun, sampo, atau deterjen di kolam curug.\nIkuti petunjuk petugas saat jalur ditutup karena hujan.' WHERE slug = 'curug';
UPDATE wisata SET kode_etik = 'Minta izin sebelum memotret pengrajin dan rumahnya.' WHERE slug = 'anyaman';
UPDATE wisata SET kode_etik = 'Petik hanya buah yang ditunjuk petugas, jangan mematahkan ranting.' WHERE slug = 'jeruk';

-- Kode etik umum (berlaku untuk semua destinasi, bisa diubah di CMS > Pengaturan)
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('kode_etik_umum', 'Bawa botol minum dan wadah makan sendiri untuk mengurangi sampah plastik.\nBawa pulang kembali semua sampahmu.\nJangan memetik, merusak, atau membawa pulang tanaman dan batu.\nHormati warga, adat, dan kegiatan setempat.');

-- Akun petugas contoh. Kata sandi: desa123 (WAJIB diganti sebelum submit)
INSERT IGNORE INTO admin (nama, username, password_hash, peran, akses) VALUES
('Jurnalis Desa', 'sayajurnalis', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'berita,galeri'),
('Pengelola Data Desa', 'datapublik', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'statistik,profil,aparat'),
('Pengelola Wisata', 'pengelolawisata', '$2y$10$el.XhXia.Tq4QrpQfvK9A.gwfzMhG2vYqInBDsiieBi7NT2kBttUC', 'Petugas', 'reservasi,pesan,wisata,budaya,umkm');
