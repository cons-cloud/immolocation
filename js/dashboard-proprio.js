/* ================================================================
   IMMO-LOCATION — Dashboard Propriétaire JS
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
  const earningsCanvas = document.getElementById('earnings-chart');
  if (earningsCanvas) loadOwnerStats();

  async function loadOwnerStats() {
    try {
      const prefix = window.pathPrefix || '../../';
      const res = await fetch(`${prefix}php/api/stats.php`);
      const data = await res.json();
      if (!data || data.role !== 'proprietaire') return;

      document.getElementById('s-biens').textContent = data.counts.biens;
      document.getElementById('s-voitures').textContent = data.counts.voitures;
      document.getElementById('s-reservations').textContent = data.counts.reservations;
      document.getElementById('s-earnings').textContent = data.counts.earnings.toLocaleString('fr-FR') + ' DH';

      new Chart(earningsCanvas, {
        type: 'bar',
        data: {
          labels: ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'],
          datasets: [{
            label: 'Revenus (DH)',
            data: data.monthly_earnings,
            backgroundColor: 'rgba(245,166,35,0.7)',
            borderColor: '#f5a623',
            borderWidth: 1,
            borderRadius: 6
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9898b0' } },
            x: { grid: { display: false }, ticks: { color: '#9898b0' } }
          }
        }
      });
    } catch(e) { console.log('Owner stats error:', e); }
  }
});

window.handleBooking = async function(id, status) {
  const label = status === 'confirmee' ? 'accepter' : 'décliner';
  const isAccept = status === 'confirmee';

  const confirmed = await window.showConfirm({
    title: isAccept ? 'Accepter la réservation' : 'Décliner la réservation',
    message: isAccept
      ? 'Voulez-vous vraiment <strong>accepter</strong> cette réservation ? Le client en sera notifié.'
      : 'Voulez-vous vraiment <strong>décliner</strong> cette réservation ? Cette action ne peut pas être annulée.',
    confirmText: isAccept ? '✓ Accepter' : '✗ Décliner',
    cancelText: 'Annuler',
    type: isAccept ? 'success' : 'warning'
  });
  if (!confirmed) return;

  const prefix = window.pathPrefix || '../../';
  const fd = new FormData();
  fd.append('id', id);
  fd.append('status', status);

  try {
    const res = await fetch(`${prefix}php/api/owner-action.php?action=booking_status`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      window.showToast(`Réservation ${isAccept ? 'acceptée' : 'déclinée'} avec succès.`, isAccept ? 'success' : 'info');
      const badge = document.getElementById(`rbadge-${id}`);
      if (badge) {
        badge.textContent = status;
        badge.className = `badge ${isAccept ? 'badge-success' : 'badge-danger'}`;
      }
      // Hide action buttons for this row
      const btns = document.querySelector(`#rbadge-${id}`)?.closest('tr')?.querySelector('td:last-child');
      if (btns) btns.innerHTML = '<span style="font-size:0.78rem;color:var(--text-muted);">—</span>';
    } else {
      window.showToast("Erreur lors de l'action.", "error");
    }
  } catch(e) {
    window.showToast("Erreur réseau.", "error");
  }
};

// Modal window helpers
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

// Open Add Property Modal
window.openAddBienModal = function() {
  const form = document.getElementById('form-bien');
  if (form) form.reset();
  
  document.getElementById('bien-id').value = '0';
  document.getElementById('bien-image_principale').value = '';
  document.getElementById('bien-image-preview').style.backgroundImage = "url('../../image/apparte.jpg')";
  document.getElementById('modal-bien-title').textContent = "Ajouter un hébergement";
  
  window.openModal('modal-bien');
};

// Open Edit Property Modal
window.openEditBienModal = async function(id) {
  const prefix = window.pathPrefix || '../../';
  try {
    const res = await fetch(`${prefix}php/api/listings-crud.php?action=get&type=bien&id=${id}`);
    const data = await res.json();
    if (!data.success) {
      window.showToast(data.error || "Impossible de charger les données", "error");
      return;
    }
    
    const item = data.data;
    const form = document.getElementById('form-bien');
    if (form) form.reset();
    
    document.getElementById('bien-id').value = item.id;
    document.getElementById('bien-titre').value = item.titre;
    document.getElementById('bien-type_bien').value = item.type_bien;
    document.getElementById('bien-prix_nuit').value = item.prix_nuit;
    document.getElementById('bien-ville').value = item.ville;
    document.getElementById('bien-surface').value = item.surface;
    document.getElementById('bien-adresse').value = item.adresse;
    document.getElementById('bien-nb_chambres').value = item.nb_chambres;
    document.getElementById('bien-nb_salles_bain').value = item.nb_salles_bain;
    document.getElementById('bien-nb_personnes').value = item.nb_personnes;
    document.getElementById('bien-description').value = item.description;
    
    document.getElementById('bien-wifi').checked = parseInt(item.wifi) === 1;
    document.getElementById('bien-piscine').checked = parseInt(item.piscine) === 1;
    document.getElementById('bien-parking').checked = parseInt(item.parking) === 1;
    document.getElementById('bien-climatisation').checked = parseInt(item.climatisation) === 1;
    document.getElementById('bien-cuisine').checked = parseInt(item.cuisine) === 1;
    
    document.getElementById('bien-image_principale').value = item.image_principale;
    
    // Resolve preview path relative to dash/ folder
    const imgUrl = item.image_principale ? item.image_principale.replace('../', '../../') : '../../image/apparte.jpg';
    document.getElementById('bien-image-preview').style.backgroundImage = `url('${imgUrl}')`;
    
    document.getElementById('modal-bien-title').textContent = "Modifier l'hébergement";
    window.openModal('modal-bien');
  } catch (e) {
    window.showToast("Erreur de connexion serveur", "error");
  }
};

// Open Add Car Modal
window.openAddVoitureModal = function() {
  const form = document.getElementById('form-voiture');
  if (form) form.reset();
  
  document.getElementById('voiture-id').value = '0';
  document.getElementById('voiture-image_principale').value = '';
  document.getElementById('voiture-image-preview').style.backgroundImage = "url('../../image/v1.jpg')";
  document.getElementById('modal-voiture-title').textContent = "Ajouter un véhicule";
  
  window.openModal('modal-voiture');
};

// Open Edit Car Modal
window.openEditVoitureModal = async function(id) {
  const prefix = window.pathPrefix || '../../';
  try {
    const res = await fetch(`${prefix}php/api/listings-crud.php?action=get&type=voiture&id=${id}`);
    const data = await res.json();
    if (!data.success) {
      window.showToast(data.error || "Impossible de charger les données", "error");
      return;
    }
    
    const item = data.data;
    const form = document.getElementById('form-voiture');
    if (form) form.reset();
    
    document.getElementById('voiture-id').value = item.id;
    document.getElementById('voiture-marque').value = item.marque;
    document.getElementById('voiture-modele').value = item.modele;
    document.getElementById('voiture-annee').value = item.annee;
    document.getElementById('voiture-couleur').value = item.couleur;
    document.getElementById('voiture-nb_places').value = item.nb_places;
    document.getElementById('voiture-prix_jour').value = item.prix_jour;
    document.getElementById('voiture-caution').value = item.caution;
    document.getElementById('voiture-ville').value = item.ville;
    document.getElementById('voiture-km').value = item.km;
    document.getElementById('voiture-carburant').value = item.carburant;
    document.getElementById('voiture-boite').value = item.boite;
    document.getElementById('voiture-immatriculation').value = item.immatriculation;
    
    document.getElementById('voiture-climatisation').checked = parseInt(item.climatisation) === 1;
    document.getElementById('voiture-gps').checked = parseInt(item.gps) === 1;
    
    document.getElementById('voiture-image_principale').value = item.image_principale;
    
    // Resolve preview path relative to dash/ folder
    const imgUrl = item.image_principale ? item.image_principale.replace('../', '../../') : '../../image/v1.jpg';
    document.getElementById('voiture-image-preview').style.backgroundImage = `url('${imgUrl}')`;
    
    document.getElementById('modal-voiture-title').textContent = "Modifier le véhicule";
    window.openModal('modal-voiture');
  } catch (e) {
    window.showToast("Erreur de connexion serveur", "error");
  }
};

// Handle live image upload on file select
window.uploadImageFile = async function(input, type) {
  if (!input.files || !input.files[0]) return;
  
  const prefix = window.pathPrefix || '../../';
  const file = input.files[0];
  const formData = new FormData();
  formData.append('image', file);
  
  window.showToast("Téléchargement de l'image...", "info");
  
  try {
    const res = await fetch(`${prefix}php/api/upload.php`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    
    if (data.success) {
      window.showToast("Image téléchargée avec succès !", "success");
      document.getElementById(`${type}-image_principale`).value = data.path;
      
      // Update preview container
      const imgUrl = data.path.replace('../', '../../');
      document.getElementById(`${type}-image-preview`).style.backgroundImage = `url('${imgUrl}')`;
    } else {
      window.showToast(data.error || "Erreur de téléchargement", "error");
    }
  } catch (e) {
    window.showToast("Erreur réseau lors du téléversement", "error");
  }
};

// Submit Add/Edit Form
window.submitListingForm = async function(event, type) {
  event.preventDefault();
  
  const prefix = window.pathPrefix || '../../';
  const form = document.getElementById(`form-${type}`);
  const formData = new FormData(form);
  
  const id = parseInt(document.getElementById(`${type}-id`).value);
  const action = (id > 0) ? 'update' : 'create';
  
  try {
    const res = await fetch(`${prefix}php/api/listings-crud.php?action=${action}`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    
    if (data.success) {
      window.showToast(`Annonce ${action === 'update' ? 'modifiée' : 'créée'} avec succès !`, "success");
      window.closeModal(`modal-${type}`);
      setTimeout(() => window.location.reload(), 1000);
    } else {
      window.showToast(data.error || "Erreur lors de l'enregistrement", "error");
    }
  } catch (e) {
    window.showToast("Erreur réseau", "error");
  }
};

// Delete Listing
window.deleteListing = async function(id, type) {
  const label = type === 'bien' ? 'cet hébergement' : 'ce véhicule';
  const typeLabel = type === 'bien' ? 'l\'hébergement' : 'le véhicule';

  const confirmed = await window.showConfirm({
    title: 'Supprimer définitivement',
    message: `Vous êtes sur le point de supprimer <strong>${label}</strong>.<br>Cette action est <strong>irréversible</strong> et ne peut pas être annulée.`,
    confirmText: '🗑 Supprimer',
    cancelText: 'Conserver',
    type: 'danger'
  });
  if (!confirmed) return;

  const prefix = window.pathPrefix || '../../';
  const formData = new FormData();
  formData.append('id', id);
  formData.append('type', type);

  try {
    const res = await fetch(`${prefix}php/api/listings-crud.php?action=delete`, {
      method: 'POST',
      body: formData
    });
    const data = await res.json();

    if (data.success) {
      window.showToast("Annonce supprimée avec succès.", "success");
      setTimeout(() => window.location.reload(), 1000);
    } else {
      window.showToast(data.error || "Erreur lors de la suppression", "error");
    }
  } catch (e) {
    window.showToast("Erreur réseau", "error");
  }
};
