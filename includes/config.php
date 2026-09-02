<?php
/**
 * PJP Admin System — configuration
 * ---------------------------------
 * Ships configured to use SQLite by default: a single self-contained
 * database file, no MySQL database/user to create in cPanel first.
 * This is the easiest way to get the admin system running immediately.
 *
 * If you'd rather use a MySQL database (optional, not required):
 *   1. In cPanel, use "MySQL Database Wizard" to create a database,
 *      a database user, and add that user to the database with
 *      "All Privileges".
 *   2. Set DB_DRIVER to 'mysql' below.
 *   3. Put the real credentials in a file ONE LEVEL ABOVE public_html
 *      (e.g. /home/YOURCPANELUSER/pjp-secrets.php) rather than here —
 *      that location is never reachable by any URL, unlike anything
 *      inside public_html. See the bottom of this file for the format.
 *      That keeps real credentials out of the git repo entirely.
 */

// 'sqlite' (default, zero setup) or 'mysql'
define('DB_DRIVER', 'sqlite');

/**
 * REQUIRED BEFORE YOU CAN CREATE YOUR ADMIN ACCOUNT.
 * admin/setup.php (the one-time "create the first admin account" page)
 * refuses to work at all until you change this from its default value —
 * otherwise anyone who finds /admin/setup.php before you do could create
 * the account instead of you. Open this file in cPanel's File Editor,
 * change the value below to any random string only you know, save, then
 * visit:  https://pjp.co.id/admin/setup.php?key=THE-STRING-YOU-PICKED
 * Once your admin account exists, this key is never needed again.
 */
define('ADMIN_SETUP_KEY', 'change-me-now');

// Used to build absolute links in emails/notifications if ever needed.
define('SITE_URL', 'https://pjp.co.id');

// Change this if you ever need to invalidate all admin sessions at once.
define('SESSION_NAME', 'pjp_admin_session');

/**
 * --- Only used if DB_DRIVER is 'sqlite' (the default) ---
 * Stored ONE LEVEL ABOVE public_html by default (e.g.
 * /home/YOURCPANELUSER/pjp-data/pjp.sqlite) so the database file — which
 * holds every message and the admin password hash — is never reachable
 * by any URL at all, regardless of how .htaccess is configured. This is
 * stronger than relying on data/.htaccess alone, though that stays in
 * place too as a second layer.
 * If PHP can't write outside public_html on your host (rare on cPanel),
 * db.php automatically falls back to data/pjp.sqlite inside public_html.
 */
define('SQLITE_PATH', dirname(__DIR__, 2) . '/pjp-data/pjp.sqlite');
define('SQLITE_FALLBACK_PATH', __DIR__ . '/../data/pjp.sqlite');

/**
 * --- Only needed if DB_DRIVER is 'mysql' ---
 * Loaded from an optional file outside public_html if present, e.g.:
 *
 *   <?php
 *   // /home/YOURCPANELUSER/pjp-secrets.php  (NOT inside public_html)
 *   define('DB_HOST', 'localhost');
 *   define('DB_NAME', 'cpaneluser_pjp');
 *   define('DB_USER', 'cpaneluser_pjp');
 *   define('DB_PASS', 'the-real-password');
 *
 * Falls back to harmless placeholders if that file doesn't exist yet
 * (fine, since DB_DRIVER defaults to 'sqlite' and never reads these).
 */
$pjp_secrets = dirname(__DIR__, 2) . '/pjp-secrets.php';
if (file_exists($pjp_secrets)) {
    require $pjp_secrets;
}
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'your_cpanel_db_name');
    define('DB_USER', 'your_cpanel_db_user');
    define('DB_PASS', 'your_cpanel_db_password');
}

/**
 * --- Google Analytics 4 (optional) ---
 * Lets the admin dashboard show live GA4 stats (active users, views,
 * engaged sessions, event count, top pages) instead of you having to
 * open analytics.google.com separately. Leave GA4_PROPERTY_ID blank to
 * disable this — the admin pages hide the whole section if it's not
 * configured, so there's nothing to break by not setting this up.
 *
 * One-time setup (all on Google's side, not this codebase):
 *   1. https://console.cloud.google.com/ -> create or pick a project ->
 *      "APIs & Services" -> Library -> enable "Google Analytics Data API".
 *   2. Same project -> "APIs & Services" -> Credentials -> Create
 *      Credentials -> Service Account. Give it any name, no roles needed.
 *   3. Open that service account -> Keys -> Add Key -> Create new key ->
 *      JSON. This downloads a .json file to your computer.
 *   4. https://analytics.google.com/ -> Admin -> (your GA4 property) ->
 *      Property Access Management -> "+" -> add the service account's
 *      email (looks like xxxx@xxxx.iam.gserviceaccount.com, it's inside
 *      the downloaded JSON as "client_email") with the Viewer role.
 *   5. Still in GA4 Admin -> Property Settings -> copy the "PROPERTY ID"
 *      (a plain number like 123456789 — NOT the "G-XXXXXXX" measurement
 *      ID used in the site's tracking snippet).
 *   6. Upload the downloaded JSON file to your server ONE LEVEL ABOVE
 *      public_html (e.g. /home/YOURCPANELUSER/ga4-service-account.json)
 *      via cPanel File Manager — never inside public_html, since
 *      anything there is reachable by URL.
 *   7. Set GA4_PROPERTY_ID below to the number from step 5. The file
 *      path already points at the right place if you followed step 6.
 */
define('GA4_PROPERTY_ID', ''); // e.g. '123456789' — blank disables the feature
define('GA4_SERVICE_ACCOUNT_PATH', dirname(__DIR__, 2) . '/ga4-service-account.json');
