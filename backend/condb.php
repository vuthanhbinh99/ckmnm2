<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '4000';
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

$options = [
    // Dòng quan trọng nhất: Chấp nhận kết nối SSL nhưng không bắt lỗi xác thực chứng chỉ
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, 
    PDO::MYSQL_ATTR_SSL_CA => true, // Vẫn bật SSL

    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Nếu chạy đến đây là thành công!
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>
