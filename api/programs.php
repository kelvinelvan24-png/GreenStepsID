<?php
// ============================================================
// programs.php — CRUD Program (GET semua, POST tambah, PUT edit, DELETE hapus)
// ============================================================
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET /api/programs.php?status=aktif ───────────────────────
if ($method === 'GET') {
    $status = $_GET['status'] ?? 'all';
    if ($status !== 'all') {
        $stmt = $db->prepare("SELECT * FROM programs WHERE status = ? ORDER BY id DESC");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $rows = $db->query("SELECT * FROM programs ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    }
    // cast tipe data
    foreach ($rows as &$r) {
        $r['id']           = (int)$r['id'];
        $r['target']       = (int)$r['target'];
        $r['participants'] = (int)$r['participants'];
    }
    jsonResponse(['success' => true, 'programs' => $rows]);
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

// ── POST /api/programs.php ────────────────────────────────────
if ($method === 'POST') {
    if (!$isAdmin) jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    $d = getInput();
    $title  = trim($d['title']  ?? '');
    $desc   = trim($d['description'] ?? $d['desc'] ?? '');
    $cat    = trim($d['category']    ?? $d['cat']  ?? 'Umum');
    $status = $d['status']   ?? 'aktif';
    $image  = $d['image_url'] ?? $d['image'] ?? 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80';
    $target = (int)($d['target'] ?? 100);
    $date   = $d['date']     ?? date('Y-m-d');

    if (!$title || !$desc) jsonResponse(['success' => false, 'message' => 'Judul dan deskripsi wajib.'], 400);

    $stmt = $db->prepare("INSERT INTO programs (title, description, category, status, image_url, target, participants, start_date) VALUES (?,?,?,?,?,?,0,?)");
    $stmt->bind_param('sssssis', $title, $desc, $cat, $status, $image, $target, $date);
    $stmt->execute();
    $newId = $db->insert_id;

    $prog = ['id' => $newId, 'title' => $title, 'description' => $desc, 'category' => $cat, 'status' => $status, 'image_url' => $image, 'target' => $target, 'participants' => 0, 'start_date' => $date];
    jsonResponse(['success' => true, 'program' => $prog]);
}

// ── PUT /api/programs.php?id=X ────────────────────────────────
if ($method === 'PUT') {
    if (!$isAdmin) jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID tidak valid.'], 400);

    $d = getInput();
    $title  = trim($d['title']       ?? '');
    $desc   = trim($d['description'] ?? $d['desc'] ?? '');
    $cat    = trim($d['category']    ?? $d['cat']  ?? 'Umum');
    $status = $d['status'] ?? 'aktif';
    $image  = $d['image_url'] ?? $d['image'] ?? 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=600&q=80';
    $target = (int)($d['target'] ?? 100);
    $date   = $d['date']   ?? date('Y-m-d');

    if (!$title || !$desc) jsonResponse(['success' => false, 'message' => 'Judul dan deskripsi wajib.'], 400);

    $stmt = $db->prepare("UPDATE programs SET title=?, description=?, category=?, status=?, image_url=?, target=?, start_date=? WHERE id=?");
    $stmt->bind_param('sssssisi', $title, $desc, $cat, $status, $image, $target, $date, $id);
    $stmt->execute();

    if ($stmt->affected_rows < 0) jsonResponse(['success' => false, 'message' => 'Program tidak ditemukan.'], 404);
    jsonResponse(['success' => true]);
}

// ── DELETE /api/programs.php?id=X ─────────────────────────────
if ($method === 'DELETE') {
    if (!$isAdmin) jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'ID tidak valid.'], 400);

    // user_programs akan terhapus otomatis (CASCADE)
    $stmt = $db->prepare("DELETE FROM programs WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'message' => 'Method tidak dikenal.'], 405);
