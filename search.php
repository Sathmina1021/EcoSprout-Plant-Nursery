<?php
$pageTitle = 'Search Results';
require_once 'includes/header.php';

$query = sanitize($conn, $_GET['q'] ?? '');
$results = ['plants' => [], 'workshops' => [], 'blog' => [], 'services' => []];

if ($query) {
    // Search plants
    $r = $conn->query("
        SELECT p.*, pc.name as cat_name FROM plants p
        LEFT JOIN plant_categories pc ON p.category_id=pc.id
        WHERE p.is_active=1 AND (p.name LIKE '%$query%' OR p.botanical_name LIKE '%$query%' OR p.description LIKE '%$query%')
        LIMIT 8
    ");
    while ($row = $r->fetch_assoc()) $results['plants'][] = $row;

    // Search workshops
    $r = $conn->query("SELECT * FROM workshops WHERE is_active=1 AND workshop_date>=CURDATE() AND (title LIKE '%$query%' OR description LIKE '%$query%') LIMIT 4");
    while ($row = $r->fetch_assoc()) $results['workshops'][] = $row;

    // Search blog
    $r = $conn->query("SELECT * FROM blog_posts WHERE is_published=1 AND (title LIKE '%$query%' OR excerpt LIKE '%$query%') LIMIT 4");
    while ($row = $r->fetch_assoc()) $results['blog'][] = $row;

    // Search services
    $r = $conn->query("SELECT * FROM services WHERE is_active=1 AND (name LIKE '%$query%' OR description LIKE '%$query%') LIMIT 4");
    while ($row = $r->fetch_assoc()) $results['services'][] = $row;
}

$totalResults = count($results['plants']) + count($results['workshops']) + count($results['blog']) + count($results['services']);
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>Search Results</h1>
    <?php if ($query): ?>
    <p><?= $totalResults ?> result<?= $totalResults!==1?'s':'' ?> for "<strong><?= htmlspecialchars($query) ?></strong>"</p>
    <?php endif; ?>
</div>

<div class="section">
    <!-- Search Box -->
    <form method="GET" style="max-width:500px; margin:0 auto 40px;">
        <div class="search-bar">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search plants, workshops, services...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>

    <?php if (!$query): ?>
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>Enter a search term</h3>
        <p>Try searching for a plant name, service, or gardening topic.</p>
    </div>
    <?php elseif ($totalResults === 0): ?>
    <div class="empty-state">
        <i class="fas fa-leaf"></i>
        <h3>No results found</h3>
        <p>No results for "<?= htmlspecialchars($query) ?>". Try a different search term.</p>
        <a href="<?= SITE_URL ?>/plants.php" class="btn-primary">Browse All Plants</a>
    </div>
    <?php else: ?>

    <?php if (!empty($results['plants'])): ?>
    <div style="margin-bottom:40px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:1.3rem;">🌿 Plants (<?= count($results['plants']) ?>)</h2>
            <a href="<?= SITE_URL ?>/plants.php?q=<?= urlencode($query) ?>" style="font-size:13px; color:var(--green-mid);">View all →</a>
        </div>
        <div class="plants-grid">
            <?php foreach ($results['plants'] as $plant): ?>
            <div class="plant-card">
                <div class="plant-card-image">
                    <img src="<?= $plant['image'] && $plant['image'] !== 'default_plant.jpg'
                        ? SITE_URL.'/uploads/plants/'.htmlspecialchars($plant['image'])
                        : '<?= SITE_URL ?>/uploads/plants/default_plant.jpg' ?>"
                         alt="<?= htmlspecialchars($plant['name']) ?>" loading="lazy">
                    <?php if ($plant['cat_name']): ?><span class="plant-badge"><?= htmlspecialchars($plant['cat_name']) ?></span><?php endif; ?>
                </div>
                <div class="plant-card-body">
                    <div class="plant-name"><?= htmlspecialchars($plant['name']) ?></div>
                    <div class="plant-botanical"><?= htmlspecialchars($plant['botanical_name'] ?? '') ?></div>
                    <div class="plant-footer">
                        <div class="plant-price"><?= formatPrice($plant['price']) ?></div>
                        <a href="<?= SITE_URL ?>/plant_detail.php?id=<?= $plant['id'] ?>" class="btn-view">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['workshops'])): ?>
    <div style="margin-bottom:40px;">
        <h2 style="font-size:1.3rem; margin-bottom:20px;">📅 Workshops (<?= count($results['workshops']) ?>)</h2>
        <div class="workshops-grid">
            <?php foreach ($results['workshops'] as $w): ?>
            <div class="workshop-card">
                <div class="workshop-date-banner">
                    <i class="fas fa-calendar-alt"></i> <?= date('D, d M Y', strtotime($w['workshop_date'])) ?>
                </div>
                <div class="workshop-body">
                    <h3><?= htmlspecialchars($w['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($w['description']??'',0,100)) ?>...</p>
                    <div class="workshop-footer">
                        <div class="workshop-price"><?= $w['price']>0?formatPrice($w['price']):'FREE' ?></div>
                        <a href="<?= SITE_URL ?>/workshop_detail.php?id=<?= $w['id'] ?>" class="btn-primary" style="padding:8px 14px;font-size:13px;">Register</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['services'])): ?>
    <div style="margin-bottom:40px;">
        <h2 style="font-size:1.3rem; margin-bottom:20px;">🏡 Services (<?= count($results['services']) ?>)</h2>
        <div class="services-grid">
            <?php foreach ($results['services'] as $s): ?>
            <div class="service-card">
                <div class="service-icon">🌿</div>
                <h3><?= htmlspecialchars($s['name']) ?></h3>
                <p><?= htmlspecialchars(substr($s['description']??'',0,100)) ?>...</p>
                <div class="service-price"><?= formatPrice($s['price']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['blog'])): ?>
    <div style="margin-bottom:40px;">
        <h2 style="font-size:1.3rem; margin-bottom:20px;">📝 Blog Posts (<?= count($results['blog']) ?>)</h2>
        <div class="blog-grid">
            <?php foreach ($results['blog'] as $post): ?>
            <a href="<?= SITE_URL ?>/blog_post.php?slug=<?= htmlspecialchars($post['slug']) ?>" class="blog-card" style="display:block;color:inherit;">
                <div class="blog-image">
                    <img src="<?= SITE_URL ?>/uploads/plants/default_plant.jpg" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                </div>
                <div class="blog-body">
                    <div class="blog-cat"><?= str_replace('_',' ',ucfirst($post['category'])) ?></div>
                    <h3><?= htmlspecialchars($post['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($post['excerpt']??'',0,100)) ?>...</p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
