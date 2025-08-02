<?php
$serverName = "localhost\\SQLEXPRESS"; // تأكد من اسم السيرفر الصحيح
$connectionOptions = [
    "Database" => "project",  // اسم قاعدة البيانات
    "Uid" => "",              // اسم المستخدم إذا تستخدم SQL Auth
    "PWD" => "",              // كلمة المرور
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("فشل الاتصال: " . print_r(sqlsrv_errors(), true));
} else {
    echo "تم الاتصال بقاعدة البيانات بنجاح!";
}
?>