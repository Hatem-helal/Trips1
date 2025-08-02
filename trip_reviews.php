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

$trip_id = $_GET['trip_id'] ?? 0;
$username = $_SESSION["username"] ?? null;

if ($username && $trip_id) {
    // جلب معرف المستخدم
    $stmt = sqlsrv_query($conn, "SELECT id, email FROM users WHERE username = ?", [$username]);
    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $customer_id = $user["id"];
    $user_email = $user["email"];

    // التأكد من أنه حجز الرحلة
    $booked = sqlsrv_query($conn, "SELECT * FROM Bookings WHERE customer_id = ? AND trip_id = ?", [$customer_id, $trip_id]);

    if (sqlsrv_has_rows($booked)) {
        // إرسال التقييم
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $rating = intval($_POST["rating"]);
            $comment = substr(trim($_POST["comment"]), 0, 150); // حد أقصى 150 حرف

            $insert = "INSERT INTO Reviews (customer_id, trip_id, rating, comment) VALUES (?, ?, ?, ?)";
            $params = [$customer_id, $trip_id, $rating, $comment];
            sqlsrv_query($conn, $insert, $params);
        }

        // عرض نموذج التقييم
        echo "<h3>أضف تقييمك</h3>";
        echo '<form method="POST">
                <label>التقييم (1-5):</label>
                <select name="rating" required>
                    <option value="5">★★★★★</option>
                    <option value="4">★★★★☆</option>
                    <option value="3">★★★☆☆</option>
                    <option value="2">★★☆☆☆</option>
                    <option value="1">★☆☆☆☆</option>
                </select><br><br>
                <label>التعليق (150 حرف):</label><br>
                <textarea name="comment" maxlength="150" rows="3" required></textarea><br><br>
                <button type="submit">إرسال</button>
              </form><hr>';
    } else {
        echo "<p>لا يمكنك التقييم إلا بعد حجز هذه الرحلة.</p>";
    }
}

// التحقق إذا كان المستخدم أدمن
$isAdmin = false;
if (isset($user_email) && $user_email === "admin@meccatrips.com") {
    $isAdmin = true;
}

// عرض التقييمات
echo "<h3>التقييمات السابقة:</h3>";
$reviews = sqlsrv_query($conn, "SELECT R.rating, R.comment, R.review_date, U.username, U.email
                                FROM Reviews R
                                JOIN users U ON R.customer_id = U.id
                                WHERE R.trip_id = ?", [$trip_id]);

while ($row = sqlsrv_fetch_array($reviews, SQLSRV_FETCH_ASSOC)) {
    echo "<div style='border:1px solid #ccc; border-radius:10px; padding:10px; margin:10px 0'>";
    echo "<p><strong>الاسم:</strong> " . htmlspecialchars($row["username"]) . "</p>";
    if ($isAdmin) {
        echo "<p><strong>البريد:</strong> " . htmlspecialchars($row["email"]) . "</p>";
    }
    echo "<p><strong>التقييم:</strong> " . str_repeat("★", $row["rating"]) . str_repeat("☆", 5 - $row["rating"]) . "</p>";
    echo "<p><strong>التعليق:</strong> " . htmlspecialchars($row["comment"]) . "</p>";
    echo "<p><small>بتاريخ: " . $row["review_date"]->format("Y-m-d H:i") . "</small></p>";
    echo "</div>";
}
?>