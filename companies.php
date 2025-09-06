<?php
include 'db_config.php'; 
include 'sidebar.php'; 

// Fetch all companies
$sql = "SELECT id, name FROM companies WHERE status=1 ORDER BY name ASC";
$result = $conn->query($sql);
?>

<!-- Page Content -->
<div class="content" style="margin-left:250px; padding:20px;">
  <div class="container-fluid">

    <!-- Page Title -->
    <div class="card shadow-sm mb-4 border-0">
      <div class="card-body bg-gradient text-white rounded" 
           style="background: linear-gradient(90deg, #007bff, #00c6ff);">
        <h3 class="mb-0"><i class="fas fa-building me-2"></i> Companies</h3>
      </div>
    </div>

    <!-- Companies List -->
    <div class="row">
      <?php if ($result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <div class="col-md-4 mb-4">
            <div class="card company-card shadow-lg border-0 h-100">
              <div class="card-body d-flex flex-column justify-content-between">
                <h5 class="card-title fw-bold text-dark text-center">
                  <?= htmlspecialchars($row['name']); ?>
                </h5>
                <a href="balance_sheet.php?company_id=<?= $row['id']; ?>" 
                   class="btn btn-primary mt-3 w-100">
                   <i class="fas fa-file-invoice-dollar me-2"></i> View Balance Sheet
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-warning shadow-sm">
            ⚠ No companies found in database.
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Bootstrap & FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  .company-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 15px;
  }
  .company-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
  }
  .card-title {
    font-size: 1.2rem;
    color: #2c3e50;
  }
</style>
