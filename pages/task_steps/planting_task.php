<?php
//// filepath: c:\xampp\htdocs\Agrilink\pages\task_steps\planting_task.php
include_once 'backend/db_connect.php';
?>

<div class="main-content p-4" style="min-height: 100vh; background-color: #f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 840px;">
      <div class="card-body p-4">
        <button class="btn btn-outline-secondary btn-sm mb-3" onclick="goBackToTasks()">&larr; Back</button>

        <h2 class="text-center mb-3 text-success"><i class="bi bi-flower3"></i> Planting Task Details</h2>
        <p class="text-muted text-center mb-4">Capture the specifics needed to perform this planting activity.</p>

        <form id="plantingForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Field</label>
              <input type="text" class="form-control" id="fieldNameDisplay" readonly>
              <input type="hidden" id="fieldIdHidden">
              <div class="form-text">Selected during task scheduling.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Crop to plant</label>
              <select class="form-select" id="cropSelect" disabled>
                <option value="">Select a field first</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Description</label>
              <textarea class="form-control" id="cropVariety" rows="3" placeholder="Auto-filled from crop" readonly></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Planting date</label>
              <input type="text" class="form-control" id="plantingDateDisplay" readonly>
              <div class="form-text">This date was set during task scheduling.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Planting method</label>
              <select class="form-select" id="plantingMethod">
                <option value="">-- Select method --</option>
                <option value="Direct seeding">Direct seeding</option>
                <option value="Transplanting">Transplanting</option>
                <option value="Broadcasting">Broadcasting</option>
                <option value="Plug trays">Plug trays</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Row spacing (cm)</label>
              <input type="number" class="form-control" id="rowSpacing" min="0" step="0.1">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Plant spacing (cm)</label>
              <input type="number" class="form-control" id="plantSpacing" min="0" step="0.1">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Seeds required</label>
              <div class="input-group">
                <input type="number" class="form-control" id="seedQuantity" min="0" step="0.01" placeholder="e.g., 5">
                <select class="form-select" id="seedUnit" style="max-width: 120px;">
                  <option value="kg">kg</option>
                  <option value="g">g</option>
                  <option value="lb">lb</option>
                  <option value="seeds">seeds</option>
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Field condition notes <small class="text-muted">(optional)</small></label>
              <textarea class="form-control" id="fieldCondition" rows="2" placeholder="e.g., Bed prepared, soil moisture adequate"></textarea>
            </div>
          </div>

          <hr class="my-4">

          <div class="mb-3">
            <label class="form-label fw-semibold">Will fertilizer be applied at planting?</label>
            <div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="applyFertilizer" id="fertilizerYes" value="yes">
                <label class="form-check-label" for="fertilizerYes">Yes</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="applyFertilizer" id="fertilizerNo" value="no">
                <label class="form-check-label" for="fertilizerNo">No</label>
              </div>
            </div>
          </div>

          <div id="fertilizerDetails" class="border rounded bg-light p-3 mb-3" style="display: none;">
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label fw-semibold">Fertilizer name</label>
                <input type="text" class="form-control" id="fertilizerName" placeholder="e.g., NPK 14-14-14">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Rate</label>
                <input type="text" class="form-control" id="fertilizerRate" placeholder="e.g., 50 kg/ha">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Application method</label>
                <input type="text" class="form-control" id="fertilizerMethod" placeholder="e.g., Banding, Broadcasting">
              </div>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Irrigation plan <small class="text-muted">(optional)</small></label>
            <textarea class="form-control" id="irrigationPlan" rows="2" placeholder="e.g., Light watering after planting, drip schedule"></textarea>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Additional notes <small class="text-muted">(optional)</small></label>
            <textarea class="form-control" id="additionalNotes" rows="2" placeholder="e.g., Monitor emergence after 7 days"></textarea>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold">Instructions for the assignee</label>
            <textarea class="form-control" id="instructions" rows="2" placeholder="e.g., Plant early morning, ensure even depth"></textarea>
          </div>

          <div class="text-center mt-4">
            <button type="button" id="continueBtn" class="btn btn-success px-5 py-2">Continue</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const base = window.location.origin;

  const fertilizerSection     = document.getElementById('fertilizerDetails');
  const fertilizerYes         = document.getElementById('fertilizerYes');
  const fertilizerNo          = document.getElementById('fertilizerNo');
  const fieldIdInput          = document.getElementById('fieldIdHidden');
  const fieldNameDisplay      = document.getElementById('fieldNameDisplay');
  const cropSelect            = document.getElementById('cropSelect');
  const varietyInput          = document.getElementById('cropVariety');
  const plantingDateDisplay   = document.getElementById('plantingDateDisplay');
  const plantingMethodInput   = document.getElementById('plantingMethod');
  const rowSpacingInput       = document.getElementById('rowSpacing');
  const plantSpacingInput     = document.getElementById('plantSpacing');
  const seedQuantityInput     = document.getElementById('seedQuantity');
  const seedUnitSelect        = document.getElementById('seedUnit');
  const fieldConditionInput   = document.getElementById('fieldCondition');
  const irrigationPlanInput   = document.getElementById('irrigationPlan');
  const additionalNotesInput  = document.getElementById('additionalNotes');
  const instructionsInput     = document.getElementById('instructions');
  const fertilizerNameInput   = document.getElementById('fertilizerName');
  const fertilizerRateInput   = document.getElementById('fertilizerRate');
  const fertilizerMethodInput = document.getElementById('fertilizerMethod');
  const continueBtn           = document.getElementById('continueBtn');

  const savedDetails          = safeParse(localStorage.getItem('plantingTaskDetails')) || null;
  const selectedFieldEntries  = normalizeFieldSelection(safeParse(localStorage.getItem('selectedFields')));
  const scheduledPlantingDate = resolveScheduledDate();

  let fieldListCachePromise   = null;
  let computedExpectedHarvest = savedDetails?.expectedHarvest || '';

  fertilizerYes?.addEventListener('change', () => fertilizerSection.style.display = 'block');
  fertilizerNo?.addEventListener('change', () => fertilizerSection.style.display = 'none');

  updatePlantingDateDisplay(scheduledPlantingDate);

  if (!selectedFieldEntries.length) {
    alert('Please select at least one field before entering planting details.');
    redirectToTasks();
    return;
  }

  init().catch(err => {
    console.error('Planting form initialization failed:', err);
    alert('Unable to load planting details. Please try again.');
    redirectToTasks();
  });

  cropSelect?.addEventListener('change', () => {
    const option = cropSelect.options[cropSelect.selectedIndex];
    if (!option || !option.value) {
      varietyInput.value = '';
      computedExpectedHarvest = computeExpectedHarvest(null, scheduledPlantingDate);
      return;
    }
    varietyInput.value = option.dataset.variety || '—';
    computedExpectedHarvest = computeExpectedHarvest(option.dataset.duration, scheduledPlantingDate);
  });

  continueBtn?.addEventListener('click', () => {
    if (!fieldIdInput?.value) {
      alert('Field information is missing.');
      return;
    }
    if (!cropSelect?.value) {
      alert('Please choose a crop.');
      return;
    }

    const cropOption = cropSelect.options[cropSelect.selectedIndex];

    const data = {
      fieldId: fieldIdInput.value,
      fieldName: (fieldNameDisplay?.value || '').trim(),
      cropId: cropSelect.value,
      cropName: (cropOption?.textContent || '').trim(),
      cropVariety: (varietyInput.value || '').trim(),
      cropDuration: cropOption?.dataset.duration || '',
      plantingDate: scheduledPlantingDate,
      expectedHarvest: computedExpectedHarvest,
      plantingMethod: plantingMethodInput?.value || '',
      rowSpacing: rowSpacingInput?.value || '',
      plantSpacing: plantSpacingInput?.value || '',
      seedQuantity: seedQuantityInput?.value || '',
      seedUnit: seedUnitSelect?.value || '',
      fieldCondition: fieldConditionInput?.value.trim() || '',
      applyFertilizer: document.querySelector('input[name="applyFertilizer"]:checked')?.value || '',
      fertilizerName: fertilizerNameInput?.value.trim() || '',
      fertilizerRate: fertilizerRateInput?.value.trim() || '',
      fertilizerMethod: fertilizerMethodInput?.value.trim() || '',
      irrigationPlan: irrigationPlanInput?.value.trim() || '',
      additionalNotes: additionalNotesInput?.value.trim() || '',
      instructions: instructionsInput?.value.trim() || '',
    };

    localStorage.setItem('plantingTaskDetails', JSON.stringify(data));
    window.location.href = `${base}/layout.php?page=assign_farmer`;
  });

  async function init() {
    const preferredFieldId = (savedDetails?.fieldId || '').toString();
    let activeField = preferredFieldId
      ? selectedFieldEntries.find(f => f.id === preferredFieldId)
      : null;

    if (!activeField) {
      activeField = selectedFieldEntries[0];
    }

    if (!activeField) {
      alert('The selected fields are no longer available.');
      redirectToTasks();
      return;
    }

    fieldIdInput.value = activeField.id;
    fieldNameDisplay.value = activeField.name || 'Loading field name...';

    await ensureFieldName(activeField.id);
    await loadFieldCrops(activeField.id, savedDetails?.cropId);
    applySavedDetails(savedDetails);
  }

  async function loadFieldCrops(fieldId, preferredCropId = null) {
    if (!cropSelect) return;
    if (!fieldId) {
      cropSelect.innerHTML = '<option value="">Select a field first</option>';
      cropSelect.disabled = true;
      varietyInput.value = '';
      computedExpectedHarvest = computeExpectedHarvest(null, scheduledPlantingDate);
      return;
    }

    cropSelect.disabled = true;
    cropSelect.innerHTML = '<option value="">Loading crops...</option>';
    varietyInput.value = '';
    computedExpectedHarvest = computeExpectedHarvest(null, scheduledPlantingDate);

    try {
      const res = await fetch(`${base}/backend/api/tasks/get_crops_by_field.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ field_id: Number(fieldId) }),
      });
      const json = await res.json();
      const crops = Array.isArray(json.data) ? json.data : [];

      if (json.field) {
        fieldNameDisplay.value =
          json.field.name?.trim() || `Field #${json.field.field_id || fieldId}`;
      } else {
        await ensureFieldName(fieldId);
      }

      if (!crops.length) {
        cropSelect.innerHTML = '<option value="">No crops linked to this field yet</option>';
        cropSelect.disabled = true;
        return;
      }

      cropSelect.innerHTML = '<option value="">-- Select crop --</option>';
      crops.forEach(crop => {
        const opt = document.createElement('option');
        opt.value = crop.crop_id;
        opt.textContent = crop.crop_name;
        opt.dataset.variety = crop.category || crop.description || '';
        opt.dataset.duration = crop.duration || '';
        cropSelect.appendChild(opt);
      });

      if (preferredCropId && crops.some(c => String(c.crop_id) === String(preferredCropId))) {
        cropSelect.value = preferredCropId;
      }

      cropSelect.disabled = false;
      cropSelect.dispatchEvent(new Event('change'));
    } catch (err) {
      console.error('Failed to load crops for field:', err);
      cropSelect.innerHTML = '<option value="">Unable to load crops</option>';
      cropSelect.disabled = true;
      computedExpectedHarvest = computeExpectedHarvest(null, scheduledPlantingDate);
    }
  }

  async function ensureFieldName(fieldId) {
    if (!fieldId || !fieldNameDisplay) return;
    try {
      const list = await getFieldList();
      const match = list.find(f => String(f.field_id) === String(fieldId));
      if (match?.name) {
        fieldNameDisplay.value = match.name;
      }
    } catch (err) {
      console.warn('Unable to resolve field name:', err);
    }
  }

  function applySavedDetails(details) {
    if (!details) return;

    plantingMethodInput && (plantingMethodInput.value = details.plantingMethod || '');
    rowSpacingInput && (rowSpacingInput.value = details.rowSpacing || '');
    plantSpacingInput && (plantSpacingInput.value = details.plantSpacing || '');
    seedQuantityInput && (seedQuantityInput.value = details.seedQuantity || '');
    seedUnitSelect && (seedUnitSelect.value = details.seedUnit || 'kg');
    fieldConditionInput && (fieldConditionInput.value = details.fieldCondition || '');
    irrigationPlanInput && (irrigationPlanInput.value = details.irrigationPlan || '');
    additionalNotesInput && (additionalNotesInput.value = details.additionalNotes || '');
    instructionsInput && (instructionsInput.value = details.instructions || '');

    if (details.applyFertilizer === 'yes') {
      fertilizerYes?.click();
    } else if (details.applyFertilizer === 'no') {
      fertilizerNo?.click();
    }

    fertilizerNameInput && (fertilizerNameInput.value = details.fertilizerName || '');
    fertilizerRateInput && (fertilizerRateInput.value = details.fertilizerRate || '');
    fertilizerMethodInput && (fertilizerMethodInput.value = details.fertilizerMethod || '');

    if (!cropSelect?.value && details.cropId) {
      cropSelect.value = details.cropId;
    }

    if (cropSelect?.value) {
      cropSelect.dispatchEvent(new Event('change'));
    }
    computedExpectedHarvest = details.expectedHarvest || '';
  }

  function computeExpectedHarvest(duration, baseDate) {
    if (!duration || !baseDate) return '';
    const days = parseDuration(duration);
    if (!Number.isFinite(days) || days <= 0) return '';
    return addDaysToIso(baseDate, days);
  }

  function autoFillExpectedHarvest(duration, baseDate) {
    if (!expectedHarvestInput) return;

    const fallback = savedDetails?.expectedHarvest || '';

    if (!duration || !baseDate) {
      if (fallback) expectedHarvestInput.value = fallback;
      expectedHarvestInput.disabled = false;
      return;
    }

    const days = parseDuration(duration);
    if (!Number.isFinite(days) || days <= 0) {
      if (fallback) expectedHarvestInput.value = fallback;
      expectedHarvestInput.disabled = false;
      return;
    }

    const computed = addDaysToIso(baseDate, days);
    if (!computed) {
      if (fallback) expectedHarvestInput.value = fallback;
      expectedHarvestInput.disabled = false;
      return;
    }

    expectedHarvestInput.value = computed;
    expectedHarvestInput.disabled = true;
  }

  function updatePlantingDateDisplay(dateStr) {
    if (!plantingDateDisplay) return;
    if (!dateStr) {
      plantingDateDisplay.value = 'Not set';
      return;
    }
    const date = new Date(`${dateStr}T00:00:00`);
    plantingDateDisplay.value = Number.isNaN(date.getTime())
      ? 'Not set'
      : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function resolveScheduledDate() {
    const stored =
      normalizeIsoString(localStorage.getItem('selectedDate')) ||
      normalizeIsoString(localStorage.getItem('taskPlantingDate')) ||
      normalizeIsoString(localStorage.getItem('taskScheduledDate')) ||
      normalizeIsoString(localStorage.getItem('selectedTaskDate')) ||
      normalizeIsoString(localStorage.getItem('plantingDate'));

    return stored || '';
  }

  async function getFieldList() {
    if (fieldListCachePromise) {
      return fieldListCachePromise;
    }
    fieldListCachePromise = fetch(`${base}/backend/api/map/get_fields.php`)
      .then(res => res.json())
      .then(data => (Array.isArray(data) ? data : []))
      .catch(err => {
        console.warn('Unable to load field list:', err);
        return [];
      });
    return fieldListCachePromise;
  }

  function parseDuration(value) {
    if (value === null || value === undefined) return NaN;
    const numeric = parseInt(String(value).replace(/[^\d-]+/g, ''), 10);
    return Number.isNaN(numeric) ? NaN : numeric;
  }

  function addDaysToIso(dateStr, days) {
    const base = createDateFromIso(dateStr);
    if (!base) return '';
    base.setUTCDate(base.getUTCDate() + days);
    return toIsoDate(base);
  }

  function createDateFromIso(iso) {
    if (!iso) return null;
    const parts = iso.split('-').map(Number);
    if (parts.length !== 3 || parts.some(Number.isNaN)) return null;
    return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
  }

  function toIsoDate(date) {
    return date.toISOString().slice(0, 10);
  }

  function normalizeIsoString(value) {
    if (!value) return '';
    const trimmed = String(value).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return trimmed;
    const parsed = new Date(trimmed);
    return Number.isNaN(parsed.getTime()) ? '' : toIsoDate(parsed);
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

  function safeParse(value) {
    try {
      return value ? JSON.parse(value) : null;
    } catch (err) {
      console.warn('Unable to parse localStorage value:', err);
      return null;
    }
  }

  function redirectToTasks() {
    window.location.href = `${base}/layout.php?page=tasks`;
  }

  window.goBackToTasks = redirectToTasks;
})();
</script>