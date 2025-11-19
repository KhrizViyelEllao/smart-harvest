<?php

include_once 'backend/db_connect.php';
?>
<div class="main-content p-4" style="min-height:100vh;background-color:#f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width:820px;">
      <div class="card-body p-4">
        <button type="button"
                class="btn btn-outline-secondary btn-sm mb-3"
                onclick="window.location.href='/Agrilink/layout.php?page=tasks'">
          &larr; Back to Tasks
        </button>

        <h2 class="text-center text-success mb-3">
          <i class="bi bi-flower3 me-2"></i>Soil Sampling Task Details
        </h2>
        <p class="text-muted text-center mb-4">
          Configure sampling depths, locations, and instructions for the crew.
        </p>

        <form id="soilSamplingForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Field</label>
              <input type="text" class="form-control" id="fieldNameDisplay" readonly>
              <input type="hidden" id="fieldIdHidden">
              <div class="form-text">Selected during scheduling.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Scheduled date</label>
              <input type="text" class="form-control" id="samplingDateDisplay" readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Sampling depth (cm)</label>
              <div class="input-group">
                <input type="number" class="form-control" id="depthStart" min="0" step="1" placeholder="e.g., 0">
                <span class="input-group-text">to</span>
                <input type="number" class="form-control" id="depthEnd" min="0" step="1" placeholder="e.g., 30">
              </div>
              <div class="form-text">Leave blank to specify in instructions.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Number of sampling points</label>
              <input type="number" class="form-control" id="samplePoints" min="1" step="1" placeholder="e.g., 10">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Sampling pattern</label>
              <select class="form-select" id="samplingPattern">
                <option value="">-- Select pattern --</option>
                <option value="Zig-zag">Zig-zag</option>
                <option value="Grid">Grid</option>
                <option value="Random">Random</option>
                <option value="Transect">Transect</option>
                <option value="Targeted zones">Targeted zones</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Sample container / bag ID</label>
              <input type="text" class="form-control" id="containerId" placeholder="e.g., Field-A-SS-01">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Equipment required</label>
              <input type="text" class="form-control" id="equipment"
                     placeholder="e.g., Auger, soil probe, clean bags">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Moisture condition</label>
              <select class="form-select" id="moistureCondition">
                <option value="">-- Select --</option>
                <option value="Dry">Dry</option>
                <option value="Slightly moist">Slightly moist</option>
                <option value="Moist">Moist</option>
                <option value="Wet">Wet</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Sampling area notes</label>
              <textarea class="form-control" id="samplingAreaNotes" rows="3"
                        placeholder="Specify zones, GPS landmarks, or special instructions"></textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Handling & transport instructions</label>
              <textarea class="form-control" id="handlingInstructions" rows="3"
                        placeholder="e.g., Keep samples shaded, deliver to lab within 6 hours."></textarea>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Additional remarks <small class="text-muted">(optional)</small></label>
              <textarea class="form-control" id="additionalNotes" rows="3"></textarea>
            </div>
          </div>

          <hr class="my-4">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Lab / test destination</label>
              <input type="text" class="form-control" id="labDestination" placeholder="e.g., Provincial Soil Lab">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Follow-up action date</label>
              <input type="date" class="form-control" id="followUpDate">
            </div>
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
  const base = window.location.origin + '/Agrilink';
  const storageKey = 'soilSamplingTaskDetails';

  const fieldIdInput        = document.getElementById('fieldIdHidden');
  const fieldNameDisplay    = document.getElementById('fieldNameDisplay');
  const samplingDateDisplay = document.getElementById('samplingDateDisplay');
  const depthStartInput     = document.getElementById('depthStart');
  const depthEndInput       = document.getElementById('depthEnd');
  const samplePointsInput   = document.getElementById('samplePoints');
  const samplingPatternSel  = document.getElementById('samplingPattern');
  const containerIdInput    = document.getElementById('containerId');
  const equipmentInput      = document.getElementById('equipment');
  const moistureSel         = document.getElementById('moistureCondition');
  const areaNotesInput      = document.getElementById('samplingAreaNotes');
  const handlingInput       = document.getElementById('handlingInstructions');
  const additionalNotes     = document.getElementById('additionalNotes');
  const labDestinationInput = document.getElementById('labDestination');
  const followUpDateInput   = document.getElementById('followUpDate');
  const continueBtn         = document.getElementById('continueBtn');

  const savedDetails   = loadJSON(storageKey);
  const selectedFields = normalizeFieldSelection(loadJSON('selectedFields'));
  const scheduledDate  = resolveScheduledDate();

  if (!selectedFields.length) {
    alert('Please select a field before preparing the soil sampling task.');
    return redirectToTasks();
  }

  fillScheduledDate(scheduledDate);
  init().catch(err => {
    console.error(err);
    alert('Unable to load soil sampling details. Returning to tasks.');
    redirectToTasks();
  });

  continueBtn?.addEventListener('click', () => {
    if (!fieldIdInput?.value) {
      alert('Field information is missing.');
      return;
    }
    if (!samplingPatternSel?.value) {
      alert('Select a sampling pattern.');
      return;
    }
    if (!equipmentInput?.value.trim()) {
      alert('Specify the required equipment.');
      return;
    }

    const payload = {
      fieldId: fieldIdInput.value,
      fieldName: fieldNameDisplay?.value || '',
      samplingDate: scheduledDate,
      depthStart: depthStartInput?.value || '',
      depthEnd: depthEndInput?.value || '',
      samplePoints: samplePointsInput?.value || '',
      samplingPattern: samplingPatternSel?.value || '',
      containerId: containerIdInput?.value || '',
      equipment: equipmentInput?.value || '',
      moistureCondition: moistureSel?.value || '',
      samplingAreaNotes: areaNotesInput?.value || '',
      handlingInstructions: handlingInput?.value || '',
      additionalNotes: additionalNotes?.value || '',
      labDestination: labDestinationInput?.value || '',
      followUpDate: followUpDateInput?.value || ''
    };

    localStorage.setItem(storageKey, JSON.stringify(payload));
    window.location.href = `${base}/layout.php?page=assign_farmer`;
  });

  async function init() {
    const preferredId = (savedDetails?.fieldId || '').toString();
    let activeField = preferredId
      ? selectedFields.find(f => f.id === preferredId)
      : null;
    if (!activeField) activeField = selectedFields[0];

    fieldIdInput.value = activeField.id;
    await hydrateFieldName(activeField.id);
    applySaved(savedDetails);
  }

  function applySaved(data) {
    if (!data) return;
    depthStartInput && (depthStartInput.value = data.depthStart || '');
    depthEndInput && (depthEndInput.value = data.depthEnd || '');
    samplePointsInput && (samplePointsInput.value = data.samplePoints || '');
    samplingPatternSel && (samplingPatternSel.value = data.samplingPattern || '');
    containerIdInput && (containerIdInput.value = data.containerId || '');
    equipmentInput && (equipmentInput.value = data.equipment || '');
    moistureSel && (moistureSel.value = data.moistureCondition || '');
    areaNotesInput && (areaNotesInput.value = data.samplingAreaNotes || '');
    handlingInput && (handlingInput.value = data.handlingInstructions || '');
    additionalNotes && (additionalNotes.value = data.additionalNotes || '');
    labDestinationInput && (labDestinationInput.value = data.labDestination || '');
    followUpDateInput && (followUpDateInput.value = data.followUpDate || '');
  }

  function fillScheduledDate(dateStr) {
    if (!samplingDateDisplay) return;
    if (!dateStr) {
      samplingDateDisplay.value = 'Not scheduled';
      return;
    }
    const d = new Date(dateStr + 'T00:00:00');
    samplingDateDisplay.value = Number.isNaN(d.getTime())
      ? 'Not scheduled'
      : d.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
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