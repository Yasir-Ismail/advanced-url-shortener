<?php
/**
 * API endpoint: Create a new short link.
 * Accepts JSON POST for AJAX usage.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/links.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// Accept both form data and JSON body
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$url       = trim($input['url'] ?? '');
$expiresAt = trim($input['expires_at'] ?? '') ?: null;
$maxClicks = trim($input['max_clicks'] ?? '') ?: null;

if ($url === '' || !is_valid_url($url)) {
    json_response(['error' => 'A valid HTTP/HTTPS URL is required.'], 422);
}

if ($expiresAt !== null) {
    $ts = strtotime($expiresAt);
    if ($ts === false || $ts <= time()) {
        json_response(['error' => 'Expiry date must be in the future.'], 422);
    }
    $expiresAt = date('Y-m-d H:i:s', $ts);
}

if ($maxClicks !== null) {
    $maxClicks = (int) $maxClicks;
    if ($maxClicks < 1) {
        json_response(['error' => 'Max clicks must be at least 1.'], 422);
    }
}

try {
    $link = create_link($url, $expiresAt, $maxClicks);
    json_response([
        'success'   => true,
        'short_url' => short_url($link['short_code']),
        'short_code'=> $link['short_code'],
        'link'      => $link,
    ], 201);
} catch (\Exception $e) {
    json_response(['error' => 'Failed to create link.'], 500);
}
