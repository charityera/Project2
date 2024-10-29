<?php
require 'cart/CartController.php';
require 'checkout/CheckoutController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

$cartController = new CartController();
$checkoutController = new CheckoutController();

$routes = [
    '/api/cart' => function() use ($cartController, $method) {
        if ($method === 'GET') {
            $user_id = $_GET['user_id'] ?? null; 
            if ($user_id) {
                return $cartController->viewCart($user_id);
            } else {
                http_response_code(400);
                return ["message" => "user_id is required"];
            }
        }
    },
    '/api/checkout/initiate' => function() use ($checkoutController, $method, $data) {
        if ($method === 'POST') {
            $user_id = $data['user_id'];
            $shipping_address = $data['shipping_address'];
            return $checkoutController->initiateCheckout($user_id, $shipping_address);
        }
    },
    '/api/cart_items' => function() use ($cartController, $method, $data) {
        if ($method === 'POST') {
            $user_id = $data['user_id'];
            $product_id = $data['product_id'];
            $quantity = $data['quantity'];
            return $cartController->addItemToCart($user_id, $product_id, $quantity);
        }
    },
];

$response = null;
if (array_key_exists($path, $routes)) {
    $response = $routes[$path]();
} else {
    http_response_code(404);
    $response = ["message" => "Endpoint not found"];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
