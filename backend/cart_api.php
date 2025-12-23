<?php
// ===================================================================
// CẤU HÌNH CORS VÀ SESSION - QUAN TRỌNG!
// ===================================================================

// Cho phép truy cập từ frontend
$allowed_origin = 'http://localhost:3000';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Cấu hình session cookie
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Bắt đầu session
session_start();

// Debug log
error_log('=== CART API DEBUG ===');
error_log('Session ID: ' . session_id());
error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Action: ' . ($_GET['action'] ?? 'none'));

// Kết nối database
require 'condb.php'; 

// ===================================================================
// HÀM TIỆN ÍCH
// ===================================================================

function respond($status, $message = null, $cart_items = [], $total_items = 0, $grand_total = 0) {
    $response = [
        'status' => $status,
        'message' => $message,
        'cart_items' => $cart_items,
        'total_items' => $total_items,
        'grand_total' => $grand_total
    ];
    
    error_log('Response: ' . json_encode($response));
    echo json_encode($response);
    exit();
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    error_log('Initialized empty cart');
}

error_log('Current cart: ' . json_encode($_SESSION['cart']));

$action = $_GET['action'] ?? 'view'; 

// ===================================================================
// 1. THÊM SẢN PHẨM VÀO GIỎ (action=add)
// ===================================================================
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = 1; 

    if (!$product_id) {
        respond('error', 'Thiếu hoặc ID sản phẩm không hợp lệ.');
    }

    try {
        $stmt = $pdo->prepare('SELECT id, name, price FROM product WHERE id = ?');
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            respond('error', 'Sản phẩm không tồn tại.');
        }

        // Thêm vào session
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'], 
                'quantity' => $quantity
            ];
        }

        error_log('Cart after add: ' . json_encode($_SESSION['cart']));

        // Tính toán lại
        $cart = $_SESSION['cart'];
        $totalItems = array_sum(array_column($cart, 'quantity'));
        $grandTotal = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cart));

        respond('success', 'Thêm vào giỏ hàng thành công.', array_values($cart), $totalItems, $grandTotal);

    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        respond('error', 'Lỗi hệ thống khi truy vấn sản phẩm.');
    }
}

// ===================================================================
// 2. XEM GIỎ HÀNG (action=view)
// ===================================================================
if ($action === 'view' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    error_log('View cart - Session cart: ' . json_encode($_SESSION['cart']));

    $cart = $_SESSION['cart'];

    if (empty($cart)) {
        respond('success', 'Giỏ hàng trống', [], 0, 0);
    }

    $totalItems = array_sum(array_column($cart, 'quantity'));
    $grandTotal = array_sum(array_map(function($item) {
        return $item['price'] * $item['quantity'];
    }, $cart));

    respond('success', 'Lấy giỏ hàng thành công', array_values($cart), $totalItems, $grandTotal);
}

// ===================================================================
// 3. CẬP NHẬT SỐ LƯỢNG (action=update)
// ===================================================================
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $new_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$product_id || $new_quantity === false || $new_quantity < 1) {
        respond('error', 'Dữ liệu cập nhật không hợp lệ.');
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
        
        // Tính toán lại
        $cart = $_SESSION['cart'];
        $totalItems = array_sum(array_column($cart, 'quantity'));
        $grandTotal = array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cart));

        respond('success', 'Cập nhật số lượng thành công.', array_values($cart), $totalItems, $grandTotal);
    } else {
        respond('error', 'Sản phẩm không có trong giỏ hàng.');
    }
}

// ===================================================================
// 4. XÓA SẢN PHẨM (action=remove)
// ===================================================================
if ($action === 'remove' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if (!$product_id) {
        respond('error', 'Thiếu hoặc ID sản phẩm không hợp lệ.');
    }

    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        
        // Tính toán lại
        $cart = $_SESSION['cart'];
        $totalItems = empty($cart) ? 0 : array_sum(array_column($cart, 'quantity'));
        $grandTotal = empty($cart) ? 0 : array_sum(array_map(function($item) {
            return $item['price'] * $item['quantity'];
        }, $cart));

        respond('success', 'Đã xóa sản phẩm khỏi giỏ hàng.', array_values($cart), $totalItems, $grandTotal);
    } else {
        respond('error', 'Sản phẩm không có trong giỏ hàng.');
    }
}

// Hành động không hợp lệ
respond('error', 'Hành động không hợp lệ.');
?>
