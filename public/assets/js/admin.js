/* ============================================================
   IBEKU HIGH SCHOOL — ADMIN PANEL SHARED JS
   File: public/assets/js/admin.js
   Sidebar toggle for tablet/mobile.
   ============================================================ */

(function () {
  var toggle  = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('adminSidebar');
  var overlay = document.getElementById('sidebarOverlay');

  if (!toggle || !sidebar || !overlay) return;

  function openSidebar() {
    sidebar.classList.add('admin-sidebar--open');
    overlay.classList.add('sidebar-overlay--show');
  }

  function closeSidebar() {
    sidebar.classList.remove('admin-sidebar--open');
    overlay.classList.remove('sidebar-overlay--show');
  }

  toggle.addEventListener('click', function () {
    sidebar.classList.contains('admin-sidebar--open') ? closeSidebar() : openSidebar();
  });

  overlay.addEventListener('click', closeSidebar);
}());