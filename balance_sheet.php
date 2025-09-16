<?php
include 'db_config.php';
include 'sidebar.php';
include 'utils.php';


$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
if (!$company_id) {
    die("<div style='margin-left:260px;padding:20px;' class='alert alert-danger'>Invalid Company ID</div>");
}

$company = $conn->query("SELECT name FROM companies WHERE id=$company_id")->fetch_assoc();
$company_name = $company ? $company['name'] : "Unknown";

$coupon_filter = $_GET['coupon_filter'] ?? "";
$from_date = $_GET['from_date'] ?? "";
$to_date   = $_GET['to_date'] ?? "";

$couponQuery = "SELECT DISTINCT coupon_no FROM coupons WHERE company_id=$company_id ORDER BY coupon_no ASC";
$coupons = $conn->query($couponQuery);

$sql = "SELECT c.id as coupon_id, c.entry_date, c.coupon_no, ci.id as item_id, ci.description, ci.qty, ci.price, ci.line_total, ci.type
        FROM coupons c
        JOIN coupon_items ci ON c.id = ci.coupon_id
        WHERE c.company_id=$company_id";
if ($coupon_filter) $sql .= " AND c.coupon_no='$coupon_filter'";
if ($from_date) $sql .= " AND c.entry_date >= '$from_date'";
if ($to_date)   $sql .= " AND c.entry_date <= '$to_date'";
$sql .= " ORDER BY c.entry_date ASC, c.id ASC, ci.id ASC";
$result = $conn->query($sql);

$debits = $credits = [];
while ($row = $result->fetch_assoc()) {
    $entry = [
        'item_id' => $row['item_id'],
        'coupon_id' => $row['coupon_id'],
        'description' => $row['description'],
        'qty' => $row['qty'],
        'price' => $row['price'],
        'line_total' => $row['line_total']
    ];

    $key = $row['entry_date']."_".$row['coupon_no'];

    if ($row['type'] === 'debit') {
        if ($row['qty'] == 1 && $row['price'] == $row['line_total']) {
            $debits[] = [
                'date' => $row['entry_date'],
                'cc'   => $row['coupon_no'],
                'items'=> [ $entry ],
                'total'=> $row['line_total'],
                'is_payment' => true
            ];
        } else {
            $debits[$key]['date'] = $row['entry_date'];
            $debits[$key]['cc'] = $row['coupon_no'];
            $debits[$key]['items'][] = $entry;
            $debits[$key]['total'] = ($debits[$key]['total'] ?? 0) + $row['line_total'];
            $debits[$key]['is_payment'] = false;
        }
    } else {
        if ($row['qty'] == 1 && $row['price'] == $row['line_total']) {
            $credits[] = [
                'date' => $row['entry_date'],
                'cc'   => $row['coupon_no'],
                'items'=> [ $entry ],
                'total'=> $row['line_total'],
                'is_payment' => true
            ];
        } else {
            $credits[$key]['date'] = $row['entry_date'];
            $credits[$key]['cc'] = $row['coupon_no'];
            $credits[$key]['items'][] = $entry;
            $credits[$key]['total'] = ($credits[$key]['total'] ?? 0) + $row['line_total'];
            $credits[$key]['is_payment'] = false;
        }
    }
}

$debitGroups = array_values($debits);
$creditGroups = array_values($credits);
?>
<!DOCTYPE html>
<html>
<head>
  <title><?= htmlspecialchars($company_name) ?> - Balance Sheet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    .desc { text-align:left; }
    .desc-payment { text-align:left; font-weight:500; }
  </style>
</head>
<body>
<div class="main-wrapper p-3">
  <h2>Balance Sheet - <?= htmlspecialchars($company_name) ?></h2>

  <!-- Filters -->
  <form class="d-flex mb-3 gap-2 align-items-center" method="GET">
    <input type="hidden" name="company_id" value="<?= $company_id ?>">
    <select name="coupon_filter" class="form-select form-select-sm w-auto">
      <option value="">All Coupons</option>
      <?php while($c=$coupons->fetch_assoc()): ?>
        <option value="<?= $c['coupon_no'] ?>" <?= $coupon_filter==$c['coupon_no']?'selected':'' ?>>
          <?= $c['coupon_no'] ?>
        </option>
      <?php endwhile; ?>
    </select>
    <input type="date" name="from_date" value="<?= $from_date ?>" class="form-control form-control-sm w-auto">
    <input type="date" name="to_date" value="<?= $to_date ?>" class="form-control form-control-sm w-auto">
    <button class="btn btn-secondary btn-sm">Filter</button>
    <a href="?company_id=<?= $company_id ?>" class="btn btn-outline-dark btn-sm">Reset</a>
    <a href="balance_sheet_pdf.php?company_id=<?= $company_id ?>&coupon_filter=<?= $coupon_filter ?>&from_date=<?= $from_date ?>&to_date=<?= $to_date ?>" class="btn btn-success btn-sm">PDF</a>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#entryModal">+ Entry</button>
    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">+ Payment</button>
    <button type="button" id="toggleEdit" class="btn btn-info btn-sm">Edit</button>
  </form>

  <!-- Balance Sheet -->
  <div class="table-responsive">
    <table class="table table-bordered text-center align-middle" id="balanceTable">
      <thead class="table-dark">
        <tr><th colspan="5">Debit</th><th colspan="5">Credit</th></tr>
        <tr>
      <th>Date</th><th>CC</th><th>Description</th><th>Amount</th><th>Actions</th> 
      <th>Date</th><th>CC</th><th>Description</th><th>Amount</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $rows = max(count($debitGroups), count($creditGroups));
        $total_debit = $total_credit = 0;

        for($i=0; $i<$rows; $i++) {
            echo "<tr>";
            if(isset($debitGroups[$i])){
                $d = $debitGroups[$i];
                $descHtml = "";
                foreach($d['items'] as $item){
                    if($d['is_payment']){
                        $descHtml .= "<div class='desc desc-payment' data-id='{$item['item_id']}'>{$item['description']}</div>";
                    } else {
                        $descHtml .= "<div class='desc' data-id='{$item['item_id']}'>{$item['description']} ({$item['qty']}×{$item['price']} = {$item['line_total']})</div>";
                    }
                }
                echo "<td>{$d['date']}</td><td>{$d['cc']}</td><td class='text-start'>$descHtml</td><td class='amount'>{$d['total']}</td><td class='actions'></td>";
                $total_debit += $d['total'];
            } else echo "<td></td><td></td><td></td><td></td><td></td>";

            if(isset($creditGroups[$i])){
                $c = $creditGroups[$i];
                $descHtml = "";
                foreach($c['items'] as $item){
                    if($c['is_payment']){
                        $descHtml .= "<div class='desc desc-payment' data-id='{$item['item_id']}'>{$item['description']}</div>";
                    } else {
                        $descHtml .= "<div class='desc' data-id='{$item['item_id']}'>{$item['description']} ({$item['qty']}×{$item['price']} = {$item['line_total']})</div>";
                    }
                }
                echo "<td>{$c['date']}</td><td>{$c['cc']}</td><td class='text-start'>$descHtml</td><td class='amount'>{$c['total']}</td><td class='actions'></td>";
                $total_credit += $c['total'];
            } else echo "<td></td><td></td><td></td><td></td><td></td>";

            echo "</tr>";
        }

        if($total_debit != $total_credit){
            $diff = abs($total_debit - $total_credit);
            echo "<tr class='table-warning'>";
            if($total_debit < $total_credit){
                echo "<td colspan='3'>Remaining Payment</td><td>$diff</td><td></td><td colspan='3'></td><td></td><td></td>";
                $total_debit += $diff;
            } else {
                echo "<td colspan='3'></td><td></td><td></td><td colspan='3'>Remaining Payment</td><td>$diff</td><td></td>";
                $total_credit += $diff;
            }
            echo "</tr>";
        }

        echo "<tr class='table-info'>
                <td colspan='3'><b>Total Debit</b></td><td><b>$total_debit</b></td><td></td>
                <td colspan='3'><b>Total Credit</b></td><td><b>$total_credit</b></td><td></td>
              </tr>";
        ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Entry Modal -->
<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="entryForm" method="POST" action="add_entry.php">
      <input type="hidden" name="company_id" value="<?= $company_id ?>">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add Entry</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col">
              <label>Type</label>
              <select name="type" class="form-select form-select-sm" required>
                <option value="debit">Debit</option><option value="credit" selected>Credit</option>
              </select>
            </div>
            <div class="col">
              <label>Coupon No</label>
              <input type="text" name="coupon_no" class="form-control form-control-sm" required>
            </div>
            <div class="col">
              <label>Date</label>
              <input type="date" name="entry_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <table class="table table-bordered" id="entryTable">
            <thead><tr><th>Description</th><th>Qty</th><th>Price</th><th>Total</th><th><button type="button" class="btn btn-success btn-sm" id="addRow">+</button></th></tr></thead>
            <tbody>
              <tr>
                <td>
                  <select name="description[]" class="form-select form-select-sm descriptionSelect" required>
                    <option value="">Select Description</option>
                    <?php
                    $motors = $conn->query("SELECT * FROM company_motors WHERE company_id = $company_id ORDER BY motor_name ASC");
                    while($motor = $motors->fetch_assoc()):
                    ?>
                      <option value="<?= htmlspecialchars($motor['motor_name']) ?>" data-price="<?= $motor['price'] ?>" data-qty="<?= $motor['qty'] ?>">
                        <?= htmlspecialchars($motor['motor_name']) ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </td>
                <td><input type="number" name="qty[]" class="form-control form-control-sm qtyInput" required></td>
                <td><input type="number" name="price[]" class="form-control form-control-sm priceInput" step="0.01" required></td>
                <td><input type="number" name="total[]" class="form-control form-control-sm totalInput" readonly></td>
                <td><button type="button" class="btn btn-danger btn-sm removeRow">−</button></td>
              </tr>
            </tbody>
          </table>
          <div class="text-end"><h5>Total: <span id="grandTotal">0</span></h5></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary btn-sm">Save Entry</button><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
      </div>
    </form>
  </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="paymentForm" method="POST" action="add_payment.php">
      <input type="hidden" name="company_id" value="<?= $company_id ?>">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Add Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col">
              <label>Type</label>
              <select name="type" class="form-select form-select-sm" required>
                <option value="debit">Debit</option><option value="credit">Credit</option>
              </select>
            </div>
            <div class="col">
              <label>Coupon No</label>
              <input type="text" name="coupon_no" class="form-control form-control-sm" required>
            </div>
            <div class="col">
              <label>Date</label>
              <input type="date" name="entry_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <table class="table table-bordered" id="paymentTable">
            <thead><tr><th>Description</th><th>Amount</th><th><button type="button" class="btn btn-success btn-sm" id="addPaymentRow">+</button></th></tr></thead>
            <tbody>
              <tr>
                <td><input type="text" name="description[]" class="form-control form-control-sm" required></td>
                <td><input type="number" name="amount[]" class="form-control form-control-sm" step="0.01" required></td>
                <td><button type="button" class="btn btn-danger btn-sm removePaymentRow">−</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary btn-sm">Add Payment</button><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function(){
    // Entry modal logic
    function recalcTotal(row){
        let qty = parseFloat(row.find(".qtyInput").val()) || 0;
        let price = parseFloat(row.find(".priceInput").val()) || 0;
        row.find(".totalInput").val((qty*price).toFixed(2));
        recalcGrand();
    }
    function recalcGrand(){
        let total=0;
        $("#entryTable tbody tr").each(function(){ total+=parseFloat($(this).find(".totalInput").val())||0; });
        $("#grandTotal").text(total.toFixed(2));
    }
    $("#entryTable").on("change",".descriptionSelect",function(){
        let opt=$(this).find("option:selected");
        let price=opt.data("price")||0; let qty=opt.data("qty")||1;
        let row=$(this).closest("tr");
        row.find(".priceInput").val(price); row.find(".qtyInput").val(qty);
        recalcTotal(row);
    });
    $("#entryTable").on("input",".qtyInput,.priceInput",function(){ recalcTotal($(this).closest("tr")); });
    $("#addRow").click(function(){ let r=$("#entryTable tbody tr:first").clone(); r.find("input").val(""); r.find("select").val(""); $("#entryTable tbody").append(r); });
    $("#entryTable").on("click",".removeRow",function(){ if($("#entryTable tbody tr").length>1){ $(this).closest("tr").remove(); recalcGrand(); } else alert("At least one row is required."); });

    // Payment modal logic
    $("#addPaymentRow").click(function(){ let r=$("#paymentTable tbody tr:first").clone(); r.find("input").val(""); $("#paymentTable tbody").append(r); });
    $("#paymentTable").on("click",".removePaymentRow",function(){ if($("#paymentTable tbody tr").length>1){ $(this).closest("tr").remove(); } else alert("At least one row is required."); });

    // Edit toggle
    let editing=false;
    $("#toggleEdit").click(function(){ editing=!editing; if(editing){ $(this).text("Done"); enableEdit(); } else { $(this).text("Edit"); saveChanges(); } });

   function enableEdit(){
    // Show actions column
    $(".action-col, .actions").removeClass("d-none");

    $("#balanceTable tbody tr").each(function(){
        $(this).find(".desc").each(function(){
            let item_id=$(this).data("id"); 
            let text=$(this).text();

            if($(this).hasClass("desc-payment")){
                let amount=$(this).closest("td").next(".amount").text();
                $(this).html(
                    `<input type="hidden" class="item-id" value="${item_id}">
                     <input type="text" class="desc-input form-control form-control-sm mb-1" value="${text}">
                     <input type="number" class="amount-input form-control form-control-sm" value="${amount}" step="0.01">`
                );
            } else {
                let p=text.match(/(.+?) \((\d+)×([\d.]+) = ([\d.]+)\)/);
                if(p){
                    let desc=p[1], qty=p[2], price=p[3];
                    $(this).html(
                        `<input type="hidden" class="item-id" value="${item_id}">
                         <input type="text" class="desc-input form-control form-control-sm mb-1" value="${desc}">
                         <input type="number" class="qty-input form-control form-control-sm mb-1" value="${qty}">
                         <input type="number" class="price-input form-control form-control-sm" value="${price}" step="0.01">`
                    );
                }
            }
        });
        $(this).find(".actions").html(`<button class="btn btn-sm btn-danger delete-row">Delete</button>`);
    });
}


 function saveChanges(){
    let updates=[];
    $("#balanceTable tbody tr").each(function(){
        $(this).find(".desc").each(function(){
            let item_id=$(this).find(".item-id").val();

            if($(this).find(".amount-input").length){
                // Payment
                let desc=$(this).find(".desc-input").val(); 
                let amount=$(this).find(".amount-input").val();
                updates.push({ item_id, description: desc, qty:1, price:amount });
            } else {
                // Entry
                let desc=$(this).find(".desc-input").val(); 
                let qty=$(this).find(".qty-input").val(); 
                let price=$(this).find(".price-input").val();
                updates.push({ item_id, description: desc, qty, price });
            }
        });
    });
    $.post("update_entries.php",{ updates: JSON.stringify(updates) },function(){
        location.reload();
    }).fail(function(){ alert("Error updating entries."); });
}


   $(document).on("click",".delete-row",function(){
    if(confirm("Delete this item?")){
        let descDiv = $(this).closest("td").siblings(".text-start").find(".desc, .desc-payment").first();
        let item_id = descDiv.data("id");
        $.post("delete_entry.php",{ item_id },function(){
            descDiv.closest("tr").remove(); 
        }).fail(function(){ alert("Error deleting entry."); });
    }
});

});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
