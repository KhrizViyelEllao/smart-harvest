<?php
// filepath: c:\xampp\htdocs\Agrilink\includes\consumer_nav.php
// Requires $base = '/Agrilink/pages' and $active = 'shop'|'orders'|'profile'
if (!isset($base))  $base = '/Agrilink/pages';
if (!isset($active)) $active = '';
?>
<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background:#198754;">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?php echo $base; ?>/shop.php">
      <i class="fa-solid fa-seedling me-2"></i>Smart Harvest
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link <?php echo $active==='shop'?'active':''; ?>" href="<?php echo $base; ?>/shop.php">
            <i class="fa-solid fa-store me-1"></i>Shop
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $active==='orders'?'active':''; ?>" href="<?php echo $base; ?>/order_history.php">
            <i class="fa-solid fa-receipt me-1"></i>Orders
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $active==='profile'?'active':''; ?>" href="<?php echo $base; ?>/profile.php">
            <i class="fa-solid fa-user me-1"></i>Profile
          </a>
        </li>
        <li class="nav-item ms-2">
          <a class="btn btn-outline-light btn-sm" href="/Agrilink/backend/auth/logout.php">
            <i class="fa-solid fa-right-from-bracket me-1"></i>Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>