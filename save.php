<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

// لا تطبع رسالة الاتصال هنا
?>
