<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
     <link rel="stylesheet" href="../css/accueil.css">
</head>
<body>
      <header class="header in-view">
        <nav class="nav">
           
            <div class="nav-brand">
            <h1>Immo<span style="color: #5f6472;">-Location</span></h1>
        </div>
            <ul class="nav-menu">
                
                    <li class="centre"><a href="acceuil.php">Accueil</a></li>
                    <li class="has-dropdown">
                        <a href="#" class="dropdown-toggle">Services▾</a>
                        <ul class="dropdown">
                            <li class="centre"><a href="appartement.php">Location d'Appartements</a></li>
                            <li class="centre"><a href="voiturre.php">Location de Voitures</a></li>
                       
                            <li class="centre"><a href="villa.php">Locattions de Villas</a></li>
                        
                        </ul>
                    </li>
                    <li class="centre"><a href="a propos.php">A propos</a></li>
                    <li class="centre"><a href="contact.php">Contact</a></li>

              
                    <li><a href="../php/connexion.php">Connexion</a></li>
                    <li><a href="inscription.php">Inscription</a></li>
                
            </ul>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>
    <img src="../image/apparte.jpg" height="250" width="250">
        <?php include '../includes/footer.php'; ?>
       
</body>
</html>