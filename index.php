<?php
// Landing page — Agrilink Smart Harvest & Market Link
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BukidSense Smart Harvest & Market Link</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet"/>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"/>

  <!-- Custom Styles -->
  <style>
    :root{
      --agrigreen:#2b9a66;
      --darkgreen:#1f6f48;
      --lightgreen:#e9f7f0;
      --accent:#ffc107;
      --text:#1e1e1e;
    }
    *{font-family:'Poppins',sans-serif;}
    body{color:var(--text);background:#f8faf9;}
    a{text-decoration:none;}
    .section-title{font-weight:700;color:var(--darkgreen);}

    /* Navbar */
    .custom-navbar{
      background:rgba(255,255,255,0.9);
      backdrop-filter:blur(10px);
      border-bottom:1px solid rgba(0,0,0,0.05);
    }
    .custom-navbar .nav-link{color:#4f4f4f!important;font-weight:500;margin-inline:0.5rem;}
    .custom-navbar .nav-link.active,
    .custom-navbar .nav-link:hover{color:var(--agrigreen)!important;}

    /* Hero Section */
    .hero-section{
      min-height:90vh;
      background:linear-gradient( rgba(25,135,84,0.75), rgba(25,135,84,0.75) ),
                 url('pics/dan-meyers-IQVFVH0ajag-unsplash.jpg') center/cover no-repeat;
      color:#fff;
      padding-top:6rem;
      display:flex;
      align-items:center;
      text-align:center;
    }
    @media (max-width:768px){
      .hero-section{padding-top:5rem;}
      .hero-title{font-size:2.5rem;}
    }
    .hero-cta .btn{
      min-width:160px;
      padding:.8rem 1.5rem;
      border-radius:30px;
      font-weight:600;
      transition:.3s ease;
    }
    .hero-cta .btn-primary{
      background:var(--accent);
      border:none;
      color:#212529;
    }
    .hero-cta .btn-outline-light:hover{background:#fff;color:var(--darkgreen);}
    .hero-stats{
      margin-top:2.5rem;
      display:flex;
      gap:2rem;
      justify-content:center;
      flex-wrap:wrap;
    }
    .hero-stat{
      background:rgba(255,255,255,0.12);
      border:1px solid rgba(255,255,255,0.25);
      border-radius:16px;
      padding:1.2rem 1.8rem;
      min-width:170px;
      transition:.3s;
    }
    .hero-stat:hover{transform:translateY(-6px);}

    /* Overview */
    .overview-card{
      background:#fff;
      border-radius:18px;
      padding:2rem;
      box-shadow:0 15px 45px rgba(29,97,65,0.08);
    }

    /* Modules */
    .modules-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
      gap:1.5rem;
    }
    .module-card{
      background:#fff;
      border-radius:18px;
      padding:1.8rem;
      box-shadow:0 15px 30px rgba(31,111,72,0.08);
      transition:.35s ease;
      position:relative;
      overflow:hidden;
    }
    .module-card::after{
      content:"";
      position:absolute;
      inset:0;
      background:linear-gradient(135deg, rgba(43,154,102,0.08), rgba(255,255,255,0));
      opacity:0;
      transition:.35s;
    }
    .module-card:hover{
      transform:translateY(-10px);
      box-shadow:0 25px 40px rgba(31,111,72,0.16);
    }
    .module-card:hover::after{opacity:1;}
    .module-icon{
      width:58px;height:58px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:50%;
      background:var(--lightgreen);
      color:var(--agrigreen);
      font-size:1.6rem;
      margin-bottom:1rem;
    }

    /* Market highlight */
    .market-highlight{
      background:linear-gradient(135deg,rgba(43,154,102,0.1),rgba(43,154,102,0.02));
      border-radius:20px;
      padding:2.5rem;
    }
    .market-steps li{margin-bottom:.9rem;}

    /* Weather Widget */
    .weather-widget{
      background:#fff;
      border-radius:16px;
      padding:1.8rem;
      box-shadow:0 12px 30px rgba(0,0,0,0.06);
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
      gap:1rem;
    }
    .weather-tile{
      border-radius:14px;
      background:var(--lightgreen);
      padding:1.2rem;
      text-align:center;
    }

    /* About */
    .about-box{
      background:#fff;
      border-radius:18px;
      padding:2rem;
      box-shadow:0 15px 30px rgba(0,0,0,0.08);
    }

    /* Footer */
    footer{
      background:var(--darkgreen);
      color:#f6fff9;
      padding:2.5rem 0;
    }
    footer a{color:#deffe6;}
    footer a:hover{color:#fff;}
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top custom-navbar py-3">
  <div class="container">
    <a class="navbar-brand fw-bold text-success" href="#home">🌱 BukidSense Smart Harvest</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#overview">Overview</a></li>
        <li class="nav-item"><a class="nav-link" href="#modules">Modules</a></li>
        <li class="nav-item"><a class="nav-link" href="#market">Market</a></li>
        <li class="nav-item"><a class="nav-link" href="#weather">Weather</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item ms-lg-3">
          <a class="btn btn-success px-4" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<header id="home" class="hero-section">
  <div class="container">
    <h1 class="display-4 fw-bold hero-title">BukidSense Smart Harvest &amp; Market Link</h1>
    <p class="lead mt-3 mb-4">
      Empowering digital farming with intelligent crop monitoring, AI-driven diagnostics, and real-time market forecasting.
    </p>
    <div class="hero-cta d-flex flex-wrap justify-content-center gap-3">
      <button class="btn btn-primary shadow" data-bs-toggle="modal" data-bs-target="#loginModal">
        <i class="bi bi-box-arrow-in-right me-2"></i>Login
      </button>
      <button class="btn btn-outline-light shadow" data-bs-toggle="modal" data-bs-target="#signupModal">
        <i class="bi bi-person-plus me-2"></i>Register
      </button>
    </div>
    <div class="hero-stats">
      <div class="hero-stat">
        <h4 class="fw-bold mb-1">500+</h4>
        <span class="small">Tracked Crop Activities</span>
      </div>
      <div class="hero-stat">
        <h4 class="fw-bold mb-1">97%</h4>
        <span class="small">Accurate Forecasting Rate</span>
      </div>
      <div class="hero-stat">
        <h4 class="fw-bold mb-1">A.I.</h4>
        <span class="small">Leaf Disease Detection</span>
      </div>
    </div>
  </div>
</header>

<!-- System Overview -->
<section id="overview" class="py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="overview-card">
          <h2 class="section-title mb-3 text-center">Digital Agriculture for Farmers &amp; Consumers</h2>
          <p class="lead text-muted text-center mb-4">
            BukidSense Smart Harvest &amp; Market Link connects farmers, agri-technicians, and consumers through one unified platform.
            Monitor crop growth, detect diseases early, get AI-backed recommendations, and sell produce seamlessly via the market link.
          </p>
          <div class="row text-center g-4">
            <div class="col-md-4">
              <i class="bi bi-tree text-success fs-1 mb-2"></i>
              <h5 class="fw-semibold">For Farmers</h5>
              <p class="small text-muted mb-0">Track fields, automate schedules, monitor weather, and reach consumers directly.</p>
            </div>
            <div class="col-md-4">
              <i class="bi bi-people text-success fs-1 mb-2"></i>
              <h5 class="fw-semibold">For Consumers</h5>
              <p class="small text-muted mb-0">Browse fresh harvests, order with pickup or delivery, and follow up with real-time tracking.</p>
            </div>
            <div class="col-md-4">
              <i class="bi bi-shield-check text-success fs-1 mb-2"></i>
              <h5 class="fw-semibold">Secure &amp; Reliable</h5>
              <p class="small text-muted mb-0">Role-based access, audit trails, and smart alerts keep everyone informed and safe.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Modules Section -->
<section id="modules" class="py-5 bg-white">
  <div class="container">
    <h2 class="section-title text-center mb-4">Core System Modules</h2>
    <p class="text-center text-muted mb-5">
      Explore the integrated modules powering the BukidSense Smart Harvest ecosystem.
    </p>
    <div class="modules-grid">
      <div class="module-card">
        <div class="module-icon"><i class="bi bi-activity"></i></div>
        <h5 class="fw-semibold mb-2">Crop Activity &amp; Growth Monitoring</h5>
        <p class="small text-muted mb-0">
          Capture field activities, track growth stages, and analyze productivity with detailed logs and progress charts.
        </p>
      </div>
      <div class="module-card">
        <div class="module-icon"><i class="bi bi-robot"></i></div>
        <h5 class="fw-semibold mb-2">Leaf Disease Detection (AI Model)</h5>
        <p class="small text-muted mb-0">
          Upload leaf images and let AI classify possible diseases, giving actionable care tips instantly.
        </p>
      </div>
      <div class="module-card">
        <div class="module-icon"><i class="bi bi-bell"></i></div>
        <h5 class="fw-semibold mb-2">Smart Task Reminders &amp; Notifications</h5>
        <p class="small text-muted mb-0">
          Automated scheduling and push alerts ensure field work, fertilizing, and irrigation stay on track.
        </p>
      </div>
      <div class="module-card">
        <div class="module-icon"><i class="bi bi-basket2"></i></div>
        <h5 class="fw-semibold mb-2">Market Page with Consumer Ordering</h5>
        <p class="small text-muted mb-0">
          Display available harvests, handle cart-based orders, process payments, and manage pickup/delivery logistics.
        </p>
      </div>
      <div class="module-card">
        <div class="module-icon"><i class="bi bi-speedometer2"></i></div>
        <h5 class="fw-semibold mb-2">Farmer Dashboard &amp; Analytics</h5>
        <p class="small text-muted mb-0">
          Visual dashboards show yield forecasts, weather warnings, revenue, and crop health indicators at a glance.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Market & Consumer Feature Highlight -->
<section id="market" class="py-5">
  <div class="container">
    <div class="market-highlight">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <h2 class="section-title mb-3">Seamless Market Link &amp; Consumer Journey</h2>
          <p class="text-muted mb-4">
            Farmers showcase harvests using rich cards with quality tags, price per kilogram, and stock availability.
            Consumers can browse, add to cart, and choose between pickup or delivery with real-time availability updates.
          </p>
          <ul class="list-unstyled market-steps">
            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Real-time inventory sync ensures accurate stock levels.</li>
            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Flexible logistics: schedule pickup or request door-to-door delivery.</li>
            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Consumer portal tracks orders, receipts, and delivery status.</li>
          </ul>
          <div class="d-flex flex-wrap gap-3 mt-3">
            <a href="#modules" class="btn btn-success px-4 py-2">Explore Modules</a>
            <a href="#login" class="btn btn-outline-success px-4 py-2">Consumer Portal</a>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Suggest using an actual screenshot from assets/pics/market-dashboard.jpg -->
            <img src="pics\detail-rice-plant-sunset-valencia-with-plantation-out-focus-rice-grains-plant-seed.jpg" class="img-fluid" alt="Market page preview">
            <div class="card-body bg-white">
              <h6 class="fw-semibold text-success mb-2">Market Snapshot</h6>
              <p class="small text-muted mb-0">Highlight fresh produce, available quantities, quality grades, and customer ratings in one intuitive interface.</p>
            </div>
          </div>
        </div>
      </div><!-- row -->
    </div>
  </div>
</section>

<!-- Live Weather & Forecast (Placeholder Widget) -->
<section id="weather" class="py-5 bg-white">
  <div class="container">
    <h2 class="section-title text-center mb-4">Live Weather &amp; Forecast</h2>
    <div class="weather-widget">
      <div class="weather-tile">
        <i class="bi bi-thermometer-half fs-3 text-success mb-2"></i>
        <h6 class="fw-semibold">Temperature</h6>
        <div class="display-6 fw-bold text-success" id="w-temp">--°C</div>
        <p class="small text-muted mb-0" id="w-desc">Loading...</p>
      </div>
      <div class="weather-tile">
        <i class="bi bi-cloud-drizzle fs-3 text-success mb-2"></i>
        <h6 class="fw-semibold">Rainfall</h6>
        <div class="display-6 fw-bold text-success" id="w-rain">--</div>
        <p class="small text-muted mb-0">Next 24 hrs</p>
      </div>
      <div class="weather-tile">
        <i class="bi bi-droplet fs-3 text-success mb-2"></i>
        <h6 class="fw-semibold">Humidity</h6>
        <div class="display-6 fw-bold text-success" id="w-humidity">--%</div>
        <p class="small text-muted mb-0">Current humidity</p>
      </div>
      <div class="weather-tile">
        <i class="bi bi-geo-alt fs-3 text-success mb-2"></i>
        <h6 class="fw-semibold">Location</h6>
        <div class="display-6 fw-bold text-success" id="w-location">--</div>
        <p class="small text-muted mb-0" id="w-updated">--</p>
      </div>
    </div>
  </div>
</section>

<!-- About the Team / System -->
<section id="about" class="py-5">
  <div class="container">
    <div class="row justify-content-center g-4">
      <div class="col-lg-8">
        <div class="about-box text-center">
          <h2 class="section-title mb-3">About BukidSense Smart Harvest &amp; Market Link</h2>
          <p class="text-muted mb-4">
            Developed by IT students from <strong>Batangas State University</strong>, this project aims to bridge agricultural gaps with technology.
            Digital crop monitoring, AI diagnosis, and direct-to-consumer market link help create sustainable, data-driven farming ecosystems.
          </p>
          <div class="row g-3 justify-content-center">
            <div class="col-md-4">
              <div class="p-3 rounded border border-success-subtle">
                <i class="bi bi-person-gear text-success fs-2 mb-2"></i>
                <h6 class="fw-semibold">Project Lead</h6>
                <p class="small text-muted mb-0">Coordinate modules and oversee analytics.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border border-success-subtle">
                <i class="bi bi-cpu text-success fs-2 mb-2"></i>
                <h6 class="fw-semibold">AI &amp; Data Engineers</h6>
                <p class="small text-muted mb-0">Build models for disease detection and yield forecasting.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 rounded border border-success-subtle">
                <i class="bi bi-braces text-success fs-2 mb-2"></i>
                <h6 class="fw-semibold">Full-stack Developers</h6>
                <p class="small text-muted mb-0">Integrate dashboards, market link, and notification workflows.</p>
              </div>
            </div>
          </div>
          <p class="small text-muted mt-4 mb-0">Have questions? Reach us at <a href="mailto:team@bukidsense.com">team@bukidsense.com</a>.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Login Anchor Section (unchanged form functions downstream rely on) -->
<section id="login" class="py-5 text-center bg-white">
  <div class="container">
    <h2 class="fw-bold mb-3 text-success">Access Your Account</h2>
    <p class="mb-4 fs-5 text-muted">Secure portals for farmers, agronomists, and consumers.</p>
    <button class="btn btn-lg btn-success px-5 shadow" data-bs-toggle="modal" data-bs-target="#loginModal">
      Go to Login
    </button>
  </div>
</section>

<!-- Login Modal (unchanged structure to preserve existing functionality) -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title" id="loginModalLabel">Login</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form action="/backend/auth.php" method="POST" novalidate>
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

<!-- Signup Modal (unchanged) -->
<div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-success text-white border-0">
        <h5 class="modal-title" id="signupModalLabel">Create Consumer Account</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form id="signupForm" action="/backend/auth/consumer_register.php" method="POST" novalidate>
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
<footer>
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-4">
        <h5 class="fw-bold">BukidSense Smart Harvest &amp; Market Link</h5>
        <p class="small mb-0">
          Empowering smart agriculture with AI, analytics, and direct market connectivity.
        </p>
      </div>
      <div class="col-lg-4">
        <h6 class="fw-semibold mb-3">Quick Links</h6>
        <ul class="list-unstyled small">
          <li><a href="#overview">System Overview</a></li>
          <li><a href="#modules">Core Modules</a></li>
          <li><a href="#market">Market Features</a></li>
          <li><a href="#weather">Weather Forecast</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6 class="fw-semibold mb-3">Contact</h6>
        <p class="small mb-1"><i class="bi bi-envelope-open me-2"></i>team@bukidsense.com</p>
        <p class="small mb-1"><i class="bi bi-telephone me-2"></i>+63 900 000 0000</p>
        <p class="small mb-0"><i class="bi bi-geo-alt me-2"></i>BSU, Batangas, Philippines</p>
      </div>
    </div>
    <hr class="border-light opacity-50 my-4">
    <div class="text-center small">&copy; <?php echo date('Y'); ?> BukidSense Smart Harvest. All rights reserved.</div>
  </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('showPassword')?.addEventListener('change', function(){
  const f=document.getElementById('loginPass'); if(f) f.type=this.checked?'text':'password';
});
document.getElementById('showSignupPw')?.addEventListener('change', function(){
  ['regPw1','regPw2'].forEach(id=>{
    const el=document.getElementById(id);
    if(el) el.type=this.checked?'text':'password';
  });
});
document.getElementById('signupForm')?.addEventListener('submit', e=>{
  const p1=document.getElementById('regPw1').value;
  const p2=document.getElementById('regPw2').value;
  if(p1!==p2){
    e.preventDefault();
    const box=document.getElementById('signupError');
    box.textContent='Passwords do not match.';
    box.classList.remove('d-none');
  }
});
(() => {
  const qs=new URLSearchParams(location.search);
  const le=qs.get('login_error');
  const se=qs.get('signup_error');
  const so=qs.get('signup_ok');
  const pre=qs.get('prefill');

  if(so){
    const msg=document.getElementById('loginMsg');
    if(msg){msg.textContent=so; msg.classList.remove('d-none');}
    if(pre) document.getElementById('loginUser').value=pre;
    new bootstrap.Modal(document.getElementById('loginModal')).show();
    return;
  }
  if(se){
    const box=document.getElementById('signupError');
    if(box){box.textContent=se; box.classList.remove('d-none');}
    new bootstrap.Modal(document.getElementById('signupModal')).show();
    return;
  }
  if(le){
    const box=document.getElementById('loginError');
    if(box){box.textContent=le; box.classList.remove('d-none');}
    new bootstrap.Modal(document.getElementById('loginModal')).show();
  }
})();

(async () => {
  try {
    const res = await fetch('/backend/api/analytics/get_weather.php', { cache: 'no-store' });
    const data = await res.json();
    if (!data || data.error) throw new Error(data?.error || 'Weather unavailable');
    document.getElementById('w-temp').textContent = `${Number(data.temperature ?? 0).toFixed(1)}°C`;
    document.getElementById('w-desc').textContent = (data.description || 'No data').replace(/\b\w/g, c => c.toUpperCase());
    document.getElementById('w-rain').textContent = data.rain === 'yes' ? 'Rain expected' : 'No rain';
    document.getElementById('w-humidity').textContent = `${data.humidity ?? '--'}%`;
    document.getElementById('w-location').textContent = data.location || 'Unknown';
    document.getElementById('w-updated').textContent = data.timestamp ? `Updated ${data.timestamp}` : '';
  } catch (err) {
    document.getElementById('w-desc').textContent = err.message || 'Weather unavailable';
  }
})();
</script>
</body>
</html>
