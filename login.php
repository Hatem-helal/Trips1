<?php
session_start();

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "project",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die("فشل الاتصال: " . print_r(sqlsrv_errors(), true));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE username = ?";
    $params = [$username];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && sqlsrv_has_rows($stmt)) {
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (password_verify($password, $user["password"])) {
            $_SESSION["username"] = $user["username"];
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];

            // التوجيه حسب نوع الحساب
            switch ($user["role"]) {
                case "admin":
                    header("Location: admin_dashboard.php");
                    break;
                case "guide":
                    header("Location: guide_dashboard.php");
                    break;
                case "developer":
                    header("Location: developer_dashboard.php");
                    break;
                case "user":
                default:
                    header("Location: Homee.php");
                    break;
            }
            exit;
        } else {
            echo "<script>alert('كلمة المرور غير صحيحة'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('اسم المستخدم غير موجود'); window.history.back();</script>";
    }
}
?>