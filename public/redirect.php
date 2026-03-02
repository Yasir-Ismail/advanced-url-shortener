<?php
/**
 * Redirect handler — performance-critical.
 *
 * Receives the short code, validates it, logs the click,
 * and issues a 301 redirect as fast as possible.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/links.php';
require_once __DIR__ . '/../includes/clicks.php';

$code = $_GET['c'] ?? '';

// ── Validate input ──────────────────────────────────────────
if ($code === '' || !preg_match('/^[a-zA-Z0-9]{1,16}$/', $code)) {
    http_response_code(404);
    include __DIR__ . '/../templates/404.php';
    exit;
}

// ── Look up the link ────────────────────────────────────────
$link = fetch_link_by_code($code);

if ($link === null) {
    http_response_code(404);
    include __DIR__ . '/../templates/404.php';
    exit;
}

// ── Check status ────────────────────────────────────────────
$status = link_status($link);

if ($status === 'disabled') {
    http_response_code(410);
    $errorTitle   = 'Link Disabled';
    $errorMessage = 'This short link has been disabled by its owner.';
    include __DIR__ . '/../templates/error.php';
    exit;
}

if ($status === 'expired') {
    http_response_code(410);
    $errorTitle   = 'Link Expired';
    $errorMessage = 'This short link has expired and is no longer available.';
    include __DIR__ . '/../templates/error.php';
    exit;
}

// ── Log click & increment (non-blocking philosophy) ─────────
// In a real production system we'd push to a queue.
// For XAMPP scope, we just keep queries lean.
increment_click_count($link['id']);
log_click(
    $link['id'],
    visitor_ip(),
    visitor_ua(),
    visitor_referer()
);

// ── Redirect (301 for SEO friendliness) ─────────────────────
redirect($link['original_url'], 301);
