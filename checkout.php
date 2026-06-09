<?php
$pageTitle = 'Checkout';
require_once 'includes/header.php';

if (!isLoggedIn()) { redirect('login.php'); }

$userId = $_SESSION['user_id'];

// Get cart
$cartItems = $conn->query("
    SELECT c.*,
        IF(c.item_type='plant', p.name, pr.name) as item_name,
        IF(c.item_type='plant', p.price, pr.price) as price,
        IF(c.item_type='plant', p.stock_quantity, pr.stock_quantity) as stock
    FROM cart c
    LEFT JOIN plants p ON c.item_type='plant' AND c.item_id=p.id
    LEFT JOIN products pr ON c.item_type='product' AND c.item_id=pr.id
    WHERE c.user_id = $userId
");

$items = [];
$subtotal = 0;
while ($row = $cartItems->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['line_total'];
    $items[] = $row;
}

if (empty($items)) {
    flashMessage('info', 'Your cart is empty.');
    redirect('plants.php');
}

$deliveryFee = 350.00;
$total = $subtotal + $deliveryFee;

// Get user details
$user = $conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryAddress = sanitize($conn, $_POST['delivery_address'] ?? '');
    $paymentMethod   = sanitize($conn, $_POST['payment_method'] ?? 'cash_on_delivery');
    $notes           = sanitize($conn, $_POST['notes'] ?? '');

    if (empty($deliveryAddress)) $errors['delivery_address'] = 'Delivery address is required.';
    if (!in_array($paymentMethod, ['cash_on_delivery','bank_transfer','online'])) {
        $errors['payment_method'] = 'Invalid payment method.';
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $orderNumber = generateOrderNumber();
            $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, delivery_address, payment_method, notes) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("isdsss", $userId, $orderNumber, $total, $deliveryAddress, $paymentMethod, $notes);
            $stmt->execute();
            $orderId = $conn->insert_id;
            $stmt->close();

            // Insert order items
            foreach ($items as $item) {
                $oId    = $orderId;
                $iType  = $item['item_type'];
                $iId    = intval($item['item_id']);
                $iName  = $item['item_name'];
                $qty    = intval($item['quantity']);
                $uPrice = floatval($item['price']);
                $sub    = floatval($item['line_total']);

                $stmt = $conn->prepare("INSERT INTO order_items (order_id,item_type,item_id,item_name,quantity,unit_price,subtotal) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("isisidd", $oId, $iType, $iId, $iName, $qty, $uPrice, $sub);
                $stmt->execute();
                $stmt->close();

                // Update stock
                $table = $item['item_type'] === 'plant' ? 'plants' : 'products';
                $conn->query("UPDATE $table SET stock_quantity = stock_quantity - {$qty} WHERE id = {$iId}");
            }

            // Clear cart
            $conn->query("DELETE FROM cart WHERE user_id = $userId");

            $conn->commit();
            flashMessage('success', "Order #$orderNumber placed successfully! 🌱");
            redirect("customer/order_confirmation.php?order=$orderNumber");

        } catch (Exception $e) {
            $conn->rollback();
            $errors['general'] = 'Order failed. Please try again.';
        }
    }
}
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>Checkout</h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/cart.php">Cart</a><span>/</span>
        <span>Checkout</span>
    </div>
</div>

<div class="section">
    <?php if (!empty($errors['general'])): ?>
    <div class="flash-message flash-error" style="position:static;transform:none;margin-bottom:20px;">
        <i class="fas fa-times-circle"></i> <?= htmlspecialchars($errors['general']) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 340px; gap:28px; align-items:start;">
        <form method="POST" id="checkoutForm">
            <!-- Delivery Details -->
            <div style="background:white; border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-sm); margin-bottom:20px;">
                <h3 style="margin-bottom:18px; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-truck" style="color:var(--green-light);"></i> Delivery Details
                </h3>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" readonly style="background:var(--cream);">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly style="background:var(--cream);">
                </div>
                <div class="form-group">
                    <label>Delivery Address <span style="color:var(--error);">*</span></label>
                    <textarea name="delivery_address" class="form-control <?= isset($errors['delivery_address']) ? 'error' : '' ?>"
                              rows="3" placeholder="Enter your full delivery address..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    <?php if (isset($errors['delivery_address'])): ?>
                    <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['delivery_address'] ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Order Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Any special instructions for delivery..."></textarea>
                </div>
            </div>

            <!-- Payment Method -->
            <div style="background:white; border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-sm);">
                <h3 style="margin-bottom:18px; display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-credit-card" style="color:var(--green-light);"></i> Payment Method
                </h3>
                <?php foreach(['cash_on_delivery'=>['💵','Cash on Delivery','Pay when your plants arrive'],'bank_transfer'=>['🏦','Bank Transfer','Transfer to our account before delivery'],'online'=>['💳','Online Payment','Secure card payment']] as $val => [$icon,$label,$desc]): ?>
                <label style="display:flex; align-items:center; gap:14px; padding:14px; border:1.5px solid var(--cream-dark); border-radius:10px; cursor:pointer; margin-bottom:10px; transition:var(--transition);">
                    <input type="radio" name="payment_method" value="<?= $val ?>" <?= $val==='cash_on_delivery'?'checked':'' ?>
                           style="accent-color:var(--green-mid);" onchange="this.closest('label').style.borderColor='var(--green-light)'">
                    <span style="font-size:20px;"><?= $icon ?></span>
                    <div>
                        <div style="font-weight:600; font-size:14px;"><?= $label ?></div>
                        <div style="font-size:12px; color:var(--text-light);"><?= $desc ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </form>

        <!-- Summary -->
        <div>
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach ($items as $item): ?>
                <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px;">
                    <span><?= htmlspecialchars(substr($item['item_name'],0,25)) ?> × <?= $item['quantity'] ?></span>
                    <span><?= formatPrice($item['line_total']) ?></span>
                </div>
                <?php endforeach; ?>
                <hr style="border:none;border-top:1px solid var(--cream-dark);margin:12px 0;">
                <div class="summary-row"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
                <div class="summary-row"><span>Delivery</span><span><?= formatPrice($deliveryFee) ?></span></div>
                <div class="summary-total"><span>Total</span><span><?= formatPrice($total) ?></span></div>
                <button form="checkoutForm" type="submit" class="btn-submit" style="margin-top:18px;">
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
