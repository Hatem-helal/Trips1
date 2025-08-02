<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: Log_in.html");
    exit;
}

$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// تعديل الرحلة
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_trip"])) {
    $trip_id = $_POST["trip_id"];
    $trip_name = $_POST["trip_name"];
    $price_per_seat = $_POST["price_per_seat"];
    $available_seats = $_POST["available_seats"];

    // تحديث بيانات الرحلة
    $sql = "UPDATE Trips SET trip_name=?, price_per_seat=?, available_seats=? WHERE trip_id=?";
    sqlsrv_query($conn, $sql, [$trip_name, $price_per_seat, $available_seats, $trip_id]);
$admin_email = $_SESSION["email"]; // تأكد أن الجلسة تحتوي على إيميل الأدمن
$log_description = "قام بتعديل عدد المقاعد إلى {$available_seats} للرحلة: {$trip_name}";
sqlsrv_query($conn, "INSERT INTO AdminLogs (admin_email, action_description, action_time) VALUES (?, ?, GETDATE())", [$admin_email, $log_description]);
    // رفع صور جديدة إن وُجدت
    if (!empty($_FILES["new_images"]["name"][0])) {
        foreach ($_FILES["new_images"]["name"] as $i => $name) {
            if ($_FILES["new_images"]["error"][$i] === 0) {
                $image_name = basename($name);
                $target_path = "images/" . time() . "_" . $image_name;
                if (move_uploaded_file($_FILES["new_images"]["tmp_name"][$i], $target_path)) {
                    sqlsrv_query($conn, "INSERT INTO TripImages (trip_id, image_path) VALUES (?, ?)", [$trip_id, $target_path]);
                }
            }
        }
    }
}

// حذف الرحلة
if (isset($_POST["delete_trip"])) {
    $trip_id = $_POST["trip_id"];
    sqlsrv_query($conn, "DELETE FROM TripImages WHERE trip_id = ?", [$trip_id]);
    sqlsrv_query($conn, "DELETE FROM Trips WHERE trip_id = ?", [$trip_id]);
}

// جلب جميع الرحلات
$stmt = sqlsrv_query($conn, "SELECT * FROM Trips");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الرحلات</title>
    <style>
        body {
            font-family: Tahoma;
            background-color: #f3f0e7;
            padding: 30px;
            margin: 0;
        }

        h2 {
            text-align: center;
            color: #8b6d35;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            color: #8b6d35;
            text-decoration: none;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #bfa76f;
            color: white;
        }

        input[type="text"], input[type="number"], input[type="file"] {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }

        .save-btn {
            background-color: #8b6d35;
            color: white;
        }

        .delete-btn {
            background-color: #d9534f;
            color: white;
        }

        .images-preview {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .images-preview img {
            max-height: 60px;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<a href="admin_dashboard.php" class="back-link">← الرجوع إلى لوحة التحكم</a>

<h2>إدارة الرحلات</h2>

<table>
    <tr>
        <th>اسم الرحلة</th>
        <th>السعر</th>
        <th>المقاعد</th>
        <th>الصور الحالية</th>
        <th>إضافة صور جديدة</th>
        <th>خيارات</th>
    </tr>
    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $trip_id = $row['trip_id'];
        $images_stmt = sqlsrv_query($conn, "SELECT image_path FROM TripImages WHERE trip_id = ?", [$trip_id]);
        ?>
    <tr>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="trip_id" value="<?= $trip_id ?>">
            <td><input type="text" name="trip_name" value="<?= htmlspecialchars($row['trip_name']) ?>"></td>
            <td><input type="number" name="price_per_seat" value="<?= $row['price_per_seat'] ?>"></td>
            <td><input type="number" name="available_seats" value="<?= $row['available_seats'] ?>"></td>
            <td>
                <div class="images-preview">
                    <?php while ($img = sqlsrv_fetch_array($images_stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="صورة">
                    <?php endwhile; ?>
                </div>
            </td>
            <td><input type="file" name="new_images[]" multiple accept="image/*"></td>
            <td>
                <button type="submit" name="update_trip" class="save-btn">حفظ</button>
                <button type="submit" name="delete_trip" class="delete-btn" onclick="return confirm('هل أنت متأكد من حذف الرحلة؟')">حذف</button>
            </td>
        </form>
    </tr>
    <?php } ?>
</table>

</body>
</html>