<?php
// ============================================================
// articles.php — CRUD Artikel/Info (GET semua, POST tambah, PUT edit, DELETE hapus)
// ============================================================
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET /api/articles.php ───────────────────────
if ($method === 'GET') {
    $rows = $db->query("SELECT * FROM articles ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['author_id'] = (int)$r['author_id'];
    }
    jsonResponse(['success' => true, 'articles' => $rows]);
}

// Semua aksi tulis butuh login
if (empty($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Belum login.'], 401);
}
$uid = (int)$_SESSION['user_id'];

// Cek apakah admin
$stmtRole = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmtRole->bind_param('i', $uid);
$stmtRole->execute();
$userRow = $stmtRole->get_result()->fetch_assoc();
$isAdmin = $userRow && $userRow['role'] === 'admin';

// ── POST /api/articles.php ────────────────────────────────────
if ($method === 'POST') {
    if (!$isAdmin) jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    $d = getInput();
    $title   = trim($d['title']   ?? '');
    $excerpt = trim($d['excerpt'] ?? '');
    $content = trim($d['content'] ?? '');
    $image   = $d['image_url'] ?? $d['image'] ?? 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80';

    if (!$title || !$content) jsonResponse(['success' => false, 'message' => 'Judul dan isi wajib.'], 400);

    $stmt = $db->prepare("INSERT INTO articles (title, excerpt, content, image_url, author_id) VALUES (?,?,?,?,?)");
    $stmt->bind_param('ssssi', $title, $excerpt, $content, $image, $uid);
    $stmt->execute();
    $newId = $db->insert_id;

    $prog = ['id' => $newId, 'title' => $title, 'excerpt' => $excerpt, 'content' => $content, 'image_url' => $image, 'author_id' => $uid, 'created_at' => date('Y-m-d H:i:s')];
    jsonResponse(['success' => true, 'article' => $prog]);
}

// ── PUT /api/articles.php?id=X ────────────────────────────────
if ($method === 'PUT') {
    if (!$isAdmin) jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID tidak valid.'], 400);

    $d = getInput();
    $title   = trim($d['title']   ?? '');
    $excerpt = trim($d['excerpt'] ?? '');
    $content = trim($d['content'] ?? '');
    $image   = $d['image_url'] ?? $d['image'] ?? 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80';

    if (!$title || !$content) jsonResponse(['success' => false, 'message' => 'Judul dan isi wajib.'], 400);

    $stmt = $db->prepare("UPDATE articles SET title=?, excerpt=?, content=?, image_url=? WHERE id=?");
    $stmt->bind_param('ssssi', $title, $excerpt, $content, $image, $id);
    $stmt->execute();

    jsonResponse(['success' => true]);
}

// ── DELETE /api/articles.php?id=X ─────────────────────────────
if ($method === 'DELETE') {
    if (!$isAdmin) jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID tidak valid.'], 400);

    $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'message' => 'Method tidak dikenal.'], 405);
