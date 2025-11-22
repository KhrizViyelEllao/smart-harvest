<?php
$displayName = $_SESSION['name']
  ?? $_SESSION['username']
  ?? $_SESSION['email']
  ?? 'Guest';

$role = $_SESSION['role'] ?? 'guest';

$roleIconClass = 'fas fa-user text-secondary';
if ($role === 'farmer')   $roleIconClass = 'fas fa-leaf text-success';
elseif ($role === 'admin') $roleIconClass = 'fas fa-shield-halved text-primary';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Harvest Dashboard</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="/assets/css/include.css" rel="stylesheet">
  <style>
    #notifBell {
      color: inherit;
      transition: color .2s ease, text-shadow .2s ease;
    }
    #notifBell:hover {
      color: #ffc107;
      text-shadow: 0 0 8px rgba(255,193,7,0.6);
    }
  </style>
</head>

<body>
<header class="modern-header">
  <nav class="header-nav d-flex justify-content-between align-items-center">

    <div class="logo-section">
      <a href="layout.php?page=<?= htmlspecialchars($_GET['page'] ?? 'dashboard') ?>" class="logo-link">
        <i class="fas fa-seedling me-2"></i> <span>PLantel</span>
      </a>
    </div>

    <div class="actions-section d-flex align-items-center">

      <div class="dropdown me-2">
        <button type="button"
                class="bg-transparent border-0 position-relative"
                id="notifBell"
                data-bs-toggle="dropdown"
                aria-expanded="false">
          <i class="fas fa-bell"></i>
          <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle d-none"
                id="notifCount">0</span>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow" id="notifList" style="min-width:320px;">
          <li class="dropdown-header d-flex justify-content-between">
            <span class="fw-semibold">Notifications</span>
            <button class="btn btn-link btn-sm p-0" id="notifRefresh">Refresh</button>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <div class="notif-items small p-2 text-center text-muted">Loading…</div>
          </li>
        </ul>
      </div>

      <div class="user-dropdown">
        <button class="user-btn d-flex align-items-center" onclick="toggleUserMenu()">
          <i class="fas fa-user-circle fa-lg me-2"></i>
          <span class="user-name me-2"><?= htmlspecialchars($displayName) ?></span>
          <span class="role-icon me-2"><i class="<?= $roleIconClass ?>"></i></span>
          <i class="fas fa-chevron-down ms-1"></i>
        </button>

        <div class="dropdown-menu" id="userMenu">
          <a href="layout.php?page=profile" class="dropdown-item"><i class="fas fa-user me-2"></i>Profile</a>
          <a href="layout.php?page=settings" class="dropdown-item"><i class="fas fa-cog me-2"></i>Settings</a>
          <div class="dropdown-divider"></div>
          <a href="/backend/auth/logout.php" class="dropdown-item">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
          </a>
        </div>
      </div>

      <button class="btn btn-modern-toggle position-fixed top-0 end-0 m-3"
              onclick="toggleSidebar()" id="sidebarToggle">
        <i class="fas fa-bars"></i>
      </button>

    </div>
  </nav>
</header>

<script src="/Agrilink/assets/js/include.js"></script>

<script>
// Notification bell logic – replace existing block with this version.
document.addEventListener('DOMContentLoaded', () => {
  const bellBtn    = document.getElementById('notifBell');
  const badgeEl    = document.getElementById('notifCount');
  const listBox    = document.querySelector('.notif-items');
  const refreshBtn = document.getElementById('notifRefresh');

  if (!bellBtn || !badgeEl || !listBox) {
    console.warn('[Notifications] Bell, badge, or list element missing.');
    return;
  }

  const API = {
    get: '/backend/functions/get_notifications.php',
    mark: '/backend/functions/mark_notifications_read.php'
  };

  const state = {
    items: [],
    loading: false,
    lastFetch: null
  };

  /**
   * Format timestamp strings into something human readable.
   */
  const formatTimestamp = (stamp) => {
    if (!stamp) return '';
    const parsed = new Date(stamp.replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) return stamp;
    return parsed.toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  /**
   * Convert notification type slugs into nicer labels.
   */
  const prettifyType = (type) => {
    if (!type) return 'General';
    return type.replace(/_/g, ' ').replace(/\b\w/g, ch => ch.toUpperCase());
  };

  /**
   * Sync the red badge with unread count.
   */
  const updateBadge = () => {
    const unread = state.items.filter(item => Number(item.is_read) === 0).length;
    if (unread > 0) {
      badgeEl.textContent = unread > 9 ? '9+' : unread;
      badgeEl.classList.remove('d-none');
    } else {
      badgeEl.textContent = '0';
      badgeEl.classList.add('d-none');
    }
  };

  /**
   * Fill dropdown with notification entries.
   */
  const renderList = () => {
    if (!state.items.length) {
      listBox.innerHTML = '<div class="text-muted py-3 text-center">No notifications.</div>';
      return;
    }

    listBox.innerHTML = state.items.map(item => `
      <div class="dropdown-item">
        <div class="small text-uppercase text-muted fw-semibold">${prettifyType(item.type)}</div>
        <div class="fw-semibold">${item.message}</div>
        <div class="text-muted small">${formatTimestamp(item.created_at)}</div>
      </div>
    `).join('');
  };

  /**
   * Fetch notifications from backend.
   */
  const fetchNotifications = async ({ showSpinner = false } = {}) => {
    if (state.loading) return;
    state.loading = true;

    if (showSpinner) {
      listBox.innerHTML = '<div class="text-muted py-3 text-center">Loading…</div>';
    }

    try {
      const response = await fetch(API.get, { cache: 'no-store' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      if (!Array.isArray(data)) throw new Error('Invalid payload structure');

      state.items = data;
      state.lastFetch = Date.now();

      updateBadge();
      renderList();

      // console.log('[Notifications] Loaded items:', data); // enable for debugging
    } catch (error) {
      console.error('[Notifications] Load failed:', error);
      listBox.innerHTML = '<div class="text-danger py-3 text-center">Unable to load notifications.</div>';
      badgeEl.classList.add('d-none');
      // alert('Failed to load notifications. Check console for details.'); // optional debug alert
    } finally {
      state.loading = false;
    }
  };

  /**
   * Mark every notification as read in backend.
   */
  const markAllRead = async () => {
    try {
      const response = await fetch(API.mark, { method: 'POST' });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();
      if (!json.success) throw new Error(json.message || 'Unknown error');

      state.items = state.items.map(item => ({ ...item, is_read: 1 }));
      updateBadge();
      renderList();

      // console.log('[Notifications] Marked all as read.'); // enable for debugging
    } catch (error) {
      console.error('[Notifications] Mark-read failed:', error);
      // alert('Could not mark notifications as read.'); // optional debug alert
    }
  };

  /**
   * Placeholder hook for future WebSocket/polling.
   */
  const initRealtimePlaceholder = () => {
    // setInterval(() => fetchNotifications(), 60000); // example polling
  };

  bellBtn.addEventListener('click', async () => {
    if (!state.items.length && !state.loading) {
      await fetchNotifications({ showSpinner: true });
    }
    await markAllRead();
  });

  refreshBtn?.addEventListener('click', async (event) => {
    event.preventDefault();
    event.stopPropagation();
    await fetchNotifications({ showSpinner: true });
  });

  fetchNotifications({ showSpinner: true });
  initRealtimePlaceholder();
});
</script>
</body>
</html>
