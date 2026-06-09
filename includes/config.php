<?php
if (!ob_get_level()) {
    ob_start();
}
// ==============================================
// EcoSprout - Database / Site Configuration
// ==============================================

// Change these only if your local MySQL settings are different.
// Defaults are made for XAMPP on Windows. DB_HOST/DB_PORT can also be set
// through environment variables if your machine uses a custom setup.
define('DB_HOST', getenv('ECOSPROUT_DB_HOST') ?: 'auto');
define('DB_PORT', getenv('ECOSPROUT_DB_PORT') ?: 'auto');
define('DB_USER', getenv('ECOSPROUT_DB_USER') ?: 'root');
define('DB_PASS', getenv('ECOSPROUT_DB_PASS') ?: '');
define('DB_NAME', getenv('ECOSPROUT_DB_NAME') ?: 'ecosprout_db');
define('SITE_NAME', 'EcoSprout Nursery');
define('UPLOAD_PATH', __DIR__ . '/../uploads/plants/');

if (!defined('SITE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $basePath = '/ecosprout';
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot  = realpath(__DIR__ . '/..');
    if ($documentRoot && $projectRoot && strpos($projectRoot, $documentRoot) === 0) {
        $relative = str_replace('\\', '/', substr($projectRoot, strlen($documentRoot)));
        $basePath = '/' . trim($relative, '/');
        if ($basePath === '/') {
            $basePath = '';
        }
    }
    define('SITE_URL', rtrim($scheme . '://' . $host . $basePath, '/'));
}

if (!class_exists('mysqli')) {
    die('<div style="font-family:sans-serif;background:#fff3cd;padding:20px;border:1px solid #ffe69c;border-radius:8px;max-width:650px;margin:50px auto;line-height:1.5;">
        <h3 style="color:#856404;margin:0 0 10px">PHP mysqli extension is not enabled</h3>
        <p style="color:#856404;margin:0">Enable <code>mysqli</code> in your PHP/XAMPP installation, restart Apache, then open EcoSprout again.</p>
    </div>');
}

mysqli_report(MYSQLI_REPORT_OFF);

// Connect to the MySQL server first, then create/select the app database.
// XAMPP/MariaDB sometimes allows root@127.0.0.1 but rejects root@localhost.
// The "auto" host tries both so the project runs on more local setups.
function ecosproutConnectDatabase(): mysqli {
    $hostCandidates = DB_HOST === 'auto' ? ['127.0.0.1', 'localhost'] : [DB_HOST];
    $portCandidates = DB_PORT === 'auto' ? [3306, 3307] : [(int) DB_PORT];
    $lastError = '';
    $attempts = [];

    foreach ($portCandidates as $port) {
        foreach ($hostCandidates as $host) {
            $attempts[] = $host . ':' . $port;
            $conn = @new mysqli($host, DB_USER, DB_PASS, '', $port);
            if (!$conn->connect_error) {
                return $conn;
            }
            $lastError = $conn->connect_error;
        }
    }

    $lowerError = strtolower($lastError);
    $extraHelp = '';
    if (strpos($lowerError, '10061') !== false || strpos($lowerError, 'actively refused') !== false || strpos($lowerError, '2002') !== false || strpos($lowerError, 'no connection') !== false) {
        $extraHelp = '<ol style="color:#600;margin:10px 0 0;padding-left:20px"><li>Open <strong>XAMPP Control Panel</strong>.</li><li>Click <strong>Start</strong> for <strong>MySQL</strong> and make sure it turns green.</li><li>If MySQL does not start, another MySQL service may be using port 3306. Change XAMPP MySQL to port 3307, then refresh this page. This project auto-tries both 3306 and 3307.</li></ol>';
    } elseif (strpos($lowerError, 'not allowed to connect') !== false) {
        $extraHelp = '<p style="color:#600;margin:8px 0 0">Your MySQL user/host permission is broken. Open XAMPP Shell after MySQL starts and run:<br><code>mysql -u root -h 127.0.0.1 -P 3306</code><br>Then run the permission SQL from <code>MYSQL_PERMISSION_FIX.sql</code>.</p>';
    }

    die('<div style="font-family:Arial,sans-serif;background:#fee;padding:22px;border:1px solid #fcc;border-radius:8px;max-width:820px;margin:50px auto;line-height:1.55;">
        <h3 style="color:#c00;margin:0 0 10px">Database Connection Failed</h3>
        <p style="color:#600;margin:0">EcoSprout tried these MySQL locations: <code>' . htmlspecialchars(implode(', ', $attempts)) . '</code></p>
        <p style="color:#900;margin:8px 0 0"><strong>Error:</strong> ' . htmlspecialchars($lastError) . '</p>
        ' . $extraHelp . '
        <p style="color:#600;margin:12px 0 0">Default XAMPP credentials used: user <code>root</code>, blank password. Edit <code>includes/config.php</code> only if your XAMPP has a different password.</p>
    </div>');
}

$conn = ecosproutConnectDatabase();

$conn->set_charset('utf8mb4');
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if (!$conn->select_db(DB_NAME)) {
    die('<div style="font-family:sans-serif;background:#fee;padding:20px;border:1px solid #fcc;border-radius:8px;max-width:650px;margin:50px auto;line-height:1.5;">
        <h3 style="color:#c00;margin:0 0 10px">Database Selection Failed</h3>
        <p style="color:#900;margin:0"><strong>Error:</strong> ' . htmlspecialchars($conn->error) . '</p>
    </div>');
}

// Start session before helper functions use flash messages or login data.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function ecosproutRunSqlFile(mysqli $conn, string $path): void {
    if (!is_file($path)) {
        return;
    }
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        return;
    }

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }

    if ($conn->errno) {
        die('<div style="font-family:sans-serif;background:#fee;padding:20px;border:1px solid #fcc;border-radius:8px;max-width:750px;margin:50px auto;line-height:1.5;">
            <h3 style="color:#c00;margin:0 0 10px">Database Setup Failed</h3>
            <p style="color:#600;margin:0">Import <code>database.sql</code> manually in phpMyAdmin, or fix the SQL error below.</p>
            <p style="color:#900;margin:8px 0 0"><strong>Error:</strong> ' . htmlspecialchars($conn->error) . '</p>
        </div>');
    }
}

function ecosproutTableExists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

// Auto-install schema/seed data on a fresh local database.
if (!ecosproutTableExists($conn, 'users')) {
    ecosproutRunSqlFile($conn, __DIR__ . '/../database.sql');
}

// Keep the bundled demo admin login working even on databases created from the older SQL file.
$demoAdminHash = '$2y$12$GKi6GkaOPLtmlrmiFmrnYuCSHwmyEEYE0Z22.lzigdV1942NZfaTG';
$oldLaravelHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$stmt = $conn->prepare("INSERT IGNORE INTO users (full_name, email, password, role, is_active) VALUES ('System Administrator', 'admin@ecosprout.lk', ?, 'admin', 1)");
if ($stmt) {
    $stmt->bind_param('s', $demoAdminHash);
    $stmt->execute();
    $stmt->close();
}
$stmt = $conn->prepare("UPDATE users SET password=? WHERE email='admin@ecosprout.lk' AND password=?");
if ($stmt) {
    $stmt->bind_param('ss', $demoAdminHash, $oldLaravelHash);
    $stmt->execute();
    $stmt->close();
}


// Keep bundled demo staff/customer logins and plant images available even on an already-created local DB.
$demoAccounts = [
    ['Staff Member', 'staff@ecosprout.lk', '$2y$12$0GsFVMhX9SLhlOnOUsqRZ.QtCiMoTAtJarXPjc3hEGP//RlcOG1pO', 'staff'],
    ['Demo Customer', 'user@ecosprout.lk', '$2y$12$TNYaakkZ54RAvxQefmMgSeqthcULZunv2hbiKfKeItbF2izX2OM96', 'customer'],
];
foreach ($demoAccounts as $demoAccount) {
    $stmt = $conn->prepare("INSERT IGNORE INTO users (full_name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
    if ($stmt) {
        $stmt->bind_param('ssss', $demoAccount[0], $demoAccount[1], $demoAccount[2], $demoAccount[3]);
        $stmt->execute();
        $stmt->close();
    }
}

$plantImages = [
    'Peace Lily' => 'peace-lily.jpg',
    'Snake Plant' => 'snake-plant.jpg',
    'Monstera' => 'monstera.jpg',
    'Bougainvillea' => 'bougainvillea.jpg',
    'Hibiscus' => 'hibiscus.jpg',
    'Anthurium' => 'anthurium.jpg',
    'Curry Leaf' => 'curry-leaf.jpg',
    'Chilli' => 'chilli.jpg',
];
foreach ($plantImages as $plantName => $plantImage) {
    $stmt = $conn->prepare("UPDATE plants SET image = ?, is_featured = 1 WHERE name = ? AND (image IS NULL OR image = '' OR image = 'default_plant.jpg' OR name = 'Peace Lily')");
    if ($stmt) {
        $stmt->bind_param('ss', $plantImage, $plantName);
        $stmt->execute();
        $stmt->close();
    }
}

// Helper functions
function sanitize($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8'));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'staff' || $_SESSION['role'] === 'admin');
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function redirect($url) {
    $url = trim((string)$url);
    if (preg_match('#^https?://#i', $url)) {
        header('Location: ' . $url);
        exit();
    }

    $url = ltrim($url, '/');
    while (strpos($url, '../') === 0) {
        $url = substr($url, 3);
    }
    header('Location: ' . SITE_URL . '/' . $url);
    exit();
}

function flashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatPrice($price) {
    return 'Rs. ' . number_format((float)$price, 2);
}

function generateOrderNumber() {
    return 'ECO-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
}

function getCartCount($conn, $userId) {
    $userId = intval($userId);
    $result = $conn->query("SELECT COALESCE(SUM(quantity),0) as total FROM cart WHERE user_id = $userId");
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    return intval($row['total'] ?? 0);
}
?>
