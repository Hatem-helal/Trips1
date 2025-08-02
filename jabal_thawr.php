<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["username"])) {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "project",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) die(print_r(sqlsrv_errors(), true));

// بيانات الرحلة
$trip_id = 3; // رقم رحلة جبل ثور
$tripStmt = sqlsrv_query($conn, "SELECT trip_name, price_per_seat, available_seats FROM Trips WHERE trip_id = ?", [$trip_id]);
$trip = sqlsrv_fetch_array($tripStmt, SQLSRV_FETCH_ASSOC);
$trip_name = $trip["trip_name"];
$price = $trip["price_per_seat"];
$available_seats = $trip["available_seats"];

// صور الرحلة
$images = [];
$imgQuery = sqlsrv_query($conn, "SELECT image_path FROM TripImages WHERE trip_id = ?", [$trip_id]);
while ($img = sqlsrv_fetch_array($imgQuery, SQLSRV_FETCH_ASSOC)) {
    $images[] = $img["image_path"];
}

// بيانات المستخدم
$username = $_SESSION["username"];
$stmt = sqlsrv_query($conn, "SELECT id FROM users WHERE username = ?", [$username]);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$customer_id = $user["id"];

// تحقق من وجود حجز
$can_review = false;
$checkBooking = sqlsrv_query($conn, "SELECT * FROM Bookings WHERE customer_id = ? AND trip_id = ?", [$customer_id, $trip_id]);
if (sqlsrv_has_rows($checkBooking)) $can_review = true;

// تنفيذ الحجز
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["seat_count"])) {
    $trip_day = $_POST["trip_day"];
    $payment_method = $_POST["payment_method"];
    $seat_count = $_POST["seat_count"];
    $booking_date = date("Y-m-d H:i:s");
    $booking_number = rand(10000, 99999);

    if ($seat_count > $available_seats) {
        echo "<script>alert('لا يوجد عدد كافٍ من المقاعد المتاحة');</script>";
    } else {
        sqlsrv_query($conn,
            "INSERT INTO Bookings (trip_id, customer_id, seats_count, payment_method, booking_date, booking_number)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$trip_id, $customer_id, $seat_count, $payment_method, $booking_date, $booking_number]
        );
        sqlsrv_query($conn, "UPDATE Trips SET available_seats = available_seats - ? WHERE trip_id = ?", [$seat_count, $trip_id]);

        if ($payment_method === "دفع إلكتروني") {
            header("Location: صفحة الدفع.html");
            exit;
        } else {
            echo "<script>alert('تم الحجز بنجاح. رقم الحجز: $booking_number');</script>";
        }
    }
}

// تنفيذ التقييم
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"])) {
    $rating = $_POST["rating"];
    $comment = $_POST["comment"];
    $review_date = date("Y-m-d H:i:s");
    sqlsrv_query($conn,
        "INSERT INTO Reviews (customer_id, trip_id, rating, comment, review_date)
         VALUES (?, ?, ?, ?, ?)",
        [$customer_id, $trip_id, $rating, $comment, $review_date]
    );
    echo "<script>alert('تم إرسال تقييمك بنجاح');</script>";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="30">
  <title>رحلة <?= htmlspecialchars($trip_name) ?></title>
  <style>
    body { font-family: 'Tajawal', sans-serif; background: #f3f0e7; margin: 0; padding: 30px; }
    .trip-box { background: white; padding: 25px; border-radius: 15px; max-width: 700px; margin: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .slider { position: relative; height: 250px; overflow: hidden; border-radius: 12px; margin-bottom: 20px; }
    .slider img { width: 100%; height: 250px; object-fit: cover; display: none; }
    .slider img.active { display: block; }
    .slider button { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.4); color: white; border: none; padding: 8px; cursor: pointer; font-size: 20px; }
    .slider .prev { left: 10px; } .slider .next { right: 10px; }
    label { font-weight: bold; margin-top: 10px; display: block; }
    input, select, textarea { width: 100%; padding: 10px; margin-top: 5px; border-radius: 8px; border: 1px solid #ccc; }
    .confirm-btn { background: #8b6d35; color: white; border: none; padding: 12px; width: 100%; margin-top: 15px; border-radius: 10px; }
    .review-box { border-bottom: 1px solid #ddd; margin-top: 20px; padding-bottom: 10px; }
    .review-stars { color: #ffcc00; font-size: 18px; }
  </style>
</head>
<body>
<div class="trip-box">
  <h2 style="color:#8b6d35;">رحلة إلى <?= htmlspecialchars($trip_name) ?></h2>

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
    <label>اليوم</label>
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
    <input type="number" name="seat_count" id="seat_count" min="1" max="<?= $available_seats ?>" value="1" required>

    <div style="margin-top: 10px;">السعر: <span id="price"><?= $price ?></span> ر.س</div>
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

  <div class="review-section">
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
        <small style="color:#777"><?= date_format($row["review_date"], "Y-m-d H:i") ?></small>
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

document.getElementById('seat_count').addEventListener('input', function () {
  const count = parseInt(this.value) || 1;
  document.getElementById('price').textContent = count * <?= $price ?>;
});
</script>
</body>
</html>