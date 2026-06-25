<?php
$required_role = 'proprietaire';
include '../../includes/auth-check.php';
include '../../includes/config.php';

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'overview';

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
<?php include '../../includes/header.php'; ?>

<main style="padding-top:100px;padding-bottom:4rem;">
  <div class="container">
    <div class="dash-layout">

      <!-- Sidebar -->
      <aside class="dash-sidebar">
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
          <div style="height:260px;position:relative;"><canvas id="earnings-chart"></canvas></div>
        </div>

        <div class="chart-card">
          <h4 class="chart-card-title">Actions rapides</h4>
          <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:0.5rem;">
            <a href="proprio.php?tab=biens" class="btn btn-secondary"><i class="fa-solid fa-building"></i> Gérer hébergements</a>
            <a href="proprio.php?tab=voitures" class="btn btn-secondary"><i class="fa-solid fa-car"></i> Gérer voitures</a>
            <a href="proprio.php?tab=reservations" class="btn btn-primary"><i class="fa-solid fa-receipt"></i> Voir réservations</a>
          </div>
        </div>

        <?php elseif ($tab === 'biens'): ?>
        <div>
          <h1 class="dash-page-title">Mes Hébergements</h1>
          <p class="dash-page-desc">Gérez vos appartements et villas publiés sur la plateforme.</p>
        </div>
        <div class="table-wrapper">
          <table class="dash-table">
            <thead><tr><th>Photo</th><th>Titre</th><th>Type</th><th>Ville</th><th>Prix/nuit</th><th>Note</th><th>Statut</th><th>Détail</th></tr></thead>
            <tbody>
              <?php if (empty($mes_biens)): ?>
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucun hébergement publié pour le moment.</td></tr>
              <?php else: ?>
                <?php foreach ($mes_biens as $b): ?>
                <tr>
                  <td><img src="<?php echo htmlspecialchars($b['image_principale']); ?>" style="width:60px;height:42px;object-fit:cover;border-radius:6px;" alt="img" onerror="this.src='../../image/apparte.jpg'"></td>
                  <td style="font-weight:600;color:var(--white);"><?php echo htmlspecialchars($b['titre']); ?></td>
                  <td style="text-transform:capitalize;"><?php echo $b['type_bien']; ?></td>
                  <td><?php echo htmlspecialchars($b['ville']); ?></td>
                  <td style="color:var(--primary);font-weight:700;"><?php echo number_format($b['prix_nuit'],0); ?> DH</td>
                  <td><i class="fa-solid fa-star" style="color:var(--primary);font-size:0.8rem;"></i> <?php echo number_format($b['note_moyenne'],1); ?> <span style="color:var(--text-muted);font-size:0.78rem;">(<?php echo $b['nb_avis']; ?>)</span></td>
                  <td><span class="badge <?php echo $b['statut']==='actif'?'badge-success':'badge-danger'; ?>"><?php echo $b['statut']; ?></span></td>
                  <td><a href="../detail-bien.php?id=<?php echo $b['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php elseif ($tab === 'voitures'): ?>
        <div>
          <h1 class="dash-page-title">Mes Véhicules</h1>
          <p class="dash-page-desc">Gérez votre flotte de véhicules disponibles à la location.</p>
        </div>
        <div class="table-wrapper">
          <table class="dash-table">
            <thead><tr><th>Photo</th><th>Véhicule</th><th>Boite</th><th>Carburant</th><th>Prix/jour</th><th>Note</th><th>Statut</th><th>Détail</th></tr></thead>
            <tbody>
              <?php if (empty($mes_voitures)): ?>
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucun véhicule publié pour le moment.</td></tr>
              <?php else: ?>
                <?php foreach ($mes_voitures as $v): ?>
                <tr>
                  <td><img src="<?php echo htmlspecialchars($v['image_principale']); ?>" style="width:60px;height:42px;object-fit:cover;border-radius:6px;" alt="img" onerror="this.src='../../image/v1.jpg'"></td>
                  <td style="font-weight:600;color:var(--white);"><?php echo htmlspecialchars($v['marque'].' '.$v['modele'].' ('.$v['annee'].')'); ?></td>
                  <td style="text-transform:capitalize;"><?php echo $v['boite']; ?></td>
                  <td style="text-transform:capitalize;"><?php echo $v['carburant']; ?></td>
                  <td style="color:var(--primary);font-weight:700;"><?php echo number_format($v['prix_jour'],0); ?> DH</td>
                  <td><i class="fa-solid fa-star" style="color:var(--primary);font-size:0.8rem;"></i> <?php echo number_format($v['note_moyenne'],1); ?> <span style="color:var(--text-muted);font-size:0.78rem;">(<?php echo $v['nb_avis']; ?>)</span></td>
                  <td><span class="badge <?php echo $v['statut']==='actif'?'badge-success':'badge-danger'; ?>"><?php echo $v['statut']; ?></span></td>
                  <td><a href="../detail-voiture.php?id=<?php echo $v['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a></td>
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
</main>

<?php include '../../includes/footer.php'; ?>
<script src="../../js/dashboard-proprio.js"></script>
</body>
</html>
