<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database" => "project", "CharacterSet" => "UTF-8"]);
if (!$conn) {
    die("فشل الاتصال: " . print_r(sqlsrv_errors(), true));
}

// بحث
$search = isset($_GET["search"]) ? $_GET["search"] : "";

// استعلام جلب المستخدمين مع التثبيت والبحث
$sql = "
SELECT 
    U.id,
    U.username,
    U.email,
    ISNULL((
        SELECT TOP 1 is_pinned 
        FROM ChatMessages 
        WHERE user_id = U.id AND is_pinned = 1
    ), 0) AS pinned
FROM users U
WHERE U.role != 'admin'
" . ($search ? "AND (U.username LIKE ? OR U.email LIKE ?)" : "") . "
ORDER BY pinned DESC, username ASC";

$params = $search ? ["%$search%", "%$search%"] : [];
$stmt = sqlsrv_query($conn, $sql, $params);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>رسائل العملاء</title>
  <style>
    body {
      font-family: Tahoma;
      background-color: #f3f0e7;
      padding: 30px;
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

    .search-box {
      text-align: center;
      margin-bottom: 30px;
    }

    .search-input {
      padding: 8px;
      width: 250px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .user-box {
      background-color: #fff;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      max-width: 800px;
      margin: auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .user-info h3 { margin: 0; color: #8b6d35; }
    .user-info small { color: #555; }

    .chat-btn {
      background-color: #8b6d35;
      color: white;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 14px;
    }

    .pin-btn {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
    }

    .actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }
  </style>
</head>
<body>

<a href="admin_dashboard.php" class="back-link">← الرجوع للوحة التحكم</a>

<form method="GET" class="search-box">
  <input type="text" name="search" class="search-input" placeholder="ابحث باسم أو بريد..." value="<?= htmlspecialchars($search) ?>">
  <button type="submit">بحث</button>
</form>

<?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
  <div class="user-box">
    <div class="user-info">
      <h3><?= htmlspecialchars($row["username"]) ?></h3>
      <small><?= htmlspecialchars($row["email"]) ?></small>
    </div>

    <div class="actions">
      <a class="chat-btn" href="admin_chat_view.php?user_id=<?= $row['id'] ?>">عرض المحادثة</a>
      <form method="POST">
        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
        <input type="hidden" name="pin_action" value="<?= $row['pinned'] ? 'unpin' : 'pin' ?>">
        <button type="submit" class="pin-btn" title="<?= $row['pinned'] ? 'إلغاء التثبيت' : 'تثبيت المحادثة' ?>">
          <?= $row['pinned'] ? '📌' : '📍' ?>
        </button>
      </form>
    </div>
  </div>
<?php } ?>

</body>
</html>