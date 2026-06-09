<?php
$pageTitle = 'Manage Workshops';
require_once '../includes/config.php';
if (!isStaff()) { redirect('login.php'); }

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title        = sanitize($conn, $_POST['title'] ?? '');
    $description  = sanitize($conn, $_POST['description'] ?? '');
    $instructor   = sanitize($conn, $_POST['instructor'] ?? '');
    $date         = sanitize($conn, $_POST['workshop_date'] ?? '');
    $startTime    = sanitize($conn, $_POST['start_time'] ?? '');
    $endTime      = sanitize($conn, $_POST['end_time'] ?? '');
    $location     = sanitize($conn, $_POST['location'] ?? '');
    $maxPart      = intval($_POST['max_participants'] ?? 20);
    $price        = floatval($_POST['price'] ?? 0);
    $isActive     = intval($_POST['is_active'] ?? 1);

    if ($action === 'add') {
        if (empty($title) || empty($date)) {
            flashMessage('error', 'Title and date are required.');
        } else {
            $stmt = $conn->prepare("INSERT INTO workshops (title,description,instructor,workshop_date,start_time,end_time,location,max_participants,price,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssidi", $title,$description,$instructor,$date,$startTime,$endTime,$location,$maxPart,$price,$isActive);
            if ($stmt->execute()) { flashMessage('success', 'Workshop added!'); }
            $stmt->close();
        }
        header('Location: '.SITE_URL.'/admin/workshops.php'); exit();
    }

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE workshops SET title=?,description=?,instructor=?,workshop_date=?,start_time=?,end_time=?,location=?,max_participants=?,price=?,is_active=? WHERE id=?");
        $stmt->bind_param("sssssssidii", $title,$description,$instructor,$date,$startTime,$endTime,$location,$maxPart,$price,$isActive,$id);
        if ($stmt->execute()) { flashMessage('success', 'Workshop updated!'); }
        $stmt->close();
        header('Location: '.SITE_URL.'/admin/workshops.php'); exit();
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("UPDATE workshops SET is_active=0 WHERE id=$id");
    flashMessage('success','Workshop deactivated.');
    header('Location: '.SITE_URL.'/admin/workshops.php'); exit();
}

$editWorkshop = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editWorkshop = $conn->query("SELECT * FROM workshops WHERE id=".intval($_GET['id']))->fetch_assoc();
}

// View registrations
$viewRegs   = null;
$viewWshop  = null;
if (isset($_GET['registrations'])) {
    $wid        = intval($_GET['registrations']);
    $viewWshop  = $conn->query("SELECT * FROM workshops WHERE id=$wid")->fetch_assoc();
    $viewRegs   = $conn->query("
        SELECT wr.*, u.full_name, u.email, u.phone
        FROM workshop_registrations wr
        JOIN users u ON wr.user_id = u.id
        WHERE wr.workshop_id = $wid
        ORDER BY wr.registration_date ASC
    ");
}

$workshops = $conn->query("SELECT w.*, COUNT(wr.id) as reg_count FROM workshops w LEFT JOIN workshop_registrations wr ON w.id=wr.workshop_id GROUP BY w.id ORDER BY w.workshop_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Workshops | EcoSprout</title>
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
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php" class="active"><i class="fas fa-calendar-alt"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
            <a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem;">📅 Workshop Management</h2>
            <a href="?action=add" class="btn-primary" style="padding:8px 16px;font-size:13px;">
                <i class="fas fa-plus"></i> Add Workshop
            </a>
        </div>
        <div class="adm-content">

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div style="background:white;border-radius:var(--radius-lg);padding:28px;box-shadow:var(--shadow-sm);margin-bottom:24px;">
                <h3 style="margin-bottom:20px;"><?= $action==='add'?'Add New Workshop':'Edit Workshop' ?></h3>
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $action ?>">
                    <?php if ($editWorkshop): ?><input type="hidden" name="id" value="<?= $editWorkshop['id'] ?>"><?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Workshop Title <span style="color:var(--error)">*</span></label>
                            <input type="text" name="title" class="form-control" required
                                   value="<?= htmlspecialchars($editWorkshop['title'] ?? '') ?>" placeholder="e.g. Introduction to Indoor Gardening">
                        </div>
                        <div class="form-group">
                            <label>Instructor</label>
                            <input type="text" name="instructor" class="form-control"
                                   value="<?= htmlspecialchars($editWorkshop['instructor'] ?? '') ?>" placeholder="e.g. Ms. Chamari Silva">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editWorkshop['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date <span style="color:var(--error)">*</span></label>
                            <input type="date" name="workshop_date" class="form-control" required
                                   value="<?= $editWorkshop['workshop_date'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control"
                                   value="<?= $editWorkshop['start_time'] ?? '09:00' ?>">
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control"
                                   value="<?= $editWorkshop['end_time'] ?? '12:00' ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control"
                                   value="<?= htmlspecialchars($editWorkshop['location'] ?? 'EcoSprout Nursery, Kegalle') ?>">
                        </div>
                        <div class="form-group">
                            <label>Max Participants</label>
                            <input type="number" name="max_participants" class="form-control" min="1"
                                   value="<?= $editWorkshop['max_participants'] ?? 20 ?>">
                        </div>
                        <div class="form-group">
                            <label>Price (Rs.) — 0 for Free</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01"
                                   value="<?= $editWorkshop['price'] ?? 0 ?>">
                        </div>
                    </div>
                    <div style="margin-bottom:18px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                            <input type="checkbox" name="is_active" value="1" <?= ($editWorkshop['is_active']??1)?'checked':'' ?> style="accent-color:var(--green-mid);">
                            ✅ Active (visible to customers)
                        </label>
                    </div>
                    <div style="display:flex;gap:12px;">
                        <button type="submit" class="btn-primary" style="padding:11px 24px;">
                            <i class="fas fa-save"></i> <?= $action==='add'?'Add Workshop':'Update Workshop' ?>
                        </button>
                        <a href="<?= SITE_URL ?>/admin/workshops.php" class="btn-view" style="padding:11px 20px;">Cancel</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($viewWshop && $viewRegs): ?>
            <!-- Registrations List -->
            <div style="background:white;border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h3 style="font-size:1rem;">Registrations: <?= htmlspecialchars($viewWshop['title']) ?></h3>
                    <a href="<?= SITE_URL ?>/admin/workshops.php" class="btn-view" style="font-size:12px;padding:6px 12px;">← Back to List</a>
                </div>
                <?php if ($viewRegs->num_rows === 0): ?>
                    <p style="color:var(--text-light);text-align:center;padding:20px 0;font-size:13px;">No registrations yet.</p>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Payment</th></tr>
                    </thead>
                    <tbody>
                        <?php $i=1; while ($r = $viewRegs->fetch_assoc()): ?>
                        <tr>
                            <td style="font-size:12px;color:var(--text-light);"><?= $i++ ?></td>
                            <td style="font-weight:500;font-size:13px;"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td style="font-size:13px;"><?= htmlspecialchars($r['email']) ?></td>
                            <td style="font-size:13px;"><?= htmlspecialchars($r['phone'] ?? '–') ?></td>
                            <td style="font-size:12px;color:var(--text-light);"><?= date('d M Y', strtotime($r['registration_date'])) ?></td>
                            <td><span class="badge <?= $r['payment_status']==='paid'?'badge-success':'badge-warning' ?>" style="font-size:10px;"><?= ucfirst($r['payment_status']) ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Workshops Table -->
            <div style="background:white;border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);">
                <table class="data-table">
                    <thead>
                        <tr><th>Title</th><th>Date</th><th>Instructor</th><th>Registrations</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($workshops->num_rows === 0): ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-light);">No workshops yet.</td></tr>
                        <?php endif; ?>
                        <?php while ($w = $workshops->fetch_assoc()):
                            $isPast = strtotime($w['workshop_date']) < time();
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:13px;"><?= htmlspecialchars(substr($w['title'],0,40)) ?></div>
                                <?php if ($isPast): ?><div style="font-size:11px;color:var(--text-light);">Past Event</div><?php endif; ?>
                            </td>
                            <td style="font-size:13px;"><?= date('d M Y', strtotime($w['workshop_date'])) ?></td>
                            <td style="font-size:13px;"><?= htmlspecialchars($w['instructor'] ?? '–') ?></td>
                            <td>
                                <a href="?registrations=<?= $w['id'] ?>" style="font-size:13px;color:var(--green-mid);font-weight:600;">
                                    <?= $w['reg_count'] ?>/<?= $w['max_participants'] ?>
                                    <i class="fas fa-external-link-alt" style="font-size:10px;"></i>
                                </a>
                            </td>
                            <td style="font-size:13px;font-weight:600;"><?= $w['price']>0?formatPrice($w['price']):'Free' ?></td>
                            <td>
                                <span class="badge <?= $w['is_active']?'badge-success':'badge-default' ?>">
                                    <?= $w['is_active']?'Active':'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="?action=edit&id=<?= $w['id'] ?>" class="btn-view" style="padding:5px 10px;font-size:12px;"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?= $w['id'] ?>" class="btn-view" style="padding:5px 10px;font-size:12px;color:var(--error);" onclick="return confirm('Deactivate this workshop?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
