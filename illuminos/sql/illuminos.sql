-- =====================================================
-- База данных онлайн-кинотеатра Illuminos
-- =====================================================
CREATE DATABASE IF NOT EXISTS illuminos_db
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE illuminos_db;

-- ---------- Жанры (категории) ----------
CREATE TABLE genres (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(50) NOT NULL UNIQUE,
    slug  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Фильмы и сериалы ----------
CREATE TABLE movies (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    poster       VARCHAR(255),
    year         YEAR,
    rating       DECIMAL(3,1) DEFAULT 0,
    type         ENUM('movie','series') DEFAULT 'movie',
    is_new       TINYINT(1) DEFAULT 0,
    views        INT DEFAULT 0,
    genre_id     INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Пользователи ----------
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100),
    email      VARCHAR(150) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- История просмотров (для рекомендаций) ----------
CREATE TABLE views_history (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    movie_id  INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Тестовые жанры ----------
INSERT INTO genres (name, slug) VALUES
('Драмы',      'drama'),
('Комедии',    'comedy'),
('Ужасы',      'horror'),
('Фантастика', 'sci-fi'),
('Триллеры',   'thriller'),
('Детям',      'kids');

-- ---------- Тестовые фильмы ----------
INSERT INTO movies (title, description, year, rating, type, is_new, views, genre_id) VALUES
('Свет далёкой звезды', 'Фантастическая драма о поиске дома',   2025, 8.9, 'movie',  1, 21000, 4),
('Тени прошлого',       'Психологический триллер',               2025, 8.1, 'series', 1, 17500, 5),
('Тёплый вечер',        'Романтическая комедия',                 2024, 7.4, 'movie',  1, 13200, 2),
('Ночной свет',         'Мистический хоррор в лесу',             2024, 6.9, 'movie',  0, 19800, 3),
('Пробуждение',         'Драма о втором шансе',                  2025, 9.0, 'series', 1, 32000, 1),
('Игра теней',          'Детективный триллер',                   2023, 8.3, 'movie',  0, 24500, 5);

-- ---------- Тестовый пользователь и история ----------
INSERT INTO users (name, email) VALUES ('Гость', 'guest@illuminos.ru');
INSERT INTO views_history (user_id, movie_id) VALUES (1, 5), (1, 2), (1, 6);
-- Добавляем поле is_featured: 1 = показывать в главном баннере
ALTER TABLE movies ADD COLUMN is_featured TINYINT(1) DEFAULT 0;

-- Отмечаем 4 фильма как «избранные для баннера»
UPDATE movies SET is_featured = 1 WHERE id IN (1, 2, 5, 6);