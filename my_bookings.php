<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) die(print_r(sqlsrv_errors(), true));

$username = $_SESSION["username"];
$stmt = sqlsrv_query($conn, "SELECT id, email FROM users WHERE username = ?", [$username]);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$customer_id = $user["id"];
$customer_email = $user["email"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_id"])) {
    $booking_id = $_POST["cancel_id"];

    $getBooking = sqlsrv_query($conn, "SELECT trip_id, seats_count, booking_number FROM Bookings WHERE booking_id = ? AND customer_id = ?", [$booking_id, $customer_id]);
    $booking = sqlsrv_fetch_array($getBooking, SQLSRV_FETCH_ASSOC);

    if ($booking) {
        sqlsrv_query($conn, "DELETE FROM Bookings WHERE booking_id = ? AND customer_id = ?", [$booking_id, $customer_id]);
        sqlsrv_query($conn, "UPDATE Trips SET available_seats = available_seats + ? WHERE trip_id = ?", [$booking["seats_count"], $booking["trip_id"]]);

        // إرسال رسالة إلى الأدمن
        $message = "قام المستخدم '$username' بإلغاء الحجز.\nرقم الحجز: {$booking['booking_number']}\nالبريد الإلكتروني: $customer_email";
        $insertMsg = sqlsrv_query($conn, "INSERT INTO Messages (user_id, message_text, sent_at) VALUES (?, ?, GETDATE())", [$customer_id, $message]);
        if (!$insertMsg) {
            die("فشل إرسال الرسالة: " . print_r(sqlsrv_errors(), true));
        }

        echo "<script>alert('تم إلغاء الحجز بنجاح'); window.location.href = 'my_bookings.php';</script>";
        exit;
    }
}

$query = "
    SELECT B.booking_id, T.trip_name, B.seats_count, B.payment_method, B.booking_date, B.booking_number
    FROM Bookings B
    JOIN Trips T ON B.trip_id = T.trip_id
    WHERE B.customer_id = ?
    ORDER BY B.booking_date DESC
";
$bookingsStmt = sqlsrv_query($conn, $query, [$customer_id]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="30">
  <title>حجوزاتي</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #f4f1eb;
      font-family: 'Tajawal', sans-serif;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 700px;
      margin: 50px auto;
      background: #fff;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      border: 1px solid #e0d6c3;
    }
    h2 {
      text-align: center;
      color: #8b6d35;
      margin-bottom: 25px;
    }
    .booking-item {
      background: #f9f8f4;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
    }
    .booking-item p {
      margin: 8px 0;
      font-size: 15px;
    }
    form {
      text-align: left;
      margin-top: 10px;
    }
    .cancel-btn {
      background-color: #a94444;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
    }
    .cancel-btn:hover {
      background-color: #892e2e;
    }
    .no-bookings {
      text-align: center;
      color: #888;
      margin-top: 20px;
      font-size: 16px;
    }
    .back-link {
      display: block;
      text-align: center;
      margin: 20px auto 0;
      text-decoration: none;
      color: #8b6d35;
      font-weight: bold;
      font-size: 16px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>حجوزاتي</h2>
    <?php if (!sqlsrv_has_rows($bookingsStmt)): ?>
      <div class="no-bookings">لا توجد حجوزات حالياً.</div>
    <?php else: ?>
      <?php while ($row = sqlsrv_fetch_array($bookingsStmt, SQLSRV_FETCH_ASSOC)): ?>
        <div class="booking-item">
          <p><strong>الرحلة:</strong> <?= htmlspecialchars($row["trip_name"]) ?></p>
          <p><strong>عدد المقاعد:</strong> <?= $row["seats_count"] ?></p>
          <p><strong>طريقة الدفع:</strong> <?= htmlspecialchars($row["payment_method"]) ?></p>
          <p><strong>تاريخ الحجز:</strong> <?= date_format($row["booking_date"], "Y-m-d H:i") ?></p>
          <p><strong>رقم الحجز:</strong> <?= $row["booking_number"] ?></p>
          <form method="POST">
            <input type="hidden" name="cancel_id" value="<?= $row["booking_id"] ?>">
            <button class="cancel-btn" onclick="return confirm('هل أنت متأكد من إلغاء الحجز؟')">إلغاء الحجز</button>
          </form>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
    <a href="homee.php" class="back-link">← العودة إلى الصفحة الرئيسية</a>
  </div>
</body>
</html>