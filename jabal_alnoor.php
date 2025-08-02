<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION["username"])) {
    header("Location: Log_in.html");
    exit;
}

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
    die("فشل الاتصال بقاعدة البيانات: " . print_r(sqlsrv_errors(), true));
}

// تنفيذ الحجز عند الضغط على تأكيد
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $trip_id = 2; // رقم رحلة جبل النور
    $trip_day = $_POST["trip_day"];
    $payment_method = $_POST["payment_method"];
    $seat_count = $_POST["seat_count"];
    $booking_date = date("Y-m-d H:i:s");

    // جلب customer_id من اسم المستخدم
    $username = $_SESSION["username"];
    $stmt = sqlsrv_query($conn, "SELECT id FROM users WHERE username = ?", [$username]);
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$user) {
        die("تعذر جلب بيانات المستخدم: " . print_r(sqlsrv_errors(), true));
    }

    $customer_id = $user["id"];

    // حفظ الحجز في قاعدة البيانات
    $insert = "INSERT INTO Bookings (trip_id, customer_id, seats_count, payment_method, booking_date)
               VALUES (?, ?, ?, ?, ?)";
    $params = [$trip_id, $customer_id, $seat_count, $payment_method, $booking_date];
    $done = sqlsrv_query($conn, $insert, $params);

    if ($done) {
        header("Location: اتمام الدفع.html");
        exit;
    } else {
        die("فشل في إدخال الحجز: " . print_r(sqlsrv_errors(), true));
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>رحلة جبل النور</title>
  <style>
    body {
      background-color: #f3f0e7;
      font-family: 'Tahoma', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
    }
    .trip-box {
      background-color: #fff;
      border-radius: 20px;
      box-shadow: 0px 5px 15px rgba(0,0,0,0.1);
      width: 360px;
      padding: 30px;
      text-align: center;
      border: 1px solid #e0d6c3;
    }
    h2 { margin-bottom: 20px; color: #8b6d35; }
    label { display: block; text-align: right; margin-top: 15px; font-weight: bold; }
    input[type="number"], select {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 8px;
      border: 1px solid #ccc;
      text-align: right;
      font-size: 14px;
    }
    .confirm-btn {
      background-color: #bfa76f;
      color: white;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 15px;
    }
    .confirm-btn:hover { background-color: #a58f4d; }
  </style>
</head>
<body>

<div class="trip-box">
  <h2>رحلة جبل النور</h2>
  <form method="POST">
    <label for="trip_day">يوم الانطلاق</label>
    <select name="trip_day" required>
      <option value="">-- اختر اليوم --</option>
      <option value="الأحد">الأحد</option>
      <option value="الاثنين">الاثنين</option>
      <option value="الثلاثاء">الثلاثاء</option>
      <option value="الأربعاء">الأربعاء</option>
      <option value="الخميس">الخميس</option>
      <option value="الجمعة">الجمعة</option>
      <option value="السبت">السبت</option>
    </select>

    <label for="payment_method">طريقة الدفع</label>
    <select name="payment_method" required>
      <option value="">-- اختر --</option>
      <option value="نقداً">نقداً</option>
      <option value="تحويل بنكي">تحويل بنكي</option>
      <option value="دفع إلكتروني">دفع إلكتروني</option>
    </select>

    <label for="seat_count">عدد المقاعد</label>
    <input type="number" name="seat_count" min="1" max="12" value="1" required>

    <button type="submit" class="confirm-btn">تأكيد الحجز</button>
  </form>
</div>

</body>
</html>