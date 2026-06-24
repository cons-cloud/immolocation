<?php
include '../config/config.php';

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $prenom = $_POST['prenom'];
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $type_compte = $_POST['type_compte'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password != $confirm_password){
        echo "Les mots de passe ne correspondent pas";
    } else {

$verif = mysqli_query($conn, "SELECT * FROM utilisateurs WHERE email='$email'");

if(mysqli_num_rows($verif) > 0){
    echo "Cet email existe déjà";
    exit();
}
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO utilisateurs
        (prenom, nom, email, telephone, type_compte, mot_de_passe)
        VALUES
        ('$prenom','$nom','$email','$telephone','$type_compte','$password_hash')";

        if(mysqli_query($conn,$sql)){
            header("Location: connexion.php");
            exit();
        } else {
            echo "Erreur lors de l'inscription";
        }
    }
}
?>













<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
     <link rel="stylesheet" href="../css/accueil.css">
     <link rel="stylesheet" href="../css/inscription.css">
</head>
<body>
     <?php include '../includes/header.php'; ?>
 <div class="form-container">
    <h1>Immo-Location</h1>
    <h2>Inscription</h2>
    <p>Créez votre compte gratuitement</p>

    <form method="POST">
        <label>Prénom</label>
        <input type="text" name="prenom"><br>
        <label>Nom</label>
        <input type="text" name="nom"><br>
        <label>Email</label>
        <input type="email" name="email"><br>
        <label>Téléphone</label>
        <input type="tel" name="telephone"><br>
        <label>Type de compte</label>
        <select name="type_compte">
            <option value="client">client(locataire)</option>
            <option value="proprietaire">proprietaire</option>
        </select><br>
        <label>Mot de passe</label>
        <input type="password" name="password"><br>
        <label>Confirmer le mot de passe</label>
        <input type="password" name="confirm_password"><br>
        <button type="submit">S'inscrire</button>
        <p>Déjà un compte ? Se connecter</p>
    </form>

</div>
    
    <?php include '../includes/footer.php'; ?>
 
    
</body>
</html>