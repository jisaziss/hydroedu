-- ========================================================
-- DATABASE SCHEMA: HydroEdu (Hydration & Education)
-- Praktikum Pemrograman Web 1 - Informatika Unsoed 2026
-- Mendukung 9 Modul & Tema SDGs 3 & 4
-- ========================================================

CREATE DATABASE IF NOT EXISTS `hydroedu` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hydroedu`;

-- --------------------------------------------------------
-- 1. TABEL USERS (MODUL 1: AUTENTIKASI & PROFIL)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') DEFAULT 'user',
  `jenis_kelamin` ENUM('L', 'P') DEFAULT 'L',
  `berat_badan` FLOAT DEFAULT 60.0,
  `tingkat_aktivitas` ENUM('ringan', 'sedang', 'berat') DEFAULT 'sedang',
  `target_harian_ml` INT DEFAULT 2000,
  `foto_profil` VARCHAR(255) DEFAULT 'default-avatar.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2. TABEL HYDRATION_LOGS (MODUL 2 & 7: TRACKER & STATISTIK)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hydration_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `jumlah_ml` INT NOT NULL,
  `kategori_minuman` ENUM('air_mineral', 'teh', 'jus', 'infus_water', 'lainnya') DEFAULT 'air_mineral',
  `catatan` VARCHAR(255) NULL,
  `waktu_minum` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tanggal` DATE NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3. TABEL HYDRATION_REMINDERS (MODUL 3: SMART CALCULATOR & REMINDER)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hydration_reminders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `interval_menit` INT DEFAULT 60,
  `jam_mulai` TIME DEFAULT '07:00:00',
  `jam_selesai` TIME DEFAULT '21:00:00',
  `status_aktif` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 4. TABEL ARTICLES (MODUL 4 & 9: PUSAT EDUKASI & CMS ADMIN)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `author_id` INT NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `kategori` ENUM('Kesehatan Ginjal', 'Performa Otak', 'Tips Hidrasi', 'Mitos & Fakta') NOT NULL,
  `ringkasan` TEXT NOT NULL,
  `konten` LONGTEXT NOT NULL,
  `gambar` VARCHAR(255) DEFAULT 'default-article.jpg',
  `views` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 5. TABEL QUIZ_QUESTIONS (MODUL 5 & 9: KUIS EDUKASI & CMS ADMIN)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pertanyaan` TEXT NOT NULL,
  `opsi_a` VARCHAR(255) NOT NULL,
  `opsi_b` VARCHAR(255) NOT NULL,
  `opsi_c` VARCHAR(255) NOT NULL,
  `opsi_d` VARCHAR(255) NOT NULL,
  `kunci_jawaban` ENUM('a', 'b', 'c', 'd') NOT NULL,
  `penjelasan` TEXT NOT NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 6. TABEL USER_QUIZ_ATTEMPTS (MODUL 5: RIWAYAT KUIS PENGGUNA)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_quiz_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `skor` INT NOT NULL,
  `total_benar` INT NOT NULL,
  `total_soal` INT NOT NULL,
  `waktu_selesai` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 7. TABEL BADGES & USER_BADGES (MODUL 6: GAMIFIKASI & PENGHARGAAN)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `badges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_badge` VARCHAR(50) NOT NULL UNIQUE,
  `nama_badge` VARCHAR(100) NOT NULL,
  `deskripsi` VARCHAR(255) NOT NULL,
  `kriteria` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `user_badges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `badge_id` INT NOT NULL,
  `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_badge_unique` (`user_id`, `badge_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`badge_id`) REFERENCES `badges`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 8. TABEL FORUM_POSTS & FORUM_COMMENTS (MODUL 8: COMMUNITY)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `forum_posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `konten` TEXT NOT NULL,
  `kategori` ENUM('Tips Harian', 'Pertanyaan', 'Cerita Sehat', 'Tantangan') DEFAULT 'Tips Harian',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `forum_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `komentar` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `forum_posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================================
-- DATA AWAL (SEEDER / DUMMY DATA)
-- Password default semua akun: password123 (bcrypt hash)
-- ========================================================

-- Akun Pengguna & Admin (password: password123)
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `jenis_kelamin`, `berat_badan`, `tingkat_aktivitas`, `target_harian_ml`) VALUES
(1, 'Admin HydroEdu', 'admin@hydroedu.id', '$2y$10$X6NtlQDuiK5JMm5y5jvLIOmBlkNYkql6Xd/5MX6ZBqwpv8ON3fwRS', 'admin', 'L', 65.0, 'sedang', 2300),
(2, 'Budi Santoso', 'budi@gmail.com', '$2y$10$X6NtlQDuiK5JMm5y5jvLIOmBlkNYkql6Xd/5MX6ZBqwpv8ON3fwRS', 'user', 'L', 70.0, 'berat', 2800),
(3, 'Siti Rahma', 'siti@gmail.com', '$2y$10$X6NtlQDuiK5JMm5y5jvLIOmBlkNYkql6Xd/5MX6ZBqwpv8ON3fwRS', 'user', 'P', 50.0, 'ringan', 1800);

-- Pengaturan Reminder Awal
INSERT INTO `hydration_reminders` (`user_id`, `interval_menit`, `jam_mulai`, `jam_selesai`, `status_aktif`) VALUES
(1, 60, '07:00:00', '21:00:00', 1),
(2, 45, '08:00:00', '22:00:00', 1),
(3, 90, '07:30:00', '20:00:00', 1);

-- Data Master Badges
INSERT INTO `badges` (`id`, `kode_badge`, `nama_badge`, `deskripsi`, `kriteria`, `icon`) VALUES
(1, 'first_sip', 'Tegukan Pertama', 'Mencatat konsumsi air untuk pertama kalinya.', 'Catat 1 log minum', 'bi-droplet-half'),
(2, 'target_hit', 'Target Harian Tuntas', 'Mencapai 100% target air dalam satu hari.', 'Capai target harian', 'bi-trophy-fill'),
(3, 'streak_3', 'Pahlawan Hidrasi 3 Hari', 'Mencapai target minum 3 hari berturut-turut.', 'Streak 3 hari', 'bi-fire'),
(4, 'scholar_80', 'Cendekia Hidrasi', 'Mendapatkan nilai kuis hidrasi minimal 80%.', 'Skor kuis >= 80', 'bi-mortarboard-fill'),
(5, 'community_star', 'Sahabat Komunitas', 'Membuat postingan pertama di forum HydroCommunity.', 'Post 1 topik forum', 'bi-chat-heart-fill');

-- Data Artikel Edukasi (SDG 4 & SDG 3)
INSERT INTO `articles` (`id`, `author_id`, `judul`, `slug`, `kategori`, `ringkasan`, `konten`, `views`) VALUES
(1, 1, 'Mengapa Otak Anda Membutuhkan Air Lebih Banyak Saat Belajar?', 'mengapa-otak-butuh-air-saat-belajar', 'Performa Otak', 'Dehidrasi ringan sebesar 1-2% saja dapat menurunkan daya konsentrasi, memori jangka pendek, dan kecepatan berpikir mahasiswa.', 'Otak manusia terdiri dari sekitar 73% air. Saat Anda belajar atau mengerjakan tugas berjam-jam, neuron otak bekerja keras dan menghasilkan panas metabolisme. Tanpa asupan cairan yang cukup, sirkulasi oksigen ke otak melambat sehingga memicu rasa kantuk, sakit kepala ringan, dan penurunan fokus. Biasakan meletakkan tumbler air di meja belajar dan minum seteguk setiap 20-30 menit.', 45),
(2, 1, 'Mitos atau Fakta: Apakah Semua Orang Wajib Minum 2 Liter Sehari?', 'mitos-fakta-aturan-2-liter-air', 'Mitos & Fakta', 'Aturan 8 gelas atau 2 liter per hari hanyalah patokan umum. Kebutuhan air setiap individu sebenarnya sangat personal.', 'Kebutuhan cairan tiap orang dipengaruhi oleh berat badan, iklim cuaca, serta intensitas aktivitas fisik. Rumus klinis dasar yang direkomendasikan adalah sekitar 30-35 ml air per kilogram berat badan. Artinya, orang dengan berat badan 70 kg membutuhkan sekitar 2.100 - 2.450 ml per hari. Gunakan fitur kalkulator di HydroEdu untuk mengetahui takaran ideal Anda.', 32),
(3, 1, 'Menjaga Ginjal Tetap Prima: Tanda Warna Urine yang Perlu Anda Waspadai', 'menjaga-ginjal-tanda-warna-urine', 'Kesehatan Ginjal', 'Warna urine adalah indikator visual paling akurat untuk mengecek status hidrasi tubuh Anda secara mandiri.', 'Salah satu cara termudah mendeteksi kecukupan cairan adalah melalui skala warna urine. Warna kuning pucat atau bening menandakan tubuh terhidrasi dengan baik. Sebaliknya, jika warna kuning pekat atau kecokelatan, itu sinyal darurat bahwa ginjal bekerja ekstra keras memfiltrasi racun karena kekurangan cairan pelarut.', 28);

-- Soal Kuis Edukasi (SDG 4)
INSERT INTO `quiz_questions` (`id`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `penjelasan`) VALUES
(1, 'Berapa persentase rata-rata kandungan air di dalam otak manusia?', 'Sekitar 50%', 'Sekitar 73%', 'Sekitar 85%', 'Sekitar 30%', 'b', 'Otak manusia terdiri atas sekitar 73% air. Kekurangan cairan sedikit saja langsung berdampak pada fungsi memori dan daya konsentrasi.'),
(2, 'Berapa rumus dasar estimasi kebutuhan air harian berdasarkan berat badan?', '10 ml per kg berat badan', '20 ml per kg berat badan', '30-35 ml per kg berat badan', '50-60 ml per kg berat badan', 'c', 'Rekomendasi medis umum menyatakan kebutuhan cairan tubuh rata-rata adalah 30 hingga 35 ml per kilogram berat badan untuk orang dewasa dengan aktivitas sedang.'),
(3, 'Warna urine yang menandakan bahwa tubuh Anda terhidrasi dengan sangat baik adalah...', 'Kuning pekat kemerahan', 'Kuning cerah bening atau pucat', 'Oranye kecokelatan', 'Putih susu', 'b', 'Warna kuning bening/jerami pucat menandakan ginjal memiliki cukup cairan untuk melarutkan limbah metabolik secara optimal.'),
(4, 'Apa dampak utama dari dehidrasi ringan (1-2%) saat Anda sedang belajar?', 'Penurunan konsentrasi dan mudah lelah', 'Koma mendadak', 'Peningkatan memori otak', 'Tekanan darah langsung melonjak drastis', 'a', 'Dehidrasi ringan (1-2%) telah terbukti secara ilmiah menurunkan kewaspadaan, memicu sakit kepala, dan memperlambat waktu respons otak.'),
(5, 'Kapan waktu terbaik untuk minum air putih guna mengaktifkan metabolisme tubuh?', 'Tepat sebelum tidur pulas', '1-2 gelas sesaat setelah bangun tidur di pagi hari', 'Hanya ketika mulut terasa sangat kering', 'Saat sedang makan besar', 'b', 'Minum air sesaat setelah bangun tidur membantu rehidrasi tubuh setelah 6-8 jam beristirahat, mengaktifkan organ internal, dan melancarkan sirkulasi darah.');

-- Contoh Log Minum Awal
INSERT INTO `hydration_logs` (`user_id`, `jumlah_ml`, `kategori_minuman`, `catatan`, `waktu_minum`, `tanggal`) VALUES
(2, 300, 'air_mineral', 'Segelas air setelah bangun pagi', CONCAT(CURDATE(), ' 06:30:00'), CURDATE()),
(2, 600, 'air_mineral', 'Tumbler saat kuliah pagi', CONCAT(CURDATE(), ' 09:15:00'), CURDATE()),
(2, 250, 'infus_water', 'Lemon water saat istirahat siang', CONCAT(CURDATE(), ' 12:45:00'), CURDATE());

-- Contoh Postingan Forum
INSERT INTO `forum_posts` (`id`, `user_id`, `judul`, `konten`, `kategori`, `created_at`) VALUES
(1, 2, 'Tips Konsisten Bawa Tumbler 1 Liter ke Kampus!', 'Halo teman-teman! Saya dulu sering lupa minum saat jam praktikum. Sekarang trik saya adalah menyiapkan botol 1 liter yang ada penanda jamnya di malam hari, lalu langsung saya masukkan ke tas ransel. Ada yang punya tips lain?', 'Tips Harian', NOW());

INSERT INTO `forum_comments` (`id`, `post_id`, `user_id`, `komentar`, `created_at`) VALUES
(1, 1, 3, 'Setuju banget! Aku juga pasang reminder tiap 60 menit di laptop supaya gak keasikan coding sampai lupa minum air.', NOW());
