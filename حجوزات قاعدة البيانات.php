<?php
include 'db.php'; // الاتصال بقاعدة البيانات

// بدء الجلسة للحصول على معرف المستخدم
session_start();
$user_id = $_SESSION['user_id']; // الحصول على معرف المستخدم من الجلسة

// استعلام لاستخراج الحجوزات للمستخدم
$query = "SELECT * FROM Bookings WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id); // ربط معرف المستخدم بالاستعلام
$stmt->execute();
$result = $stmt->get_result();

// تحقق إذا كان هناك حجوزات
if ($result->num_rows > 0) {
    // عرض الحجوزات
    while ($row = $result->fetch_assoc()) {
        echo '<div class="booking-item">';
        echo '<p><strong>الرحلة:</strong> ' . htmlspecialchars($row['trip_name']) . '</p>';
        echo '<p><strong>التاريخ:</strong> ' . htmlspecialchars($row['trip_date']) . '</p>';
        echo '<p><strong>عدد المقاعد:</strong> ' . htmlspecialchars($row['seats']) . '</p>';
        echo '<p><strong>طريقة الدفع:</strong> ' . htmlspecialchars($row['payment_method']) . '</p>';
        echo '</div>';
    }
} else {
    echo '<p>لا توجد حجوزات لعرضها.</p>';
}

$stmt->close();
$conn->close();
?>
