<?php
// filepath: c:\xampp\htdocs\Agrilink\pages\tasks.php
include 'backend/db_connect.php';

// Fetch all field tasks with details
$tasksQuery = "
  SELECT 
    ft.field_task_id,
    ft.task_id,
    ft.field_id,
    ft.assigned_farmer_id,
    ft.start_date,
    ft.end_date,
    ft.status,
    ft.notes,
    ft.details,
    t.task_name,
    t.description as task_description,
    t.icon,
    t.category,
    f.name as field_name,
    f.area,
    fr.farmer_name,
    GROUP_CONCAT(DISTINCT c.crop_name SEPARATOR ', ') as crop_name
  FROM field_tasks ft
  JOIN tasks t ON ft.task_id = t.task_id
  LEFT JOIN fields f ON ft.field_id = f.field_id
  LEFT JOIN farmers fr ON ft.assigned_farmer_id = fr.farmer_id
  LEFT JOIN field_crops fc ON ft.field_id = fc.field_id
  LEFT JOIN crops c ON fc.crop_id = c.crop_id
  GROUP BY ft.field_task_id
  ORDER BY ft.start_date DESC, t.task_name
";
$tasksResult = $conn->query($tasksQuery);
$assignedTasks = [];
if ($tasksResult) {
  while ($row = $tasksResult->fetch_assoc()) {
    $assignedTasks[] = $row;
  }
}

// Fetch task types for stepper
$taskTypes = [];
$sql = "SELECT task_id, task_name, description, icon, category FROM tasks ORDER BY task_name";
$res = $conn->query($sql);
if ($res) {
  while ($r = $res->fetch_assoc()) {
    $taskTypes[] = $r;
  }
}
?>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
  body { background:#f7faf7; }
  .task-list-card {
    background: white;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    padding: 20px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
  }
  .task-list-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }
  .task-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 8px;
  }
  .badge-planting { background: #d1f0d1; color: #0f5132; }
  .badge-cleaning { background: #cfe2ff; color: #084298; }
  .badge-fertilizing { background: #fff3cd; color: #664d03; }
  .badge-harvest { background: #f8d7da; color: #842029; }
  .badge-pest { background: #e2d9f3; color: #432874; }
  .badge-watering { background: #d1ecf1; color: #0c5460; }
  .badge-default { background: #e9ecef; color: #495057; }

  .status-badge {
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
  }
  .status-pending { background: #fff3cd; color: #664d03; }
  .status-in-progress { background: #cfe2ff; color: #084298; }
  .status-completed { background: #d1e7dd; color: #0f5132; }
  .status-abandoned { background: #f8d7da; color: #842029; }

  #stepperSection { display: none; }
  .step { display: none; }
  .step.active { display: block; }
  .task-card {
    background: white;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    padding: 18px;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
    text-align: center;
    height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }
  .task-card:hover { transform: translateY(-6px); box-shadow: 0 6px 18px rgba(20,20,20,0.06); }
  .task-icon { font-size: 26px; margin-bottom: 8px; }
  .task-name { font-weight: 600; font-size: 14px; color: #1f2937; }
  .field-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    cursor: pointer;
    transition: transform 0.12s ease, box-shadow 0.12s ease;
    text-align: center;
  }
  .field-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
  .field-card.selected { border-color: #198754; background: #f8fff9; }
</style>

<div class="container py-4">
  <!-- Task List View (Shows first) -->
  <div id="taskListView">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="m-0 text-success"><i class="bi bi-clipboard2-check me-2"></i> Field Task Manager</h2>
      <button class="btn btn-success" onclick="showAddTaskStepper()">
        <i class="bi bi-plus-circle"></i> Add Task
      </button>
    </div>
    <?php if (empty($assignedTasks)): ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No tasks yet. Click "Add Task" to create one.
      </div>
    <?php else: ?>
      <div id="taskListContainer">
        <?php foreach ($assignedTasks as $task): 
          $category = strtolower($task['category'] ?? 'default');
          $badgeClass = 'badge-' . $category;
          $status = $task['status'] ?? 'pending';
          $statusClass = 'status-' . str_replace(' ', '-', strtolower($status));
        ?>
          <div class="task-list-card" onclick="showTaskDetails(<?= $task['field_task_id'] ?>)">
            <div class="d-flex justify-content-between align-items-start">
              <div class="flex-grow-1">
                <div class="mb-2">
                  <span class="task-badge <?= $badgeClass ?>">
                    <?= htmlspecialchars($task['category'] ?? 'General') ?>
                  </span>
                  <span class="status-badge <?= $statusClass ?>">
                    <?= htmlspecialchars(ucfirst($status)) ?>
                  </span>
                </div>
                <h5 class="mb-1">
                  <span style="font-size: 20px;"><?= htmlspecialchars($task['icon'] ?? '📋') ?></span>
                  <?= htmlspecialchars($task['task_name']) ?>
                </h5>
                <p class="text-muted mb-2 small">
                  <?= htmlspecialchars($task['task_description'] ?? 'No description') ?>
                </p>
                <div class="small text-secondary">
                  <i class="bi bi-person"></i> <?= htmlspecialchars($task['farmer_name'] ?? 'Unassigned') ?>
                  <span class="mx-2">•</span>
                  <i class="bi bi-calendar"></i> <?= $task['start_date'] ? date('M d, Y', strtotime($task['start_date'])) : 'No date' ?>
                  <?php if ($task['field_name']): ?>
                    <span class="mx-2">•</span>
                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($task['field_name']) ?>
                  <?php endif; ?>
                  <?php if ($task['crop_name']): ?>
                    <span class="mx-2">•</span>
                    <i class="bi bi-flower1"></i> <?= htmlspecialchars($task['crop_name']) ?>
                  <?php endif; ?>
                </div>
              </div>
              <div>
                <i class="bi bi-chevron-right text-muted"></i>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <!-- Add Task Stepper (Hidden by default) -->
  <div id="stepperSection" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="hideAddTaskStepper()">
          <i class="bi bi-x-lg"></i> Cancel
        </button>
        <h2 class="m-0 text-success"><i class="bi bi-clipboard-plus me-2"></i> Add Task</h2>
      </div>
    </div>

    <!-- Stepper Progress -->
    <div class="mb-4">
      <div class="progress" style="height:8px;">
        <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width:33%"></div>
      </div>
      <div class="d-flex justify-content-between mt-2">
        <small class="text-muted">1) Choose Task</small>
        <small class="text-muted">2) Pick Date</small>
        <small class="text-muted">3) Select Field</small>
      </div>
    </div>

    <!-- STEP 1: Task Cards -->
    <div id="step1" class="step active">
      <h5 class="mb-3">Select task type</h5>
      <div class="row g-3" id="taskGrid">
        <?php if (empty($taskTypes)): ?>
          <div class="col-12"><div class="alert alert-warning">No task types found.</div></div>
        <?php else: ?>
          <?php foreach ($taskTypes as $t): 
            $icon = $t['icon'] ?: '🌾';
            $desc = htmlspecialchars($t['description']);
            $name = htmlspecialchars($t['task_name']);
            $tid = (int)$t['task_id'];
          ?>
            <div class="col-6 col-md-4 col-lg-3">
              <div class="task-card" data-taskid="<?= $tid ?>" data-taskname="<?= $name ?>" onclick="chooseTask(this)">
                <div class="task-icon"><?= htmlspecialchars($icon) ?></div>
                <div class="task-name"><?= $name ?></div>
                <?php if (!empty($desc)): ?>
                  <small class="text-muted mt-1"><?= strlen($desc) > 60 ? substr($desc,0,57).'...' : $desc ?></small>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- STEP 2: Calendar -->
    <div id="step2" class="step">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-outline-secondary btn-sm" onclick="goBackToStep(1)">&larr; Back</button>
        <div>
          <span class="text-muted">Selected task:</span>
          <span id="selectedTaskName" class="fw-semibold ms-2"></span>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-8 mb-3">
          <div id="calendar"></div>
        </div>
        <div class="col-lg-4">
          <div class="card">
            <div class="card-body">
              <h6 class="card-title">Selected Date</h6>
              <p id="selectedDateDisplay" class="fw-semibold text-success">— none —</p>
              <div class="mb-3">
                <label class="form-label">Optional time</label>
                <input type="time" id="selectedTime" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Notes (optional)</label>
                <textarea id="taskNotes" class="form-control" rows="3"></textarea>
              </div>
              <div class="d-grid gap-2">
                <button id="continueBtn" class="btn btn-success" disabled onclick="continueToNext()">Continue</button>
                <button class="btn btn-outline-secondary" onclick="goBackToStep(1)">Choose different task</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 3: Select Field -->
    <div id="step3" class="step">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-outline-secondary btn-sm" onclick="goBackToStep(2)">&larr; Back</button>
        <div>
          <span class="text-muted">Selected task:</span>
          <span id="selectedTaskNameStep3" class="fw-semibold ms-2"></span>
          <span class="text-muted ms-3">Date:</span>
          <span id="selectedDateStep3" class="fw-semibold ms-2"></span>
        </div>
      </div>
      <div class="container py-5">
        <h2 class="text-center mb-4 text-success"><i class="bi bi-geo-alt"></i> Select Fields</h2>
        <p class="text-muted text-center mb-4">Choose one or more fields where this task will be applied.</p>
        <div class="row g-3" id="fieldList"></div>
        <div class="text-center mt-4">
          <button id="continueBtnStep3" class="btn btn-success px-4 py-2">Continue</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Task Details Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTaskName"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <strong>Category:</strong>
          <span id="modalCategory" class="task-badge"></span>
        </div>
        <div class="mb-3">
          <strong>Status:</strong>
          <span id="modalStatus" class="status-badge"></span>
        </div>
        <div class="mb-3">
          <strong><i class="bi bi-person"></i> Assigned to:</strong>
          <span id="modalAssignee"></span>
        </div>
        <div class="mb-3">
          <strong><i class="bi bi-calendar"></i> Start Date:</strong>
          <span id="modalStartDate"></span>
        </div>
        <div class="mb-3">
          <strong><i class="bi bi-calendar-check"></i> End Date:</strong>
          <span id="modalEndDate"></span>
        </div>
        <div class="mb-3">
          <strong><i class="bi bi-geo-alt"></i> Field:</strong>
          <span id="modalField"></span>
        </div>
        <div class="mb-3">
          <strong><i class="bi bi-flower1"></i> Crop:</strong>
          <span id="modalCrop"></span>
        </div>
        <div class="mb-3">
          <strong><i class="bi bi-card-text"></i> Notes:</strong>
          <p id="modalNotes" class="text-muted small mb-0"></p>
        </div>
        <div class="mb-3">
          <strong><i class="bi bi-info-circle"></i> Details:</strong>
          <p id="modalDetails" class="text-muted small mb-0"></p>
        </div>
      </div>
      <div class="modal-footer d-flex flex-column">
        <button type="button" class="btn btn-link text-danger btn-sm" id="deleteTaskBtn">Delete Task</button>
        <div class="d-flex gap-2 w-100">
          <button type="button" class="btn btn-outline-danger flex-fill" id="abandonTaskBtn">Abandon</button>
          <button type="button" class="btn btn-success flex-fill" id="completeTaskBtn">Mark Complete</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  let selectedTask = null;
  let selectedDate = null;
  let selectedTime = '';
  let selectedNotes = '';
  let fcCalendar = null;
  let fcRendered = false;
  let currentTaskId = null;

  function showAddTaskStepper() {
    document.getElementById('taskListView').style.display = 'none';
    document.getElementById('stepperSection').style.display = 'block';
    goToStep(1);
  }

  function hideAddTaskStepper() {
    document.getElementById('stepperSection').style.display = 'none';
    document.getElementById('taskListView').style.display = 'block';
  }

  function chooseTask(el) {
    const taskId = el.getAttribute('data-taskid');
    const taskName = el.getAttribute('data-taskname');
    selectedTask = { id: taskId, name: taskName };
    document.getElementById('selectedTaskName').textContent = taskName;
    goToStep(2);
  }

  function goToStep(n) {
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('step' + n).classList.add('active');
    const progress = {1: 33, 2: 66, 3: 100}[n] || 0;
    document.getElementById('progressBar').style.width = progress + '%';

    if (n === 3) {
      document.getElementById('selectedTaskNameStep3').textContent = selectedTask ? selectedTask.name : '';
      document.getElementById('selectedDateStep3').textContent = selectedDate || '';
      loadFields();
    }

    if (n === 2) {
      ensureCalendarInitialized();
      if (!fcRendered) {
        try {
          fcCalendar.render();
          fcRendered = true;
        } catch (err) {
          console.error('Error rendering calendar:', err);
        }
      } else {
        try { fcCalendar.updateSize(); } catch (e) {}
      }
    }
  }

  function goBackToStep(n) {
    if (n === 1) {
      selectedDate = null;
      selectedTime = '';
      selectedNotes = '';
      document.getElementById('selectedDateDisplay').textContent = '— none —';
      document.getElementById('continueBtn').disabled = true;
    }
    goToStep(n);
  }

  function ensureCalendarInitialized() {
    if (fcCalendar) return;
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') {
      console.error('Calendar element or FullCalendar library not found');
      return;
    }

    fcCalendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      dayMaxEvents: true,
      selectable: true,
      dateClick: function(info) {
        selectedDate = info.dateStr;
        document.getElementById('selectedDateDisplay').textContent = selectedDate;
        document.getElementById('continueBtn').disabled = false;
      },
      validRange: function(nowDate) {
        return { start: nowDate };
      }
    });
  }

  function continueToNext() {
    selectedTime = document.getElementById('selectedTime').value;
    selectedNotes = document.getElementById('taskNotes').value || '';
    goToStep(3);
  }

  function loadFields() {
    const base = window.location.origin + '/Agrilink';
    fetch(base + '/backend/api/map/get_fields.php')
      .then(response => response.json())
      .then(fields => {
        const fieldList = document.getElementById('fieldList');
        fieldList.innerHTML = '';

        fields.forEach(field => {
          const col = document.createElement('div');
          col.classList.add('col-md-3');
          col.innerHTML = `
            <div class="field-card" data-id="${field.field_id}">
              <h5>${field.name}</h5>
              <p class="text-muted mb-1">${field.type || 'Unknown Type'}</p>
              <small class="text-secondary">${field.area || 'N/A'} ha</small>
            </div>
          `;
          fieldList.appendChild(col);
        });

        document.querySelectorAll('.field-card').forEach(card => {
          card.addEventListener('click', () => card.classList.toggle('selected'));
        });

        document.getElementById('continueBtnStep3').addEventListener('click', () => {
          const selectedFields = Array.from(document.querySelectorAll('.field-card.selected')).map(c => c.dataset.id);
          if (selectedFields.length === 0) {
            alert('Please select at least one field.');
            return;
          }

          localStorage.setItem('selectedFields', JSON.stringify(selectedFields));
          localStorage.setItem('selectedFieldId', selectedFields[0]);
          localStorage.setItem('selectedTask', JSON.stringify(selectedTask));
          localStorage.setItem('selectedDate', selectedDate);
          localStorage.setItem('selectedTime', selectedTime);
          localStorage.setItem('selectedNotes', selectedNotes);

          const base = window.location.origin + '/Agrilink/layout.php?page=';
          const taskName = (selectedTask?.name || '').toLowerCase();
          let nextPage = base + 'assign_farmer';

          if (taskName.includes('clean')) nextPage = base + 'cleaning_task';
          else if (taskName.includes('plant')) nextPage = base + 'planting_task';
          else if (taskName.includes('fertiliz')) nextPage = base + 'fertilizing_task';
          else if (taskName.includes('harvest')) nextPage = base + 'harvest_task';
          else if (taskName.includes('pest')) nextPage = base + 'pest_control';

          localStorage.setItem('taskType', taskName);
          window.location.href = nextPage;
        });
      })
      .catch(error => console.error('Error loading fields:', error));
  }

  function showTaskDetails(fieldTaskId) {
    if (!fieldTaskId) return;
    currentTaskId = fieldTaskId;

    const base = window.location.origin + '/Agrilink';
    fetch(base + '/backend/api/tasks/get_field_task_details.php?id=' + fieldTaskId)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }

        document.getElementById('modalTaskName').textContent = data.task_name;
        document.getElementById('modalCategory').textContent = data.category || 'General';
        document.getElementById('modalCategory').className = 'task-badge badge-' + (data.category || 'default').toLowerCase();
        document.getElementById('modalStatus').textContent = data.status || 'Pending';
        document.getElementById('modalStatus').className = 'status-badge status-' + (data.status || 'pending').toLowerCase().replace(' ', '-');
        document.getElementById('modalAssignee').textContent = data.farmer_name || 'Unassigned';
        document.getElementById('modalStartDate').textContent = data.start_date || 'No date';
        document.getElementById('modalEndDate').textContent = data.end_date || 'N/A';
        document.getElementById('modalField').textContent = data.field_name || 'No field';
        document.getElementById('modalCrop').textContent = data.crop_name || 'N/A';
        document.getElementById('modalNotes').textContent = data.notes || 'No notes';
        
        // ✅ Use formatted details with HTML
        document.getElementById('modalDetails').innerHTML = data.details_formatted || 'No additional details';

        new bootstrap.Modal(document.getElementById('taskDetailModal')).show();
      })
      .catch(err => {
        console.error('Error loading task details:', err);
        alert('Failed to load task details');
      });
  }

  document.getElementById('deleteTaskBtn').addEventListener('click', () => {
    if (!confirm('Are you sure you want to delete this task?')) return;
    updateTaskStatus('deleted');
  });

  document.getElementById('abandonTaskBtn').addEventListener('click', () => {
    if (!confirm('Mark this task as abandoned?')) return;
    updateTaskStatus('abandoned');
  });

  document.getElementById('completeTaskBtn').addEventListener('click', () => {
    if (!confirm('Mark this task as complete?')) return;
    updateTaskStatus('completed');
  });

  function updateTaskStatus(status) {
    const base = window.location.origin + '/Agrilink';

    if (status === 'deleted') {
      return deleteTask(currentTaskId);
    }

    fetch(base + '/backend/api/tasks/update_field_task_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ field_task_id: currentTaskId, status: status })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert('Task updated successfully');
        location.reload();
      } else {
        alert('Error: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(err => {
      console.error('Error updating task:', err);
      alert('Failed to update task');
    });
  }

  function deleteTask(fieldTaskId) {
    const base = window.location.origin + '/Agrilink';
    return fetch(base + '/backend/api/tasks/delete_field_task.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ field_task_id: fieldTaskId })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert('Task deleted successfully');
        location.reload();
      } else {
        alert('Error: ' + (data.message || 'Failed to delete task'));
      }
    })
    .catch(err => {
      console.error('Error deleting task:', err);
      alert('Failed to delete task');
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    ensureCalendarInitialized();
  });

  // Call this when a field is selected in Step 2
  function onFieldChosen(field) {
    // field: { field_id, name }
    const selected = [{ id: String(field.field_id), name: field.name || '' }];
    localStorage.setItem('selectedFields', JSON.stringify(selected));
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>




