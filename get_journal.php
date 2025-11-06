<?php
session_start();
if (!isset($_SESSION['admin'])) { echo json_encode(['status'=>'unauthorized']); exit; }
require 'db_config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = $conn->query("SELECT * FROM journal WHERE id=$id");

if ($res && $res->num_rows) {
  echo json_encode(['status'=>'success', 'entry'=>$res->fetch_assoc()]);
} else {
  echo json_encode(['status'=>'not_found']);
}
?>
