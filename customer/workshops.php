<?php
$pageTitle = 'My Workshops';
require_once '../includes/header.php';

if (!isLoggedIn()) { redirect('login.php'); }
$userId = intval($_SESSION['user_id']);
$registrations = $conn->query("
    SELECT wr.*, w.title, w.description, w.workshop_date, w.start_time, w.end_time, w.location, w.instructor, w.price
    FROM workshop_registrations wr
    JOIN workshops w ON wr.workshop_id = w.id
    WHERE wr.user_id=$userId
    ORDER BY w.workshop_date DESC
");
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>My Workshops</h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/customer/dashboard.php">Dashboard</a><span>/</span>
        <span>Workshops</span>
    </div>
</div>

<div class="section">
    <?php if (!$registrations || $registrations->num_rows === 0): ?>
    <div class="empty-state">
        <i class="fas fa-calendar"></i>
        <h3>No workshop registrations yet</h3>
        <p>Join a gardening workshop and your registrations will appear here.</p>
        <a href="<?= SITE_URL ?>/workshops.php" class="btn-primary"><i class="fas fa-calendar-plus"></i> Browse Workshops</a>
    </div>
    <?php else: ?>
    <div class="workshops-grid">
        <?php while ($w = $registrations->fetch_assoc()): ?>
        <div class="workshop-card">
            <div class="workshop-date">
                <span><?= date('M', strtotime($w['workshop_date'])) ?></span>
                <strong><?= date('d', strtotime($w['workshop_date'])) ?></strong>
            </div>
            <div class="workshop-body">
                <span class="badge <?= $w['payment_status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst($w['payment_status']) ?></span>
                <h3><?= htmlspecialchars($w['title']) ?></h3>
                <p><?= htmlspecialchars(substr($w['description'] ?? '', 0, 130)) ?>...</p>
                <div class="workshop-meta">
                    <span><i class="fas fa-clock"></i> <?= date('g:i A', strtotime($w['start_time'])) ?> - <?= date('g:i A', strtotime($w['end_time'])) ?></span>
                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($w['location']) ?></span>
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($w['instructor']) ?></span>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
