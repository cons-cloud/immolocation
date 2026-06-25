<?php
if (!isset($path_prefix)) {
    $path_prefix = '';
    if (file_exists('includes/config.php')) {
        $path_prefix = '';
    } elseif (file_exists('../includes/config.php')) {
        $path_prefix = '../';
    } elseif (file_exists('../../includes/config.php')) {
        $path_prefix = '../../';
    }
}
?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">

      <!-- Brand -->
      <div>
        <div class="footer-brand-logo">Immo-Location</div>
        <p class="footer-desc">
          Votre plateforme de confiance pour la location d'appartements, de villas et de voitures au Maroc.
          Des milliers de biens sélectionnés pour des séjours inoubliables.
        </p>
        <div class="footer-socials">
          <a href="https://www.facebook.com/share/16EXm9CXxg/" target="_blank" class="footer-social-btn" aria-label="Facebook">
            <i class="fa-brands fa-facebook-f"></i>
          </a>
          <a href="#" class="footer-social-btn" aria-label="Instagram">
            <i class="fa-brands fa-instagram"></i>
          </a>
          <a href="#" class="footer-social-btn" aria-label="WhatsApp">
            <i class="fa-brands fa-whatsapp"></i>
          </a>
          <a href="#" class="footer-social-btn" aria-label="Twitter">
            <i class="fa-brands fa-x-twitter"></i>
          </a>
        </div>
      </div>

      <!-- Services -->
      <div>
        <h4 class="footer-col-title">Services</h4>
        <ul class="footer-links">
          <li><a href="<?php echo $path_prefix; ?>page/appartement.php"><i class="fa-solid fa-building" style="width:16px;"></i> Appartements</a></li>
          <li><a href="<?php echo $path_prefix; ?>page/villa.php"><i class="fa-solid fa-house-chimney" style="width:16px;"></i> Villas</a></li>
          <li><a href="<?php echo $path_prefix; ?>page/voiturre.php"><i class="fa-solid fa-car" style="width:16px;"></i> Voitures</a></li>
          <li><a href="<?php echo $path_prefix; ?>page/recherche.php"><i class="fa-solid fa-search" style="width:16px;"></i> Recherche</a></li>
        </ul>
      </div>

      <!-- Liens -->
      <div>
        <h4 class="footer-col-title">Liens utiles</h4>
        <ul class="footer-links">
          <li><a href="<?php echo $path_prefix; ?>page/a-propos.php"><i class="fa-solid fa-circle-info" style="width:16px;"></i> À propos</a></li>
          <li><a href="<?php echo $path_prefix; ?>page/avis.php"><i class="fa-solid fa-star" style="width:16px;"></i> Avis clients</a></li>
          <li><a href="<?php echo $path_prefix; ?>page/contact.php"><i class="fa-solid fa-envelope" style="width:16px;"></i> Contact</a></li>
          <li><a href="<?php echo $path_prefix; ?>page/inscription.php"><i class="fa-solid fa-user-plus" style="width:16px;"></i> S'inscrire</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div>
        <h4 class="footer-col-title">Contact</h4>
        <div class="footer-contact-item">
          <i class="fa-solid fa-location-dot"></i>
          <span>Belle Vue, Meknès 50000, Maroc</span>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-phone"></i>
          <span><a href="tel:+212690869233" style="color:inherit;transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='inherit'">+212 690 869 233</a></span>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-envelope"></i>
          <span><a href="mailto:abdoulkarimnourdine78@gmail.com" style="color:inherit;transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='inherit'">contact@immolocation.ma</a></span>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-clock"></i>
          <span>Lun–Sam : 8h00 – 18h00</span>
        </div>
      </div>

    </div>
  </div>

  <!-- Footer Bottom -->
  <div class="footer-bottom">
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;width:100%;">
      <p class="footer-copyright">
        © <?php echo date('Y'); ?> <strong style="color:var(--primary);">Immo-Location</strong> — Tous droits réservés
      </p>
      <div style="display:flex;gap:1.5rem;">
        <a href="#" style="color:var(--text-muted);font-size:0.82rem;transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Confidentialité</a>
        <a href="#" style="color:var(--text-muted);font-size:0.82rem;transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">CGU</a>
        <a href="<?php echo $path_prefix; ?>page/contact.php" style="color:var(--text-muted);font-size:0.82rem;transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Support</a>
      </div>
    </div>
  </div>
</footer>

<!-- Global JS -->
<script src="<?php echo $path_prefix; ?>js/main.js"></script>

<!-- Realtime polling if connected -->
<?php if (isset($_SESSION['user_id'])): ?>
<script src="<?php echo $path_prefix; ?>js/realtime.js"></script>
<?php endif; ?>