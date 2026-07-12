<?php
/**
 * KuCL Meetup Project - bootstrap/config
 *
 * Loads .env, sets up error handling (display_errors OFF by design), and
 * exposes simple constants used by s3.php.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Errors are logged, never displayed to the browser.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('UTC');

function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
}

define('AWS_REGION', env('AWS_REGION', 'ap-south-1'));
define('AWS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID', ''));
define('AWS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY', ''));

define('S3_BUCKET', env('S3_BUCKET', ''));
define('S3_PREFIX', rtrim(env('S3_PREFIX', 'meetups/'), '/') . '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
