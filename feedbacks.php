<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database" => "project", "CharacterSet" => "UTF-8"]);

// حذف التقييم
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_review_id"])) {
    $review_id = $_POST["delete_review_id"];
    sqlsrv_query($conn, "DELETE FROM Reviews WHERE review_id = ?", [$review_id]);
    echo "<script>location.href='feedbacks.php';</script>";
    exit;
}

// إحصائيات الرحلات
$sql = "
    SELECT T.trip_name, 
           COUNT(B.booking_id) AS total_bookings,
           COUNT(R.review_id) AS total_reviews,
           ISNULL(AVG(CAST(R.rating AS FLOAT)), 0) AS avg_rating
    FROM Trips T
    LEFT JOIN Bookings B ON T.trip_id = B.trip_id
    LEFT JOIN Reviews R ON T.trip_id = R.trip_id
    GROUP BY T.trip_name
";
$stmt = sqlsrv_query($conn, $sql);

$trip_names = [];
$bookings = [];
$reviews = [];
$ratings = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $trip_names[] = $row['trip_name'];
    $bookings[] = $row['total_bookings'];
    $reviews[] = $row['total_reviews'];
    $ratings[] = round($row['avg_rating'], 1);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="10">
  <title>تقييمات العملاء وإحصائيات الرحلات</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f3f0e7;
      font-family: 'Tajawal', sans-serif;
      padding: 40px;
    }
    h2, h3 {
      text-align: center;
      color: #8b6d35;
    }
    canvas {
      max-width: 800px;
      margin: 30px auto;
      display: block;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .review-box {
      max-width: 800px;
      margin: 30px auto;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .review-item {
      border-bottom: 1px solid #ddd;
      padding: 15px 0;
    }
    .review-stars {
      color: #ffcc00;
    }
    .delete-btn {
      background-color: #c9302c;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 5px;
      font-size: 14px;
      cursor: pointer;
      float: left;
    }
    form {
      display: inline;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      text-decoration: none;
      color: #8b6d35;
      font-weight: bold;
      font-size: 16px;
    }
  </style>
</head>
<body>

<a href="admin_dashboard.php" class="back-link">← العودة إلى لوحة التحكم</a>

<h2>إحصائيات الرحلات</h2>

<canvas id="tripChart" width="800" height="400"></canvas>

<script>
  const ctx = document.getElementById('tripChart').getContext('2d');
  const tripChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($trip_names) ?>,
      datasets: [
        {
          label: 'عدد الحجوزات',
          data: <?= json_encode($bookings) ?>,
          backgroundColor: 'rgba(139, 109, 53, 0.7)'
        },
        {
          label: 'عدد التقييمات',
          data: <?= json_encode($reviews) ?>,
          backgroundColor: 'rgba(191, 167, 111, 0.7)'
        },
        {
          label: 'متوسط التقييم',
          data: <?= json_encode($ratings) ?>,
          backgroundColor: 'rgba(255, 206, 86, 0.7)'
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'مقارنة إحصائيات كل رحلة'
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
</script>

<div class="review-box">
  <h3>جميع تقييمات العملاء</h3>
  <?php
  $reviewQuery = "
    SELECT R.review_id, R.rating, R.comment, R.review_date, U.username, T.trip_name
    FROM Reviews R
    JOIN users U ON R.customer_id = U.id
    JOIN Trips T ON R.trip_id = T.trip_id
    ORDER BY R.review_date DESC";
  $reviewStmt = sqlsrv_query($conn, $reviewQuery);

  while ($row = sqlsrv_fetch_array($reviewStmt, SQLSRV_FETCH_ASSOC)):
  ?>
    <div class="review-item">
      <strong><?= htmlspecialchars($row["username"]) ?> (<?= htmlspecialchars($row["trip_name"]) ?>)</strong><br>
      <span class="review-stars"><?= str_repeat("★", $row["rating"]) . str_repeat("☆", 5 - $row["rating"]) ?></span><br>
      <p><?= nl2br(htmlspecialchars($row["comment"])) ?></p>
      <small style="color: #777;"><?= date_format($row["review_date"], "Y-m-d H:i") ?></small>
      <form method="POST" onsubmit="return confirm('هل تريد حذف هذا التقييم؟');">
        <input type="hidden" name="delete_review_id" value="<?= $row["review_id"] ?>">
        <button type="submit" class="delete-btn">حذف</button>
      </form>
    </div>
  <?php endwhile; ?>
</div>

</body>
</html>