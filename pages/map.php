<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<!-- Leaflet Draw CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>




<style>
body {
  margin: 0;
  padding: 0;
}
#map {
  height: 100vh;
  width: 100%;
}

.field-label {
  background: rgba(255, 255, 255, 0.7);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  color: #1b5e20;
  text-align: center;
  white-space: nowrap;
  pointer-events: none;
}
</style>

<!-- MAP CONTAINER -->
<div id="map"></div>

<!-- SAVE FIELD MODAL -->
<div class="modal fade" id="fieldModal" tabindex="-1" aria-labelledby="fieldModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="fieldModalLabel">Save Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="fieldForm">
          <div class="mb-3">
            <label for="field_name" class="form-label">Field Name</label>
            <input type="text" class="form-control" id="field_name" placeholder="Enter field name">
          </div>

          <div class="mb-3">
            <label for="field_area" class="form-label">Total Area (sq.m)</label>
            <input type="text" class="form-control" id="field_area" readonly>
          </div>

          <div class="mb-3">
            <label for="field_perimeter" class="form-label">Perimeter (m)</label>
            <input type="text" class="form-control" id="field_perimeter" readonly>
          </div>

          <div class="mb-3">
            <label for="field_type" class="form-label">Field Type</label>
            <select class="form-select" id="field_type">
              <option value="Organic">Organic</option>
              <option value="Non-organic">Non-organic</option>
              <option value="Transitioning">Transitioning</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="field_notes" class="form-label">Notes (optional)</label>
            <textarea class="form-control" id="field_notes" rows="3" placeholder="Enter notes..."></textarea>
          </div>

          <input type="hidden" id="field_geometry">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="saveField()">Save Field</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">Field Saved</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
        <div id="successMsg">Field saved successfully!</div>
      </div>
    </div>
  </div>
</div>

<!-- LEAFLET + BOOTSTRAP SCRIPTS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.geometryutil/0.9.3/leaflet.geometryutil.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function mapInit() {
  if (window.myMap) {
    window.myMap.remove();
  }

  window.myMap = L.map('map');
  var map = window.myMap;

  // ✅ Esri satellite layer
  L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    attribution: 'Tiles © Esri'
  }).addTo(map);

  // ✅ Initialize FeatureGroup once
  window.drawnItems = new L.FeatureGroup();
  map.addLayer(window.drawnItems);

  // ✅ Drawing tools
  var drawControl = new L.Control.Draw({
    edit: { featureGroup: window.drawnItems },
    draw: {
      circle: false,
      polyline: false,
      marker: false
    }
  });
  map.addControl(drawControl);

  // ✅ Load fields from DB
  loadFields(map);

  // ✅ Handle new drawing
  map.on('draw:created', function (e) {
    var layer = e.layer;
    window.drawnItems.addLayer(layer);

    var latlngs = layer.getLatLngs()[0];
    var area = L.GeometryUtil.geodesicArea(latlngs);
    var perimeter = 0;
    for (var i = 0; i < latlngs.length - 1; i++) {
      perimeter += latlngs[i].distanceTo(latlngs[i + 1]);
    }

    document.getElementById("field_area").value = area.toFixed(2);
    document.getElementById("field_perimeter").value = perimeter.toFixed(2);
    document.getElementById("field_geometry").value = JSON.stringify(layer.toGeoJSON().geometry);

    var modal = new bootstrap.Modal(document.getElementById('fieldModal'));
    modal.show();

    window.lastDrawnLayer = layer;
  });

  // ✅ Handle editing of existing shapes
  map.on('draw:edited', async function (e) {
    const layers = e.layers;
    layers.eachLayer(async function (layer) {
      const updatedGeometry = JSON.stringify(layer.toGeoJSON().geometry);
      const fieldId = layer.field_id;

      if (!fieldId) return;

      try {
        const res = await fetch('/Agrilink/backend/api/map/update_field.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ field_id: fieldId, geometry: updatedGeometry })
        });
        const result = await res.json();
        console.log('✅ Updated field:', result);
      } catch (err) {
        console.error('❌ Failed to update field:', err);
      }
    });

    setTimeout(() => loadFields(window.myMap), 500);
  });

  // ✅ Handle deleting of shapes
  map.on('draw:deleted', async function (e) {
    const layers = e.layers;
    layers.eachLayer(async function (layer) {
      const fieldId = layer.field_id;
      if (!fieldId) return;

      try {
        const res = await fetch('/Agrilink/backend/api/map/delete_field.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ field_id: fieldId })
        });
        const result = await res.json();
        console.log('🗑️ Deleted field:', result);
      } catch (err) {
        console.error('❌ Failed to delete field:', err);
      }
    });

    setTimeout(() => loadFields(window.myMap), 500);
  });

  // ✅ Default view: Gimalas, Balayan, Batangas
  const defaultLat = 13.9449;  // 13°56'41.6"N
  const defaultLng = 120.7517; // 120°45'06.0"E
  map.setView([defaultLat, defaultLng], 15);

  L.marker([defaultLat, defaultLng])
    .addTo(map)
    .bindPopup("📍 Gimalas, Balayan, Batangas")
    .openPopup();
}


// ✅ Load fields from backend
async function loadFields(map) {
  try {
    const res = await fetch("/Agrilink/backend/api/map/get_fields.php");
    const fields = await res.json();

    let allLayers = L.featureGroup();

    fields.forEach(field => {
      if (!field.geometry) return;

      const geometry = typeof field.geometry === "string"
        ? JSON.parse(field.geometry)
        : field.geometry;

      const polygon = L.geoJSON(geometry, {
        style: {
          color: "#28a745",
          weight: 2,
          fillOpacity: 0.4
        }
      });

      polygon.eachLayer(layer => {
        // Attach field_id for edit/delete reference
        layer.field_id = field.field_id;
        window.drawnItems.addLayer(layer);
      });

      // Label in center
      const layer = polygon.getLayers()[0];
      const center = layer.getBounds().getCenter();
      const label = L.divIcon({
        className: "field-label",
        html: `<strong>${field.name}</strong>`,
        iconSize: [100, 20]
      });
      L.marker(center, { icon: label }).addTo(map);

      polygon.bindPopup(`
        <b>${field.name}</b><br>
        Type: ${field.type}<br>
        Area: ${field.area} m²<br>
        Perimeter: ${field.perimeter} m<br>
        Notes: ${field.notes || "None"}
      `);

      allLayers.addLayer(polygon);
    });

    // Only zoom once on load
    if (!window.fieldsLoaded && allLayers.getLayers().length > 0) {
      map.fitBounds(allLayers.getBounds());
      window.fieldsLoaded = true;
    }

    console.log("✅ Loaded fields:", fields);
  } catch (err) {
    console.error("❌ Failed to load fields:", err);
  }
}

// ✅ Save new field
async function saveField() {
  const payload = {
    name: document.getElementById("field_name")?.value || '',
    area: document.getElementById("field_area")?.value || '',
    perimeter: document.getElementById("field_perimeter")?.value || '',
    type: document.getElementById("field_type")?.value || '',
    notes: document.getElementById("field_notes")?.value || '',
    geometry: document.getElementById("field_geometry")?.value || ''
  };

  try {
    const url = `${window.location.origin}/Agrilink/backend/api/map/save_field.php`;
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    if (!res.ok) throw new Error(await res.text() || res.statusText);
    const ct = res.headers.get('content-type') || '';
    const result = ct.includes('application/json') ? await res.json() : { message: await res.text() };

    // Hide the edit/add modal if present
    const fieldModalEl = document.getElementById('fieldModal');
    if (fieldModalEl) {
      const inst = bootstrap.Modal.getInstance(fieldModalEl) || new bootstrap.Modal(fieldModalEl);
      inst.hide();
    }

    // Show success modal
    const msgEl = document.getElementById('successMsg');
    if (msgEl) msgEl.textContent = result.message || 'Field saved successfully!';
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    successModal.show();

    // Refresh fields on the map
    if (typeof loadFields === 'function') {
      await loadFields(window.myMap || null);
    }
  } catch (err) {
    console.error(err);
    alert("Error saving field: " + err.message);
  }
}

document.addEventListener("DOMContentLoaded", mapInit);
</script>

