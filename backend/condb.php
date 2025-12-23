<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '4000';
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$options = [
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Tắt kiểm tra chứng chỉ nghiêm ngặt
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>
