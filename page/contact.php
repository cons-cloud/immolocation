
  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
     <link rel="stylesheet" href="../css/contact.css">
     <link rel="stylesheet" href="../css/accueil.css">
<link rel="stylesheet" href="../css/contact.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
     <div class="contact-container">

  <!-- PARTIE GAUCHE -->
  <section class="contact">
    <div class="adresse">
      <h3>Adresse</h3>
      <p>maroc, belle vue belle vue 5000</p>
    </div>

    <div class="telephone">
      <h3>Téléphone</h3>
      <p>0690869233</p>
    </div>

    <div class="email">
      <h3>E-mail</h3>
      <p>abdoulkarimnourdine78gmail.com</p>
    </div>

    <div class="horaires">
      <h3>Horaires</h3>
      <p>Lundi - Samedi : 8h - 18h</p>
    </div>
  </section>

  <!-- PARTIE DROITE -->
  <section class="contact-form">
    <h2>Envoyez-nous un message</h2>

    <form action="form.php" method="get">

      <div class="nom">
        <input type="text" placeholder="Nom complet">
      </div>

      <div class="email">
        <input type="email" placeholder="Email">
      </div>

      <div class="sujet">
        <input type="text" placeholder="Sujet">
      </div>

      <div class="message">
        <textarea placeholder="Votre message..."></textarea>
      </div>

      <div class="bouton">
        <button>Envoyer le message</button>
      </div>

    </form>
  </section>

</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>