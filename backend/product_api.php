<?php
// Thiết lập Header để cho phép truy cập từ Frontend (CORS) và định dạng JSON
header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

// Kích hoạt Session (cần thiết nếu bạn muốn truy cập session giỏ hàng trong API này)
// session_start(); 

// 1. KẾT NỐI CƠ SỞ DỮ LIỆU
// Giả sử file condb.php nằm cùng thư mục hoặc đường dẫn tương đối đúng
require 'condb.php'; 

// Hàm tiện ích để trả về phản hồi JSON
function respond($status, $data = [], $message = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// 2. LOGIC CHÍNH: LẤY DANH SÁCH SẢN PHẨM

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Truy vấn lấy các trường cần thiết (đảm bảo các tên cột trùng khớp với DB của bạn)
        $sql = 'SELECT id, name, price, description FROM product ORDER BY id ASC';
        
        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($products) {
            // Trả về danh sách sản phẩm thành công
            respond('success', $products, 'Tải danh sách sản phẩm thành công.');
        } else {
            // Trường hợp không có sản phẩm nào trong DB
            respond('success', [], 'Không tìm thấy sản phẩm nào.');
        }

    } catch (PDOException $e) {
        // Ghi lại lỗi và trả về lỗi cho frontend
        error_log('Database error: ' . $e->getMessage());
        respond('error', [], 'Lỗi hệ thống: Không thể kết nối hoặc truy vấn cơ sở dữ liệu.');
    }
} else {
    // Trường hợp yêu cầu không phải là GET
    respond('error', [], 'Phương thức yêu cầu không hợp lệ. Chỉ chấp nhận GET.');
}

// Chú thích:
// File này chỉ xử lý việc LẤY (GET) danh sách sản phẩm.
// Các thao tác thêm, sửa, xóa giỏ hàng NÊN được đặt trong file 'cart_api.php'
// để phân tách rõ ràng chức năng.
?>