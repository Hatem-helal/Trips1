<?php
session_start();
if (!isset($_SESSION["user_id"])) exit;

$user_id = $_SESSION["user_id"];

$serverName = "localhost\\SQLEXPRESS";
$conn = sqlsrv_connect($serverName, ["Database" => "project", "CharacterSet" => "UTF-8"]);
if (!$conn) exit;

// جلب الرسائل مع تجاهل الرسائل المخفية
$sql = "
    SELECT message_text, sender, created_at
    FROM ChatMessages
    WHERE user_id = ? AND (is_hidden_from_user IS NULL OR is_hidden_from_user = 0)
    ORDER BY created_at ASC
";
$stmt = sqlsrv_query($conn, $sql, [$user_id]);

while ($msg = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $senderClass = $msg["sender"] === "admin" ? "admin" : "user";
    $messageText = nl2br(htmlspecialchars($msg["message_text"]));
    $messageTime = $msg["created_at"]->format("Y-m-d H:i");

    echo "<div class='msg {$senderClass}'>";
    echo $messageText;
    echo "<div class='msg-time'>{$messageTime}</div>";
    echo "</div>";
}
?>