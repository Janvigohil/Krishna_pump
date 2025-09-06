<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
require_once 'db_config.php';

// Pagination settings
$limit = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause (default = last 30 days)
$where = "ww.work_date >= CURDATE() - INTERVAL 30 DAY";
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    $start = $conn->real_escape_string($_GET['start_date']);
    $end   = $conn->real_escape_string($_GET['end_date']);
    $where = "ww.work_date BETWEEN '$start' AND '$end'";
}

// Count total records (for pagination)
$countQuery = "
    SELECT COUNT(DISTINCT ww.id) AS total
    FROM worker_work ww
    LEFT JOIN worker_motor_work wmw ON ww.id = wmw.work_id
    LEFT JOIN workers w ON ww.worker_id = w.id
    WHERE $where
";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Main query (with worker name)
$query = "
    SELECT 
        ww.id, ww.work_date, ww.cost, ww.bill,
        w.name AS worker_name,
        GROUP_CONCAT(mp.name ORDER BY mp.name SEPARATOR ', ') AS part_names
    FROM worker_work ww
    LEFT JOIN worker_motor_work wmw ON ww.id = wmw.work_id
    LEFT JOIN motor_parts mp ON wmw.part_id = mp.id
    LEFT JOIN workers w ON ww.worker_id = w.id
    WHERE $where
    GROUP BY ww.id
    ORDER BY ww.work_date DESC, ww.id ASC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($query);

// Organize data
$records_by_date = [];
$grand_cost = 0;
$grand_bill = 0;

while ($row = $result->fetch_assoc()) {
    $date = $row['work_date'];
    if (!isset($records_by_date[$date])) {
        $records_by_date[$date] = [
            'rows' => [],
            'total_cost' => 0,
            'total_bill' => 0
        ];
    }
    $records_by_date[$date]['rows'][] = $row;
    $records_by_date[$date]['total_cost'] += $row['cost'];
    $records_by_date[$date]['total_bill'] += $row['bill'];

    $grand_cost += $row['cost'];
    $grand_bill += $row['bill'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Krishna Pump | Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f4f5fa; font-family: 'Segoe UI', sans-serif; }
    .main-content { flex: 1; padding: 20px; }
    .table-responsive { max-height: 70vh; overflow-y: auto; }
    .sidebar { width: 250px; }
  </style>
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Navbar -->
      <nav class="navbar navbar-light bg-white shadow-sm px-4 mb-4">
        <div class="container-fluid">
          <h4 class="mb-0">Dashboard</h4>
          <span class="text-muted">Welcome, <?= htmlspecialchars($_SESSION['admin']) ?></span>
        </div>
      </nav>

      <!-- Filter Form -->
      <form method="GET" class="mb-4 row g-2">
        <div class="col-md-3">
          <label for="start_date" class="form-label">Start Date</label>
          <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $_GET['start_date'] ?? '' ?>">
        </div>
        <div class="col-md-3">
          <label for="end_date" class="form-label">End Date</label>
          <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $_GET['end_date'] ?? '' ?>">
        </div>
        <div class="col-md-3 align-self-end">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="index.php" class="btn btn-secondary">Reset</a>
        </div>
      </form>

      <!-- Records Table -->
      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead class="table-primary">
            <tr>
              <th>Date</th>
              <th>Sr No</th>
              <th>Worker</th>
              <th>Motor Parts List</th>
              <th>Costing</th>
              <th>Bill</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($records_by_date)): ?>
              <tr><td colspan="6" class="text-center">No records found.</td></tr>
            <?php else: ?>
              <?php foreach ($records_by_date as $date => $group): ?>
                <tr class="table-warning"><td colspan="6"><strong>Date: <?= date('d-m-Y', strtotime($date)) ?></strong></td></tr>
                <?php $sr = 1; foreach ($group['rows'] as $row): ?>
                  <tr>
                    <td><?= date('d-m-Y', strtotime($row['work_date'])) ?></td>
                    <td><?= $sr++ ?></td>
                    <td><?= htmlspecialchars($row['worker_name']) ?></td>
                    <td><?= htmlspecialchars($row['part_names']) ?></td>
                    <td>₹<?= number_format($row['cost'], 2) ?></td>
                    <td>₹<?= number_format($row['bill'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="table-secondary fw-bold">
                  <td colspan="4" class="text-end">Subtotal:</td>
                  <td>₹<?= number_format($group['total_cost'], 2) ?></td>
                  <td>₹<?= number_format($group['total_bill'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <tfoot class="table-success">
            <tr>
              <th colspan="4" class="text-end">Grand Total:</th>
              <th>₹<?= number_format($grand_cost, 2) ?></th>
              <th>₹<?= number_format($grand_bill, 2) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav>
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&start_date=<?= $_GET['start_date'] ?? '' ?>&end_date=<?= $_GET['end_date'] ?? '' ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
</div>
</body>
</html>
