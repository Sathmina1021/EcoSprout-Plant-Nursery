<?php
$pageTitle = 'Forgot Password';
require_once 'includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="flash-message flash-error" style="position:static;transform:none;margin-bottom:18px;"><i class="fas fa-times-circle"></i> Please enter a valid email address.</div>';
    } else {
        $message = '<div class="flash-message flash-info" style="position:static;transform:none;margin-bottom:18px;"><i class="fas fa-info-circle"></i> Password reset email sending is not enabled in this local demo. Use the demo admin login or create a new customer account.</div>';
    }
}
?>

<div class="page-hero" style="min-height:auto; padding:50px 24px;">
    <h1>Forgot Password</h1>
    <p>Local demo password reset notice</p>
</div>

<div style="padding:60px 24px; background:var(--cream);">
    <div class="form-container">
        <h2>Reset Password</h2>
        <p class="subtitle">This project runs locally, so email delivery is disabled by default.</p>
        <?= $message ?>
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="yourname@example.com" required>
            </div>
            <button type="submit" class="btn-submit"><i class="fas fa-envelope"></i> Check Account</button>
        </form>
        <div style="margin-top:22px; padding:16px; background:var(--cream); border:1px solid var(--cream-dark); border-radius:10px; font-size:13px; color:var(--text-mid);">
            Demo admin: <strong>admin@ecosprout.lk</strong> / <strong>Admin@123</strong>
        </div>
        <p class="auth-link"><a href="<?= SITE_URL ?>/login.php">Back to login</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
