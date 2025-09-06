<?php
// echo "db_config included<br>";
$host = 'localhost'; 
$user = 'root';
$password = ''; 
$database = 'krishna_pump';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "db_config connected<br>";
?>
