<?php
$pageTitle = 'Gardening Services';
require_once 'includes/header.php';

$services = $conn->query("SELECT * FROM services WHERE is_active=1 ORDER BY name ASC");
$serviceIcons = ['🏡','✂️','🔬','🪴','💧','🌻','🎨','🌿'];
?>

<div class="page-hero">
    <h1>🏡 Gardening Services</h1>
    <p>Professional landscaping and garden care by our expert team</p>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span><span>Services</span>
    </div>
</div>

<div class="section">
    <div class="section-header">
        <div class="section-tag">What We Offer</div>
        <h2>Expert Gardening Services</h2>
        <p>From one-off garden makeovers to regular maintenance contracts, we cover all your gardening needs.</p>
    </div>

    <div class="services-grid">
        <?php $i=0; while ($s = $services->fetch_assoc()): ?>
        <div class="service-card">
            <div class="service-icon"><?= $serviceIcons[$i % count($serviceIcons)] ?></div>
            <h3><?= htmlspecialchars($s['name']) ?></h3>
            <p><?= htmlspecialchars($s['description'] ?? '') ?></p>
            <?php if ($s['duration']): ?>
            <p style="font-size:12px; color:var(--text-light); margin-bottom:8px;">
                <i class="fas fa-clock"></i> Duration: <?= htmlspecialchars($s['duration']) ?>
            </p>
            <?php endif; ?>
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:14px;">
                <div class="service-price"><?= formatPrice($s['price']) ?></div>
                <div style="display:flex; gap:8px;">
                    <a href="<?= SITE_URL ?>/service_detail.php?id=<?= $s['id'] ?>" class="btn-view" style="padding:8px 12px; font-size:13px;">Details</a>
                    <a href="<?= SITE_URL ?>/contact.php?category=service&subject=<?= urlencode('Enquiry about '.$s['name']) ?>"
                       class="btn-primary" style="padding:8px 16px; font-size:13px;">
                        Book Now
                    </a>
                </div>
            </div>
        </div>
        <?php $i++; endwhile; ?>
    </div>

    <!-- CTA -->
    <div style="background:var(--green-pale); border-radius:var(--radius-lg); padding:40px; text-align:center; margin-top:48px;">
        <h3 style="font-size:1.4rem; margin-bottom:10px;">Need a Custom Service?</h3>
        <p style="color:var(--text-mid); margin-bottom:20px;">Can't find exactly what you need? Contact us for a bespoke quote tailored to your garden.</p>
        <a href="<?= SITE_URL ?>/contact.php?category=service" class="btn-primary">
            <i class="fas fa-envelope"></i> Get a Free Quote
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
