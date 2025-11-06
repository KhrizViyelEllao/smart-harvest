<?php
// filepath: c:\xampp\htdocs\Agrilink\pages\dashboard.php
include 'backend/db_connect.php';

// Fetch statistics
$stats = [
    'total_fields' => 0,
    'total_farmers' => 0,
    'total_crops' => 0,
    'active_tasks' => 0,
    'pending_tasks' => 0,
    'completed_tasks' => 0,
    'total_harvest' => 0,
    'total_area' => 0
];

// Total Fields
$result = $conn->query("SELECT COUNT(*) as count FROM fields");
if ($row = $result->fetch_assoc()) $stats['total_fields'] = $row['count'];

// Total Farmers
$result = $conn->query("SELECT COUNT(*) as count FROM farmers");
if ($row = $result->fetch_assoc()) $stats['total_farmers'] = $row['count'];

// Total Crops
$result = $conn->query("SELECT COUNT(DISTINCT crop_id) as count FROM field_crops");
if ($row = $result->fetch_assoc()) $stats['total_crops'] = $row['count'];

// Active Tasks
$result = $conn->query("SELECT COUNT(*) as count FROM field_tasks WHERE status IN ('pending', 'in-progress')");
if ($row = $result->fetch_assoc()) $stats['active_tasks'] = $row['count'];

// Pending Tasks
$result = $conn->query("SELECT COUNT(*) as count FROM field_tasks WHERE status = 'pending'");
if ($row = $result->fetch_assoc()) $stats['pending_tasks'] = $row['count'];

// Completed Tasks
$result = $conn->query("SELECT COUNT(*) as count FROM field_tasks WHERE status = 'completed'");
if ($row = $result->fetch_assoc()) $stats['completed_tasks'] = $row['count'];

// Total Harvest (kg) - FIXED: using actual_yield_kg from harvests table
$result = $conn->query("SELECT COALESCE(SUM(actual_yield_kg), 0) as total FROM harvests WHERE actual_yield_kg IS NOT NULL");
if ($row = $result->fetch_assoc()) $stats['total_harvest'] = $row['total'];

// Total Area
$result = $conn->query("SELECT COALESCE(SUM(area), 0) as total FROM fields");
if ($row = $result->fetch_assoc()) $stats['total_area'] = $row['total'];

// Recent Task Assignments (Last 10)
$recentTasksQuery = "
  SELECT 
    ft.field_task_id,
    ft.start_date,
    ft.status,
    t.task_name,
    t.icon,
    f.name as field_name,
    fr.farmer_name
  FROM field_tasks ft
  JOIN tasks t ON ft.task_id = t.task_id
  LEFT JOIN fields f ON ft.field_id = f.field_id
  LEFT JOIN farmers fr ON ft.assigned_farmer_id = fr.farmer_id
  ORDER BY ft.start_date DESC
  LIMIT 10
";
$recentTasks = [];
$result = $conn->query($recentTasksQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentTasks[] = $row;
    }
}

// Crop Distribution (for pie chart)
$cropDistQuery = "
  SELECT c.crop_name, COUNT(fc.id) as count
  FROM field_crops fc
  JOIN crops c ON fc.crop_id = c.crop_id
  GROUP BY c.crop_name
  ORDER BY count DESC
  LIMIT 5
";
$cropDistribution = [];
$result = $conn->query($cropDistQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cropDistribution[] = $row;
    }
}

// Task Status Distribution (for doughnut chart)
$taskStatusQuery = "
  SELECT status, COUNT(*) as count
  FROM field_tasks
  GROUP BY status
";
$taskStatus = [];
$result = $conn->query($taskStatusQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $taskStatus[] = $row;
    }
}

// Monthly Harvest Trend (for line chart - last 6 months) - FIXED: using harvests table
$harvestTrendQuery = "
  SELECT 
    DATE_FORMAT(harvest_date, '%Y-%m') as month,
    SUM(actual_yield_kg) as total_yield
  FROM harvests
  WHERE harvest_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND actual_yield_kg IS NOT NULL
  GROUP BY DATE_FORMAT(harvest_date, '%Y-%m')
  ORDER BY month ASC
";
$harvestTrend = [];
$result = $conn->query($harvestTrendQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $harvestTrend[] = $row;
    }
}

// Top Performing Fields (by harvest yield) - FIXED: using harvests table with field_crops join
$topFieldsQuery = "
  SELECT 
    f.name as field_name,
    SUM(h.actual_yield_kg) as total_yield
  FROM harvests h
  JOIN field_crops fc ON h.crop_id = fc.crop_id
  JOIN fields f ON fc.field_id = f.field_id
  WHERE h.actual_yield_kg IS NOT NULL
  GROUP BY f.field_id, f.name
  ORDER BY total_yield DESC
  LIMIT 5
";
$topFields = [];
$result = $conn->query($topFieldsQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $topFields[] = $row;
    }
}
?>

<style>
  .stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 20px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  }
  .stat-card.green {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  }
  .stat-card.orange {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  }
  .stat-card.blue {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  }
  .stat-card.purple {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  .stat-card.teal {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  }
  .stat-card.red {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  }
  .stat-card.indigo {
    background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
  }
  .stat-card.yellow {
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #333;
  }
  .stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
  }
  .stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 10px 0;
  }
  .stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
  }
  .chart-container {
    position: relative;
    height: 300px;
  }
  .task-row {
    transition: background-color 0.2s ease;
  }
  .task-row:hover {
    background-color: #f8f9fa;
  }
  .status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .status-pending {
    background: #fff3cd;
    color: #856404;
  }
  .status-in-progress {
    background: #cfe2ff;
    color: #084298;
  }
  .status-completed {
    background: #d1e7dd;
    color: #0f5132;
  }
  .status-abandoned {
    background: #f8d7da;
    color: #842029;
  }
</style>

<div class="container-fluid py-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-1">
        <i class="bi bi-speedometer2 text-success"></i> Dashboard
      </h2>
      <p class="text-muted mb-0">Welcome to Agrilink Smart Harvest System</p>
    </div>
    <div class="text-muted">
      <i class="bi bi-calendar3"></i> <?= date('F d, Y') ?>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card green">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Total Fields</div>
            <div class="stat-value"><?= number_format($stats['total_fields']) ?></div>
            <small><i class="bi bi-geo-alt"></i> <?= number_format($stats['total_area'], 2) ?> hectares</small>
          </div>
          <div class="stat-icon"><i class="bi bi-map"></i></div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card blue">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Farmers</div>
            <div class="stat-value"><?= number_format($stats['total_farmers']) ?></div>
            <small><i class="bi bi-people"></i> Active workforce</small>
          </div>
          <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card orange">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Crop Types</div>
            <div class="stat-value"><?= number_format($stats['total_crops']) ?></div>
            <small><i class="bi bi-flower2"></i> Varieties planted</small>
          </div>
          <div class="stat-icon"><i class="bi bi-tree"></i></div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card purple">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Active Tasks</div>
            <div class="stat-value"><?= number_format($stats['active_tasks']) ?></div>
            <small><i class="bi bi-clock-history"></i> In progress</small>
          </div>
          <div class="stat-icon"><i class="bi bi-list-check"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Secondary Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card teal">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Pending Tasks</div>
            <div class="stat-value"><?= number_format($stats['pending_tasks']) ?></div>
            <small><i class="bi bi-hourglass-split"></i> Awaiting action</small>
          </div>
          <div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card indigo">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Completed Tasks</div>
            <div class="stat-value"><?= number_format($stats['completed_tasks']) ?></div>
            <small><i class="bi bi-check-circle"></i> All time</small>
          </div>
          <div class="stat-icon"><i class="bi bi-clipboard-check"></i></div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card red">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Total Harvest</div>
            <div class="stat-value"><?= number_format($stats['total_harvest']) ?></div>
            <small><i class="bi bi-box-seam"></i> Kilograms</small>
          </div>
          <div class="stat-icon"><i class="bi bi-basket"></i></div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="stat-card yellow">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Completion Rate</div>
            <div class="stat-value">
              <?php
              $total = $stats['pending_tasks'] + $stats['completed_tasks'];
              $rate = $total > 0 ? round(($stats['completed_tasks'] / $total) * 100) : 0;
              echo $rate . '%';
              ?>
            </div>
            <small><i class="bi bi-graph-up"></i> Task efficiency</small>
          </div>
          <div class="stat-icon"><i class="bi bi-speedometer"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="row g-3 mb-4">
    <!-- Crop Distribution Chart -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="bi bi-pie-chart text-success"></i> Crop Distribution
          </h5>
          <?php if (!empty($cropDistribution)): ?>
            <div class="chart-container">
              <div id="cropDistChart"></div>
            </div>
          <?php else: ?>
            <div class="alert alert-info mb-0">
              <i class="bi bi-info-circle"></i> No crop data available yet.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Task Status Chart -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="bi bi-diagram-3 text-primary"></i> Task Status
          </h5>
          <?php if (!empty($taskStatus)): ?>
            <div class="chart-container">
              <div id="taskStatusChart"></div>
            </div>
          <?php else: ?>
            <div class="alert alert-info mb-0">
              <i class="bi bi-info-circle"></i> No task data available yet.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Top Fields Chart -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="bi bi-bar-chart text-warning"></i> Top Performing Fields
          </h5>
          <?php if (!empty($topFields)): ?>
            <div class="chart-container">
              <div id="topFieldsChart"></div>
            </div>
          <?php else: ?>
            <div class="alert alert-info mb-0">
              <i class="bi bi-info-circle"></i> No harvest data available yet.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Harvest Trend Chart -->
  <div class="row g-3 mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="bi bi-graph-up-arrow text-success"></i> Harvest Trend (Last 6 Months)
          </h5>
          <?php if (!empty($harvestTrend)): ?>
            <div style="height: 300px;">
              <div id="harvestTrendChart"></div>
            </div>
          <?php else: ?>
            <div class="alert alert-info mb-0">
              <i class="bi bi-info-circle"></i> No harvest data available for the last 6 months.
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Task Assignments Table -->
  <div class="row">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="bi bi-clock-history text-info"></i> Recent Task Assignments
          </h5>
          
          <?php if (empty($recentTasks)): ?>
            <div class="alert alert-info">
              <i class="bi bi-info-circle"></i> No tasks assigned yet.
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th><i class="bi bi-calendar3"></i> Date</th>
                    <th><i class="bi bi-list-task"></i> Task</th>
                    <th><i class="bi bi-map"></i> Field</th>
                    <th><i class="bi bi-person"></i> Assigned To</th>
                    <th><i class="bi bi-flag"></i> Status</th>
                    <th class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentTasks as $task): ?>
                    <tr class="task-row">
                      <td>
                        <small class="text-muted">
                          <?= date('M d, Y', strtotime($task['start_date'])) ?>
                        </small>
                      </td>
                      <td>
                        <?php if ($task['icon']): ?>
                          <i class="bi bi-<?= htmlspecialchars($task['icon']) ?> text-success"></i>
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($task['task_name']) ?></strong>
                      </td>
                      <td>
                        <i class="bi bi-geo-alt text-muted"></i>
                        <?= htmlspecialchars($task['field_name'] ?? 'N/A') ?>
                      </td>
                      <td>
                        <i class="bi bi-person-badge text-primary"></i>
                        <?= htmlspecialchars($task['farmer_name'] ?? 'Unassigned') ?>
                      </td>
                      <td>
                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $task['status'])) ?>">
                          <?= ucfirst($task['status']) ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <a href="layout.php?page=tasks#task-<?= $task['field_task_id'] ?>" 
                           class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-eye"></i> View
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="text-center mt-3">
              <a href="layout.php?page=tasks" class="btn btn-success">
                <i class="bi bi-list-check"></i> View All Tasks
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
window.addEventListener('load', function () {
  // Crop Distribution (Pie)
  <?php if (!empty($cropDistribution)): ?>
  new ApexCharts(document.querySelector('#cropDistChart'), {
    chart: { type: 'pie', height: 300 },
    series: <?= json_encode(array_map('intval', array_column($cropDistribution, 'count'))) ?>,
    labels: <?= json_encode(array_column($cropDistribution, 'crop_name')) ?>,
    legend: { position: 'bottom' },
    colors: ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6']
  }).render();
  <?php endif; ?>

  // Task Status (Donut)
  <?php if (!empty($taskStatus)): ?>
  new ApexCharts(document.querySelector('#taskStatusChart'), {
    chart: { type: 'donut', height: 300 },
    series: <?= json_encode(array_map('intval', array_column($taskStatus, 'count'))) ?>,
    labels: <?= json_encode(array_map('ucfirst', array_column($taskStatus, 'status'))) ?>,
    legend: { position: 'bottom' },
    colors: ['#fbbf24','#3b82f6','#10b981','#ef4444']
  }).render();
  <?php endif; ?>

  // Top Fields (Bar)
  <?php if (!empty($topFields)): ?>
  new ApexCharts(document.querySelector('#topFieldsChart'), {
    chart: { type: 'bar', height: 300 },
    series: [{
      name: 'Yield (kg)',
      data: <?= json_encode(array_map('floatval', array_column($topFields, 'total_yield'))) ?>
    }],
    xaxis: { categories: <?= json_encode(array_column($topFields, 'field_name')) ?> },
    plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
    dataLabels: { enabled: false },
    colors: ['#10b981']
  }).render();
  <?php endif; ?>

  // Harvest Trend (Line)
  <?php if (!empty($harvestTrend)): ?>
  new ApexCharts(document.querySelector('#harvestTrendChart'), {
    chart: { type: 'line', height: 300 },
    series: [{
      name: 'Harvest Yield (kg)',
      data: <?= json_encode(array_map('floatval', array_column($harvestTrend, 'total_yield'))) ?>
    }],
    xaxis: {
      categories: <?= json_encode(array_map(function($m){ return date('M Y', strtotime($m.'-01')); }, array_column($harvestTrend, 'month'))) ?>
    },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#10b981'],
    fill: { type: 'gradient', gradient: { shade: 'light', opacityFrom: 0.3, opacityTo: 0 } },
    markers: { size: 3 }
  }).render();
  <?php endif; ?>
});
</script>