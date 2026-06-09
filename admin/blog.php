<?php
$pageTitle = 'Manage Blog';
require_once '../includes/config.php';

if (!isStaff()) { flashMessage('error','Access denied.'); redirect('login.php'); }

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$message = '';

// ── Handle POST (add / edit) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = sanitize($conn, $_POST['title']    ?? '');
    $excerpt   = sanitize($conn, $_POST['excerpt']  ?? '');
    $content   = $_POST['content']  ?? '';   // allow HTML
    $category  = sanitize($conn, $_POST['category'] ?? 'plant_care');
    $is_pub    = intval($_POST['is_published'] ?? 0);

    // Auto-generate slug from title
    $slug_base = strtolower(trim(preg_replace('/[^a-z0-9]+/i','-', $title),'-'));
    $slug      = sanitize($conn, $_POST['slug'] !== '' ? $_POST['slug'] : $slug_base);

    // Image upload
    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $imageName = uniqid('blog_') . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/blog/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if ($action === 'add') {
        if (empty($title)) {
            $message = '<div class="alert-error"><i class="fas fa-times-circle"></i> Title is required.</div>';
        } else {
            $author_id = intval($_SESSION['user_id'] ?? 1);
            $img = $imageName ?: '';
            $stmt = $conn->prepare("INSERT INTO blog_posts (author_id,title,slug,excerpt,content,image,category,is_published) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issssssi", $author_id,$title,$slug,$excerpt,$content,$img,$category,$is_pub);
            if ($stmt->execute()) {
                flashMessage('success', "Post \"$title\" created successfully!");
                header('Location: '.SITE_URL.'/admin/blog.php'); exit();
            } else {
                $message = '<div class="alert-error"><i class="fas fa-times-circle"></i> Error: slug may already exist. Try a different title.</div>';
            }
            $stmt->close();
        }
    }

    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $existing = $conn->query("SELECT image FROM blog_posts WHERE id=$id")->fetch_assoc();
        $img = $imageName ?: ($existing['image'] ?? '');
        $stmt = $conn->prepare("UPDATE blog_posts SET title=?,slug=?,excerpt=?,content=?,image=?,category=?,is_published=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssssssii", $title,$slug,$excerpt,$content,$img,$category,$is_pub,$id);
        if ($stmt->execute()) {
            flashMessage('success', 'Post updated successfully!');
            header('Location: '.SITE_URL.'/admin/blog.php'); exit();
        }
        $stmt->close();
    }
}

// ── Quick toggle publish ───────────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = intval($_GET['toggle']);
    $cur = $conn->query("SELECT is_published FROM blog_posts WHERE id=$id")->fetch_assoc()['is_published'] ?? 0;
    $new = $cur ? 0 : 1;
    $conn->query("UPDATE blog_posts SET is_published=$new WHERE id=$id");
    flashMessage('success', $new ? 'Post published.' : 'Post unpublished.');
    header('Location: '.SITE_URL.'/admin/blog.php'); exit();
}

// ── Delete (admin only) ────────────────────────────────────────────────────────
if (isset($_GET['delete']) && isAdmin()) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM blog_posts WHERE id=$id");
    flashMessage('success', 'Post deleted.');
    header('Location: '.SITE_URL.'/admin/blog.php'); exit();
}

// ── Load post for editing ──────────────────────────────────────────────────────
$editPost = null;
if (($action === 'edit') && isset($_GET['id'])) {
    $editPost = $conn->query("SELECT * FROM blog_posts WHERE id=".intval($_GET['id']))->fetch_assoc();
    if (!$editPost) { flashMessage('error','Post not found.'); header('Location: '.SITE_URL.'/admin/blog.php'); exit(); }
}

// ── Blog stats ─────────────────────────────────────────────────────────────────
$totalPosts     = $conn->query("SELECT COUNT(*) c FROM blog_posts")->fetch_assoc()['c'];
$publishedPosts = $conn->query("SELECT COUNT(*) c FROM blog_posts WHERE is_published=1")->fetch_assoc()['c'];
$draftPosts     = $conn->query("SELECT COUNT(*) c FROM blog_posts WHERE is_published=0")->fetch_assoc()['c'];
$totalViews     = $conn->query("SELECT COALESCE(SUM(views),0) c FROM blog_posts")->fetch_assoc()['c'];

// ── Filter & list ──────────────────────────────────────────────────────────────
$filterCat = sanitize($conn, $_GET['cat'] ?? '');
$filterPub = $_GET['pub'] ?? '';
$search    = sanitize($conn, $_GET['q'] ?? '');
$where = "1=1";
if ($filterCat) $where .= " AND b.category='$filterCat'";
if ($filterPub !== '') $where .= " AND b.is_published=" . intval($filterPub);
if ($search) $where .= " AND (b.title LIKE '%$search%' OR b.excerpt LIKE '%$search%')";
$posts = $conn->query("SELECT b.*, u.full_name as author FROM blog_posts b LEFT JOIN users u ON b.author_id=u.id WHERE $where ORDER BY b.created_at DESC");

$catLabels = ['plant_care'=>'Plant Care','diy'=>'DIY','seasonal'=>'Seasonal','landscaping'=>'Landscaping','news'=>'News'];
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
    <style>
    body{margin:0}
    .admin-wrap{display:flex;min-height:100vh}
    .adm-sidebar{width:220px;background:var(--green-dark);position:fixed;top:0;left:0;bottom:0;z-index:100;overflow-y:auto;display:flex;flex-direction:column}
    .adm-sidebar .logo{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
    .adm-sidebar .logo .logo-text{color:white;font-size:16px}
    .adm-nav a{display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:rgba(255,255,255,.7)}
    .adm-nav a:hover,.adm-nav a.active{background:rgba(255,255,255,.1);color:white}
    .adm-nav a i{width:16px}
    .adm-main{margin-left:220px;flex:1;background:var(--cream)}
    .adm-topbar{background:white;padding:14px 24px;border-bottom:1px solid var(--cream-dark);display:flex;align-items:center;justify-content:space-between}
    .adm-content{padding:24px}
    .stat-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
    .stat-card{background:white;border-radius:var(--radius);padding:18px 20px;box-shadow:var(--shadow-sm);border-left:4px solid var(--green-light)}
    .stat-card .num{font-size:28px;font-weight:700;color:var(--green-dark);font-family:'DM Sans',sans-serif}
    .stat-card .lbl{font-size:12px;color:var(--text-light);margin-top:2px}
    .toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
    .toolbar input[type=text]{padding:8px 14px;border:1px solid var(--cream-dark);border-radius:8px;font-size:13px;background:white;flex:1;min-width:180px}
    .toolbar select{padding:8px 12px;border:1px solid var(--cream-dark);border-radius:8px;font-size:13px;background:white}
    .table-wrap{background:white;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden}
    table.blog-table{width:100%;border-collapse:collapse}
    .blog-table th{background:var(--green-dark);color:white;font-size:12px;font-weight:500;padding:11px 14px;text-align:left}
    .blog-table td{padding:11px 14px;font-size:13px;border-bottom:1px solid var(--cream-dark);vertical-align:middle}
    .blog-table tr:last-child td{border-bottom:none}
    .blog-table tr:hover td{background:#fafaf8}
    .badge-pub{background:#e8f5e9;color:#2e7d32;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600}
    .badge-draft{background:#fff8e1;color:#e65100;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600}
    .badge-cat{background:var(--green-pale);color:var(--green-dark);padding:3px 9px;border-radius:20px;font-size:11px}
    .action-btns{display:flex;gap:6px;align-items:center}
    .btn-sm{padding:5px 11px;border-radius:6px;font-size:12px;font-weight:500;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:4px;text-decoration:none}
    .btn-edit{background:#e3f2fd;color:#1565c0}
    .btn-edit:hover{background:#bbdefb}
    .btn-view{background:#e8f5e9;color:#2e7d32}
    .btn-view:hover{background:#c8e6c9}
    .btn-toggle-pub{background:#fff8e1;color:#f57c00}
    .btn-toggle-pub:hover{background:#ffe0b2}
    .btn-del{background:#ffebee;color:#c62828}
    .btn-del:hover{background:#ffcdd2}
    .form-card{background:white;border-radius:var(--radius-lg);padding:28px;box-shadow:var(--shadow-sm);max-width:860px}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    .form-group{display:flex;flex-direction:column;gap:6px}
    .form-group.full{grid-column:1/-1}
    .form-group label{font-size:13px;font-weight:600;color:var(--text-dark)}
    .form-group input,.form-group select,.form-group textarea{padding:9px 13px;border:1px solid var(--cream-dark);border-radius:8px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:white;transition:border .2s}
    .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--green-light)}
    .form-group textarea{resize:vertical;min-height:90px}
    #contentArea{min-height:300px;font-family:'DM Sans',sans-serif;font-size:14px}
    .editor-toolbar{display:flex;gap:4px;padding:8px;background:#f5f5f5;border:1px solid var(--cream-dark);border-bottom:none;border-radius:8px 8px 0 0;flex-wrap:wrap}
    .editor-toolbar button{padding:4px 10px;border:1px solid #ddd;border-radius:5px;background:white;font-size:12px;cursor:pointer;color:var(--text-dark)}
    .editor-toolbar button:hover{background:#e8f5e9;border-color:var(--green-light)}
    #contentArea{border:1px solid var(--cream-dark);border-radius:0 0 8px 8px;padding:14px;min-height:320px;outline:none;line-height:1.7}
    .preview-img{max-width:120px;max-height:70px;border-radius:6px;object-fit:cover;border:1px solid var(--cream-dark)}
    .alert-error{background:#ffebee;color:#c62828;border:1px solid #ef9a9a;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:13px}
    .toggle-switch{position:relative;width:44px;height:24px;display:inline-block}
    .toggle-switch input{opacity:0;width:0;height:0}
    .toggle-slider{position:absolute;inset:0;background:#ddd;border-radius:24px;cursor:pointer;transition:.2s}
    .toggle-slider:before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;background:white;border-radius:50%;transition:.2s}
    input:checked + .toggle-slider{background:var(--green-mid)}
    input:checked + .toggle-slider:before{transform:translateX(20px)}
    .pub-row{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f0faf4;border-radius:8px;border:1px solid #c8e6c9}
    .thumb-img{width:48px;height:36px;object-fit:cover;border-radius:5px;border:1px solid var(--cream-dark)}
    </style>
</head>
<body>
<?php $flash = getFlash(); if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg" style="z-index:9999">
    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($flash['message']) ?>
    <button onclick="document.getElementById('flashMsg').remove()" class="flash-close">&times;</button>
</div>
<?php endif; ?>
<div class="admin-wrap">
    <!-- Sidebar -->
    <aside class="adm-sidebar">
        <div class="logo">
            <div style="display:flex;align-items:center;gap:8px"><span>🌿</span><span class="logo-text">Eco<strong>Sprout</strong></span></div>
            <div style="font-size:11px;color:rgba(255,255,255,.5);margin-top:3px"><?= isAdmin() ? 'Admin' : 'Staff' ?> Panel</div>
        </div>
        <nav class="adm-nav" style="padding:10px 0">
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= SITE_URL ?>/admin/users.php"><i class="fas fa-users"></i> Users</a>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/staff/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/admin/plants.php"><i class="fas fa-seedling"></i> Plants</a>
            <a href="<?= SITE_URL ?>/admin/orders.php"><i class="fas fa-box"></i> Orders</a>
            <a href="<?= SITE_URL ?>/admin/workshops.php"><i class="fas fa-calendar-alt"></i> Workshops</a>
            <a href="<?= SITE_URL ?>/admin/queries.php"><i class="fas fa-question-circle"></i> Queries</a>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/services.php"><i class="fas fa-tools"></i> Services</a>
            <a href="<?= SITE_URL ?>/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/admin/blog.php" class="active"><i class="fas fa-blog"></i> Blog Posts</a>
            <a href="<?= SITE_URL ?>/index.php"><i class="fas fa-globe"></i> View Site</a>
            <a href="<?= SITE_URL ?>/logout.php" style="color:rgba(255,100,100,.8)"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="adm-main">
        <div class="adm-topbar">
            <h2 style="margin:0;font-size:1.1rem"><i class="fas fa-blog" style="color:var(--green-mid);margin-right:8px"></i>
                <?= $action==='add' ? 'New Blog Post' : ($action==='edit' ? 'Edit Blog Post' : 'Blog Posts') ?>
            </h2>
            <div style="display:flex;align-items:center;gap:12px">
                <span style="font-size:13px;color:var(--text-mid)"><?= htmlspecialchars($_SESSION['name'] ?? '') ?></span>
                <?php if ($action === 'list'): ?>
                <a href="?action=add" class="btn-primary" style="font-size:13px;padding:7px 16px"><i class="fas fa-plus"></i> New Post</a>
                <?php else: ?>
                <a href="<?= SITE_URL ?>/admin/blog.php" class="btn-view" style="font-size:13px;padding:7px 16px"><i class="fas fa-arrow-left"></i> Back to list</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="adm-content">
        <?= $message ?>

        <?php if ($action === 'list'): ?>
        <!-- ── STATS ─────────────────────────────────────────────────────── -->
        <div class="stat-cards">
            <div class="stat-card"><div class="num"><?= $totalPosts ?></div><div class="lbl">Total Posts</div></div>
            <div class="stat-card" style="border-color:#2e7d32"><div class="num" style="color:#2e7d32"><?= $publishedPosts ?></div><div class="lbl">Published</div></div>
            <div class="stat-card" style="border-color:#e65100"><div class="num" style="color:#e65100"><?= $draftPosts ?></div><div class="lbl">Drafts</div></div>
            <div class="stat-card" style="border-color:var(--gold)"><div class="num" style="color:var(--brown)"><?= number_format($totalViews) ?></div><div class="lbl">Total Views</div></div>
        </div>

        <!-- ── TOOLBAR ───────────────────────────────────────────────────── -->
        <form method="GET" action="">
            <div class="toolbar">
                <input type="text" name="q" placeholder="Search posts…" value="<?= htmlspecialchars($search) ?>">
                <select name="cat" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($catLabels as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $filterCat===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="pub" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="1" <?= $filterPub==='1'?'selected':'' ?>>Published</option>
                    <option value="0" <?= $filterPub==='0'?'selected':'' ?>>Drafts</option>
                </select>
                <button type="submit" class="btn-primary" style="padding:8px 16px;font-size:13px"><i class="fas fa-search"></i> Search</button>
                <?php if ($search || $filterCat || $filterPub !== ''): ?>
                <a href="<?= SITE_URL ?>/admin/blog.php" class="btn-view" style="padding:8px 14px;font-size:13px">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- ── TABLE ─────────────────────────────────────────────────────── -->
        <div class="table-wrap">
            <?php if ($posts->num_rows === 0): ?>
            <div style="padding:48px;text-align:center;color:var(--text-light)">
                <i class="fas fa-blog" style="font-size:40px;opacity:.3;margin-bottom:12px;display:block"></i>
                <p>No posts found. <a href="?action=add" style="color:var(--green-mid)">Create the first post →</a></p>
            </div>
            <?php else: ?>
            <table class="blog-table">
                <thead>
                    <tr>
                        <th style="width:50px">Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($p = $posts->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($p['image']): ?>
                        <img src="<?= SITE_URL ?>/uploads/blog/<?= htmlspecialchars($p['image']) ?>" alt="" class="thumb-img" onerror="this.style.display='none'">
                        <?php else: ?>
                        <div style="width:48px;height:36px;background:var(--cream-dark);border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:16px">🌿</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:13px;color:var(--text-dark);max-width:260px"><?= htmlspecialchars($p['title']) ?></div>
                        <div style="font-size:11px;color:var(--text-light);margin-top:2px">/<?= htmlspecialchars($p['slug']) ?></div>
                    </td>
                    <td><span class="badge-cat"><?= $catLabels[$p['category']] ?? ucfirst($p['category']) ?></span></td>
                    <td style="font-size:12px;color:var(--text-mid)"><?= htmlspecialchars($p['author'] ?? 'EcoSprout') ?></td>
                    <td>
                        <?php if ($p['is_published']): ?>
                        <span class="badge-pub"><i class="fas fa-check-circle"></i> Published</span>
                        <?php else: ?>
                        <span class="badge-draft"><i class="fas fa-clock"></i> Draft</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;color:var(--text-mid)"><?= number_format($p['views']) ?></td>
                    <td style="font-size:12px;color:var(--text-light)"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="?action=edit&id=<?= $p['id'] ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Edit</a>
                            <a href="<?= SITE_URL ?>/blog_post.php?slug=<?= urlencode($p['slug']) ?>" target="_blank" class="btn-sm btn-view"><i class="fas fa-eye"></i></a>
                            <a href="?toggle=<?= $p['id'] ?>" class="btn-sm btn-toggle-pub" onclick="return confirm('Toggle publish status?')" title="<?= $p['is_published'] ? 'Unpublish' : 'Publish' ?>">
                                <i class="fas fa-<?= $p['is_published'] ? 'eye-slash' : 'paper-plane' ?>"></i>
                            </a>
                            <?php if (isAdmin()): ?>
                            <a href="?delete=<?= $p['id'] ?>" class="btn-sm btn-del" onclick="return confirm('Delete this post permanently?')"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- ── ADD / EDIT FORM ───────────────────────────────────────────── -->
        <div class="form-card">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $action ?>">
                <?php if ($editPost): ?><input type="hidden" name="id" value="<?= $editPost['id'] ?>"><?php endif; ?>

                <div class="form-grid">
                    <!-- Title -->
                    <div class="form-group full">
                        <label for="title">Post Title <span style="color:red">*</span></label>
                        <input type="text" id="title" name="title" required placeholder="E.g. 10 Best Indoor Plants for Sri Lankan Homes"
                               value="<?= htmlspecialchars($editPost['title'] ?? '') ?>"
                               oninput="autoSlug(this.value)">
                    </div>

                    <!-- Slug -->
                    <div class="form-group full">
                        <label for="slug">URL Slug</label>
                        <input type="text" id="slug" name="slug" placeholder="auto-generated-from-title"
                               value="<?= htmlspecialchars($editPost['slug'] ?? '') ?>">
                        <span style="font-size:11px;color:var(--text-light)">Leave blank to auto-generate from title</span>
                    </div>

                    <!-- Excerpt -->
                    <div class="form-group full">
                        <label for="excerpt">Excerpt / Short Description</label>
                        <textarea id="excerpt" name="excerpt" rows="2" placeholder="Brief summary shown on the blog listing page…"><?= htmlspecialchars($editPost['excerpt'] ?? '') ?></textarea>
                    </div>

                    <!-- Category -->
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <?php foreach ($catLabels as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($editPost['category']??'plant_care')===$k?'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Featured Image -->
                    <div class="form-group">
                        <label for="image">Featured Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if (!empty($editPost['image'])): ?>
                        <div style="margin-top:6px;display:flex;align-items:center;gap:8px">
                            <img src="<?= SITE_URL ?>/uploads/blog/<?= htmlspecialchars($editPost['image']) ?>" class="preview-img" onerror="this.style.display='none'">
                            <span style="font-size:11px;color:var(--text-light)">Current image (upload to replace)</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Content editor -->
                    <div class="form-group full">
                        <label>Content</label>
                        <div class="editor-toolbar">
                            <button type="button" onclick="fmt('bold')"><i class="fas fa-bold"></i></button>
                            <button type="button" onclick="fmt('italic')"><i class="fas fa-italic"></i></button>
                            <button type="button" onclick="fmt('underline')"><i class="fas fa-underline"></i></button>
                            <button type="button" onclick="fmtBlock('h2')">H2</button>
                            <button type="button" onclick="fmtBlock('h3')">H3</button>
                            <button type="button" onclick="fmt('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
                            <button type="button" onclick="fmt('insertOrderedList')"><i class="fas fa-list-ol"></i></button>
                            <button type="button" onclick="insertLink()"><i class="fas fa-link"></i></button>
                            <button type="button" onclick="fmt('removeFormat')"><i class="fas fa-remove-format"></i></button>
                        </div>
                        <div id="contentArea" contenteditable="true"><?= $editPost['content'] ?? '' ?></div>
                        <input type="hidden" name="content" id="contentHidden">
                    </div>

                    <!-- Publish toggle -->
                    <div class="form-group full">
                        <div class="pub-row">
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_published" value="1" id="pubToggle"
                                       <?= ($editPost['is_published'] ?? 0) ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--green-dark)" id="pubLabel">
                                    <?= ($editPost['is_published'] ?? 0) ? 'Published — visible to visitors' : 'Draft — not visible to visitors' ?>
                                </div>
                                <div style="font-size:12px;color:var(--text-light)">Toggle to publish or save as draft</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:24px">
                    <button type="submit" class="btn-primary" style="padding:10px 28px">
                        <i class="fas fa-save"></i> <?= $action==='add' ? 'Create Post' : 'Save Changes' ?>
                    </button>
                    <a href="<?= SITE_URL ?>/admin/blog.php" class="btn-view" style="padding:10px 20px">Cancel</a>
                    <?php if ($editPost && $editPost['is_published']): ?>
                    <a href="<?= SITE_URL ?>/blog_post.php?slug=<?= urlencode($editPost['slug']) ?>" target="_blank" class="btn-view" style="padding:10px 20px;margin-left:auto">
                        <i class="fas fa-external-link-alt"></i> View Live Post
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<script>
function autoSlug(title) {
    const slugField = document.getElementById('slug');
    if (slugField && slugField.dataset.manual !== 'true') {
        slugField.value = title.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    }
}
document.getElementById('slug')?.addEventListener('input', function() {
    this.dataset.manual = 'true';
});

function fmt(cmd) { document.execCommand(cmd, false, null); }
function fmtBlock(tag) { document.execCommand('formatBlock', false, tag); }
function insertLink() {
    const url = prompt('Enter URL:');
    if (url) document.execCommand('createLink', false, url);
}

// Sync contenteditable to hidden input on submit
document.querySelector('form')?.addEventListener('submit', function() {
    document.getElementById('contentHidden').value = document.getElementById('contentArea')?.innerHTML || '';
});

// Publish toggle label
document.getElementById('pubToggle')?.addEventListener('change', function() {
    document.getElementById('pubLabel').textContent = this.checked
        ? 'Published — visible to visitors'
        : 'Draft — not visible to visitors';
});
</script>
<script>window.ECOSPROUT_SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
