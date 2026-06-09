<?php
require_once 'includes/config.php';
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    flashMessage('error', 'Service not found.');
    redirect('services.php');
}
$service = $conn->query("SELECT * FROM services WHERE id=$id AND is_active=1")->fetch_assoc();
if (!$service) {
    flashMessage('error', 'Service not found.');
    redirect('services.php');
}
$pageTitle = $service['name'];
require_once 'includes/header.php';
?>

<div class="page-hero" style="min-height:auto; padding:50px 24px;">
    <h1><?= htmlspecialchars($service['name']) ?></h1>
    <p>Professional EcoSprout gardening service</p>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/services.php">Services</a><span>/</span>
        <span><?= htmlspecialchars($service['name']) ?></span>
    </div>
</div>

<div class="section">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:42px; align-items:start;">
        <div style="border-radius:var(--radius-lg); overflow:hidden; background:var(--cream-dark); aspect-ratio:4/3;">
            <img src="<?= !empty($service['image']) ? SITE_URL . '/uploads/services/' . htmlspecialchars($service['image']) : '<?= SITE_URL ?>/uploads/services/landscape-design.jpg' ?>"
                 alt="<?= htmlspecialchars($service['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div>
            <div class="section-tag">Gardening Service</div>
            <h2 style="margin:10px 0 14px;"><?= htmlspecialchars($service['name']) ?></h2>
            <p style="color:var(--text-mid); line-height:1.8; margin-bottom:24px;"><?= nl2br(htmlspecialchars($service['description'] ?? '')) ?></p>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:26px;">
                <div style="background:white; border-radius:var(--radius); padding:18px; box-shadow:var(--shadow-sm);">
                    <div style="font-size:12px; color:var(--text-light); text-transform:uppercase; font-weight:700;">Starting Price</div>
                    <div style="font-size:1.4rem; color:var(--green-dark); font-family:'Playfair Display',serif; font-weight:700;"><?= formatPrice($service['price']) ?></div>
                </div>
                <div style="background:white; border-radius:var(--radius); padding:18px; box-shadow:var(--shadow-sm);">
                    <div style="font-size:12px; color:var(--text-light); text-transform:uppercase; font-weight:700;">Duration</div>
                    <div style="font-size:1rem; color:var(--green-dark); font-weight:700;"><?= htmlspecialchars($service['duration'] ?: 'Custom') ?></div>
                </div>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="<?= SITE_URL ?>/contact.php?category=service&subject=<?= urlencode('Enquiry about ' . $service['name']) ?>" class="btn-primary">
                    <i class="fas fa-calendar-check"></i> Book This Service
                </a>
                <a href="<?= SITE_URL ?>/services.php" class="btn-secondary" style="border-color:var(--green-mid); color:var(--green-mid);">
                    <i class="fas fa-arrow-left"></i> Back to Services
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
