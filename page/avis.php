<?php
session_start();
include '../includes/config.php';

// ── Filtres ──────────────────────────────────────────────────
$filtre_type = $_GET['type'] ?? 'tous';   // tous | bien | voiture
$filtre_note = (int)($_GET['note'] ?? 0); // 0 = tous, 1-5
$sort        = $_GET['sort'] ?? 'recent';

$per_page = 9;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

// ── Build where ──────────────────────────────────────────────
$where = "WHERE a.statut='actif'";
if ($filtre_type !== 'tous') $where .= " AND a.type_cible='" . mysqli_real_escape_string($conn, $filtre_type) . "'";
if ($filtre_note)            $where .= " AND a.note=$filtre_note";

$order = match($sort) {
    'note_desc' => 'ORDER BY a.note DESC',
    'note_asc'  => 'ORDER BY a.note ASC',
    default     => 'ORDER BY a.date_creation DESC',
};

$total_q   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM avis a $where"))['c'] ?? 0;
$total_pages = ceil($total_q / $per_page);

$avis_list = mysqli_query($conn, "
  SELECT a.*, 
         u.prenom, u.nom, u.avatar,
         COALESCE(b.titre, CONCAT(v.marque,' ',v.modele)) as titre_cible,
         COALESCE(b.image_principale, v.image_principale) as img_cible,
         COALESCE(b.ville, v.ville) as ville_cible,
         IF(a.type_cible='bien', CONCAT('detail-bien.php?id=',b.id), CONCAT('detail-voiture.php?id=',v.id)) as lien_cible
  FROM avis a
  JOIN utilisateurs u ON a.auteur_id = u.id
  LEFT JOIN biens b ON a.bien_id = b.id
  LEFT JOIN voitures v ON a.voiture_id = v.id
  $where $order
  LIMIT $per_page OFFSET $offset
");

// ── Stats globales ───────────────────────────────────────────
$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, AVG(note) as moyenne FROM avis WHERE statut='actif'"));
$note_moyenne = round($stats['moyenne'] ?? 0, 1);
$total_avis   = (int)($stats['total'] ?? 0);

$dist = [];
for ($n = 5; $n >= 1; $n--) {
    $d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM avis WHERE statut='actif' AND note=$n"));
    $dist[$n] = (int)($d['c'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../image/favicon.png">
  <link rel="apple-touch-icon" href="../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Avis Clients — Immo-Location</title>
  <meta name="description" content="Lisez les avis vérifiés de nos clients sur nos biens immobiliers et voitures de location au Maroc.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/listing.css">
  <style>
    /* ── Rating Summary Box ───────────────────────────── */
    .rating-summary {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 2.5rem;
      display: flex;
      gap: 3rem;
      align-items: center;
      margin-bottom: 2.5rem;
      flex-wrap: wrap;
    }
    .rating-big {
      text-align: center;
      min-width: 120px;
    }
    .rating-big-score {
      font-size: 5rem;
      font-weight: 900;
      color: var(--white);
      line-height: 1;
      font-family: 'Outfit', sans-serif;
    }
    .rating-big-stars { color: var(--warning); font-size: 1.3rem; margin: 0.5rem 0; }
    .rating-big-total { color: var(--text-muted); font-size: 0.88rem; }
    .rating-bars { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 0.6rem; }
    .rating-bar-row { display: flex; align-items: center; gap: 0.75rem; }
    .rating-bar-label { color: var(--text-secondary); font-size: 0.85rem; width: 30px; flex-shrink: 0; }
    .rating-bar-track {
      flex: 1; height: 8px;
      background: var(--bg-secondary);
      border-radius: 100px; overflow: hidden;
    }
    .rating-bar-fill { height: 100%; background: var(--gradient-gold); border-radius: 100px; transition: width 1s ease; }
    .rating-bar-count { color: var(--text-muted); font-size: 0.78rem; width: 28px; text-align: right; }

    /* ── Avis Card ──────────────────────────────────── */
    .avis-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
    }
    .avis-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 1.75rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
    }
    .avis-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-xl);
      border-color: rgba(245,166,35,0.25);
    }
    .avis-stars { color: var(--warning); font-size: 0.9rem; letter-spacing: 2px; }
    .avis-text { color: var(--text-secondary); font-size: 0.92rem; line-height: 1.7; font-style: italic; }
    .avis-cible {
      display: flex; align-items: center; gap: 0.6rem;
      background: var(--bg-secondary);
      border-radius: var(--radius-md);
      padding: 0.5rem 0.75rem;
      text-decoration: none;
    }
    .avis-cible img { width: 44px; height: 36px; object-fit: cover; border-radius: 6px; }
    .avis-cible-name { font-size: 0.82rem; font-weight: 600; color: var(--white); }
    .avis-cible-loc { font-size: 0.75rem; color: var(--text-muted); }
    .avis-author { display: flex; align-items: center; gap: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border); }
    .avis-avatar {
      width: 40px; height: 40px; border-radius: 50%;
      background: var(--gradient-gold);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.85rem; color: var(--bg);
      flex-shrink: 0;
    }
    .avis-author-name { font-weight: 600; font-size: 0.9rem; color: var(--white); }
    .avis-author-date { font-size: 0.75rem; color: var(--text-muted); }
    .avis-type-chip {
      margin-left: auto;
      font-size: 0.72rem;
      padding: 3px 10px;
      border-radius: 100px;
    }

    /* ── Filters Bar ────────────────────────────────── */
    .filters-bar {
      display: flex; gap: 1rem; flex-wrap: wrap;
      align-items: center; margin-bottom: 2rem;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 1rem 1.25rem;
    }
    .filter-chip {
      padding: 0.45rem 1rem;
      border-radius: 100px;
      border: 1px solid var(--border);
      background: var(--bg-secondary);
      color: var(--text-secondary);
      font-family: inherit;
      font-size: 0.83rem;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex; align-items: center; gap: 0.35rem;
    }
    .filter-chip:hover, .filter-chip.active {
      background: var(--primary); color: var(--bg); border-color: var(--primary);
    }
    .filter-chip.star-active {
      background: rgba(255,190,11,0.15); color: var(--warning); border-color: rgba(255,190,11,0.3);
    }

    /* ── Formulaire avis ────────────────────────────── */
    .add-avis-card {
      background: linear-gradient(135deg, rgba(245,166,35,0.06) 0%, var(--bg-card) 100%);
      border: 1px solid rgba(245,166,35,0.25);
      border-radius: var(--radius-xl);
      padding: 2.5rem;
      margin-bottom: 3rem;
    }
    .star-select { display: flex; gap: 0.5rem; }
    .star-select label { font-size: 2rem; color: var(--border); cursor: pointer; transition: color 0.15s; }
    .star-select input[type="radio"] { display: none; }
    .star-select input[type="radio"]:checked ~ label { color: var(--text-disabled); }
    .star-select label:hover,
    .star-select label:hover ~ label { color: var(--warning); }
  </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 80px;">

  <!-- ── Page Hero ──────────────────────────────────── -->
  <div class="page-hero">
    <div class="container">
      <div class="section-badge reveal"><i class="fa-solid fa-star"></i> Témoignages</div>
      <h1 class="page-hero-title reveal delay-1">
        Avis de nos <span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">Clients</span>
      </h1>
      <p class="page-hero-subtitle reveal delay-2">
        <?php echo $total_avis; ?> avis vérifiés — Note moyenne : <?php echo $note_moyenne; ?>/5
      </p>
      <nav class="breadcrumb reveal delay-3">
        <a href="acceuil.php">Accueil</a>
        <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span>
        <span>Avis clients</span>
      </nav>
    </div>
  </div>

  <div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <!-- ── Rating Summary ─────────────────────────────── -->
    <div class="rating-summary reveal">
      <div class="rating-big">
        <div class="rating-big-score"><?php echo $note_moyenne; ?></div>
        <div class="rating-big-stars">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <i class="fa-<?php echo $s <= round($note_moyenne) ? 'solid' : 'regular'; ?> fa-star"></i>
          <?php endfor; ?>
        </div>
        <div class="rating-big-total"><?php echo $total_avis; ?> avis vérifiés</div>
      </div>

      <div class="rating-bars">
        <?php for ($n = 5; $n >= 1; $n--):
          $pct = $total_avis > 0 ? round($dist[$n] / $total_avis * 100) : 0;
        ?>
        <div class="rating-bar-row">
          <span class="rating-bar-label"><?php echo $n; ?><i class="fa-solid fa-star" style="color:var(--warning);font-size:0.7rem;margin-left:2px;"></i></span>
          <div class="rating-bar-track">
            <div class="rating-bar-fill" style="width:<?php echo $pct; ?>%"></div>
          </div>
          <span class="rating-bar-count"><?php echo $dist[$n]; ?></span>
        </div>
        <?php endfor; ?>
      </div>

      <div style="display:flex;flex-direction:column;gap:0.75rem;min-width:200px;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
          <div style="width:48px;height:48px;border-radius:50%;background:var(--success-glow);display:flex;align-items:center;justify-content:center;color:var(--success);font-size:1.2rem;">
            <i class="fa-solid fa-check-double"></i>
          </div>
          <div>
            <div style="font-weight:700;color:var(--white);">100% vérifiés</div>
            <div style="font-size:0.78rem;color:var(--text-muted);">Réservations confirmées</div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.75rem;">
          <div style="width:48px;height:48px;border-radius:50%;background:var(--primary-glow);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.2rem;">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div>
            <div style="font-weight:700;color:var(--white);">Avis honnêtes</div>
            <div style="font-size:0.78rem;color:var(--text-muted);">Non modifiés par la plateforme</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Formulaire (si connecté) ─────────────────── -->
    <?php if (isset($_SESSION['user_id']) && $_SESSION['type_compte'] === 'client'):
      // Traitement
      $msg_avis = '';
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
          $note_post = (int)($_POST['note'] ?? 0);
          $comm_post = trim($_POST['commentaire'] ?? '');
          $type_post = $_POST['type_cible'] ?? '';
          $cible_id  = (int)($_POST['cible_id'] ?? 0);

          if ($note_post >= 1 && $note_post <= 5 && $comm_post && $type_post && $cible_id) {
              $uid  = $_SESSION['user_id'];
              // Vérifie qu'il a une réservation confirmée
              $check = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT id FROM reservations WHERE client_id=$uid AND statut IN ('confirmee','terminee') 
                 AND " . ($type_post === 'bien' ? "bien_id=$cible_id" : "voiture_id=$cible_id") . " LIMIT 1"));
              if ($check) {
                  $stmt = mysqli_prepare($conn, "INSERT INTO avis (auteur_id, type_cible, bien_id, voiture_id, note, commentaire) VALUES (?, ?, ?, ?, ?, ?)");
                  $bind_bien = $type_post === 'bien' ? $cible_id : null;
                  $bind_voit = $type_post === 'voiture' ? $cible_id : null;
                  mysqli_stmt_bind_param($stmt, 'isiiss', $uid, $type_post, $bind_bien, $bind_voit, $note_post, $comm_post);
                  
                  if (mysqli_stmt_execute($stmt)) {
                      $msg_avis = 'success';
                      
                      // Recalculate average note and total count for target
                      $table = ($type_post === 'bien') ? 'biens' : 'voitures';
                      $id_col = ($type_post === 'bien') ? 'bien_id' : 'voiture_id';

                      $stats_q = mysqli_query($conn, "SELECT COUNT(*) as cnt, AVG(note) as avg_rating FROM avis WHERE type_cible = '$type_post' AND $id_col = $cible_id AND statut = 'actif'");
                      $stats = mysqli_fetch_assoc($stats_q);
                      $cnt = $stats['cnt'] ?? 0;
                      $avg_rating = round($stats['avg_rating'] ?? 0, 2);

                      mysqli_query($conn, "UPDATE $table SET note_moyenne = $avg_rating, nb_avis = $cnt WHERE id = $cible_id");

                      // Notify owner
                      $owner_q = mysqli_query($conn, "SELECT proprietaire_id, " . ($type_post === 'bien' ? 'titre as title' : "CONCAT(marque, ' ', modele) as title") . " FROM $table WHERE id = $cible_id");
                      $owner_data = mysqli_fetch_assoc($owner_q);
                      if ($owner_data) {
                          $owner_id = $owner_data['proprietaire_id'];
                          $item_title = $owner_data['title'];
                          $notif_title = "Nouvel avis reçu";
                          $notif_msg = "Un client a laissé un avis (" . $note_post . "/5) sur \"" . $item_title . "\".";
                          $notif_type = "avis";
                          
                          $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (utilisateur_id, titre, message, type) VALUES (?, ?, ?, ?)");
                          mysqli_stmt_bind_param($notif_stmt, 'isss', $owner_id, $notif_title, $notif_msg, $notif_type);
                          mysqli_stmt_execute($notif_stmt);
                      }
                  } else {
                      $msg_avis = 'error';
                  }
              } else {
                  $msg_avis = 'no_reservation';
              }
          } else {
              $msg_avis = 'error';
          }
      }

      // Ses réservations pour choisir la cible
      $mes_res = mysqli_query($conn, "
        SELECT r.id, r.type_reservation,
               IF(r.type_reservation='bien', b.titre, CONCAT(v.marque,' ',v.modele)) as titre,
               IF(r.type_reservation='bien', r.bien_id, r.voiture_id) as cible_id
        FROM reservations r
        LEFT JOIN biens b ON r.bien_id=b.id
        LEFT JOIN voitures v ON r.voiture_id=v.id
        WHERE r.client_id={$_SESSION['user_id']} AND r.statut IN ('confirmee','terminee')
        ORDER BY r.date_creation DESC LIMIT 20
      ");
      $mes_res_list = [];
      while ($rr = mysqli_fetch_assoc($mes_res)) $mes_res_list[] = $rr;
    ?>
    <div class="add-avis-card reveal">
      <h3 style="font-size:1.3rem;font-weight:700;color:var(--white);margin-bottom:0.5rem;">
        <i class="fa-solid fa-pen-to-square" style="color:var(--primary);margin-right:0.5rem;"></i>
        Laisser un avis
      </h3>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;font-size:0.9rem;">
        Partagez votre expérience avec la communauté Immo-Location.
      </p>

      <?php if ($msg_avis === 'success'): ?>
        <div class="alert alert-avis success" style="background:rgba(0,212,170,0.12);border:1px solid rgba(0,212,170,0.35);border-radius:12px;padding:1rem 1.25rem;color:var(--success);margin-bottom:1rem;display:flex;align-items:center;gap:0.75rem;">
          <i class="fa-solid fa-circle-check" style="font-size:1.2rem;"></i>
          <div><strong>Avis publié !</strong> Votre avis a été publié avec succès. Merci pour votre contribution.</div>
        </div>
      <?php elseif ($msg_avis === 'no_reservation'): ?>
        <div class="alert alert-avis danger" style="background:rgba(255,77,109,0.12);border:1px solid rgba(255,77,109,0.35);border-radius:12px;padding:1rem 1.25rem;color:var(--danger);margin-bottom:1rem;display:flex;align-items:center;gap:0.75rem;">
          <i class="fa-solid fa-triangle-exclamation" style="font-size:1.2rem;"></i>
          <div><strong>Réservation requise</strong> — Vous devez avoir une réservation confirmée pour cet article avant de laisser un avis.</div>
        </div>
      <?php elseif ($msg_avis === 'error'): ?>
        <div class="alert alert-avis danger" style="background:rgba(255,77,109,0.12);border:1px solid rgba(255,77,109,0.35);border-radius:12px;padding:1rem 1.25rem;color:var(--danger);margin-bottom:1rem;display:flex;align-items:center;gap:0.75rem;">
          <i class="fa-solid fa-circle-exclamation" style="font-size:1.2rem;"></i>
          <div><strong>Champs incomplets</strong> — Veuillez remplir tous les champs obligatoires et choisir une note.</div>
        </div>
      <?php endif; ?>

      <?php if (!empty($mes_res_list)): ?>
      <form method="POST">
        <input type="hidden" name="submit_avis" value="1">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
          <div class="form-group">
            <label class="form-label">Votre réservation</label>
            <select name="type_cible" id="avis-type" class="form-control" onchange="updateCibleId()">
              <?php foreach ($mes_res_list as $rr): ?>
              <option value="<?php echo $rr['type_reservation']; ?>" data-id="<?php echo $rr['cible_id']; ?>">
                <?php echo htmlspecialchars($rr['titre']); ?> (<?php echo $rr['type_reservation']; ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <input type="hidden" name="cible_id" id="avis-cible-id" value="<?php echo $mes_res_list[0]['cible_id'] ?? ''; ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Note</label>
            <div class="star-select" id="star-select">
              <?php for ($s = 5; $s >= 1; $s--): ?>
              <input type="radio" name="note" id="star<?php echo $s; ?>" value="<?php echo $s; ?>">
              <label for="star<?php echo $s; ?>" title="<?php echo $s; ?> étoile<?php echo $s > 1 ? 's' : ''; ?>">
                <i class="fa-solid fa-star"></i>
              </label>
              <?php endfor; ?>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Votre commentaire</label>
          <textarea name="commentaire" class="form-control" rows="4"
                    placeholder="Décrivez votre expérience : qualité du bien, accueil, propreté..."
                    required minlength="20"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-paper-plane"></i> Publier mon avis
        </button>
      </form>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:0.9rem;">
          <i class="fa-solid fa-info-circle" style="color:var(--primary);margin-right:6px;"></i>
          Vous devez avoir effectué une réservation confirmée pour laisser un avis.
          <a href="recherche.php" style="color:var(--primary);">Explorer nos annonces →</a>
        </p>
      <?php endif; ?>
    </div>
    <?php elseif (!isset($_SESSION['user_id'])): ?>
    <div class="add-avis-card reveal" style="text-align:center;">
      <i class="fa-regular fa-pen-to-square" style="font-size:2.5rem;color:var(--primary);margin-bottom:1rem;display:block;"></i>
      <h3 style="color:var(--white);margin-bottom:0.5rem;">Vous avez séjourné chez nous ?</h3>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">Connectez-vous pour laisser votre avis et aider la communauté.</p>
      <a href="connexion.php" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket"></i> Se connecter</a>
    </div>
    <?php endif; ?>

    <!-- ── Filtres ─────────────────────────────────────── -->
    <div class="filters-bar reveal">
      <span style="color:var(--text-muted);font-size:0.85rem;font-weight:600;flex-shrink:0;">Filtrer :</span>

      <a href="?type=tous&note=<?php echo $filtre_note; ?>&sort=<?php echo $sort; ?>"
         class="filter-chip <?php echo $filtre_type === 'tous' ? 'active' : ''; ?>">
        <i class="fa-solid fa-grid-2"></i> Tous
      </a>
      <a href="?type=bien&note=<?php echo $filtre_note; ?>&sort=<?php echo $sort; ?>"
         class="filter-chip <?php echo $filtre_type === 'bien' ? 'active' : ''; ?>">
        <i class="fa-solid fa-building"></i> Biens
      </a>
      <a href="?type=voiture&note=<?php echo $filtre_note; ?>&sort=<?php echo $sort; ?>"
         class="filter-chip <?php echo $filtre_type === 'voiture' ? 'active' : ''; ?>">
        <i class="fa-solid fa-car"></i> Voitures
      </a>

      <div style="width:1px;background:var(--border);height:24px;flex-shrink:0;"></div>

      <?php for ($n = 5; $n >= 1; $n--): ?>
      <a href="?type=<?php echo $filtre_type; ?>&note=<?php echo $filtre_note == $n ? 0 : $n; ?>&sort=<?php echo $sort; ?>"
         class="filter-chip <?php echo $filtre_note == $n ? 'star-active' : ''; ?>">
        <?php echo $n; ?><i class="fa-solid fa-star" style="color:var(--warning);font-size:0.7rem;"></i>
      </a>
      <?php endfor; ?>

      <div style="margin-left:auto;">
        <select class="form-control" style="min-width:160px;" onchange="window.location.href=this.value">
          <option value="?type=<?php echo $filtre_type; ?>&note=<?php echo $filtre_note; ?>&sort=recent"
                  <?php echo $sort==='recent'?'selected':''; ?>>Plus récents</option>
          <option value="?type=<?php echo $filtre_type; ?>&note=<?php echo $filtre_note; ?>&sort=note_desc"
                  <?php echo $sort==='note_desc'?'selected':''; ?>>Mieux notés</option>
          <option value="?type=<?php echo $filtre_type; ?>&note=<?php echo $filtre_note; ?>&sort=note_asc"
                  <?php echo $sort==='note_asc'?'selected':''; ?>>Moins bien notés</option>
        </select>
      </div>
    </div>

    <!-- ── Avis Grid ────────────────────────────────────── -->
    <?php $avis_count = 0; ?>
    <div class="avis-grid">
      <?php if ($avis_list && mysqli_num_rows($avis_list) > 0):
        while ($avis = mysqli_fetch_assoc($avis_list)):
          $avis_count++;
      ?>
      <div class="avis-card reveal">
        <!-- Stars -->
        <div class="avis-stars">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <i class="fa-<?php echo $s <= $avis['note'] ? 'solid' : 'regular'; ?> fa-star"
               style="color:<?php echo $s <= $avis['note'] ? 'var(--warning)' : 'var(--border)'; ?>"></i>
          <?php endfor; ?>
        </div>

        <!-- Comment -->
        <p class="avis-text">"<?php echo htmlspecialchars($avis['commentaire']); ?>"</p>

        <!-- Cible -->
        <?php if ($avis['titre_cible']): ?>
        <a href="<?php echo htmlspecialchars($avis['lien_cible']); ?>" class="avis-cible">
          <?php if ($avis['img_cible']): ?>
          <img src="<?php echo htmlspecialchars($avis['img_cible']); ?>" alt=""
               onerror="this.src='../image/villa.webp'">
          <?php endif; ?>
          <div>
            <div class="avis-cible-name"><?php echo htmlspecialchars($avis['titre_cible']); ?></div>
            <?php if ($avis['ville_cible']): ?>
            <div class="avis-cible-loc"><i class="fa-solid fa-location-dot" style="font-size:0.7rem;"></i> <?php echo htmlspecialchars($avis['ville_cible']); ?></div>
            <?php endif; ?>
          </div>
          <i class="fa-solid fa-arrow-up-right-from-square" style="margin-left:auto;color:var(--text-muted);font-size:0.75rem;"></i>
        </a>
        <?php endif; ?>

        <!-- Author -->
        <div class="avis-author">
          <div class="avis-avatar">
            <?php echo strtoupper(substr($avis['prenom'], 0, 1) . substr($avis['nom'], 0, 1)); ?>
          </div>
          <div>
            <div class="avis-author-name"><?php echo htmlspecialchars($avis['prenom'].' '.$avis['nom']); ?></div>
            <div class="avis-author-date"><?php echo date('d M Y', strtotime($avis['date_creation'])); ?></div>
          </div>
          <span class="avis-type-chip badge <?php echo $avis['type_cible'] === 'bien' ? 'badge-primary' : 'badge-accent'; ?>">
            <i class="fa-solid fa-<?php echo $avis['type_cible'] === 'bien' ? 'building' : 'car'; ?>"></i>
            <?php echo ucfirst($avis['type_cible']); ?>
          </span>
        </div>
      </div>
      <?php endwhile;
      else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:4rem;color:var(--text-muted);">
          <i class="fa-regular fa-star" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:0.3;"></i>
          <p>Aucun avis ne correspond à votre sélection.</p>
          <a href="avis.php" class="btn btn-secondary" style="margin-top:1rem;">Voir tous les avis</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── Pagination ─────────────────────────────────── -->
    <?php if ($total_pages > 1): ?>
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

  </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
// Star selector
(function() {
  const stars = document.querySelectorAll('#star-select input[type="radio"]');
  const labels = document.querySelectorAll('#star-select label');
  stars.forEach((star, i) => {
    star.addEventListener('change', () => {
      labels.forEach((lbl, j) => {
        lbl.querySelector('i').style.color = j >= i ? 'var(--warning)' : 'var(--border)';
      });
    });
  });
})();

function updateCibleId() {
  const sel = document.getElementById('avis-type');
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('avis-cible-id').value = opt.dataset.id || '';
}
</script>
</body>
</html>
