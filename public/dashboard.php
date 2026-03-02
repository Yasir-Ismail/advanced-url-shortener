<?php
/**
 * Analytics Dashboard — overall stats & per-link overview.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/links.php';
require_once __DIR__ . '/../includes/clicks.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$stats      = count_links_by_status();
$totalClks  = total_clicks();
$links      = fetch_all_links('created_at', 'DESC');

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p class="subtitle">Overview of all shortened links and analytics.</p>
</div>

<!-- ── Stat Cards ──────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= $stats['total'] ?></span>
        <span class="stat-label">Total Links</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= number_format($totalClks) ?></span>
        <span class="stat-label">Total Clicks</span>
    </div>
    <div class="stat-card accent-green">
        <span class="stat-value"><?= $stats['active'] ?></span>
        <span class="stat-label">Active</span>
    </div>
    <div class="stat-card accent-orange">
        <span class="stat-value"><?= $stats['expired'] ?></span>
        <span class="stat-label">Expired</span>
    </div>
    <div class="stat-card accent-red">
        <span class="stat-value"><?= $stats['disabled'] ?></span>
        <span class="stat-label">Disabled</span>
    </div>
</div>

<!-- ── Links Table ─────────────────────────────────────────── -->
<div class="card">
    <h2 class="card-title">All Links</h2>

    <?php if (empty($links)): ?>
        <p class="empty-state">No links created yet. <a href="<?= BASE_URL ?>">Create one →</a></p>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Short Code</th>
                        <th>Destination</th>
                        <th>Clicks</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Click</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                        <?php $st = link_status($link); ?>
                        <tr>
                            <td>
                                <a href="<?= h(short_url($link['short_code'])) ?>" class="code-link" target="_blank">
                                    <?= h($link['short_code']) ?>
                                </a>
                            </td>
                            <td class="url-cell" title="<?= h($link['original_url']) ?>">
                                <?= h(mb_strimwidth($link['original_url'], 0, 55, '…')) ?>
                            </td>
                            <td class="num-cell"><?= number_format($link['click_count']) ?></td>
                            <td>
                                <span class="badge badge-<?= $st ?>">
                                    <?= ucfirst($st) ?>
                                </span>
                            </td>
                            <td><?= h(format_dt($link['created_at'])) ?></td>
                            <td><?= h(time_ago(last_click_at($link['id']))) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/analytics?id=<?= $link['id'] ?>" class="btn btn-sm">Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
