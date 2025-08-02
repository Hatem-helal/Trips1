<?php
session_start();

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// تحديث صورة الأدمن
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["new_profile_image"])) {
    $user = $_SESSION["username"];

    if ($_FILES["new_profile_image"]["error"] === 0) {
        $image_name = basename($_FILES["new_profile_image"]["name"]);
        $image_path = "images/profile_" . time() . "_" . $image_name;

        if (move_uploaded_file($_FILES["new_profile_image"]["tmp_name"], $image_path)) {
            $updateImage = "UPDATE users SET profile_image = ? WHERE username = ?";
            sqlsrv_query($conn, $updateImage, [$image_path, $user]);
            $_SESSION["profile_image"] = $image_path;
        }
    }
}

// تحديث صورة صفحة about
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["about_background"])) {
    if ($_FILES["about_background"]["error"] === 0) {
        $bg_name = basename($_FILES["about_background"]["name"]);
        $bg_path = "images/about_" . time() . "_" . $bg_name;

        if (move_uploaded_file($_FILES["about_background"]["tmp_name"], $bg_path)) {
            $update = "UPDATE SiteSettings SET about_image_path = ? WHERE id = 1";
            sqlsrv_query($conn, $update, [$bg_path]);
        }
    }
}

// جلب صورة الأدمن
$profileImage = "images/admin_icon.png";
$stmt = sqlsrv_query($conn, "SELECT profile_image FROM users WHERE username = ?", [$_SESSION["username"]]);
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    if (!empty($row["profile_image"])) {
        $profileImage = $row["profile_image"];
        $_SESSION["profile_image"] = $profileImage;
    }
}

// الإحصائيات
$bookingCount = 0;
$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM Bookings");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    $bookingCount = $row["total"];
}

$userCount = 0;
$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    $userCount = $row["total"];
}

$topTrip = "لا يوجد";
$stmt = sqlsrv_query($conn, "
    SELECT TOP 1 T.trip_name, COUNT(B.booking_id) AS total
    FROM Bookings B
    JOIN Trips T ON B.trip_id = T.trip_id
    GROUP BY T.trip_name
    ORDER BY total DESC
");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    $topTrip = $row["trip_name"] . " (" . $row["total"] . " حجز)";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم الأدمن</title>
    <style>
        body {
            margin: 0;
            font-family: 'Tahoma', sans-serif;
            background-color: #f3f0e7;
        }

        header {
            background-color: #a78b60;
            color: white;
            padding: 20px;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logout-btn {
            position: absolute;
            left: 20px;
            top: 20px;
            background-color: #d9534f;
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
        }

        .logout-btn:hover {
            background-color: #c9302c;
        }

        .welcome {
            position: absolute;
            right: 20px;
            top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            font-size: 15px;
        }

        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }

        nav {
            background-color: #e0d6c3;
            padding: 10px 0;
            text-align: center;
        }

        nav a {
            margin: 0 15px;
            color: #5a432b;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .dashboard {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .card h3 {
            color: #8b6d35;
            margin-top: 0;
        }

        .stats {
            font-size: 16px;
            line-height: 2;
        }

        .btn {
            background-color: #bfa76f;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin-top: 10px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #a58f4d;
        }

        input[type="file"] {
            margin-top: 10px;
        }
    </style>
</head>
<body>

<header>
    <h1>لوحة تحكم الأدمن</h1>
    <a href="logout.php" class="logout-btn">تسجيل الخروج</a>
    <div class="welcome">
        <img src="<?php echo $_SESSION['profile_image'] ?? $profileImage; ?>" class="avatar" alt="أدمن">
        <?php echo "مرحباً، " . $_SESSION["username"]; ?>
    </div>
</header>

<nav>
    <a href="feedbacks.php">التقييمات</a>
    <a href="Trips.php">الرحلات</a>
    <a href="messages_admin.php">رسائل العملاء</a>
</nav>

<div class="dashboard">

    <div class="card">
        <h3>إحصائيات عامة</h3>
        <div class="stats">
            إجمالي الحجوزات: <strong><?php echo $bookingCount; ?></strong><br>
            إجمالي المستخدمين: <strong><?php echo $userCount; ?></strong><br>
            أكثر الرحلات حجزًا: <strong><?php echo $topTrip; ?></strong>
        </div>
    </div>

    <div class="card">
        <h3>إدارة الرحلات</h3>
        <a class="btn" href="manage_trips.php">تعديل الرحلات</a>
        <a class="btn" href="add_trips.php">إضافة رحلة جديدة</a>
    </div>

    <div class="card">
        <h3>الدردشة مع العملاء</h3>
        <a class="btn" href="admin_chat_list.php">عرض المحادثات</a>
    </div>

    <div class="card">
        <h3>تغيير الصورة الشخصية</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="new_profile_image" accept="image/*" required>
            <button type="submit" class="btn">تحديث الصورة</button>
        </form>
    </div>

    <div class="card">
        <h3>تغيير صورة صفحة "من نحن"</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="about_background" accept="image/*" required>
            <button type="submit" class="btn">تحديث الخلفية</button>
        </form>
    </div>

</div>

</body>
</html>