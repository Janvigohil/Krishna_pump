<?php
include 'db_config.php';
$data=json_decode(file_get_contents("php://input"),true);
$id=(int)$data['id']; $price=(float)$data['price'];
$conn->query("UPDATE labour_work SET price=$price WHERE id=$id");
echo json_encode(["success"=>true]);
?>
