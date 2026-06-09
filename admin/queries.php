<?php
$pageTitle = 'Manage Queries';
require_once '../includes/config.php';
if (!isStaff()) { redirect('login.php'); }

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond'])) {
    $queryId  = intval($_POST['query_id']);
    $response = sanitize($conn, $_POST['response'] ?? '');
    $status   = sanitize($conn, $_POST['status'] ?? 'resolved');
    $staffId  = $_SESSION['user_id'];

    if (!empty($response)) {
        $conn->query("UPDATE queries SET response='$response', status='$status', responded_by=$staffId, responded_at=NOW() WHERE id=$queryId");
        flashMessage('success', 'Response sent successfully!');
        header('Location: queries.php'); exit();
    }
}

// Handle status update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id     = intval($_GET['id']);
    $status = sanitize($conn, $_GET['status']);
    $validStatuses = ['new','in_progress','resolved','closed'];
    if (in_array($status, $validStatuses)) {
        $conn->query("UPDATE queries SET status='$status' WHERE id=$id");
        flashMessage('success', 'Query status updated.');
        header('Location: queries.php'); exit();
    }
}

$filter = sanitize($conn, $_GET['filter'] ?? 'all');
$view   = intval($_GET['view'] ?? 0);

$where = "1=1";
if ($filter !== 'all') $where .= " AND status='$filter'";

$queries = $conn->query("SELECT q.*, u.full_name as user_name FROM queries q LEFT JOIN users u ON q.user_id=u.id WHERE $where ORDER BY q.created_at DESC");

$viewQuery = null;
if ($view) {
    $viewQuery = $conn->query("SELECT * FROM queries WHERE id=$view")->fetch_assoc();
    // Mark as in_progress if new
    if ($viewQuery && $viewQuery['status'] === 'new') {
        $conn->query("UPDATE queries SET status='in_progress' WHERE id=$view");
        $viewQuery['status'] = 'in_progress';
    }
}

$counts = [];
foreach(['all','new','in_progress','resolved','closed'] as $s) {
    $w = $s === 'all' ? '1=1' : "status='$s'";
    $counts[$s] = $conn->query("SELECT COUNT(*) c FROM queries WHERE $w")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Queries | EcoSprout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    <style>
        body { margin:0; }
        .admin-wrap { display:flex; min-height:100vh; }
        .adm-sidebar { width:220px; background:var(--green-dark); position:fixed; top:0; left:0; bottom:0; z-index:100; overflow-y:auto; }
        .adm-sidebar .logo { padding:18px 16px; border-bottom:1px solid rgba(255,255,255,.1); }
        .adm-sidebar .logo .logo-text { color:white; font-size:16px; }
        .adm-nav a { display:flex; align-items:center; gap:10px; padding:10px 16px; font-size:13px; color:rgba(255,255,255,.7); }
        .adm-nav a:hover, .adm-nav a.active { background:rgba(255,255,255,.1); color:white; }
        .adm-nav a i { width:16px; }
        .adm-main { margin-left:220px; flex:1; background:var(--cream); }
        .adm-topbar { background:white; padding:14px 24px; border-bottom:1px solid var(--cream-dark); }
        .adm-content { padding:24px; }
        .query-panel { display:grid; grid-template-columns:1fr 1.2fr; gap:20px; align-items:start; }
        .query-item { padding:14px; border-bottom:1px solid var(--cream-dark); cursor:pointer; transition:background .2s; }
        .query-item:hover { background:var(--cream); }
        .query-item.active { background:var(--green-pale); border-left:3px solid var(--green-mid); }
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
        <div class="logo">
            <div class="nav-logo" style="margin:0;"><span>🌿</span><span class="logo-text">Eco<strong>Sprout</strong></span></div>
        </div>
        <nav class="adm-nav" style="padding:10px 0;">
            <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php" class="active"><i class="fas fa-question-circle"></i> Queries</a>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
            <a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem;">💬 Customer Queries</h2>
        </div>
        <div class="adm-content">

            <!-- Filter Tabs -->
            <div class="filter-tabs" style="margin-bottom:20px;">
                <?php foreach(['all'=>'All','new'=>'🔴 New','in_progress'=>'🟡 In Progress','resolved'=>'🟢 Resolved','closed'=>'⚫ Closed'] as $f => $l): ?>
                <a href="?filter=<?= $f ?>" class="filter-tab <?= $filter===$f?'active':'' ?>" data-group="filter">
                    <?= $l ?> <span style="font-size:11px; opacity:.7;">(<?= $counts[$f] ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="query-panel">
                <!-- Queries List -->
                <div style="background:white; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm);">
                    <div style="padding:14px 16px; border-bottom:1px solid var(--cream-dark); font-size:12px; font-weight:600; color:var(--text-light);">
                        <?= $queries->num_rows ?> QUERIES
                    </div>
                    <?php if ($queries->num_rows === 0): ?>
                        <p style="text-align:center; padding:30px; color:var(--text-light); font-size:14px;">No queries found.</p>
                    <?php endif; ?>
                    <?php while ($q = $queries->fetch_assoc()):
                        $bc = match($q['status']){
                            'new'=>'badge-error','in_progress'=>'badge-warning',
                            'resolved'=>'badge-success',default=>'badge-default'
                        };
                    ?>
                    <a href="?filter=<?= $filter ?>&view=<?= $q['id'] ?>" class="query-item <?= $view===$q['id']?'active':'' ?>" style="display:block; text-decoration:none; color:inherit;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                            <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars(substr($q['subject'],0,40)) ?></div>
                            <span class="badge <?= $bc ?>" style="font-size:10px;"><?= str_replace('_',' ',ucfirst($q['status'])) ?></span>
                        </div>
                        <div style="font-size:12px; color:var(--text-light); margin-bottom:4px;">
                            <?= htmlspecialchars($q['name']) ?> · <?= htmlspecialchars($q['email']) ?>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--text-light);">
                            <span><?= str_replace('_',' ',ucfirst($q['category'])) ?></span>
                            <span><?= date('d M Y', strtotime($q['created_at'])) ?></span>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>

                <!-- Query Detail + Reply -->
                <div>
                    <?php if ($viewQuery): ?>
                    <div style="background:white; border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm); margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
                            <div>
                                <h3 style="font-size:1.1rem; margin-bottom:4px;"><?= htmlspecialchars($viewQuery['subject']) ?></h3>
                                <div style="font-size:12px; color:var(--text-light);">
                                    From: <strong><?= htmlspecialchars($viewQuery['name']) ?></strong>
                                    · <?= htmlspecialchars($viewQuery['email']) ?>
                                    <?php if ($viewQuery['phone']): ?>· <?= htmlspecialchars($viewQuery['phone']) ?><?php endif; ?>
                                </div>
                                <div style="font-size:11px; color:var(--text-light); margin-top:2px;">
                                    Received: <?= date('d M Y, g:i A', strtotime($viewQuery['created_at'])) ?>
                                    · Category: <?= str_replace('_',' ', ucfirst($viewQuery['category'])) ?>
                                </div>
                            </div>
                            <div style="display:flex; gap:6px;">
                                <a href="?filter=<?= $filter ?>&id=<?= $viewQuery['id'] ?>&status=resolved" class="btn-view" style="font-size:12px; padding:5px 10px;">Mark Resolved</a>
                                <a href="?filter=<?= $filter ?>&id=<?= $viewQuery['id'] ?>&status=closed" class="btn-view" style="font-size:12px; padding:5px 10px; color:var(--error);">Close</a>
                            </div>
                        </div>

                        <div style="background:var(--cream); border-radius:10px; padding:16px; margin-bottom:16px;">
                            <div style="font-size:12px; font-weight:600; color:var(--text-light); margin-bottom:8px;">CUSTOMER MESSAGE</div>
                            <p style="font-size:14px; line-height:1.7; color:var(--text-dark);"><?= nl2br(htmlspecialchars($viewQuery['message'])) ?></p>
                        </div>

                        <?php if ($viewQuery['response']): ?>
                        <div style="background:var(--green-pale); border-radius:10px; padding:16px;">
                            <div style="font-size:12px; font-weight:600; color:var(--green-mid); margin-bottom:8px;">STAFF RESPONSE · <?= date('d M Y', strtotime($viewQuery['responded_at'])) ?></div>
                            <p style="font-size:14px; line-height:1.7;"><?= nl2br(htmlspecialchars($viewQuery['response'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Reply Form -->
                    <?php if (!in_array($viewQuery['status'], ['resolved','closed'])): ?>
                    <div style="background:white; border-radius:var(--radius-lg); padding:20px; box-shadow:var(--shadow-sm);">
                        <h4 style="margin-bottom:14px; font-size:14px;">Send Response</h4>
                        <form method="POST">
                            <input type="hidden" name="query_id" value="<?= $viewQuery['id'] ?>">
                            <div class="form-group">
                                <textarea name="response" class="form-control" rows="5" required
                                          placeholder="Type your response to the customer..."><?= htmlspecialchars($viewQuery['response'] ?? '') ?></textarea>
                            </div>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <select name="status" class="form-control" style="width:auto;">
                                    <option value="in_progress">Keep In Progress</option>
                                    <option value="resolved" selected>Mark as Resolved</option>
                                </select>
                                <button type="submit" name="respond" class="btn-primary" style="padding:10px 20px;">
                                    <i class="fas fa-paper-plane"></i> Send Response
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div style="background:white; border-radius:var(--radius-lg); padding:48px 24px; text-align:center; box-shadow:var(--shadow-sm);">
                        <i class="fas fa-envelope-open" style="font-size:40px; color:var(--green-pale); margin-bottom:12px;"></i>
                        <h3 style="color:var(--text-mid); font-size:1rem;">Select a query to view and respond</h3>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
