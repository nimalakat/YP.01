<?php
/**
 * index.php — главная страница Illuminos (стиль OKKO)
 *
 * Задание 3. Реализованы разделы:
 *   1. Топ новинки
 *   2. Популярные фильмы/сериалы
 *   3. Категории контента (жанры)
 *   4. Персональные рекомендации
 *   5. Поиск по фильмам/сериалам
 */
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;

/* ==========================================================
   1. ПОИСК по фильмам и сериалам
   ========================================================== */
$search = trim($_GET['q'] ?? '');
$searchResults = [];

if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM movies
         WHERE title LIKE :q1 OR description LIKE :q2
         ORDER BY rating DESC
         LIMIT 40"
    );
    $stmt->execute([
        ':q1' => '%' . $search . '%',
        ':q2' => '%' . $search . '%',
    ]);
    $searchResults = $stmt->fetchAll();
}

/* ==========================================================
   2. ФИЛЬМЫ ДЛЯ ГЛАВНОГО БАННЕРА
   ========================================================== */
$featured = $pdo->query(
    "SELECT * FROM movies WHERE is_featured = 1 ORDER BY rating DESC LIMIT 5"
)->fetchAll();
if (empty($featured)) {
    $featured = $pdo->query("SELECT * FROM movies ORDER BY rating DESC LIMIT 5")->fetchAll();
}
/* ==========================================================
   3. ПРОДОЛЖИТЬ ПРОСМОТР (только для авторизованных)
   ========================================================== */
   $continueWatching = [];
   if ($user_id) {
       // Используем GROUP BY вместо DISTINCT — так можно сортировать
       // по viewed_at, не добавляя его в SELECT.
       $cw = $pdo->prepare(
           "SELECT m.*, MAX(vh.viewed_at) AS last_view
            FROM views_history vh
            JOIN movies m ON m.id = vh.movie_id
            WHERE vh.user_id = :u
            GROUP BY m.id
            ORDER BY last_view DESC
            LIMIT 12"
       );
       $cw->execute([':u' => $user_id]);
       $continueWatching = $cw->fetchAll();
   }

/* ==========================================================
   4. НОВИНКИ — ТОП
   ========================================================== */
$newMovies = $pdo->query(
    "SELECT * FROM movies WHERE is_new = 1
     ORDER BY created_at DESC LIMIT 15"
)->fetchAll();

/* ==========================================================
   5. ПОПУЛЯРНОЕ — по количеству просмотров
   ========================================================== */
$popular = $pdo->query(
    "SELECT * FROM movies ORDER BY views DESC LIMIT 15"
)->fetchAll();

/* ==========================================================
   6. ТОП СЕРИАЛОВ и ТОП ФИЛЬМОВ
   ========================================================== */
$topSeries = $pdo->query(
    "SELECT * FROM movies WHERE type = 'series'
     ORDER BY rating DESC LIMIT 15"
)->fetchAll();

$topMovies = $pdo->query(
    "SELECT * FROM movies WHERE type = 'movie'
     ORDER BY rating DESC LIMIT 15"
)->fetchAll();

/* ==========================================================
   7. ПЕРСОНАЛЬНЫЕ РЕКОМЕНДАЦИИ
   ========================================================== */
$recommendations = [];

if ($user_id) {
    // Определяем любимый жанр пользователя по истории просмотров
    $recomStmt = $pdo->prepare(
        "SELECT m.*
         FROM movies m
         WHERE m.genre_id = (
             SELECT m2.genre_id
             FROM views_history vh
             JOIN movies m2 ON m2.id = vh.movie_id
             WHERE vh.user_id = :uid1
             GROUP BY m2.genre_id
             ORDER BY COUNT(*) DESC
             LIMIT 1
         )
         AND m.id NOT IN (
             SELECT movie_id FROM views_history WHERE user_id = :uid2
         )
         ORDER BY m.rating DESC
         LIMIT 15"
    );
    $recomStmt->execute([
        ':uid1' => $user_id,
        ':uid2' => $user_id,
    ]);
    $recommendations = $recomStmt->fetchAll();
}

// Для гостя или если рекомендаций нет — показываем случайные
if (empty($recommendations)) {
    $recommendations = $pdo->query(
        "SELECT * FROM movies ORDER BY RAND() LIMIT 15"
    )->fetchAll();
}

/* ==========================================================
   8. КАТЕГОРИИ (жанры)
   ========================================================== */
$genres = $pdo->query("SELECT * FROM genres ORDER BY name")->fetchAll();

/* ==========================================================
   Функция карточки фильма
   ========================================================== */
function card(array $m): string {
    $poster = htmlspecialchars($m['poster'] ?? 'img/placeholder.jpg');
    $title  = htmlspecialchars($m['title']);
    $link   = (($m['type'] ?? 'movie') === 'series' ? 'series.php' : 'movie.php') . '?id=' . (int)$m['id'];
    $badge  = !empty($m['is_new']) ? '<span class="card-badge">Новинка</span>' : '';

    return '<a href="' . $link . '" class="card-item">'
         . $badge
         . '<div class="card-poster"><img src="' . $poster . '" alt="' . $title . '" loading="lazy"></div>'
         . '<div class="card-title">' . $title . '</div>'
         . '<div class="card-meta">' . (int)$m['year'] . ' · ★ ' . $m['rating'] . '</div>'
         . '</a>';
}

/* ==========================================================
   Функция ряда (rail) — переиспользуемый блок
   ========================================================== */
function rail(string $title, array $items, string $moreLink = ''): string {
    if (!$items) return '';
    $html  = '<section class="rail">';
    $html .= '<div class="rail-head"><h3>' . htmlspecialchars($title) . '</h3>';
    if ($moreLink) {
        $html .= '<a href="' . htmlspecialchars($moreLink) . '" class="rail-more">Смотреть все →</a>';
    }
    $html .= '</div>';
    $html .= '<div class="rail-track">';
    foreach ($items as $m) $html .= card($m);
    $html .= '</div></section>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Illuminos — онлайн-кинотеатр</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="okko-layout">

<!-- ================== ШАПКА ================== -->
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">ILLUMINOS</a>

        <nav class="topnav">
            <a href="index.php" class="active">Главная</a>
            <a href="catalog.php?type=movie">Фильмы</a>
            <a href="catalog.php?type=series">Сериалы</a>
            <a href="#categories">Категории</a>
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

<?php if ($search !== ''): ?>
<!-- ================== РЕЗУЛЬТАТЫ ПОИСКА ================== -->
<main class="page">
    <h1 class="page-title">
        <i class="bi bi-search"></i> Результаты: «<?= htmlspecialchars($search) ?>»
    </h1>

    <?php if (!$searchResults): ?>
        <p class="empty">Ничего не найдено. Попробуйте другой запрос.</p>
    <?php else: ?>
        <p class="page-subtitle">Найдено: <b><?= count($searchResults) ?></b></p>
        <div class="grid">
            <?php foreach ($searchResults as $m) echo card($m); ?>
        </div>
    <?php endif; ?>
</main>

<?php else: ?>

<!-- ================== HERO-КАРУСЕЛЬ ================== -->
<section class="hero-block">
    <div class="hero-slider" id="heroSlider">
        <?php foreach ($featured as $i => $m):
            $poster = htmlspecialchars($m['poster'] ?? 'img/placeholder.jpg');
            $link   = ($m['type'] === 'series' ? 'series.php' : 'movie.php') . '?id=' . $m['id'];
        ?>
            <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
                 style="background-image:url('<?= $poster ?>')">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="hero-meta">
                        <span class="hero-rating">★ <?= $m['rating'] ?></span>
                        <span><?= (int)$m['year'] ?></span>
                        <span><?= $m['type'] === 'series' ? 'Сериал' : 'Фильм' ?></span>
                        <?php if (!empty($m['is_new'])): ?>
                            <span class="hero-new">ПРЕМЬЕРА</span>
                        <?php endif; ?>
                    </div>
                    <h2 class="hero-title"><?= htmlspecialchars($m['title']) ?></h2>
                    <p class="hero-desc"><?= htmlspecialchars($m['description']) ?></p>
                    <div class="hero-actions">
                    <a href="watch.php?id=<?= $m['id'] ?>" class="btn-primary">
                            <i class="bi bi-play-fill"></i> Смотреть
                        </a>
                        <a href="<?= $link ?>" class="btn-secondary">
                            <i class="bi bi-info-circle"></i> Подробнее
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <button class="hero-arrow hero-prev" onclick="heroMove(-1)" aria-label="Назад">‹</button>
        <button class="hero-arrow hero-next" onclick="heroMove(1)"  aria-label="Вперёд">›</button>

        <div class="hero-dots">
            <?php foreach ($featured as $i => $m): ?>
                <span class="dot <?= $i === 0 ? 'active' : '' ?>"
                      onclick="heroGo(<?= $i ?>)"></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================== ПАНЕЛЬ КАТЕГОРИЙ ================== -->
<section class="category-bar" id="categories">
    <div class="category-track">
        <a href="index.php" class="cat-chip active">
            <i class="bi bi-house-door-fill"></i> Всё
        </a>
        <?php foreach ($genres as $g): ?>
            <a href="genre.php?slug=<?= urlencode($g['slug']) ?>" class="cat-chip">
                <?= htmlspecialchars($g['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ================== ПРОДОЛЖИТЬ ПРОСМОТР ================== -->
<?= rail('Продолжить просмотр', $continueWatching) ?>

<!-- ================== НОВИНКИ ================== -->
<?= rail('Новинки', $newMovies, 'catalog.php?type=movie') ?>

<!-- ================== БОЛЬШОЙ БАННЕР ================== -->
<?php if (!empty($featured[1])): $b = $featured[1]; ?>
<section class="big-banner">
    <div class="big-banner-bg"
         style="background-image:url('<?= htmlspecialchars($b['poster'] ?? 'img/placeholder.jpg') ?>')">
    </div>
    <div class="big-banner-content">
        <span class="big-banner-label">Рекомендуем</span>
        <h2><?= htmlspecialchars($b['title']) ?></h2>
        <p><?= htmlspecialchars($b['description']) ?></p>
        <div class="hero-actions">
            <a href="<?= $b['type'] === 'series' ? 'series.php' : 'movie.php' ?>?id=<?= $b['id'] ?>"
               class="btn-primary">
                <i class="bi bi-play-fill"></i> Смотреть
            </a>
            <a href="<?= $b['type'] === 'series' ? 'series.php' : 'movie.php' ?>?id=<?= $b['id'] ?>"
               class="btn-secondary">
                Подробнее
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================== ПОПУЛЯРНОЕ ================== -->
<?= rail('Популярное', $popular) ?>

<!-- ================== ТОП СЕРИАЛОВ ================== -->
<?= rail('Сериалы в топе', $topSeries, 'catalog.php?type=series') ?>

<!-- ================== ТОП ФИЛЬМОВ ================== -->
<?= rail('Фильмы в топе', $topMovies, 'catalog.php?type=movie') ?>

<!-- ================== РЕКОМЕНДАЦИИ ================== -->
<?= rail('Рекомендуем вам', $recommendations) ?>

<?php endif; ?>

<!-- ================== ПОДВАЛ ================== -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-col">
            <div class="footer-brand">ILLUMINOS</div>
            <p class="footer-copy">© <?= date('Y') ?> Illuminos. Все права защищены.</p>
        </div>
        <div class="footer-col">
            <h4>Разделы</h4>
            <a href="catalog.php?type=movie">Фильмы</a>
            <a href="catalog.php?type=series">Сериалы</a>
        </div>
        <div class="footer-col">
            <h4>Аккаунт</h4>
            <a href="cabinet.php">Личный кабинет</a>
            <a href="payment.php">Подписка</a>
            <a href="login.php">Войти</a>
        </div>
        <div class="footer-col">
            <h4>Соцсети</h4>
            <div class="socials">
                <a href="#"><i class="bi bi-telegram"></i></a>
                <a href="#"><i class="bi bi-youtube"></i></a>
                <a href="#"><i class="bi bi-vk"></i></a>
            </div>
        </div>
    </div>
</footer>

<script>
/* Простая карусель баннера */
let heroIndex = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots   = document.querySelectorAll('.hero-dots .dot');

function heroShow(i) {
    if (!slides.length) return;
    heroIndex = (i + slides.length) % slides.length;
    slides.forEach((s, k) => s.classList.toggle('active', k === heroIndex));
    dots.forEach((d, k) => d.classList.toggle('active', k === heroIndex));
}
function heroMove(dir) { heroShow(heroIndex + dir); }
function heroGo(i)     { heroShow(i); }

setInterval(() => heroMove(1), 6000);
</script>
</body>
</html>