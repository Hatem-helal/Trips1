<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
print_r($_POST);
echo "</pre>";
$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "project",
    "Uid" => "", // Windows Authentication
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("فشل الاتصال: " . print_r(sqlsrv_errors(), true));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {
        echo "<script>alert('كلمتا المرور غير متطابقتين'); window.history.back();</script>";
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $params = [$username, $email, $hashedPassword];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        echo "<script>alert('تم إنشاء الحساب بنجاح'); window.location.href='Log_in.html';</script>";
    } else {
        echo "<script>alert('فشل في إنشاء الحساب');</script>";
        echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>";
    }
}
?>