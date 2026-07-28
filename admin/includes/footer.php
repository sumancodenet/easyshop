</div> <!-- .admin-content -->

<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
(function() {
  const sidebar = document.getElementById('adminSidebar');
  const toggle = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');

  if (toggle) {
    toggle.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
      } else {
        sidebar.classList.toggle('collapsed');
      }
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function() {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  }

  // Auto-collapse on window resize
  window.addEventListener('resize', function() {
    if (window.innerWidth <= 768) {
      sidebar.classList.remove('collapsed');
    } else {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    }
  });
})();

// Toast system
function showToast(type, title, message, duration) {
  duration = duration || 4000;
  const container = document.getElementById('toastContainer');
  const icons = {
    success: 'bi-check-circle-fill',
    error: 'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill'
  };

  const toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.innerHTML =
    '<i class="bi ' + (icons[type] || icons.info) + ' toast-icon"></i>' +
    '<div class="toast-content">' +
      '<div class="toast-title">' + title + '</div>' +
      '<div class="toast-msg">' + message + '</div>' +
    '</div>' +
    '<button class="toast-close" onclick="this.closest(\'.toast\').classList.add(\'removing\');setTimeout(function(){this.closest(\'.toast\').remove()}.bind(this),300)"><i class="bi bi-x"></i></button>';

  container.appendChild(toast);

  setTimeout(function() {
    if (toast.parentNode) {
      toast.classList.add('removing');
      setTimeout(function() { if (toast.parentNode) toast.remove(); }, 300);
    }
  }, duration);
}

// Auto-show PHP flash messages (from URL params)
(function() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('success')) {
    showToast('success', 'Success!', params.get('success'));
  }
  if (params.get('error')) {
    showToast('error', 'Error!', params.get('error'));
  }
})();
</script>
</body>
</html>
