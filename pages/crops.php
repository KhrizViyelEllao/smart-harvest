<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
  <h2 class="mb-3">Crop Catalogue</h2>

  <!-- Search -->
  <input type="text" id="searchCrop" class="form-control mb-3" placeholder="Search crops...">

  <!-- Farm Stats -->
  <div class="mb-3">
    <span class="badge bg-success">Active</span>
    <span class="badge bg-warning text-dark">Planned</span>
    <span class="badge bg-secondary">Past</span>
    <span class="badge bg-danger">Needs plan</span>
  </div>

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

          <div class="mb-3">
            <label for="image_path" class="form-label">Image Path (optional)</label>
            <input type="text" id="image_path" class="form-control" placeholder="assets\images">
          </div>

          <button type="submit" class="btn btn-success w-100">Save Crop</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let allFields = [];
let allCrops = [];
let map;

// 🔹 Initialization
document.addEventListener("DOMContentLoaded", cropManagementInit);

async function cropManagementInit() {
  try {
    const cropRes = await fetch("backend/api/crops/getCrops.php");
    const cropData = await cropRes.json();

    allCrops = [...(cropData.onFarm || []), ...(cropData.notOnFarm || [])];

    renderCrops(cropData.onFarm, "onFarmCrops", true);
    renderCrops(allCrops, "notOnFarmCrops", false);

    await loadFields();

    // Search feature
    document.getElementById("searchCrop").addEventListener("input", (e) => {
      const term = e.target.value.toLowerCase();
      document.querySelectorAll(".crop-card").forEach(card => {
        const name = card.dataset.name.toLowerCase();
        card.style.display = name.includes(term) ? "" : "none";
      });
    });

    // 🟢 Planting Date -> Expected Harvest auto calculation
    document.getElementById("planting_date").addEventListener("change", handlePlantingDateChange);

  } catch (err) {
    console.error("Error loading data:", err);
  }
}

// 🔹 Render Crops
function renderCrops(list, containerId, onFarm) {
  const container = document.getElementById(containerId);
  container.innerHTML = "";

  if (!list || list.length === 0) {
    container.innerHTML = "<p class='text-muted'>No crops found.</p>";
    return;
  }

  list.forEach(crop => {
    const col = document.createElement("div");
    col.className = "col crop-card";
    col.dataset.name = crop.crop_name;
    col.dataset.id = crop.crop_id;
    col.dataset.duration = crop.duration || 0;

    col.innerHTML = `
      <div class="card shadow-sm h-100">
        <img src="${crop.image_path || 'assets/images/placeholder.jpg'}"
          class="card-img-top fixed-crop-img"
          alt="${crop.crop_name}">
        <div class="card-body text-center">
          <h6>${crop.crop_name}</h6>
          ${
            onFarm
              ? `<span class="badge bg-${getStatusColor(crop.status)}">${crop.status}</span>`
              : `<button class="btn btn-sm btn-outline-success mt-2">Add</button>`
          }
        </div>
      </div>`;

    container.appendChild(col);
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

// 🔹 Fetch and populate fields
async function loadFields() {
  try {
    const BASE_URL = window.location.origin + "/Agrilink";
    const res = await fetch(`${BASE_URL}/backend/api/map/get_fields.php`);
    allFields = await res.json();
    populateFieldDropdown(allFields);
  } catch (err) {
    console.error("Error loading fields:", err);
  }
}

function populateFieldDropdown(fields) {
  const select = document.getElementById("field_id");
  if (!select) return;

  if (!fields || fields.length === 0) {
    select.innerHTML = `<option disabled selected>No fields available</option>`;
    return;
  }

  select.innerHTML = fields.map(f => 
    `<option value="${f.field_id}">
        ${f.name || '(Unnamed Field)'}
     </option>`
  ).join("");
}

// 🔹 Add Crop button handler
document.addEventListener("click", (e) => {
  if (e.target.matches(".btn-outline-success")) {
    const card = e.target.closest(".crop-card");
    const cropId = card.dataset.id;

    const selectedCrop = allCrops.find(c => String(c.crop_id) === String(cropId));
    const duration = selectedCrop?.duration ?? card.dataset.duration ?? 0;

    document.getElementById("selectedCropId").value = cropId;
    document.getElementById("selectedCropDuration").value = duration;

    const plantingInput = document.getElementById("planting_date");
    const harvestInput = document.getElementById("expected_harvest");
    if (plantingInput) plantingInput.value = "";
    if (harvestInput) harvestInput.value = "";

    const modal = new bootstrap.Modal(document.getElementById("addCropModal"));
    modal.show();

    loadMap();
  }
});

// 🔹 Auto-calculate Expected Harvest Date
function handlePlantingDateChange() {
  const plantingInput = document.getElementById("planting_date");
  const harvestInput = document.getElementById("expected_harvest");
  const durationValue = parseInt(document.getElementById("selectedCropDuration").value, 10) || 0;

  if (!plantingInput || !harvestInput) return;

  const plantingDate = plantingInput.value;
  if (!plantingDate || durationValue <= 0) {
    harvestInput.value = "";
    return;
  }

  const plant = new Date(plantingDate);
  if (Number.isNaN(plant.getTime())) {
    harvestInput.value = "";
    return;
  }

  plant.setDate(plant.getDate() + durationValue);

  const yyyy = plant.getFullYear();
  const mm = String(plant.getMonth() + 1).padStart(2, "0");
  const dd = String(plant.getDate()).padStart(2, "0");

  harvestInput.value = `${yyyy}-${mm}-${dd}`;
}

// 🔹 Map loader
function loadMap() {
  if (map) {
    map.eachLayer(layer => {
      if (layer instanceof L.TileLayer) return;
      map.removeLayer(layer);
    });
  } else {
    map = L.map("map").setView([13.75, 121.05], 12);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
  }

  allFields.forEach(f => {
    if (f.geometry) {
      const coords = JSON.parse(f.geometry);
      const layer = L.geoJSON(coords).addTo(map);
      layer.on("click", () => {
        document.getElementById("field_id").value = f.field_id;
      });
    }
  });
}

// 🟢 Handle Add Crop Form Submission
document.getElementById("addCropForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const field_id = document.getElementById("field_id").value;
  const crop_id = document.getElementById("selectedCropId").value;
  const planting_date = document.getElementById("planting_date").value;
  const expected_harvest = document.getElementById("expected_harvest").value;

  if (!field_id || !crop_id || !planting_date || !expected_harvest) {
    alert("⚠️ Please complete all fields before saving.");
    return;
  }

  try {
    const BASE_URL = window.location.origin + "/Agrilink";
    const res = await fetch(`${BASE_URL}/backend/api/crops/addCrop.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        field_id,
        crop_id,
        planting_date,
        expected_harvest
      }),
    });

    const data = await res.json();

    if (data.success) {
      alert("Crop added successfully!");
      const modal = bootstrap.Modal.getInstance(document.getElementById("addCropModal"));
      modal.hide();

      // Optionally reload crop list
      cropManagementInit();
    } else {
      alert("Failed to add crop: " + (data.message || "Unknown error"));
    }
  } catch (err) {
    console.error("Error saving crop:", err);
    alert("Error saving crop. Check console for details.");
  }
});

document.addEventListener("DOMContentLoaded", async () => {
  const BASE_URL = window.location.origin + "/Agrilink";

  try {
    const res = await fetch(`${BASE_URL}/backend/api/crops/getCrops.php`);
    const data = await res.json();

    const onFarmCrops = data.onFarm || [];
    const container = document.getElementById("onFarmCrops");
    container.innerHTML = "";

    if (onFarmCrops.length === 0) {
      container.innerHTML = `
        <div class="col-12 text-center text-muted">
          No crops currently on your farm.
        </div>`;
      return;
    }

    onFarmCrops.forEach(crop => {
      const card = `
        <div class="col">
          <div class="card h-100 shadow-sm border-0">
            <img src="${BASE_URL}/${crop.image_path}"
                 class="card-img-top fixed-crop-img"
                 alt="${crop.crop_name}">
            <div class="card-body">
              <h6 class="card-title mb-1">${crop.crop_name}</h6>
              <p class="text-muted small mb-1">Field ID: ${crop.field_id}</p>
              <p class="text-muted small">Duration: ${crop.duration ? crop.duration + ' days' : 'N/A'}</p>
            </div>
          </div>
        </div>
      `;
      container.insertAdjacentHTML("beforeend", card);
    });
  } catch (err) {
    console.error("Error loading crops:", err);
  }
});

// 🟢 Handle Add New Crop Form Submission
document.getElementById("newCropForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const crop_name = document.getElementById("crop_name").value.trim();
  const category = document.getElementById("category").value.trim();
  const duration = document.getElementById("duration").value.trim();
  const description = document.getElementById("description").value.trim();
  const image_path = document.getElementById("image_path").value.trim();

  if (!crop_name || !duration) {
    Swal.fire({
      icon: "warning",
      title: "Incomplete Form",
      text: "Please enter at least the crop name and duration.",
      confirmButtonColor: "#198754"
    });
    return;
  }

  const BASE_URL = window.location.origin + "/Agrilink";

  try {
    const res = await fetch(`${BASE_URL}/backend/api/crops/addNewCrop.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        crop_name,
        description,
        category,
        duration,
        image_path
      }),
    });

    const data = await res.json();

    if (data.success) {
      Swal.fire({
        icon: "success",
        title: "Crop Added!",
        text: data.message,
        confirmButtonColor: "#198754"
      }).then(() => {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById("newCropModal"));
        modal.hide();

        // Reload crop list
        cropManagementInit();
      });
    } else {
      Swal.fire({
        icon: "error",
        title: "Failed to Add Crop",
        text: data.message || "Something went wrong.",
        confirmButtonColor: "#dc3545"
      });
    }
  } catch (err) {
    console.error("Error adding new crop:", err);
    Swal.fire({
      icon: "error",
      title: "Unexpected Error",
      text: "Please check console for details.",
      confirmButtonColor: "#dc3545"
    });
  }
});
</script>