<?php

include 'backend/db_connect.php';

// Fetch all people (farmers + users: admin, consumer)
$peopleQuery = "
  SELECT 
    f.farmer_id as id,
    f.farmer_name as name,
    f.contact_number,
    f.email,
    f.address,
    'Farmer' as role,
    'active' as status,
    NULL as username,
    f.created_at
  FROM farmers f
  UNION ALL
  SELECT 
    u.user_id as id,
    u.name,
    u.contact_number,
    u.email,
    u.address,
    'Admin' as role,
    CASE WHEN u.is_active = 1 THEN 'active' ELSE 'inactive' END as status,
    u.username,
    u.created_at
  FROM users u
  WHERE u.role = 'admin'
  UNION ALL
  SELECT 
    u.user_id as id,
    u.name,
    u.contact_number,
    u.email,
    u.address,
    'Consumer' as role,
    CASE WHEN u.is_active = 1 THEN 'active' ELSE 'inactive' END as status,
    u.username,
    u.created_at
  FROM users u
  WHERE u.role = 'consumer'
  ORDER BY 
    CASE 
      WHEN role = 'Admin' THEN 1
      WHEN role = 'Farmer' THEN 2
      WHEN role = 'Consumer' THEN 3
      ELSE 4
    END,
    created_at DESC
";

$peopleResult = $conn->query($peopleQuery);
$people = [];
if ($peopleResult) {
  while ($row = $peopleResult->fetch_assoc()) {
    $people[] = $row;
  }
}
?>

<style>
  .people-card {
    background: white;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .people-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  .role-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
  }
  .role-admin { background: #dc3545; color: white; }
  .role-farmer { background: #198754; color: white; }
  .role-consumer { background: #0d6efd; color: white; } /* NEW */
  .status-badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
  }
  .status-active { background: #d1e7dd; color: #0f5132; }
  .status-inactive { background: #f8d7da; color: #842029; }
</style>

<div class="container py-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="mb-1 text-success"><i class="bi bi-people-gear me-2"></i> People Management</h3>
      <p class="text-muted mb-0">Manage admins and farmers</p>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPersonModal">
      <i class="bi bi-person-plus"></i> Add New Person
    </button>
  </div>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <?php
    $totalPeople = count($people);
    $activePeople = count(array_filter($people, fn($p) => $p['status'] === 'active'));
    $farmers = count(array_filter($people, fn($p) => $p['role'] === 'Farmer'));
    $admins = count(array_filter($people, fn($p) => $p['role'] === 'Admin'));
    $consumers = count(array_filter($people, fn($p) => $p['role'] === 'Consumer')); // NEW
    ?>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="text-muted mb-1">Total People</h6>
              <h3 class="mb-0"><?= $totalPeople ?></h3>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded">
              <i class="bi bi-people fs-4 text-primary"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="text-muted mb-1">Active</h6>
              <h3 class="mb-0"><?= $activePeople ?></h3>
            </div>
            <div class="bg-success bg-opacity-10 p-3 rounded">
              <i class="bi bi-check-circle fs-4 text-success"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="text-muted mb-1">Farmers</h6>
              <h3 class="mb-0"><?= $farmers ?></h3>
            </div>
            <div class="bg-success bg-opacity-10 p-3 rounded">
              <i class="bi bi-person-badge fs-4 text-success"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="text-muted mb-1">Admins</h6>
              <h3 class="mb-0"><?= $admins ?></h3>
            </div>
            <div class="bg-danger bg-opacity-10 p-3 rounded">
              <i class="bi bi-person-gear fs-4 text-danger"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="text-muted mb-1">Consumers</h6>
              <h3 class="mb-0"><?= $consumers ?></h3>
            </div>
            <div class="bg-primary bg-opacity-10 p-3 rounded">
              <i class="bi bi-bag fs-4 text-primary"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search by name or email...">
        </div>
        <div class="col-md-3">
          <select id="roleFilter" class="form-select">
            <option value="">All Roles</option>
            <option value="Farmer">Farmer</option>
            <option value="Admin">Admin</option>
            <option value="Consumer">Consumer</option> <!-- NEW -->
          </select>
        </div>
        <div class="col-md-3">
          <select id="statusFilter" class="form-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
            <i class="bi bi-arrow-clockwise"></i> Reset
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- People List -->
  <div class="row g-3" id="peopleList">
    <?php if (empty($people)): ?>
      <div class="col-12">
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> No people found. Click "Add New Person" to get started.
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($people as $person): ?>
        <div class="col-md-6 col-lg-4 person-item" 
             data-name="<?= strtolower(htmlspecialchars($person['name'])) ?>"
             data-email="<?= strtolower(htmlspecialchars($person['email'] ?? '')) ?>"
             data-role="<?= htmlspecialchars($person['role']) ?>"
             data-status="<?= htmlspecialchars($person['status']) ?>">
          <div class="people-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="d-flex align-items-center gap-2">
                <div class="bg-secondary bg-opacity-10 rounded-circle p-2">
                  <i class="bi bi-person fs-5 text-secondary"></i>
                </div>
                <div>
                  <h6 class="mb-0"><?= htmlspecialchars($person['name']) ?></h6>
                  <?php if ($person['username']): ?>
                    <small class="text-primary">@<?= htmlspecialchars($person['username']) ?></small>
                  <?php endif; ?>
                  <small class="text-muted d-block"><?= htmlspecialchars($person['email'] ?? 'No email') ?></small>
                </div>
              </div>
              <div class="dropdown">
                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <a class="dropdown-item" href="#" 
                       onclick="editPerson(<?= $person['id'] ?>, '<?= htmlspecialchars($person['role']) ?>')">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                  </li>
                  <?php if ($person['role'] === 'Admin' || $person['role'] === 'Consumer'): ?>
                  <li>
                    <a class="dropdown-item" href="#" 
                       onclick="resetPassword(<?= $person['id'] ?>)">
                      <i class="bi bi-key"></i> Reset Password
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="#" 
                       onclick="toggleStatus(<?= $person['id'] ?>, '<?= htmlspecialchars($person['role']) ?>', '<?= $person['status'] ?>')">
                      <i class="bi bi-toggle-on"></i> Toggle Status
                    </a>
                  </li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a class="dropdown-item text-danger" href="#" 
                       onclick="deletePerson(<?= $person['id'] ?>, '<?= htmlspecialchars($person['role']) ?>')">
                      <i class="bi bi-trash"></i> Remove
                    </a>
                  </li>
                </ul>
              </div>
            </div>
            
            <div class="mb-2">
              <span class="role-badge role-<?= strtolower($person['role']) ?>">
                <?= htmlspecialchars($person['role']) ?>
              </span>
              <span class="status-badge status-<?= $person['status'] ?> ms-2">
                <?= ucfirst($person['status']) ?>
              </span>
            </div>

            <div class="small text-muted">
              <?php if ($person['contact_number']): ?>
                <div><i class="bi bi-telephone"></i> <?= htmlspecialchars($person['contact_number']) ?></div>
              <?php endif; ?>
              <?php if ($person['address']): ?>
                <div><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($person['address']) ?></div>
              <?php endif; ?>
              <div><i class="bi bi-calendar"></i> Joined <?= date('M d, Y', strtotime($person['created_at'])) ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New Person</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addPersonForm">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Role <span class="text-danger">*</span></label>
            <select class="form-select" id="addRole" required>
              <option value="">-- Select Role --</option>
              <option value="farmer">Farmer</option>
              <option value="admin">Admin</option>
              <option value="consumer">Consumer</option> <!-- NEW -->
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="addName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" id="addEmail">
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="addContact">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea class="form-control" id="addAddress" rows="2"></textarea>
          </div>
          <!-- USERNAME FIELD (Only for Admin) -->
          <div class="mb-3" id="usernameField" style="display: none;">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="addUsername">
            <small class="text-muted">Used for login</small>
          </div>
          <!-- PASSWORD FIELD (Only for Admin) -->
          <div class="mb-3" id="passwordField" style="display: none;">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="addPassword" minlength="6">
            <small class="text-muted">Minimum 6 characters (will be hashed)</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Person</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Person Modal -->
<div class="modal fade" id="editPersonModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Person</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editPersonForm">
        <input type="hidden" id="editId">
        <input type="hidden" id="editRoleType">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editName" required>
          </div>
          <div class="mb-3" id="editUsernameField" style="display: none;">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editUsername">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" id="editEmail">
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="editContact">
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea class="form-control" id="editAddress" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-key"></i> Reset Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="resetPasswordForm">
        <input type="hidden" id="resetUserId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="newPassword" required minlength="6">
            <small class="text-muted">Minimum 6 characters</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="confirmPassword" required minlength="6">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Show username/password fields for Admin and Consumer
  document.getElementById('addRole').addEventListener('change', function() {
    const usernameField = document.getElementById('usernameField');
    const passwordField = document.getElementById('passwordField');
    if (this.value === 'admin' || this.value === 'consumer') {
      usernameField.style.display = 'block';
      passwordField.style.display = 'block';
      document.getElementById('addUsername').setAttribute('required', 'required');
      document.getElementById('addPassword').setAttribute('required', 'required');
    } else {
      usernameField.style.display = 'none';
      passwordField.style.display = 'none';
      document.getElementById('addUsername').removeAttribute('required');
      document.getElementById('addPassword').removeAttribute('required');
    }
  });

  // Add Person Form Submit
  document.getElementById('addPersonForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
      name: document.getElementById('addName').value,
      email: document.getElementById('addEmail').value,
      contact: document.getElementById('addContact').value,
      address: document.getElementById('addAddress').value,
      role: document.getElementById('addRole').value,
      username: document.getElementById('addUsername').value,
      password: document.getElementById('addPassword').value
    };

    try {
      const response = await fetch('/backend/api/settings/add_person.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      });

      const data = await response.json();
      
      if (data.success) {
        alert('✅ Person added successfully!');
        location.reload();
      } else {
        alert('❌ Error: ' + (data.message || 'Failed to add person'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('❌ An error occurred while adding the person');
    }
  });

  // Edit Person
  async function editPerson(id, role) {
    try {
      const response = await fetch(`/backend/api/settings/get_person.php?id=${id}&role=${role}`);
      const data = await response.json();

      if (data.success) {
        document.getElementById('editId').value = id;
        document.getElementById('editRoleType').value = role;
        document.getElementById('editName').value = data.person.name;
        document.getElementById('editEmail').value = data.person.email || '';
        document.getElementById('editContact').value = data.person.contact_number || '';
        document.getElementById('editAddress').value = data.person.address || '';

        // Show username field only for Admin or Consumer
        const usernameField = document.getElementById('editUsernameField');
        if ((role === 'Admin' || role === 'Consumer') && data.person.username) {
          usernameField.style.display = 'block';
          document.getElementById('editUsername').value = data.person.username;
        } else {
          usernameField.style.display = 'none';
        }

        new bootstrap.Modal(document.getElementById('editPersonModal')).show();
      } else {
        alert('❌ Failed to load person details');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('❌ An error occurred');
    }
  }

  // Edit Person Form Submit
  document.getElementById('editPersonForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
      id: document.getElementById('editId').value,
      role: document.getElementById('editRoleType').value,
      name: document.getElementById('editName').value,
      username: document.getElementById('editUsername').value,
      email: document.getElementById('editEmail').value,
      contact: document.getElementById('editContact').value,
      address: document.getElementById('editAddress').value
    };

    try {
      const response = await fetch('/backend/api/settings/update_person.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      });

      const data = await response.json();
      
      if (data.success) {
        alert('✅ Person updated successfully!');
        location.reload();
      } else {
        alert('❌ Error: ' + (data.message || 'Failed to update person'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('❌ An error occurred while updating');
    }
  });

  // Reset Password (Only for Admin)
  function resetPassword(userId) {
    document.getElementById('resetUserId').value = userId;
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
  }

  // Reset Password Form Submit
  document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
      alert('❌ Passwords do not match!');
      return;
    }

    try {
      const response = await fetch('/backend/api/settings/reset_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: document.getElementById('resetUserId').value,
          password: newPassword
        })
      });

      const data = await response.json();
      
      if (data.success) {
        alert('✅ Password reset successfully!');
        bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
        document.getElementById('resetPasswordForm').reset();
      } else {
        alert('❌ Error: ' + (data.message || 'Failed to reset password'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('❌ An error occurred');
    }
  });

  // Toggle Status (Only for Admin)
  async function toggleStatus(id, role, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    
    if (!confirm(`Change status to ${newStatus}?`)) return;

    try {
      const response = await fetch('/backend/api/settings/toggle_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, role, status: newStatus })
      });

      const data = await response.json();
      
      if (data.success) {
        alert('✅ Status updated successfully!');
        location.reload();
      } else {
        alert('❌ Failed to update status');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('❌ An error occurred');
    }
  }

  // Delete Person
  async function deletePerson(id, role) {
    if (!confirm('Are you sure you want to remove this person? This action cannot be undone.')) return;

    try {
      const response = await fetch('/backend/api/settings/delete_person.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, role })
      });

      const data = await response.json();
      
      if (data.success) {
        alert('✅ Person removed successfully!');
        location.reload();
      } else {
        alert('❌ Failed to remove person');
      }
    } catch (error) {
      console.error('Error:', error);
      alert('❌ An error occurred');
    }
  }

  // Search and Filter
  document.getElementById('searchInput').addEventListener('input', filterPeople);
  document.getElementById('roleFilter').addEventListener('change', filterPeople);
  document.getElementById('statusFilter').addEventListener('change', filterPeople);

  function filterPeople() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    document.querySelectorAll('.person-item').forEach(item => {
      const name = item.dataset.name;
      const email = item.dataset.email;
      const role = item.dataset.role;
      const status = item.dataset.status;

      const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
      const matchesRole = !roleFilter || role === roleFilter;
      const matchesStatus = !statusFilter || status === statusFilter;

      if (matchesSearch && matchesRole && matchesStatus) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }
    });
  }

  function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filterPeople();
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
