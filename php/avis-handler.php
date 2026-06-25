<?php
session_start();
include '../includes/config.php';

$success = false;
$error = '';

if (!isset($_SESSION['user_id'])) {
    $error = 'Vous devez être connecté pour laisser un avis.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auteur_id = $_SESSION['user_id'];
    $type_cible = $_POST['type_cible'] ?? ''; // 'bien' or 'voiture'
    $bien_id = isset($_POST['bien_id']) && $_POST['bien_id'] !== '' ? (int)$_POST['bien_id'] : null;
    $voiture_id = isset($_POST['voiture_id']) && $_POST['voiture_id'] !== '' ? (int)$_POST['voiture_id'] : null;
    $note = (int)($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($type_cible !== 'bien' && $type_cible !== 'voiture') {
        $error = 'Type de cible invalide.';
    } elseif ($type_cible === 'bien' && !$bien_id) {
        $error = 'Bien immobilier non spécifié.';
    } elseif ($type_cible === 'voiture' && !$voiture_id) {
        $error = 'Voiture non spécifiée.';
    } elseif ($note < 1 || $note > 5) {
        $error = 'La note doit être comprise entre 1 et 5.';
    } elseif (empty($commentaire)) {
        $error = 'Le commentaire ne peut pas être vide.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO avis (auteur_id, type_cible, bien_id, voiture_id, note, commentaire) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issiis', $auteur_id, $type_cible, $bien_id, $voiture_id, $note, $commentaire);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            
            // Recalculate average note and total count for target
            $target_id = ($type_cible === 'bien') ? $bien_id : $voiture_id;
            $table = ($type_cible === 'bien') ? 'biens' : 'voitures';
            $id_col = ($type_cible === 'bien') ? 'bien_id' : 'voiture_id';

            $stats_q = mysqli_query($conn, "SELECT COUNT(*) as cnt, AVG(note) as avg_rating FROM avis WHERE type_cible = '$type_cible' AND $id_col = $target_id AND statut = 'actif'");
            $stats = mysqli_fetch_assoc($stats_q);
            $cnt = $stats['cnt'] ?? 0;
            $avg_rating = round($stats['avg_rating'] ?? 0, 2);

            mysqli_query($conn, "UPDATE $table SET note_moyenne = $avg_rating, nb_avis = $cnt WHERE id = $target_id");

            // Notify owner
            $owner_q = mysqli_query($conn, "SELECT proprietaire_id, " . ($type_cible === 'bien' ? 'titre as title' : "CONCAT(marque, ' ', modele) as title") . " FROM $table WHERE id = $target_id");
            $owner_data = mysqli_fetch_assoc($owner_q);
            if ($owner_data) {
                $owner_id = $owner_data['proprietaire_id'];
                $item_title = $owner_data['title'];
                $notif_title = "Nouvel avis reçu";
                $notif_msg = "Un client a laissé un avis (" . $note . "/5) sur \"" . $item_title . "\".";
                $notif_type = "avis";
                
                $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($notif_stmt, 'isss', $owner_id, $notif_title, $notif_msg, $notif_type);
                mysqli_stmt_execute($notif_stmt);
            }
        } else {
            $error = 'Erreur lors de l\'enregistrement de votre avis.';
        }
    }
}

if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Merci ! Votre avis a été enregistré.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $error ?: 'Erreur de soumission']);
    }
    exit();
} else {
    $redirect = $_SERVER['HTTP_REFERER'] ?? '../page/acceuil.php';
    if ($success) {
        header("Location: $redirect?success_avis=1");
    } else {
        header("Location: $redirect?error_avis=" . urlencode($error));
    }
    exit();
}
?>
