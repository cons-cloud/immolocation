<?php
// Détection dynamique du préfixe de chemin vers la racine du projet
$path_prefix = '';
if (file_exists('includes/config.php')) {
    $path_prefix = '';
} elseif (file_exists('../includes/config.php')) {
    $path_prefix = '../';
} elseif (file_exists('../../includes/config.php')) {
    $path_prefix = '../../';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Loading Screen -->
<div id="loading-screen" class="loading-screen">
  <div class="spinner"></div>
  <span style="color: var(--text-muted); font-size: 0.85rem;">Chargement...</span>
</div>

<!-- Navigation -->
<nav class="nav" id="main-nav">
  <div class="nav-brand">
    <a href="<?php echo $path_prefix; ?>page/acceuil.php" class="nav-logo">
      Immo<span style="color: var(--primary);">-Location</span>
    </a>
  </div>

  <ul class="nav-menu" id="nav-menu">
    <li>
      <a href="<?php echo $path_prefix; ?>page/acceuil.php" class="nav-link <?php echo ($current_page === 'acceuil.php') ? 'active' : ''; ?>">
        <i class="fa-solid fa-house" style="font-size:0.8rem;"></i> Accueil
      </a>
    </li>

    <li class="nav-dropdown">
      <a href="#" class="nav-link nav-dropdown-toggle">
        Services <i class="fa-solid fa-chevron-down" style="font-size:0.7rem;"></i>
      </a>
      <ul class="nav-dropdown-menu">
        <li>
          <a href="<?php echo $path_prefix; ?>page/appartement.php" class="nav-dropdown-item">
            <i class="fa-solid fa-building"></i>
            Appartements
          </a>
        </li>
        <li>
          <a href="<?php echo $path_prefix; ?>page/villa.php" class="nav-dropdown-item">
            <i class="fa-solid fa-house-chimney"></i>
            Villas
          </a>
        </li>
        <li>
          <a href="<?php echo $path_prefix; ?>page/voiturre.php" class="nav-dropdown-item">
            <i class="fa-solid fa-car"></i>
            Voitures
          </a>
        </li>
      </ul>
    </li>

    <li>
      <a href="<?php echo $path_prefix; ?>page/a-propos.php" class="nav-link <?php echo ($current_page === 'a-propos.php') ? 'active' : ''; ?>">
        À propos
      </a>
    </li>
    <li>
      <a href="<?php echo $path_prefix; ?>page/avis.php" class="nav-link <?php echo ($current_page === 'avis.php') ? 'active' : ''; ?>">
        Avis
      </a>
    </li>
    <li>
      <a href="<?php echo $path_prefix; ?>page/contact.php" class="nav-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>">
        Contact
      </a>
    </li>
  </ul>

  <div class="nav-actions">
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Notifications -->
      <div style="position:relative;" id="notif-wrapper">
        <button class="btn btn-secondary btn-icon" id="notif-btn" title="Notifications">
          <i class="fa-solid fa-bell"></i>
          <span id="notif-count" class="badge badge-danger" style="position:absolute;top:-6px;right:-6px;font-size:0.65rem;padding:2px 6px;display:none;">0</span>
        </button>
        <div id="notif-panel" style="
          position:absolute;top:calc(100% + 10px);right:0;
          width:320px;background:var(--bg-card);
          border:1px solid var(--border);border-radius:var(--radius-md);
          box-shadow:var(--shadow-lg);z-index:200;
          opacity:0;visibility:hidden;transition:all 0.3s;
        ">
          <div style="padding:1rem;border-bottom:1px solid var(--border);font-weight:600;">
            Notifications
          </div>
          <div id="notif-list" style="max-height:300px;overflow-y:auto;padding:0.5rem;"></div>
        </div>
      </div>

      <!-- User menu -->
      <div class="nav-dropdown">
        <button class="btn btn-secondary" style="gap:0.5rem;">
          <i class="fa-solid fa-circle-user"></i>
          <?php echo htmlspecialchars($_SESSION['prenom'] ?? 'Mon compte'); ?>
          <i class="fa-solid fa-chevron-down" style="font-size:0.7rem;"></i>
        </button>
        <ul class="nav-dropdown-menu" style="right:0;left:auto;">
          <?php if ($_SESSION['type_compte'] === 'admin'): ?>
            <li><a href="<?php echo $path_prefix; ?>page/dash/admin.php" class="nav-dropdown-item"><i class="fa-solid fa-gauge"></i> Dashboard Admin</a></li>
          <?php elseif ($_SESSION['type_compte'] === 'proprietaire'): ?>
            <li><a href="<?php echo $path_prefix; ?>page/dash/proprio.php" class="nav-dropdown-item"><i class="fa-solid fa-gauge"></i> Mon Dashboard</a></li>
          <?php else: ?>
            <li><a href="<?php echo $path_prefix; ?>page/dash/client.php" class="nav-dropdown-item"><i class="fa-solid fa-gauge"></i> Mon Espace</a></li>
          <?php endif; ?>
          <li><a href="<?php echo $path_prefix; ?>page/favoris.php" class="nav-dropdown-item"><i class="fa-solid fa-heart"></i> Mes Favoris</a></li>
          <li style="border-top:1px solid var(--border);margin-top:0.5rem;padding-top:0.5rem;">
            <a href="<?php echo $path_prefix; ?>php/logout.php" class="nav-dropdown-item" style="color:var(--danger);">
              <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
            </a>
          </li>
        </ul>
      </div>
    <?php else: ?>
      <a href="<?php echo $path_prefix; ?>page/connexion.php" class="btn btn-secondary">
        <i class="fa-solid fa-right-to-bracket"></i> Connexion
      </a>
      <a href="<?php echo $path_prefix; ?>page/inscription.php" class="btn btn-primary">
        <i class="fa-solid fa-user-plus"></i> S'inscrire
      </a>
    <?php endif; ?>
  </div>

  <!-- Hamburger -->
  <button class="nav-hamburger" id="nav-hamburger" aria-label="Menu">
    <span></span>
    <span></span>
    <span></span>
  </button>
</nav>

<!-- Back to top -->
<button id="back-to-top" aria-label="Retour en haut">
  <i class="fa-solid fa-chevron-up"></i>
</button>

<!-- Toast container -->
<div id="toast-container"></div>

<script>
window.pathPrefix = '<?php echo $path_prefix; ?>';
// Notification panel toggle
const notifBtn = document.getElementById('notif-btn');
const notifPanel = document.getElementById('notif-panel');
if (notifBtn && notifPanel) {
  notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    const isOpen = notifPanel.style.opacity === '1';
    notifPanel.style.opacity = isOpen ? '0' : '1';
    notifPanel.style.visibility = isOpen ? 'hidden' : 'visible';
    if (!isOpen) loadNotifications();
  });
  document.addEventListener('click', () => {
    notifPanel.style.opacity = '0';
    notifPanel.style.visibility = 'hidden';
  });
}

async function loadNotifications() {
  try {
    const res = await fetch('<?php echo $path_prefix; ?>php/api/notifications.php?action=list');
    const data = await res.json();
    const list = document.getElementById('notif-list');
    const count = document.getElementById('notif-count');
    if (!list) return;

    if (data.notifications && data.notifications.length > 0) {
      list.innerHTML = data.notifications.map(n => `
        <div class="notif-item" style="
          padding:0.75rem;border-radius:8px;margin-bottom:4px;
          background:${n.lue ? 'transparent' : 'var(--primary-glow)'};
          border:1px solid ${n.lue ? 'transparent' : 'rgba(245,166,35,0.2)'};
          cursor:pointer;transition:background 0.2s;
        " onclick="markNotifRead(${n.id}, this)">
          <div style="font-size:0.85rem;font-weight:${n.lue ? '400' : '600'};color:var(--text-primary);">${n.titre}</div>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">${n.message}</div>
          <div style="font-size:0.72rem;color:var(--text-disabled);margin-top:4px;">${n.date_creation}</div>
        </div>
      `).join('');

      const unread = data.notifications.filter(n => !n.lue).length;
      if (count) {
        count.textContent = unread;
        count.style.display = unread > 0 ? 'flex' : 'none';
      }
    } else {
      list.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--text-muted);">Aucune notification</div>';
    }
  } catch(e) {
    console.log('Notifications non disponibles');
  }
}

async function markNotifRead(id, el) {
  try {
    await fetch(`<?php echo $path_prefix; ?>php/api/notifications.php?action=read&id=${id}`);
    el.style.background = 'transparent';
    el.style.border = '1px solid transparent';
    el.querySelector('div').style.fontWeight = '400';
    await loadNotifications();
  } catch(e) {}
}

// Auto-trigger notifications list on load if connected
<?php if (isset($_SESSION['user_id'])): ?>
document.addEventListener('DOMContentLoaded', () => {
  loadNotifications();
});
<?php endif; ?>
</script>