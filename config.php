<?php
/**
 * config.php
 * Shared configuration for the entire site.
 * Include at the top of all PHP pages.
 */

// ┌─────────────────────────────────────────────────────────────────────────────
// │ 1. DATABASE
// └─────────────────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
define('TABLE_PREFIX', '');

// ┌─────────────────────────────────────────────────────────────────────────────
// │ 2. SITE-METADATA
// └─────────────────────────────────────────────────────────────────────────────
define('SITE_NAME',        'My Website');
define('SITE_DESCRIPTION', 'Short description of the website for search engines and sharing.');
define('SITE_LANGUAGE',       'en');
define('SITE_OG_LOCALE',   'en_GB');
define('SITE_AUTHOR',   'Your Name');
define('SITE_BASE_URL',    'https://www.example.com');
define('SITE_OG_IMAGE',  SITE_BASE_URL . '/assets/og-default.jpg');

// ┌─────────────────────────────────────────────────────────────────────────────
// │ 3. UPLOAD / GALLERI
// └─────────────────────────────────────────────────────────────────────────────
define('UPLOAD_DIR',    __DIR__ . '/uploads/gallery/');
define('UPLOAD_URL',    '/uploads/gallery/');   // Rod-relativ URL
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024);       // 5 MB

// ┌─────────────────────────────────────────────────────────────────────────────
// │ 4. ADMIN / AUTH
// └─────────────────────────────────────────────────────────────────────────────
define('ADMIN_USER',   'admin');
define('ADMIN_PASS_HASH', password_hash('change_this_password', PASSWORD_BCRYPT));
define('SESSION_NAME', 'cms_admin_session');

// ┌─────────────────────────────────────────────────────────────────────────────
// │ 5. NYHEDER
// └─────────────────────────────────────────────────────────────────────────────
define('ARTICLES_PER_PAGE', 5);

// ┌─────────────────────────────────────────────────────────────────────────────
// │ 6. NAVIGATION
// │
// │ Hvert menupunkt er et array med:
// │   'label' => Teksten der vises
// │   'href'  => URL (relativ eller absolut)
// │   'match' => Streng der matches mod den aktuelle fil for at markere aktiv
// │
// │ Tilføj, fjern eller omarranger frit.
// └─────────────────────────────────────────────────────────────────────────────
define('SITE_NAV', [
    ['label' => 'Home',  'href' => '/index.php',   'match' => 'index'],
    ['label' => 'News',  'href' => '/news.php', 'match' => 'news'],
    ['label' => 'Gallery',  'href' => '/gallery.php', 'match' => 'gallery'],
]);

// ┌─────────────────────────────────────────────────────────────────────────────
// │ 7. HJÆLPEFUNKTIONER
// └─────────────────────────────────────────────────────────────────────────────

/**
 * Shared database singleton.
 */
function db(): mysqli {
    static $conn = null;
    if ($conn !== null) return $conn;
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('DB connection error: ' . $conn->connect_error);
        die('A technical error occurred. Please try again later.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

/**
 * Safe HTML escaping shorthand.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return a safely escaped table name with optional prefix.
 */
function table_name(string $base): string {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $base)) {
        throw new InvalidArgumentException('Invalid base table name.');
    }
    if (TABLE_PREFIX !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', TABLE_PREFIX)) {
        throw new RuntimeException('TABLE_PREFIX in config.php contains invalid characters.');
    }

    $full = TABLE_PREFIX . $base;
    return '`' . str_replace('`', '``', $full) . '`';
}

/**
 * Determine whether a nav item matches the current path.
 */
function nav_is_active(string $match): bool {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    return str_contains($path, $match);
}

/**
 * Return the canonical URL for the current page.
 */
function canonical_url(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $query = $_SERVER['QUERY_STRING'] ?? '';
    return SITE_BASE_URL . $path . ($query ? '?' . $query : '');
}
