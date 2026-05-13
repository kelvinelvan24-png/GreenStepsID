<?php
// ============================================================
// users.php — Edit profil, Hapus akun, Leaderboard
// ============================================================
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = getDB();

// ── GET /api/users.php?action=leaderboard ────────────────────
if ($method === 'GET' && $action === 'leaderboard') {
    $rows = $db->query("SELECT id, name, city, role, xp FROM users ORDER BY xp DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['xp'] = (int)$r['xp'];
    }
    jsonResponse(['success' => true, 'users' => $rows]);
}

// Semua aksi lain butuh login
if (empty($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Belum login.'], 401);
}
$uid = (int)$_SESSION['user_id'];

// ── POST /api/users.php?action=redeem_reward ─────────────────
if ($method === 'POST' && $action === 'redeem_reward') {
    $d = getInput();
    $rewardName = trim($d['reward_name'] ?? '');
    $cost = (int)($d['cost'] ?? 0);

    if (!$rewardName || $cost <= 0) {
        jsonResponse(['success' => false, 'message' => 'Data reward tidak valid.'], 400);
    }

    $xpRow = $db->query("SELECT xp FROM users WHERE id = $uid")->fetch_assoc();
    $currentXp = (int)$xpRow['xp'];

    if ($currentXp < $cost) {
        jsonResponse(['success' => false, 'message' => 'XP tidak cukup.'], 400);
    }

    $db->query("UPDATE users SET xp = xp - $cost WHERE id = $uid");

    $reason = 'Tukar Reward: ' . $rewardName;
    $amount = -$cost;
    $logStmt = $db->prepare("INSERT INTO rep_log (user_id, amount, reason) VALUES (?, ?, ?)");
    $logStmt->bind_param('iis', $uid, $amount, $reason);
    $logStmt->execute();

    jsonResponse(['success' => true, 'message' => 'Reward berhasil ditukar.', 'xp' => $currentXp - $cost]);
}

// ── PUT /api/users.php — Edit profil ─────────────────────────
if ($method === 'PUT') {
    $d    = getInput();
    $name = trim($d['name'] ?? '');
    $city = trim($d['city'] ?? '-');
    $pass = $d['password'] ?? '';

    if (!$name) {
        jsonResponse(['success' => false, 'message' => 'Nama tidak boleh kosong.'], 400);
    }

    if ($pass !== '') {
        if (strlen($pass) < 6) {
            jsonResponse(['success' => false, 'message' => 'Password minimal 6 karakter.'], 400);
        }
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET name = ?, city = ?, password = ? WHERE id = ?");
        $stmt->bind_param('sssi', $name, $city, $hash, $uid);
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, city = ? WHERE id = ?");
        $stmt->bind_param('ssi', $name, $city, $uid);
    }
    $stmt->execute();

    // Ambil data terbaru
    $uStmt = $db->prepare("SELECT id, name, email, city, role, xp FROM users WHERE id = ?");
    $uStmt->bind_param('i', $uid);
    $uStmt->execute();
    $user = $uStmt->get_result()->fetch_assoc();
    $user['id'] = (int)$user['id'];
    $user['xp'] = (int)$user['xp'];

    jsonResponse(['success' => true, 'user' => $user]);
}

// ── DELETE /api/users.php — Hapus akun sendiri ───────────────
if ($method === 'DELETE') {
    // Cek bukan admin (admin tidak boleh hapus diri sendiri)
    $roleStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $roleStmt->bind_param('i', $uid);
    $roleStmt->execute();
    $row = $roleStmt->get_result()->fetch_assoc();
    if ($row && $row['role'] === 'admin') {
        jsonResponse(['success' => false, 'message' => 'Admin tidak bisa menghapus akun ini.'], 403);
    }

    // Hapus user (CASCADE hapus user_programs & rep_log)
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();

    session_destroy();
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'message' => 'Method tidak dikenal.'], 405);
