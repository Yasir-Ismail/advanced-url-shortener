<?php
/**
 * Per-link analytics page — deep dive into a single link's performance.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/links.php';
require_once __DIR__ . '/../includes/clicks.php';

$linkId = (int) ($_GET['id'] ?? 0);
$link   = $linkId > 0 ? fetch_link_by_id($linkId) : null;

if ($link === null) {
    http_response_code(404);
    $errorTitle   = 'Link Not Found';
    $errorMessage = 'The link you are looking for does not exist.';
    include __DIR__ . '/../templates/error.php';
    exit;
}

$pageTitle  = 'Analytics — ' . $link['short_code'];
$activePage = 'links';

$status       = link_status($link);
$lastClick    = last_click_at($link['id']);
$recentClicks = recent_clicks($link['id'], 50);
$browsers     = browser_breakdown($link['id']);
$ips          = unique_ips($link['id'], 30);
$dailyClicks  = clicks_per_day($link['id'], 30);

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>Link Analytics</h1>
            <p class="subtitle">
                <a href="<?= h(short_url($link['short_code'])) ?>" target="_blank" class="code-link"><?= h($link['short_code']) ?></a>
                → <a href="<?= h($link['original_url']) ?>" target="_blank" rel="noopener"><?= h(mb_strimwidth($link['original_url'], 0, 70, '…')) ?></a>
            </p>
        </div>
        <span class="badge badge-<?= $status ?> badge-lg"><?= ucfirst($status) ?></span>
    </div>
</div>

<!-- ── Summary Cards ──────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= number_format($link['click_count']) ?></span>
        <span class="stat-label">Total Clicks</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= count($ips) ?></span>
        <span class="stat-label">Unique IPs</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= h(time_ago($lastClick)) ?></span>
        <span class="stat-label">Last Click</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= h(format_dt($link['created_at'])) ?></span>
        <span class="stat-label">Created</span>
    </div>
</div>

<!-- ── Link Meta ──────────────────────────────────────────── -->
<div class="card meta-card">
    <h2 class="card-title">Link Details</h2>
    <dl class="meta-grid">
        <dt>Original URL</dt>
        <dd><a href="<?= h($link['original_url']) ?>" target="_blank" rel="noopener"><?= h($link['original_url']) ?></a></dd>

        <dt>Short URL</dt>
        <dd>
            <span id="shortUrlText"><?= h(short_url($link['short_code'])) ?></span>
            <button class="btn btn-sm btn-copy-inline" onclick="copyText('shortUrlText')">Copy</button>
        </dd>

        <dt>Status</dt>
        <dd><span class="badge badge-<?= $status ?>"><?= ucfirst($status) ?></span></dd>

        <dt>Expires</dt>
        <dd><?= $link['expires_at'] ? h(format_dt($link['expires_at'])) : 'Never' ?></dd>

        <dt>Max Clicks</dt>
        <dd><?= $link['max_clicks'] !== null ? number_format($link['max_clicks']) : 'Unlimited' ?></dd>
    </dl>
</div>

<!-- ── Clicks Per Day Chart (simple CSS bar chart) ─────────── -->
<?php if (!empty($dailyClicks)): ?>
<div class="card">
    <h2 class="card-title">Clicks Per Day <span class="text-muted">(last 30 days)</span></h2>
    <?php
        $maxDay = max(array_column($dailyClicks, 'cnt'));
    ?>
    <div class="bar-chart">
        <?php foreach ($dailyClicks as $dc): ?>
            <div class="bar-row">
                <span class="bar-label"><?= date('M j', strtotime($dc['day'])) ?></span>
                <div class="bar-track">
                    <div class="bar-fill" style="width: <?= $maxDay > 0 ? round($dc['cnt'] / $maxDay * 100) : 0 ?>%"></div>
                </div>
                <span class="bar-value"><?= $dc['cnt'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Browser Breakdown ──────────────────────────────────── -->
<?php if (!empty($browsers)): ?>
<div class="card">
    <h2 class="card-title">Browser Breakdown</h2>
    <?php $totalBr = array_sum($browsers); ?>
    <div class="breakdown-list">
        <?php foreach ($browsers as $browser => $cnt): ?>
            <div class="breakdown-row">
                <span class="breakdown-label"><?= h($browser) ?></span>
                <div class="breakdown-bar-track">
                    <div class="breakdown-bar-fill" style="width: <?= round($cnt / $totalBr * 100) ?>%"></div>
                </div>
                <span class="breakdown-value"><?= $cnt ?> <span class="text-muted">(<?= round($cnt / $totalBr * 100, 1) ?>%)</span></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── IP Addresses ───────────────────────────────────────── -->
<?php if (!empty($ips)): ?>
<div class="card">
    <h2 class="card-title">Top IP Addresses</h2>
    <div class="table-wrapper">
        <table class="table table-compact">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th class="num-cell">Hits</th>
                    <th>Last Seen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ips as $ip): ?>
                    <tr>
                        <td><code><?= h($ip['ip_address']) ?></code></td>
                        <td class="num-cell"><?= $ip['hits'] ?></td>
                        <td><?= h(time_ago($ip['last_seen'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Recent Clicks Log ──────────────────────────────────── -->
<div class="card">
    <h2 class="card-title">Recent Clicks</h2>

    <?php if (empty($recentClicks)): ?>
        <p class="empty-state">No clicks recorded yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table table-compact">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>IP Address</th>
                        <th>Browser / User Agent</th>
                        <th>Referer</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentClicks as $click): ?>
                        <tr>
                            <td class="nowrap"><?= h(format_dt($click['clicked_at'])) ?></td>
                            <td><code><?= h($click['ip_address']) ?></code></td>
                            <td class="ua-cell" title="<?= h($click['user_agent'] ?? '') ?>">
                                <?= h(mb_strimwidth($click['user_agent'] ?? '—', 0, 70, '…')) ?>
                            </td>
                            <td class="url-cell"><?= $click['referer'] ? h(mb_strimwidth($click['referer'], 0, 40, '…')) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function copyText(id) {
    const text = document.getElementById(id).textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = 'Copy', 2000);
    });
}
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
