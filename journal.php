<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
require 'db_config.php';

// Fetch all journal entries
$result = $conn->query("SELECT * FROM journal ORDER BY entry_date DESC, id DESC");
$entries = $result->fetch_all(MYSQLI_ASSOC);
$total = array_sum(array_column($entries, 'amount'));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Journal - Pending Payments | Krishna Pump</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f4f5fa; }
    .card { border:none; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
    .form-label { font-weight:600; }
    .table thead th { background:#0d6efd; color:#fff; }
    .total-bar { background:#f8f9fa; font-weight:bold; font-size:1.1rem; padding:10px 15px; text-align:right; border-top:2px solid #0d6efd; }
    .btn-primary { box-shadow:0 2px 4px rgba(13,110,253,0.3); }
    .action-btns button { margin: 0 3px; }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main" style="margin-left:250px;padding:20px;max-width:1000px;">
  <!-- Add Form -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">🧾 Add or Edit Pending Payment</h5>
    </div>
    <div class="card-body">
      <form id="journalForm" class="row g-3">
        <input type="hidden" name="id" id="entryId">
        <div class="col-md-3">
          <label class="form-label">Date</label>
          <input type="date" name="entry_date" id="entryDate" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-7">
          <label class="form-label">Details (From + Description)</label>
          <input type="text" name="details" id="details" class="form-control" placeholder="e.g. ABC Motors - Pending bill payment" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Amount (₹)</label>
          <input type="number" name="amount" id="amount" step="0.01" class="form-control" required>
        </div>
        <div class="col-12 text-end">
          <button type="submit" class="btn btn-primary px-4" id="saveBtn">💾 Save Entry</button>
          <button type="button" class="btn btn-secondary px-4 d-none" id="cancelEdit">❌ Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">📋 Pending Payments List</h5>
      <span>Total Entries: <?= count($entries) ?></span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
          <thead>
            <tr>
              <th style="width:70px;">Sr</th>
              <th>Date</th>
              <th>Details</th>
              <th class="text-end">Amount (₹)</th>
              <th style="width:130px;">Actions</th>
            </tr>
          </thead>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let editingId = null;

// Add / Update Entry
document.getElementById('journalForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  if (!data.amount || data.amount <= 0) {
    alert("Please enter a valid amount.");
    return;
  }

  const resp = await fetch('save_journal.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  const res = await resp.json();

  if (res.status === 'success') location.reload();
  else alert("Failed to save entry. Try again.");
});

// Delete Entry
async function deleteEntry(id) {
  if (!confirm("Are you sure the payment is received or you want to delete this entry?")) return;
  const resp = await fetch('delete_journal.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  });
  const res = await resp.json();
  if (res.status === 'success') {
    document.querySelector(`tr[data-id="${id}"]`).remove();
    alert("Entry deleted successfully!");
    location.reload();
  } else {
    alert("Delete failed.");
  }
}

// Edit Entry
async function editEntry(id) {
  const resp = await fetch('get_journal.php?id=' + id);
  const data = await resp.json();

  if (data.status === 'success') {
    editingId = id;
    document.getElementById('entryId').value = id;
    document.getElementById('entryDate').value = data.entry.entry_date;
    document.getElementById('details').value = data.entry.details;
    document.getElementById('amount').value = data.entry.amount;
    document.getElementById('saveBtn').textContent = "✅ Update Entry";
    document.getElementById('cancelEdit').classList.remove('d-none');
  }
}

document.getElementById('cancelEdit').onclick = () => {
  editingId = null;
  document.getElementById('journalForm').reset();
  document.getElementById('entryId').value = '';
  document.getElementById('saveBtn').textContent = "💾 Save Entry";
  document.getElementById('cancelEdit').classList.add('d-none');
};
</script>
</body>
</html>
