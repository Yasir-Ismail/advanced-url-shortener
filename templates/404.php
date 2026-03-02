<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
$pageTitle = '404 — Not Found';
include __DIR__ . '/header.php';
?>

<div class="error-page">
    <h1 class="error-code">404</h1>
    <p class="error-text">The short link you requested does not exist.</p>
    <a href="<?= BASE_URL ?>" class="btn btn-primary">Create a Link</a>
</div>

<?php include __DIR__ . '/footer.php'; ?>
