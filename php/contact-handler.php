<?php
include '../includes/config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$nom || !$email || !$message) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO messages (nom, email, sujet, message) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssss', $nom, $email, $sujet, $message);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = 'Erreur technique lors de l\'envoi du message.';
        }
    }
}

if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Votre message a été envoyé avec succès !']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $error ?: 'Requête invalide']);
    }
    exit();
} else {
    if ($success) {
        header('Location: ../page/contact.php?success=1');
    } else {
        header('Location: ../page/contact.php?error=' . urlencode($error));
    }
    exit();
}
?>
