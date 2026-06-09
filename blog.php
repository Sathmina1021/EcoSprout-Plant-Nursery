<?php
$pageTitle = 'Gardening Blog';
require_once 'includes/header.php';

$category = sanitize($conn, $_GET['cat'] ?? '');
$search   = sanitize($conn, $_GET['q'] ?? '');
$where    = isStaff() ? "1=1" : "b.is_published=1";
if ($category) $where .= " AND b.category='$category'";
if ($search)   $where .= " AND (b.title LIKE '%$search%' OR b.excerpt LIKE '%$search%')";

$posts = $conn->query("
    SELECT b.*, u.full_name as author
    FROM blog_posts b LEFT JOIN users u ON b.author_id=u.id
    WHERE $where ORDER BY b.created_at DESC
");

$totalPublished = $conn->query("SELECT COUNT(*) c FROM blog_posts WHERE is_published=1")->fetch_assoc()['c'];
$totalDrafts    = isStaff() ? $conn->query("SELECT COUNT(*) c FROM blog_posts WHERE is_published=0")->fetch_assoc()['c'] : 0;
$catLabels = ['plant_care'=>'🌿 Plant Care','diy'=>'🔨 DIY','seasonal'=>'🍂 Seasonal','landscaping'=>'🏡 Landscaping','news'=>'📰 News'];
?>

<?php if (isStaff()): ?>
<!-- ── Admin/Staff Quick Bar ────────────────────────────────────────────────── -->
<div style="background:var(--green-dark);padding:10px 0;position:sticky;top:70px;z-index:90;box-shadow:0 2px 8px rgba(0,0,0,.2)">
    <div style="max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <span style="color:rgba(255,255,255,.7);font-size:12px"><i class="fas fa-shield-alt"></i> <?= isAdmin() ? 'Admin' : 'Staff' ?> view</span>
        <span style="color:white;font-size:12px"><i class="fas fa-check-circle" style="color:#81c784"></i> <?= $totalPublished ?> published</span>
        <?php if ($totalDrafts > 0): ?>
        <span style="color:#ffcc80;font-size:12px"><i class="fas fa-clock"></i> <?= $totalDrafts ?> draft<?= $totalDrafts > 1 ? 's' : '' ?></span>
        <?php endif; ?>
        <div style="margin-left:auto;display:flex;gap:10px">
            <a href="<?= SITE_URL ?>/admin/blog.php?action=add" style="background:var(--green-light);color:white;padding:6px 16px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none">
                <i class="fas fa-plus"></i> New Post
            </a>
            <a href="<?= SITE_URL ?>/admin/blog.php" style="background:rgba(255,255,255,.1);color:white;padding:6px 16px;border-radius:6px;font-size:12px;font-decoration:none">
                <i class="fas fa-cog"></i> Manage All Posts
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="page-hero">
    <div style="background:var(--green-light);color:white;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;display:inline-block;margin-bottom:12px;letter-spacing:.05em">BLOG</div>
    <h1>From Our Blog</h1>
    <p>Expert advice, seasonal guides, and DIY projects to help your garden thrive.</p>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span><span>Blog</span>
    </div>
</div>

<div class="section">
    <!-- Filter + Search bar -->
    <form method="GET" action="" style="margin-bottom:32px">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-bottom:16px">
            <div style="position:relative;flex:1;min-width:200px">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:13px"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search posts…"
                    style="width:100%;padding:9px 14px 9px 36px;border:1.5px solid var(--cream-dark);border-radius:30px;font-size:14px;font-family:'DM Sans',sans-serif;background:white;color:var(--text-dark)">
            </div>
            <button type="submit" class="btn-primary" style="padding:9px 20px;border-radius:30px;font-size:13px">Search</button>
            <?php if ($search): ?>
            <a href="blog.php<?= $category ? '?cat='.$category : '' ?>" style="font-size:13px;color:var(--text-mid)">Clear ×</a>
            <?php endif; ?>
        </div>
        <div class="filter-tabs">
            <a href="blog.php" class="filter-tab <?= !$category?'active':'' ?>">All Posts</a>
            <?php foreach($catLabels as $c=>$l): ?>
            <a href="?cat=<?= $c ?><?= $search?'&q='.urlencode($search):'' ?>" class="filter-tab <?= $category===$c?'active':'' ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>
    </form>

    <?php if ($posts->num_rows === 0): ?>
    <div class="empty-state">
        <i class="fas fa-pen"></i>
        <h3>No posts yet</h3>
        <p><?= isStaff() ? 'Be the first — <a href="'.SITE_URL.'/admin/blog.php?action=add" style="color:var(--green-mid)">create a post</a>.' : 'Check back soon for gardening tips and guides!' ?></p>
    </div>
    <?php else: ?>
    <div class="blog-grid">
        <?php while ($post = $posts->fetch_assoc()): ?>
        <div style="position:relative">
            <?php if (isStaff()): ?>
            <!-- Staff/Admin overlay controls -->
            <div style="position:absolute;top:10px;right:10px;z-index:10;display:flex;gap:6px">
                <?php if (!$post['is_published']): ?>
                <span style="background:#e65100;color:white;font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px">DRAFT</span>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/admin/blog.php?action=edit&id=<?= $post['id'] ?>"
                   style="background:white;color:var(--green-mid);width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 2px 6px rgba(0,0,0,.15)"
                   title="Edit post">
                   <i class="fas fa-edit"></i>
                </a>
                <a href="<?= SITE_URL ?>/admin/blog.php?toggle=<?= $post['id'] ?>"
                   onclick="return confirm('Toggle publish status?')"
                   style="background:white;color:<?= $post['is_published'] ? '#e65100' : '#2e7d32' ?>;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 2px 6px rgba(0,0,0,.15)"
                   title="<?= $post['is_published'] ? 'Unpublish' : 'Publish' ?>">
                   <i class="fas fa-<?= $post['is_published'] ? 'eye-slash' : 'paper-plane' ?>"></i>
                </a>
                <?php if (isAdmin()): ?>
                <a href="<?= SITE_URL ?>/admin/blog.php?delete=<?= $post['id'] ?>"
                   onclick="return confirm('Permanently delete this post?')"
                   style="background:white;color:#c62828;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;box-shadow:0 2px 6px rgba(0,0,0,.15)"
                   title="Delete post">
                   <i class="fas fa-trash"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/blog_post.php?slug=<?= htmlspecialchars($post['slug']) ?>"
               class="blog-card <?= (!$post['is_published'] && isStaff()) ? 'draft-card' : '' ?>"
               style="display:block;color:inherit;text-decoration:none;<?= (!$post['is_published'] && isStaff()) ? 'opacity:.75' : '' ?>">
                <div class="blog-image">
                    <?php
                    $imgSrc = SITE_URL . '/assets/images/category-outdoor.jpg';
                    if (!empty($post['image'])) {
                        $imgPath = __DIR__ . '/uploads/blog/' . $post['image'];
                        if (file_exists($imgPath)) $imgSrc = SITE_URL . '/uploads/blog/' . htmlspecialchars($post['image']);
                    }
                    ?>
                    <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                </div>
                <div class="blog-body">
                    <div class="blog-cat"><?= strtoupper(str_replace('_',' ',$post['category'])) ?></div>
                    <h3><?= htmlspecialchars($post['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($post['excerpt']??'',0,120)) ?>…</p>
                </div>
                <div class="blog-footer">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($post['author'] ?? 'EcoSprout Team') ?></span>
                    <span><?= date('d M Y', strtotime($post['created_at'])) ?></span>
                </div>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
