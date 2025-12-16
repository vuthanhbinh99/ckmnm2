<?php
// Thiết lập cho phép CORS nếu frontend và backend khác tên miền
header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

session_start();
// Sử dụng file kết nối cơ sở dữ liệu để đảm bảo môi trường hoạt động
require 'condb.php'; 

// Hàm tiện ích để trả về phản hồi JSON
function respond($status, $data = [], $message = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}

// Khởi tạo giỏ hàng nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? 'view'; 

// ===================================================================
// 1. XỬ LÝ HÀNH ĐỘNG THÊM SẢN PHẨM (action=add) - Không thay đổi
// ===================================================================
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = 1; 

    if (!$product_id) {
        respond('error', [], 'Thiếu hoặc ID sản phẩm không hợp lệ.');
    }

    try {
        // Lấy thông tin chi tiết sản phẩm từ database
        $stmt = $pdo->prepare('SELECT id, name, price FROM product WHERE id = ?');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            respond('error', [], 'Sản phẩm không tồn tại trong cơ sở dữ liệu.');
        }

        // Thêm hoặc tăng số lượng trong PHP Session
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'], 
                'quantity' => $quantity
            ];
        }

        respond('success', ['cart_item' => $_SESSION['cart'][$product_id]], 'Thêm vào giỏ hàng thành công.');

    } catch (PDOException $e) {
        error_log('Database error during cart add: ' . $e->getMessage());
        respond('error', [], 'Lỗi hệ thống khi truy vấn sản phẩm.');
    }
}

// ===================================================================
// 3. XỬ LÝ HÀNH ĐỘNG CẬP NHẬT SỐ LƯỢNG (action=update)
// ===================================================================
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $new_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$product_id || $new_quantity === false || $new_quantity < 1) {
        respond('error', [], 'Dữ liệu cập nhật không hợp lệ.');
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        respond('success', $_SESSION['cart'][$product_id], 'Cập nhật số lượng thành công.');
    } else {
        respond('error', [], 'Sản phẩm không có trong giỏ hàng.');
    }
}

// ===================================================================
// 4. XỬ LÝ HÀNH ĐỘNG XÓA SẢN PHẨM (action=remove)
// ===================================================================
if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if (!$product_id) {
        respond('error', [], 'Thiếu hoặc ID sản phẩm không hợp lệ.');
    }

    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        respond('success', [], 'Đã xóa sản phẩm khỏi giỏ hàng.');
    } else {
        respond('error', [], 'Sản phẩm không có trong giỏ hàng.');
    }
}


// ===================================================================
// 2. XỬ LÝ HÀNH ĐỘNG XEM GIỎ HÀNG (action=view HOẶC MẶC ĐỊNH) - Không thay đổi
// ===================================================================
if ($action === 'view' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cart = $_SESSION['cart'];

    // Tính toán tổng số lượng và tổng tiền
    $totalItems = array_sum(array_column($cart, 'quantity'));
    $grandTotal = array_sum(array_map(function($item) {
        return $item['price'] * $item['quantity'];
    }, $cart));

    // Dữ liệu sẽ trả về cho frontend
    $response = [
        'status' => 'success',
        'cart_items' => array_values($cart), 
        'total_items' => $totalItems,
        'grand_total' => $grandTotal
    ];

    echo json_encode($response);
    exit;
}

// Trường hợp không có action nào khớp
if (!in_array($action, ['add', 'view', 'update', 'remove'])) {
    respond('error', [], 'Hành động không hợp lệ.');
}

?>
