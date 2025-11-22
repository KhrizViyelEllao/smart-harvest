<?php

include_once 'backend/db_connect.php';
?>
<div class="main-content p-4" style="min-height:100vh;background-color:#f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:820px;">
      <div class="card-body p-4">
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="window.location.href='/layout.php?page=tasks'">
          &larr; Back to Tasks
        </button>

        <h2 class="text-center text-primary mb-3">
          <i class="bi bi-droplet-half me-2"></i>Irrigation Task Details
        </h2>
        <p class="text-muted text-center mb-4">Document the irrigation plan and resources for this field activity.</p>

        <form id="irrigationForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Field</label>
              <input type="text" class="form-control" id="fieldNameDisplay" readonly>
              <input type="hidden" id="fieldIdHidden">
              <div class="form-text">Chosen during scheduling.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Scheduled date</label>
              <input type="text" class="form-control" id="irrigationDateDisplay" readonly>
              <div class="form-text">From task Step 2.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Irrigation type</label>
              <select class="form-select" id="irrigationType">
                <option value="">-- Select type --</option>
                <option value="Drip">Drip irrigation</option>
                <option value="Sprinkler">Sprinkler</option>
                <option value="Furrow">Surface / Furrow</option>
                <option value="Flood">Flood</option>
                <option value="Mist">Misting / Fogging</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Water source</label>
              <select class="form-select" id="waterSource">
                <option value="">-- Select source --</option>
                <option value="Groundwater well">Groundwater well</option>
                <option value="Surface canal">Surface canal</option>
                <option value="Rainwater tank">Rainwater tank</option>
                <option value="Municipal supply">Municipal supply</option>
                <option value="River / Stream">River / Stream</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Estimated duration</label>
              <div class="input-group">
                <input type="number" class="form-control" id="durationValue" min="0" step="0.5" placeholder="e.g., 2">
                <select class="form-select" id="durationUnit" style="max-width:130px;">
                  <option value="hours">Hours</option>
                  <option value="minutes">Minutes</option>
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Frequency</label>
              <input type="text" class="form-control" id="frequency" placeholder="e.g., Every 3 days">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Irrigation equipment</label>
              <input type="text" class="form-control" id="equipment" placeholder="e.g., Pump, drip lines">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Water volume (optional)</label>
              <div class="input-group">
                <input type="number" class="form-control" id="waterVolume" min="0" step="0.01" placeholder="e.g., 5">
                <select class="form-select" id="volumeUnit" style="max-width:130px;">
                  <option value="m3">m³</option>
                  <option value="liters">Liters</option>
                  <option value="gallons">Gallons</option>
                </select>
              </div>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Soil moisture / condition notes</label>
              <textarea class="form-control" id="soilCondition" rows="2"
                        placeholder="e.g., Topsoil dry, subsoil moist at 15cm depth"></textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Instructions for assignee</label>
              <textarea class="form-control" id="instructions" rows="3"
                        placeholder="e.g., Begin at northern block, check emitters for clogs."></textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Additional notes <small class="text-muted">(optional)</small></label>
              <textarea class="form-control" id="additionalNotes" rows="3"></textarea>
            </div>
          </div>

          <hr class="my-4">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Follow-up needed?</label>
              <select class="form-select" id="followUpPlan">
                <option value="">-- Select --</option>
                <option value="Moisture check">Moisture check</option>
                <option value="Irrigation audit">Irrigation audit</option>
                <option value="Equipment maintenance">Equipment maintenance</option>
                <option value="Fertilizer injection">Fertilizer injection</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Follow-up schedule (optional)</label>
              <input type="date" class="form-control" id="followUpDate">
            </div>
          </div>

          <div class="text-center mt-4">
            <button type="button" id="continueBtn" class="btn btn-primary px-5 py-2">Continue</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const base = window.location.origin;
  const storageKey = 'irrigationTaskDetails';

  const fieldIdInput      = document.getElementById('fieldIdHidden');
  const fieldNameDisplay  = document.getElementById('fieldNameDisplay');
  const irrigationDateEl  = document.getElementById('irrigationDateDisplay');
  const irrigationTypeSel = document.getElementById('irrigationType');
  const waterSourceSel    = document.getElementById('waterSource');
  const durationValInput  = document.getElementById('durationValue');
  const durationUnitSel   = document.getElementById('durationUnit');
  const frequencyInput    = document.getElementById('frequency');
  const equipmentInput    = document.getElementById('equipment');
  const waterVolumeInput  = document.getElementById('waterVolume');
  const volumeUnitSel     = document.getElementById('volumeUnit');
  const soilConditionInput= document.getElementById('soilCondition');
  const instructionsInput = document.getElementById('instructions');
  const notesInput        = document.getElementById('additionalNotes');
  const followUpPlanSel   = document.getElementById('followUpPlan');
  const followUpDateInput = document.getElementById('followUpDate');
  const continueBtn       = document.getElementById('continueBtn');

  const saved = loadJSON(storageKey);
  const selectedFields = normalizeFieldSelection(loadJSON('selectedFields'));
  const scheduledDate  = resolveScheduledDate();

  if (!selectedFields.length) {
    alert('Please select a field before preparing the irrigation task.');
    return redirectToTasks();
  }

  fillScheduledDate(scheduledDate);
  init().catch(err => {
    console.error(err);
    alert('Unable to load irrigation details. Returning to tasks.');
    redirectToTasks();
  });

  continueBtn?.addEventListener('click', () => {
    if (!fieldIdInput?.value) {
      alert('Field information is missing.');
      return;
    }
    if (!irrigationTypeSel?.value) {
      alert('Choose an irrigation type.');
      return;
    }
    if (!waterSourceSel?.value) {
      alert('Select a water source.');
      return;
    }

    const payload = {
      fieldId: fieldIdInput.value,
      fieldName: fieldNameDisplay?.value || '',
      irrigationDate: scheduledDate,
      irrigationType: irrigationTypeSel.value,
      waterSource: waterSourceSel.value,
      durationValue: durationValInput?.value || '',
      durationUnit: durationUnitSel?.value || '',
      frequency: frequencyInput?.value || '',
      equipment: equipmentInput?.value || '',
      waterVolume: waterVolumeInput?.value || '',
      volumeUnit: volumeUnitSel?.value || '',
      soilCondition: soilConditionInput?.value || '',
      instructions: instructionsInput?.value || '',
      additionalNotes: notesInput?.value || '',
      followUpPlan: followUpPlanSel?.value || '',
      followUpDate: followUpDateInput?.value || ''
    };

    localStorage.setItem(storageKey, JSON.stringify(payload));
    window.location.href = `${base}/layout.php?page=assign_farmer`;
  });

  async function init() {
    const preferredId = (saved?.fieldId || '').toString();
    let activeField = preferredId
      ? selectedFields.find(f => f.id === preferredId)
      : null;
    if (!activeField) activeField = selectedFields[0];

    fieldIdInput.value = activeField.id;
    await hydrateFieldName(activeField.id);

    applySaved(saved);
  }

  function applySaved(data) {
    if (!data) return;
    irrigationTypeSel && (irrigationTypeSel.value = data.irrigationType || '');
    waterSourceSel && (waterSourceSel.value = data.waterSource || '');
    durationValInput && (durationValInput.value = data.durationValue || '');
    durationUnitSel && (durationUnitSel.value = data.durationUnit || 'hours');
    frequencyInput && (frequencyInput.value = data.frequency || '');
    equipmentInput && (equipmentInput.value = data.equipment || '');
    waterVolumeInput && (waterVolumeInput.value = data.waterVolume || '');
    volumeUnitSel && (volumeUnitSel.value = data.volumeUnit || 'm3');
    soilConditionInput && (soilConditionInput.value = data.soilCondition || '');
    instructionsInput && (instructionsInput.value = data.instructions || '');
    notesInput && (notesInput.value = data.additionalNotes || '');
    followUpPlanSel && (followUpPlanSel.value = data.followUpPlan || '');
    followUpDateInput && (followUpDateInput.value = data.followUpDate || '');
  }

  function fillScheduledDate(dateStr) {
    if (!irrigationDateEl) return;
    if (!dateStr) {
      irrigationDateEl.value = 'Not scheduled';
      return;
    }
    const date = new Date(dateStr + 'T00:00:00');
    irrigationDateEl.value = Number.isNaN(date.getTime())
      ? 'Not scheduled'
      : date.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
  }

  async function hydrateFieldName(fieldId) {
    if (!fieldNameDisplay) return;
    fieldNameDisplay.value = 'Loading...';
    try {
      const fields = await getFieldList();
      const match = fields.find(f => String(f.field_id) === String(fieldId));
      fieldNameDisplay.value = match?.name || `Field #${fieldId}`;
    } catch (err) {
      fieldNameDisplay.value = `Field #${fieldId}`;
    }
  }

  function resolveScheduledDate() {
    return normalizeIso(localStorage.getItem('selectedDate'))
        || normalizeIso(localStorage.getItem('taskScheduledDate'))
        || normalizeIso(localStorage.getItem('taskIrrigationDate'))
        || '';
  }

  function redirectToTasks() {
    window.location.href = `${base}/layout.php?page=tasks`;
  }

  async function getFieldList() {
    if (getFieldList.cache) return getFieldList.cache;
    getFieldList.cache = fetch(`${base}/backend/api/map/get_fields.php`)
      .then(r => r.json())
      .then(arr => Array.isArray(arr) ? arr : [])
      .catch(() => []);
    return getFieldList.cache;
  }

  function normalizeFieldSelection(raw) {
    if (!Array.isArray(raw)) return [];
    return raw.map(item => {
      if (typeof item === 'object' && item) {
        const id = item.field_id ?? item.fieldId ?? item.id;
        if (!id) return null;
        return { id: String(id), name: item.name ?? item.field_name ?? '' };
      }
      const id = String(item || '').trim();
      return id ? { id, name: '' } : null;
    }).filter(Boolean);
  }

  function loadJSON(key) {
    try { return JSON.parse(localStorage.getItem(key) || 'null'); }
    catch { return null; }
  }

  function normalizeIso(val) {
    if (!val) return '';
    const trimmed = String(val).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return trimmed;
    const d = new Date(trimmed);
    if (Number.isNaN(d.getTime())) return '';
    return d.toISOString().slice(0, 10);
  }
})();
</script>