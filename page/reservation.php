<?php
session_start();
include '../includes/config.php';

$type = $_GET['type'] ?? ''; // 'bien' or 'voiture'
$id = (int)($_GET['id'] ?? 0);
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

if (($type !== 'bien' && $type !== 'voiture') || $id <= 0 || !$date_debut || !$date_fin) {
    header('Location: acceuil.php');
    exit();
}

$item = null;
$price_unit = 0;
$label_unit = '';
$title = '';
$image = '';
$subtotal = 0;
$fees = 0;
$total = 0;
$nb_jours = 0;

// Calculate days/nights
$start = new DateTime($date_debut);
$end = new DateTime($date_fin);
$diff = $start->diff($end);
$nb_jours = (int)$diff->days;

if ($nb_jours <= 0) {
    header('Location: acceuil.php');
    exit();
}

if ($type === 'bien') {
    $q = mysqli_query($conn, "SELECT id, titre, type_bien, prix_nuit, image_principale, note_moyenne, nb_avis FROM biens WHERE id = $id AND statut='actif'");
    $item = mysqli_fetch_assoc($q);
    if ($item) {
        $price_unit = (float)$item['prix_nuit'];
        $label_unit = 'nuit';
        $title = $item['titre'];
        $image = $item['image_principale'];
        $subtotal = $nb_jours * $price_unit;
        $fees = round($subtotal * 0.05); // 5% service fees
        $total = $subtotal + $fees;
    }
} else {
    $q = mysqli_query($conn, "SELECT id, marque, modele, prix_jour, caution, image_principale, note_moyenne, nb_avis FROM voitures WHERE id = $id AND statut='actif'");
    $item = mysqli_fetch_assoc($q);
    if ($item) {
        $price_unit = (float)$item['prix_jour'];
        $label_unit = 'jour';
        $title = $item['marque'] . ' ' . $item['modele'];
        $image = $item['image_principale'];
        $subtotal = $nb_jours * $price_unit;
        $fees = 50; // 50 DH fixed file fees
        $total = $subtotal + $fees; // Note: Caution is not paid upfront, just blocked/guaranteed
    }
}

if (!$item) {
    header('Location: acceuil.php');
    exit();
}

// Prefill values from session
$client_prenom = $_SESSION['prenom'] ?? '';
$client_nom = $_SESSION['nom'] ?? '';
$client_email = $_SESSION['email'] ?? '';
$client_telephone = '';
if (isset($_SESSION['user_id'])) {
    $user_q = mysqli_query($conn, "SELECT telephone FROM utilisateurs WHERE id = {$_SESSION['user_id']}");
    $user_data = mysqli_fetch_assoc($user_q);
    if ($user_data) {
        $client_telephone = $user_data['telephone'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réservation — Immo-Location</title>
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
    
    <!-- Progress Indicator -->
    <div class="steps-progress">
      <div class="steps-progress-bar"></div>
      <div class="step-node active">
        <div class="step-circle">1</div>
        <span class="step-label">Vérification</span>
      </div>
      <div class="step-node">
        <div class="step-circle">2</div>
        <span class="step-label">Coordonnées</span>
      </div>
      <div class="step-node">
        <div class="step-circle">3</div>
        <span class="step-label">Confirmation</span>
      </div>
    </div>

    <!-- Split layout -->
    <div class="checkout-layout">
      
      <!-- Wizard Steps form -->
      <form action="../php/reservation-handler.php" method="POST" id="checkout-wizard-form" class="glass-card" style="padding:var(--space-xl); hover:none;">
        <input type="hidden" name="type" value="<?php echo $type; ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="date_debut" value="<?php echo $date_debut; ?>">
        <input type="hidden" name="date_fin" value="<?php echo $date_fin; ?>">
        <input type="hidden" name="nb_jours" value="<?php echo $nb_jours; ?>">
        <input type="hidden" name="prix_total" value="<?php echo $total; ?>">

        <!-- STEP 1: VERIFICATION DATES -->
        <div class="wizard-step active" id="step-1">
          <h2 style="color:var(--white); margin-bottom:0.5rem;"><i class="fa-solid fa-calendar-days" style="color:var(--primary);"></i> Vérification des dates</h2>
          <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.9rem;">Veuillez confirmer les dates sélectionnées ci-dessous.</p>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Date d'arrivée / Début</label>
              <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($date_debut)); ?>" readonly>
            </div>
            <div class="form-group">
              <label class="form-label">Date de départ / Fin</label>
              <input type="text" class="form-control" value="<?php echo date('d/m/Y', strtotime($date_fin)); ?>" readonly>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Durée totale</label>
            <input type="text" class="form-control" value="<?php echo $nb_jours . ' ' . $label_unit . ($nb_jours > 1 ? 's' : ''); ?>" readonly>
          </div>

          <div style="display:flex; justify-content:flex-end; margin-top:2rem;">
            <button type="button" class="btn btn-primary btn-next-step">
              Continuer <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- STEP 2: COORDONNEES CLIENT -->
        <div class="wizard-step" id="step-2">
          <h2 style="color:var(--white); margin-bottom:0.5rem;"><i class="fa-solid fa-address-card" style="color:var(--primary);"></i> Vos coordonnées</h2>
          <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.9rem;">Remplissez les détails pour l'enregistrement de votre contrat.</p>

          <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-warning" style="margin-bottom:1.5rem;">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span>Vous n'êtes pas connecté. Vous pouvez réserver, mais nous vous conseillons de vous connecter pour suivre votre historique.</span>
            </div>
          <?php endif; ?>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="client_prenom">Prénom *</label>
              <input type="text" name="client_prenom" id="client_prenom" class="form-control" 
                     placeholder="Votre prénom" required value="<?php echo htmlspecialchars($client_prenom); ?>">
              <span class="form-error" style="display:none;"><i class="fa-solid fa-circle-exclamation"></i> Prénom requis</span>
            </div>
            <div class="form-group">
              <label class="form-label" for="client_nom">Nom de famille *</label>
              <input type="text" name="client_nom" id="client_nom" class="form-control" 
                     placeholder="Votre nom" required value="<?php echo htmlspecialchars($client_nom); ?>">
              <span class="form-error" style="display:none;"><i class="fa-solid fa-circle-exclamation"></i> Nom requis</span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="client_email">Adresse email *</label>
              <input type="email" name="client_email" id="client_email" class="form-control" 
                     placeholder="votre.email@mail.com" required value="<?php echo htmlspecialchars($client_email); ?>">
              <span class="form-error" style="display:none;"><i class="fa-solid fa-circle-exclamation"></i> Email requis</span>
            </div>
            <div class="form-group">
              <label class="form-label" for="client_telephone">Numéro de téléphone *</label>
              <input type="tel" name="client_telephone" id="client_telephone" class="form-control" 
                     placeholder="06 12 34 56 78" required value="<?php echo htmlspecialchars($client_telephone); ?>">
              <span class="form-error" style="display:none;"><i class="fa-solid fa-circle-exclamation"></i> Téléphone requis</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="message_client">Demandes spéciales / Message pour l'hôte</label>
            <textarea name="message_client" id="message_client" class="form-control" 
                      placeholder="Heure d'arrivée prévue, besoins spécifiques..."></textarea>
          </div>

          <div style="display:flex; justify-content:space-between; margin-top:2rem;">
            <button type="button" class="btn btn-secondary btn-prev-step">
              <i class="fa-solid fa-arrow-left"></i> Retour
            </button>
            <button type="button" class="btn btn-primary btn-next-step">
              Continuer <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- STEP 3: REGLEMENT & SOUMISSION -->
        <div class="wizard-step" id="step-3">
          <h2 style="color:var(--white); margin-bottom:0.5rem;"><i class="fa-solid fa-shield-halved" style="color:var(--primary);"></i> Conditions & Validation</h2>
          <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.9rem;">Prêt à réserver. Veuillez accepter nos conditions pour finaliser.</p>

          <div class="form-group" style="background:rgba(8, 8, 16, 0.4); padding:1rem; border-radius:8px; border:1px solid var(--border); margin-bottom:1.5rem;">
            <p style="font-size:0.85rem; color:var(--text-secondary); line-height:1.6;">
              En validant cette réservation, vous acceptez d'être redirigé vers la page de paiement sécurisé simulé. Votre réservation sera validée définitivement une fois le paiement par carte simulé approuvé.
            </p>
          </div>

          <label class="checkbox-item" style="margin-bottom:2rem;">
            <input type="checkbox" name="accept_terms" required>
            <span>J'accepte les conditions générales de location et de vente. *</span>
          </label>

          <div style="display:flex; justify-content:space-between; margin-top:2rem;">
            <button type="button" class="btn btn-secondary btn-prev-step">
              <i class="fa-solid fa-arrow-left"></i> Retour
            </button>
            <button type="submit" class="btn btn-success">
              <i class="fa-solid fa-check"></i> Confirmer & Régler
            </button>
          </div>
        </div>

      </form>

      <!-- Sidebar Summary Panel -->
      <aside class="summary-panel">
        <h3 class="summary-heading">Résumé de la commande</h3>
        
        <div class="summary-item-card">
          <img class="summary-item-img" src="<?php echo htmlspecialchars($image); ?>" alt="Miniature">
          <div class="summary-item-info">
            <h4><?php echo htmlspecialchars($title); ?></h4>
            <p style="font-size:0.75rem;"><i class="fa-solid fa-star" style="color:var(--primary);margin-right:2px;"></i> <?php echo number_format($item['note_moyenne'], 1); ?> (<?php echo $item['nb_avis']; ?> avis)</p>
          </div>
        </div>

        <div class="detail-divider" style="margin: 1rem 0;"></div>

        <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Date d'arrivée</span>
            <span style="color:var(--white);font-weight:500;"><?php echo date('d M Y', strtotime($date_debut)); ?></span>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Date de départ</span>
            <span style="color:var(--white);font-weight:500;"><?php echo date('d M Y', strtotime($date_fin)); ?></span>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Durée</span>
            <span style="color:var(--white);font-weight:500;"><?php echo $nb_jours . ' ' . $label_unit . ($nb_jours > 1 ? 's' : ''); ?></span>
          </div>
        </div>

        <div class="detail-divider" style="margin: 1rem 0;"></div>

        <div style="display:flex; flex-direction:column; gap:0.5rem; margin-bottom:1rem;">
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Tarif unitaire</span>
            <span><?php echo number_format($price_unit, 0); ?> DH / <?php echo $label_unit; ?></span>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
            <span>Sous-total</span>
            <span><?php echo number_format($subtotal, 0); ?> DH</span>
          </div>
          
          <?php if ($type === 'bien'): ?>
            <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
              <span>Frais de service (5%)</span>
              <span><?php echo number_format($fees, 0); ?> DH</span>
            </div>
          <?php else: ?>
            <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
              <span>Frais de dossier</span>
              <span>50 DH</span>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--text-secondary);">
              <span>Caution (Garantie)</span>
              <span style="color:var(--primary);"><?php echo number_format((float)$item['caution'], 0); ?> DH</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="detail-divider" style="margin: 1rem 0;"></div>

        <div style="display:flex; justify-content:space-between; font-size:1.1rem; font-weight:700; color:var(--white);">
          <span>Total à régler</span>
          <span style="color:var(--primary);"><?php echo number_format($total, 0); ?> DH</span>
        </div>
      </aside>

    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>

<script src="../js/reservation.js"></script>
</body>
</html>
