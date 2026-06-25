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

async function toggleUserStatus(id, currentStatus) {
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
}

async function toggleListingStatus(id, type, currentStatus) {
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
}

async function cancelBooking(id) {
  if (!confirm("Êtes-vous sûr de vouloir annuler cette réservation ? Cette action est irréversible.")) return;

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
}
