<?php
$pageTitle = 'Edit Profile';
require_once '../includes/header.php';

if (!isLoggedIn()) { redirect('../login.php'); }
$userId = $_SESSION['user_id'];
$user   = $conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'profile') {
        $fullName = sanitize($conn, $_POST['full_name'] ?? '');
        $phone    = sanitize($conn, $_POST['phone'] ?? '');
        $address  = sanitize($conn, $_POST['address'] ?? '');

        if (empty($fullName)) $errors['full_name'] = 'Name is required.';

        if (empty($errors)) {
            $conn->query("UPDATE users SET full_name='$fullName', phone='$phone', address='$address' WHERE id=$userId");
            $_SESSION['name'] = $fullName;
            $user['full_name'] = $fullName;
            $user['phone']     = $phone;
            $user['address']   = $address;
            flashMessage('success', 'Profile updated successfully!');
            header('Location: profile.php'); exit();
        }
    }

    if ($action === 'password') {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $errors['current'] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors['new'] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors['confirm'] = 'Passwords do not match.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hashed' WHERE id=$userId");
            flashMessage('success', 'Password changed successfully!');
            header('Location: profile.php'); exit();
        }
    }
}
?>

<div class="page-hero" style="min-height:auto; padding:40px 24px;">
    <h1>Edit Profile</h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="dashboard.php">Dashboard</a><span>/</span>
        <span>Profile</span>
    </div>
</div>

<div class="section" style="max-width:800px; margin:0 auto;">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">

        <!-- Profile Info -->
        <div style="background:white; border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-sm);">
            <h3 style="margin-bottom:20px; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-user" style="color:var(--green-light);"></i> Personal Information
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="profile">
                <div class="form-group">
                    <label>Full Name <span style="color:var(--error);">*</span></label>
                    <input type="text" name="full_name" class="form-control <?= isset($errors['full_name'])?'error':'' ?>"
                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    <?php if (isset($errors['full_name'])): ?><div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['full_name'] ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background:var(--cream);">
                    <div class="form-hint">Email cannot be changed.</div>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="e.g. 0771234567">
                </div>
                <div class="form-group">
                    <label>Delivery Address</label>
                    <textarea name="address" class="form-control" rows="3" placeholder="Your default delivery address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-submit" style="padding:12px;">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div style="background:white; border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-sm);">
            <h3 style="margin-bottom:20px; font-size:1.1rem; display:flex; align-items:center; gap:8px;">
                <i class="fas fa-lock" style="color:var(--green-light);"></i> Change Password
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control <?= isset($errors['current'])?'error':'' ?>" required>
                    <?php if (isset($errors['current'])): ?><div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['current'] ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control <?= isset($errors['new'])?'error':'' ?>" required>
                    <?php if (isset($errors['new'])): ?><div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['new'] ?></div><?php endif; ?>
                    <div class="form-hint">Min 8 characters</div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm'])?'error':'' ?>" required>
                    <?php if (isset($errors['confirm'])): ?><div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['confirm'] ?></div><?php endif; ?>
                </div>
                <button type="submit" class="btn-submit" style="padding:12px; background:var(--brown);">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
