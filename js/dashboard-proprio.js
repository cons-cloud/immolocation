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

async function handleBooking(id, status) {
  const label = status === 'confirmee' ? 'accepter' : 'décliner';
  if (!confirm(`Voulez-vous vraiment ${label} cette réservation ?`)) return;

  const prefix = window.pathPrefix || '../../';
  const fd = new FormData();
  fd.append('id', id);
  fd.append('status', status);

  try {
    const res = await fetch(`${prefix}php/api/owner-action.php?action=booking_status`, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      window.showToast(`Réservation ${status === 'confirmee' ? 'acceptée' : 'déclinée'} avec succès.`, status === 'confirmee' ? 'success' : 'info');
      const badge = document.getElementById(`rbadge-${id}`);
      if (badge) {
        badge.textContent = status;
        badge.className = `badge ${status === 'confirmee' ? 'badge-success' : 'badge-danger'}`;
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
}
