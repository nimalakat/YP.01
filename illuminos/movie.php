<?php
/**
 * movie.php — страница фильма (стиль онлайн-кинотеатра)
 */
require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT m.*, g.name AS genre_name
     FROM movies m
     LEFT JOIN genres g ON g.id = m.genre_id
     WHERE m.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$movie = $stmt->fetch();

if (!$movie) { http_response_code(404); die('Фильм не найден'); }

/* Логируем просмотр */
if (!empty($_SESSION['user_id'])) {
    $pdo->prepare("INSERT INTO views_history (user_id, movie_id) VALUES (:u, :m)")
        ->execute([':u' => $_SESSION['user_id'], ':m' => $id]);
}

/* Персоны */
$p = $pdo->prepare(
    "SELECT p.* FROM persons p
     JOIN movie_persons mp ON mp.person_id = p.id
     WHERE mp.movie_id = :id"
);
$p->execute([':id' => $id]);
$persons = $p->fetchAll();

/* Похожие */
$s = $pdo->prepare(
    "SELECT * FROM movies WHERE genre_id = :g AND id != :id
     ORDER BY rating DESC LIMIT 12"
);
$s->execute([':g' => $movie['genre_id'], ':id' => $id]);
$similar = $s->fetchAll();

/* Видео: трейлер или демо */
$videoUrl = !empty($movie['trailer'])
    ? $movie['trailer']
    : 'https://test-videos.co.uk/vids/bigbuckbunny/mp4/h264/1080/Big_Buck_Bunny_1080_10s_1MB.mp4';

function card(array $m): string {
    $poster = htmlspecialchars($m['poster'] ?? 'img/placeholder.jpg');
    $title  = htmlspecialchars($m['title']);
    $link   = (($m['type'] ?? 'movie') === 'series' ? 'series.php' : 'movie.php') . '?id=' . (int)$m['id'];
    $badge  = !empty($m['is_new']) ? '<span class="card-badge">Новинка</span>' : '';
    return '<a href="' . $link . '" class="card-item">' . $badge
         . '<div class="card-poster"><img src="' . $poster . '" alt="' . $title . '" loading="lazy"></div>'
         . '<div class="card-title">' . $title . '</div>'
         . '<div class="card-meta">' . (int)$m['year'] . ' · ★ ' . $m['rating'] . '</div>'
         . '</a>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> — Illuminos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ШАПКА -->
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">ILLUMINOS</a>
        <nav class="topnav">
            <a href="index.php">Главная</a>
            <a href="catalog.php?type=movie">Фильмы</a>
            <a href="catalog.php?type=series">Сериалы</a>
        </nav>
        <div class="topbar-actions">
            <form class="topsearch" method="get" action="index.php">
                <i class="bi bi-search"></i>
                <input type="text" name="q" placeholder="Поиск">
            </form>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="cabinet.php" class="topuser"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Профиль') ?></a>
            <?php else: ?>
                <a href="register.php" class="btn-register">Регистрация</a>
                <a href="login.php" class="btn-signin">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ФОН-ПОДЛОЖКА С РАЗМЫТИЕМ -->
<div class="detail-bg" style="background-image:url('<?= htmlspecialchars($movie['poster'] ?? 'img/placeholder.jpg') ?>')"></div>

<main class="detail-page">

    <!-- ОСНОВНОЙ БЛОК -->
    <section class="detail-hero">

        <!-- ПОСТЕР -->
        <div class="detail-poster">
            <img src="<?= htmlspecialchars($movie['poster'] ?? 'img/placeholder.jpg') ?>"
                 alt="<?= htmlspecialchars($movie['title']) ?>">
            <?php if (!empty($movie['is_new'])): ?>
                <span class="detail-badge">Премьера</span>
            <?php endif; ?>
        </div>

        <!-- ИНФО -->
        <div class="detail-info">
            <h1 class="detail-title"><?= htmlspecialchars($movie['title']) ?></h1>

            <div class="detail-meta">
                <span class="detail-rating">★ <?= $movie['rating'] ?></span>
                <span><?= (int)$movie['year'] ?></span>
                <span><?= htmlspecialchars($movie['genre_name'] ?? '') ?></span>
                <span><?= $movie['type'] === 'series' ? 'Сериал' : 'Фильм' ?></span>
                <span class="detail-age">18+</span>
            </div>

            <p class="detail-desc"><?= htmlspecialchars($movie['description']) ?></p>

            <div class="detail-actions">
                <a href="watch.php?id=<?= $movie['id'] ?>" class="btn-primary detail-btn-lg">
                    <i class="bi bi-play-fill"></i> Смотреть
                </a>
                <a href="cabinet.php?add_fav=<?= $movie['id'] ?>" class="btn-secondary detail-btn-lg">
                    <i class="bi bi-heart"></i> В избранное
                </a>
                <button class="btn-icon" title="Поделиться">
                    <i class="bi bi-share"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- ПЛЕЕР / ТРЕЙЛЕР -->
    <section class="detail-section">
        <h2 class="detail-section-title"><i class="bi bi-play-btn"></i> Трейлер</h2>
        <div class="detail-player">
            <video controls preload="metadata"
                   poster="<?= htmlspecialchars($movie['poster'] ?? 'img/placeholder.jpg') ?>">
                <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
            </video>
        </div>
    </section>

    <!-- ОПИСАНИЕ -->
    <section class="detail-section">
        <h2 class="detail-section-title"><i class="bi bi-info-circle"></i> О фильме</h2>
        <div class="detail-about">
            <div class="detail-about-text">
                <p><?= nl2br(htmlspecialchars($movie['description'])) ?></p>
            </div>
            <div class="detail-about-facts">
                <div class="fact-row">
                    <span class="fact-label">Год:</span>
                    <span class="fact-value"><?= (int)$movie['year'] ?></span>
                </div>
                <div class="fact-row">
                    <span class="fact-label">Жанр:</span>
                    <span class="fact-value"><?= htmlspecialchars($movie['genre_name'] ?? '—') ?></span>
                </div>
                <div class="fact-row">
                    <span class="fact-label">Тип:</span>
                    <span class="fact-value"><?= $movie['type'] === 'series' ? 'Сериал' : 'Фильм' ?></span>
                </div>
                <div class="fact-row">
                    <span class="fact-label">Рейтинг:</span>
                    <span class="fact-value" style="color:var(--accent)">★ <?= $movie['rating'] ?> / 10</span>
                </div>
            </div>
        </div>
    </section>

    <!-- АКТЁРЫ -->
    <?php if ($persons): ?>
        <section class="detail-section">
            <h2 class="detail-section-title"><i class="bi bi-people"></i> Актёры и режиссёры</h2>
            <div class="persons-grid">
                <?php foreach ($persons as $p): ?>
                    <div class="person-card">
                        <div class="person-avatar"><i class="bi bi-person-fill"></i></div>
                        <div class="person-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="person-role"><?= ($p['role'] ?? 'actor') === 'director' ? 'Режиссёр' : 'Актёр' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- ПОХОЖИЕ -->
    <?php if ($similar): ?>
        <section class="detail-section">
            <h2 class="detail-section-title"><i class="bi bi-collection"></i> Похожие фильмы</h2>
            <div class="rail-track">
                <?php foreach ($similar as $m) echo card($m); ?>
            </div>
        </section>
    <?php endif; ?>

</main>

<footer class="footer">
    <div class="footer-inner">
        <div class="footer-col">
            <div class="footer-brand">ILLUMINOS</div>
            <p class="footer-copy">© <?= date('Y') ?> Illuminos.</p>
        </div>
        <div class="footer-col"><h4>Разделы</h4>
            <a href="catalog.php?type=movie">Фильмы</a>
            <a href="catalog.php?type=series">Сериалы</a>
        </div>
        <div class="footer-col"><h4>Аккаунт</h4>
            <a href="cabinet.php">Личный кабинет</a>
        </div>
        <div class="footer-col"><h4>Соцсети</h4>
            <div class="socials">
                <a href="#"><i class="bi bi-telegram"></i></a>
                <a href="#"><i class="bi bi-youtube"></i></a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>