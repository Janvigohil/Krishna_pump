<?php
include 'db_config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $conn->query("DELETE FROM coupon_items WHERE id=$item_id");
    echo "deleted";
}
?>
