<?php
// filepath: c:\xampp\htdocs\Agrilink\pages\harvest.php
include 'backend/db_connect.php';

$harvests = [];
$sql = "
  SELECT
    h.harvest_id,
    h.crop_id,
    h.field_id,
    h.field_task_id,
    h.harvest_date,
    h.predicted_yield_kg,
    h.actual_yield_kg,
    h.quality,
    h.notes,
    h.created_at,
    c.crop_name,
    f.name AS field_name,
    p.product_id,
    p.status AS product_status
  FROM harvests h
  LEFT JOIN crops c ON h.crop_id = c.crop_id
  LEFT JOIN field_tasks ft ON h.field_task_id = ft.field_task_id
  LEFT JOIN fields f ON h.field_id = f.field_id
  LEFT JOIN products p ON p.harvest_id = h.harvest_id
  ORDER BY h.harvest_date DESC, h.created_at DESC
";
if ($result = $conn->query($sql)) {
  while ($row = $result->fetch_assoc()) {
    $harvests[] = $row;
  }
  $result->free();
}

$metrics = [
  'predicted_total' => 0,
  'actual_total'    => 0,
  'pending_count'   => 0,
  'recorded_count'  => 0,
];
$cropSummary = [];
$cropNames = [];

foreach ($harvests as $item) {
  if ($item['predicted_yield_kg'] !== null) {
    $metrics['predicted_total'] += (float)$item['predicted_yield_kg'];
  }
  if ($item['actual_yield_kg'] !== null) {
    $metrics['actual_total'] += (float)$item['actual_yield_kg'];
    $metrics['recorded_count']++;
  } else {
    $metrics['pending_count']++;
  }

  $cropKey    = $item['crop_name'] ?: 'Unknown crop';
  $qualityKey = $item['quality'] ?: 'Unspecified';

  $cropNames[$cropKey] = true;

  if (!isset($cropSummary[$cropKey][$qualityKey])) {
    $cropSummary[$cropKey][$qualityKey] = ['predicted' => 0, 'actual' => 0, 'records' => 0];
  }

  if ($item['predicted_yield_kg'] !== null) {
    $cropSummary[$cropKey][$qualityKey]['predicted'] += (float)$item['predicted_yield_kg'];
  }
  if ($item['actual_yield_kg'] !== null) {
    $cropSummary[$cropKey][$qualityKey]['actual'] += (float)$item['actual_yield_kg'];
  }

  $cropSummary[$cropKey][$qualityKey]['records']++;
}

ksort($cropSummary);
$cropOptions = array_keys($cropNames);
sort($cropOptions);
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0 fw-semibold text-success">
      <i class="bi bi-basket2-fill me-2"></i>Harvest Log
    </h2>
    <span class="text-muted small">Track your crop yield and inventory</span>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="border rounded-3 px-3 py-3 bg-white">
        <p class="text-muted mb-1 small">Predicted total (kg)</p>
        <h4 class="mb-0 text-success"><?php echo number_format($metrics['predicted_total'], 2); ?></h4>
      </div>
    </div>
    <div class="col-md-3">
      <div class="border rounded-3 px-3 py-3 bg-white">
        <p class="text-muted mb-1 small">Actual recorded (kg)</p>
        <h4 class="mb-0 text-primary"><?php echo number_format($metrics['actual_total'], 2); ?></h4>
      </div>
    </div>
    <div class="col-md-3">
      <div class="border rounded-3 px-3 py-3 bg-white">
        <p class="text-muted mb-1 small">Pending entries</p>
        <h4 class="mb-0 text-warning"><?php echo $metrics['pending_count']; ?></h4>
      </div>
    </div>
    <div class="col-md-3">
      <div class="border rounded-3 px-3 py-3 bg-white">
        <p class="text-muted mb-1 small">Recorded entries</p>
        <h4 class="mb-0 text-success"><?php echo $metrics['recorded_count']; ?></h4>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
      <h6 class="mb-0 text-uppercase small text-muted">Inventory by crop</h6>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
          <thead class="table-light">
            <tr>
              <th>Crop</th>
              <th class="text-end">Predicted (kg)</th>
              <th class="text-end">Actual (kg)</th>
              <th class="text-end">Variance (kg)</th>
              <th class="text-end">Quality</th>
              <th class="text-end">Batches</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$cropSummary): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-3">No crops recorded.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($cropSummary as $crop => $qualities): ?>
                <?php ksort($qualities); ?>
                <?php foreach ($qualities as $quality => $data): ?>
                  <?php $variance = $data['actual'] - $data['predicted']; ?>
                  <tr>
                    <td><?php echo htmlspecialchars($crop); ?></td>
                    <td class="text-end"><?php echo number_format($data['predicted'], 2); ?></td>
                    <td class="text-end"><?php echo number_format($data['actual'], 2); ?></td>
                    <td class="text-end <?php echo $variance >= 0 ? 'text-success' : 'text-danger'; ?>">
                      <?php echo number_format($variance, 2); ?>
                    </td>
                    <td class="text-end"><?php echo htmlspecialchars($quality === 'Unspecified' ? '—' : ucfirst($quality)); ?></td>
                    <td class="text-end"><?php echo $data['records']; ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Filter by crop</label>
          <select id="filterCrop" class="form-select">
            <option value="">All crops</option>
            <?php foreach ($cropOptions as $crop): ?>
              <option value="<?php echo htmlspecialchars($crop); ?>"><?php echo htmlspecialchars($crop); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Filter by quality</label>
          <select id="filterQuality" class="form-select">
            <option value="">All quality grades</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small text-muted mb-1">Search notes / field</label>
          <input type="search" id="filterKeyword" class="form-control" placeholder="Type to filter…">
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Field</th>
              <th>Crop</th>
              <th>Predicted (kg)</th>
              <th>Actual (kg)</th>
              <th>Variance (kg)</th>
              <th>Quality</th>
              <th>Notes</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$harvests): ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-5">
                  No harvests recorded yet.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($harvests as $item): ?>
                <?php
                  $actualSet = $item['actual_yield_kg'] !== null && $item['actual_yield_kg'] !== '';
                  $statusBadge = $actualSet
                    ? '<span class="badge bg-success-subtle text-success">Recorded</span>'
                    : '<span class="badge bg-warning-subtle text-warning">Pending</span>';
                ?>
                <tr
                  data-harvest='<?php echo json_encode($item, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>'
                  data-crop="<?php echo htmlspecialchars(strtolower($item['crop_name'] ?? 'unknown'), ENT_QUOTES); ?>"
                  data-quality="<?php echo htmlspecialchars(strtolower($item['quality'] ?? ''), ENT_QUOTES); ?>"
                  data-text="<?php echo htmlspecialchars(strtolower(($item['notes'] ?? '') . ' ' . ($item['field_name'] ?? '') . ' ' . ($item['crop_name'] ?? '')), ENT_QUOTES); ?>"
                  data-ts="<?php echo strtotime($item['created_at'] ?? 'now'); ?>"
                >
                  <td>
                    <div><?php echo date('M d, Y', strtotime($item['harvest_date'])); ?></div>
                    <small class="text-muted"><?php echo date('g:i a · M d, Y', strtotime($item['created_at'])); ?></small>
                  </td>
                  <td><?php echo htmlspecialchars($item['field_name'] ?? '—'); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($item['crop_name'] ?? 'Unknown crop'); ?></div>
                    <small class="text-muted">Task #<?php echo (int)$item['field_task_id']; ?></small>
                  </td>
                  <td><?php echo $item['predicted_yield_kg'] !== null ? number_format($item['predicted_yield_kg'], 2) : '—'; ?></td>
                  <td>
                    <?php echo $item['actual_yield_kg'] !== null ? number_format($item['actual_yield_kg'], 2) : '—'; ?>
                    <div><?php echo $statusBadge; ?></div>
                  </td>
                  <td>
                    <?php
                      if ($item['predicted_yield_kg'] !== null && $item['actual_yield_kg'] !== null) {
                        $variance = $item['actual_yield_kg'] - $item['predicted_yield_kg'];
                        $class = $variance >= 0 ? 'text-success' : 'text-danger';
                        echo '<span class="' . $class . '">' . number_format($variance, 2) . '</span>';
                      } else {
                        echo '—';
                      }
                    ?>
                  </td>
                  <td><?php echo htmlspecialchars($item['quality'] ?: '—'); ?></td>
                  <td class="text-truncate" style="max-width: 220px;">
                    <?php echo htmlspecialchars($item['notes'] ?: '—'); ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <button class="btn btn-outline-success update-yield-btn">
                        <?php echo $actualSet ? 'Edit yield' : 'Log yield'; ?>
                      </button>

                      <?php if ($actualSet && empty($item['product_id'])): ?>
                        <button
                          type="button"
                          class="btn btn-outline-primary market-btn"
                          data-harvest-id="<?php echo (int)$item['harvest_id']; ?>"
                          data-default-name="<?php echo htmlspecialchars($item['crop_name'] ?? 'Product'); ?>"
                          data-default-qty="<?php echo htmlspecialchars($item['actual_yield_kg'] ?? ''); ?>"
                        >
                          Add to Market
                        </button>
                      <?php elseif ($actualSet && !empty($item['product_id'])): ?>
                        <span class="badge bg-<?php echo ($item['product_status']==='available'?'success':'secondary'); ?>">
                          In Market: <?php echo htmlspecialchars($item['product_status']); ?>
                        </span>
                        <a class="btn btn-outline-secondary" href="layout.php?page=market">Manage</a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="actualYieldModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="actualYieldForm">
      <div class="modal-header">
        <h5 class="modal-title">Update Actual Yield</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modalHarvestId">
        <p class="mb-1"><strong>Crop:</strong> <span id="modalCropName">—</span></p>
        <p class="text-muted small mb-3">
          Predicted yield: <span id="modalPredictedYield">—</span>
        </p>
        <div class="mb-3">
          <label class="form-label fw-semibold">Actual yield (kg)</label>
          <input type="number" class="form-control" id="modalActualYield" min="0" step="0.01" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Notes (optional)</label>
          <textarea class="form-control" id="modalNotes" rows="3" placeholder="Add remarks"></textarea>
        </div>
        <div class="alert alert-warning d-none" id="modalError"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="addMarketModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="marketForm" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Publish Harvest to Market</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="marketHarvestId" name="harvest_id">
        <div class="mb-3">
          <label class="form-label">Product Name</label>
          <input type="text" class="form-control" id="marketName" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Short Description</label>
          <textarea class="form-control" id="marketDescription" rows="2"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Price per kg (PHP)</label>
          <input type="number" step="0.01" min="0" class="form-control" id="marketPrice" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Available Quantity (kg)</label>
          <input type="number" step="0.01" min="0" class="form-control" id="marketQty" required>
          <div id="qtyHelp" class="form-text"></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Image (optional)</label>
          <input type="file" class="form-control" id="marketImage" accept="image/*">
        </div>
        <div class="alert alert-warning d-none" id="marketError"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit">Publish</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const harvestTableBody = document.querySelector('table.table-hover tbody');
  const harvestRows = Array.from(harvestTableBody.querySelectorAll('tr[data-harvest]')).sort((a, b) => {
    return (parseInt(b.dataset.ts || '0', 10) - parseInt(a.dataset.ts || '0', 10));
  });
  harvestRows.forEach(row => harvestTableBody.appendChild(row));

  const rows = harvestRows;

  const base = window.location.origin + '/Agrilink';
  const modalEl = document.getElementById('actualYieldModal');
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('actualYieldForm');
  const fieldHarvestId = document.getElementById('modalHarvestId');
  const fieldActual = document.getElementById('modalActualYield');
  const fieldNotes = document.getElementById('modalNotes');
  const labelCrop = document.getElementById('modalCropName');
  const labelPredicted = document.getElementById('modalPredictedYield');
  const errorBox = document.getElementById('modalError');

  document.querySelectorAll('.update-yield-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      if (!row) return;
      const payload = JSON.parse(row.dataset.harvest || '{}');
      fieldHarvestId.value = payload.harvest_id || '';
      fieldActual.value = payload.actual_yield_kg ?? '';
      fieldNotes.value = payload.notes ?? '';
      labelCrop.textContent = payload.crop_name || '—';
      labelPredicted.textContent = payload.predicted_yield_kg != null
        ? `${Number(payload.predicted_yield_kg).toLocaleString()} kg`
        : '—';
      errorBox.classList.add('d-none');
      modal.show();
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const harvestId = Number(fieldHarvestId.value);
    const actualValue = fieldActual.value.trim();
    if (!harvestId || actualValue === '') {
      showError('Invalid harvest data.');
      return;
    }

    try {
      const res = await fetch(`${base}/backend/api/harvests/update_actual_yield.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          harvest_id: harvestId,
          actual_yield_kg: Number(actualValue),
          notes: fieldNotes.value.trim()
        })
      });
      const json = await res.json();
      if (!json.success) {
        throw new Error(json.message || 'Failed to update harvest.');
      }

      modal.hide();
      window.location.reload();
    } catch (err) {
      showError(err.message || 'Unable to save actual yield. Please retry.');
    }
  });

  function showError(message) {
    errorBox.textContent = message;
    errorBox.classList.remove('d-none');
  }

  const filterCrop = document.getElementById('filterCrop');
  const filterQuality = document.getElementById('filterQuality');
  const filterKeyword = document.getElementById('filterKeyword');

  [filterCrop, filterQuality].forEach(ctrl => ctrl?.addEventListener('change', applyFilters));
  filterKeyword?.addEventListener('input', applyFilters);

  function applyFilters() {
    const cropValue = (filterCrop?.value || '').toLowerCase();
    const qualityValue = (filterQuality?.value || '').toLowerCase();
    const keyword = (filterKeyword?.value || '').toLowerCase();

    rows.forEach(row => {
      const matchesCrop = !cropValue || row.dataset.crop === cropValue;
      const matchesQuality = !qualityValue || row.dataset.quality === qualityValue;
      const matchesKeyword = !keyword || (row.dataset.text || '').includes(keyword);
      row.classList.toggle('d-none', !(matchesCrop && matchesQuality && matchesKeyword));
    });
  }

  const addMarketModalEl = document.getElementById('addMarketModal');
  const addMarketModal = new bootstrap.Modal(addMarketModalEl);
  const marketForm = document.getElementById('marketForm');
  const mHarvest = document.getElementById('marketHarvestId');
  const mName = document.getElementById('marketName');
  const mDesc = document.getElementById('marketDescription');
  const mPrice = document.getElementById('marketPrice');
  const mQty = document.getElementById('marketQty');
  const mImage = document.getElementById('marketImage');
  const mErr = document.getElementById('marketError');
  const qtyHelp = document.getElementById('qtyHelp');

  // Enforce max based on actual_yield_kg
  function validateQty() {
    const max = parseFloat(mQty.max || '0') || 0;
    const val = parseFloat(mQty.value || '0');
    let msg = '';
    if (isNaN(val) || val < 0) {
      msg = 'Enter a non-negative number.';
    } else if (val > max) {
      msg = `Cannot exceed ${max.toLocaleString()} kg.`;
    }
    mQty.setCustomValidity(msg);
    if (qtyHelp) {
      qtyHelp.textContent = max ? `Available from this harvest: ${max.toLocaleString()} kg (max).` : '';
    }
    return !msg;
  }
  mQty?.addEventListener('input', validateQty);

  document.querySelectorAll('.market-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      mHarvest.value = btn.dataset.harvestId || '';
      mName.value = btn.dataset.defaultName || 'Product';
      mDesc.value = '';
      mPrice.value = '';
      mQty.value = btn.dataset.defaultQty || '';
      mImage.value = '';
      mErr.classList.add('d-none');
      const maxQty = parseFloat(btn.dataset.defaultQty || '0') || 0;
      mQty.max = String(maxQty);
      // Optional: prefill with full available
      mQty.value = maxQty ? String(maxQty) : '';
      validateQty();
      addMarketModal.show();
    });
  });

  marketForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateQty()) {
      mQty.reportValidity();
      return;
    }
    const fd = new FormData();
    fd.append('harvest_id', mHarvest.value);
    fd.append('name', mName.value.trim());
    fd.append('description', mDesc.value.trim());
    fd.append('price_per_kg', mPrice.value);
    fd.append('available_qty', mQty.value);
    if (mImage.files[0]) fd.append('image', mImage.files[0]);

    try {
      const res = await fetch(`${base}/backend/api/products/store.php`, { method: 'POST', body: fd });
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Failed to publish');
      addMarketModal.hide();
      window.location.href = `${base}/layout.php?page=market`;
    } catch (err) {
      mErr.textContent = err.message || 'Publish failed';
      mErr.classList.remove('d-none');
    }
  });
});
</script>