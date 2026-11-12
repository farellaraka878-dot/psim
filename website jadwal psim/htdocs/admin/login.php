<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
if (is_admin()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
    $statement->execute([$username]);
    $admin = $statement->fetch();
    if (strlen($password) <= 128 && $admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        redirect('index.php');
    }
    flash('error', 'Username atau password tidak sesuai.');
    redirect('login.php');
}

render_header('Login Admin');
?>
<section class="auth-wrap">
    <form method="post" class="auth-card">
        <p class="eyebrow">AREA TERBATAS</p>
        <h1>Login Admin</h1>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" required autocomplete="username">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <button class="button button-blue" type="submit">Masuk</button>
        <a class="back-link" href="../index.php">← Kembali ke jadwal</a>
    </form>
</section>
<?php render_footer(); ?>
