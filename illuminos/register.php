<?php
/**
 * register.php — регистрация пользователя
 */
require_once 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (mb_strlen($name) < 2)                        $errors[] = 'Имя слишком короткое';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Некорректный email';
    if (strlen($pass) < 6)                           $errors[] = 'Пароль минимум 6 символов';
    if ($pass !== $pass2)                            $errors[] = 'Пароли не совпадают';

    $chk = $pdo->prepare("SELECT id FROM users WHERE email = :e");
    $chk->execute([':e' => $email]);
    if ($chk->fetch()) $errors[] = 'Email уже занят';

    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:n, :e, :p, 'user')")
            ->execute([':n' => $name, ':e' => $email, ':p' => $hash]);
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — Illuminos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<div class="auth-wrapper">
    <div class="auth-card">

        <a href="index.php" class="auth-logo">ILLUMINOS</a>
        <h1 class="auth-title">Регистрация</h1>
        <p class="auth-subtitle">Создайте аккаунт и начните смотреть</p>

        <?php foreach ($errors as $e): ?>
            <div class="auth-error">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($e) ?>
            </div>
        <?php endforeach; ?>

        <form method="post" class="auth-form">

            <div class="auth-field">
                <label>Имя</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-person"></i>
                    <input type="text" name="name" placeholder="Ваше имя" required>
                </div>
            </div>

            <div class="auth-field">
                <label>Email</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-envelope"></i>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
            </div>

            <div class="auth-field">
                <label>Пароль</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" placeholder="Минимум 6 символов" required>
                </div>
            </div>

            <div class="auth-field">
                <label>Повтор пароля</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="password2" placeholder="Ещё раз" required>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
                <i class="bi bi-person-plus"></i> Зарегистрироваться
            </button>
        </form>

        <p class="auth-footer">
            Уже есть аккаунт? <a href="login.php">Войти</a>
        </p>
    </div>
</div>

</body>
</html>