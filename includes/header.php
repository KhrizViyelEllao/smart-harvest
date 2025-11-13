<?php
// No session_start here. Layout starts it first.
$displayName = $_SESSION['name']
  ?? $_SESSION['username']
  ?? $_SESSION['email']
  ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Harvest Dashboard</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap is loaded globally in layout.php -->

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link href="/Agrilink/assets/css/include.css" rel="stylesheet">

</head>
<body>


<!-- Modern Top Header -->
<header class="modern-header" role="banner">
    <nav class="header-nav d-flex justify-content-between align-items-center">
        <!-- Logo Section (Left) -->
        <div class="logo-section">
            <a href="layout.php?page=<?= htmlspecialchars($_GET['page'] ?? 'dashboard') ?>" class="logo-link">
                <i class="fas fa-seedling me-2"></i>
                <span>Smart Harvest</span>
            </a>
        </div>





        <!-- Actions Section (Right) -->
        <div class="actions-section d-flex align-items-center">
            <!-- Notifications -->
            <button class="action-btn" aria-label="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>

            <!-- User Profile Dropdown -->
            <div class="user-dropdown">
                <button class="user-btn d-flex align-items-center" onclick="toggleUserMenu()">
                    
                    <span class="user-name me-2"><?= htmlspecialchars($displayName) ?></span>
                    <i class="fas fa-chevron-down ms-1"></i>
                </button>
                <div class="dropdown-menu" id="userMenu">
                    <a href="#" class="dropdown-item"><i class="fas fa-user me-2"></i> Profile</a>
                    <a href="#" class="dropdown-item"><i class="fas fa-cog me-2"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </div>
            </div>

            <!-- Dark Mode Toggle -->
            <button class="action-btn ms-2" onclick="toggleDarkMode()" aria-label="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </button>

            <!-- Sidebar Toggle (Modern, Always Visible) -->
            <button class="btn btn-modern-toggle position-fixed top-0 end-0 m-3" onclick="toggleSidebar()" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
</header>

<!-- Your existing sidebar and content here... -->




    <!-- Custom JS -->
    <script src="/Agrilink/assets/js/include.js"></script>
</body>
</html>
