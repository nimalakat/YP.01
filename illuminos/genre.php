<?php
/**
 * genre.php — все фильмы категории
 */
require_once 'db.php';

$slug = trim($_GET['slug'] ?? '');

$g = $pdo->prepare("SELECT * FROM genres WHERE slug = :s LIMIT 1");
$g->execute([':s' => $slug]);
$genre = $g->fetch();

if (!$genre) { http_response_code(404); die('Жанр не найден'); }

$m = $pdo->prepare("SELECT * FROM movies WHERE genre_id = :g ORDER BY rating DESC");
$m->execute([':g' => $genre['id']]);
$movies = $m->fetchAll();

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
    <title><?= htmlspecialchars($genre['name']) ?> — Illuminos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">ILLUMINOS</a>
        <nav class="topnav">
            <a href="index.php">Главная</a>
            <a href="genre.php?slug=drama">Фильмы</a>
            <a href="genre.php?slug=sci-fi">Сериалы</a>
        </nav>
        <div class="topbar-actions">
    <form class="topsearch" method="get" action="index.php">
        <i class="bi bi-search"></i>
        <input type="text" name="q" placeholder="Поиск" value="<?= htmlspecialchars($search ?? '') ?>">
    </form>

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

<main class="page">
    <h1 class="page-title"><?= htmlspecialchars($genre['name']) ?></h1>

    <?php if (!$movies): ?>
        <p class="empty">В этой категории пока нет фильмов.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($movies as $m) echo card($m); ?>
        </div>
    <?php endif; ?>
</main>

<footer class="footer">
    <div class="footer-inner">
        <div class="footer-col">
            <div class="footer-brand">ILLUMINOS</div>
            <p class="footer-copy">© <?= date('Y') ?> Illuminos.</p>
        </div>
        <div class="footer-col"><h4>Разделы</h4>
            <a href="index.php">Главная</a>
        </div>
        <div class="footer-col"><h4>Аккаунт</h4>
            <a href="cabinet.php">Личный кабинет</a>
            <a href="payment.php">Подписка</a>
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