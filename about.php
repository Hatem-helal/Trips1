<?php
// الاتصال بقاعدة البيانات
$serverName = "localhost\\SQLEXPRESS";
$connectionOptions = ["Database" => "project", "CharacterSet" => "UTF-8"];
$conn = sqlsrv_connect($serverName, $connectionOptions);

// جلب مسار الخلفية
$background = "images/default.jpg"; // صورة افتراضية
$stmt = sqlsrv_query($conn, "SELECT about_image_path FROM SiteSettings WHERE id = 1");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    if (!empty($row["about_image_path"])) {
        $background = $row["about_image_path"];
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>من نحن - رحلات مكة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
            background-image: url('<?= $background ?>');
            background-size: cover;
            background-position: center;
            height: 100vh;
            color: white;
            cursor: pointer;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.55); /* تغطية شفافة للنص */
            width: 100%;
            height: 100%;
            padding: 50px 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }

        p {
            font-size: 20px;
            max-width: 800px;
            line-height: 2;
        }

        .note {
            margin-top: 25px;
            font-size: 14px;
            color: #ccc;
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.body.addEventListener("click", function () {
                window.location.href = "homee.php";
            });
        });
    </script>
</head>
<body>

<div class="overlay">
    <h1>منصة رحلات مكة</h1>
    <p>
        نُرحب بكم في منصتنا التي تهدف إلى تسهيل رحلات الزوار والمعتمرين إلى أقدس البقاع، مكة المكرمة.  
        نقدم لكم تجربة سلسة لحجز الرحلات الدينية والتراثية بأعلى مستويات الراحة والجودة والاهتمام.
    </p>
    <p>
        أهدافنا:<br>
        - تقديم رحلات مخصصة ومتميزة.<br>
        - دعم الزوار بخدمة احترافية.<br>
        - تسهيل الوصول إلى الأماكن المقدسة بكل أمان وسهولة.<br>
        -(هذا الموقع مشروع تخرج وليس موقع حقيقي للرحلات)
    </p>
    <div class="note">اضغط في أي مكان للانتقال إلى الصفحة الرئيسية</div>
</div>

</body>
</html>