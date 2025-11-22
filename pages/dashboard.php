<?php

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

// Total Harvest (kg)
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

// Monthly Harvest Trend (for line chart - last 6 months)
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

// Top Performing Fields (by harvest yield)
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
<!-- Bootstrap Icons (for bi-*) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
  :root {
    --agri-green:#198754;
    --agri-green-soft:#e9f6ec;
    --agri-green-lite:#f5faf6;
    --agri-green-border:#cfe7d3;
  }
  .kpi-card {
    background: var(--agri-green-lite);
    border: 1px solid var(--agri-green-border);
    border-radius: 10px;
    padding: 16px;
    position: relative;
  }
  .kpi-card::after {
    content:'';
    position:absolute;
    inset:0;
    border-radius:10px;
    pointer-events:none;
    box-shadow: 0 2px 4px rgba(25,135,84,.08);
  }
  .kpi-card:hover {
    border-color:#b6d9bb;
    background:#f2f9f3;
  }
  .kpi-icon {
    font-size: 1.75rem;
    color: var(--agri-green);
    opacity:.9;
  }
  .section-card {
    border:1px solid var(--agri-green-border);
    border-radius:10px;
    background:#fff;
  }
  .section-card .card-header {
    background: var(--agri-green-soft);
    border-bottom:1px solid var(--agri-green-border);
    font-weight:600;
  }
  .section-card .card-header i {
    color: var(--agri-green)!important;
  }
  .table thead th {
    font-weight:600;
  }
  .btn-light-border {
    background:#fff;
    border:1px solid var(--agri-green-border);
    color:#255e37;
  }
  .btn-light-border:hover {
    background: var(--agri-green-soft);
    border-color:#b6d9bb;
  }
  .badge.bg-light.text-dark {
    background: var(--agri-green-soft)!important;
    color:#255e37!important;
    border:1px solid var(--agri-green-border);
  }
</style>

<div class="container-fluid py-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h2 class="mb-1 text-success">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
      </h2>
      <p class="text-muted mb-0">Overview of farm, tasks, market and finances</p>
    </div>
    <div class="text-muted">
      <i class="bi bi-calendar3"></i> <?= date('F d, Y') ?>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Fields</div>
          <div class="kpi-value"><?= number_format($stats['total_fields']) ?></div>
          <small class="text-muted"><?= number_format($stats['total_area'], 2) ?> hectares</small>
        </div>
        <div class="kpi-icon"><i class="bi bi-map"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Farmers</div>
          <div class="kpi-value"><?= number_format($stats['total_farmers']) ?></div>
          <small class="text-muted">Active workforce</small>
        </div>
        <div class="kpi-icon"><i class="bi bi-people"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Crop Types</div>
          <div class="kpi-value"><?= number_format($stats['total_crops']) ?></div>
          <small class="text-muted">Varieties planted</small>
        </div>
        <div class="kpi-icon"><i class="bi bi-flower2"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Active Tasks</div>
          <div class="kpi-value"><?= number_format($stats['active_tasks']) ?></div>
          <small class="text-muted">In progress</small>
        </div>
        <div class="kpi-icon"><i class="bi bi-list-check"></i></div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Pending Tasks</div>
          <div class="kpi-value"><?= number_format($stats['pending_tasks']) ?></div>
        </div>
        <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Completed Tasks</div>
          <div class="kpi-value"><?= number_format($stats['completed_tasks']) ?></div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clipboard-check"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Total Harvest (kg)</div>
          <div class="kpi-value"><?= number_format($stats['total_harvest']) ?></div>
        </div>
        <div class="kpi-icon"><i class="bi bi-basket"></i></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="kpi-card h-100 d-flex justify-content-between">
        <div>
          <div class="kpi-label">Completion Rate</div>
          <div class="kpi-value">
            <?php
              $total = $stats['pending_tasks'] + $stats['completed_tasks'];
              $rate = $total > 0 ? round(($stats['completed_tasks'] / $total) * 100) : 0;
              echo $rate . '%';
            ?>
          </div>
        </div>
        <div class="kpi-icon"><i class="bi bi-speedometer"></i></div>
      </div>
    </div>
  </div>

  <!-- Market & Finances -->
  <div class="row g-3 mb-4">
    <div class="col-lg-6">
      <div class="card section-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><i class="bi bi-cart-check me-2 text-success"></i>Recent Orders</strong>
          <a href="layout.php?page=market" class="btn btn-sm btn-light-border">Open Market</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="recentOrdersTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Buyer</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr>
              </tbody>
            </table>
          </div>
          <div class="px-3 py-2 border-top text-muted small">
            Showing latest pending/confirmed orders
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card section-card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong><i class="bi bi-cash-coin me-2 text-success"></i>Finances</strong>
          <a href="layout.php?page=market#tabFinance" class="btn btn-sm btn-light-border">View Details</a>
        </div>
        <div class="card-body">
          <div class="row g-3" id="financeCards">
            <div class="col-6">
              <div class="kpi-card">
                <div class="kpi-label">Revenue (Completed)</div>
                <div class="kpi-value" id="revCompleted">₱0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="kpi-card">
                <div class="kpi-label">Awaiting Completion</div>
                <div class="kpi-value" id="revAwaiting">₱0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="kpi-card">
                <div class="kpi-label">Pending Potential</div>
                <div class="kpi-value" id="revPending">₱0.00</div>
              </div>
            </div>
            <div class="col-6">
              <div class="kpi-card">
                <div class="kpi-label">Cancelled Value</div>
                <div class="kpi-value" id="revCancelled">₱0.00</div>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <strong class="small">Top Products</strong>
            <div class="table-responsive">
              <table class="table table-sm mb-0" id="topProductsTable">
                <thead class="table-light">
                  <tr>
                    <th>Product</th>
                    <th>Qty Sold (kg)</th>
                    <th>Total Sales (₱)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td colspan="3" class="text-center text-muted py-2">Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modules -->
  <div class="card section-card mb-4">
    <div class="card-header">
      <strong><i class="bi bi-grid me-2 text-success"></i>Modules</strong>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-6 col-md-4 col-lg-2">
          <a class="kpi-card d-block text-decoration-none h-100" href="layout.php?page=crops">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="bi bi-flower2"></i></div>
              <div>
                <div class="fw-semibold text-dark">Crops</div>
                <small class="text-muted">Catalogue</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
          <a class="kpi-card d-block text-decoration-none h-100" href="layout.php?page=harvest">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="bi bi-basket2"></i></div>
              <div>
                <div class="fw-semibold text-dark">Harvest</div>
                <small class="text-muted">Logs</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
          <a class="kpi-card d-block text-decoration-none h-100" href="layout.php?page=tasks">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="bi bi-clipboard2-check"></i></div>
              <div>
                <div class="fw-semibold text-dark">Tasks</div>
                <small class="text-muted">Manager</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
          <a class="kpi-card d-block text-decoration-none h-100" href="layout.php?page=market">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="bi bi-shop-window"></i></div>
              <div>
                <div class="fw-semibold text-dark">Market</div>
                <small class="text-muted">Orders</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
          <a class="kpi-card d-block text-decoration-none h-100" href="layout.php?page=settings">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="bi bi-people-gear"></i></div>
              <div>
                <div class="fw-semibold text-dark">People</div>
                <small class="text-muted">Management</small>
              </div>
            </div>
          </a>
        </div>
        <div class="col-sm-6 col-md-4 col-lg-2">
          <a class="kpi-card d-block text-decoration-none h-100" href="layout.php?page=fields">
            <div class="d-flex align-items-center">
              <div class="kpi-icon me-2"><i class="bi bi-pin-map"></i></div>
              <div>
                <div class="fw-semibold text-dark">Fields</div>
                <small class="text-muted">Parcels</small>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card section-card h-100">
        <div class="card-header"><strong><i class="bi bi-pie-chart me-2 text-success"></i>Crop Distribution</strong></div>
        <div class="card-body">
          <?php if (!empty($cropDistribution)): ?>
            <div style="height: 280px;"><div id="cropDistChart"></div></div>
          <?php else: ?>
            <div class="alert alert-light border mb-0">No crop data.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card section-card h-100">
        <div class="card-header"><strong><i class="bi bi-diagram-3 me-2 text-success"></i>Task Status</strong></div>
        <div class="card-body">
          <?php if (!empty($taskStatus)): ?>
            <div style="height: 280px;"><div id="taskStatusChart"></div></div>
          <?php else: ?>
            <div class="alert alert-light border mb-0">No task data.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card section-card h-100">
        <div class="card-header"><strong><i class="bi bi-bar-chart me-2 text-success"></i>Top Fields</strong></div>
        <div class="card-body">
          <?php if (!empty($topFields)): ?>
            <div style="height: 280px;"><div id="topFieldsChart"></div></div>
          <?php else: ?>
            <div class="alert alert-light border mb-0">No harvest data.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Harvest Trend -->
  <div class="card section-card mb-4">
    <div class="card-header"><strong><i class="bi bi-graph-up-arrow me-2 text-success"></i>Harvest Trend (Last 6 Months)</strong></div>
    <div class="card-body">
      <?php if (!empty($harvestTrend)): ?>
        <div style="height: 300px;"><div id="harvestTrendChart"></div></div>
      <?php else: ?>
        <div class="alert alert-light border mb-0">No harvest data for the last 6 months.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Task Assignments -->
  <div class="card section-card">
    <div class="card-header"><strong><i class="bi bi-clock-history me-2 text-success"></i>Recent Task Assignments</strong></div>
    <div class="card-body">
      <?php if (empty($recentTasks)): ?>
        <div class="alert alert-light border mb-0">No tasks assigned yet.</div>
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
                <tr>
                  <td><small class="text-muted"><?= date('M d, Y', strtotime($task['start_date'])) ?></small></td>
                  <td>
                    <?php if ($task['icon']): ?>
                      <i class="bi bi-<?= htmlspecialchars($task['icon']) ?> text-success"></i>
                    <?php endif; ?>
                    <strong><?= htmlspecialchars($task['task_name']) ?></strong>
                  </td>
                  <td><i class="bi bi-geo-alt text-muted"></i> <?= htmlspecialchars($task['field_name'] ?? 'N/A') ?></td>
                  <td><i class="bi bi-person-badge text-muted"></i> <?= htmlspecialchars($task['farmer_name'] ?? 'Unassigned') ?></td>
                  <td>
                    <span class="badge bg-light text-dark"><?= ucfirst($task['status']) ?></span>
                  </td>
                  <td class="text-center">
                    <a href="layout.php?page=tasks#task-<?= $task['field_task_id'] ?>" class="btn btn-sm btn-light-border">
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

<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
(function(){
  const base = location.origin;
  const money = n => '₱' + Number(n||0).toFixed(2);
  const esc = s => (s||'').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));

  // Finance summary
  async function loadFinance(){
    try{
      const res = await fetch(base + '/backend/api/orders/finance_summary.php', {cache:'no-store'});
      const j = await res.json();
      if(!j.success) throw new Error(j.message||'Failed');
      const s = j.summary||{};
      document.getElementById('revCompleted').textContent = money(s.revenue_completed||0);
      document.getElementById('revAwaiting').textContent  = money(s.awaiting_completion||0);
      document.getElementById('revPending').textContent   = money(s.pending_value||0);
      document.getElementById('revCancelled').textContent = money(s.cancelled_value||0);
      const tbody = document.querySelector('#topProductsTable tbody');
      const top = j.top_products||[];
      if(!top.length){ tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-2">No completed sales yet.</td></tr>'; return; }
      tbody.innerHTML = top.map(r=>`<tr><td>${esc(r.name)}</td><td>${Number(r.qty_sold||0).toFixed(2)}</td><td>${money(r.total_sales||0)}</td></tr>`).join('');
    }catch(e){
      // keep defaults
    }
  }

  // Recent orders (pending/confirmed)
  async function loadRecentOrders(){
    const tbody = document.querySelector('#recentOrdersTable tbody');
    try{
      const res = await fetch(base + '/backend/api/orders/seller_orders.php', {cache:'no-store'});
      const j = await res.json();
      if(!j.success) throw new Error(j.message||'Failed');
      const rows = (j.data||[]).filter(o=>o.status==='pending'||o.status==='confirmed').slice(0,7);
      if(!rows.length){ tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No recent orders.</td></tr>'; return; }
      tbody.innerHTML = rows.map(o=>{
        const btns = [
          o.status==='pending'   ? `<button class="btn btn-sm btn-light-border me-1" data-act="status" data-id="${o.order_id}" data-next="confirmed">Accept</button>` : '',
          o.status==='confirmed' ? `<button class="btn btn-sm btn-success me-1" data-act="status" data-id="${o.order_id}" data-next="completed">Complete</button>` : '',
          (o.status==='pending'||o.status==='confirmed') ? `<button class="btn btn-sm btn-outline-danger" data-act="status" data-id="${o.order_id}" data-next="cancelled">Cancel</button>` : ''
        ].join('');
        return `<tr>
          <td>#${o.order_id}</td>
          <td>${esc(o.product_name||'')}</td>
          <td><small>${esc(o.buyer_name||'')}</small></td>
          <td>${Number(o.quantity_kg||0).toFixed(2)}</td>
          <td>${money(o.total_price||0)}</td>
          <td><span class="badge bg-light text-dark">${o.status}</span></td>
          <td class="text-end">${btns}</td>
        </tr>`;
      }).join('');
    }catch(e){
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Failed to load orders.</td></tr>';
    }
  }

  // Handle order status actions
  document.addEventListener('click', async (e)=>{
    const b = e.target.closest('button[data-act="status"]');
    if(!b) return;
    const id = Number(b.dataset.id);
    const next = b.dataset.next;
    if(!confirm('Set order #'+id+' to '+next+'?')) return;
    try{
      const res = await fetch(base + '/backend/api/orders/update_status.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({order_id:id, status:next})
      });
      const j = await res.json();
      if(!j.success) throw new Error(j.message||'Failed');
      loadRecentOrders();
      loadFinance();
    }catch(err){ alert(err.message); }
  });

  // Charts (single accent color, no gradients)
  <?php if (!empty($cropDistribution)): ?>
  new ApexCharts(document.querySelector('#cropDistChart'), {
    chart: { type: 'pie', height: 280 },
    series: <?= json_encode(array_map('intval', array_column($cropDistribution, 'count'))) ?>,
    labels: <?= json_encode(array_column($cropDistribution, 'crop_name')) ?>,
    legend: { position: 'bottom' },
    colors: ['#198754','#6c757d','#adb5bd','#495057','#343a40']
  }).render();
  <?php endif; ?>

  <?php if (!empty($taskStatus)): ?>
  new ApexCharts(document.querySelector('#taskStatusChart'), {
    chart: { type: 'donut', height: 280 },
    series: <?= json_encode(array_map('intval', array_column($taskStatus, 'count'))) ?>,
    labels: <?= json_encode(array_map('ucfirst', array_column($taskStatus, 'status'))) ?>,
    legend: { position: 'bottom' },
    colors: ['#198754','#6c757d','#adb5bd','#343a40']
  }).render();
  <?php endif; ?>

  <?php if (!empty($topFields)): ?>
  new ApexCharts(document.querySelector('#topFieldsChart'), {
    chart: { type: 'bar', height: 280 },
    series: [{ name:'Yield (kg)', data: <?= json_encode(array_map('floatval', array_column($topFields, 'total_yield'))) ?> }],
    xaxis: { categories: <?= json_encode(array_column($topFields, 'field_name')) ?> },
    dataLabels: { enabled: false },
    plotOptions: { bar: { columnWidth: '50%' } },
    colors: ['#198754']
  }).render();
  <?php endif; ?>

  <?php if (!empty($harvestTrend)): ?>
  new ApexCharts(document.querySelector('#harvestTrendChart'), {
    chart: { type: 'line', height: 300 },
    series: [{ name: 'Harvest Yield (kg)', data: <?= json_encode(array_map('floatval', array_column($harvestTrend, 'total_yield'))) ?> }],
    xaxis: { categories: <?= json_encode(array_map(function($m){ return date('M Y', strtotime($m.'-01')); }, array_column($harvestTrend, 'month'))) ?> },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#198754'],
    markers: { size: 3 }
  }).render();
  <?php endif; ?>

  loadFinance();
  loadRecentOrders();
})();
</script>