<?php
$conn = new mysqli("localhost", "root", "", "krishna_pump");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Customer Work - Krishna Pump</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f4f5fa; }
    .sidebar {
      width: 250px; height: 100vh;
      background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
      color: white; position: fixed; top: 0; left: 0; padding-top: 20px;
    }
    .sidebar a {
      color: white; text-decoration: none; display: block;
      padding: 12px 20px; transition: background 0.3s ease;
    }
    .sidebar a:hover { background: rgba(255,255,255,0.1); }
    .main-content { margin-left: 250px; padding: 20px; }
    .group-img {
      cursor: pointer; width: 100px; height: 100px;
      margin: 6px; object-fit: contain;
      border-radius: 10px; border: 2px solid transparent;
      transition: 0.3s ease;
    }
    .group-img:hover { border-color: #0d6efd; transform: scale(1.05); }
    .motor-button {
      cursor: pointer; padding: 8px 12px; margin: 6px;
      background-color: #ffffff; border: 1px solid #ced4da;
      border-radius: 5px; box-shadow: 1px 1px 3px rgba(0,0,0,0.1);
    }
    .motor-button:hover { background-color: #e9ecef; }
    .remove-btn { color: red; font-weight: bold; cursor: pointer; }
  </style>
</head>
<body>

<?php include 'sidebar.php';?>
<div class="main-content">
  <div class="card shadow">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Add Customer Work</h4>
    </div>
    <div class="card-body">
      <form id="work-form">
        <div class="row">
          <!-- Motor Groups -->
          <div class="col-md-6 border-end">
            <h5>Select Motor Group</h5>
            <div class="d-flex flex-wrap mb-3">
              <?php
              $groups = $conn->query("SELECT * FROM motor_groups");
              while($g = $groups->fetch_assoc()): ?>
                <img src="<?= $g['image_path'] ?>" 
                     class="group-img" 
                     data-id="<?= $g['id'] ?>" 
                     title="<?= $g['group_name'] ?>" 
                     alt="<?= $g['group_name'] ?>">
              <?php endwhile; ?>
            </div>
            <div id="motor-subtypes">
              <h6 class="text-secondary">Select Subtype</h6>
              <div id="subtype-buttons" class="d-flex flex-wrap"></div>
            </div>
          </div>

          <!-- Customer Info + Parts -->
          <div class="col-md-6">
            <h5 class="mt-3">Selected Parts</h5>
            <table class="table table-bordered" id="parts-table">
              <thead class="table-light">
                <tr>
                  <th style="width:60%">Part Name</th>
                  <th style="width:25%">Price (₹)</th>
                  <th style="width:15%">Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>

            <div class="mb-3">
              <label>Customer Name:</label>
              <input type="text" id="customer_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Customer Contact:</label>
              <input type="text" id="customer_contact" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Date:</label>
              <input type="date" id="work_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-3">
              <label>Payment Method:</label>
              <select id="payment_method" class="form-control" required>
                <option value="">Select</option>
                <option value="Cash">Cash</option>
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>
            <div class="mb-3">
              <label>Total Amount:</label>
              <input type="number" id="final_bill" class="form-control" readonly>
            </div>
            <button type="submit" class="btn btn-success w-100">Generate PDF</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let parts = [];

// Load subtypes dynamically
document.querySelectorAll('.group-img').forEach(img => {
  img.onclick = () => {
    const groupId = img.dataset.id;
    fetch(`get_subtypes.php?group_id=${groupId}`)
      .then(res => res.json())
      .then(data => {
        const container = document.getElementById('subtype-buttons');
        container.innerHTML = '';
        data.forEach(item => {
          const btn = document.createElement('div');
          btn.className = 'motor-button';
          btn.innerText = item.name;
          btn.onclick = () => addPart(item.name);
          container.appendChild(btn);
        });
      });
  };
});

// Add part with manual price
function addPart(name) {
  const tbody = document.querySelector("#parts-table tbody");
  const row = document.createElement("tr");

  row.innerHTML = `
    <td>${name}</td>
    <td><input type="number" class="form-control part-price" min="0" value="0"></td>
    <td><span class="remove-btn" onclick="removePart(this)">&times;</span></td>
  `;

  tbody.appendChild(row);

  // recalc when price changes
  row.querySelector(".part-price").addEventListener("input", updateTotal);
}

// Remove part
function removePart(el) {
  el.closest("tr").remove();
  updateTotal();
}

// Update total dynamically
function updateTotal() {
  let total = 0;
  document.querySelectorAll(".part-price").forEach(input => {
    total += parseFloat(input.value) || 0;
  });
  document.getElementById("final_bill").value = total;
}

// Submit → generate PDF
document.getElementById("work-form").onsubmit = function(e) {
  e.preventDefault();

  const customerName = document.getElementById("customer_name").value.trim();
  const customerContact = document.getElementById("customer_contact").value.trim();
  const workDate = document.getElementById("work_date").value;
  const paymentMethod = document.getElementById("payment_method").value;
  const billAmount = parseFloat(document.getElementById("final_bill").value);

  const partRows = document.querySelectorAll("#parts-table tbody tr");
  parts = [];
  partRows.forEach(row => {
    const name = row.querySelector("td:first-child").innerText.trim();
    const price = parseFloat(row.querySelector(".part-price").value) || 0;
    if (name) parts.push({ desc: name, price });
  });

  if (!customerName || !customerContact || !workDate || !paymentMethod || parts.length === 0) {
    alert("Please fill all fields and select at least one part.");
    return;
  }

  // Build hidden form to send data to PDF generator
  const form = document.createElement("form");
  form.method = "POST";
  form.action = "generate_customer_pdf.php";
  form.target = "_blank";

  form.innerHTML = `
    <input type="hidden" name="invoice_no" value="${Date.now()}">
    <input type="hidden" name="date" value="${workDate}">
    <input type="hidden" name="name" value="${customerName}">
    <input type="hidden" name="address" value="${customerContact}">
    <input type="hidden" name="payment_method" value="${paymentMethod}">
  `;

  parts.forEach((p, i) => {
    form.innerHTML += `<input type="hidden" name="items[${i}][desc]" value="${p.desc}">`;
    form.innerHTML += `<input type="hidden" name="items[${i}][price]" value="${p.price}">`;
  });

  document.body.appendChild(form);
  form.submit();
  form.remove();
};
</script>

</body>
</html>
