<?php
$pageTitle = 'Workshops & Events';
require_once 'includes/header.php';

$workshops = $conn->query("
    SELECT * FROM workshops 
    WHERE is_active = 1 AND workshop_date >= CURDATE()
    ORDER BY workshop_date ASC
");
$past = $conn->query("
    SELECT * FROM workshops 
    WHERE is_active = 1 AND workshop_date < CURDATE()
    ORDER BY workshop_date DESC LIMIT 3
");
?>

<div class="page-hero">
    <h1>🌱 Workshops & Events</h1>
    <p>Learn from our expert gardeners — from beginner basics to advanced techniques</p>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span><span>Workshops</span>
    </div>
</div>

<div class="section">
    <div class="section-header">
        <div class="section-tag">Upcoming Events</div>
        <h2>Join Our Next Workshop</h2>
        <p>Hands-on learning experiences for plant lovers of all skill levels.</p>
    </div>

    <?php if ($workshops->num_rows === 0): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h3>No upcoming workshops</h3>
        <p>Check back soon — new workshops are added regularly!</p>
    </div>
    <?php else: ?>
    <div class="workshops-grid">
        <?php while ($w = $workshops->fetch_assoc()):
            $spotsLeft = $w['max_participants'] - $w['current_participants'];
            $isFull = $spotsLeft <= 0;

            // Check if user is already registered
            $isRegistered = false;
            if (isLoggedIn()) {
                $check = $conn->query("SELECT id FROM workshop_registrations WHERE workshop_id={$w['id']} AND user_id={$_SESSION['user_id']}");
                $isRegistered = $check->num_rows > 0;
            }
        ?>
        <div class="workshop-card" style="<?= $isFull ? 'opacity:.8;' : '' ?>">
            <div class="workshop-date-banner" style="<?= $isFull ? 'background:var(--text-mid);' : '' ?>">
                <i class="fas fa-calendar-alt"></i>
                <?= date('D, d M Y', strtotime($w['workshop_date'])) ?>
                <?php if ($isFull): ?><span style="margin-left:auto; font-size:11px; background:rgba(255,255,255,.2); padding:2px 8px; border-radius:20px;">FULL</span><?php endif; ?>
            </div>
            <div class="workshop-body">
                <h3><?= htmlspecialchars($w['title']) ?></h3>
                <p><?= htmlspecialchars(substr($w['description'] ?? '', 0, 120)) ?>...</p>
                <div class="workshop-meta">
                    <span><i class="fas fa-user-tie"></i> <?= htmlspecialchars($w['instructor']) ?></span>
                    <span><i class="fas fa-clock"></i> <?= date('g:i A', strtotime($w['start_time'])) ?> – <?= date('g:i A', strtotime($w['end_time'])) ?></span>
                </div>
                <div class="workshop-meta">
                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($w['location']) ?></span>
                    <span><i class="fas fa-users"></i> <?= $isFull ? 'Fully Booked' : "$spotsLeft spots left" ?></span>
                </div>

                <!-- Spots progress bar -->
                <div style="background:var(--cream-dark); border-radius:4px; height:6px; margin:12px 0; overflow:hidden;">
                    <div style="height:100%; border-radius:4px; width:<?= min(100, ($w['current_participants']/$w['max_participants'])*100) ?>%; background:<?= $isFull ? 'var(--error)' : 'var(--green-light)' ?>; transition:width .3s;"></div>
                </div>

                <div class="workshop-footer">
                    <div>
                        <div class="workshop-price"><?= $w['price'] > 0 ? formatPrice($w['price']) : 'FREE' ?></div>
                        <div class="spots-left" style="font-size:11px;"><?= $w['max_participants'] ?> total seats</div>
                    </div>
                    <?php if ($isRegistered): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Registered</span>
                    <?php elseif ($isFull): ?>
                        <span class="badge badge-error">Fully Booked</span>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/workshop_detail.php?id=<?= $w['id'] ?>" class="btn-primary" style="padding:8px 16px; font-size:13px;">
                            Register Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
