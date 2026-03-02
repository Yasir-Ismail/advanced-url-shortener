<?php
/**
 * API endpoint: Delete a link.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/links.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$linkId = (int) ($input['link_id'] ?? 0);

if ($linkId < 1) {
    json_response(['error' => 'Invalid link ID.'], 422);
}

$link = fetch_link_by_id($linkId);
if ($link === null) {
    json_response(['error' => 'Link not found.'], 404);
}

delete_link($linkId);
json_response(['success' => true]);
