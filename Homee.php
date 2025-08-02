<?php
session_start();

$isLoggedIn = isset($_SESSION["username"]);
$username = $isLoggedIn ? $_SESSION["username"] : null;

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);

// صورة المستخدم
$profileImage = 'images/admin_icon.png';
if ($isLoggedIn && isset($_SESSION["user_id"])) {
    $user_id = $_SESSION["user_id"];
    $stmt = sqlsrv_query($conn, "SELECT profile_image FROM users WHERE id = ?", [$user_id]);
    if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        if (!empty($row["profile_image"]) && file_exists($row["profile_image"])) {
            $profileImage = $row["profile_image"];
        }
    }
}

// جلب الرحلات + صورة واحدة لكل رحلة من TripImages
$sql = "
  SELECT T.trip_id, T.trip_name,
         (SELECT TOP 1 image_path FROM TripImages WHERE trip_id = T.trip_id) AS image_path,
         ISNULL(AVG(CAST(R.rating AS FLOAT)), 0) AS avg_rating
  FROM Trips T
  LEFT JOIN Reviews R ON T.trip_id = R.trip_id
  GROUP BY T.trip_id, T.trip_name
";
$stmt = sqlsrv_query($conn, $sql);
$trips = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $trips[] = $row;
}

function getTripPage($name) {
    $map = [
        'جبل النور' => 'jabal_alnoorr.php',
        'جبل ثور' => 'jabal_thawr.php',
        'جبل عرفات' => 'jabal_arafat.php',
        'ساحة الحرم' => 'sahat_alharam.php'
    ];
    return $map[$name] ?? '#';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="10">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>الصفحة الرئيسية</title>
  <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body { margin: 0; font-family: 'Tajawal', sans-serif; background-color: #f3f0e7; }
    header {
      background-color: #8b6d35;
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .title { flex: 1; text-align: center; font-size: 22px; font-weight: bold; }
    .profile {
      width: 40px; height: 40px; border-radius: 50%;
      object-fit: cover; border: 2px solid white;
    }
    .menu-toggle {
      font-size: 26px; background: none; border: none;
      color: white; cursor: pointer;
    }
    .sidebar {
      position: fixed; top: 0; right: -260px;
      width: 260px; height: 100%;
      background: #fff; box-shadow: -2px 0 5px rgba(0,0,0,0.1);
      transition: right 0.3s ease; padding: 30px 20px; z-index: 999;
    }
    .sidebar.open { right: 0; }
    .sidebar a {
      display: block; color: #8b6d35;
      text-decoration: none; margin-bottom: 15px;
      font-weight: bold;
    }
    h2 {
      text-align: center; margin: 30px 0 10px;
      color: #8b6d35;
    }
    .grid {
      display: flex; flex-wrap: wrap;
      justify-content: center; gap: 20px;
      padding: 20px;
    }
    .card {
      background: white; border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      width: 250px; overflow: hidden; text-align: center;
    }
    .card img {
      width: 100%; height: 160px;
      object-fit: cover;
    }
    .card h3 {
      color: #8b6d35; padding: 15px 10px 5px; margin: 0;
    }
    .card .rating {
      color: #ffcc00; font-size: 18px; margin-bottom: 10px;
    }
    .card .rating span {
      font-size: 14px; color: #555;
    }
    .card a {
      display: block; background: #8b6d35;
      color: white; padding: 10px;
      text-decoration: none;
      border-radius: 0 0 12px 12px;
    }
    .card a:hover {
      background-color: #6d542a;
    }
    .support-button {
      position: fixed; bottom: 20px; left: 20px;
      background-color: #8b6d35; color: white;
      border: none; border-radius: 50%;
      width: 60px; height: 60px; font-size: 28px;
      cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .support-button:hover {
      background-color: #6d542a;
    }
    .notification-bubble {
      position: absolute; top: -5px; right: -5px;
      background: red; color: white;
      font-size: 12px; font-weight: bold;
      padding: 3px 7px; border-radius: 50%;
    }
    footer {
      text-align: center; font-size: 14px;
      color: #777; padding: 15px;
      background-color: #f9f9f9;
      border-top: 1px solid #ccc;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<header>
  <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
  <div class="title">رحلات مكة</div>
  <?php if ($isLoggedIn): ?>
    <img src="<?= htmlspecialchars($profileImage) ?>" class="profile" alt="صورة المستخدم">
  <?php endif; ?>
</header>

<div class="sidebar" id="sidebar">
  <a href="homee.php">الصفحة الرئيسية</a>
  <?php if ($isLoggedIn): ?>
    <a href="users_settings.php">الإعدادات</a>
    <a href="my_bookings.php">حجوزاتي</a>
    <a href="logout.php">تسجيل الخروج</a>
  <?php else: ?>
    <a href="Log_in.html">تسجيل الدخول</a>
    <a href="register.html">تسجيل جديد</a>
  <?php endif; ?>
  <a href="Trips.php">الرحلات</a>
</div>

<h2>الرحلات المميزة</h2>
<div class="grid">
  <?php foreach ($trips as $trip):
    $avg = floatval($trip["avg_rating"]);
    $fullStars = floor($avg);
    $halfStar = ($avg - $fullStars >= 0.5);
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    $imgPath = !empty($trip["image_path"]) && file_exists($trip["image_path"])
               ? $trip["image_path"]
               : "images/default.jpg";
  ?>
    <div class="card">
      <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($trip["trip_name"]) ?>">
      <h3><?= htmlspecialchars($trip["trip_name"]) ?></h3>
      <div class="rating">
        <?= str_repeat("★", $fullStars) ?>
        <?= $halfStar ? "½" : "" ?>
        <?= str_repeat("☆", $emptyStars) ?>
        <span>(<?= number_format($avg, 1) ?>)</span>
      </div>
      <a href="<?= getTripPage($trip["trip_name"]) ?>">عرض التفاصيل</a>
    </div>
  <?php endforeach; ?>
</div>

<a href="user_chat.php" class="support-button" title="الدعم الفني">
  <span style="font-family:Arial;">&#128483;</span>
  <?php
    if ($isLoggedIn) {
        $unread = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM ChatMessages WHERE user_id = ? AND sender = 'admin' AND is_read = 0", [$user_id]);
        $unreadCount = ($unread && $row = sqlsrv_fetch_array($unread, SQLSRV_FETCH_ASSOC)) ? $row["total"] : 0;
        if ($unreadCount > 0) {
            echo '<span class="notification-bubble">' . $unreadCount . '</span>';
        }
    }
  ?>
</a>

<footer>
  <p>
    جميع الحقوق محفوظة &copy; 2025 - مشروع طلاب <strong>جامعة أم القرى</strong><br>
    إعداد: <strong> حاتم هلال الحربي - ريان علي الزهراني      </strong>
  </p>
</footer>

<script>
  function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("open");
  }
</script>

</body>
</html>