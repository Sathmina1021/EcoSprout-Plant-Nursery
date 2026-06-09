<?php
$pageTitle = 'Login';
require_once 'includes/config.php';

if (isLoggedIn()) redirect('index.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password, role, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'No account found with this email address.';
        } elseif (!$user['is_active']) {
            $error = 'Your account has been deactivated. Please contact support.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Incorrect password. Please try again.';
        } else {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['full_name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            flashMessage('success', 'Welcome back, ' . $user['full_name'] . '! 🌿');

            // Redirect based on role
            $redirect = $_GET['redirect'] ?? '';
            if ($redirect) {
                $basePath = parse_url(SITE_URL, PHP_URL_PATH) ?: '';
                $redirectPath = parse_url($redirect, PHP_URL_PATH) ?: '';
                if ($redirectPath && ($basePath === '' || strpos($redirectPath, $basePath . '/') === 0 || $redirectPath === $basePath)) {
                    header("Location: $redirect"); exit();
                }
            }
            if ($user['role'] === 'admin') redirect('admin/dashboard.php');
            elseif ($user['role'] === 'staff') redirect('staff/dashboard.php');
            else redirect('customer/dashboard.php');
        }
    }
}

require_once 'includes/header.php';
?>

<div class="page-hero" style="min-height:auto; padding:50px 24px;">
    <h1>Welcome Back</h1>
    <p>Sign in to your EcoSprout account</p>
</div>

<div style="padding:60px 24px; background:var(--cream);">
<div class="form-container">
    <h2>Sign In</h2>
    <p class="subtitle">Enter your credentials to access your account</p>

    <?php if ($error): ?>
    <div class="flash-message flash-error" style="position:static; transform:none; margin-bottom:20px;">
        <i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
    <div class="flash-message flash-success" style="position:static; transform:none; margin-bottom:20px;">
        <i class="fas fa-check-circle"></i> Account created successfully! Please log in.
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email Address <span>*</span></label>
            <input type="email" name="email" class="form-control" placeholder="yourname@example.com"
                   value="<?= htmlspecialchars($email) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label style="display:flex; justify-content:space-between;">
                Password <span>*</span>
                <a href="<?= SITE_URL ?>/forgot_password.php" style="font-size:12px; color:var(--green-mid); font-weight:400;">Forgot password?</a>
            </label>
            <div style="position:relative;">
                <input type="password" name="password" class="form-control" placeholder="Your password" required style="padding-right:42px;">
                <button type="button" class="toggle-password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; color:var(--text-mid);">
                <input type="checkbox" name="remember" style="accent-color:var(--green-mid);">
                Remember me for 30 days
            </label>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
    </form>

    <p class="auth-link">Don't have an account? <a href="<?= SITE_URL ?>/register.php">Create one free</a></p>

    <!-- Demo accounts info -->
    <div style="margin-top:24px; padding:16px; background:var(--cream); border-radius:10px; border:1px solid var(--cream-dark);">
        <p style="font-size:12px; font-weight:600; color:var(--text-mid); margin-bottom:8px;">📋 Demo Login Accounts (for testing):</p>
        <p style="font-size:12px; color:var(--text-light); margin-bottom:4px;"><strong>Admin:</strong> admin@ecosprout.lk / Admin@123</p>
        <p style="font-size:12px; color:var(--text-light); margin-bottom:4px;"><strong>Staff:</strong> staff@ecosprout.lk / Staff@123</p>
        <p style="font-size:12px; color:var(--text-light); margin-bottom:0;"><strong>User:</strong> user@ecosprout.lk / User@123</p>
    </div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
