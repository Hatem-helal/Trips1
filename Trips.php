<?php
session_start();

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

$sql = "
    SELECT 
        T.trip_id,
        T.trip_name,
        T.price_per_seat,
        T.available_seats,
        ISNULL(AVG(CAST(R.rating AS FLOAT)), 0) AS avg_rating
    FROM Trips T
    LEFT JOIN Reviews R ON T.trip_id = R.trip_id
    GROUP BY T.trip_id, T.trip_name, T.price_per_seat, T.available_seats
";
$stmt = sqlsrv_query($conn, $sql);
$trips = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // جلب الصور الخاصة بكل رحلة
    $trip_id = $row['trip_id'];
    $imageStmt = sqlsrv_query($conn, "SELECT image_path FROM TripImages WHERE trip_id = ?", [$trip_id]);
    $images = [];
    while ($img = sqlsrv_fetch_array($imageStmt, SQLSRV_FETCH_ASSOC)) {
        $images[] = $img['image_path'];
    }
    $row['images'] = $images;
    $trips[] = $row;
}

function getTripPage($tripName) {
    $map = [
        'جبل النور' => 'jabal_alnoorr.php',
        'جبل ثور' => 'jabal_thawr.php',
        'جبل عرفات' => 'jabal_arafat.php',
        'ساحة الحرم' => 'sahat_alharam.php',
    ];
    return $map[$tripName] ?? '#';
}

$homeLink = ($_SESSION["role"] ?? "") === "admin" ? "admin_dashboard.php" : "homee.php";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="10">
  <title>جدول الرحلات</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { background-color: #f3f0e7; font-family: 'Tajawal', sans-serif; margin: 0; padding: 0; }
    h1 { text-align: center; padding: 30px 0; color: #8b6d35; }
    .container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; padding: 20px; }
    .trip-card { background: #fff; border: 1px solid #d4c5a9; border-radius: 15px; width: 300px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: right; overflow: hidden; }

    .slider { position: relative; width: 100%; height: 180px; overflow: hidden; }
    .slider img { position: absolute; top: 0; left: 0; width: 100%; height: 180px; object-fit: cover; display: none; }
    .slider img.active { display: block; }
    .slider button { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.4); color: white; border: none; font-size: 20px; padding: 5px 10px; cursor: pointer; z-index: 1; border-radius: 5px; }
    .slider .prev { left: 10px; }
    .slider .next { right: 10px; }

    .trip-content { padding: 15px; }
    .trip-name { font-size: 20px; font-weight: bold; color: #8b6d35; margin-bottom: 10px; }
    .price, .seats, .rating { margin-bottom: 8px; font-size: 15px; }
    .stars { color: #ffcc00; font-size: 18px; }
    .btn { display: block; background-color: #8b6d35; color: white; text-align: center; padding: 10px; margin: 10px 0; border-radius: 8px; text-decoration: none; font-weight: bold; }
    .btn:hover { background-color: #6d542a; }
    .home-btn { display: inline-block; background-color: #8b6d35; color: white; padding: 10px 25px; border-radius: 10px; text-decoration: none; font-size: 16px; }
    .home-btn:hover { background-color: #6d542a; }
  </style>
</head>
<body>
  <h1>جدول الرحلات</h1>
  <div class="container">
    <?php foreach ($trips as $trip): 
      $page = getTripPage($trip["trip_name"]);
      $avg = floatval($trip["avg_rating"]);
      $fullStars = floor($avg);
      $halfStar = ($avg - $fullStars >= 0.5);
      $images = $trip["images"];
    ?>
    <div class="trip-card">
      <div class="slider">
        <?php foreach ($images as $i => $imgPath): ?>
          <img src="<?= htmlspecialchars($imgPath) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
        <?php endforeach; ?>
        <?php if (count($images) > 1): ?>
          <button class="prev" onclick="changeSlide(this, -1)">‹</button>
          <button class="next" onclick="changeSlide(this, 1)">›</button>
        <?php endif; ?>
      </div>
      <div class="trip-content">
        <div class="trip-name"><?= htmlspecialchars($trip["trip_name"]) ?></div>
        <div class="price">السعر: <?= $trip["price_per_seat"] ?> ر.س</div>
        <div class="seats">المقاعد المتبقية: <?= $trip["available_seats"] ?></div>
        <div class="rating">
          التقييم:
          <span class="stars">
            <?= str_repeat("★", $fullStars) ?>
            <?= $halfStar ? "½" : "" ?>
            <?= str_repeat("☆", 5 - $fullStars - ($halfStar ? 1 : 0)) ?>
          </span>
          (<?= number_format($avg, 1) ?>)
        </div>
        <a class="btn" href="<?= $page ?>">عرض التفاصيل</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <script>
    function changeSlide(button, direction) {
      const slider = button.closest('.slider');
      const images = slider.querySelectorAll('img');
      let current = Array.from(images).findIndex(img => img.classList.contains('active'));
      images[current].classList.remove('active');
      current = (current + direction + images.length) % images.length;
      images[current].classList.add('active');
    }
  </script>

  <div style="text-align: center; margin: 40px 0;">
    <a href="<?= $homeLink ?>" class="home-btn">العودة إلى الصفحة الرئيسية</a>
  </div>
</body>
</html>