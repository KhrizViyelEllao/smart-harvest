<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'consumer') {
  header('Location: /Agrilink/index.php?login_error=' . urlencode('Login as consumer to access orders'));
  exit;
}
$base = '/Agrilink/pages';
$active = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order History - Smart Harvest</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Fonts + Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <!-- Bootstrap + Shared styles -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/Agrilink/assets/css/include.css" rel="stylesheet">
  <style> body { font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; } </style>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/../includes/consumer_nav.php'; ?>

<div class="container pb-5">
  <h4 class="mb-3 text-success fw-bold">Order History</h4>
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="ordersTable">
          <thead class="table-light">
            <tr>
              <th>Order #</th>
              <th>Product</th>
              <th>Qty (kg)</th>
              <th>Total (₱)</th>
              <th>Status</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title">Order Details</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="orderDetailsBody"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const base = location.origin + '/Agrilink';
const tbody = document.querySelector('#ordersTable tbody');
const detailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
const detailsBody = document.getElementById('orderDetailsBody');

function badge(status){
  const map = {pending:'warning', confirmed:'primary', completed:'success', cancelled:'secondary'};
  return `<span class="badge bg-${map[status]||'secondary'} text-uppercase">${status}</span>`;
}

async function loadOrders(){
  tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Loading...</td></tr>';
  try {
    const res = await fetch(base + '/backend/api/orders/my_orders.php');
    const j = await res.json();
    if(!j.success) throw new Error('Load failed');
    if (!j.data || !j.data.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No orders yet.</td></tr>';
      return;
    }
    tbody.innerHTML = '';
    j.data.forEach(o=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>#${o.order_id}</td>
        <td>${o.product_name || '—'}</td>
        <td>${Number(o.quantity_kg).toFixed(2)}</td>
        <td>₱${Number(o.total_price).toFixed(2)}</td>
        <td>${badge(o.status)}</td>
        <td>${o.created_at}</td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary" data-action="view" data-id="${o.order_id}">Details</button>
            ${o.status==='pending' ? `<button class="btn btn-outline-danger" data-action="cancel" data-id="${o.order_id}">Cancel</button>` : ''}
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  } catch(e){
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Error loading orders.</td></tr>';
  }
}

tbody.addEventListener('click', async e=>{
  const btn = e.target.closest('button[data-action]');
  if (!btn) return;
  const id = Number(btn.dataset.id);
  if (btn.dataset.action === 'view') {
    const row = btn.closest('tr').children;
    detailsBody.innerHTML = `
      <div class="d-flex flex-column gap-1">
        <div><strong>Order #:</strong> ${row[0].textContent}</div>
        <div><strong>Product:</strong> ${row[1].textContent}</div>
        <div><strong>Quantity:</strong> ${row[2].textContent} kg</div>
        <div><strong>Total:</strong> ${row[3].textContent}</div>
        <div><strong>Status:</strong> ${row[4].textContent}</div>
        <div><strong>Date:</strong> ${row[5].textContent}</div>
      </div>`;
    detailsModal.show();
  } else if (btn.dataset.action === 'cancel') {
    if (!confirm('Cancel this order?')) return;
    const res = await fetch(base + '/backend/api/orders/cancel.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({order_id: id})
    });
    const j = await res.json();
    if (!j.success) return alert(j.message || 'Cancel failed');
    loadOrders();
  }
});

loadOrders();
</script>
</body>
</html>