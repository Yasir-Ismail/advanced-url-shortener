<?php
/**
 * HTML layout header.
 *
 * Variables expected:
 *   $pageTitle  (string)
 *   $activePage (string, optional)
 */

$pageTitle  = $pageTitle  ?? 'URL Shortener';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> — Short</title>
    <link rel="stylesheet" href="<?= rtrim(BASE_URL, '/') ?>/assets/css/style.css">
</head>
<body>

<nav class="nav">
    <div class="nav-inner container">
        <a href="<?= BASE_URL ?>" class="nav-brand">
            <span class="brand-icon">⚡</span> Short
        </a>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>" class="nav-link <?= $activePage === 'create' ? 'active' : '' ?>">Create</a>
            <a href="<?= BASE_URL ?>dashboard" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="<?= BASE_URL ?>admin/links" class="nav-link <?= $activePage === 'links' ? 'active' : '' ?>">Manage</a>
        </div>
    </div>
</nav>

<main class="container main">
