<?php
$pageTitle = 'Manage Orders';
require_once '../includes/config.php';
if (!isStaff()) { redirect('login.php'); }

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $status  = sanitize($conn, $_POST['status']);
    $payStatus = sanitize($conn, $_POST['payment_status'] ?? 'pending');
    $validStatus = ['pending','confirmed','processing','shipped','delivered','cancelled'];
    if (in_array($status, $validStatus)) {
        $conn->query("UPDATE orders SET status='$status', payment_status='$payStatus' WHERE id=$orderId");
        flashMessage('success', 'Order status updated!');
    }
    header('Location: orders.php'); exit();
}

$filter = sanitize($conn, $_GET['filter'] ?? 'all');
$view   = intval($_GET['view'] ?? 0);
$where  = $filter !== 'all' ? "WHERE o.status='$filter'" : "";

$orders = $conn->query("
    SELECT o.*, u.full_name, u.email, u.phone
    FROM orders o JOIN users u ON o.user_id=u.id
    $where ORDER BY o.created_at DESC
");

$viewOrder     = null;
$viewOrderItems = null;
if ($view) {
    $viewOrder      = $conn->query("SELECT o.*, u.full_name, u.email, u.phone FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=$view")->fetch_assoc();
    $viewOrderItems = $conn->query("SELECT * FROM order_items WHERE order_id=$view");
}

$statusCounts = [];
foreach(['all','pending','confirmed','processing','shipped','delivered','cancelled'] as $s) {
    $w = $s === 'all' ? '' : "WHERE status='$s'";
    $statusCounts[$s] = $conn->query("SELECT COUNT(*) c FROM orders $w")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | EcoSprout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    <style>
        body{margin:0}.admin-wrap{display:flex;min-height:100vh}
        .adm-sidebar{width:220px;background:var(--green-dark);position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto}
        .adm-sidebar .logo{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
        .adm-sidebar .logo .logo-text{color:white;font-size:16px}
        .adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:rgba(255,255,255,.7)}
        .adm-nav a:hover,.adm-nav a.active{background:rgba(255,255,255,.1);color:white}
        .adm-nav a i{width:16px}
        .adm-main{margin-left:220px;flex:1;background:var(--cream)}
        .adm-topbar{background:white;padding:14px 24px;border-bottom:1px solid var(--cream-dark)}
        .adm-content{padding:24px}
    </style>
</head>
<body>
<?php $flash = getFlash(); if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg" style="z-index:9999;">
    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['message']) ?>
    <button onclick="document.getElementById('flashMsg').remove()" class="flash-close">&times;</button>
</div>
<?php endif; ?>

<div class="admin-wrap">
    <aside class="adm-sidebar">
        <div class="logo"><div style="display:flex;align-items:center;gap:8px;"><span>🌿</span><span class="logo-text">Eco<strong>Sprout</strong></span></div></div>
        <nav class="adm-nav" style="padding:10px 0;">
            <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php" class="active"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
            <a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar"><h2 style="margin:0;font-size:1.1rem;">📦 Order Management</h2></div>
        <div class="adm-content">

            <!-- Filter Tabs -->
            <div class="filter-tabs" style="margin-bottom:20px;">
                <?php foreach(['all'=>'All','pending'=>'⏳ Pending','confirmed'=>'✅ Confirmed','processing'=>'🔄 Processing','shipped'=>'🚚 Shipped','delivered'=>'📬 Delivered','cancelled'=>'❌ Cancelled'] as $f=>$l): ?>
                <a href="?filter=<?= $f ?>" class="filter-tab <?= $filter===$f?'active':'' ?>">
                    <?= $l ?> <span style="font-size:11px;opacity:.7;">(<?= $statusCounts[$f] ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>

            <div style="display:grid; grid-template-columns:1fr <?= $viewOrder?'400px':'' ?>; gap:20px;">
                <!-- Orders Table -->
                <div style="background:white; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm);">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders->num_rows === 0): ?>
                            <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No orders found.</td></tr>
                            <?php endif; ?>
                            <?php while ($o = $orders->fetch_assoc()):
                                $bc = match($o['status']){
                                    'delivered'=>'badge-success','cancelled'=>'badge-error',
                                    'pending'=>'badge-warning',default=>'badge-info'
                                };
                                $pc = $o['payment_status']==='paid'?'badge-success':'badge-warning';
                            ?>
                            <tr style="<?= $view===$o['id']?'background:rgba(183,228,199,.2);':'' ?>">
                                <td><strong style="color:var(--green-mid);font-size:12px;"><?= htmlspecialchars($o['order_number']) ?></strong></td>
                                <td>
                                    <div style="font-size:13px;font-weight:500;"><?= htmlspecialchars(substr($o['full_name'],0,20)) ?></div>
                                    <div style="font-size:11px;color:var(--text-light);"><?= htmlspecialchars($o['email']) ?></div>
                                </td>
                                <td style="font-weight:700;font-size:13px;"><?= formatPrice($o['total_amount']) ?></td>
                                <td><span class="badge <?= $pc ?>" style="font-size:10px;"><?= ucfirst($o['payment_status']) ?></span></td>
                                <td><span class="badge <?= $bc ?>" style="font-size:10px;"><?= ucfirst($o['status']) ?></span></td>
                                <td style="font-size:12px;color:var(--text-light);"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                <td>
                                    <a href="?filter=<?= $filter ?>&view=<?= $o['id'] ?>" class="btn-view" style="padding:5px 10px;font-size:12px;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Order Detail Panel -->
                <?php if ($viewOrder): ?>
                <div>
                    <div style="background:white; border-radius:var(--radius-lg); padding:20px; box-shadow:var(--shadow-sm); margin-bottom:14px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:14px;">
                            <h3 style="font-size:1rem;"><?= htmlspecialchars($viewOrder['order_number']) ?></h3>
                            <span class="badge <?= match($viewOrder['status']){'delivered'=>'badge-success','cancelled'=>'badge-error','pending'=>'badge-warning',default=>'badge-info'} ?>">
                                <?= ucfirst($viewOrder['status']) ?>
                            </span>
                        </div>
                        <div style="font-size:13px; margin-bottom:6px;"><strong>Customer:</strong> <?= htmlspecialchars($viewOrder['full_name']) ?></div>
                        <div style="font-size:13px; margin-bottom:6px;"><strong>Email:</strong> <?= htmlspecialchars($viewOrder['email']) ?></div>
                        <div style="font-size:13px; margin-bottom:6px;"><strong>Phone:</strong> <?= htmlspecialchars($viewOrder['phone'] ?? 'N/A') ?></div>
                        <div style="font-size:13px; margin-bottom:6px;"><strong>Address:</strong> <?= htmlspecialchars($viewOrder['delivery_address']) ?></div>
                        <div style="font-size:13px; margin-bottom:6px;"><strong>Payment:</strong> <?= str_replace('_',' ',ucfirst($viewOrder['payment_method'])) ?></div>
                        <div style="font-size:13px;"><strong>Ordered:</strong> <?= date('d M Y, g:i A', strtotime($viewOrder['created_at'])) ?></div>

                        <?php if ($viewOrder['notes']): ?>
                        <div style="margin-top:10px; padding:10px; background:var(--cream); border-radius:8px; font-size:13px;">
                            <strong>Notes:</strong> <?= htmlspecialchars($viewOrder['notes']) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Items -->
                        <div style="margin-top:14px; border-top:1px solid var(--cream-dark); padding-top:14px;">
                            <div style="font-size:12px; font-weight:600; color:var(--text-light); margin-bottom:8px;">ORDER ITEMS</div>
                            <?php while ($item = $viewOrderItems->fetch_assoc()): ?>
                            <div style="display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-bottom:1px solid var(--cream-dark);">
                                <span><?= htmlspecialchars($item['item_name']) ?> × <?= $item['quantity'] ?></span>
                                <span style="font-weight:600;"><?= formatPrice($item['subtotal']) ?></span>
                            </div>
                            <?php endwhile; ?>
                            <div style="display:flex; justify-content:space-between; font-size:14px; font-weight:700; padding-top:8px;">
                                <span>Total</span>
                                <span style="color:var(--green-dark);"><?= formatPrice($viewOrder['total_amount']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Update Status -->
                    <div style="background:white; border-radius:var(--radius-lg); padding:18px; box-shadow:var(--shadow-sm);">
                        <h4 style="font-size:14px; margin-bottom:12px;">Update Order Status</h4>
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                            <div class="form-group">
                                <label style="font-size:12px;">Order Status</label>
                                <select name="status" class="form-control">
                                    <?php foreach(['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $viewOrder['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-size:12px;">Payment Status</label>
                                <select name="payment_status" class="form-control">
                                    <?php foreach(['pending','paid','refunded'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $viewOrder['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn-primary" style="width:100%; padding:10px; justify-content:center;">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
