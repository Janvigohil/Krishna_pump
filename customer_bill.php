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
    body {
      background-color: #f4f5fa;
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
    .group-img {
      cursor: pointer;
      width: 100px;
      height: 100px;
      margin: 6px;
      object-fit: contain;
      border-radius: 10px;
      border: 2px solid transparent;
      transition: 0.3s ease;
    }
    .group-img:hover {
      border-color: #0d6efd;
      transform: scale(1.05);
    }
    .motor-button {
      cursor: pointer;
      padding: 8px 12px;
      margin: 6px;
      background-color: #ffffff;
      border: 1px solid #ced4da;
      border-radius: 5px;
      box-shadow: 1px 1px 3px rgba(0,0,0,0.1);
    }
    .motor-button:hover {
      background-color: #e9ecef;
    }
    .remove-btn {
      color: red;
      font-weight: bold;
      margin-left: 10px;
      cursor: pointer;
    }
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
      <div class="row">
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

        <div class="col-md-6">
          <h5 class="mt-3">Selected Parts</h5>
          <ul id="selected-parts" class="list-group mb-3"></ul>

          <div class="mb-3">
            <label>Customer Name:</label>
            <input type="text" id="customer_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Customer Contact:</label>
            <input type="text" id="customer_contact" class="form-control" required>
          </div>

          <div class="mb-3">
            <label>Final Bill Amount:</label>
            <input type="number" step="1" id="final_bill" class="form-control" placeholder="Enter bill amount">
          </div>

          <button class="btn btn-success w-100" id="save-work">Save & Download PDF</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let selectedParts = [];

// Load subtypes
const groupImages = document.querySelectorAll('.group-img');
groupImages.forEach(img => {
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
          btn.innerText = `${item.name}`;
          btn.onclick = () => addToSelection(item.id, item.name);
          container.appendChild(btn);
        });
      });
  };
});

function addToSelection(id, name) {
  const part = { id, name };
  selectedParts.push(part);
  const li = document.createElement('li');
  li.className = "list-group-item d-flex justify-content-between align-items-center";
  li.innerHTML = `<span>${name}</span><span class='remove-btn' onclick='removePart(this)'>&times;</span>`;
  document.getElementById('selected-parts').appendChild(li);
}

function removePart(el) {
  const index = [...el.parentNode.parentNode.children].indexOf(el.parentNode);
  selectedParts.splice(index, 1);
  el.parentNode.remove();
}

// Save & Download
  document.getElementById('save-work').onclick = () => {
    const customerName = document.getElementById('customer_name').value.trim();
    const customerContact = document.getElementById('customer_contact').value.trim();
    const billAmount = parseFloat(document.getElementById('final_bill').value);

    if (!customerName || !customerContact || !billAmount || selectedParts.length === 0) {
      alert("Please fill in all fields and select parts.");
      return;
    }

    fetch("save_customer_work.php", {
      method: "POST",
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        customer_name: customerName,
        customer_contact: customerContact,
        bill_amount: billAmount,
        parts: selectedParts
      })
    })
    .then(res => res.json())
    .then(response => {
      if (response.status === "success") {
        window.open(`generate_customer_pdf.php?id=${response.id}`, '_blank');
        location.reload();
      } else {
        alert("Failed to save work.");
      }
    });
  };
</script>
</body>
</html>
