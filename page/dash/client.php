<?php
$required_role = null; // any logged-in user
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../page/connexion.php');
    exit();
}
// Redirect admins and owners to their own dashboard
if ($_SESSION['type_compte'] === 'admin') { header('Location: admin.php'); exit(); }
if ($_SESSION['type_compte'] === 'proprietaire') { header('Location: proprio.php'); exit(); }

include '../../includes/config.php';
$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'overview';

$mes_reservations = [];
$mes_favoris_biens = [];
$mes_favoris_voit = [];

if ($tab === 'reservations') {
    $q = mysqli_query($conn, "
        SELECT r.*,
               IF(r.type_reservation='bien', b.titre, CONCAT(v.marque,' ',v.modele)) as item_title,
               IF(r.type_reservation='bien', b.image_principale, v.image_principale) as item_img,
               p.statut as pay_statut, p.reference as txn_ref
        FROM reservations r
        LEFT JOIN biens b ON r.bien_id = b.id AND r.type_reservation='bien'
        LEFT JOIN voitures v ON r.voiture_id = v.id AND r.type_reservation='voiture'
        LEFT JOIN paiements p ON p.reservation_id = r.id
        WHERE r.client_id = $user_id
        ORDER BY r.date_creation DESC
    ");
    while ($r = mysqli_fetch_assoc($q)) $mes_reservations[] = $r;
} elseif ($tab === 'favoris') {
    $q_biens = mysqli_query($conn, "
        SELECT b.id, b.titre, b.prix_nuit, b.image_principale, b.note_moyenne, b.nb_avis, b.ville
        FROM favoris f JOIN biens b ON f.bien_id = b.id
        WHERE f.utilisateur_id = $user_id AND f.type_favori = 'bien'
    ");
    while ($r = mysqli_fetch_assoc($q_biens)) $mes_favoris_biens[] = $r;

    $q_voit = mysqli_query($conn, "
        SELECT v.id, CONCAT(v.marque,' ',v.modele) as titre, v.prix_jour, v.image_principale, v.note_moyenne, v.nb_avis, v.ville
        FROM favoris f JOIN voitures v ON f.voiture_id = v.id
        WHERE f.utilisateur_id = $user_id AND f.type_favori = 'voiture'
    ");
    while ($r = mysqli_fetch_assoc($q_voit)) $mes_favoris_voit[] = $r;
}

// Quick stats
$nb_reservations = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM reservations WHERE client_id=$user_id"))['c'];
$nb_favoris = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM favoris WHERE utilisateur_id=$user_id"))['c'];
$nb_avis = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM avis WHERE auteur_id=$user_id"))['c'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../../image/favicon.png">
  <link rel="apple-touch-icon" href="../../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Espace — Immo-Location</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../../css/global.css">
  <link rel="stylesheet" href="../../css/dashboard.css">
</head>
<body>
<?php include '../../includes/header.php'; ?>

<main style="padding-top:100px;padding-bottom:4rem;">
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
            <div class="dash-user-role">Client</div>
          </div>
        </div>
        <nav class="dash-menu">
          <a href="client.php?tab=overview" class="dash-menu-link <?php echo $tab==='overview'?'active':''; ?>"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
          <a href="client.php?tab=reservations" class="dash-menu-link <?php echo $tab==='reservations'?'active':''; ?>"><i class="fa-solid fa-receipt"></i> Mes réservations</a>
          <a href="client.php?tab=favoris" class="dash-menu-link <?php echo $tab==='favoris'?'active':''; ?>"><i class="fa-solid fa-heart"></i> Mes favoris</a>
          <a href="client.php?tab=profil" class="dash-menu-link <?php echo $tab==='profil'?'active':''; ?>"><i class="fa-solid fa-user-pen"></i> Mon profil</a>
          <a href="../../php/logout.php" class="dash-menu-link" style="color:var(--danger);margin-top:2rem;"><i class="fa-solid fa-right-from-bracket" style="color:var(--danger);"></i> Déconnexion</a>
        </nav>
      </aside>

      <!-- Contenu -->
      <div class="dash-content">
        <!-- Mobile Toggle Bar -->
        <div class="dash-mobile-bar">
          <button class="dash-sidebar-toggle-btn" id="dashSidebarToggle">
            <i class="fa-solid fa-bars-staggered"></i> Menu Espace
          </button>
        </div>

        <?php if ($tab === 'overview'): ?>
        <div>
          <h1 class="dash-page-title">Bonjour, <?php echo htmlspecialchars($_SESSION['prenom']); ?> 👋</h1>
          <p class="dash-page-desc">Gérez vos réservations, favoris et profil depuis cet espace personnel.</p>
        </div>

        <div class="dash-stats-grid">
          <div class="stat-widget">
            <div class="stat-widget-info"><h3><?php echo $nb_reservations; ?></h3><p>Réservations</p></div>
            <div class="stat-widget-icon"><i class="fa-solid fa-receipt"></i></div>
          </div>
          <div class="stat-widget">
            <div class="stat-widget-info"><h3><?php echo $nb_favoris; ?></h3><p>Favoris</p></div>
            <div class="stat-widget-icon" style="background:rgba(255,77,109,0.2);color:var(--danger);"><i class="fa-solid fa-heart"></i></div>
          </div>
          <div class="stat-widget">
            <div class="stat-widget-info"><h3><?php echo $nb_avis; ?></h3><p>Avis rédigés</p></div>
            <div class="stat-widget-icon" style="background:var(--success-glow);color:var(--success);"><i class="fa-solid fa-star"></i></div>
          </div>
        </div>

        <!-- Recent bookings preview -->
        <?php
        $recent_q = mysqli_query($conn, "
            SELECT r.numero_reservation, r.date_debut, r.date_fin, r.statut, r.prix_total,
                   IF(r.type_reservation='bien', b.titre, CONCAT(v.marque,' ',v.modele)) as item_title
            FROM reservations r
            LEFT JOIN biens b ON r.bien_id = b.id
            LEFT JOIN voitures v ON r.voiture_id = v.id
            WHERE r.client_id = $user_id ORDER BY r.date_creation DESC LIMIT 3
        ");
        $recent_res = [];
        while ($rr = mysqli_fetch_assoc($recent_q)) $recent_res[] = $rr;
        ?>
        <?php if (!empty($recent_res)): ?>
        <div class="chart-card">
          <h4 class="chart-card-title">Réservations récentes <a href="client.php?tab=reservations" class="btn btn-secondary btn-sm" style="font-size:0.78rem;">Voir tout</a></h4>
          <?php foreach ($recent_res as $rr): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem 0;border-bottom:1px solid var(--border);gap:0.5rem;flex-wrap:wrap;">
            <div style="min-width:0;flex:1;">
              <div style="font-weight:600;color:var(--white);font-size:0.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($rr['item_title']); ?></div>
              <div style="font-size:0.78rem;color:var(--text-secondary);"><?php echo date('d/m/Y',strtotime($rr['date_debut'])); ?> → <?php echo date('d/m/Y',strtotime($rr['date_fin'])); ?></div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <span class="badge <?php echo $rr['statut']==='confirmee'?'badge-success':($rr['statut']==='annulee'?'badge-danger':'badge-warning'); ?>" style="text-transform:capitalize;"><?php echo $rr['statut']; ?></span>
              <div style="font-size:0.82rem;color:var(--primary);font-weight:700;margin-top:2px;"><?php echo number_format($rr['prix_total'],0); ?> DH</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick explore -->
        <div class="chart-card">
          <h4 class="chart-card-title">Explorer la plateforme</h4>
          <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.5rem;">
            <a href="../appartement.php" class="btn btn-secondary"><i class="fa-solid fa-building"></i> Appartements</a>
            <a href="../villa.php" class="btn btn-secondary"><i class="fa-solid fa-house-chimney"></i> Villas</a>
            <a href="../voiturre.php" class="btn btn-secondary"><i class="fa-solid fa-car"></i> Voitures</a>
            <a href="../recherche.php" class="btn btn-primary"><i class="fa-solid fa-search"></i> Rechercher</a>
          </div>
        </div>

        <?php elseif ($tab === 'reservations'): ?>
        <div>
          <h1 class="dash-page-title">Mes Réservations</h1>
          <p class="dash-page-desc">Historique complet de vos locations et statuts de paiement.</p>
        </div>
        <div class="table-wrapper">
          <table class="dash-table">
            <thead><tr><th>Réf.</th><th>Annonce</th><th>Dates</th><th>Total</th><th>Paiement</th><th>Statut</th><th>Reçu</th></tr></thead>
            <tbody>
              <?php if (empty($mes_reservations)): ?>
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucune réservation pour le moment. <a href="../acceuil.php" style="color:var(--primary);">Explorer les annonces →</a></td></tr>
              <?php else: ?>
                <?php foreach ($mes_reservations as $r): ?>
                <tr>
                  <td style="font-family:monospace;font-weight:700;font-size:0.78rem;color:var(--white);"><?php echo htmlspecialchars($r['numero_reservation']); ?></td>
                  <td>
                    <div style="display:flex;gap:8px;align-items:center;">
                      <img src="<?php echo htmlspecialchars($r['item_img'] ?? ''); ?>" style="width:48px;height:34px;object-fit:cover;border-radius:4px;" alt="" onerror="this.style.display='none'">
                      <span style="font-weight:500;color:var(--white);"><?php echo htmlspecialchars($r['item_title']); ?></span>
                    </div>
                  </td>
                  <td style="font-size:0.82rem;">Du <?php echo date('d/m/y',strtotime($r['date_debut'])); ?><br>au <?php echo date('d/m/y',strtotime($r['date_fin'])); ?></td>
                  <td style="color:var(--primary);font-weight:700;"><?php echo number_format($r['prix_total'],0); ?> DH</td>
                  <td><span class="badge <?php echo $r['pay_statut']==='valide'?'badge-success':'badge-warning'; ?>"><?php echo $r['pay_statut'] ?? 'En attente'; ?></span></td>
                  <td><span class="badge <?php echo $r['statut']==='confirmee'?'badge-success':($r['statut']==='annulee'?'badge-danger':'badge-warning'); ?>" style="text-transform:capitalize;"><?php echo $r['statut']; ?></span></td>
                  <td>
                    <?php if ($r['statut'] === 'confirmee'): ?>
                      <a href="../confirmation.php?id=<?php echo $r['id']; ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-receipt"></i></a>
                    <?php else: ?>—<?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php elseif ($tab === 'favoris'): ?>
        <div>
          <h1 class="dash-page-title">Mes Favoris</h1>
          <p class="dash-page-desc">Vos hébergements et véhicules sauvegardés pour une réservation future.</p>
        </div>

        <?php if (!empty($mes_favoris_biens)): ?>
          <h3 style="color:var(--white);margin-bottom:1rem;"><i class="fa-solid fa-building" style="color:var(--primary);margin-right:8px;"></i>Hébergements sauvegardés</h3>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-bottom:2rem;">
            <?php foreach ($mes_favoris_biens as $f): ?>
            <div class="listing-card">
              <div class="listing-card-image" style="height:160px;">
                <img src="<?php echo htmlspecialchars($f['image_principale']); ?>" alt="<?php echo htmlspecialchars($f['titre']); ?>" onerror="this.src='../../image/apparte.jpg'">
              </div>
              <div style="padding:1rem;">
                <div style="font-weight:700;color:var(--white);margin-bottom:4px;"><?php echo htmlspecialchars($f['titre']); ?></div>
                <div style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:8px;"><i class="fa-solid fa-location-dot" style="color:var(--primary);"></i> <?php echo htmlspecialchars($f['ville']); ?></div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="color:var(--primary);font-weight:700;"><?php echo number_format($f['prix_nuit'],0); ?> DH/nuit</span>
                  <a href="../detail-bien.php?id=<?php echo $f['id']; ?>" class="btn btn-primary btn-sm">Voir</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($mes_favoris_voit)): ?>
          <h3 style="color:var(--white);margin-bottom:1rem;"><i class="fa-solid fa-car" style="color:var(--accent);margin-right:8px;"></i>Véhicules sauvegardés</h3>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;">
            <?php foreach ($mes_favoris_voit as $f): ?>
            <div class="listing-card">
              <div class="listing-card-image" style="height:160px;">
                <img src="<?php echo htmlspecialchars($f['image_principale']); ?>" alt="<?php echo htmlspecialchars($f['titre']); ?>" onerror="this.src='../../image/v1.jpg'">
              </div>
              <div style="padding:1rem;">
                <div style="font-weight:700;color:var(--white);margin-bottom:4px;"><?php echo htmlspecialchars($f['titre']); ?></div>
                <div style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:8px;"><i class="fa-solid fa-location-dot" style="color:var(--accent);"></i> <?php echo htmlspecialchars($f['ville']); ?></div>
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="color:var(--accent);font-weight:700;"><?php echo number_format($f['prix_jour'],0); ?> DH/jour</span>
                  <a href="../detail-voiture.php?id=<?php echo $f['id']; ?>" class="btn btn-accent btn-sm">Voir</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (empty($mes_favoris_biens) && empty($mes_favoris_voit)): ?>
          <div style="text-align:center;padding:3rem;color:var(--text-muted);">
            <i class="fa-solid fa-heart-crack" style="font-size:3rem;margin-bottom:1rem;display:block;color:var(--danger);opacity:0.3;"></i>
            <p>Aucun favori pour le moment.</p>
            <a href="../acceuil.php" class="btn btn-primary" style="margin-top:1rem;"><i class="fa-solid fa-search"></i> Explorer les annonces</a>
          </div>
        <?php endif; ?>

        <?php elseif ($tab === 'profil'): ?>
        <div>
          <h1 class="dash-page-title">Mon Profil</h1>
          <p class="dash-page-desc">Mettez à jour vos informations personnelles.</p>
        </div>
        <?php
        $u_q = mysqli_query($conn,"SELECT * FROM utilisateurs WHERE id=$user_id");
        $u = mysqli_fetch_assoc($u_q);
        $update_success = '';
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profil'])) {
            $prenom = trim($_POST['prenom']??'');
            $nom = trim($_POST['nom']??'');
            $telephone = trim($_POST['telephone']??'');
            $ville = trim($_POST['ville']??'');
            $bio = trim($_POST['bio']??'');
            if ($prenom && $nom) {
                $stmt = mysqli_prepare($conn,"UPDATE utilisateurs SET prenom=?,nom=?,telephone=?,ville=?,bio=? WHERE id=?");
                mysqli_stmt_bind_param($stmt,'sssssi',$prenom,$nom,$telephone,$ville,$bio,$user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['prenom']=$prenom; $_SESSION['nom']=$nom;
                    $update_success='success';
                    $u['prenom']=$prenom; $u['nom']=$nom; $u['telephone']=$telephone; $u['ville']=$ville; $u['bio']=$bio;
                }
            }
        }
        ?>
        <?php if ($update_success): ?><div style="background:rgba(0,212,170,0.12);border:1px solid rgba(0,212,170,0.35);border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem;color:var(--success);display:flex;align-items:center;gap:0.75rem;"><i class="fa-solid fa-circle-check" style="font-size:1.2rem;"></i><div><strong>Profil mis à jour !</strong> Vos modifications ont été enregistrées avec succès.</div></div><?php endif; ?>
        <div class="glass-card" style="padding:var(--space-xl);">
          <form method="POST">
            <input type="hidden" name="update_profil" value="1">
            <div class="grid-2">
              <div class="form-group"><label class="form-label">Prénom</label><input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($u['prenom']); ?>" required></div>
              <div class="form-group"><label class="form-label">Nom</label><input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($u['nom']); ?>" required></div>
              <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($u['email']); ?>" readonly style="opacity:0.6;cursor:not-allowed;"></div>
              <div class="form-group"><label class="form-label">Téléphone</label><input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($u['telephone']??''); ?>"></div>
            </div>
            <div class="form-group"><label class="form-label">Ville de résidence</label><input type="text" name="ville" class="form-control" value="<?php echo htmlspecialchars($u['ville']??''); ?>"></div>
            <div class="form-group"><label class="form-label">Bio / Présentation (optionnel)</label><textarea name="bio" class="form-control"><?php echo htmlspecialchars($u['bio']??''); ?></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications</button>
          </form>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
</body>
</html>
