<?php
session_start();
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../page/acceuil.php');
    exit();
}

$res_id = (int)($_POST['reservation_id'] ?? 0);
$montant = (float)($_POST['montant'] ?? 0.0);
$card_number = trim($_POST['card_number'] ?? '');
$last_4 = substr(str_replace(' ', '', $card_number), -4);

if ($res_id <= 0) {
    header('Location: ../page/acceuil.php');
    exit();
}

// 1. Verify reservation status
$query = mysqli_prepare($conn, "SELECT * FROM reservations WHERE id = ? AND statut = 'en_attente'");
mysqli_stmt_bind_param($query, 'i', $res_id);
mysqli_stmt_execute($query);
$res = mysqli_fetch_assoc(mysqli_stmt_get_result($query));

if (!$res) {
    header('Location: ../page/acceuil.php');
    exit();
}

// 2. Generate transaction reference: TXN-XXXXXX
$txn_ref = 'TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

// 3. Insert payment
$ins_pay = mysqli_prepare($conn, "
    INSERT INTO paiements (reservation_id, montant, methode, statut, reference, derniers_4)
    VALUES (?, ?, 'carte', 'valide', ?, ?)
");
mysqli_stmt_bind_param($ins_pay, 'idss', $res_id, $montant, $txn_ref, $last_4);
mysqli_stmt_execute($ins_pay);

// 4. Update reservation status
mysqli_query($conn, "UPDATE reservations SET statut = 'confirmee' WHERE id = $res_id");

// 5. Block dates in disponibilites
$type_res = $res['type_reservation'];
$target_id = ($type_res === 'bien') ? $res['bien_id'] : $res['voiture_id'];
$date_debut = $res['date_debut'];
$date_fin = $res['date_fin'];

$ins_disp = mysqli_prepare($conn, "
    INSERT INTO disponibilites (type_ressource, ressource_id, date_debut, date_fin, raison)
    VALUES (?, ?, ?, ?, 'reservation')
");
mysqli_stmt_bind_param($ins_disp, 'siss', $type_res, $target_id, $date_debut, $date_fin);
mysqli_stmt_execute($ins_disp);

// 6. Notify Client
$client_id = $res['client_id'];
$notif_title = "Paiement validé";
$notif_msg = "Le paiement de votre réservation " . $res['numero_reservation'] . " a été validé.";
$notif_type = "paiement";

$notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($notif_stmt, 'isss', $client_id, $notif_title, $notif_msg, $notif_type);
mysqli_stmt_execute($notif_stmt);

// 7. Notify Owner
$owner_id = 0;
$item_title = '';
if ($type_res === 'bien') {
    $item_q = mysqli_query($conn, "SELECT titre, proprietaire_id FROM biens WHERE id = $target_id");
    $item = mysqli_fetch_assoc($item_q);
    $owner_id = $item['proprietaire_id'] ?? 0;
    $item_title = $item['titre'] ?? 'Hébergement';
} else {
    $item_q = mysqli_query($conn, "SELECT CONCAT(marque, ' ', modele) as title, proprietaire_id FROM voitures WHERE id = $target_id");
    $item = mysqli_fetch_assoc($item_q);
    $owner_id = $item['proprietaire_id'] ?? 0;
    $item_title = $item['title'] ?? 'Véhicule';
}

if ($owner_id > 0) {
    $owner_title = "Nouvelle réservation confirmée";
    $owner_msg = "Votre annonce \"" . $item_title . "\" a été réservée du " . date('d/m/Y', strtotime($date_debut)) . " au " . date('d/m/Y', strtotime($date_fin)) . ".";
    $owner_type = "reservation";
    
    $notif_owner = mysqli_prepare($conn, "INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($notif_owner, 'isss', $owner_id, $owner_title, $owner_msg, $owner_type);
    mysqli_stmt_execute($notif_owner);
}

// Redirect to confirmation
header("Location: ../page/confirmation.php?id=$res_id");
exit();
?>
