<?php
include_once 'backend/db_connect.php';
?>

<!-- PAGE WRAPPER -->
<div class="main-content p-4" style="min-height: 100vh; background-color: #f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 800px;">
      <div class="card-body p-4">
        <button class="btn btn-outline-secondary btn-sm mb-3" onclick="goBackToTasks()">&larr; Back</button>

        <h2 class="text-center mb-3 text-success"><i class="bi bi-bug"></i> Pest Control Task Details</h2>
        <p class="text-muted text-center mb-4">Provide details about this pest control activity.</p>

        <form id="pestForm">

          <!-- Target Pests -->
          <div class="mb-3">
            <label class="form-label fw-semibold">What pest(s) are being targeted?</label>
            <input type="text" class="form-control" id="targetPests" placeholder="e.g., Aphids, Fruit flies, Caterpillars">
          </div>

          <!-- Method of Control -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Method of control</label>
            <select class="form-select" id="controlMethod">
              <option value="">-- Select method --</option>
              <option value="Chemical">Chemical Control</option>
              <option value="Biological">Biological Control</option>
              <option value="Cultural">Cultural Control</option>
              <option value="Mechanical">Mechanical/Physical Control</option>
              <option value="Integrated">Integrated Pest Management</option>
            </select>
          </div>

          <!-- Pesticide Use -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Will a pesticide or treatment be used?</label>
            <div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="usePesticide" id="usePesticideYes" value="yes">
                <label class="form-check-label" for="usePesticideYes">Yes</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="usePesticide" id="usePesticideNo" value="no">
                <label class="form-check-label" for="usePesticideNo">No</label>
              </div>
            </div>
          </div>

          <!-- Pesticide Details -->
          <div id="pesticideDetails" class="border rounded p-3 bg-light mb-3" style="display: none;">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Product name</label>
                <input type="text" class="form-control" id="pesticideProduct" placeholder="e.g., Malathion, Neem oil">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Supplier <small class="text-muted">(optional)</small></label>
                <input type="text" class="form-control" id="pesticideSupplier" placeholder="Supplier name">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">Quantity</label>
                <input type="number" class="form-control" id="pesticideQuantity" placeholder="10">
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">Unit</label>
                <select class="form-select" id="pesticideUnit">
                  <option value="L">L</option>
                  <option value="mL">mL</option>
                  <option value="kg">kg</option>
                  <option value="g">g</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Application method</label>
                <input type="text" class="form-control" id="applicationMethod" placeholder="e.g., Spraying, Drenching">
              </div>
            </div>
          </div>

          <!-- Weather Conditions -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Weather conditions <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" id="weatherCondition" placeholder="e.g., Cloudy, Light wind, No rain">
          </div>

          <!-- Safety Measures -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Safety measures or precautions</label>
            <textarea class="form-control" id="safetyMeasures" rows="2" placeholder="e.g., Wear gloves and mask, avoid spraying near water sources"></textarea>
          </div>

          <!-- Notes -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Anything specific to add related to this task? <small class="text-muted">(optional)</small></label>
            <textarea class="form-control" id="specificNotes" rows="2" placeholder="Add additional pest control details..."></textarea>
          </div>

          <!-- Instructions -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Instructions for the assignee</label>
            <textarea class="form-control" id="instructions" rows="2" placeholder="e.g., Apply early morning, check wind direction"></textarea>
          </div>

          <!-- Continue -->
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
  const pesticideDetails = document.getElementById('pesticideDetails');
  document.getElementById('usePesticideYes').addEventListener('change', () => pesticideDetails.style.display = 'block');
  document.getElementById('usePesticideNo').addEventListener('change', () => pesticideDetails.style.display = 'none');

  document.getElementById('continueBtn').addEventListener('click', () => {
    const data = {
      targetPests: document.getElementById('targetPests').value.trim(),
      controlMethod: document.getElementById('controlMethod').value,
      usePesticide: document.querySelector('input[name="usePesticide"]:checked')?.value || '',
      pesticideProduct: document.getElementById('pesticideProduct').value.trim(),
      pesticideSupplier: document.getElementById('pesticideSupplier').value.trim(),
      pesticideQuantity: document.getElementById('pesticideQuantity').value,
      pesticideUnit: document.getElementById('pesticideUnit').value,
      applicationMethod: document.getElementById('applicationMethod').value.trim(),
      weatherCondition: document.getElementById('weatherCondition').value.trim(),
      safetyMeasures: document.getElementById('safetyMeasures').value.trim(),
      specificNotes: document.getElementById('specificNotes').value.trim(),
      instructions: document.getElementById('instructions').value.trim(),
    };

    // Save temporarily in localStorage
    localStorage.setItem('pestControlTaskDetails', JSON.stringify(data));

    // Go to next step — assign farmer
    const base = window.location.origin;
    window.location.href = `${base}/layout.php?page=assign_farmer`;
  });

  function goBackToTasks() {
    const base = window.location.origin;
    window.location.href = `${base}/layout.php?page=tasks`;
  }
</script>
