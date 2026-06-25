<?php
session_start();
include '../includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif'");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['prenom']     = $user['prenom'];
            $_SESSION['nom']        = $user['nom'];
            $_SESSION['email']      = $user['email'];
            $_SESSION['type_compte'] = $user['type_compte'];

            // Update last login
            mysqli_query($conn, "UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = {$user['id']}");

            // Redirect based on role
            $redirect = match($user['type_compte']) {
                'admin'        => 'dash/admin.php',
                'proprietaire' => 'dash/proprio.php',
                default        => 'acceuil.php',
            };
            header("Location: $redirect");
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

if (isset($_SESSION['user_id'])) {
    header('Location: acceuil.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — Immo-Location</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-body">

<div class="auth-layout">
  <!-- Left panel -->
  <div class="auth-left">
    <div class="auth-left-content">
      <a href="acceuil.php" class="auth-logo">
        <span>Immo</span><span style="color:var(--primary);">-Location</span>
      </a>
      <h2 class="auth-left-title">La meilleure plateforme de location au Maroc</h2>
      <p class="auth-left-desc">Appartements, villas et voitures — tout ce dont vous avez besoin pour un séjour parfait.</p>
      <div class="auth-features">
        <div class="auth-feature"><i class="fa-solid fa-shield-halved"></i><span>Paiements 100% sécurisés</span></div>
        <div class="auth-feature"><i class="fa-solid fa-bolt"></i><span>Réservation instantanée</span></div>
        <div class="auth-feature"><i class="fa-solid fa-headset"></i><span>Support 7j/7</span></div>
      </div>
      <div class="auth-testimonial">
        <div class="auth-testimonial-stars">
          <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
        </div>
        <p>"Service exceptionnel ! Villa parfaite, réservation simple. Je recommande à 100% !"</p>
        <span>— Fatima Z., Cliente satisfaite</span>
      </div>
    </div>
    <div class="auth-left-bg" style="background-image:url('../image/villa.jpg')"></div>
  </div>

  <!-- Right panel (form) -->
  <div class="auth-right">
    <div class="auth-form-wrapper">
      <div class="auth-form-header">
        <h1>Bon retour ! 👋</h1>
        <p>Connectez-vous pour accéder à votre espace</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        Inscription réussie ! Connectez-vous maintenant.
      </div>
      <?php endif; ?>

      <form method="POST" class="auth-form" id="login-form">
        <div class="form-group">
          <label class="form-label" for="email">Adresse e-mail</label>
          <div class="input-wrapper">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input type="email" id="email" name="email" class="form-control" 
                   placeholder="vous@example.com" required
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
            <label class="form-label" for="password" style="margin:0;">Mot de passe</label>
            <a href="#" style="font-size:0.82rem;color:var(--primary);">Mot de passe oublié ?</a>
          </div>
          <div class="input-wrapper">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" id="password" name="password" class="form-control" 
                   placeholder="Votre mot de passe" required>
            <button type="button" class="input-toggle" onclick="togglePassword('password', this)">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>

        <label class="checkbox-item" style="margin-bottom:1.5rem;">
          <input type="checkbox" name="remember">
          <span>Se souvenir de moi</span>
        </label>

        <button type="submit" class="btn btn-primary w-full btn-lg" id="submit-btn">
          <i class="fa-solid fa-right-to-bracket"></i>
          Se connecter
        </button>
      </form>

      <div class="auth-divider"><span>ou continuer avec</span></div>

      <div class="auth-social">
        <button class="auth-social-btn" onclick="showToast('Connexion Google disponible bientôt', 'info')">
          <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285f4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34a853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#fbbc05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#ea4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          Google
        </button>
        <button class="auth-social-btn" onclick="showToast('Connexion Facebook disponible bientôt', 'info')">
          <i class="fa-brands fa-facebook-f" style="color:#1877f2;"></i>
          Facebook
        </button>
      </div>

      <p class="auth-switch">
        Pas encore de compte ? 
        <a href="inscription.php">Créer un compte gratuitement</a>
      </p>
    </div>
  </div>
</div>

<script src="../js/main.js"></script>
<script>
function togglePassword(id, btn) {
  const input = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa-solid fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa-solid fa-eye';
  }
}

document.getElementById('login-form')?.addEventListener('submit', function(e) {
  const btn = document.getElementById('submit-btn');
  btn.classList.add('loading');
  btn.disabled = true;
  // Form will submit normally; this is just UX
  setTimeout(() => { btn.classList.remove('loading'); btn.disabled = false; }, 5000);
});
</script>
</body></html>
