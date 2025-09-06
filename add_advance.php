<?php
include 'db_config.php';

$worker_id = intval($_POST['worker_id']);
$amount = floatval($_POST['amount']);
$method = $_POST['method'];

if ($worker_id && $amount && in_array($method, ['cash', 'gpay'])) {
    $stmt = $conn->prepare("INSERT INTO advance_salary (worker_id, amount, cash_gpay) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $worker_id, $amount, $method);
    $stmt->execute();
}

header("Location: worker_detail.php?id=$worker_id");
exit;
?>
