-- =========================================================
-- ISI KONTEN CONTOH (Fase 12b)
-- Berita lengkap, agenda, deskripsi dan ulasan UMKM, ulasan wisata, galeri, FAQ.
-- AMAN dijalankan ulang: data yang sudah ada tidak digandakan.
-- Jalankan SETELAH migrasi-semua.sql.
--
-- PENTING: nama orang, angka, dan kejadian di sini adalah KARANGAN untuk mengisi tampilan.
-- Sesuaikan dengan Data Desa Marga Mulya dari panitia sebelum dikumpulkan.
-- =========================================================

-- ---------- Berita yang sudah ada: isi dibuat lengkap ----------
UPDATE berita SET foto = '/assets/img/air.svg', isi = 'Ruas jalan sepanjang 1,2 km dari balai desa menuju parkiran Curug Cikaso Mulya selesai dibeton pada pekan ini. Sebelumnya jalan berupa batu dan tanah yang licin saat hujan, sehingga minibus rombongan harus berhenti di balai desa.

Pembangunan memakai Dana Desa 2026 sebesar Rp412 juta dan dikerjakan secara padat karya oleh 38 warga dari RW 03 dan RW 04 selama tujuh minggu. Lebar jalan kini 4 meter dengan saluran air di kedua sisi supaya air hujan tidak menggerus badan jalan.

Kepala Desa mengingatkan pengunjung tetap mengikuti saran cuaca di website. Saat hujan deras, jalur setapak dari parkiran ke curug tetap ditutup demi keselamatan.' WHERE judul = 'Jalan menuju Curug Cikaso selesai dibeton';

UPDATE berita SET foto = '/assets/img/kegiatan.svg', isi = 'Cakupan imunisasi dasar lengkap bayi di Desa Marga Mulya mencapai 94 persen tahun ini, naik 6 poin dibanding tahun lalu. Kenaikan ini terjadi setelah posyandu tidak lagi hanya buka di balai RW 01, tetapi berkeliling ke setiap RW sesuai jadwal bulanan.

Bidan desa mencatat banyak orang tua yang sebelumnya terkendala jarak dan pekerjaan di sawah kini bisa datang karena posyandu digelar dekat rumah. Kader juga mengingatkan jadwal lewat grup WhatsApp RT dan kalender di halaman Informasi Desa.

Target berikutnya adalah 100 persen, termasuk imunisasi lanjutan untuk anak usia 18 bulan. Orang tua yang anaknya tertinggal jadwal bisa datang ke posyandu terdekat atau bertanya ke bidan desa.' WHERE judul = 'Cakupan imunisasi dasar capai 94 persen';

UPDATE berita SET foto = '/assets/img/jeruk.svg', isi = 'Dinas Pertanian Kabupaten kembali membuka bantuan bibit jeruk dan pupuk organik untuk kelompok tani. Tahun ini Desa Marga Mulya mendapat kuota 60 paket, masing-masing berisi 25 bibit jeruk siam dan 50 kg pupuk organik.

Pendaftaran dilakukan lewat ketua kelompok tani masing-masing, lalu dikumpulkan di kantor desa paling lambat akhir bulan. Penerima akan ditetapkan lewat musyawarah dan hasilnya bisa dipantau di menu Pantau bansos.

Kelompok tani yang menerima bantuan tahun lalu diminta melaporkan jumlah bibit yang tumbuh, supaya distribusi tahun ini bisa diprioritaskan untuk kebun yang belum pernah menerima.' WHERE judul = 'Pendaftaran bantuan bibit jeruk dibuka';

-- ---------- Berita baru ----------
INSERT IGNORE INTO berita (id, judul, tanggal, isi, foto) VALUES
(101, 'Website desa kini bisa dipakai mengajukan surat dari rumah', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Mulai bulan ini, warga Marga Mulya bisa mengajukan surat keterangan dari rumah lewat menu Informasi Desa, bagian Layanan surat. Warga cukup memilih jenis surat dan menulis keperluannya, lalu mendapat kode pengajuan yang diawali SRT.

Petugas menyiapkan surat lebih dulu. Saat status berubah menjadi Siap diambil, warga datang ke kantor desa membawa KTP dan KK asli, lalu mengambil nomor antrean lewat QR di meja layanan. Dengan cara ini warga cukup datang sekali.

Pemerintah desa sengaja tidak meminta NIK atau foto KTP lewat website untuk menjaga keamanan data kependudukan warga.', '/assets/img/kegiatan.svg'),
(102, 'Panen raya jeruk diperkirakan lebih awal', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Kelompok Tani Mulya Sejahtera memperkirakan panen raya jeruk tahun depan bisa dimulai lebih awal, sekitar akhir Mei, karena pembungaan serempak setelah kemarau.

Agrowisata Petik Jeruk akan menambah kuota harian dari 60 menjadi 80 pengunjung selama musim panen. Pengunjung disarankan memesan lewat menu Reservasi supaya bisa datang tanpa antre di loket.

Selain jeruk segar, KWT Mulya Sejahtera menyiapkan produk olahan seperti sirup jeruk dan selai yang bisa dibeli di kios oleh-oleh kebun.', '/assets/img/jeruk.svg'),
(103, 'Pemuda desa ikut kelas anyaman, sepuluh produk baru dipasarkan', DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'Sebanyak 20 pemuda mengikuti kelas anyaman bambu empat pertemuan di Kampung Anyaman. Para pengrajin senior mengajarkan teknik dasar sampai pembuatan produk modern seperti tas laptop dan kap lampu.

Hasilnya, sepuluh desain produk baru mulai dipasarkan lewat katalog UMKM di website desa dan pameran kabupaten. Beberapa peserta kini menerima pesanan dari luar kota lewat WhatsApp.

BUMDes berencana membuka kelas kedua awal tahun depan. Pendaftaran akan diumumkan di menu Agenda.', '/assets/img/anyaman.svg'),
(104, 'Waspada longsor di lereng menuju curug selama musim hujan', DATE_SUB(CURDATE(), INTERVAL 13 DAY), 'Memasuki musim hujan, pemerintah desa bersama relawan Linmas memasang rambu peringatan di tiga titik rawan longsor dan banjir, yaitu lereng menuju Curug Cikaso, bantaran Sungai Cikaso, dan tebing jalan RW 04.

Lokasi titik rawan bisa dilihat di Peta desa pada halaman Profil. Saat prakiraan hujan lebat muncul di website, zona tersebut otomatis disorot dan warga diminta tidak melintas.

Warga yang melihat retakan tanah atau pohon miring di sekitar lereng diminta segera melapor ke posko desa atau menelepon nomor darurat yang tercantum di halaman Kontak.', '/assets/img/sawah2.svg'),
(105, 'Bank sampah kumpulkan 11,9 ton sampah dalam enam bulan', DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Bank Sampah Mulya Lestari mencatat 11,9 ton sampah terpilah dalam enam bulan terakhir. Sampah plastik dan kardus dijual ke pengepul, sedangkan sampah organik diolah menjadi kompos untuk kebun jeruk.

Nasabah bank sampah kini berjumlah 214 keluarga. Tabungan sampah bisa dicairkan menjelang hari raya atau dipakai membayar iuran kebersihan.

Pengunjung wisata juga diajak ikut menjaga desa dengan membawa pulang sampahnya atau memasukkannya ke tempat sampah terpilah di setiap destinasi.', '/assets/img/kegiatan.svg');

-- ---------- Agenda tambahan ----------
INSERT IGNORE INTO agenda (id, judul, tanggal, jam, tempat, isi) VALUES
(101, 'Musyawarah desa penetapan penerima bantuan', DATE_ADD(CURDATE(), INTERVAL 10 DAY), '19.30 sampai 21.30', 'Balai desa', 'Pembahasan usulan penerima bantuan tahun depan. Terbuka untuk ketua RT, RW, tokoh masyarakat, dan warga yang ingin memberi masukan.'),
(102, 'Pasar tani Minggu pagi', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '06.00 sampai 10.00', 'Halaman balai desa', 'Sayur, buah, beras, dan olahan tani langsung dari petani Marga Mulya. Bawa tas belanja sendiri.'),
(103, 'Senam lansia dan cek tensi gratis', DATE_ADD(CURDATE(), INTERVAL 12 DAY), '07.00 sampai 09.00', 'Lapangan desa', 'Senam bersama dilanjutkan pemeriksaan tekanan darah dan gula darah oleh bidan desa dan kader posbindu.');

-- ---------- Deskripsi UMKM yang sudah ada ----------
UPDATE umkm SET deskripsi = 'Keripik dibuat dari singkong kebun sendiri, digoreng setiap pagi dan dibumbui cabai serta gula aren. Bu Darmi sudah berjualan sejak 2009 dan memasok warung di tiga kecamatan.' WHERE nama_usaha = 'Keripik Singkong Bu Darmi' AND deskripsi IS NULL;
UPDATE umkm SET deskripsi = 'Dianyam oleh 12 ibu di RW 02 dari bambu tali yang dipanen di kebun desa. Bisa pesan ukuran dan warna khusus untuk hampers atau seserahan.' WHERE nama_usaha = 'Anyaman Rukun' AND deskripsi IS NULL;
UPDATE umkm SET deskripsi = 'Diperas dari jeruk siam hasil kebun anggota KWT, tanpa pewarna dan pengawet. Simpan di kulkas setelah dibuka, tahan sampai dua minggu.' WHERE nama_usaha = 'Sirup Jeruk Mulya' AND deskripsi IS NULL;
UPDATE umkm SET deskripsi = 'Ditanam di Terasering Sigarung dengan pupuk kompos dari bank sampah desa. Pulen dan wangi, digiling setelah dipesan supaya tetap segar.' WHERE nama_usaha = 'Beras Sigarung' AND deskripsi IS NULL;
UPDATE umkm SET deskripsi = 'Tikar pandan dengan motif warisan nenek Mak Ijah. Satu lembar dikerjakan sekitar tiga hari. Cocok untuk alas duduk atau hiasan dinding.' WHERE nama_usaha = 'Tikar Pandan Mak Ijah' AND deskripsi IS NULL;
UPDATE umkm SET deskripsi = 'Rengginang ketan dengan terasi dan bawang, dijemur di bawah matahari dua hari. Dijual mentah supaya bisa digoreng sendiri di rumah.' WHERE nama_usaha = 'Rengginang Kang Ujang' AND deskripsi IS NULL;

-- ---------- UMKM baru ----------
INSERT IGNORE INTO umkm (id, nama_usaha, kategori, produk, harga, satuan, pemilik, wa, lat, lng, foto, deskripsi) VALUES
(101, 'Kopi Lereng Cikaso', 'Olahan tani', 'Kopi robusta bubuk sangrai sedang', 45000, '200 g', 'Kang Asep, RT 09', '6280000000107', -7.206, 107.577, '/assets/img/sawah2.svg', 'Kopi ditanam di sela pohon jeruk di lereng Cikaso, disangrai sendiri dalam jumlah kecil. Bisa pesan bentuk biji untuk kedai kopi.'),
(102, 'Dodol Jeruk Teh Euis', 'Makanan', 'Dodol jeruk tanpa pengawet', 25000, '250 g', 'Teh Euis, RT 05', '6280000000108', -7.209, 107.574, '/assets/img/jeruk.svg', 'Dodol kenyal dari sari jeruk dan ketan, dimasak di kuali selama enam jam. Oleh-oleh favorit pengunjung kebun jeruk.'),
(103, 'Ojek Wisata Mulya', 'Jasa', 'Antar jemput parkiran ke curug', 15000, 'orang, pulang pergi', 'Paguyuban ojek RW 04', '6280000000109', -7.207, 107.575, '/assets/img/air.svg', 'Delapan pengemudi warga desa yang hafal jalur. Membantu lansia dan anak-anak dari parkiran sampai gerbang jalur curug.');

-- ---------- Ulasan UMKM (sudah disetujui) ----------
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 101, id, nama_usaha, 'Rara', 5, 'Pedas manisnya pas, renyah sampai hari ketiga. Langsung pesan lagi untuk oleh-oleh kantor.', 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY) FROM umkm WHERE nama_usaha = 'Keripik Singkong Bu Darmi';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 102, id, nama_usaha, 'Pak Hendra', 4, 'Rasanya enak, cuma kemasannya bisa dibuat lebih rapat supaya tidak cepat melempem.', 1, DATE_SUB(CURDATE(), INTERVAL 18 DAY) FROM umkm WHERE nama_usaha = 'Keripik Singkong Bu Darmi';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 103, id, nama_usaha, 'Nadia', 5, 'Keranjangnya kuat dan rapi, saya pakai untuk hampers lebaran. Ibu-ibunya ramah dan mau menyesuaikan ukuran.', 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY) FROM umkm WHERE nama_usaha = 'Anyaman Rukun';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 104, id, nama_usaha, 'Dimas', 5, 'Sirupnya benar-benar terasa jeruk asli, tidak terlalu manis. Enak dicampur air es dan soda.', 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY) FROM umkm WHERE nama_usaha = 'Sirup Jeruk Mulya';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 105, id, nama_usaha, 'Bu Sri', 5, 'Berasnya pulen dan wangi. Senang bisa beli langsung dari petani yang menanamnya.', 1, DATE_SUB(CURDATE(), INTERVAL 9 DAY) FROM umkm WHERE nama_usaha = 'Beras Sigarung';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 106, id, nama_usaha, 'Larasati', 4, 'Motifnya cantik sekali. Pengiriman ke Bandung butuh empat hari, tapi barang sampai aman.', 1, DATE_SUB(CURDATE(), INTERVAL 25 DAY) FROM umkm WHERE nama_usaha = 'Tikar Pandan Mak Ijah';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 107, id, nama_usaha, 'Yoga', 5, 'Rengginangnya mekar besar waktu digoreng dan terasinya terasa. Cocok untuk teman makan.', 1, DATE_SUB(CURDATE(), INTERVAL 12 DAY) FROM umkm WHERE nama_usaha = 'Rengginang Kang Ujang';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 108, id, nama_usaha, 'Fikri', 5, 'Aromanya kuat dan tidak asam. Kang Asep juga menjelaskan cara seduh yang pas.', 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY) FROM umkm WHERE nama_usaha = 'Kopi Lereng Cikaso';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 109, id, nama_usaha, 'Keluarga Wibowo', 5, 'Anak-anak suka sekali dodolnya, rasa jeruknya segar. Habis dalam sehari.', 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY) FROM umkm WHERE nama_usaha = 'Dodol Jeruk Teh Euis';
INSERT IGNORE INTO ulasan_umkm (id, umkm_id, usaha, nama, bintang, komentar, tampil, dibuat_pada) SELECT 110, id, nama_usaha, 'Bu Ratna', 5, 'Sangat membantu untuk ibu saya yang sudah sepuh. Pengemudinya sabar dan hati-hati di tanjakan.', 1, DATE_SUB(CURDATE(), INTERVAL 7 DAY) FROM umkm WHERE nama_usaha = 'Ojek Wisata Mulya';

-- ---------- Reservasi selesai + ulasan wisata tambahan ----------
INSERT IGNORE INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0201', id, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 4, 'Keluarga Santoso', '081200000031', '', 'Selesai', 'Mobil pribadi', 35 FROM wisata WHERE slug = 'terasering';
INSERT IGNORE INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0202', id, DATE_SUB(CURDATE(), INTERVAL 9 DAY), 22, 'Pramuka SMPN 1 Sukamaju', '081200000032', '', 'Selesai', 'Bus atau elf rombongan', 12 FROM wisata WHERE slug = 'anyaman';
INSERT IGNORE INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0203', id, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 2, 'Anisa Putri', '081200000033', '', 'Selesai', 'Sepeda motor', 20 FROM wisata WHERE slug = 'curug';
INSERT IGNORE INTO reservasi (kode, wisata_id, tanggal, jumlah, nama, wa, catatan, status, kendaraan, jarak_km)
SELECT 'MM-0204', id, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 5, 'Arisan RT 02 Sukamaju', '081200000034', '', 'Selesai', 'Angkutan umum', 15 FROM wisata WHERE slug = 'jeruk';

INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar, dibuat_pada)
SELECT id, wisata_id, 'Keluarga Santoso', 5, 'Kami berangkat jam enam pagi sesuai saran website, kabutnya turun di antara petak sawah. Pemandu menjelaskan cara pengairan tradisional dengan sabar.', DATE_SUB(CURDATE(), INTERVAL 11 DAY) FROM reservasi WHERE kode = 'MM-0201';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar, dibuat_pada)
SELECT id, wisata_id, 'Kak Bima, pembina pramuka', 5, 'Hujan turun sejak siang tapi kelas anyaman tetap jalan di dalam ruangan. Setiap anak pulang membawa tatakan buatan sendiri.', DATE_SUB(CURDATE(), INTERVAL 8 DAY) FROM reservasi WHERE kode = 'MM-0202';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar, dibuat_pada)
SELECT id, wisata_id, 'Anisa', 4, 'Curugnya indah dan jalannya sudah bagus. Jalur setapak terakhir agak licin, pakai sandal gunung.', DATE_SUB(CURDATE(), INTERVAL 6 DAY) FROM reservasi WHERE kode = 'MM-0203';
INSERT IGNORE INTO ulasan (reservasi_id, wisata_id, nama, bintang, komentar, dibuat_pada)
SELECT id, wisata_id, 'Bu Wati', 5, 'Petik jeruk sambil ngobrol dengan petaninya. Kami naik angkot dan dapat kupon Tiket Hijau untuk beli sirup jeruk.', DATE_SUB(CURDATE(), INTERVAL 3 DAY) FROM reservasi WHERE kode = 'MM-0204';

-- ---------- Galeri tambahan (ganti dengan foto asli desa lewat CMS > Galeri) ----------
INSERT IGNORE INTO galeri (id, judul, kategori, foto) VALUES
(101, 'Kelas anyaman untuk pemuda', 'Kegiatan', '/assets/img/anyaman2.svg'),
(102, 'Sawah Sigarung menjelang panen', 'Alam', '/assets/img/sawah2.svg'),
(103, 'Pasar tani Minggu pagi', 'Kegiatan', '/assets/img/kirab.svg'),
(104, 'Jalan beton menuju curug', 'Pembangunan', '/assets/img/air.svg');

-- ---------- Pertanyaan umum tambahan ----------
INSERT IGNORE INTO faq (id, pertanyaan, kata_kunci, jawaban, urutan, aktif) VALUES
(21, 'Apakah ada wisata yang tetap bisa dikunjungi saat hujan?', 'hujan,indoor,dalam ruangan,berteduh,musim hujan', 'Ada. Kelas di Kampung Anyaman Bambu berlangsung di dalam ruangan, jadi tetap berjalan saat hujan. Kios oleh-oleh UMKM juga tetap buka. Curug dan jalur lereng bisa ditutup saat hujan deras.', 21, 1),
(22, 'Apakah bisa membawa hewan peliharaan?', 'hewan,anjing,kucing,peliharaan', 'Hewan peliharaan boleh dibawa ke Terasering dan kebun jeruk asal memakai tali dan kotorannya dibersihkan. Hewan tidak boleh dibawa ke kelas anyaman dan kolam curug.', 22, 1),
(23, 'Apakah ada musala dan tempat makan?', 'musala,musholla,salat,sholat,makan,warung,kuliner', 'Setiap destinasi punya musala kecil. Warung makan warga ada di parkiran curug dan dekat kebun jeruk, menyediakan nasi liwet, pepes, dan minuman jeruk segar.', 23, 1),
(24, 'Bagaimana cara menitipkan produk di katalog UMKM desa?', 'titip,daftar umkm,jualan,katalog,produk saya', 'Warga Marga Mulya yang punya usaha bisa mendaftar ke kantor desa atau BUMDes dengan membawa contoh produk. Petugas akan memotret produk dan memasukkannya ke katalog UMKM tanpa biaya.', 24, 1);
