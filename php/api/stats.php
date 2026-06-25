<?php
session_start();
header('Content-Type: application/json');
include '../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['type_compte'];

$response = [];

if ($role === 'admin') {
    // 1. General counts
    $users_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM utilisateurs"))['c'] ?? 0;
    $biens_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM biens"))['c'] ?? 0;
    $voitures_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM voitures"))['c'] ?? 0;
    $bookings_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations"))['c'] ?? 0;
    
    // 2. Booking types distribution
    $type_biens = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE type_reservation='bien'"))['c'] ?? 0;
    $type_voitures = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE type_reservation='voiture'"))['c'] ?? 0;
    
    // 3. Monthly earnings (current year)
    $earnings = array_fill(1, 12, 0);
    $earn_q = mysqli_query($conn, "
        SELECT MONTH(p.date_paiement) as m, SUM(p.montant) as val 
        FROM paiements p 
        WHERE p.statut='valide' AND YEAR(p.date_paiement) = YEAR(CURDATE())
        GROUP BY MONTH(p.date_paiement)
    ");
    while ($r = mysqli_fetch_assoc($earn_q)) {
        $earnings[(int)$r['m']] = (float)$r['val'];
    }
    
    $response = [
        'role' => 'admin',
        'counts' => [
            'users' => $users_cnt,
            'biens' => $biens_cnt,
            'voitures' => $voitures_cnt,
            'reservations' => $bookings_cnt
        ],
        'distribution' => [
            'biens' => $type_biens,
            'voitures' => $type_voitures
        ],
        'monthly_earnings' => array_values($earnings)
    ];

} elseif ($role === 'proprietaire') {
    // 1. General counts for this owner
    $biens_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM biens WHERE proprietaire_id=$user_id"))['c'] ?? 0;
    $voitures_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM voitures WHERE proprietaire_id=$user_id"))['c'] ?? 0;
    
    // Reservations count on owner's items
    $bookings_q = mysqli_query($conn, "
        SELECT COUNT(*) as c 
        FROM reservations r
        LEFT JOIN biens b ON r.bien_id = b.id AND r.type_reservation='bien'
        LEFT JOIN voitures v ON r.voiture_id = v.id AND r.type_reservation='voiture'
        WHERE b.proprietaire_id = $user_id OR v.proprietaire_id = $user_id
    ");
    $bookings_cnt = mysqli_fetch_assoc($bookings_q)['c'] ?? 0;

    // Total earnings
    $earnings_q = mysqli_query($conn, "
        SELECT SUM(p.montant) as val 
        FROM paiements p
        JOIN reservations r ON p.reservation_id = r.id
        LEFT JOIN biens b ON r.bien_id = b.id AND r.type_reservation='bien'
        LEFT JOIN voitures v ON r.voiture_id = v.id AND r.type_reservation='voiture'
        WHERE p.statut='valide' AND (b.proprietaire_id = $user_id OR v.proprietaire_id = $user_id)
    ");
    $total_earnings = (float)(mysqli_fetch_assoc($earnings_q)['val'] ?? 0);

    // Monthly earnings for owner
    $earnings = array_fill(1, 12, 0);
    $earn_q = mysqli_query($conn, "
        SELECT MONTH(p.date_paiement) as m, SUM(p.montant) as val 
        FROM paiements p 
        JOIN reservations r ON p.reservation_id = r.id
        LEFT JOIN biens b ON r.bien_id = b.id AND r.type_reservation='bien'
        LEFT JOIN voitures v ON r.voiture_id = v.id AND r.type_reservation='voiture'
        WHERE p.statut='valide' 
          AND YEAR(p.date_paiement) = YEAR(CURDATE())
          AND (b.proprietaire_id = $user_id OR v.proprietaire_id = $user_id)
        GROUP BY MONTH(p.date_paiement)
    ");
    while ($r = mysqli_fetch_assoc($earn_q)) {
        $earnings[(int)$r['m']] = (float)$r['val'];
    }

    $response = [
        'role' => 'proprietaire',
        'counts' => [
            'biens' => $biens_cnt,
            'voitures' => $voitures_cnt,
            'reservations' => $bookings_cnt,
            'earnings' => $total_earnings
        ],
        'monthly_earnings' => array_values($earnings)
    ];
} else {
    // Client basic stats
    $bookings_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE client_id=$user_id"))['c'] ?? 0;
    $favs_cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM favoris WHERE utilisateur_id=$user_id"))['c'] ?? 0;
    
    $response = [
        'role' => 'client',
        'counts' => [
            'reservations' => $bookings_cnt,
            'favoris' => $favs_cnt
        ]
    ];
}

echo json_encode($response);
exit();
?>
