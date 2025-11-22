<?php
include_once 'backend/db_connect.php';
?>

<!-- PAGE WRAPPER -->
<div class="main-content p-4" style="min-height: 100vh; background-color: #f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 800px;">
      <div class="card-body p-4">
        <button class="btn btn-outline-secondary btn-sm mb-3" onclick="goBackToTasks()">&larr; Back</button>


        <h2 class="text-center mb-3 text-success"><i class="bi bi-brush"></i> Cleaning Task Details</h2>
        <p class="text-muted text-center mb-4">Tell us more about this cleaning activity.</p>

        <form id="cleaningForm">

          <!-- What needs to be cleaned -->
          <div class="mb-3">
            <label class="form-label fw-semibold">What needs to be cleaned? <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" id="cleanTarget" placeholder="e.g., Greenhouse walls, tools, machinery">
          </div>

          <!-- Cleaner or Sanitizer -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Will a cleaner or sanitizing agent be used?</label>
            <div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="useCleaner" id="useCleanerYes" value="yes">
                <label class="form-check-label" for="useCleanerYes">Yes</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="useCleaner" id="useCleanerNo" value="no">
                <label class="form-check-label" for="useCleanerNo">No</label>
              </div>
            </div>
          </div>

          <!-- Cleaner Details Section -->
          <div id="cleanerDetails" class="border rounded p-3 bg-light mb-3" style="display: none;">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Product</label>
                <input type="text" class="form-control" id="product" placeholder="e.g., Zonrox">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Supplier <small class="text-muted">(optional)</small></label>
                <input type="text" class="form-control" id="supplier" placeholder="Supplier name">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Quantity</label>
                <input type="number" class="form-control" id="quantity" placeholder="10">
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">Unit</label>
                <select class="form-select" id="unit">
                  <option value="L">L</option>
                  <option value="mL">mL</option>
                  <option value="kg">kg</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Estimated Water Usage -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Estimated water usage <small class="text-muted">(optional)</small></label>
            <div class="input-group">
              <input type="number" class="form-control" id="waterUsage" placeholder="e.g., 20">
              <span class="input-group-text">L</span>
            </div>
          </div>

          <!-- Additional notes -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Anything specific to add related to this task? <small class="text-muted">(optional)</small></label>
            <textarea class="form-control" id="specificNotes" rows="2" placeholder="Add any special details about this cleaning task..."></textarea>
          </div>

          <!-- Instructions for Assignee -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Add any instructions or specifics for the assignee</label>
            <textarea class="form-control" id="instructions" rows="2" placeholder="e.g., Wear gloves, clean early morning"></textarea>
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

<!-- TOGGLE SCRIPT -->
<script>
  const cleanerDetails = document.getElementById('cleanerDetails');
  document.getElementById('useCleanerYes').addEventListener('change', () => cleanerDetails.style.display = 'block');
  document.getElementById('useCleanerNo').addEventListener('change', () => cleanerDetails.style.display = 'none');

  document.getElementById('continueBtn').addEventListener('click', () => {
  const data = {
    cleanTarget: document.getElementById('cleanTarget').value.trim(),
    useCleaner: document.querySelector('input[name="useCleaner"]:checked')?.value || '',
    product: document.getElementById('product').value.trim(),
    supplier: document.getElementById('supplier').value.trim(),
    quantity: document.getElementById('quantity').value,
    unit: document.getElementById('unit').value,
    waterUsage: document.getElementById('waterUsage').value,
    specificNotes: document.getElementById('specificNotes').value.trim(),
    instructions: document.getElementById('instructions').value.trim(),
  };

  // save current step details temporarily
  localStorage.setItem('cleaningTaskDetails', JSON.stringify(data));

  // go to the next step — assign farmer
  const base = window.location.origin;
  window.location.href = `${base}/layout.php?page=assign_farmer`;

});


function goBackToTasks() {
  const base = window.location.origin;
window.location.href = `${base}/layout.php?page=tasks`;

}



</script>


