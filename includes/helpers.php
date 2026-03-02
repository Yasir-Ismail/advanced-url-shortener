<?php
/**
 * Shared helpers: sanitization, validation, response utilities.
 */

/**
 * Sanitize a string for HTML output (XSS prevention).
 */
function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validate a URL strictly.
 */
function is_valid_url(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    return in_array(strtolower($scheme), ['http', 'https'], true);
}

/**
 * Get the visitor's IP address (handles proxies conservatively).
 */
function visitor_ip(): string
{
    // In production, only trust X-Forwarded-For behind a known reverse proxy.
    // For XAMPP/local dev, REMOTE_ADDR is fine.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Get the visitor's user agent.
 */
function visitor_ua(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Get the HTTP referer.
 */
function visitor_referer(): string
{
    return $_SERVER['HTTP_REFERER'] ?? '';
}

/**
 * Build the full short URL for display.
 */
function short_url(string $code): string
{
    return rtrim(BASE_URL, '/') . '/' . $code;
}

/**
 * Send a JSON response and exit.
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Redirect with proper status and exit.
 */
function redirect(string $url, int $status = 302): void
{
    http_response_code($status);
    header("Location: {$url}");
    exit;
}

/**
 * Format a datetime string for display.
 */
function format_dt(?string $dt): string
{
    if ($dt === null) {
        return '—';
    }
    return date('M j, Y g:i A', strtotime($dt));
}

/**
 * Relative time (e.g. "3 hours ago").
 */
function time_ago(?string $dt): string
{
    if ($dt === null) {
        return 'Never';
    }

    $diff = time() - strtotime($dt);

    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return intdiv($diff, 60) . 'm ago';
    if ($diff < 86400) return intdiv($diff, 3600) . 'h ago';
    if ($diff < 2592000) return intdiv($diff, 86400) . 'd ago';

    return format_dt($dt);
}

/**
 * CSRF token helpers.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(string $token): bool
{
    return hash_equals(csrf_token(), $token);
}
