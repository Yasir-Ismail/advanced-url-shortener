<?php
/**
 * Create Link page — the main landing page.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/links.php';

$pageTitle  = 'Shorten a URL';
$activePage = 'create';

$error   = '';
$success = null;

// ── Handle form POST ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrfToken)) {
        $error = 'Invalid session. Please reload and try again.';
    } else {
        $url       = trim($_POST['url'] ?? '');
        $expiresAt = trim($_POST['expires_at'] ?? '') ?: null;
        $maxClicks = trim($_POST['max_clicks'] ?? '') ?: null;

        // Validate URL
        if ($url === '') {
            $error = 'URL is required.';
        } elseif (!is_valid_url($url)) {
            $error = 'Please enter a valid HTTP or HTTPS URL.';
        }

        // Validate expiry date
        if (!$error && $expiresAt !== null) {
            $ts = strtotime($expiresAt);
            if ($ts === false || $ts <= time()) {
                $error = 'Expiry date must be in the future.';
            } else {
                $expiresAt = date('Y-m-d H:i:s', $ts);
            }
        }

        // Validate max clicks
        if (!$error && $maxClicks !== null) {
            $maxClicks = (int) $maxClicks;
            if ($maxClicks < 1) {
                $error = 'Max clicks must be at least 1.';
            }
        }

        if (!$error) {
            try {
                $success = create_link($url, $expiresAt, $maxClicks);
            } catch (\Exception $e) {
                $error = 'Failed to create link. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h1>Shorten a URL</h1>
    <p class="subtitle">Paste a long URL and get a trackable short link in seconds.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="result-card">
        <p class="result-label">Your short link is ready</p>
        <div class="result-url-row">
            <input type="text" class="result-url" id="shortUrl" value="<?= h(short_url($success['short_code'])) ?>" readonly>
            <button class="btn btn-primary btn-copy" onclick="copyUrl()">Copy</button>
        </div>
        <p class="result-meta">
            Points to: <a href="<?= h($success['original_url']) ?>" target="_blank" rel="noopener"><?= h(mb_strimwidth($success['original_url'], 0, 80, '…')) ?></a>
        </p>
        <?php if ($success['expires_at']): ?>
            <p class="result-meta">Expires: <?= h(format_dt($success['expires_at'])) ?></p>
        <?php endif; ?>
        <?php if ($success['max_clicks']): ?>
            <p class="result-meta">Max clicks: <?= (int)$success['max_clicks'] ?></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="POST" action="" class="create-form card">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="url">Destination URL</label>
        <input type="url" id="url" name="url" class="input" placeholder="https://example.com/very/long/path"
               value="<?= h($_POST['url'] ?? '') ?>" required autofocus>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="expires_at">Expiry Date <span class="optional">(optional)</span></label>
            <input type="datetime-local" id="expires_at" name="expires_at" class="input"
                   value="<?= h($_POST['expires_at'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="max_clicks">Max Clicks <span class="optional">(optional)</span></label>
            <input type="number" id="max_clicks" name="max_clicks" class="input" min="1" placeholder="Unlimited"
                   value="<?= h($_POST['max_clicks'] ?? '') ?>">
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">Shorten</button>
</form>

<script>
function copyUrl() {
    const input = document.getElementById('shortUrl');
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = 'Copy', 2000);
    });
}
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
