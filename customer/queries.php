<?php
$pageTitle = 'My Queries';
require_once '../includes/header.php';

if (!isLoggedIn()) { redirect('login.php'); }
$userId = intval($_SESSION['user_id']);
$queries = $conn->query("SELECT * FROM queries WHERE user_id=$userId ORDER BY created_at DESC");
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>My Queries</h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/customer/dashboard.php">Dashboard</a><span>/</span>
        <span>Queries</span>
    </div>
</div>

<div class="section" style="max-width:950px; margin:0 auto;">
    <?php if (!$queries || $queries->num_rows === 0): ?>
    <div class="empty-state">
        <i class="fas fa-question-circle"></i>
        <h3>No queries yet</h3>
        <p>Ask us about plants, orders, services, or workshops.</p>
        <a href="<?= SITE_URL ?>/contact.php" class="btn-primary"><i class="fas fa-envelope"></i> Contact Us</a>
    </div>
    <?php else: ?>
    <div style="background:white; border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm);">
        <table class="data-table">
            <thead><tr><th>Subject</th><th>Category</th><th>Status</th><th>Submitted</th><th>Response</th></tr></thead>
            <tbody>
                <?php while ($q = $queries->fetch_assoc()):
                    $badge = match($q['status']) {
                        'resolved' => 'badge-success',
                        'closed' => 'badge-default',
                        'in_progress' => 'badge-warning',
                        default => 'badge-info'
                    };
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($q['subject']) ?></strong><br><small style="color:var(--text-light);"><?= htmlspecialchars(substr($q['message'], 0, 80)) ?>...</small></td>
                    <td><?= str_replace('_', ' ', ucfirst($q['category'])) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= str_replace('_', ' ', ucfirst($q['status'])) ?></span></td>
                    <td><?= date('d M Y', strtotime($q['created_at'])) ?></td>
                    <td><?= $q['response'] ? nl2br(htmlspecialchars($q['response'])) : '<span style="color:var(--text-light);">Pending</span>' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
