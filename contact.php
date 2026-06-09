<?php
$pageTitle = 'Contact Us';
require_once 'includes/header.php';

$errors   = [];
$success  = false;
$formData = [
    'category' => sanitize($conn, $_GET['category'] ?? 'general'),
    'subject'  => trim($_GET['subject'] ?? '')
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name']     = trim($_POST['name'] ?? '');
    $formData['email']    = trim($_POST['email'] ?? '');
    $formData['phone']    = trim($_POST['phone'] ?? '');
    $formData['subject']  = trim($_POST['subject'] ?? '');
    $formData['message']  = trim($_POST['message'] ?? '');
    $formData['category'] = sanitize($conn, $_POST['category'] ?? 'general');

    if (empty($formData['name']))    $errors['name']    = 'Your name is required.';
    if (empty($formData['email']))   $errors['email']   = 'Email address is required.';
    elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
    if (empty($formData['subject'])) $errors['subject'] = 'Subject is required.';
    if (empty($formData['message'])) $errors['message'] = 'Message cannot be empty.';
    elseif (strlen($formData['message']) < 20) $errors['message'] = 'Message must be at least 20 characters.';

    $validCategories = ['plant_care','order','service','workshop','general'];
    if (!in_array($formData['category'], $validCategories)) $formData['category'] = 'general';

    if (empty($errors)) {
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $stmt = $conn->prepare("INSERT INTO queries (user_id, name, email, phone, subject, message, category) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss",
            $userId, $formData['name'], $formData['email'],
            $formData['phone'], $formData['subject'],
            $formData['message'], $formData['category']
        );
        if ($stmt->execute()) {
            $success = true;
            $formData = [];
        } else {
            $errors['general'] = 'Submission failed. Please try again.';
        }
        $stmt->close();
    }
}
?>

<div class="page-hero">
    <h1>Get In Touch</h1>
    <p>We'd love to hear from you — plant questions, service enquiries, or just to say hello!</p>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span><span>Contact</span>
    </div>
</div>

<div class="section">
    <div style="display:grid; grid-template-columns:1fr 1.4fr; gap:48px; align-items:start;">
        <!-- Contact Info -->
        <div>
            <div class="section-tag">Contact Information</div>
            <h2 style="margin:10px 0 16px;">Visit Our Nursery</h2>
            <p style="color:var(--text-mid); margin-bottom:32px; line-height:1.7;">
                Come visit us at our nursery in Kegalle or reach out through any of the channels below. Our gardening experts are ready to help.
            </p>

            <?php
            $contactItems = [
                ['fas fa-map-marker-alt', 'Our Location',  'No. 42, Kandy Road, Kegalle, Sri Lanka'],
                ['fas fa-phone',          'Call Us',        '+94 35 222 3456'],
                ['fas fa-envelope',       'Email Us',       'info@ecosprout.lk'],
                ['fas fa-clock',          'Opening Hours',  'Mon–Sat: 8:00 AM – 6:00 PM<br>Sunday: 9:00 AM – 4:00 PM'],
                ['fab fa-whatsapp',       'WhatsApp',       '+94 77 123 4567'],
            ];
            foreach ($contactItems as [$icon, $label, $value]):
            ?>
            <div style="display:flex; gap:16px; margin-bottom:22px; align-items:flex-start;">
                <div style="width:44px; height:44px; border-radius:12px; background:var(--green-pale); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="<?= $icon ?>" style="color:var(--green-mid); font-size:16px;"></i>
                </div>
                <div>
                    <div style="font-size:12px; font-weight:600; color:var(--text-light); text-transform:uppercase; letter-spacing:.06em; margin-bottom:2px;"><?= $label ?></div>
                    <div style="font-size:14px; color:var(--text-dark);"><?= $value ?></div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Map placeholder -->
            <div style="margin-top:28px; border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--cream-dark);">
                <div style="background:var(--cream-dark); height:200px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px;">
                    <i class="fas fa-map" style="font-size:36px; color:var(--green-light);"></i>
                    <p style="font-size:13px; color:var(--text-light);">Kegalle, Western Province, Sri Lanka</p>
                    <a href="https://maps.google.com/?q=Kegalle,Sri+Lanka" target="_blank" class="btn-view" style="font-size:12px; display:inline-flex; margin-top:4px;">
                        <i class="fas fa-external-link-alt"></i> Open in Google Maps
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div>
            <?php if ($success): ?>
            <div style="background:white; border-radius:var(--radius-lg); padding:48px; text-align:center; box-shadow:var(--shadow-sm);">
                <div style="font-size:56px; margin-bottom:16px;">🌱</div>
                <h3 style="font-size:1.5rem; margin-bottom:10px;">Query Submitted!</h3>
                <p style="color:var(--text-mid); margin-bottom:24px; line-height:1.7;">
                    Thank you for reaching out! Our team will review your query and respond within 24 hours.
                </p>
                <a href="<?= SITE_URL ?>/contact.php" class="btn-primary">Send Another Message</a>
            </div>
            <?php else: ?>
            <div style="background:white; border-radius:var(--radius-lg); padding:36px; box-shadow:var(--shadow-sm);">
                <h3 style="font-size:1.3rem; margin-bottom:6px;">Send Us a Message</h3>
                <p style="color:var(--text-light); font-size:14px; margin-bottom:24px;">We typically respond within 24 business hours.</p>

                <?php if (!empty($errors['general'])): ?>
                <div class="flash-message flash-error" style="position:static;transform:none;margin-bottom:16px;">
                    <i class="fas fa-times-circle"></i> <?= htmlspecialchars($errors['general']) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Your Name <span style="color:var(--error)">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name'])?'error':'' ?>"
                                   placeholder="Full name"
                                   value="<?= htmlspecialchars($formData['name'] ?? (isLoggedIn() ? $_SESSION['name'] : '')) ?>">
                            <?php if (isset($errors['name'])): ?>
                            <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" class="form-control"
                                   placeholder="e.g. 0771234567"
                                   value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email Address <span style="color:var(--error)">*</span></label>
                        <input type="email" name="email" class="form-control <?= isset($errors['email'])?'error':'' ?>"
                               placeholder="yourname@example.com"
                               value="<?= htmlspecialchars($formData['email'] ?? (isLoggedIn() ? $_SESSION['email'] : '')) ?>">
                        <?php if (isset($errors['email'])): ?>
                        <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['email'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Query Category</label>
                        <select name="category" class="form-control">
                            <?php foreach(['plant_care'=>'🌿 Plant Care','order'=>'📦 Order Enquiry','service'=>'🏡 Gardening Service','workshop'=>'📚 Workshop','general'=>'💬 General Enquiry'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($formData['category']??'general')===$val?'selected':'' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Subject <span style="color:var(--error)">*</span></label>
                        <input type="text" name="subject" class="form-control <?= isset($errors['subject'])?'error':'' ?>"
                               placeholder="Brief description of your query"
                               value="<?= htmlspecialchars($formData['subject'] ?? '') ?>">
                        <?php if (isset($errors['subject'])): ?>
                        <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['subject'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Message <span style="color:var(--error)">*</span></label>
                        <textarea name="message" class="form-control <?= isset($errors['message'])?'error':'' ?>"
                                  rows="5" placeholder="Describe your query in detail..."><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                        <div class="form-error"><i class="fas fa-exclamation-circle"></i> <?= $errors['message'] ?></div>
                        <?php endif; ?>
                        <div class="form-hint">Minimum 20 characters</div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
