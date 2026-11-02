<?php
/**
 * payment.php — страница оплаты тарифов
 */
require_once 'db.php';

$uid = $_SESSION['user_id'] ?? null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $uid) {
    $tariff_id = (int)($_POST['tariff_id'] ?? 0);
    $method    = $_POST['method'] ?? 'card';

    $t = $pdo->prepare("SELECT * FROM tariffs WHERE id = :id");
    $t->execute([':id' => $tariff_id]);
    $tariff = $t->fetch();

    if ($tariff) {
        $pdo->prepare("INSERT INTO payments (user_id, tariff_id, amount, method, status)
                       VALUES (:u, :t, :a, :m, 'paid')")
            ->execute([':u' => $uid, ':t' => $tariff_id, ':a' => $tariff['price'], ':m' => $method]);

        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $pdo->prepare("INSERT INTO subscriptions (user_id, tariff_id, expires_at, status)
                       VALUES (:u, :t, :e, 'active')")
            ->execute([':u' => $uid, ':t' => $tariff_id, ':e' => $expires]);

        $success = true;
    }
}

$tariffs = $pdo->query("SELECT * FROM tariffs ORDER BY price")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подписка — Illuminos</title>
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
            <a href="cabinet.php">Профиль</a>
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
    <h1 class="page-title" style="text-align:center;">Выберите тариф</h1>
    <p class="empty" style="padding-top:0;">Отмена в любой момент. Без скрытых платежей.</p>

    <?php if ($success): ?>
        <div class="auth-error" style="background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.4);color:#86EFAC;text-align:center;">
            <i class="bi bi-check-circle"></i> Оплата прошла успешно! Подписка активирована.
        </div>
    <?php endif; ?>

    <?php if (!$uid): ?>
        <div class="auth-error" style="text-align:center;">
            Для покупки нужно <a href="login.php" style="color:var(--accent)">войти</a>.
        </div>
    <?php endif; ?>

    <div class="tariffs-grid">
        <?php foreach ($tariffs as $t): ?>
            <div class="tariff-card <?= $t['is_popular'] ? 'popular' : '' ?>">
                <?php if ($t['is_popular']): ?>
                    <div class="tariff-badge">Популярный</div>
                <?php endif; ?>
                <h3 class="tariff-name"><?= htmlspecialchars($t['name']) ?></h3>
                <div class="tariff-price">
                    <?= number_format($t['price'], 0) ?> ₽
                    <small>/<?= htmlspecialchars($t['period']) ?></small>
                </div>
                <ul class="tariff-features">
                    <?php foreach (explode('|', $t['features']) as $feat): ?>
                        <li><i class="bi bi-check-lg" style="color:var(--accent)"></i> <?= htmlspecialchars(trim($feat)) ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($uid): ?>
                    <form method="post">
                        <input type="hidden" name="tariff_id" value="<?= $t['id'] ?>">
                        <select name="method" class="pay-select">
                            <option value="card">Банковская карта</option>
                            <option value="sbp">СБП</option>
                            <option value="wallet">Кошелёк</option>
                        </select>
                        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
                            Оплатить
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn-secondary" style="width:100%;justify-content:center;">Войти для оплаты</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
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