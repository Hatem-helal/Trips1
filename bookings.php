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
    die(print_r(sqlsrv_errors(), true));
}

$username = $_SESSION["username"];
$user_stmt = sqlsrv_query($conn, "SELECT id FROM users WHERE username = ?", [$username]);
$user = sqlsrv_fetch_array($user_stmt, SQLSRV_FETCH_ASSOC);
$customer_id = $user["id"];

$sql = "SELECT T.trip_name, B.seats_count, B.payment_method, B.booking_date 
        FROM Bookings B
        JOIN Trips T ON B.trip_id = T.trip_id
        WHERE B.customer_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$customer_id]);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<div class='booking-item'>";
    echo "<p><strong>الرحلة:</strong> " . $row["trip_name"] . "</p>";
    echo "<p><strong>عدد المقاعد:</strong> " . $row["seats_count"] . "</p>";
    echo "<p><strong>طريقة الدفع:</strong> " . $row["payment_method"] . "</p>";
    echo "<p><strong>تاريخ الحجز:</strong> " . $row["booking_date"]->format("Y-m-d H:i") . "</p>";
    echo "</div>";
}
?>