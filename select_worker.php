<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }
require 'db_config.php';
$workers = $conn->query("SELECT id, name FROM workers ORDER BY name");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Select Worker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include 'sidebar.php'; ?>
<nav class="navbar navbar-light bg-white shadow-sm px-4" style="margin-left:250px">
  <div class="container-fluid"><h4 class="mb-0">Select Worker for New Work</h4></div>
</nav>

<div class="container" style="margin-left:250px; max-width:900px;">
  <div class="card mt-4">
    <div class="card-body">
      <p class="text-muted mb-3">Choose a worker to start the Add Work form.</p>
      <div class="list-group">
        <?php while($w = $workers->fetch_assoc()): ?>
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
             href="add_work.php?worker_id=<?= (int)$w['id'] ?>">
            <span>👷 <?= htmlspecialchars($w['name']) ?></span>
            <span class="badge bg-primary">Select</span>
          </a>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
