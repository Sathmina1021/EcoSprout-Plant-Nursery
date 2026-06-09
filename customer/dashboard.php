<?php
$pageTitle = 'My Dashboard';
require_once '../includes/header.php';

if (!isLoggedIn() || !isCustomer()) {
    flashMessage('error', 'Please login as a customer to access this page.');
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$user   = $conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();

// Stats
$totalOrders      = $conn->query("SELECT COUNT(*) c FROM orders WHERE user_id=$userId")->fetch_assoc()['c'];
$pendingOrders    = $conn->query("SELECT COUNT(*) c FROM orders WHERE user_id=$userId AND status IN ('pending','confirmed','processing')")->fetch_assoc()['c'];
$totalSpent       = $conn->query("SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE user_id=$userId AND payment_status='paid'")->fetch_assoc()['c'];
$workshopsJoined  = $conn->query("SELECT COUNT(*) c FROM workshop_registrations WHERE user_id=$userId")->fetch_assoc()['c'];

// Recent orders
$recentOrders = $conn->query("SELECT * FROM orders WHERE user_id=$userId ORDER BY created_at DESC LIMIT 5");

// Upcoming workshops
$upcomingWorkshops = $conn->query("
    SELECT w.*, wr.payment_status as reg_status
    FROM workshop_registrations wr
    JOIN workshops w ON wr.workshop_id = w.id
    WHERE wr.user_id = $userId AND w.workshop_date >= CURDATE()
    ORDER BY w.workshop_date ASC LIMIT 3
");
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-avatar">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=2d6a4f&color=fff&size=80" alt="Avatar">
            <h4><?= htmlspecialchars($user['full_name']) ?></h4>
            <p style="color:var(--green-mid);">🌿 Plant Enthusiast</p>
        </div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="orders.php"><i class="fas fa-box"></i> My Orders</a></li>
            <li><a href="workshops.php"><i class="fas fa-calendar-alt"></i> My Workshops</a></li>
            <li><a href="queries.php"><i class="fas fa-question-circle"></i> My Queries</a></li>
            <li><a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a></li>
            <li><a href="<?= SITE_URL ?>/plants.php"><i class="fas fa-seedling"></i> Shop Plants</a></li>
            <li><a href="<?= SITE_URL ?>/logout.php" style="color:var(--error);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main -->
    <div class="dashboard-main">
        <div style="margin-bottom:24px;">
            <h2 style="font-size:1.6rem;">Welcome back, <?= htmlspecialchars(explode(' ',$user['full_name'])[0]) ?>! 👋</h2>
            <p style="color:var(--text-light); font-size:14px;">Here's what's happening with your account.</p>
        </div>

        <!-- Stats -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= $totalOrders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?= $pendingOrders ?></div>
                <div class="stat-label">Active Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?= number_format($totalSpent, 0) ?></div>
                <div class="stat-label">Total Spent (Rs.)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🌱</div>
                <div class="stat-value"><?= $workshopsJoined ?></div>
                <div class="stat-label">Workshops Joined</div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm); margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:1.1rem;">Recent Orders</h3>
                <a href="orders.php" style="font-size:13px; color:var(--green-mid);">View all →</a>
            </div>

            <?php if ($recentOrders->num_rows === 0): ?>
            <div class="empty-state" style="padding:30px 0;">
                <i class="fas fa-box-open"></i>
                <p>No orders yet. <a href="<?= SITE_URL ?>/plants.php" style="color:var(--green-mid);">Start shopping!</a></p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recentOrders->fetch_assoc()):
                        $badgeClass = match($order['status']) {
                            'delivered' => 'badge-success',
                            'cancelled' => 'badge-error',
                            'pending'   => 'badge-warning',
                            default     => 'badge-info'
                        };
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        <td><?= formatPrice($order['total_amount']) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($order['status']) ?></span></td>
                        <td>
                            <a href="order_detail.php?id=<?= $order['id'] ?>" style="font-size:12px; color:var(--green-mid);">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Upcoming Workshops -->
        <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:1.1rem;">Upcoming Workshops</h3>
                <a href="workshops.php" style="font-size:13px; color:var(--green-mid);">View all →</a>
            </div>

            <?php if ($upcomingWorkshops->num_rows === 0): ?>
            <div class="empty-state" style="padding:30px 0;">
                <i class="fas fa-calendar"></i>
                <p>No upcoming workshops. <a href="<?= SITE_URL ?>/workshops.php" style="color:var(--green-mid);">Browse workshops!</a></p>
            </div>
            <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php while ($w = $upcomingWorkshops->fetch_assoc()): ?>
                <div style="display:flex; align-items:center; justify-content:space-between; padding:14px; background:var(--cream); border-radius:10px;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <div style="width:46px; height:46px; background:var(--green-pale); border-radius:10px; display:flex; flex-direction:column; align-items:center; justify-content:center; flex-shrink:0;">
                            <span style="font-size:11px; font-weight:700; color:var(--green-mid);"><?= date('M', strtotime($w['workshop_date'])) ?></span>
                            <span style="font-size:16px; font-weight:700; color:var(--green-dark); line-height:1;"><?= date('d', strtotime($w['workshop_date'])) ?></span>
                        </div>
                        <div>
                            <div style="font-weight:600; font-size:14px;"><?= htmlspecialchars($w['title']) ?></div>
                            <div style="font-size:12px; color:var(--text-light);"><?= date('g:i A', strtotime($w['start_time'])) ?> · <?= htmlspecialchars($w['location']) ?></div>
                        </div>
                    </div>
                    <span class="badge <?= $w['reg_status']==='paid'?'badge-success':'badge-warning' ?>">
                        <?= ucfirst($w['reg_status']) ?>
                    </span>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
