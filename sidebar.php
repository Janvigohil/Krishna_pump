<?php
// sidebar.php
?>
<style>
  body {
    margin-left: 0;
    font-family: Arial, sans-serif;
  }

  /* Sidebar */
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: #2c3e50;
    color: white;
    padding-top: 20px;
    transition: all 0.3s ease;
    overflow-y: auto;
    z-index: 1000;
  }
  .sidebar.collapsed {
    left: -250px;
  }

  .sidebar h4 {
    text-align: center;
    font-weight: bold;
    margin-bottom: 20px;
  }
  .sidebar .user-box {
    text-align: center;
    margin-bottom: 30px;
  }
  .sidebar .user-box img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin-bottom: 10px;
  }
  .sidebar .nav-link {
    color: white;
    padding: 12px 20px;
    display: block;
    text-decoration: none;
    transition: background 0.3s;
  }
  .sidebar .nav-link:hover,
  .sidebar .nav-link.active {
    background: rgba(255,255,255,0.1);
  }

  /* Toggle Button */
  .toggle-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1100;
    background: #2c3e50;
    color: white;
    border: none;
    padding: 10px 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: left 0.3s ease;
  }

  /* Main Wrapper (Fix here) */
  .main-wrapper {
    margin-left: 250px;  /* default shift */
    transition: margin-left 0.3s ease;
    padding: 20px;
  }
  .sidebar.collapsed ~ .main-wrapper {
    margin-left: 0 !important;
  }
</style>

<div class="sidebar" id="sidebar">
  <h4>⚙️ Krishna Pump</h4>
  
  <a href="index.php" class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='index.php'){echo 'active';} ?>">🏠 Dashboard</a>
  <a href="workers_work.php" class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='workers_work.php'){echo 'active';} ?>">👷 Workers Work</a>
  <a href="customer_bill.php" class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='customer_bill.php'){echo 'active';} ?>">🔧 Repairing Bill</a>
  <a href="motor_bill.php" class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='motor_bill.php'){echo 'active';} ?>">📑 Motor Bill</a>
  <a href="add_work.php" class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='add_work.php'){echo 'active';} ?>">➕ Add Work</a>
  <a href="companies.php" class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='companies.php'){echo 'active';} ?>">📊 Balance Sheet</a>
  <a href="journal.php" class="nav-link <?php if(basename($_SERVER['PHP_SELF'])=='journal.php'){echo 'active';} ?>"> 📓 General</a>
  <a href="logout.php" class="nav-link text-danger">🚪 Logout</a>
</div>

<button class="toggle-btn" onclick="toggleSidebar()">☰</button>

<div class="main-wrapper">
  <!-- 👇 Aa main-wrapper andar tamaru page content mukhvu -->
  <?php
  // index.php / other pages content
  ?>
</div>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
  }
</script>
