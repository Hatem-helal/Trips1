<?php
session_start();

// الاتصال بقاعدة البيانات
$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "project",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("فشل الاتصال: " . print_r(sqlsrv_errors(), true));
}

// المعالجة عند الإرسال
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
echo "<script>alert('A');</script>";
    if ($password !== $confirm_password) {
        echo "<script>alert('كلمتا المرور غير متطابقتين'); window.history.back();</script>";
        exit;
    }
echo "<script>alert('B!');</script>";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    echo "<script>alert('C!');</script>";
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    echo "<script>alert('تD');</script>";
    $params = [$username, $email, $hashedPassword];
    echo "<script>alert('Eنجاح!');</script>";
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        // التوجيه بعد إنشاء الحساب بنجاح
        header("Location: Log_in.html");
        exit;
    } else {
        echo "خطأ أثناء إنشاء الحساب: " . print_r(sqlsrv_errors(), true);
    }
}
?>