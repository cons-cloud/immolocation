<?php
session_start();
header('Content-Type: application/json');
include '../../includes/config.php';

// Authorization check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['type_compte'], ['proprietaire', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['type_compte'] === 'admin');
$action = $_GET['action'] ?? '';

if ($action === 'get') {
    $type = $_GET['type'] ?? '';
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0 || !in_array($type, ['bien', 'voiture'])) {
        echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
        exit();
    }

    $table = ($type === 'bien') ? 'biens' : 'voitures';
    $q = mysqli_query($conn, "SELECT * FROM $table WHERE id = $id");
    $item = mysqli_fetch_assoc($q);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Annonce introuvable']);
        exit();
    }

    // Owner check
    if (!$is_admin && (int)$item['proprietaire_id'] !== $user_id) {
        echo json_encode(['success' => false, 'error' => 'Non autorisé à consulter cette annonce']);
        exit();
    }

    echo json_encode(['success' => true, 'data' => $item]);
    exit();
}

if ($action === 'create' || $action === 'update') {
    $type = $_POST['type'] ?? ''; // 'bien' or 'voiture'
    if (!in_array($type, ['bien', 'voiture'])) {
        echo json_encode(['success' => false, 'error' => 'Type d\'annonce invalide']);
        exit();
    }

    $id = (int)($_POST['id'] ?? 0);
    $table = ($type === 'bien') ? 'biens' : 'voitures';

    // If update, check owner permission
    if ($action === 'update') {
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID manquant pour la modification']);
            exit();
        }
        $check_q = mysqli_query($conn, "SELECT proprietaire_id FROM $table WHERE id = $id");
        $check = mysqli_fetch_assoc($check_q);
        if (!$check) {
            echo json_encode(['success' => false, 'error' => 'Annonce introuvable']);
            exit();
        }
        if (!$is_admin && (int)$check['proprietaire_id'] !== $user_id) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé à modifier cette annonce']);
            exit();
        }
    }

    // Determine owner ID (Admin can assign another owner, proprietor defaults to self)
    $owner_id = $user_id;
    if ($is_admin && isset($_POST['proprietaire_id'])) {
        $owner_id = (int)$_POST['proprietaire_id'];
    }

    if ($type === 'bien') {
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type_bien = $_POST['type_bien'] ?? 'appartement';
        $adresse = trim($_POST['adresse'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $prix_nuit = (float)($_POST['prix_nuit'] ?? 0);
        $surface = (int)($_POST['surface'] ?? 0);
        $nb_chambres = (int)($_POST['nb_chambres'] ?? 1);
        $nb_salles_bain = (int)($_POST['nb_salles_bain'] ?? 1);
        $nb_personnes = (int)($_POST['nb_personnes'] ?? 2);
        
        $wifi = isset($_POST['wifi']) ? 1 : 0;
        $piscine = isset($_POST['piscine']) ? 1 : 0;
        $parking = isset($_POST['parking']) ? 1 : 0;
        $climatisation = isset($_POST['climatisation']) ? 1 : 0;
        $cuisine = isset($_POST['cuisine']) ? 1 : 0;
        
        $image_principale = trim($_POST['image_principale'] ?? '');
        if (empty($image_principale)) {
            $image_principale = ($type_bien === 'villa') ? '../image/villa.jpg' : '../image/apparte.jpg';
        }

        if (empty($titre) || $prix_nuit <= 0 || empty($ville)) {
            echo json_encode(['success' => false, 'error' => 'Veuillez remplir les champs obligatoires (Titre, Ville, Prix)']);
            exit();
        }

        if ($action === 'create') {
            $stmt = mysqli_prepare($conn, "INSERT INTO biens (proprietaire_id, titre, description, type_bien, adresse, ville, prix_nuit, surface, nb_chambres, nb_salles_bain, nb_personnes, wifi, piscine, parking, climatisation, cuisine, image_principale, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')");
            mysqli_stmt_bind_param($stmt, 'isssssdiiiiiiiiis', $owner_id, $titre, $description, $type_bien, $adresse, $ville, $prix_nuit, $surface, $nb_chambres, $nb_salles_bain, $nb_personnes, $wifi, $piscine, $parking, $climatisation, $cuisine, $image_principale);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE biens SET proprietaire_id = ?, titre = ?, description = ?, type_bien = ?, adresse = ?, ville = ?, prix_nuit = ?, surface = ?, nb_chambres = ?, nb_salles_bain = ?, nb_personnes = ?, wifi = ?, piscine = ?, parking = ?, climatisation = ?, cuisine = ?, image_principale = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'isssssdiiiiiiiiisi', $owner_id, $titre, $description, $type_bien, $adresse, $ville, $prix_nuit, $surface, $nb_chambres, $nb_salles_bain, $nb_personnes, $wifi, $piscine, $parking, $climatisation, $cuisine, $image_principale, $id);
        }

    } else { // voiture
        $marque = trim($_POST['marque'] ?? '');
        $modele = trim($_POST['modele'] ?? '');
        $annee = (int)($_POST['annee'] ?? date('Y'));
        $couleur = trim($_POST['couleur'] ?? '');
        $immatriculation = trim($_POST['immatriculation'] ?? '');
        $carburant = $_POST['carburant'] ?? 'essence';
        $boite = $_POST['boite'] ?? 'manuelle';
        $nb_places = (int)($_POST['nb_places'] ?? 5);
        $prix_jour = (float)($_POST['prix_jour'] ?? 0);
        $caution = (float)($_POST['caution'] ?? 0);
        $ville = trim($_POST['ville'] ?? '');
        $km = (int)($_POST['km'] ?? 0);
        
        $climatisation = isset($_POST['climatisation']) ? 1 : 0;
        $gps = isset($_POST['gps']) ? 1 : 0;
        
        $image_principale = trim($_POST['image_principale'] ?? '');
        if (empty($image_principale)) {
            $image_principale = '../image/v1.jpg';
        }

        if (empty($marque) || empty($modele) || $prix_jour <= 0 || empty($ville)) {
            echo json_encode(['success' => false, 'error' => 'Veuillez remplir les champs obligatoires (Marque, Modèle, Ville, Prix)']);
            exit();
        }

        if ($action === 'create') {
            $stmt = mysqli_prepare($conn, "INSERT INTO voitures (proprietaire_id, marque, modele, annee, couleur, immatriculation, carburant, boite, nb_places, climatisation, gps, prix_jour, caution, ville, image_principale, km, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')");
            mysqli_stmt_bind_param($stmt, 'ississsssiiddssi', $owner_id, $marque, $modele, $annee, $couleur, $immatriculation, $carburant, $boite, $nb_places, $climatisation, $gps, $prix_jour, $caution, $ville, $image_principale, $km);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE voitures SET proprietaire_id = ?, marque = ?, modele = ?, annee = ?, couleur = ?, immatriculation = ?, carburant = ?, boite = ?, nb_places = ?, climatisation = ?, gps = ?, prix_jour = ?, caution = ?, ville = ?, image_principale = ?, km = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ississsssiiddssii', $owner_id, $marque, $modele, $annee, $couleur, $immatriculation, $carburant, $boite, $nb_places, $climatisation, $gps, $prix_jour, $caution, $ville, $image_principale, $km, $id);
        }
    }

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement en base de données: ' . mysqli_error($conn)]);
    }
    exit();
}

if ($action === 'delete') {
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0 || !in_array($type, ['bien', 'voiture'])) {
        echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
        exit();
    }

    $table = ($type === 'bien') ? 'biens' : 'voitures';

    // Ownership check
    $check_q = mysqli_query($conn, "SELECT proprietaire_id FROM $table WHERE id = $id");
    $check = mysqli_fetch_assoc($check_q);
    if (!$check) {
        echo json_encode(['success' => false, 'error' => 'Annonce introuvable']);
        exit();
    }
    if (!$is_admin && (int)$check['proprietaire_id'] !== $user_id) {
        echo json_encode(['success' => false, 'error' => 'Non autorisé à supprimer cette annonce']);
        exit();
    }

    // Delete
    $stmt = mysqli_prepare($conn, "DELETE FROM $table WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . mysqli_error($conn)]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
exit();
?>
