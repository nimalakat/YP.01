<?php
/**
 * catalog.php — универсальная страница каталога.
 * Принимает ?type=movie|series|cartoon или ?genre=slug
 */
require_once 'db.php';

$type  = trim($_GET['type']  ?? '');
$genre = trim($_GET['genre'] ?? '');

$title  = 'Каталог';
$where  = [];
$params = [];

/* ---------- Определяем, что показывать ---------- */
if ($genre !== '') {
    // Конкретный жанр
    $g = $pdo->prepare("SELECT * FROM genres WHERE slug = :s LIMIT 1");
    $g->execute([':s' => $genre]);
    $gRow = $g->fetch();

    if ($gRow) {
        $title  = $gRow['name'];
        $where[] = 'genre_id = :gid';
        $params[':gid'] = $gRow['id'];
    } else {
        $title = 'Каталог';
    }
} elseif ($type === 'movie') {
    $title   = 'Фильмы';
    $where[] = "type = 'movie'";
} elseif ($type === 'series') {
    $title   = 'Сериалы';
    $where[] = "type = 'series'";
} elseif ($type === 'cartoon') {
    // Мультфильмы = жанр "cartoons" или "kids"
    $g = $pdo->prepare("SELECT id FROM genres WHERE slug IN ('cartoons','kids') LIMIT 1");
    $g->execute();
    $gRow = $g->fetch();
    $title = 'Мультфильмы';
    if ($gRow) {
        $where[] = 'genre_id = :gid';
        $params[':gid'] = $gRow['id'];
    } else {
        $where[] = '1 = 0';
    }
} else {
    $title = 'Все фильмы и сериалы';
}

/* ---------- Запрос ---------- */
$sql = "SELECT * FROM movies";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY rating DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();

/* ---------- Карточка ---------- */
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
    <title><?= htmlspecialchars($title) ?> — Illuminos</title>
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
            <a href="catalog.php?type=movie"   class="<?= $type === 'movie'   ? 'active' : '' ?>">Фильмы</a>
            <a href="catalog.php?type=series"  class="<?= $type === 'series'  ? 'active' : '' ?>">Сериалы</a>
            <a href="index.php#categories">Категории</a>
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
    <h1 class="page-title"><?= htmlspecialchars($title) ?></h1>

    <?php if (!$movies): ?>
        <p class="empty">Пока ничего нет в этом разделе.</p>
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
            <a href="catalog.php?type=movie">Фильмы</a>
            <a href="catalog.php?type=series">Сериалы</a>
        </div>
        <div class="footer-col"><h4>Аккаунт</h4>
            <a href="cabinet.php">Личный кабинет</a>
            <a href="payment.php">Подписка</a>
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