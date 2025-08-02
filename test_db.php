<?php
$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "project",
    "Uid" => "",
    "PWD" => "",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn) {
    echo "تم الاتصال بقاعدة البيانات بنجاح.";
} else {
    echo "فشل الاتصال بقاعدة البيانات:<br>";
    die(print_r(sqlsrv_errors(), true));
}
?>