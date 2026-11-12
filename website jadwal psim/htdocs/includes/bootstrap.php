<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

if (strlen(APP_KEY) < 32 || APP_KEY === 'GANTI_DENGAN_KUNCI_ACAK_PANJANG_SEBELUM_DIPAKAI') {
    http_response_code(500);
    exit('Konfigurasi APP_KEY belum aman. Atur kunci acak minimal 32 karakter di config/database.php.');
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function db(): PDO
{
    static $pdo;
    if (!$pdo instanceof PDO) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $provided = $_POST['csrf_token'] ?? '';
    if (!is_string($provided) || !hash_equals(csrf_token(), $provided)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba kembali.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_admin(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('login.php');
    }
}

function visitor_hash(): string
{
    return hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), APP_KEY);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($message) ? $message : null;
}

function render_header(string $title): void
{
    $flashMessage = take_flash();
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#082c59">
        <title><?= e($title) ?> | Jadwal PSIM Jogja</title>
        <link rel="stylesheet" href="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../assets/css/style.css' : 'assets/css/style.css' ?>">
        <script src="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../assets/js/app.js' : 'assets/js/app.js' ?>" defer></script>
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../index.php' : 'index.php' ?>">
            <img src="https://psimjogja.id/wp-content/uploads/2020/08/PSIM.png" alt="Logo PSIM Yogyakarta">
            <span>JADWAL <b>PSIM JOGJA</b></span>
        </a>
        <nav class="site-nav" aria-label="Navigasi utama">
            <a href="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../index.php#jadwal' : '#jadwal' ?>">Jadwal</a>
            <a href="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../index.php#komentar' : '#komentar' ?>">Suara Suporter</a>
            <a class="admin-link" href="<?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? 'index.php' : 'admin/login.php' ?>">ADMIN</a>
        </nav>
    </header>
    <?php if ($flashMessage): ?>
        <div class="flash <?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
    <?php endif; ?>
    <main>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer class="site-footer">
        <span>© <?= date('Y') ?> JADWAL PSIM JOGJA</span>
        <span>Satu Jiwa, Satu Kebanggaan, Laskar Mataram Selamanya.</span>
    </footer>
    </body>
    </html>
    <?php
}
