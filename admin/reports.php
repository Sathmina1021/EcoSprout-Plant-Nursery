<?php
$pageTitle = 'Reports & Analytics';
require_once '../includes/config.php';
if (!isAdmin()) { flashMessage('error','Admins only.'); redirect('login.php'); }

// Date range filter
$dateFrom = sanitize($conn, $_GET['from'] ?? date('Y-m-01'));
$dateTo   = sanitize($conn, $_GET['to']   ?? date('Y-m-d'));

// Overall stats
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(total_amount),0) c FROM orders WHERE payment_status='paid' AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['c'];
$totalOrders    = $conn->query("SELECT COUNT(*) c FROM orders WHERE DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['c'];
$newCustomers   = $conn->query("SELECT COUNT(*) c FROM users WHERE role='customer' AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['c'];
$avgOrderValue  = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

// Monthly revenue (last 6 months)
$monthlyRevenue = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') as month,
           MONTH(created_at) as m, YEAR(created_at) as y,
           SUM(total_amount) as revenue, COUNT(*) as orders
    FROM orders 
    WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY y ASC, m ASC
");
$months = []; $revenues = []; $orderCounts = [];
while ($r = $monthlyRevenue->fetch_assoc()) {
    $months[]      = $r['month'];
    $revenues[]    = round(floatval($r['revenue']));
    $orderCounts[] = intval($r['orders']);
}

// Top selling plants
$topPlants = $conn->query("
    SELECT oi.item_name, SUM(oi.quantity) as total_sold, SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.item_type='plant' AND DATE(o.created_at) BETWEEN '$dateFrom' AND '$dateTo'
    GROUP BY oi.item_name
    ORDER BY total_sold DESC LIMIT 8
");

// Orders by status
$ordersByStatus = $conn->query("
    SELECT status, COUNT(*) as count FROM orders
    WHERE DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'
    GROUP BY status
");
$statusData = [];
while ($r = $ordersByStatus->fetch_assoc()) $statusData[$r['status']] = $r['count'];

// Workshop registrations
$workshopStats = $conn->query("
    SELECT w.title, COUNT(wr.id) as registrations, w.max_participants
    FROM workshops w
    LEFT JOIN workshop_registrations wr ON w.id=wr.workshop_id
    GROUP BY w.id ORDER BY registrations DESC LIMIT 5
");

// Recent orders in range
$recentOrders = $conn->query("
    SELECT o.*, u.full_name
    FROM orders o JOIN users u ON o.user_id=u.id
    WHERE DATE(o.created_at) BETWEEN '$dateFrom' AND '$dateTo'
    ORDER BY o.created_at DESC LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | EcoSprout Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        body{margin:0}.admin-wrap{display:flex;min-height:100vh}
        .adm-sidebar{width:220px;background:var(--green-dark);position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto}
        .adm-sidebar .logo{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
        .adm-sidebar .logo .logo-text{color:white;font-size:16px}
        .adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:rgba(255,255,255,.7)}
        .adm-nav a:hover,.adm-nav a.active{background:rgba(255,255,255,.1);color:white}
        .adm-nav a i{width:16px}
        .adm-main{margin-left:220px;flex:1;background:var(--cream)}
        .adm-topbar{background:white;padding:14px 24px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between;}
        .adm-content{padding:24px}
        .report-stat{background:white;border-radius:var(--radius);padding:20px;box-shadow:var(--shadow-sm);}
        .chart-box{background:white;border-radius:var(--radius-lg);padding:22px;box-shadow:var(--shadow-sm);}
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
            <a href="<?= SITE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <a href="<?= SITE_URL ?>/admin/reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem;">📊 Reports & Analytics</h2>
            <!-- Date Range Filter -->
            <form method="GET" style="display:flex;align-items:center;gap:8px;">
                <label style="font-size:13px;color:var(--text-mid);">From:</label>
                <input type="date" name="from" value="<?= $dateFrom ?>" class="form-control" style="padding:6px 10px;width:auto;">
                <label style="font-size:13px;color:var(--text-mid);">To:</label>
                <input type="date" name="to" value="<?= $dateTo ?>" class="form-control" style="padding:6px 10px;width:auto;">
                <button type="submit" class="btn-primary" style="padding:8px 14px;font-size:13px;">Apply</button>
                <button type="button" onclick="window.print()" class="btn-view" style="padding:8px 14px;font-size:13px;">
                    <i class="fas fa-print"></i> Print
                </button>
            </form>
        </div>

        <div class="adm-content">
            <!-- KPI Cards -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
                <div class="report-stat" style="border-top:4px solid var(--green-light);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-light);margin-bottom:6px;">Total Revenue</div>
                    <div style="font-size:1.6rem;font-weight:700;color:var(--green-dark);font-family:'Playfair Display',serif;">Rs. <?= number_format($totalRevenue, 0) ?></div>
                    <div style="font-size:12px;color:var(--text-light);margin-top:4px;">Paid orders only</div>
                </div>
                <div class="report-stat" style="border-top:4px solid var(--gold);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-light);margin-bottom:6px;">Total Orders</div>
                    <div style="font-size:1.6rem;font-weight:700;color:var(--green-dark);font-family:'Playfair Display',serif;"><?= number_format($totalOrders) ?></div>
                    <div style="font-size:12px;color:var(--text-light);margin-top:4px;">All statuses</div>
                </div>
                <div class="report-stat" style="border-top:4px solid #1565c0;">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-light);margin-bottom:6px;">New Customers</div>
                    <div style="font-size:1.6rem;font-weight:700;color:var(--green-dark);font-family:'Playfair Display',serif;"><?= number_format($newCustomers) ?></div>
                    <div style="font-size:12px;color:var(--text-light);margin-top:4px;">Registered in range</div>
                </div>
                <div class="report-stat" style="border-top:4px solid var(--brown-light);">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-light);margin-bottom:6px;">Avg. Order Value</div>
                    <div style="font-size:1.6rem;font-weight:700;color:var(--green-dark);font-family:'Playfair Display',serif;">Rs. <?= number_format($avgOrderValue, 0) ?></div>
                    <div style="font-size:12px;color:var(--text-light);margin-top:4px;">Per order average</div>
                </div>
            </div>

            <!-- Charts Row -->
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px;">
                <!-- Revenue Chart -->
                <div class="chart-box">
                    <h3 style="font-size:1rem;margin-bottom:16px;">Monthly Revenue (Last 6 Months)</h3>
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
                <!-- Order Status Donut -->
                <div class="chart-box">
                    <h3 style="font-size:1rem;margin-bottom:16px;">Orders by Status</h3>
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>

            <!-- Bottom Row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                <!-- Top Plants -->
                <div class="chart-box">
                    <h3 style="font-size:1rem;margin-bottom:16px;">Top Selling Plants</h3>
                    <?php if ($topPlants->num_rows === 0): ?>
                        <p style="color:var(--text-light);font-size:13px;text-align:center;padding:20px 0;">No sales data in this period.</p>
                    <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th style="text-align:left;font-size:11px;color:var(--text-light);padding:0 0 8px;font-weight:600;text-transform:uppercase;">Plant</th>
                                <th style="text-align:right;font-size:11px;color:var(--text-light);padding:0 0 8px;font-weight:600;text-transform:uppercase;">Sold</th>
                                <th style="text-align:right;font-size:11px;color:var(--text-light);padding:0 0 8px;font-weight:600;text-transform:uppercase;">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = $topPlants->fetch_assoc()): ?>
                            <tr>
                                <td style="padding:8px 0;font-size:13px;border-bottom:1px solid var(--cream-dark);"><?= htmlspecialchars($p['item_name']) ?></td>
                                <td style="padding:8px 0;font-size:13px;font-weight:600;text-align:right;border-bottom:1px solid var(--cream-dark);"><?= $p['total_sold'] ?></td>
                                <td style="padding:8px 0;font-size:13px;font-weight:600;text-align:right;border-bottom:1px solid var(--cream-dark);color:var(--green-mid);"><?= formatPrice($p['total_revenue']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Workshop Stats -->
                <div class="chart-box">
                    <h3 style="font-size:1rem;margin-bottom:16px;">Workshop Registrations</h3>
                    <?php if ($workshopStats->num_rows === 0): ?>
                        <p style="color:var(--text-light);font-size:13px;text-align:center;padding:20px 0;">No workshop data available.</p>
                    <?php else: ?>
                    <?php while ($w = $workshopStats->fetch_assoc()):
                        $pct = $w['max_participants'] > 0 ? ($w['registrations'] / $w['max_participants']) * 100 : 0;
                    ?>
                    <div style="margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                            <span style="font-weight:500;"><?= htmlspecialchars(substr($w['title'],0,30)) ?>...</span>
                            <span style="color:var(--text-light);"><?= $w['registrations'] ?>/<?= $w['max_participants'] ?></span>
                        </div>
                        <div style="background:var(--cream-dark);border-radius:4px;height:8px;overflow:hidden;">
                            <div style="height:100%;border-radius:4px;background:<?= $pct >= 90 ? 'var(--error)' : 'var(--green-light)' ?>;width:<?= min(100, $pct) ?>%;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="chart-box">
                <h3 style="font-size:1rem;margin-bottom:16px;">Orders in Selected Period</h3>
                <?php if ($recentOrders->num_rows === 0): ?>
                    <p style="color:var(--text-light);font-size:13px;text-align:center;padding:20px 0;">No orders in this period.</p>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($o = $recentOrders->fetch_assoc()):
                            $bc = match($o['status']){'delivered'=>'badge-success','cancelled'=>'badge-error','pending'=>'badge-warning',default=>'badge-info'};
                            $pc = $o['payment_status']==='paid'?'badge-success':'badge-warning';
                        ?>
                        <tr>
                            <td><strong style="color:var(--green-mid);font-size:12px;"><?= htmlspecialchars($o['order_number']) ?></strong></td>
                            <td style="font-size:13px;"><?= htmlspecialchars($o['full_name']) ?></td>
                            <td style="font-weight:700;font-size:13px;"><?= formatPrice($o['total_amount']) ?></td>
                            <td><span class="badge <?= $pc ?>" style="font-size:10px;"><?= ucfirst($o['payment_status']) ?></span></td>
                            <td><span class="badge <?= $bc ?>" style="font-size:10px;"><?= ucfirst($o['status']) ?></span></td>
                            <td style="font-size:12px;color:var(--text-light);"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const revCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Revenue (Rs.)',
                data: <?= json_encode($revenues) ?>,
                backgroundColor: 'rgba(82,183,136,0.7)',
                borderColor: '#2d6a4f',
                borderWidth: 2,
                borderRadius: 6,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: <?= json_encode($orderCounts) ?>,
                type: 'line',
                borderColor: '#d4a843',
                backgroundColor: 'rgba(212,168,67,.15)',
                borderWidth: 2,
                pointRadius: 4,
                fill: true,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top' } },
        scales: {
            y:  { beginAtZero: true, position: 'left',  title: { display: true, text: 'Revenue (Rs.)' } },
            y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Orders' }, grid: { drawOnChartArea: false } }
        }
    }
});

// Status Donut
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = <?= json_encode($statusData) ?>;
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: ['#ffa726','#29b6f6','#42a5f5','#26a69a','#66bb6a','#ef5350'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right', labels: { font: { size: 12 } } }
        },
        cutout: '65%'
    }
});
</script>
</body>
</html>
