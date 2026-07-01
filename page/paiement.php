<?php
session_start();
include '../includes/config.php';

$res_id = (int)($_GET['id'] ?? 0);
if ($res_id <= 0) {
    header('Location: acceuil.php');
    exit();
}

// Get reservation info
$query = mysqli_prepare($conn, "
    SELECT r.*, u.prenom, u.nom, u.email
    FROM reservations r
    JOIN utilisateurs u ON r.client_id = u.id
    WHERE r.id = ? AND r.statut = 'en_attente'
");
mysqli_stmt_bind_param($query, 'i', $res_id);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
$res = mysqli_fetch_assoc($result);

if (!$res) {
    // If already confirmed, redirect to confirmation directly
    $confirm_q = mysqli_query($conn, "SELECT id FROM reservations WHERE id = $res_id AND statut = 'confirmee'");
    if (mysqli_num_rows($confirm_q) > 0) {
        header("Location: confirmation.php?id=$res_id");
        exit();
    }
    header('Location: acceuil.php');
    exit();
}

$title = '';
$image = '';
if ($res['type_reservation'] === 'bien') {
    $item_q = mysqli_query($conn, "SELECT titre, image_principale FROM biens WHERE id = {$res['bien_id']}");
    $item = mysqli_fetch_assoc($item_q);
    $title = $item['titre'] ?? 'Hébergement';
    $image = $item['image_principale'] ?? '';
} else {
    $item_q = mysqli_query($conn, "SELECT CONCAT(marque, ' ', modele) as car_title, image_principale FROM voitures WHERE id = {$res['voiture_id']}");
    $item = mysqli_fetch_assoc($item_q);
    $title = $item['car_title'] ?? 'Véhicule';
    $image = $item['image_principale'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../image/favicon.png">
  <link rel="apple-touch-icon" href="../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Paiement Sécurisé — Immo-Location</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/reservation.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 100px; padding-bottom: 4rem;">
  <div class="container">
    
    <div style="text-align:center; margin-bottom: 2rem;">
      <h1 style="color:var(--white); font-weight:800; margin-bottom: 5px;">Paiement Sécurisé</h1>
      <p style="color:var(--text-secondary); font-size:0.95rem;">Complétez le règlement de votre réservation en toute sécurité.</p>
    </div>

    <div class="checkout-layout">
      
      <!-- Interactive Card & Fields (Left) -->
      <div class="glass-card" style="padding: var(--space-xl); hover:none;">
        <h2 style="color:var(--white); margin-bottom:1.5rem; font-size:1.3rem;"><i class="fa-solid fa-credit-card" style="color:var(--primary);margin-right:8px;"></i> Carte de crédit</h2>

        <div class="payment-container">
          <!-- 3D Card Animation Wrapper -->
          <div class="card-wrapper">
            <div class="credit-card" id="credit-card-3d">
              <!-- Front Face -->
              <div class="card-face front">
                <div class="card-brand-row">
                  <div class="card-chip"></div>
                  <i class="fa-brands fa-cc-visa" style="font-size:2rem;color:rgba(255,255,255,0.85);"></i>
                </div>
                <div class="card-number-display">•••• •••• •••• ••••</div>
                <div class="card-holder-row">
                  <div>
                    <div class="card-holder-label">Titulaire</div>
                    <div class="card-holder-name">VOTRE NOM COMPLET</div>
                  </div>
                  <div>
                    <div class="card-holder-label">Expire fin</div>
                    <div class="card-expiry-val">MM/AA</div>
                  </div>
                </div>
              </div>
              <!-- Back Face -->
              <div class="card-face back">
                <div class="card-magnetic-strip"></div>
                <div class="card-signature-box">
                  <div class="card-cvv-display">•••</div>
                </div>
                <div style="text-align:right; padding-right:20px; font-size:0.6rem; color:var(--text-secondary); margin-top:20px;">
                  SECURE PREPAID CARD
                </div>
              </div>
            </div>
          </div>

          <!-- Card Form Inputs -->
          <form action="../php/paiement-handler.php" method="POST" id="payment-form" style="width:100%;">
            <input type="hidden" name="reservation_id" value="<?php echo $res_id; ?>">
            <input type="hidden" name="montant" value="<?php echo $res['prix_total']; ?>">

            <div class="form-group">
              <label class="form-label" for="card_number">Numéro de carte</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-credit-card input-icon"></i>
                <input type="text" id="card_number" name="card_number" class="form-control" 
                       placeholder="4000 1234 5678 9010" required autocomplete="off">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="card_name">Titulaire de la carte</label>
              <div class="input-wrapper">
                <i class="fa-solid fa-user input-icon"></i>
                <input type="text" id="card_name" name="card_name" class="form-control" 
                       placeholder="NOM DE FAMILLE PRENOM" required autocomplete="off">
              </div>
            </div>

            <div class="payment-form-grid">
              <div class="form-group">
                <label class="form-label" for="card_expiry">Date d'expiration</label>
                <div class="input-wrapper">
                  <i class="fa-solid fa-calendar input-icon"></i>
                  <input type="text" id="card_expiry" name="card_expiry" class="form-control" 
                         placeholder="MM/AA" required autocomplete="off">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="card_cvv">CVV</label>
                <div class="input-wrapper">
                  <i class="fa-solid fa-lock input-icon"></i>
                  <input type="password" id="card_cvv" name="card_cvv" class="form-control" 
                         placeholder="•••" required autocomplete="off">
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-success w-full btn-lg" id="pay-submit-btn" style="margin-top:1.5rem;">
              <i class="fa-solid fa-lock-open"></i> Régler <?php echo number_format($res['prix_total'], 0, ',', ' '); ?> DH
            </button>
          </form>
        </div>

      </div>

      <!-- Right Summary Panel -->
      <aside class="summary-panel">
        <h3 class="summary-heading">Détail du paiement</h3>
        
        <div class="summary-item-card">
          <img class="summary-item-img" src="<?php echo htmlspecialchars($image); ?>" alt="Miniature">
          <div class="summary-item-info">
            <h4><?php echo htmlspecialchars($title); ?></h4>
            <p style="text-transform:capitalize;font-size:0.75rem;"><i class="fa-solid fa-receipt" style="color:var(--primary);margin-right:2px;"></i> Commande : <?php echo htmlspecialchars($res['numero_reservation']); ?></p>
          </div>
        </div>

        <div class="detail-divider" style="margin: 1rem 0;"></div>

        <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Date d'arrivée</span>
            <span style="color:var(--white);font-weight:500;"><?php echo date('d M Y', strtotime($res['date_debut'])); ?></span>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Date de départ</span>
            <span style="color:var(--white);font-weight:500;"><?php echo date('d M Y', strtotime($res['date_fin'])); ?></span>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Durée</span>
            <span style="color:var(--white);font-weight:500;"><?php echo $res['nb_jours'] . ' jours/nuits'; ?></span>
          </div>
        </div>

        <div class="detail-divider" style="margin: 1rem 0;"></div>

        <div style="display:flex; justify-content:space-between; font-size:1.15rem; font-weight:700; color:var(--white);">
          <span>Total à régler</span>
          <span style="color:var(--primary);"><?php echo number_format($res['prix_total'], 0, ',', ' '); ?> DH</span>
        </div>
      </aside>

    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>

<script src="../js/payment.js"></script>
</body>
</html>
