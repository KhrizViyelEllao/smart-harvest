<?php
include_once 'backend/db_connect.php'; // adjust path if needed

// Fetch all farmers from the database
$query = "SELECT * FROM farmers";
$result = $conn->query($query);
?>

<!-- (Optional) Font Awesome if not already globally included -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<div class="container py-4">
  <h2 class="text-success mb-4 text-center">👨‍🌾 Assign Farmer</h2>
  <p class="text-muted text-center mb-4">Select the farmers you want to assign to this task.</p>

  <form id="assignFarmerForm">
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle text-center">
        <thead class="table-success">
          <tr>
            <th>Select</th>
            <th>Farmer Name</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td>
                <input type="checkbox" name="farmer_ids[]" value="<?php echo $row['farmer_id']; ?>">
              </td>
              <td><?php echo htmlspecialchars($row['farmer_name']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="text-center mt-4">
      <button type="submit" class="btn btn-success px-4 py-2">Save</button>
    </div>
  </form>
</div>

<!-- Success Modal (styled like map.php) -->
<div class="modal fade" id="successAssignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Farmers Assigned</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-check-circle fa-3x text-success mb-3"></i>
        <div id="assignSuccessMsg">Farmers successfully assigned!</div>
      </div>
      <div class="modal-footer justify-content-center">
        <button id="goToTasksBtn" type="button" class="btn btn-success px-4">Go to Tasks</button>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('assignFarmerForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const selected = [...document.querySelectorAll('input[name="farmer_ids[]"]:checked')]
    .map(cb => cb.value);

  if (selected.length === 0) {
    alert('Please select at least one farmer.');
    return;
  }

  const selectedFields = JSON.parse(localStorage.getItem('selectedFields') || '[]');
  const selectedTask = localStorage.getItem('selectedTask') || '';
  const taskType = localStorage.getItem('taskType') || '';

  let taskDetails = {};
  if (taskType.includes('clean')) {
    taskDetails = JSON.parse(localStorage.getItem('cleaningTaskDetails') || '{}');
  } else if (taskType.includes('plant')) {
    taskDetails = JSON.parse(localStorage.getItem('plantingTaskDetails') || '{}');
  } else if (taskType.includes('harvest')) {
    taskDetails = JSON.parse(localStorage.getItem('harvestTaskDetails') || '{}');
  } else if (taskType.includes('fertilizing')) {
    taskDetails = JSON.parse(localStorage.getItem('fertilizingTaskDetails') || '{}');
  } else if (taskType.includes('pest_control')) {
    taskDetails = JSON.parse(localStorage.getItem('pestcontrolTaskDetails') || '{}');
  } else if (taskType.includes('planting')) {
    taskDetails = JSON.parse(localStorage.getItem('plantingTaskDetails') || '{}');
  } else if (taskType.includes('irrigation')) {
    taskDetails = JSON.parse(localStorage.getItem('irrigationTaskDetails') || '{}');
  } else if (taskType.includes('soil_sampling')) {
    taskDetails = JSON.parse(localStorage.getItem('soilsamplingTaskDetails') || '{}');
  } 

  const selectedFarmers = selected;

  const payload = {
    farmer_ids: selectedFarmers,
    fields: selectedFields,
    task: localStorage.getItem('selectedTask'),
    details: taskDetails,
    notes: localStorage.getItem('selectedNotes') || '',
    end_date: localStorage.getItem('taskEndDate') || '',
    end_time: localStorage.getItem('taskEndTime') || ''
  };

  try {
    const res = await fetch('/Agrilink/backend/api/save_field_task.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const data = await res.json();
    if (data.success) {
      const modalEl = document.getElementById('successAssignModal');
      const msgEl = document.getElementById('assignSuccessMsg');
      if (msgEl && data.message) msgEl.textContent = data.message;
      const modal = new bootstrap.Modal(modalEl);
      modal.show();

      document.getElementById('goToTasksBtn').onclick = () => {
        window.location.href = '/Agrilink/layout.php?page=tasks';
      };

      setTimeout(() => {
        if (modalEl.classList.contains('show')) {
          window.location.href = '/Agrilink/layout.php?page=tasks';
        }
      }, 4000);
    } else {
      alert('Failed to save assignment.');
    }
  } catch (err) {
    console.error('Error:', err);
    alert('Error saving assignment.');
  }
});
</script>
