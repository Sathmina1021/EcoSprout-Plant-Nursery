<?php
$pageTitle = 'My Cart';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    flashMessage('info', 'Please login to view your cart.');
    redirect('login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$userId = $_SESSION['user_id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item'])) {
        $itemId   = intval($_POST['item_id']);
        $itemType = sanitize($conn, $_POST['item_type']);
        $conn->query("DELETE FROM cart WHERE user_id=$userId AND item_id=$itemId AND item_type='$itemType'");
        flashMessage('success', 'Item removed from cart.');
        header('Location: ' . SITE_URL . '/cart.php'); exit();
    }
}

// Fetch cart items with details
$cartItems = $conn->query("
    SELECT c.*,
        IF(c.item_type='plant', p.name, pr.name) as item_name,
        IF(c.item_type='plant', p.price, pr.price) as price,
        IF(c.item_type='plant', p.image, pr.image) as image,
        IF(c.item_type='plant', p.stock_quantity, pr.stock_quantity) as stock
    FROM cart c
    LEFT JOIN plants p ON c.item_type='plant' AND c.item_id=p.id
    LEFT JOIN products pr ON c.item_type='product' AND c.item_id=pr.id
    WHERE c.user_id = $userId
    ORDER BY c.added_at DESC
");

$items       = [];
$subtotal    = 0;
$deliveryFee = 350.00;

while ($row = $cartItems->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['quantity'];
    $subtotal += $row['line_total'];
    $items[]  = $row;
}
$total = $subtotal + ($subtotal > 0 ? $deliveryFee : 0);
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>🛒 My Shopping Cart</h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span><span>Cart</span>
    </div>
</div>

<div class="section">
    <?php if (empty($items)): ?>
    <div class="empty-state">
        <i class="fas fa-shopping-basket"></i>
        <h3>Your cart is empty</h3>
        <p>Add some beautiful plants or products to get started!</p>
        <a href="<?= SITE_URL ?>/plants.php" class="btn-primary"><i class="fas fa-seedling"></i> Browse Plants</a>
    </div>
    <?php else: ?>
    <div style="display:grid; grid-template-columns:1fr 320px; gap:28px; align-items:start;">
        <!-- Cart Items -->
        <div>
            <div style="background:white; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); overflow:hidden;">
                <div style="padding:18px 24px; border-bottom:1px solid var(--cream-dark); display:flex; justify-content:space-between; align-items:center;">
                    <h3 style="font-size:1.1rem;"><?= count($items) ?> Item<?= count($items)!==1?'s':'' ?> in Cart</h3>
                    <a href="<?= SITE_URL ?>/plants.php" style="font-size:13px; color:var(--green-mid);">
                        <i class="fas fa-plus"></i> Add More
                    </a>
                </div>

                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr id="cart-row-<?= $item['id'] ?>">
                            <td>
                                <div style="display:flex; align-items:center; gap:14px;">
                                    <img src="<?= $item['image'] && $item['image'] !== 'default_plant.jpg'
                                        ? SITE_URL.'/uploads/plants/'.htmlspecialchars($item['image'])
                                        : '<?= SITE_URL ?>/uploads/plants/default_plant.jpg' ?>"
                                         alt="<?= htmlspecialchars($item['item_name']) ?>" class="cart-item-image">
                                    <div>
                                        <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($item['item_name']) ?></div>
                                        <div style="font-size:12px; color:var(--text-light);"><?= ucfirst($item['item_type']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-weight:600;"><?= formatPrice($item['price']) ?></td>
                            <td>
                                <div class="cart-qty">
                                    <button class="qty-btn" data-action="decrease" 
                                            onclick="updateQty(<?= $item['id'] ?>, <?= $item['item_id'] ?>, '<?= $item['item_type'] ?>', -1)">−</button>
                                    <input type="number" class="qty-input" id="qty-<?= $item['id'] ?>"
                                           value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                                           readonly style="width:48px;">
                                    <button class="qty-btn" data-action="increase"
                                            onclick="updateQty(<?= $item['id'] ?>, <?= $item['item_id'] ?>, '<?= $item['item_type'] ?>', 1)">+</button>
                                </div>
                            </td>
                            <td style="font-weight:700;" id="total-<?= $item['id'] ?>"><?= formatPrice($item['line_total']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                    <input type="hidden" name="item_type" value="<?= $item['item_type'] ?>">
                                    <button type="submit" name="remove_item" value="1"
                                            style="background:none; border:none; color:var(--text-light); cursor:pointer; font-size:16px; padding:4px 8px; border-radius:6px; transition:var(--transition);"
                                            onclick="return confirm('Remove this item?')"
                                            title="Remove item">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Order Summary -->
        <div>
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal (<?= count($items) ?> items)</span>
                    <span id="cartSubtotal"><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span><?= formatPrice($deliveryFee) ?></span>
                </div>
                <div class="summary-row" style="font-size:12px; color:var(--text-light);">
                    <span>Estimated Delivery</span>
                    <span>3–5 Business Days</span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span id="cartTotal"><?= formatPrice($total) ?></span>
                </div>
                <a href="<?= SITE_URL ?>/checkout.php" class="btn-primary" style="width:100%; justify-content:center; margin-top:18px; font-size:15px;">
                    <i class="fas fa-credit-card"></i> Proceed to Checkout
                </a>
                <a href="<?= SITE_URL ?>/plants.php" style="display:block; text-align:center; margin-top:12px; font-size:13px; color:var(--text-light);">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>

            <!-- Promo note -->
            <div style="margin-top:14px; padding:14px; background:var(--green-pale); border-radius:var(--radius); font-size:13px; color:var(--green-dark);">
                <i class="fas fa-leaf"></i> <strong>Free delivery</strong> on orders over Rs. 5,000!
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
async function updateQty(cartId, itemId, itemType, delta) {
    const input = document.getElementById('qty-' + cartId);
    const maxQty = parseInt(input.getAttribute('max')) || 99;
    const newQty = Math.min(maxQty, Math.max(1, parseInt(input.value) + delta));
    input.value = newQty;

    try {
        const res = await fetch((window.ECOSPROUT_SITE_URL || '').replace(/\/$/, '') + '/cart_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=update&item_id=${itemId}&item_type=${itemType}&quantity=${newQty}`
        });
        const data = await res.json();
        if (data.success) {
            // Update cart badge
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = data.cart_count;
            // Reload for simplicity (could do full AJAX update)
            location.reload();
        }
    } catch(e) { console.error(e); }
}
</script>

<?php require_once 'includes/footer.php'; ?>
