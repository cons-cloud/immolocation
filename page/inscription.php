<?php
session_start();
include '../includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom       = htmlspecialchars(trim($_POST['prenom'] ?? ''));
    $nom          = htmlspecialchars(trim($_POST['nom'] ?? ''));
    $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $telephone    = htmlspecialchars(trim($_POST['telephone'] ?? ''));
    $type_compte  = $_POST['type_compte'] ?? 'client';
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $ville        = htmlspecialchars(trim($_POST['ville'] ?? ''));

    // Validation
    if (!$prenom || !$nom || !$email || !$password) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (!in_array($type_compte, ['client', 'proprietaire'])) {
        $error = 'Type de compte invalide.';
    } else {
        // Check email exists
        $check = mysqli_prepare($conn, "SELECT id FROM utilisateurs WHERE email = ?");
        mysqli_stmt_bind_param($check, 's', $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 
                "INSERT INTO utilisateurs (prenom, nom, email, telephone, mot_de_passe, type_compte, ville, statut, email_verifie) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'actif', 0)");
            mysqli_stmt_bind_param($stmt, 'sssssss', $prenom, $nom, $email, $telephone, $hash, $type_compte, $ville);
            
            if (mysqli_stmt_execute($stmt)) {
                header('Location: connexion.php?registered=1');
                exit();
            } else {
                $error = 'Erreur lors de l\'inscription. Veuillez réessayer.';
            }
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
  <title>Créer un compte — Immo-Location</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-body">

<div class="auth-layout">
  <!-- Left -->
  <div class="auth-left">
    <div class="auth-left-content">
      <a href="acceuil.php" class="auth-logo"><span>Immo</span><span style="color:var(--primary);">-Location</span></a>
      <h2 class="auth-left-title">Rejoignez notre communauté</h2>
      <p class="auth-left-desc">Créez votre compte gratuitement et accédez à des milliers d'offres de location au Maroc.</p>
      <div class="auth-features">
        <div class="auth-feature"><i class="fa-solid fa-user-plus"></i><span>Inscription gratuite et rapide</span></div>
        <div class="auth-feature"><i class="fa-solid fa-heart"></i><span>Sauvegardez vos favoris</span></div>
        <div class="auth-feature"><i class="fa-solid fa-bell"></i><span>Notifications en temps réel</span></div>
        <div class="auth-feature"><i class="fa-solid fa-house"></i><span>Publiez vos biens (propriétaires)</span></div>
      </div>
    </div>
    <div class="auth-left-bg" style="background-image:url('../image/apparte.jpg')"></div>
  </div>

  <!-- Right -->
  <div class="auth-right">
    <div class="auth-form-wrapper">
      <div class="auth-form-header">
        <h1>Créer un compte ✨</h1>
        <p>Remplissez le formulaire pour commencer</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><?php echo $error; ?></div>
      <?php endif; ?>

      <!-- Steps indicator -->
      <div class="form-steps" id="steps-indicator">
        <div class="form-step-item active" id="step-dot-1">
          <div style="text-align:center;">
            <div class="form-step-dot">1</div>
            <div class="form-step-label">Compte</div>
          </div>
        </div>
        <div class="form-step-line"></div>
        <div class="form-step-item" id="step-dot-2">
          <div style="text-align:center;">
            <div class="form-step-dot">2</div>
            <div class="form-step-label">Profil</div>
          </div>
        </div>
        <div class="form-step-line"></div>
        <div class="form-step-item" id="step-dot-3">
          <div style="text-align:center;">
            <div class="form-step-dot">3</div>
            <div class="form-step-label">Sécurité</div>
          </div>
        </div>
      </div>

      <form method="POST" id="register-form">

        <!-- Step 1: Type de compte -->
        <div class="form-panel active" id="panel-1">
          <div class="form-group">
            <label class="form-label">Quel est votre profil ?</label>
            <div class="type-selector">
              <div class="type-btn active" id="type-client" onclick="selectType('client')">
                <i class="fa-solid fa-user"></i>
                <span>Client</span>
                <small>Je cherche une location</small>
              </div>
              <div class="type-btn" id="type-proprio" onclick="selectType('proprietaire')">
                <i class="fa-solid fa-house"></i>
                <span>Propriétaire</span>
                <small>Je propose des locations</small>
              </div>
            </div>
            <input type="hidden" name="type_compte" id="type-compte-input" value="client">
          </div>
          <div class="form-group">
            <label class="form-label">Adresse e-mail *</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-envelope input-icon"></i>
              <input type="email" name="email" class="form-control" placeholder="vous@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
          </div>
          <button type="button" class="btn btn-primary w-full btn-lg" onclick="nextStep(1)">
            Continuer <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>

        <!-- Step 2: Profil -->
        <div class="form-panel" id="panel-2">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="form-label">Prénom *</label>
              <input type="text" name="prenom" class="form-control" placeholder="Votre prénom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Nom *</label>
              <input type="text" name="nom" class="form-control" placeholder="Votre nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Téléphone</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-phone input-icon"></i>
              <input type="tel" name="telephone" class="form-control" placeholder="+212 6XX XXX XXX" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Ville</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-location-dot input-icon"></i>
              <input type="text" name="ville" class="form-control" placeholder="Votre ville" value="<?php echo htmlspecialchars($_POST['ville'] ?? ''); ?>">
            </div>
          </div>
          <div style="display:flex;gap:0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
              <i class="fa-solid fa-arrow-left"></i> Retour
            </button>
            <button type="button" class="btn btn-primary" style="flex:1;" onclick="nextStep(2)">
              Continuer <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- Step 3: Sécurité -->
        <div class="form-panel" id="panel-3">
          <div class="form-group">
            <label class="form-label">Mot de passe *</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-lock input-icon"></i>
              <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 caractères" required>
              <button type="button" class="input-toggle" onclick="togglePassword('password', this)"><i class="fa-solid fa-eye"></i></button>
            </div>
            <!-- Password strength -->
            <div class="pwd-strength" id="pwd-strength" style="margin-top:0.5rem;display:none;">
              <div class="pwd-bar"><div class="pwd-fill" id="pwd-fill"></div></div>
              <span id="pwd-label" style="font-size:0.75rem;color:var(--text-muted);"></span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirmer le mot de passe *</label>
            <div class="input-wrapper">
              <i class="fa-solid fa-lock-keyhole input-icon"></i>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Répétez le mot de passe" required>
              <button type="button" class="input-toggle" onclick="togglePassword('confirm_password', this)"><i class="fa-solid fa-eye"></i></button>
            </div>
          </div>
          <label class="checkbox-item" style="margin-bottom:1.5rem;">
            <input type="checkbox" name="cgu" required>
            <span>J'accepte les <a href="#" style="color:var(--primary);">CGU</a> et la <a href="#" style="color:var(--primary);">politique de confidentialité</a></span>
          </label>
          <div style="display:flex;gap:0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="prevStep(3)"><i class="fa-solid fa-arrow-left"></i> Retour</button>
            <button type="submit" class="btn btn-primary" style="flex:1;" id="submit-btn">
              <i class="fa-solid fa-user-plus"></i> Créer mon compte
            </button>
          </div>
        </div>

      </form>

      <p class="auth-switch" style="margin-top:1.5rem;">
        Déjà un compte ? <a href="connexion.php">Se connecter</a>
      </p>
    </div>
  </div>
</div>

<script src="../js/main.js"></script>
<script>
let currentStep = 1;

function selectType(type) {
  document.getElementById('type-compte-input').value = type;
  document.getElementById('type-client').classList.toggle('active', type === 'client');
  document.getElementById('type-proprio').classList.toggle('active', type === 'proprietaire');
}

function nextStep(from) {
  // Basic validation
  const panel = document.getElementById('panel-' + from);
  const required = panel.querySelectorAll('[required]');
  let valid = true;
  required.forEach(f => {
    if (!f.value.trim()) {
      f.classList.add('error');
      f.style.borderColor = 'var(--danger)';
      valid = false;
    }
  });
  if (!valid) {
    showToast('Veuillez remplir tous les champs obligatoires', 'error');
    return;
  }

  // Email validation
  if (from === 1) {
    const email = panel.querySelector('[type=email]');
    if (!email.value.includes('@') || !email.value.includes('.')) {
      showToast('Email invalide', 'error');
      email.style.borderColor = 'var(--danger)';
      return;
    }
  }

  document.getElementById('panel-' + from).classList.remove('active');
  document.getElementById('panel-' + (from + 1)).classList.add('active');

  // Update dots
  document.getElementById('step-dot-' + from).classList.remove('active');
  document.getElementById('step-dot-' + from).classList.add('done');
  document.getElementById('step-dot-' + (from + 1)).classList.add('active');
  currentStep = from + 1;
}

function prevStep(from) {
  document.getElementById('panel-' + from).classList.remove('active');
  document.getElementById('panel-' + (from - 1)).classList.add('active');
  document.getElementById('step-dot-' + from).classList.remove('active');
  document.getElementById('step-dot-' + (from - 1)).classList.remove('done');
  document.getElementById('step-dot-' + (from - 1)).classList.add('active');
  currentStep = from - 1;
}

function togglePassword(id, btn) {
  const input = document.getElementById(id);
  const icon = btn.querySelector('i');
  input.type = input.type === 'password' ? 'text' : 'password';
  icon.className = input.type === 'text' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}

// Password strength
document.getElementById('password')?.addEventListener('input', function() {
  const val = this.value;
  const strength = document.getElementById('pwd-strength');
  const fill = document.getElementById('pwd-fill');
  const label = document.getElementById('pwd-label');

  if (!val) { strength.style.display = 'none'; return; }
  strength.style.display = 'block';

  let score = 0;
  if (val.length >= 6) score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    {pct:'20%',color:'var(--danger)',text:'Très faible'},
    {pct:'40%',color:'#ff6b35',text:'Faible'},
    {pct:'60%',color:'var(--warning)',text:'Moyen'},
    {pct:'80%',color:'#90e0ef',text:'Fort'},
    {pct:'100%',color:'var(--success)',text:'Très fort'},
  ];

  const lvl = levels[Math.min(score-1, 4)] || levels[0];
  fill.style.width = lvl.pct;
  fill.style.background = lvl.color;
  label.textContent = lvl.text;
  label.style.color = lvl.color;
});

// Clear error styles on input
document.querySelectorAll('.form-control').forEach(f => {
  f.addEventListener('input', () => {
    f.style.borderColor = '';
    f.classList.remove('error');
  });
});
</script>
<style>
.pwd-bar { height: 4px; background: var(--border); border-radius: 2px; margin-bottom: 4px; overflow: hidden; }
.pwd-fill { height: 100%; border-radius: 2px; transition: all 0.4s; width: 0; }
</style>
</body></html>