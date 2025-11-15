<?php

session_start();
// Authorization disabled for this page
$base = '/Agrilink/pages';
$active = 'cart';
$CURRENT_USER_ID = (int)($_SESSION['user_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Cart - Smart Harvest</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="/Agrilink/assets/css/include.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/../includes/consumer_nav.php'; ?>

<div class="container pb-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="text-success fw-bold mb-0">My Cart</h4>
    <a href="<?php echo $base; ?>/shop.php" class="btn btn-outline-success btn-sm">
      <i class="fa fa-store me-1"></i>Continue Shopping
    </a>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:52px;"></th>
                  <th>Product</th>
                  <th>Price/kg</th>
                  <th style="width:160px;">Qty (kg)</th>
                  <th>Item Total</th>
                  <th style="width:80px;"></th>
                </tr>
              </thead>
              <tbody id="cartTbody">
                <tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr>
              </tbody>
              <tfoot class="d-none" id="cartFoot">
                <tr>
                  <td colspan="6" class="text-end small text-muted px-3 pb-2">Prices may change at checkout if stock changes.</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3">Summary</h6>
          <div class="mb-2">
            <label class="form-label mb-1">Delivery Option</label>
            <select id="cartDelivery" class="form-select form-select-sm">
              <option value="pickup">Pickup (₱0)</option>
              <option value="delivery" selected>Delivery (₱40)</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label mb-1">Address (delivery)</label>
            <input type="text" id="cartAddress" class="form-control form-control-sm"
                   value="<?php echo htmlspecialchars($_SESSION['address'] ?? '', ENT_QUOTES); ?>"
                   placeholder="Delivery address">
          </div>
          <div class="mb-3">
            <label class="form-label mb-1">Contact</label>
            <input type="text" id="cartContact" class="form-control form-control-sm"
                   value="<?php echo htmlspecialchars($_SESSION['contact'] ?? '', ENT_QUOTES); ?>"
                   placeholder="Contact number">
          </div>
          <ul class="list-unstyled small mb-3">
            <li class="d-flex justify-content-between"><span>Subtotal:</span><strong id="sumSubtotal">₱0.00</strong></li>
            <li class="d-flex justify-content-between"><span>Est. Delivery Fee:</span><strong id="sumFee">₱0.00</strong></li>
            <li class="d-flex justify-content-between border-top pt-2"><span>Total:</span><strong id="sumGrand">₱0.00</strong></li>
          </ul>
          <button class="btn btn-success w-100" id="checkoutBtn">
            <i class="fa fa-check me-1"></i>Confirm Order (All)
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Confirm Checkout Modal -->
<div class="modal fade" id="confirmCheckoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h6 class="modal-title">Confirm Checkout</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Please confirm ordering all items in your cart.</p>
        <ul class="list-unstyled small mb-0">
          <li class="d-flex justify-content-between"><span>Items:</span><strong id="ccItems">0</strong></li>
          <li class="d-flex justify-content-between"><span>Subtotal:</span><strong id="ccSubtotal">₱0.00</strong></li>
          <li class="d-flex justify-content-between"><span>Delivery Fee:</span><strong id="ccFee">₱0.00</strong></li>
          <li class="d-flex justify-content-between border-top pt-2"><span>Total:</span><strong id="ccGrand">₱0.00</strong></li>
        </ul>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning" id="confirmCheckoutBtn">
          <span class="spinner-border spinner-border-sm me-1 d-none" id="ccSpin"></span>
          <span id="ccBtnText">Confirm</span>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const base = location.origin + '/Agrilink';
let CURRENT_USER_ID = <?php echo $CURRENT_USER_ID ?: 0; ?>;
const FLAT_DELIVERY_FEE = 40;

function escapeHtml(s){return (s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function normalizeImagePath(path){
  if(!path) return null;
  if(/^https?:\/\//i.test(path)) return path;
  if(path.startsWith('/')) return location.origin + path;
  return `${base}/${path.replace(/^\//,'')}`;
}
function showToast(msg,type='info'){
  const cont=document.getElementById('toastContainer');
  const bg= type==='success'?'bg-success text-white' : type==='danger'?'bg-danger text-white':'bg-info text-dark';
  const el=document.createElement('div'); el.className='toast '+bg; el.role='alert';
  el.innerHTML=`<div class="d-flex"><div class="toast-body small">${escapeHtml(msg)}</div>
    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  cont.appendChild(el); new bootstrap.Toast(el,{delay:1400}).show();
}

const ccModalEl        = document.getElementById('confirmCheckoutModal');
const ccModal          = new bootstrap.Modal(ccModalEl);
const ccItemsEl        = document.getElementById('ccItems');
const ccSubtotalEl     = document.getElementById('ccSubtotal');
const ccFeeEl          = document.getElementById('ccFee');
const ccGrandEl        = document.getElementById('ccGrand');
const confirmCheckoutBtn = document.getElementById('confirmCheckoutBtn');
const ccSpin           = document.getElementById('ccSpin');
const ccBtnText        = document.getElementById('ccBtnText');

async function hydrateUser(){
  if (CURRENT_USER_ID) return;
  try{
    const r = await fetch(base+'/backend/auth/me.php',{cache:'no-store',credentials:'same-origin'});
    const j = await r.json();
    if (j.success && j.user_id) CURRENT_USER_ID = Number(j.user_id);
  }catch(_){}
}

const tbody = document.getElementById('cartTbody');
const tfoot = document.getElementById('cartFoot');
const deliveryEl = document.getElementById('cartDelivery');
const addressEl = document.getElementById('cartAddress');
const contactEl = document.getElementById('cartContact');
const subtotalEl = document.getElementById('sumSubtotal');
const feeEl = document.getElementById('sumFee');
const grandEl = document.getElementById('sumGrand');

// keep delivery selected on load
deliveryEl.value = 'delivery';

function calcFee(opt, items){
  if (opt === 'pickup') return 0;
  return FLAT_DELIVERY_FEE;
}
function updateSummary(items){
  const subtotal = items.reduce((n,i)=> n + (parseFloat(i.quantity_kg||0)*parseFloat(i.price_per_kg||0)), 0);
  const fee = items.length ? calcFee(deliveryEl.value, items) : 0;
  subtotalEl.textContent = '₱'+subtotal.toFixed(2);
  feeEl.textContent = '₱'+fee.toFixed(2);
  grandEl.textContent = '₱'+(subtotal+fee).toFixed(2);
}

async function loadCart(){
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center py-4">Login required.</td></tr>';
    tfoot.classList.add('d-none'); return;
  }
  tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center py-4">Loading...</td></tr>';
  try{
    const r = await fetch(base + '/backend/api/cart/get.php?user_id=' + CURRENT_USER_ID, { cache:'no-store' });
    const j = await r.json();
    if(!j.success) throw new Error(j.message||'Load failed');
    const items = j.data || [];
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Your cart is empty.</td></tr>';
      tfoot.classList.add('d-none');
      updateSummary([]);
      updateCartCount(); // nav bubble
      return;
    }
    tfoot.classList.remove('d-none');
    tbody.innerHTML = '';
    items.forEach(it=>{
      const tr = document.createElement('tr');
      const img = normalizeImagePath(it.image_url) || 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="64" height="48"><rect width="100%" height="100%" fill="%23e9ecef"/></svg>';
      tr.innerHTML = `
        <td><img src="${escapeHtml(img)}" alt="" class="rounded" style="width:52px;height:40px;object-fit:cover"></td>
        <td>${escapeHtml(it.name)}</td>
        <td>₱${Number(it.price_per_kg).toFixed(2)}</td>
        <td>
          <div class="input-group input-group-sm" style="max-width:150px;">
            <input type="number" class="form-control" min="0.01" step="0.01"
                   data-qty="${it.cart_item_id}" data-avail="${it.available_qty}"
                   value="${Number(it.quantity_kg).toFixed(2)}">
            <span class="input-group-text">kg</span>
          </div>
          <div class="small text-muted">Avail: ${Number(it.available_qty).toFixed(2)} kg</div>
        </td>
        <td>₱<span data-itemtotal="${it.cart_item_id}">${(it.quantity_kg * it.price_per_kg).toFixed(2)}</span></td>
        <td class="text-end">
          <button class="btn btn-outline-danger btn-sm" title="Remove" data-remove="${it.cart_item_id}">
            <i class="fa fa-trash"></i>
          </button>
        </td>`;
      tbody.appendChild(tr);
    });
    bindRowEvents(items);
    updateSummary(items);
    updateCartCount();
  }catch(e){
    tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center py-4">'+escapeHtml(e.message)+'</td></tr>';
    tfoot.classList.add('d-none');
  }
}

function bindRowEvents(items){
  // qty change
  tbody.querySelectorAll('input[data-qty]').forEach(inp=>{
    inp.addEventListener('change', async ()=>{
      const id = Number(inp.dataset.qty);
      let qty = parseFloat(inp.value || 0);
      const avail = parseFloat(inp.dataset.avail || 0);
      if (qty<=0){ showToast('Quantity must be > 0','danger'); inp.value=''; return; }
      if (qty>avail){ qty = avail; inp.value = avail.toFixed(2); }
      try{
        const r = await fetch(base + '/backend/api/cart/update.php', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ user_id: CURRENT_USER_ID, cart_item_id: id, quantity_kg: qty })
        });
        const j = await r.json();
        if(!r.ok || !j.success) throw new Error(j.message||'Update failed');
        const item = items.find(x=>x.cart_item_id==id);
        if (item){
          item.quantity_kg = qty;
          tbody.querySelector(`[data-itemtotal="${id}"]`).textContent = (qty*item.price_per_kg).toFixed(2);
          updateSummary(items);
        }
        showToast('Cart updated','success');
      }catch(ex){ showToast(ex.message||'Update failed','danger'); }
    });
  });

  // remove
  tbody.querySelectorAll('button[data-remove]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id = Number(btn.dataset.remove);
      try{
        const r = await fetch(base + '/backend/api/cart/remove.php', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ user_id: CURRENT_USER_ID, cart_item_id: id })
        });
        const j = await r.json();
        if(!r.ok || !j.success) throw new Error(j.message||'Remove failed');
        showToast('Item removed','success');
        loadCart();
      }catch(ex){ showToast(ex.message||'Remove failed','danger'); }
    });
  });
}

deliveryEl.addEventListener('change', async ()=>{
  // recompute with current DOM items
  const items=[];
  tbody.querySelectorAll('tr').forEach(tr=>{
    const qtyEl = tr.querySelector('input[data-qty]');
    const totalEl = qtyEl ? tr.querySelector(`[data-itemtotal="${qtyEl.dataset.qty}"]`) : null;
    if (qtyEl && totalEl){
      const qty = parseFloat(qtyEl.value||0);
      const price = qty ? parseFloat(totalEl.textContent)/qty : 0;
      items.push({ quantity_kg: qty, price_per_kg: price });
    }
  });
  updateSummary(items);
});

document.getElementById('checkoutBtn').addEventListener('click', async ()=>{
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) { showToast('Login required','danger'); return; }
  const delivery_option = deliveryEl.value;
  const address = addressEl.value.trim();
  if(delivery_option==='delivery' && !address){ showToast('Address required for delivery','danger'); return; }

  // Derive items count, subtotal, fee, grand from current UI
  const itemsCount = tbody.querySelectorAll('input[data-qty]').length;
  const subtotal = parsePeso(subtotalEl.textContent);
  const fee = parsePeso(feeEl.textContent);
  const grand = parsePeso(grandEl.textContent);

  ccItemsEl.textContent = itemsCount;
  ccSubtotalEl.textContent = '₱' + subtotal.toFixed(2);
  ccFeeEl.textContent = '₱' + fee.toFixed(2);
  ccGrandEl.textContent = '₱' + grand.toFixed(2);

  // Stash payload on button dataset for the confirm handler
  confirmCheckoutBtn.dataset.delivery_option = delivery_option;
  confirmCheckoutBtn.dataset.address = address;
  confirmCheckoutBtn.dataset.contact = contactEl.value.trim();

  ccModal.show();
});

// Confirm button performs checkout
let ccBusy = false;
confirmCheckoutBtn.addEventListener('click', async ()=>{
  if (ccBusy) return;
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) { showToast('Login required','danger'); return; }

  ccBusy = true;
  confirmCheckoutBtn.disabled = true;
  ccSpin.classList.remove('d-none');
  ccBtnText.textContent = 'Processing...';

  const delivery_option = confirmCheckoutBtn.dataset.delivery_option || 'pickup';
  const address = confirmCheckoutBtn.dataset.address || '';
  const contact = confirmCheckoutBtn.dataset.contact || '';

  try{
    const r = await fetch(base + '/backend/api/cart/checkout.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        user_id: CURRENT_USER_ID,
        delivery_option,
        address,
        contact_info: contact,
        payment_method: 'cash'
      })
    });
    const j = await r.json();
    if(!r.ok || !j.success) throw new Error(j.message||'Checkout failed');
    ccModal.hide();
    showToast('Orders created: ' + (j.orders?j.orders.length:0), 'success');
    loadCart();
  }catch(ex){
    showToast(ex.message||'Checkout failed','danger');
  }finally{
    ccBusy = false;
    confirmCheckoutBtn.disabled = false;
    ccSpin.classList.add('d-none');
    ccBtnText.textContent = 'Confirm';
  }
});

// Helper to parse currency string like "₱123.45"
function parsePeso(txt){ return parseFloat(String(txt).replace(/[^\d.]/g,'')||0); }

async function updateCartCount(){
  if(!CURRENT_USER_ID){ await hydrateUser(); }
  if(!CURRENT_USER_ID) return;
  try{
    const r = await fetch(base + '/backend/api/cart/get.php?user_id=' + CURRENT_USER_ID, { cache:'no-store' });
    const j = await r.json();
    if(!j.success) return;
    const count = (j.data||[]).length;
    const el = document.getElementById('cartCount');
    if (el) el.textContent = count;
  }catch(_){}
}

hydrateUser().then(()=>{ loadCart(); updateCartCount(); });
</script>
</body>
</html>