<?php
session_start();
include '../includes/config.php';

// Stats
$nb_biens      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM biens WHERE statut='actif'"))['c'] ?? 0;
$nb_voitures   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM voitures WHERE statut='actif'"))['c'] ?? 0;
$nb_clients    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM utilisateurs WHERE type_compte='client'"))['c'] ?? 0;
$nb_reserv     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE statut IN('confirmee','terminee')"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../image/favicon.png">
  <link rel="apple-touch-icon" href="../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>À Propos — Immo-Location</title>
  <meta name="description" content="Découvrez Immo-Location, la plateforme de référence pour la location immobilière et automobile au Maroc. Notre histoire, nos valeurs et notre équipe.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <style>
    /* ── Sections ───────────────────────────────────── */
    .about-section { padding: 5rem 0; }
    .about-section:nth-child(even) { background: var(--bg-secondary); }

    /* Mission */
    .mission-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4rem;
      align-items: center;
    }
    .mission-visual {
      position: relative;
      border-radius: var(--radius-xl);
      overflow: hidden;
    }
    .mission-visual img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: var(--radius-xl); }
    .mission-visual-badge {
      position: absolute; bottom: 1.5rem; left: 1.5rem;
      background: var(--gradient-gold);
      color: var(--bg);
      padding: 0.75rem 1.25rem;
      border-radius: var(--radius-md);
      font-weight: 800;
      font-size: 0.95rem;
    }
    .mission-content h2 { font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; color: var(--white); margin-bottom: 1.25rem; }
    .mission-content p { color: var(--text-secondary); line-height: 1.8; margin-bottom: 1rem; }
    .mission-list { list-style: none; margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
    .mission-list li { display: flex; align-items: flex-start; gap: 0.75rem; color: var(--text-secondary); font-size: 0.95rem; }
    .mission-list li i { color: var(--primary); font-size: 1rem; margin-top: 2px; flex-shrink: 0; }

    /* Values */
    .values-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1.5rem; }
    .value-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 2rem;
      text-align: center;
      transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
    }
    .value-card:hover { transform: translateY(-6px); border-color: rgba(245,166,35,0.3); box-shadow: var(--shadow-xl); }
    .value-icon {
      width: 70px; height: 70px;
      border-radius: 50%;
      background: var(--primary-glow);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.8rem; color: var(--primary);
      margin: 0 auto 1.25rem;
    }
    .value-card h3 { font-size: 1.1rem; font-weight: 700; color: var(--white); margin-bottom: 0.5rem; }
    .value-card p { color: var(--text-muted); font-size: 0.88rem; line-height: 1.7; }

    /* Team */
    .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; }
    .team-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      overflow: hidden;
      text-align: center;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .team-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-xl); }
    .team-avatar {
      width: 100%; height: 200px;
      background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg) 100%);
      display: flex; align-items: center; justify-content: center;
      font-size: 4rem;
      color: var(--primary);
      font-weight: 900;
      font-family: 'Outfit', sans-serif;
    }
    .team-info { padding: 1.5rem; }
    .team-name { font-weight: 700; font-size: 1rem; color: var(--white); margin-bottom: 4px; }
    .team-role { font-size: 0.82rem; color: var(--primary); font-weight: 600; margin-bottom: 0.5rem; }
    .team-desc { font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; }

    /* Timeline */
    .timeline { position: relative; max-width: 700px; margin: 0 auto; }
    .timeline::before {
      content: '';
      position: absolute; left: 28px; top: 0; bottom: 0; width: 2px;
      background: linear-gradient(to bottom, var(--primary), transparent);
    }
    .timeline-item { display: flex; gap: 1.5rem; margin-bottom: 2.5rem; }
    .timeline-dot {
      width: 56px; height: 56px;
      border-radius: 50%;
      background: var(--gradient-gold);
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; color: var(--bg); font-size: 0.9rem;
      flex-shrink: 0;
      font-family: 'Outfit', sans-serif;
    }
    .timeline-body {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 1.5rem;
      flex: 1;
    }
    .timeline-body h3 { font-size: 1rem; font-weight: 700; color: var(--white); margin-bottom: 0.4rem; }
    .timeline-body p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }

    /* Stats row */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
    .stat-big {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 2rem;
      text-align: center;
    }
    .stat-big-value { font-size: 2.8rem; font-weight: 900; color: var(--white); font-family: 'Outfit', sans-serif; }
    .stat-big-label { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem; }

    @media (max-width: 768px) {
      .mission-grid { grid-template-columns: 1fr; gap: 2rem; }
      .stats-row { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
      .stats-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 80px;">

  <!-- ── Page Hero ──────────────────────────────────── -->
  <div class="page-hero">
    <div class="container">
      <div class="section-badge reveal"><i class="fa-solid fa-info-circle"></i> Notre histoire</div>
      <h1 class="page-hero-title reveal delay-1">
        À <span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">Propos</span> de nous
      </h1>
      <p class="page-hero-subtitle reveal delay-2">
        La plateforme de référence pour la location immobilière et automobile au Maroc. 
        Fondée avec passion, construite pour la confiance.
      </p>
      <nav class="breadcrumb reveal delay-3">
        <a href="acceuil.php">Accueil</a>
        <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right"></i></span>
        <span>À Propos</span>
      </nav>
    </div>
  </div>

  <!-- ── Chiffres clés ───────────────────────────────── -->
  <section class="about-section" style="padding: 3.5rem 0;">
    <div class="container">
      <div class="stats-row">
        <div class="stat-big reveal">
          <div class="stat-big-value" data-count="<?php echo $nb_biens + $nb_voitures; ?>" data-suffix="+">0+</div>
          <div class="stat-big-label">Annonces actives</div>
        </div>
        <div class="stat-big reveal delay-1">
          <div class="stat-big-value" data-count="<?php echo $nb_clients; ?>" data-suffix="+">0+</div>
          <div class="stat-big-label">Clients satisfaits</div>
        </div>
        <div class="stat-big reveal delay-2">
          <div class="stat-big-value" data-count="<?php echo $nb_reserv; ?>" data-suffix="+">0+</div>
          <div class="stat-big-label">Réservations confirmées</div>
        </div>
        <div class="stat-big reveal delay-3">
          <div class="stat-big-value" data-count="4" data-suffix=".9★">0</div>
          <div class="stat-big-label">Note moyenne</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Mission ─────────────────────────────────────── -->
  <section class="about-section">
    <div class="container">
      <div class="mission-grid">
        <div class="mission-visual reveal-left">
          <img src="../image/villa.jpg" alt="Villa de luxe Immo-Location">
          <div class="mission-visual-badge">
            <i class="fa-solid fa-award"></i> #1 au Maroc
          </div>
        </div>
        <div class="mission-content reveal-right">
          <div class="section-badge" style="margin-bottom:1rem;">
            <i class="fa-solid fa-bullseye"></i> Notre Mission
          </div>
          <h2>Simplifier la location, partout au Maroc</h2>
          <p>
            Immo-Location est née d'un constat simple : trouver une location sûre, transparente et de qualité au Maroc 
            était trop compliqué. Nous avons décidé de changer ça.
          </p>
          <p>
            Notre plateforme connecte propriétaires sérieux et locataires exigeants dans un environnement de confiance, 
            avec des outils modernes pour rendre chaque étape — de la recherche à la remise des clés — fluide et agréable.
          </p>
          <ul class="mission-list">
            <li><i class="fa-solid fa-check-circle"></i> Biens vérifiés et propriétaires certifiés</li>
            <li><i class="fa-solid fa-check-circle"></i> Paiements sécurisés et transparents</li>
            <li><i class="fa-solid fa-check-circle"></i> Support client 7j/7 en français et arabe</li>
            <li><i class="fa-solid fa-check-circle"></i> Avis vérifiés de vraies réservations</li>
            <li><i class="fa-solid fa-check-circle"></i> Annulation flexible selon les conditions</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Valeurs ─────────────────────────────────────── -->
  <section class="about-section">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal"><i class="fa-solid fa-gem"></i> Ce qui nous guide</div>
        <h2 class="section-title reveal delay-1">Nos Valeurs</h2>
        <p class="section-subtitle reveal delay-2">
          Des principes forts qui guident chacune de nos décisions, au service de nos utilisateurs.
        </p>
      </div>

      <div class="values-grid">
        <div class="value-card reveal">
          <div class="value-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <h3>Confiance</h3>
          <p>Chaque propriétaire est vérifié, chaque annonce est contrôlée. Vous louez en sachant exactement ce que vous obtenez.</p>
        </div>
        <div class="value-card reveal delay-1">
          <div class="value-icon" style="background:var(--accent-glow);color:var(--accent);">
            <i class="fa-solid fa-handshake"></i>
          </div>
          <h3>Transparence</h3>
          <p>Pas de frais cachés, pas de mauvaises surprises. Prix affichés, conditions claires, avis authentiques.</p>
        </div>
        <div class="value-card reveal delay-2">
          <div class="value-icon" style="background:var(--success-glow);color:var(--success);">
            <i class="fa-solid fa-bolt"></i>
          </div>
          <h3>Rapidité</h3>
          <p>Réservation en 3 clics, confirmation instantanée, paiement sécurisé. Votre temps est précieux.</p>
        </div>
        <div class="value-card reveal delay-3">
          <div class="value-icon" style="background:rgba(255,190,11,0.15);color:var(--warning);">
            <i class="fa-solid fa-star"></i>
          </div>
          <h3>Excellence</h3>
          <p>Seulement les meilleures annonces, les hôtes les plus sérieux. La médiocrité n'a pas sa place chez nous.</p>
        </div>
        <div class="value-card reveal delay-4">
          <div class="value-icon" style="background:rgba(138,43,226,0.15);color:#a855f7;">
            <i class="fa-solid fa-headset"></i>
          </div>
          <h3>Proximité</h3>
          <p>Un support humain, réactif, en français et en arabe. Nous sommes là à chaque étape de votre location.</p>
        </div>
        <div class="value-card reveal delay-5">
          <div class="value-icon" style="background:rgba(0,200,150,0.15);color:#00c896;">
            <i class="fa-solid fa-leaf"></i>
          </div>
          <h3>Responsabilité</h3>
          <p>Nous encourageons des locations durables et responsables, dans le respect des locaux et de l'environnement.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Histoire / Timeline ─────────────────────────── -->
  <section class="about-section">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal"><i class="fa-solid fa-clock-rotate-left"></i> Notre parcours</div>
        <h2 class="section-title reveal delay-1">Notre Histoire</h2>
        <p class="section-subtitle reveal delay-2">
          De l'idée à la réalité, voici comment Immo-Location a grandi.
        </p>
      </div>

      <div class="timeline reveal">
        <div class="timeline-item">
          <div class="timeline-dot">2022</div>
          <div class="timeline-body">
            <h3>🌱 L'idée germait...</h3>
            <p>Face à la difficulté de trouver une location fiable à Meknès, l'idée de créer une plateforme locale de confiance prend forme. Premiers croquis, premières conversations.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-dot">2023</div>
          <div class="timeline-body">
            <h3>🚀 Lancement officiel</h3>
            <p>Immo-Location.ma est lancée avec les premières annonces de biens immobiliers à Meknès et Fès. L'accueil est immédiat : 50 inscriptions la première semaine.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-dot">2024</div>
          <div class="timeline-body">
            <h3>🚗 Expansion voitures</h3>
            <p>Nous ajoutons la location de voitures à notre catalogue. La plateforme dépasse 500 utilisateurs inscrits et reçoit ses premiers partenariats avec des propriétaires professionnels.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-dot">2025</div>
          <div class="timeline-body">
            <h3>⭐ Plateforme #1 régionale</h3>
            <p>Immo-Location devient la référence pour la location à Meknès et ses environs. Lancement du système de notation vérifiée et du dashboard propriétaire complet.</p>
          </div>
        </div>
        <div class="timeline-item">
          <div class="timeline-dot">2026</div>
          <div class="timeline-body">
            <h3>🌍 Ambitions nationales</h3>
            <p>Expansion vers toutes les grandes villes du Maroc. Nouvelle version de la plateforme avec paiement sécurisé, notifications temps réel, et app mobile en développement.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Équipe ──────────────────────────────────────── -->
  <section class="about-section">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal"><i class="fa-solid fa-people-group"></i> Notre équipe</div>
        <h2 class="section-title reveal delay-1">Les Visages Derrière Immo-Location</h2>
        <p class="section-subtitle reveal delay-2">
          Une équipe passionnée, engagée à vous offrir la meilleure expérience de location.
        </p>
      </div>

      <div class="team-grid">
        <div class="team-card reveal">
          <div class="team-avatar">KN</div>
          <div class="team-info">
            <div class="team-name">Abdoulkarim Nourdine</div>
            <div class="team-role">Fondateur & CEO</div>
            <div class="team-desc">Visionnaire et architecte de la plateforme, Karim a lancé Immo-Location pour révolutionner la location au Maroc.</div>
          </div>
        </div>
        <div class="team-card reveal delay-1">
          <div class="team-avatar" style="color:var(--accent);">JA</div>
          <div class="team-info">
            <div class="team-name">Jamila Ait Bouchnani</div>
            <div class="team-role">Développeuse & CTO</div>
            <div class="team-desc">Architecte technique d'Immo-Location, Jamila conçoit des interfaces modernes et des systèmes robustes.</div>
          </div>
        </div>
        <div class="team-card reveal delay-2">
          <div class="team-avatar" style="color:var(--success);">SA</div>
          <div class="team-info">
            <div class="team-name">Support Client</div>
            <div class="team-role">Service Client 7j/7</div>
            <div class="team-desc">Notre équipe support est disponible du lundi au dimanche pour répondre à toutes vos questions.</div>
          </div>
        </div>
        <div class="team-card reveal delay-3">
          <div class="team-avatar" style="color:var(--warning);">VQ</div>
          <div class="team-info">
            <div class="team-name">Équipe Vérification</div>
            <div class="team-role">Qualité & Conformité</div>
            <div class="team-desc">Chaque bien est inspecté par notre équipe pour garantir la qualité et la conformité des annonces.</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── Contact CTA ─────────────────────────────────── -->
  <section class="cta-section" style="padding: 5rem 0;">
    <div class="container">
      <div class="cta-card reveal">
        <div class="cta-glow"></div>
        <div class="cta-content" style="text-align:center;">
          <div class="section-badge" style="margin-bottom:1.5rem;">
            <i class="fa-solid fa-envelope"></i> Contactez-nous
          </div>
          <h2 class="cta-title">Une question ? Un projet ?</h2>
          <p class="cta-desc">
            Notre équipe est disponible 7j/7 pour répondre à toutes vos questions.<br>
            Partenariat, presse, support — nous sommes là.
          </p>
          <div class="cta-actions">
            <a href="contact.php" class="btn btn-primary btn-lg">
              <i class="fa-solid fa-envelope"></i> Nous contacter
            </a>
            <a href="recherche.php" class="btn btn-secondary btn-lg">
              <i class="fa-solid fa-magnifying-glass"></i> Explorer les annonces
            </a>
          </div>

          <!-- Infos contact rapide -->
          <div style="display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;margin-top:2.5rem;">
            <div style="display:flex;align-items:center;gap:0.6rem;color:var(--text-secondary);font-size:0.88rem;">
              <i class="fa-solid fa-phone" style="color:var(--primary);"></i>
              0690 869 233
            </div>
            <div style="display:flex;align-items:center;gap:0.6rem;color:var(--text-secondary);font-size:0.88rem;">
              <i class="fa-solid fa-envelope" style="color:var(--primary);"></i>
              contact@immolocation.ma
            </div>
            <div style="display:flex;align-items:center;gap:0.6rem;color:var(--text-secondary);font-size:0.88rem;">
              <i class="fa-solid fa-location-dot" style="color:var(--primary);"></i>
              Meknès, Maroc
            </div>
            <div style="display:flex;align-items:center;gap:0.6rem;color:var(--text-secondary);font-size:0.88rem;">
              <i class="fa-solid fa-clock" style="color:var(--primary);"></i>
              Lun–Sam, 8h–18h
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php include '../includes/footer.php'; ?>

<script>
// Counter animation
document.querySelectorAll('[data-count]').forEach(el => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const target = parseInt(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        let current = 0;
        const duration = 2000;
        const step = target / (duration / 16);
        const timer = setInterval(() => {
          current = Math.min(current + step, target);
          el.textContent = Math.floor(current).toLocaleString('fr-FR') + suffix;
          if (current >= target) clearInterval(timer);
        }, 16);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.5 });
  observer.observe(el);
});
</script>
</body>
</html>