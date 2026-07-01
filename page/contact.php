<?php
session_start();
include '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../image/favicon.png">
  <link rel="apple-touch-icon" href="../image/favicon.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact — Immo-Location</title>
  <meta name="description" content="Contactez l'équipe d'Immo-Location pour toute question concernant la location d'appartements, villas ou voitures au Maroc.">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Icon Libraries -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/contact.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<main style="padding-top: 80px;">

  <!-- ── Page Hero ──────────────────────────────────── -->
  <div class="page-hero">
    <div class="container">
      <div class="section-badge"><i class="fa-solid fa-envelope"></i> Contactez-nous</div>
      <h1 class="page-hero-title">
        Restons en <span style="background:var(--gradient-gold);background-size:200%;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;">Contact</span>
      </h1>
      <p class="page-hero-subtitle">
        Une question, une suggestion ou besoin d'assistance ? Notre équipe est à votre écoute 7j/7.
      </p>
      <nav class="breadcrumb">
        <a href="acceuil.php">Accueil</a>
        <span class="breadcrumb-sep">/</span>
        <span>Contact</span>
      </nav>
    </div>
  </div>

  <!-- ── Main Content ────────────────────────────────── -->
  <section class="section">
    <div class="container">
      
      <!-- Notifications / Feedback -->
      <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success" style="margin-bottom: var(--space-xl);">
        <i class="fa-solid fa-circle-check"></i>
        <span>Votre message a été envoyé avec succès ! Nous vous répondrons très prochainement.</span>
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-error" style="margin-bottom: var(--space-xl);">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($_GET['error']); ?></span>
      </div>
      <?php endif; ?>

      <div class="contact-layout">
        
        <!-- LEFT COLUMN: Contact details -->
        <div class="contact-info-panel">
          
          <!-- Adresse -->
          <div class="contact-info-card">
            <div class="contact-info-icon">
              <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div class="contact-info-details">
              <h4>Adresse</h4>
              <p>Belle Vue, Meknès 50000, Maroc</p>
            </div>
          </div>
          
          <!-- Téléphone -->
          <div class="contact-info-card">
            <div class="contact-info-icon">
              <i class="fa-solid fa-phone-volume"></i>
            </div>
            <div class="contact-info-details">
              <h4>Téléphone</h4>
              <p><a href="tel:+212690869233" style="color: inherit;">+212 690 869 233</a></p>
            </div>
          </div>
          
          <!-- Email -->
          <div class="contact-info-card">
            <div class="contact-info-icon">
              <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div class="contact-info-details">
              <h4>E-mail</h4>
              <p><a href="mailto:contact@immolocation.ma" style="color: inherit;">contact@immolocation.ma</a></p>
            </div>
          </div>
          
          <!-- Horaires -->
          <div class="contact-info-card">
            <div class="contact-info-icon">
              <i class="fa-solid fa-business-time"></i>
            </div>
            <div class="contact-info-details">
              <h4>Horaires</h4>
              <p>Lundi - Samedi : 8h00 - 18h00</p>
            </div>
          </div>
          
        </div>

        <!-- RIGHT COLUMN: Contact form -->
        <div class="contact-form-panel">
          <h3 class="contact-form-title">Envoyez-nous un message</h3>
          <p class="contact-form-desc">Notre équipe commerciale ou support vous répondra sous 24 heures.</p>
          
          <form action="../php/contact-handler.php" method="POST" style="margin-top: var(--space-lg);">
            
            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="nom">Nom complet <span style="color: var(--danger);">*</span></label>
                <input type="text" id="nom" name="nom" class="form-control" placeholder="Ex: Nourdine El" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="email">Adresse e-mail <span style="color: var(--danger);">*</span></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Ex: nourdine@gmail.com" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="sujet">Sujet</label>
              <input type="text" id="sujet" name="sujet" class="form-control" placeholder="Ex: Renseignement réservation ou partenariat">
            </div>

            <div class="form-group">
              <label class="form-label" for="message">Message <span style="color: var(--danger);">*</span></label>
              <textarea id="message" name="message" class="form-control" rows="5" placeholder="Écrivez votre message ici..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-md);">
              <i class="fa-solid fa-paper-plane"></i> Envoyer le message
            </button>
            
          </form>
        </div>

      </div>

      <!-- Interactive Map -->
      <div class="contact-map-wrapper">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13249.208154562413!2d-5.5560867822998!3d33.88219565576251!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda0708b3558f623%3A0xc3b8606d09a7fcb8!2sMekn%C3%A8s!5e0!3m2!1sfr!2sma!4v1700000000000!5m2!1sfr!2sma" 
          width="100%" 
          height="100%" 
          style="border:0;" 
          allowfullscreen="" 
          loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>

    </div>
  </section>

</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>