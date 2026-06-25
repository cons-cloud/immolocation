<?php
session_start();
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../page/acceuil.php');
    exit();
}

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$date_debut = $_POST['date_debut'] ?? '';
$date_fin = $_POST['date_fin'] ?? '';
$nb_jours = (int)($_POST['nb_jours'] ?? 0);
$prix_total = (float)($_POST['prix_total'] ?? 0.0);

$client_prenom = trim($_POST['client_prenom'] ?? '');
$client_nom = trim($_POST['client_nom'] ?? '');
$client_email = filter_var(trim($_POST['client_email'] ?? ''), FILTER_SANITIZE_EMAIL);
$client_telephone = trim($_POST['client_telephone'] ?? '');
$message_client = trim($_POST['message_client'] ?? '');

if (($type !== 'bien' && $type !== 'voiture') || $id <= 0 || !$date_debut || !$date_fin || !$client_email) {
    header('Location: ../page/acceuil.php');
    exit();
}

// 1. Resolve client account ID
$client_id = 0;
if (isset($_SESSION['user_id'])) {
    $client_id = $_SESSION['user_id'];
} else {
    // Check if user exists by email
    $chk_q = mysqli_prepare($conn, "SELECT id FROM utilisateurs WHERE email = ?");
    mysqli_stmt_bind_param($chk_q, 's', $client_email);
    mysqli_stmt_execute($chk_q);
    $chk_res = mysqli_stmt_get_result($chk_q);
    $existing_user = mysqli_fetch_assoc($chk_res);

    if ($existing_user) {
        $client_id = $existing_user['id'];
    } else {
        // Auto-register guest client
        $pass_hash = password_hash('Guest123!', PASSWORD_BCRYPT);
        $ins_user = mysqli_prepare($conn, "INSERT INTO utilisateurs (prenom, nom, email, telephone, mot_de_passe, type_compte) VALUES (?, ?, ?, ?, ?, 'client')");
        mysqli_stmt_bind_param($ins_user, 'sssss', $client_prenom, $client_nom, $client_email, $client_telephone, $pass_hash);
        mysqli_stmt_execute($ins_user);
        $client_id = mysqli_insert_id($conn);
        
        // Log them in
        $_SESSION['user_id'] = $client_id;
        $_SESSION['prenom'] = $client_prenom;
        $_SESSION['nom'] = $client_nom;
        $_SESSION['email'] = $client_email;
        $_SESSION['type_compte'] = 'client';
    }
}

// 2. Generate unique reservation number: RES-XXXXXX
$num_reservation = '';
$unique = false;
while (!$unique) {
    $rand = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    $num_reservation = 'RES-' . $rand;
    $chk_res = mysqli_query($conn, "SELECT id FROM reservations WHERE numero_reservation = '$num_reservation'");
    if (mysqli_num_rows($chk_res) == 0) {
        $unique = true;
    }
}

// 3. Create reservation in database (status: 'en_attente')
$bien_id = ($type === 'bien') ? $id : null;
$voiture_id = ($type === 'voiture') ? $id : null;

$stmt = mysqli_prepare($conn, "
    INSERT INTO reservations (client_id, type_reservation, bien_id, voiture_id, date_debut, date_fin, nb_jours, prix_total, message_client, numero_reservation, statut)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
");
mysqli_stmt_bind_param($stmt, 'isiississs', $client_id, $type, $bien_id, $voiture_id, $date_debut, $date_fin, $nb_jours, $prix_total, $message_client, $num_reservation);

if (mysqli_stmt_execute($stmt)) {
    $reservation_id = mysqli_insert_id($conn);
    header("Location: ../page/paiement.php?id=$reservation_id");
} else {
    // Error redirect
    header("Location: ../page/acceuil.php?booking_error=1");
}
exit();
?>
