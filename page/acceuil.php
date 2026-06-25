<?php
session_start();
include '../includes/config.php';

// Récupérer les biens en vedette
$biens_vedette = mysqli_query($conn, "SELECT b.*, u.prenom, u.nom FROM biens b JOIN utilisateurs u ON b.proprietaire_id = u.id WHERE b.statut='actif' AND b.disponible=1 ORDER BY b.note_moyenne DESC LIMIT 6");

// Récupérer les voitures vedettes
$voitures_vedette = mysqli_query($conn, "SELECT v.*, u.prenom FROM voitures v JOIN utilisateurs u ON v.proprietaire_id = u.id WHERE v.statut='actif' AND v.disponible=1 ORDER BY v.note_moyenne DESC LIMIT 4");

// Stats
$nb_biens = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM biens WHERE statut='actif'"))['c'] ?? 0;
$nb_voitures = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM voitures WHERE statut='actif'"))['c'] ?? 0;
$nb_clients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM utilisateurs WHERE type_compte='client'"))['c'] ?? 0;
$nb_reservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE statut='confirmee'"))['c'] ?? 0;

// Avis récents
$avis_recents = mysqli_query($conn, "
  SELECT a.*, u.prenom, u.nom, u.avatar
  FROM avis a
  JOIN utilisateurs u ON a.auteur_id = u.id
  WHERE a.statut='actif'
  ORDER BY a.date_creation DESC
  LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Immo-Location — Location Immobilière & Automobile Premium au Maroc</title>
  <meta name="description" content="Trouvez votre appartement, villa ou voiture de location idéale au Maroc. Réservation simple, paiement sécurisé, service premium.">
  <meta name="keywords" content="location appartement Maroc, location villa Meknès, location voiture Maroc, immobilier location">
  <meta property="og:title" content="Immo-Location — Location Premium au Maroc">
  <meta property="og:description" content="Appartements, villas et voitures de location au Maroc. Qualité premium, prix transparents.">
  <meta property="og:type" content="website">
  <meta name="theme-color" content="#f5a623">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/accueil.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main>

  <!-- ═══════════════════════════════════════════════════════
       HERO SECTION
  ═══════════════════════════════════════════════════════ -->
  <section class="hero-section" id="accueil">
    <!-- Background -->
    <div class="hero-bg" id="hero-bg">
      <div class="hero-slide active" style="background-image: url('../image/villa.jpg')"></div>
      <div class="hero-slide" style="background-image: url('../image/apparte.jpg')"></div>
      <div class="hero-slide" style="background-image: url('../image/v1.jpg')"></div>
    </div>
    <div class="hero-overlay"></div>

    <!-- Particles -->
    <div class="hero-particles" id="particles"></div>

    <!-- Content -->
    <div class="hero-content container">
      <div class="hero-badge reveal">
        <i class="fa-solid fa-star"></i>
        <span>Plateforme #1 au Maroc</span>
      </div>

      <h1 class="hero-title reveal delay-1">
        Louez en toute
        <span class="hero-title-highlight">confiance</span>
      </h1>

      <p class="hero-subtitle reveal delay-2">
        Appartements, villas et voitures — trouvez la location idéale pour chaque occasion. 
        Qualité premium, prix transparents, réservation instantanée.
      </p>

      <!-- Search Bar -->
      <div class="hero-search reveal delay-3">
        <div class="search-tabs">
          <button class="search-tab active" data-type="bien">
            <i class="fa-solid fa-building"></i> Immobilier
          </button>
          <button class="search-tab" data-type="voiture">
            <i class="fa-solid fa-car"></i> Voitures
          </button>
        </div>

        <form class="search-form" action="recherche.php" method="GET" id="hero-search-form">
          <input type="hidden" name="type" id="search-type-input" value="bien">

          <div class="search-field">
            <i class="fa-solid fa-location-dot search-icon"></i>
            <input type="text" name="ville" placeholder="Ville, quartier..." class="search-input" autocomplete="off">
          </div>

          <div class="search-divider"></div>

          <div class="search-field" id="date-field">
            <i class="fa-solid fa-calendar-days search-icon"></i>
            <input type="date" name="date_debut" placeholder="Arrivée" class="search-input">
          </div>

          <div class="search-divider"></div>

          <div class="search-field" id="date-fin-field">
            <i class="fa-solid fa-calendar-check search-icon"></i>
            <input type="date" name="date_fin" placeholder="Départ" class="search-input">
          </div>

          <div class="search-divider"></div>

          <div class="search-field" id="guests-field">
            <i class="fa-solid fa-users search-icon"></i>
            <select name="personnes" class="search-input">
              <option value="">Personnes</option>
              <option value="1">1 personne</option>
              <option value="2">2 personnes</option>
              <option value="4">3-4 personnes</option>
              <option value="8">5-8 personnes</option>
              <option value="12">8+ personnes</option>
            </select>
          </div>

          <button type="submit" class="search-btn">
            <i class="fa-solid fa-magnifying-glass"></i>
            Rechercher
          </button>
        </form>
      </div>

      <!-- Quick stats -->
      <div class="hero-stats reveal delay-4">
        <div class="hero-stat">
          <span class="hero-stat-value" data-count="<?php echo $nb_biens + $nb_voitures; ?>" data-suffix="+">0</span>
          <span class="hero-stat-label">Annonces</span>
        </div>
        <div class="hero-stat-divider"></div>
        <div class="hero-stat">
          <span class="hero-stat-value" data-count="<?php echo $nb_clients; ?>" data-suffix="+">0</span>
          <span class="hero-stat-label">Clients satisfaits</span>
        </div>
        <div class="hero-stat-divider"></div>
        <div class="hero-stat">
          <span class="hero-stat-value" data-count="<?php echo $nb_reservations; ?>" data-suffix="+">0</span>
          <span class="hero-stat-label">Réservations</span>
        </div>
        <div class="hero-stat-divider"></div>
        <div class="hero-stat">
          <span class="hero-stat-value" data-count="4" data-suffix=".9★">0</span>
          <span class="hero-stat-label">Note moyenne</span>
        </div>
      </div>
    </div>

    <!-- Scroll indicator -->
    <div class="hero-scroll">
      <div class="scroll-mouse">
        <div class="scroll-wheel"></div>
      </div>
    </div>

    <!-- Slide dots -->
    <div class="hero-dots">
      <button class="hero-dot active" data-slide="0"></button>
      <button class="hero-dot" data-slide="1"></button>
      <button class="hero-dot" data-slide="2"></button>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       STATS SECTION
  ═══════════════════════════════════════════════════════ -->
  <section class="section stats-section">
    <div class="container">
      <div class="grid-4">
        <div class="stat-card reveal">
          <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
          <div class="stat-value" data-count="<?php echo $nb_biens; ?>" data-suffix="+">0+</div>
          <div class="stat-label">Biens disponibles</div>
        </div>
        <div class="stat-card reveal delay-1">
          <div class="stat-icon" style="background:var(--accent-glow);color:var(--accent);">
            <i class="fa-solid fa-car"></i>
          </div>
          <div class="stat-value" data-count="<?php echo $nb_voitures; ?>" data-suffix="+">0+</div>
          <div class="stat-label">Voitures disponibles</div>
        </div>
        <div class="stat-card reveal delay-2">
          <div class="stat-icon" style="background:var(--success-glow);color:var(--success);">
            <i class="fa-solid fa-users"></i>
          </div>
          <div class="stat-value" data-count="<?php echo $nb_clients; ?>" data-suffix="+">0+</div>
          <div class="stat-label">Clients satisfaits</div>
        </div>
        <div class="stat-card reveal delay-3">
          <div class="stat-icon" style="background:rgba(255,190,11,0.2);color:var(--warning);">
            <i class="fa-solid fa-star"></i>
          </div>
          <div class="stat-value" data-count="49" data-suffix="/50">0/50</div>
          <div class="stat-label">Note de satisfaction</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       SERVICES SECTION
  ═══════════════════════════════════════════════════════ -->
  <section class="section">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal">
          <i class="fa-solid fa-gem"></i> Nos Services
        </div>
        <h2 class="section-title reveal delay-1">Ce que nous proposons</h2>
        <p class="section-subtitle reveal delay-2">
          Une gamme complète de locations pour répondre à tous vos besoins, avec une qualité irréprochable.
        </p>
      </div>

      <div class="services-grid">
        <a href="appartement.php" class="service-card reveal">
          <div class="service-card-bg" style="background-image:url('../image/apparte.jpg')"></div>
          <div class="service-card-overlay"></div>
          <div class="service-card-content">
            <div class="service-icon">
              <i class="fa-solid fa-building"></i>
            </div>
            <h3 class="service-title">Appartements</h3>
            <p class="service-desc">Des appartements modernes au cœur des villes</p>
            <div class="service-cta">
              Découvrir <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
        </a>

        <a href="villa.php" class="service-card reveal delay-1">
          <div class="service-card-bg" style="background-image:url('../image/villa.webp')"></div>
          <div class="service-card-overlay"></div>
          <div class="service-card-content">
            <div class="service-icon">
              <i class="fa-solid fa-house-chimney"></i>
            </div>
            <h3 class="service-title">Villas</h3>
            <p class="service-desc">Des villas luxueuses avec piscine et jardin</p>
            <div class="service-cta">
              Découvrir <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
        </a>

        <a href="voiturre.php" class="service-card reveal delay-2">
          <div class="service-card-bg" style="background-image:url('../image/v1.jpg')"></div>
          <div class="service-card-overlay"></div>
          <div class="service-card-content">
            <div class="service-icon">
              <i class="fa-solid fa-car"></i>
            </div>
            <h3 class="service-title">Voitures</h3>
            <p class="service-desc">Véhicules premium pour tous vos déplacements</p>
            <div class="service-cta">
              Découvrir <i class="fa-solid fa-arrow-right"></i>
            </div>
          </div>
        </a>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       BIENS EN VEDETTE
  ═══════════════════════════════════════════════════════ -->
  <section class="section" style="background:var(--bg-secondary);border-radius:0;">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal">
          <i class="fa-solid fa-fire"></i> Sélection Premium
        </div>
        <h2 class="section-title reveal delay-1">Nos Biens en Vedette</h2>
        <p class="section-subtitle reveal delay-2">
          Découvrez notre sélection de logements exceptionnels, choisis pour leur qualité et leur situation.
        </p>
      </div>

      <div class="listing-grid">
        <?php if ($biens_vedette && mysqli_num_rows($biens_vedette) > 0): ?>
          <?php while ($bien = mysqli_fetch_assoc($biens_vedette)): ?>
          <div class="listing-card reveal">
            <div class="listing-card-image">
              <img src="<?php echo htmlspecialchars($bien['image_principale'] ?? '../image/villa.webp'); ?>" 
                   alt="<?php echo htmlspecialchars($bien['titre']); ?>"
                   loading="lazy">
              <div class="listing-card-badge">
                <span class="badge badge-primary"><?php echo ucfirst($bien['type_bien']); ?></span>
              </div>
              <button class="listing-card-fav" data-type="bien" data-id="<?php echo $bien['id']; ?>" aria-label="Ajouter aux favoris">
                <i class="fa-regular fa-heart"></i>
              </button>
            </div>
            <div class="listing-card-body">
              <h3 class="listing-card-title"><?php echo htmlspecialchars($bien['titre']); ?></h3>
              <div class="listing-card-location">
                <i class="fa-solid fa-location-dot"></i>
                <span><?php echo htmlspecialchars($bien['ville']); ?></span>
              </div>
              <div class="listing-card-features">
                <span class="listing-feat"><i class="fa-solid fa-bed"></i> <?php echo $bien['nb_chambres']; ?> ch.</span>
                <span class="listing-feat"><i class="fa-solid fa-shower"></i> <?php echo $bien['nb_salles_bain']; ?> sdb</span>
                <span class="listing-feat"><i class="fa-solid fa-expand"></i> <?php echo $bien['surface']; ?>m²</span>
                <span class="listing-feat"><i class="fa-solid fa-user-group"></i> <?php echo $bien['nb_personnes']; ?> pers.</span>
              </div>
              <div class="listing-card-footer">
                <div class="listing-price">
                  <span class="listing-price-amount"><?php echo number_format($bien['prix_nuit'], 0, ',', ' '); ?> DH</span>
                  <span class="listing-price-unit">/ nuit</span>
                </div>
                <div class="listing-rating">
                  <i class="fa-solid fa-star"></i>
                  <span><?php echo number_format($bien['note_moyenne'], 1); ?></span>
                  <span style="color:var(--text-muted);">(<?php echo $bien['nb_avis']; ?>)</span>
                </div>
              </div>
              <a href="detail-bien.php?id=<?php echo $bien['id']; ?>" class="btn btn-primary w-full mt-md">
                <i class="fa-solid fa-eye"></i> Voir le bien
              </a>
            </div>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <!-- Placeholder cards si pas de données -->
          <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="listing-card reveal">
            <div class="listing-card-image">
              <img src="../image/villa.jpg" alt="Villa disponible" loading="lazy">
              <div class="listing-card-badge"><span class="badge badge-primary">Villa</span></div>
              <button class="listing-card-fav" data-type="bien" data-id="<?php echo $i+1; ?>">
                <i class="fa-regular fa-heart"></i>
              </button>
            </div>
            <div class="listing-card-body">
              <h3 class="listing-card-title">Villa avec Piscine</h3>
              <div class="listing-card-location"><i class="fa-solid fa-location-dot"></i> Meknès</div>
              <div class="listing-card-features">
                <span class="listing-feat"><i class="fa-solid fa-bed"></i> 4 ch.</span>
                <span class="listing-feat"><i class="fa-solid fa-shower"></i> 2 sdb</span>
                <span class="listing-feat"><i class="fa-solid fa-expand"></i> 250m²</span>
              </div>
              <div class="listing-card-footer">
                <div class="listing-price">
                  <span class="listing-price-amount">1 200 DH</span>
                  <span class="listing-price-unit">/ nuit</span>
                </div>
                <div class="listing-rating"><i class="fa-solid fa-star"></i> 4.8 (24)</div>
              </div>
              <a href="villa.php" class="btn btn-primary w-full mt-md"><i class="fa-solid fa-eye"></i> Voir le bien</a>
            </div>
          </div>
          <?php endfor; ?>
        <?php endif; ?>
      </div>

      <div class="text-center" style="margin-top:2rem;">
        <a href="recherche.php" class="btn btn-outline btn-lg reveal">
          <i class="fa-solid fa-grid-2"></i> Voir toutes les annonces
        </a>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       VOITURES EN VEDETTE
  ═══════════════════════════════════════════════════════ -->
  <section class="section">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal">
          <i class="fa-solid fa-car"></i> Flotte Premium
        </div>
        <h2 class="section-title reveal delay-1">Voitures Disponibles</h2>
        <p class="section-subtitle reveal delay-2">
          Des véhicules récents, entretenus et assurés pour tous vos déplacements au Maroc.
        </p>
      </div>

      <div class="cars-grid">
        <?php if ($voitures_vedette && mysqli_num_rows($voitures_vedette) > 0): ?>
          <?php while ($car = mysqli_fetch_assoc($voitures_vedette)): ?>
          <div class="car-card reveal">
            <div class="car-card-image">
              <img src="<?php echo htmlspecialchars($car['image_principale'] ?? '../image/v1.jpg'); ?>" 
                   alt="<?php echo htmlspecialchars($car['marque'].' '.$car['modele']); ?>"
                   loading="lazy">
              <button class="listing-card-fav" data-type="voiture" data-id="<?php echo $car['id']; ?>">
                <i class="fa-regular fa-heart"></i>
              </button>
              <div class="car-badge">
                <span class="badge badge-accent"><?php echo ucfirst($car['carburant']); ?></span>
              </div>
            </div>
            <div class="car-card-body">
              <h3 class="car-title"><?php echo htmlspecialchars($car['marque'].' '.$car['modele']); ?></h3>
              <div class="car-meta">
                <span><i class="fa-solid fa-calendar"></i> <?php echo $car['annee']; ?></span>
                <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($car['ville']); ?></span>
                <span><i class="fa-solid fa-users"></i> <?php echo $car['nb_places']; ?> places</span>
              </div>
              <div class="car-features">
                <?php if ($car['climatisation']): ?><span class="car-feat"><i class="fa-solid fa-snowflake"></i> Clim</span><?php endif; ?>
                <?php if ($car['gps']): ?><span class="car-feat"><i class="fa-solid fa-map-location-dot"></i> GPS</span><?php endif; ?>
                <span class="car-feat"><i class="fa-solid fa-gear"></i> <?php echo ucfirst($car['boite']); ?></span>
              </div>
              <div class="car-footer">
                <div class="listing-price">
                  <span class="listing-price-amount"><?php echo number_format($car['prix_jour'], 0, ',', ' '); ?> DH</span>
                  <span class="listing-price-unit">/ jour</span>
                </div>
                <a href="detail-voiture.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-sm">
                  Réserver <i class="fa-solid fa-arrow-right"></i>
                </a>
              </div>
            </div>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <!-- Placeholder voitures -->
          <?php 
          $cars_demo = [
            ['img'=>'v1.jpg','nom'=>'Volkswagen Golf 8','annee'=>2023,'prix'=>500,'carb'=>'Essence','boite'=>'Automatique'],
            ['img'=>'dacia.jpg','nom'=>'Dacia Duster','annee'=>2022,'prix'=>350,'carb'=>'Diesel','boite'=>'Manuelle'],
            ['img'=>'v2.jpg','nom'=>'Mercedes Classe C','annee'=>2024,'prix'=>800,'carb'=>'Hybride','boite'=>'Automatique'],
            ['img'=>'v3.jpg','nom'=>'Toyota Yaris','annee'=>2021,'prix'=>280,'carb'=>'Essence','boite'=>'Automatique'],
          ];
          foreach ($cars_demo as $i => $car): ?>
          <div class="car-card reveal delay-<?php echo $i; ?>">
            <div class="car-card-image">
              <img src="../image/<?php echo $car['img']; ?>" alt="<?php echo $car['nom']; ?>" loading="lazy">
              <button class="listing-card-fav" data-type="voiture" data-id="<?php echo $i+1; ?>">
                <i class="fa-regular fa-heart"></i>
              </button>
              <div class="car-badge"><span class="badge badge-accent"><?php echo $car['carb']; ?></span></div>
            </div>
            <div class="car-card-body">
              <h3 class="car-title"><?php echo $car['nom']; ?></h3>
              <div class="car-meta">
                <span><i class="fa-solid fa-calendar"></i> <?php echo $car['annee']; ?></span>
                <span><i class="fa-solid fa-location-dot"></i> Meknès</span>
                <span><i class="fa-solid fa-users"></i> 5 places</span>
              </div>
              <div class="car-features">
                <span class="car-feat"><i class="fa-solid fa-snowflake"></i> Clim</span>
                <span class="car-feat"><i class="fa-solid fa-gear"></i> <?php echo $car['boite']; ?></span>
              </div>
              <div class="car-footer">
                <div class="listing-price">
                  <span class="listing-price-amount"><?php echo $car['prix']; ?> DH</span>
                  <span class="listing-price-unit">/ jour</span>
                </div>
                <a href="voiturre.php" class="btn btn-primary btn-sm">Réserver <i class="fa-solid fa-arrow-right"></i></a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="text-center" style="margin-top:2rem;">
        <a href="voiturre.php" class="btn btn-outline btn-lg reveal">
          <i class="fa-solid fa-car-side"></i> Voir toutes les voitures
        </a>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       POURQUOI NOUS
  ═══════════════════════════════════════════════════════ -->
  <section class="section why-section">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal">
          <i class="fa-solid fa-shield-halved"></i> Nos Atouts
        </div>
        <h2 class="section-title reveal delay-1">Pourquoi Nous Choisir ?</h2>
        <p class="section-subtitle reveal delay-2">
          Plus qu'une plateforme, c'est une expérience de confiance à chaque étape.
        </p>
      </div>

      <div class="why-grid">
        <div class="why-card reveal">
          <div class="why-icon"><i class="fa-solid fa-shield-halved"></i></div>
          <h3>100% Sécurisé</h3>
          <p>Paiements cryptés, biens vérifiés, propriétaires certifiés. Votre sécurité est notre priorité.</p>
        </div>
        <div class="why-card reveal delay-1">
          <div class="why-icon" style="background:var(--accent-glow);color:var(--accent);"><i class="fa-solid fa-bolt"></i></div>
          <h3>Réservation Instantanée</h3>
          <p>Réservez en quelques clics, confirmation immédiate par email avec tous les détails.</p>
        </div>
        <div class="why-card reveal delay-2">
          <div class="why-icon" style="background:var(--success-glow);color:var(--success);"><i class="fa-solid fa-headset"></i></div>
          <h3>Support 7j/7</h3>
          <p>Notre équipe est disponible 7 jours sur 7 pour vous accompagner avant, pendant et après.</p>
        </div>
        <div class="why-card reveal delay-3">
          <div class="why-icon" style="background:rgba(255,190,11,0.2);color:var(--warning);"><i class="fa-solid fa-star"></i></div>
          <h3>Qualité Garantie</h3>
          <p>Chaque bien est inspecté et noté par de vrais clients. Seulement le meilleur pour vous.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       HOW IT WORKS
  ═══════════════════════════════════════════════════════ -->
  <section class="section" style="background:var(--bg-secondary);">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal"><i class="fa-solid fa-map"></i> Simple & Rapide</div>
        <h2 class="section-title reveal delay-1">Comment ça marche ?</h2>
        <p class="section-subtitle reveal delay-2">Réservez votre bien en 3 étapes simples</p>
      </div>

      <div class="steps-grid">
        <div class="step reveal">
          <div class="step-number">01</div>
          <div class="step-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
          <h3>Recherchez</h3>
          <p>Utilisez nos filtres avancés pour trouver le bien ou la voiture qui correspond exactement à vos besoins.</p>
        </div>
        <div class="step-connector reveal delay-1"></div>
        <div class="step reveal delay-2">
          <div class="step-number">02</div>
          <div class="step-icon" style="background:var(--accent-glow);color:var(--accent);"><i class="fa-solid fa-calendar-check"></i></div>
          <h3>Réservez</h3>
          <p>Choisissez vos dates, remplissez le formulaire de réservation et procédez au paiement sécurisé.</p>
        </div>
        <div class="step-connector reveal delay-3"></div>
        <div class="step reveal delay-4">
          <div class="step-number">03</div>
          <div class="step-icon" style="background:var(--success-glow);color:var(--success);"><i class="fa-solid fa-key"></i></div>
          <h3>Profitez</h3>
          <p>Recevez votre confirmation et les clés de votre location. Profitez de votre séjour en toute sérénité.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       TÉMOIGNAGES
  ═══════════════════════════════════════════════════════ -->
  <section class="section">
    <div class="container">
      <div class="section-header">
        <div class="section-badge reveal"><i class="fa-solid fa-comment-dots"></i> Témoignages</div>
        <h2 class="section-title reveal delay-1">Ce que disent nos clients</h2>
        <p class="section-subtitle reveal delay-2">Plus de <?php echo $nb_reservations + 50; ?> avis vérifiés de clients satisfaits</p>
      </div>

      <div class="testimonials-slider" id="testimonials">
        <?php if ($avis_recents && mysqli_num_rows($avis_recents) > 0): ?>
          <?php while ($avis = mysqli_fetch_assoc($avis_recents)): ?>
          <div class="testimonial-card reveal">
            <div class="testimonial-stars">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fa-solid fa-star" style="color:<?php echo $s <= $avis['note'] ? 'var(--warning)' : 'var(--text-disabled)'; ?>"></i>
              <?php endfor; ?>
            </div>
            <p class="testimonial-text">"<?php echo htmlspecialchars($avis['commentaire']); ?>"</p>
            <div class="testimonial-author">
              <div class="testimonial-avatar">
                <?php echo strtoupper(substr($avis['prenom'], 0, 1) . substr($avis['nom'], 0, 1)); ?>
              </div>
              <div>
                <div class="testimonial-name"><?php echo htmlspecialchars($avis['prenom'].' '.$avis['nom']); ?></div>
                <div class="testimonial-date"><?php echo date('M Y', strtotime($avis['date_creation'])); ?></div>
              </div>
            </div>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <!-- Placeholder avis -->
          <?php 
          $demo_avis = [
            ['nom'=>'Fatima Z.','note'=>5,'comment'=>'Service exceptionnel ! La villa était encore plus belle qu\'en photos. Je recommande vivement Immo-Location !'],
            ['nom'=>'Ahmed B.','note'=>5,'comment'=>'Réservation ultra simple, voiture parfaite et propriétaire très accueillant. 10/10 !'],
            ['nom'=>'Laila M.','note'=>4,'comment'=>'Très bon appartement, bien situé et propre. La plateforme est facile à utiliser. Super expérience !'],
          ];
          foreach ($demo_avis as $i => $av): ?>
          <div class="testimonial-card reveal delay-<?php echo $i; ?>">
            <div class="testimonial-stars">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fa-solid fa-star" style="color:<?php echo $s <= $av['note'] ? 'var(--warning)' : 'var(--text-disabled)'; ?>"></i>
              <?php endfor; ?>
            </div>
            <p class="testimonial-text">"<?php echo $av['comment']; ?>"</p>
            <div class="testimonial-author">
              <div class="testimonial-avatar"><?php echo strtoupper(substr($av['nom'],0,2)); ?></div>
              <div>
                <div class="testimonial-name"><?php echo $av['nom']; ?></div>
                <div class="testimonial-date">Juin 2026</div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="text-center" style="margin-top:2rem;">
        <a href="avis.php" class="btn btn-outline reveal">
          <i class="fa-solid fa-star"></i> Voir tous les avis
        </a>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════
       CTA SECTION
  ═══════════════════════════════════════════════════════ -->
  <section class="cta-section reveal">
    <div class="container">
      <div class="cta-card">
        <div class="cta-glow"></div>
        <div class="cta-content">
          <div class="section-badge" style="margin-bottom:1.5rem;">
            <i class="fa-solid fa-rocket"></i> Commencez maintenant
          </div>
          <h2 class="cta-title">Prêt à trouver votre location idéale ?</h2>
          <p class="cta-desc">
            Rejoignez des milliers de clients satisfaits. Créez votre compte gratuitement et accédez 
            à toutes nos offres premium.
          </p>
          <div class="cta-actions">
            <a href="inscription.php" class="btn btn-primary btn-lg">
              <i class="fa-solid fa-user-plus"></i> Créer un compte gratuit
            </a>
            <a href="recherche.php" class="btn btn-secondary btn-lg">
              <i class="fa-solid fa-magnifying-glass"></i> Explorer les annonces
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php include '../includes/footer.php'; ?>

<script>
// ── HERO SLIDESHOW ──────────────────────────────────────────
(function() {
  const slides = document.querySelectorAll('.hero-slide');
  const dots   = document.querySelectorAll('.hero-dot');
  let current  = 0;
  let interval;

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = (index + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
  }

  function next() { goTo(current + 1); }

  interval = setInterval(next, 5000);

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      clearInterval(interval);
      goTo(i);
      interval = setInterval(next, 5000);
    });
  });
})();

// ── SEARCH TABS ─────────────────────────────────────────────
document.querySelectorAll('.search-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('search-type-input').value = tab.dataset.type;

    // Toggle guests/voiture fields
    const guestsField = document.getElementById('guests-field');
    if (tab.dataset.type === 'voiture') {
      guestsField.style.display = 'none';
    } else {
      guestsField.style.display = 'flex';
    }
  });
});

// ── PARTICLES ───────────────────────────────────────────────
(function() {
  const container = document.getElementById('particles');
  if (!container) return;
  for (let i = 0; i < 20; i++) {
    const particle = document.createElement('div');
    particle.style.cssText = `
      position: absolute;
      width: ${Math.random() * 4 + 1}px;
      height: ${Math.random() * 4 + 1}px;
      background: rgba(245, 166, 35, ${Math.random() * 0.5 + 0.1});
      border-radius: 50%;
      top: ${Math.random() * 100}%;
      left: ${Math.random() * 100}%;
      animation: float ${Math.random() * 6 + 4}s ease-in-out infinite;
      animation-delay: ${Math.random() * 3}s;
    `;
    container.appendChild(particle);
  }
})();

// ── COUNTER ANIMATION ───────────────────────────────────────
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