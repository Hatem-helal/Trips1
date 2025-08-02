<?php
session_start();

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "project",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// تنفيذ الإلغاء إذا تم الضغط على زر
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_id"])) {
    $cancel_id = $_POST["cancel_id"];
    $delete = "DELETE FROM Bookings WHERE booking_id = ?";
    sqlsrv_query($conn, $delete, [$cancel_id]);
}

// جلب بيانات الحجوزات
$sql = "
SELECT b.booking_id, u.username, t.trip_name, b.seats_count, b.payment_method, b.booking_date
FROM Bookings b
JOIN users u ON b.customer_id = u.id
JOIN Trips t ON b.trip_id = t.trip_id
ORDER BY b.booking_date DESC
";
$stmt = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إدارة الحجوزات</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Tajawal', sans-serif;
      background-color: #f3f0e7;
      padding: 30px;
    }

    h2 {
      text-align: center;
      color: #8b6d35;
      margin-bottom: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 15px;
      text-align: center;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #8b6d35;
      color: white;
    }

    tr:hover {
      background-color: #f5f5f5;
    }

    form {
      display: inline-block;
    }

    .btn-cancel {
      background-color: #d9534f;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 8px;
      cursor: pointer;
    }

    .btn-cancel:hover {
      background-color: #c9302c;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 25px;
      color: #8b6d35;
      font-weight: bold;
      text-decoration: none;
    }

    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <h2>جميع الحجوزات</h2>

  <table>
    <tr>
      <th>رقم الحجز</th>
      <th>اسم المستخدم</th>
      <th>اسم الرحلة</th>
      <th>عدد المقاعد</th>
      <th>طريقة الدفع</th>
      <th>تاريخ الحجز</th>
      <th>إجراء</th>
    </tr>

    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
    <tr>
      <td><?= $row["booking_id"] ?></td>
      <td><?= htmlspecialchars($row["username"]) ?></td>
      <td><?= htmlspecialchars($row["trip_name"]) ?></td>
      <td><?= $row["seats_count"] ?></td>
      <td><?= htmlspecialchars($row["payment_method"]) ?></td>
      <td><?= $row["booking_date"]->format('Y-m-d H:i') ?></td>
      <td>
        <form method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟');">
          <input type="hidden" name="cancel_id" value="<?= $row["booking_id"] ?>">
          <button type="submit" class="btn-cancel">إلغاء</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>

  <a href="admin_dashboard.php" class="back-link">العودة إلى لوحة التحكم</a>

</body>
</html>