<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
require 'db_config.php';

$worker_id = isset($_GET['worker_id']) ? (int)$_GET['worker_id'] : 0;
if ($worker_id <= 0) { header("Location: add_work.php"); exit(); }

// Fetch worker name
$wRes = $conn->query("SELECT name FROM workers WHERE id=$worker_id");
if ($wRes->num_rows == 0) { die("Worker not found."); }
$worker = $wRes->fetch_assoc()['name'];

// Fetch all workers for dropdown
$workers = $conn->query("SELECT id, name FROM workers ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Fetch existing records
$result = $conn->query("SELECT * FROM labour_work WHERE worker_id=$worker_id ORDER BY work_date DESC, id DESC");
$entries = $result->fetch_all(MYSQLI_ASSOC);
$total = array_sum(array_column($entries, 'price'));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Labouring Work - <?= htmlspecialchars($worker) ?> | Krishna Pump</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f5f6fa;}
    .card{border:none;box-shadow:0 2px 6px rgba(0,0,0,0.1);border-radius:12px;}
    .table thead th{background:#194787ff;color:#fff;}
    .total-bar{background:#194787ff;font-weight:bold;font-size:1.1rem;padding:10px 15px;text-align:right;border-top:2px solid #194787ff;}
    .top-controls select{min-width:200px;}
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main" style="margin-left:250px;padding:20px;max-width:950px;">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
    <h4 class="m-0">👷 Labouring Work — <span class="text-success"><?= htmlspecialchars($worker) ?></span></h4>

    <!-- Dropdowns -->
    <div class="d-flex gap-2 top-controls">
      <!-- Change Worker -->
      <select class="form-select form-select-sm" id="changeWorker" onchange="changeWorker(this.value)">
        <option value="">Change Worker...</option>
        <?php foreach($workers as $w): ?>
          <option value="<?= $w['id'] ?>" <?= $w['id']==$worker_id?'selected':'' ?>>
            <?= htmlspecialchars($w['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Change Category -->
      <select class="form-select form-select-sm" id="changeCategory" onchange="changeCategory(this.value)">
        <option value="">Change Category...</option>
        <option value="labour_work.php?worker_id=<?= $worker_id ?>" selected>Labouring</option>
        <option value="margin_work.php?worker_id=<?= $worker_id ?>">Margin</option>
      </select>
    </div>
  </div>

  <!-- Add Labouring Work -->
  <div class="card mb-4">
    <div class="card-header bg-success text-white"><b>Add Labouring Work</b></div>
    <div class="card-body">
      <form id="labourForm" class="row g-3">
        <input type="hidden" name="worker_id" value="<?= $worker_id ?>">
        <div class="col-md-3">
          <label class="form-label">Date</label>
          <input type="date" name="work_date" id="work_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Description</label>
          <input type="text" name="description" id="description" class="form-control" placeholder="Enter work detail" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Price (₹)</label>
          <input type="number" name="price" id="price" class="form-control" step="0.01" required>
        </div>
        <div class="col-12 text-end">
          <button type="submit" class="btn btn-success px-4">💾 Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Work Entries -->
  <div class="card">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
      <b>🧾 Labour Work Entries</b>
      <span>Total: ₹<span id="totalPrice"><?= number_format($total, 2) ?></span></span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" id="workTable">
          <thead>
            <tr><th>#</th><th>Date</th><th>Description</th><th class="text-end">Price (₹)</th></tr>
          </thead>
          <tbody id="workTableBody">
            <?php if ($entries): $i=1; foreach($entries as $e): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= date('d-m-Y', strtotime($e['work_date'])) ?></td>
                <td><?= htmlspecialchars($e['description']) ?></td>
                <td class="text-end fw-bold text-success"><?= number_format($e['price'],2) ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No entries yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const form = document.getElementById('labourForm');
const tableBody = document.getElementById('workTableBody');
const totalPrice = document.getElementById('totalPrice');

form.addEventListener('submit', async (e)=>{
  e.preventDefault();
  const data = Object.fromEntries(new FormData(form).entries());

  const resp = await fetch('save_labour_work.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(data)
  });
  const res = await resp.json();

  if(res.status === 'success'){
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${tableBody.rows.length + 1}</td>
      <td>${new Date(data.work_date).toLocaleDateString('en-GB')}</td>
      <td>${data.description}</td>
      <td class="text-end fw-bold text-success">${parseFloat(data.price).toFixed(2)}</td>
    `;
    tableBody.prepend(row);
    totalPrice.textContent = (parseFloat(totalPrice.textContent.replace(/,/g,'')) + parseFloat(data.price)).toFixed(2);
    form.reset();
    form.work_date.value = new Date().toISOString().slice(0,10);
  } else {
    alert("Error saving data.");
  }
});

// Change worker
function changeWorker(workerId){
  if(workerId) window.location.href = `labour_work.php?worker_id=${workerId}`;
}

// Change category
function changeCategory(url){
  if(url) window.location.href = url;
}
</script>
</body>
</html>
