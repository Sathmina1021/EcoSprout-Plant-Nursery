<?php
$pageTitle = 'Order Details';
require_once '../includes/header.php';

if (!isLoggedIn()) { redirect('login.php'); }
$userId = intval($_SESSION['user_id']);
$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) { redirect('customer/orders.php'); }

$order = $conn->query("SELECT * FROM orders WHERE id=$orderId AND user_id=$userId")->fetch_assoc();
if (!$order) {
    flashMessage('error', 'Order not found.');
    redirect('customer/orders.php');
}
$items = $conn->query("SELECT * FROM order_items WHERE order_id=$orderId");
$statusBadge = match($order['status']) {
    'delivered' => 'badge-success',
    'cancelled' => 'badge-error',
    'pending' => 'badge-warning',
    default => 'badge-info'
};
$payBadge = $order['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning';
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>Order <?= htmlspecialchars($order['order_number']) ?></h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/customer/orders.php">My Orders</a><span>/</span>
        <span>Details</span>
    </div>
</div>

<div class="section" style="max-width:980px; margin:0 auto;">
    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:24px; align-items:start;">
        <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm);">
            <h3 style="font-size:1.1rem; margin-bottom:16px;">Items</h3>
            <table class="data-table">
                <thead>
                    <tr><th>Item</th><th>Type</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= ucfirst($item['item_type']) ?></td>
                        <td><?= intval($item['quantity']) ?></td>
                        <td><?= formatPrice($item['unit_price']) ?></td>
                        <td><strong><?= formatPrice($item['subtotal']) ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm);">
            <h3 style="font-size:1.1rem; margin-bottom:16px;">Order Summary</h3>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;"><span>Status</span><span class="badge <?= $statusBadge ?>"><?= ucfirst($order['status']) ?></span></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;"><span>Payment</span><span class="badge <?= $payBadge ?>"><?= ucfirst($order['payment_status']) ?></span></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;"><span>Method</span><strong><?= str_replace('_', ' ', ucfirst($order['payment_method'])) ?></strong></div>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;"><span>Date</span><strong><?= date('d M Y, g:i A', strtotime($order['created_at'])) ?></strong></div>
            <hr style="border:none; border-top:1px solid var(--cream-dark); margin:16px 0;">
            <div style="display:flex; justify-content:space-between; font-size:18px; color:var(--green-dark); font-weight:700;"><span>Total</span><span><?= formatPrice($order['total_amount']) ?></span></div>
            <div style="margin-top:18px; padding:14px; background:var(--cream); border-radius:10px; font-size:13px; color:var(--text-mid);">
                <strong>Delivery Address</strong><br>
                <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
            </div>
            <?php if ($order['notes']): ?>
            <div style="margin-top:12px; padding:14px; background:var(--green-pale); border-radius:10px; font-size:13px;">
                <strong>Notes</strong><br><?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/customer/orders.php" class="btn-primary" style="margin-top:18px; width:100%; justify-content:center;">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
