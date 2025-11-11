<!-- Modern Sidebar Navbar -->
<nav class="sidebar-navbar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-seedling"></i>
        </div>
    </div>
    <br>
    <ul class="nav-list">
        <li class="nav-item">
            <a href="layout.php?page=dashboard"
               class="nav-link <?php echo ($_GET['page'] ?? 'dashboard') == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="layout.php?page=map"
               class="nav-link <?php echo ($_GET['page'] ?? '') == 'map' ? 'active' : ''; ?>">
                <i class="fas fa-map"></i>
                <span>Map</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="layout.php?page=crops"
               class="nav-link <?php echo ($_GET['page'] ?? '') == 'crops' ? 'active' : ''; ?>">
                <i class="fas fa-leaf"></i>
                <span>Crops</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="layout.php?page=tasks"
               class="nav-link <?php echo ($_GET['page'] ?? '') == 'tasks' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Tasks</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="layout.php?page=harvest"
               class="nav-link <?php echo ($_GET['page'] ?? '') == 'harvest' ? 'active' : ''; ?>">
                <i class="fas fa-tractor"></i>
                <span>Harvest</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="layout.php?page=market"
               class="nav-link <?php echo ($_GET['page'] ?? '') == 'market' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                <span>Market</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="layout.php?page=analytics"
               class="nav-link <?php echo ($_GET['page'] ?? '') == 'analytics' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="layout.php?page=settings"
               class="nav-link <?php echo ($_GET['page'] ?? '') == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar footer with Logout button -->
    <div class="sidebar-footer d-flex justify-content-center"
         style="position:absolute;left:12px;right:12px;bottom:12px;">
        <button type="button"
                class="btn btn-outline-danger w-100 text-center"
                data-bs-toggle="modal"
                data-bs-target="#logoutModal">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
        </button>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Confirm Logout</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to log out?
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="confirmLogoutBtn" class="btn btn-danger">Logout</button>
      </div>
    </div>
  </div>
</div>

<script>
// Redirect to logout endpoint on confirm
document.getElementById('confirmLogoutBtn')?.addEventListener('click', () => {
  window.location.href = '/Agrilink/backend/auth/logout.php';
});
</script>

<!-- Overlay for Mobile (Auto-hides sidebar when clicking outside) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
