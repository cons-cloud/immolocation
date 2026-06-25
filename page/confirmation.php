<?php
session_start();
include '../includes/config.php';

$res_id = (int)($_GET['id'] ?? 0);
if ($res_id <= 0) {
    header('Location: acceuil.php');
    exit();
}

// Get reservation and payment details
$query = mysqli_prepare($conn, "
    SELECT r.*, p.reference as txn_ref, p.date_paiement, u.prenom, u.nom, u.email, u.telephone
    FROM reservations r
    JOIN paiements p ON p.reservation_id = r.id
    JOIN utilisateurs u ON r.client_id = u.id
    WHERE r.id = ? AND r.statut = 'confirmee'
");
mysqli_stmt_bind_param($query, 'i', $res_id);
mysqli_stmt_execute($query);
$res = mysqli_fetch_assoc(mysqli_stmt_get_result($query));

if (!$res) {
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réservation Confirmée — Immo-Location</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/reservation.css">
  <style>
    @media print {
      body * { visibility: hidden; }
      .success-card, .success-card * { visibility: visible; }
      .success-card { position: absolute; left: 0; top: 0; width: 100%; border: none; background: none; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 100px; padding-bottom: 4rem;">
  <div class="container">
    
    <div class="glass-card success-card">
      <div class="success-icon-wrapper">
        <i class="fa-solid fa-circle-check"></i>
      </div>

      <h1 style="color:var(--white); font-weight:800; margin-bottom:0.5rem; font-size:2rem;">Réservation Confirmée !</h1>
      <p style="color:var(--text-secondary); margin-bottom:2rem;">Merci pour votre confiance. Votre contrat est validé et vos dates sont bloquées.</p>

      <div class="qr-code-box">
        <!-- Dynamic canvas-drawn barcode pattern representation -->
        <canvas id="qr-canvas" width="130" height="130"></canvas>
        <div style="font-size:0.75rem; color:var(--bg-primary); font-weight:700; margin-top:5px; font-family:monospace;">
          <?php echo htmlspecialchars($res['numero_reservation']); ?>
        </div>
      </div>

      <!-- Receipt items -->
      <table class="confirmation-receipt-table">
        <tr>
          <td>Numéro de commande</td>
          <td><?php echo htmlspecialchars($res['numero_reservation']); ?></td>
        </tr>
        <tr>
          <td>Désignation</td>
          <td><?php echo htmlspecialchars($title); ?></td>
        </tr>
        <tr>
          <td>Client</td>
          <td><?php echo htmlspecialchars($res['prenom'] . ' ' . $res['nom']); ?></td>
        </tr>
        <tr>
          <td>Dates</td>
          <td>Du <?php echo date('d/m/Y', strtotime($res['date_debut'])); ?> au <?php echo date('d/m/Y', strtotime($res['date_fin'])); ?></td>
        </tr>
        <tr>
          <td>Durée</td>
          <td><?php echo $res['nb_jours'] . ' jours/nuits'; ?></td>
        </tr>
        <tr>
          <td>Transaction ID</td>
          <td style="font-family:monospace; font-size:0.8rem;"><?php echo htmlspecialchars($res['txn_ref']); ?></td>
        </tr>
        <tr style="border-top: 2px solid var(--border);">
          <td style="font-weight:700; font-size:1.05rem; color:var(--white);">Montant payé</td>
          <td style="font-weight:700; font-size:1.15rem; color:var(--primary);"><?php echo number_format($res['prix_total'], 0, ',', ' '); ?> DH</td>
        </tr>
      </table>

      <!-- Action buttons -->
      <div style="display:flex; justify-content:center; gap:var(--space-sm); flex-wrap:wrap; margin-top:2.5rem;" class="no-print">
        <button onclick="window.print()" class="btn btn-secondary">
          <i class="fa-solid fa-print"></i> Imprimer le reçu
        </button>
        
        <?php if (isset($_SESSION['type_compte']) && $_SESSION['type_compte'] === 'client'): ?>
          <a href="dash/client.php" class="btn btn-primary">
            <i class="fa-solid fa-gauge"></i> Accéder à mon espace
          </a>
        <?php else: ?>
          <a href="acceuil.php" class="btn btn-primary">
            <i class="fa-solid fa-house"></i> Retour à l'accueil
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>

<!-- Canvas QR code generation script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const canvas = document.getElementById('qr-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const size = canvas.width;
  
  // Clear canvas
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, size, size);

  ctx.fillStyle = '#080810';

  // 1. Draw Finder Patterns (Big squares on 3 corners)
  const drawFinder = (x, y) => {
    ctx.fillRect(x, y, 35, 35);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x + 5, y + 5, 25, 25);
    ctx.fillStyle = '#080810';
    ctx.fillRect(x + 10, y + 10, 15, 15);
  };

  drawFinder(5, 5);          // Top-left
  drawFinder(size - 40, 5);  // Top-right
  drawFinder(5, size - 40);  // Bottom-left

  // 2. Draw random barcode-looking blocks for simulated premium QR feel
  // Seedable-like pseudo-random generator based on reservation number
  const resNumber = "<?php echo $res['numero_reservation']; ?>";
  let hash = 0;
  for (let i = 0; i < resNumber.length; i++) {
    hash = resNumber.charCodeAt(i) + ((hash << 5) - hash);
  }

  const getRand = (index) => {
    const x = Math.sin(hash + index) * 10000;
    return x - Math.floor(x);
  };

  // Grid sizing
  const gridCount = 21;
  const cellSize = size / gridCount;

  for (let row = 0; row < gridCount; row++) {
    for (let col = 0; col < gridCount; col++) {
      // Exclude finder pattern areas
      if ((row < 7 && col < 7) || (row < 7 && col >= gridCount - 7) || (row >= gridCount - 7 && col < 7)) {
        continue;
      }
      
      // Draw random pixels
      if (getRand(row * gridCount + col) > 0.5) {
        ctx.fillRect(col * cellSize, row * cellSize, cellSize + 0.5, cellSize + 0.5);
      }
    }
  }
});
</script>
</body>
</html>
