<?php
$pageTitle = 'Home';
require_once 'includes/header.php';

// Fetch featured plants
$featuredPlants = $conn->query("
    SELECT p.*, pc.name as category_name 
    FROM plants p 
    LEFT JOIN plant_categories pc ON p.category_id = pc.id 
    WHERE p.is_featured = 1 AND p.is_active = 1 
    LIMIT 8
");

// Fetch services
$services = $conn->query("SELECT * FROM services WHERE is_active = 1 LIMIT 4");

// Fetch upcoming workshops
$workshops = $conn->query("
    SELECT * FROM workshops 
    WHERE is_active = 1 AND workshop_date >= CURDATE() 
    ORDER BY workshop_date ASC LIMIT 3
");

// Fetch blog posts
$blogPosts = $conn->query("
    SELECT b.*, u.full_name as author_name 
    FROM blog_posts b 
    LEFT JOIN users u ON b.author_id = u.id 
    WHERE b.is_published = 1 
    ORDER BY b.created_at DESC LIMIT 3
");
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-pattern"></div>
    <div class="hero-image"></div>
    <div class="hero-content">
        <div class="hero-badge">🌱 Kegalle's Premier Plant Nursery</div>
        <h1>Grow Something <em>Beautiful</em> Today</h1>
        <p class="hero-desc">Discover hundreds of plants, professional landscaping services, and gardening workshops designed for every level of plant enthusiast in Sri Lanka.</p>
        <div class="hero-actions">
            <a href="<?= SITE_URL ?>/plants.php" class="btn-primary">
                <i class="fas fa-seedling"></i> Browse Plants
            </a>
            <a href="<?= SITE_URL ?>/workshops.php" class="btn-secondary">
                <i class="fas fa-calendar"></i> View Workshops
            </a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><span>500+</span><p>Plant Varieties</p></div>
            <div class="hero-stat"><span>1,200+</span><p>Happy Customers</p></div>
            <div class="hero-stat"><span>15+</span><p>Expert Staff</p></div>
        </div>
    </div>
</section>

<!-- SEARCH BAR -->
<div style="background:white; padding:24px; text-align:center; border-bottom:1px solid var(--cream-dark);">
    <form action="<?= SITE_URL ?>/search.php" method="GET" style="max-width:480px;margin:0 auto;">
        <div class="search-bar">
            <input type="text" name="q" placeholder="Search for plants, services, workshops..." 
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </div>
    </form>
</div>

<!-- PLANT CATEGORIES -->
<div class="section">
    <div class="section-header">
        <div class="section-tag">Shop by Category</div>
        <h2>Find Your Perfect Plant</h2>
        <p>From lush indoor specimens to vibrant garden varieties, we have something for every space and style.</p>
    </div>
    <div class="categories-grid">
        <a href="<?= SITE_URL ?>/plants.php?category=1" class="category-card">
            <img src="<?= SITE_URL ?>/assets/images/category-indoor.jpg" alt="Indoor Plants" loading="lazy">
            <div class="category-overlay"><h3>🏠 Indoor Plants</h3><p>For living spaces & offices</p></div>
        </a>
        <a href="<?= SITE_URL ?>/plants.php?category=2" class="category-card">
            <img src="<?= SITE_URL ?>/assets/images/category-outdoor.jpg" alt="Outdoor Plants" loading="lazy">
            <div class="category-overlay"><h3>🌳 Outdoor Plants</h3><p>Hardy garden varieties</p></div>
        </a>
        <a href="<?= SITE_URL ?>/plants.php?category=3" class="category-card">
            <img src="<?= SITE_URL ?>/assets/images/category-ornamental.jpg" alt="Ornamental Plants" loading="lazy">
            <div class="category-overlay"><h3>🌺 Ornamental</h3><p>Flowers & decorative species</p></div>
        </a>
        <a href="<?= SITE_URL ?>/plants.php?category=4" class="category-card">
            <img src="<?= SITE_URL ?>/assets/images/category-edible.jpg" alt="Edible Plants" loading="lazy">
            <div class="category-overlay"><h3>🥦 Edible Plants</h3><p>Herbs, vegetables & fruits</p></div>
        </a>
    </div>
</div>

<!-- FEATURED PLANTS -->
<div style="background:white; padding:20px 0 60px;">
<div class="section" style="padding-top:60px;">
    <div class="section-header">
        <div class="section-tag">Featured</div>
        <h2>Popular Plants</h2>
        <p>Our most loved plants, hand-selected by our expert horticulturalists.</p>
    </div>
    <div class="plants-grid">
        <?php while ($plant = $featuredPlants->fetch_assoc()): ?>
        <div class="plant-card">
            <div class="plant-card-image">
                <img src="<?= $plant['image'] && $plant['image'] !== 'default_plant.jpg' 
                    ? SITE_URL.'/uploads/plants/'.htmlspecialchars($plant['image'])
                    : SITE_URL.'/uploads/plants/default_plant.jpg' ?>"
                     alt="<?= htmlspecialchars($plant['name']) ?>" loading="lazy">
                <?php if ($plant['category_name']): ?>
                    <span class="plant-badge"><?= htmlspecialchars($plant['category_name']) ?></span>
                <?php endif; ?>
            </div>
            <div class="plant-card-body">
                <div class="plant-name"><?= htmlspecialchars($plant['name']) ?></div>
                <div class="plant-botanical"><?= htmlspecialchars($plant['botanical_name'] ?? '') ?></div>
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
                </div>
                <div class="plant-footer">
                    <div class="plant-price">
                        <?= formatPrice($plant['price']) ?>
                        <small> / plant</small>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <a href="<?= SITE_URL ?>/plant_detail.php?id=<?= $plant['id'] ?>" class="btn-view">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if (isLoggedIn()): ?>
                        <button class="btn-cart" data-id="<?= $plant['id'] ?>" data-type="plant">
                            <i class="fas fa-shopping-basket"></i> Add
                        </button>
                        <?php else: ?>
                        <a href="<?= SITE_URL ?>/login.php" class="btn-cart" style="text-decoration:none;">
                            <i class="fas fa-shopping-basket"></i> Add
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <div style="text-align:center; margin-top:36px;">
        <a href="<?= SITE_URL ?>/plants.php" class="btn-primary">
            View All Plants <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
</div>

<!-- SERVICES -->
<div class="section">
    <div class="section-header">
        <div class="section-tag">Our Services</div>
        <h2>Professional Gardening Services</h2>
        <p>From garden design to regular maintenance, our expert team transforms your outdoor spaces.</p>
    </div>
    <div class="services-grid">
        <?php
        $serviceIcons = ['🏡', '✂️', '🔬', '🪴', '💧'];
        $i = 0;
        while ($service = $services->fetch_assoc()):
        ?>
        <div class="service-card">
            <div class="service-icon"><?= $serviceIcons[$i % count($serviceIcons)] ?></div>
            <h3><?= htmlspecialchars($service['name']) ?></h3>
            <p><?= htmlspecialchars(substr($service['description'] ?? '', 0, 100)) ?>...</p>
            <div class="service-price">
                <?= formatPrice($service['price']) ?>
                <?php if ($service['duration']): ?>
                    <small style="font-weight:400;color:var(--text-light);"> · <?= htmlspecialchars($service['duration']) ?></small>
                <?php endif; ?>
            </div>
            <div style="margin-top:14px;">
                <a href="<?= SITE_URL ?>/service_detail.php?id=<?= $service['id'] ?>" class="btn-view" style="display:inline-flex;">
                    Learn More <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php $i++; endwhile; ?>
    </div>
</div>

<!-- WORKSHOPS -->
<div style="background:var(--green-dark); padding:80px 0;">
<div class="section" style="padding-top:0; padding-bottom:0;">
    <div class="section-header" style="color:white;">
        <div class="section-tag" style="background:rgba(255,255,255,.15); color:var(--green-pale);">Learn & Grow</div>
        <h2 style="color:white;">Upcoming Workshops</h2>
        <p style="color:rgba(255,255,255,.7);">Join our expert-led workshops and deepen your knowledge of plants and gardening.</p>
    </div>
    <div class="workshops-grid">
        <?php while ($workshop = $workshops->fetch_assoc()): ?>
        <div class="workshop-card">
            <div class="workshop-date-banner">
                <i class="fas fa-calendar-alt"></i>
                <?= date('D, d M Y', strtotime($workshop['workshop_date'])) ?>
                · <?= date('g:i A', strtotime($workshop['start_time'])) ?>
            </div>
            <div class="workshop-body">
                <h3><?= htmlspecialchars($workshop['title']) ?></h3>
                <p><?= htmlspecialchars(substr($workshop['description'] ?? '', 0, 110)) ?>...</p>
                <div class="workshop-meta">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($workshop['instructor']) ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($workshop['location']) ?></span>
                </div>
                <div class="workshop-footer">
                    <div>
                        <div class="workshop-price"><?= $workshop['price'] > 0 ? formatPrice($workshop['price']) : 'Free' ?></div>
                        <div class="spots-left"><?= $workshop['max_participants'] - $workshop['current_participants'] ?> spots left</div>
                    </div>
                    <a href="<?= SITE_URL ?>/workshop_detail.php?id=<?= $workshop['id'] ?>" class="btn-primary" style="padding:8px 16px; font-size:13px;">
                        Register
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <div style="text-align:center; margin-top:36px;">
        <a href="<?= SITE_URL ?>/workshops.php" class="btn-secondary">
            View All Workshops <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
</div>

<!-- BLOG -->
<div class="section">
    <div class="section-header">
        <div class="section-tag">Gardening Tips</div>
        <h2>From Our Blog</h2>
        <p>Expert advice, seasonal guides, and DIY projects to help your garden thrive.</p>
    </div>
    <div class="blog-grid">
        <?php while ($post = $blogPosts->fetch_assoc()): ?>
        <a href="<?= SITE_URL ?>/blog_post.php?slug=<?= htmlspecialchars($post['slug']) ?>" class="blog-card" style="display:block;color:inherit;">
            <div class="blog-image">
                <img src="<?= !empty($post['image']) ? SITE_URL . '/uploads/blog/' . htmlspecialchars($post['image']) : SITE_URL . '/uploads/blog/blog-indoor-plants.jpg' ?>"
                     alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
            </div>
            <div class="blog-body">
                <div class="blog-cat"><?= str_replace('_', ' ', ucfirst($post['category'])) ?></div>
                <h3><?= htmlspecialchars($post['title']) ?></h3>
                <p><?= htmlspecialchars(substr($post['excerpt'] ?? '', 0, 100)) ?>...</p>
            </div>
            <div class="blog-footer">
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($post['author_name'] ?? 'EcoSprout') ?></span>
                <span><?= date('d M Y', strtotime($post['created_at'])) ?></span>
            </div>
        </a>
        <?php endwhile; ?>
    </div>
</div>


<!-- GALLERY -->
<div style="background:white; padding:20px 0 70px;">
<div class="section" style="padding-top:40px; padding-bottom:0;">
    <div class="section-header">
        <div class="section-tag">Photo Gallery</div>
        <h2>Fresh Nursery Gallery</h2>
        <p>Real plant photos are bundled with the project, so the frontend works offline without broken image links.</p>
    </div>
    <div class="gallery-grid">
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/monstera.jpg" alt="Monstera plant">
            <h4>Monstera</h4>
        </div>
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/snake-plant.jpg" alt="Snake plant">
            <h4>Snake Plant</h4>
        </div>
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/peace-lily.jpg" alt="Peace lily plant">
            <h4>Peace Lily</h4>
        </div>
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/anthurium.jpg" alt="Anthurium plant">
            <h4>Anthurium</h4>
        </div>
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/hibiscus.jpg" alt="Hibiscus flower">
            <h4>Hibiscus</h4>
        </div>
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/bougainvillea.jpg" alt="Bougainvillea bonsai">
            <h4>Bougainvillea</h4>
        </div>
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/curry-leaf.jpg" alt="Curry leaf plant">
            <h4>Curry Leaf</h4>
        </div>
        <div class="gallery-card">
            <img src="<?= SITE_URL ?>/uploads/plants/chilli.jpg" alt="Chilli plant">
            <h4>Chilli</h4>
        </div>
    </div>
</div>
</div>

<!-- CTA BANNER -->
<div style="background:linear-gradient(135deg,var(--green-mid),var(--green-dark)); padding:64px 24px; text-align:center;">
    <div style="max-width:560px; margin:0 auto;">
        <div class="section-tag" style="background:rgba(255,255,255,.15); color:var(--green-pale);">Get Started</div>
        <h2 style="color:white; font-size:2rem; margin:12px 0;">Ready to Start Your Green Journey?</h2>
        <p style="color:rgba(255,255,255,.75); margin-bottom:28px;">Create a free account to browse our catalogue, make purchases, and register for workshops.</p>
        <?php if (!isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/register.php" class="btn-primary" style="font-size:16px; padding:16px 32px;">
            <i class="fas fa-user-plus"></i> Create Free Account
        </a>
        <?php else: ?>
        <a href="<?= SITE_URL ?>/plants.php" class="btn-primary" style="font-size:16px; padding:16px 32px;">
            <i class="fas fa-seedling"></i> Shop Now
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
