<?php
$pageTitle = 'Admin Dashboard';
require_once '../includes/config.php';

if (!isAdmin()) {
    flashMessage('error', 'Access denied. Admin privileges required.');
    redirect('login.php');
}

// Stats
$totalUsers      = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$totalCustomers  = $conn->query("SELECT COUNT(*) c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$totalStaff      = $conn->query("SELECT COUNT(*) c FROM users WHERE role='staff'")->fetch_assoc()['c'];
$totalOrders     = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$pendingOrders   = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$totalRevenue    = $conn->query("SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE payment_status='paid'")->fetch_assoc()['c'];
$totalPlants     = $conn->query("SELECT COUNT(*) c FROM plants WHERE is_active=1")->fetch_assoc()['c'];
$openQueries     = $conn->query("SELECT COUNT(*) c FROM queries WHERE status IN ('new','in_progress')")->fetch_assoc()['c'];
$totalWorkshops  = $conn->query("SELECT COUNT(*) c FROM workshops WHERE workshop_date >= CURDATE()")->fetch_assoc()['c'];

// Recent orders
$recentOrders = $conn->query("
    SELECT o.*, u.full_name 
    FROM orders o JOIN users u ON o.user_id=u.id 
    ORDER BY o.created_at DESC LIMIT 8
");

// New queries
$newQueries = $conn->query("SELECT * FROM queries WHERE status='new' ORDER BY created_at DESC LIMIT 5");

$adminNav = [
    ['dashboard.php','fas fa-tachometer-alt','Dashboard'],
    ['users.php','fas fa-users','Manage Users'],
    ['plants.php','fas fa-seedling','Manage Plants'],
    ['orders.php','fas fa-box','Orders'],
    ['workshops.php','fas fa-calendar-alt','Workshops'],
    ['queries.php','fas fa-question-circle','Queries'],
    ['services.php','fas fa-tools','Services'],
    ['reports.php','fas fa-chart-bar','Reports'],
    ['blog.php','fas fa-blog','Blog Posts'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | EcoSprout Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    <style>
        .admin-layout { display:flex; min-height:100vh; padding-top:0; }
        .admin-sidebar {
            width:240px; background:var(--green-dark); color:white;
            padding:0; position:fixed; top:0; left:0; bottom:0; z-index:100;
            display:flex; flex-direction:column; overflow-y:auto;
        }
        .admin-logo { padding:20px 20px 16px; border-bottom:1px solid rgba(255,255,255,.1); }
        .admin-logo .logo-text { color:white; font-size:18px; }
        .admin-role { font-size:11px; color:rgba(255,255,255,.5); margin-top:2px; }
        .admin-nav { padding:12px 0; flex:1; }
        .admin-nav a {
            display:flex; align-items:center; gap:10px; padding:11px 20px;
            font-size:13px; color:rgba(255,255,255,.7); transition:var(--transition);
        }
        .admin-nav a:hover, .admin-nav a.active { background:rgba(255,255,255,.1); color:white; }
        .admin-nav a i { width:16px; font-size:14px; }
        .admin-nav .nav-section { padding:12px 20px 4px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:rgba(255,255,255,.3); }
        .admin-main { margin-left:240px; flex:1; background:var(--cream); min-height:100vh; }
        .admin-topbar {
            background:white; padding:16px 28px; border-bottom:1px solid var(--cream-dark);
            display:flex; align-items:center; justify-content:space-between;
            position:sticky; top:0; z-index:50;
        }
        .admin-content { padding:28px; }
        .admin-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:28px; }
        .admin-stat {
            background:white; border-radius:var(--radius); padding:20px;
            box-shadow:var(--shadow-sm); border-left:4px solid var(--green-light);
        }
        .admin-stat.orange { border-color: var(--gold); }
        .admin-stat.red    { border-color: var(--error); }
        .admin-stat.blue   { border-color: #1565c0; }
        .admin-stat .value { font-size:1.6rem; font-weight:700; color:var(--green-dark); font-family:'Playfair Display',serif; }
        .admin-stat .label { font-size:12px; color:var(--text-light); margin-top:2px; }
        .admin-stat .icon  { font-size:22px; margin-bottom:6px; }
    </style>
</head>
<body>

<!-- Flash -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['message']) ?>
    <button onclick="document.getElementById('flashMsg').remove()" class="flash-close">&times;</button>
</div>
<?php endif; ?>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <div class="nav-logo" style="margin:0;">
                <span class="logo-icon">🌿</span>
                <span class="logo-text">Eco<strong>Sprout</strong></span>
            </div>
            <div class="admin-role">⚙️ Administrator Panel</div>
        </div>
        <nav class="admin-nav">
            <div class="nav-section">Main</div>
            <?php foreach ($adminNav as [$href, $icon, $label]):
                $active = basename($_SERVER['PHP_SELF']) === $href;
            ?>
            <a href="<?= SITE_URL ?>/admin/<?= $href ?>" class="<?= $active ? 'active' : '' ?>">
                <i class="<?= $icon ?>"></i> <?= $label ?>
                <?php if ($label === 'Queries' && $openQueries > 0): ?>
                    <span style="margin-left:auto; background:var(--error); color:white; font-size:10px; padding:1px 6px; border-radius:10px;"><?= $openQueries ?></span>
                <?php endif; ?>
                <?php if ($label === 'Orders' && $pendingOrders > 0): ?>
                    <span style="margin-left:auto; background:var(--gold); color:white; font-size:10px; padding:1px 6px; border-radius:10px;"><?= $pendingOrders ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <div class="nav-section">Account</div>
            <a href="<?= SITE_URL ?>/index.php"><i class="fas fa-globe"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="admin-topbar">
            <div>
                <h2 style="font-size:1.2rem; margin:0;">Dashboard Overview</h2>
                <p style="font-size:12px; color:var(--text-light); margin:0;"><?= date('l, d F Y') ?></p>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:13px; color:var(--text-mid);">
                    <i class="fas fa-user-shield" style="color:var(--green-mid);"></i>
                    <?= htmlspecialchars($_SESSION['name']) ?>
                </span>
                <a href="<?= SITE_URL ?>/logout.php" style="font-size:12px; color:var(--error);">Logout</a>
            </div>
        </div>

        <div class="admin-content">
            <!-- Stats Grid -->
            <div class="admin-stats">
                <div class="admin-stat">
                    <div class="icon">👥</div>
                    <div class="value"><?= $totalCustomers ?></div>
                    <div class="label">Customers</div>
                </div>
                <div class="admin-stat orange">
                    <div class="icon">📦</div>
                    <div class="value"><?= $totalOrders ?></div>
                    <div class="label">Total Orders</div>
                </div>
                <div class="admin-stat">
                    <div class="icon">💰</div>
                    <div class="value">Rs. <?= number_format($totalRevenue, 0) ?></div>
                    <div class="label">Revenue</div>
                </div>
                <div class="admin-stat red">
                    <div class="icon">⏳</div>
                    <div class="value"><?= $pendingOrders ?></div>
                    <div class="label">Pending Orders</div>
                </div>
                <div class="admin-stat">
                    <div class="icon">🌿</div>
                    <div class="value"><?= $totalPlants ?></div>
                    <div class="label">Plant Varieties</div>
                </div>
                <div class="admin-stat red">
                    <div class="icon">💬</div>
                    <div class="value"><?= $openQueries ?></div>
                    <div class="label">Open Queries</div>
                </div>
                <div class="admin-stat blue">
                    <div class="icon">🧑‍🏫</div>
                    <div class="value"><?= $totalStaff ?></div>
                    <div class="label">Staff Members</div>
                </div>
                <div class="admin-stat">
                    <div class="icon">📅</div>
                    <div class="value"><?= $totalWorkshops ?></div>
                    <div class="label">Upcoming Workshops</div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1.4fr 1fr; gap:24px;">
                <!-- Recent Orders -->
                <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 style="font-size:1rem;">Recent Orders</h3>
                        <a href="orders.php" style="font-size:12px; color:var(--green-mid);">View all →</a>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php while ($o = $recentOrders->fetch_assoc()):
                                $bc = match($o['status']){
                                    'delivered'=>'badge-success','cancelled'=>'badge-error',
                                    'pending'=>'badge-warning',default=>'badge-info'
                                };
                            ?>
                            <tr>
                                <td style="font-size:12px;font-weight:600;"><?= htmlspecialchars($o['order_number']) ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars(substr($o['full_name'],0,18)) ?></td>
                                <td style="font-size:12px;"><?= formatPrice($o['total_amount']) ?></td>
                                <td><span class="badge <?= $bc ?>" style="font-size:10px;"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- New Queries -->
                <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <h3 style="font-size:1rem;">New Queries</h3>
                        <a href="queries.php" style="font-size:12px; color:var(--green-mid);">View all →</a>
                    </div>
                    <?php if ($newQueries->num_rows === 0): ?>
                        <p style="font-size:13px; color:var(--text-light); text-align:center; padding:20px 0;">No new queries 🎉</p>
                    <?php else: ?>
                        <?php while ($q = $newQueries->fetch_assoc()): ?>
                        <div style="padding:12px 0; border-bottom:1px solid var(--cream-dark);">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div>
                                    <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars(substr($q['subject'],0,35)) ?></div>
                                    <div style="font-size:11px; color:var(--text-light); margin-top:2px;">
                                        <?= htmlspecialchars($q['name']) ?> · <?= date('d M', strtotime($q['created_at'])) ?>
                                    </div>
                                </div>
                                <a href="queries.php?view=<?= $q['id'] ?>" style="font-size:11px; color:var(--green-mid);">Reply</a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm); margin-top:24px;">
                <h3 style="font-size:1rem; margin-bottom:16px;">Quick Actions</h3>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <?php
                    $actions = [
                        ['plants.php?action=add','fas fa-plus','Add New Plant','var(--green-mid)'],
                        ['workshops.php?action=add','fas fa-calendar-plus','Add Workshop','var(--brown-light)'],
                        ['users.php?action=add','fas fa-user-plus','Add Staff','var(--gold)'],
                        ['reports.php','fas fa-chart-bar','View Reports','#1565c0'],
                        ['queries.php','fas fa-envelope','Reply Queries','var(--error)'],
                    ];
                    foreach ($actions as [$href,$icon,$label,$color]):
                    ?>
                    <a href="<?= SITE_URL ?>/admin/<?= $href ?>" 
                       style="display:flex; align-items:center; gap:8px; padding:10px 16px; border-radius:10px; background:var(--cream); font-size:13px; font-weight:600; color:var(--text-dark); transition:var(--transition);"
                       onmouseover="this.style.background='<?= $color ?>'; this.style.color='white'"
                       onmouseout="this.style.background='var(--cream)'; this.style.color='var(--text-dark)'">
                        <i class="<?= $icon ?>"></i> <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
