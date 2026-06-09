<?php
require_once 'includes/config.php';
$id = intval($_GET['id'] ?? 0);
if (!$id) { redirect('plants.php'); }

$plant = $conn->query("
    SELECT p.*, pc.name as cat_name
    FROM plants p LEFT JOIN plant_categories pc ON p.category_id=pc.id
    WHERE p.id=$id AND p.is_active=1
")->fetch_assoc();

if (!$plant) { flashMessage('error','Plant not found.'); redirect('plants.php'); }

$pageTitle = $plant['name'];

// Related plants
$related = $conn->query("
    SELECT * FROM plants
    WHERE category_id={$plant['category_id']} AND id != $id AND is_active=1
    LIMIT 4
");

require_once 'includes/header.php';
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1><?= htmlspecialchars($plant['name']) ?></h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/plants.php">Plants</a><span>/</span>
        <?php if ($plant['cat_name']): ?>
        <a href="<?= SITE_URL ?>/plants.php?category=<?= $plant['category_id'] ?>"><?= htmlspecialchars($plant['cat_name']) ?></a><span>/</span>
        <?php endif; ?>
        <span><?= htmlspecialchars($plant['name']) ?></span>
    </div>
</div>

<div class="section">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:48px; align-items:start;">
        <!-- Image -->
        <div>
            <div style="border-radius:var(--radius-lg); overflow:hidden; aspect-ratio:4/3; background:var(--cream-dark);">
                <img src="<?= $plant['image'] && $plant['image'] !== 'default_plant.jpg'
                    ? SITE_URL.'/uploads/plants/'.htmlspecialchars($plant['image'])
                    : SITE_URL.'/uploads/plants/default_plant.jpg' ?>"
                     alt="<?= htmlspecialchars($plant['name']) ?>"
                     style="width:100%; height:100%; object-fit:cover;">
            </div>
        </div>

        <!-- Details -->
        <div>
            <?php if ($plant['cat_name']): ?>
            <div class="section-tag" style="margin-bottom:10px;"><?= htmlspecialchars($plant['cat_name']) ?></div>
            <?php endif; ?>
            <h1 style="font-size:2rem; margin-bottom:6px;"><?= htmlspecialchars($plant['name']) ?></h1>
            <?php if ($plant['botanical_name']): ?>
            <p style="font-style:italic; color:var(--text-light); margin-bottom:16px; font-size:15px;"><?= htmlspecialchars($plant['botanical_name']) ?></p>
            <?php endif; ?>
            <p style="color:var(--text-mid); line-height:1.8; font-size:15px; margin-bottom:24px;"><?= htmlspecialchars($plant['description'] ?? '') ?></p>

            <!-- Care Info Cards -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:24px;">
                <div style="background:var(--cream); border-radius:10px; padding:14px; display:flex; align-items:center; gap:10px;">
                    <span style="font-size:24px;">☀️</span>
                    <div>
                        <div style="font-size:11px; font-weight:600; color:var(--text-light); text-transform:uppercase;">Sunlight</div>
                        <div style="font-size:13px; font-weight:500;"><?= str_replace('_',' ',ucfirst($plant['sunlight_requirement'])) ?></div>
                    </div>
                </div>
                <div style="background:var(--cream); border-radius:10px; padding:14px; display:flex; align-items:center; gap:10px;">
                    <span style="font-size:24px;">💧</span>
                    <div>
                        <div style="font-size:11px; font-weight:600; color:var(--text-light); text-transform:uppercase;">Watering</div>
                        <div style="font-size:13px; font-weight:500;"><?= str_replace('_',' ',ucfirst($plant['water_frequency'])) ?></div>
                    </div>
                </div>
                <div style="background:var(--cream); border-radius:10px; padding:14px; display:flex; align-items:center; gap:10px;">
                    <span style="font-size:24px;">📦</span>
                    <div>
                        <div style="font-size:11px; font-weight:600; color:var(--text-light); text-transform:uppercase;">Stock</div>
                        <div style="font-size:13px; font-weight:500; color:<?= $plant['stock_quantity']>0?'var(--success)':'var(--error)' ?>;">
                            <?= $plant['stock_quantity']>0 ? $plant['stock_quantity'].' available' : 'Out of Stock' ?>
                        </div>
                    </div>
                </div>
                <div style="background:var(--cream); border-radius:10px; padding:14px; display:flex; align-items:center; gap:10px;">
                    <span style="font-size:24px;">🏷️</span>
                    <div>
                        <div style="font-size:11px; font-weight:600; color:var(--text-light); text-transform:uppercase;">Price</div>
                        <div style="font-size:16px; font-weight:700; color:var(--green-dark); font-family:'Playfair Display',serif;"><?= formatPrice($plant['price']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Add to Cart -->
            <?php if ($plant['stock_quantity'] > 0): ?>
            <div style="display:flex; gap:12px; align-items:center; margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:0; border:1.5px solid var(--cream-dark); border-radius:10px; overflow:hidden;">
                    <button id="qtyDec" style="width:40px; height:44px; border:none; background:var(--cream); cursor:pointer; font-size:18px;">−</button>
                    <input type="number" id="qtyInput" value="1" min="1" max="<?= $plant['stock_quantity'] ?>" 
                           style="width:56px; height:44px; border:none; text-align:center; font-size:15px; font-weight:600; font-family:'DM Sans',sans-serif;">
                    <button id="qtyInc" style="width:40px; height:44px; border:none; background:var(--cream); cursor:pointer; font-size:18px;">+</button>
                </div>
                <?php if (isLoggedIn()): ?>
                <button id="addToCartBtn" class="btn-primary" style="flex:1; justify-content:center;"
                        data-id="<?= $plant['id'] ?>" data-type="plant">
                    <i class="fas fa-shopping-basket"></i> Add to Cart
                </button>
                <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn-primary" style="flex:1; justify-content:center; text-decoration:none;">
                    <i class="fas fa-sign-in-alt"></i> Login to Purchase
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div style="padding:14px; background:#fff3e0; border-radius:10px; color:var(--warning); font-size:14px; margin-bottom:20px;">
                <i class="fas fa-exclamation-triangle"></i> Currently out of stock. Check back soon!
            </div>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/contact.php?subject=<?= urlencode('Query about '.$plant['name']) ?>" 
               class="btn-view" style="display:inline-flex;">
                <i class="fas fa-question-circle"></i> Ask About This Plant
            </a>
        </div>
    </div>

    <!-- Care Instructions -->
    <?php if ($plant['care_instructions']): ?>
    <div style="background:var(--green-pale); border-radius:var(--radius-lg); padding:28px; margin-top:40px;">
        <h3 style="margin-bottom:12px; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-leaf" style="color:var(--green-mid);"></i> Care Instructions
        </h3>
        <p style="color:var(--text-dark); line-height:1.8;"><?= nl2br(htmlspecialchars($plant['care_instructions'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- Related Plants -->
    <?php if ($related->num_rows > 0): ?>
    <div style="margin-top:56px;">
        <h2 style="margin-bottom:24px;">You Might Also Like</h2>
        <div class="plants-grid">
            <?php while ($rp = $related->fetch_assoc()): ?>
            <div class="plant-card">
                <div class="plant-card-image">
                    <img src="<?= $rp['image'] && $rp['image'] !== 'default_plant.jpg'
                        ? SITE_URL.'/uploads/plants/'.htmlspecialchars($rp['image'])
                        : SITE_URL.'/uploads/plants/default_plant.jpg' ?>"
                         alt="<?= htmlspecialchars($rp['name']) ?>" loading="lazy">
                </div>
                <div class="plant-card-body">
                    <div class="plant-name"><?= htmlspecialchars($rp['name']) ?></div>
                    <div class="plant-footer">
                        <div class="plant-price"><?= formatPrice($rp['price']) ?></div>
                        <a href="<?= SITE_URL ?>/plant_detail.php?id=<?= $rp['id'] ?>" class="btn-view">View</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const qtyInput = document.getElementById('qtyInput');
const maxQty   = <?= $plant['stock_quantity'] ?>;

document.getElementById('qtyDec')?.addEventListener('click', () => {
    qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1);
});
document.getElementById('qtyInc')?.addEventListener('click', () => {
    qtyInput.value = Math.min(maxQty, parseInt(qtyInput.value) + 1);
});

document.getElementById('addToCartBtn')?.addEventListener('click', async function() {
    const qty    = parseInt(qtyInput.value);
    const itemId = this.dataset.id;
    const orig   = this.innerHTML;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    this.disabled  = true;

    try {
        const res = await fetch((window.ECOSPROUT_SITE_URL || '').replace(/\/$/, '') + '/cart_action.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `action=add&item_id=${itemId}&item_type=plant&quantity=${qty}`
        });
        const data = await res.json();
        if (data.success) {
            this.innerHTML = '<i class="fas fa-check"></i> Added to Cart!';
            this.style.background = 'var(--success)';
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = data.cart_count;
        } else {
            this.innerHTML = '<i class="fas fa-times"></i> ' + (data.message || 'Error');
            this.style.background = 'var(--error)';
        }
    } catch { this.innerHTML = '<i class="fas fa-times"></i> Error'; }

    setTimeout(() => {
        this.innerHTML = orig;
        this.style.background = '';
        this.disabled = false;
    }, 2500);
});
</script>

<?php require_once 'includes/footer.php'; ?>
