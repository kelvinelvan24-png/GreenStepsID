-- ============================================================
-- GreenSteps ID — Database Schema + Seed Data
-- Import via phpMyAdmin atau: mysql -u root < greensteps.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS greensteps_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greensteps_db;

-- ========================
-- TABEL USERS
-- ========================
CREATE TABLE IF NOT EXISTS users (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100)  NOT NULL,
  email        VARCHAR(150)  NOT NULL UNIQUE,
  password     VARCHAR(255)  NOT NULL,
  city         VARCHAR(100)  DEFAULT '-',
  role         ENUM('admin','member') DEFAULT 'member',
  xp           INT           DEFAULT 0,
  created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================
-- TABEL PROGRAMS
-- ========================
CREATE TABLE IF NOT EXISTS programs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200)  NOT NULL,
  description  TEXT,
  category     VARCHAR(100)  DEFAULT 'Umum',
  status       ENUM('aktif','selesai','akan-datang') DEFAULT 'aktif',
  image_url    VARCHAR(255)  DEFAULT '',
  target       INT           DEFAULT 100,
  participants INT           DEFAULT 0,
  start_date   DATE,
  created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================
-- TABEL USER_PROGRAMS (join)
-- ========================
CREATE TABLE IF NOT EXISTS user_programs (
  user_id    INT NOT NULL,
  program_id INT NOT NULL,
  joined_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, program_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================
-- TABEL REP_LOG (XP history)
-- ========================
CREATE TABLE IF NOT EXISTS rep_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  amount     INT NOT NULL,
  reason     VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================
-- SEED DATA — USERS
-- password_hash dengan PASSWORD_BCRYPT
-- admin123  → $2y$10$...
-- sari123   → $2y$10$...
-- dst.
-- (hash dibuat oleh PHP, di sini pakai plaintext marker — config.php handle)
-- ========================
INSERT INTO users (name, email, password, city, role, xp) VALUES
('Admin GreenSteps', 'admin@greensteps.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jakarta',    'admin',  350),
('Sari Dewi',        'sari@email.com',       '$2y$10$TKh8H1.PfuPhX2859nMfeuyIwom/gFnfcLM4OfqTaHZuAjqJlG./e', 'Bandung',    'member', 180),
('Budi Santoso',     'budi@email.com',       '$2y$10$mKPxiNKwEhRkWXy4BhKrN.7Kw7fqBLz5cq8DuNpZnVx4TDl5x5B6', 'Surabaya',   'member', 60),
('Citra Lestari',    'citra@email.com',      '$2y$10$4kFJ0XvjAyG7T3O2.v2mEOH.1tYnXDw7s4VpKHR9vTjAl2PFQfgEa', 'Yogyakarta', 'member', 310);

-- Note: password plaintext mapping:
-- admin@greensteps.id → admin123
-- sari@email.com      → sari123
-- budi@email.com      → budi123
-- citra@email.com     → citra123

-- ========================
-- TABEL ARTICLES (Info)
-- ========================
CREATE TABLE IF NOT EXISTS articles (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255)  NOT NULL,
  excerpt      TEXT,
  content      TEXT,
  image_url    VARCHAR(255)  DEFAULT '',
  author_id    INT,
  created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================
-- SEED DATA — PROGRAMS
-- ========================
INSERT INTO programs (title, description, category, status, image_url, target, participants, start_date) VALUES
('30 Hari Zero Waste',      'Tantang dirimu selama 30 hari penuh untuk mengurangi sampah dalam kehidupan sehari-hari. Dari membawa tumbler, belanja tanpa plastik, hingga kompos sampah dapur.',       'Zero Waste',   'aktif',        'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80', 500,  340, '2025-01-01'),
('Beach Cleanup Nasional',  'Bergabunglah dalam aksi bersih pantai serentak di 20 kota pesisir Indonesia. Bersama kita jaga keindahan lautan untuk generasi mendatang.',                             'Bersih-Bersih','aktif',        'https://images.unsplash.com/photo-1618477461853-cf6ed80f417a?auto=format&fit=crop&w=600&q=80', 1000, 723, '2025-03-15'),
('1000 Pohon Jawa Barat',   'Program penanaman 1000 pohon di lahan kritis Jawa Barat. Setiap peserta mendapatkan bibit pohon dan panduan perawatan untuk ditanam di sekitar tempat tinggal.',        'Penghijauan',  'aktif',        'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80', 1000, 487, '2025-04-22'),
('Solar Panel Kampung',     'Edukasi dan bantuan instalasi panel surya untuk kampung yang belum terjangkau listrik di wilayah terpencil Indonesia.',                                                  'Energi',       'akan-datang',  'https://images.unsplash.com/photo-1509391366360-2e959784a276?auto=format&fit=crop&w=600&q=80', 200,  0,   '2025-06-01'),
('Sekolah Hijau 2024',      'Program edukasi lingkungan ke 50 sekolah dasar di seluruh Indonesia. Mengajarkan anak-anak tentang pentingnya menjaga lingkungan sejak dini.',                          'Edukasi',      'selesai',      'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=600&q=80', 500,  512, '2024-08-01'),
('Sungai Bersih Ciliwung',  'Aksi bersih sungai Ciliwung yang melibatkan warga sekitar, komunitas, dan pemerintah daerah untuk memulihkan ekosistem sungai.',                                       'Bersih-Bersih','selesai',      'https://images.unsplash.com/photo-1469122312224-c5846569feb1?auto=format&fit=crop&w=600&q=80', 300,  298, '2024-10-05');

-- ========================
-- SEED DATA — ARTICLES
-- ========================
INSERT INTO articles (title, excerpt, content, image_url, author_id) VALUES
('Mengapa Gaya Hidup Zero Waste Bukan Sekadar Tren', 'Kita hidup di era di mana sampah plastik mencemari lautan dan TPA sudah melampaui kapasitas. Saatnya berubah sebelum terlambat.', 'Gaya hidup zero waste atau bebas sampah adalah filosofi yang mendorong desain ulang siklus hidup sumber daya sehingga semua produk dapat digunakan kembali. Tidak ada sampah yang dikirim ke tempat pembuangan akhir, insinerator, atau laut. Proses yang direkomendasikan ini mirip dengan cara sumber daya digunakan kembali di alam. Mulailah dari langkah kecil: bawa botol minum sendiri, gunakan tas belanja kain, dan pelajari cara mengompos sisa makananmu.', 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80', 1),
('5 Produk Ramah Lingkungan Wajib Ada di Rumahmu', 'Pengganti plastik sekali pakai yang mudah ditemukan dan terjangkau di Indonesia.', 'Mengurangi plastik sekali pakai tidak harus mahal atau sulit. Berikut adalah 5 barang yang bisa kamu jadikan investasi jangka panjang: 1. Sikat gigi bambu (mudah terurai). 2. Loofah atau spons gambas untuk cuci piring (alternatif spons sintetis). 3. Tas belanja kanvas yang kuat. 4. Sabun mandi batangan (mengurangi botol plastik). 5. Wadah makanan kaca atau stainless (awet dan aman untuk makanan panas). Dengan mengubah barang-barang kecil ini, dampak yang diberikan bisa sangat besar.', 'https://images.unsplash.com/photo-1588688463994-38c23e85e51c?auto=format&fit=crop&w=600&q=80', 1),
('Kompos Sendiri di Rumah: Panduan Lengkap untuk Pemula', 'Ubah sisa makananmu menjadi pupuk subur dengan cara yang mudah dan tanpa biaya besar.', 'Lebih dari 50% sampah rumah tangga adalah sampah organik yang sebenarnya bisa dikomposkan. Jika dibuang ke TPA, sampah organik ini akan menghasilkan gas metana yang merupakan gas rumah kaca berbahaya. Cara membuat kompos sangat mudah: siapkan wadah berlubang, masukkan campuran tanah, sisa makanan (hijau), dan daun kering/kertas (cokelat). Aduk setiap beberapa hari agar oksigen masuk. Dalam 1-2 bulan, kamu akan mendapatkan kompos yang sangat subur untuk tanamanmu.', 'https://images.unsplash.com/photo-1518531933037-91b2f5f229cc?auto=format&fit=crop&w=600&q=80', 1);

-- ========================
-- SEED DATA — USER_PROGRAMS
-- ========================
INSERT INTO user_programs (user_id, program_id) VALUES
(2, 1), (2, 2),
(3, 1),
(4, 1), (4, 2), (4, 3);

-- ========================
-- TABEL CARBON_LOGS
-- ========================
CREATE TABLE IF NOT EXISTS carbon_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  transport    FLOAT DEFAULT 0,
  electricity  FLOAT DEFAULT 0,
  food         FLOAT DEFAULT 0,
  shopping     FLOAT DEFAULT 0,
  total        FLOAT DEFAULT 0,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
