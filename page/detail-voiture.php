<?php
session_start();
include '../includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: voiturre.php');
    exit();
}

// 1. Get car details with owner info
$query = mysqli_prepare($conn, "
    SELECT v.*, u.prenom, u.nom, u.avatar as host_avatar, u.ville as host_ville
    FROM voitures v
    JOIN utilisateurs u ON v.proprietaire_id = u.id
    WHERE v.id = ? AND v.statut = 'actif'
");
mysqli_stmt_bind_param($query, 'i', $id);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
$car = mysqli_fetch_assoc($result);

if (!$car) {
    header('Location: voiturre.php');
    exit();
}

// Increment views or just retrieve gallery
$gallery_q = mysqli_query($conn, "SELECT url FROM images_voitures WHERE voiture_id = $id ORDER BY ordre ASC");
$gallery_images = [];
while ($row = mysqli_fetch_assoc($gallery_q)) {
    $gallery_images[] = $row['url'];
}
if (empty($gallery_images)) {
    $gallery_images[] = $car['image_principale'];
}

// 2. Fetch reviews
$reviews_q = mysqli_query($conn, "
    SELECT a.*, u.prenom, u.nom, u.avatar
    FROM avis a
    JOIN utilisateurs u ON a.auteur_id = u.id
    WHERE a.type_cible = 'voiture' AND a.voiture_id = $id AND a.statut = 'actif'
    ORDER BY a.date_creation DESC
");
$reviews = [];
while ($row = mysqli_fetch_assoc($reviews_q)) {
    $reviews[] = $row;
}

// Check if item is in client's favorites
$is_fav = false;
if (isset($_SESSION['user_id'])) {
    $fav_q = mysqli_query($conn, "SELECT id FROM favoris WHERE utilisateur_id = {$_SESSION['user_id']} AND type_favori = 'voiture' AND voiture_id = $id");
    $is_fav = mysqli_num_rows($fav_q) > 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../image/favicon.png">
  <link rel="apple-touch-icon" href="../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($car['marque'] . ' ' . $car['modele']); ?> — Immo-Location</title>
  <meta name="description" content="Location de <?php echo htmlspecialchars($car['marque'] . ' ' . $car['modele']); ?> à <?php echo htmlspecialchars($car['ville']); ?>. Voiture premium, boîte <?php echo $car['boite']; ?>, carburant <?php echo $car['carburant']; ?>.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/detail.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 100px; padding-bottom: 4rem;">
  <div class="container">

    <!-- Breadcrumb & Back -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
      <nav class="breadcrumb" style="margin:0;">
        <a href="acceuil.php">Accueil</a>
        <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span>
        <a href="voiturre.php">Voitures</a>
        <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span>
        <span>Détail</span>
      </nav>
      
      <button class="listing-card-fav <?php echo $is_fav ? 'active' : ''; ?>" 
              data-type="voiture" data-id="<?php echo $car['id']; ?>" 
              style="position:static; width:40px; height:40px; border-radius:var(--radius-md);">
        <i class="<?php echo $is_fav ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
      </button>
    </div>

    <!-- Gallery section -->
    <div class="detail-gallery">
      <div class="gallery-main" onclick="openLightbox(0)">
        <img src="<?php echo htmlspecialchars($car['image_principale']); ?>" alt="Image principale">
      </div>
      <div class="detail-gallery-thumbs">
        <?php for ($i = 0; $i < min(2, count($gallery_images)); $i++): ?>
          <div class="gallery-thumb" onclick="openLightbox(<?php echo $i; ?>)">
            <img src="<?php echo htmlspecialchars($gallery_images[$i]); ?>" alt="Miniature">
            <?php if ($i === 1 && count($gallery_images) > 2): ?>
              <div class="gallery-more-overlay">
                <i class="fa-solid fa-images"></i>
                <span>+<?php echo count($gallery_images) - 2; ?> photos</span>
              </div>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Main Detail Layout -->
    <div class="detail-layout">
      
      <!-- Content Area -->
      <div>
        <div class="detail-header">
          <span class="badge badge-accent" style="margin-bottom:0.5rem;text-transform:capitalize;">
            <i class="fa-solid fa-car"></i> Véhicule
          </span>
          <h1 class="detail-title"><?php echo htmlspecialchars($car['marque'] . ' ' . $car['modele']); ?></h1>
          
          <div class="detail-meta-row">
            <div class="detail-meta-item">
              <i class="fa-solid fa-location-dot"></i>
              <span>Disponible à <?php echo htmlspecialchars($car['ville']); ?></span>
            </div>
            <div class="detail-meta-item">
              <i class="fa-solid fa-star"></i>
              <strong style="color:var(--white);"><?php echo number_format($car['note_moyenne'], 1); ?></strong>
              <span>(<?php echo $car['nb_avis']; ?> avis)</span>
            </div>
          </div>
        </div>

        <div class="detail-divider"></div>

        <!-- Specs grid -->
        <h3 class="detail-section-title"><i class="fa-solid fa-circle-info"></i> Fiche technique</h3>
        <div class="specs-grid" style="margin-bottom: 2rem;">
          <div class="spec-card">
            <i class="fa-solid fa-gears spec-icon"></i>
            <span class="spec-val" style="text-transform:capitalize;"><?php echo htmlspecialchars($car['boite']); ?></span>
            <span class="spec-label">Transmission</span>
          </div>
          <div class="spec-card">
            <i class="fa-solid fa-gas-pump spec-icon"></i>
            <span class="spec-val" style="text-transform:capitalize;"><?php echo htmlspecialchars($car['carburant']); ?></span>
            <span class="spec-label">Carburant</span>
          </div>
          <div class="spec-card">
            <i class="fa-solid fa-chair spec-icon"></i>
            <span class="spec-val"><?php echo $car['nb_places']; ?></span>
            <span class="spec-label">Nombre de places</span>
          </div>
          <div class="spec-card">
            <i class="fa-solid fa-calendar spec-icon"></i>
            <span class="spec-val"><?php echo $car['annee']; ?></span>
            <span class="spec-label">Année modèle</span>
          </div>
        </div>

        <h3 class="detail-section-title"><i class="fa-solid fa-circle-check"></i> Options & Confort</h3>
        <div class="specs-grid" style="margin-bottom: 2rem;">
          <?php if ($car['climatisation']): ?>
            <div class="spec-card">
              <i class="fa-solid fa-snowflake spec-icon" style="color:var(--success);"></i>
              <span class="spec-val">Oui</span>
              <span class="spec-label">Climatisation</span>
            </div>
          <?php endif; ?>
          <?php if ($car['gps']): ?>
            <div class="spec-card">
              <i class="fa-solid fa-location-crosshairs spec-icon" style="color:var(--success);"></i>
              <span class="spec-val">Oui</span>
              <span class="spec-label">GPS</span>
            </div>
          <?php endif; ?>
          <div class="spec-card">
            <i class="fa-solid fa-gauge-high spec-icon"></i>
            <span class="spec-val"><?php echo number_format($car['km'], 0, ',', ' '); ?> km</span>
            <span class="spec-label">Kilométrage</span>
          </div>
          <div class="spec-card">
            <i class="fa-solid fa-shield-halved spec-icon"></i>
            <span class="spec-val"><?php echo number_format($car['caution'], 0, ',', ' '); ?> DH</span>
            <span class="spec-label">Caution</span>
          </div>
        </div>

        <div class="detail-divider"></div>

        <!-- Description -->
        <h3 class="detail-section-title"><i class="fa-solid fa-align-left"></i> Description</h3>
        <div class="detail-description" style="margin-bottom: 2rem;">
          <p>
            Profitez de cette magnifique <?php echo htmlspecialchars($car['marque'] . ' ' . $car['modele']); ?> de couleur <?php echo htmlspecialchars($car['couleur']); ?> pour tous vos déplacements à <?php echo htmlspecialchars($car['ville']); ?> et dans tout le Maroc. Véhicule révisé et parfaitement propre à chaque prise en charge. Caution exigée lors du départ.
          </p>
        </div>

        <div class="detail-divider"></div>

        <!-- Owner card -->
        <h3 class="detail-section-title"><i class="fa-solid fa-user-tie"></i> Agence / Propriétaire</h3>
        <div class="host-card" style="margin-bottom: 2rem;">
          <img src="<?php echo $car['host_avatar'] ? htmlspecialchars($car['host_avatar']) : '../image/logo.png'; ?>" 
               alt="Avatar propriétaire" class="host-avatar"
               onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($car['prenom'] . ' ' . $car['nom']); ?>&background=141424&color=f5a623';">
          <div class="host-info">
            <h4><?php echo htmlspecialchars($car['prenom'] . ' ' . $car['nom']); ?></h4>
            <p>Agence partenaire • Réside à <?php echo htmlspecialchars($car['host_ville'] ?: $car['ville']); ?></p>
          </div>
        </div>

        <div class="detail-divider"></div>

        <!-- Reviews section -->
        <h3 class="detail-section-title"><i class="fa-solid fa-star"></i> Avis clients</h3>
        <div class="reviews-summary">
          <div class="rating-big-box">
            <span class="rating-big-num"><?php echo number_format($car['note_moyenne'], 1); ?></span>
            <div class="stars-row">
              <?php
              $full_stars = floor($car['note_moyenne']);
              for ($i = 0; $i < 5; $i++) {
                  if ($i < $full_stars) {
                      echo '<i class="fa-solid fa-star"></i>';
                  } else {
                      echo '<i class="fa-regular fa-star"></i>';
                  }
              }
              ?>
            </div>
            <span class="avis-score-label"><?php echo $car['nb_avis']; ?> avis conducteurs</span>
          </div>
          <div style="display:flex; flex-direction:column; justify-content:center;">
            <p class="detail-description" style="font-size:0.9rem;">
              Les notes et avis sont collectés uniquement auprès de personnes ayant loué ce véhicule sur notre plateforme.
            </p>
          </div>
        </div>

        <!-- Avis list -->
        <div class="reviews-list">
          <?php if (empty($reviews)): ?>
            <p class="detail-description" style="font-style:italic;">Aucun avis pour l'instant. Louez ce véhicule et partagez votre expérience !</p>
          <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
              <div class="review-item">
                <div class="review-item-header">
                  <div class="review-author">
                    <img class="review-author-avatar" 
                         src="<?php echo $rev['avatar'] ? htmlspecialchars($rev['avatar']) : '../image/logo.png'; ?>" 
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($rev['prenom'] . ' ' . $rev['nom']); ?>&background=141424&color=f5a623';"
                         alt="Avatar client">
                    <div>
                      <span class="review-author-name"><?php echo htmlspecialchars($rev['prenom'] . ' ' . $rev['nom']); ?></span>
                      <div class="stars-row" style="font-size:0.75rem; margin:0;">
                        <?php
                        for ($i = 0; $i < 5; $i++) {
                            if ($i < $rev['note']) {
                                echo '<i class="fa-solid fa-star"></i>';
                            } else {
                                echo '<i class="fa-regular fa-star"></i>';
                            }
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                  <span class="review-date"><?php echo date('d/m/Y', strtotime($rev['date_creation'])); ?></span>
                </div>
                <p class="review-content"><?php echo nl2br(htmlspecialchars($rev['commentaire'])); ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>

      <!-- Booking Sidebar Panel -->
      <aside class="booking-sidebar">
        <div class="booking-price-header">
          <div>
            <span class="booking-price-amount"><?php echo number_format($car['prix_jour'], 0, ',', ' '); ?> DH</span>
            <span class="booking-price-label">/ jour</span>
          </div>
          <div>
            <span style="font-size: 0.82rem; color: var(--text-secondary);">
              <i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Dispo
            </span>
          </div>
        </div>

        <form action="reservation.php" method="GET" id="booking-sidebar-form">
          <input type="hidden" name="type" value="voiture">
          <input type="hidden" name="id" value="<?php echo $car['id']; ?>">

          <div class="booking-dates-box">
            <div class="booking-date-field">
              <span class="booking-date-label">Début location</span>
              <input type="date" name="date_debut" id="date_debut" class="booking-date-input" required 
                     min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="booking-date-field">
              <span class="booking-date-label">Fin location</span>
              <input type="date" name="date_fin" id="date_fin" class="booking-date-input" required
                     min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
          </div>

          <div class="booking-pricing-breakdown" id="pricing-breakdown" style="display:none;">
            <div class="breakdown-row">
              <span id="price-nci"><?php echo number_format($car['prix_jour'], 0); ?> DH × <span id="days-count">0</span> jours</span>
              <span id="price-subtotal">0 DH</span>
            </div>
            <div class="breakdown-row">
              <span>Caution de garantie (Restituée)</span>
              <span><?php echo number_format($car['caution'], 0, ',', ' '); ?> DH</span>
            </div>
            <div class="breakdown-row">
              <span>Frais de dossier (Fixes)</span>
              <span>50 DH</span>
            </div>
            <div class="breakdown-row total">
              <span>Total à régler</span>
              <span id="price-total">0 DH</span>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full btn-lg">
            <i class="fa-solid fa-credit-card"></i> Réserver le véhicule
          </button>
        </form>

        <div class="occupancy-calendar-info">
          <span class="occupancy-dot"></span>
          <span>Dates indisponibles bloquées en base.</span>
        </div>
      </aside>

    </div>
  </div>
</main>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
  <span class="lightbox-close"><i class="fa-solid fa-xmark"></i></span>
  <div class="lightbox-content" onclick="event.stopPropagation()">
    <img id="lightbox-img" src="" alt="Agrandissement photo">
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Script details page logic -->
<script>
const galleryImages = <?php echo json_encode($gallery_images); ?>;

function openLightbox(index) {
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  if (lightbox && lightboxImg && galleryImages[index]) {
    lightboxImg.src = galleryImages[index];
    lightbox.classList.add('active');
  }
}

function closeLightbox() {
  const lightbox = document.getElementById('lightbox');
  if (lightbox) {
    lightbox.classList.remove('active');
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const prixJour = <?php echo (float)$car['prix_jour']; ?>;
  const dateDebutInput = document.getElementById('date_debut');
  const dateFinInput = document.getElementById('date_fin');
  const pricingBreakdown = document.getElementById('pricing-breakdown');
  const daysCount = document.getElementById('days-count');
  const subtotalLabel = document.getElementById('price-subtotal');
  const totalLabel = document.getElementById('price-total');

  // Disable dates already booked
  try {
    const res = await fetch(`../php/api/reservations.php?type=voiture&id=<?php echo $car['id']; ?>`);
    const data = await res.json();
    if (data.occupied_ranges) {
      const blockDates = (inputEl) => {
        inputEl.addEventListener('input', () => {
          const selected = new Date(inputEl.value);
          const isOccupied = data.occupied_ranges.some(range => {
            const start = new Date(range.from);
            const end = new Date(range.to);
            return selected >= start && selected <= end;
          });
          if (isOccupied) {
            window.showToast("Ce véhicule est indisponible à cette date !", "warning", "Date indisponible");
            inputEl.value = '';
            pricingBreakdown.style.display = 'none';
          }
        });
      };
      blockDates(dateDebutInput);
      blockDates(dateFinInput);
    }
  } catch(e) {
    console.log("Impossible de récupérer le calendrier d'occupation", e);
  }

  function calculatePrice() {
    const startVal = dateDebutInput.value;
    const endVal = dateFinInput.value;

    if (startVal && endVal) {
      const start = new Date(startVal);
      const end = new Date(endVal);

      if (end <= start) {
        window.showToast("La date de fin doit être après le début !", "warning", "Erreur de dates");
        dateFinInput.value = '';
        pricingBreakdown.style.display = 'none';
        return;
      }

      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays > 0) {
        const subtotal = diffDays * prixJour;
        const total = subtotal + 50; // 50 DH fixed fees

        daysCount.textContent = diffDays;
        subtotalLabel.textContent = subtotal.toLocaleString('fr-FR') + ' DH';
        totalLabel.textContent = total.toLocaleString('fr-FR') + ' DH';

        pricingBreakdown.style.display = 'flex';
      }
    } else {
      pricingBreakdown.style.display = 'none';
    }
  }

  dateDebutInput.addEventListener('change', () => {
    dateFinInput.min = dateDebutInput.value ? dateDebutInput.value : "<?php echo date('Y-m-d', strtotime('+1 day')); ?>";
    calculatePrice();
  });
  dateFinInput.addEventListener('change', calculatePrice);
});
</script>
</body>
</html>
