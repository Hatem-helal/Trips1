<?php
session_start();
if ($_SESSION["role"] !== "admin") {
    header("Location: Log_in.html");
    exit;
}

$user_id = $_GET["user_id"] ?? null;
if (!$user_id) {
    echo "معرّف المستخدم غير موجود.";
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database" => "project", "CharacterSet" => "UTF-8"]);

// تحديث الرسائل إلى مقروءة
sqlsrv_query($conn, "UPDATE ChatMessages SET is_read = 1 WHERE user_id = ? AND sender != 'admin' AND is_read = 0", [$user_id]);

// إرسال رسالة جديدة
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["message"])) {
        $msg = trim($_POST["message"]);
        if ($msg !== "") {
            $sql = "INSERT INTO ChatMessages (user_id, sender, message_text, is_read) VALUES (?, 'admin', ?, 1)";
            sqlsrv_query($conn, $sql, [$user_id, $msg]);
        }
    }

    if (isset($_POST["hide_from_user"], $_POST["message_id"])) {
        $msgId = $_POST["message_id"];
        sqlsrv_query($conn, "UPDATE ChatMessages SET is_hidden_from_user = 1 WHERE message_id = ?", [$msgId]);
    }

    if (isset($_POST["pin_action"])) {
        $action = $_POST["pin_action"];
        sqlsrv_query($conn, "UPDATE ChatMessages SET is_pinned = 0 WHERE user_id = ?", [$user_id]);
        if ($action === "pin") {
            $latest = sqlsrv_query($conn, "SELECT TOP 1 message_id FROM ChatMessages WHERE user_id = ? ORDER BY created_at DESC", [$user_id]);
            if ($latest && ($msg = sqlsrv_fetch_array($latest, SQLSRV_FETCH_ASSOC))) {
                sqlsrv_query($conn, "UPDATE ChatMessages SET is_pinned = 1 WHERE message_id = ?", [$msg["message_id"]]);
            }
        }
        header("Location: admin_chat_view.php?user_id=" . $user_id);
        exit;
    }
}

// جلب اسم المستخدم
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [$user_id]);
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// جلب الرسائل
$sql = "SELECT * FROM ChatMessages WHERE user_id = ? ORDER BY created_at ASC";
$messages = sqlsrv_query($conn, $sql, [$user_id]);

// التحقق من التثبيت
$pinned = false;
$check = sqlsrv_query($conn, "SELECT TOP 1 * FROM ChatMessages WHERE user_id = ? AND is_pinned = 1", [$user_id]);
if ($check && sqlsrv_fetch_array($check)) {
    $pinned = true;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>محادثة مع <?= htmlspecialchars($user["username"]) ?></title>
  <style>
    body {
      font-family: Tahoma;
      background-color: #f3f0e7;
      padding: 30px;
      margin: 0;
    }
    .chat-box {
      max-width: 700px;
      margin: auto;
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .msg {
      padding: 10px;
      margin: 10px 0;
      border-radius: 10px;
      max-width: 80%;
      word-wrap: break-word;
      position: relative;
    }
    .admin { background-color: #d9edf7; align-self: flex-start; }
    .user { background-color: #dff0d8; align-self: flex-end; text-align: right; }
    .msg-container {
      display: flex;
      flex-direction: column;
    }
    .msg-time {
      font-size: 11px;
      color: #666;
      margin-top: 5px;
    }
    .form-container {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }
    textarea {
      flex: 1;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    button {
      background-color: #8b6d35;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }
    .back-link {
      display: block;
      margin-bottom: 20px;
      font-size: 18px;
      color: #8b6d35;
      text-decoration: none;
      font-weight: bold;
      max-width: 700px;
      margin-right: auto;
      margin-left: auto;
    }
    .hide-form {
      display: inline;
      margin-top: 5px;
    }
    .hide-form button {
      background-color: #c9302c;
      font-size: 12px;
      padding: 5px 8px;
    }
    .pin-form {
      text-align: left;
      margin-bottom: 15px;
    }
    .pin-form button {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #8b6d35;
    }
  </style>
</head>
<body>

<a href="admin_chat_list.php" class="back-link">← الرجوع إلى قائمة المحادثات</a>

<div class="chat-box">
  <form method="POST" class="pin-form" title="<?= $pinned ? 'إلغاء التثبيت' : 'تثبيت المحادثة' ?>">
    <input type="hidden" name="pin_action" value="<?= $pinned ? 'unpin' : 'pin' ?>">
    <button type="submit"><?= $pinned ? '📌' : '📍' ?></button>
  </form>

  <h2>محادثة مع: <?= htmlspecialchars($user["username"]) ?></h2>

  <div class="msg-container" id="chat-messages">
    <?php while ($msg = sqlsrv_fetch_array($messages, SQLSRV_FETCH_ASSOC)) { ?>
      <div class="msg <?= $msg["sender"] === 'admin' ? 'admin' : 'user' ?>">
        <?= nl2br(htmlspecialchars($msg["message_text"])) ?>
        <div class="msg-time"><?= $msg["created_at"]->format('Y-m-d H:i') ?></div>

        <?php if ($msg["sender"] === "admin" && empty($msg["is_hidden_from_user"])): ?>
          <form method="POST" class="hide-form">
            <input type="hidden" name="message_id" value="<?= $msg["message_id"] ?>">
            <button type="submit" name="hide_from_user">إخفاء من جهة العميل</button>
          </form>
        <?php endif; ?>
      </div>
    <?php } ?>
  </div>

  <form method="POST" class="form-container">
    <textarea name="message" placeholder="اكتب ردك هنا..." required></textarea>
    <button type="submit">إرسال</button>
  </form>
</div>

<script>
  // تحديث الرسائل تلقائيًا كل 30 ثانية
  setInterval(() => {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_messages.php?user_id=<?= $user_id ?>", true);
    xhr.onload = function () {
      if (this.status === 200) {
        document.getElementById("chat-messages").innerHTML = this.responseText;
      }
    };
    xhr.send();
  }, 30000);
</script>

</body>
</html>