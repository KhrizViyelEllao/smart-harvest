<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Leaflet (needed for map / L object) -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
  crossorigin=""
/>
<script
  src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
  crossorigin=""
></script>

<script>
const BASE_URL = window.location.origin + "/Agrilink"; // define early & once
</script>

<style>
.fixed-crop-img {
  height: 110px;           /* was 180px */
  object-fit: cover;
  width: 100%;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

/* Compact cards */
.compact-cards .card {
  border-radius: 0.5rem;
}

.compact-cards .card-body {
  padding: 0.45rem 0.5rem;
}

.compact-cards h6 {
  font-size: 0.8rem;
  margin-bottom: 0.15rem;
}

.compact-cards .badge,
.compact-cards .btn {
  font-size: 0.65rem;
  padding: 0.2rem 0.4rem;
}
</style>

<div class="container py-4">
  <h2 class="mb-3 text-success"><i class="bi bi-flower2 me-2"></i>Crop Catalogue</h2>

  <!-- Search -->
  <input type="text" id="searchCrop" class="form-control mb-3" placeholder="Search crops...">



  <!-- On your farm -->
  <h5>On your farm</h5>
  <div id="onFarmCrops" class="row compact-cards row-cols-3 row-cols-md-6 g-2 mb-3"></div>

  <!-- Add to your farm -->
  <h5>Add to your farm</h5>
  <div id="notOnFarmCrops" class="row compact-cards row-cols-3 row-cols-md-6 g-2"></div>
</div>

<!-- 🟢 Add Crop Modal -->
<div class="modal fade" id="addCropModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Crop to Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Map Panel -->
          <div class="col-md-8">
            <div id="map" style="height: 400px;"></div>
          </div>
          <!-- Form Panel -->
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

<!-- 🔹 Add New Crop Button -->
<div class="text-end mb-3">
  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newCropModal">
    <i class="bi bi-plus-circle"></i> Add New Crop
  </button>
</div>

<!-- 🔹 Add New Crop Modal -->
<div class="modal fade" id="newCropModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Crop</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="newCropForm">
          <div class="mb-3">
            <label for="crop_name" class="form-label">Crop Name</label>
            <input type="text" id="crop_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <input type="text" id="category" class="form-control" placeholder="e.g. Vegetable, Fruit, Grain">
          </div>

          <div class="mb-3">
            <label for="duration" class="form-label">Duration (days)</label>
            <input type="number" id="duration" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" class="form-control" rows="3"></textarea>
          </div>

          <!-- Replace text path with a file input + preview -->
          <div class="mb-3">
            <label for="image_file" class="form-label">Image (optional)</label>
            <input type="file" id="image_file" class="form-control" accept="image/*">
            <div class="form-text">PNG/JPG up to 2MB.</div>
            <img id="image_preview" alt="Preview" style="display:none; max-height:120px; border-radius:6px; margin-top:8px;">
          </div>

          <button type="submit" class="btn btn-success w-100">Save Crop</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let allFields = [];
let allCrops  = [];
let map;

document.addEventListener("DOMContentLoaded", () => {
  initCrops();
  wireEvents();
  initImagePreview();
});

function wireEvents() {
  document.getElementById("planting_date")?.addEventListener("change", handlePlantingDateChange);

  // Add existing crop to field
  document.addEventListener("click", e => {
    if (e.target.matches(".btn-outline-success")) {
      const card = e.target.closest(".crop-card");
      if (!card) return;
      document.getElementById("selectedCropId").value       = card.dataset.id;
      document.getElementById("selectedCropDuration").value = card.dataset.duration || 0;
      document.getElementById("planting_date").value = "";
      document.getElementById("expected_harvest").value = "";
      new bootstrap.Modal(document.getElementById("addCropModal")).show();
      loadMap();
    }
  });

  // Add crop to field submit
  document.getElementById("addCropForm")?.addEventListener("submit", submitAddCropToField);

  // New crop submit (image upload)
  document.getElementById("newCropForm")?.addEventListener("submit", submitNewCrop);
}

async function initCrops() {
  try {
    const res  = await fetch(`${BASE_URL}/backend/api/crops/getCrops.php`);
    const data = await res.json();

    const onFarm    = data.onFarm    || [];
    const notOnFarm = data.notOnFarm || [];

    allCrops = [...onFarm, ...notOnFarm];

    renderCropCollection(onFarm, "onFarmCrops", true);
    renderCropCollection(notOnFarm, "notOnFarmCrops", false);

    document.getElementById("searchCrop").addEventListener("input", e => {
      const term = e.target.value.toLowerCase();
      document.querySelectorAll(".crop-card").forEach(card => {
        card.style.display = card.dataset.name.toLowerCase().includes(term) ? "" : "none";
      });
    });

    await loadFields();
  } catch (err) { 
    
    console.error("Failed to init crops:", err);
    document.getElementById("onFarmCrops").innerHTML = `<div class="col-12 text-danger small">Error loading crops.</div>`;
  }
}

function renderCropCollection(list, containerId, onFarm) {
  const wrap = document.getElementById(containerId);
  wrap.innerHTML = "";
  if (!list.length) {
    wrap.innerHTML = `<div class="col-12 text-muted small">No crops.</div>`;
    return;
  }
  list.forEach(crop => {
    const col = document.createElement("div");
    col.className = "col crop-card";
    col.dataset.name = crop.crop_name || "";
    col.dataset.id = crop.crop_id;
    col.dataset.duration = crop.duration || 0;

    const rawPath = crop.image_path || "";
    const imgUrl = rawPath
      ? (rawPath.startsWith("http") ? rawPath : `${BASE_URL}/${rawPath}`)
      : `${BASE_URL}/assets/images/placeholder.jpg`;

    col.innerHTML = `
      <div class="card shadow-sm h-100">
        <img src="${imgUrl}" class="card-img-top fixed-crop-img" alt="${escapeHtml(crop.crop_name)}"
             onerror="this.onerror=null;this.src='${BASE_URL}/assets/images/placeholder.jpg';">
        <div class="card-body text-center">
          <h6>${escapeHtml(crop.crop_name)}</h6>
          ${
            onFarm
              ? `<span class="badge bg-${getStatusColor(crop.status)}">${escapeHtml(crop.status||'Active')}</span>`
              : `<button type="button" class="btn btn-sm btn-outline-success mt-2">Add</button>`
          }
        </div>
      </div>`;
    wrap.appendChild(col);
  });
}

function getStatusColor(status) {
  switch (status) {
    case "Active": return "success";
    case "Planned": return "warning text-dark";
    case "Past": return "secondary";
    case "Needs plan": return "danger";
    default: return "light";
  }
}

async function loadFields() {
  try {
    const res = await fetch(`${BASE_URL}/backend/api/map/get_fields.php`);
    allFields = await res.json();
    populateFieldDropdown(allFields);
  } catch (e) {
    console.error("Fields load error:", e);
  }
}

function populateFieldDropdown(fields) {
  const sel = document.getElementById("field_id");
  if (!sel) return;
  if (!fields.length) {
    sel.innerHTML = `<option disabled selected>No fields available</option>`;
    return;
  }
  sel.innerHTML = fields.map(f =>
    `<option value="${f.field_id}">${escapeHtml(f.name || ('Field '+f.field_id))}</option>`
  ).join("");
}

function handlePlantingDateChange() {
  const planting = document.getElementById("planting_date").value;
  const duration = parseInt(document.getElementById("selectedCropDuration").value, 10) || 0;
  const out = document.getElementById("expected_harvest");
  if (!planting || !duration) { out.value = ""; return; }
  const d = new Date(planting);
  if (isNaN(d)) { out.value = ""; return; }
  d.setDate(d.getDate() + duration);
  out.value = d.toISOString().slice(0,10);
}

function loadMap() {
  if (!document.getElementById("map")) return;
  if (!map) {
    map = L.map("map").setView([13.75, 121.05], 12);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap contributors"
    }).addTo(map);
  }
  // Clear previous field layers (keep base tiles)
  map.eachLayer(l => { if (!(l instanceof L.TileLayer)) map.removeLayer(l); });

  allFields.forEach(f => {
    if (!f.geometry) return;
    try {
      const geo = JSON.parse(f.geometry);
      const layer = L.geoJSON(geo).addTo(map);
      layer.on("click", () => {
        document.getElementById("field_id").value = f.field_id;
      });
      map.fitBounds(layer.getBounds());
    } catch(_) {}
  });
}

async function submitAddCropToField(e) {
  e.preventDefault();
  const field_id        = document.getElementById("field_id").value;
  const crop_id         = document.getElementById("selectedCropId").value;
  const planting_date   = document.getElementById("planting_date").value;
  const expected_harvest= document.getElementById("expected_harvest").value;
  if (!field_id || !crop_id || !planting_date || !expected_harvest) {
    alert("Please complete all fields.");
    return;
  }
  try {
    const res = await fetch(`${BASE_URL}/backend/api/crops/addCrop.php`, {
      method:"POST",
      headers:{ "Content-Type":"application/json" },
      body: JSON.stringify({ field_id, crop_id, planting_date, expected_harvest })
    });
    const j = await res.json();
    if (!j.success) return alert(j.message||"Failed");
    bootstrap.Modal.getInstance(document.getElementById("addCropModal")).hide();
    initCrops();
  } catch(err){
    alert("Error saving: "+err.message);
  }
}

async function submitNewCrop(e) {
  e.preventDefault();
  const cropNameInput = document.getElementById("crop_name");
  const durationInput = document.getElementById("duration");
  const cropName = cropNameInput.value.trim();
  const duration = durationInput.value.trim();
  if (!cropName || !duration) {
    Swal.fire({icon:"warning",title:"Incomplete",text:"Crop name and duration required.",confirmButtonColor:"#198754"});
    return;
  }
  const fd = new FormData(e.target);
  try {
    const res = await fetch(`${BASE_URL}/backend/api/crops/addNewCrop.php`, { method:"POST", body:fd });
    const data = await res.json();
    if (data.success) {
      Swal.fire({icon:"success",title:"Added",text:data.message,confirmButtonColor:"#198754"})
        .then(()=>{
          bootstrap.Modal.getInstance(document.getElementById("newCropModal")).hide();
          e.target.reset();
          document.getElementById("image_preview").style.display="none";
          initCrops();
        });
    } else {
      Swal.fire({icon:"error",title:"Failed",text:data.message||"Error"});
    }
  } catch(err){
    Swal.fire({icon:"error",title:"Upload Error",text:err.message});
  }
}

function initImagePreview() {
  const inp = document.getElementById("image_file");
  const prev= document.getElementById("image_preview");
  if (!inp) return;
  inp.addEventListener("change", () => {
    const f = inp.files[0];
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
</script>