/**
 * main.js
 * -----------------------------------------------------------------------
 * Shared front-end behaviour for every PuestoMap page:
 *  - Mobile sidebar toggle
 *  - Simple confirm-before-delete helper used on management tables
 * -----------------------------------------------------------------------
 */
document.addEventListener('DOMContentLoaded', function () {
  var toggleBtn = document.getElementById('pmSidebarToggle');
  var sidebar = document.getElementById('pmSidebar');

  // Show the hamburger toggle only on small screens
  function syncToggleVisibility() {
    if (!toggleBtn) return;
    toggleBtn.style.display = window.innerWidth <= 900 ? 'inline-flex' : 'none';
  }
  syncToggleVisibility();
  window.addEventListener('resize', syncToggleVisibility);

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }
});

/**
 * pmConfirmDelete
 * Simple confirmation wrapper used on "Remove" / "Deactivate" buttons
 * throughout the management tables. Returns false (cancelling the form
 * submission) when the user dismisses the browser confirmation dialog.
 */
function pmConfirmDelete(message) {
  return window.confirm(message || 'Are you sure you want to proceed? This action cannot be undone.');
}
