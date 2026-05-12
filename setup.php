<?php
// ============================================================
// setup.php — Jalankan SEKALI untuk setup database
// Akses: http://localhost/greensteps/setup.php
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = '';
$port = 3306;

// Koneksi tanpa pilih DB dulu
$conn = new mysqli($host, $user, $pass, '', $port);
if ($conn->connect_error) {
    die('<h2 style="color:red">❌ Gagal konek MySQL: ' . $conn->connect_error . '</h2><p>Pastikan XAMPP MySQL sudah Start.</p>');
}

// Buat database
$conn->query("CREATE DATABASE IF NOT EXISTS greensteps_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db('greensteps_db');

// Buat tabel
$conn->query("CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  city       VARCHAR(100) DEFAULT '-',
  role       ENUM('admin','member') DEFAULT 'member',
  xp         INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS programs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(200) NOT NULL,
  description  TEXT,
  category     VARCHAR(100) DEFAULT 'Umum',
  status       ENUM('aktif','selesai','akan-datang') DEFAULT 'aktif',
  emoji        VARCHAR(10) DEFAULT '🌿',
  target       INT DEFAULT 100,
  participants INT DEFAULT 0,
  start_date   DATE,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS user_programs (
  user_id    INT NOT NULL,
  program_id INT NOT NULL,
  joined_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, program_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS rep_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  amount     INT NOT NULL,
  reason     VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed users (hanya jika belum ada)
$chk = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc();
if ($chk['c'] == 0) {
    $users = [
        ['Admin GreenSteps', 'admin@greensteps.id', 'admin123', 'Jakarta',    'admin',  350],
        ['Sari Dewi',        'sari@email.com',       'sari123',  'Bandung',    'member', 180],
        ['Budi Santoso',     'budi@email.com',       'budi123',  'Surabaya',   'member', 60],
        ['Citra Lestari',    'citra@email.com',      'citra123', 'Yogyakarta', 'member', 310],
    ];
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, city, role, xp) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($users as $u) {
        $hash = password_hash($u[2], PASSWORD_BCRYPT);
        $stmt->bind_param('sssssi', $u[0], $u[1], $hash, $u[3], $u[4], $u[5]);
        $stmt->execute();
    }
    echo "<p>✅ 4 user seed berhasil dibuat</p>";
}

// Seed programs
$chk2 = $conn->query("SELECT COUNT(*) as c FROM programs")->fetch_assoc();
if ($chk2['c'] == 0) {
    $programs = [
        ['30 Hari Zero Waste',     'Tantang dirimu selama 30 hari penuh untuk mengurangi sampah dalam kehidupan sehari-hari. Dari membawa tumbler, belanja tanpa plastik, hingga kompos sampah dapur.',       'Zero Waste',   'aktif',        '♻️', 500,  340, '2025-01-01'],
        ['Beach Cleanup Nasional', 'Bergabunglah dalam aksi bersih pantai serentak di 20 kota pesisir Indonesia. Bersama kita jaga keindahan lautan untuk generasi mendatang.',                             'Bersih-Bersih','aktif',        '🌊', 1000, 723, '2025-03-15'],
        ['1000 Pohon Jawa Barat',  'Program penanaman 1000 pohon di lahan kritis Jawa Barat. Setiap peserta mendapatkan bibit pohon dan panduan perawatan untuk ditanam di sekitar tempat tinggal.',        'Penghijauan',  'aktif',        '🌳', 1000, 487, '2025-04-22'],
        ['Solar Panel Kampung',    'Edukasi dan bantuan instalasi panel surya untuk kampung yang belum terjangkau listrik di wilayah terpencil Indonesia.',                                                  'Energi',       'akan-datang',  '☀️', 200,  0,   '2025-06-01'],
        ['Sekolah Hijau 2024',     'Program edukasi lingkungan ke 50 sekolah dasar di seluruh Indonesia. Mengajarkan anak-anak tentang pentingnya menjaga lingkungan sejak dini.',                          'Edukasi',      'selesai',      '📚', 500,  512, '2024-08-01'],
        ['Sungai Bersih Ciliwung', 'Aksi bersih sungai Ciliwung yang melibatkan warga sekitar, komunitas, dan pemerintah daerah untuk memulihkan ekosistem sungai.',                                       'Bersih-Bersih','selesai',      '🏞️', 300,  298, '2024-10-05'],
    ];
    $stmt2 = $conn->prepare("INSERT INTO programs (title, description, category, status, emoji, target, participants, start_date) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($programs as $p) {
        $stmt2->bind_param('sssssiis', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7]);
        $stmt2->execute();
    }
    echo "<p>✅ 6 program seed berhasil dibuat</p>";
}

// Seed user_programs
$chk3 = $conn->query("SELECT COUNT(*) as c FROM user_programs")->fetch_assoc();
if ($chk3['c'] == 0) {
    $joins = [[2,1],[2,2],[3,1],[4,1],[4,2],[4,3]];
    $stmt3 = $conn->prepare("INSERT IGNORE INTO user_programs (user_id, program_id) VALUES (?,?)");
    foreach ($joins as $j) {
        $stmt3->bind_param('ii', $j[0], $j[1]);
        $stmt3->execute();
    }
    echo "<p>✅ Data join user-program berhasil dibuat</p>";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Setup GreenSteps DB</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 60px auto; padding: 2rem; background: #f5fbee; border-radius: 16px; }
    h1 { color: #1f5c08; }
    .box { background: #fff; border: 1px solid #c8e8a0; border-radius: 10px; padding: 1.5rem; margin-top: 1rem; }
    .cred { background: #eaf6d8; padding: 0.5rem 1rem; border-radius: 8px; margin: 0.4rem 0; font-family: monospace; }
    a.btn { display: inline-block; margin-top: 1.5rem; background: #3d7d1f; color: #fff; padding: 0.7rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 700; }
    a.btn:hover { background: #2e6116; }
  </style>
</head>
<body>
  <h1>🌿 GreenSteps ID — Setup Database</h1>
  <div class="box">
    <h3>✅ Database <code>greensteps_db</code> siap!</h3>
    <p>Tabel yang dibuat: <strong>users, programs, user_programs, rep_log</strong></p>
    <p><strong>Akun demo:</strong></p>
    <div class="cred">admin@greensteps.id / admin123 (Admin)</div>
    <div class="cred">sari@email.com / sari123 (Member — 180 XP)</div>
    <div class="cred">budi@email.com / budi123 (Member — 60 XP)</div>
    <div class="cred">citra@email.com / citra123 (Member — 310 XP)</div>
    <a class="btn" href="greensteps-id.html">🚀 Buka GreenSteps ID</a>
  </div>
  <p style="margin-top:1rem;font-size:0.8rem;color:#7a9a50">
    ⚠️ Hapus file <code>setup.php</code> setelah setup selesai untuk keamanan.
  </p>
</body>
</html>
