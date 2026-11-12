<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (empty($_SESSION['visit_counted'])) {
    db()->exec('UPDATE visitor_stats SET total_visits = total_visits + 1 WHERE id = 1');
    $dailyVisit = db()->prepare(
        'INSERT INTO visitor_daily (visit_date, visits) VALUES (CURRENT_DATE(), 1)
         ON DUPLICATE KEY UPDATE visits = visits + 1'
    );
    $dailyVisit->execute();
    $_SESSION['visit_counted'] = true;
}

$matches = db()->query(
    "SELECT * FROM matches
     ORDER BY CASE WHEN status = 'upcoming' THEN 0 ELSE 1 END,
              CASE WHEN status = 'upcoming' THEN kickoff_at END ASC,
              CASE WHEN status = 'completed' THEN kickoff_at END DESC"
)->fetchAll();

$comments = db()->query('SELECT name, category, body, created_at FROM comments ORDER BY created_at DESC LIMIT 12')->fetchAll();
$ratingSummary = db()->query('SELECT ROUND(AVG(score), 1) AS average_score, COUNT(*) AS rating_count FROM ratings')->fetch();
$averageRating = $ratingSummary['average_score'] !== null ? (float) $ratingSummary['average_score'] : 0;
$ratingCount = (int) $ratingSummary['rating_count'];
$ratingCookieExists = isset($_COOKIE['psim_rating_token']);
$thankYou = $_SESSION['rating_thanks'] ?? false;
unset($_SESSION['rating_thanks']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'comment') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $category = (string) ($_POST['category'] ?? '');
        $honeypot = trim((string) ($_POST['website'] ?? ''));

        if ($honeypot !== '') {
            flash('success', 'Terima kasih, pesan Anda sudah dikirim.');
            redirect('index.php#komentar');
        }
        if ($name === '' || mb_strlen($name) > 80 || mb_strlen($body) < 3 || mb_strlen($body) > 1000
            || !in_array($category, ['Saran', 'Kritik', 'Komentar'], true)) {
            flash('error', 'Periksa kembali nama, kategori, dan isi pesan Anda.');
            redirect('index.php#komentar');
        }

        $ipHash = visitor_hash();
        $rateLimit = db()->prepare('SELECT COUNT(*) FROM comments WHERE ip_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)');
        $rateLimit->execute([$ipHash]);
        $duplicate = db()->prepare('SELECT COUNT(*) FROM comments WHERE ip_hash = ? AND body = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)');
        $duplicate->execute([$ipHash, $body]);

        if ((int) $rateLimit->fetchColumn() > 0 || (int) $duplicate->fetchColumn() > 0) {
            flash('error', 'Mohon tunggu sebelum mengirim pesan lagi. Pesan yang sama tidak dapat dikirim berulang.');
            redirect('index.php#komentar');
        }

        $insert = db()->prepare('INSERT INTO comments (name, category, body, ip_hash) VALUES (?, ?, ?, ?)');
        $insert->execute([$name, $category, $body, $ipHash]);
        flash('success', 'Terima kasih, pesan Anda sudah dikirim.');
        redirect('index.php#komentar');
    }

    if ($action === 'rating') {
        $score = filter_var($_POST['score'] ?? null, FILTER_VALIDATE_INT);
        if ($score === false || $score < 1 || $score > 5) {
            flash('error', 'Pilih rating dari 1 sampai 5 bintang.');
            redirect('index.php#rating');
        }
        if ($ratingCookieExists) {
            flash('error', 'Terima kasih, browser ini sudah pernah mengirim rating.');
            redirect('index.php#rating');
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash_hmac('sha256', $token, APP_KEY);
        try {
            $ipHash = visitor_hash();
            $recentRating = db()->prepare('SELECT COUNT(*) FROM ratings WHERE ip_hash = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)');
            $recentRating->execute([$ipHash]);
            if ((int) $recentRating->fetchColumn() > 0) {
                flash('error', 'Rating dari jaringan ini sudah dikirim dalam 24 jam terakhir.');
                redirect('index.php#rating');
            }
            $insert = db()->prepare('INSERT INTO ratings (visitor_hash, ip_hash, score) VALUES (?, ?, ?)');
            $insert->execute([$tokenHash, $ipHash, $score]);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            exit('Rating tidak dapat disimpan. Silakan coba kembali.');
        }
        setcookie('psim_rating_token', $token, [
            'expires' => time() + 60 * 60 * 24 * 365,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_SESSION['rating_thanks'] = true;
        redirect('index.php#rating');
    }
}

render_header('Jadwal Pertandingan');
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">MUSIM 2026/2027 · LASKAR MATARAM</p>
        <h1>JADWAL PERTANDINGAN <span>PSIM YOGYAKARTA</span></h1>
        <p class="hero-slogan">Satu Jiwa, Satu Kebanggaan,<br>Laskar Mataram Selamanya.</p>
        <a class="button button-gold" href="#jadwal">Lihat Jadwal <span aria-hidden="true">↓</span></a>
    </div>
    <div class="hero-crest">
        <img src="https://psimjogja.id/wp-content/uploads/2020/08/PSIM.png" alt="Lambang resmi PSIM Yogyakarta">
    </div>
</section>

<section class="section schedule-section" id="jadwal">
    <div class="section-heading">
        <div>
            <p class="eyebrow">JANGAN LEWATKAN</p>
            <h2>Jadwal Pertandingan</h2>
        </div>
        <span class="section-note">Diperbarui oleh admin berdasarkan sumber resmi</span>
    </div>

    <?php if (!$matches): ?>
        <div class="empty-state">
            <span class="empty-icon">⚽</span>
            <h3>Belum ada jadwal terverifikasi</h3>
            <p>Jadwal akan ditampilkan setelah diumumkan oleh PSIM atau I.League.</p>
        </div>
    <?php else: ?>
        <div class="match-list">
            <?php foreach ($matches as $match): ?>
                <?php
                $isUpcoming = $match['status'] === 'upcoming';
                $kickoff = $match['kickoff_at'] ? new DateTimeImmutable($match['kickoff_at'], new DateTimeZone('Asia/Jakarta')) : null;
                $psimHome = (int) $match['psim_home'] === 1;
                ?>
                <article class="match-card">
                    <div class="match-date">
                        <span class="date-day"><?= $kickoff ? e($kickoff->format('d')) : '—' ?></span>
                        <span><?= $kickoff ? e($kickoff->format('M Y')) : 'Tanggal TBA' ?></span>
                    </div>
                    <div class="match-teams">
                        <div class="team">
                            <?php if (!$psimHome): ?><img src="<?= e($match['opponent_logo']) ?>" alt="" loading="lazy"><?php else: ?><img src="https://psimjogja.id/wp-content/uploads/2020/08/PSIM.png" alt="" loading="lazy"><?php endif; ?>
                            <strong><?= $psimHome ? 'PSIM Yogyakarta' : e($match['opponent']) ?></strong>
                        </div>
                        <span class="versus">VS</span>
                        <div class="team">
                            <?php if ($psimHome): ?>
                                <img src="<?= e($match['opponent_logo']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <img src="https://psimjogja.id/wp-content/uploads/2020/08/PSIM.png" alt="" loading="lazy">
                            <?php endif; ?>
                            <strong><?= $psimHome ? e($match['opponent']) : 'PSIM Yogyakarta' ?></strong>
                        </div>
                    </div>
                    <div class="match-details">
                        <span><b>Waktu</b><?= $kickoff && $kickoff->format('H:i') !== '00:00' ? e($kickoff->format('H:i')) . ' WIB' : 'Belum diumumkan' ?></span>
                        <span><b>Stadion</b><?= e($match['venue']) ?></span>
                        <span><b>Lokasi</b><?= e($match['location']) ?></span>
                    </div>
                    <div class="match-meta">
                        <span class="competition-name"><?= e($match['competition']) ?></span>
                        <span class="status <?= $isUpcoming ? 'status-upcoming' : 'status-completed' ?>">
                            <?= $isUpcoming ? 'Akan Datang' : 'Selesai' ?>
                        </span>
                        <?php if ($match['source_url'] !== ''): ?>
                            <a class="source-link" href="<?= e($match['source_url']) ?>" target="_blank" rel="noopener noreferrer">Sumber resmi ↗</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p class="schedule-disclaimer">Jadwal dapat berubah. Waktu ditampilkan sebagai belum diumumkan jika sumber resmi masih mencantumkan TBC.</p>
</section>

<section class="rating-section" id="rating">
    <div class="rating-copy">
        <p class="eyebrow">BANTU KAMI LEBIH BAIK</p>
        <h2>Bagaimana website ini?</h2>
        <p>Penilaian Anda membantu kami menyajikan informasi jadwal dengan lebih baik.</p>
    </div>
    <div class="rating-widget">
        <div class="rating-summary">
            <strong><?= $ratingCount > 0 ? e(number_format($averageRating, 1)) : '—' ?></strong>
            <span class="stars-display" aria-label="Rata-rata rating <?= e(number_format($averageRating, 1)) ?> dari 5"><?= $ratingCount > 0 ? str_repeat('★', (int) round($averageRating)) : '☆☆☆☆☆' ?></span>
            <small><?= $ratingCount ?> penilaian</small>
        </div>
        <?php if ($thankYou): ?>
            <p class="thanks-message">Terima kasih atas penilaian Anda!</p>
        <?php elseif (!$ratingCookieExists): ?>
            <form method="post" class="rating-form" aria-label="Beri rating website">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="rating">
                <div class="star-picker" role="group" aria-label="Pilih rating">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                        <button type="submit" name="score" value="<?= $star ?>" aria-label="<?= $star ?> bintang">★</button>
                    <?php endfor; ?>
                </div>
                <small>Pilih 1–5 bintang</small>
            </form>
        <?php else: ?>
            <p class="thanks-message">Anda sudah memberikan rating. Terima kasih!</p>
        <?php endif; ?>
    </div>
</section>

<section class="section feedback-section" id="komentar">
    <div class="section-heading">
        <div>
            <p class="eyebrow">SUARA LASKAR MATARAM</p>
            <h2>Komentar & Masukan</h2>
        </div>
    </div>
    <div class="feedback-grid">
        <form method="post" class="feedback-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="comment">
            <label class="honeypot" aria-hidden="true">Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            <label for="name">Nama</label>
            <input id="name" name="name" type="text" maxlength="80" required placeholder="Nama Anda">
            <label for="category">Kategori</label>
            <select id="category" name="category" required>
                <option value="Komentar">Komentar</option>
                <option value="Saran">Saran</option>
                <option value="Kritik">Kritik</option>
            </select>
            <label for="body">Pesan</label>
            <textarea id="body" name="body" maxlength="1000" minlength="3" required rows="5" placeholder="Tuliskan komentar, saran, atau kritik Anda..."></textarea>
            <button class="button button-blue" type="submit">Kirim Pesan</button>
        </form>
        <div class="comment-list">
            <?php if (!$comments): ?>
                <div class="empty-comments">Belum ada komentar. Jadilah yang pertama berbagi!</div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <article class="comment-card">
                        <div class="comment-top">
                            <strong><?= e($comment['name']) ?></strong>
                            <span class="comment-category"><?= e($comment['category']) ?></span>
                        </div>
                        <p><?= nl2br(e($comment['body'])) ?></p>
                        <time datetime="<?= e($comment['created_at']) ?>"><?= e(date('d/m/Y · H:i', strtotime($comment['created_at']))) ?> WIB</time>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php render_footer(); ?>
