<?php
$pageTitle = 'Plant Catalogue';
require_once 'includes/header.php';

// Get filters
$search     = sanitize($conn, $_GET['q'] ?? '');
$categoryId = intval($_GET['category'] ?? 0);
$sort       = sanitize($conn, $_GET['sort'] ?? 'featured');
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 12;
$offset     = ($page - 1) * $perPage;

// Build query
$where = "p.is_active = 1";
if ($search)     $where .= " AND (p.name LIKE '%$search%' OR p.botanical_name LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($categoryId) $where .= " AND p.category_id = $categoryId";

$orderBy = match($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    'newest'     => 'p.created_at DESC',
    default      => 'p.is_featured DESC, p.name ASC'
};

$totalResult = $conn->query("SELECT COUNT(*) as cnt FROM plants p WHERE $where");
$total       = $totalResult->fetch_assoc()['cnt'];
$totalPages  = ceil($total / $perPage);

$plants = $conn->query("
    SELECT p.*, pc.name as category_name 
    FROM plants p 
    LEFT JOIN plant_categories pc ON p.category_id = pc.id 
    WHERE $where 
    ORDER BY $orderBy 
    LIMIT $perPage OFFSET $offset
");

$categories = $conn->query("SELECT * FROM plant_categories ORDER BY name");
?>

<div class="page-hero">
    <h1>🌿 Plant Catalogue</h1>
    <p>Explore our collection of <?= $total ?> beautiful plant varieties</p>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a>
        <span>/</span>
        <span>Plants</span>
        <?php if ($search): ?>
            <span>/</span><span>Search: "<?= htmlspecialchars($search) ?>"</span>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <!-- Search + Filters Bar -->
    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-end; margin-bottom:28px; padding:20px; background:white; border-radius:var(--radius); box-shadow:var(--shadow-sm);">
        <form method="GET" style="flex:1; min-width:200px;">
            <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
            <div class="search-bar" style="max-width:none;">
                <input type="text" name="q" placeholder="Search plants by name or description..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <form method="GET" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
            <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
            <label style="font-size:13px; font-weight:600; color:var(--text-mid);">Sort by:</label>
            <select name="sort" class="form-control" style="width:auto; padding:8px 12px;" onchange="this.form.submit()">
                <option value="featured" <?= $sort==='featured'?'selected':'' ?>>Featured</option>
                <option value="name"     <?= $sort==='name'?'selected':'' ?>>Name A–Z</option>
                <option value="newest"   <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
                <option value="price_asc"  <?= $sort==='price_asc'?'selected':'' ?>>Price: Low → High</option>
                <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High → Low</option>
            </select>
        </form>
    </div>

    <div style="display:grid; grid-template-columns:220px 1fr; gap:28px;">
        <!-- Sidebar Filters -->
        <aside>
            <div style="background:white; border-radius:var(--radius); padding:20px; box-shadow:var(--shadow-sm);">
                <h4 style="margin-bottom:14px; font-size:14px; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light);">Categories</h4>
                <ul style="list-style:none;">
                    <li>
                        <a href="<?= SITE_URL ?>/plants.php<?= $search ? '?q='.urlencode($search) : '' ?>"
                           style="display:flex; justify-content:space-between; padding:8px 10px; border-radius:8px; font-size:14px; <?= !$categoryId ? 'background:var(--green-pale); color:var(--green-dark); font-weight:600;' : 'color:var(--text-mid);' ?>">
                            All Plants
                            <span style="font-size:11px; opacity:.6;"><?= $conn->query("SELECT COUNT(*) as c FROM plants WHERE is_active=1")->fetch_assoc()['c'] ?></span>
                        </a>
                    </li>
                    <?php while ($cat = $categories->fetch_assoc()): 
                        $catCount = $conn->query("SELECT COUNT(*) as c FROM plants WHERE category_id={$cat['id']} AND is_active=1")->fetch_assoc()['c'];
                    ?>
                    <li>
                        <a href="?category=<?= $cat['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>"
                           style="display:flex; justify-content:space-between; padding:8px 10px; border-radius:8px; font-size:14px; <?= $categoryId==$cat['id'] ? 'background:var(--green-pale); color:var(--green-dark); font-weight:600;' : 'color:var(--text-mid);' ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                            <span style="font-size:11px; opacity:.6;"><?= $catCount ?></span>
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>

                <hr style="border:none; border-top:1px solid var(--cream-dark); margin:18px 0;">

                <h4 style="margin-bottom:14px; font-size:14px; text-transform:uppercase; letter-spacing:.06em; color:var(--text-light);">Light Requirements</h4>
                <?php foreach(['full_sun'=>'☀️ Full Sun','partial_shade'=>'⛅ Partial Shade','full_shade'=>'🌑 Full Shade'] as $val => $label): ?>
                <label style="display:flex; align-items:center; gap:8px; padding:6px 0; cursor:pointer; font-size:13px; color:var(--text-mid);">
                    <input type="checkbox" style="accent-color:var(--green-mid);"> <?= $label ?>
                </label>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Plants Grid -->
        <div>
            <?php if ($total === 0): ?>
            <div class="empty-state">
                <i class="fas fa-seedling"></i>
                <h3>No plants found</h3>
                <p><?= $search ? "No results for \"$search\"" : 'No plants in this category yet.' ?></p>
                <a href="<?= SITE_URL ?>/plants.php" class="btn-primary">View All Plants</a>
            </div>
            <?php else: ?>
            <p style="font-size:13px; color:var(--text-light); margin-bottom:16px;">
                Showing <?= min($offset+1,$total) ?>–<?= min($offset+$perPage,$total) ?> of <?= $total ?> plants
            </p>
            <div class="plants-grid">
                <?php while ($plant = $plants->fetch_assoc()): ?>
                <div class="plant-card">
                    <div class="plant-card-image">
                        <img src="<?= $plant['image'] && $plant['image'] !== 'default_plant.jpg'
                            ? SITE_URL.'/uploads/plants/'.htmlspecialchars($plant['image'])
                            : SITE_URL.'/uploads/plants/default_plant.jpg' ?>"
                             alt="<?= htmlspecialchars($plant['name']) ?>" loading="lazy">
                        <?php if ($plant['category_name']): ?>
                            <span class="plant-badge"><?= htmlspecialchars($plant['category_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($plant['stock_quantity'] == 0): ?>
                            <span class="plant-badge" style="background:var(--error); right:12px; left:auto; top:12px;">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="plant-card-body">
                        <div class="plant-name"><?= htmlspecialchars($plant['name']) ?></div>
                        <?php if ($plant['botanical_name']): ?>
                        <div class="plant-botanical"><?= htmlspecialchars($plant['botanical_name']) ?></div>
                        <?php endif; ?>
                        <p class="plant-desc"><?= htmlspecialchars($plant['description'] ?? '') ?></p>
                        <div class="plant-meta">
                            <span class="plant-meta-item">
                                <i class="fas fa-sun"></i>
                                <?= str_replace('_', ' ', ucfirst($plant['sunlight_requirement'])) ?>
                            </span>
                            <span class="plant-meta-item">
                                <i class="fas fa-tint"></i>
                                <?= str_replace('_', ' ', ucfirst($plant['water_frequency'])) ?>
                            </span>
                            <span class="plant-meta-item">
                                <i class="fas fa-box"></i>
                                <?= $plant['stock_quantity'] > 0 ? $plant['stock_quantity'].' left' : 'Out of stock' ?>
                            </span>
                        </div>
                        <div class="plant-footer">
                            <div class="plant-price">
                                <?= formatPrice($plant['price']) ?>
                            </div>
                            <div style="display:flex; gap:6px;">
                                <a href="<?= SITE_URL ?>/plant_detail.php?id=<?= $plant['id'] ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($plant['stock_quantity'] > 0): ?>
                                    <?php if (isLoggedIn()): ?>
                                    <button class="btn-cart" data-id="<?= $plant['id'] ?>" data-type="plant">
                                        <i class="fas fa-basket-shopping"></i>
                                    </button>
                                    <?php else: ?>
                                    <a href="<?= SITE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-cart" style="text-decoration:none;">
                                        <i class="fas fa-basket-shopping"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination" style="margin-top:32px; justify-content:center;">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?><?= $categoryId ? "&category=$categoryId" : '' ?><?= $search ? "&q=".urlencode($search) : '' ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
                <a href="?page=<?= $p ?><?= $categoryId ? "&category=$categoryId" : '' ?><?= $search ? "&q=".urlencode($search) : '' ?>"
                   class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?><?= $categoryId ? "&category=$categoryId" : '' ?><?= $search ? "&q=".urlencode($search) : '' ?>" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
