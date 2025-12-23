<?php
/**
 * API Helper - Hỗ trợ các thao tác giỏ hàng
 */
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();
header('Content-Type: application/json');

//Test 1
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';

// API: Lấy số lượng sản phẩm trong giỏ abc
if ($action === 'get_cart_count') {
    $count = array_sum(array_column($_SESSION['cart'], 'quantity'));
    echo json_encode(['count' => $count]);
}

// API: Lấy tổng tiền giỏ hàng
elseif ($action === 'get_cart_total') {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    echo json_encode(['total' => $total]);
}

// API: Lấy toàn bộ giỏ hàng
elseif ($action === 'get_cart') {
    echo json_encode($_SESSION['cart']);
}

// API: Xóa sản phẩm
elseif ($action === 'remove' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success' => true, 'message' => 'Sản phẩm đã xóa']);
}

// API: Xóa hết giỏ hàng
elseif ($action === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'message' => 'Giỏ hàng đã xóa']);
}

else {
    echo json_encode(['error' => 'Action không hợp lệ']);
}
?>
