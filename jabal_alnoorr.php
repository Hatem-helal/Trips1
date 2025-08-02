<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["username"])) {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// بيانات الرحلة
$trip_id = 2; // رقم رحلة جبل النور
$tripQuery = "SELECT trip_name, price_per_seat, available_seats FROM Trips WHERE trip_id = ?";
$tripStmt = sqlsrv_query($conn, $tripQuery, [$trip_id]);
$trip = sqlsrv_fetch_array($tripStmt, SQLSRV_FETCH_ASSOC);
$trip_name = $trip["trip_name"];
$price = $trip["price_per_seat"];
$available_seats = $trip["available_seats"];

// جلب الصور
$images = [];
$imageStmt = sqlsrv_query($conn, "SELECT image_path FROM TripImages WHERE trip_id = ?", [$trip_id]);
while ($img = sqlsrv_fetch_array($imageStmt, SQLSRV_FETCH_ASSOC)) {
    $images[] = $img['image_path'];
}

// بيانات المستخدم
$username = $_SESSION["username"];
$stmt = sqlsrv_query($conn, "SELECT id FROM users WHERE username = ?", [$username]);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$customer_id = $user["id"];

// تحقق من الحجز
$can_review = false;
$checkBooking = sqlsrv_query($conn, "SELECT * FROM Bookings WHERE customer_id = ? AND trip_id = ?", [$customer_id, $trip_id]);
if (sqlsrv_has_rows($checkBooking)) {
    $can_review = true;
}

// تنفيذ الحجز
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["seat_count"])) {
    $trip_day = $_POST["trip_day"];
    $payment_method = $_POST["payment_method"];
    $seat_count = $_POST["seat_count"];
    $booking_date = date("Y-m-d H:i:s");
    $booking_number = rand(10000, 99999);

    if ($seat_count > $available_seats) {
        echo "<script>alert('عذرًا، لا يوجد عدد كافٍ من المقاعد المتاحة.');</script>";
    } else {
        $insert = "INSERT INTO Bookings (trip_id, customer_id, seats_count, payment_method, booking_date, booking_number)
                   VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$trip_id, $customer_id, $seat_count, $payment_method, $booking_date, $booking_number];
        $done = sqlsrv_query($conn, $insert, $params);

        if ($done) {
            $updateSeats = "UPDATE Trips SET available_seats = available_seats - ? WHERE trip_id = ?";
            sqlsrv_query($conn, $updateSeats, [$seat_count, $trip_id]);

            if ($payment_method === "دفع إلكتروني") {
                header("Location: صفحة الدفع.html");
                exit;
            } elseif ($payment_method === "تحويل بنكي") {
                echo "<script>alert('تم الحجز!\\nرقم الحجز: $booking_number\\nالتحويل إلى:\\nبنك البلاد\\nرقم الحساب: 1234567890\\nآيبان: SA4420000000001234567890');</script>";
            } else {
                echo "<script>alert('تم الحجز!\\nرقم الحجز: $booking_number');</script>";
            }
        }
    }
}

// التقييم
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"])) {
    $rating = $_POST["rating"];
    $comment = $_POST["comment"];
    $review_date = date("Y-m-d H:i:s");

    $insertReview = "INSERT INTO Reviews (customer_id, trip_id, rating, comment, review_date)
                     VALUES (?, ?, ?, ?, ?)";
    $params = [$customer_id, $trip_id, $rating, $comment, $review_date];
    $doneReview = sqlsrv_query($conn, $insertReview, $params);

    if ($doneReview) {
        echo "<script>alert('تم إرسال تقييمك بنجاح');</script>";
    } else {
        echo "<script>alert('فشل إرسال التقييم');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>رحلة إلى جبل النور</title>
  <style>
    body {
      background: #f3f0e7;
      font-family: 'Tahoma', sans-serif;
      margin: 0;
      padding: 30px;
    }

    .trip-box {
      background-color: #fff;
      max-width: 700px;
      margin: auto;
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border: 1px solid #d6cfc2;
    }

    h2 {
      color: #8b6d35;
      text-align: center;
    }

    .slider {
      position: relative;
      height: 250px;
      margin-bottom: 20px;
    }

    .slider img {
      width: 100%;
      height: 250px;
      object-fit: cover;
      border-radius: 12px;
      display: none;
    }

    .slider img.active {
      display: block;
    }

    .slider button {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(0,0,0,0.5);
      color: white;
      border: none;
      padding: 10px;
      font-size: 18px;
      cursor: pointer;
    }

    .prev { left: 10px; }
    .next { right: 10px; }

    label { display: block; margin-top: 10px; font-weight: bold; }
    input, select, textarea {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      margin-top: 5px;
    }

    .confirm-btn {
      margin-top: 15px;
      width: 100%;
      background-color: #8b6d35;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 10px;
      cursor: pointer;
    }

    .confirm-btn:hover {
      background-color: #6f552c;
    }

    .review-box {
      border-bottom: 1px solid #ccc;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    .review-stars {
      color: #ffcc00;
      font-size: 18px;
    }
  </style>
</head>
<body>
<div class="trip-box">
  <h2>رحلة إلى <?= htmlspecialchars($trip_name) ?></h2>

  <div class="slider">
    <?php foreach ($images as $i => $img): ?>
      <img src="<?= htmlspecialchars($img) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
    <?php endforeach; ?>
    <?php if (count($images) > 1): ?>
      <button class="prev" onclick="changeSlide(-1)">‹</button>
      <button class="next" onclick="changeSlide(1)">›</button>
    <?php endif; ?>
  </div>

  <form method="POST">
    <label>يوم الانطلاق</label>
    <select name="trip_day" required>
      <option value="">-- اختر اليوم --</option>
      <option>الأحد</option><option>الاثنين</option><option>الثلاثاء</option>
      <option>الأربعاء</option><option>الخميس</option><option>الجمعة</option><option>السبت</option>
    </select>

    <label>طريقة الدفع</label>
    <select name="payment_method" required>
      <option value="">-- اختر --</option>
      <option>نقداً</option>
      <option>تحويل بنكي</option>
      <option>دفع إلكتروني</option>
    </select>

    <label>عدد المقاعد</label>
    <input type="number" name="seat_count" min="1" max="<?= $available_seats ?>" value="1" required>

    <div style="margin-top: 10px;">السعر: <?= $price ?> ر.س لكل مقعد</div>
    <div style="margin-top: 5px;">المقاعد المتاحة: <?= $available_seats ?></div>

    <button type="submit" class="confirm-btn">تأكيد الحجز</button>
  </form>

  <?php if ($can_review): ?>
    <form method="POST" style="margin-top: 30px;">
      <label>التقييم</label>
      <select name="rating" required>
        <option value="">-- اختر --</option>
        <option value="5">★★★★★</option>
        <option value="4">★★★★</option>
        <option value="3">★★★</option>
        <option value="2">★★</option>
        <option value="1">★</option>
      </select>

      <label>تعليق</label>
      <textarea name="comment" rows="4" required></textarea>
      <button type="submit" name="submit_review" class="confirm-btn">إرسال التقييم</button>
    </form>
  <?php endif; ?>

  <div style="margin-top: 40px;">
    <h3>آراء العملاء:</h3>
    <?php
    $reviews = sqlsrv_query($conn,
        "SELECT R.rating, R.comment, R.review_date, U.username
         FROM Reviews R JOIN users U ON R.customer_id = U.id
         WHERE R.trip_id = ? ORDER BY R.review_date DESC", [$trip_id]);
    while ($row = sqlsrv_fetch_array($reviews, SQLSRV_FETCH_ASSOC)):
    ?>
      <div class="review-box">
        <strong><?= htmlspecialchars($row["username"]) ?></strong><br>
        <div class="review-stars"><?= str_repeat("★", $row["rating"]) . str_repeat("☆", 5 - $row["rating"]) ?></div>
        <p><?= nl2br(htmlspecialchars($row["comment"])) ?></p>
        <small style="color:#888"><?= date_format($row["review_date"], "Y-m-d H:i") ?></small>
      </div>
    <?php endwhile; ?>
  </div>

  <a href="Trips.php" style="display:block; text-align:center; margin-top:25px; color:#8b6d35;">← العودة إلى جدول الرحلات</a>
</div>

<script>
let current = 0;
function changeSlide(step) {
  const imgs = document.querySelectorAll('.slider img');
  imgs[current].classList.remove('active');
  current = (current + step + imgs.length) % imgs.length;
  imgs[current].classList.add('active');
}
</script>
</body>
</html>