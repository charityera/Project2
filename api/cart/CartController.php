<?php
require_once __DIR__ . '/../config/Database.php';

class CartController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function viewCart($user_id) {
        $query = "SELECT cart.cart_id, 
                             cart_item.product_id, 
                             product.product_name, 
                             SUM(cart_item.quantity) AS quantity, 
                             product.price
                      FROM cart_items AS cart_item
                      JOIN products AS product ON cart_item.product_id = product.product_id
                      JOIN (SELECT cart_id FROM carts WHERE user_id = ?) AS cart ON cart_item.cart_id = cart.cart_id
                      GROUP BY cart_item.product_id";
        
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
            $row['total'] = number_format($row['quantity'] * $row['price'], 2, '.', ''); 
     
            $cart['items'][] = [
                "product_id" => $row['product_id'],
                "product_name" => $row['product_name'],
                "quantity" => (int)$row['quantity'], 
                "price" => number_format((float)$row['price'], 2, '.', ''),
                "total" => $row['total']
            ];
            
            $cart['total_items'] += $row['quantity'];
            $cart['total_price'] += (float)$row['total'];
            $cart["cart_id"] = $row["cart_id"]; 
        }
    
        $cart['total_price'] = number_format($cart['total_price'], 2, '.', '');
    
        return $cart;
    }
    
    

    public function addItemToCart($user_id, $product_id, $quantity) {
        $stmt = $this->conn->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_id = $cart ? $cart["cart_id"] : null;

        if (!$cart_id) {
            $stmt = $this->conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
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

    public function updateItemQuantityById($user_id, $product_id, $quantity) {
        $stmt = $this->conn->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_id = $cart ? $cart["cart_id"] : null;
    
        if (!$cart_id) {
            return ["message" => "Cart not found for user."];
        }
    
        $stmt = $this->conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $cart_id, $product_id]);
    
        if ($stmt->rowCount() > 0) {
            return $this->viewCart($user_id); 
        } else {
            return ["message" => "Product not found in the cart or quantity is the same."];
        }
    }
    

}
?>
