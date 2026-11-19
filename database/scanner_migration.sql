-- ========================================================
-- MIGRASI: AI Drink Scanner (tambahan, tidak mengubah tabel lama)
-- Jalankan sekali di phpMyAdmin pada database `hydroedu`
-- (tab SQL / Import). Aman dijalankan berulang (IF NOT EXISTS).
-- Catatan: tabel ini juga dibuat otomatis oleh aplikasi saat fitur
-- pertama kali dibuka, jadi langkah ini opsional.
-- ========================================================

USE `hydroedu`;

CREATE TABLE IF NOT EXISTS `drink_scans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nama_minuman` VARCHAR(150) NOT NULL,
  `kategori` VARCHAR(40) NOT NULL,
  `hasil_json` MEDIUMTEXT NOT NULL,          -- hasil analisis terstruktur (JSON)
  `thumbnail` MEDIUMTEXT NULL,               -- pratinjau JPEG kecil (data URL), bukan foto asli
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_created` (`user_id`, `created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
