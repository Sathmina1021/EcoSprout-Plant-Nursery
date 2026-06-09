<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart.']);
    exit();
}

$action   = $_POST['action'] ?? $_GET['action'] ?? '';
$userId   = $_SESSION['user_id'];
$itemId   = intval($_POST['item_id'] ?? $_GET['item_id'] ?? 0);
$itemType = sanitize($conn, $_POST['item_type'] ?? 'plant');
$quantity = max(1, intval($_POST['quantity'] ?? 1));

if (!in_array($itemType, ['plant', 'product'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid item type.']);
    exit();
}

switch ($action) {
    case 'add':
        if (!$itemId) { echo json_encode(['success'=>false,'message'=>'Invalid item.']); exit(); }

        // Check stock
        $table = $itemType === 'plant' ? 'plants' : 'products';
        $item  = $conn->query("SELECT * FROM $table WHERE id = $itemId AND is_active = 1")->fetch_assoc();
        if (!$item) { echo json_encode(['success'=>false,'message'=>'Item not found.']); exit(); }
        if ($item['stock_quantity'] < 1) { echo json_encode(['success'=>false,'message'=>'Item is out of stock.']); exit(); }

        // Check if already in cart
        $existing = $conn->query("SELECT * FROM cart WHERE user_id=$userId AND item_type='$itemType' AND item_id=$itemId")->fetch_assoc();
        if ($existing) {
            $newQty = $existing['quantity'] + $quantity;
            if ($newQty > $item['stock_quantity']) {
                echo json_encode(['success'=>false,'message'=>'Not enough stock available.']); exit();
            }
            $conn->query("UPDATE cart SET quantity=$newQty WHERE id={$existing['id']}");
        } else {
            $conn->query("INSERT INTO cart (user_id, item_type, item_id, quantity) VALUES ($userId, '$itemType', $itemId, $quantity)");
        }
        echo json_encode(['success' => true, 'cart_count' => getCartCount($conn, $userId), 'message' => 'Added to cart!']);
        break;

    case 'update':
        if (!$itemId || !$quantity) { echo json_encode(['success'=>false,'message'=>'Invalid request.']); exit(); }
        $conn->query("UPDATE cart SET quantity=$quantity WHERE user_id=$userId AND item_id=$itemId AND item_type='$itemType'");
        
        // Recalculate totals
        $cart = $conn->query("
            SELECT c.*, 
                IF(c.item_type='plant', p.price, pr.price) as price,
                IF(c.item_type='plant', p.name, pr.name) as name
            FROM cart c
            LEFT JOIN plants p ON c.item_type='plant' AND c.item_id=p.id
            LEFT JOIN products pr ON c.item_type='product' AND c.item_id=pr.id
            WHERE c.user_id=$userId
        ");
        $subtotal = 0;
        while ($row = $cart->fetch_assoc()) {
            $subtotal += $row['price'] * $row['quantity'];
        }
        echo json_encode(['success'=>true, 'subtotal'=>number_format($subtotal,2), 'cart_count'=>getCartCount($conn,$userId)]);
        break;

    case 'remove':
        $conn->query("DELETE FROM cart WHERE user_id=$userId AND item_id=$itemId AND item_type='$itemType'");
        echo json_encode(['success'=>true, 'cart_count'=>getCartCount($conn,$userId), 'message'=>'Item removed.']);
        break;

    case 'count':
        echo json_encode(['success'=>true, 'count'=>getCartCount($conn,$userId)]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Unknown action.']);
}
?>
