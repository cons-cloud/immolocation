<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
       <link rel="stylesheet" href="../css/accueil.css">

</head>

/* Cards grid */
.cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; }
.card { background: var(--white); padding: 1.25rem; border-radius: 12px; box-shadow: 0 10px 30px rgba(16,24,40,0.06); text-decoration: none; color: inherit; display: block; transition: transform .22s ease, box-shadow .22s ease; position: relative; overflow: hidden; }
.card:hover{ transform: translateY(-6px) scale(1.01); box-shadow: 0 18px 46px rgba(16,24,40,0.12); animation: tilt-wobble 0.9s ease; }
.card::before {
    content: '';
    position: absolute;
    inset: -40%;
    background: radial-gradient(circle at center, rgba(0,85,164,0.12), transparent 55%);
    opacity: 0;
    transform: scale(0.8);
    transition: opacity .25s ease, transform .25s ease;
}
.card:hover::before { opacity: 1; transform: scale(1); }

<body>
    <div>
        <ul class="menu">
            <li><a href="acceuil.php">acceuil</a></li>
             <li><a href="a propos.php">a propos</a></li>
              <li><a href="contact.php">contact</a></li>
               <li><a href="inscription.php">inscription</a></li>
                 <li><a href="connexion.php">connexion</a></li>
                 <li><a href="#">service</a></li>
        </ul>
         <footer>
              <nav class="nav">

        <div class="nav-brand">
            <h1>Immo Location</h1>
        </div>
        <ul class="nav-menu">
        
            <li><a href="acceuil.php">acceuil</a></li>
<li>
           <div class="dropdown">
  <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    service
  </button>
  <ul class="dropdown-menu">
    <li><button class="dropdown-item" type="button"><a href="voiture.html">voiture</button></a></li>
    <li><button class="dropdown-item" type="button"><a href="appartement.html">appartement</a></button></li>
    <li><button class="dropdown-item" type="button"><a href="villa.html">villa</a></button></li>
    
</li>
</li>
</ul>
</div>
             <li><a href="a propos.html">a propos</a></li>
              <li><a href="contact.html">contact</a></li>
               <li><a href="inscription.html">inscription</a></li>
                 <li><a href="connexion.php">connexion</a></li>
                 
        </ul>
       
    
    </nav>
      <footer>
        <div class="service">
          <p>services</p><br>
          <p>voitures</p>
          <p>maisons</p>
          <p>villas</p>
          <p>appartement</p>
        </div><br>
       <div class="contact">
    <p>contactez-nous</p><br>

    <i class="fa-solid fa-location-dot"></i>
    <p>belle vue, meknes 5000, maroc</p><br>

    <i class="fa-solid fa-phone"></i>
    <p>0690869233</p><br>

    <i class="fa-solid fa-envelope"></i>
    <p>abdoulkarimnourdine78@gmail.com</p>
</div>
    </footer>
 
</body>
</html>