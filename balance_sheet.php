<?php
include 'db_config.php';
include 'sidebar.php';

$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
if (!$company_id) {
    die("<div style='margin-left:260px;padding:20px;' class='alert alert-danger'>Invalid Company ID</div>");
}

// Fetch company name
$company = $conn->query("SELECT name FROM companies WHERE id=$company_id")->fetch_assoc();
$company_name = $company ? $company['name'] : "Unknown";

// Coupon filter
$coupon_filter = isset($_GET['coupon_filter']) ? $_GET['coupon_filter'] : "";

// Fetch coupons for dropdown
$couponQuery = "SELECT DISTINCT coupon_no FROM coupons WHERE company_id=$company_id ORDER BY coupon_no ASC";
$coupons = $conn->query($couponQuery);

// Fetch debit/credit items
$sql = "SELECT c.entry_date, c.coupon_no, ci.description, ci.qty, ci.price, ci.line_total, ci.type
        FROM coupons c
        JOIN coupon_items ci ON c.id = ci.coupon_id
        WHERE c.company_id=$company_id";
if ($coupon_filter) {
    $sql .= " AND c.coupon_no='$coupon_filter'";
}
$sql .= " ORDER BY c.entry_date ASC, c.id ASC, ci.id ASC";
$result = $conn->query($sql);

// Split into debit/credit
$debits = $credits = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['entry_date']."_".$row['coupon_no'];
    if ($row['type'] === 'debit') {
        $debits[$key]['date'] = $row['entry_date'];
        $debits[$key]['cc']   = $row['coupon_no'];
        $debits[$key]['items'][] = "{$row['description']} ({$row['qty']}×{$row['price']} = {$row['line_total']})";
        $debits[$key]['total'] = ($debits[$key]['total'] ?? 0) + $row['line_total'];
    } else {
        $credits[$key]['date'] = $row['entry_date'];
        $credits[$key]['cc']   = $row['coupon_no'];
        $credits[$key]['items'][] = "{$row['description']} ({$row['qty']}×{$row['price']} = {$row['line_total']})";
        $credits[$key]['total'] = ($credits[$key]['total'] ?? 0) + $row['line_total'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($company_name) ?> - Balance Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar already included above -->

<div class="main-wrapper">  <!-- 👈 wrapper j sidebar.php ma define karyo -->
    <h2 class="mb-4">Balance Sheet - <?= htmlspecialchars($company_name) ?></h2>

    <!-- Filters -->
    <form class="d-flex mb-3" method="GET">
        <input type="hidden" name="company_id" value="<?= $company_id ?>">
        <select name="coupon_filter" class="form-select w-auto me-2">
            <option value="">All Coupons</option>
            <?php while($c = $coupons->fetch_assoc()): ?>
                <option value="<?= $c['coupon_no'] ?>" <?= $coupon_filter==$c['coupon_no']?'selected':'' ?>>
                    <?= $c['coupon_no'] ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button class="btn btn-secondary me-2">Filter</button>
        <a href="balance_sheet_pdf.php?company_id=<?= $company_id ?>&coupon_filter=<?= $coupon_filter ?>" class="btn btn-success me-2">
            <i class="fas fa-file-pdf"></i> Download PDF
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#entryModal">
            + Add Entry
        </button>
    </form>

    <!-- Table -->
    <div class="table-wrapper">
        <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
                <tr><th colspan="4">Debit</th><th colspan="4">Credit</th></tr>
                <tr>
                    <th>Date</th><th>CC</th><th>Description</th><th>Amount</th>
                    <th>Date</th><th>CC</th><th>Description</th><th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $debitGroups  = array_values($debits);
                $creditGroups = array_values($credits);
                $rows = max(count($debitGroups), count($creditGroups));
                $total_debit=0; $total_credit=0;

                for ($i=0; $i<$rows; $i++) {
                    echo "<tr>";
                    if (isset($debitGroups[$i])) {
                        $d = $debitGroups[$i];
                        echo "<td>{$d['date']}</td><td>{$d['cc']}</td>
                              <td class='text-start'>".implode("<br>",$d['items'])."</td>
                              <td>{$d['total']}</td>";
                        $total_debit += $d['total'];
                    } else echo "<td></td><td></td><td></td><td></td>";

                    if (isset($creditGroups[$i])) {
                        $c = $creditGroups[$i];
                        echo "<td>{$c['date']}</td><td>{$c['cc']}</td>
                              <td class='text-start'>".implode("<br>",$c['items'])."</td>
                              <td>{$c['total']}</td>";
                        $total_credit += $c['total'];
                    } else echo "<td></td><td></td><td></td><td></td>";
                    echo "</tr>";
                }

                // Balance row
                if ($total_debit != $total_credit) {
                    $diff = abs($total_debit - $total_credit);
                    echo "<tr class='table-warning'>";
                    if ($total_debit < $total_credit) {
                        echo "<td></td><td></td><td>Remaining Payment</td><td>$diff</td>
                              <td></td><td></td><td></td><td></td>";
                        $total_debit += $diff;
                    } else {
                        echo "<td></td><td></td><td></td><td></td>
                              <td></td><td></td><td>Remaining Payment</td><td>$diff</td>";
                        $total_credit += $diff;
                    }
                    echo "</tr>";
                }

                echo "<tr class='table-info'>
                        <td colspan='3'><b>Total Debit</b></td><td><b>$total_debit</b></td>
                        <td colspan='3'><b>Total Credit</b></td><td><b>$total_credit</b></td>
                      </tr>";
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Entry Modal (same as before) -->
<div class="modal fade" id="entryModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="add_entry.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="company_id" value="<?= $company_id ?>">
        <div class="row mb-2">
          <div class="col-md-4">
            <label>Type</label>
            <select name="type" class="form-control">
              <option value="debit">Debit</option>
              <option value="credit">Credit</option>
            </select>
          </div>
          <div class="col-md-4">
            <label>Coupon No</label>
            <input type="text" name="coupon_no" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label>Date</label>
            <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <table class="table table-bordered" id="itemsTable">
          <thead><tr><th>Description</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td><input type="text" name="description[]" class="form-control" required></td>
              <td><input type="number" name="qty[]" class="form-control qty" required></td>
              <td><input type="number" step="0.01" name="price[]" class="form-control price" required></td>
              <td><input type="text" class="form-control line_total" readonly></td>
              <td><button type="button" class="btn btn-success btn-sm" onclick="addRow()">+</button></td>
            </tr>
          </tbody>
        </table>
        <h5 class="text-end">Total: <span id="grandTotal">0</span></h5>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">Save Entry</button>
      </div>
    </form>
  </div>
</div>

<script>
function addRow(){
  let row=`<tr>
    <td><input type="text" name="description[]" class="form-control" required></td>
    <td><input type="number" name="qty[]" class="form-control qty" required></td>
    <td><input type="number" step="0.01" name="price[]" class="form-control price" required></td>
    <td><input type="text" class="form-control line_total" readonly></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove();calcTotal()">×</button></td>
  </tr>`;
  document.querySelector("#itemsTable tbody").insertAdjacentHTML('beforeend',row);
}
function calcTotal(){
  let total=0;
  document.querySelectorAll("#itemsTable tbody tr").forEach(tr=>{
    let qty=tr.querySelector(".qty").value||0;
    let price=tr.querySelector(".price").value||0;
    let line=qty*price;
    tr.querySelector(".line_total").value=line;
    total+=parseFloat(line);
  });
  document.getElementById("grandTotal").innerText=total.toFixed(2);
}
document.addEventListener("input",e=>{
  if(e.target.classList.contains("qty")||e.target.classList.contains("price")){
    calcTotal();
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
