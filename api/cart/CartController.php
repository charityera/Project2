<?php
require_once __DIR__ . '/../config/Database.php';

class CartController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function viewCart($user_id) {
        $query = "SELECT cart.cart_id, cart.user_id, cart_item.product_id, product.product_name, cart_item.quantity, product.price
                  FROM shopping_cart AS cart
                  JOIN cart_items AS cart_item ON cart.cart_id = cart_item.cart_id
                  JOIN products AS product ON cart_item.product_id = product.product_id
                  WHERE cart.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
    
        $cart = [
            "cart_id" => null,
            "user_id" => $user_id,
            "items" => [],
            "total_items" => 0,
            "total_price" => 0,
        ];
    
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['total'] = $row['quantity'] * $row['price'];
            $cart['items'][] = $row;
            $cart['total_items'] += $row['quantity'];
            $cart['total_price'] += $row['total'];
            $cart["cart_id"] = $row["cart_id"]; 
        }
        return $cart;
    }
    

    public function addItemToCart($user_id, $product_id, $quantity) {

        $stmt = $this->conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_id = $cart ? $cart["cart_id"] : null;

   
        if (!$cart_id) {
            $stmt = $this->conn->prepare("INSERT INTO shopping_cart (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $cart_id = $this->conn->lastInsertId();
        }


        $stmt = $this->conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $stmt->execute([$cart_id, $product_id, $quantity, $quantity]);

        
        return [
            "message" => "Cart item saved",
            "user_id" => $user_id,
            "product_id" => $product_id,
            "quantity" => $quantity
        ];
    }
}
?>
