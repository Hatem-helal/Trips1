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

// التحقق من تسجيل الدخول
if (!isset($_SESSION['username'])) {
    die("الرجاء تسجيل الدخول أولاً.");
}

// استقبال البيانات من النموذج
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $trip_id = $_POST["trip_id"];
    $seats_count = $_POST["seat_count"];
    $payment_method = $_POST["payment_method"];
    $booking_date = date("Y-m-d");

    // الحصول على customer_id من جدول users
    $username = $_SESSION['username'];
    $getCustomerSql = "SELECT id FROM users WHERE username = ?";
    $getCustomerStmt = sqlsrv_query($conn, $getCustomerSql, [$username]);
    if ($row = sqlsrv_fetch_array($getCustomerStmt, SQLSRV_FETCH_ASSOC)) {
        $customer_id = $row['id'];

        // إدخال الحجز
        $insertSql = "INSERT INTO bookings (trip_id, customer_id, seats_count, payment_method, booking_date)
                      VALUES (?, ?, ?, ?, ?)";
        $params = [$trip_id, $customer_id, $seats_count, $payment_method, $booking_date];
        $stmt = sqlsrv_query($conn, $insertSql, $params);

        if ($stmt) {
            echo "<script>alert('تم تأكيد الحجز بنجاح!'); window.location.href='حجوزاتي.php';</script>";
        } else {
            echo "فشل في حفظ الحجز: " . print_r(sqlsrv_errors(), true);
        }
    } else {
        echo "المستخدم غير موجود.";
    }
    sqlsrv_close($conn);
}
?>