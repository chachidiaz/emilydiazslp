<?php
// MySQL connection and schema migrations. Migrations run once each, in order, on first use.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $db = null;
    if ($db) {
        return $db;
    }
    if (!config('db.name')) {
        http_response_code(503);
        exit('The course app is not set up yet: data/config.php has no database settings.');
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        config('db.host', 'localhost'), (int) config('db.port', 3306), config('db.name'));
    $db = new PDO($dsn, (string) config('db.user'), (string) config('db.pass', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $db->exec("SET time_zone = '+00:00'");
    migrate($db);
    return $db;
}

/** Each entry runs once. Never edit a shipped migration; add a new one. */
function migrations(): array
{
    return [
        1 => <<<SQL
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NULL,
  google_sub VARCHAR(64) NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  status ENUM('active','suspended','invited') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME NULL,
  session_version INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  last_sign_in_at DATETIME NULL,
  UNIQUE KEY users_email (email),
  UNIQUE KEY users_google (google_sub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind ENUM('reset','magic') NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY auth_tokens_hash (token_hash),
  KEY auth_tokens_user (user_id, kind),
  CONSTRAINT auth_tokens_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
  bucket VARCHAR(190) NOT NULL,
  at DATETIME NOT NULL,
  KEY rate_limits_bucket (bucket, at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
        2 => <<<SQL
ALTER TABLE users
  ADD COLUMN avatar_file VARCHAR(80) NULL,
  ADD COLUMN prefs TEXT NULL,
  ADD COLUMN pending_email VARCHAR(190) NULL;

ALTER TABLE auth_tokens MODIFY kind ENUM('reset','magic','email') NOT NULL;

CREATE TABLE user_sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  sid_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  last_seen_at DATETIME NOT NULL,
  UNIQUE KEY user_sessions_sid (sid_hash),
  KEY user_sessions_user (user_id),
  CONSTRAINT user_sessions_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL,
  title VARCHAR(160) NOT NULL,
  category VARCHAR(60) NOT NULL DEFAULT '',
  instructor VARCHAR(120) NOT NULL DEFAULT '',
  summary TEXT NULL,
  cover_bg VARCHAR(20) NOT NULL DEFAULT '',
  cover_art TEXT NULL,
  cover_image VARCHAR(255) NULL,
  price_cents INT UNSIGNED NOT NULL DEFAULT 0,
  compare_at_cents INT UNSIGNED NULL,
  published TINYINT(1) NOT NULL DEFAULT 1,
  sort INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY courses_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  section VARCHAR(120) NOT NULL DEFAULT '',
  position INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  minutes INT UNSIGNED NOT NULL DEFAULT 0,
  video_url VARCHAR(500) NULL,
  captions_url VARCHAR(500) NULL,
  description TEXT NULL,
  practice TEXT NULL,
  UNIQUE KEY lessons_course_pos (course_id, position),
  CONSTRAINT lessons_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enrollments (
  user_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  has_access TINYINT(1) NOT NULL DEFAULT 1,
  source ENUM('purchase','admin') NOT NULL DEFAULT 'admin',
  granted_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, course_id),
  CONSTRAINT enrollments_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT enrollments_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lesson_progress (
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  completed_at DATETIME NULL,
  position_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, lesson_id),
  KEY lesson_progress_recent (user_id, updated_at),
  CONSTRAINT lesson_progress_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT lesson_progress_lesson_fk FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE downloads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NULL,
  label VARCHAR(160) NOT NULL,
  file_type VARCHAR(10) NOT NULL DEFAULT 'PDF',
  size_label VARCHAR(20) NOT NULL DEFAULT '',
  file VARCHAR(500) NULL,
  sort INT NOT NULL DEFAULT 0,
  KEY downloads_course (course_id, lesson_id),
  CONSTRAINT downloads_course_fk FOREIGN KEY (course_id) REFERENCES courses (id) ON DELETE CASCADE,
  CONSTRAINT downloads_lesson_fk FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL,
        // Sample catalog from the design canvas. Replace with Emily's real courses before launch.
        3 => function (PDO $db): void {
            require_once __DIR__ . '/sample_catalog.php';
            seed_sample_catalog($db);
        },
    ];
}

function migrate(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version INT UNSIGNED PRIMARY KEY, ran_at DATETIME NOT NULL) ENGINE=InnoDB');
    $done = array_map('intval', $db->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN));
    foreach (migrations() as $version => $sql) {
        if (in_array($version, $done, true)) {
            continue;
        }
        // Take a lock so two first requests don't both run the same migration.
        $db->query("SELECT GET_LOCK('courses_migrate', 30)");
        try {
            $again = $db->prepare('SELECT 1 FROM schema_migrations WHERE version = ?');
            $again->execute([$version]);
            if (!$again->fetchColumn()) {
                if ($sql instanceof Closure) {
                    $sql($db);
                } else {
                    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                        $db->exec($stmt);
                    }
                }
                $db->prepare('INSERT INTO schema_migrations (version, ran_at) VALUES (?, UTC_TIMESTAMP())')->execute([$version]);
            }
        } finally {
            $db->query("SELECT RELEASE_LOCK('courses_migrate')");
        }
    }
}

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
