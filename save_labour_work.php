<?php
session_start();
if (!isset($_SESSION['admin'])) { echo json_encode(['status'=>'unauthorized']); exit; }
require 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) { echo json_encode(['status'=>'invalid']); exit; }

$worker_id = (int)$data['worker_id'];
$date = $conn->real_escape_string($data['work_date']);
$desc = $conn->real_escape_string($data['description']);
$price = (float)$data['price'];

$q = "INSERT INTO labour_work (worker_id, work_date, description, price)
      VALUES ($worker_id, '$date', '$desc', $price)";
if ($conn->query($q)) echo json_encode(['status'=>'success']);
else echo json_encode(['status'=>'error', 'error'=>$conn->error]);
?>
