/* ============================================================
   SIMPROKON EVM — app.js
   ============================================================ */

// Auto-dismiss alerts after 4s
document.addEventListener('DOMContentLoaded', function () {
  // Auto dismiss alerts
  setTimeout(() => {
    document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      bsAlert?.close();
    });
  }, 4000);

  // Activate all Bootstrap tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });

  // Format rupiah inputs on load
  document.querySelectorAll('.rupiah-input').forEach(inp => {
    const raw = inp.value.replace(/\D/g, '');
    if (raw) inp.value = parseInt(raw).toLocaleString('id-ID');
    inp.addEventListener('input', function () {
      const v = this.value.replace(/\D/g, '');
      this.value = v ? parseInt(v).toLocaleString('id-ID') : '';
    });
  });

  // Confirm delete buttons
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
  });

  // Active sidebar highlight
  const currentPage = new URLSearchParams(window.location.search).get('p') || 'dashboard';
  document.querySelectorAll('.sidebar-link').forEach(link => {
    const href = link.getAttribute('href') || '';
    if (href.includes('p=' + currentPage)) {
      link.classList.add('active');
    }
  });

  // Sidebar toggle
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar   = document.getElementById('sidebar');
  const content   = document.getElementById('page-content');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      sidebar?.classList.toggle('collapsed');
      content?.classList.toggle('expanded');
    });
  }
});

// Global chart defaults
if (typeof Chart !== 'undefined') {
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
}

// Utility: format rupiah
function formatRupiah(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}
