<?php
$pageTitle = 'Staff Dashboard';
require_once '../includes/config.php';

if (!isStaff()) {
    flashMessage('error', 'Please login as staff or admin.');
    redirect('login.php');
}

$pendingOrders = $conn->query("SELECT COUNT(*) c FROM orders WHERE status IN ('pending','confirmed','processing')")->fetch_assoc()['c'];
$openQueries   = $conn->query("SELECT COUNT(*) c FROM queries WHERE status IN ('new','in_progress')")->fetch_assoc()['c'];
$lowStock      = $conn->query("SELECT COUNT(*) c FROM plants WHERE is_active=1 AND stock_quantity <= 5")->fetch_assoc()['c'];
$upcomingWorkshops = $conn->query("SELECT COUNT(*) c FROM workshops WHERE is_active=1 AND workshop_date >= CURDATE()")->fetch_assoc()['c'];
$recentOrders = $conn->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | EcoSprout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    <style>
        body{margin:0}.admin-wrap{display:flex;min-height:100vh}.adm-sidebar{width:220px;background:var(--green-dark);position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto}.adm-sidebar .logo{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,.1)}.adm-sidebar .logo .logo-text{color:white;font-size:16px}.adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:rgba(255,255,255,.7)}.adm-nav a:hover,.adm-nav a.active{background:rgba(255,255,255,.1);color:white}.adm-nav a i{width:16px}.adm-main{margin-left:220px;flex:1;background:var(--cream);min-height:100vh}.adm-topbar{background:white;padding:14px 24px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between}.adm-content{padding:24px}.staff-card{background:white;border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm);border-top:4px solid var(--green-light)}
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
        <div class="logo"><div style="display:flex;align-items:center;gap:8px;"><span>🌿</span><span class="logo-text">Eco<strong>Sprout</strong></span></div><div style="font-size:11px;color:rgba(255,255,255,.55);margin-top:4px;">Staff Panel</div></div>
        <nav class="adm-nav" style="padding:10px 0;">
            <a href="<?= SITE_URL ?>/staff/dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <?php if (isAdmin()): ?><a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-shield-alt"></i> Admin Dashboard</a><?php endif; ?>
            <a href="<?= SITE_URL ?>/index.php"><i class="fas fa-globe"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem;">👋 Staff Dashboard</h2>
            <span style="font-size:13px;color:var(--text-mid);">Logged in as <?= htmlspecialchars($_SESSION['name']) ?></span>
        </div>
        <div class="adm-content">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
                <div class="staff-card"><div style="font-size:26px;">📦</div><h3><?= $pendingOrders ?></h3><p style="color:var(--text-light);font-size:13px;">Active Orders</p></div>
                <div class="staff-card"><div style="font-size:26px;">💬</div><h3><?= $openQueries ?></h3><p style="color:var(--text-light);font-size:13px;">Open Queries</p></div>
                <div class="staff-card"><div style="font-size:26px;">🌿</div><h3><?= $lowStock ?></h3><p style="color:var(--text-light);font-size:13px;">Low Stock Plants</p></div>
                <div class="staff-card"><div style="font-size:26px;">📅</div><h3><?= $upcomingWorkshops ?></h3><p style="color:var(--text-light);font-size:13px;">Upcoming Workshops</p></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
                <div style="background:white;border-radius:var(--radius-lg);padding:22px;box-shadow:var(--shadow-sm);">
                    <h3 style="font-size:1rem;margin-bottom:14px;">Recent Orders</h3>
                    <?php if ($recentOrders->num_rows === 0): ?>
                        <p style="color:var(--text-light);font-size:13px;">No orders yet.</p>
                    <?php else: ?>
                    <table class="data-table">
                        <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php while ($o = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td><a href="<?= SITE_URL ?>/admin/orders.php?view=<?= $o['id'] ?>" style="color:var(--green-mid);font-weight:600;"><?= htmlspecialchars($o['order_number']) ?></a></td>
                                <td><?= htmlspecialchars($o['full_name']) ?></td>
                                <td><?= formatPrice($o['total_amount']) ?></td>
                                <td><span class="badge badge-info"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <div style="background:white;border-radius:var(--radius-lg);padding:22px;box-shadow:var(--shadow-sm);">
                    <h3 style="font-size:1rem;margin-bottom:14px;">Quick Actions</h3>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <a href="<?= SITE_URL ?>/admin/plants.php?action=add" class="btn-primary" style="justify-content:center;"><i class="fas fa-plus"></i> Add Plant</a>
                        <a href="<?= SITE_URL ?>/admin/orders.php" class="btn-view" style="justify-content:center;"><i class="fas fa-box"></i> Manage Orders</a>
                        <a href="<?= SITE_URL ?>/admin/workshops.php?action=add" class="btn-view" style="justify-content:center;"><i class="fas fa-calendar-plus"></i> Add Workshop</a>
                        <a href="<?= SITE_URL ?>/admin/queries.php" class="btn-view" style="justify-content:center;"><i class="fas fa-reply"></i> Reply Queries</a>
                        <a href="<?= SITE_URL ?>/admin/blog.php" class="btn-view" style="justify-content:center;"><i class="fas fa-blog"></i> Manage Blog</a>
                        <a href="<?= SITE_URL ?>/admin/blog.php?action=add" class="btn-view" style="justify-content:center;"><i class="fas fa-pen"></i> Write New Post</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>window.ECOSPROUT_SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
