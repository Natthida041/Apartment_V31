<?php
// config.php
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = ''; // ใส่รหัสผ่านที่ถูกต้องของ MySQL
$dbName = 'apartment_manage2';

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>