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
            <span>Overview</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="layout.php?page=map" 
           class="nav-link <?php echo ($_GET['page'] ?? '') == 'map' ? 'active' : ''; ?>">
            <i class="fas fa-map-alt"></i>
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
            <i class="fas fa-farmer"></i>
            <span>Tasks</span>
        </a>
    </li>

    <li class="nav-item">
        <a href="layout.php?page=harvest" 
           class="nav-link <?php echo ($_GET['page'] ?? '') == 'harvest' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Harvest</span>
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


</nav>

<!-- Overlay for Mobile (Auto-hides sidebar when clicking outside) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
