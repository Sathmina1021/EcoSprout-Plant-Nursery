<?php
$pageTitle = 'Workshop Detail';
require_once 'includes/header.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { flashMessage('error','Workshop not found.'); redirect('workshops.php'); }

$workshop = $conn->query("SELECT * FROM workshops WHERE id=$id AND is_active=1")->fetch_assoc();
if (!$workshop) { flashMessage('error','Workshop not found.'); redirect('workshops.php'); }

$pageTitle = htmlspecialchars($workshop['title']);
$spotsLeft = $workshop['max_participants'] - $workshop['current_participants'];
$isFull    = $spotsLeft <= 0;
$isPast    = strtotime($workshop['workshop_date']) < strtotime('today');

$isRegistered = false;
if (isLoggedIn()) {
    $check = $conn->query("SELECT * FROM workshop_registrations WHERE workshop_id=$id AND user_id={$_SESSION['user_id']}");
    $isRegistered = $check->num_rows > 0;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!isLoggedIn()) { redirect("login.php?redirect=".urlencode($_SERVER['REQUEST_URI'])); }
    if ($isFull)         { $error = 'This workshop is fully booked.'; }
    elseif ($isPast)     { $error = 'This workshop has already taken place.'; }
    elseif ($isRegistered){ $error = 'You are already registered for this workshop.'; }
    else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO workshop_registrations (workshop_id, user_id, payment_status) VALUES (?,?,?)");
            $status = $workshop['price'] > 0 ? 'pending' : 'paid';
            $stmt->bind_param("iis", $id, $_SESSION['user_id'], $status);
            $stmt->execute();
            $stmt->close();
            $conn->query("UPDATE workshops SET current_participants = current_participants + 1 WHERE id = $id");
            $conn->commit();
            $isRegistered = true;
            $workshop['current_participants']++;
            $spotsLeft--;
            flashMessage('success', 'Successfully registered for "' . $workshop['title'] . '"! 🌱');
        } catch(Exception $e) {
            $conn->rollback();
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>

<div class="page-hero">
    <h1><?= htmlspecialchars($workshop['title']) ?></h1>
    <div class="breadcrumb">
        <a href="<?= SITE_URL ?>">Home</a><span>/</span>
        <a href="<?= SITE_URL ?>/workshops.php">Workshops</a><span>/</span>
        <span><?= htmlspecialchars(substr($workshop['title'],0,30)) ?>...</span>
    </div>
</div>

<div class="section">
    <div style="display:grid; grid-template-columns:1fr 340px; gap:32px; align-items:start;">
        <!-- Details -->
        <div>
            <div style="background:white; border-radius:var(--radius-lg); padding:32px; box-shadow:var(--shadow-sm); margin-bottom:20px;">
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
                    <?php if ($isPast): ?>
                        <span class="badge badge-default">Past Event</span>
                    <?php elseif ($isFull): ?>
                        <span class="badge badge-error">Fully Booked</span>
                    <?php else: ?>
                        <span class="badge badge-success">Open for Registration</span>
                    <?php endif; ?>
                    <?php if ($workshop['price'] == 0): ?>
                        <span class="badge badge-info">Free Event</span>
                    <?php endif; ?>
                </div>

                <h2 style="font-size:1.6rem; margin-bottom:16px;"><?= htmlspecialchars($workshop['title']) ?></h2>
                <p style="color:var(--text-mid); line-height:1.8; font-size:15px;"><?= nl2br(htmlspecialchars($workshop['description'] ?? '')) ?></p>
            </div>

            <!-- Workshop details grid -->
            <div style="background:white; border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-sm);">
                <h3 style="margin-bottom:18px;">Workshop Details</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <?php
                    $details = [
                        ['fas fa-calendar','Date', date('l, d F Y', strtotime($workshop['workshop_date']))],
                        ['fas fa-clock','Time', date('g:i A', strtotime($workshop['start_time'])).' – '.date('g:i A', strtotime($workshop['end_time']))],
                        ['fas fa-user-tie','Instructor', $workshop['instructor']],
                        ['fas fa-map-marker-alt','Location', $workshop['location']],
                        ['fas fa-users','Capacity', $workshop['max_participants'].' participants'],
                        ['fas fa-ticket-alt','Spots Left', $spotsLeft > 0 ? "$spotsLeft available" : 'Fully booked'],
                    ];
                    foreach ($details as [$icon,$label,$value]):
                    ?>
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <div style="width:36px; height:36px; border-radius:8px; background:var(--green-pale); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="<?= $icon ?>" style="color:var(--green-mid); font-size:14px;"></i>
                        </div>
                        <div>
                            <div style="font-size:11px; font-weight:600; color:var(--text-light); text-transform:uppercase;"><?= $label ?></div>
                            <div style="font-size:14px; color:var(--text-dark);"><?= htmlspecialchars($value) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Registration Card -->
        <div style="position:sticky; top:90px;">
            <div style="background:white; border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-md); border:2px solid var(--green-pale);">
                <div style="text-align:center; margin-bottom:20px;">
                    <div style="font-size:2rem; font-weight:700; color:var(--green-dark); font-family:'Playfair Display',serif;">
                        <?= $workshop['price'] > 0 ? formatPrice($workshop['price']) : 'FREE' ?>
                    </div>
                    <div style="font-size:13px; color:var(--text-light);">per person</div>
                </div>

                <!-- Progress -->
                <div style="margin-bottom:18px;">
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--text-light); margin-bottom:6px;">
                        <span><?= $workshop['current_participants'] ?> registered</span>
                        <span><?= $spotsLeft > 0 ? $spotsLeft.' spots left' : 'Fully booked' ?></span>
                    </div>
                    <div style="background:var(--cream-dark); border-radius:4px; height:8px; overflow:hidden;">
                        <div style="height:100%; border-radius:4px; background:var(--green-light); width:<?= min(100, ($workshop['current_participants']/$workshop['max_participants'])*100) ?>%;"></div>
                    </div>
                </div>

                <?php if ($error): ?>
                <div class="flash-message flash-error" style="position:static;transform:none;margin-bottom:14px;font-size:13px;">
                    <i class="fas fa-times-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <?php if ($isRegistered): ?>
                    <div style="text-align:center; padding:16px; background:var(--green-pale); border-radius:10px;">
                        <i class="fas fa-check-circle" style="font-size:28px; color:var(--green-mid); margin-bottom:8px; display:block;"></i>
                        <strong style="color:var(--green-dark);">You're Registered!</strong>
                        <p style="font-size:13px; color:var(--text-mid); margin-top:4px;">See you on <?= date('d M', strtotime($workshop['workshop_date'])) ?>!</p>
                    </div>
                <?php elseif ($isPast): ?>
                    <div style="text-align:center; padding:16px; background:var(--cream); border-radius:10px; color:var(--text-light);">
                        This workshop has already taken place.
                    </div>
                <?php elseif ($isFull): ?>
                    <div style="text-align:center; padding:16px; background:#ffebee; border-radius:10px; color:var(--error);">
                        This workshop is fully booked.
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-primary" style="width:100%; justify-content:center;">
                        <i class="fas fa-sign-in-alt"></i> Login to Register
                    </a>
                <?php else: ?>
                    <form method="POST">
                        <button type="submit" name="register" class="btn-submit">
                            <i class="fas fa-calendar-check"></i> Register Now
                        </button>
                    </form>
                <?php endif; ?>

                <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--cream-dark);">
                    <p style="font-size:12px; color:var(--text-light); text-align:center;">
                        <i class="fas fa-shield-alt" style="color:var(--green-light);"></i>
                        Secure registration · Cancel up to 48hrs before
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
