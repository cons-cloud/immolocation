/* ================================================================
   IMMO-LOCATION — Realtime Notifications Polling (AJAX)
   ================================================================ */

(function() {
  const displayedNotifications = new Set();
  let firstLoad = true;

  async function checkNotifications() {
    try {
      const prefix = window.pathPrefix || '';
      const res = await fetch(`${prefix}php/api/notifications.php?action=list`);
      const data = await res.json();
      
      if (!data || !data.notifications) return;

      const unreadCountBadge = document.getElementById('notif-count');
      const unreadNotifications = data.notifications.filter(n => !n.lue);

      // Update badge count
      if (unreadCountBadge) {
        unreadCountBadge.textContent = unreadNotifications.length;
        unreadCountBadge.style.display = unreadNotifications.length > 0 ? 'flex' : 'none';
      }

      // Check for new notifications to show as toast
      data.notifications.forEach(n => {
        if (!displayedNotifications.has(n.id)) {
          displayedNotifications.add(n.id);
          
          // We only display toast on subsequent polls, not on initial page load
          if (!firstLoad && !n.lue) {
            let toastType = 'info';
            if (n.type === 'reservation') toastType = 'success';
            if (n.type === 'paiement') toastType = 'success';
            if (n.type === 'alerte') toastType = 'danger';

            window.showToast(n.message, toastType, n.titre);
            
            // If the notification panel is open, reload list
            const panel = document.getElementById('notif-panel');
            if (panel && panel.style.opacity === '1') {
              if (typeof window.loadNotifications === 'function') {
                window.loadNotifications();
              }
            }
          }
        }
      });

      firstLoad = false;
    } catch (e) {
      console.log('Realtime notification check error:', e);
    }
  }

  // Check immediately, then check every 10 seconds
  document.addEventListener('DOMContentLoaded', () => {
    checkNotifications();
    setInterval(checkNotifications, 10000);
  });
})();
