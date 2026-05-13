<?php
// ============================================================
// user_programs.php — Join / Unjoin program + sistem XP
// ============================================================
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Belum login.'], 401);
}

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action']     ?? '';
$programId  = (int)($_GET['program_id'] ?? 0);
$uid        = (int)$_SESSION['user_id'];
$db         = getDB();

if (!$programId) {
    jsonResponse(['success' => false, 'message' => 'ID Program diperlukan.'], 400);
}

// ── MENDAPATKAN PESERTA (Admin Only) ─────────────────────────
if ($method === 'GET' && $action === 'participants') {
    // Cek admin
    $stmtRole = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->bind_param('i', $uid);
    $stmtRole->execute();
    $roleRow = $stmtRole->get_result()->fetch_assoc();
    if (!$roleRow || $roleRow['role'] !== 'admin') {
        jsonResponse(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }

    $pStmt = $db->prepare("
        SELECT u.id, u.name, u.email 
        FROM users u 
        JOIN user_programs up ON u.id = up.user_id 
        WHERE up.program_id = ?
        ORDER BY u.name ASC
    ");
    $pStmt->bind_param('i', $programId);
    $pStmt->execute();
    $participants = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    jsonResponse(['success' => true, 'participants' => $participants]);
}

if ($method !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Request tidak valid.'], 400);
}

// Cek program ada & aktif
$pStmt = $db->prepare("SELECT id, title, status FROM programs WHERE id = ?");
$pStmt->bind_param('i', $programId);
$pStmt->execute();
$prog = $pStmt->get_result()->fetch_assoc();

if (!$prog) {
    jsonResponse(['success' => false, 'message' => 'Program tidak ditemukan.'], 404);
}

// ── JOIN ─────────────────────────────────────────────────────
if ($action === 'join') {
    if ($prog['status'] !== 'aktif') {
        jsonResponse(['success' => false, 'message' => 'Program tidak aktif.'], 400);
    }

    // Cek sudah join?
    $cStmt = $db->prepare("SELECT 1 FROM user_programs WHERE user_id = ? AND program_id = ?");
    $cStmt->bind_param('ii', $uid, $programId);
    $cStmt->execute();
    if ($cStmt->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Sudah bergabung.'], 409);
    }

    // Insert join with IGNORE to prevent race conditions
    $ins = $db->prepare("INSERT IGNORE INTO user_programs (user_id, program_id) VALUES (?, ?)");
    $ins->bind_param('ii', $uid, $programId);
    $ins->execute();
    
    // Jika tidak ada baris yang terpengaruh, berarti sudah join (race condition)
    if ($ins->affected_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Sudah bergabung.'], 409);
    }

    // Tambah participants
    $upd = $db->prepare("UPDATE programs SET participants = participants + 1 WHERE id = ?");
    $upd->bind_param('i', $programId);
    $upd->execute();

    // XP +30
    $xpAmount = 30;
    $reason   = 'Bergabung ke: ' . $prog['title'];
    $db->query("UPDATE users SET xp = xp + $xpAmount WHERE id = $uid");
    $logStmt = $db->prepare("INSERT INTO rep_log (user_id, amount, reason) VALUES (?, ?, ?)");
    $logStmt->bind_param('iis', $uid, $xpAmount, $reason);
    $logStmt->execute();

    // Ambil XP terbaru
    $xpRow = $db->query("SELECT xp FROM users WHERE id = $uid")->fetch_assoc();
    jsonResponse(['success' => true, 'xp' => (int)$xpRow['xp'], 'xp_gained' => $xpAmount]);
}

// ── UNJOIN ────────────────────────────────────────────────────
if ($action === 'unjoin') {
    // Cek memang join?
    $cStmt = $db->prepare("SELECT 1 FROM user_programs WHERE user_id = ? AND program_id = ?");
    $cStmt->bind_param('ii', $uid, $programId);
    $cStmt->execute();
    if ($cStmt->get_result()->num_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Belum bergabung.'], 400);
    }

    // Hapus join
    $del = $db->prepare("DELETE FROM user_programs WHERE user_id = ? AND program_id = ?");
    $del->bind_param('ii', $uid, $programId);
    $del->execute();

    if ($del->affected_rows === 0) {
        jsonResponse(['success' => false, 'message' => 'Belum bergabung.'], 400);
    }

    // Kurangi participants (min 0)
    $db->query("UPDATE programs SET participants = GREATEST(0, participants - 1) WHERE id = $programId");

    // XP -20
    $xpAmount = -20;
    $reason   = 'Keluar dari: ' . $prog['title'];
    $db->query("UPDATE users SET xp = GREATEST(0, xp + ($xpAmount)) WHERE id = $uid");
    $logStmt = $db->prepare("INSERT INTO rep_log (user_id, amount, reason) VALUES (?, ?, ?)");
    $logStmt->bind_param('iis', $uid, $xpAmount, $reason);
    $logStmt->execute();

    $xpRow = $db->query("SELECT xp FROM users WHERE id = $uid")->fetch_assoc();
    jsonResponse(['success' => true, 'xp' => (int)$xpRow['xp'], 'xp_lost' => abs($xpAmount)]);
}

jsonResponse(['success' => false, 'message' => 'Action tidak dikenal.'], 400);
