<?php
include 'db_config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = json_decode($_POST['updates'], true);
    foreach($updates as $u){
        $item_id = intval($u['item_id']);
        $description = $conn->real_escape_string($u['description']);
        $qty = intval($u['qty']);
        $price = floatval($u['price']);
        $line_total = $qty * $price;
        $conn->query("UPDATE coupon_items SET description='$description', qty=$qty, price=$price, line_total=$line_total WHERE id=$item_id");
    }
    echo "success";
}
?>
