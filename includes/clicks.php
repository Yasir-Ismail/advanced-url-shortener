<?php
/**
 * Click-log service — append-only event logging & analytics queries.
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Record a click event.
 */
function log_click(int $linkId, string $ip, ?string $userAgent, ?string $referer = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO click_logs (link_id, ip_address, user_agent, referer, clicked_at)
         VALUES (:link_id, :ip, :ua, :ref, NOW())'
    );

    $stmt->execute([
        ':link_id' => $linkId,
        ':ip'      => $ip,
        ':ua'      => $userAgent,
        ':ref'     => $referer,
    ]);
}

/**
 * Recent clicks for a specific link.
 */
function recent_clicks(int $linkId, int $limit = 50): array
{
    $stmt = db()->prepare(
        'SELECT * FROM click_logs WHERE link_id = ? ORDER BY clicked_at DESC LIMIT ?'
    );
    $stmt->bindValue(1, $linkId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Last click timestamp for a link.
 */
function last_click_at(int $linkId): ?string
{
    $stmt = db()->prepare(
        'SELECT clicked_at FROM click_logs WHERE link_id = ? ORDER BY clicked_at DESC LIMIT 1'
    );
    $stmt->execute([$linkId]);
    $val = $stmt->fetchColumn();
    return $val ?: null;
}

/**
 * Click counts grouped by date for a link (for charts).
 */
function clicks_per_day(int $linkId, int $days = 30): array
{
    $stmt = db()->prepare(
        'SELECT DATE(clicked_at) AS day, COUNT(*) AS cnt
         FROM click_logs
         WHERE link_id = ? AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
         GROUP BY DATE(clicked_at)
         ORDER BY day ASC'
    );
    $stmt->bindValue(1, $linkId, PDO::PARAM_INT);
    $stmt->bindValue(2, $days, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Browser breakdown from user-agent strings for a link.
 * Simple regex-based detection — no external lib needed.
 */
function browser_breakdown(int $linkId): array
{
    $stmt = db()->prepare(
        'SELECT user_agent FROM click_logs WHERE link_id = ? AND user_agent IS NOT NULL'
    );
    $stmt->execute([$linkId]);
    $rows = $stmt->fetchAll();

    $browsers = [];

    foreach ($rows as $row) {
        $ua = $row['user_agent'];
        $browser = detect_browser($ua);
        $browsers[$browser] = ($browsers[$browser] ?? 0) + 1;
    }

    arsort($browsers);
    return $browsers;
}

/**
 * Unique IPs for a link.
 */
function unique_ips(int $linkId, int $limit = 100): array
{
    $stmt = db()->prepare(
        'SELECT ip_address, COUNT(*) AS hits, MAX(clicked_at) AS last_seen
         FROM click_logs
         WHERE link_id = ?
         GROUP BY ip_address
         ORDER BY hits DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $linkId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Detect browser from user-agent string (basic).
 */
function detect_browser(string $ua): string
{
    $patterns = [
        'Edge'    => '/Edg[e\/]/i',
        'Opera'   => '/OPR|Opera/i',
        'Chrome'  => '/Chrome/i',
        'Firefox' => '/Firefox/i',
        'Safari'  => '/Safari/i',
        'IE'      => '/MSIE|Trident/i',
    ];

    foreach ($patterns as $name => $pattern) {
        if (preg_match($pattern, $ua)) {
            return $name;
        }
    }

    return 'Other';
}

/**
 * Referrer breakdown for a link.
 */
function referer_breakdown(int $linkId): array
{
    $stmt = db()->prepare(
        'SELECT referer, COUNT(*) AS cnt
         FROM click_logs
         WHERE link_id = ? AND referer IS NOT NULL AND referer != \'\'
         GROUP BY referer
         ORDER BY cnt DESC
         LIMIT 20'
    );
    $stmt->execute([$linkId]);
    return $stmt->fetchAll();
}
