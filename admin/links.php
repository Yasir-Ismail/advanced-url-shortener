<?php
/**
 * Admin — Manage Links (toggle active, delete).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/links.php';
require_once __DIR__ . '/../includes/clicks.php';

$pageTitle  = 'Manage Links';
$activePage = 'links';

$message = '';
$msgType = '';

// ── Handle toggle / delete actions ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $message = 'Invalid session.';
        $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $linkId = (int) ($_POST['link_id'] ?? 0);

        if ($linkId > 0) {
            switch ($action) {
                case 'toggle':
                    toggle_link_active($linkId);
                    $message = 'Link status toggled.';
                    $msgType = 'success';
                    break;

                case 'delete':
                    delete_link($linkId);
                    $message = 'Link deleted permanently.';
                    $msgType = 'success';
                    break;

                default:
                    $message = 'Unknown action.';
                    $msgType = 'error';
            }
        }
    }
}

$links = fetch_all_links('created_at', 'DESC');

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1>Manage Links</h1>
    <p class="subtitle">Toggle status, view details, or delete links.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?>"><?= h($message) ?></div>
<?php endif; ?>

<div class="card">
    <?php if (empty($links)): ?>
        <p class="empty-state">No links yet. <a href="<?= BASE_URL ?>">Create one →</a></p>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Short Code</th>
                        <th>Destination</th>
                        <th>Clicks</th>
                        <th>Max Clicks</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                                <?= h(mb_strimwidth($link['original_url'], 0, 50, '…')) ?>
                            </td>
                            <td class="num-cell"><?= number_format($link['click_count']) ?></td>
                            <td class="num-cell"><?= $link['max_clicks'] !== null ? number_format($link['max_clicks']) : '∞' ?></td>
                            <td><?= $link['expires_at'] ? h(format_dt($link['expires_at'])) : 'Never' ?></td>
                            <td>
                                <span class="badge badge-<?= $st ?>">
                                    <?= ucfirst($st) ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <form method="POST" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Toggle active/inactive">
                                        <?= $link['is_active'] ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>

                                <a href="<?= BASE_URL ?>admin/analytics?id=<?= $link['id'] ?>" class="btn btn-sm">Analytics</a>

                                <form method="POST" class="inline-form" onsubmit="return confirm('Delete this link and all its click data?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
