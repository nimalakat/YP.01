<?php
/**
 * service.php
 * Типовая страница услуги. Принимает id услуги через GET-параметр.
 */

require_once 'db.php'; // подключение к БД

// Получаем id из URL, приводим к целому числу
$id = (int)($_GET['id'] ?? 1);

// Подготовленный запрос для получения одной услуги
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$service = $stmt->fetch();

// Если услуга не найдена — 404
if (!$service) {
    http_response_code(404);
    die('Услуга не найдена');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($service['title']) ?> — Illuminos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Внешний CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Шапка -->
<header class="site-header">
    <div class="container">
        <a href="index.php" class="logo"><strong>Illuminos</strong></a>
    </div>
</header>

<!-- Контент услуги -->
<main class="container py-5">
    <h1><?= htmlspecialchars($service['title']) ?></h1>
    <p class="mt-3"><?= htmlspecialchars($service['description']) ?></p>
    <a href="index.php#contact" class="btn-primary">Заказать услугу</a>
</main>

<!-- Подвал -->
<footer class="site-footer">
    <div class="container">&copy; <?= date('Y') ?> Illuminos</div>
</footer>

</body>
</html>