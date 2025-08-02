<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "developer") {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database" => "project", "CharacterSet" => "UTF-8"]);

// صلاحيات المستخدم
if (isset($_POST["ban_user_id"])) {
    sqlsrv_query($conn, "UPDATE users SET is_banned = 1 WHERE id = ?", [$_POST["ban_user_id"]]);
}
if (isset($_POST["unban_user_id"])) {
    sqlsrv_query($conn, "UPDATE users SET is_banned = 0 WHERE id = ?", [$_POST["unban_user_id"]]);
}
if (isset($_POST["make_admin"])) {
    sqlsrv_query($conn, "UPDATE users SET role = 'admin' WHERE id = ?", [$_POST["make_admin"]]);
}
if (isset($_POST["remove_admin"])) {
    sqlsrv_query($conn, "UPDATE users SET role = 'user' WHERE id = ?", [$_POST["remove_admin"]]);
}
if (isset($_POST["delete_review"])) {
    sqlsrv_query($conn, "DELETE FROM Reviews WHERE review_id = ?", [$_POST["delete_review"]]);
}

// البيانات
$users = sqlsrv_query($conn, "SELECT * FROM users");
$reviews = sqlsrv_query($conn, "SELECT R.*, U.username, T.trip_name FROM Reviews R JOIN users U ON R.customer_id = U.id JOIN Trips T ON R.trip_id = T.trip_id");
$logs = sqlsrv_query($conn, "SELECT * FROM AdminLogs ORDER BY action_time DESC");
$messages = sqlsrv_query($conn, "SELECT C.*, U.username FROM ChatMessages C JOIN users U ON C.user_id = U.id ORDER BY created_at DESC");

// الأرباح
$profitStmt = sqlsrv_query($conn, "
    SELECT T.trip_name, 
           SUM(B.seats_count * T.price_per_seat) AS total_profit
    FROM Trips T
    LEFT JOIN Bookings B ON T.trip_id = B.trip_id
    GROUP BY T.trip_name
");

$total_profit = 0;
$trip_profits = [];
while ($row = sqlsrv_fetch_array($profitStmt, SQLSRV_FETCH_ASSOC)) {
    $trip_profits[] = $row;
    $total_profit += $row["total_profit"];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="10">
    <title>لوحة تحكم المبرمج</title>
    <style>
        body { font-family: Tahoma; background-color: #f4f1eb; padding: 30px; }
        h2 { color: #8b6d35; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 50px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #8b6d35; color: white; }
        form { display: inline; }
        button { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; }
        .ban { background-color: #d9534f; color: white; }
        .unban { background-color: #5cb85c; color: white; }
        .admin { background-color: #337ab7; color: white; }
        .remove-admin { background-color: #f0ad4e; color: white; }
        .delete { background-color: #a94442; color: white; }
        .log, .chat-box, .profit-box {
            font-size: 14px;
            background: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
        }
        .logout-btn {
            background-color: #8b6d35;
            color: white;
            padding: 10px 20px;
            float: left;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

<a href="logout.php" class="logout-btn">تسجيل الخروج</a>

<h2>المستخدمون</h2>
<table>
    <tr>
        <th>اسم المستخدم</th><th>البريد</th><th>الدور</th><th>كلمة المرور</th><th>الحالة</th><th>خيارات</th>
    </tr>
    <?php while ($user = sqlsrv_fetch_array($users, SQLSRV_FETCH_ASSOC)): ?>
    <tr>
        <td><?= htmlspecialchars($user["username"]) ?></td>
        <td><?= htmlspecialchars($user["email"]) ?></td>
        <td><?= $user["role"] ?></td>
        <td><?= $user["password_plain"] ?? 'غير متوفر' ?></td>
        <td><?= $user["is_banned"] ? "محظور" : "نشط" ?></td>
        <td>
            <?php if ($user["is_banned"]): ?>
                <form method="POST"><input type="hidden" name="unban_user_id" value="<?= $user["id"] ?>"><button class="unban">إزالة الحظر</button></form>
            <?php else: ?>
                <form method="POST"><input type="hidden" name="ban_user_id" value="<?= $user["id"] ?>"><button class="ban">حظر</button></form>
            <?php endif; ?>
            <?php if ($user["role"] === "admin"): ?>
                <form method="POST"><input type="hidden" name="remove_admin" value="<?= $user["id"] ?>"><button class="remove-admin">إزالة الأدمن</button></form>
            <?php else: ?>
                <form method="POST"><input type="hidden" name="make_admin" value="<?= $user["id"] ?>"><button class="admin">جعل أدمن</button></form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<h2>التقييمات</h2>
<table>
    <tr><th>الرحلة</th><th>المستخدم</th><th>التقييم</th><th>التعليق</th><th>التاريخ</th><th>حذف</th></tr>
    <?php while ($review = sqlsrv_fetch_array($reviews, SQLSRV_FETCH_ASSOC)): ?>
    <tr>
        <td><?= htmlspecialchars($review["trip_name"]) ?></td>
        <td><?= htmlspecialchars($review["username"]) ?></td>
        <td><?= $review["rating"] ?> نجوم</td>
        <td><?= htmlspecialchars($review["comment"]) ?></td>
        <td><?= $review["review_date"]->format("Y-m-d H:i") ?></td>
        <td><form method="POST"><input type="hidden" name="delete_review" value="<?= $review["review_id"] ?>"><button class="delete">حذف</button></form></td>
    </tr>
    <?php endwhile; ?>
</table>

<h2>سجل نشاطات الأدمن</h2>
<?php while ($log = sqlsrv_fetch_array($logs, SQLSRV_FETCH_ASSOC)): ?>
    <div class="log">
        <strong><?= htmlspecialchars($log["admin_email"]) ?>:</strong> <?= htmlspecialchars($log["action_description"]) ?> 
        <br><small><?= $log["action_time"]->format("Y-m-d H:i") ?></small>
    </div>
<?php endwhile; ?>

<h2>جميع المحادثات</h2>
<?php while ($msg = sqlsrv_fetch_array($messages, SQLSRV_FETCH_ASSOC)): ?>
    <div class="chat-box">
        <strong><?= htmlspecialchars($msg["username"]) ?>:</strong>
        <?= nl2br(htmlspecialchars($msg["message_text"])) ?>
        <br><small><?= $msg["created_at"]->format("Y-m-d H:i") ?> (<?= $msg["sender"] ?>)</small>
        <br><em style="color: red;">* هذه المحادثة تحت المراجعة من قبل الإدارة</em>
    </div>
<?php endwhile; ?>

<h2>إجمالي الأرباح</h2>
<?php foreach ($trip_profits as $row): ?>
    <div class="profit-box">
        <strong><?= htmlspecialchars($row["trip_name"]) ?>:</strong>
        <?= number_format($row["total_profit"]) ?> ريال
    </div>
<?php endforeach; ?>
<div class="profit-box" style="background-color:#e3d6b9; font-weight:bold;">
    <strong>إجمالي المداخيل:</strong> <?= number_format($total_profit) ?> ريال
</div>

</body>
</html>