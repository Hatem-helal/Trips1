<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: Log_in.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الرئيسية - رحلات مكة</title>
</head>
<body>
  <h1>مرحبًا بك يا <?php echo $_SESSION["username"]; ?> في الصفحة الرئيسية!</h1>
</body>
</html>