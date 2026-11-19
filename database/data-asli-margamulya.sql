-- =========================================================
-- DATA DESA MARGAMULYA, KECAMATAN MAUK, KABUPATEN TANGERANG (BANTEN)
-- Menggantikan data contoh lama (desa perbukitan fiktif) dengan data desa yang asli.
--
-- Data ASLI (bersumber, tanpa tanda):
--   - Kode wilayah 36.03.08.2008, Kecamatan Mauk, Kabupaten Tangerang, Banten
--   - BPS, Kecamatan Mauk Dalam Angka 2025 (data 2024): luas 6,14 km2 (13,66% kecamatan), jarak 4 km ke kantor
--     kecamatan dan 32 km ke ibu kota kabupaten, 5 RW dan 25 RT, penduduk 8.449 jiwa (4.359 laki-laki, 4.090 perempuan),
--     kepadatan 1.376,17 jiwa/km2, rasio jenis kelamin 106,58, 8,94% penduduk kecamatan
--   - Peta wilayah BPS 2025: Marga Mulya berbatasan langsung dengan laut di utara, Desa Ketapang di barat,
--     Desa Tanjung Anom dan Kecamatan Sukadiri di timur, Desa Tegal Kunir Lor di selatan
--   - Arti nama "Marga" (kelompok/keturunan) + "Mulya" (baik), dipilih lewat musyawarah (BKKBN)
--   - Kampung KB Bebulak di RW 03, Dusun Bebulak (BKKBN)
--   - SD Negeri Margamulya di Kp. Bebulak, berdiri 1983 (Dapodik Kemendikdasmen)
--   - Sawah sekitar 330 ha, Kelompok Tani Jamblang, percontohan benih padi Cakrabuana (Jejak Kata, Okt 2023)
--   - Potensi pesisir: kelapa, mangrove, hasil laut, kerajinan batok kelapa, Pantai Pasir Putih (Universitas
--     Pembangunan Jaya, program SENANDUNG 2025)
--   - Nomor darurat nasional 112, 119, 110, 113, 115
-- Data ILUSTRASI: semua yang diberi tanda "(ilustrasi)" atau kolom statistik.ilustrasi = 1.
-- Ganti dengan data resmi desa lewat CMS kapan saja.
--
-- Jalankan SETELAH migrasi-semua.sql dan SEBELUM reset-transaksi.sql. Aman dijalankan ulang.
-- =========================================================

-- ---------- Pengaturan umum ----------
UPDATE pengaturan SET nilai = 'Desa Marga Mulya' WHERE kunci = 'nama_desa';
UPDATE pengaturan SET nilai = 'Kecamatan Mauk' WHERE kunci = 'kecamatan';
UPDATE pengaturan SET nilai = 'Kabupaten Tangerang' WHERE kunci = 'kabupaten';
UPDATE pengaturan SET nilai = 'Kantor Desa Margamulya, Kecamatan Mauk, Kabupaten Tangerang, Banten 15531' WHERE kunci = 'alamat';
UPDATE pengaturan SET nilai = 'Selamat datang di Desa Marga Mulya' WHERE kunci = 'hero_judul';
UPDATE pengaturan SET nilai = 'Desa tepi laut di utara Kabupaten Tangerang. Warganya nelayan, petani, dan pengrajin. Urus keperluan warga, atau kenali pantai, mangrove, dan produk buatan warga kami.' WHERE kunci = 'hero_teks';
-- Titik pusat peta: tengah wilayah Margamulya menurut peta BPS 2025 (perkiraan). Geser ke titik kantor desa yang tepat lewat CMS > Pengaturan.
UPDATE pengaturan SET nilai = '-6.0450' WHERE kunci = 'lat';
UPDATE pengaturan SET nilai = '106.5380' WHERE kunci = 'lng';
UPDATE pengaturan SET nilai = 'Marga Mulya (Margamulya) adalah desa di Kecamatan Mauk, Kabupaten Tangerang, Banten, dengan kode wilayah 36.03.08.2008. Menurut BPS (Kecamatan Mauk Dalam Angka 2025), luasnya 6,14 km² dan dihuni 8.449 jiwa pada 2024, terdiri dari 4.359 laki-laki dan 4.090 perempuan di 5 RW dan 25 RT. Desa ini berbatasan langsung dengan laut di sebelah utara, berjarak 4 km dari kantor Kecamatan Mauk dan 32 km dari ibu kota Kabupaten Tangerang. Sebagian besar warga bekerja sebagai petani, nelayan, wiraswasta, dan pekerja lepas. Potensi desa meliputi sawah sekitar 330 hektare, kelapa, mangrove, hasil laut, serta kerajinan batok kelapa.' WHERE kunci = 'profil_ringkas';
UPDATE pengaturan SET nilai = 'Nama Margamulya lahir dari musyawarah seluruh komponen desa. "Marga" berarti kelompok atau keturunan, dan "Mulya" berarti baik. Warga berharap Margamulya menjadi desa yang baik untuk ditempati dan ditinggali.' WHERE kunci = 'sejarah';
UPDATE pengaturan SET nilai = '(ilustrasi) Terwujudnya Desa Marga Mulya yang guyub, sejahtera, dan tangguh, dengan pesisir dan pertanian yang lestari.' WHERE kunci = 'visi';
UPDATE pengaturan SET nilai = '(ilustrasi) Meningkatkan kesejahteraan nelayan dan pengolah hasil laut.\n(ilustrasi) Melestarikan mangrove dan pesisir sebagai pelindung desa dari rob dan abrasi.\n(ilustrasi) Mengembangkan UMKM olahan hasil laut, kelapa, dan kerajinan batok kelapa.\n(ilustrasi) Menjaga lahan pertanian pangan dan meningkatkan hasil panen petani.\n(ilustrasi) Memberikan pelayanan desa yang cepat, terbuka, dan mudah dijangkau warga.' WHERE kunci = 'misi';
UPDATE pengaturan SET nilai = 'Laut Jawa' WHERE kunci = 'batas_utara';
UPDATE pengaturan SET nilai = 'Desa Tanjung Anom dan Kecamatan Sukadiri' WHERE kunci = 'batas_timur';
UPDATE pengaturan SET nilai = 'Desa Tegal Kunir Lor' WHERE kunci = 'batas_selatan';
UPDATE pengaturan SET nilai = 'Desa Ketapang' WHERE kunci = 'batas_barat';
UPDATE pengaturan SET nilai = 'Bawa botol minum dan wadah makan sendiri.\nJangan menginjak atau mencabut bibit mangrove.\nBuang sampah di tempatnya, terutama plastik di pantai.\nMinta izin sebelum memotret warga atau naik ke perahu nelayan.' WHERE kunci = 'kode_etik_umum';
UPDATE pengaturan SET nilai = '(ilustrasi) Bank Sampah Margamulya buka setiap Sabtu pukul 08.00 sampai 11.00 di Balai RW 03. Sampah ditimbang, dicatat di buku tabungan, dan bisa ditukar uang atau sembako.' WHERE kunci = 'info_bank_sampah';
UPDATE pengaturan SET nilai = 'Data asli: luas wilayah, jarak, jumlah RW dan RT, serta penduduk menurut jenis kelamin dari BPS (Kecamatan Mauk Dalam Angka 2025, data 2024); luas sawah dari pemberitaan 2023. Angka bertanda ilustrasi adalah data contoh yang mengikuti struktur Data EcoQuest IIT Challenge 2026. Perangkat desa dapat menggantinya dengan data resmi kapan saja melalui CMS.' WHERE kunci = 'catatan_data';
UPDATE pengaturan SET nilai = '' WHERE kunci IN ('sambutan_kades', 'foto_kades');

-- ---------- Statistik (struktur mengikuti Data EcoQuest A sampai M) ----------
DELETE FROM statistik;
INSERT INTO statistik (kategori, label, nilai, satuan, urutan, ilustrasi) VALUES
-- A. Kondisi geografis: luas menurut penggunaan (total 614 ha = 6,14 km2 BPS; sawah 330 ha dari berita 2023)
('Lahan', 'Persawahan', 330, 'ha', 1, 0),
('Lahan', 'Pemukiman', 150, 'ha', 2, 1),
('Lahan', 'Pekarangan', 48, 'ha', 3, 1),
('Lahan', 'Prasarana umum lainnya', 34, 'ha', 4, 1),
('Lahan', 'Perkebunan kelapa', 30, 'ha', 5, 1),
('Lahan', 'Mangrove dan sempadan', 12, 'ha', 6, 1),
('Lahan', 'Perkuburan', 6, 'ha', 7, 1),
('Lahan', 'Perkantoran', 2, 'ha', 8, 1),
('Lahan', 'Taman', 2, 'ha', 9, 1),
-- B. Pemerintahan desa
('Pemerintahan', 'RW', 5, 'RW', 1, 0),
('Pemerintahan', 'RT', 25, 'RT', 2, 0),
('Pemerintahan', 'Aparat desa', 6, 'orang', 3, 0),
('Pemerintahan', 'Anggota Linmas', 25, 'orang', 4, 1),
('Pemerintahan', 'Pos kamling', 12, 'pos', 5, 1),
('Pemerintahan', 'Pos polisi', 0, 'pos', 6, 1),
-- C. Kependudukan dan angkatan kerja (BPS 2024: 4.359 laki-laki + 4.090 perempuan = 8.449 jiwa)
('Kependudukan', 'Laki-laki', 4359, 'jiwa', 1, 0),
('Kependudukan', 'Perempuan', 4090, 'jiwa', 2, 0),
('Kependudukan', 'Kepadatan penduduk', 1376.17, 'jiwa/km²', 6, 0),
('Kependudukan', 'Rasio jenis kelamin (laki-laki per 100 perempuan)', 106.58, 'rasio', 7, 0),
('Kependudukan', 'Rumah tangga (KK)', 2470, 'KK', 3, 1),
('Kependudukan', 'Usia kerja (18-56 tahun)', 5120, 'jiwa', 4, 1),
('Kependudukan', 'Pencari kerja terdaftar', 310, 'jiwa', 5, 1),
('Mata pencaharian', 'Nelayan dan buruh nelayan', 1240, 'jiwa', 1, 1),
('Mata pencaharian', 'Petani dan buruh tani', 1080, 'jiwa', 2, 1),
('Mata pencaharian', 'Karyawan swasta dan buruh pabrik', 870, 'jiwa', 3, 1),
('Mata pencaharian', 'Wiraswasta dan pedagang', 690, 'jiwa', 4, 1),
('Mata pencaharian', 'Pengolah hasil laut', 260, 'jiwa', 5, 1),
('Mata pencaharian', 'Pengrajin batok kelapa', 140, 'jiwa', 6, 1),
('Mata pencaharian', 'Peternak', 110, 'jiwa', 7, 1),
('Mata pencaharian', 'Guru', 70, 'jiwa', 8, 1),
('Mata pencaharian', 'PNS', 45, 'jiwa', 9, 1),
('Mata pencaharian', 'TNI/Polri', 15, 'jiwa', 10, 1),
('Mata pencaharian', 'Pekerja lepas dan lainnya', 600, 'jiwa', 11, 1),
-- E. Pendidikan (total 8.449)
('Pendidikan penduduk', 'Belum/tidak sekolah', 1390, 'jiwa', 1, 1),
('Pendidikan penduduk', 'SD/sederajat', 3020, 'jiwa', 2, 1),
('Pendidikan penduduk', 'SMP/sederajat', 1840, 'jiwa', 3, 1),
('Pendidikan penduduk', 'SMA/SMK', 1760, 'jiwa', 4, 1),
('Pendidikan penduduk', 'Diploma', 160, 'jiwa', 5, 1),
('Pendidikan penduduk', 'Sarjana ke atas', 279, 'jiwa', 6, 1),
('Sekolah', 'PAUD/TK', 2, 'unit', 1, 1),
('Sekolah', 'SD/MI', 3, 'unit', 2, 1),
('Sekolah', 'SMP/MTs', 1, 'unit', 3, 1),
('Sekolah', 'Kursus menjahit dan komputer', 2, 'lembaga', 4, 1),
('Sekolah', 'Kejar Paket A, B, C', 1, 'kelompok', 5, 1),
-- F. Kesehatan
('Kesehatan', 'Imunisasi dasar lengkap', 92, '%', 1, 1),
('Kesehatan', 'Balita gizi baik', 89, '%', 2, 1),
('Kesehatan', 'KK dengan air bersih', 84, '%', 3, 1),
('Kesehatan', 'KK dengan jamban sehat', 81, '%', 4, 1),
('Kesehatan', 'Peserta KB aktif', 1310, 'pasangan', 5, 1),
('Fasilitas kesehatan', 'Posyandu', 5, 'unit', 1, 1),
('Fasilitas kesehatan', 'Polindes/Poskesdes', 1, 'unit', 2, 1),
('Fasilitas kesehatan', 'Praktik bidan', 2, 'unit', 3, 1),
('Fasilitas kesehatan', 'Bidan', 2, 'orang', 4, 1),
('Fasilitas kesehatan', 'Kader posyandu', 25, 'orang', 5, 1),
-- G. Pertanian, perikanan, peternakan
('Pertanian', 'Padi', 3600, 'ton/tahun', 1, 1),
('Pertanian', 'Kelapa', 180, 'ton/tahun', 2, 1),
('Pertanian', 'Sayuran', 90, 'ton/tahun', 3, 1),
('Pertanian', 'Kelompok tani', 4, 'kelompok', 4, 1),
('Perikanan', 'Hasil tangkapan laut', 210, 'ton/tahun', 1, 1),
('Perikanan', 'Bandeng tambak', 75, 'ton/tahun', 2, 1),
('Perikanan', 'Kelompok nelayan', 3, 'kelompok', 3, 1),
('Perikanan', 'Perahu motor tempel', 140, 'unit', 4, 1),
('Perikanan', 'Terasi dan ikan asin olahan warga', 18, 'ton/tahun', 5, 1),
('Peternakan', 'Ayam', 7800, 'ekor', 1, 1),
('Peternakan', 'Itik', 1900, 'ekor', 2, 1),
('Peternakan', 'Puyuh', 600, 'ekor', 3, 1),
('Peternakan', 'Kambing', 420, 'ekor', 4, 1),
('Peternakan', 'Sapi', 60, 'ekor', 5, 1),
('Peternakan', 'Kerbau', 35, 'ekor', 6, 1),
-- H. Ekonomi
('Ekonomi', 'Industri rumah tangga makanan', 38, 'usaha', 1, 1),
('Ekonomi', 'Kerajinan batok kelapa', 12, 'usaha', 2, 1),
('Ekonomi', 'Olahan hasil laut', 15, 'usaha', 3, 1),
('Ekonomi', 'Koperasi', 1, 'unit', 4, 1),
('Ekonomi', 'BUMDes', 1, 'unit', 5, 1),
('Ekonomi', 'Pasar tanpa bangunan', 1, 'unit', 6, 1),
-- I. Infrastruktur
('Jalan desa', 'Aspal', 5.2, 'km', 1, 1),
('Jalan desa', 'Beton', 4.1, 'km', 2, 1),
('Jalan desa', 'Tanah', 2.3, 'km', 3, 1),
('Jalan desa', 'Makadam', 1.8, 'km', 4, 1);

-- ---------- Aparat desa (Wikipedia mencatat 6 aparat; kepala desa perlu dikonfirmasi ke kantor desa) ----------
DELETE FROM aparat;
INSERT INTO aparat (id, jabatan, nama, urutan) VALUES
(1, 'Kepala Desa', 'Abu Bakar', 1),
(2, 'Sekretaris Desa', 'Nama sekretaris (ilustrasi)', 2),
(3, 'Kaur Keuangan', 'Nama kaur keuangan (ilustrasi)', 3),
(4, 'Kaur Perencanaan', 'Nama kaur perencanaan (ilustrasi)', 4),
(5, 'Kasi Pemerintahan', 'Nama kasi pemerintahan (ilustrasi)', 5),
(6, 'Kasi Pelayanan', 'Nama kasi pelayanan (ilustrasi)', 6);

-- ---------- Wisata (4 baris lama diganti isinya; id tetap supaya reservasi lama tidak rusak) ----------
UPDATE wisata SET slug = 'pantai-pasir-putih', nama = 'Pantai Pasir Putih Margamulya', jenis = 'Alam', harga = 5000, kuota = 300, jam_buka = '06.00 sampai 18.00',
  ringkas = 'Pantai warga dengan warung dan bale yang direvitalisasi bersama mahasiswa pada 2025.',
  deskripsi = 'Kawasan pesisir Margamulya tempat warga dan pengunjung bersantai sambil menikmati angin laut. Pada 2025, warung dan bale di Pantai Pasir Putih direvitalisasi lewat program pemberdayaan Universitas Pembangunan Jaya, termasuk 14 warung pantai. Harga tiket, kuota, dan jam buka di halaman ini adalah ilustrasi.',
  fasilitas = 'Warung pantai, Bale, Parkir (ilustrasi)', tips = 'Datang pagi atau menjelang sore supaya tidak terlalu panas. Hindari bermain air saat ombak tinggi atau angin kencang.',
  kode_etik = 'Bawa pulang sampahmu, jangan dibuang ke laut.\nBelanja di warung warga untuk mendukung ekonomi pesisir.', lat = -6.0300, lng = 106.5330,
  foto = '/assets/img/pantai.svg', tingkat_jalur = 'Mudah', ramah_kursi_roda = 0, ramah_lansia = 1, ada_toilet = 1,
  catatan_akses = '(ilustrasi) Jalan desa sampai area parkir, lalu berjalan di pasir sekitar 100 meter.', saat_hujan = 0
WHERE id = 2;
UPDATE wisata SET slug = 'kampung-nelayan', nama = 'Wisata Kampung Nelayan (ilustrasi)', jenis = 'Edukasi', harga = 15000, kuota = 60, jam_buka = '07.00 sampai 11.00',
  ringkas = 'Melihat perahu nelayan bersandar dan belajar mengolah ikan asin serta terasi bersama warga.',
  deskripsi = 'Pagi hari, perahu nelayan Margamulya kembali dari melaut membawa ikan dan udang rebon. Pengunjung diajak melihat hasil tangkapan, belajar menjemur ikan asin, dan mencoba membuat terasi bersama ibu-ibu pengolah hasil laut. Paket kegiatan, harga, dan jam adalah ilustrasi.',
  fasilitas = 'Pemandu warga, Area jemur ikan, Warung (ilustrasi)', tips = 'Datang pagi saat perahu kembali dari melaut. Pakai alas kaki yang boleh basah.',
  kode_etik = 'Minta izin sebelum naik ke perahu atau memotret nelayan.\nJangan menginjak ikan yang sedang dijemur.', lat = -6.0320, lng = 106.5360,
  foto = '/assets/img/perahu.svg', tingkat_jalur = 'Mudah', ramah_kursi_roda = 0, ramah_lansia = 1, ada_toilet = 1,
  catatan_akses = '(ilustrasi) Jalan desa sampai dermaga kecil, lalu jalan kaki sekitar 50 meter.', saat_hujan = 0
WHERE id = 1;
UPDATE wisata SET slug = 'batok-kelapa', nama = 'Kelas Kerajinan Batok Kelapa (ilustrasi)', jenis = 'Budaya', harga = 25000, kuota = 30, jam_buka = '09.00 sampai 15.00',
  ringkas = 'Membuat mangkuk, gantungan kunci, atau briket dari batok kelapa bersama pengrajin warga.',
  deskripsi = 'Kelapa melimpah di Margamulya, dan pengrajin warga mengolah batoknya menjadi kerajinan serta briket arang, yang juga dikembangkan lewat program pemberdayaan Universitas Pembangunan Jaya pada 2025. Kelas berlangsung di dalam ruangan sehingga tetap berjalan saat hujan. Nama kelas, harga, dan kuota adalah ilustrasi.',
  fasilitas = 'Ruang kelas, Alat dan bahan, Hasil karya dibawa pulang (ilustrasi)', tips = 'Cocok untuk rombongan sekolah dan keluarga. Anak di bawah 10 tahun didampingi orang dewasa saat memakai amplas dan alat bor.',
  kode_etik = 'Ikuti arahan pengrajin saat memakai alat.\nSisa batok dikumpulkan untuk dijadikan briket.', lat = -6.0450, lng = 106.5410,
  foto = '/assets/img/kelapa.svg', tingkat_jalur = 'Mudah', ramah_kursi_roda = 1, ramah_lansia = 1, ada_toilet = 1,
  catatan_akses = '(ilustrasi) Ruang kelas di lantai dasar dan bisa dilalui kursi roda.', saat_hujan = 1
WHERE id = 3;
UPDATE wisata SET slug = 'mangrove', nama = 'Edukasi Mangrove Pesisir Margamulya', jenis = 'Alam', harga = 10000, kuota = 80, jam_buka = '07.00 sampai 16.00',
  ringkas = 'Mengenal hutan bakau dan ikut menanam bibit mangrove di pesisir desa.',
  deskripsi = 'Ekosistem mangrove di pesisir Margamulya bernilai ekonomi dan ekologis: menahan abrasi, menjadi tempat ikan bertelur, dan bahan olahan warga. Pengunjung bisa belajar mengenal jenis bakau lalu menanam bibit bersama kelompok warga. Paket kegiatan, harga, dan kuota di halaman ini adalah ilustrasi.',
  fasilitas = 'Bibit mangrove, Pemandu warga, Tempat cuci kaki (ilustrasi)', tips = 'Pakai sepatu yang boleh basah. Periksa jadwal pasang surut, menanam paling mudah saat air surut.',
  kode_etik = 'Jangan mencabut atau mematahkan akar bakau.\nTanam bibit hanya di titik yang ditunjuk pemandu.', lat = -6.0330, lng = 106.5290,
  foto = '/assets/img/mangrove.svg', tingkat_jalur = 'Sedang', ramah_kursi_roda = 0, ramah_lansia = 0, ada_toilet = 0,
  catatan_akses = '(ilustrasi) Jalan setapak berlumpur menuju titik tanam, sekitar 300 meter dari parkir.', saat_hujan = 0
WHERE id = 4;

-- ---------- UMKM (jenis produk asli dari laporan UPJ 2025; nama usaha dan harga ilustrasi) ----------
UPDATE umkm SET nama_usaha = 'Kerupuk Ikan Bu Darmi (ilustrasi)', kategori = 'Olahan hasil laut', produk = 'Kerupuk ikan siap goreng', harga = 15000, satuan = '250 g', pemilik = 'Bu Darmi, RT 03 (ilustrasi)', lat = -6.0420, lng = 106.5370,
  deskripsi = 'Kerupuk dari ikan tangkapan nelayan pesisir, dijemur di bawah matahari dan dikemas ulang supaya awet. Contoh olahan hasil laut yang menjadi salah satu potensi UMKM Margamulya.' WHERE id = 1;
UPDATE umkm SET nama_usaha = 'Kerajinan Batok Rukun (ilustrasi)', kategori = 'Kerajinan', produk = 'Mangkuk dan gelas batok kelapa', harga = 25000, satuan = 'buah', pemilik = 'Kelompok Rukun, RW 02 (ilustrasi)', lat = -6.0450, lng = 106.5410, foto = '/assets/img/kelapa.svg',
  deskripsi = 'Batok kelapa dihaluskan, dipernis, lalu dibentuk menjadi mangkuk, gelas, dan gantungan kunci. Kerajinan batok kelapa adalah salah satu produk unggulan warga Margamulya.' WHERE id = 2;
UPDATE umkm SET nama_usaha = 'Minyak Kelapa Mulya (ilustrasi)', kategori = 'Olahan kelapa', produk = 'Minyak kelapa murni (VCO)', harga = 35000, satuan = 'botol 250 ml', pemilik = 'Kelompok Wanita Tani, RW 04 (ilustrasi)', lat = -6.0480, lng = 106.5440, foto = '/assets/img/kelapa.svg',
  deskripsi = 'Minyak kelapa murni yang dibuat dari kelapa kebun warga tanpa pemanasan tinggi. Bisa untuk memasak atau perawatan kulit.' WHERE id = 3;
UPDATE umkm SET nama_usaha = 'Bandeng Presto Bu Nur (ilustrasi)', kategori = 'Olahan hasil laut', produk = 'Bandeng presto duri lunak', harga = 45000, satuan = '2 ekor (500 g)', pemilik = 'Bu Nur, RT 05 (ilustrasi)', lat = -6.0360, lng = 106.5350, foto = '/assets/img/ikan.svg',
  deskripsi = 'Bandeng dari tambak pesisir dimasak presto sampai durinya lunak, jadi aman untuk anak dan lansia. Tahan dua hari di suhu ruang dan seminggu di kulkas.' WHERE id = 4;
UPDATE umkm SET nama_usaha = 'Briket Batok Mak Ijah (ilustrasi)', kategori = 'Kerajinan', produk = 'Briket arang batok kelapa', harga = 20000, satuan = 'kg', pemilik = 'Mak Ijah, RT 07 (ilustrasi)', lat = -6.0400, lng = 106.5400, foto = '/assets/img/kegiatan.svg',
  deskripsi = 'Briket dari sisa batok kelapa, panasnya tahan lama dan asapnya sedikit. Cocok untuk bakar ikan dan sate. Pembuatan briket batok kelapa dikembangkan warga sejak program pemberdayaan 2025.' WHERE id = 5;
UPDATE umkm SET nama_usaha = 'Rengginang Kang Ujang (ilustrasi)', kategori = 'Makanan', produk = 'Rengginang ketan rebon', harga = 20000, satuan = '300 g', pemilik = 'Kang Ujang, RT 11 (ilustrasi)', lat = -6.0500, lng = 106.5360,
  deskripsi = 'Rengginang ketan dengan udang rebon dari pesisir, dijemur alami. Produk pangan khas rumahan yang biasa dijadikan oleh-oleh.' WHERE id = 6;
UPDATE ulasan_umkm SET usaha = (SELECT nama_usaha FROM umkm WHERE umkm.id = ulasan_umkm.umkm_id);
UPDATE ulasan_umkm SET komentar = 'Kerupuknya gurih dan rasa ikannya terasa. Langsung pesan lagi untuk oleh-oleh kantor.' WHERE id = 101;
UPDATE ulasan_umkm SET komentar = 'Mangkuk batoknya halus dan unik, saya pakai untuk hampers. Ibu-ibunya ramah dan mau menyesuaikan ukuran.' WHERE id = 103;
UPDATE ulasan_umkm SET komentar = 'Minyaknya jernih dan wangi kelapa. Saya pakai untuk memasak dan untuk kulit anak.' WHERE id = 104;
UPDATE ulasan_umkm SET komentar = 'Bandengnya gurih dan durinya benar-benar lunak. Anak saya suka.' WHERE id = 105;
UPDATE ulasan_umkm SET komentar = 'Briketnya awet, dipakai bakar ikan satu sore masih menyala. Asapnya juga tidak banyak.' WHERE id = 106;
UPDATE ulasan_umkm SET komentar = 'Rengginangnya mekar besar waktu digoreng dan rebonnya terasa. Cocok untuk teman makan.' WHERE id = 107;

-- ---------- Fasilitas (titik peta = perkiraan, ilustrasi; geser lewat CMS) ----------
DELETE FROM fasilitas;
INSERT INTO fasilitas (id, nama, kategori, alamat, keterangan, lat, lng) VALUES
(1, 'Kantor Desa Margamulya', 'Pemerintahan', 'Desa Margamulya, Kecamatan Mauk', 'Layanan administrasi Senin sampai Jumat (jam layanan ilustrasi). Titik peta perkiraan.', -6.0450, 106.5380),
(2, 'SD Negeri Margamulya', 'Pendidikan', 'Kp. Bebulak', 'Sekolah dasar negeri, berdiri 1983 (Dapodik). Titik peta perkiraan.', -6.0490, 106.5340),
(3, 'Kampung KB Bebulak', 'Kesehatan', 'RW 03, Dusun Bebulak', 'Kampung Keluarga Berkualitas binaan BKKBN. Titik peta perkiraan.', -6.0495, 106.5350),
(4, 'Posyandu RW 01 (ilustrasi)', 'Kesehatan', 'RW 01', 'Penimbangan balita dan imunisasi setiap bulan (ilustrasi).', -6.0420, 106.5420),
(5, 'Balai Desa dan Lapangan (ilustrasi)', 'Olahraga dan ruang publik', 'Dekat kantor desa', 'Tempat musyawarah dan kegiatan warga (ilustrasi).', -6.0455, 106.5370),
(6, 'Bank Sampah Margamulya (ilustrasi)', 'Lingkungan', 'Balai RW 03', 'Buka setiap Sabtu pagi (ilustrasi).', -6.0480, 106.5350),
(7, 'Warung Pantai Pasir Putih', 'Ekonomi', 'Pesisir Margamulya', '14 warung pantai direvitalisasi pada 2025 (UPJ). Titik peta perkiraan.', -6.0305, 106.5335),
(8, 'Pasar kaget Minggu pagi (ilustrasi)', 'Ekonomi', 'Jalan desa utama', 'Pasar tanpa bangunan, ramai Minggu pagi (ilustrasi).', -6.0470, 106.5400);

-- ---------- Zona rawan bencana (ilustrasi; wilayah pantura Tangerang rawan banjir, rob, dan abrasi) ----------
DELETE FROM zona_rawan;
INSERT INTO zona_rawan (id, nama, jenis, lat, lng, radius_m, keterangan) VALUES
(1, 'Pesisir Pantai Pasir Putih (ilustrasi)', 'Rob dan abrasi', -6.0290, 106.5330, 350, 'Air pasang bisa masuk ke daratan saat bulan purnama dan angin kencang. Jangan bermain air saat ombak tinggi.'),
(2, 'Dataran sawah dekat saluran Cikait (ilustrasi)', 'Banjir', -6.0560, 106.5395, 300, 'Air bisa meluap saat hujan lebat lebih dari 2 jam. Jauhi saluran yang arusnya deras.'),
(3, 'Kawasan mangrove dan tambak (ilustrasi)', 'Rob dan abrasi', -6.0330, 106.5290, 250, 'Jalan setapak tergenang saat pasang tinggi. Datang saat air surut dan bersama pemandu.');

-- ---------- Pembangunan (ilustrasi; saluran Cikait/Cikuil disebut dinormalisasi dalam berita 2023) ----------
UPDATE pembangunan SET nama = 'Perbaikan tambatan perahu nelayan (ilustrasi)', lokasi = 'Pesisir utara Margamulya', tahun = 2026, anggaran = 185000000, sumber_dana = 'Dana Desa', progres = 100, status = 'Selesai',
  deskripsi = 'Tambatan perahu diperkuat supaya perahu nelayan aman saat air pasang dan angin kencang.', lat = -6.0315, lng = 106.5355 WHERE id = 1;
UPDATE pembangunan SET nama = 'Pemeliharaan saluran Cikait dan Cikuil (ilustrasi)', lokasi = 'Area persawahan Margamulya', tahun = 2026, anggaran = 220000000, sumber_dana = 'Dana Desa', progres = 65, status = 'Berjalan',
  deskripsi = 'Melanjutkan normalisasi saluran pembuang supaya sawah tidak tergenang dan air tetap lancar saat musim tanam.', lat = -6.0550, lng = 106.5390 WHERE id = 2;
UPDATE pembangunan SET nama = 'Penataan bale dan warung Pantai Pasir Putih (ilustrasi)', lokasi = 'Pesisir Margamulya', tahun = 2026, anggaran = 95000000, sumber_dana = 'Pendapatan asli desa', progres = 40, status = 'Berjalan',
  deskripsi = 'Melanjutkan revitalisasi warung pantai tahun 2025 dengan tempat sampah terpilah dan toilet umum.', lat = -6.0300, lng = 106.5330 WHERE id = 3;
UPDATE pembangunan SET nama = 'Rumah pengolahan sampah TPS3R (ilustrasi)', lokasi = 'Dekat Balai RW 03', tahun = 2026, anggaran = 150000000, sumber_dana = 'Bantuan provinsi', progres = 0, status = 'Perencanaan',
  deskripsi = 'Tempat memilah sampah organik menjadi kompos dan plastik untuk bank sampah, supaya sampah tidak berakhir di laut.', lat = -6.0480, lng = 106.5350 WHERE id = 4;

-- ---------- Program bantuan (ilustrasi; id tetap karena data penerima menempel) ----------
UPDATE bantuan SET nama = 'Bantuan Langsung Tunai Dana Desa (ilustrasi)', penyelenggara = 'Pemerintah Desa Marga Mulya' WHERE id = 1;
UPDATE bantuan SET nama = 'Bantuan alat keselamatan melaut (ilustrasi)', penyelenggara = 'Dinas Perikanan Kabupaten Tangerang', sasaran = 'Nelayan kecil yang terdaftar di kelompok nelayan', syarat = 'Terdaftar sebagai anggota kelompok nelayan\nMemiliki Kartu KUSUKA (Kartu Pelaku Usaha Kelautan dan Perikanan)', jadwal = 'Pendataan melalui ketua kelompok nelayan (ilustrasi)' WHERE id = 2;
UPDATE bantuan SET nama = 'Pelatihan dan modal usaha olahan kelapa (ilustrasi)', penyelenggara = 'BUMDes Marga Mulya', sasaran = 'Pengrajin batok kelapa, pengolah hasil laut, dan pemuda yang ingin memulai usaha', syarat = 'Berusia 17 tahun ke atas\nBersedia mengikuti pelatihan 3 hari', jadwal = 'Dibuka dua kali setahun', cara_daftar = 'Isi formulir di kantor BUMDes atau kirim pesan lewat halaman Kontak dengan topik Layanan administrasi.' WHERE id = 3;

-- ---------- Budaya dan kegiatan rutin (ilustrasi) ----------
DELETE FROM budaya;
INSERT INTO budaya (id, judul, waktu, isi) VALUES
(1, 'Sedekah laut (ilustrasi)', 'Setahun sekali', 'Syukuran nelayan atas hasil laut, diisi doa bersama, arak-arakan perahu, dan makan bersama warga.'),
(2, 'Gotong royong bersih pantai (ilustrasi)', 'Minggu pertama setiap bulan', 'Warga, pemuda, dan pengunjung memungut sampah plastik di pesisir lalu menyetorkannya ke bank sampah.'),
(3, 'Tanam mangrove bersama (ilustrasi)', 'Musim hujan', 'Warga dan pengunjung menanam bibit bakau di pesisir untuk menahan abrasi.');

-- ---------- Agenda (id 2 dan 4 dipakai data relawan) ----------
DELETE FROM agenda WHERE id NOT IN (2, 4);
UPDATE agenda SET judul = 'Tanam mangrove bersama (ilustrasi)', tempat = 'Pesisir utara Margamulya', jam = '07.00 sampai 10.00', isi = 'Menanam bibit bakau di titik yang rawan abrasi. Terbuka untuk warga, pelajar, dan pengunjung. Datang saat air surut.' WHERE id = 2;
UPDATE agenda SET judul = 'Gotong royong bersih pantai (ilustrasi)', tempat = 'Pantai Pasir Putih Margamulya', jam = '06.30 sampai 09.30', isi = 'Memungut sampah plastik di pesisir dan menanam bibit mangrove. Sarung tangan dan karung disediakan.' WHERE id = 4;
INSERT INTO agenda (id, judul, tanggal, jam, tempat, isi, relawan_kuota) VALUES
(1, 'Posyandu RW 01 (ilustrasi)', '2026-11-21', '08.00 sampai 11.00', 'Posyandu RW 01', 'Penimbangan balita, imunisasi, dan pemberian makanan tambahan.', 0),
(5, 'Posyandu Kampung KB Bebulak (ilustrasi)', '2026-12-05', '08.00 sampai 11.00', 'RW 03, Dusun Bebulak', 'Penimbangan balita dan penyuluhan KB.', 0),
(101, 'Musyawarah desa penetapan penerima bantuan (ilustrasi)', '2026-11-27', '13.00 sampai 15.00', 'Balai desa', 'Pembahasan usulan penerima bantuan bersama ketua RT, RW, dan BPD.', 0),
(102, 'Penyuluhan keselamatan melaut (ilustrasi)', '2026-12-02', '09.00 sampai 12.00', 'Balai desa', 'Penggunaan pelampung, membaca prakiraan gelombang, dan pendataan Kartu KUSUKA untuk nelayan.', 0),
(103, 'Pelatihan olahan ikan untuk ibu-ibu nelayan (ilustrasi)', '2026-12-09', '09.00 sampai 14.00', 'Balai desa', 'Membuat bandeng presto, abon ikan, dan kemasan yang menarik untuk dijual.', 0);
UPDATE relawan SET agenda_judul = (SELECT judul FROM agenda WHERE agenda.id = relawan.agenda_id);

-- ---------- Berita (ilustrasi, kecuali yang menyebut sumber) ----------
DELETE FROM berita;
INSERT INTO berita (id, judul, tanggal, isi, foto) VALUES
(1, 'Warung dan bale Pantai Pasir Putih direvitalisasi bersama mahasiswa', '2026-11-15', 'Pada 2025, mahasiswa Universitas Pembangunan Jaya lewat program SENANDUNG merevitalisasi warung dan bale di Pantai Pasir Putih Margamulya, termasuk 14 warung pantai, serta melatih pelaku UMKM tentang kewirausahaan dan pemasaran digital.\n\nLangkah ini menjadi awal penataan pesisir sebagai tujuan wisata warga. (Rencana lanjutan pada berita ini adalah ilustrasi.)', '/assets/img/pantai.svg'),
(2, 'Pendataan Kartu KUSUKA untuk nelayan dibuka (ilustrasi)', '2026-11-11', 'Nelayan Margamulya yang belum memiliki Kartu Pelaku Usaha Kelautan dan Perikanan (KUSUKA) bisa mendaftar lewat ketua kelompok nelayan. Kartu ini menjadi syarat berbagai bantuan perikanan.\n\n(Jadwal dan teknis pendataan adalah ilustrasi.)', '/assets/img/perahu.svg'),
(3, 'Ibu-ibu nelayan belajar membuat bandeng presto (ilustrasi)', '2026-11-06', 'Pelatihan olahan ikan diikuti ibu-ibu dari kampung nelayan. Hasilnya dijual di warung Pantai Pasir Putih dan dititipkan lewat halaman UMKM.', '/assets/img/ikan.svg'),
(101, 'Website desa kini bisa dipakai mengajukan surat dari rumah', '2026-11-16', 'Warga Margamulya bisa mengajukan surat keterangan dari HP, memantau statusnya, lalu datang ke kantor desa saat surat sudah siap. (Tanggal peluncuran adalah ilustrasi.)', '/assets/img/foto/47c5aaa5.jpg'),
(102, 'Pemuda belajar membuat briket dari batok kelapa (ilustrasi)', '2026-11-08', 'Sisa batok kelapa yang biasanya dibuang kini diolah menjadi briket arang. Pelatihan diikuti pemuda dan ibu-ibu dari lima RW.', '/assets/img/kegiatan.svg'),
(103, 'Waspada rob saat bulan purnama (ilustrasi)', '2026-11-04', 'Air pasang tinggi bisa masuk ke kawasan pesisir saat bulan purnama dan angin kencang. Warga dan pengunjung diminta tidak bermain air saat ombak tinggi dan memantau peta zona rawan di website.', '/assets/img/pantai.svg'),
(104, 'Bank sampah kumpulkan 11,9 ton sampah (ilustrasi)', '2026-10-28', 'Sampah plastik yang dikumpulkan warga tidak lagi berakhir di saluran dan pantai, tetapi ditabung di bank sampah dan bisa ditukar uang.', '/assets/img/kegiatan.svg');

-- ---------- Galeri ----------
DELETE FROM galeri;
INSERT INTO galeri (id, judul, kategori, foto) VALUES
(105, 'Perahu nelayan Margamulya (gambar ilustrasi)', 'Alam', '/assets/img/perahu.svg'),
(106, 'Edukasi di posyandu (foto ilustrasi)', 'Kegiatan', '/assets/img/foto/47c5aaa5.jpg'),
(107, 'Pantai Pasir Putih (gambar ilustrasi)', 'Alam', '/assets/img/pantai.svg'),
(108, 'Rengginang rebon buatan warga (foto ilustrasi)', 'Kegiatan', '/assets/img/foto/64cc8c9f.jpg'),
(109, 'Mangrove pesisir (gambar ilustrasi)', 'Alam', '/assets/img/mangrove.svg'),
(110, 'Kerajinan batok kelapa (foto ilustrasi)', 'Kegiatan', '/assets/img/foto/70c97b14.jpg');

-- ---------- FAQ ----------
UPDATE faq SET jawaban = 'Ada. Kelas Kerajinan Batok Kelapa berlangsung di dalam ruangan, jadi tetap berjalan saat hujan. Untuk pantai dan mangrove, sebaiknya tunda kunjungan saat hujan deras atau angin kencang.' WHERE id = 21;
UPDATE faq SET jawaban = 'Hewan peliharaan boleh dibawa ke pantai asal memakai tali dan kotorannya dibersihkan. Hewan tidak boleh masuk ke petak sawah dan area tanam mangrove.' WHERE id = 22;
UPDATE faq SET pertanyaan = 'Apakah ada tempat makan dan toilet?', jawaban = 'Warung warga tersedia di Pantai Pasir Putih. Toilet tersedia di area pantai dan balai desa (ilustrasi).' WHERE id = 23;
UPDATE faq SET jawaban = 'Drone tidak boleh diterbangkan di atas sawah dan permukiman tanpa izin pengelola. Minta izin ke petugas desa lebih dulu.' WHERE id = 3;

-- ---------- Asisten desa: menu pilihan (ubah lewat CMS > Asisten desa) ----------
-- Tulisan {jam_layanan}, {alamat}, {wa}, dan {email} diganti otomatis dengan isi Pengaturan.
DELETE FROM asisten_menu;
INSERT INTO asisten_menu (id, induk_id, judul, pertanyaan, jawaban, tautan, label_tautan, urutan, aktif) VALUES
(1, 0, 'Layanan surat', 'Surat apa yang Anda perlukan?', '', '', '', 1, 1),
(2, 0, 'Bantuan sosial', 'Apa yang ingin Anda ketahui soal bantuan?', '', '', '', 2, 1),
(3, 0, 'Wisata dan kunjungan rombongan', 'Pilih informasi wisata:', '', '', '', 3, 1),
(4, 0, 'Produk UMKM warga', 'Mau membeli atau menitipkan produk?', '', '', '', 4, 1),
(5, 0, 'Aspirasi dan pengaduan', 'Pilih yang Anda perlukan:', '', '', '', 5, 1),
(6, 0, 'Nomor darurat', '', 'Dalam keadaan darurat, langsung telepon:\n112 untuk semua keadaan darurat\n110 Polisi\n113 Pemadam kebakaran\n119 Ambulans\nNomor lengkap ada di halaman Kontak.', '/kontak#darurat', 'Lihat semua nomor darurat', 6, 1),
(7, 0, 'Jam layanan dan alamat kantor desa', '', 'Kantor desa buka {jam_layanan}.\nAlamat: {alamat}\nWhatsApp: +{wa}', '/kontak', 'Buka halaman Kontak', 7, 1),
(11, 1, 'Surat keterangan tidak mampu (SKTM)', '', 'Ajukan lewat website supaya petugas bisa menyiapkannya lebih dulu. Saat mengambil surat, bawa surat pengantar RT dan RW, KTP, dan Kartu Keluarga asli. Syarat lengkap tertulis di halaman Layanan surat.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 1, 1),
(12, 1, 'Surat keterangan domisili', '', 'Ajukan lewat website, lalu ambil di kantor desa dengan membawa surat pengantar RT dan RW serta KTP asli. Syarat lengkap tertulis di halaman Layanan surat.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 2, 1),
(13, 1, 'Surat keterangan usaha', '', 'Cocok untuk nelayan, pedagang, atau pengrajin yang butuh surat untuk pinjaman atau bantuan usaha. Ajukan lewat website, lalu bawa KTP dan foto tempat usaha saat mengambil surat.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 3, 1),
(14, 1, 'Surat pengantar nikah', '', 'Ajukan lewat website, lalu datang bersama calon pengantin membawa KTP, Kartu Keluarga, dan pas foto. Petugas akan menjelaskan langkah berikutnya ke KUA.', '/data-desa?grup=Layanan+surat#surat', 'Ajukan surat sekarang', 4, 1),
(15, 1, 'Cek status surat saya', '', 'Masukkan kode pengajuan yang diawali SRT- di halaman Layanan surat untuk melihat apakah surat sudah siap diambil.', '/data-desa?grup=Layanan+surat&srt=#surat', 'Cek status surat', 5, 1),
(21, 2, 'Cara cek apakah saya penerima bantuan', '', 'Buka halaman Pantau bansos, lalu ketik nama dan RT. Demi privasi, nama penerima hanya ditampilkan sebagian.', '/data-desa?grup=Pantau+bansos#cek-bansos', 'Cek penerima bantuan', 1, 1),
(22, 2, 'Cara mengusulkan warga yang layak dibantu', '', 'Sampaikan usulan ke ketua RT atau lewat aspirasi warga dengan kategori Bantuan sosial. Usulan dibahas di musyawarah desa.', '/kontak#aspirasi', 'Kirim usulan', 2, 1),
(23, 2, 'Bantuan untuk nelayan', '', 'Bantuan perikanan biasanya mensyaratkan Kartu KUSUKA dan keanggotaan kelompok nelayan. Tanyakan pendataan ke ketua kelompok nelayan atau kantor desa.', '/data-desa?grup=Pantau+bansos', 'Lihat program bantuan', 3, 1),
(31, 3, 'Tempat wisata, harga, dan jam buka', '', 'Ada Pantai Pasir Putih, kawasan mangrove, kampung nelayan, dan kelas kerajinan batok kelapa. Harga dan jam buka tiap tempat ada di halaman Wisata.', '/wisata', 'Lihat semua wisata', 1, 1),
(32, 3, 'Pesan kunjungan rombongan', '', 'Rombongan minimal 10 orang (sekolah, kampus, komunitas) sebaiknya memesan dulu supaya pemandu dan tempat disiapkan. Pengunjung perorangan dan keluarga cukup datang langsung.', '/reservasi', 'Pesan kunjungan rombongan', 2, 1),
(33, 3, 'Waktu terbaik berkunjung dan info rob', '', 'Datang pagi atau sore supaya tidak terlalu panas. Hindari bermain air saat ombak tinggi atau bulan purnama karena air pasang bisa naik. Prakiraan cuaca ada di halaman Wisata.', '/wisata', 'Lihat prakiraan cuaca', 3, 1),
(41, 4, 'Cara memesan produk', '', 'Pilih produk di halaman UMKM, lalu tekan Pesan lewat WhatsApp. Pesan langsung terkirim ke pembuatnya. Harga dan pembayaran disepakati dengan penjual.', '/umkm', 'Lihat produk UMKM', 1, 1),
(42, 4, 'Cara menitipkan produk saya', '', 'Isi form Ajukan produk di halaman UMKM. Petugas desa meninjau 1 sampai 2 hari kerja, lalu produk tampil di website. Gratis.', '/umkm#ajukan', 'Ajukan produk', 2, 1),
(51, 5, 'Kirim aspirasi atau laporan', '', 'Tulis masalahnya, misalnya jalan rusak atau lampu mati, dan lampirkan foto bila ada. Anda mendapat kode untuk memantau tindak lanjutnya.', '/kontak#aspirasi', 'Kirim aspirasi', 1, 1),
(52, 5, 'Lihat perkembangan aspirasi warga', '', 'Semua aspirasi yang sudah diperiksa petugas tampil di Papan Aspirasi beserta statusnya.', '/kontak#papan', 'Buka Papan Aspirasi', 2, 1);
