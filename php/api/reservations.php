<?php
header('Content-Type: application/json');
include '../../includes/config.php';

$type = $_GET['type'] ?? ''; // 'bien' or 'voiture'
$id = (int)($_GET['id'] ?? 0);

if (($type !== 'bien' && $type !== 'voiture') || $id <= 0) {
    echo json_encode(['error' => 'Paramètres invalides']);
    exit();
}

$ranges = [];
$stmt = mysqli_prepare($conn, "SELECT date_debut, date_fin FROM disponibilites WHERE type_ressource = ? AND ressource_id = ? AND date_fin >= CURDATE()");
mysqli_stmt_bind_param($stmt, 'si', $type, $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($res)) {
    $ranges[] = [
        'from' => $row['date_debut'],
        'to'   => $row['date_fin']
    ];
}

echo json_encode(['occupied_ranges' => $ranges]);
exit();
?>
