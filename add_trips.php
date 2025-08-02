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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_trip"])) {
    $trip_name = $_POST["new_trip_name"];
    $price_per_seat = $_POST["new_price_per_seat"];
    $available_seats = $_POST["new_available_seats"];
    $departure_time = str_replace("T", " ", $_POST["departure_time"]);
    $return_time = str_replace("T", " ", $_POST["return_time"]);

    $sql = "INSERT INTO Trips (trip_name, price_per_seat, available_seats, departure_time, return_time)
            VALUES (?, ?, ?, ?, ?)";
    $params = [$trip_name, $price_per_seat, $available_seats, $departure_time, $return_time];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        $trip_id_result = sqlsrv_query($conn, "SELECT SCOPE_IDENTITY() AS id");
        $trip_id = sqlsrv_fetch_array($trip_id_result, SQLSRV_FETCH_ASSOC)["id"];

        if (!empty($_FILES["trip_images"]["name"][0])) {
            foreach ($_FILES["trip_images"]["name"] as $i => $name) {
                if ($_FILES["trip_images"]["error"][$i] === 0) {
                    $image_name = basename($name);
                    $target_path = "images/" . time() . "_$i" . "_" . $image_name;
                    if (move_uploaded_file($_FILES["trip_images"]["tmp_name"][$i], $target_path)) {
                        sqlsrv_query($conn, "INSERT INTO TripImages (trip_id, image_path) VALUES (?, ?)", [$trip_id, $target_path]);
                    }
                }
            }
        }
if (isset($_SESSION["email"])) {
    $admin_email = $_SESSION["email"];
    $log_description = "أضاف رحلة جديدة: '{$trip_name}' (السعر: {$price_per_seat} ر.س، المقاعد: {$available_seats})";
    $log_sql = "INSERT INTO AdminLogs (admin_email, action_description, action_time) VALUES (?, ?, GETDATE())";
    sqlsrv_query($conn, $log_sql, [$admin_email, $log_description]);
}
        echo "<script>alert('تمت إضافة الرحلة بنجاح'); window.location.href='Trips.php';</script>";
    } else {
        echo "<script>alert('فشل في إضافة الرحلة');</script>";
        die(print_r(sqlsrv_errors(), true));
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>إضافة رحلة</title>
  <style>
    body {
      font-family: Tahoma;
      background-color: #f3f0e7;
      padding: 30px;
      margin: 0;
    }

    .back-link {
      display: inline-block;
      margin-bottom: 25px;
      margin-right: 20px;
      font-size: 18px;
      text-decoration: none;
      color: #8b6d35;
      font-weight: bold;
    }

    .form-section {
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      max-width: 600px;
      margin: auto;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    label {
      margin-top: 10px;
      font-weight: bold;
    }

    input, button {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
      box-sizing: border-box;
    }

    button {
      background-color: #8b6d35;
      color: white;
      font-weight: bold;
      cursor: pointer;
      border: none;
      margin-top: 20px;
    }

    button:hover {
      background-color: #6d542a;
    }
  </style>
</head>
<body>

<a href="admin_dashboard.php" class="back-link">← الرجوع إلى لوحة التحكم</a>

<h2 style="text-align:center; color:#8b6d35;">إضافة رحلة جديدة</h2>

<div class="form-section">
  <form method="POST" enctype="multipart/form-data">
    <label>اسم الرحلة:</label>
    <input type="text" name="new_trip_name" required>

    <label>السعر لكل مقعد:</label>
    <input type="number" name="new_price_per_seat" required>

    <label>عدد المقاعد:</label>
    <input type="number" name="new_available_seats" required>

    <label>وقت الانطلاق:</label>
    <input type="datetime-local" name="departure_time" required>

    <label>وقت العودة:</label>
    <input type="datetime-local" name="return_time" required>

    <label>صور الرحلة:</label>
    <input type="file" name="trip_images[]" accept="image/*" multiple required>

    <button type="submit" name="add_trip">إضافة الرحلة</button>
  </form>
</div>

</body>
</html>