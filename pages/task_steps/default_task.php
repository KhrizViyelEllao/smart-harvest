<?php
include_once 'backend/db_connect.php';
?>

<!-- PAGE WRAPPER -->
<div class="main-content p-4" style="min-height: 100vh; background-color: #f8f9fa;">
  <div class="container py-5">
    <div class="card shadow-sm mx-auto" style="max-width: 800px;">
      <div class="card-body p-4">

        <button class="btn btn-outline-secondary btn-sm mb-3" onclick="goBackToTasks()">&larr; Back</button>

        <h2 class="text-center mb-3 text-success"><i class="bi bi-list-task"></i> Task Details</h2>
        <p class="text-muted text-center mb-4">Tell us more about this task. Add any notes, requirements, or instructions for the assignee.</p>

        <form id="defaultTaskForm">

          <!-- Task Description -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Describe the task</label>
            <textarea class="form-control" id="taskDescription" rows="3" placeholder="e.g., Inspect crops for pest signs, prepare irrigation lines"></textarea>
          </div>

          <!-- Required Materials or Tools -->
          <div class="mb-3">
            <label class="form-label fw-semibold">List materials or tools needed <small class="text-muted">(optional)</small></label>
            <input type="text" class="form-control" id="materials" placeholder="e.g., gloves, shovel, sprayer">
          </div>

          <!-- Estimated Duration -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Estimated duration <small class="text-muted">(optional)</small></label>
            <div class="input-group">
              <input type="number" class="form-control" id="duration">
              <span class="input-group-text">hours</span>
            </div>
          </div>

          <!-- Additional Notes -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Anything specific to add related to this task? <small class="text-muted">(optional)</small></label>
            <textarea class="form-control" id="specificNotes" rows="2" placeholder="Add any special instructions or context..."></textarea>
          </div>

          <!-- Instructions for Assignee -->
          <div class="mt-3">
            <label class="form-label fw-semibold">Instructions for the assignee</label>
            <textarea class="form-control" id="instructions" rows="2" placeholder="e.g., Perform task early morning, notify supervisor once done"></textarea>
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
  document.getElementById('continueBtn').addEventListener('click', () => {
    const data = {
      taskDescription: document.getElementById('taskDescription').value.trim(),
      materials: document.getElementById('materials').value.trim(),
      duration: document.getElementById('duration').value,
      specificNotes: document.getElementById('specificNotes').value.trim(),
      instructions: document.getElementById('instructions').value.trim(),
    };

    // Validate
    if (!data.taskDescription) {
      alert('Please provide a short description of the task.');
      return;
    }

    // Save temporarily
    localStorage.setItem('defaultTaskDetails', JSON.stringify(data));

    // Proceed to assign farmer
    const base = window.location.origin;
window.location.href = `${base}/layout.php?page=assign_farmer`;

  });

  function goBackToTasks() {
    const base = window.location.origin;
window.location.href = `${base}/layout.php?page=tasks`;

  }
</script>
