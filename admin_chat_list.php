<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database" => "project", "CharacterSet" => "UTF-8"]);

$sql = "
SELECT 
  u.id, 
  u.username, 
  u.email, 
  u.profile_image, 
  MAX(c.created_at) as last_msg,
  SUM(CASE WHEN c.is_read = 0 AND c.sender != 'admin' THEN 1 ELSE 0 END) AS unread_count
FROM ChatMessages c
JOIN users u ON u.id = c.user_id
GROUP BY u.id, u.username, u.email, u.profile_image
ORDER BY last_msg DESC
";
$stmt = sqlsrv_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>قائمة المحادثات</title>
  <style>
    body {
      font-family: Tahoma;
      background-color: #f3f0e7;
      padding: 30px;
      margin: 0;
      position: relative;
    }

    .back-link {
      position: absolute;
      top: 20px;
      right: 20px;
      color: #8b6d35;
      text-decoration: none;
      font-weight: bold;
      font-size: 16px;
    }

    h2 {
      text-align: center;
      color: #8b6d35;
      margin-bottom: 30px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
    }

    .card {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
      position: relative;
      transition: transform 0.2s;
    }

    .card:hover {
      transform: scale(1.02);
    }

    .card img {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 50%;
      margin-bottom: 10px;
      border: 2px solid #ccc;
    }

    .card p {
      margin: 8px 0;
      font-size: 14px;
    }

    .card a {
      display: inline-block;
      margin-top: 10px;
      padding: 8px 12px;
      background-color: #8b6d35;
      color: white;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
    }

    .card a:hover {
      background-color: #6d542a;
    }

    .badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: #d9534f;
      color: white;
      font-size: 13px;
      padding: 4px 8px;
      border-radius: 20px;
      font-weight: bold;
      box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
  </style>
</head>
<body>

<a href="admin_dashboard.php" class="back-link">← الرجوع للوحة التحكم</a>

<h2>محادثات العملاء</h2>

<div class="grid">
  <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
    <div class="card">
      <?php if ($row['unread_count'] > 0): ?>
        <div class="badge"><?= $row['unread_count'] ?></div>
      <?php endif; ?>
      <img src="<?= $row['profile_image'] ?? 'images/admin_icon.png' ?>" alt="صورة المستخدم">
      <p><strong><?= htmlspecialchars($row['username']) ?></strong></p>
      <p><?= htmlspecialchars($row['email']) ?></p>
      <a href="admin_chat_view.php?user_id=<?= $row['id'] ?>">عرض المحادثة</a>
    </div>
  <?php } ?>
</div>

</body>
</html>