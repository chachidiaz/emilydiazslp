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
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    $db->exec($stmt);
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
