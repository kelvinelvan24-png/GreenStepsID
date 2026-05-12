<?php
// ============================================================
// carbon.php — API untuk Carbon Calculator
// ============================================================
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

if ($method === 'GET') {
    // Ambil riwayat kalkulasi user (harus login)
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $user_id = (int)$_SESSION['user_id'];
    $stmt = $db->prepare("SELECT * FROM carbon_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $logs = [];
    while ($row = $res->fetch_assoc()) {
        $logs[] = $row;
    }
    jsonResponse(['success' => true, 'logs' => $logs]);

} elseif ($method === 'POST') {
    // Simpan hasil kalkulasi (harus login)
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Silakan masuk untuk menghitung dan menyimpan emisi karbon.'], 401);
    }

    $input = getInput();
    $user_id = (int)$_SESSION['user_id'];
    $trans = (float)($input['transport'] ?? 0);
    $elec  = (float)($input['electricity'] ?? 0);
    $food  = (float)($input['food'] ?? 0);
    $shop  = (float)($input['shopping'] ?? 0);
    $total = $trans + $elec + $food + $shop;

    $stmt = $db->prepare("INSERT INTO carbon_logs (user_id, transport, electricity, food, shopping, total) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iddddd", $user_id, $trans, $elec, $food, $shop, $total);
    
    if ($stmt->execute()) {
        // Tambah XP (+10) untuk kalkulasi
        $xp_gained = 10;
        $db->query("UPDATE users SET xp = xp + $xp_gained WHERE id = $user_id");
        
        // Catat di rep_log
        $stmt_log = $db->prepare("INSERT INTO rep_log (user_id, amount, reason) VALUES (?, ?, 'Menghitung emisi karbon')");
        $stmt_log->bind_param("ii", $user_id, $xp_gained);
        $stmt_log->execute();

        // Ambil XP terbaru
        $res = $db->query("SELECT xp FROM users WHERE id = $user_id");
        $new_xp = $res->fetch_assoc()['xp'];
        $_SESSION['user_xp'] = $new_xp;

        jsonResponse([
            'success' => true, 
            'message' => 'Hasil disimpan', 
            'total' => $total,
            'xp_gained' => $xp_gained,
            'new_xp' => $new_xp
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Gagal menyimpan data']);
    }
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
