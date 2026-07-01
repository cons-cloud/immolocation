<?php
$required_role = 'proprietaire';
include '../../includes/auth-check.php';
include '../../includes/config.php';

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'overview';

// Normalize DB image paths for display inside page/dash/ context
function imgPath(string $p, string $fallback = '../../image/apparte.jpg'): string {
    if (empty(trim($p))) return $fallback;
    // Stored as '../image/...' (relative to php/) → convert for page/dash/
    if (str_starts_with($p, '../image/'))    return '../../image/' . substr($p, strlen('../image/'));
    if (str_starts_with($p, '../../image/')) return $p;
    if (str_starts_with($p, 'image/'))       return '../../' . $p;
    return $p;
}

// Fetch owner's data per tab
$mes_biens = $mes_voitures = $mes_reservations = [];

if ($tab === 'biens') {
    $q = mysqli_query($conn, "SELECT * FROM biens WHERE proprietaire_id = $user_id ORDER BY date_creation DESC");
    while ($r = mysqli_fetch_assoc($q)) $mes_biens[] = $r;
} elseif ($tab === 'voitures') {
    $q = mysqli_query($conn, "SELECT * FROM voitures WHERE proprietaire_id = $user_id ORDER BY date_creation DESC");
    while ($r = mysqli_fetch_assoc($q)) $mes_voitures[] = $r;
} elseif ($tab === 'reservations') {
    $q = mysqli_query($conn, "
        SELECT r.*, u.prenom, u.nom, u.email, u.telephone,
               IF(r.type_reservation='bien', b.titre, CONCAT(v.marque,' ',v.modele)) as item_title
        FROM reservations r
        JOIN utilisateurs u ON r.client_id = u.id
        LEFT JOIN biens b ON r.bien_id = b.id
        LEFT JOIN voitures v ON r.voiture_id = v.id
        WHERE b.proprietaire_id = $user_id OR v.proprietaire_id = $user_id
        ORDER BY r.date_creation DESC
    ");
    while ($r = mysqli_fetch_assoc($q)) $mes_reservations[] = $r;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../../image/favicon.png">
  <link rel="apple-touch-icon" href="../../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Propriétaire — Immo-Location</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../../css/global.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<!-- Toast container (used by JS notifications) -->
<div id="toast-container"></div>

<main style="padding-top:2rem;padding-bottom:4rem;">

  <div class="container">
    <div class="dash-layout">

      <!-- Sidebar -->
      <aside class="dash-sidebar">
        <button class="dash-sidebar-close" id="dashSidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="dash-user-profile">
          <img class="dash-user-avatar"
               src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['prenom'].' '.$_SESSION['nom']); ?>&background=141424&color=f5a623"
               alt="Avatar">
          <div>
            <span class="dash-user-name"><?php echo htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']); ?></span>
            <div class="dash-user-role">Propriétaire</div>
          </div>
        </div>
        <nav class="dash-menu">
          <a href="proprio.php?tab=overview" class="dash-menu-link <?php echo $tab==='overview'?'active':''; ?>"><i class="fa-solid fa-chart-pie"></i> Vue d'ensemble</a>
          <a href="proprio.php?tab=biens" class="dash-menu-link <?php echo $tab==='biens'?'active':''; ?>"><i class="fa-solid fa-building"></i> Mes Hébergements</a>
          <a href="proprio.php?tab=voitures" class="dash-menu-link <?php echo $tab==='voitures'?'active':''; ?>"><i class="fa-solid fa-car"></i> Mes Voitures</a>
          <a href="proprio.php?tab=reservations" class="dash-menu-link <?php echo $tab==='reservations'?'active':''; ?>"><i class="fa-solid fa-receipt"></i> Réservations</a>
          <a href="../../php/logout.php" class="dash-menu-link" style="color:var(--danger);margin-top:2rem;"><i class="fa-solid fa-right-from-bracket" style="color:var(--danger);"></i> Déconnexion</a>
        </nav>
      </aside>

      <!-- Contenu -->
      <div class="dash-content">
        <!-- Mobile Toggle Bar -->
        <div class="dash-mobile-bar">
          <button class="dash-sidebar-toggle-btn" id="dashSidebarToggle">
            <i class="fa-solid fa-bars-staggered"></i> Menu Dashboard
          </button>
        </div>

        <?php if ($tab === 'overview'): ?>
        <div>
          <h1 class="dash-page-title">Bonjour, <?php echo htmlspecialchars($_SESSION['prenom']); ?> 👋</h1>
          <p class="dash-page-desc">Voici un aperçu de vos performances sur la plateforme.</p>
        </div>

        <div class="dash-stats-grid">
          <div class="stat-widget"><div class="stat-widget-info"><h3 id="s-biens">—</h3><p>Hébergements</p></div><div class="stat-widget-icon"><i class="fa-solid fa-building"></i></div></div>
          <div class="stat-widget"><div class="stat-widget-info"><h3 id="s-voitures">—</h3><p>Véhicules</p></div><div class="stat-widget-icon"><i class="fa-solid fa-car"></i></div></div>
          <div class="stat-widget"><div class="stat-widget-info"><h3 id="s-reservations">—</h3><p>Réservations</p></div><div class="stat-widget-icon"><i class="fa-solid fa-receipt"></i></div></div>
          <div class="stat-widget"><div class="stat-widget-info"><h3 id="s-earnings" style="color:var(--primary);">—</h3><p>Revenus (DH)</p></div><div class="stat-widget-icon" style="background:var(--primary-glow);color:var(--primary);"><i class="fa-solid fa-money-bill-wave"></i></div></div>
        </div>

        <div class="chart-card">
          <h4 class="chart-card-title">Revenus mensuels (DH)</h4>
          <div style="height: 250px; max-height: 40vw; min-height: 180px; position: relative;"><canvas id="earnings-chart"></canvas></div>
        </div>

        <div class="chart-card">
          <h4 class="chart-card-title">Actions rapides</h4>
          <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.5rem;">
            <a href="proprio.php?tab=biens" class="btn btn-secondary"><i class="fa-solid fa-building"></i> Gérer hébergements</a>
            <a href="proprio.php?tab=voitures" class="btn btn-secondary"><i class="fa-solid fa-car"></i> Gérer voitures</a>
            <a href="proprio.php?tab=reservations" class="btn btn-primary"><i class="fa-solid fa-receipt"></i> Voir réservations</a>
          </div>
        </div>

        <?php elseif ($tab === 'biens'): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
          <div>
            <h1 class="dash-page-title">Mes Hébergements</h1>
            <p class="dash-page-desc">Gérez vos appartements et villas publiés sur la plateforme.</p>
          </div>
          <button class="btn btn-primary btn-sm" onclick="openAddBienModal()"><i class="fa-solid fa-plus"></i> Ajouter un hébergement</button>
        </div>
        <div class="table-wrapper">
          <table class="dash-table">
            <thead><tr><th>Photo</th><th>Titre</th><th>Type</th><th>Ville</th><th>Prix/nuit</th><th>Note</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if (empty($mes_biens)): ?>
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucun hébergement publié pour le moment.</td></tr>
              <?php else: ?>
                <?php foreach ($mes_biens as $b): ?>
                <tr>
                  <td><img src="<?php echo htmlspecialchars(imgPath($b['image_principale'],'../../image/apparte.jpg')); ?>" style="width:60px;height:42px;object-fit:cover;border-radius:6px;" alt="img" onerror="this.src='../../image/apparte.jpg'"></td>
                  <td style="font-weight:600;color:var(--white);"><?php echo htmlspecialchars($b['titre']); ?></td>
                  <td style="text-transform:capitalize;"><?php echo $b['type_bien']; ?></td>
                  <td><?php echo htmlspecialchars($b['ville']); ?></td>
                  <td style="color:var(--primary);font-weight:700;"><?php echo number_format($b['prix_nuit'],0); ?> DH</td>
                  <td><i class="fa-solid fa-star" style="color:var(--primary);font-size:0.8rem;"></i> <?php echo number_format($b['note_moyenne'],1); ?> <span style="color:var(--text-muted);font-size:0.78rem;">(<?php echo $b['nb_avis']; ?>)</span></td>
                  <td><span class="badge <?php echo $b['statut']==='actif'?'badge-success':'badge-danger'; ?>"><?php echo $b['statut']; ?></span></td>
                  <td>
                    <div style="display:flex; gap:4px;">
                      <a href="../detail-bien.php?id=<?php echo $b['id']; ?>" class="btn btn-secondary btn-sm" title="Voir l'annonce publique"><i class="fa-solid fa-eye"></i></a>
                      <button class="btn btn-primary btn-sm" onclick="openEditBienModal(<?php echo $b['id']; ?>)" title="Modifier"><i class="fa-solid fa-pencil"></i></button>
                      <button class="btn btn-danger btn-sm" onclick="deleteListing(<?php echo $b['id']; ?>, 'bien')" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php elseif ($tab === 'voitures'): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
          <div>
            <h1 class="dash-page-title">Mes Véhicules</h1>
            <p class="dash-page-desc">Gérez votre flotte de véhicules disponibles à la location.</p>
          </div>
          <button class="btn btn-accent btn-sm" onclick="openAddVoitureModal()"><i class="fa-solid fa-plus"></i> Ajouter un véhicule</button>
        </div>
        <div class="table-wrapper">
          <table class="dash-table">
            <thead><tr><th>Photo</th><th>Véhicule</th><th>Boite</th><th>Carburant</th><th>Prix/jour</th><th>Note</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if (empty($mes_voitures)): ?>
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucun véhicule publié pour le moment.</td></tr>
              <?php else: ?>
                <?php foreach ($mes_voitures as $v): ?>
                <tr>
                  <td><img src="<?php echo htmlspecialchars(imgPath($v['image_principale'],'../../image/v1.jpg')); ?>" style="width:60px;height:42px;object-fit:cover;border-radius:6px;" alt="img" onerror="this.src='../../image/v1.jpg'"></td>
                  <td style="font-weight:600;color:var(--white);"><?php echo htmlspecialchars($v['marque'].' '.$v['modele'].' ('.$v['annee'].')'); ?></td>
                  <td style="text-transform:capitalize;"><?php echo $v['boite']; ?></td>
                  <td style="text-transform:capitalize;"><?php echo $v['carburant']; ?></td>
                  <td style="color:var(--primary);font-weight:700;"><?php echo number_format($v['prix_jour'],0); ?> DH</td>
                  <td><i class="fa-solid fa-star" style="color:var(--primary);font-size:0.8rem;"></i> <?php echo number_format($v['note_moyenne'],1); ?> <span style="color:var(--text-muted);font-size:0.78rem;">(<?php echo $v['nb_avis']; ?>)</span></td>
                  <td><span class="badge <?php echo $v['statut']==='actif'?'badge-success':'badge-danger'; ?>"><?php echo $v['statut']; ?></span></td>
                  <td>
                    <div style="display:flex; gap:4px;">
                      <a href="../detail-voiture.php?id=<?php echo $v['id']; ?>" class="btn btn-secondary btn-sm" title="Voir l'annonce publique"><i class="fa-solid fa-eye"></i></a>
                      <button class="btn btn-accent btn-sm" onclick="openEditVoitureModal(<?php echo $v['id']; ?>)" title="Modifier"><i class="fa-solid fa-pencil"></i></button>
                      <button class="btn btn-danger btn-sm" onclick="deleteListing(<?php echo $v['id']; ?>, 'voiture')" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php elseif ($tab === 'reservations'): ?>
        <div>
          <h1 class="dash-page-title">Réservations reçues</h1>
          <p class="dash-page-desc">Acceptez ou déclinez les demandes de réservation de vos clients.</p>
        </div>
        <div class="table-wrapper">
          <table class="dash-table">
            <thead><tr><th>Réf.</th><th>Client</th><th>Annonce</th><th>Dates</th><th>Total</th><th>Statut</th><th>Action</th></tr></thead>
            <tbody>
              <?php if (empty($mes_reservations)): ?>
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucune réservation reçue pour le moment.</td></tr>
              <?php else: ?>
                <?php foreach ($mes_reservations as $r): ?>
                <tr>
                  <td style="font-family:monospace;font-weight:700;color:var(--white);font-size:0.8rem;"><?php echo htmlspecialchars($r['numero_reservation']); ?></td>
                  <td>
                    <div style="font-weight:600;"><?php echo htmlspecialchars($r['prenom'].' '.$r['nom']); ?></div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);"><?php echo htmlspecialchars($r['telephone'] ?? ''); ?></div>
                  </td>
                  <td style="font-weight:500;"><?php echo htmlspecialchars($r['item_title']); ?></td>
                  <td style="font-size:0.82rem;">Du <?php echo date('d/m/y',strtotime($r['date_debut'])); ?><br>au <?php echo date('d/m/y',strtotime($r['date_fin'])); ?></td>
                  <td style="color:var(--primary);font-weight:700;"><?php echo number_format($r['prix_total'],0); ?> DH</td>
                  <td>
                    <span class="badge <?php echo $r['statut']==='confirmee'?'badge-success':($r['statut']==='annulee'?'badge-danger':'badge-warning'); ?>" id="rbadge-<?php echo $r['id']; ?>" style="text-transform:capitalize;">
                      <?php echo $r['statut']; ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($r['statut'] === 'en_attente'): ?>
                      <div style="display:flex;gap:4px;">
                        <button class="btn btn-success btn-sm" onclick="handleBooking(<?php echo $r['id']; ?>,'confirmee')"><i class="fa-solid fa-check"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="handleBooking(<?php echo $r['id']; ?>,'annulee')"><i class="fa-solid fa-xmark"></i></button>
                      </div>
                    <?php else: ?>
                      <span style="font-size:0.78rem;color:var(--text-muted);">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
<!-- Modal Bien (Hébergement) -->
<div class="dash-modal" id="modal-bien">
  <div class="dash-modal-container">
    <div class="dash-modal-header">
      <h3 class="dash-modal-title" id="modal-bien-title">Ajouter un hébergement</h3>
      <span class="dash-modal-close" onclick="closeModal('modal-bien')">&times;</span>
    </div>
    <form id="form-bien" onsubmit="submitListingForm(event, 'bien')">
      <input type="hidden" name="id" id="bien-id" value="0">
      <input type="hidden" name="type" value="bien">
      <input type="hidden" name="image_principale" id="bien-image_principale" value="">
      
      <div class="dash-modal-body">
        <div class="form-group">
          <label class="form-label">Titre de l'annonce *</label>
          <input type="text" name="titre" id="bien-titre" class="form-control" placeholder="Ex: Bel appartement moderne à Gueliz" required>
        </div>
        
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Type de bien *</label>
            <select name="type_bien" id="bien-type_bien" class="form-control">
              <option value="appartement">Appartement</option>
              <option value="villa">Villa</option>
              <option value="maison">Maison</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Prix par nuit (DH) *</label>
            <input type="number" name="prix_nuit" id="bien-prix_nuit" class="form-control" min="1" step="any" required>
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Ville *</label>
            <input type="text" name="ville" id="bien-ville" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Surface (m²)</label>
            <input type="number" name="surface" id="bien-surface" class="form-control" min="0">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Adresse complète</label>
          <input type="text" name="adresse" id="bien-adresse" class="form-control">
        </div>

        <div class="grid-3" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:1rem;">
          <div class="form-group">
            <label class="form-label">Chambres</label>
            <input type="number" name="nb_chambres" id="bien-nb_chambres" class="form-control" min="1" value="1">
          </div>
          <div class="form-group">
            <label class="form-label">Salles de bain</label>
            <input type="number" name="nb_salles_bain" id="bien-nb_salles_bain" class="form-control" min="1" value="1">
          </div>
          <div class="form-group">
            <label class="form-label">Voyageurs max</label>
            <input type="number" name="nb_personnes" id="bien-nb_personnes" class="form-control" min="1" value="2">
          </div>
        </div>

        <div class="form-group" style="margin-top:1rem;">
          <label class="form-label" style="margin-bottom:0.5rem; display:block;">Équipements inclus</label>
          <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.5rem;">
            <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary); cursor:pointer;">
              <input type="checkbox" name="wifi" id="bien-wifi"> Wifi gratuit
            </label>
            <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary); cursor:pointer;">
              <input type="checkbox" name="piscine" id="bien-piscine"> Piscine
            </label>
            <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary); cursor:pointer;">
              <input type="checkbox" name="parking" id="bien-parking"> Parking
            </label>
            <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary); cursor:pointer;">
              <input type="checkbox" name="climatisation" id="bien-climatisation"> Climatisation
            </label>
            <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary); cursor:pointer;">
              <input type="checkbox" name="cuisine" id="bien-cuisine"> Cuisine équipée
            </label>
          </div>
        </div>

        <div class="form-group" style="margin-top:1rem;">
          <label class="form-label">Description détaillée</label>
          <textarea name="description" id="bien-description" class="form-control" rows="4" placeholder="Décrivez votre hébergement, points forts, situation géographique..."></textarea>
        </div>

        <div class="form-group" style="margin-top:1rem;">
          <label class="form-label">Photo principale</label>
          <div style="display:flex; align-items:center; gap:1rem;">
            <div id="bien-image-preview" style="width:100px; height:70px; border-radius:6px; border:1px solid var(--border); overflow:hidden; background:var(--bg-secondary) url('../../image/apparte.jpg') no-repeat center/cover; flex-shrink:0;"></div>
            <div style="flex:1;">
              <input type="file" id="bien-image-file" class="form-control" accept="image/*" onchange="uploadImageFile(this, 'bien')">
              <p style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">JPG, PNG ou WEBP. Max 5 Mo.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="dash-modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-bien')">Annuler</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Voiture (Véhicule) -->
<div class="dash-modal" id="modal-voiture">
  <div class="dash-modal-container">
    <div class="dash-modal-header">
      <h3 class="dash-modal-title" id="modal-voiture-title">Ajouter un véhicule</h3>
      <span class="dash-modal-close" onclick="closeModal('modal-voiture')">&times;</span>
    </div>
    <form id="form-voiture" onsubmit="submitListingForm(event, 'voiture')">
      <input type="hidden" name="id" id="voiture-id" value="0">
      <input type="hidden" name="type" value="voiture">
      <input type="hidden" name="image_principale" id="voiture-image_principale" value="">
      
      <div class="dash-modal-body">
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Marque *</label>
            <input type="text" name="marque" id="voiture-marque" class="form-control" placeholder="Ex: Volkswagen" required>
          </div>
          <div class="form-group">
            <label class="form-label">Modèle *</label>
            <input type="text" name="modele" id="voiture-modele" class="form-control" placeholder="Ex: Golf 8" required>
          </div>
        </div>

        <div class="grid-3" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:1rem;">
          <div class="form-group">
            <label class="form-label">Année</label>
            <input type="number" name="annee" id="voiture-annee" class="form-control" min="2000" max="2027" value="2024">
          </div>
          <div class="form-group">
            <label class="form-label">Couleur</label>
            <input type="text" name="couleur" id="voiture-couleur" class="form-control" placeholder="Ex: Noir">
          </div>
          <div class="form-group">
            <label class="form-label">Places</label>
            <input type="number" name="nb_places" id="voiture-nb_places" class="form-control" min="1" value="5">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Prix par jour (DH) *</label>
            <input type="number" name="prix_jour" id="voiture-prix_jour" class="form-control" min="1" step="any" required>
          </div>
          <div class="form-group">
            <label class="form-label">Caution (DH)</label>
            <input type="number" name="caution" id="voiture-caution" class="form-control" min="0" value="0">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Ville *</label>
            <input type="text" name="ville" id="voiture-ville" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Kilométrage (km)</label>
            <input type="number" name="km" id="voiture-km" class="form-control" min="0" value="0">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Carburant</label>
            <select name="carburant" id="voiture-carburant" class="form-control">
              <option value="essence">Essence</option>
              <option value="diesel">Diesel</option>
              <option value="hybride">Hybride</option>
              <option value="electrique">Électrique</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Boite de vitesse</label>
            <select name="boite" id="voiture-boite" class="form-control">
              <option value="manuelle">Manuelle</option>
              <option value="automatique">Automatique</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Immatriculation</label>
          <input type="text" name="immatriculation" id="voiture-immatriculation" class="form-control" placeholder="Ex: 12345-A-60">
        </div>

        <div class="form-group" style="margin-top:1rem;">
          <label class="form-label" style="margin-bottom:0.5rem; display:block;">Options incluses</label>
          <div style="display:flex; gap: 2rem;">
            <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary); cursor:pointer;">
              <input type="checkbox" name="climatisation" id="voiture-climatisation" checked> Climatisation
            </label>
            <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.88rem; color:var(--text-secondary); cursor:pointer;">
              <input type="checkbox" name="gps" id="voiture-gps"> GPS intégré
            </label>
          </div>
        </div>

        <div class="form-group" style="margin-top:1rem;">
          <label class="form-label">Photo principale</label>
          <div style="display:flex; align-items:center; gap:1rem;">
            <div id="voiture-image-preview" style="width:100px; height:70px; border-radius:6px; border:1px solid var(--border); overflow:hidden; background:var(--bg-secondary) url('../../image/v1.jpg') no-repeat center/cover; flex-shrink:0;"></div>
            <div style="flex:1;">
              <input type="file" id="voiture-image-file" class="form-control" accept="image/*" onchange="uploadImageFile(this, 'voiture')">
              <p style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">JPG, PNG ou WEBP. Max 5 Mo.</p>
            </div>
          </div>
        </div>
      </div>
      <div class="dash-modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-voiture')">Annuler</button>
        <button type="submit" class="btn btn-accent"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>
</main>

<?php include '../../includes/footer-dash.php'; ?>
<script src="../../js/dashboard-proprio.js"></script>
</body>
</html>
