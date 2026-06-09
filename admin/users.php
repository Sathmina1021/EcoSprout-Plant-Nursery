<?php
$pageTitle = 'Manage Users';
require_once '../includes/config.php';
if (!isAdmin()) { flashMessage('error','Admins only.'); redirect('login.php'); }

// Toggle user active status
if (isset($_GET['toggle'])) {
    $uid = intval($_GET['toggle']);
    $conn->query("UPDATE users SET is_active = 1 - is_active WHERE id=$uid AND id != {$_SESSION['user_id']}");
    flashMessage('success', 'User status updated.');
    header('Location: users.php'); exit();
}

// Add staff account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name     = sanitize($conn, $_POST['full_name'] ?? '');
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['staff','admin']) ? $_POST['role'] : 'staff';
    $password = password_hash('Staff@123', PASSWORD_DEFAULT);

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            flashMessage('error', 'Email already registered.');
        } else {
            $conn->query("INSERT INTO users (full_name, email, password, role) VALUES ('$name','$email','$password','$role')");
            flashMessage('success', "Staff account created! Default password: Staff\@123");
        }
    } else {
        flashMessage('error', 'Invalid name or email.');
    }
    header('Location: users.php'); exit();
}

$roleFilter = sanitize($conn, $_GET['role'] ?? 'all');
$search     = sanitize($conn, $_GET['q'] ?? '');
$where = "1=1";
if ($roleFilter !== 'all') $where .= " AND role='$roleFilter'";
if ($search) $where .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%')";

$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY created_at DESC");

$counts = [];
foreach(['all','customer','staff','admin'] as $r) {
    $w = $r === 'all' ? '1=1' : "role='$r'";
    $counts[$r] = $conn->query("SELECT COUNT(*) c FROM users WHERE $w")->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | EcoSprout</title>
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
        .adm-topbar{background:white;padding:14px 24px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between}
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
            <a href="<?= SITE_URL ?>/admin/users.php" class="active"><i class="fas fa-users"></i> Users</a>
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem;">👥 User Management</h2>
            <button onclick="openModal('addStaffModal')" class="btn-primary" style="padding:8px 16px;font-size:13px;">
                <i class="fas fa-user-plus"></i> Add Staff Account
            </button>
        </div>
        <div class="adm-content">
            <!-- Filter + Search -->
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px; align-items:center;">
                <div class="filter-tabs" style="margin:0;">
                    <?php foreach(['all'=>'All Users','customer'=>'Customers','staff'=>'Staff','admin'=>'Admins'] as $r=>$l): ?>
                    <a href="?role=<?= $r ?><?= $search?"&q=".urlencode($search):'' ?>" class="filter-tab <?= $roleFilter===$r?'active':'' ?>">
                        <?= $l ?> <span style="font-size:11px;opacity:.7;">(<?= $counts[$r] ?>)</span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <form method="GET" style="display:flex;gap:0;margin-left:auto;">
                    <input type="hidden" name="role" value="<?= $roleFilter ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email..." class="form-control" style="border-radius:8px 0 0 8px;width:220px;padding:8px 12px;">
                    <button type="submit" style="padding:8px 14px;background:var(--green-mid);color:white;border:none;border-radius:0 8px 8px 0;cursor:pointer;"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div style="background:white; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm);">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows === 0): ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No users found.</td></tr>
                        <?php endif; ?>
                        <?php while ($u = $users->fetch_assoc()):
                            $roleBadge = match($u['role']){'admin'=>'badge-error','staff'=>'badge-info',default=>'badge-default'};
                        ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['full_name']) ?>&background=2d6a4f&color=fff&size=36"
                                         style="width:36px;height:36px;border-radius:50%;">
                                    <div style="font-weight:500;font-size:13px;"><?= htmlspecialchars($u['full_name']) ?></div>
                                </div>
                            </td>
                            <td style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="font-size:13px;"><?= htmlspecialchars($u['phone'] ?? '–') ?></td>
                            <td><span class="badge <?= $roleBadge ?>" style="font-size:11px;"><?= ucfirst($u['role']) ?></span></td>
                            <td style="font-size:12px;color:var(--text-light);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= $u['is_active']?'badge-success':'badge-error' ?>">
                                    <?= $u['is_active']?'Active':'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <a href="?toggle=<?= $u['id'] ?>&role=<?= $roleFilter ?>" class="btn-view" style="padding:5px 10px;font-size:12px;"
                                   onclick="return confirm('Toggle this user\'s status?')">
                                    <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </a>
                                <?php else: ?>
                                <span style="font-size:12px;color:var(--text-light);">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal-overlay" id="addStaffModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3 style="font-size:1.1rem;">Add Staff Account</h3>
            <button class="modal-close" onclick="closeModal('addStaffModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="form-group">
                <label>Full Name <span style="color:var(--error)">*</span></label>
                <input type="text" name="full_name" class="form-control" required placeholder="Staff member's name">
            </div>
            <div class="form-group">
                <label>Email Address <span style="color:var(--error)">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="staff@ecosprout.lk">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="staff">Staff</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <p style="font-size:12px;color:var(--text-light);margin-bottom:16px;">Default password: <strong>Staff@123</strong> (staff must change on first login)</p>
            <button type="submit" name="add_staff" class="btn-submit" style="padding:12px;">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
    </div>
</div>

<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
