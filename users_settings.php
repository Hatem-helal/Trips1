<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: Log_in.html");
    exit;
}

$user_id = $_SESSION["user_id"];
$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_name = $_POST["username"];
    $new_email = $_POST["email"];
    $new_password = null;

    if (!empty($_POST["new_password"]) && $_POST["new_password"] === $_POST["confirm_password"]) {
        $new_password = password_hash($_POST["new_password"], PASSWORD_DEFAULT);
    }

    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] === 0) {
        $getOld = sqlsrv_query($conn, "SELECT profile_image FROM users WHERE id = ?", [$user_id]);
        if ($getOld && ($old = sqlsrv_fetch_array($getOld, SQLSRV_FETCH_ASSOC))) {
            if (!empty($old["profile_image"]) && file_exists($old["profile_image"])) {
                unlink($old["profile_image"]);
            }
        }

        $image_name = basename($_FILES["profile_image"]["name"]);
        $image_path = "images/profile_" . time() . "_" . $image_name;
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $image_path);
        sqlsrv_query($conn, "UPDATE users SET profile_image = ? WHERE id = ?", [$image_path, $user_id]);
        $_SESSION["profile_image"] = $image_path;
    }

    if ($new_password) {
        sqlsrv_query($conn, "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?", [$new_name, $new_email, $new_password, $user_id]);
    } else {
        sqlsrv_query($conn, "UPDATE users SET username = ?, email = ? WHERE id = ?", [$new_name, $new_email, $user_id]);
    }

    $_SESSION["username"] = $new_name;
    $_SESSION["email"] = $new_email;
    $success = true;
}

$stmt = sqlsrv_query($conn, "SELECT * FROM users WHERE id = ?", [$user_id]);
if ($stmt === false) {
    die("فشل الاستعلام: " . print_r(sqlsrv_errors(), true));
}
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>إعدادات الحساب</title>
  <style>
    body {
      font-family: Tahoma;
      background-color: #f3f0e7;
      margin: 0;
      padding: 20px;
    }

    .settings-box {
      background-color: #fff;
      max-width: 500px;
      margin: auto;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    h2 {
      color: #8b6d35;
      text-align: center;
      font-size: 22px;
    }

    .avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ccc;
      display: block;
      margin: 0 auto 10px auto;
    }

    input[type="text"], input[type="email"], input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      text-align: right;
      box-sizing: border-box;
    }

    input[type="file"] { display: none; }

    .link-btn {
      background: none;
      border: none;
      color: #8b6d35;
      text-decoration: underline;
      cursor: pointer;
      font-size: 14px;
      margin: 8px auto 15px auto;
      display: block;
    }

    button {
      background-color: #8b6d35;
      color: white;
      border: none;
      padding: 12px;
      margin-top: 15px;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      font-size: 16px;
    }

    .back-btn {
      margin-top: 25px;
      background-color: #bfa76f;
      text-decoration: none;
      font-weight: bold;
      display: block;
      text-align: center;
      padding: 10px;
      border-radius: 8px;
      color: white;
    }

    .change-password-toggle {
      color: #8b6d35;
      cursor: pointer;
      margin-top: 8px;
      display: inline-block;
    }

    .password-section {
      display: none;
      margin-top: 10px;
    }

    .eye-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
    }

    .relative { position: relative; }

    @media (max-width: 600px) {
      .settings-box { padding: 15px; }
      h2 { font-size: 20px; }
      button, .back-btn { font-size: 15px; padding: 10px; }
      .avatar { width: 80px; height: 80px; }
    }
  </style>

  <script>
    function togglePasswordSection() {
      const section = document.getElementById('password-section');
      section.style.display = section.style.display === 'none' ? 'block' : 'none';
    }

    function toggleVisibility(id) {
      const input = document.getElementById(id);
      input.type = input.type === 'password' ? 'text' : 'password';
    }

    function triggerFileInput() {
      document.getElementById('real-file').click();
    }

    function showPopup(message) {
      const popup = document.createElement("div");
      popup.textContent = message;
      popup.style.position = "fixed";
      popup.style.top = "20px";
      popup.style.right = "20px";
      popup.style.backgroundColor = "#4CAF50";
      popup.style.color = "white";
      popup.style.padding = "12px 20px";
      popup.style.borderRadius = "8px";
      popup.style.boxShadow = "0 2px 8px rgba(0,0,0,0.2)";
      popup.style.zIndex = "9999";
      document.body.appendChild(popup);
      setTimeout(() => popup.remove(), 3000);
    }
  </script>
</head>
<body>

<div class="settings-box">
  <h2>إعدادات الحساب</h2>

  <?php if (isset($success)) echo "<script>showPopup('تم حفظ التعديلات بنجاح');</script>"; ?>

  <form method="POST" enctype="multipart/form-data">
    <img src="<?= $user['profile_image'] ?? 'images/admin_icon.png' ?>" class="avatar" alt="الصورة الشخصية">
    <input type="file" name="profile_image" id="real-file" accept="image/*">
    <button type="button" class="link-btn" onclick="triggerFileInput()">تغيير الصورة</button>

    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" placeholder="الاسم" required>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="البريد الإلكتروني" required>

    <div class="relative">
      <input type="password" value="**********" id="current_password" readonly>
      <span class="eye-icon" onclick="toggleVisibility('current_password')">👁️</span>
    </div>

    <span class="change-password-toggle" onclick="togglePasswordSection()">تغيير كلمة المرور</span>

    <div id="password-section" class="password-section">
      <div class="relative">
        <input type="password" name="new_password" id="new_password" placeholder="كلمة المرور الجديدة">
        <span class="eye-icon" onclick="toggleVisibility('new_password')">👁️</span>
      </div>
      <div class="relative">
        <input type="password" name="confirm_password" id="confirm_password" placeholder="تأكيد كلمة المرور">
        <span class="eye-icon" onclick="toggleVisibility('confirm_password')">👁️</span>
      </div>
    </div>

    <button type="submit">حفظ</button>
  </form>

  <a href="homee.php" class="back-btn">العودة إلى الصفحة الرئيسية</a>
</div>

</body>
</html>