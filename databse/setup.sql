CREATE TABLE tes_koneksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesan VARCHAR(255) NOT NULL,
  dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tes_koneksi (pesan)
VALUES ('Halo dari database Desa Marga Mulya!');