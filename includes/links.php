<?php
/**
 * Link service — CRUD operations on the `links` table.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/shortcode.php';

/**
 * Create a new shortened link.
 *
 * @param string      $originalUrl  Validated URL
 * @param string|null $expiresAt    Y-m-d H:i:s or null
 * @param int|null    $maxClicks    Positive int or null
 * @return array      The created link row
 */
function create_link(string $originalUrl, ?string $expiresAt = null, ?int $maxClicks = null): array
{
    $pdo  = db();
    $code = generate_unique_short_code();

    $stmt = $pdo->prepare(
        'INSERT INTO links (original_url, short_code, expires_at, max_clicks, created_at)
         VALUES (:url, :code, :expires, :max_clicks, NOW())'
    );

    $stmt->execute([
        ':url'        => $originalUrl,
        ':code'       => $code,
        ':expires'    => $expiresAt,
        ':max_clicks' => $maxClicks,
    ]);

    return fetch_link_by_code($code);
}

/**
 * Fetch a link row by short_code.
 *
 * @param string $code
 * @return array|null
 */
function fetch_link_by_code(string $code): ?array
{
    $stmt = db()->prepare('SELECT * FROM links WHERE short_code = ? LIMIT 1');
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Fetch a link row by ID.
 */
function fetch_link_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM links WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Determine effective status of a link.
 *
 * @return string  'active' | 'disabled' | 'expired'
 */
function link_status(array $link): string
{
    if (!$link['is_active']) {
        return 'disabled';
    }

    // Date-based expiry
    if ($link['expires_at'] !== null && strtotime($link['expires_at']) <= time()) {
        return 'expired';
    }

    // Click-limit expiry
    if ($link['max_clicks'] !== null && $link['click_count'] >= $link['max_clicks']) {
        return 'expired';
    }

    return 'active';
}

/**
 * Increment click_count atomically.
 */
function increment_click_count(int $linkId): void
{
    db()->prepare('UPDATE links SET click_count = click_count + 1 WHERE id = ?')
        ->execute([$linkId]);
}

/**
 * Toggle is_active flag.
 */
function toggle_link_active(int $linkId): void
{
    db()->prepare('UPDATE links SET is_active = NOT is_active WHERE id = ?')
        ->execute([$linkId]);
}

/**
 * Delete a link (cascade removes click_logs).
 */
function delete_link(int $linkId): void
{
    db()->prepare('DELETE FROM links WHERE id = ?')->execute([$linkId]);
}

/**
 * Fetch all links with ordering.
 */
function fetch_all_links(string $orderBy = 'created_at', string $dir = 'DESC'): array
{
    $allowed = ['created_at', 'click_count', 'short_code'];
    $orderBy = in_array($orderBy, $allowed, true) ? $orderBy : 'created_at';
    $dir     = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

    $stmt = db()->query("SELECT * FROM links ORDER BY {$orderBy} {$dir}");
    return $stmt->fetchAll();
}

/**
 * Count links by status (for dashboard stats).
 */
function count_links_by_status(): array
{
    $all = fetch_all_links();
    $stats = ['total' => 0, 'active' => 0, 'expired' => 0, 'disabled' => 0];

    foreach ($all as $link) {
        $stats['total']++;
        $status = link_status($link);
        $stats[$status]++;
    }

    return $stats;
}

/**
 * Total click count across all links.
 */
function total_clicks(): int
{
    return (int) db()->query('SELECT COALESCE(SUM(click_count), 0) FROM links')->fetchColumn();
}
