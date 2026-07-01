<?php
/**
 * favoris.php — Page des favoris utilisateur
 * Redirige vers le dashboard client (onglet favoris) si connecté,
 * sinon affiche une page d'invitation à se connecter.
 */
session_start();
include '../includes/config.php';

// Si connecté → redirection directe vers dashboard client, onglet favoris
if (isset($_SESSION['user_id']) && $_SESSION['type_compte'] === 'client') {
    header('Location: dash/client.php?tab=favoris');
    exit();
}
if (isset($_SESSION['user_id']) && $_SESSION['type_compte'] === 'proprietaire') {
    header('Location: dash/proprio.php');
    exit();
}
if (isset($_SESSION['user_id']) && $_SESSION['type_compte'] === 'admin') {
    header('Location: dash/admin.php');
    exit();
}

// ── Statistiques publiques pour l'accroche ───────────────────
$nb_biens = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM biens WHERE statut='actif'"))['c'] ?? 0;
$nb_voit  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM voitures WHERE statut='actif'"))['c'] ?? 0;

// Quelques biens à afficher en teaser
$top_biens = mysqli_query($conn, "SELECT * FROM biens WHERE statut='actif' ORDER BY note_moyenne DESC LIMIT 3");
$top_voit  = mysqli_query($conn, "SELECT * FROM voitures WHERE statut='actif' ORDER BY note_moyenne DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../image/favicon.png">
  <link rel="apple-touch-icon" href="../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Favoris — Immo-Location</title>
  <meta name="description" content="Sauvegardez vos biens et voitures préférés sur Immo-Location pour les retrouver facilement.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/listing.css">
  <style>
    .favoris-hero {
      min-height: 70vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 8rem 1rem 5rem;
      position: relative;
      overflow: hidden;
    }
    .favoris-hero::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 60% 60% at 50% 40%, rgba(245,166,35,0.12) 0%, transparent 70%);
      pointer-events: none;
    }
    .favoris-hero-icon {
      width: 100px; height: 100px;
      background: rgba(255,77,109,0.1);
      border: 2px solid rgba(255,77,109,0.2);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2.8rem;
      color: var(--danger);
      margin: 0 auto 2rem;
      animation: pulse-heart 2s ease-in-out infinite;
    }
    @keyframes pulse-heart {
      0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,77,109,0.3); }
      50% { transform: scale(1.05); box-shadow: 0 0 0 12px rgba(255,77,109,0); }
    }
    .favoris-cta-title {
      font-size: clamp(2rem, 5vw, 3rem);
      font-weight: 900;
      color: var(--white);
      line-height: 1.2;
      margin-bottom: 1rem;
      font-family: 'Outfit', sans-serif;
    }
    .favoris-cta-subtitle {
      font-size: 1.1rem;
      color: var(--text-secondary);
      max-width: 500px;
      margin: 0 auto 2.5rem;
      line-height: 1.7;
    }
    .cta-btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
    .features-row {
      display: flex; gap: 1.5rem; justify-content: center;
      flex-wrap: wrap; margin-top: 3rem;
    }
    .feature-chip {
      display: flex; align-items: center; gap: 0.6rem;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 100px;
      padding: 0.6rem 1.25rem;
      font-size: 0.85rem;
      color: var(--text-secondary);
    }
    .feature-chip i { color: var(--primary); }

    /* Teaser section */
    .teaser-section { padding: 4rem 0; background: var(--bg-secondary); }
    .teaser-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.25rem; }
    .teaser-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      overflow: hidden;
      position: relative;
    }
    .teaser-card img { width: 100%; height: 180px; object-fit: cover; }
    .teaser-card-body { padding: 1rem; }
    .teaser-lock-overlay {
      position: absolute; inset: 0;
      background: rgba(10,10,20,0.55);
      display: flex; align-items: center; justify-content: center;
      backdrop-filter: blur(2px);
      opacity: 0;
      transition: opacity 0.25s;
    }
    .teaser-card:hover .teaser-lock-overlay { opacity: 1; }
    .teaser-lock-badge {
      background: var(--primary);
      color: var(--bg);
      padding: 0.6rem 1.25rem;
      border-radius: 100px;
      font-weight: 700;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 80px;">

  <!-- ── Hero ─────────────────────────────────────────── -->
  <section class="favoris-hero">
    <div style="position:relative;z-index:1;max-width:600px;margin:0 auto;">
      <div class="favoris-hero-icon">
        <i class="fa-solid fa-heart"></i>
      </div>

      <h1 class="favoris-cta-title">
        Sauvegardez vos<br>
        <span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">
          coups de cœur
        </span>
      </h1>

      <p class="favoris-cta-subtitle">
        Connectez-vous pour sauvegarder vos biens et voitures préférés. 
        Retrouvez-les facilement et réservez quand vous le souhaitez.
      </p>

      <div class="cta-btns">
        <a href="connexion.php" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-right-to-bracket"></i> Se connecter
        </a>
        <a href="inscription.php" class="btn btn-secondary btn-lg">
          <i class="fa-solid fa-user-plus"></i> Créer un compte
        </a>
      </div>

      <div class="features-row">
        <div class="feature-chip"><i class="fa-solid fa-heart"></i> Favoris illimités</div>
        <div class="feature-chip"><i class="fa-solid fa-bell"></i> Alertes prix</div>
        <div class="feature-chip"><i class="fa-solid fa-lock"></i> Compte sécurisé</div>
        <div class="feature-chip"><i class="fa-solid fa-bolt"></i> Réservation rapide</div>
      </div>
    </div>
  </section>

  <!-- ── Teaser ──────────────────────────────────────── -->
  <section class="teaser-section">
    <div class="container">
      <div class="section-header" style="margin-bottom:2rem;">
        <div class="section-badge"><i class="fa-solid fa-fire"></i> À ne pas manquer</div>
        <h2 class="section-title">Les annonces les mieux notées</h2>
        <p class="section-subtitle">
          Connectez-vous pour les ajouter à vos favoris et les retrouver à tout moment.
        </p>
      </div>

      <!-- Biens -->
      <?php if (mysqli_num_rows($top_biens) > 0): ?>
      <h3 style="color:var(--white);font-size:1.1rem;margin-bottom:1rem;">
        <i class="fa-solid fa-building" style="color:var(--primary);margin-right:8px;"></i> Hébergements
      </h3>
      <div class="teaser-grid" style="margin-bottom:2.5rem;">
        <?php while ($b = mysqli_fetch_assoc($top_biens)): ?>
        <div class="teaser-card">
          <img src="<?php echo htmlspecialchars($b['image_principale'] ?? '../image/villa.webp'); ?>"
               alt="<?php echo htmlspecialchars($b['titre']); ?>"
               onerror="this.src='../image/villa.webp'">
          <div class="teaser-lock-overlay">
            <a href="connexion.php" class="teaser-lock-badge">
              <i class="fa-solid fa-heart"></i> Sauvegarder
            </a>
          </div>
          <div class="teaser-card-body">
            <div style="font-weight:700;color:var(--white);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?php echo htmlspecialchars($b['titre']); ?>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <div style="font-size:0.82rem;color:var(--text-muted);">
                <i class="fa-solid fa-location-dot" style="color:var(--primary);font-size:0.7rem;"></i>
                <?php echo htmlspecialchars($b['ville']); ?>
              </div>
              <div style="color:var(--primary);font-weight:700;font-size:0.9rem;">
                <?php echo number_format($b['prix_nuit'], 0, ',', ' '); ?> DH/nuit
              </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.75rem;">
              <div style="font-size:0.78rem;color:var(--warning);">
                <i class="fa-solid fa-star"></i> <?php echo number_format($b['note_moyenne'], 1); ?>
                <span style="color:var(--text-muted);">(<?php echo $b['nb_avis']; ?>)</span>
              </div>
              <a href="detail-bien.php?id=<?php echo $b['id']; ?>" class="btn btn-secondary btn-sm">Voir</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>

      <!-- Voitures -->
      <?php if (mysqli_num_rows($top_voit) > 0): ?>
      <h3 style="color:var(--white);font-size:1.1rem;margin-bottom:1rem;">
        <i class="fa-solid fa-car" style="color:var(--accent);margin-right:8px;"></i> Voitures
      </h3>
      <div class="teaser-grid">
        <?php while ($v = mysqli_fetch_assoc($top_voit)): ?>
        <div class="teaser-card">
          <img src="<?php echo htmlspecialchars($v['image_principale'] ?? '../image/v1.jpg'); ?>"
               alt="<?php echo htmlspecialchars($v['marque'].' '.$v['modele']); ?>"
               onerror="this.src='../image/v1.jpg'">
          <div class="teaser-lock-overlay">
            <a href="connexion.php" class="teaser-lock-badge" style="background:var(--accent);">
              <i class="fa-solid fa-heart"></i> Sauvegarder
            </a>
          </div>
          <div class="teaser-card-body">
            <div style="font-weight:700;color:var(--white);margin-bottom:4px;">
              <?php echo htmlspecialchars($v['marque'].' '.$v['modele']); ?>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <div style="font-size:0.82rem;color:var(--text-muted);">
                <i class="fa-solid fa-location-dot" style="color:var(--accent);font-size:0.7rem;"></i>
                <?php echo htmlspecialchars($v['ville']); ?>
              </div>
              <div style="color:var(--accent);font-weight:700;font-size:0.9rem;">
                <?php echo number_format($v['prix_jour'], 0, ',', ' '); ?> DH/jour
              </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.75rem;">
              <div style="font-size:0.78rem;color:var(--warning);">
                <i class="fa-solid fa-star"></i> <?php echo number_format($v['note_moyenne'], 1); ?>
                <span style="color:var(--text-muted);">(<?php echo $v['nb_avis']; ?>)</span>
              </div>
              <a href="detail-voiture.php?id=<?php echo $v['id']; ?>" class="btn btn-secondary btn-sm">Voir</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>

      <!-- CTA Bottom -->
      <div class="cta-section reveal" style="margin-top:3rem;">
        <div class="cta-card">
          <div class="cta-glow"></div>
          <div class="cta-content" style="text-align:center;">
            <div class="section-badge" style="margin-bottom:1rem;">
              <i class="fa-solid fa-heart"></i> Commencez gratuitement
            </div>
            <h2 class="cta-title">Créez votre liste de favoris</h2>
            <p class="cta-desc">
              Rejoignez des milliers de clients et sauvegardez vos biens préférés.<br>
              Inscription 100% gratuite, sans engagement.
            </p>
            <div class="cta-actions">
              <a href="inscription.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-user-plus"></i> Créer mon compte
              </a>
              <a href="recherche.php" class="btn btn-secondary btn-lg">
                <i class="fa-solid fa-magnifying-glass"></i> Explorer les annonces
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
