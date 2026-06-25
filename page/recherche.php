<?php
session_start();
include '../includes/config.php';

// ── Paramètres ──────────────────────────────────────────────
$q        = trim($_GET['q']        ?? '');
$type     = $_GET['type']          ?? 'tous';   // tous | bien | voiture
$ville    = trim($_GET['ville']    ?? '');
$prix_max = (int)($_GET['prix_max'] ?? 0);
$sort     = $_GET['sort']          ?? 'note';

// ── Pagination ───────────────────────────────────────────────
$per_page = 9;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

// ── Construction des requêtes ────────────────────────────────
$biens    = [];
$voitures = [];
$total_b  = 0;
$total_v  = 0;

$search_like = '%' . mysqli_real_escape_string($conn, $q) . '%';
$ville_like  = '%' . mysqli_real_escape_string($conn, $ville) . '%';

// Order clause
$order_b = match($sort) {
    'prix_asc'  => 'ORDER BY b.prix_nuit ASC',
    'prix_desc' => 'ORDER BY b.prix_nuit DESC',
    'recent'    => 'ORDER BY b.date_creation DESC',
    default     => 'ORDER BY b.note_moyenne DESC',
};
$order_v = match($sort) {
    'prix_asc'  => 'ORDER BY v.prix_jour ASC',
    'prix_desc' => 'ORDER BY v.prix_jour DESC',
    'recent'    => 'ORDER BY v.date_creation DESC',
    default     => 'ORDER BY v.note_moyenne DESC',
};

if ($type === 'tous' || $type === 'bien') {
    $where_b = "WHERE b.statut='actif'";
    if ($q)        $where_b .= " AND (b.titre LIKE '$search_like' OR b.description LIKE '$search_like' OR b.ville LIKE '$search_like')";
    if ($ville)    $where_b .= " AND b.ville LIKE '$ville_like'";
    if ($prix_max) $where_b .= " AND b.prix_nuit <= $prix_max";

    $total_b = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM biens b $where_b"))['c'] ?? 0;
    $limit_b = $type === 'tous' ? "LIMIT 6" : "LIMIT $per_page OFFSET $offset";
    $res_b   = mysqli_query($conn, "SELECT b.*, u.prenom, u.nom FROM biens b JOIN utilisateurs u ON b.proprietaire_id=u.id $where_b $order_b $limit_b");
    while ($r = mysqli_fetch_assoc($res_b)) $biens[] = $r;
}

if ($type === 'tous' || $type === 'voiture') {
    $where_v = "WHERE v.statut='actif'";
    if ($q)        $where_v .= " AND (v.marque LIKE '$search_like' OR v.modele LIKE '$search_like' OR v.ville LIKE '$search_like')";
    if ($ville)    $where_v .= " AND v.ville LIKE '$ville_like'";
    if ($prix_max) $where_v .= " AND v.prix_jour <= $prix_max";

    $total_v = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM voitures v $where_v"))['c'] ?? 0;
    $limit_v = $type === 'tous' ? "LIMIT 4" : "LIMIT $per_page OFFSET $offset";
    $res_v   = mysqli_query($conn, "SELECT v.*, u.prenom FROM voitures v JOIN utilisateurs u ON v.proprietaire_id=u.id $where_v $order_v $limit_v");
    while ($r = mysqli_fetch_assoc($res_v)) $voitures[] = $r;
}

$total = $total_b + $total_v;
$total_pages = $type !== 'tous' ? ceil(($type === 'bien' ? $total_b : $total_v) / $per_page) : 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recherche<?php echo $q ? ' — '.htmlspecialchars($q) : ''; ?> — Immo-Location</title>
  <meta name="description" content="Recherchez parmi nos biens immobiliers et voitures disponibles à la location au Maroc.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/listing.css">
  <style>
    .search-hero {
      background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-card) 100%);
      border-bottom: 1px solid var(--border);
      padding: 6rem 0 3rem;
    }
    .search-hero-form {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      margin-top: 1.5rem;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 1rem;
    }
    .search-hero-input {
      flex: 1;
      min-width: 200px;
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 0.75rem 1rem 0.75rem 2.75rem;
      color: var(--white);
      font-size: 1rem;
      font-family: inherit;
      position: relative;
    }
    .search-hero-input:focus { outline: none; border-color: var(--primary); }
    .input-wrap { position: relative; flex: 1; min-width: 200px; }
    .input-wrap .fa { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--primary); }
    .type-tabs { display: flex; gap: 0.5rem; }
    .type-tab {
      padding: 0.65rem 1.25rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--border);
      background: var(--bg-secondary);
      color: var(--text-secondary);
      font-family: inherit;
      font-size: 0.88rem;
      cursor: pointer;
      transition: all 0.2s;
      display: flex; align-items: center; gap: 0.4rem;
    }
    .type-tab.active, .type-tab:hover {
      background: var(--primary);
      color: var(--bg);
      border-color: var(--primary);
    }
    .section-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .section-title-row h2 { font-size: 1.4rem; font-weight: 700; color: var(--white); }
    .results-header { margin-bottom: 2rem; }
    .results-count-badge {
      display: inline-flex; align-items: center; gap: 0.4rem;
      background: var(--primary-glow); border: 1px solid rgba(245,166,35,0.3);
      color: var(--primary); padding: 0.35rem 1rem; border-radius: 100px;
      font-size: 0.85rem; font-weight: 600;
    }
    .no-results {
      text-align: center; padding: 5rem 2rem;
      color: var(--text-muted);
    }
    .no-results i { font-size: 4rem; opacity: 0.3; margin-bottom: 1rem; display: block; }
    .no-results h2 { font-size: 1.4rem; color: var(--white); margin-bottom: 0.5rem; }
  </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 80px;">

  <!-- ── Search Hero ───────────────────────────────────── -->
  <div class="search-hero">
    <div class="container">
      <div class="section-badge reveal"><i class="fa-solid fa-magnifying-glass"></i> Recherche</div>
      <h1 class="page-hero-title reveal delay-1">
        Trouvez votre <span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">location idéale</span>
      </h1>
      <p class="page-hero-subtitle reveal delay-2">Biens immobiliers et voitures disponibles au Maroc</p>

      <form class="search-hero-form reveal delay-3" method="GET" action="" id="search-form">
        <!-- Type tabs -->
        <div class="type-tabs">
          <button type="button" class="type-tab <?php echo $type === 'tous'    ? 'active' : ''; ?>" data-type="tous">
            <i class="fa-solid fa-grid-2"></i> Tous
          </button>
          <button type="button" class="type-tab <?php echo $type === 'bien'    ? 'active' : ''; ?>" data-type="bien">
            <i class="fa-solid fa-building"></i> Biens
          </button>
          <button type="button" class="type-tab <?php echo $type === 'voiture' ? 'active' : ''; ?>" data-type="voiture">
            <i class="fa-solid fa-car"></i> Voitures
          </button>
        </div>
        <input type="hidden" name="type" id="type-input" value="<?php echo htmlspecialchars($type); ?>">

        <!-- Mot-clé -->
        <div class="input-wrap">
          <i class="fa fa-magnifying-glass"></i>
          <input type="text" name="q" class="search-hero-input" placeholder="Appartement, villa, Golf, Meknès..."
                 value="<?php echo htmlspecialchars($q); ?>" autocomplete="off">
        </div>

        <!-- Ville -->
        <div class="input-wrap">
          <i class="fa fa-location-dot"></i>
          <input type="text" name="ville" class="search-hero-input" placeholder="Ville ou région"
                 value="<?php echo htmlspecialchars($ville); ?>">
        </div>

        <!-- Prix max -->
        <div class="input-wrap" style="min-width:180px;">
          <i class="fa fa-tag"></i>
          <input type="number" name="prix_max" class="search-hero-input" placeholder="Prix max (DH)"
                 value="<?php echo $prix_max ?: ''; ?>" min="0" step="50">
        </div>

        <!-- Tri -->
        <select name="sort" class="form-control" style="min-width:160px;">
          <option value="note"      <?php echo $sort==='note'     ?'selected':''; ?>>Mieux notés</option>
          <option value="prix_asc"  <?php echo $sort==='prix_asc' ?'selected':''; ?>>Prix croissant</option>
          <option value="prix_desc" <?php echo $sort==='prix_desc'?'selected':''; ?>>Prix décroissant</option>
          <option value="recent"    <?php echo $sort==='recent'   ?'selected':''; ?>>Plus récents</option>
        </select>

        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-magnifying-glass"></i> Rechercher
        </button>
      </form>
    </div>
  </div>

  <div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <!-- ── Results Header ──────────────────────────────── -->
    <div class="results-header">
      <?php if ($q || $ville || $prix_max): ?>
        <span class="results-count-badge">
          <i class="fa-solid fa-circle-check"></i>
          <?php echo $total; ?> résultat<?php echo $total > 1 ? 's' : ''; ?> trouvé<?php echo $total > 1 ? 's' : ''; ?>
          <?php if ($q): ?> pour "<strong><?php echo htmlspecialchars($q); ?></strong>"<?php endif; ?>
        </span>
      <?php else: ?>
        <span class="results-count-badge">
          <i class="fa-solid fa-fire"></i> Toutes nos annonces
        </span>
      <?php endif; ?>
    </div>

    <?php if ($total === 0): ?>
      <!-- ── No Results ─────────────────────────────────── -->
      <div class="no-results">
        <i class="fa-solid fa-magnifying-glass-minus"></i>
        <h2>Aucun résultat trouvé</h2>
        <p>Essayez d'autres mots-clés ou élargissez votre recherche.</p>
        <a href="recherche.php" class="btn btn-primary" style="margin-top:1.5rem;">
          <i class="fa-solid fa-rotate-left"></i> Réinitialiser la recherche
        </a>
      </div>

    <?php else: ?>

      <!-- ── BIENS ────────────────────────────────────── -->
      <?php if (!empty($biens)): ?>
      <div style="margin-bottom: 3rem;">
        <div class="section-title-row">
          <h2><i class="fa-solid fa-building" style="color:var(--primary);margin-right:0.5rem;"></i> Biens immobiliers
            <span style="font-size:0.85rem;font-weight:400;color:var(--text-muted);margin-left:0.5rem;">(<?php echo $total_b; ?>)</span>
          </h2>
          <?php if ($type === 'tous' && $total_b > 6): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'bien'])); ?>" class="btn btn-secondary btn-sm">
              Voir tout <i class="fa-solid fa-arrow-right"></i>
            </a>
          <?php endif; ?>
        </div>

        <div class="listing-grid">
          <?php foreach ($biens as $bien): ?>
          <div class="listing-card reveal">
            <div class="listing-card-image">
              <img src="<?php echo htmlspecialchars($bien['image_principale'] ?? '../image/villa.webp'); ?>"
                   alt="<?php echo htmlspecialchars($bien['titre']); ?>" loading="lazy">
              <div class="listing-card-badge">
                <span class="badge badge-primary"><?php echo ucfirst($bien['type_bien']); ?></span>
              </div>
              <button class="listing-card-fav" data-type="bien" data-id="<?php echo $bien['id']; ?>">
                <i class="fa-regular fa-heart"></i>
              </button>
            </div>
            <div class="listing-card-body">
              <h3 class="listing-card-title"><?php echo htmlspecialchars($bien['titre']); ?></h3>
              <div class="listing-card-location">
                <i class="fa-solid fa-location-dot"></i>
                <span><?php echo htmlspecialchars($bien['ville']); ?></span>
              </div>
              <div class="listing-card-features">
                <span class="listing-feat"><i class="fa-solid fa-bed"></i> <?php echo $bien['nb_chambres']; ?> ch.</span>
                <span class="listing-feat"><i class="fa-solid fa-shower"></i> <?php echo $bien['nb_salles_bain']; ?> sdb</span>
                <span class="listing-feat"><i class="fa-solid fa-expand"></i> <?php echo $bien['surface']; ?>m²</span>
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
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── VOITURES ─────────────────────────────────── -->
      <?php if (!empty($voitures)): ?>
      <div>
        <div class="section-title-row">
          <h2><i class="fa-solid fa-car" style="color:var(--accent);margin-right:0.5rem;"></i> Voitures
            <span style="font-size:0.85rem;font-weight:400;color:var(--text-muted);margin-left:0.5rem;">(<?php echo $total_v; ?>)</span>
          </h2>
          <?php if ($type === 'tous' && $total_v > 4): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['type' => 'voiture'])); ?>" class="btn btn-secondary btn-sm">
              Voir tout <i class="fa-solid fa-arrow-right"></i>
            </a>
          <?php endif; ?>
        </div>

        <div class="cars-grid">
          <?php foreach ($voitures as $car): ?>
          <div class="car-card reveal">
            <div class="car-card-image">
              <img src="<?php echo htmlspecialchars($car['image_principale'] ?? '../image/v1.jpg'); ?>"
                   alt="<?php echo htmlspecialchars($car['marque'].' '.$car['modele']); ?>" loading="lazy">
              <button class="listing-card-fav" data-type="voiture" data-id="<?php echo $car['id']; ?>">
                <i class="fa-regular fa-heart"></i>
              </button>
              <div class="car-badge"><span class="badge badge-accent"><?php echo ucfirst($car['carburant']); ?></span></div>
            </div>
            <div class="car-card-body">
              <h3 class="car-title"><?php echo htmlspecialchars($car['marque'].' '.$car['modele']); ?></h3>
              <div class="car-meta">
                <span><i class="fa-solid fa-calendar"></i> <?php echo $car['annee']; ?></span>
                <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($car['ville']); ?></span>
                <span><i class="fa-solid fa-users"></i> <?php echo $car['nb_places']; ?> places</span>
              </div>
              <div class="car-features">
                <?php if ($car['climatisation']): ?><span class="car-feat"><i class="fa-solid fa-snowflake"></i> Clim</span><?php endif; ?>
                <?php if ($car['gps']): ?><span class="car-feat"><i class="fa-solid fa-map-location-dot"></i> GPS</span><?php endif; ?>
                <span class="car-feat"><i class="fa-solid fa-gear"></i> <?php echo ucfirst($car['boite']); ?></span>
              </div>
              <div class="car-footer">
                <div class="listing-price">
                  <span class="listing-price-amount"><?php echo number_format($car['prix_jour'], 0, ',', ' '); ?> DH</span>
                  <span class="listing-price-unit">/ jour</span>
                </div>
                <a href="detail-voiture.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-sm">
                  Réserver <i class="fa-solid fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── Pagination (mode filtré) ───────────────────── -->
      <?php if ($type !== 'tous' && $total_pages > 1): ?>
      <div class="pagination" style="margin-top:3rem;">
        <?php if ($page > 1): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>" class="page-btn">
            <i class="fa-solid fa-chevron-left"></i>
          </a>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"
             class="page-btn <?php echo $p === $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>" class="page-btn">
            <i class="fa-solid fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
// Type tabs
document.querySelectorAll('.type-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.type-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('type-input').value = tab.dataset.type;
  });
});
</script>
</body>
</html>
