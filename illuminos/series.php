<?php
/**
 * series.php — страница сериала (стиль OKKO)
 */
require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT m.*, g.name AS genre_name FROM movies m
     LEFT JOIN genres g ON g.id = m.genre_id
     WHERE m.id = :id AND m.type = 'series' LIMIT 1"
);
$stmt->execute([':id' => $id]);
$series = $stmt->fetch();

if (!$series) { http_response_code(404); die('Сериал не найден'); }

if (!empty($_SESSION['user_id'])) {
    $pdo->prepare("INSERT INTO views_history (user_id, movie_id) VALUES (:u, :m)")
        ->execute([':u' => $_SESSION['user_id'], ':m' => $id]);
}

/* Сезоны */
$se = $pdo->prepare("SELECT * FROM seasons WHERE movie_id = :id ORDER BY number");
$se->execute([':id' => $id]);
$seasons = $se->fetchAll();

$episodesBySeason = [];
foreach ($seasons as $s) {
    $ep = $pdo->prepare("SELECT * FROM episodes WHERE season_id = :sid ORDER BY number");
    $ep->execute([':sid' => $s['id']]);
    $episodesBySeason[$s['id']] = $ep->fetchAll();
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
    "SELECT * FROM movies WHERE genre_id = :g AND id != :id AND type = 'series'
     ORDER BY rating DESC LIMIT 12"
);
$s->execute([':g' => $series['genre_id'], ':id' => $id]);
$similar = $s->fetchAll();

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
    <title><?= htmlspecialchars($series['title']) ?> — Illuminos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

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

<!-- Исправлена ссылка на watch.php -->
<section class="movie-hero" style="background-image:url('<?= htmlspecialchars($series['poster'] ?? 'img/placeholder.jpg') ?>')">
    <div class="movie-hero-overlay"></div>
    <div class="movie-hero-content">
        <div class="movie-hero-meta">
            <span class="hero-rating">★ <?= $series['rating'] ?></span>
            <span><?= (int)$series['year'] ?></span>
            <span><?= htmlspecialchars($series['genre_name'] ?? '') ?></span>
            <span>Сериал</span>
        </div>
        <h1 class="movie-hero-title"><?= htmlspecialchars($series['title']) ?></h1>
        <p class="movie-hero-desc"><?= htmlspecialchars($series['description']) ?></p>
        <div class="hero-actions">
            <a href="watch.php?id=<?= $series['id'] ?>" class="btn-primary">
                <i class="bi bi-play-fill"></i> Смотреть
            </a>
            <a href="cabinet.php?add_fav=<?= $series['id'] ?>" class="btn-secondary">
                <i class="bi bi-heart"></i> В избранное
            </a>
        </div>
    </div>
</section>

<main class="page">

    <!-- СЕЗОНЫ -->
    <?php if ($seasons): ?>
        <section class="content-block">
            <h2 class="block-title">Сезоны и эпизоды</h2>
            <div class="accordion" id="seasonsAcc">
                <?php foreach ($seasons as $s): ?>
                    <div class="accordion-item bg-dark text-white border-secondary">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed bg-dark text-white"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#season<?= $s['id'] ?>">
                                Сезон <?= (int)$s['number'] ?> — <?= htmlspecialchars($s['title'] ?? '') ?>
                            </button>
                        </h2>
                        <div id="season<?= $s['id'] ?>" class="accordion-collapse collapse"
                             data-bs-parent="#seasonsAcc">
                            <div class="accordion-body">
                                <?php foreach ($episodesBySeason[$s['id']] as $ep): ?>
                                    <div class="episode-row">
                                        <span><b><?= (int)$ep['number'] ?>.</b> <?= htmlspecialchars($ep['title'] ?? '') ?></span>
                                        <span class="text-secondary"><?= (int)$ep['duration'] ?> мин</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- ТРЕЙЛЕР -->
    <?php if (!empty($series['trailer'])): ?>
        <section class="content-block">
            <h2 class="block-title">Трейлер</h2>
            <div class="ratio ratio-16x9">
                <iframe src="<?= htmlspecialchars($series['trailer']) ?>" allowfullscreen></iframe>
            </div>
        </section>
    <?php endif; ?>

    <!-- АКТЁРЫ -->
    <?php if ($persons): ?>
        <section class="content-block">
            <h2 class="block-title">Актёры и режиссёры</h2>
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
        <section class="rail" style="padding-left:0;padding-right:0;">
            <div class="rail-head"><h3>Похожие сериалы</h3></div>
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
            <a href="index.php">Главная</a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>