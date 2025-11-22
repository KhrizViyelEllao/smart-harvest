<!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- Leaflet Draw CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css"/>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
  body {
    margin: 0;
    padding: 0;
  }
  #map {
    position: relative;
    height: 100vh;
    width: 100%;
    overflow: hidden;
  }

  .field-label {
    background: rgba(255, 255, 255, 0.9); /* keep field name readable */
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    color: #1b5e20;
    text-align: center;
    white-space: nowrap;
    pointer-events: none;
  }

  /* Legend styles */
  .weather-legend {
  max-width: 200px;
    z-index: 1000;
    top: 90px;
    right: 24px;
  }
  .weather-legend .legend-color{
    display:inline-block;
    width:14px;
    height:14px;
    border-radius:3px;
    margin-right:6px;
  }
  .weather-legend .legend-border{
    display:inline-block;
    width:14px;
    height:14px;
    border:3px solid #1b5e20;
    border-radius:3px;
    margin-right:6px;
  }

  .weather-status-indicator {
    position: absolute;
    top: 10px;
    left: 10px;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    display: none;
  }

  .loading-spinner {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  .field-pulse {
    animation: fieldPulse 2s infinite;
  }

  @keyframes fieldPulse {
    0% { stroke-width: 2; }
    50% { stroke-width: 6; }
    100% { stroke-width: 2; }
  }

  .crop-panel {
    position: absolute;
    top: 340px;        /* sits just below the weather legend */
    right: 24px;
    width: 320px;
    max-height: 45vh;
    padding: 14px 16px;
    border-radius: 12px;
    background: rgba(255,255,255,0.95);
    box-shadow: 0 6px 18px rgba(0,0,0,0.18);
    overflow-y: auto;
    z-index: 1200;
  }
  .crop-panel-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    margin-bottom: 10px;
  }
  .crop-card {
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 10px;
    background: #f8f9fa;
  }

  .leaflet-tooltip.field-tooltip {
    background: rgba(255,255,255,0.96);
    border: 1px solid #dce3da;
    box-shadow: 0 4px 12px rgba(27,94,32,0.12);
    border-radius: 10px;
    padding: 10px 12px;
    min-width: 220px;
  }
  .field-tooltip h6 {
    margin: 0 0 6px;
    font-size: 14px;
    color: #1b5e20;
  }
  .field-tooltip .info-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    margin-bottom: 4px;
  }
  .field-tooltip .section-title {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #607d3b;
    margin: 8px 0 4px;
  }
  .field-tooltip .crop-item {
    font-size: 11px;
    margin-bottom: 4px;
  }
  </style>

  <!-- MAP CONTAINER -->
  <div id="map">
  </div>

  <!-- SAVE FIELD MODAL - KEEP ORIGINAL STYLE -->
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
              <input type="text" class="form-control" id="field_name" placeholder="Enter field name" required>
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
              <select class="form-select" id="field_type" required>
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
          <button type="button" class="btn btn-success" onclick="saveField()" id="saveFieldBtn">Save Field</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Success Modal - KEEP ORIGINAL STYLE -->
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

  <div id="weatherStatus" class="weather-status-indicator">
    <span class="loading-spinner"></span>
    <span id="weatherStatusText">Updating weather data...</span>
  </div>

  <!-- Legend -->
  <div id="weatherLegend" class="weather-legend position-absolute m-3 p-3 bg-white shadow-sm rounded">
    <div class="fw-semibold mb-2">Weather Layers</div>
    <div class="d-flex flex-column gap-1 small">
      <div><span class="legend-color" style="background:#4dabf5;"></span> ≤ 20 °C (Cool)</div>
      <div><span class="legend-color" style="background:#81c784;"></span> 20–25 °C (Mild)</div>
      <div><span class="legend-color" style="background:#ffb74d;"></span> 25–30 °C (Warm)</div>
      <div><span class="legend-color" style="background:#ef5350;"></span> ≥ 30 °C (Hot)</div>
      <div class="mt-2"><span class="legend-border" style="border-color:#2196f3;"></span> Rainfall detected</div>
    </div>
  </div>


  <br>



  <div id="cropPanel" class="crop-panel">
    <div class="crop-panel-header">
      <i class="fas fa-seedling text-success"></i>
      Crop Recommendations
    </div>
    <div id="cropPanelBody" class="small text-muted">
      Click a field to see crop recommendations.
    </div>
  </div>

  <!-- LEAFLET + BOOTSTRAP SCRIPTS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.geometryutil/0.9.3/leaflet.geometryutil.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  const WEATHER_ENDPOINT = '/backend/api/analytics/get_weather.php';
  const CROP_REC_ENDPOINT = '/backend/api/map/simple_crop_recs.php';
  const WEATHER_REFRESH_MS = 10 * 60 * 1000; // 10 minutes
  let weatherRefreshTimer = null;
  const fieldWeatherCache = new Map();
  const cropRecCache = new Map();
  const cropPanelBody = document.getElementById('cropPanelBody');

  function temperatureToColor(temp) {
    if (temp === null || temp === undefined) return '#9e9e9e';
    if (temp <= 20) return '#4dabf5';
    if (temp <= 25) return '#81c784';
    if (temp <= 30) return '#ffb74d';
    return '#ef5350';
  }

  function lightenColor(hex, factor = 0.35) {
    if (!hex) return '#dfe6e9';
    const num = parseInt(hex.replace('#',''),16);
    const r = Math.min(255, Math.round(((num>>16)&0xff) + (255-((num>>16)&0xff))*factor));
    const g = Math.min(255, Math.round(((num>>8)&0xff) + (255-((num>>8)&0xff))*factor));
    const b = Math.min(255, Math.round((num&0xff) + (255-(num&0xff))*factor));
    return `#${r.toString(16).padStart(2,'0')}${g.toString(16).padStart(2,'0')}${b.toString(16).padStart(2,'0')}`;
  }

  // Enhanced weather styling with alerts
  function applyWeatherStyling(layer, weather) {
    if (!weather) {
      return layer.setStyle({
        color: '#95a5a6',
        weight: 2,
        fillColor: '#f2f4f5',
        fillOpacity: 0.65,
        dashArray: '5, 5'
      });
    }
    const tempColor = lightenColor(temperatureToColor(weather.temperature));
    const hasRain   = weather.rain === 'yes';
    const hasAlerts = Array.isArray(weather.alerts) && weather.alerts.length > 0;

    layer.setStyle({
      color: hasRain ? '#0d47a1' : (hasAlerts ? '#ff4444' : '#1b5e20'),
      weight: hasAlerts ? 4 : (hasRain ? 3 : 2),
      fillColor: tempColor,
      fillOpacity: 0.65,
      dashArray: hasAlerts ? '5, 5' : null
    });

    // Add pulsing effect for critical alerts
    if (hasAlerts && !layer._pulseInterval) {
      let thick = false;
      layer._pulseInterval = setInterval(() => {
        layer.setStyle({ weight: thick ? 2 : 6 });
        thick = !thick;
      }, 1000);
    } else if (!hasAlerts && layer._pulseInterval) {
      clearInterval(layer._pulseInterval);
      layer._pulseInterval = null;
    }
  }

  function buildWeatherSummary(weather) {
    if (!weather) return '<div class="text-muted">Weather data unavailable.</div>';
    
    const hasAlerts = weather.alerts && weather.alerts.length > 0;
    const alertHtml = hasAlerts ? 
      `<div class="alert alert-warning mt-2 p-2 small">
          <strong>Alerts:</strong> ${weather.alerts.join(', ')}
      </div>` : '';

    return `
      <div class="mt-2 border-top pt-2">
        <div class="fw-semibold">Current Weather</div>
        <div class="small">${weather.description || '—'}</div>
        <div class="small">Temperature: ${weather.temperature ?? '—'} °C</div>
        <div class="small">Humidity: ${weather.humidity ?? '—'}%</div>
        <div class="small">Rainfall: ${weather.rain === 'yes' ? 'Yes (past hour)' : 'No'}</div>
        <div class="small text-muted">Updated: ${weather.timestamp || '—'}</div>
        ${alertHtml}
      </div>
    `;
  }

  async function fetchWeather(lat, lng) {
    const url = `${WEATHER_ENDPOINT}?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}`;
    
    // Show loading indicator
    document.getElementById('weatherStatus').style.display = 'block';
    
    try {
      const res = await fetch(url, { cache: 'no-store' });
      if (!res.ok) throw new Error(`Weather API HTTP ${res.status}`);
      const json = await res.json();
      if (json.error) throw new Error(json.error);
      return json;
    } finally {
      document.getElementById('weatherStatus').style.display = 'none';
    }
  }

  async function attachWeatherToLayer(field, layer) {
    try {
      const bounds = layer.getBounds();
      const center = bounds.getCenter();
      const weather = await fetchWeather(center.lat, center.lng);
      fieldWeatherCache.set(field.field_id, weather);
      layer._weather = weather;

      applyWeatherStyling(layer, weather);
      updateTooltipAndPopup(field, layer);
    } catch (error) {
      console.error(`Weather fetch failed for field ${field.field_id}:`, error);
      layer._weather = null;
      applyWeatherStyling(layer, null);
      updateTooltipAndPopup(field, layer);
    }
  }

  function updateTooltipAndPopup(field, layer) {
    const cropSummary = Array.isArray(field.crops) && field.crops.length
      ? field.crops.map(crop => {
          const plant = crop.planting_date ? new Date(crop.planting_date).toLocaleDateString() : 'N/A';
          const harvest = crop.expected_harvest ? new Date(crop.expected_harvest).toLocaleDateString() : 'N/A';
          return `
            <div class="crop-item">
              <strong>${crop.crop_name}</strong>
              <div class="text-muted">${crop.category || 'Uncategorized'}</div>
              <div>Planted: ${plant} • Harvest: ${harvest}</div>
            </div>
          `;
        }).join('')
      : '<div class="text-muted">No crops assigned.</div>';

    const weatherHtml = buildWeatherSummary(layer._weather);

    const tooltipHtml = `
      <div class="field-tooltip">
        <h6>${field.name}</h6>
        <div class="info-row">
          <span>Area</span>
          <span>${field.area ?? '—'} m²</span>
        </div>
        <div class="info-row">
          <span>Type</span>
          <span>${field.type ?? '—'}</span>
        </div>
        <div class="info-row">
          <span>Perimeter</span>
          <span>${field.perimeter ?? '—'} m</span>
        </div>
        <div class="section-title">Current Crops</div>
        ${cropSummary}
        <div class="section-title">Weather</div>
        ${weatherHtml}
      </div>
    `;

    const popupHtml = `
      <div style="max-width:320px;">
        <h5>${field.name}</h5>
        <div class="row small mb-2">
          <div class="col-6">Area: ${field.area ?? '—'} m²</div>
          <div class="col-6">Perimeter: ${field.perimeter ?? '—'} m</div>
        </div>
        <div class="mb-2">
          <strong>📋 Current Crops</strong>
          <div style="max-height:140px;overflow-y:auto;">${cropSummary}</div>
        </div>
        ${weatherHtml}
        ${field.notes ? `<div class="mt-2"><strong>Notes:</strong> ${field.notes}</div>` : ''}
      </div>
    `;

    layer.bindTooltip(tooltipHtml, {
      direction: 'top',
      sticky: true,
      opacity: 0.95,
      className: 'field-tooltip'
    });
    layer.bindPopup(popupHtml);
  }


// ==================== CROP RECOMMENDATIONS ====================

async function getCropRecommendations(fieldId) {
  if (cropRecCache.has(fieldId)) return cropRecCache.get(fieldId);
  try {
    const res = await fetch(`${CROP_REC_ENDPOINT}?field_id=${encodeURIComponent(fieldId)}`, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    cropRecCache.set(fieldId, json);
    return json;
  } catch (err) {
    console.error(`Crop recommendations failed for field ${fieldId}:`, err);
    return {
      success: false,
      recommendations: [],
      message: 'Unable to fetch crop recommendations right now.'
    };
  }
}

function scoreBadgeClass(score) {
  if (score >= 0.85) return 'bg-success';
  if (score >= 0.7) return 'bg-warning text-dark';
  return 'bg-secondary';
}

function renderCropPanel(field, recData) {
  if (!recData.success || !Array.isArray(recData.recommendations) || recData.recommendations.length === 0) {
    cropPanelBody.innerHTML = `
      <div class="fw-semibold mb-1">${field.name}</div>
      <div class="text-muted small">${recData.message || 'No crop suggestions available.'}</div>
    `;
    return;
  }

  const cards = recData.recommendations.map(rec => `
    <div class="crop-card">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="fw-semibold">${rec.crop_name}</span>
        <span class="badge ${scoreBadgeClass(rec.score)}">${Math.round(rec.score * 100)}%</span>
      </div>
      <div class="small">
        <div>🌡️ Optimal Temp: ${rec.optimal_temp}</div>
        <div>💧 Water Needs: ${rec.water_needs}</div>
        <div>📅 Duration: ${rec.duration_days} days</div>
        <div>🗓️ Season: ${rec.season}</div>
      </div>
    </div>
  `).join('');

  cropPanelBody.innerHTML = `
    <div class="fw-semibold mb-1">${field.name}</div>
    ${recData.season_context ? `<div class="text-muted small mb-2"><i class="fas fa-calendar-alt me-1"></i>${recData.season_context}</div>` : ''}
    ${cards}
  `;
}

async function handleFieldClick(field, layer) {
  layer.openPopup();
  cropPanelBody.innerHTML = `
    <div class="small text-muted">
      <span class="spinner-border spinner-border-sm text-success me-2" role="status"></span>
      Loading recommendations...
    </div>
  `;
  const recData = await getCropRecommendations(field.field_id);
  renderCropPanel(field, recData);
}

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

      // Reset form
      document.getElementById("field_name").value = '';
      document.getElementById("field_notes").value = '';

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
          const res = await fetch('/backend/api/map/update_field.php', {
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

        if (!confirm('Are you sure you want to delete this field?')) return;

        try {
          const res = await fetch('/backend/api/map/delete_field.php', {
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

  // FIXED: Save Field Function
  async function saveField() {
    const fieldName = document.getElementById('field_name').value.trim();
    const fieldType = document.getElementById('field_type').value;
    const fieldNotes = document.getElementById('field_notes').value.trim();
    const fieldGeometry = document.getElementById('field_geometry').value;
    const fieldArea = document.getElementById('field_area').value;
    const fieldPerimeter = document.getElementById('field_perimeter').value;

    // Validation
    if (!fieldName) {
      alert('Please enter a field name');
      return;
    }

    if (!fieldType) {
      alert('Please select a field type');
      return;
    }

    if (!fieldGeometry) {
      alert('No field geometry found. Please draw a field first.');
      return;
    }

    const saveBtn = document.getElementById('saveFieldBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = 'Saving...';
    saveBtn.disabled = true;

    try {
      const response = await fetch('/backend/api/map/save_field.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: fieldName,
          type: fieldType,
          notes: fieldNotes,
          geometry: fieldGeometry,
          area: fieldArea,
          perimeter: fieldPerimeter
        })
      });

      const result = await response.json();

      if (result.success) {
        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('fieldModal'));
        modal.hide();
        
        // Show success message
        document.getElementById('successMsg').innerHTML = `Field <strong>${fieldName}</strong> saved successfully!`;
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        
        // Reload fields and clean up
        setTimeout(() => {
          loadFields(window.myMap);
          // Remove the drawn layer from the drawing layer group
          if (window.lastDrawnLayer) {
            window.drawnItems.removeLayer(window.lastDrawnLayer);
          }
        }, 1000);
        
      } else {
        throw new Error(result.message || 'Failed to save field');
      }
    } catch (error) {
      console.error('Error saving field:', error);
      alert('Error saving field: ' + error.message);
    } finally {
      saveBtn.innerHTML = originalText;
      saveBtn.disabled = false;
    }
  }

  // ✅ Load fields from backend
  async function loadFields(map) {
    try {
      const res = await fetch('/backend/api/map/get_fields.php', { cache: 'no-store' });
      const fields = await res.json();
      if (window.drawnItems) {
        window.drawnItems.clearLayers();
      }
      if (map._fieldLabels) {
        map._fieldLabels.forEach(lbl => map.removeLayer(lbl));
      }
      map._fieldLabels = [];

      const boundsGroup = L.featureGroup();

      fields.forEach(field => {
        if (!field.geometry) return;

        const geometry = typeof field.geometry === "string"
          ? JSON.parse(field.geometry)
          : field.geometry;

        const polygon = L.geoJSON(geometry, {
          style: { color: '#1b5e20', weight: 2, fillOpacity: 0.35, fillColor: '#81c784' }
        });

        polygon.eachLayer(layer => {
          layer.field_id = field.field_id;
          window.drawnItems.addLayer(layer);
          boundsGroup.addLayer(layer);

          layer._weather = fieldWeatherCache.get(field.field_id) || null;
          updateTooltipAndPopup(field, layer);
          attachWeatherToLayer(field, layer);

          layer.on('click', () => handleFieldClick(field, layer));
        });

        const layer = polygon.getLayers()[0];
        if (layer) {
          const center = layer.getBounds().getCenter();
          const label = L.divIcon({
            className: "field-label",
            html: `<strong>${field.name}</strong>`,
            iconSize: [100, 20]
          });
          const labelMarker = L.marker(center, { icon: label }).addTo(map);
          map._fieldLabels.push(labelMarker);
        }
      });

      if (!window.fieldsLoaded && boundsGroup.getLayers().length > 0) {
        map.fitBounds(boundsGroup.getBounds(), { padding: [40, 40] });
        window.fieldsLoaded = true;
      }

      if (weatherRefreshTimer) clearInterval(weatherRefreshTimer);
      weatherRefreshTimer = setInterval(() => {
        window.drawnItems.eachLayer(layer => {
          const fieldId = layer.field_id;
          const field = fields.find(f => f.field_id === fieldId);
          if (!field) return;
          attachWeatherToLayer(field, layer);
        });
      }, WEATHER_REFRESH_MS);
    } catch (err) {
      console.error('❌ Failed to load fields:', err);
    }
  }

  // Initial map setup
  mapInit();
  </script>