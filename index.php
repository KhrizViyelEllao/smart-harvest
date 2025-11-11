<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Smart Harvest</title>

  <!-- Google Fonts: Poppins -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top custom-navbar">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">🌱 Smart Harvest</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav fs-6">
        <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item ms-3">
          <a class="btn btn-success px-3" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<header id="home" class="hero-section d-flex align-items-center text-center text-white">
  <div class="container">
    <h1 class="display-4 fw-bold mb-3 hero-title">Smart Harvest</h1>
    <p class="lead mb-4 hero-subtitle">
      Empowering Farmers & Admins with Smart Crop Monitoring and Forecasting
    </p>
    <button class="btn btn-lg btn-success fw-semibold shadow hero-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
      Login to Your Account
    </button>
  </div>
</header>

<!-- Features Section -->
<section id="features" class="py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-5 text-success">Core Features</h2>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="feature-card p-4 h-100 shadow-sm rounded bg-white text-center">
          <i class="bi bi-bar-chart-line-fill feature-icon text-success mb-3"></i>
          <h5 class="fw-semibold mb-2">Crop Monitoring</h5>
          <p class="text-muted small">
            Real-time tracking of crop health and growth stages to maximize yield.
          </p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card p-4 h-100 shadow-sm rounded bg-white text-center">
          <i class="bi bi-bug-fill feature-icon text-success mb-3"></i>
          <h5 class="fw-semibold mb-2">Pest Alerts</h5>
          <p class="text-muted small">
            Early warnings and identification of pest threats to protect your crops.
          </p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card p-4 h-100 shadow-sm rounded bg-white text-center">
          <i class="bi bi-droplet-half feature-icon text-success mb-3"></i>
          <h5 class="fw-semibold mb-2">Soil & Water Logs</h5>
          <p class="text-muted small">
            Detailed records of soil quality and water usage for sustainable farming.
          </p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card p-4 h-100 shadow-sm rounded bg-white text-center">
          <i class="bi bi-calendar-event-fill feature-icon text-success mb-3"></i>
          <h5 class="fw-semibold mb-2">Harvest Forecasting</h5>
          <p class="text-muted small">
            Predictive analytics to plan harvests and optimize resource allocation.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- About Section -->
<section id="about" class="bg-light py-5">
  <div class="container">
    <h2 class="text-center fw-bold mb-4 text-success">About Smart Harvest</h2>
    <p class="lead text-center mx-auto mb-5" style="max-width: 700px;">
      Smart Harvest is a comprehensive system designed to empower farmers and administrators with advanced tools for crop monitoring, pest management, soil and water tracking, and harvest forecasting. Our platform leverages smart technology to help you make informed decisions, increase productivity, and promote sustainable agriculture.
    </p>
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <ul class="list-group list-group-flush fs-6">
          <li class="list-group-item">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            User-friendly interface tailored for farmers and admins
          </li>
          <li class="list-group-item">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            Real-time data and alerts for proactive crop management
          </li>
          <li class="list-group-item">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            Secure login and role-based access control
          </li>
          <li class="list-group-item">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            Supports sustainable and efficient farming practices
          </li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- Login Section (kept for scroll anchor) -->
<section id="login" class="py-5 text-center">
  <div class="container">
    <h2 class="fw-bold mb-4 text-success">Access Your Account</h2>
    <p class="mb-4 fs-5">
      Secure login portal for farmers and administrators.
    </p>
    <button class="btn btn-lg btn-success px-5 shadow" data-bs-toggle="modal" data-bs-target="#loginModal">Go to Login</button>
  </div>
</section>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title" id="loginModalLabel">Login</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form action="/Agrilink/backend/auth.php" method="POST" novalidate>
          <div id="loginError" class="alert alert-danger d-none py-2 mb-3"></div>
          <div id="loginMsg" class="alert alert-success d-none py-2 mb-3"></div>
          <div class="mb-3">
            <label class="form-label">Email or Username</label>
            <input type="text" class="form-control" id="loginUser" name="username" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" id="loginPass" name="password" required>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" id="showPassword" class="form-check-input">
            <label class="form-check-label" for="showPassword">Show Password</label>
          </div>
          <button type="submit" class="btn btn-success w-100 py-2">Login</button>
          <div class="text-center mt-3 small">
            <a href="#" class="text-decoration-none">Forgot Password?</a>
            <div class="mt-2">
              Don’t have an account?
              <a href="#" class="text-success fw-semibold" data-bs-toggle="modal" data-bs-target="#signupModal" data-bs-dismiss="modal">Sign Up</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Signup Modal -->
<div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title" id="signupModalLabel">Create Consumer Account</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form id="signupForm" action="/Agrilink/backend/auth/consumer_register.php" method="POST" novalidate>
          <div id="signupError" class="alert alert-danger d-none py-2 mb-3"></div>
          <div id="signupMsg" class="alert alert-success d-none py-2 mb-3"></div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required maxlength="80">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact Number</label>
              <input type="text" name="contact_number" class="form-control" maxlength="30">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control" maxlength="255">
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" name="password" id="regPw1" class="form-control" required minlength="6">
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="password_confirm" id="regPw2" class="form-control" required minlength="6">
            </div>
            <div class="col-12">
              <div class="form-check mt-2">
                <input type="checkbox" class="form-check-input" id="showSignupPw">
                <label for="showSignupPw" class="form-check-label">Show Passwords</label>
              </div>
            </div>
          </div>
          <div class="form-text mt-2">Password must be at least 6 characters.</div>
          <div class="d-flex justify-content-between align-items-center mt-4">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-success px-4" type="submit">Sign Up</button>
          </div>
          <div class="text-center mt-3 small">
            Already have an account?
            <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="bg-success text-white text-center py-3">
  <div class="container">
    <small>&copy; 2024 Smart Harvest. All rights reserved.</small>
  </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle password visibility
document.getElementById('showPassword')?.addEventListener('change', function(){
  const f = document.getElementById('loginPass'); if (f) f.type = this.checked ? 'text' : 'password';
});
document.getElementById('showSignupPw')?.addEventListener('change', function(){
  ['regPw1','regPw2'].forEach(id => { const el = document.getElementById(id); if (el) el.type = this.checked ? 'text' : 'password'; });
});

// Basic signup validation
document.getElementById('signupForm')?.addEventListener('submit', e=>{
  const p1 = document.getElementById('regPw1').value;
  const p2 = document.getElementById('regPw2').value;
  if (p1 !== p2) {
    e.preventDefault();
    const box = document.getElementById('signupError');
    box.textContent = 'Passwords do not match.';
    box.classList.remove('d-none');
  }
});

// Show appropriate modal based on query params
(() => {
  const qs = new URLSearchParams(location.search);
  const le = qs.get('login_error');
  const se = qs.get('signup_error');
  const so = qs.get('signup_ok');
  const pre = qs.get('prefill');

  if (so) {
    // After signup: open login modal with message and prefilled username
    document.getElementById('loginMsg')?.classList.remove('d-none');
    document.getElementById('loginMsg').textContent = so;
    if (pre) document.getElementById('loginUser').value = pre;
    new bootstrap.Modal(document.getElementById('loginModal')).show();
    return;
  }
  if (se) {
    const box = document.getElementById('signupError');
    box.textContent = se; box.classList.remove('d-none');
    new bootstrap.Modal(document.getElementById('signupModal')).show();
    return;
  }
  if (le) {
    const box = document.getElementById('loginError');
    box.textContent = le; box.classList.remove('d-none');
    new bootstrap.Modal(document.getElementById('loginModal')).show();
  }
})();
</script>
</body>
</html>
