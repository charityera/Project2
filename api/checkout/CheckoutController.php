<?php
require_once __DIR__ . '/../config/Database.php';


class CheckoutController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function initiateCheckout($user_id, $shipping_address) {
        $cartController = new CartController();
        $cart = $cartController->viewCart($user_id);

        if ($cart['total_items'] === 0) {
            return ["message" => "Cart is empty, cannot proceed to checkout"];
        }

        $query = "INSERT INTO orders (user_id, total_amount, shipping_address, status) VALUES (?, ?, ?, 'processed')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $cart["total_price"], json_encode($shipping_address)]);

        return ["message" => "Checkout initiated", "order_id" => $this->conn->lastInsertId()];
    }
}
?>
