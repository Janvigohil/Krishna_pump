<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
require 'db_config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { header("Location: workers_work.php"); exit(); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $bill = isset($_POST['bill']) && $_POST['bill']!=='' ? (float)$_POST['bill'] : null;
  $stmt = $conn->prepare("UPDATE worker_work SET bill=? WHERE id=?");
  $stmt->bind_param('di', $bill, $id);
  $stmt->execute();
  header("Location: workers_work.php?status=completed"); exit();
}

$w = $conn->query("SELECT w.*, wr.name worker_name FROM worker_work w JOIN workers wr ON wr.id=w.worker_id WHERE w.id=$id")->fetch_assoc();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Complete Work</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'sidebar.php'; ?>
<nav class="navbar navbar-light bg-white shadow-sm px-4" style="margin-left:250px">
  <div class="container-fluid"><h4 class="mb-0">Complete Work (<?= htmlspecialchars($w['work_no']) ?>)</h4></div>
</nav>
<div class="container" style="margin-left:250px; max-width:600px;">
  <div class="card mt-4">
    <div class="card-body">
      <p class="mb-1">Worker: <b><?= htmlspecialchars($w['worker_name']) ?></b></p>
      <p class="mb-3 text-muted">Cost: ₹<?= number_format((float)$w['cost'],2) ?></p>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Final Bill</label>
          <input type="number" step="0.01" name="bill" class="form-control" placeholder="Enter bill to mark completed" required>
        </div>
        <div class="d-flex gap-2">
          <a href="workers_work.php?status=pending" class="btn btn-secondary">Cancel</a>
          <button class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
