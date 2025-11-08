<?php
include_once 'backend/db_connect.php'; // adjust path if needed


// Fetch all farmers from the database
$query = "SELECT * FROM farmers";
$result = $conn->query($query);
?>

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

  <script>
  // Save selected farmers
  document.getElementById('assignFarmerForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const selected = [...document.querySelectorAll('input[name="farmer_ids[]"]:checked')]
      .map(cb => cb.value);

    if (selected.length === 0) {
      alert('Please select at least one farmer.');
      return;
    }

    // Retrieve previously stored task or field data if needed
        const selectedFields = JSON.parse(localStorage.getItem('selectedFields') || '[]');
        const selectedTask = localStorage.getItem('selectedTask') || '';
        const taskType = localStorage.getItem('taskType') || '';

        // Include task-specific details
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
        }


        try { 
        const res = await fetch('/Agrilink/backend/api/save_field_task.php', {


          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            farmer_ids: selected,
            fields: selectedFields,
            task: selectedTask,
            task_type: taskType,
            details: taskDetails
          })
        });

        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();
        if (data.success) {
          alert('✅ Farmers successfully assigned!');
          window.location.href = '/Agrilink/layout.php?page=tasks'; // use routed Tasks page
        } else {
          alert('❌ Failed to save assignment.');
        }
      } catch (err) {
        console.error('Error:', err);
        alert('Error saving assignment.');
      }


  });
  </script>
