<?php
session_start();
if (!isset($_SESSION['admin'])) { echo json_encode(['status'=>'unauthorized']); exit; }
require 'db_config.php';

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['id'])) { echo json_encode(['status'=>'invalid']); exit; }

$id = (int)$data['id'];
$conn->query("DELETE FROM journal WHERE id=$id");
echo json_encode(['status'=>'success']);
?>
