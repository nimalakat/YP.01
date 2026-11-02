<?php
/**
 * =========================================================
 * config.php — ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
 * Использует PHP (PDO) + SQL (MySQL)
 * =========================================================
 */

// ---------- Параметры подключения к MySQL ----------
define('DB_HOST', 'localhost');    // хост (обычно localhost)
define('DB_USER', 'root');         // логин MySQL
define('DB_PASS', '');             // пароль (пусто в OpenServer)
define('DB_NAME', 'illuminos');    // имя базы данных
define('DB_CHARSET', 'utf8mb4');   // кодировка

// ---------- Базовый URL проекта ----------
define('BASE_URL', '/illuminos/');

// ---------- Запуск сессии ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Подключение к базе через PDO ----------
try {
    // Формируем DSN-строку подключения
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    // Опции PDO (безопасность и удобство)
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // ошибки как исключения
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ассоц. массив
        PDO::ATTR_EMULATE_PREPARES   => false,                  // настоящие подготовленные запросы
    ];

    // Создаём объект PDO — переменная $pdo доступна везде
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Если подключиться не удалось — выводим сообщение
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

/**
 * Экранирование HTML (защита от XSS)
 */
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}