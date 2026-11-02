<?php
/**
 * admin.php — Панель администратора (управление фильмами и пользователями)
 */
require_once 'db.php';

// 1. Проверка: пускаем только админа
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Доступ запрещен. Вы не администратор.');
}

// 2. Удаление фильма
if (!empty($_GET['del_movie'])) {
    $id = (int)$_GET['del_movie'];
    $pdo->prepare("DELETE FROM movies WHERE id = :id")->execute([':id' => $id]);
    header('Location: admin.php'); exit;
}

// 3. Добавление фильма
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $year  = (int)($_POST['year'] ?? 2024);
    $type  = $_POST['type'] ?? 'movie';
    $gid   = (int)($_POST['genre_id'] ?? 1);

    if ($title !== '') {
        $pdo->prepare("INSERT INTO movies (title, description, year, type, genre_id, poster) 
                       VALUES (:t, :d, :y, :ty, :g, 'img/poster.jpg')")
            ->execute([':t' => $title, ':d' => $desc, ':y' => $year, ':ty' => $type, ':g' => $gid]);
        header('Location: admin.php'); exit;
    }
}

// 4. Смена роли пользователя
if (!empty($_GET['change_role'])) {
    $uid = (int)$_GET['uid'];
    $newRole = $_GET['change_role'];
    $pdo->prepare("UPDATE users SET role = :r WHERE id = :id")->execute([':r' => $newRole, ':id' => $uid]);
    header('Location: admin.php'); exit;
}

// Получаем данные для вывода
$movies = $pdo->query("SELECT * FROM movies ORDER BY id DESC")->fetchAll();
$users  = $pdo->query("SELECT * FROM users ORDER BY id")->fetchAll();
$genres = $pdo->query("SELECT * FROM genres")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель — Illuminos</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; background: var(--bg-soft); }
        .admin-table th, .admin-table td { padding: 12px; border: 1px solid var(--border); text-align: left; }
        .admin-table th { background: var(--bg); color: var(--accent); }
        .admin-form { background: var(--bg-soft); padding: 24px; border-radius: 12px; margin-bottom: 40px; display: grid; gap: 12px; max-width: 500px; }
        .admin-form input, .admin-form select, .admin-form textarea { padding: 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg); color: #fff; }
        .btn-del { color: #E50914; font-weight: bold; text-decoration: none; }
        .btn-del:hover { text-decoration: underline; }
    </style>
</head>
<body style="padding: 40px; background: var(--bg); color: var(--text);">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h1 style="color: var(--accent); margin-bottom: 30px;">Панель управления (Админ)</h1>
        <a href="index.php" style="color: #fff; margin-bottom: 20px; display: inline-block;">← На сайт</a>

        <!-- Пользователи -->
        <h2>Пользователи</h2>
        <table class="admin-table">
            <tr><th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th>Действие</th></tr>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><b><?= $u['role'] ?></b></td>
                <td>
                    <?php if ($u['role'] !== 'admin'): ?>
                        <a href="?uid=<?= $u['id'] ?>&change_role=admin" style="color: var(--accent);">Сделать админом</a>
                    <?php else: ?>
                        <a href="?uid=<?= $u['id'] ?>&change_role=user" style="color: var(--text-dim);">Снять права</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Добавление фильма -->
        <h2>Добавить новый фильм</h2>
        <form method="post" class="admin-form">
            <input type="hidden" name="add_movie" value="1">
            <input type="text" name="title" placeholder="Название фильма" required>
            <textarea name="description" placeholder="Описание" rows="3"></textarea>
            <input type="number" name="year" placeholder="Год" value="2024">
            <select name="type">
                <option value="movie">Фильм</option>
                <option value="series">Сериал</option>
            </select>
            <select name="genre_id">
                <?php foreach ($genres as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary">Добавить</button>
        </form>

        <!-- Список фильмов -->
        <h2>Список фильмов</h2>
        <table class="admin-table">
            <tr><th>ID</th><th>Название</th><th>Год</th><th>Тип</th><th>Действие</th></tr>
            <?php foreach ($movies as $m): ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td><?= htmlspecialchars($m['title']) ?></td>
                <td><?= $m['year'] ?></td>
                <td><?= $m['type'] ?></td>
                <td><a href="?del_movie=<?= $m['id'] ?>" class="btn-del" onclick="return confirm('Удалить фильм?')">Удалить</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>