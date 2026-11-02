<?php
/**
 * watch.php — страница просмотра фильма/сериала.
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

/* Похожие */
$s = $pdo->prepare(
    "SELECT * FROM movies WHERE genre_id = :g AND id != :id
     ORDER BY rating DESC LIMIT 12"
);
$s->execute([':g' => $movie['genre_id'], ':id' => $id]);
$similar = $s->fetchAll();

$videoUrl = !empty($movie['trailer'])
    ? $movie['trailer']
    : 'https://test-videos.co.uk/vids/bigbuckbunny/mp4/h264/1080/Big_Buck_Bunny_1080_10s_1MB.mp4';

function card(array $m): string {
    $poster = htmlspecialchars($m['poster'] ?? 'img/placeholder.jpg');
    $title  = htmlspecialchars($m['title']);
    $link   = (($m['type'] ?? 'movie') === 'series' ? 'series.php' : 'movie.php') . '?id=' . (int)$m['id'];
    return '<a href="' . $link . '" class="card-item">'
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
    <title>Смотрю: <?= htmlspecialchars($movie['title']) ?> — Illuminos</title>
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
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="cabinet.php" class="topuser">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'Профиль') ?>
                </a>
            <?php else: ?>
                <a href="register.php" class="btn-register">Регистрация</a>
                <a href="login.php" class="btn-signin">Войти</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ПАНЕЛЬ УПРАВЛЕНИЯ (НОВОЕ) -->
<div class="player-nav">
    <a href="javascript:history.back()" class="player-back">
        <i class="bi bi-chevron-left"></i> Назад
    </a>
    <span class="player-title"><?= htmlspecialchars($movie['title']) ?></span>
</div>

<!-- ПЛЕЕР -->
<section class="watch-player">
    <video id="player" controls preload="metadata"
           poster="<?= htmlspecialchars($movie['poster'] ?? 'img/placeholder.jpg') ?>">
        <source src="<?= htmlspecialchars($videoUrl) ?>" type="video/mp4">
        Ваш браузер не поддерживает видео.
    </video>
</section>

<main class="page">
    <section class="watch-info">
        <div class="watch-poster">
            <img src="<?= htmlspecialchars($movie['poster'] ?? 'img/placeholder.jpg') ?>"
                 alt="<?= htmlspecialchars($movie['title']) ?>">
        </div>
        <div class="watch-details">
            <h1 class="watch-title"><?= htmlspecialchars($movie['title']) ?></h1>
            <div class="watch-meta">
                <span class="hero-rating">★ <?= $movie['rating'] ?></span>
                <span><?= (int)$movie['year'] ?></span>
                <span><?= htmlspecialchars($movie['genre_name'] ?? '') ?></span>
                <span><?= $movie['type'] === 'series' ? 'Сериал' : 'Фильм' ?></span>
            </div>
            <p class="watch-desc"><?= htmlspecialchars($movie['description']) ?></p>
            <div class="watch-actions">
                <a href="movie.php?id=<?= $movie['id'] ?>" class="btn-secondary">
                    <i class="bi bi-info-circle"></i> Подробнее
                </a>
                <a href="cabinet.php?add_fav=<?= $movie['id'] ?>" class="btn-secondary">
                    <i class="bi bi-heart"></i> В избранное
                </a>
            </div>
        </div>
    </section>

    <?php if ($similar): ?>
        <section class="rail" style="padding-left:0;padding-right:0;">
            <div class="rail-head"><h3>Похожие фильмы</h3></div>
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
            <p class="footer-copy">© <?= date('Y') ?> Illuminos. Все права защищены.</p>
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
                <a href="#"><i class="bi bi-vk"></i></a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>