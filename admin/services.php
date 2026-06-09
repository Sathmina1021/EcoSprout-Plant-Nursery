<?php
$pageTitle = 'Manage Services';
require_once '../includes/config.php';
if (!isStaff()) { flashMessage('error','Access denied.'); redirect('login.php'); }

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($conn, $_POST['name'] ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $duration    = sanitize($conn, $_POST['duration'] ?? '');
    $isActive    = intval($_POST['is_active'] ?? 1);
    $imageName   = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $imageName = uniqid('service_') . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/services/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if ($name === '') {
        flashMessage('error', 'Service name is required.');
    } elseif ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, image, is_active) VALUES (?,?,?,?,?,?)");
        $img = $imageName ?: '';
        $stmt->bind_param('ssdssi', $name, $description, $price, $duration, $img, $isActive);
        if ($stmt->execute()) flashMessage('success', 'Service added successfully.');
        else flashMessage('error', 'Could not add service.');
        $stmt->close();
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $existing = $conn->query("SELECT image FROM services WHERE id=$id")->fetch_assoc();
        $img = $imageName ?: ($existing['image'] ?? '');
        $stmt = $conn->prepare("UPDATE services SET name=?, description=?, price=?, duration=?, image=?, is_active=? WHERE id=?");
        $stmt->bind_param('ssdssii', $name, $description, $price, $duration, $img, $isActive, $id);
        if ($stmt->execute()) flashMessage('success', 'Service updated successfully.');
        else flashMessage('error', 'Could not update service.');
        $stmt->close();
    }
    header('Location: ' . SITE_URL . '/admin/services.php'); exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("UPDATE services SET is_active=0 WHERE id=$id");
    flashMessage('success', 'Service deactivated.');
    header('Location: ' . SITE_URL . '/admin/services.php'); exit();
}

$editService = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editService = $conn->query("SELECT * FROM services WHERE id=" . intval($_GET['id']))->fetch_assoc();
}
$services = $conn->query("SELECT * FROM services ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services | EcoSprout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    <style>body{margin:0}.admin-wrap{display:flex;min-height:100vh}.adm-sidebar{width:220px;background:var(--green-dark);position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto}.adm-sidebar .logo{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,.1)}.adm-sidebar .logo .logo-text{color:white;font-size:16px}.adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:rgba(255,255,255,.7)}.adm-nav a:hover,.adm-nav a.active{background:rgba(255,255,255,.1);color:white}.adm-nav a i{width:16px}.adm-main{margin-left:220px;flex:1;background:var(--cream)}.adm-topbar{background:white;padding:14px 24px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between}.adm-content{padding:24px}</style>
</head>
<body>
<?php $flash = getFlash(); if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg" style="z-index:9999;"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['message']) ?><button onclick="document.getElementById('flashMsg').remove()" class="flash-close">&times;</button></div>
<?php endif; ?>
<div class="admin-wrap">
    <aside class="adm-sidebar">
        <div class="logo"><div style="display:flex;align-items:center;gap:8px;"><span>🌿</span><span class="logo-text">Eco<strong>Sprout</strong></span></div></div>
        <nav class="adm-nav" style="padding:10px 0;">
            <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/services.php" class="active"><i class="fas fa-tools"></i> Services</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <?php if (isAdmin()): ?><a href="<?= SITE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a><a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a><?php endif; ?>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem;">🛠 Manage Services</h2>
            <a href="<?= SITE_URL ?>/admin/services.php?action=add" class="btn-primary" style="padding:8px 16px;font-size:13px;"><i class="fas fa-plus"></i> Add Service</a>
        </div>
        <div class="adm-content">
            <?php if ($action === 'add' || ($action === 'edit' && $editService)): ?>
            <div style="background:white;border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:22px;">
                <h3 style="font-size:1rem;margin-bottom:18px;"><?= $action === 'add' ? 'Add New Service' : 'Edit Service' ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $action ?>">
                    <?php if ($editService): ?><input type="hidden" name="id" value="<?= $editService['id'] ?>"><?php endif; ?>
                    <div class="form-row">
                        <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editService['name'] ?? '') ?>" required></div>
                        <div class="form-group"><label>Price (Rs.)</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= htmlspecialchars($editService['price'] ?? '0') ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Duration</label><input type="text" name="duration" class="form-control" value="<?= htmlspecialchars($editService['duration'] ?? '') ?>" placeholder="e.g. 1 day"></div>
                        <div class="form-group"><label>Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($editService['description'] ?? '') ?></textarea></div>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:18px;font-size:13px;"><input type="checkbox" name="is_active" value="1" <?= ($editService['is_active'] ?? 1) ? 'checked' : '' ?> style="accent-color:var(--green-mid);"> Active</label>
                    <button type="submit" class="btn-primary" style="padding:11px 24px;"><i class="fas fa-save"></i> Save Service</button>
                    <a href="<?= SITE_URL ?>/admin/services.php" class="btn-view" style="padding:11px 20px;">Cancel</a>
                </form>
            </div>
            <?php endif; ?>

            <div style="background:white;border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Price</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (!$services || $services->num_rows === 0): ?><tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-light);">No services found.</td></tr><?php endif; ?>
                        <?php while ($s = $services->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong><br><small style="color:var(--text-light);"><?= htmlspecialchars(substr($s['description'] ?? '', 0, 80)) ?>...</small></td>
                            <td><?= formatPrice($s['price']) ?></td>
                            <td><?= htmlspecialchars($s['duration'] ?? 'Custom') ?></td>
                            <td><span class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-error' ?>"><?= $s['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td style="display:flex;gap:6px;">
                                <a href="<?= SITE_URL ?>/admin/services.php?action=edit&id=<?= $s['id'] ?>" class="btn-view" style="padding:5px 10px;font-size:12px;">Edit</a>
                                <?php if ($s['is_active']): ?><a href="<?= SITE_URL ?>/admin/services.php?delete=<?= $s['id'] ?>" onclick="return confirm('Deactivate this service?')" class="btn-view" style="padding:5px 10px;font-size:12px;color:var(--error);">Deactivate</a><?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>window.ECOSPROUT_SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
