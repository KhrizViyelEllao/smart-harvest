<?php
include_once 'backend/db_connect.php';
?>

<!-- PAGE WRAPPER -->
<div class="main-content p-4" style="min-height: 100vh; background-color: #f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 800px;">
      <div class="card-body p-4">
        <!-- BACK BUTTON -->
        <button class="btn btn-outline-secondary btn-sm mb-3" onclick="goBackToTasks()">&larr; Back</button>

        <!-- HEADER -->
        <h2 class="text-center mb-3 text-success"><i class="bi bi-droplet-half"></i> Fertilizing Task</h2>
        <p class="text-muted text-center mb-4">
          Select which crops in your chosen field will be fertilized and fill in the details below.
        </p>

        <!-- CROPS SECTION -->
        <div class="mb-4">
          <h5 class="fw-bold mb-3 text-center">Select Crop(s) in Field</h5>
          <div id="cropList" class="row g-3 justify-content-center">
            <div class="text-center text-muted">Loading field crops...</div>
          </div>
        </div>

        <!-- FERTILIZING FORM -->
        <form id="fertilizingForm">
          <!-- Fertilizer Type -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Fertilizer Type</label>
            <input type="text" class="form-control" id="fertilizerType" placeholder="e.g., Urea, Organic Compost">
          </div>

          <!-- Application Method -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Application Method</label>
            <select class="form-select" id="applicationMethod">
              <option value="">Select method...</option>
              <option value="broadcast">Broadcast</option>
              <option value="side-dressing">Side Dressing</option>
              <option value="foliar">Foliar Spray</option>
              <option value="drip">Drip Irrigation</option>
            </select>
          </div>

          <!-- Amount and Unit -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Amount</label>
              <input type="number" class="form-control" id="fertilizerAmount" placeholder="e.g., 25">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Unit</label>
              <select class="form-select" id="fertilizerUnit">
                <option value="kg">kg</option>
                <option value="g">g</option>
                <option value="L">L</option>
                <option value="mL">mL</option>
              </select>
            </div>
          </div>

          <!-- Supplier -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Supplier <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" id="fertilizerSupplier" placeholder="Enter supplier name">
          </div>

          <!-- Frequency -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Application Frequency</label>
            <input type="text" class="form-control" id="applicationFrequency" placeholder="e.g., Weekly, every 3 days">
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Additional Notes <small class="text-muted">(optional)</small></label>
            <textarea class="form-control" id="fertilizerNotes" rows="2" placeholder="Add any special considerations or reminders..."></textarea>
          </div>

          <!-- Instructions -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Instructions for Assignee</label>
            <textarea class="form-control" id="fertilizerInstructions" rows="2" placeholder="e.g., Apply after watering, wear gloves"></textarea>
          </div>

          <!-- Continue Button -->
          <div class="text-center mt-4">
            <button type="button" id="continueBtn" class="btn btn-success px-5 py-2">Continue</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const selectedFieldId = localStorage.getItem('selectedFieldId');
  const selectedFieldName = localStorage.getItem('selectedFieldName');
  
  if (!selectedFieldId) {
    alert('No field selected. Please go back and choose a field first.');
    goBackToTasks();
    return;
  }

  loadCropsByField(selectedFieldId, selectedFieldName);
});

function goBackToTasks() {
const base = window.location.origin;
window.location.href = `${base}/layout.php?page=tasks`;

}

async function loadCropsByField(fieldId, fieldName) {
  const cropList = document.getElementById('cropList');
  cropList.innerHTML = '<div class="text-center text-muted">Loading crops from ' + fieldName + '...</div>';

  try {
    const res = await fetch('/backend/api/tasks/get_crops_by_field.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ field_id: fieldId })
    });

    const data = await res.json();

    if (!data.success || !data.data || data.data.length === 0) {
      cropList.innerHTML = '<div class="text-muted text-center">No crops found in this field.</div>';
      return;
    }

    cropList.innerHTML = '';
    data.data.forEach(crop => {
      const col = document.createElement('div');
      col.className = 'col-md-4';
      col.innerHTML = `
        <div class="crop-card p-3 border rounded text-center shadow-sm" data-id="${crop.crop_id}">
          <h6 class="mb-1 fw-semibold">${crop.crop_name}</h6>
          <small class="text-muted">${crop.variety || ''}</small>
        </div>
      `;
      cropList.appendChild(col);
    });

    document.querySelectorAll('.crop-card').forEach(card => {
      card.addEventListener('click', () => card.classList.toggle('selected'));
    });

  } catch (err) {
    console.error('Error loading crops:', err);
    cropList.innerHTML = '<div class="text-danger text-center">Error loading crops.</div>';
  }
}

document.getElementById('continueBtn').addEventListener('click', () => {
  const selectedCrops = [...document.querySelectorAll('.crop-card.selected')].map(c => c.dataset.id);

  if (selectedCrops.length === 0) {
    alert('Please select at least one crop to fertilize.');
    return;
  }

  const data = {
    fertilizerType: document.getElementById('fertilizerType').value.trim(),
    applicationMethod: document.getElementById('applicationMethod').value,
    fertilizerAmount: document.getElementById('fertilizerAmount').value,
    fertilizerUnit: document.getElementById('fertilizerUnit').value,
    fertilizerSupplier: document.getElementById('fertilizerSupplier').value.trim(),
    applicationFrequency: document.getElementById('applicationFrequency').value.trim(),
    fertilizerNotes: document.getElementById('fertilizerNotes').value.trim(),
    fertilizerInstructions: document.getElementById('fertilizerInstructions').value.trim(),
    crops: selectedCrops,
    field_id: localStorage.getItem('selectedFieldId')
  };

  // Save to localStorage for next step
  localStorage.setItem('fertilizingTaskDetails', JSON.stringify(data));
  localStorage.setItem('taskType', 'fertilizing');

  const base = window.location.origin;
window.location.href = `${base}/layout.php?page=assign_farmer`;

});
</script>

<!-- STYLES -->
<style>
.crop-card {
  cursor: pointer;
  transition: all 0.2s ease;
  background-color: #fff;
}
.crop-card.selected {
  border: 2px solid #198754;
  background-color: #e6ffee;
  box-shadow: 0 0 8px rgba(25, 135, 84, 0.3);
}
.crop-card:hover {
  transform: scale(1.03);
}
</style>
