/**
 * IMMO-LOCATION — main.js
 * Navigation, animations, révélations, toasts, utilitaires globaux
 */

document.addEventListener('DOMContentLoaded', () => {

  // ── 1. LOADING SCREEN ──────────────────────────────────────
  const loadingScreen = document.getElementById('loading-screen');
  if (loadingScreen) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        loadingScreen.classList.add('done');
        setTimeout(() => loadingScreen.remove(), 500);
      }, 600);
    });
  }

  // ── 2. NAVIGATION ──────────────────────────────────────────
  const nav = document.getElementById('main-nav');
  const hamburger = document.getElementById('nav-hamburger');
  const navMenu = document.getElementById('nav-menu');

  // Scroll effect
  if (nav) {
    const onScroll = () => {
      if (window.scrollY > 50) {
        nav.classList.add('scrolled');
      } else {
        nav.classList.remove('scrolled');
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Hamburger toggle
  if (hamburger && navMenu) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      navMenu.classList.toggle('open');
      document.body.style.overflow = navMenu.classList.contains('open') ? 'hidden' : '';
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!nav.contains(e.target) && navMenu.classList.contains('open')) {
        hamburger.classList.remove('open');
        navMenu.classList.remove('open');
        document.body.style.overflow = '';
      }
    });

    // Close on link click
    navMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('open');
        navMenu.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }

  // Active nav link based on current page
  const currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-link').forEach(link => {
    const href = link.getAttribute('href')?.split('/').pop();
    if (href === currentPage) {
      link.classList.add('active');
    }
  });

  // Mobile dropdown toggle
  document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
    const toggle = dropdown.querySelector('.nav-dropdown-toggle');
    if (toggle) {
      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        dropdown.classList.toggle('open');
      });
    }
  });

  // ── 3. INTERSECTION OBSERVER (Reveal animations) ────────────
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('visible');
        }, entry.target.dataset.delay ? parseInt(entry.target.dataset.delay) : 0);
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach((el, i) => {
    // Auto stagger for sibling elements
    if (!el.dataset.delay) {
      const siblings = [...(el.parentElement?.children || [])];
      const idx = siblings.indexOf(el);
      if (idx > 0) el.dataset.delay = idx * 100;
    }
    revealObserver.observe(el);
  });

  // ── 4. COUNTER ANIMATION ─────────────────────────────────────
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('[data-count]').forEach(el => {
    counterObserver.observe(el);
  });

  function animateCounter(el) {
    const target = parseInt(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;

    el.style.animation = 'count-up 0.5s ease';

    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = Math.floor(current).toLocaleString('fr-FR') + suffix;
      if (current >= target) clearInterval(timer);
    }, 16);
  }
});

// ── 5. TOAST SYSTEM ──────────────────────────────────────────
window.showToast = function(message, type = 'info', title = '', duration = 4000) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const icons = {
    success: 'fa-check',
    error:   'fa-xmark',
    info:    'fa-info',
    warning: 'fa-triangle-exclamation'
  };

  const titles = {
    success: title || 'Succès',
    error:   title || 'Erreur',
    info:    title || 'Information',
    warning: title || 'Attention'
  };

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <div class="toast-icon"><i class="fa-solid ${icons[type]}"></i></div>
    <div class="toast-content">
      <div class="toast-title">${titles[type]}</div>
      <div class="toast-msg">${message}</div>
    </div>
    <div class="toast-close"><i class="fa-solid fa-xmark"></i></div>
  `;

  container.appendChild(toast);

  toast.querySelector('.toast-close').addEventListener('click', () => {
    removeToast(toast);
  });

  if (duration > 0) {
    setTimeout(() => removeToast(toast), duration);
  }

  return toast;
};

function removeToast(toast) {
  toast.classList.add('hiding');
  setTimeout(() => toast.remove(), 300);
}

// ── 6. CONFIRM DIALOG (replaces native confirm()) ─────────────
window.showConfirm = function({ title = 'Confirmer l\'action', message = 'Êtes-vous sûr ?', confirmText = 'Confirmer', cancelText = 'Annuler', type = 'danger' } = {}) {
  return new Promise((resolve) => {
    // Remove any existing confirm dialog
    const existing = document.getElementById('confirm-dialog-overlay');
    if (existing) existing.remove();

    const icons = {
      danger:  { icon: 'fa-triangle-exclamation', color: 'var(--danger)',  glow: 'var(--danger-glow)',  bg: 'rgba(255,77,109,0.12)' },
      warning: { icon: 'fa-exclamation-circle',   color: 'var(--warning)', glow: 'rgba(255,190,11,0.2)',bg: 'rgba(255,190,11,0.12)' },
      info:    { icon: 'fa-circle-info',           color: 'var(--info)',    glow: 'rgba(76,201,240,0.2)',bg: 'rgba(76,201,240,0.12)' },
      success: { icon: 'fa-circle-check',          color: 'var(--success)', glow: 'var(--success-glow)', bg: 'rgba(0,212,170,0.12)' }
    };
    const cfg = icons[type] || icons.danger;

    const btnConfirmClass = type === 'danger' ? 'btn-danger' : type === 'warning' ? 'btn-warning' : 'btn-primary';

    const overlay = document.createElement('div');
    overlay.id = 'confirm-dialog-overlay';
    overlay.className = 'confirm-dialog-overlay';
    overlay.innerHTML = `
      <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title">
        <div class="confirm-dialog-icon-wrap" style="background:${cfg.bg};border-color:${cfg.color}30;">
          <i class="fa-solid ${cfg.icon}" style="color:${cfg.color};"></i>
        </div>
        <h3 class="confirm-dialog-title" id="confirm-dialog-title">${title}</h3>
        <p class="confirm-dialog-message">${message}</p>
        <div class="confirm-dialog-actions">
          <button class="btn btn-ghost confirm-cancel-btn" id="confirm-cancel-btn">${cancelText}</button>
          <button class="btn ${btnConfirmClass} confirm-ok-btn" id="confirm-ok-btn">${confirmText}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    // Animate in
    requestAnimationFrame(() => overlay.classList.add('active'));

    const cleanup = (result) => {
      overlay.classList.remove('active');
      document.body.style.overflow = '';
      setTimeout(() => overlay.remove(), 300);
      resolve(result);
    };

    overlay.querySelector('#confirm-ok-btn').addEventListener('click', () => cleanup(true));
    overlay.querySelector('#confirm-cancel-btn').addEventListener('click', () => cleanup(false));
    overlay.addEventListener('click', (e) => { if (e.target === overlay) cleanup(false); });

    // Focus confirm button for keyboard navigation
    setTimeout(() => overlay.querySelector('#confirm-ok-btn').focus(), 50);
  });
};

document.addEventListener('DOMContentLoaded', () => {
  // ── 7. BACK TO TOP ───────────────────────────────────────────
  const backToTop = document.getElementById('back-to-top');
  if (backToTop) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) {
        backToTop.classList.add('visible');
      } else {
        backToTop.classList.remove('visible');
      }
    }, { passive: true });

    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ── 7. FAVORITES ─────────────────────────────────────────────
  document.querySelectorAll('.listing-card-fav').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();

      const type = btn.dataset.type;
      const id   = btn.dataset.id;

      try {
        const prefix = window.pathPrefix || '../';
        const res = await fetch(`${prefix}php/api/favoris.php?action=toggle&type=${type}&id=${id}`);
        const data = await res.json();

        if (data.success) {
          btn.classList.toggle('active');
          const icon = btn.querySelector('i');
          if (btn.classList.contains('active')) {
            icon.className = 'fa-solid fa-heart';
            window.showToast('Ajouté à vos favoris !', 'success');
          } else {
            icon.className = 'fa-regular fa-heart';
            window.showToast('Retiré de vos favoris', 'info');
          }
        } else if (data.redirect) {
          window.location.href = data.redirect;
        }
      } catch {
        window.showToast('Connectez-vous pour ajouter aux favoris', 'warning');
      }
    });
  });

  // ── 8. MODAL ─────────────────────────────────────────────────
  window.openModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.add('open');
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  };

  window.closeModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove('open');
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }
  };

  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  });

  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.modalOpen));
  });

  document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.dataset.modalClose));
  });

  // ── 9. SEARCH BAR ANIMATION ──────────────────────────────────
  const searchInputs = document.querySelectorAll('.search-input');
  searchInputs.forEach(input => {
    input.addEventListener('focus', () => {
      input.closest('.search-bar, .search-wrapper')?.classList.add('focused');
    });
    input.addEventListener('blur', () => {
      input.closest('.search-bar, .search-wrapper')?.classList.remove('focused');
    });
  });

  // ── 10. PARALLAX ─────────────────────────────────────────────
  const heroSection = document.querySelector('.hero-section');
  if (heroSection && window.innerWidth > 768) {
    window.addEventListener('scroll', () => {
      const scrolled = window.scrollY;
      const heroImg = heroSection.querySelector('.hero-bg');
      if (heroImg) {
        heroImg.style.transform = `translateY(${scrolled * 0.4}px)`;
      }
    }, { passive: true });
  }

  // ── 11. SMOOTH HOVER CARD TILT ────────────────────────────────
  document.querySelectorAll('.listing-card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = `translateY(-6px) rotateX(${-y * 5}deg) rotateY(${x * 5}deg)`;
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.transition = 'transform 0.4s ease';
      setTimeout(() => card.style.transition = '', 400);
    });
  });

  // ── 12. FORM VALIDATION ───────────────────────────────────────
  window.validateForm = function(form) {
    let valid = true;
    form.querySelectorAll('[required]').forEach(field => {
      const error = field.parentElement.querySelector('.form-error');
      if (!field.value.trim()) {
        field.classList.add('error');
        if (error) error.style.display = 'flex';
        valid = false;
      } else {
        field.classList.remove('error');
        if (error) error.style.display = 'none';
      }
    });
    return valid;
  };

  document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('input', () => {
      if (input.value.trim()) {
        input.classList.remove('error');
        const error = input.parentElement.querySelector('.form-error');
        if (error) error.style.display = 'none';
      }
    });
  });

  // ── 13. CURSOR GLOW (desktop only) ───────────────────────────
  if (window.innerWidth > 1024) {
    const cursor = document.createElement('div');
    cursor.id = 'cursor-glow';
    cursor.style.cssText = `
      position: fixed;
      width: 200px; height: 200px;
      background: radial-gradient(circle, rgba(245,166,35,0.06) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
      z-index: 0;
      transform: translate(-50%, -50%);
      transition: opacity 0.3s;
    `;
    document.body.appendChild(cursor);

    document.addEventListener('mousemove', (e) => {
      cursor.style.left = e.clientX + 'px';
      cursor.style.top  = e.clientY + 'px';
    }, { passive: true });
  }

  // ── 14. DASHBOARD SIDEBAR DRAWER TOGGLE (mobile) ───────────────
  const sidebar = document.querySelector('.dash-sidebar');
  const toggleBtn = document.getElementById('dashSidebarToggle');
  if (sidebar && toggleBtn) {
    // Create overlay dynamically if not exists
    let overlay = document.querySelector('.dash-sidebar-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'dash-sidebar-overlay';
      document.body.appendChild(overlay);
    }

    const openSidebar = () => {
      sidebar.classList.add('open');
      overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    };

    const closeSidebar = () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
      document.body.style.overflow = '';
    };

    toggleBtn.addEventListener('click', openSidebar);
    overlay.addEventListener('click', closeSidebar);

    const closeBtn = document.getElementById('dashSidebarClose');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeSidebar);
    }
  }

  console.log('%c🏠 Immo-Location v2.0 — Powered by Excellence', 
    'color: #f5a623; font-size: 14px; font-weight: bold;');
});
