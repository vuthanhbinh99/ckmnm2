<?php
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '4000';
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');

$options = [
    // Chỉ định file chứng chỉ gốc của hệ thống Debian/Ubuntu bên trong Docker
    PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
    
    // Ép buộc phải kiểm tra chứng chỉ để đảm bảo kết nối là Secure
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true, 

    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Thêm charset vào DSN
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Nếu vẫn lỗi, hãy thử bỏ dòng 'PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true' 
    // nhưng GIỮ LẠI dòng 'PDO::MYSQL_ATTR_SSL_CA'
    die("Lỗi kết nối database: " . $e->getMessage());
}
?>
