<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Leaflet (same tiles as map.php) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.geometryutil/0.9.3/leaflet.geometryutil.min.js"></script>

<script>
const BASE_URL = window.location.origin + "/Agrilink";
// Inline placeholder (SVG data URL) – no network request, no 404
const CROP_PLACEHOLDER = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="250"><rect width="100%" height="100%" fill="%23e9ecef"/><text x="50%" y="50%" text-anchor="middle" fill="%236c757d" font-size="20" font-family="Arial">No Image</text></svg>';

// Globals
let allFields = [];
let allCrops  = [];
let map;
let fieldLayers = {};   // field_id => L.GeoJSON
let highlightedId = null;
let currentSort = { field: 'name', order: 'asc' }; // Default sorting

document.addEventListener("DOMContentLoaded", () => {
  initCrops();
  wireEvents();
  initImagePreview();
});

function wireEvents() {
  // Ensure Leaflet map recalculates size once modal is shown and highlight current dropdown selection
  const addCropModalEl = document.getElementById('addCropModal');
  addCropModalEl?.addEventListener('shown.bs.modal', () => {
    loadMap(true);
    const sel = document.getElementById('field_id');
    if (sel && sel.value) highlightField(parseInt(sel.value,10), true);
  });

  document.getElementById("planting_date")?.addEventListener("change", handlePlantingDateChange);

  // Highlight map when dropdown changes
  document.getElementById('field_id')?.addEventListener('change', (e) => {
    const id = parseInt(e.target.value, 10);
    if (!isNaN(id)) highlightField(id, true);
  });

  // Open Add-to-field modal (explicit Add button)
  document.addEventListener("click", e => {
    const addBtn = e.target.closest('[data-action="add-crop"]');
    if (!addBtn) return;
    const card = addBtn.closest(".crop-card");
    if (!card) return;
    document.getElementById("selectedCropId").value       = card.dataset.id;
    document.getElementById("selectedCropDuration").value = card.dataset.duration || 0;
    document.getElementById("planting_date").value = "";
    document.getElementById("expected_harvest").value = "";
    const m = new bootstrap.Modal(document.getElementById("addCropModal"));
    m.show();
    loadMap(true);
  });

  // Delete via explicit Delete button only
  document.addEventListener('click', onDeleteClick);

  document.getElementById("addCropForm")?.addEventListener("submit", submitAddCropToField);

  const formNew = document.getElementById("newCropForm");
  if (formNew) {
    formNew.addEventListener("submit", submitNewCrop, { once:false });
  }

  // Click on on-farm cards to delete
  document.getElementById('onFarmCrops')?.addEventListener('click', onOnFarmCardClick);

  // Sort functionality
  document.getElementById('sortField')?.addEventListener('change', applySorting);
  document.getElementById('sortOrder')?.addEventListener('change', applySorting);
}

async function initCrops() {
  try {
    const res  = await fetch(`${BASE_URL}/backend/api/crops/getCrops.php`, { cache: 'no-store' });
    const data = await res.json();

    const onFarm    = data.onFarm    || [];
    const notOnFarm = data.notOnFarm || [];

    allCrops = [...onFarm, ...notOnFarm];

    renderCropCollection(onFarm, "onFarmCrops", true);
    renderCropCollection(notOnFarm, "notOnFarmCrops", false);

    const searchEl = document.getElementById("searchCrop");
    searchEl?.addEventListener("input", e => {
      const term = e.target.value.toLowerCase();
      document.querySelectorAll(".crop-card").forEach(card => {
        const nm = (card.dataset.name || "").toLowerCase();
        card.style.display = nm.includes(term) ? "" : "none";
      });
    });

    await loadFields();
  } catch (err) {
    console.error("Failed to init crops:", err);
    const cont = document.getElementById("onFarmCrops");
    if (cont) cont.innerHTML = `<div class="col-12 text-danger small">Error loading crops.</div>`;
  }
}

function applySorting() {
  const sortField = document.getElementById('sortField').value;
  const sortOrder = document.getElementById('sortOrder').value;
  
  currentSort = { field: sortField, order: sortOrder };
  
  // Re-render both collections with new sorting
  const onFarm = allCrops.filter(crop => crop.field_crop_id);
  const notOnFarm = allCrops.filter(crop => !crop.field_crop_id);
  
  renderCropCollection(onFarm, "onFarmCrops", true);
  renderCropCollection(notOnFarm, "notOnFarmCrops", false);
}

function renderCropCollection(list, containerId, onFarm) {
  const wrap = document.getElementById(containerId);
  if (!wrap) return;
  wrap.innerHTML = "";
  if (!list.length) {
    wrap.innerHTML = `<div class="col-12 text-muted small">No crops.</div>`;
    return;
  }
  
  // Apply sorting to the list
  const sortedList = sortCropList(list);
  
  sortedList.forEach(crop => {
    const col = document.createElement("div");
    col.className = "col crop-card";
    col.dataset.id = crop.crop_id;
    col.dataset.name = crop.crop_name || "";
    col.dataset.duration = crop.duration || 0;
    col.dataset.description = crop.description || "";
    col.dataset.category = crop.category || "";
    col.dataset.created_at = crop.created_at || "";
    if (onFarm) col.setAttribute('title','Click to delete');

    const imgUrl = `${BASE_URL}/backend/api/crops/image.php?id=${crop.crop_id}`;

    col.innerHTML = `
      <div class="card shadow-sm h-100 position-relative">
        <div class="image-container">
          <img src="${imgUrl}" class="card-img-top fixed-crop-img" alt="${escapeHtml(crop.crop_name)}"
               onerror="this.onerror=null;this.src=CROP_PLACEHOLDER;">
          <div class="hover-overlay">
            <div class="eye-icon">
              <i class="fas fa-eye"></i>
            </div>
          </div>
        </div>
        <div class="card-body text-center">
          <h6>${escapeHtml(crop.crop_name)}</h6>
          <div class="mt-2 d-flex justify-content-center gap-2">
            ${
              onFarm
                ? `<span class="badge bg-success">Active</span>`
                : `
                  <button type="button" class="btn btn-sm btn-outline-success" data-action="add-crop">Add</button>
                  <button type="button" class="btn btn-sm btn-outline-danger"  data-action="delete-crop">Delete</button>
                `
            }
          </div>
        </div>
      </div>`;
    wrap.appendChild(col);
  });
  
  // Add hover event listeners for the eye icon
  addHoverEffects();
}

function sortCropList(list) {
  return list.sort((a, b) => {
    let aValue, bValue;
    
    switch(currentSort.field) {
      case 'name':
        aValue = (a.crop_name || '').toLowerCase();
        bValue = (b.crop_name || '').toLowerCase();
        break;
      case 'category':
        aValue = (a.category || '').toLowerCase();
        bValue = (b.category || '').toLowerCase();
        break;
      case 'duration':
        aValue = parseInt(a.duration) || 0;
        bValue = parseInt(b.duration) || 0;
        break;
      default:
        aValue = (a.crop_name || '').toLowerCase();
        bValue = (b.crop_name || '').toLowerCase();
    }
    
    if (aValue < bValue) return currentSort.order === 'asc' ? -1 : 1;
    if (aValue > bValue) return currentSort.order === 'asc' ? 1 : -1;
    return 0;
  });
}

function addHoverEffects() {
  const cards = document.querySelectorAll('.crop-card');
  
  cards.forEach(card => {
    const imageContainer = card.querySelector('.image-container');
    const overlay = card.querySelector('.hover-overlay');
    
    if (!imageContainer || !overlay) return;
    
    // Show overlay on hover
    imageContainer.addEventListener('mouseenter', () => {
      overlay.style.opacity = '1';
    });
    
    // Hide overlay when mouse leaves
    imageContainer.addEventListener('mouseleave', () => {
      overlay.style.opacity = '0';
    });
    
    // Click on eye icon to show product details
    const eyeIcon = overlay.querySelector('.eye-icon');
    if (eyeIcon) {
      eyeIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        showProductDetails(card);
      });
    }
  });
}

function showProductDetails(card) {
  const cropId = card.dataset.id;
  const cropName = card.dataset.name;
  const description = card.dataset.description;
  const category = card.dataset.category;
  const duration = card.dataset.duration;
  
  // Create modal content
  const modalContent = `
    <div class="row">
      <div class="col-md-5">
        <img src="${BASE_URL}/backend/api/crops/image.php?id=${cropId}" 
             class="img-fluid rounded" 
             alt="${escapeHtml(cropName)}"
             onerror="this.onerror=null;this.src=CROP_PLACEHOLDER;">
      </div>
      <div class="col-md-7">
        <h4 class="text-success mb-3">${escapeHtml(cropName)}</h4>
        ${category ? `<p class="mb-2"><strong>Category:</strong> <span class="badge bg-info">${escapeHtml(category)}</span></p>` : ''}
        ${duration ? `<p class="mb-2"><strong>Duration:</strong> ${duration} days</p>` : ''}
        <div class="mt-3">
          <strong>Description:</strong>
          <div class="mt-2 p-3 bg-light rounded">
            ${description ? escapeHtml(description) : '<p class="text-muted mb-0">No description available.</p>'}
          </div>
        </div>
      </div>
    </div>
  `;
  
  // Use SweetAlert2 for the modal
  Swal.fire({
    title: 'Product Details',
    html: modalContent,
    width: '700px',
    showCloseButton: true,
    showConfirmButton: false,
    customClass: {
      popup: 'product-details-modal'
    }
  });
}

async function onDeleteClick(e) {
  const btn = e.target.closest('[data-action="delete-crop"]');
  if (!btn) return;

  const card = btn.closest('.crop-card');
  const cropId = Number(card?.dataset.id);
  const name = (card?.dataset.name) || 'this crop';
  if (!cropId) { showCropError('Missing crop id'); return; }

  const ok = await Swal.fire({
    icon: 'warning',
    title: 'Delete crop?',
    html: `This will remove <strong>${escapeHtml(name)}</strong> and its assignments.`,
    showCancelButton: true,
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc3545',
    focusCancel: true
  }).then(r => r.isConfirmed);
  if (!ok) return;

  try {
    // Send id via query to avoid body parsing mismatches
    const res = await fetch(`${BASE_URL}/backend/api/crops/deleteCrop.php?crop_id=${encodeURIComponent(cropId)}`, { method: 'POST' });
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Delete failed');

    document.querySelectorAll(`.crop-card[data-id="${cropId}"]`).forEach(el => el.remove());
    allCrops = allCrops.filter(c => String(c.crop_id) !== String(cropId));
    Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
  } catch (err) {
    Swal.fire({icon:'error', title:'Failed', text: err.message || 'Error deleting crop'});
  }
}

async function onOnFarmCardClick(e) {
  const card = e.target.closest('.crop-card');
  if (!card) return;
  // Ignore if user somehow clicks a button inside (safety)
  if (e.target.closest('button,[data-action]')) return;

  const cropId = Number(card.dataset.id);
  const name = (card.dataset.name) || 'this crop';
  if (!cropId) { showCropError('Missing crop id'); return; }

  const ok = await Swal.fire({
    icon: 'warning',
    title: 'Delete crop?',
    html: `This will remove <strong>${escapeHtml(name)}</strong> and its assignments.`,
    showCancelButton: true,
    confirmButtonText: 'Delete',
    confirmButtonColor: '#dc3545',
    focusCancel: true
  }).then(r => r.isConfirmed);
  if (!ok) return;

  try {
    const res = await fetch(`${BASE_URL}/backend/api/crops/deleteCrop.php?crop_id=${encodeURIComponent(cropId)}`, { method: 'POST' });
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Delete failed');

    document.querySelectorAll(`.crop-card[data-id="${cropId}"]`).forEach(el => el.remove());
    allCrops = allCrops.filter(c => String(c.crop_id) !== String(cropId));
    Swal.fire({icon:'success', title:'Deleted', timer:1200, showConfirmButton:false});
  } catch (err) {
    Swal.fire({icon:'error', title:'Failed', text: err.message || 'Error deleting crop'});
  }
}

function handlePlantingDateChange() {
  const planting = document.getElementById("planting_date")?.value;
  const duration = parseInt(document.getElementById("selectedCropDuration")?.value, 10) || 0;
  const out = document.getElementById("expected_harvest");
  if (!out) return;
  if (!planting || !duration) { out.value = ""; return; }
  const d = new Date(planting);
  if (isNaN(d)) { out.value = ""; return; }
  d.setDate(d.getDate() + duration);
  out.value = d.toISOString().slice(0,10);
}

// Esri tiles + highlight support
function baseTileEsriImagery() {
  return L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles © Esri'
  });
}
function normalStyle()    { return { color: '#28a745', weight: 2, fillOpacity: 0.40 }; }
function highlightStyle() { return { color: '#ffc107', weight: 4, fillOpacity: 0.45 }; }

function highlightField(fieldId, fit = false) {
  if (highlightedId != null && fieldLayers[highlightedId]) {
    fieldLayers[highlightedId].eachLayer(l => l.setStyle(normalStyle()));
  }
  const grp = fieldLayers[fieldId];
  if (!grp) return;
  grp.eachLayer(l => { l.setStyle(highlightStyle()); l.bringToFront(); });
  highlightedId = fieldId;
  if (fit) {
    try { map.fitBounds(grp.getBounds(), { padding: [12,12] }); } catch {}
  }
}

function loadMap(inModal = false) {
  const defaultLat = 13.9449;   // Balayan, Batangas
  const defaultLng = 120.7517;
  const mapEl = document.getElementById("map");
  if (!mapEl) return;

  if (!map) {
    map = L.map("map");
    baseTileEsriImagery().addTo(map);
    map.setView([defaultLat, defaultLng], 15);
  }

  // Remove non-tile layers
  map.eachLayer(l => { if (!(l instanceof L.TileLayer)) map.removeLayer(l); });
  fieldLayers = {};

  // Draw fields and keep references
  const all = L.featureGroup();
  (allFields || []).forEach(f => {
    if (!f.geometry) return;

    let geo = f.geometry;
    if (typeof geo === 'string') { try { geo = JSON.parse(geo); } catch { return; } }

    const group = L.geoJSON(geo, { style: normalStyle });
    fieldLayers[Number(f.field_id)] = group;

    group.eachLayer(layer => {
      layer.on("click", () => {
        const sel = document.getElementById("field_id");
        if (sel) {
          sel.value = String(f.field_id);
          sel.dispatchEvent(new Event('change'));
        } else {
          highlightField(Number(f.field_id), true);
        }
      });
    });

    group.addTo(map);
    all.addLayer(group);
  });

  if (all.getLayers().length) {
    map.fitBounds(all.getBounds(), { padding: [12, 12] });
  } else {
    map.setView([defaultLat, defaultLng], 15);
  }

  if (inModal) setTimeout(() => map.invalidateSize(), 150);
}

function showCropError(message = 'Please complete all fields.') {
  const el = document.getElementById('cropErrorMsg');
  if (el) el.textContent = message;
  const modalEl = document.getElementById('cropErrorModal');
  if (modalEl) new bootstrap.Modal(modalEl).show();
}
function showCropSuccess(message = 'Saved successfully!') {
  const el = document.getElementById('cropSuccessMsg');
  if (el) el.textContent = message;
  const modalEl = document.getElementById('cropSuccessModal');
  if (modalEl) new bootstrap.Modal(modalEl).show();
}

async function submitAddCropToField(e) {
  e.preventDefault();
  const field_id         = document.getElementById("field_id")?.value;
  const crop_id          = document.getElementById("selectedCropId")?.value;
  const planting_date    = document.getElementById("planting_date")?.value;
  const expected_harvest = document.getElementById("expected_harvest")?.value;

  if (!field_id || !crop_id || !planting_date || !expected_harvest) {
    showCropError("Please complete all fields.");
    return;
  }

  try {
    const res = await fetch(`${BASE_URL}/backend/api/crops/addCrop.php`, {
      method:"POST",
      headers:{ "Content-Type":"application/json" },
      body: JSON.stringify({ field_id, crop_id, planting_date, expected_harvest })
    });
    const j = await res.json();
    if (!j.success) {
      showCropError(j.message || "Failed to save.");
      return;
    }

    // Add to on-farm list WITHOUT removing from add list
    addCropToOnFarmList(crop_id);

    // Close the add modal then show success confirmation
    const m = bootstrap.Modal.getInstance(document.getElementById("addCropModal"));
    m && m.hide();
    showCropSuccess(j.message || "Crop assigned to field.");

  } catch(err){
    showCropError("Error saving: " + (err.message || "Unexpected error"));
  }
}

function addCropToOnFarmList(cropId){
  if (document.querySelector(`#onFarmCrops .crop-card[data-id="${cropId}"]`)) return;
  const crop = allCrops.find(c => String(c.crop_id) === String(cropId));
  if (!crop) return;

  const wrap = document.getElementById("onFarmCrops");
  if (!wrap) return;

  const col = document.createElement("div");
  col.className = "col crop-card";
  col.dataset.name = crop.crop_name || "";
  col.dataset.id = crop.crop_id;
  col.dataset.duration = crop.duration || 0;
  col.dataset.description = crop.description || "";
  col.dataset.category = crop.category || "";
  col.dataset.created_at = crop.created_at || "";

  const imgUrl = `${BASE_URL}/backend/api/crops/image.php?id=${crop.crop_id}`;

  col.innerHTML = `
    <div class="card shadow-sm h-100 position-relative">
      <div class="image-container">
        <img src="${imgUrl}" class="card-img-top fixed-crop-img" alt="${escapeHtml(crop.crop_name)}"
             onerror="this.onerror=null;this.src=CROP_PLACEHOLDER;">
        <div class="hover-overlay">
          <div class="eye-icon">
            <i class="fas fa-eye"></i>
          </div>
        </div>
      </div>
      <div class="card-body text-center">
        <h6>${escapeHtml(crop.crop_name)}</h6>
        <span class="badge bg-success">Active</span>
      </div>
    </div>`;
  wrap.appendChild(col);
  
  // Re-add hover effects for the new card
  addHoverEffects();
}

async function submitNewCrop(e) {
  e.preventDefault();
  const form = e.target;
  const btn  = form.querySelector('button[type="submit"]');
  const cropNameInput = document.getElementById("crop_name");
  const durationInput = document.getElementById("duration");
  const cropName = (cropNameInput?.value || "").trim();
  const duration = (durationInput?.value || "").trim();
  if (!cropName || !duration) {
    Swal.fire({icon:"warning",title:"Incomplete",text:"Crop name and duration required.",confirmButtonColor:"#198754"});
    return;
  }
  const fd = new FormData(form);
  try {
    if (btn) { btn.disabled = true; btn.dataset.oldText = btn.innerHTML; btn.innerHTML = 'Saving...'; }
    const res = await fetch(`${BASE_URL}/backend/api/crops/addNewCrop.php`, { method:"POST", body:fd, cache:'no-store' });
    const data = await res.json();
    if (data.success) {
      await Swal.fire({icon:"success",title:"Added",text:data.message||"New crop added!",confirmButtonColor:"#198754"});
      const nm = bootstrap.Modal.getInstance(document.getElementById("newCropModal"));
      nm && nm.hide();
      form.reset();
      const prev = document.getElementById("image_preview");
      if (prev) { prev.src = ""; prev.style.display = "none"; }
      await initCrops();
    } else {
      Swal.fire({icon:"error",title:"Failed",text:data.message||"Error adding crop"});
    }
  } catch(err){
    Swal.fire({icon:"error",title:"Upload Error",text:err.message||"Network error"});
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.oldText || 'Save Crop'; }
  }
}

function initImagePreview() {
  const inp = document.getElementById("image_file");
  const prev= document.getElementById("image_preview");
  if (!inp || !prev) return;
  inp.addEventListener("change", () => {
    const f = inp.files?.[0];
    if (f) {
      prev.src = URL.createObjectURL(f);
      prev.style.display = "block";
    } else {
      prev.src = "";
      prev.style.display = "none";
    }
  });
}

function escapeHtml(str){
  return (str||"").replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// Loads fields and populates the dropdown; safe fallback if endpoint differs.
async function loadFields() {
  const sel = document.getElementById("field_id");
  if (!sel) return;
  sel.innerHTML = `<option value="">Loading fields...</option>`;

  let json = null;
  try {
    // Try a common endpoint name; adjust to your actual route if different
    const res = await fetch(`${BASE_URL}/backend/api/fields/list.php`, { cache: 'no-store' });
    if (res.ok) json = await res.json();
  } catch {}

  // Fallback to alternative path, or empty list
  const fields = Array.isArray(json?.data) ? json.data
                : Array.isArray(json) ? json
                : [];

  allFields = fields;

  if (!fields.length) {
    sel.innerHTML = `<option value="">No fields found</option>`;
    return;
  }

  sel.innerHTML = `<option value="">-- Select field --</option>`;
  fields.forEach(f => {
    const opt = document.createElement('option');
    opt.value = f.field_id;
    opt.textContent = f.name || f.field_name || `Field #${f.field_id}`;
    sel.appendChild(opt);
  });
}
</script>

<style>
.fixed-crop-img {
  height: 110px;
  object-fit: cover;
  width: 100%;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}
.compact-cards .card { border-radius: 0.5rem; }
.compact-cards .card-body { padding: 0.45rem 0.5rem; }
.compact-cards h6 { font-size: 0.8rem; margin-bottom: 0.15rem; }
.compact-cards .badge, .compact-cards .btn { font-size: 0.65rem; padding: 0.2rem 0.4rem; }
#onFarmCrops .crop-card { cursor: pointer; }
#onFarmCrops .crop-card .card:hover { box-shadow: 0 0.5rem 1rem rgba(220,53,69,.15); }

/* Hover effect styles */
.image-container {
  position: relative;
  overflow: hidden;
}

.hover-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

.eye-icon {
  color: white;
  font-size: 2rem;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.eye-icon:hover {
  transform: scale(1.1);
}

/* Product details modal styling */
.product-details-modal .swal2-popup {
  padding: 1.5rem;
}

.product-details-modal .swal2-close {
  font-size: 1.5rem;
}

/* Sorting controls */
.sorting-controls {
  background: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 1rem;
}

.sorting-controls label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}
</style>

<div class="container py-4">
  <h2 class="mb-3 text-success"><i class="bi bi-flower2 me-2"></i>Crop Catalogue</h2>

  <!-- Sorting Controls -->
  <div class="sorting-controls">
    <div class="row g-3">
      <div class="col-md-6">
        <label for="sortField" class="form-label">Sort by:</label>
        <select id="sortField" class="form-select">
          <option value="name">Crop Name</option>
          <option value="category">Category</option>
          <option value="duration">Duration</option>
        </select>
      </div>
      <div class="col-md-6">
        <label for="sortOrder" class="form-label">Order:</label>
        <select id="sortOrder" class="form-select">
          <option value="asc">Ascending (A-Z)</option>
          <option value="desc">Descending (Z-A)</option>
        </select>
      </div>
    </div>
  </div>

  <input type="text" id="searchCrop" class="form-control mb-3" placeholder="Search crops...">

  <h5>On your farm</h5>
  <div id="onFarmCrops" class="row compact-cards row-cols-3 row-cols-md-6 g-2 mb-3"></div>

  <h5>Add to your farm</h5>
  <div id="notOnFarmCrops" class="row compact-cards row-cols-3 row-cols-md-6 g-2"></div>
</div>

<!-- Rest of the modals remain exactly the same -->
<!-- Add Crop Modal -->
<div class="modal fade" id="addCropModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Crop to Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-8">
            <div id="map" style="height: 400px;"></div>
          </div>
          <div class="col-md-4">
            <form id="addCropForm">
              <input type="hidden" id="selectedCropId">
              <input type="hidden" id="selectedCropDuration">

              <div class="mb-3">
                <label for="field_id" class="form-label">Select Field</label>
                <select id="field_id" class="form-select"></select>
              </div>

              <div class="mb-3">
                <label for="planting_date" class="form-label">Planting Date</label>
                <input type="date" id="planting_date" class="form-control">
              </div>

              <div class="mb-3">
                <label for="expected_harvest" class="form-label">Expected Harvest</label>
                <input type="date" id="expected_harvest" class="form-control" readonly>
              </div>

              <button type="submit" class="btn btn-success w-100">Save</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add New Crop Button -->
<div class="text-end mb-3">
  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newCropModal">
    <i class="bi bi-plus-circle"></i> Add New Crop
  </button>
</div>

<!-- Add New Crop Modal -->
<div class="modal fade" id="newCropModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Crop</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="newCropForm" method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="crop_name" class="form-label">Crop Name</label>
            <input type="text" name="crop_name" id="crop_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select name="category" id="category" class="form-select" required>
              <option value="">-- Select Category --</option>
              <option value="Vegetable">Vegetable</option>
              <option value="Fruit">Fruit</option>
              <option value="Grain">Grain</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="duration" class="form-label">Duration (days)</label>
            <input type="number" name="duration" id="duration" class="form-control" min="1" required>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label for="image_file" class="form-label">Image (optional)</label>
            <input type="file" name="image_file" id="image_file" accept="image/*">
            <div class="form-text">PNG/JPG up to 10MB.</div>
            <img id="image_preview" alt="Preview" style="display:none; max-height:120px; border-radius:6px; margin-top:8px;">
          </div>

          <button type="submit" class="btn btn-success w-100">Save Crop</button>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Success Modal -->
<div class="modal fade" id="cropSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Saved</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
        <div id="cropSuccessMsg">Saved successfully!</div>
      </div>
    </div>
  </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="cropErrorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Action Required</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-exclamation-circle fa-3x text-danger mb-3"></i>
        <div id="cropErrorMsg">Please complete all fields.</div>
      </div>
    </div>
  </div>
</div>