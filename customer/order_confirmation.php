<?php
$pageTitle = 'Order Confirmed';
require_once '../includes/header.php';

if (!isLoggedIn()) { redirect('../login.php'); }

$orderNumber = sanitize($conn, $_GET['order'] ?? '');
if (!$orderNumber) { redirect('../plants.php'); }

$order = $conn->query("
    SELECT o.*, u.full_name, u.email
    FROM orders o JOIN users u ON o.user_id=u.id
    WHERE o.order_number='$orderNumber' AND o.user_id={$_SESSION['user_id']}
")->fetch_assoc();

if (!$order) { redirect('../plants.php'); }

$items = $conn->query("SELECT * FROM order_items WHERE order_id={$order['id']}");
?>

<div class="section" style="max-width:600px; margin:60px auto; text-align:center;">
    <div style="background:white; border-radius:var(--radius-lg); padding:48px; box-shadow:var(--shadow-md);">
        <div style="width:80px; height:80px; background:var(--green-pale); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:36px;">
            ✅
        </div>
        <h1 style="font-size:1.8rem; margin-bottom:8px;">Order Confirmed!</h1>
        <p style="color:var(--text-light); margin-bottom:28px;">
            Thank you, <?= htmlspecialchars(explode(' ',$order['full_name'])[0]) ?>! Your order has been placed successfully.
        </p>

        <div style="background:var(--green-pale); border-radius:12px; padding:16px; margin-bottom:24px;">
            <div style="font-size:12px; font-weight:600; color:var(--green-mid); text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px;">Order Number</div>
            <div style="font-size:1.4rem; font-weight:700; color:var(--green-dark); font-family:'Playfair Display',serif;"><?= htmlspecialchars($order['order_number']) ?></div>
        </div>

        <!-- Order summary -->
        <div style="text-align:left; margin-bottom:24px;">
            <?php while ($item = $items->fetch_assoc()): ?>
            <div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--cream-dark); font-size:14px;">
                <span><?= htmlspecialchars($item['item_name']) ?> × <?= $item['quantity'] ?></span>
                <span style="font-weight:600;"><?= formatPrice($item['subtotal']) ?></span>
            </div>
            <?php endwhile; ?>
            <div style="display:flex; justify-content:space-between; padding:12px 0; font-size:16px; font-weight:700; color:var(--green-dark);">
                <span>Total Paid</span>
                <span><?= formatPrice($order['total_amount']) ?></span>
            </div>
        </div>

        <div style="background:var(--cream); border-radius:10px; padding:14px; margin-bottom:24px; font-size:13px; color:var(--text-mid); text-align:left;">
            <div style="margin-bottom:4px;"><strong>📦 Delivery to:</strong> <?= htmlspecialchars($order['delivery_address']) ?></div>
            <div><strong>💳 Payment:</strong> <?= str_replace('_',' ',ucfirst($order['payment_method'])) ?></div>
        </div>

        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/customer/orders.php" class="btn-primary">
                <i class="fas fa-box"></i> View My Orders
            </a>
            <a href="<?= SITE_URL ?>/plants.php" class="btn-secondary" style="border-color:var(--green-mid); color:var(--green-mid);">
                <i class="fas fa-seedling"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
