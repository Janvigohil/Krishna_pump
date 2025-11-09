<?php
include 'db_config.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

$id = 0;
if (isset($data['id'])) {
    $id = (int)$data['id'];
} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
} elseif (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
}

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM labour_work WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    echo "Invalid request — No ID received.";
}
?>
