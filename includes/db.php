<?php
/**
 * PDO connection + self-migrating schema.
 * Tables are created automatically on first run — no SQL to run by hand.
 */

require_once __DIR__ . '/config.php';

/**
 * Prefer storing the SQLite file outside public_html (SQLITE_PATH). If that
 * directory can't be created/written on this host, fall back to the
 * .htaccess-protected data/ folder inside public_html (SQLITE_FALLBACK_PATH)
 * so the app still works rather than fataling.
 */
function pjp_resolve_sqlite_path(): string {
    $primaryDir = dirname(SQLITE_PATH);
    if (!is_dir($primaryDir)) {
        @mkdir($primaryDir, 0755, true);
    }
    if (is_dir($primaryDir) && is_writable($primaryDir)) {
        return SQLITE_PATH;
    }

    $fallbackDir = dirname(SQLITE_FALLBACK_PATH);
    if (!is_dir($fallbackDir)) {
        @mkdir($fallbackDir, 0755, true);
    }
    return SQLITE_FALLBACK_PATH;
}

function pjp_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        $path = pjp_resolve_sqlite_path();
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    pjp_migrate($pdo);
    return $pdo;
}

function pjp_migrate(PDO $pdo): void {
    $isMysql = DB_DRIVER === 'mysql';
    $pk = $isMysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $dt = $isMysql ? 'DATETIME' : 'TEXT'; // ISO 8601 strings work fine in SQLite
    $text = $isMysql ? 'TEXT' : 'TEXT';
    $engine = $isMysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id $pk,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at $dt NOT NULL
    )$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id $pk,
        created_at $dt NOT NULL,
        name VARCHAR(200),
        email VARCHAR(200),
        phone VARCHAR(100),
        subject VARCHAR(255),
        message $text,
        source_page VARCHAR(255),
        raw_data $text,
        is_read INTEGER NOT NULL DEFAULT 0
    )$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id $pk,
        slug VARCHAR(255) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        excerpt $text,
        body $text,
        image VARCHAR(500),
        category VARCHAR(100),
        status VARCHAR(20) NOT NULL DEFAULT 'draft',
        display_start $dt,
        display_end $dt,
        created_at $dt NOT NULL,
        updated_at $dt NOT NULL
    )$engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id $pk,
        message $text NOT NULL,
        link_url VARCHAR(500),
        link_text VARCHAR(100),
        style VARCHAR(20) NOT NULL DEFAULT 'info',
        start_at $dt,
        end_at $dt,
        active INTEGER NOT NULL DEFAULT 1,
        created_at $dt NOT NULL
    )$engine");
}

/** Current time as an ISO-8601-ish string that sorts/compares correctly in both drivers. */
function pjp_now(): string {
    return gmdate('Y-m-d H:i:s');
}
