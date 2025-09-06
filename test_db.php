<?php
$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'krishna_pump';

$start = microtime(true);
$conn = new mysqli($host, $user, $password, $database);
$end = microtime(true);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected in " . round(($end - $start), 3) . " seconds";
$conn->close();
?>
