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

$cntPending = (int)$conn->query("SELECT COUNT(*) c FROM worker_work WHERE worker_id=$worker_id AND (bill IS NULL OR bill=0)")->fetch_assoc()['c'];
$cntCompleted = (int)$conn->query("SELECT COUNT(*) c FROM worker_work WHERE worker_id=$worker_id AND bill>0")->fetch_assoc()['c'];
$cntLabour = (int)$conn->query("SELECT COUNT(*) c FROM labour_work WHERE worker_id=$worker_id")->fetch_assoc()['c'];

$default = $cntPending > 0 ? 'pending' : 'completed';
$status = $_GET['status'] ?? $default;
$isPending = ($status==='pending');

// filter for normal work
$filter = "WHERE w.worker_id=$worker_id";
if ($from && $to) {
  $from_date = date('Y-m-d', strtotime($from));
  $to_date = date('Y-m-d', strtotime($to));
  $filter .= " AND w.work_date BETWEEN '$from_date' AND '$to_date'";
}
$filter .= $isPending ? " AND (w.bill IS NULL OR w.bill=0)" : " AND w.bill>0";

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

// labour work
$labour_sql = "SELECT * FROM labour_work WHERE worker_id=$worker_id ORDER BY work_date DESC";
$labour_data = $conn->query($labour_sql);
$total_labour = 0;
foreach($labour_data as $ld){ $total_labour += $ld['price']; }
$labour_data->data_seek(0); // reset pointer for loop below

// advance total
$total_advance = $conn->query("SELECT SUM(amount) total_advance FROM advance_salary WHERE worker_id=$worker_id")->fetch_assoc()['total_advance'] ?? 0;

// add advance
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_advance'])) {
  $amount = floatval($_POST['advance_amount']);
  $type = $conn->real_escape_string($_POST['advance_type']);
  if ($amount>0) {
    $stmt=$conn->prepare("INSERT INTO advance_salary (worker_id,amount,cash_gpay) VALUES (?,?,?)");
    $stmt->bind_param("ids",$worker_id,$amount,$type);
    $stmt->execute();
    header("Location: worker_detail.php?id=$worker_id&status=$status");
    exit;
  }
}

// add labour work
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_labour'])) {
  $date = $_POST['labour_date'];
  $desc = $conn->real_escape_string($_POST['labour_description']);
  $price = floatval($_POST['labour_price']);
  if ($price>0) {
    $stmt=$conn->prepare("INSERT INTO labour_work (worker_id,work_date,description,price) VALUES (?,?,?,?)");
    $stmt->bind_param("issd",$worker_id,$date,$desc,$price);
    $stmt->execute();
    header("Location: worker_detail.php?id=$worker_id&status=labour");
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
.delete-work{padding:2px 6px;}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-wrapper">
  <nav class="navbar navbar-light bg-white shadow-sm px-4 mb-3">
    <div class="container-fluid">
      <h5 class="mb-0">👷 <?= htmlspecialchars($worker['name']) ?> - Detail View</h5>
      <span class="text-muted">Worker ID: <?= $worker_id ?></span>
    </div>
  </nav>

  <div class="main-content">
    <form method="get" class="row g-3 mb-3">
      <input type="hidden" name="id" value="<?= $worker_id ?>">
      <div class="col-md-3"><label>From</label><input type="date" name="start_date" value="<?= $from ?>" class="form-control"></div>
      <div class="col-md-3"><label>To</label><input type="date" name="end_date" value="<?= $to ?>" class="form-control"></div>
      <div class="col-md-6 d-flex align-items-end gap-2">
        <button class="btn btn-primary">Filter</button>
        <a href="export_pdf.php?id=<?= $worker_id ?>&status=<?= $status ?>" class="btn btn-success">📄 PDF</a>
        <a href="add_work.php?worker_id=<?= $worker_id ?>" class="btn btn-info">➕ Add Work</a>
      </div>
    </form>

    <div class="mb-3">
      <a class="btn <?= $isPending?'btn-primary':'btn-outline-primary' ?>" href="?id=<?= $worker_id ?>&status=pending">Pending (<?= $cntPending ?>)</a>
      <a class="btn <?= !$isPending?'btn-success':'btn-outline-success' ?>" href="?id=<?= $worker_id ?>&status=completed">Completed (<?= $cntCompleted ?>)</a>
      <a class="btn <?= $status==='labour'?'btn-warning':'btn-outline-warning' ?>" href="?id=<?= $worker_id ?>&status=labour">Lauboring (<?= $cntLabour ?>)</a>
    </div>

    <?php if($status!=='labour'): ?>
    <!-- Normal Work Table -->
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-sm table-hover align-middle" id="workTable">
        <thead class="table-primary">
          <tr><th>Date</th><th>Sr</th><th>Parts</th><th>Cost</th><th>Bill</th><th>Margin</th><th>Salary (50%)</th><th>Action</th></tr>
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
          <tr data-id="<?= $row['id'] ?>" data-date="<?= $date ?>">
            <td><?= $sr===1?date('d-m-Y',strtotime($date)):'' ?></td>
            <td><?= $sr++ ?></td>
            <td class="parts-col"><?= htmlspecialchars($row['parts']) ?></td>
            <td><input type="number" class="inline-edit cost" value="<?= $cost ?>"></td>
            <td><input type="number" class="inline-edit bill" value="<?= $bill ?>"></td>
            <td class="margin">₹<?= number_format($margin,2) ?></td>
            <td class="salary text-success fw-bold">₹<?= number_format($salary,2) ?></td>
            <td><button class="btn btn-sm btn-danger delete-work"><i class="bi bi-trash"></i></button></td>
          </tr>
          <?php endforeach; ?>
          <tr class="table-light day-total" data-date="<?= $date ?>">
            <td colspan="3">Total (<?= date('d-m-Y',strtotime($date)) ?>)</td>
            <td class="day-cost">₹<?= number_format($day_cost,2) ?></td>
            <td class="day-bill">₹<?= number_format($day_bill,2) ?></td>
            <td class="day-margin">₹<?= number_format($day_margin,2) ?></td>
            <td class="day-salary fw-bold">₹<?= number_format($day_salary,2) ?></td>
            <td></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-secondary">
          <tr><th colspan="3">Grand Total</th><th id="grandCost">₹<?= number_format($grand_cost,2) ?></th><th id="grandBill">₹<?= number_format($grand_bill,2) ?></th><th id="grandMargin">₹<?= number_format($grand_margin,2) ?></th><th id="grandSalary" class="text-success">₹<?= number_format($grand_salary,2) ?></th><th></th></tr>
          <tr><td colspan="6" class="text-end">Advance Taken</td><td id="advanceTaken" class="text-danger">- ₹<?= number_format($total_advance,2) ?></td><td></td></tr>
          <tr><td colspan="6" class="text-end">Remaining Salary</td><td id="remainingSalary" class="text-primary fw-bold">₹<?= number_format($grand_salary-$total_advance,2) ?></td><td></td></tr>
        </tfoot>
      </table>
    </div>

    <?php else: ?>
    <!-- Lauboring Section -->
    
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-sm">
        <thead class="table-primary"><tr><th>Date</th><th>Description</th><th>Price (₹)</th><th>Action</th></tr></thead>
        <tbody>
          <?php if($labour_data->num_rows>0): while($l=$labour_data->fetch_assoc()): ?>
            <tr data-id="<?= $l['id'] ?>">
              <td><?= date('d-m-Y',strtotime($l['work_date'])) ?></td>
              <td><?= htmlspecialchars($l['description']) ?></td>
              <td>₹<?= number_format($l['price'],2) ?></td>
              <td><a href="delete_labour.php?id=<?= $l['id'] ?>&worker_id=<?= $worker_id ?>" class="btn btn-danger btn-sm">Delete</a></td>
            </tr>
          <?php endwhile; else: ?><tr><td colspan="4" class="text-center">No lauboring work found.</td></tr><?php endif; ?>
        </tbody>
        <tfoot class="table-secondary">
          <tr><th colspan="2" class="text-end">Total Lauboring Amount</th><th colspan="2" class="text-success fw-bold">₹<?= number_format($total_labour,2) ?></th></tr>
        </tfoot>
      </table>
    </div>
    <?php endif; ?>

    

<script>
function recalcTotals() {
  let grandCost=0, grandBill=0, grandMargin=0, grandSalary=0;
  document.querySelectorAll(".day-total").forEach(dayRow=>{
    const date=dayRow.dataset.date;
    let dayCost=0, dayBill=0, dayMargin=0, daySalary=0;
    document.querySelectorAll(`tr[data-date='${date}']`).forEach(tr=>{
      if(tr.classList.contains("day-total")) return;
      const cost=parseFloat(tr.querySelector(".cost").value)||0;
      const bill=parseFloat(tr.querySelector(".bill").value)||0;
      const margin=bill-cost, salary=margin/2;
      dayCost+=cost; dayBill+=bill; dayMargin+=margin; daySalary+=salary;
    });
    dayRow.querySelector(".day-cost").textContent="₹"+dayCost.toFixed(2);
    dayRow.querySelector(".day-bill").textContent="₹"+dayBill.toFixed(2);
    dayRow.querySelector(".day-margin").textContent="₹"+dayMargin.toFixed(2);
    dayRow.querySelector(".day-salary").textContent="₹"+daySalary.toFixed(2);
    grandCost+=dayCost; grandBill+=dayBill; grandMargin+=dayMargin; grandSalary+=daySalary;
  });
  document.getElementById("grandCost").textContent="₹"+grandCost.toFixed(2);
  document.getElementById("grandBill").textContent="₹"+grandBill.toFixed(2);
  document.getElementById("grandMargin").textContent="₹"+grandMargin.toFixed(2);
  document.getElementById("grandSalary").textContent="₹"+grandSalary.toFixed(2);
  const advance=parseFloat(document.getElementById("advanceTaken").textContent.replace(/[^0-9.-]/g,''))||0;
  document.getElementById("remainingSalary").textContent="₹"+(grandSalary-advance).toFixed(2);
}
document.querySelectorAll('.inline-edit').forEach(inp=>{
  inp.addEventListener('change',async e=>{
    const tr=e.target.closest('tr');
    const id=tr.dataset.id;
    const cost=parseFloat(tr.querySelector('.cost').value)||0;
    const bill=parseFloat(tr.querySelector('.bill').value)||0;
    const margin=bill-cost, salary=margin/2;
    tr.querySelector('.margin').innerHTML='₹'+margin.toFixed(2);
    tr.querySelector('.salary').innerHTML='₹'+salary.toFixed(2);
    recalcTotals();
    await fetch('update_work.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,cost,bill})});
  });
});
document.querySelectorAll('.delete-work').forEach(btn=>{
  btn.addEventListener('click',async e=>{
    e.preventDefault();
    if(!confirm("Are you sure you want to delete this work?")) return;
    const tr=btn.closest('tr');
    const id=tr.dataset.id;
    const res=await fetch('delete_work.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
    const data=await res.json();
    if(data.success){tr.remove();recalcTotals();}else{alert("Delete failed!");}
  });
});
</script>
</body>
</html>
