<?php
$required_role = 'admin';
include '../../includes/auth-check.php';
include '../../includes/config.php';

$tab = $_GET['tab'] ?? 'overview';

// Normalize DB image paths for display inside page/dash/ context
function imgPath(string $p, string $fallback = '../../image/apparte.jpg'): string {
    if (empty(trim($p))) return $fallback;
    if (str_starts_with($p, '../image/'))    return '../../image/' . substr($p, strlen('../image/'));
    if (str_starts_with($p, '../../image/')) return $p;
    if (str_starts_with($p, 'image/'))       return '../../' . $p;
    return $p;
}

// Queries based on selected tab
$users_list = [];
$listings_list = [];
$bookings_list = [];

if ($tab === 'users') {
    $q = mysqli_query($conn, "SELECT id, prenom, nom, email, telephone, type_compte, statut, date_creation FROM utilisateurs ORDER BY date_creation DESC");
    while ($r = mysqli_fetch_assoc($q)) { $users_list[] = $r; }
} elseif ($tab === 'annonces') {
    // Combine biens and voitures with images
    $q1 = mysqli_query($conn, "SELECT id, titre as title, 'bien' as type, type_bien as subtype, prix_nuit as price, statut, image_principale, date_creation FROM biens ORDER BY date_creation DESC");
    while ($r = mysqli_fetch_assoc($q1)) { $listings_list[] = $r; }
    
    $q2 = mysqli_query($conn, "SELECT id, CONCAT(marque, ' ', modele) as title, 'voiture' as type, boite as subtype, prix_jour as price, statut, image_principale, date_creation FROM voitures ORDER BY date_creation DESC");
    while ($r = mysqli_fetch_assoc($q2)) { $listings_list[] = $r; }
} elseif ($tab === 'reservations') {
    $q = mysqli_query($conn, "
        SELECT r.*, u.prenom, u.nom, 
               IF(r.type_reservation='bien', b.titre, CONCAT(v.marque, ' ', v.modele)) as item_title
        FROM reservations r
        JOIN utilisateurs u ON r.client_id = u.id
        LEFT JOIN biens b ON r.bien_id = b.id AND r.type_reservation='bien'
        LEFT JOIN voitures v ON r.voiture_id = v.id AND r.type_reservation='voiture'
        ORDER BY r.date_creation DESC
    ");
    while ($r = mysqli_fetch_assoc($q)) { $bookings_list[] = $r; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../../image/favicon.png">
  <link rel="apple-touch-icon" href="../../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — Immo-Location</title>
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

<main style="padding-top: 2rem; padding-bottom: 4rem;">

  <div class="container">
    
    <div class="dash-layout">
      
      <!-- Sidebar Navigation -->
      <aside class="dash-sidebar">
        <button class="dash-sidebar-close" id="dashSidebarClose"><i class="fa-solid fa-xmark"></i></button>
        <div class="dash-user-profile">
          <img class="dash-user-avatar" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>&background=141424&color=f5a623" alt="Avatar Admin">
          <div class="dash-user-info">
            <span class="dash-user-name"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <div class="dash-user-role">Administrateur</div>
          </div>
        </div>

        <nav class="dash-menu">
          <a href="admin.php?tab=overview" class="dash-menu-link <?php echo $tab === 'overview' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> Vue d'ensemble
          </a>
          <a href="admin.php?tab=users" class="dash-menu-link <?php echo $tab === 'users' ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Utilisateurs
          </a>
          <a href="admin.php?tab=annonces" class="dash-menu-link <?php echo $tab === 'annonces' ? 'active' : ''; ?>">
            <i class="fa-solid fa-rectangle-list"></i> Annonces
          </a>
          <a href="admin.php?tab=reservations" class="dash-menu-link <?php echo $tab === 'reservations' ? 'active' : ''; ?>">
            <i class="fa-solid fa-receipt"></i> Réservations
          </a>
          <a href="../../php/logout.php" class="dash-menu-link" style="color:var(--danger); margin-top:2rem;">
            <i class="fa-solid fa-right-from-bracket" style="color:var(--danger);"></i> Déconnexion
          </a>
        </nav>
      </aside>

      <!-- Main Workspace -->
      <div class="dash-content">
        <!-- Mobile Toggle Bar -->
        <div class="dash-mobile-bar">
          <button class="dash-sidebar-toggle-btn" id="dashSidebarToggle">
            <i class="fa-solid fa-bars-staggered"></i> Menu Dashboard
          </button>
        </div>
        
        <!-- Tab: Overview -->
        <?php if ($tab === 'overview'): ?>
          <div>
            <h1 class="dash-page-title">Vue d'ensemble</h1>
            <p class="dash-page-desc">Consultez les performances et indicateurs clés de la plateforme en temps réel.</p>
          </div>

          <!-- Stats Widgets Grid -->
          <div class="dash-stats-grid">
            <div class="stat-widget">
              <div class="stat-widget-info">
                <h3 id="stat-users">0</h3>
                <p>Utilisateurs</p>
              </div>
              <div class="stat-widget-icon"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-widget">
              <div class="stat-widget-info">
                <h3 id="stat-biens">0</h3>
                <p>Hébergements</p>
              </div>
              <div class="stat-widget-icon"><i class="fa-solid fa-building"></i></div>
            </div>
            <div class="stat-widget">
              <div class="stat-widget-info">
                <h3 id="stat-voitures">0</h3>
                <p>Véhicules</p>
              </div>
              <div class="stat-widget-icon"><i class="fa-solid fa-car"></i></div>
            </div>
            <div class="stat-widget">
              <div class="stat-widget-info">
                <h3 id="stat-reservations">0</h3>
                <p>Réservations</p>
              </div>
              <div class="stat-widget-icon"><i class="fa-solid fa-receipt"></i></div>
            </div>
          </div>

          <!-- Charts -->
          <div class="dash-charts-grid">
            <div class="chart-card">
              <h4 class="chart-card-title">Courbe des revenus (DH)</h4>
              <div style="height: 250px; max-height: 40vw; min-height: 180px; position: relative;">
                <canvas id="earnings-chart"></canvas>
              </div>
            </div>
            <div class="chart-card">
              <h4 class="chart-card-title">Type de réservations</h4>
              <div style="height: 250px; max-height: 40vw; min-height: 180px; position: relative; display: flex; justify-content: center; align-items: center;">
                <canvas id="types-chart"></canvas>
              </div>
            </div>
          </div>

        <!-- Tab: Users -->
        <?php elseif ($tab === 'users'): ?>
          <div>
            <h1 class="dash-page-title">Gestion des Utilisateurs</h1>
            <p class="dash-page-desc">Activez, suspendez ou gérez les comptes de la plateforme.</p>
          </div>

          <div class="table-wrapper">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Utilisateur</th>
                  <th>Téléphone</th>
                  <th>Rôle</th>
                  <th>Date création</th>
                  <th>Statut</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users_list as $u): ?>
                  <tr>
                    <td>
                      <div class="table-user-cell">
                        <img class="table-user-img" src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['prenom'] . ' ' . $u['nom']); ?>&background=141424&color=f5a623" alt="User">
                        <div>
                          <div class="table-user-name"><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></div>
                          <div class="table-user-email"><?php echo htmlspecialchars($u['email']); ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($u['telephone'] ?: 'Non renseigné'); ?></td>
                    <td>
                      <span class="badge <?php echo $u['type_compte'] === 'admin' ? 'badge-primary' : ($u['type_compte'] === 'proprietaire' ? 'badge-accent' : 'badge-info'); ?>" style="text-transform: capitalize;">
                        <?php echo $u['type_compte']; ?>
                      </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($u['date_creation'])); ?></td>
                    <td>
                      <span class="badge <?php echo $u['statut'] === 'actif' ? 'badge-success' : 'badge-danger'; ?>" id="status-badge-user-<?php echo $u['id']; ?>" style="text-transform: capitalize;">
                        <?php echo $u['statut']; ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                        <button onclick="toggleUserStatus(<?php echo $u['id']; ?>, '<?php echo $u['statut']; ?>')" class="btn btn-secondary btn-sm" id="status-btn-user-<?php echo $u['id']; ?>">
                          <?php echo $u['statut'] === 'actif' ? 'Suspendre' : 'Activer'; ?>
                        </button>
                      <?php else: ?>
                        <span style="font-size:0.8rem;color:var(--text-muted);">Vous</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        <!-- Tab: Listings -->
        <?php elseif ($tab === 'annonces'): ?>
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem;">
          <div>
            <h1 class="dash-page-title">Gestion des Annonces</h1>
            <p class="dash-page-desc">Validez, modifiez ou ajoutez les hébergements et voitures de la plateforme.</p>
          </div>
          <div style="display:flex; gap:0.5rem;">
            <button class="btn btn-primary btn-sm" onclick="openAddBienModal()"><i class="fa-solid fa-plus"></i> Hébergement</button>
            <button class="btn btn-accent btn-sm" onclick="openAddVoitureModal()"><i class="fa-solid fa-plus"></i> Véhicule</button>
          </div>
        </div>

          <div class="table-wrapper">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Photo</th>
                  <th>Titre</th>
                  <th>Type</th>
                  <th>Catégorie</th>
                  <th>Tarif unitaire</th>
                  <th>Statut</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($listings_list as $l): ?>
                  <tr>
                    <td><img src="<?php echo htmlspecialchars(imgPath($l['image_principale'] ?? '', $l['type']==='voiture' ? '../../image/v1.jpg' : '../../image/apparte.jpg')); ?>" style="width:60px;height:42px;object-fit:cover;border-radius:6px;" alt="img" onerror="this.src='../../image/apparte.jpg'"></td>
                    <td style="font-weight:600; color:var(--white);"><?php echo htmlspecialchars($l['title']); ?></td>
                    <td>
                      <span class="badge <?php echo $l['type'] === 'bien' ? 'badge-primary' : 'badge-accent'; ?>" style="text-transform: uppercase;">
                        <?php echo $l['type']; ?>
                      </span>
                    </td>
                    <td style="text-transform: capitalize;"><?php echo htmlspecialchars($l['subtype']); ?></td>
                    <td><?php echo number_format($l['price'], 0); ?> DH</td>
                    <td>
                      <span class="badge <?php echo $l['statut'] === 'actif' ? 'badge-success' : 'badge-danger'; ?>" id="status-badge-listing-<?php echo $l['type']; ?>-<?php echo $l['id']; ?>" style="text-transform: capitalize;">
                        <?php echo $l['statut']; ?>
                      </span>
                    </td>
                    <td>
                      <div style="display:flex; gap:4px; align-items:center;">
                        <button onclick="toggleListingStatus(<?php echo $l['id']; ?>, '<?php echo $l['type']; ?>', '<?php echo $l['statut']; ?>')" class="btn btn-secondary btn-sm" id="status-btn-listing-<?php echo $l['type']; ?>-<?php echo $l['id']; ?>">
                          <?php echo $l['statut'] === 'actif' ? 'Désactiver' : 'Activer'; ?>
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="openEditListingAdmin(<?php echo $l['id']; ?>, '<?php echo $l['type']; ?>')" title="Modifier" style="<?php echo $l['type'] === 'voiture' ? 'background:var(--accent); border-color:var(--accent);' : ''; ?>"><i class="fa-solid fa-pencil"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteListing(<?php echo $l['id']; ?>, '<?php echo $l['type']; ?>')" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        <!-- Tab: Reservations -->
        <?php elseif ($tab === 'reservations'): ?>
          <div>
            <h1 class="dash-page-title">Gestion des Réservations</h1>
            <p class="dash-page-desc">Suivez et validez l'ensemble des commandes de la plateforme.</p>
          </div>

          <div class="table-wrapper">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Commande</th>
                  <th>Client</th>
                  <th>Annonce</th>
                  <th>Dates</th>
                  <th>Total</th>
                  <th>Statut</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($bookings_list as $b): ?>
                  <tr>
                    <td style="font-family:monospace;font-weight:700;color:var(--white);"><?php echo htmlspecialchars($b['numero_reservation']); ?></td>
                    <td><?php echo htmlspecialchars($b['prenom'] . ' ' . $b['nom']); ?></td>
                    <td style="font-weight:600;"><?php echo htmlspecialchars($b['item_title']); ?></td>
                    <td style="font-size:0.82rem;">Du <?php echo date('d/m/y', strtotime($b['date_debut'])); ?> au <?php echo date('d/m/y', strtotime($b['date_fin'])); ?></td>
                    <td style="font-weight:700;color:var(--primary);"><?php echo number_format($b['prix_total'], 0); ?> DH</td>
                    <td>
                      <span class="badge <?php echo $b['statut'] === 'confirmee' ? 'badge-success' : ($b['statut'] === 'annulee' ? 'badge-danger' : 'badge-warning'); ?>" id="status-badge-res-<?php echo $b['id']; ?>" style="text-transform: capitalize;">
                        <?php echo $b['statut']; ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($b['statut'] === 'confirmee' || $b['statut'] === 'en_attente'): ?>
                        <button onclick="cancelBooking(<?php echo $b['id']; ?>)" class="btn btn-danger btn-sm" id="cancel-btn-res-<?php echo $b['id']; ?>">
                          Annuler
                        </button>
                      <?php else: ?>
                        <span style="font-size:0.82rem;color:var(--text-disabled);">Aucune</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
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
          <label class="form-label">Propriétaire *</label>
          <select name="proprietaire_id" id="bien-proprietaire_id" class="form-control" required>
            <?php
            $owners_q = mysqli_query($conn, "SELECT id, prenom, nom FROM utilisateurs WHERE type_compte='proprietaire' ORDER BY prenom ASC");
            while ($o = mysqli_fetch_assoc($owners_q)) {
                echo '<option value="' . $o['id'] . '">' . htmlspecialchars($o['prenom'] . ' ' . $o['nom']) . '</option>';
            }
            ?>
          </select>
        </div>

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
          <textarea name="description" id="bien-description" class="form-control" rows="4" placeholder="Décrivez l'hébergement, points forts..."></textarea>
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
        <div class="form-group">
          <label class="form-label">Propriétaire *</label>
          <select name="proprietaire_id" id="voiture-proprietaire_id" class="form-control" required>
            <?php
            $owners_q = mysqli_query($conn, "SELECT id, prenom, nom FROM utilisateurs WHERE type_compte='proprietaire' ORDER BY prenom ASC");
            while ($o = mysqli_fetch_assoc($owners_q)) {
                echo '<option value="' . $o['id'] . '">' . htmlspecialchars($o['prenom'] . ' ' . $o['nom']) . '</option>';
            }
            ?>
          </select>
        </div>

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

<!-- Chart rendering logic and AJAX handlers -->
<script src="../../js/dashboard-admin.js"></script>
</body>
</html>