// js/app.js - small client-side helpers
document.addEventListener('DOMContentLoaded', function() {
  // Notification badge demo (optional)
  const userDropdown = document.getElementById('userDropdown');
  if (userDropdown && !userDropdown.querySelector('.notification-badge')) {
    const badge = document.createElement('span');
    badge.className = 'notification-badge';
    badge.textContent = '3';
    userDropdown.appendChild(badge);
  }

  // certificate request form submit fallback for pure PHP flow
  const reqForm = document.getElementById('certificateRequestForm');
  if (reqForm) {
    reqForm.addEventListener('submit', function(e) {
      // allow native submit; we could do AJAX if desired
    });
  }
});
