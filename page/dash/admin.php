<?php
$required_role = 'admin';
include '../../includes/auth-check.php';
include '../../includes/config.php';

$tab = $_GET['tab'] ?? 'overview';

// Queries based on selected tab
$users_list = [];
$listings_list = [];
$bookings_list = [];

if ($tab === 'users') {
    $q = mysqli_query($conn, "SELECT id, prenom, nom, email, telephone, type_compte, statut, date_creation FROM utilisateurs ORDER BY date_creation DESC");
    while ($r = mysqli_fetch_assoc($q)) { $users_list[] = $r; }
} elseif ($tab === 'annonces') {
    // Combine biens and voitures
    $q1 = mysqli_query($conn, "SELECT id, titre as title, 'bien' as type, type_bien as subtype, prix_nuit as price, statut, date_creation FROM biens ORDER BY date_creation DESC");
    while ($r = mysqli_fetch_assoc($q1)) { $listings_list[] = $r; }
    
    $q2 = mysqli_query($conn, "SELECT id, CONCAT(marque, ' ', modele) as title, 'voiture' as type, boite as subtype, prix_jour as price, statut, date_creation FROM voitures ORDER BY date_creation DESC");
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

<?php include '../../includes/header.php'; ?>

<main style="padding-top: 100px; padding-bottom: 4rem;">
  <div class="container">
    
    <div class="dash-layout">
      
      <!-- Sidebar Navigation -->
      <aside class="dash-sidebar">
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
              <div style="height: 250px; position: relative;">
                <canvas id="earnings-chart"></canvas>
              </div>
            </div>
            <div class="chart-card">
              <h4 class="chart-card-title">Type de réservations</h4>
              <div style="height: 250px; position: relative; display: flex; justify-content: center; align-items: center;">
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
          <div>
            <h1 class="dash-page-title">Gestion des Annonces</h1>
            <p class="dash-page-desc">Validez ou suspendez les hébergements et voitures publiés.</p>
          </div>

          <div class="table-wrapper">
            <table class="dash-table">
              <thead>
                <tr>
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
                      <button onclick="toggleListingStatus(<?php echo $l['id']; ?>, '<?php echo $l['type']; ?>', '<?php echo $l['statut']; ?>')" class="btn btn-secondary btn-sm" id="status-btn-listing-<?php echo $l['type']; ?>-<?php echo $l['id']; ?>">
                        <?php echo $l['statut'] === 'actif' ? 'Désactiver' : 'Activer'; ?>
                      </button>
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
</main>

<?php include '../../includes/footer.php'; ?>

<!-- Chart rendering logic and AJAX handlers -->
<script src="../../js/dashboard-admin.js"></script>
</body>
</html>