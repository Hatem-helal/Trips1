<?php
session_start();
include 'db.php'; // تأكد أن الاتصال بـ SQL Server مضبوط في هذا الملف

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // فلترة البيانات القادمة من النموذج
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // التحقق من وجود المستخدم
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $params = [$username];
    $stmt = sqlsrv_prepare($conn, $sql, $params);

    if (!$stmt) {
        die("فشل في تحضير الاستعلام: " . print_r(sqlsrv_errors(), true));
    }

    if (!sqlsrv_execute($stmt)) {
        die("فشل في تنفيذ الاستعلام: " . print_r(sqlsrv_errors(), true));
    }

    // التحقق من النتائج
    if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // التحقق من كلمة المرور
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["username"] = $row["username"];

            // توجيه المستخدم للصفحة الرئيسية
            header("Location: Home.html");
            exit;
        } else {
            echo "<script>alert('كلمة المرور غير صحيحة'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('اسم المستخدم غير موجود'); window.history.back();</script>";
        exit;
    }
}
?>