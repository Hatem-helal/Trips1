<?php
session_start();

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

// تأكد أن المستخدم مسجل دخوله
if (!isset($_SESSION["username"])) {
    die("يجب تسجيل الدخول أولاً");
}

// استقبال البيانات من النموذج
$trip_id = $_POST["trip_id"];
$seat_count = $_POST["seat_count"];
$payment_method = $_POST["payment_method"];
$username = $_SESSION["username"];

// الحصول على customer_id من اسم المستخدم
$sql_user = "SELECT id FROM users WHERE username = ?";
$params_user = [$username];
$stmt_user = sqlsrv_query($conn, $sql_user, $params_user);
$user = sqlsrv_fetch_array($stmt_user, SQLSRV_FETCH_ASSOC);
$customer_id = $user["id"];

// حفظ الحجز
$sql = "INSERT INTO Bookings (trip_id, customer_id, seats_count, payment_method, booking_date)
        VALUES (?, ?, ?, ?, GETDATE())";
$params = [$trip_id, $customer_id, $seat_count, $payment_method];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    header("Location: اتمام الدفع.html");
    exit;
} else {
    echo "فشل الحجز: " . print_r(sqlsrv_errors(), true);
}
?>