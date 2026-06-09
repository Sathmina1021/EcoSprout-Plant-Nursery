<?php
$pageTitle = 'Create Account';
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) redirect('index.php');

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['full_name'] = trim($_POST['full_name'] ?? '');
    $formData['email']     = trim($_POST['email'] ?? '');
    $formData['phone']     = trim($_POST['phone'] ?? '');
    $formData['password']  = $_POST['password'] ?? '';
    $formData['confirm']   = $_POST['confirm_password'] ?? '';
    $formData['address']   = trim($_POST['address'] ?? '');

    // Validation
    if (empty($formData['full_name'])) $errors['full_name'] = 'Full name is required.';
    elseif (strlen($formData['full_name']) < 2) $errors['full_name'] = 'Name must be at least 2 characters.';

    if (empty($formData['email'])) $errors['email'] = 'Email address is required.';
    elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    else {
        // Check if email exists
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->bind_param("s", $formData['email']);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            $errors['email'] = 'This email address is already registered.';
        }
        $checkEmail->close();
    }

    if (empty($formData['password'])) $errors['password'] = 'Password is required.';
    elseif (strlen($formData['password']) < 8) $errors['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Z]/', $formData['password'])) $errors['password'] = 'Password must contain at least one uppercase letter.';
    elseif (!preg_match('/[0-9]/', $formData['password'])) $errors['password'] = 'Password must contain at least one number.';

    if ($formData['password'] !== $formData['confirm']) $errors['confirm'] = 'Passwords do not match.';

    if (empty($errors)) {
        $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, address, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
        $stmt->bind_param("sssss",
            $formData['full_name'], $formData['email'],
            $formData['phone'], $formData['address'], $hashedPassword
        );

        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            $_SESSION['user_id'] = $userId;
            $_SESSION['name']    = $formData['full_name'];
            $_SESSION['email']   = $formData['email'];
            $_SESSION['role']    = 'customer';
            flashMessage('success', 'Welcome to EcoSprout, ' . $formData['full_name'] . '! 🌱');
            redirect('index.php');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<div class="page-hero" style="min-height:auto; padding:50px 24px;">
    <h1>Join EcoSprout</h1>
    <p>Create your account to start exploring plants and gardening services</p>
</div>

<div style="padding:60px 24px; background:var(--cream);">
<div class="form-container" style="max-width:600px;">
    <h2>Create Your Account</h2>
    <p class="subtitle">Fill in your details to get started</p>

    <?php if (!empty($errors['general'])): ?>
    <div class="flash-message flash-error" style="position:static; transform:none; margin-bottom:20px;">
        <i class="fas fa-times-circle"></i> <?= htmlspecialchars($errors['general']) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="registerForm" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label>Full Name <span>*</span></label>
                <input type="text" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'error' : '' ?>"
                       placeholder="e.g. Kasun Perera" value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-control"
                       placeholder="e.g. 0771234567" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Email Address <span>*</span></label>
            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'error' : '' ?>"
                   placeholder="yourname@example.com" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
            <?php if (isset($errors['email'])): ?>
            <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" placeholder="Your delivery address" rows="2"><?= htmlspecialchars($formData['address'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Password <span>*</span></label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password" class="form-control <?= isset($errors['password']) ? 'error' : '' ?>"
                           placeholder="Min 8 chars, 1 uppercase, 1 number" required style="padding-right:42px;">
                    <button type="button" class="toggle-password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Confirm Password <span>*</span></label>
                <div style="position:relative;">
                    <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm']) ? 'error' : '' ?>"
                           placeholder="Repeat your password" required style="padding-right:42px;">
                    <button type="button" class="toggle-password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm'])): ?>
                <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errors['confirm']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Password strength indicator -->
        <div style="margin-top:-8px; margin-bottom:16px;">
            <div id="strengthBar" style="height:4px; border-radius:2px; background:var(--cream-dark); overflow:hidden; margin-bottom:4px;">
                <div id="strengthFill" style="height:100%; width:0; transition:all .3s; border-radius:2px;"></div>
            </div>
            <div id="strengthText" style="font-size:11px; color:var(--text-light);"></div>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-size:13px; color:var(--text-mid);">
                <input type="checkbox" name="agree_terms" required style="margin-top:2px; accent-color:var(--green-mid);">
                I agree to the <a href="#" style="color:var(--green-mid);">Terms of Service</a> and <a href="#" style="color:var(--green-mid);">Privacy Policy</a>
            </label>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fas fa-user-plus"></i> Create Account
        </button>
    </form>

    <p class="auth-link">Already have an account? <a href="<?= SITE_URL ?>/login.php">Sign in here</a></p>
</div>
</div>

<script>
// Password strength meter
document.getElementById('password').addEventListener('input', function() {
    const val = this.value;
    let strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    const colors = ['', '#e53935', '#fb8c00', '#fdd835', '#43a047'];
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    fill.style.width = (strength * 25) + '%';
    fill.style.background = colors[strength];
    text.textContent = strength > 0 ? 'Password strength: ' + labels[strength] : '';
    text.style.color = colors[strength];
});
</script>

<?php require_once 'includes/footer.php'; ?>
