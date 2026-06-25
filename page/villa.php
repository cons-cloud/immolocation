<?php
session_start();
include '../includes/config.php';

$ville    = $_GET['ville']    ?? '';
$prix_max = $_GET['prix_max'] ?? '';
$chambres = $_GET['chambres'] ?? '';
$piscine  = isset($_GET['piscine']) ? 1 : null;

$where = "WHERE b.statut='actif' AND b.type_bien='villa'";
if ($ville)    $where .= " AND b.ville LIKE '%" . mysqli_real_escape_string($conn, $ville) . "%'";
if ($prix_max) $where .= " AND b.prix_nuit <= " . (int)$prix_max;
if ($chambres) $where .= " AND b.nb_chambres >= " . (int)$chambres;
if ($piscine)  $where .= " AND b.piscine = 1";

$sort = match($_GET['sort'] ?? 'note') {
  'prix_asc'  => 'ORDER BY b.prix_nuit ASC',
  'prix_desc' => 'ORDER BY b.prix_nuit DESC',
  'recent'    => 'ORDER BY b.date_creation DESC',
  default     => 'ORDER BY b.note_moyenne DESC',
};

$per_page = 9; $page = max(1, (int)($_GET['page'] ?? 1)); $offset = ($page-1)*$per_page;
$total_q = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM biens b $where"))['c'] ?? 0;
$total_pages = ceil($total_q / $per_page);
$biens = mysqli_query($conn,"SELECT b.*,u.prenom,u.nom FROM biens b JOIN utilisateurs u ON b.proprietaire_id=u.id $where $sort LIMIT $per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Location de Villas Luxe au Maroc — Immo-Location</title>
  <meta name="description" content="Villas de luxe à louer avec piscine, jardin et vue panoramique. Réservation instantanée.">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/listing.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<main style="padding-top:80px;">
  <div class="page-hero">
    <div class="container">
      <div class="section-badge reveal"><i class="fa-solid fa-house-chimney"></i> Luxe</div>
      <h1 class="page-hero-title reveal delay-1">Location de <span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">Villas</span></h1>
      <p class="page-hero-subtitle reveal delay-2">Villas luxueuses avec piscine, jardin et espace privatif pour des séjours d'exception</p>
      <nav class="breadcrumb reveal delay-3">
        <a href="acceuil.php">Accueil</a><span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span><span>Villas</span>
      </nav>
    </div>
  </div>

  <div class="container" style="padding-top:3rem;padding-bottom:4rem;">
    <div class="listing-layout">
      <aside class="filters-sidebar reveal-left">
        <div class="sidebar-header">
          <h3><i class="fa-solid fa-sliders"></i> Filtres</h3>
          <a href="villa.php" class="btn btn-secondary btn-sm">Réinitialiser</a>
        </div>
        <form method="GET">
          <div class="filter-section">
            <h4 class="filter-title">Localisation</h4>
            <div style="position:relative;">
              <i class="fa-solid fa-location-dot" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--primary);"></i>
              <input type="text" name="ville" value="<?php echo htmlspecialchars($ville); ?>" placeholder="Ville..." class="form-control" style="padding-left:2.5rem;">
            </div>
          </div>
          <div class="filter-section">
            <h4 class="filter-title">Prix max / nuit</h4>
            <div class="price-slider-wrapper">
              <input type="range" name="prix_max" min="500" max="10000" step="100" value="<?php echo $prix_max ?: 10000; ?>" class="price-slider" id="price-range">
              <div class="price-labels"><span>500 DH</span><span id="price-display"><?php echo $prix_max ?: 10000; ?> DH</span></div>
            </div>
          </div>
          <div class="filter-section">
            <h4 class="filter-title">Chambres (min)</h4>
            <div class="room-btns">
              <?php foreach ([2,3,4,5,6] as $nb): ?>
              <button type="button" class="room-btn <?php echo $chambres==$nb?'active':''; ?>" onclick="this.closest('form').querySelector('[name=chambres]').value=<?php echo $nb; ?>;document.querySelectorAll('.room-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active');"><?php echo $nb; ?>+</button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="chambres" value="<?php echo $chambres; ?>">
          </div>
          <div class="filter-section">
            <h4 class="filter-title">Équipements</h4>
            <div class="checkbox-list">
              <label class="checkbox-item"><input type="checkbox" name="piscine" value="1" <?php echo $piscine?'checked':''; ?>><span><i class="fa-solid fa-water-ladder"></i> Piscine</span></label>
              <label class="checkbox-item"><input type="checkbox" name="wifi" value="1" <?php echo isset($_GET['wifi'])?'checked':''; ?>><span><i class="fa-solid fa-wifi"></i> WiFi</span></label>
              <label class="checkbox-item"><input type="checkbox" name="parking" value="1" <?php echo isset($_GET['parking'])?'checked':''; ?>><span><i class="fa-solid fa-square-parking"></i> Parking</span></label>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-full"><i class="fa-solid fa-magnifying-glass"></i> Filtrer</button>
        </form>
      </aside>

      <div class="listing-results">
        <div class="results-toolbar">
          <span class="results-count"><strong><?php echo $total_q; ?></strong> villa<?php echo $total_q>1?'s':''; ?> trouvée<?php echo $total_q>1?'s':''; ?></span>
          <div class="results-actions">
            <select class="form-control" style="width:auto;" onchange="window.location.href=this.value">
              <option value="?sort=note" <?php echo ($_GET['sort']??'note')==='note'?'selected':''; ?>>Mieux notées</option>
              <option value="?sort=prix_asc" <?php echo ($_GET['sort']??'')==='prix_asc'?'selected':''; ?>>Prix croissant</option>
              <option value="?sort=prix_desc" <?php echo ($_GET['sort']??'')==='prix_desc'?'selected':''; ?>>Prix décroissant</option>
            </select>
          </div>
        </div>

        <div class="listing-grid">
          <?php if ($biens && mysqli_num_rows($biens) > 0): ?>
            <?php while ($bien = mysqli_fetch_assoc($biens)): ?>
            <div class="listing-card reveal">
              <div class="listing-card-image">
                <img src="<?php echo htmlspecialchars($bien['image_principale'] ?? '../image/villa.jpg'); ?>" alt="<?php echo htmlspecialchars($bien['titre']); ?>" loading="lazy">
                <div class="listing-card-badge"><span class="badge badge-primary">Villa</span><?php if ($bien['piscine']): ?><span class="badge badge-info" style="margin-left:4px;"><i class="fa-solid fa-water-ladder"></i></span><?php endif; ?></div>
                <button class="listing-card-fav" data-type="bien" data-id="<?php echo $bien['id']; ?>"><i class="fa-regular fa-heart"></i></button>
              </div>
              <div class="listing-card-body">
                <h3 class="listing-card-title"><?php echo htmlspecialchars($bien['titre']); ?></h3>
                <div class="listing-card-location"><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($bien['ville']); ?></span></div>
                <div class="listing-card-features">
                  <span class="listing-feat"><i class="fa-solid fa-bed"></i> <?php echo $bien['nb_chambres']; ?> ch.</span>
                  <span class="listing-feat"><i class="fa-solid fa-shower"></i> <?php echo $bien['nb_salles_bain']; ?> sdb</span>
                  <span class="listing-feat"><i class="fa-solid fa-expand"></i> <?php echo $bien['surface']; ?>m²</span>
                  <span class="listing-feat"><i class="fa-solid fa-user-group"></i> <?php echo $bien['nb_personnes']; ?></span>
                </div>
                <div class="listing-card-footer">
                  <div class="listing-price"><span class="listing-price-amount"><?php echo number_format($bien['prix_nuit'],0,',',' '); ?> DH</span><span class="listing-price-unit">/ nuit</span></div>
                  <div class="listing-rating"><i class="fa-solid fa-star"></i> <?php echo number_format($bien['note_moyenne'],1); ?> (<?php echo $bien['nb_avis']; ?>)</div>
                </div>
                <a href="detail-bien.php?id=<?php echo $bien['id']; ?>" class="btn btn-primary w-full mt-md"><i class="fa-solid fa-eye"></i> Voir la villa</a>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <!-- Demo cards -->
            <?php $demos=[['villa.jpg','Villa avec Piscine Privée',5,3,350,'Meknès',1200],['villa.webp','Villa Vue Panoramique',4,2,250,'Meknès',900]]; foreach($demos as $i=>$d): ?>
            <div class="listing-card reveal delay-<?php echo $i; ?>">
              <div class="listing-card-image">
                <img src="../image/<?php echo $d[0]; ?>" alt="<?php echo $d[1]; ?>" loading="lazy">
                <div class="listing-card-badge"><span class="badge badge-primary">Villa</span></div>
                <button class="listing-card-fav" data-type="bien" data-id="<?php echo $i+1; ?>"><i class="fa-regular fa-heart"></i></button>
              </div>
              <div class="listing-card-body">
                <h3 class="listing-card-title"><?php echo $d[1]; ?></h3>
                <div class="listing-card-location"><i class="fa-solid fa-location-dot"></i><span><?php echo $d[5]; ?></span></div>
                <div class="listing-card-features">
                  <span class="listing-feat"><i class="fa-solid fa-bed"></i> <?php echo $d[2]; ?> ch.</span>
                  <span class="listing-feat"><i class="fa-solid fa-shower"></i> <?php echo $d[3]; ?> sdb</span>
                  <span class="listing-feat"><i class="fa-solid fa-expand"></i> <?php echo $d[4]; ?>m²</span>
                </div>
                <div class="listing-card-footer">
                  <div class="listing-price"><span class="listing-price-amount"><?php echo number_format($d[6],0,',',' '); ?> DH</span><span class="listing-price-unit">/ nuit</span></div>
                  <div class="listing-rating"><i class="fa-solid fa-star"></i> 4.8 (24)</div>
                </div>
                <a href="detail-bien.php?id=<?php echo $i+1; ?>" class="btn btn-primary w-full mt-md"><i class="fa-solid fa-eye"></i> Voir la villa</a>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php for ($p=1;$p<=$total_pages;$p++): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$p])); ?>" class="page-btn <?php echo $p===$page?'active':''; ?>"><?php echo $p; ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
<?php include '../includes/footer.php'; ?>
<script>
const slider = document.getElementById('price-range');
const display = document.getElementById('price-display');
if (slider&&display) { slider.addEventListener('input',()=>{display.textContent=parseInt(slider.value).toLocaleString('fr-FR')+' DH';}); }
</script>
</body></html>