<?php
/**
 * login.php — авторизация (со встроенным созданием админа)
 */
require_once 'db.php';

// =========================================================
// АВТОМАТИЧЕСКОЕ СОЗДАНИЕ АДМИНА admin26
// Проверяем, есть ли такой пользователь, если нет - создаем
// =========================================================
$checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = 'admin26'");
$checkAdmin->execute();
if (!$checkAdmin->fetch()) {
    $hash = password_hash('123456', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Админ 26', 'admin26', :p, 'admin')")
        ->execute([':p' => $hash]);
}

// =========================================================
// СТАНДАРТНАЯ ЛОГИКА ВХОДА
// =========================================================
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email)) {
        $error = 'Введите логин';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'] ?? '')) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            header('Location: cabinet.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — Illuminos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

<div class="auth-wrapper">
    <div class="auth-card">
        <a href="index.php" class="auth-logo">ILLUMINOS</a>
        <h1 class="auth-title">Вход</h1>
        <p class="auth-subtitle">Войдите, чтобы продолжить</p>

        <?php if ($error): ?>
            <div class="auth-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <div class="auth-field">
                <label>Логин или Email</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-person"></i>
                    <!-- Изменили type="email" на type="text", чтобы принимало admin26 -->
                    <input type="text" name="email" placeholder="Ваш логин" required>
                </div>
            </div>
            <div class="auth-field">
                <label>Пароль</label>
                <div class="auth-input-wrap">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
                <i class="bi bi-box-arrow-in-right"></i> Войти
            </button>
        </form>

        <p class="auth-footer">Нет аккаунта? <a href="register.php">Создать</a></p>
    </div>
</div>

</body>
</html>