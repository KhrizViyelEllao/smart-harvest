<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'consumer') {
  header('Location: /Agrilink/index.php?login_error=' . urlencode('Login as consumer to access the shop'));
  exit;
}
$base = '/Agrilink/pages';
$active = 'shop';
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
  </style>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/../includes/consumer_nav.php'; ?>

<div class="container pb-5">
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
        <div class="mb-2">
          <label class="form-label">Delivery Address</label>
          <input type="text" id="orderAddress" class="form-control" placeholder="Required if delivery"
                 value="<?php echo htmlspecialchars($_SESSION['address'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Contact Number</label>
          <input type="text" id="orderContact" class="form-control"
                 value="<?php echo htmlspecialchars($_SESSION['contact'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="mt-2 p-2 bg-light border rounded">
          <strong>Total: ₱<span id="orderTotal">0.00</span></strong>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit">Confirm Order</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const base = location.origin + '/Agrilink';
const productsWrap = document.getElementById('products');
const orderModal = new bootstrap.Modal(document.getElementById('orderModal'));

function escapeHtml(str){return (str||'').replace(/[&<>"']/g,s=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));}

async function loadProducts(){
  productsWrap.innerHTML = '<div class="col-12 text-muted">Loading...</div>';
  try {
    const res = await fetch(base + '/backend/api/products/list.php');
    const json = await res.json();
    if(!json.success) throw new Error('Load failed');
    const data = (json.data||[]).filter(p=>p.status==='available');
    if(!data.length){
      productsWrap.innerHTML = '<div class="col-12 text-center text-muted py-4">No available products.</div>';
      return;
    }
    productsWrap.innerHTML = '';
    data.forEach(p=>{
      const col = document.createElement('div');
      col.className='col-sm-6 col-lg-4';
      col.innerHTML = `
        <div class="card h-100 shadow-sm">
          ${p.image_url?'<img src="'+escapeHtml(p.image_url)+'" class="card-img-top" style="height:180px;object-fit:cover;">':''}
          <div class="card-body d-flex flex-column">
            <h6 class="card-title mb-1">${escapeHtml(p.name)}</h6>
            <p class="small text-muted mb-2">${escapeHtml(p.description||'')}</p>
            <div class="mb-2">
              <span class="badge bg-success">₱${Number(p.price_per_kg).toFixed(2)}/kg</span>
              <span class="badge bg-secondary">Avail: ${Number(p.available_qty).toFixed(2)} kg</span>
            </div>
            <button class="btn btn-outline-success mt-auto order-btn"
                    data-id="${p.product_id}"
                    data-name="${escapeHtml(p.name)}"
                    data-price="${p.price_per_kg}"
                    data-available="${p.available_qty}">
              <i class="fa-solid fa-cart-plus me-1"></i>Order Now
            </button>
          </div>
        </div>`;
      productsWrap.appendChild(col);
    });
    document.querySelectorAll('.order-btn').forEach(btn=>{
      btn.addEventListener('click',()=>{
        document.getElementById('orderProductId').value = btn.dataset.id;
        document.getElementById('orderProductName').value = btn.dataset.name;
        document.getElementById('orderPrice').value = Number(btn.dataset.price).toFixed(2);
        document.getElementById('orderAvailable').value = Number(btn.dataset.available).toFixed(2);
        document.getElementById('orderQty').value='';
        document.getElementById('orderTotal').textContent='0.00';
        document.getElementById('orderError').classList.add('d-none');
        orderModal.show();
      });
    });
  } catch(e){
    productsWrap.innerHTML = '<div class="col-12 text-danger">Error loading products.</div>';
  }
}

document.getElementById('orderQty').addEventListener('input',()=>{
  const q = parseFloat(document.getElementById('orderQty').value||0);
  const price = parseFloat(document.getElementById('orderPrice').value||0);
  document.getElementById('orderTotal').textContent = (q*price).toFixed(2);
});

document.getElementById('orderForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const err = document.getElementById('orderError');
  err.classList.add('d-none');
  const payload = {
    product_id: Number(document.getElementById('orderProductId').value),
    quantity_kg: parseFloat(document.getElementById('orderQty').value||0),
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
    bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide();
    loadProducts();
    alert('Order placed. Reference: '+j.order_id);
  }catch(ex){
    err.textContent = ex.message;
    err.classList.remove('d-none');
  }
});

loadProducts();
</script>
</body>
</html>