<?php
session_start();
// Authorization disabled for this page
// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'consumer') {
//   header('Location: /Agrilink/index.php?login_error=' . urlencode('Login as consumer to access profile'));
//   exit;
// }
$base = '/Agrilink/pages';
$active = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - Smart Harvest</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Fonts + Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <!-- Bootstrap + Shared styles -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/Agrilink/assets/css/include.css" rel="stylesheet">
  <style>
    body { font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; }
  </style>
</head>
<body class="bg-light">
<?php require_once __DIR__ . '/../includes/consumer_nav.php'; ?>

<div class="container pb-5" style="max-width:900px;">
  <h4 class="mb-3 text-success fw-bold">My Profile</h4>
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6"><strong>Name:</strong> <span id="pfName"><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></span></div>
        <div class="col-md-6"><strong>Email:</strong> <span id="pfEmail"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></span></div>
        <div class="col-md-6"><strong>Contact:</strong> <span id="pfContact"><?php echo htmlspecialchars($_SESSION['contact'] ?? ''); ?></span></div>
        <div class="col-md-6"><strong>Address:</strong> <span id="pfAddress"><?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?></span></div>
      </div>
      <div class="mt-4 text-end">
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
          <i class="fa fa-pen me-1"></i>Edit Profile
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="profileForm" class="modal-content">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title">Update Profile</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="pfError" class="alert alert-danger d-none mb-2"></div>
        <div class="mb-2">
          <label class="form-label">Name</label>
          <input type="text" id="fName" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Email</label>
          <input type="email" id="fEmail" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Contact</label>
          <input type="text" id="fContact" class="form-control" value="<?php echo htmlspecialchars($_SESSION['contact'] ?? ''); ?>">
        </div>
        <div class="mb-2">
          <label class="form-label">Address</label>
          <input type="text" id="fAddress" class="form-control" value="<?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?>">
        </div>
        <hr>
        <div class="mb-2">
          <label class="form-label">New Password (optional)</label>
          <input type="password" id="fNewPw" class="form-control" minlength="6" placeholder="Leave blank to keep current">
        </div>
        <div class="mb-2">
          <label class="form-label">Current Password (required if changing password)</label>
          <input type="password" id="fCurrPw" class="form-control" placeholder="Required if changing password">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary btn-sm" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success btn-sm" type="submit">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="profileSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h6 class="modal-title">Update Successful</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-circle-check fa-3x text-success mb-3"></i>
        <div id="profileSuccessMsg">Profile updated.</div>
      </div>
    </div>
  </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="profileErrorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h6 class="modal-title">Update Failed</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <i class="fa fa-triangle-exclamation fa-3x text-danger mb-3"></i>
        <div id="profileErrorMsg">Unable to update profile.</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const base = location.origin + '/Agrilink';
const editProfileModalEl = document.getElementById('editProfileModal');
const profileForm = document.getElementById('profileForm');

function hideOpenModals(exceptId=null){
  document.querySelectorAll('.modal.show').forEach(m=>{
    if(exceptId && m.id===exceptId) return;
    (bootstrap.Modal.getInstance(m)||new bootstrap.Modal(m)).hide();
  });
  setTimeout(()=>{
    document.querySelectorAll('.modal-backdrop').forEach(b=>b.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
  },200);
}

function showProfileSuccess(message='Profile updated.'){
  document.getElementById('profileSuccessMsg').textContent = message;
  hideOpenModals('profileSuccessModal');
  new bootstrap.Modal(document.getElementById('profileSuccessModal')).show();
}

function showProfileError(message='Unable to update profile.'){
  document.getElementById('profileErrorMsg').textContent = message;
  hideOpenModals('profileErrorModal');
  new bootstrap.Modal(document.getElementById('profileErrorModal')).show();
}

profileForm.addEventListener('submit', async e=>{
  e.preventDefault();
  const err = document.getElementById('pfError');
  err.classList.add('d-none');
  const payload = {
    name: fName.value.trim(),
    email: fEmail.value.trim(),
    contact_number: fContact.value.trim(),
    address: fAddress.value.trim(),
    new_password: fNewPw.value,
    current_password: fCurrPw.value
  };
  if (!payload.name || !payload.email) {
    err.textContent = 'Name and Email required.';
    err.classList.remove('d-none');
    showProfileError('Name and Email are required.');
    return;
  }
  if (payload.new_password && payload.new_password.length < 6) {
    err.textContent = 'New password too short.';
    err.classList.remove('d-none');
    showProfileError('New password must be at least 6 characters.');
    return;
  }
  try {
    const res = await fetch(base + '/backend/api/profile/update.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const text = await res.text();
    let j;
    try { j = JSON.parse(text); } catch(parseErr) {
      throw new Error(text.replace(/<[^>]*>/g,'').trim() || 'Invalid server response');
    }
    if(!j.success) throw new Error(j.message||'Update failed');
    pfName.textContent = j.data.name;
    pfEmail.textContent = j.data.email;
    pfContact.textContent = j.data.contact_number || '';
    pfAddress.textContent = j.data.address || '';
    fNewPw.value = ''; fCurrPw.value = '';
    const modalInstance = bootstrap.Modal.getInstance(editProfileModalEl);
    if (modalInstance) modalInstance.hide();
    showProfileSuccess('Profile details updated successfully.');
  } catch(ex){
    err.textContent = ex.message;
    err.classList.remove('d-none');
    showProfileError(ex.message || 'Failed to update profile.');
  }
});
</script>
</body>
</html>