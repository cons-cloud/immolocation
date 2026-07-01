<?php
session_start();
header('Content-Type: application/json');

// Authorization check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_compte'], ['proprietaire', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['image']['error'] ?? 'inconnu';
    echo json_encode(['success' => false, 'error' => 'Erreur de téléversement (Code: ' . $errCode . ')']);
    exit();
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Format de fichier non supporté. Autorisé: JPG, PNG, WEBP']);
    exit();
}

// Validate size
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'Le fichier est trop volumineux. Taille max: 5 Mo']);
    exit();
}

// Ensure directory exists
$targetDir = '../../image/uploads/';
if (!file_exists($targetDir)) {
    if (!mkdir($targetDir, 0777, true)) {
        echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier de destination']);
        exit();
    }
}

// Generate unique file name
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($ext)) {
    $ext = ($mimeType === 'image/png') ? 'png' : (($mimeType === 'image/webp') ? 'webp' : 'jpg');
}
$newFileName = 'img_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . strtolower($ext);
$targetFile = $targetDir . $newFileName;

if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    // Relative path for database storing
    $dbPath = '../image/uploads/' . $newFileName;
    echo json_encode(['success' => true, 'path' => $dbPath]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors du déplacement du fichier']);
}
exit();
?>
