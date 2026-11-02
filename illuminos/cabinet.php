<?php
/**
 * cabinet.php — Личный кабинет пользователя
 */
require_once 'db.php';

// 1. Доступ только для авторизованных
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$uid  = $_SESSION['user_id'];
$name = $_SESSION['user_name'] ?? 'Пользователь';
$role = $_SESSION['user_role'] ?? 'user';

/* ---------- Обработка избранного ---------- */
if (!empty($_GET['add_fav'])) {
    $pdo->prepare("INSERT IGNORE INTO favorites (user_id, movie_id) VALUES (:u, :m)")
        ->execute([':u' => $uid, ':m' => (int)$_GET['add_fav']]);
    header('Location: cabinet.php'); exit;
}
if (!empty($_GET['del_fav'])) {
    $pdo->prepare("DELETE FROM favorites WHERE user_id = :u AND movie_id = :m")
        ->execute([':u' => $uid, ':m' => (int)$_GET['del_fav']]);
    header('Location: cabinet.php'); exit;
}

/* ---------- Запросы к БД ---------- */
// История (без дублей)
$h = $pdo->prepare(
    "SELECT m.*, MAX(vh.viewed_at) AS last_view FROM views_history vh
     JOIN movies m ON m.id = vh.movie_id
     WHERE vh.user_id = :u GROUP BY m.id ORDER BY last_view DESC LIMIT 18"
);
$h->execute([':u' => $uid]);
$history = $h->fetchAll();

// Избранное
$f = $pdo->prepare(
    "SELECT m.* FROM favorites f
     JOIN movies m ON m.id = f.movie_id
     WHERE f.user_id = :u ORDER BY f.added_at DESC"
);
$f->execute([':u' => $uid]);
$favorites = $f->fetchAll();

// Активная подписка
$s = $pdo->prepare(
    "SELECT s.*, t.name AS tariff_name, t.price
     FROM subscriptions s JOIN tariffs t ON t.id = s.tariff_id
     WHERE s.user_id = :u AND s.status = 'active'
     ORDER BY s.id DESC LIMIT 1"
);
$s->execute([':u' => $uid]);
$subscription = $s->fetch();

// Функция карточки
function card(array $m, bool $withDel = false): string {
    $poster = htmlspecialchars($m['poster'] ?? 'img/placeholder.jpg');
    $title  = htmlspecialchars($m['title']);
    $link   = (($m['type'] ?? 'movie') === 'series' ? 'series.php' : 'movie.php') . '?id=' . (int)$m['id'];
    $del    = $withDel ? '<a href="cabinet.php?del_fav=' . $m['id'] . '" class="card-del" title="Удалить">✕</a>' : '';
    return '<div style="position:relative;">' . $del
         . '<a href="' . $link . '" class="card-item">'
         . '<div class="card-poster"><img src="' . $poster . '" alt="' . $title . '" loading="lazy"></div>'
         . '<div class="card-title">' . $title . '</div>'
         . '<div class="card-meta">' . (int)$m['year'] . ' · ★ ' . $m['rating'] . '</div>'
         . '</a></div>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет — Illuminos</title>
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
            <a href="catalog.php?type=movie">Фильмы</a>
            <a href="catalog.php?type=series">Сериалы</a>
            <a href="cabinet.php" class="active">Профиль</a>
        </nav>
        <div class="topbar-actions">
            <a href="cabinet.php" class="topuser active"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($name) ?></a>
            <a href="logout.php" class="btn-register">Выйти</a>
        </div>
    </div>
</header>

<main class="page">
    
    <!-- Шапка профиля -->
    <section class="profile-header">
    <div class="profile-avatar"><i class="bi bi-person-fill"></i></div>
    <div>
        <h1 class="profile-name"><?= htmlspecialchars($name) ?></h1>
        <p class="profile-role">
            Статус: <b>
            <?php 
                if ($role === 'admin') echo 'Администратор';
                elseif ($role === 'subscriber') echo 'Подписчик';
                else echo 'Пользователь';
            ?>
            </b>
        </p>
    </div>

    <?php if ($role === 'admin'): ?>
        <a href="admin.php" class="btn-primary" style="margin-left: auto;">
            <i class="bi bi-shield-lock"></i> Админ-панель
        </a>
    <?php endif; ?>
</section>
    <!-- Вкладки-секции -->
    <div class="cabinet-grid">
        
        <!-- ЛЕВАЯ КОЛОНКА: ПОДПИСКА -->
        <aside class="cabinet-sidebar">
            <div class="cabinet-block">
                <h3 class="block-title"><i class="bi bi-star-fill" style="color:var(--accent)"></i> Подписка</h3>
                <?php if ($subscription): ?>
                    <div class="sub-card active">
                        <div class="sub-name"><?= htmlspecialchars($subscription['tariff_name']) ?></div>
                        <div class="sub-info">Активна до <?= date('d.m.Y', strtotime($subscription['expires_at'])) ?></div>
                        <div class="sub-price"><?= number_format($subscription['price'], 0) ?> ₽</div>
                    </div>
                <?php else: ?>
                    <div class="sub-card empty">
                        <div class="sub-name">Нет подписки</div>
                        <div class="sub-info">Оформите тариф для просмотра</div>
                        <a href="payment.php" class="btn-primary" style="margin-top:16px;width:100%;justify-content:center;">Оформить</a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- ПРАВАЯ КОЛОНКА: КОНТЕНТ -->
        <div class="cabinet-content">

            <!-- ИЗБРАННОЕ -->
            <section class="cabinet-block">
                <h2 class="block-title"><i class="bi bi-heart-fill" style="color:#E50914"></i> Избранное</h2>
                <?php if ($favorites): ?>
                    <div class="grid">
                        <?php foreach ($favorites as $m) echo card($m, true); ?>
                    </div>
                <?php else: ?>
                    <p class="empty">Вы пока ничего не добавили в избранное.</p>
                <?php endif; ?>
            </section>

            <!-- ИСТОРИЯ ПРОСМОТРОВ -->
            <section class="cabinet-block">
                <h2 class="block-title"><i class="bi bi-clock-history"></i> История просмотров</h2>
                <?php if ($history): ?>
                    <div class="grid">
                        <?php foreach ($history as $m) echo card($m); ?>
                    </div>
                <?php else: ?>
                    <p class="empty">Вы пока ничего не смотрели.</p>
                <?php endif; ?>
            </section>

        </div>
    </div>

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
            <a href="payment.php">Подписка</a>
            <a href="logout.php">Выйти</a>
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