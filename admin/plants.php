<?php
$pageTitle = 'Manage Plants';
require_once '../includes/config.php';

if (!isStaff()) { flashMessage('error','Access denied.'); redirect('login.php'); }

$message = '';
$action  = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = sanitize($conn, $_POST['name'] ?? '');
    $botanicalName= sanitize($conn, $_POST['botanical_name'] ?? '');
    $description  = sanitize($conn, $_POST['description'] ?? '');
    $careInstructions = sanitize($conn, $_POST['care_instructions'] ?? '');
    $categoryId   = intval($_POST['category_id'] ?? 0);
    $sunlight     = sanitize($conn, $_POST['sunlight_requirement'] ?? 'partial_shade');
    $water        = sanitize($conn, $_POST['water_frequency'] ?? 'weekly');
    $price        = floatval($_POST['price'] ?? 0);
    $stock        = intval($_POST['stock_quantity'] ?? 0);
    $isFeatured   = intval($_POST['is_featured'] ?? 0);
    $isActive     = intval($_POST['is_active'] ?? 1);

    // Handle image upload
    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $imageName = uniqid('plant_') . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/plants/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if ($action === 'add') {
        if (empty($name) || $price <= 0) {
            $message = '<div class="flash-message flash-error" style="position:static;transform:none;margin-bottom:16px;"><i class="fas fa-times-circle"></i> Name and a valid price are required.</div>';
        } else {
            $img = $imageName ?: 'default_plant.jpg';
            $stmt = $conn->prepare("INSERT INTO plants (category_id,name,botanical_name,description,care_instructions,sunlight_requirement,water_frequency,price,stock_quantity,image,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issssssdisii", $categoryId,$name,$botanicalName,$description,$careInstructions,$sunlight,$water,$price,$stock,$img,$isFeatured,$isActive);
            if ($stmt->execute()) {
                flashMessage('success', "Plant \"$name\" added successfully!");
                header('Location: '.SITE_URL.'/admin/plants.php'); exit();
            }
            $stmt->close();
        }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $existing = $conn->query("SELECT image FROM plants WHERE id=$id")->fetch_assoc();
        $img = $imageName ?: ($existing['image'] ?? 'default_plant.jpg');

        $stmt = $conn->prepare("UPDATE plants SET category_id=?,name=?,botanical_name=?,description=?,care_instructions=?,sunlight_requirement=?,water_frequency=?,price=?,stock_quantity=?,image=?,is_featured=?,is_active=? WHERE id=?");
        $stmt->bind_param("issssssdisiii", $categoryId,$name,$botanicalName,$description,$careInstructions,$sunlight,$water,$price,$stock,$img,$isFeatured,$isActive,$id);
        if ($stmt->execute()) {
            flashMessage('success', "Plant updated successfully!");
            header('Location: '.SITE_URL.'/admin/plants.php'); exit();
        }
        $stmt->close();
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("UPDATE plants SET is_active=0 WHERE id=$id");
    flashMessage('success', 'Plant deactivated.');
    header('Location: '.SITE_URL.'/admin/plants.php'); exit();
}

// Get plant for editing
$editPlant = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editPlant = $conn->query("SELECT * FROM plants WHERE id=".intval($_GET['id']))->fetch_assoc();
}

// Fetch all plants
$search = sanitize($conn, $_GET['q'] ?? '');
$where  = $search ? "WHERE name LIKE '%$search%' OR botanical_name LIKE '%$search%'" : "";
$plants = $conn->query("SELECT p.*, pc.name as cat_name FROM plants p LEFT JOIN plant_categories pc ON p.category_id=pc.id $where ORDER BY p.created_at DESC");
$categories = $conn->query("SELECT * FROM plant_categories ORDER BY name");
$catList = [];
while ($c = $categories->fetch_assoc()) $catList[] = $c;

// Inline admin CSS (shared)
$adminCSS = '
<style>
body { margin:0; }
.admin-wrap { display:flex; min-height:100vh; }
.adm-sidebar {
    width:220px; background:var(--green-dark); position:fixed; top:0; left:0; bottom:0; z-index:100;
    overflow-y:auto; display:flex; flex-direction:column;
}
.adm-sidebar .logo { padding:18px 16px; border-bottom:1px solid rgba(255,255,255,.1); }
.adm-sidebar .logo .logo-text { color:white; font-size:16px; }
.adm-nav a { display:flex; align-items:center; gap:10px; padding:10px 16px; font-size:13px; color:rgba(255,255,255,.7); }
.adm-nav a:hover, .adm-nav a.active { background:rgba(255,255,255,.1); color:white; }
.adm-nav a i { width:16px; }
.adm-main { margin-left:220px; flex:1; background:var(--cream); }
.adm-topbar { background:white; padding:14px 24px; border-bottom:1px solid var(--cream-dark); display:flex; align-items:center; justify-content:space-between; }
.adm-content { padding:24px; }
</style>';
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
    <?= $adminCSS ?>
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
            <div class="nav-logo" style="margin:0;"><span class="logo-icon">🌿</span><span class="logo-text">Eco<strong>Sprout</strong></span></div>
            <div style="font-size:10px;color:rgba(255,255,255,.4);margin-top:2px;"><?= isAdmin()?'Administrator':'Staff' ?> Panel</div>
        </div>
        <nav class="adm-nav" style="padding:10px 0;">
            <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/admin/plants.php" class="active"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar-alt"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/index.php"><i class="fas fa-globe"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8);"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem;">🌿 Manage Plants</h2>
            <a href="?action=add" class="btn-primary" style="padding:8px 16px; font-size:13px;">
                <i class="fas fa-plus"></i> Add New Plant
            </a>
        </div>

        <div class="adm-content">
            <?= $message ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Form -->
            <div style="background:white; border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-sm); margin-bottom:24px;">
                <h3 style="margin-bottom:20px;"><?= $action === 'add' ? 'Add New Plant' : 'Edit Plant' ?></h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $action ?>">
                    <?php if ($editPlant): ?><input type="hidden" name="id" value="<?= $editPlant['id'] ?>"><?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Plant Name <span style="color:var(--error)">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?= htmlspecialchars($editPlant['name'] ?? '') ?>" placeholder="e.g. Peace Lily">
                        </div>
                        <div class="form-group">
                            <label>Botanical Name</label>
                            <input type="text" name="botanical_name" class="form-control"
                                   value="<?= htmlspecialchars($editPlant['botanical_name'] ?? '') ?>" placeholder="e.g. Spathiphyllum wallisii">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">-- Select Category --</option>
                                <?php foreach ($catList as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($editPlant['category_id']??'')==$cat['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (Rs.) <span style="color:var(--error)">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required
                                   value="<?= $editPlant['price'] ?? '' ?>" placeholder="e.g. 850.00">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" min="0"
                                   value="<?= $editPlant['stock_quantity'] ?? 0 ?>">
                        </div>
                        <div class="form-group">
                            <label>Plant Image</label>
                            <input type="file" name="image" class="form-control image-input" accept="image/*" data-preview="imgPreview">
                            <?php if (!empty($editPlant['image']) && $editPlant['image'] !== 'default_plant.jpg'): ?>
                            <img id="imgPreview" src="<?= SITE_URL ?>/uploads/plants/<?= htmlspecialchars($editPlant['image']) ?>"
                                 style="width:60px; height:60px; object-fit:cover; border-radius:8px; margin-top:6px;">
                            <?php else: ?>
                            <img id="imgPreview" src="" style="display:none; width:60px; height:60px; object-fit:cover; border-radius:8px; margin-top:6px;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Sunlight Requirement</label>
                            <select name="sunlight_requirement" class="form-control">
                                <?php foreach(['full_sun'=>'☀️ Full Sun','partial_shade'=>'⛅ Partial Shade','full_shade'=>'🌑 Full Shade'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($editPlant['sunlight_requirement']??'partial_shade')===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Watering Frequency</label>
                            <select name="water_frequency" class="form-control">
                                <?php foreach(['daily'=>'Daily','every_2_days'=>'Every 2 Days','weekly'=>'Weekly','bi_weekly'=>'Bi-Weekly'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($editPlant['water_frequency']??'weekly')===$v?'selected':'' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe this plant..."><?= htmlspecialchars($editPlant['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Care Instructions</label>
                        <textarea name="care_instructions" class="form-control" rows="3" placeholder="How to care for this plant..."><?= htmlspecialchars($editPlant['care_instructions'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex; gap:20px; margin-bottom:20px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px;">
                            <input type="checkbox" name="is_featured" value="1" <?= ($editPlant['is_featured']??0)?'checked':'' ?> style="accent-color:var(--green-mid);">
                            ⭐ Featured on Homepage
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px;">
                            <input type="checkbox" name="is_active" value="1" <?= ($editPlant['is_active']??1)?'checked':'' ?> style="accent-color:var(--green-mid);">
                            ✅ Active (visible to customers)
                        </label>
                    </div>

                    <div style="display:flex; gap:12px;">
                        <button type="submit" class="btn-primary" style="padding:11px 24px;">
                            <i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Plant' : 'Update Plant' ?>
                        </button>
                        <a href="<?= SITE_URL ?>/admin/plants.php" class="btn-view" style="padding:11px 20px;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Plants Table -->
            <div style="background:white; border-radius:var(--radius-lg); padding:20px; box-shadow:var(--shadow-sm);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h3 style="font-size:1rem;">All Plants (<?= $plants->num_rows ?>)</h3>
                    <form method="GET" style="display:flex; gap:0;">
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search plants..." style="border-radius:8px 0 0 8px; padding:8px 12px; width:200px;">
                        <button type="submit" style="padding:8px 14px; background:var(--green-mid); color:white; border:none; border-radius:0 8px 8px 0; cursor:pointer;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($plants->num_rows === 0): ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-light);">No plants found.</td></tr>
                        <?php else: ?>
                        <?php while ($plant = $plants->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?= $plant['image'] && $plant['image'] !== 'default_plant.jpg'
                                    ? SITE_URL.'/uploads/plants/'.htmlspecialchars($plant['image'])
                                    : SITE_URL.'/uploads/plants/default_plant.jpg' ?>"
                                     style="width:44px; height:44px; object-fit:cover; border-radius:8px;">
                            </td>
                            <td>
                                <div style="font-weight:600; font-size:13px;"><?= htmlspecialchars($plant['name']) ?></div>
                                <?php if ($plant['botanical_name']): ?>
                                <div style="font-size:11px; color:var(--text-light); font-style:italic;"><?= htmlspecialchars($plant['botanical_name']) ?></div>
                                <?php endif; ?>
                                <?php if ($plant['is_featured']): ?><span style="font-size:10px;">⭐ Featured</span><?php endif; ?>
                            </td>
                            <td><span class="badge badge-default" style="font-size:11px;"><?= htmlspecialchars($plant['cat_name'] ?? 'Uncategorised') ?></span></td>
                            <td style="font-weight:600;"><?= formatPrice($plant['price']) ?></td>
                            <td>
                                <span style="font-size:13px; color:<?= $plant['stock_quantity'] > 5 ? 'var(--success)' : ($plant['stock_quantity'] > 0 ? 'var(--warning)' : 'var(--error)') ?>; font-weight:600;">
                                    <?= $plant['stock_quantity'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $plant['is_active'] ? 'badge-success' : 'badge-error' ?>">
                                    <?= $plant['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <a href="?action=edit&id=<?= $plant['id'] ?>" class="btn-view" style="padding:5px 10px; font-size:12px;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?= $plant['id'] ?>" class="btn-view" style="padding:5px 10px; font-size:12px; color:var(--error);"
                                       onclick="return confirm('Deactivate this plant?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
