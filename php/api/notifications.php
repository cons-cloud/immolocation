<?php
session_start();
header('Content-Type: application/json');
include '../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $q = mysqli_query($conn, "SELECT id, titre, message, type, lue, DATE_FORMAT(date_creation, '%d/%m/%Y %H:%i') as date_creation FROM notifications WHERE utilisateur_id = $user_id ORDER BY date_creation DESC LIMIT 20");
    $notifications = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $r['lue'] = (bool)$r['lue'];
        $notifications[] = $r;
    }
    echo json_encode(['notifications' => $notifications]);
    exit();
} elseif ($action === 'read') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET lue = 1 WHERE id = ? AND utilisateur_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
        mysqli_stmt_execute($stmt);
        echo json_encode(['success' => true]);
        exit();
    }
}

echo json_encode(['error' => 'Action non supportée']);
exit();
?>
