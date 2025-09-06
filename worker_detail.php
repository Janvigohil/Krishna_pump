<?php
include 'db_config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit(); }

$worker_id = $_GET['id'] ?? 0;
$from = $_GET['start_date'] ?? '';
$to = $_GET['end_date'] ?? '';
$filter_type = $_GET['filter'] ?? '';

$worker = $conn->query("SELECT * FROM workers WHERE id=$worker_id")->fetch_assoc();
if (!$worker) { echo "<div class='container mt-5 alert alert-danger'>Worker not found.</div>"; exit; }

// counts for tabs
$cntPending = (int)$conn->query("SELECT COUNT(*) c FROM worker_work WHERE worker_id=$worker_id AND (bill IS NULL OR bill=0)")->fetch_assoc()['c'];
$cntCompleted = (int)$conn->query("SELECT COUNT(*) c FROM worker_work WHERE worker_id=$worker_id AND bill>0")->fetch_assoc()['c'];
$default = $cntPending > 0 ? 'pending' : 'completed';
$status = $_GET['status'] ?? $default;
$isPending = ($status==='pending');

// date filter
$filter = "WHERE w.worker_id=$worker_id";
if ($from && $to) {
  $from_date = date('Y-m-d', strtotime($from));
  $to_date = date('Y-m-d', strtotime($to));
  $filter .= " AND w.work_date BETWEEN '$from_date' AND '$to_date'";
}
$filter .= $isPending ? " AND (w.bill IS NULL OR w.bill=0)" : " AND w.bill>0";

// fetch works
$sql = "SELECT w.id,w.work_date,w.cost,w.bill,
               GROUP_CONCAT(mp.name ORDER BY mp.name SEPARATOR ', ') AS parts
        FROM worker_work w
        LEFT JOIN worker_motor_work wm ON w.id=wm.work_id
        LEFT JOIN motor_parts mp ON wm.part_id=mp.id
        $filter
        GROUP BY w.id ORDER BY w.work_date DESC,w.id DESC";
$result = $conn->query($sql);
$data_by_date = [];
while($row=$result->fetch_assoc()){ $data_by_date[$row['work_date']][]=$row; }

// advance totals
$total_advance = $conn->query("SELECT SUM(amount) total_advance FROM advance_salary WHERE worker_id=$worker_id")->fetch_assoc()['total_advance'] ?? 0;

// add advance handler
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_advance'])) {
  $amount = floatval($_POST['advance_amount']);
  $type = $conn->real_escape_string($_POST['advance_type']);
  if ($amount>0) {
    $stmt=$conn->prepare("INSERT INTO advance_salary (worker_id,amount,cash_gpay) VALUES (?,?,?)");
    $stmt->bind_param("ids",$worker_id,$amount,$type);
    $stmt->execute();
    header("Location: worker_detail.php?id=$worker_id&start_date=$from&end_date=$to&status=$status");
    exit;
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Worker Detail - <?= htmlspecialchars($worker['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{background:#f4f5fa;font-family:'Segoe UI',sans-serif;font-size:14px;}
    td,th{font-size:16px;white-space:nowrap;}
    td.parts-col{font-size:16px;white-space:normal;max-width:280px;}
    input.inline-edit{width:90px;font-size:13px;text-align:right;font-weight:bold;border:1px solid #bbb;border-radius:4px;padding:2px 4px;}
    input.inline-edit.cost,input.inline-edit.bill {font-size:16px;font-weight:bold;padding:2px 4px;text-align:right;}
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<!-- 👇 Wrap all page content inside main-wrapper (required for sidebar collapse effect) -->
<div class="main-wrapper">

  <!-- Navbar -->
  <nav class="navbar navbar-light bg-white shadow-sm px-4 mb-3">
    <div class="container-fluid">
      <h5 class="mb-0">👷 <?= htmlspecialchars($worker['name']) ?> - Detail View</h5>
      <span class="text-muted">Worker ID: <?= $worker_id ?></span>
    </div>
  </nav>

  <div class="main-content">

    <!-- Filter -->
    <form method="get" class="row g-3 mb-3">
      <input type="hidden" name="id" value="<?= $worker_id ?>">
      <div class="col-md-3"><label>From</label><input type="date" name="start_date" value="<?= $from ?>" class="form-control"></div>
      <div class="col-md-3"><label>To</label><input type="date" name="end_date" value="<?= $to ?>" class="form-control"></div>
      <div class="col-md-6 d-flex align-items-end gap-2">
        <button class="btn btn-primary">Filter</button>
        <a href="export_pdf.php?id=<?= $worker_id ?>&start_date=<?= $from ?>&end_date=<?= $to ?>&status=<?= $status ?>" class="btn btn-success">📄 PDF</a>
        <a href="add_work.php?worker_id=<?= $worker_id ?>" class="btn btn-info">➕ Add Work</a>
      </div>
    </form>

    <!-- Pending/Completed toggle -->
    <div class="mb-3">
      <a class="btn <?= $isPending?'btn-primary':'btn-outline-primary' ?>" href="?id=<?= $worker_id ?>&status=pending">Pending (<?= $cntPending ?>)</a>
      <a class="btn <?= !$isPending?'btn-success':'btn-outline-success' ?>" href="?id=<?= $worker_id ?>&status=completed">Completed (<?= $cntCompleted ?>)</a>
    </div>

    <!-- Work table -->
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-sm table-hover align-middle">
        <thead class="table-primary">
          <tr><th>Date</th><th>Sr</th><th>Parts</th><th>Cost</th><th>Bill</th><th>Margin</th><th>Salary (50%)</th></tr>
        </thead>
        <tbody>
          <?php
          $grand_cost=$grand_bill=$grand_margin=$grand_salary=0;
          foreach($data_by_date as $date=>$entries):
            $sr=1;$day_cost=$day_bill=$day_margin=$day_salary=0;
            foreach($entries as $row):
              $cost=(float)$row['cost']; $bill=(float)$row['bill'];
              $margin=$bill-$cost; $salary=$margin/2;
              $day_cost+=$cost;$day_bill+=$bill;$day_margin+=$margin;$day_salary+=$salary;
              $grand_cost+=$cost;$grand_bill+=$bill;$grand_margin+=$margin;$grand_salary+=$salary;
          ?>
          <tr data-id="<?= $row['id'] ?>">
            <td><?= $sr===1?date('d-m-Y',strtotime($date)):'' ?></td>
            <td><?= $sr++ ?></td>
            <td class="parts-col"><?= htmlspecialchars($row['parts']) ?></td>
            <td><input type="number" class="inline-edit cost" value="<?= $cost ?>"></td>
            <td><input type="number" class="inline-edit bill" value="<?= $bill ?>"></td>
            <td class="margin">₹<?= number_format($margin,2) ?></td>
            <td class="salary text-success fw-bold">₹<?= number_format($salary,2) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="table-light">
            <td colspan="3">Total (<?= date('d-m-Y',strtotime($date)) ?>)</td>
            <td>₹<?= number_format($day_cost,2) ?></td>
            <td>₹<?= number_format($day_bill,2) ?></td>
            <td>₹<?= number_format($day_margin,2) ?></td>
            <td class="fw-bold">₹<?= number_format($day_salary,2) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-secondary">
          <tr><th colspan="3">Grand Total</th><th>₹<?= number_format($grand_cost,2) ?></th><th>₹<?= number_format($grand_bill,2) ?></th><th>₹<?= number_format($grand_margin,2) ?></th><th class="text-success">₹<?= number_format($grand_salary,2) ?></th></tr>
          <tr><td colspan="6" class="text-end">Advance Taken</td><td class="text-danger">- ₹<?= number_format($total_advance,2) ?></td></tr>
          <tr><td colspan="6" class="text-end">Remaining Salary</td><td class="text-primary fw-bold">₹<?= number_format($grand_salary-$total_advance,2) ?></td></tr>
        </tfoot>
      </table>
    </div>

    <!-- Add Advance -->
    <div class="card mb-4">
      <div class="card-header bg-warning text-dark">➕ Add Salary Advance</div>
      <div class="card-body">
        <form method="POST" class="row g-2">
          <input type="hidden" name="add_advance" value="1">
          <div class="col-md-4"><label>Amount (₹)</label><input type="number" name="advance_amount" step="0.01" class="form-control" required></div>
          <div class="col-md-4"><label>Payment Type</label>
            <select name="advance_type" class="form-control" required>
              <option value="cash">CASH</option>
              <option value="gpay">GPay</option>
            </select>
          </div>
          <div class="col-md-4 align-self-end"><button type="submit" class="btn btn-success">💸 Submit</button></div>
        </form>
      </div>
    </div>

    <!-- Advance History -->
    <form method="get" class="d-flex gap-2 mb-3">
      <input type="hidden" name="id" value="<?= $worker_id ?>">
      <input type="hidden" name="start_date" value="<?= $from ?>">
      <input type="hidden" name="end_date" value="<?= $to ?>">
      <button name="filter" value="cash" class="btn btn-outline-info">💵 CASH</button>
      <button name="filter" value="gpay" class="btn btn-outline-success">📱 GPay</button>
      <a href="worker_detail.php?id=<?= $worker_id ?>&start_date=<?= $from ?>&end_date=<?= $to ?>&status=<?= $status ?>" class="btn btn-outline-secondary">🔄 Reset</a>
    </form>

    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="table-light"><tr><th>Date</th><th>Amount (₹)</th><th>Type</th></tr></thead>
        <tbody>
          <?php
          $adv_sql = "SELECT * FROM advance_salary WHERE worker_id=$worker_id";
          if ($filter_type==='cash' || $filter_type==='gpay') $adv_sql .= " AND cash_gpay='$filter_type'";
          $adv_sql .= " ORDER BY date_advance DESC";
          $history = $conn->query($adv_sql);
          if ($history->num_rows>0): while($row=$history->fetch_assoc()): ?>
            <tr><td><?= date('d-m-Y',strtotime($row['date_advance'])) ?></td><td>₹<?= number_format($row['amount'],2) ?></td><td><?= strtoupper($row['cash_gpay']) ?></td></tr>
          <?php endwhile; else: ?>
            <tr><td colspan="3" class="text-center">No advances found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div><!-- /main-content -->

</div><!-- /main-wrapper -->

<script>
document.querySelectorAll('.inline-edit').forEach(inp=>{
  inp.addEventListener('change',async e=>{
    const tr=e.target.closest('tr');
    const id=tr.dataset.id;
    const cost=parseFloat(tr.querySelector('.cost').value)||0;
    const bill=parseFloat(tr.querySelector('.bill').value)||0;
    const margin=bill-cost, salary=margin/2;
    tr.querySelector('.margin').innerHTML='₹'+margin.toFixed(2);
    tr.querySelector('.salary').innerHTML='₹'+salary.toFixed(2);
    await fetch('update_work.php',{
      method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id,cost,bill})
    });
  });
});
</script>
</body>
</html>
