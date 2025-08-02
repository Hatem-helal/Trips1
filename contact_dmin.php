<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = $_POST["message"];
    $user_id = $_SESSION["user_id"];
    $sql = "INSERT INTO Messages (user_id, message_text) VALUES (?, ?)";
    sqlsrv_query($conn, $sql, [$user_id, $message]);
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التواصل مع الإدارة</title>
    <style>
        body { font-family: Tahoma; background-color: #f3f0e7; padding: 30px; }
        form { background: #fff; padding: 20px; border-radius: 10px; max-width: 500px; margin: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 150px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; }
        button { background: #8b6d35; color: white; border: none; padding: 10px 20px; border-radius: 6px; margin-top: 10px; }
        .success { text-align: center; color: green; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php if (isset($success)) echo '<div class="success">تم إرسال رسالتك بنجاح.</div>'; ?>

<form method="POST">
    <h3>أرسل رسالتك للإدارة:</h3>
    <textarea name="message" required placeholder="اكتب رسالتك هنا..."></textarea>
    <button type="submit">إرسال</button>
</form>

</body>
</html>