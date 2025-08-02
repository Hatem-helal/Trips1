<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: Log_in.html");
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

$serverName = "localhost\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database" => "project", "CharacterSet" => "UTF-8"]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"])) {
    $msg = trim($_POST["message"]);
    if ($msg !== "") {
        $sql = "INSERT INTO ChatMessages (user_id, sender, message_text) VALUES (?, 'user', ?)";
        sqlsrv_query($conn, $sql, [$user_id, $msg]);
    }
}

$sql = "SELECT * FROM ChatMessages WHERE user_id = ? AND (is_hidden_from_user IS NULL OR is_hidden_from_user = 0) ORDER BY created_at ASC";
$messages = sqlsrv_query($conn, $sql, [$user_id]);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="30">
  <title>الدعم الفني</title>
  <style>
    body {
      background-color: #f3f0e7;
      font-family: 'Tajawal', sans-serif;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 700px;
      margin: 30px auto;
      background: white;
      padding: 25px;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .msg {
      margin: 10px 0;
      padding: 12px;
      border-radius: 10px;
      max-width: 80%;
      word-wrap: break-word;
    }
    .admin {
      background-color: #d9edf7;
      align-self: flex-start;
    }
    .user {
      background-color: #dff0d8;
      align-self: flex-end;
      text-align: right;
    }
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
    h2 {
      text-align: center;
      color: #8b6d35;
    }
    footer {
      text-align: center;
      margin-top: 40px;
      font-size: 14px;
      color: #555;
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: #8b6d35;
      font-weight: bold;
      text-decoration: none;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>الدعم الفني</h2>

  <div class="msg-container" id="chat-messages">
    <?php while ($msg = sqlsrv_fetch_array($messages, SQLSRV_FETCH_ASSOC)) { ?>
      <div class="msg <?= $msg["sender"] === 'admin' ? 'admin' : 'user' ?>">
        <?= nl2br(htmlspecialchars($msg["message_text"])) ?>
        <div class="msg-time"><?= $msg["created_at"]->format('Y-m-d H:i') ?></div>
      </div>
    <?php } ?>
  </div>

  <form method="POST" class="form-container">
    <textarea name="message" placeholder="اكتب رسالتك..." required></textarea>
    <button type="submit">إرسال</button>
  </form>

  <a class="back-link" href="<?= $_SESSION['role'] === 'admin' ? 'admin_chat_list.php' : 'homee.php' ?>">← العودة</a>
</div>

<footer>
  جميع الحقوق محفوظة - طلاب جامعة أم القرى |    حاتم هلال الحربي _ ريان علي الزهراني
</footer>

</body>
</html>