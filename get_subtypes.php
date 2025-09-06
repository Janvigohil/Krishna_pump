<?php
$conn = new mysqli("localhost", "root", "", "krishna_pump");

$group_id = $_GET['group_id'];
$data = [];

$result = $conn->query("SELECT id, name, cost FROM motor_parts WHERE group_id = $group_id");
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'cost' => $row['cost']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
