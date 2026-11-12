<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    http_response_code(403);
    exit('Akun admin sudah tersedia. Halaman setup telah dikunci.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $username) || strlen($password) < 12 || strlen($password) > 128) {
        flash('error', 'Username harus 3–60 karakter. Password harus 12–128 karakter.');
        redirect('setup.php');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->query('SELECT id FROM visitor_stats WHERE id = 1 FOR UPDATE');
        if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
            $pdo->rollBack();
            http_response_code(403);
            exit('Akun admin sudah tersedia. Halaman setup telah dikunci.');
        }
        $insert = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
    flash('success', 'Admin berhasil dibuat. Silakan login.');
    redirect('login.php');
}

render_header('Buat Admin');
?>
<section class="auth-wrap">
    <form method="post" class="auth-card">
        <p class="eyebrow">PENYIAPAN PERTAMA KALI</p>
        <h1>Buat Akun Admin</h1>
        <p>Halaman ini hanya berfungsi sebelum akun admin pertama dibuat.</p>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" required minlength="3" maxlength="60" autocomplete="username">
        <label for="password">Password (minimal 12 karakter)</label>
        <input id="password" name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password">
        <button class="button button-blue" type="submit">Buat Admin</button>
        <small>Pastikan database sudah diimpor dari <code>database.sql</code>.</small>
    </form>
</section>
<?php render_footer(); ?>
