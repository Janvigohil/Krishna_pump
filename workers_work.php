<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'db_config.php';

// Fetch all non-deleted workers
$workers = $conn->query("SELECT * FROM workers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Workers Summary - Krishna Pump</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background-color: #f4f5fa;
      font-family: 'Segoe UI', sans-serif;
    }

    .sidebar {
      width: 250px;
      height: 100vh;
      background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      color: white;
      position: fixed;
      top: 0;
      left: 0;
      padding-top: 20px;
    }

    .sidebar a {
      color: white;
      text-decoration: none;
      display: block;
      padding: 12px 20px;
      transition: background 0.3s ease;
    }

    .sidebar a:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .main-content {
      margin-left: 250px;
      padding: 20px;
    }

    .navbar {
      margin-left: 250px;
    }

    .table thead th {
      background-color: #f1f3f5;
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>


<!-- Top Navbar -->
<nav class="navbar navbar-light bg-white shadow-sm px-4">
  <div class="container-fluid">
    <h4 class="mb-0">Workers Work Summary</h4>
    <span class="text-muted">Welcome, <?= htmlspecialchars($_SESSION['admin']) ?></span>
  </div>
</nav>

<!-- Main Content -->
<div class="main-content">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">All Workers & Total Salary</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th scope="col">Sr No</th>
              <th scope="col">Worker Name</th>
              <th scope="col">Total Salary</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sr = 1;
            while ($worker = $workers->fetch_assoc()) {
                $worker_id = $worker['id'];

                // Calculate total salary
                $salary_query = $conn->query("SELECT SUM(salary) AS total_salary FROM worker_work WHERE worker_id = $worker_id");
                $salary_row = $salary_query->fetch_assoc();
                $total_salary = $salary_row['total_salary'] ?? 0;

                echo "<tr>
                        <td>{$sr}</td>
                        <td>" . htmlspecialchars($worker['name']) . "</td>
                        <td>₹ " . number_format($total_salary, 2) . "</td>
                        <td><a href='worker_detail.php?id={$worker_id}' class='btn btn-sm btn-primary'><i class='bi bi-eye'></i> View</a></td>
                      </tr>";
                $sr++;
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</body>
</html>
