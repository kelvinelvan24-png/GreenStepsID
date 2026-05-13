<?php
require_once __DIR__ . '/config.php';

$db = getDB();

echo "<h1>GreenSteps DB Setup & Migration</h1>";

// 1. Ubah kolom emoji menjadi image_url di tabel programs
echo "<h3>1. Mengubah struktur tabel programs...</h3>";
$query1 = "ALTER TABLE programs CHANGE emoji image_url VARCHAR(255) DEFAULT ''";
if ($db->query($query1)) {
    echo "<p style='color:green;'>✅ Berhasil: Kolom 'emoji' diubah menjadi 'image_url'.</p>";
} else {
    // Kemungkinan kolom sudah tidak ada/sudah diubah, abaikan error
    echo "<p style='color:orange;'>⚠️ Info: Kolom 'emoji' mungkin sudah diubah sebelumnya (Error: " . $db->error . ")</p>";
}

// 2. Buat tabel articles
echo "<h3>2. Membuat tabel articles...</h3>";
$query2 = "CREATE TABLE IF NOT EXISTS articles (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255)  NOT NULL,
  excerpt      TEXT,
  content      TEXT,
  image_url    VARCHAR(255)  DEFAULT '',
  author_id    INT,
  created_at   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($db->query($query2)) {
    echo "<p style='color:green;'>✅ Berhasil: Tabel 'articles' berhasil dibuat atau sudah ada.</p>";
} else {
    echo "<p style='color:red;'>❌ Gagal: " . $db->error . "</p>";
}

// 3. Update data programs (Mengganti sisa-sisa emoji menjadi URL)
echo "<h3>3. Memperbarui data kampanye (emoji lama -> gambar)...</h3>";
$updateQueries = [
    "UPDATE programs SET image_url = 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80' WHERE title LIKE '%Zero Waste%'",
    "UPDATE programs SET image_url = 'https://images.unsplash.com/photo-1595273670150-bd0c3c392e46?auto=format&fit=crop&w=600&q=80' WHERE title LIKE '%Beach Cleanup%'",
    "UPDATE programs SET image_url = 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80' WHERE title LIKE '%1000 Pohon%'",
    "UPDATE programs SET image_url = 'https://images.unsplash.com/photo-1509391366360-2e959784a276?auto=format&fit=crop&w=600&q=80' WHERE title LIKE '%Solar Panel%'",
    "UPDATE programs SET image_url = 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=600&q=80' WHERE title LIKE '%Sekolah Hijau%'",
    "UPDATE programs SET image_url = 'https://images.unsplash.com/photo-1469122312224-c5846569feb1?auto=format&fit=crop&w=600&q=80' WHERE title LIKE '%Sungai Bersih%'"
];
foreach($updateQueries as $q) {
    $db->query($q);
}
echo "<p style='color:green;'>✅ Berhasil: Seluruh gambar program telah diperbarui di database.</p>";

// 4. Masukkan data dummy artikel jika masih kosong
echo "<h3>4. Menambahkan data artikel default...</h3>";
$res = $db->query("SELECT COUNT(*) as count FROM articles");
$count = $res->fetch_assoc()['count'];

if ($count == 0) {
    $query3 = "INSERT INTO articles (title, excerpt, content, image_url, author_id) VALUES
    ('Mengapa Gaya Hidup Zero Waste Bukan Sekadar Tren', 'Kita hidup di era di mana sampah plastik mencemari lautan...', 'Gaya hidup zero waste atau bebas sampah adalah filosofi yang mendorong desain ulang siklus hidup sumber daya sehingga semua produk dapat digunakan kembali. Tidak ada sampah yang dikirim ke tempat pembuangan akhir, insinerator, atau laut. Proses yang direkomendasikan ini mirip dengan cara sumber daya digunakan kembali di alam. Mulailah dari langkah kecil: bawa botol minum sendiri, gunakan tas belanja kain, dan pelajari cara mengompos sisa makananmu.', 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=600&q=80', 1),
    ('5 Produk Ramah Lingkungan Wajib Ada di Rumahmu', 'Pengganti plastik sekali pakai yang mudah ditemukan dan terjangkau...', 'Mengurangi plastik sekali pakai tidak harus mahal atau sulit. Berikut adalah 5 barang yang bisa kamu jadikan investasi jangka panjang: 1. Sikat gigi bambu (mudah terurai). 2. Loofah atau spons gambas untuk cuci piring (alternatif spons sintetis). 3. Tas belanja kanvas yang kuat. 4. Sabun mandi batangan (mengurangi botol plastik). 5. Wadah makanan kaca atau stainless (awet dan aman untuk makanan panas). Dengan mengubah barang-barang kecil ini, dampak yang diberikan bisa sangat besar.', 'https://images.unsplash.com/photo-1588688463994-38c23e85e51c?auto=format&fit=crop&w=600&q=80', 1),
    ('Kompos Sendiri di Rumah: Panduan Lengkap untuk Pemula', 'Ubah sisa makananmu menjadi pupuk subur dengan cara yang mudah dan tanpa biaya besar.', 'Lebih dari 50 persen sampah rumah tangga adalah sampah organik yang sebenarnya bisa dikomposkan. Jika dibuang ke TPA, sampah organik ini akan menghasilkan gas metana yang merupakan gas rumah kaca berbahaya. Cara membuat kompos sangat mudah: siapkan wadah berlubang, masukkan campuran tanah, sisa makanan (hijau), dan daun kering atau kertas (cokelat). Aduk setiap beberapa hari agar oksigen masuk. Dalam 1-2 bulan, kamu akan mendapatkan kompos yang sangat subur untuk tanamanmu.', 'https://images.unsplash.com/photo-1518531933037-91b2f5f229cc?auto=format&fit=crop&w=600&q=80', 1)";
    
    if ($db->query($query3)) {
        echo "<p style='color:green;'>✅ Berhasil: 3 artikel default berhasil ditambahkan.</p>";
    } else {
        echo "<p style='color:red;'>❌ Gagal: " . $db->error . "</p>";
    }
} else {
    echo "<p style='color:blue;'>ℹ️ Info: Data artikel sudah ada, melewati proses pengisian data default.</p>";
}

// 5. Buat tabel carbon_logs
echo "<h3>5. Membuat tabel carbon_logs...</h3>";
$query5 = "CREATE TABLE IF NOT EXISTS carbon_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  transport    DECIMAL(10,2) DEFAULT 0.00,
  electricity  DECIMAL(10,2) DEFAULT 0.00,
  food         DECIMAL(10,2) DEFAULT 0.00,
  shopping     DECIMAL(10,2) DEFAULT 0.00,
  total        DECIMAL(10,2) DEFAULT 0.00,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($query5)) {
    echo "<p style='color:green;'>✅ Berhasil: Tabel 'carbon_logs' berhasil dibuat atau sudah ada.</p>";
} else {
    echo "<p style='color:red;'>❌ Gagal: " . $db->error . "</p>";
}

echo "<hr><p><strong>Semua proses selesai!</strong> Silakan tutup halaman ini dan refresh aplikasi GreenSteps-mu.</p>";
echo "<a href='../greensteps-id.html' style='display:inline-block;padding:10px 20px;background:#3d7d1f;color:white;text-decoration:none;border-radius:5px;'>Kembali ke Aplikasi</a>";
?>
