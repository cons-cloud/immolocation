<?php
session_start();
header('Content-Type: application/json');
include '../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['redirect' => '../page/connexion.php']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? ''; // 'bien' or 'voiture'
$id = (int)($_GET['id'] ?? 0);

if ($action !== 'toggle' || ($type !== 'bien' && $type !== 'voiture') || $id <= 0) {
    echo json_encode(['error' => 'Paramètres invalides']);
    exit();
}

// Check if favorite exists
$bien_id = ($type === 'bien') ? $id : null;
$voiture_id = ($type === 'voiture') ? $id : null;

if ($type === 'bien') {
    $q = mysqli_query($conn, "SELECT id FROM favoris WHERE utilisateur_id = $user_id AND type_favori = 'bien' AND bien_id = $id");
} else {
    $q = mysqli_query($conn, "SELECT id FROM favoris WHERE utilisateur_id = $user_id AND type_favori = 'voiture' AND voiture_id = $id");
}

if (mysqli_num_rows($q) > 0) {
    // Delete
    $row = mysqli_fetch_assoc($q);
    mysqli_query($conn, "DELETE FROM favoris WHERE id = {$row['id']}");
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    // Add
    if ($type === 'bien') {
        $stmt = mysqli_prepare($conn, "INSERT INTO favoris (utilisateur_id, type_favori, bien_id, voiture_id) VALUES (?, 'bien', ?, NULL)");
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $id);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO favoris (utilisateur_id, type_favori, bien_id, voiture_id) VALUES (?, 'voiture', NULL, ?)");
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $id);
    }
    mysqli_stmt_execute($stmt);
    echo json_encode(['success' => true, 'action' => 'added']);
}
exit();
?>
