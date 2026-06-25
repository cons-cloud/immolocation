<?php
session_start();
header('Content-Type: application/json');
include '../../includes/config.php';

// Access check
if (!isset($_SESSION['user_id']) || $_SESSION['type_compte'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$action = $_GET['action'] ?? '';

if ($action === 'user_status') {
    $user_id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($user_id > 0 && in_array($status, ['actif', 'suspendu', 'en_attente'])) {
        $stmt = mysqli_prepare($conn, "UPDATE utilisateurs SET statut = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
            exit();
        }
    }
} elseif ($action === 'listing_status') {
    $type = $_POST['type'] ?? ''; // 'bien' or 'voiture'
    $item_id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($item_id > 0 && in_array($status, ['actif', 'inactif', 'en_attente']) && ($type === 'bien' || $type === 'voiture')) {
        $table = ($type === 'bien') ? 'biens' : 'voitures';
        $stmt = mysqli_prepare($conn, "UPDATE $table SET statut = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $item_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
            exit();
        }
    }
} elseif ($action === 'booking_status') {
    $res_id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($res_id > 0 && in_array($status, ['en_attente', 'confirmee', 'annulee', 'terminee'])) {
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET statut = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $status, $res_id);
        if (mysqli_stmt_execute($stmt)) {
            // Also if cancelled, remove dates from disponibilites
            if ($status === 'annulee') {
                mysqli_query($conn, "DELETE FROM disponibilites WHERE raison='reservation' AND type_ressource=(SELECT type_reservation FROM reservations WHERE id=$res_id) AND ressource_id=IFNULL((SELECT bien_id FROM reservations WHERE id=$res_id), (SELECT voiture_id FROM reservations WHERE id=$res_id))");
            }
            echo json_encode(['success' => true]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Action invalide']);
exit();
?>
