<?php
// Authorization disabled for this page
require_once __DIR__ . '/../backend/db_connect.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 text-success"><i class="bi bi-shop-window me-2"></i>Market & Engagement</h4>
    <div class="d-flex align-items-center gap-2">
      <small class="text-muted">Manage products, orders & finances</small>
      <button id="refreshBtn" class="btn btn-outline-success btn-sm">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
      </button>
    </div>
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
                  <th>Quality</th> <!-- ADDED -->
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
        <div class="mb-3">
          <label class="form-label">Quality</label>
          <input type="text" id="editQuality" class="form-control" placeholder="e.g. Grade A, Premium">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="marketConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-warning" id="marketConfirmContent">
      <div class="modal-header bg-warning text-white" id="marketConfirmHeader">
        <h5 class="modal-title" id="marketConfirmTitle">Confirm</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-exclamation-triangle fa-3x text-warning mb-3" id="marketConfirmIcon"></i>
        <div id="marketConfirmMessage">Are you sure?</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning" type="button" id="marketConfirmBtn">Confirm</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="marketSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Success</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
        <div id="marketSuccessMsg">Action completed</div>
      </div>
    </div>
  </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="marketErrorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Error</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-exclamation-circle fa-3x text-danger mb-3"></i>
        <div id="marketErrorMsg">Operation failed</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const base = location.origin + '/Agrilink';

  // Removed all auth checks and heartbeat
  // const allowed = new Set(['farm_owner','farmer','admin','seller']);
  // async function requireAuth() { ... }
  // if (!(await requireAuth())) return;
  // setInterval(async () => { ... }, 5 * 60 * 1000);

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
    const map={pending:'info',confirmed:'primary',completed:'success',cancelled:'secondary'};
    return `<span class="badge bg-${map[s]||'secondary'} text-dark">${s}</span>`;
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
          <td>${
          (p.quality
            ? `<span class="badge ${
                p.quality==='high'?'bg-success':
                p.quality==='medium'?'bg-warning text-dark':
                'bg-danger'
              }">${escapeHtml(p.quality)}</span>`
            : '—')
        }</td>
          <td>${fmt(p.available_qty)}</td>
          <td><span class="badge bg-${p.status==='available'?'success':'secondary'}">${p.status}</span></td>
          <td class="small text-muted">${p.created_at}</td>
          <td class="text-end">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary" data-action="edit"
                      data-id="${p.product_id}"
                      data-quality="${escapeHtml(p.quality||'')}"
                      data-hkg="${hkg}">Edit</button>
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

  // Modal helpers (avoid overlapping)
  function hideOpenModals(exceptId = null) {
    document.querySelectorAll('.modal.show').forEach(m => {
      if (exceptId && m.id === exceptId) return;
      (bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m)).hide();
    });
    setTimeout(() => {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('padding-right');
    }, 200);
  }
  function openMarketConfirm({ title='Confirm', message='Are you sure?', confirmText='Confirm', variant='info' }) {
    const header  = document.getElementById('marketConfirmHeader');
    const content = document.getElementById('marketConfirmContent');
    const iconEl  = document.getElementById('marketConfirmIcon');
    const titleEl = document.getElementById('marketConfirmTitle');
    const msgEl   = document.getElementById('marketConfirmMessage');
    const btnEl   = document.getElementById('marketConfirmBtn');
    const modalEl = document.getElementById('marketConfirmModal');

    header.className  = 'modal-header';
    content.className = 'modal-content';
    btnEl.className   = 'btn';
    iconEl.className  = 'fa fa-circle-info fa-3x mb-3 text-info';

    if (variant === 'danger') {
      header.classList.add('bg-danger','text-white');
      content.classList.add('border-danger');
      btnEl.classList.add('btn-danger');
      iconEl.className = 'fa fa-triangle-exclamation fa-3x mb-3 text-danger';
    } else if (variant === 'success') {
      header.classList.add('bg-success','text-white');
      content.classList.add('border-success');
      btnEl.classList.add('btn-success');
      iconEl.className = 'fa fa-circle-check fa-3x mb-3 text-success';
    } else {
      header.classList.add('bg-info','text-dark');
      content.classList.add('border-info');
      btnEl.classList.add('btn-info','text-white');
      iconEl.className = 'fa fa-circle-info fa-3x mb-3 text-info';
    }

    titleEl.textContent = title;
    msgEl.textContent   = message;
    btnEl.textContent   = confirmText;

    return new Promise(resolve => {
      hideOpenModals('marketConfirmModal');
      const modal = new bootstrap.Modal(modalEl);
      let confirmed = false;

      function onConfirm() { confirmed = true; cleanup(); modal.hide(); resolve(true); }
      function onHidden()  { cleanup(); if (!confirmed) resolve(false); }
      function cleanup() {
        btnEl.removeEventListener('click', onConfirm);
        modalEl.removeEventListener('hidden.bs.modal', onHidden);
      }

      btnEl.addEventListener('click', onConfirm);
      modalEl.addEventListener('hidden.bs.modal', onHidden);
      modal.show();
    });
  }
  function showMarketSuccess(msg='Action completed') {
    const el = document.getElementById('marketSuccessMsg');
    if (el) el.textContent = msg;
    hideOpenModals('marketSuccessModal');
    new bootstrap.Modal(document.getElementById('marketSuccessModal')).show();
  }
  function showMarketError(msg='Operation failed') {
    const el = document.getElementById('marketErrorMsg');
    if (el) el.textContent = msg;
    hideOpenModals('marketErrorModal');
    new bootstrap.Modal(document.getElementById('marketErrorModal')).show();
  }

  // Product actions
  productsBody.addEventListener('click', async e=>{
    const btn=e.target.closest('button[data-action]');
    if(!btn) return;
    const id=Number(btn.dataset.id);
    const act=btn.dataset.action;

    if(act==='toggle'){
      const newStatus = btn.dataset.status==='available' ? 'sold_out':'available';
      try{
        const r=await fetch(base+'/backend/api/products/status.php',{
          method:'POST',headers:{'Content-Type':'application/json'},
          body:JSON.stringify({product_id:id,status:newStatus})
        });
        const j=await r.json();
        if(!j.success) return showMarketError(j.message||'Failed to update status');
        showMarketSuccess('Status updated');
        await loadProducts();
      }catch(err){ showMarketError('Failed to update status'); }
    } else if(act==='delete'){
      const ok = await openMarketConfirm({
        title: 'Remove product?',
        message: 'This will permanently remove the product.',
        confirmText: 'Remove',
        variant: 'danger'
      });
      if(!ok) return;
      try{
        const r=await fetch(base+'/backend/api/products/delete.php',{
          method:'DELETE',headers:{'Content-Type':'application/json'},
          body:JSON.stringify({product_id:id})
        });
        const j=await r.json();
        if(!j.success) return showMarketError(j.message||'Failed to remove product');
        showMarketSuccess('Product removed');
        await loadProducts();
      }catch(err){ showMarketError('Failed to remove product'); }
    } else if(act==='edit'){
      const row=btn.closest('tr');
      editProductId.value=id;
      editName.value=row.querySelector('td:nth-child(2) strong').textContent.trim();
      editDescription.value=row.querySelector('td:nth-child(2) .small').textContent.trim();
      editPrice.value=row.querySelector('td:nth-child(4)').textContent.replace(/[^0-9.]/g,'');
      document.getElementById('editQuality').value = btn.dataset.quality || '';
      editQty.value=row.querySelector('td:nth-child(6)').textContent;
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
      available_qty:editQty.value,
      quality:document.getElementById('editQuality').value.trim()
    };
    try{
      const r=await fetch(base+'/backend/api/products/update.php',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify(payload)
      });
      const j=await r.json();
      if(!j.success) return showMarketError(j.message||'Failed to update product');
      editModal.hide();
      showMarketSuccess('Product updated');
      await loadProducts();
    }catch(err){ showMarketError('Failed to update product'); }
  });

  // Order actions
  ordersBody.addEventListener('click', async e=>{
    const btn=e.target.closest('button[data-act="status"]');
    if(!btn) return;
    const id=Number(btn.dataset.id);
    const next=btn.dataset.next;

    const variant = next==='cancelled' ? 'danger' : (next==='completed' ? 'success' : 'info');
    const ok = await openMarketConfirm({
      title: 'Update order status?',
      message: `Set order #${id} to ${next}?`,
      confirmText: 'Update',
      variant
    });
    if(!ok) return;

    try{
      const r=await fetch(base+'/backend/api/orders/update_status.php',{
        method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({order_id:id,status:next})
      });
      const j=await r.json();
      if(!j.success) return showMarketError(j.message||'Failed to update order');
      showMarketSuccess('Order updated');
      await loadOrders();
      await loadFinance();
    }catch(err){ showMarketError('Failed to update order'); }
  });

  statusFilter.addEventListener('change', loadOrders);

  const refreshBtn = document.getElementById('refreshBtn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', async () => {
      const original = refreshBtn.innerHTML;
      refreshBtn.disabled = true;
      refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Refreshing...';
      try {
        await loadOrders();
        await loadFinance();
      } finally {
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = original;
      }
    });
  }

  // Tab triggers
  document.getElementById('marketTabs').addEventListener('click', e=>{
    if(e.target.matches('[data-bs-target="#tabOrders"]')) loadOrders();
    if(e.target.matches('[data-bs-target="#tabFinance"]')) loadFinance();
  });

  loadProducts();
});
</script>