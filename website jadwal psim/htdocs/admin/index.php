<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete_comment') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $delete = db()->prepare('DELETE FROM comments WHERE id = ?');
            $delete->execute([$id]);
            flash('success', 'Komentar berhasil dihapus.');
        }
        redirect('index.php#moderasi');
    }

    if ($action === 'save_match') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $opponent = trim((string) ($_POST['opponent'] ?? ''));
        $logo = trim((string) ($_POST['opponent_logo'] ?? ''));
        $psimHome = (string) ($_POST['psim_home'] ?? '');
        $competition = trim((string) ($_POST['competition'] ?? ''));
        $date = trim((string) ($_POST['match_date'] ?? ''));
        $time = trim((string) ($_POST['match_time'] ?? ''));
        $venue = trim((string) ($_POST['venue'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $status = (string) ($_POST['status'] ?? '');
        $source = trim((string) ($_POST['source_url'] ?? ''));

        $validDate = $date === '' || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
        $validTime = $time === '' || (bool) preg_match('/^\d{2}:\d{2}$/', $time);
        $validUrl = $logo === '' || (strlen($logo) <= 500 && filter_var($logo, FILTER_VALIDATE_URL));
        $validSource = $source === '' || (strlen($source) <= 500 && filter_var($source, FILTER_VALIDATE_URL));
        if ($opponent === '' || mb_strlen($opponent) > 120 || $venue === '' || mb_strlen($venue) > 160
            || !in_array($psimHome, ['0', '1'], true) || $competition === '' || mb_strlen($competition) > 120
            || $location === '' || mb_strlen($location) > 160 || !$validDate || !$validTime
            || !$validUrl || !$validSource || !in_array($status, ['upcoming', 'completed'], true)) {
            flash('error', 'Data jadwal tidak valid. Periksa kembali isian Anda.');
            redirect('index.php#kelola-jadwal');
        }
        $kickoff = $date === '' ? null : $date . ' ' . ($time === '' ? '00:00' : $time) . ':00';
        if ($id) {
            $save = db()->prepare('UPDATE matches SET opponent=?, opponent_logo=?, psim_home=?, competition=?, kickoff_at=?, venue=?, location=?, status=?, source_url=? WHERE id=?');
            $save->execute([$opponent, $logo, (int) $psimHome, $competition, $kickoff, $venue, $location, $status, $source, $id]);
        } else {
            $save = db()->prepare('INSERT INTO matches (opponent, opponent_logo, psim_home, competition, kickoff_at, venue, location, status, source_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $save->execute([$opponent, $logo, (int) $psimHome, $competition, $kickoff, $venue, $location, $status, $source]);
        }
        flash('success', 'Jadwal berhasil disimpan.');
        redirect('index.php#kelola-jadwal');
    }

    if ($action === 'delete_match') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $delete = db()->prepare('DELETE FROM matches WHERE id = ?');
            $delete->execute([$id]);
            flash('success', 'Jadwal berhasil dihapus.');
        }
        redirect('index.php#kelola-jadwal');
    }
}

$visitorCount = (int) db()->query('SELECT total_visits FROM visitor_stats WHERE id = 1')->fetchColumn();
$dailyRows = db()->query('SELECT visit_date, visits FROM visitor_daily WHERE visit_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY) ORDER BY visit_date ASC')->fetchAll();
$dailyCounts = [];
foreach ($dailyRows as $row) {
    $dailyCounts[$row['visit_date']] = (int) $row['visits'];
}
$lastSevenDays = [];
for ($day = 6; $day >= 0; $day--) {
    $date = new DateTimeImmutable('-' . $day . ' days');
    $key = $date->format('Y-m-d');
    $lastSevenDays[] = ['label' => $date->format('d/m'), 'visits' => $dailyCounts[$key] ?? 0];
}
$maxDailyVisits = max(1, max(array_column($lastSevenDays, 'visits')));
$ratingStats = db()->query('SELECT ROUND(AVG(score), 1) AS average_score, COUNT(*) AS rating_count FROM ratings')->fetch();
$ratingRows = db()->query('SELECT score, created_at FROM ratings ORDER BY created_at DESC LIMIT 100')->fetchAll();
$comments = db()->query('SELECT id, name, category, body, created_at FROM comments ORDER BY created_at DESC')->fetchAll();
$matches = db()->query('SELECT * FROM matches ORDER BY kickoff_at ASC')->fetchAll();
$editMatch = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editing = null;
foreach ($matches as $match) {
    if ((int) $match['id'] === $editMatch) {
        $editing = $match;
        break;
    }
}

render_header('Dashboard Admin');
?>
<section class="admin-hero">
    <div>
        <p class="eyebrow">PANEL PENGELOLAAN</p>
        <h1>Halo, <?= e($_SESSION['admin_username'] ?? 'Admin') ?></h1>
        <p>Kelola jadwal, moderasi masukan, dan pantau statistik website.</p>
    </div>
    <form method="post" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="button button-outline" type="submit">Keluar</button>
    </form>
</section>
<section class="admin-stats">
    <article class="stat-card"><span>Total kunjungan</span><strong><?= number_format($visitorCount, 0, ',', '.') ?></strong><small>Pengunjung unik per sesi</small></article>
    <article class="stat-card"><span>Rata-rata rating</span><strong><?= $ratingStats['average_score'] !== null ? e(number_format((float) $ratingStats['average_score'], 1)) . ' / 5' : '—' ?></strong><small><?= (int) $ratingStats['rating_count'] ?> penilaian</small></article>
    <article class="stat-card"><span>Masukan diterima</span><strong><?= count($comments) ?></strong><small>Saran, kritik, dan komentar</small></article>
</section>
<section class="admin-section visitor-chart-section">
    <div class="section-heading"><div><p class="eyebrow">7 HARI TERAKHIR</p><h2>Statistik Kunjungan</h2></div></div>
    <div class="visitor-chart" aria-label="Jumlah kunjungan unik per hari selama tujuh hari terakhir">
        <?php foreach ($lastSevenDays as $day): ?>
            <div class="visitor-bar-item">
                <span class="visitor-count"><?= number_format($day['visits'], 0, ',', '.') ?></span>
                <div class="visitor-bar-track"><span style="height: <?= max(5, (int) round($day['visits'] / $maxDailyVisits * 100)) ?>%"></span></div>
                <span class="visitor-date"><?= e($day['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<section class="admin-section" id="kelola-jadwal">
    <div class="section-heading">
        <div><p class="eyebrow">DATA PERTANDINGAN</p><h2><?= $editing ? 'Edit Jadwal' : 'Tambah Jadwal' ?></h2></div>
    </div>
    <form method="post" class="match-admin-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_match">
        <input type="hidden" name="id" value="<?= $editing ? (int) $editing['id'] : '' ?>">
        <label>Nama lawan<input name="opponent" required maxlength="120" value="<?= e($editing['opponent'] ?? '') ?>"></label>
        <label>URL logo lawan<input name="opponent_logo" type="url" value="<?= e($editing['opponent_logo'] ?? '') ?>"></label>
        <label>Kandang/tandang<select name="psim_home"><option value="1" <?= !$editing || (int) $editing['psim_home'] === 1 ? 'selected' : '' ?>>PSIM kandang</option><option value="0" <?= $editing && (int) $editing['psim_home'] === 0 ? 'selected' : '' ?>>PSIM tandang</option></select></label>
        <label>Kompetisi<input name="competition" required maxlength="120" value="<?= e($editing['competition'] ?? 'BRI Super League 2026/27') ?>"></label>
        <label>Tanggal<input name="match_date" type="date" value="<?= $editing && $editing['kickoff_at'] ? e(substr($editing['kickoff_at'], 0, 10)) : '' ?>"></label>
        <label>Jam (WIB)<input name="match_time" type="time" value="<?= $editing && $editing['kickoff_at'] && substr($editing['kickoff_at'], 11, 5) !== '00:00' ? e(substr($editing['kickoff_at'], 11, 5)) : '' ?>"></label>
        <label>Stadion<input name="venue" required maxlength="160" value="<?= e($editing['venue'] ?? '') ?>"></label>
        <label>Lokasi<input name="location" required maxlength="160" value="<?= e($editing['location'] ?? '') ?>"></label>
        <label>Status<select name="status"><option value="upcoming" <?= !$editing || $editing['status'] === 'upcoming' ? 'selected' : '' ?>>Akan Datang</option><option value="completed" <?= $editing && $editing['status'] === 'completed' ? 'selected' : '' ?>>Selesai</option></select></label>
        <label class="wide-field">URL sumber resmi<input name="source_url" type="url" value="<?= e($editing['source_url'] ?? '') ?>"></label>
        <div class="wide-field form-actions"><button class="button button-blue" type="submit">Simpan Jadwal</button><?php if ($editing): ?><a class="back-link" href="index.php#kelola-jadwal">Batal edit</a><?php endif; ?></div>
    </form>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Pertandingan</th><th>Kompetisi</th><th>Tanggal</th><th>Stadion</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($matches as $match): ?>
                <tr>
                    <td><?= (int) $match['psim_home'] === 1 ? 'PSIM vs ' . e($match['opponent']) : e($match['opponent']) . ' vs PSIM' ?></td>
                    <td><?= e($match['competition']) ?></td>
                    <td><?= $match['kickoff_at'] ? e(date('d/m/Y', strtotime($match['kickoff_at']))) : 'TBA' ?></td>
                    <td><?= e($match['venue']) ?></td>
                    <td><?= $match['status'] === 'upcoming' ? 'Akan Datang' : 'Selesai' ?></td>
                    <td class="table-actions"><a href="?edit=<?= (int) $match['id'] ?>#kelola-jadwal">Edit</a><form method="post" onsubmit="return confirm('Hapus jadwal ini?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_match"><input type="hidden" name="id" value="<?= (int) $match['id'] ?>"><button type="submit">Hapus</button></form></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$matches): ?><tr><td colspan="6">Belum ada jadwal.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="admin-section" id="moderasi">
    <div class="section-heading"><div><p class="eyebrow">MODERASI</p><h2>Komentar & Masukan</h2></div></div>
    <?php if (!$comments): ?><p class="admin-empty">Belum ada komentar masuk.</p><?php else: ?>
        <div class="admin-comments">
            <?php foreach ($comments as $comment): ?>
                <article class="admin-comment">
                    <div class="comment-top"><strong><?= e($comment['name']) ?> <span class="comment-category"><?= e($comment['category']) ?></span></strong><time><?= e(date('d/m/Y H:i', strtotime($comment['created_at']))) ?> WIB</time></div>
                    <p><?= nl2br(e($comment['body'])) ?></p>
                    <form method="post" onsubmit="return confirm('Hapus komentar ini?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_comment"><input type="hidden" name="id" value="<?= (int) $comment['id'] ?>"><button class="text-danger" type="submit">Hapus komentar</button></form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<section class="admin-section">
    <div class="section-heading"><div><p class="eyebrow">RATING WEBSITE</p><h2>Riwayat Penilaian</h2></div></div>
    <div class="admin-rating-list">
        <?php foreach ($ratingRows as $rating): ?><span><?= str_repeat('★', (int) $rating['score']) ?><?= str_repeat('☆', 5 - (int) $rating['score']) ?> <small><?= e(date('d/m/Y H:i', strtotime($rating['created_at']))) ?></small></span><?php endforeach; ?>
        <?php if (!$ratingRows): ?><p class="admin-empty">Belum ada rating.</p><?php endif; ?>
    </div>
</section>
<?php render_footer(); ?>
