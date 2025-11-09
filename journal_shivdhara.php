<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
require 'db_config.php';

$TABLE = 'journal_shivdhara'; // shivdhara
// Fetch
$stmt = $conn->prepare("SELECT id, entry_date, details, amount FROM $TABLE ORDER BY entry_date DESC, id DESC");
$stmt->execute();
$res = $stmt->get_result();
$entries = $res->fetch_all(MYSQLI_ASSOC);
$total = array_sum(array_column($entries, 'amount'));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>General - Pending Payments | Krishna Pump</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style> /* same styles as before, trimmed for brevity */ body{background:#f4f5fa} .card{border:none;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.08)} .form-label{font-weight:600} .table thead th{background:#0dcaf0} .total-bar{background:#f8f9fa;font-weight:600;padding:10px;text-align:right;border-top:2px solid #0d6efd}</style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main" style="margin-left:250px;padding:20px;max-width:1000px;">
  <div class="mb-3 d-flex gap-2">
    <a href="journal.php" class="btn btn-outline-info">General</a>
    <a href="journal_hirani.php" class="btn btn-outline-info">Hirani</a>
    <a href="journal_shivdhara.php" class="btn btn-info">Shivdhara</a>
  </div>

  <div class="card mb-4">
    <div class="card-header bg-info"><h5 class="mb-0">🧾 Add or Edit Pending Payment (General)</h5></div>
    <div class="card-body">
      <form id="journalForm" class="row g-3" onsubmit="return false;">
        <input type="hidden" name="id" id="entryId">
        <input type="hidden" name="table" id="tableName" value="<?= htmlspecialchars($TABLE) ?>">
        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="entry_date" id="entryDate" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-7"><label class="form-label">Details</label><input type="text" name="details" id="details" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Amount (₹)</label><input type="number" name="amount" id="amount" step="0.01" class="form-control" required></div>
        <div class="col-12 text-end">
          <button type="button" class="btn btn-primary px-4" id="saveBtn">💾 Save Entry</button>
          <button type="button" class="btn btn-secondary px-4 d-none" id="cancelEdit">❌ Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">📋 Pending Payments (General)</h5>
      <span>Total Entries: <?= count($entries) ?></span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead><tr><th style="width:70px;">Sr</th><th>Date</th><th>Details</th><th class="text-end">Amount (₹)</th><th style="width:130px;">Actions</th></tr></thead>
          <tbody id="journalTableBody">
            <?php if ($entries): $i=1; foreach($entries as $e): ?>
              <tr data-id="<?= $e['id'] ?>">
                <td><?= $i++ ?></td>
                <td><?= date('d-m-Y', strtotime($e['entry_date'])) ?></td>
                <td><?= nl2br(htmlspecialchars($e['details'])) ?></td>
                <td class="text-end fw-bold text-danger"><?= number_format($e['amount'], 2) ?></td>
                <td class="text-center action-btns">
                  <button class="btn btn-sm btn-warning" onclick="editEntry(<?= $e['id'] ?>)">✏️</button>
                  <button class="btn btn-sm btn-danger" onclick="deleteEntry(<?= $e['id'] ?>)">🗑️</button>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No pending payments recorded yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="total-bar">Total Pending: ₹<span id="totalAmount"><?= number_format($total,2) ?></span></div>
    </div>
  </div>
</div>

<script>
const TABLE_NAME = document.getElementById('tableName').value;

// Save / Update
document.getElementById('saveBtn').addEventListener('click', async () => {
  const id = document.getElementById('entryId').value || '';
  const entry_date = document.getElementById('entryDate').value;
  const details = document.getElementById('details').value.trim();
  const amount = document.getElementById('amount').value;

  if (!entry_date || !details || !amount || parseFloat(amount) <= 0) {
    alert('Please fill valid details and amount.');
    return;
  }

  const payload = { id, entry_date, details, amount, table: TABLE_NAME };

  try {
    const res = await fetch('save_journal.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.status === 'success') window.location.reload();
    else alert('Save failed: ' + (data.message || 'Server error'));
  } catch (err) {
    alert('Request failed: ' + err.message);
  }
});

// Delete
async function deleteEntry(id) {
  // if (!confirm('Are you sure you want to delete this entry?')) return;
  try {
    const res = await fetch('delete_journal.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, table: TABLE_NAME })
    });
    const data = await res.json();
    if (data.status === 'success') {
      document.querySelector(`tr[data-id="${id}"]`).remove();
      location.reload();
    } else alert('Delete failed: ' + (data.message || 'Server error'));
  } catch (err) { alert('Request failed: ' + err.message); }
}

// Edit
async function editEntry(id) {
  try {
    const resp = await fetch('get_journal.php?id=' + id + '&table=' + encodeURIComponent(TABLE_NAME));
    const j = await resp.json();
    if (j.status === 'success') {
      document.getElementById('entryId').value = j.entry.id;
      document.getElementById('entryDate').value = j.entry.entry_date;
      document.getElementById('details').value = j.entry.details;
      document.getElementById('amount').value = j.entry.amount;
      document.getElementById('saveBtn').textContent = '✅ Update Entry';
      document.getElementById('cancelEdit').classList.remove('d-none');
    } else alert('Could not fetch entry.');
  } catch (err) { alert('Request failed: ' + err.message); }
}

document.getElementById('cancelEdit').onclick = function() {
  document.getElementById('journalForm').reset();
  document.getElementById('entryId').value = '';
  document.getElementById('saveBtn').textContent = '💾 Save Entry';
  document.getElementById('cancelEdit').classList.add('d-none');
};
</script>
</body>
</html>
