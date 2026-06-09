<?php
$pageTitle = 'My Orders';
require_once '../includes/header.php';

if (!isLoggedIn()) { redirect('../login.php'); }
$userId = $_SESSION['user_id'];

$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$total     = $conn->query("SELECT COUNT(*) c FROM orders WHERE user_id=$userId")->fetch_assoc()['c'];
$totalPgs  = ceil($total / $perPage);
$orders    = $conn->query("SELECT * FROM orders WHERE user_id=$userId ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>My Orders</h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="dashboard.php">Dashboard</a><span>/</span>
        <span>Orders</span>
    </div>
</div>

<div class="section">
    <?php if ($total === 0): ?>
    <div class="empty-state">
        <i class="fas fa-box-open"></i>
        <h3>No orders yet</h3>
        <p>You haven't placed any orders. Start exploring our plant catalogue!</p>
        <a href="<?= SITE_URL ?>/plants.php" class="btn-primary"><i class="fas fa-seedling"></i> Browse Plants</a>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = $orders->fetch_assoc()):
                $itemCount = $conn->query("SELECT SUM(quantity) c FROM order_items WHERE order_id={$order['id']}")->fetch_assoc()['c'];
                $statusBadge = match($order['status']) {
                    'delivered'  => 'badge-success',
                    'cancelled'  => 'badge-error',
                    'shipped'    => 'badge-info',
                    'processing' => 'badge-info',
                    'confirmed'  => 'badge-info',
                    default      => 'badge-warning'
                };
                $payBadge = $order['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning';
            ?>
            <tr>
                <td><strong style="color:var(--green-mid);"><?= htmlspecialchars($order['order_number']) ?></strong></td>
                <td><?= date('d M Y, g:i A', strtotime($order['created_at'])) ?></td>
                <td><?= $itemCount ?> item<?= $itemCount!=1?'s':'' ?></td>
                <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                <td><span class="badge <?= $payBadge ?>"><?= ucfirst($order['payment_status']) ?></span></td>
                <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($order['status']) ?></span></td>
                <td>
                    <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-view" style="display:inline-flex; font-size:12px; padding:6px 12px;">
                        View Details
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($totalPgs > 1): ?>
    <div class="pagination" style="margin-top:24px; justify-content:center;">
        <?php for ($p = 1; $p <= $totalPgs; $p++): ?>
        <a href="?page=<?= $p ?>" class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
