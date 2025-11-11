<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$validPages = ['dashboard', 'analytics', 'market', 'forecast', 'about', 'map', 'harvest', 'settings', 'crops', 'tasks'];

// Add your task step pages to the whitelist
$taskStepPages = ['cleaning_task', 'review_task', 'assign_farmer', 'planting_task', 'harvest_task', 'fertilizing_task', 'default_task' , 'pest_control'];

if (!in_array($page, array_merge($validPages, $taskStepPages))) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agrilink Smart System</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- ✅ Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9faf7;
        }

        .layout-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            background-color: #fff;
            padding: 1.5rem;
        }

        .nav-link.active {
            background-color: #10b981 !important;
            color: #fff !important;
            border-radius: 8px;
        }

        #page-loader {
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.85);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 9999;
        }
        #page-loader .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        #page-loader p {
            margin-top: 10px;
            color: #10b981;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="layout-wrapper">
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <!-- ✅ Page Loader -->
        <div id="page-loader">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Loading, please wait...</p>
        </div>

        <!-- ✅ Page Content -->
        <main>
            <?php
            // Support both normal and task_steps pages
            $mainPath = "pages/{$page}.php";
            $subPath = "pages/task_steps/{$page}.php";

            if (file_exists($mainPath)) {
                include $mainPath;
            } elseif (file_exists($subPath)) {
                include $subPath;
            } else {
                include "pages/dashboard.php"; // fallback
            }
            ?>
        </main>
    </div>
</div>

<!-- ✅ Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- ✅ Optional JS per-page -->
<?php
  // Dynamically include assets/js/{page}.js only if it exists
  $assetJsRel = "assets/js/{$page}.js";
  $assetJsAbs = __DIR__ . '/' . $assetJsRel;
  if (file_exists($assetJsAbs)) {
      echo '<script src="'.htmlspecialchars($assetJsRel, ENT_QUOTES).'"></script>';
      $initFunc = $page.'Init';
      echo "<script>if (typeof {$initFunc} === 'function') {$initFunc}();</script>";
  }
?>

</body>
</html>
