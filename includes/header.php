<?php
require_once __DIR__ . '/config.php';
$cartCount = isLoggedIn() ? getCartCount($conn, $_SESSION['user_id']) : 0;
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | EcoSprout' : 'EcoSprout – Plant Nursery & Gardening Services' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
    <?= isset($extraCSS) ? $extraCSS : '' ?>
    <script>window.ECOSPROUT_SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
</head>
<body>

<!-- Flash Message -->
<?php if ($flash): ?>
<div class="flash-message flash-<?= $flash['type'] ?>" id="flashMsg">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'times-circle' : 'info-circle') ?>"></i>
    <?= htmlspecialchars($flash['message']) ?>
    <button onclick="document.getElementById('flashMsg').remove()" class="flash-close">&times;</button>
</div>
<?php endif; ?>

<!-- Navigation -->
<nav class="navbar" id="mainNav">
    <div class="nav-container">
        <a href="<?= SITE_URL ?>/index.php" class="nav-logo">
            <span class="logo-icon">🌿</span>
            <span class="logo-text">Eco<strong>Sprout</strong></span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
            <li><a href="<?= SITE_URL ?>/plants.php">Plants</a></li>
            <li><a href="<?= SITE_URL ?>/services.php">Services</a></li>
            <li><a href="<?= SITE_URL ?>/workshops.php">Workshops</a></li>
            <li><a href="<?= SITE_URL ?>/blog.php">Blog</a></li>
            <li><a href="<?= SITE_URL ?>/contact.php">Contact</a></li>
        </ul>

        <div class="nav-actions">
            <a href="<?= SITE_URL ?>/search.php" class="nav-icon" title="Search"><i class="fas fa-search"></i></a>

            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/cart.php" class="nav-icon cart-icon" title="Cart">
                    <i class="fas fa-shopping-basket"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="nav-user-menu">
                    <button class="nav-avatar" id="userMenuBtn">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="<?= SITE_URL ?>/customer/dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a>
                        <a href="<?= SITE_URL ?>/customer/orders.php"><i class="fas fa-box"></i> My Orders</a>
                        <a href="<?= SITE_URL ?>/customer/profile.php"><i class="fas fa-user"></i> Profile</a>
                        <?php if (isStaff()): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= SITE_URL ?>/staff/dashboard.php"><i class="fas fa-tools"></i> Staff Panel</a>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                            <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn-nav-login">Login</a>
                <a href="<?= SITE_URL ?>/register.php" class="btn-nav-register">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="main-content">
