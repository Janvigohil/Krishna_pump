<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Show MySQL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

        // Check if table exists before preparing statement
    $check = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($check->num_rows == 0) {
        die("Table 'admin' does not exist in the database.");
    }

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $user, $pass);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION["admin"] = $user;
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Login - Krishna Pump</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2 class="text-center mb-4">Admin Login</h2>
  <form method="POST" class="w-50 mx-auto p-4 border rounded bg-white shadow-sm">
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100">Login</button>
  </form>
</div>
</body>
</html>
