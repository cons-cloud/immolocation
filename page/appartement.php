<?php
session_start();
include '../includes/config.php';

// Filtres
$ville    = $_GET['ville']    ?? '';
$prix_max = $_GET['prix_max'] ?? '';
$chambres = $_GET['chambres'] ?? '';
$type_bien = 'appartement';

// Build query
$where = "WHERE b.statut='actif' AND b.type_bien='appartement'";
if ($ville)    $where .= " AND b.ville LIKE '%" . mysqli_real_escape_string($conn, $ville) . "%'";
if ($prix_max) $where .= " AND b.prix_nuit <= " . (int)$prix_max;
if ($chambres) $where .= " AND b.nb_chambres >= " . (int)$chambres;

$sort = match($_GET['sort'] ?? 'note') {
  'prix_asc'  => 'ORDER BY b.prix_nuit ASC',
  'prix_desc' => 'ORDER BY b.prix_nuit DESC',
  'recent'    => 'ORDER BY b.date_creation DESC',
  default     => 'ORDER BY b.note_moyenne DESC',
};

// Pagination
$per_page = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$total_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM biens b $where"))['c'] ?? 0;
$total_pages = ceil($total_q / $per_page);

$biens = mysqli_query($conn, "SELECT b.*, u.prenom, u.nom FROM biens b JOIN utilisateurs u ON b.proprietaire_id = u.id $where $sort LIMIT $per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Location d'Appartements à Meknès & Maroc — Immo-Location</title>
  <meta name="description" content="Découvrez notre sélection d'appartements à louer au Maroc. Logements modernes, prix transparents, réservation en ligne.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/listing.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 80px;">

  <!-- Page Hero -->
  <div class="page-hero">
    <div class="container">
      <div class="section-badge reveal"><i class="fa-solid fa-building"></i> Logements</div>
      <h1 class="page-hero-title reveal delay-1">
        Location d'<span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">Appartements</span>
      </h1>
      <p class="page-hero-subtitle reveal delay-2">Des appartements modernes et confortables dans les meilleures localisations au Maroc</p>
      <nav class="breadcrumb reveal delay-3">
        <a href="acceuil.php">Accueil</a>
        <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span>
        <span>Appartements</span>
      </nav>
    </div>
  </div>

  <div class="container" style="padding-top: 3rem; padding-bottom: 4rem;">

    <!-- Filters + Results layout -->
    <div class="listing-layout">

      <!-- Sidebar Filters -->
      <aside class="filters-sidebar reveal-left">
        <div class="sidebar-header">
          <h3><i class="fa-solid fa-sliders"></i> Filtres</h3>
          <a href="appartement.php" class="btn btn-secondary btn-sm">Réinitialiser</a>
        </div>

        <form method="GET" action="" class="filters-form">
          <div class="filter-section">
            <h4 class="filter-title">Localisation</h4>
            <div class="form-group">
              <div style="position:relative;">
                <i class="fa-solid fa-location-dot" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--primary);"></i>
                <input type="text" name="ville" value="<?php echo htmlspecialchars($ville); ?>" 
                       placeholder="Ville, quartier..." class="form-control" style="padding-left:2.5rem;">
              </div>
            </div>
          </div>

          <div class="filter-section">
            <h4 class="filter-title">Prix max / nuit</h4>
            <div class="price-slider-wrapper">
              <input type="range" name="prix_max" min="100" max="5000" step="50"
                     value="<?php echo $prix_max ?: 5000; ?>" class="price-slider" id="price-range">
              <div class="price-labels">
                <span>100 DH</span>
                <span id="price-display"><?php echo $prix_max ?: 5000; ?> DH</span>
              </div>
            </div>
          </div>

          <div class="filter-section">
            <h4 class="filter-title">Chambres (min)</h4>
            <div class="room-btns">
              <?php foreach ([1,2,3,4,5] as $nb): ?>
              <button type="button" class="room-btn <?php echo $chambres == $nb ? 'active' : ''; ?>" 
                      onclick="this.closest('form').querySelector('[name=chambres]').value=<?php echo $nb; ?>; document.querySelectorAll('.room-btn').forEach(b=>b.classList.remove('active')); this.classList.add('active');">
                <?php echo $nb; ?>+
              </button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="chambres" value="<?php echo $chambres; ?>">
          </div>

          <div class="filter-section">
            <h4 class="filter-title">Équipements</h4>
            <div class="checkbox-list">
              <label class="checkbox-item">
                <input type="checkbox" name="wifi" value="1" <?php echo isset($_GET['wifi']) ? 'checked' : ''; ?>>
                <span><i class="fa-solid fa-wifi"></i> WiFi</span>
              </label>
              <label class="checkbox-item">
                <input type="checkbox" name="parking" value="1" <?php echo isset($_GET['parking']) ? 'checked' : ''; ?>>
                <span><i class="fa-solid fa-square-parking"></i> Parking</span>
              </label>
              <label class="checkbox-item">
                <input type="checkbox" name="clim" value="1" <?php echo isset($_GET['clim']) ? 'checked' : ''; ?>>
                <span><i class="fa-solid fa-snowflake"></i> Climatisation</span>
              </label>
            </div>
          </div>

          <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort'] ?? 'note'); ?>">
          <button type="submit" class="btn btn-primary w-full">
            <i class="fa-solid fa-magnifying-glass"></i> Appliquer
          </button>
        </form>
      </aside>

      <!-- Results -->
      <div class="listing-results">

        <!-- Toolbar -->
        <div class="results-toolbar">
          <span class="results-count">
            <strong><?php echo $total_q; ?></strong> appartement<?php echo $total_q > 1 ? 's' : ''; ?> trouvé<?php echo $total_q > 1 ? 's' : ''; ?>
          </span>
          <div class="results-actions">
            <select class="form-control" style="width:auto;" onchange="window.location.href=this.value">
              <?php 
              $base_params = http_build_query(array_filter(['ville'=>$ville,'prix_max'=>$prix_max,'chambres'=>$chambres]));
              ?>
              <option value="?<?php echo $base_params; ?>&sort=note" <?php echo ($_GET['sort']??'note')==='note'?'selected':''; ?>>Mieux notés</option>
              <option value="?<?php echo $base_params; ?>&sort=prix_asc" <?php echo ($_GET['sort']??'')==='prix_asc'?'selected':''; ?>>Prix croissant</option>
              <option value="?<?php echo $base_params; ?>&sort=prix_desc" <?php echo ($_GET['sort']??'')==='prix_desc'?'selected':''; ?>>Prix décroissant</option>
              <option value="?<?php echo $base_params; ?>&sort=recent" <?php echo ($_GET['sort']??'')==='recent'?'selected':''; ?>>Plus récents</option>
            </select>
            <div class="view-btns">
              <button class="view-btn active" id="grid-view" title="Vue grille"><i class="fa-solid fa-grip"></i></button>
              <button class="view-btn" id="list-view" title="Vue liste"><i class="fa-solid fa-list"></i></button>
            </div>
          </div>
        </div>

        <!-- Grid -->
        <div class="listing-grid" id="results-grid">
          <?php if ($biens && mysqli_num_rows($biens) > 0): ?>
            <?php while ($bien = mysqli_fetch_assoc($biens)): ?>
            <div class="listing-card reveal">
              <div class="listing-card-image">
                <img src="<?php echo htmlspecialchars($bien['image_principale'] ?? '../image/apparte.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($bien['titre']); ?>"
                     loading="lazy">
                <div class="listing-card-badge">
                  <?php if ($bien['disponible']): ?>
                    <span class="badge badge-success">Disponible</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Indisponible</span>
                  <?php endif; ?>
                </div>
                <button class="listing-card-fav" data-type="bien" data-id="<?php echo $bien['id']; ?>">
                  <i class="fa-regular fa-heart"></i>
                </button>
              </div>
              <div class="listing-card-body">
                <h3 class="listing-card-title"><?php echo htmlspecialchars($bien['titre']); ?></h3>
                <div class="listing-card-location">
                  <i class="fa-solid fa-location-dot"></i>
                  <span><?php echo htmlspecialchars($bien['adresse'] ? $bien['adresse'].', '.$bien['ville'] : $bien['ville']); ?></span>
                </div>
                <div class="listing-card-features">
                  <span class="listing-feat"><i class="fa-solid fa-bed"></i> <?php echo $bien['nb_chambres']; ?> ch.</span>
                  <span class="listing-feat"><i class="fa-solid fa-shower"></i> <?php echo $bien['nb_salles_bain']; ?> sdb</span>
                  <span class="listing-feat"><i class="fa-solid fa-expand"></i> <?php echo $bien['surface']; ?>m²</span>
                  <span class="listing-feat"><i class="fa-solid fa-user-group"></i> <?php echo $bien['nb_personnes']; ?> pers.</span>
                </div>
                <div class="listing-feat-icons">
                  <?php if ($bien['wifi']): ?><span class="feat-chip"><i class="fa-solid fa-wifi"></i></span><?php endif; ?>
                  <?php if ($bien['parking']): ?><span class="feat-chip"><i class="fa-solid fa-square-parking"></i></span><?php endif; ?>
                  <?php if ($bien['piscine']): ?><span class="feat-chip"><i class="fa-solid fa-water-ladder"></i></span><?php endif; ?>
                  <?php if ($bien['climatisation']): ?><span class="feat-chip"><i class="fa-solid fa-snowflake"></i></span><?php endif; ?>
                </div>
                <div class="listing-card-footer">
                  <div class="listing-price">
                    <span class="listing-price-amount"><?php echo number_format($bien['prix_nuit'], 0, ',', ' '); ?> DH</span>
                    <span class="listing-price-unit">/ nuit</span>
                  </div>
                  <div class="listing-rating">
                    <i class="fa-solid fa-star"></i>
                    <span><?php echo number_format($bien['note_moyenne'], 1); ?></span>
                    <span style="color:var(--text-muted);">(<?php echo $bien['nb_avis']; ?>)</span>
                  </div>
                </div>
                <a href="detail-bien.php?id=<?php echo $bien['id']; ?>" class="btn btn-primary w-full mt-md">
                  <i class="fa-solid fa-eye"></i> Voir le bien
                </a>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Empty state -->
            <div class="empty-state" style="grid-column:1/-1;">
              <div class="empty-icon"><i class="fa-solid fa-building-circle-xmark"></i></div>
              <h3>Aucun appartement trouvé</h3>
              <p>Essayez de modifier vos filtres de recherche.</p>
              <a href="appartement.php" class="btn btn-primary">Voir tous les appartements</a>
            </div>
          <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>" class="page-btn">
              <i class="fa-solid fa-chevron-left"></i>
            </a>
          <?php endif; ?>

          <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>" 
               class="page-btn <?php echo $p === $page ? 'active' : ''; ?>">
              <?php echo $p; ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $total_pages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>" class="page-btn">
              <i class="fa-solid fa-chevron-right"></i>
            </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      </div><!-- /listing-results -->
    </div><!-- /listing-layout -->
  </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
// Price range slider
const slider = document.getElementById('price-range');
const display = document.getElementById('price-display');
if (slider && display) {
  slider.addEventListener('input', () => {
    display.textContent = parseInt(slider.value).toLocaleString('fr-FR') + ' DH';
  });
}

// View toggle
document.getElementById('grid-view')?.addEventListener('click', function() {
  document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
  this.classList.add('active');
  document.getElementById('results-grid').className = 'listing-grid';
});

document.getElementById('list-view')?.addEventListener('click', function() {
  document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
  this.classList.add('active');
  document.getElementById('results-grid').className = 'listing-list';
});
</script>
</body>
</html>