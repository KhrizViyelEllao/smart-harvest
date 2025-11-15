<?php
include_once 'backend/db_connect.php';
?>
<!-- Optional Font Awesome (for check icon consistency) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<div class="main-content p-4" style="min-height: 100vh; background-color: #f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 840px;">
      <div class="card-body p-4">
        <button class="btn btn-outline-secondary btn-sm mb-3" onclick="goBackToTasks()">&larr; Back</button>

        <h2 class="text-center mb-3 text-success"><i class="bi bi-basket2"></i> Harvest Task Details</h2>
        <p class="text-muted text-center mb-4">Log the expected harvest so it’s stored immediately.</p>

        <form id="harvestForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Field</label>
              <input type="text" class="form-control" id="fieldNameDisplay" readonly>
              <input type="hidden" id="fieldIdHidden">
              <div class="form-text">Selected during task scheduling.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Crop to harvest</label>
              <select class="form-select" id="cropSelect" required>
                <option value="">Loading crops...</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Crop description</label>
              <textarea class="form-control" id="cropDescription" rows="3" placeholder="Auto-filled from crop" readonly></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Harvest date</label>
              <input type="date" class="form-control" id="harvestDate" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Predicted yield (kg)</label>
              <input type="number" class="form-control" id="predictedYield" min="0" step="0.01" placeholder="e.g., 120" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Actual yield (kg) <small class="text-muted">(optional)</small></label>
              <input type="number" class="form-control" id="actualYield" min="0" step="0.01" placeholder="Enter when harvest is done">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Quality grade</label>
              <select class="form-select" id="quality" required>
                <option value="">-- Select quality --</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Notes <small class="text-muted">(optional)</small></label>
              <textarea class="form-control" id="notes" rows="3" placeholder="e.g., Moisture level, pests observed"></textarea>
            </div>
          </div>

          <div class="text-center mt-4">
            <button type="submit" class="btn btn-success px-5 py-2">Save harvest</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Success Modal (styled like map.php) -->
<div class="modal fade" id="harvestSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Harvest Saved</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
        <div id="harvestSuccessMsg">Harvest saved successfully!</div>
      </div>
      <div class="modal-footer justify-content-center">
        <button id="goAssignFarmerBtn" type="button" class="btn btn-success px-4">Assign Farmers</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const base = window.location.origin + '/Agrilink';

  const fieldIdInput         = document.getElementById('fieldIdHidden');
  const fieldNameDisplay     = document.getElementById('fieldNameDisplay');
  const cropSelect           = document.getElementById('cropSelect');
  const cropDescriptionInput = document.getElementById('cropDescription');
  const harvestDateInput     = document.getElementById('harvestDate');
  const predictedYieldInput  = document.getElementById('predictedYield');
  const actualYieldInput     = document.getElementById('actualYield');
  const qualitySelect        = document.getElementById('quality');
  const notesInput           = document.getElementById('notes');
  const form                 = document.getElementById('harvestForm');

  const savedDetails   = safeParse(localStorage.getItem('harvestTaskDetails')) || null;
  const selectedFields = normalizeFieldSelection(safeParse(localStorage.getItem('selectedFields')));

  const scheduledDate = resolveScheduledDate();
  if (scheduledDate && harvestDateInput) {
    harvestDateInput.value = scheduledDate;
  }

  if (!selectedFields.length) {
    alert('Please select at least one field before entering harvest details.');
    redirectToTasks();
    return;
  }

  init().catch(err => {
    console.error('Harvest form initialization failed:', err);
    alert('Unable to load harvest details. Please try again.');
    redirectToTasks();
  });

  cropSelect?.addEventListener('change', () => {
    const option = cropSelect.options[cropSelect.selectedIndex];
    cropDescriptionInput.value = (option && option.dataset.description) ? option.dataset.description : '';
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!fieldIdInput.value) { alert('Missing field.'); return; }
    if (!cropSelect.value) { alert('Select a crop.'); return; }
    if (!harvestDateInput.value) { alert('Set harvest date.'); return; }
    if (!qualitySelect.value) { alert('Select quality grade.'); return; }

    const payload = {
      crop_id: Number(cropSelect.value),
      field_id: Number(fieldIdInput.value),
      harvest_date: harvestDateInput.value,
      predicted_yield_kg: predictedYieldInput.value !== '' ? Number(predictedYieldInput.value) : null,
      actual_yield_kg: actualYieldInput.value !== '' ? Number(actualYieldInput.value) : null,
      quality: String(qualitySelect.value).toLowerCase(),
      notes: notesInput.value.trim()
    };

    try {
      const res = await fetch(`${base}/backend/api/harvests/store.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Failed to save harvest.');

      localStorage.setItem('harvestTaskDetails', JSON.stringify({
        ...payload,
        harvest_id: json.harvest_id ?? null,
        fieldId: fieldIdInput.value,
        fieldName: fieldNameDisplay.value,
        cropName: cropSelect.options[cropSelect.selectedIndex]?.textContent?.trim() || ''
      }));

      showHarvestSuccess(json.message || 'Harvest saved successfully!');
    } catch (err) {
      console.error('Save harvest failed:', err);
      alert(err.message || 'Unable to save harvest.');
    }
  });

  function showHarvestSuccess(message) {
    const msgEl = document.getElementById('harvestSuccessMsg');
    if (msgEl) msgEl.textContent = message;
    const modalEl = document.getElementById('harvestSuccessModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    document.getElementById('goAssignFarmerBtn')?.addEventListener('click', () => {
      window.location.href = `${base}/layout.php?page=assign_farmer`;
    });

    // Auto-redirect after 4s if still open
    setTimeout(() => {
      if (modalEl.classList.contains('show')) {
        window.location.href = `${base}/layout.php?page=assign_farmer`;
      }
    }, 4000);
  }

  async function init() {
    const preferredFieldId = (savedDetails?.fieldId || '').toString();
    let activeField = preferredFieldId
      ? selectedFields.find(f => f.id === preferredFieldId)
      : null;

    if (!activeField) activeField = selectedFields[0];
    if (!activeField) throw new Error('No field information.');

    fieldIdInput.value = activeField.id;
    fieldNameDisplay.value = activeField.name || `Field #${activeField.id}`;

    await loadFieldCrops(activeField.id, savedDetails?.crop_id || savedDetails?.cropId);
    applySavedDetails(savedDetails);
  }

  async function loadFieldCrops(fieldId, preferredCropId = null) {
    cropSelect.disabled = true;
    cropSelect.innerHTML = '<option value="">Loading crops...</option>';
    cropDescriptionInput.value = '';

    try {
      const res = await fetch(`${base}/backend/api/tasks/get_crops_by_field.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ field_id: Number(fieldId) })
      });

      // Handle non-OK or non-JSON responses safely
      if (!res.ok) {
        const txt = await res.text();
        throw new Error(txt || res.statusText);
      }
      const ct = res.headers.get('content-type') || '';
      const json = ct.includes('application/json') ? await res.json() : { success:false, message:'Invalid response' };

      const crops = Array.isArray(json.data) ? json.data : [];

      if (json.field) {
        fieldNameDisplay.value = json.field.name?.trim() || fieldNameDisplay.value;
      }

      if (!crops.length) {
        cropSelect.innerHTML = '<option value="">No crops linked to this field</option>';
        return;
      }

      cropSelect.innerHTML = '<option value="">-- Select crop --</option>';
      crops.forEach(crop => {
        const opt = document.createElement('option');
        opt.value = crop.crop_id;
        opt.textContent = crop.crop_name;
        opt.dataset.description = crop.description || '';
        cropSelect.appendChild(opt);
      });

      if (preferredCropId && crops.some(c => String(c.crop_id) === String(preferredCropId))) {
        cropSelect.value = preferredCropId;
      }

      cropSelect.disabled = false;
      cropSelect.dispatchEvent(new Event('change'));
    } catch (err) {
      console.error('Failed to load crops:', err);
      cropSelect.innerHTML = '<option value="">Unable to load crops</option>';
    }
  }

  function applySavedDetails(details) {
    if (!details) return;
    if (cropSelect && details.crop_id) {
      cropSelect.value = details.crop_id;
      cropSelect.dispatchEvent(new Event('change'));
    }
    harvestDateInput && (harvestDateInput.value = details.harvest_date || harvestDateInput.value);
    predictedYieldInput && (predictedYieldInput.value = details.predicted_yield_kg ?? '');
    actualYieldInput && (actualYieldInput.value = details.actual_yield_kg ?? '');
    qualitySelect && (qualitySelect.value = details.quality || '');
    notesInput && (notesInput.value = details.notes || '');
  }

  function resolveScheduledDate() {
    const stored =
      normalizeIsoString(localStorage.getItem('selectedDate')) ||
      normalizeIsoString(localStorage.getItem('taskHarvestDate')) ||
      normalizeIsoString(localStorage.getItem('taskScheduledDate')) ||
      normalizeIsoString(localStorage.getItem('selectedTaskDate'));
    return stored || '';
  }

  function normalizeFieldSelection(raw) {
    if (!Array.isArray(raw)) return [];
    return raw
      .map(item => {
        if (typeof item === 'object' && item) {
            const id = item.field_id ?? item.fieldId ?? item.id;
            if (!id) return null;
            return { id: String(id), name: item.name ?? item.field_name ?? '' };
        }
        const id = String(item).trim();
        return id ? { id, name: '' } : null;
      })
      .filter(Boolean);
  }

  function normalizeIsoString(value) {
    if (!value) return '';
    const trimmed = String(value).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return trimmed;
    const parsed = new Date(trimmed);
    return Number.isNaN(parsed.getTime()) ? '' : parsed.toISOString().slice(0, 10);
  }

  function safeParse(value) {
    try { return value ? JSON.parse(value) : null; } catch { return null; }
  }

  function redirectToTasks() {
    window.location.href = `${base}/layout.php?page=tasks`;
  }

  window.goBackToTasks = redirectToTasks;
})();
</script>