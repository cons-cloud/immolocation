<?php
session_start();
include '../includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: appartement.php');
    exit();
}

// 1. Get property details with owner info
$query = mysqli_prepare($conn, "
    SELECT b.*, u.prenom, u.nom, u.avatar as host_avatar, u.ville as host_ville
    FROM biens b
    JOIN utilisateurs u ON b.proprietaire_id = u.id
    WHERE b.id = ? AND b.statut = 'actif'
");
mysqli_stmt_bind_param($query, 'i', $id);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
$bien = mysqli_fetch_assoc($result);

if (!$bien) {
    header('Location: appartement.php');
    exit();
}

// Increment views
mysqli_query($conn, "UPDATE biens SET vues = vues + 1 WHERE id = $id");

// 2. Fetch additional gallery images
$gallery_q = mysqli_query($conn, "SELECT url FROM images_biens WHERE bien_id = $id ORDER BY ordre ASC");
$gallery_images = [];
while ($row = mysqli_fetch_assoc($gallery_q)) {
    $gallery_images[] = $row['url'];
}
// Fallback if no gallery images
if (empty($gallery_images)) {
    $gallery_images[] = $bien['image_principale'];
}

// 3. Fetch reviews
$reviews_q = mysqli_query($conn, "
    SELECT a.*, u.prenom, u.nom, u.avatar
    FROM avis a
    JOIN utilisateurs u ON a.auteur_id = u.id
    WHERE a.type_cible = 'bien' AND a.bien_id = $id AND a.statut = 'actif'
    ORDER BY a.date_creation DESC
");
$reviews = [];
while ($row = mysqli_fetch_assoc($reviews_q)) {
    $reviews[] = $row;
}

// Check if item is in client's favorites
$is_fav = false;
if (isset($_SESSION['user_id'])) {
    $fav_q = mysqli_query($conn, "SELECT id FROM favoris WHERE utilisateur_id = {$_SESSION['user_id']} AND type_favori = 'bien' AND bien_id = $id");
    $is_fav = mysqli_num_rows($fav_q) > 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($bien['titre']); ?> — Immo-Location</title>
  <meta name="description" content="<?php echo htmlspecialchars(substr($bien['description'], 0, 150)); ?>">
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
        <a href="<?php echo $bien['type_bien'] === 'villa' ? 'villa.php' : 'appartement.php'; ?>">
          <?php echo $bien['type_bien'] === 'villa' ? 'Villas' : 'Appartements'; ?>
        </a>
        <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span>
        <span>Détail</span>
      </nav>
      
      <button class="listing-card-fav <?php echo $is_fav ? 'active' : ''; ?>" 
              data-type="bien" data-id="<?php echo $bien['id']; ?>" 
              style="position:static; width:40px; height:40px; border-radius:var(--radius-md);">
        <i class="<?php echo $is_fav ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
      </button>
    </div>

    <!-- Gallery section -->
    <div class="detail-gallery">
      <div class="gallery-main" onclick="openLightbox(0)">
        <img src="<?php echo htmlspecialchars($bien['image_principale']); ?>" alt="Image principale">
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
          <span class="badge badge-primary" style="margin-bottom:0.5rem;text-transform:capitalize;">
            <i class="fa-solid fa-house"></i> <?php echo htmlspecialchars($bien['type_bien']); ?>
          </span>
          <h1 class="detail-title"><?php echo htmlspecialchars($bien['titre']); ?></h1>
          
          <div class="detail-meta-row">
            <div class="detail-meta-item">
              <i class="fa-solid fa-location-dot"></i>
              <span><?php echo htmlspecialchars($bien['adresse'] . ', ' . $bien['ville']); ?></span>
            </div>
            <div class="detail-meta-item">
              <i class="fa-solid fa-star"></i>
              <strong style="color:var(--white);"><?php echo number_format($bien['note_moyenne'], 1); ?></strong>
              <span>(<?php echo $bien['nb_avis']; ?> avis)</span>
            </div>
            <div class="detail-meta-item">
              <i class="fa-solid fa-eye"></i>
              <span><?php echo $bien['vues']; ?> vues</span>
            </div>
          </div>
        </div>

        <div class="detail-divider"></div>

        <!-- Specs grid -->
        <h3 class="detail-section-title"><i class="fa-solid fa-circle-info"></i> Caractéristiques</h3>
        <div class="specs-grid" style="margin-bottom: 2rem;">
          <div class="spec-card">
            <i class="fa-solid fa-maximize spec-icon"></i>
            <span class="spec-val"><?php echo $bien['surface']; ?> m²</span>
            <span class="spec-label">Surface</span>
          </div>
          <div class="spec-card">
            <i class="fa-solid fa-bed spec-icon"></i>
            <span class="spec-val"><?php echo $bien['nb_chambres']; ?></span>
            <span class="spec-label">Chambres</span>
          </div>
          <div class="spec-card">
            <i class="fa-solid fa-bath spec-icon"></i>
            <span class="spec-val"><?php echo $bien['nb_salles_bain']; ?></span>
            <span class="spec-label">Salles de bain</span>
          </div>
          <div class="spec-card">
            <i class="fa-solid fa-users spec-icon"></i>
            <span class="spec-val"><?php echo $bien['nb_personnes']; ?></span>
            <span class="spec-label">Capacité</span>
          </div>
        </div>

        <h3 class="detail-section-title"><i class="fa-solid fa-circle-check"></i> Équipements inclus</h3>
        <div class="specs-grid" style="margin-bottom: 2rem;">
          <?php if ($bien['wifi']): ?>
            <div class="spec-card">
              <i class="fa-solid fa-wifi spec-icon" style="color:var(--success);"></i>
              <span class="spec-val">Oui</span>
              <span class="spec-label">Wi-Fi</span>
            </div>
          <?php endif; ?>
          <?php if ($bien['piscine']): ?>
            <div class="spec-card">
              <i class="fa-solid fa-water-ladder spec-icon" style="color:var(--success);"></i>
              <span class="spec-val">Oui</span>
              <span class="spec-label">Piscine</span>
            </div>
          <?php endif; ?>
          <?php if ($bien['parking']): ?>
            <div class="spec-card">
              <i class="fa-solid fa-square-parking spec-icon" style="color:var(--success);"></i>
              <span class="spec-val">Oui</span>
              <span class="spec-label">Parking</span>
            </div>
          <?php endif; ?>
          <?php if ($bien['climatisation']): ?>
            <div class="spec-card">
              <i class="fa-solid fa-snowflake spec-icon" style="color:var(--success);"></i>
              <span class="spec-val">Oui</span>
              <span class="spec-label">Clim</span>
            </div>
          <?php endif; ?>
          <?php if ($bien['cuisine']): ?>
            <div class="spec-card">
              <i class="fa-solid fa-kitchen-set spec-icon" style="color:var(--success);"></i>
              <span class="spec-val">Oui</span>
              <span class="spec-label">Cuisine</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="detail-divider"></div>

        <!-- Description -->
        <h3 class="detail-section-title"><i class="fa-solid fa-align-left"></i> Description</h3>
        <div class="detail-description" style="margin-bottom: 2rem;">
          <p><?php echo nl2br(htmlspecialchars($bien['description'])); ?></p>
        </div>

        <div class="detail-divider"></div>

        <!-- Localisation Map -->
        <h3 class="detail-section-title"><i class="fa-solid fa-map-location-dot"></i> Localisation</h3>
        <p class="detail-description" style="margin-bottom:1rem;"><?php echo htmlspecialchars($bien['adresse'] . ', ' . $bien['ville']); ?></p>
        <div class="contact-map-wrapper" style="height:300px; margin-top:0; border-radius:var(--radius-md);">
          <!-- Styled premium dark map placeholder iframe -->
          <iframe 
            src="https://maps.google.com/maps?q=<?php echo urlencode($bien['adresse'] . ' ' . $bien['ville'] . ' Maroc'); ?>&t=&z=14&ie=UTF8&iwloc=&output=embed" 
            allowfullscreen>
          </iframe>
        </div>

        <div class="detail-divider"></div>

        <!-- Owner card -->
        <h3 class="detail-section-title"><i class="fa-solid fa-user-tie"></i> Propriétaire</h3>
        <div class="host-card" style="margin-bottom: 2rem;">
          <img src="<?php echo $bien['host_avatar'] ? htmlspecialchars($bien['host_avatar']) : '../image/logo.png'; ?>" 
               alt="Avatar propriétaire" class="host-avatar"
               onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($bien['prenom'] . ' ' . $bien['nom']); ?>&background=141424&color=f5a623';">
          <div class="host-info">
            <h4><?php echo htmlspecialchars($bien['prenom'] . ' ' . $bien['nom']); ?></h4>
            <p>Hôte certifié • Réside à <?php echo htmlspecialchars($bien['host_ville'] ?: $bien['ville']); ?></p>
          </div>
        </div>

        <div class="detail-divider"></div>

        <!-- Reviews section -->
        <h3 class="detail-section-title"><i class="fa-solid fa-star"></i> Avis des voyageurs</h3>
        <div class="reviews-summary">
          <div class="rating-big-box">
            <span class="rating-big-num"><?php echo number_format($bien['note_moyenne'], 1); ?></span>
            <div class="stars-row">
              <?php
              $full_stars = floor($bien['note_moyenne']);
              for ($i = 0; $i < 5; $i++) {
                  if ($i < $full_stars) {
                      echo '<i class="fa-solid fa-star"></i>';
                  } else {
                      echo '<i class="fa-regular fa-star"></i>';
                  }
              }
              ?>
            </div>
            <span class="avis-score-label"><?php echo $bien['nb_avis']; ?> voyageurs ont laissé leur avis</span>
          </div>
          <div style="display:flex; flex-direction:column; justify-content:center;">
            <p class="detail-description" style="font-size:0.9rem;">
              Tous les avis proviennent de voyageurs ayant réservé et séjourné dans ce logement. Les avis sont modérés par notre système.
            </p>
          </div>
        </div>

        <!-- Avis list -->
        <div class="reviews-list">
          <?php if (empty($reviews)): ?>
            <p class="detail-description" style="font-style:italic;">Aucun avis pour l'instant. Soyez le premier à partager votre expérience !</p>
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
            <span class="booking-price-amount"><?php echo number_format($bien['prix_nuit'], 0, ',', ' '); ?> DH</span>
            <span class="booking-price-label">/ nuit</span>
          </div>
          <div>
            <span style="font-size: 0.82rem; color: var(--text-secondary);">
              <i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Dispo
            </span>
          </div>
        </div>

        <form action="reservation.php" method="GET" id="booking-sidebar-form">
          <input type="hidden" name="type" value="bien">
          <input type="hidden" name="id" value="<?php echo $bien['id']; ?>">

          <div class="booking-dates-box">
            <div class="booking-date-field">
              <span class="booking-date-label">Arrivée</span>
              <input type="date" name="date_debut" id="date_debut" class="booking-date-input" required 
                     min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="booking-date-field">
              <span class="booking-date-label">Départ</span>
              <input type="date" name="date_fin" id="date_fin" class="booking-date-input" required
                     min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
          </div>

          <div class="booking-pricing-breakdown" id="pricing-breakdown" style="display:none;">
            <div class="breakdown-row">
              <span id="price-nci"><?php echo number_format($bien['prix_nuit'], 0); ?> DH × <span id="days-count">0</span> nuits</span>
              <span id="price-subtotal">0 DH</span>
            </div>
            <div class="breakdown-row">
              <span>Frais de service (5%)</span>
              <span id="price-fees">0 DH</span>
            </div>
            <div class="breakdown-row total">
              <span>Total</span>
              <span id="price-total">0 DH</span>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full btn-lg">
            <i class="fa-solid fa-credit-card"></i> Réserver maintenant
          </button>
        </form>

        <div class="occupancy-calendar-info">
          <span class="occupancy-dot"></span>
          <span>Des dates de réservation sont bloquées.</span>
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
  // Calendar and pricing dynamic updates
  const prixNuit = <?php echo (float)$bien['prix_nuit']; ?>;
  const dateDebutInput = document.getElementById('date_debut');
  const dateFinInput = document.getElementById('date_fin');
  const pricingBreakdown = document.getElementById('pricing-breakdown');
  const daysCount = document.getElementById('days-count');
  const subtotalLabel = document.getElementById('price-subtotal');
  const feesLabel = document.getElementById('price-fees');
  const totalLabel = document.getElementById('price-total');

  // Disable dates already booked
  try {
    const res = await fetch(`../php/api/reservations.php?type=bien&id=<?php echo $bien['id']; ?>`);
    const data = await res.json();
    if (data.occupied_ranges) {
      // Date restriction logic
      const blockDates = (inputEl) => {
        inputEl.addEventListener('input', () => {
          const selected = new Date(inputEl.value);
          const isOccupied = data.occupied_ranges.some(range => {
            const start = new Date(range.from);
            const end = new Date(range.to);
            return selected >= start && selected <= end;
          });
          if (isOccupied) {
            window.showToast("Ce jour est déjà réservé !", "warning", "Date indisponible");
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
        window.showToast("La date de départ doit être après l'arrivée !", "warning", "Erreur de dates");
        dateFinInput.value = '';
        pricingBreakdown.style.display = 'none';
        return;
      }

      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays > 0) {
        const subtotal = diffDays * prixNuit;
        const fees = Math.round(subtotal * 0.05);
        const total = subtotal + fees;

        daysCount.textContent = diffDays;
        subtotalLabel.textContent = subtotal.toLocaleString('fr-FR') + ' DH';
        feesLabel.textContent = fees.toLocaleString('fr-FR') + ' DH';
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
