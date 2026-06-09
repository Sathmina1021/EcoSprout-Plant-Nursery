<?php
require_once 'includes/config.php';

$slug = sanitize($conn, $_GET['slug'] ?? '');
if ($slug === '') {
    flashMessage('error', 'Blog post not found.');
    redirect('blog.php');
}

// Staff/admin can preview drafts; public can only see published
$pubFilter = isStaff() ? "" : "AND b.is_published=1";
$stmt = $conn->prepare("SELECT b.*, u.full_name as author FROM blog_posts b LEFT JOIN users u ON b.author_id=u.id WHERE b.slug=? $pubFilter LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    flashMessage('error', 'Blog post not found.');
    redirect('blog.php');
}

// Only count views for published posts from non-staff
if ($post['is_published'] && !isStaff()) {
    $conn->query('UPDATE blog_posts SET views = views + 1 WHERE id=' . intval($post['id']));
}

$pageTitle = $post['title'];
$related = $conn->query("SELECT title, slug, excerpt, created_at, image, category FROM blog_posts WHERE is_published=1 AND id<>" . intval($post['id']) . " ORDER BY created_at DESC LIMIT 3");
$catLabels = ['plant_care'=>'Plant Care','diy'=>'DIY','seasonal'=>'Seasonal','landscaping'=>'Landscaping','news'=>'News'];
require_once 'includes/header.php';

// Reading time estimate
$wordCount = str_word_count(strip_tags($post['content'] ?? $post['excerpt'] ?? ''));
$readMins  = max(1, round($wordCount / 200));
?>

<?php if (isStaff()): ?>
<!-- ── Admin/Staff Post Bar ──────────────────────────────────────────────────── -->
<div style="background:var(--green-dark);padding:10px 0;position:sticky;top:70px;z-index:90;box-shadow:0 2px 8px rgba(0,0,0,.2)">
    <div style="max-width:1200px;margin:0 auto;padding:0 24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <i class="fas fa-shield-alt" style="color:rgba(255,255,255,.5)"></i>
        <span style="color:rgba(255,255,255,.8);font-size:12px">
            <?= $post['is_published']
                ? '<span style="color:#81c784"><i class="fas fa-check-circle"></i> Published</span>'
                : '<span style="color:#ffcc80"><i class="fas fa-clock"></i> Draft — not visible to public</span>'
            ?>
        </span>
        <span style="color:rgba(255,255,255,.5);font-size:12px">•</span>
        <span style="color:rgba(255,255,255,.6);font-size:12px"><i class="fas fa-eye"></i> <?= number_format($post['views']) ?> views</span>
        <span style="color:rgba(255,255,255,.5);font-size:12px">•</span>
        <span style="color:rgba(255,255,255,.6);font-size:12px">Author: <?= htmlspecialchars($post['author'] ?? 'EcoSprout') ?></span>
        <div style="margin-left:auto;display:flex;gap:8px">
            <a href="<?= SITE_URL ?>/admin/blog.php?action=edit&id=<?= $post['id'] ?>"
               style="background:var(--green-light);color:white;padding:6px 14px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none">
               <i class="fas fa-edit"></i> Edit Post
            </a>
            <a href="<?= SITE_URL ?>/admin/blog.php?toggle=<?= $post['id'] ?>"
               onclick="return confirm('Toggle publish status?')"
               style="background:rgba(255,255,255,.1);color:white;padding:6px 14px;border-radius:6px;font-size:12px;text-decoration:none">
               <i class="fas fa-<?= $post['is_published'] ? 'eye-slash' : 'paper-plane' ?>"></i>
               <?= $post['is_published'] ? 'Unpublish' : 'Publish Now' ?>
            </a>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/blog.php?delete=<?= $post['id'] ?>"
               onclick="return confirm('Permanently delete this post?')"
               style="background:rgba(200,50,50,.3);color:#ffcdd2;padding:6px 14px;border-radius:6px;font-size:12px;text-decoration:none">
               <i class="fas fa-trash"></i> Delete
            </a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/admin/blog.php"
               style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);padding:6px 14px;border-radius:6px;font-size:12px;text-decoration:none">
               <i class="fas fa-list"></i> All Posts
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="page-hero" style="min-height:auto; padding:50px 24px;">
    <?php if (!$post['is_published'] && isStaff()): ?>
    <div style="background:#e65100;color:white;padding:4px 14px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;margin-bottom:12px;letter-spacing:.08em">DRAFT PREVIEW</div>
    <?php endif; ?>
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <p><?= htmlspecialchars($post['excerpt'] ?? '') ?></p>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/blog.php">Blog</a><span>/</span>
        <span><?= htmlspecialchars(substr($post['title'], 0, 40)) ?></span>
    </div>
</div>

<div class="section" style="max-width:900px; margin:0 auto;">
    <article style="background:white; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm);">
        <div style="height:320px; overflow:hidden; background:var(--cream-dark);">
            <?php
            $imgSrc = SITE_URL . '/assets/images/category-outdoor.jpg';
            if (!empty($post['image'])) {
                $imgPath = __DIR__ . '/uploads/blog/' . $post['image'];
                if (file_exists($imgPath)) $imgSrc = SITE_URL . '/uploads/blog/' . htmlspecialchars($post['image']);
            }
            ?>
            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div style="padding:32px;">
            <div style="display:flex; flex-wrap:wrap; gap:14px; color:var(--text-light); font-size:13px; margin-bottom:20px; align-items:center">
                <span style="background:var(--green-pale);color:var(--green-dark);padding:3px 12px;border-radius:20px;font-weight:600;font-size:12px">
                    <?= $catLabels[$post['category']] ?? ucfirst($post['category']) ?>
                </span>
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($post['author'] ?? 'EcoSprout Team') ?></span>
                <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($post['created_at'])) ?></span>
                <span><i class="fas fa-clock"></i> <?= $readMins ?> min read</span>
                <span><i class="fas fa-eye"></i> <?= number_format($post['views'] + ($post['is_published'] && !isStaff() ? 1 : 0)) ?> views</span>
            </div>
            <div style="font-size:15px; line-height:1.9; color:var(--text-dark);">
                <?= $post['content'] ?: nl2br(htmlspecialchars($post['excerpt'] ?? '')) ?>
            </div>
        </div>
    </article>

    <!-- Back to blog -->
    <div style="margin-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <a href="<?= SITE_URL ?>/blog.php" style="color:var(--green-mid);font-size:14px;display:flex;align-items:center;gap:6px">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>
        <?php if (isStaff()): ?>
        <a href="<?= SITE_URL ?>/admin/blog.php?action=edit&id=<?= $post['id'] ?>"
           style="background:var(--green-mid);color:white;padding:8px 18px;border-radius:8px;font-size:13px;text-decoration:none">
           <i class="fas fa-edit"></i> Edit This Post
        </a>
        <?php endif; ?>
    </div>

    <?php if ($related && $related->num_rows > 0): ?>
    <div style="margin-top:36px;">
        <h2 style="font-size:1.4rem; margin-bottom:18px;">More Gardening Tips</h2>
        <div class="blog-grid">
            <?php while ($r = $related->fetch_assoc()): ?>
            <a href="<?= SITE_URL ?>/blog_post.php?slug=<?= urlencode($r['slug']) ?>" class="blog-card" style="display:block; color:inherit; text-decoration:none;">
                <?php if (!empty($r['image'])): ?>
                <div class="blog-image" style="height:150px">
                    <img src="<?= SITE_URL ?>/uploads/blog/<?= htmlspecialchars($r['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.style.display='none'">
                </div>
                <?php endif; ?>
                <div class="blog-body">
                    <div class="blog-cat"><?= strtoupper(str_replace('_',' ',$r['category']??'')) ?></div>
                    <h3><?= htmlspecialchars($r['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($r['excerpt'] ?? '', 0, 100)) ?>…</p>
                </div>
                <div class="blog-footer">
                    <span>EcoSprout</span>
                    <span><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
