<?php
// Remove/disable the redirect block; do not call header() here
$roles = ['farm_owner','farmer','admin'];
if (session_status() === PHP_SESSION_ACTIVE) {
  // $role = $_SESSION['role'] ?? '';
  // if (!isset($_SESSION['user_id']) || !in_array($role, $roles, true)) {
  //   header('Location: /Agrilink/index.php?auth=denied');
  //   exit;
  // }
}
require_once __DIR__ . '/../backend/db_connect.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 text-success"><i class="bi bi-shop-window me-2"></i>Market & Engagement</h4>
    <small class="text-muted">Manage products, orders & finances</small>
  </div>

  <ul class="nav nav-tabs mb-3" id="marketTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabProducts" type="button">Products</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabOrders" type="button">Orders</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabFinance" type="button">Finances</button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- Products -->
    <div class="tab-pane fade show active" id="tabProducts">
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="productsTable">
              <thead class="table-light">
                <tr>
                  <th>Image</th>
                  <th>Product</th>
                  <th>Crop / Field</th>
                  <th>Price/kg</th>
                  <th>Available (kg)</th>
                  <th>Status</th>
                  <th>Created</th>
                  <th></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Orders -->
    <div class="tab-pane fade" id="tabOrders">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Incoming Orders</h6>
        <select id="orderStatusFilter" class="form-select form-select-sm" style="max-width:180px;">
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="ordersTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Product</th>
                  <th>Buyer</th>
                  <th>Qty (kg)</th>
                  <th>Total (₱)</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Finances -->
    <div class="tab-pane fade" id="tabFinance">
      <div class="row g-3 mb-3" id="financeCards"></div>
      <div class="card shadow-sm">
        <div class="card-header py-2">
          <strong class="small mb-0">Top Products (Completed Sales)</strong>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm mb-0" id="topProductsTable">
              <thead class="table-light">
                <tr>
                  <th>Product</th>
                  <th>Qty Sold (kg)</th>
                  <th>Total Sales (₱)</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editProductForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editProductId">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" id="editName" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea id="editDescription" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Price/kg (PHP)</label>
          <input type="number" step="0.01" min="0" id="editPrice" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Available Qty (kg)</label>
          <input type="number" step="0.01" min="0" id="editQty" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const base = location.origin + '/Agrilink';
  const allowed = new Set(['farm_owner','farmer','admin']);

  // Client-side guard (does not require server headers)
  try {
    const res = await fetch(base + '/backend/auth/me.php', {cache:'no-store'});
    const me = await res.json();
    if (!me.success || !allowed.has(me.role)) {
      location.href = base + '/index.php?auth=denied';
      return;
    }
  } catch (_) {
    location.href = base + '/index.php?auth=denied';
    return;
  }

  // Heartbeat every 5 minutes to refresh session cookie
  setInterval(() => {
    fetch(base + '/backend/auth/me.php', {cache:'no-store'}).catch(()=>{});
  }, 5 * 60 * 1000);

  const productsBody = document.querySelector('#productsTable tbody');
  const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
  const editForm = document.getElementById('editProductForm');
  // Orders
  const ordersBody = document.querySelector('#ordersTable tbody');
  const statusFilter = document.getElementById('orderStatusFilter');
  // Finance
  const financeCards = document.getElementById('financeCards');
  const topProductsBody = document.querySelector('#topProductsTable tbody');

  function fmt(n){ return Number(n||0).toFixed(2); }
  function escapeHtml(str){return (str||'').replace(/[&<>"']/g,s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));}
  function orderBadge(s){
    const map={pending:'warning',confirmed:'primary',completed:'success',cancelled:'secondary'};
    return `<span class="badge bg-${map[s]||'secondary'}">${s}</span>`;
  }

  async function loadProducts(){
    try{
      const res=await fetch(base+'/backend/api/products/list.php',{cache:'no-store'});
      const json=await res.json();
      productsBody.innerHTML='';
      if(!json.success || !json.data.length){
        productsBody.innerHTML='<tr><td colspan="8" class="text-center text-muted py-4">No products listed.</td></tr>'; return;
      }
      json.data.forEach(p=>{
        const tr=document.createElement('tr');
        // expect backend to return p.harvest_actual_kg (actual_yield_kg of linked harvest)
        const hkg = Number(p.harvest_actual_kg||0);
        tr.innerHTML=`
          <td>${p.image_url?'<img src="'+p.image_url+'" style="height:48px;width:48px;object-fit:cover;border-radius:4px;">':'<span class="text-muted small">No image</span>'}</td>
          <td><strong>${escapeHtml(p.name)}</strong><div class="small text-muted">${escapeHtml(p.description||'')}</div></td>
          <td><div>${escapeHtml(p.crop_name||'—')}</div><div class="small text-muted">${escapeHtml(p.field_name||'—')}</div></td>
          <td>₱${fmt(p.price_per_kg)}</td>
          <td>${fmt(p.available_qty)}</td>
          <td><span class="badge bg-${p.status==='available'?'success':'secondary'}">${p.status}</span></td>
          <td class="small text-muted">${p.created_at}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary" data-action="edit" data-id="${p.product_id}" data-hkg="${hkg}">Edit</button>
              <button class="btn btn-outline-warning" data-action="toggle" data-status="${p.status}" data-id="${p.product_id}">${p.status==='available'?'Mark Sold Out':'Mark Available'}</button>
              <button class="btn btn-outline-danger" data-action="delete" data-id="${p.product_id}">Remove</button>
            </div>
          </td>`;
        productsBody.appendChild(tr);
      });
    }catch(e){
      productsBody.innerHTML='<tr><td colspan="8" class="text-center text-danger py-4">Error loading products.</td></tr>';
    }
  }

  async function loadOrders(){
    const status = statusFilter.value ? '?status='+encodeURIComponent(statusFilter.value) : '';
    ordersBody.innerHTML='<tr><td colspan="8" class="text-center text-muted py-4">Loading...</td></tr>';
    try{
      const res=await fetch(base+'/backend/api/orders/seller_orders.php'+status);
      const j=await res.json();
      if(!j.success) throw new Error(j.message||'Failed');
      if(!j.data.length){ ordersBody.innerHTML='<tr><td colspan="8" class="text-center text-muted py-4">No orders found.</td></tr>'; return;}
      ordersBody.innerHTML='';
      j.data.forEach(o=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`
          <td>#${o.order_id}</td>
          <td>${escapeHtml(o.product_name||'—')}</td>
          <td>${escapeHtml(o.buyer_name||'—')}<div class="small text-muted">${escapeHtml(o.contact_info||'')}</div></td>
          <td>${fmt(o.quantity_kg)}</td>
          <td>₱${fmt(o.total_price)}</td>
          <td>${orderBadge(o.status)}</td>
          <td class="small">${o.created_at}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              ${o.status==='pending'?'<button class="btn btn-outline-primary" data-act="status" data-next="confirmed" data-id="'+o.order_id+'">Accept</button>':''}
              ${o.status==='confirmed'?'<button class="btn btn-outline-success" data-act="status" data-next="completed" data-id="'+o.order_id+'">Complete</button>':''}
              ${(o.status==='pending'||o.status==='confirmed')?'<button class="btn btn-outline-danger" data-act="status" data-next="cancelled" data-id="'+o.order_id+'">Cancel</button>':''}
            </div>
          </td>`;
        ordersBody.appendChild(tr);
      });
    }catch(e){
      ordersBody.innerHTML='<tr><td colspan="8" class="text-center text-danger py-4">'+e.message+'</td></tr>';
    }
  }

  async function loadFinance(){
    financeCards.innerHTML='<div class="col-12 text-muted small">Loading summary...</div>';
    topProductsBody.innerHTML='<tr><td colspan="3" class="text-center text-muted py-3">Loading...</td></tr>';
    try{
      const res=await fetch(base+'/backend/api/orders/finance_summary.php');
      const j=await res.json();
      if(!j.success) throw new Error(j.message||'Failed');
      const s=j.summary||{};
      financeCards.innerHTML=`
        <div class="col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
              <div class="small text-muted">Revenue (Completed)</div>
              <div class="h5 mb-0 text-success">₱${fmt(s.revenue_completed)}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
              <div class="small text-muted">Awaiting Completion</div>
              <div class="h5 mb-0">₱${fmt(s.awaiting_completion)}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
              <div class="small text-muted">Pending Potential</div>
              <div class="h5 mb-0">₱${fmt(s.pending_value)}</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
              <div class="small text-muted">Cancelled Value</div>
              <div class="h5 mb-0 text-danger">₱${fmt(s.cancelled_value)}</div>
            </div>
          </div>
        </div>`;
      const top=j.top_products||[];
      if(!top.length){ topProductsBody.innerHTML='<tr><td colspan="3" class="text-center text-muted py-3">No completed sales yet.</td></tr>'; }
      else {
        topProductsBody.innerHTML='';
        top.forEach(r=>{
          topProductsBody.innerHTML+=`
            <tr>
              <td>${escapeHtml(r.name)}</td>
              <td>${fmt(r.qty_sold)}</td>
              <td>₱${fmt(r.total_sales)}</td>
            </tr>`;
        });
      }
    }catch(e){
      financeCards.innerHTML='<div class="col-12 text-danger small">'+e.message+'</div>';
      topProductsBody.innerHTML='<tr><td colspan="3" class="text-center text-danger py-3">'+e.message+'</td></tr>';
    }
  }

  // Product actions
  productsBody.addEventListener('click', async e=>{
    const btn=e.target.closest('button[data-action]');
    if(!btn) return;
    const id=Number(btn.dataset.id);
    const act=btn.dataset.action;
    if(act==='toggle'){
      const newStatus = btn.dataset.status==='available' ? 'sold_out':'available';
      const r=await fetch(base+'/backend/api/products/status.php',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({product_id:id,status:newStatus})
      });
      const j=await r.json();
      if(!j.success) return alert(j.message||'Failed');
      loadProducts();
    } else if(act==='delete'){
      if(!confirm('Remove product?')) return;
      const r=await fetch(base+'/backend/api/products/delete.php',{
        method:'DELETE',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({product_id:id})
      });
      const j=await r.json();
      if(!j.success) return alert(j.message||'Failed');
      loadProducts();
    } else if(act==='edit'){
      const row=btn.closest('tr');
      editProductId.value=id;
      editName.value=row.querySelector('td:nth-child(2) strong').textContent.trim();
      editDescription.value=row.querySelector('td:nth-child(2) .small').textContent.trim();
      editPrice.value=row.querySelector('td:nth-child(4)').textContent.replace(/[^0-9.]/g,'');
      editQty.value=row.querySelector('td:nth-child(5)').textContent;
      editModal.show();
    }
  });

  editForm.addEventListener('submit', async e=>{
    e.preventDefault();
    const payload={
      product_id:Number(editProductId.value),
      name:editName.value.trim(),
      description:editDescription.value.trim(),
      price_per_kg:editPrice.value,
      available_qty:editQty.value
    };
    const r=await fetch(base+'/backend/api/products/update.php',{
      method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify(payload)
    });
    const j=await r.json();
    if(!j.success) return alert(j.message||'Failed');
    editModal.hide();
    loadProducts();
  });

  // Order actions
  ordersBody.addEventListener('click', async e=>{
    const btn=e.target.closest('button[data-act="status"]');
    if(!btn) return;
    const id=Number(btn.dataset.id);
    const next=btn.dataset.next;
    if(!confirm('Set order #'+id+' to '+next+'?')) return;
    const r=await fetch(base+'/backend/api/orders/update_status.php',{
      method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({order_id:id,status:next})
    });
    const j=await r.json();
    if(!j.success) return alert(j.message||'Failed');
    loadOrders();
    loadFinance();
  });

  statusFilter.addEventListener('change', loadOrders);

  // Tab triggers
  document.getElementById('marketTabs').addEventListener('click', e=>{
    if(e.target.matches('[data-bs-target="#tabOrders"]')) loadOrders();
    if(e.target.matches('[data-bs-target="#tabFinance"]')) loadFinance();
  });

  loadProducts();
});
</script>