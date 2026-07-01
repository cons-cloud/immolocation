<?php
session_start();
header('Content-Type: application/json');
include '../../includes/config.php';

// Access check
if (!isset($_SESSION['user_id']) || $_SESSION['type_compte'] !== 'proprietaire') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'booking_status') {
    $res_id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? ''; // 'confirmee' or 'annulee'

    if ($res_id > 0 && in_array($status, ['confirmee', 'annulee'])) {
        // First verify that this booking belongs to an asset owned by this owner
        $check_q = mysqli_query($conn, "
            SELECT r.id, r.client_id, r.numero_reservation, r.type_reservation, r.bien_id, r.voiture_id, r.date_debut, r.date_fin,
                   IF(r.type_reservation='bien', b.titre, CONCAT(v.marque, ' ', v.modele)) as item_title
            FROM reservations r
            LEFT JOIN biens b ON r.bien_id = b.id AND r.type_reservation='bien'
            LEFT JOIN voitures v ON r.voiture_id = v.id AND r.type_reservation='voiture'
            WHERE r.id = $res_id AND (b.proprietaire_id = $user_id OR v.proprietaire_id = $user_id)
        ");
        $booking = mysqli_fetch_assoc($check_q);
        
        if ($booking) {
            $stmt = mysqli_prepare($conn, "UPDATE reservations SET statut = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $res_id);
            if (mysqli_stmt_execute($stmt)) {
                // If cancelled, remove dates from disponibilites
                if ($status === 'annulee') {
                    $target_id = ($booking['type_reservation'] === 'bien') ? $booking['bien_id'] : $booking['voiture_id'];
                    $del_disp = mysqli_prepare($conn, "
                        DELETE FROM disponibilites 
                        WHERE raison='reservation' 
                          AND type_ressource = ? 
                          AND ressource_id = ? 
                          AND date_debut = ? 
                          AND date_fin = ?
                    ");
                    mysqli_stmt_bind_param($del_disp, 'siss', $booking['type_reservation'], $target_id, $booking['date_debut'], $booking['date_fin']);
                    mysqli_stmt_execute($del_disp);
                }
                
                // Notify client
                $notif_title = ($status === 'confirmee') ? "Réservation acceptée" : "Réservation déclinée";
                $notif_msg = "Votre réservation " . $booking['numero_reservation'] . " pour \"" . $booking['item_title'] . "\" a été " . ($status === 'confirmee' ? 'acceptée par le propriétaire' : 'déclinée') . ".";
                $notif_type = ($status === 'confirmee') ? 'reservation' : 'alerte';
                
                $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($notif_stmt, 'isss', $booking['client_id'], $notif_title, $notif_msg, $notif_type);
                mysqli_stmt_execute($notif_stmt);

                echo json_encode(['success' => true]);
                exit();
            }
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Action non supportée']);
exit();
?>
