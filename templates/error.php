<?php
/**
 * Generic error page.
 * Expected variables: $errorTitle, $errorMessage
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pageTitle = $errorTitle ?? 'Error';
include __DIR__ . '/header.php';
?>

<div class="error-page">
    <h1 class="error-code"><?= h($errorTitle ?? 'Error') ?></h1>
    <p class="error-text"><?= h($errorMessage ?? 'Something went wrong.') ?></p>
    <a href="<?= BASE_URL ?>" class="btn btn-primary">Go Home</a>
</div>

<?php include __DIR__ . '/footer.php'; ?>
