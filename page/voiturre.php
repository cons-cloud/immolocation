<?php
session_start();
include '../includes/config.php';

$ville    = $_GET['ville']    ?? '';
$prix_max = $_GET['prix_max'] ?? '';
$marque   = $_GET['marque']   ?? '';
$boite    = $_GET['boite']    ?? '';
$carburant = $_GET['carburant'] ?? '';

$where = "WHERE v.statut='actif'";
if ($ville)     $where .= " AND v.ville LIKE '%".mysqli_real_escape_string($conn,$ville)."%'";
if ($prix_max)  $where .= " AND v.prix_jour <= ".(int)$prix_max;
if ($marque)    $where .= " AND v.marque LIKE '%".mysqli_real_escape_string($conn,$marque)."%'";
if ($boite)     $where .= " AND v.boite = '".mysqli_real_escape_string($conn,$boite)."'";
if ($carburant) $where .= " AND v.carburant = '".mysqli_real_escape_string($conn,$carburant)."'";

$sort = match($_GET['sort'] ?? 'note') {
  'prix_asc'  => 'ORDER BY v.prix_jour ASC',
  'prix_desc' => 'ORDER BY v.prix_jour DESC',
  default     => 'ORDER BY v.note_moyenne DESC',
};

$per_page = 8; $page = max(1,(int)($_GET['page']??1)); $offset=($page-1)*$per_page;
$total_q = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM voitures v $where"))['c'] ?? 0;
$total_pages = ceil($total_q/$per_page);
$voitures = mysqli_query($conn,"SELECT v.*,u.prenom FROM voitures v JOIN utilisateurs u ON v.proprietaire_id=u.id $where $sort LIMIT $per_page OFFSET $offset");

// Get distinct brands
$marques_q = mysqli_query($conn,"SELECT DISTINCT marque FROM voitures WHERE statut='actif' ORDER BY marque");
$marques = [];
while ($m = mysqli_fetch_assoc($marques_q)) $marques[] = $m['marque'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../image/favicon.png">
  <link rel="apple-touch-icon" href="../image/favicon.png"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Location de Voitures au Maroc — Immo-Location</title>
  <meta name="description" content="Louer une voiture au Maroc. Flotte premium récente, assurée et entretenue. Réservation en ligne.">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/listing.css">
  <link rel="stylesheet" href="../css/voiture.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<main style="padding-top:80px;">
  <div class="page-hero">
    <div class="container">
      <div class="section-badge reveal"><i class="fa-solid fa-car"></i> Mobilité Premium</div>
      <h1 class="page-hero-title reveal delay-1">Location de <span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">Voitures</span></h1>
      <p class="page-hero-subtitle reveal delay-2">Véhicules récents, assurés et entretenus pour tous vos déplacements au Maroc</p>
      <nav class="breadcrumb reveal delay-3">
        <a href="acceuil.php">Accueil</a><span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span><span>Voitures</span>
      </nav>
    </div>
  </div>

  <div class="container" style="padding-top:3rem;padding-bottom:4rem;">
    <div class="listing-layout">
      <!-- Sidebar -->
      <aside class="filters-sidebar reveal-left">
        <div class="sidebar-header">
          <h3><i class="fa-solid fa-sliders"></i> Filtres</h3>
          <a href="voiturre.php" class="btn btn-secondary btn-sm">Réinitialiser</a>
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
            <h4 class="filter-title">Prix max / jour</h4>
            <div class="price-slider-wrapper">
              <input type="range" name="prix_max" min="100" max="2000" step="50" value="<?php echo $prix_max ?: 2000; ?>" class="price-slider" id="price-range">
              <div class="price-labels"><span>100 DH</span><span id="price-display"><?php echo $prix_max ?: 2000; ?> DH</span></div>
            </div>
          </div>

          <div class="filter-section">
            <h4 class="filter-title">Marque</h4>
            <select name="marque" class="form-control">
              <option value="">Toutes les marques</option>
              <?php if (empty($marques)): foreach (['Volkswagen','Dacia','Mercedes','Toyota','Renault','BMW'] as $m): ?>
              <option value="<?php echo $m; ?>" <?php echo $marque===$m?'selected':''; ?>><?php echo $m; ?></option>
              <?php endforeach; else: foreach ($marques as $m): ?>
              <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $marque===$m?'selected':''; ?>><?php echo htmlspecialchars($m); ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>

          <div class="filter-section">
            <h4 class="filter-title">Boîte de vitesse</h4>
            <div class="checkbox-list">
              <label class="checkbox-item"><input type="radio" name="boite" value="" <?php echo !$boite?'checked':''; ?>><span>Toutes</span></label>
              <label class="checkbox-item"><input type="radio" name="boite" value="automatique" <?php echo $boite==='automatique'?'checked':''; ?>><span><i class="fa-solid fa-gear"></i> Automatique</span></label>
              <label class="checkbox-item"><input type="radio" name="boite" value="manuelle" <?php echo $boite==='manuelle'?'checked':''; ?>><span><i class="fa-solid fa-gear"></i> Manuelle</span></label>
            </div>
          </div>

          <div class="filter-section">
            <h4 class="filter-title">Carburant</h4>
            <div class="checkbox-list">
              <label class="checkbox-item"><input type="radio" name="carburant" value="" <?php echo !$carburant?'checked':''; ?>><span>Tous</span></label>
              <label class="checkbox-item"><input type="radio" name="carburant" value="essence" <?php echo $carburant==='essence'?'checked':''; ?>><span><i class="fa-solid fa-gas-pump"></i> Essence</span></label>
              <label class="checkbox-item"><input type="radio" name="carburant" value="diesel" <?php echo $carburant==='diesel'?'checked':''; ?>><span><i class="fa-solid fa-gas-pump"></i> Diesel</span></label>
              <label class="checkbox-item"><input type="radio" name="carburant" value="hybride" <?php echo $carburant==='hybride'?'checked':''; ?>><span><i class="fa-solid fa-leaf"></i> Hybride</span></label>
              <label class="checkbox-item"><input type="radio" name="carburant" value="electrique" <?php echo $carburant==='electrique'?'checked':''; ?>><span><i class="fa-solid fa-bolt"></i> Électrique</span></label>
            </div>
          </div>

          <div class="filter-section">
            <h4 class="filter-title">Options</h4>
            <div class="checkbox-list">
              <label class="checkbox-item"><input type="checkbox" name="climatisation" value="1" <?php echo isset($_GET['climatisation'])?'checked':''; ?>><span><i class="fa-solid fa-snowflake"></i> Climatisation</span></label>
              <label class="checkbox-item"><input type="checkbox" name="gps" value="1" <?php echo isset($_GET['gps'])?'checked':''; ?>><span><i class="fa-solid fa-map-location-dot"></i> GPS</span></label>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full"><i class="fa-solid fa-magnifying-glass"></i> Filtrer</button>
        </form>
      </aside>

      <!-- Results -->
      <div class="listing-results">
        <div class="results-toolbar">
          <span class="results-count"><strong><?php echo $total_q; ?></strong> voiture<?php echo $total_q>1?'s':''; ?> trouvée<?php echo $total_q>1?'s':''; ?></span>
          <div class="results-actions">
            <select class="form-control" style="width:auto;" onchange="window.location.href=this.value">
              <option value="?sort=note">Mieux notées</option>
              <option value="?sort=prix_asc">Prix croissant</option>
              <option value="?sort=prix_desc">Prix décroissant</option>
            </select>
          </div>
        </div>

        <div class="cars-listing-grid">
          <?php if ($voitures && mysqli_num_rows($voitures) > 0): ?>
            <?php while ($car = mysqli_fetch_assoc($voitures)): ?>
            <div class="car-listing-card reveal">
              <div class="car-listing-image">
                <img src="<?php echo htmlspecialchars($car['image_principale'] ?? '../image/v1.jpg'); ?>" alt="<?php echo htmlspecialchars($car['marque'].' '.$car['modele']); ?>" loading="lazy">
                <button class="listing-card-fav" data-type="voiture" data-id="<?php echo $car['id']; ?>"><i class="fa-regular fa-heart"></i></button>
                <div class="car-badges">
                  <span class="badge badge-accent"><?php echo ucfirst($car['carburant']); ?></span>
                  <?php if ($car['disponible']): ?><span class="badge badge-success">Dispo</span><?php endif; ?>
                </div>
              </div>
              <div class="car-listing-body">
                <div class="car-listing-header">
                  <div>
                    <h3 class="car-listing-title"><?php echo htmlspecialchars($car['marque'].' '.$car['modele']); ?></h3>
                    <div class="listing-card-location"><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($car['ville']); ?></span></div>
                  </div>
                  <div class="listing-rating"><i class="fa-solid fa-star"></i><?php echo number_format($car['note_moyenne'],1); ?></div>
                </div>
                <div class="car-specs">
                  <div class="car-spec"><i class="fa-solid fa-calendar"></i><span><?php echo $car['annee']; ?></span></div>
                  <div class="car-spec"><i class="fa-solid fa-users"></i><span><?php echo $car['nb_places']; ?> places</span></div>
                  <div class="car-spec"><i class="fa-solid fa-gear"></i><span><?php echo ucfirst($car['boite']); ?></span></div>
                  <div class="car-spec"><i class="fa-solid fa-gas-pump"></i><span><?php echo ucfirst($car['carburant']); ?></span></div>
                  <?php if ($car['climatisation']): ?><div class="car-spec"><i class="fa-solid fa-snowflake"></i><span>Clim</span></div><?php endif; ?>
                  <?php if ($car['gps']): ?><div class="car-spec"><i class="fa-solid fa-map-location-dot"></i><span>GPS</span></div><?php endif; ?>
                </div>
                <div class="car-listing-footer">
                  <div class="listing-price">
                    <span class="listing-price-amount"><?php echo number_format($car['prix_jour'],0,',',' '); ?> DH</span>
                    <span class="listing-price-unit">/ jour</span>
                  </div>
                  <div style="display:flex;gap:0.5rem;">
                    <a href="detail-voiture.php?id=<?php echo $car['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a>
                    <a href="reservation.php?type=voiture&id=<?php echo $car['id']; ?>" class="btn btn-primary btn-sm">Réserver</a>
                  </div>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          <?php else: ?>
            <?php
            $demo_cars=[
              ['v1.jpg','Volkswagen','Golf 8',2023,'Gris',500,'Essence','Automatique',5,1,1,4.9,15],
              ['dacia.jpg','Dacia','Duster',2022,'Blanc',350,'Diesel','Manuelle',5,1,0,4.7,22],
              ['v2.jpg','Mercedes','Classe C',2024,'Noir',800,'Hybride','Automatique',5,1,1,5.0,8],
              ['v3.jpg','Toyota','Yaris',2021,'Bleu',280,'Essence','Automatique',5,1,0,4.6,31],
            ];
            foreach ($demo_cars as $i=>$c): ?>
            <div class="car-listing-card reveal delay-<?php echo $i%4; ?>">
              <div class="car-listing-image">
                <img src="../image/<?php echo $c[0]; ?>" alt="<?php echo $c[1].' '.$c[2]; ?>" loading="lazy">
                <button class="listing-card-fav" data-type="voiture" data-id="<?php echo $i+1; ?>"><i class="fa-regular fa-heart"></i></button>
                <div class="car-badges"><span class="badge badge-accent"><?php echo $c[5]; ?></span><span class="badge badge-success">Dispo</span></div>
              </div>
              <div class="car-listing-body">
                <div class="car-listing-header">
                  <div>
                    <h3 class="car-listing-title"><?php echo $c[1].' '.$c[2]; ?></h3>
                    <div class="listing-card-location"><i class="fa-solid fa-location-dot"></i><span>Meknès</span></div>
                  </div>
                  <div class="listing-rating"><i class="fa-solid fa-star"></i><?php echo $c[11]; ?></div>
                </div>
                <div class="car-specs">
                  <div class="car-spec"><i class="fa-solid fa-calendar"></i><span><?php echo $c[3]; ?></span></div>
                  <div class="car-spec"><i class="fa-solid fa-users"></i><span><?php echo $c[8]; ?> places</span></div>
                  <div class="car-spec"><i class="fa-solid fa-gear"></i><span><?php echo $c[7]; ?></span></div>
                  <div class="car-spec"><i class="fa-solid fa-gas-pump"></i><span><?php echo $c[6]; ?></span></div>
                  <?php if ($c[9]): ?><div class="car-spec"><i class="fa-solid fa-snowflake"></i><span>Clim</span></div><?php endif; ?>
                </div>
                <div class="car-listing-footer">
                  <div class="listing-price"><span class="listing-price-amount"><?php echo $c[5]; ?> DH</span><span class="listing-price-unit">/ jour</span></div>
                  <div style="display:flex;gap:0.5rem;">
                    <a href="detail-voiture.php?id=<?php echo $i+1; ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a>
                    <a href="reservation.php?type=voiture&id=<?php echo $i+1; ?>" class="btn btn-primary btn-sm">Réserver</a>
                  </div>
                </div>
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
const slider=document.getElementById('price-range');const display=document.getElementById('price-display');
if(slider&&display){slider.addEventListener('input',()=>{display.textContent=parseInt(slider.value).toLocaleString('fr-FR')+' DH';});}
</script>
</body></html>