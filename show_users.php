<?php
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

$sql = "SELECT id, username, email FROM users";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die("خطأ في الاستعلام: " . print_r(sqlsrv_errors(), true));
}

echo "<h2>قائمة المستخدمين:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>المعرف</th><th>اسم المستخدم</th><th>البريد الإلكتروني</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "</tr>";
}

echo "</table>";
sqlsrv_close($conn);
?>