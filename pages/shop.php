<?php
session_start(); // ADD THIS
// Authorization disabled for this page
$base = '/Agrilink/pages';
$active = 'shop';
$CURRENT_USER_ID = (int)($_SESSION['user_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop - Smart Harvest</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Fonts + Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <!-- Bootstrap + Shared styles -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/Agrilink/assets/css/include.css" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; }
    .product-card { transition: transform .15s ease, box-shadow .15s ease; border: 1px solid #eee; }
    .product-card:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.08); }
    .product-img { height: 180px; object-fit: cover; }
    .rating i { color: #f1c40f; } /* gold */
    .badge-low { background: #ffdad6; color: #b02a37; }
    .badge-new { background: #d1e7ff; color: #084298; }
    .skeleton { position: relative; overflow: hidden; background: #e9ecef; border-radius: .5rem; }
    .skeleton::after { content: ""; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, rgba(233,236,239,0) 0%, rgba(255,255,255,0.6) 50%, rgba(233,236,239,0) 100%); animation: shimmer 1.2s infinite; }
    @keyframes shimmer { 100% { transform: translateX(100%);} }
    .skeleton-line { height: 12px; margin-bottom: 8px; border-radius: 6px; }
    .skeleton-img { height: 180px; }
    .card-actions .btn { min-width: 42%; }
    .form-range::-webkit-slider-thumb { background: #198754; }
    .toast-container { z-index: 1080; }
    .badge-quality-high { background:#198754; }
    .badge-quality-medium { background:#ffc107; color:#212529; }
    .badge-quality-low { background:#dc3545; }
    .recently-viewed-wrap {
      display: flex;
      gap: 12px;
      overflow-x: auto;
      padding-bottom: 4px;
    }
    .recently-viewed-wrap::-webkit-scrollbar { height: 6px; }
    .recently-viewed-wrap::-webkit-scrollbar-thumb { background: rgba(25,135,84,0.4); border-radius: 4px; }
    .recent-card {
      min-width: 150px;
      max-width: 150px;
      border: 1px solid #eef2ed;
      border-radius: 10px;
      background: #fff;
      box-shadow: 0 4px 10px rgba(0,0,0,0.06);
      transition: transform .15s ease;
      cursor: pointer;
    }
    .recent-card:hover { transform: translateY(-3px); }
    .recent-card img {
      width: 100%;
      height: 90px;
      object-fit: cover;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }
    .recent-card .recent-body { padding: 8px 10px; }
    .recent-card .recent-price {
      font-weight: 600;
      color: #1b5e20;
      font-size: 0.85rem;
    }
    .compare-panel {
      position: fixed;
      right: 24px;
      bottom: 24px;
      width: 320px;
      max-height: 60vh;
      padding: 16px;
      border-radius: 14px;
      background: rgba(255,255,255,0.96);
      border: 1px solid #e5f0e4;
      box-shadow: 0 10px 24px rgba(0,0,0,0.12);
      z-index: 1075;
      backdrop-filter: blur(6px);
      overflow-y: auto;
    }
    .compare-panel.compare-empty { opacity: 0.75; }
    .compare-body .compare-card {
      display: flex;
      gap: 10px;
      padding: 10px;
      border-radius: 10px;
      background: #f8faf7;
      border: 1px solid #e4eddf;
      margin-bottom: 10px;
    }
    .compare-card img {
      width: 54px;
      height: 54px;
      border-radius: 8px;
      object-fit: cover;
    }
    .compare-pill {
      font-size: 0.75rem;
      padding: 2px 6px;
      border-radius: 999px;
      background: #e9f5eb;
      color: #1b5e20;
      margin-right: 6px;
    }
    .price-alert-badge {
      background: #fff3cd;
      color: #856404;
    }
    .price-alert-hit {
      background: #d4edda !important;
      color: #0f5132 !important;
    }
    .bulk-info-badge {
      background: #e8f5e9;
      color: #1b5e20;
    }
    .ring-highlight {
      animation: highlightPulse 1.2s ease;
    }
    @keyframes highlightPulse {
      0% { box-shadow: 0 0 0 0 rgba(25,135,84,0.0); }
      50% { box-shadow: 0 0 0 6px rgba(25,135,84,0.25); }
      100% { box-shadow: 0 0 0 0 rgba(25,135,84,0.0); }
    }
  </style>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/../includes/consumer_nav.php'; ?>

<div class="container pb-5">
  <!-- Floating Cart Button -->

  <!-- Filters/Search -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label mb-1">Search</label>
          <input type="text" id="filterSearch" class="form-control" placeholder="Search products...">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">Min Price</label>
          <input type="number" step="0.01" min="0" id="filterMinPrice" class="form-control" placeholder="0.00">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">Max Price</label>
          <input type="number" step="0.01" min="0" id="filterMaxPrice" class="form-control" placeholder="9999.99">
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">Availability</label>
          <select id="filterAvailability" class="form-select">
            <option value="available" selected>Available</option>
            <option value="all">All</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label mb-1">Sort</label>
          <select id="filterSort" class="form-select">
            <option value="new">Newest</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
            <option value="rating_desc">Rating: High to Low</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <section id="recentlyViewedSection" class="mb-4 d-none">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="text-success fw-semibold mb-0">Recently Viewed</h6>
      <button type="button" class="btn btn-link btn-sm text-decoration-none" id="recentlyViewedClear">Clear</button>
    </div>
    <div class="recently-viewed-wrap" id="recentlyViewedWrap">
      <div class="text-muted small py-3">No recently viewed products yet.</div>
    </div>
  </section>

  <h4 class="mb-3 text-success fw-bold">Available Products</h4>
  <div id="products" class="row g-4"></div>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="orderForm" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title">Place Order</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="orderError" class="alert alert-danger d-none mb-2"></div>
        <input type="hidden" id="orderProductId">
        <div class="mb-2">
          <label class="form-label">Product</label>
          <input type="text" id="orderProductName" class="form-control" readonly>
        </div>
        <div class="mb-2 row g-2">
          <div class="col">
            <label class="form-label">Price/kg (₱)</label>
            <input type="text" id="orderPrice" class="form-control" readonly>
          </div>
          <div class="col">
            <label class="form-label">Available (kg)</label>
            <input type="text" id="orderAvailable" class="form-control" readonly>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Quantity (kg)</label>
          <input type="number" step="0.01" min="0.01" id="orderQty" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Delivery Option</label>
          <select id="orderDelivery" class="form-select">
            <option value="pickup">Pickup</option>
            <option value="delivery">Delivery</option>
          </select>
        </div>
        <div class="mb-2 d-none" id="orderAddressWrap">
          <label class="form-label">Delivery Address</label>
          <input type="text" id="orderAddress" class="form-control"
                 placeholder="Required if delivery"
                 value="<?php echo htmlspecialchars($_SESSION['address'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Contact Number</label>
          <input type="text" id="orderContact" class="form-control"
                 value="<?php echo htmlspecialchars($_SESSION['contact'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="mt-2 p-2 bg-light border rounded">
          <div class="d-flex justify-content-between mb-1 d-none" id="orderFeeRow">
            <span>Delivery Fee:</span>
            <strong>₱<span id="orderDeliveryFee">0.00</span></strong>
          </div>
          <div class="d-flex justify-content-between text-success mb-1 d-none" id="orderDiscountRow">
            <span>Bulk Discount:</span>
            <strong>-₱<span id="orderDiscountAmount">0.00</span></strong>
          </div>
          <div class="d-flex justify-content-between">
            <span>Total:</span>
            <strong>₱<span id="orderTotal">0.00</span></strong>
          </div>
          <div class="small text-muted mt-1" id="orderBulkHint">Order ≥10kg to unlock bulk savings.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit">Confirm Order</button>
      </div>
    </form>
  </div>
</div>

<!-- Add To Cart Modal -->
<div class="modal fade" id="addCartModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addCartForm" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title">Add To Cart</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="addCartError" class="alert alert-danger d-none mb-2"></div>
        <input type="hidden" id="cartProductId">
        <div class="mb-2">
          <label class="form-label">Product</label>
          <input type="text" id="cartProductName" class="form-control" readonly>
        </div>
        <div class="row g-2 mb-2">
          <div class="col">
            <label class="form-label">Price/kg (₱)</label>
            <input type="text" id="cartPrice" class="form-control" readonly>
          </div>
          <div class="col">
            <label class="form-label">Available (kg)</label>
            <input type="text" id="cartAvailable" class="form-control" readonly>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Quantity (kg)</label>
            <input type="number" step="0.01" min="0.01" id="cartQty" class="form-control" required>
        </div>
        <div class="p-2 bg-light border rounded small">
          <div class="d-flex justify-content-between mb-1">
            <span>Subtotal:</span>
            <span>₱<span id="cartItemSubtotal">0.00</span></span>
          </div>
          <div class="d-flex justify-content-between text-success mb-1 d-none" id="cartDiscountRowModal">
            <span>Bulk Discount:</span>
            <span>-₱<span id="cartDiscountAmount">0.00</span></span>
          </div>
          <div class="d-flex justify-content-between fw-semibold">
            <span>Payable:</span>
            <span>₱<span id="cartPayableTotal">0.00</span></span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit">Add To Cart</button>
      </div>
    </form>
  </div>
</div>

<!-- Price Alert Modal (add before existing success/error modals) -->
<div class="modal fade" id="priceAlertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="priceAlertForm" class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h6 class="modal-title"><i class="fas fa-bell me-2"></i>Price Alert</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="priceAlertProductId">
        <div class="mb-2">
          <strong id="priceAlertName"></strong>
          <div class="small text-muted">Current price: ₱<span id="priceAlertCurrent">0.00</span>/kg</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Notify me when price is at or below (₱/kg)</label>
          <input type="number" step="0.01" min="0.01" class="form-control" id="priceAlertTarget" required>
        </div>
        <div class="alert alert-info small mb-0">Alerts are saved on this device.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="priceAlertRemoveBtn">Remove Alert</button>
        <button type="submit" class="btn btn-warning text-dark">Save Alert</button>
      </div>
    </form>
  </div>
</div>

<!-- Info/Warning Modal -->
<div class="modal fade" id="shopInfoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-info" id="shopInfoContent">
      <div class="modal-header bg-info text-dark" id="shopInfoHeader">
        <h6 class="modal-title" id="shopInfoTitle">Please Confirm</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-circle-info fa-3x text-info mb-3" id="shopInfoIcon"></i>
        <div id="shopInfoMessage">Are you sure?</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-info text-white" type="button" id="shopInfoBtn">Proceed</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="shopSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title">Order Placed</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-circle-check fa-3x text-success mb-3"></i>
        <div id="shopSuccessMsg">Your order has been submitted.</div>
      </div>
    </div>
  </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="shopErrorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title">Order Failed</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-triangle-exclamation fa-3x text-danger mb-3"></i>
        <div id="shopErrorMsg">Unable to process your order.</div>
      </div>
    </div>
  </div>
</div>

<!-- Toasts -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const base = location.origin + '/Agrilink';
let CURRENT_USER_ID = <?php echo $CURRENT_USER_ID ?: 0; ?>; // make writable
const FLAT_DELIVERY_FEE = 40;
const BULK_DISCOUNTS = [
  { threshold: 50, rate: 0.10 },
  { threshold: 20, rate: 0.07 },
  { threshold: 10, rate: 0.05 }
];
const RECENT_KEY = 'agrilink_recent_products';
const MAX_RECENT = 6;
const COMPARE_KEY = 'agrilink_compare_products';
const COMPARE_MAX = 3;
const PRICE_ALERT_KEY = 'agrilink_price_alerts';

const orderDiscountRow = document.getElementById('orderDiscountRow');
const orderDiscountAmountEl = document.getElementById('orderDiscountAmount');
const orderBulkHint = document.getElementById('orderBulkHint');

const cartDiscountRowModal = document.getElementById('cartDiscountRowModal');
const cartDiscountAmountEl = document.getElementById('cartDiscountAmount');
const cartItemSubtotalEl = document.getElementById('cartItemSubtotal');
const cartPayableTotalEl = document.getElementById('cartPayableTotal');
const cartDiscountRow = document.getElementById('cartDiscountRow');
const cartDiscountTotalEl = document.getElementById('cartDiscountTotal');

let compareSelection = loadCompareSelection();
let priceAlerts = loadPriceAlerts();
let productsData = [];
let currentCartItems = [];

const orderDeliveryEl      = document.getElementById('orderDelivery');
const orderAddressWrap     = document.getElementById('orderAddressWrap');
const orderFeeRow          = document.getElementById('orderFeeRow');
const orderDeliveryFeeEl   = document.getElementById('orderDeliveryFee');
const orderQtyInput        = document.getElementById('orderQty');
const orderPriceInput      = document.getElementById('orderPrice');
const orderTotalEl         = document.getElementById('orderTotal');

async function hydrateUser(){
  try{
    if (CURRENT_USER_ID) return;
    const res = await fetch(base + '/backend/auth/me.php', { cache:'no-store', credentials:'same-origin' });
    const j = await res.json();
    if (j && j.success && j.user_id) {
      CURRENT_USER_ID = Number(j.user_id);
    }
  }catch(_){}
}

async function updateCartCount(){
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) return;
  try{
    const r = await fetch(base+'/backend/api/cart/get.php?user_id='+CURRENT_USER_ID, { cache:'no-store', credentials:'same-origin' });
    const j = await r.json();
    if (!j.success) return;
    const count = (j.data||[]).reduce((n,it)=>n + (Number(it.quantity_kg)>0 ? 1 : 0), 0);
    [document.getElementById('cartCount'), document.querySelector('[data-cart-count]')].forEach(t=>{ if(t) t.textContent = count; });
  }catch(_){}
}

const productsWrap = document.getElementById('products');
const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
const addCartModal = new bootstrap.Modal(document.getElementById('addCartModal'));

const PRODUCT_PLACEHOLDER = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="480" height="320"><rect width="100%" height="100%" fill="%23e9ecef"/><text x="50%" y="50%" text-anchor="middle" fill="%236c757d" font-size="22" font-family="Arial">No Image</text></svg>';

const ratingsCache = new Map();

function escapeHtml(str){return (str||'').replace(/[&<>"']/g,s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));}
function normalizeImagePath(path){
  if(!path) return null;
  if(/^https?:\/\//i.test(path)) return path;
  if(path.startsWith('/')) return location.origin + path;
  return `${base}/${path.replace(/^\//,'')}`;
}
function getProductImageUrl(p){
  if (p.image_url) {
    const resolved = normalizeImagePath(p.image_url);
    if (resolved) return resolved;
  }
  return `${base}/backend/api/products/image.php?id=${encodeURIComponent(p.product_id)}`;
}

// Toasts
function showToast(message, type='info') {
  const icon = type==='success' ? 'fa-circle-check' : (type==='danger' ? 'fa-triangle-exclamation' : 'fa-circle-info');
  const bg   = type==='success' ? 'bg-success text-white' : (type==='danger' ? 'bg-danger text-white' : 'bg-info text-dark');
  const id = 't'+Date.now()+Math.random().toString(16).slice(2);
  const el = document.createElement('div');
  el.className = `toast align-items-center ${bg} border-0`;
  el.id = id;
  el.role = 'alert';
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body"><i class="fa ${icon} me-2"></i>${escapeHtml(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  document.getElementById('toastContainer').appendChild(el);
  new bootstrap.Toast(el, { delay: 1400 }).show();
}

// Skeletons
function renderSkeleton(count=6){
  productsWrap.innerHTML = '';
  for(let i=0;i<count;i++){
    const col = document.createElement('div');
    col.className = 'col-sm-6 col-lg-4';
    col.innerHTML = `
      <div class="card product-card">
        <div class="skeleton skeleton-img"></div>
        <div class="card-body">
          <div class="skeleton skeleton-line" style="width:60%"></div>
          <div class="skeleton skeleton-line" style="width:90%"></div>
          <div class="d-flex justify-content-between">
            <div class="skeleton skeleton-line" style="width:30%"></div>
            <div class="skeleton skeleton-line" style="width:30%"></div>
          </div>
          <div class="skeleton skeleton-line mt-2" style="width:100%; height:36px;"></div>
        </div>
      </div>`;
    productsWrap.appendChild(col);
  }
}

function ratingStars(avg=0) {
  avg = Number(avg)||0;
  const full = Math.floor(avg);
  const half = (avg - full) >= 0.5 ? 1 : 0;
  let html = '';
  for (let i=0;i<full;i++) html += '<i class="fa-solid fa-star"></i>';
  if (half) html += '<i class="fa-regular fa-star-half-stroke"></i>';
  for (let i=full+half;i<5;i++) html += '<i class="fa-regular fa-star"></i>';
  return html;
}

function productBadges(p){
  const out = [];
  const avail = Number(p.available_qty||0);
  if (avail > 0 && avail <= 5) out.push('<span class="badge badge-low">Low stock</span>');
  if (p.created_at) {
    const d = new Date(p.created_at.replace(' ','T'));
    const days = (Date.now() - d.getTime())/86400000;
    if (!isNaN(days) && days <= 7) out.push('<span class="badge badge-new">New</span>');
  }
  return out.join(' ');
}

function qualityBadge(p){
  const q = (p.quality || p.harvest_quality || p.product_quality || '').toLowerCase();
  if(!q) return '';
  let cls='', label='';
  if(q==='high'){ cls='badge-quality-high'; label='High'; }
  else if(q==='medium'){ cls='badge-quality-medium'; label='Medium'; }
  else if(q==='low'){ cls='badge-quality-low'; label='Low'; }
  else { cls='bg-secondary'; label=q; }
  return `<span class="badge ${cls} me-1" title="Quality">${label} Quality</span>`;
}

// Filters
function applyFilters(data){
  const q = document.getElementById('filterSearch').value.trim().toLowerCase();
  const min = parseFloat(document.getElementById('filterMinPrice').value||'');
  const max = parseFloat(document.getElementById('filterMaxPrice').value||'');
  const avail = document.getElementById('filterAvailability').value;
  const sort = document.getElementById('filterSort').value;

  let arr = data.filter(p=>{
    if (avail === 'available' && p.status !== 'available') return false;
    if (q) {
      const hay = `${p.name||''} ${p.description||''}`.toLowerCase();
      if (!hay.includes(q)) return false;
    }
    const price = Number(p.price_per_kg||0);
    if (!isNaN(min) && price < min) return false;
    if (!isNaN(max) && price > max) return false;
    return true;
  });

  if (sort === 'price_asc') arr.sort((a,b)=>a.price_per_kg-b.price_per_kg);
  else if (sort === 'price_desc') arr.sort((a,b)=>b.price_per_kg-a.price_per_kg);
  else if (sort === 'rating_desc') arr.sort((a,b)=>(ratingsCache.get(b.product_id)||0)-(ratingsCache.get(a.product_id)||0));
  else arr.sort((a,b)=>new Date(b.created_at)-new Date(a.created_at));

  return arr;
}

function renderProducts(list){
  productsWrap.innerHTML = '';
  if (!list.length) {
    productsWrap.innerHTML = '<div class="col-12 text-center text-muted py-4">No products found.</div>';
    return;
  }
  list.forEach(p=>{
    const col = document.createElement('div');
    col.className='col-sm-6 col-lg-4';
    const imgSrc = getProductImageUrl(p) || PRODUCT_PLACEHOLDER;
    const avg = ratingsCache.get(p.product_id) || 0;
    const priceAlertBadge = buildPriceAlertBadge(p);
    const bulkBadge = bulkInfoBadge();
    col.innerHTML = `
      <div class="card product-card h-100" data-product-card="${p.product_id}">
        <img src="${escapeHtml(imgSrc)}" class="card-img-top product-img"
             alt="${escapeHtml(p.name)}"
             onerror="this.onerror=null;this.src='${escapeHtml(PRODUCT_PLACEHOLDER)}';">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start">
            <h6 class="card-title mb-1">${escapeHtml(p.name)}</h6>
            <div class="rating ms-2" data-pid="${p.product_id}" title="${avg?avg.toFixed(1)+' / 5':''}">
              ${ratingStars(avg)}
            </div>
          </div>
          <div class="small mb-2 d-flex flex-wrap align-items-center gap-1">
            ${qualityBadge(p)} ${productBadges(p)} ${priceAlertBadge} ${bulkBadge}
          </div>
          <p class="small text-muted mb-2">${escapeHtml(p.description||'')}</p>
          <div class="mb-2 d-flex justify-content-between">
            <span class="badge bg-success">₱${Number(p.price_per_kg).toFixed(2)}/kg</span>
            <span class="badge ${Number(p.available_qty)<=5?'badge-low':'bg-secondary'}">Avail: ${Number(p.available_qty).toFixed(2)} kg</span>
          </div>
          <div class="mt-auto d-flex gap-2 card-actions">
            <button class="btn btn-outline-success flex-fill order-btn">
              <i class="fa-solid fa-basket-shopping me-1"></i>Order
            </button>
            <button class="btn btn-success flex-fill addcart-btn" ${p.status!=='available'?'disabled':''}>
              <i class="fa-solid fa-cart-plus me-1"></i>Add to Cart
            </button>
          </div>
          <div class="d-flex gap-2 mt-2">
            <button class="btn btn-sm btn-outline-secondary flex-fill compare-btn">
              <i class="fas fa-balance-scale me-1"></i>Compare
            </button>
            <button class="btn btn-sm btn-outline-warning flex-fill alert-btn">
              <i class="fas fa-bell me-1"></i>Alert
            </button>
          </div>
        </div>
      </div>`;
    const orderBtn = col.querySelector('.order-btn');
    orderBtn.addEventListener('click', () => {
      markProductViewed(p);
      orderDeliveryEl.value = 'pickup';
      document.getElementById('orderProductId').value = p.product_id;
      document.getElementById('orderProductName').value = p.name;
      orderPriceInput.value = Number(p.price_per_kg).toFixed(2);
      document.getElementById('orderAvailable').value = Number(p.available_qty).toFixed(2);
      orderQtyInput.value = '';
      refreshOrderModalTotals();
      document.getElementById('orderError').classList.add('d-none');
      orderModal.show();
    });
    const addBtn = col.querySelector('.addcart-btn');
    addBtn?.addEventListener('click', async () => {
      markProductViewed(p);
      if (!CURRENT_USER_ID) { await hydrateUser(); }
      if (!CURRENT_USER_ID) { showToast('Login required','danger'); return; }
      openAddCart({
        id: p.product_id,
        name: p.name,
        price: p.price_per_kg,
        available: p.available_qty
      });
    });
    col.querySelector('.compare-btn').addEventListener('click', () => toggleCompare(p));
    col.querySelector('.alert-btn').addEventListener('click', () => {
      if (!priceAlertModal) return;
      openPriceAlert(p);
    });
    productsWrap.appendChild(col);
  });

  // Bind actions
  highlightCompareButtons();
  renderComparePanel();
}

// Ratings fetch
async function loadRatingsForList(list){
  // Fetch per product summary; update cache and star DOM
  await Promise.all(list.map(async p=>{
    try{
      const r = await fetch(base + '/backend/api/reviews/summary.php?product_id=' + encodeURIComponent(p.product_id), { cache:'no-store' });
      const j = await r.json();
      if (j.success && j.data) {
        const avg = Number(j.data.avg_rating||0);
        ratingsCache.set(p.product_id, avg);
        const el = productsWrap.querySelector(`.rating[data-pid="${p.product_id}"]`);
        if (el) {
          el.innerHTML = ratingStars(avg);
          el.title = (avg?avg.toFixed(1)+' / 5':'');
        }
      }
    }catch(_){}
  }));
  // If sort by rating currently, re-render with new order
  if (document.getElementById('filterSort').value === 'rating_desc') {
    renderProducts(applyFilters(productsData));
  }
}

// Load products
async function loadProducts(){
  renderSkeleton(6);
  try {
    const res = await fetch(base + '/backend/api/products/list.php', { cache:'no-store' });
    const json = await res.json();
    if(!json.success) throw new Error('Load failed');
    productsData = Array.isArray(json.data) ? json.data : [];
    const filtered = applyFilters(productsData);
    renderProducts(filtered);
    loadRatingsForList(filtered);
  } catch(e){
    productsWrap.innerHTML = '<div class="col-12 text-danger">Error loading products.</div>';
  }
}

// Filter bindings
['filterSearch','filterMinPrice','filterMaxPrice','filterAvailability','filterSort'].forEach(id=>{
  const el = document.getElementById(id);
  const evt = id==='filterSearch' ? 'input' : 'change';
  el.addEventListener(evt, ()=>{
    const list = applyFilters(productsData);
    renderProducts(list);
    loadRatingsForList(list);
  });
});

// Order total live calc
document.getElementById('orderQty').addEventListener('input',()=>{
  const q = parseFloat(document.getElementById('orderQty').value||0);
  const price = parseFloat(document.getElementById('orderPrice').value||0);
  document.getElementById('orderTotal').textContent = (q*price).toFixed(2);
});

// ...bulk-aware totals...
function refreshOrderModalTotals(){
  const qty  = parseFloat(orderQtyInput.value || 0);
  const price = parseFloat(orderPriceInput.value || 0);
  const opt  = orderDeliveryEl.value;
  const { subtotal, rate, discount, total } = computeBulkAmounts(qty, price);
  const fee = opt === 'delivery' && qty > 0 ? FLAT_DELIVERY_FEE : 0;

  orderDeliveryFeeEl.textContent = fee.toFixed(2);
  orderAddressWrap.classList.toggle('d-none', opt !== 'delivery');
  orderFeeRow.classList.toggle('d-none', opt !== 'delivery');

  const discountActive = rate > 0 && qty > 0;
  orderDiscountRow.classList.toggle('d-none', !discountActive);
  if (discountActive) orderDiscountAmountEl.textContent = discount.toFixed(2);

  orderTotalEl.textContent = (total + fee).toFixed(2);
  orderBulkHint.textContent = discountActive
    ? `${Math.round(rate * 100)}% bulk discount applied.`
    : 'Order more than 10kg to unlock bulk savings.';
}
document.getElementById('orderQty').addEventListener('input', refreshOrderModalTotals);

orderDeliveryEl.addEventListener('change', refreshOrderModalTotals);
orderQtyInput.addEventListener('input', refreshOrderModalTotals);
refreshOrderModalTotals();

// Modal helpers
function hideOpenModals(exceptId = null) {
  document.querySelectorAll('.modal.show').forEach(modalEl => {
    if (exceptId && modalEl.id === exceptId) return;
    (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
  });
  setTimeout(() => {
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
  }, 200);
}
function openShopInfo({ title='Please Confirm', message='Are you sure?', confirmText='Proceed' }) {
  const header  = document.getElementById('shopInfoHeader');
  const content = document.getElementById('shopInfoContent');
  const iconEl  = document.getElementById('shopInfoIcon');
  const titleEl = document.getElementById('shopInfoTitle');
  const msgEl   = document.getElementById('shopInfoMessage');
  const btnEl   = document.getElementById('shopInfoBtn');
  const modalEl = document.getElementById('shopInfoModal');

  header.className  = 'modal-header bg-info text-dark';
  content.className = 'modal-content border-info';
  iconEl.className  = 'fa fa-circle-info fa-3x text-info mb-3';
  btnEl.className   = 'btn btn-info text-white';

  titleEl.textContent = title;
  msgEl.textContent   = message;
  btnEl.textContent   = confirmText;

  return new Promise(resolve => {
    hideOpenModals('shopInfoModal');
    const modal = new bootstrap.Modal(modalEl);
    let confirmed = false;

    function onConfirm() {
      confirmed = true;
      cleanup();
      modal.hide();
      resolve(true);
    }
    function onHidden() {
      cleanup();
      if (!confirmed) resolve(false);
    }
    function cleanup() {
      btnEl.removeEventListener('click', onConfirm);
      modalEl.removeEventListener('hidden.bs.modal', onHidden);
    }

    btnEl.addEventListener('click', onConfirm);
    modalEl.addEventListener('hidden.bs.modal', onHidden);
    modal.show();
  });
}

function showShopSuccess(message='Order placed successfully!') {
  const el = document.getElementById('shopSuccessMsg');
  if (el) el.textContent = message;
  hideOpenModals('shopSuccessModal');
  new bootstrap.Modal(document.getElementById('shopSuccessModal')).show();
}
function showShopError(message='Unable to process your order.') {
  const el = document.getElementById('shopErrorMsg');
  if (el) el.textContent = message;
  hideOpenModals('shopErrorModal');
  new bootstrap.Modal(document.getElementById('shopErrorModal')).show();
}

// Cart count bubble (if present in nav)
async function updateCartCount(){
  if (!CURRENT_USER_ID) return;
  try{
    const r = await fetch(base+'/backend/api/cart/get.php?user_id='+CURRENT_USER_ID, { cache:'no-store' });
    const j = await r.json();
    if (!j.success) return;
    const count = (j.data||[]).reduce((n,it)=>n + Number(it.quantity_kg>0 ? 1 : 0), 0);
    const targets = [document.getElementById('cartCount'), document.querySelector('[data-cart-count]')].filter(Boolean);
    if (targets.length) targets.forEach(t=>t.textContent = count);
  }catch(_){}
}

// Place order
document.getElementById('orderForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const qty = parseFloat(document.getElementById('orderQty').value || 0);
  const available = parseFloat(document.getElementById('orderAvailable').value || 0);
  if (qty <= 0 || qty > available) {
    showShopError('Quantity must be greater than 0 and not exceed available stock.');
    return;
  }
  const confirm = await openShopInfo({
    title: 'Place order?',
    message: 'Do you want to submit this order?',
    confirmText: 'Submit Order'
  });
  if (!confirm) return;

  const payload = {
    product_id: Number(document.getElementById('orderProductId').value),
    quantity_kg: qty,
    delivery_option: document.getElementById('orderDelivery').value,
    address: document.getElementById('orderAddress').value.trim(),
    contact_info: document.getElementById('orderContact').value.trim()
  };

  try{
    const res = await fetch(base + '/backend/api/orders/create.php', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    });
    const text = await res.text();
    let j;
    try { j = JSON.parse(text); } catch(parseErr) {
      throw new Error(text.replace(/<[^>]*>/g,'').trim() || 'Invalid server response');
    }
    if(!j.success) throw new Error(j.message||'Order failed');
    hideOpenModals();
    showShopSuccess('Order placed. Reference: ' + j.order_id);
    loadProducts();
    updateCartCount();
  }catch(ex){
    showShopError(ex.message || 'Order failed');
  }
});

// Ensure cart functions exist (add if missing)
const cartPanelEl     = document.getElementById('cartPanel');
const cartOffcanvas   = cartPanelEl ? new bootstrap.Offcanvas(cartPanelEl) : null;
const cartList        = document.getElementById('cartList');
const cartSubtotalEl  = document.getElementById('cartSubtotal');
const cartDelFeeEl    = document.getElementById('cartDelFee');
const cartGrandEl     = document.getElementById('cartGrand');
const cartDeliveryEl  = document.getElementById('cartDelivery');
const cartAddressEl   = document.getElementById('cartAddress');
const cartContactEl   = document.getElementById('cartContact');
const cartCheckoutBtn = document.getElementById('cartCheckoutBtn');
const cartCountBubble = document.getElementById('cartCountBubble');


function calcDeliveryFee(deliveryOpt){
  return deliveryOpt === 'delivery' ? FLAT_DELIVERY_FEE : 0;
}
function updateCartTotals(items = []) {
  let subtotal = 0;
  let discount = 0;
  items.forEach(item => {
    const line = computeBulkAmounts(Number(item.quantity_kg) || 0, Number(item.price_per_kg) || 0);
    subtotal += line.subtotal;
    discount += line.discount;
  });
  const deliveryFee = items.length ? calcDeliveryFee(cartDeliveryEl.value) : 0;
  cartSubtotalEl.textContent = '₱' + subtotal.toFixed(2);
  cartDiscountRow.classList.toggle('d-none', discount <= 0);
  if (discount > 0) cartDiscountTotalEl.textContent = discount.toFixed(2);
  cartDelFeeEl.textContent = '₱' + deliveryFee.toFixed(2);
  cartGrandEl.textContent = '₱' + (subtotal - discount + deliveryFee).toFixed(2);
}
cartDeliveryEl?.addEventListener('change', () => updateCartTotals(currentCartItems));
async function loadCart(){
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) {
    cartList.innerHTML = '<div class="list-group-item text-danger">Login required.</div>';
    updateCartTotals([]);
    return;
  }
  cartList.innerHTML = '<div class="list-group-item text-muted">Loading cart...</div>';
  try{
    const r = await fetch(base+'/backend/api/cart/get.php?user_id='+CURRENT_USER_ID, { cache:'no-store' });
    const j = await r.json();
    if(!j.success) throw new Error(j.message||'Cart load failed');
    const items = j.data || [];
    currentCartItems = items.map(it => ({ ...it }));
    if (!items.length) {
      cartList.innerHTML = '<div class="list-group-item text-center text-muted py-4">Cart empty.</div>';
      updateCartTotals(currentCartItems);
      cartCountBubble.textContent = '0';
      return;
    }
    cartList.innerHTML = '';
    items.forEach(it => {
      const line = computeBulkAmounts(Number(it.quantity_kg) || 0, Number(it.price_per_kg) || 0);
      const li = document.createElement('div');
      li.className = 'list-group-item d-flex align-items-start gap-2';
      li.innerHTML = `
        <div class="flex-grow-1">
          <div class="d-flex justify-content-between">
            <strong>${escapeHtml(it.name)}</strong>
            <button class="btn btn-sm btn-outline-danger" data-remove="${it.cart_item_id}"><i class="fa fa-times"></i></button>
          </div>
          <div class="small text-muted mb-1">₱${Number(it.price_per_kg).toFixed(2)}/kg | Avail ${Number(it.available_qty).toFixed(2)}kg</div>
          <div class="d-flex align-items-center gap-2">
            <input type="number" min="0.01" step="0.01" class="form-control form-control-sm w-50"
                   data-qty="${it.cart_item_id}" value="${Number(it.quantity_kg).toFixed(2)}">
            <div>
              <div class="small">Item Total: ₱<span data-itemtotal="${it.cart_item_id}">${line.total.toFixed(2)}</span></div>
              <div class="small text-success ${line.rate>0?'':'d-none'}" data-discountnote="${it.cart_item_id}">
                ${line.rate>0?`-${Math.round(line.rate*100)}% bulk discount`:''}
              </div>
            </div>
          </div>
        </div>`;
      cartList.appendChild(li);
    });
    bindCartEvents(currentCartItems);
    updateCartTotals(currentCartItems);
    cartCountBubble.textContent = items.length;
  }catch(e){
    cartList.innerHTML='<div class="list-group-item text-danger">'+escapeHtml(e.message)+'</div>';
    updateCartTotals([]);
  }
}
function bindCartEvents(items){
  cartList.querySelectorAll('input[data-qty]').forEach(inp=>{
    inp.addEventListener('input', async ()=>{
      const id = Number(inp.dataset.qty);
      const newQty = parseFloat(inp.value||0);
      if(newQty<=0){ inp.value=''; return; }
      try{
        const r = await fetch(base+'/backend/api/cart/update.php',{
          method:'POST',headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ user_id: CURRENT_USER_ID, cart_item_id: id, quantity_kg: newQty })
        });
        const j = await r.json();
        if(!r.ok || !j.success) throw new Error(j.message||'Update failed');
        const item = items.find(i=>i.cart_item_id==id);
        if(item){
          item.quantity_kg=newQty;
          const line = computeBulkAmounts(newQty, item.price_per_kg);
          cartList.querySelector(`[data-itemtotal="${id}"]`).textContent = line.total.toFixed(2);
          const note = cartList.querySelector(`[data-discountnote="${id}"]`);
          if (note) {
            note.classList.toggle('d-none', line.rate <= 0);
            note.textContent = line.rate > 0 ? `-${Math.round(line.rate*100)}% bulk discount` : '';
          }
          updateCartTotals(items);
        }
        showToast('Cart updated','success');
      }catch(ex){ showToast(ex.message||'Update failed','danger'); }
    });
  });
  cartList.querySelectorAll('button[data-remove]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id = Number(btn.dataset.remove);
      try{
        const r = await fetch(base+'/backend/api/cart/remove.php',{
          method:'POST',headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ user_id: CURRENT_USER_ID, cart_item_id: id })
        });
        const j = await r.json();
        if(!r.ok || !j.success) throw new Error(j.message||'Remove failed');
        showToast('Item removed','success');
        await loadCart();
        updateCartCount();
      }catch(ex){ showToast(ex.message||'Remove failed','danger'); }
    });
  });
}
cartDeliveryEl?.addEventListener('change', () => updateCartTotals(currentCartItems));

// Add To Cart Modal logic
const addCartModalEl = document.getElementById('addCartModal');
const addCartForm    = document.getElementById('addCartForm');

function openAddCart(prod){
  const err = document.getElementById('addCartError');
  err.classList.add('d-none');
  document.getElementById('cartProductId').value   = prod.id;
  document.getElementById('cartProductName').value = prod.name;
  document.getElementById('cartPrice').value       = Number(prod.price).toFixed(2);
  document.getElementById('cartAvailable').value   = Number(prod.available).toFixed(2);
  document.getElementById('cartQty').value         = '';
  cartItemSubtotalEl.textContent = '0.00';
  cartPayableTotalEl.textContent = '0.00';
  cartDiscountAmountEl.textContent = '0.00';
  cartDiscountRowModal.classList.add('d-none');
  addCartModal.show();
}
document.getElementById('cartQty').addEventListener('input', () => {
  const qty = parseFloat(document.getElementById('cartQty').value || 0);
  const price = parseFloat(document.getElementById('cartPrice').value || 0);
  const { subtotal, discount, total, rate } = computeBulkAmounts(qty, price);
  cartItemSubtotalEl.textContent = subtotal.toFixed(2);
  cartPayableTotalEl.textContent = total.toFixed(2);
  cartDiscountRowModal.classList.toggle('d-none', !(rate > 0 && qty > 0));
  if (rate > 0 && qty > 0) cartDiscountAmountEl.textContent = discount.toFixed(2);
});

addCartForm.addEventListener('submit', async e=>{
  e.preventDefault();
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) { showToast('Login required','danger'); return; }
  const pid = Number(document.getElementById('cartProductId').value);
  const qty = parseFloat(document.getElementById('cartQty').value||0);
  const avail = parseFloat(document.getElementById('cartAvailable').value||0);
  const errBox = document.getElementById('addCartError');
  if (qty<=0 || qty>avail){
    errBox.textContent='Invalid quantity.';
    errBox.classList.remove('d-none');
    return;
  }
  try{
    const r = await fetch(base+'/backend/api/cart/add.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ user_id: CURRENT_USER_ID, product_id: pid, quantity_kg: qty })
    });
    const j = await r.json();
    if(!r.ok || !j.success) throw new Error(j.message||'Add failed');
    addCartModal.hide();
    showToast('Added to cart','success');
    updateCartCount();
    await loadCart();
    const off = getCartOffcanvas();
    off && off.show();
  }catch(ex){
    errBox.textContent = ex.message || 'Add failed';
    errBox.classList.remove('d-none');
  }
});

// Replace offcanvas init with lazy getter (markup is below the script)
function getCartOffcanvas(){
  const el = document.getElementById('cartPanel');
  return el ? bootstrap.Offcanvas.getOrCreateInstance(el) : null;
}

// Floating cart button now redirects to cart page


// When checking out, hide cart using lazy init
cartCheckoutBtn?.addEventListener('click', async ()=>{
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) { showToast('Login required','danger'); return; }
  const delivery_option = cartDeliveryEl.value;
  const address = cartAddressEl.value.trim();
  const contact = cartContactEl.value.trim();
  if(delivery_option==='delivery' && !address){ showToast('Address required','danger'); return; }
  if(!confirm('Confirm ordering all items in cart?')) return;
  try{
    const r = await fetch(base+'/backend/api/cart/checkout.php',{
      method:'POST',headers:{'Content-Type':'application/json'},
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
    showToast('Orders created: '+j.orders.length,'success');
    const off = getCartOffcanvas();
    off && off.hide();
    updateCartCount();
    loadProducts();
  }catch(ex){ showToast(ex.message||'Checkout failed','danger'); }
});

// Replace updateCartCount to also bubble count
async function updateCartCount(){
  if (!CURRENT_USER_ID) { await hydrateUser(); }
  if (!CURRENT_USER_ID) return;
  try{
    const r = await fetch(base+'/backend/api/cart/get.php?user_id='+CURRENT_USER_ID, { cache:'no-store' });
    const j = await r.json();
    if(!j.success) return;
    const items = j.data||[];
    const count = items.length;
    if(cartCountBubble) cartCountBubble.textContent = count;
    const navCount = document.getElementById('cartCount');
    if(navCount) navCount.textContent = count;
  }catch(_){}
}

// Initial boot
hydrateUser().then(()=>{
  updateCartCount();
  loadProducts();
});

// ...helper functions section...
function loadCompareSelection() {
  try {
    const raw = localStorage.getItem(COMPARE_KEY);
    const list = raw ? JSON.parse(raw) : [];
    return Array.isArray(list) ? list.slice(0, COMPARE_MAX).map(Number) : [];
  } catch { return []; }
}
function saveCompareSelection() {
  localStorage.setItem(COMPARE_KEY, JSON.stringify(compareSelection));
}
function loadPriceAlerts() {
  try {
    const raw = localStorage.getItem(PRICE_ALERT_KEY);
    const obj = raw ? JSON.parse(raw) : {};
    return obj && typeof obj === 'object' ? obj : {};
  } catch { return {}; }
}
function savePriceAlerts() {
  localStorage.setItem(PRICE_ALERT_KEY, JSON.stringify(priceAlerts));
}
function getRecentProducts() {
  try {
    const raw = localStorage.getItem(RECENT_KEY);
    const list = raw ? JSON.parse(raw) : [];
    return Array.isArray(list) ? list : [];
  } catch { return []; }
}
function saveRecentProducts(list) {
  localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, MAX_RECENT)));
}
function markProductViewed(product) {
  const list = getRecentProducts().filter(item => item.id !== product.product_id);
  list.unshift({
    id: product.product_id,
    name: product.name,
    price: Number(product.price_per_kg),
    image: getProductImageUrl(product)
  });
  saveRecentProducts(list);
  renderRecentProducts();
}
function renderRecentProducts() {
  const wrap = document.getElementById('recentlyViewedWrap');
  const section = document.getElementById('recentlyViewedSection');
  if (!wrap || !section) return;
  const list = getRecentProducts();
  if (!list.length) {
    wrap.innerHTML = '<div class="text-muted small py-3">No recently viewed products yet.</div>';
    section.classList.add('d-none');
    return;
  }
  section.classList.remove('d-none');
  wrap.innerHTML = list.map(item => `
    <div class="recent-card" data-recent-id="${item.id}">
      <img src="${escapeHtml(item.image || PRODUCT_PLACEHOLDER)}" alt="${escapeHtml(item.name)}">
      <div class="recent-body">
        <div class="small fw-semibold text-truncate">${escapeHtml(item.name)}</div>
        <div class="recent-price">₱${Number(item.price).toFixed(2)}/kg</div>
      </div>
    </div>
  `).join('');
  wrap.querySelectorAll('[data-recent-id]').forEach(card => {
    card.addEventListener('click', () => {
      const pid = Number(card.dataset.recentId);
      const cardEl = document.querySelector(`[data-product-card="${pid}"]`);
      if (cardEl) {
        cardEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        cardEl.classList.add('ring-highlight');
        setTimeout(() => cardEl.classList.remove('ring-highlight'), 1200);
      }
    });
  });
}
document.getElementById('recentlyViewedClear')?.addEventListener('click', () => {
  saveRecentProducts([]);
  renderRecentProducts();
});

function getBulkDiscountRate(qty) {
  let rate = 0;
  for (const rule of BULK_DISCOUNTS) {
    if (qty >= rule.threshold && rule.rate > rate) rate = rule.rate;
  }
  return rate;
}
function computeBulkAmounts(qty, price) {
  const subtotal = qty * price;
  const rate = getBulkDiscountRate(qty);
  const discount = subtotal * rate;
  return { subtotal, rate, discount, total: subtotal - discount };
}
function bulkInfoBadge() {
  const primary = BULK_DISCOUNTS.slice().sort((a, b) => b.threshold - a.threshold)[0];
  return `<span class="badge bulk-info-badge">Bulk ${Math.round(primary.rate * 100)}% ≥${primary.threshold}kg</span>`;
}
function buildPriceAlertBadge(product) {
  const target = priceAlerts[product.product_id];
  if (!target) return '';
  const price = Number(product.price_per_kg);
  return price <= target
    ? '<span class="badge price-alert-badge price-alert-hit">Price alert hit</span>'
    : `<span class="badge price-alert-badge">Alert @ ₱${Number(target).toFixed(2)}</span>`;
}

if (priceAlertForm && priceAlertModal) {
  let priceAlertProductId = null;

  function openPriceAlert(product) {
    priceAlertProductId = product.product_id;
    document.getElementById('priceAlertProductId').value = product.product_id;
    document.getElementById('priceAlertName').textContent = product.name;
    document.getElementById('priceAlertCurrent').textContent = Number(product.price_per_kg).toFixed(2);
    document.getElementById('priceAlertTarget').value = priceAlerts[product.product_id] ?? '';
    priceAlertModal.show();
  }
  window.openPriceAlert = openPriceAlert;

  priceAlertForm.addEventListener('submit', e => {
    e.preventDefault();
    if (!priceAlertProductId) return;
    const target = parseFloat(document.getElementById('priceAlertTarget').value || '0');
    if (!target || target <= 0) { showToast('Enter a valid target price.','danger'); return; }
    priceAlerts[priceAlertProductId] = target;
    savePriceAlerts();
    priceAlertModal.hide();
    showToast('Price alert saved.','success');
    const list = applyFilters(productsData);
    renderProducts(list);
    loadRatingsForList(list);
  });

  priceAlertRemoveBtn?.addEventListener('click', () => {
    if (!priceAlertProductId) return;
    delete priceAlerts[priceAlertProductId];
    savePriceAlerts();
    document.getElementById('priceAlertTarget').value = '';
    priceAlertModal.hide();
    showToast('Price alert removed.','info');
    const list = applyFilters(productsData);
    renderProducts(list);
    loadRatingsForList(list);
  });
}

// Compare functions
function toggleCompare(product) {
  const id = Number(product.product_id);
  const index = compareSelection.indexOf(id);
  if (index >= 0) compareSelection.splice(index, 1);
  else {
    if (compareSelection.length >= COMPARE_MAX) { showToast('Compare limit reached (3 items).','danger'); return; }
    compareSelection.push(id);
  }
  saveCompareSelection();
  renderComparePanel();
  highlightCompareButtons();
}
function renderComparePanel() {
  const panel = document.getElementById('comparePanel');
  const body = document.getElementById('compareBody');
  const notice = document.getElementById('compareNotice');
  if (!panel || !body || !notice) return;
  if (!compareSelection.length) {
    body.innerHTML = '';
    notice.classList.remove('d-none');
    panel.classList.add('compare-empty');
    return;
  }
  notice.classList.add('d-none');
  panel.classList.remove('compare-empty');
  const rows = compareSelection
    .map(id => productsData.find(p => Number(p.product_id) === Number(id)))
    .filter(Boolean);
  body.innerHTML = rows.map(p => `
    <div class="compare-card">
      <img src="${escapeHtml(getProductImageUrl(p) || PRODUCT_PLACEHOLDER)}" alt="${escapeHtml(p.name)}">
      <div class="flex-grow-1 small">
        <div class="d-flex justify-content-between">
          <strong>${escapeHtml(p.name)}</strong>
          <button type="button" class="btn btn-link btn-sm text-danger p-0" data-remove-compare="${p.product_id}">Remove</button>
        </div>
        <div>Price: ₱${Number(p.price_per_kg).toFixed(2)}/kg</div>
        <div>Available: ${Number(p.available_qty).toFixed(2)} kg</div>
        <div>Quality: ${escapeHtml((p.quality || p.harvest_quality || p.product_quality || 'N/A').toString())}</div>
      </div>
    </div>
  `).join('');
  body.querySelectorAll('[data-remove-compare]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = Number(btn.dataset.removeCompare);
      compareSelection = compareSelection.filter(item => item !== id);
      saveCompareSelection();
      renderComparePanel();
      highlightCompareButtons();
    });
  });
}
document.getElementById('compareClearBtn')?.addEventListener('click', () => {
  compareSelection = [];
  saveCompareSelection();
  renderComparePanel();
  highlightCompareButtons();
});
function highlightCompareButtons() {
  document.querySelectorAll('[data-compare-id]').forEach(btn => {
    const id = Number(btn.dataset.compareId);
    const active = compareSelection.includes(id);
    btn.classList.toggle('btn-success', active);
    btn.classList.toggle('btn-outline-secondary', !active);
  });
}
</script>
<!-- Cart Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartPanel">
  <div class="offcanvas-header">
    <h6 class="offcanvas-title">My Cart</h6>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0 d-flex flex-column">
    <div id="cartList" class="list-group list-group-flush small"></div>
    <div class="mt-auto p-3 border-top">
      <div class="d-flex justify-content-between mb-2">
        <span>Subtotal:</span><strong id="cartSubtotal">₱0.00</strong>
      </div>
      <div class="d-flex justify-content-between mb-2 text-success d-none" id="cartDiscountRow">
        <span>Bulk Discount:</span><strong>-₱<span id="cartDiscountTotal">0.00</span></strong>
      </div>
      <div class="mb-2">
        <label class="form-label mb-1">Delivery Option</label>
        <select id="cartDelivery" class="form-select form-select-sm">
          <option value="pickup">Pickup (₱0)</option>
          <option value="delivery">Delivery</option>
        </select>
      </div>
      <div class="mb-2">
        <label class="form-label mb-1">Address (delivery)</label>
        <input type="text" id="cartAddress" class="form-control form-control-sm" placeholder="Delivery address">
      </div>
      <div class="mb-2">
        <label class="form-label mb-1">Contact</label>
        <input type="text" id="cartContact" class="form-control form-control-sm"
               value="<?php echo htmlspecialchars($_SESSION['contact'] ?? '', ENT_QUOTES); ?>">
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span>Est. Delivery Fee:</span><strong id="cartDelFee">₱0.00</strong>
      </div>
      <div class="d-flex justify-content-between mb-3">
        <span>Total:</span><strong id="cartGrand">₱0.00</strong>
      </div>
      <button class="btn btn-success w-100 btn-sm" id="cartCheckoutBtn">
        <i class="fa fa-check me-1"></i>Confirm Order (All)
      </button>
    </div>
  </div>
</div>

<!-- Comparison panel near end of body -->
<div id="comparePanel" class="compare-panel shadow-sm compare-empty">
  <div class="compare-panel-header d-flex justify-content-between align-items-center mb-2">
    <span class="fw-semibold"><i class="fas fa-balance-scale me-2"></i>Compare</span>
    <button type="button" class="btn btn-link btn-sm text-decoration-none" id="compareClearBtn">Clear</button>
  </div>
  <div id="compareNotice" class="text-muted small">Select up to three products to compare.</div>
  <div id="compareBody" class="compare-body mt-2"></div>
</div>
</body>
</html>