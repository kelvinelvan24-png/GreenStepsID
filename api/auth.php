<?php
// ============================================================
// auth.php — Login, Register, Logout, Get Current User
// ============================================================
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── GET /api/auth.php?action=me ──────────────────────────────
if ($action === 'me' && $method === 'GET') {
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'user' => null]);
    }
    $db   = getDB();
    $uid  = (int)$_SESSION['user_id'];
    $stmt = $db->prepare("SELECT id, name, email, city, role, xp, created_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        session_destroy();
        jsonResponse(['success' => false, 'user' => null]);
    }
    // ambil joined program ids
    $stmt2 = $db->prepare("SELECT program_id FROM user_programs WHERE user_id = ?");
    $stmt2->bind_param('i', $uid);
    $stmt2->execute();
    $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $user['joined'] = array_column($rows, 'program_id');
    jsonResponse(['success' => true, 'user' => $user]);
}

// ── POST /api/auth.php?action=login ──────────────────────────
if ($action === 'login' && $method === 'POST') {
    $data  = getInput();
    $email = trim($data['email'] ?? '');
    $pass  = $data['password'] ?? '';

    if (!$email || !$pass) {
        jsonResponse(['success' => false, 'message' => 'Email dan password wajib diisi.'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT id, name, email, city, role, xp, password FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($pass, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Email atau password salah.'], 401);
    }

    $_SESSION['user_id'] = $user['id'];
    unset($user['password']);

    // ambil joined
    $stmt2 = $db->prepare("SELECT program_id FROM user_programs WHERE user_id = ?");
    $stmt2->bind_param('i', $user['id']);
    $stmt2->execute();
    $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $user['joined'] = array_column($rows, 'program_id');

    jsonResponse(['success' => true, 'user' => $user]);
}

// ── POST /api/auth.php?action=register ───────────────────────
if ($action === 'register' && $method === 'POST') {
    $data  = getInput();
    $name  = trim($data['name']  ?? '');
    $email = trim($data['email'] ?? '');
    $city  = trim($data['city']  ?? '-');
    $pass  = $data['password']   ?? '';

    if (!$name || !$email || !$pass) {
        jsonResponse(['success' => false, 'message' => 'Nama, email, dan password wajib diisi.'], 400);
    }
    if (strlen($pass) < 6) {
        jsonResponse(['success' => false, 'message' => 'Password minimal 6 karakter.'], 400);
    }

    $db   = getDB();
    $chk  = $db->prepare("SELECT id FROM users WHERE email = ?");
    $chk->bind_param('s', $email);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        jsonResponse(['success' => false, 'message' => 'Email sudah terdaftar.'], 409);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $ins  = $db->prepare("INSERT INTO users (name, email, password, city, role, xp) VALUES (?, ?, ?, ?, 'member', 0)");
    $ins->bind_param('ssss', $name, $email, $hash, $city);
    $ins->execute();
    $newId = $db->insert_id;

    $_SESSION['user_id'] = $newId;
    $user = ['id' => $newId, 'name' => $name, 'email' => $email, 'city' => $city, 'role' => 'member', 'xp' => 0, 'joined' => []];
    jsonResponse(['success' => true, 'user' => $user]);
}

// ── POST /api/auth.php?action=logout ─────────────────────────
if ($action === 'logout' && $method === 'POST') {
    session_destroy();
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'message' => 'Action tidak dikenal.'], 400);
