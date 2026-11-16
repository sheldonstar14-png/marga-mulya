-- =========================================================
-- MIGRASI FASE 11: laporan bulanan untuk kepala desa.
-- AMAN dijalankan ulang. Sudah termasuk di migrasi-semua.sql.
-- =========================================================
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES
('email_laporan', ''),
('laporan_terakhir', '');

-- Akun pengelola data ikut bisa membuka laporan bulanan
UPDATE admin SET akses = CONCAT(akses, ',laporan') WHERE akses LIKE '%statistik%' AND akses NOT LIKE '%laporan%';
