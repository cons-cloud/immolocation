<?php
header('Content-Type: application/json');
include '../../includes/config.php';

$type = $_GET['type'] ?? 'bien'; // 'bien' or 'voiture'
$limit = (int)($_GET['limit'] ?? 10);
$ville = $_GET['ville'] ?? '';

$response = [];

if ($type === 'bien') {
    $where = "WHERE statut='actif' AND disponible=1";
    if (!empty($ville)) {
        $where .= " AND ville LIKE '%" . mysqli_real_escape_string($conn, $ville) . "%'";
    }
    
    $q = mysqli_query($conn, "SELECT id, titre, description, type_bien, ville, prix_nuit, surface, nb_chambres, note_moyenne, image_principale FROM biens $where ORDER BY note_moyenne DESC LIMIT $limit");
    while ($r = mysqli_fetch_assoc($q)) {
        $response[] = $r;
    }
} else {
    $where = "WHERE statut='actif' AND disponible=1";
    if (!empty($ville)) {
        $where .= " AND ville LIKE '%" . mysqli_real_escape_string($conn, $ville) . "%'";
    }
    
    $q = mysqli_query($conn, "SELECT id, marque, modele, annee, carburant, boite, prix_jour, note_moyenne, image_principale FROM voitures $where ORDER BY note_moyenne DESC LIMIT $limit");
    while ($r = mysqli_fetch_assoc($q)) {
        $response[] = $r;
    }
}

echo json_encode($response);
exit();
?>
