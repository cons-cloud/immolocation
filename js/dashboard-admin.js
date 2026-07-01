/* ================================================================
   IMMO-LOCATION — Admin Dashboard Logic (Charts & Actions)
   ================================================================ */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Initialise Statistics & Graphs if overview is active
  const earningsCanvas = document.getElementById('earnings-chart');
  const typesCanvas = document.getElementById('types-chart');

  if (earningsCanvas && typesCanvas) {
    loadDashboardStats();
  }

  async function loadDashboardStats() {
    try {
      const prefix = window.pathPrefix || '../../';
      const res = await fetch(`${prefix}php/api/stats.php`);
      const data = await res.json();

      if (!data || data.role !== 'admin') return;

      // Update counters
      document.getElementById('stat-users').textContent = data.counts.users;
      document.getElementById('stat-biens').textContent = data.counts.biens;
      document.getElementById('stat-voitures').textContent = data.counts.voitures;
      document.getElementById('stat-reservations').textContent = data.counts.reservations;

      // Render line/bar earnings chart
      new Chart(earningsCanvas, {
        type: 'line',
        data: {
          labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
          datasets: [{
            label: 'Revenus (DH)',
            data: data.monthly_earnings,
            borderColor: '#f5a623',
            backgroundColor: 'rgba(245, 166, 35, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: { grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { color: '#9898b0' } },
            x: { grid: { display: false }, ticks: { color: '#9898b0' } }
          }
        }
      });

      // Render doughnut chart for categories
      new Chart(typesCanvas, {
        type: 'doughnut',
        data: {
          labels: ['Immobilier', 'Voitures'],
          datasets: [{
            data: [data.distribution.biens, data.distribution.voitures],
            backgroundColor: ['#f5a623', '#6c63ff'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: { color: '#9898b0', font: { family: 'Inter' } }
            }
          }
        }
      });

    } catch (e) {
      console.log('Error loading admin stats:', e);
    }
  }
});

// 2. AJAX Actions Handlers
const getPrefix = () => window.pathPrefix || '../../';

window.toggleUserStatus = async function(id, currentStatus) {
  const newStatus = currentStatus === 'actif' ? 'suspendu' : 'actif';
  const prefix = getPrefix();
  
  const formData = new FormData();
  formData.append('id', id);
  formData.append('status', newStatus);

  try {
    const res = await fetch(`${prefix}php/api/admin-action.php?action=user_status`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      window.showToast(`L'utilisateur a été ${newStatus === 'actif' ? 'activé' : 'suspendu'} avec succès.`, 'success');
      
      // Update DOM
      const badge = document.getElementById(`status-badge-user-${id}`);
      const btn = document.getElementById(`status-btn-user-${id}`);
      
      if (badge && btn) {
        badge.textContent = newStatus;
        badge.className = `badge ${newStatus === 'actif' ? 'badge-success' : 'badge-danger'}`;
        btn.textContent = newStatus === 'actif' ? 'Suspendre' : 'Activer';
        // Update function param call on the button
        btn.setAttribute('onclick', `toggleUserStatus(${id}, '${newStatus}')`);
      }
    } else {
      window.showToast("Erreur lors de la modification du statut.", "error");
    }
  } catch (e) {
    window.showToast("Erreur réseau.", "error");
  }
};

window.toggleListingStatus = async function(id, type, currentStatus) {
  const newStatus = currentStatus === 'actif' ? 'inactif' : 'actif';
  const prefix = getPrefix();

  const formData = new FormData();
  formData.append('id', id);
  formData.append('type', type);
  formData.append('status', newStatus);

  try {
    const res = await fetch(`${prefix}php/api/admin-action.php?action=listing_status`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      window.showToast(`L'annonce a été ${newStatus === 'actif' ? 'activée' : 'désactivée'} avec succès.`, 'success');

      // Update DOM
      const badge = document.getElementById(`status-badge-listing-${type}-${id}`);
      const btn = document.getElementById(`status-btn-listing-${type}-${id}`);

      if (badge && btn) {
        badge.textContent = newStatus;
        badge.className = `badge ${newStatus === 'actif' ? 'badge-success' : 'badge-danger'}`;
        btn.textContent = newStatus === 'actif' ? 'Désactiver' : 'Activer';
        btn.setAttribute('onclick', `toggleListingStatus(${id}, '${type}', '${newStatus}')`);
      }
    } else {
      window.showToast("Erreur lors de la modification du statut de l'annonce.", "error");
    }
  } catch(e) {
    window.showToast("Erreur réseau.", "error");
  }
};

window.cancelBooking = async function(id) {
  const confirmed = await window.showConfirm({
    title: 'Annuler la réservation',
    message: 'Êtes-vous sûr de vouloir <strong>annuler cette réservation</strong> ?<br>Cette action est <strong>irréversible</strong> et le client en sera informé.',
    confirmText: '⚠ Annuler la réservation',
    cancelText: 'Conserver',
    type: 'danger'
  });
  if (!confirmed) return;

  const prefix = getPrefix();
  const formData = new FormData();
  formData.append('id', id);
  formData.append('status', 'annulee');

  try {
    const res = await fetch(`${prefix}php/api/admin-action.php?action=booking_status`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      window.showToast("La réservation a été annulée avec succès.", "success");

      // Update DOM
      const badge = document.getElementById(`status-badge-res-${id}`);
      const btn = document.getElementById(`cancel-btn-res-${id}`);

      if (badge) {
        badge.textContent = 'annulee';
        badge.className = 'badge badge-danger';
      }
      if (btn) {
        btn.remove(); // Remove action button
      }
    } else {
      window.showToast("Erreur lors de l'annulation.", "error");
    }
  } catch (e) {
    window.showToast("Erreur réseau.", "error");
  }
};

// ── MODAL HELPERS ──────────────────────────────────────────────
window.openModal = function(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('active');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
};

window.closeModal = function(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('active');
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
};

// ── ADD BIEN ───────────────────────────────────────────────────
window.openAddBienModal = function() {
  const form = document.getElementById('form-bien');
  if (form) form.reset();
  document.getElementById('bien-id').value = '0';
  document.getElementById('bien-image_principale').value = '';
  document.getElementById('bien-image-preview').style.backgroundImage = "url('../../image/apparte.jpg')";
  document.getElementById('modal-bien-title').textContent = 'Ajouter un hébergement';
  window.openModal('modal-bien');
};

// ── EDIT BIEN ──────────────────────────────────────────────────
window.openEditBienModal = async function(id) {
  const prefix = getPrefix();
  try {
    const res = await fetch(`${prefix}php/api/listings-crud.php?action=get&type=bien&id=${id}`);
    const data = await res.json();
    if (!data.success) { window.showToast(data.error || 'Erreur chargement', 'error'); return; }

    const item = data.data;
    const form = document.getElementById('form-bien');
    if (form) form.reset();

    document.getElementById('bien-id').value          = item.id;
    document.getElementById('bien-titre').value        = item.titre;
    document.getElementById('bien-type_bien').value    = item.type_bien;
    document.getElementById('bien-prix_nuit').value    = item.prix_nuit;
    document.getElementById('bien-ville').value        = item.ville;
    document.getElementById('bien-surface').value      = item.surface;
    document.getElementById('bien-adresse').value      = item.adresse;
    document.getElementById('bien-nb_chambres').value  = item.nb_chambres;
    document.getElementById('bien-nb_salles_bain').value = item.nb_salles_bain;
    document.getElementById('bien-nb_personnes').value = item.nb_personnes;
    document.getElementById('bien-description').value  = item.description;

    document.getElementById('bien-wifi').checked          = parseInt(item.wifi) === 1;
    document.getElementById('bien-piscine').checked       = parseInt(item.piscine) === 1;
    document.getElementById('bien-parking').checked       = parseInt(item.parking) === 1;
    document.getElementById('bien-climatisation').checked = parseInt(item.climatisation) === 1;
    document.getElementById('bien-cuisine').checked       = parseInt(item.cuisine) === 1;

    // Set owner select if present (admin-only field)
    const ownerSel = document.getElementById('bien-proprietaire_id');
    if (ownerSel && item.proprietaire_id) ownerSel.value = item.proprietaire_id;

    document.getElementById('bien-image_principale').value = item.image_principale;
    const imgUrl = item.image_principale ? item.image_principale.replace('../', '../../') : '../../image/apparte.jpg';
    document.getElementById('bien-image-preview').style.backgroundImage = `url('${imgUrl}')`;

    document.getElementById('modal-bien-title').textContent = "Modifier l'hébergement";
    window.openModal('modal-bien');
  } catch(e) {
    window.showToast('Erreur de connexion serveur', 'error');
  }
};

// ── ADD VOITURE ────────────────────────────────────────────────
window.openAddVoitureModal = function() {
  const form = document.getElementById('form-voiture');
  if (form) form.reset();
  document.getElementById('voiture-id').value = '0';
  document.getElementById('voiture-image_principale').value = '';
  document.getElementById('voiture-image-preview').style.backgroundImage = "url('../../image/v1.jpg')";
  document.getElementById('modal-voiture-title').textContent = 'Ajouter un véhicule';
  window.openModal('modal-voiture');
};

// ── EDIT VOITURE ───────────────────────────────────────────────
window.openEditVoitureModal = async function(id) {
  const prefix = getPrefix();
  try {
    const res = await fetch(`${prefix}php/api/listings-crud.php?action=get&type=voiture&id=${id}`);
    const data = await res.json();
    if (!data.success) { window.showToast(data.error || 'Erreur chargement', 'error'); return; }

    const item = data.data;
    const form = document.getElementById('form-voiture');
    if (form) form.reset();

    document.getElementById('voiture-id').value            = item.id;
    document.getElementById('voiture-marque').value        = item.marque;
    document.getElementById('voiture-modele').value        = item.modele;
    document.getElementById('voiture-annee').value         = item.annee;
    document.getElementById('voiture-couleur').value       = item.couleur;
    document.getElementById('voiture-nb_places').value     = item.nb_places;
    document.getElementById('voiture-prix_jour').value     = item.prix_jour;
    document.getElementById('voiture-caution').value       = item.caution;
    document.getElementById('voiture-ville').value         = item.ville;
    document.getElementById('voiture-km').value            = item.km;
    document.getElementById('voiture-carburant').value     = item.carburant;
    document.getElementById('voiture-boite').value         = item.boite;
    document.getElementById('voiture-immatriculation').value = item.immatriculation;

    document.getElementById('voiture-climatisation').checked = parseInt(item.climatisation) === 1;
    document.getElementById('voiture-gps').checked           = parseInt(item.gps) === 1;

    // Set owner select if present (admin-only field)
    const ownerSel = document.getElementById('voiture-proprietaire_id');
    if (ownerSel && item.proprietaire_id) ownerSel.value = item.proprietaire_id;

    document.getElementById('voiture-image_principale').value = item.image_principale;
    const imgUrl = item.image_principale ? item.image_principale.replace('../', '../../') : '../../image/v1.jpg';
    document.getElementById('voiture-image-preview').style.backgroundImage = `url('${imgUrl}')`;

    document.getElementById('modal-voiture-title').textContent = 'Modifier le véhicule';
    window.openModal('modal-voiture');
  } catch(e) {
    window.showToast('Erreur de connexion serveur', 'error');
  }
};

// Unified opener for admin (dispatches to correct handler based on type)
window.openEditListingAdmin = function(id, type) {
  if (type === 'bien') window.openEditBienModal(id);
  else window.openEditVoitureModal(id);
};

// ── LIVE IMAGE UPLOAD ──────────────────────────────────────────
window.uploadImageFile = async function(input, type) {
  if (!input.files || !input.files[0]) return;

  const prefix = getPrefix();
  const formData = new FormData();
  formData.append('image', input.files[0]);

  window.showToast("Téléchargement en cours...", 'info');

  try {
    const res  = await fetch(`${prefix}php/api/upload.php`, { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      window.showToast('Image téléchargée !', 'success');
      document.getElementById(`${type}-image_principale`).value = data.path;
      const imgUrl = data.path.replace('../', '../../');
      document.getElementById(`${type}-image-preview`).style.backgroundImage = `url('${imgUrl}')`;
    } else {
      window.showToast(data.error || 'Erreur de téléchargement', 'error');
    }
  } catch(e) {
    window.showToast('Erreur réseau lors du téléversement', 'error');
  }
};

// ── SUBMIT CREATE / UPDATE ─────────────────────────────────────
window.submitListingForm = async function(event, type) {
  event.preventDefault();

  const prefix   = getPrefix();
  const form     = document.getElementById(`form-${type}`);
  const formData = new FormData(form);
  const id       = parseInt(document.getElementById(`${type}-id`).value);
  const action   = id > 0 ? 'update' : 'create';

  try {
    const res  = await fetch(`${prefix}php/api/listings-crud.php?action=${action}`, { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      window.showToast(`Annonce ${action === 'update' ? 'modifiée' : 'créée'} avec succès !`, 'success');
      window.closeModal(`modal-${type}`);
      setTimeout(() => window.location.reload(), 1000);
    } else {
      window.showToast(data.error || "Erreur lors de l'enregistrement", 'error');
    }
  } catch(e) {
    window.showToast('Erreur réseau', 'error');
  }
};

// ── DELETE LISTING ─────────────────────────────────────────────
window.deleteListing = async function(id, type) {
  const label = type === 'bien' ? 'cet hébergement' : 'ce véhicule';

  const confirmed = await window.showConfirm({
    title: 'Supprimer définitivement',
    message: `Vous êtes sur le point de supprimer <strong>${label}</strong>.<br>Cette action est <strong>irréversible</strong> et ne peut pas être annulée.`,
    confirmText: '\uD83D\uDDD1 Supprimer',
    cancelText: 'Conserver',
    type: 'danger'
  });
  if (!confirmed) return;

  const prefix   = getPrefix();
  const formData = new FormData();
  formData.append('id', id);
  formData.append('type', type);

  try {
    const res  = await fetch(`${prefix}php/api/listings-crud.php?action=delete`, { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      window.showToast('Annonce supprimée avec succès.', 'success');
      setTimeout(() => window.location.reload(), 1000);
    } else {
      window.showToast(data.error || 'Erreur lors de la suppression', 'error');
    }
  } catch(e) {
    window.showToast('Erreur réseau', 'error');
  }
};
