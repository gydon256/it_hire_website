<?php
// This file is included at the top of every page
// $pageTitle   = set before including this file
// $activePage  = 'home' | 'requests' | 'register' | 'login' | 'dashboard'
// $depth       = '' (root) | '../' (1 level deep) | '../../' (2 levels deep)

$depth = $depth ?? '';
if (session_status() === PHP_SESSION_NONE) session_start();

$isLoggedIn   = isset($_SESSION['user_type']);
$userType     = $_SESSION['user_type']     ?? '';
$userName     = $_SESSION['user_name']     ?? '';
$userInitials = $_SESSION['user_initials'] ?? '?';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($pageTitle ?? 'IT Hire') ?> — IT Hire</title>
    <link rel="stylesheet" href="<?= $depth ?>assets/style.css"/>
</head>
<body>

<nav class="navbar">
    <a href="<?= $depth ?>index.php" class="nav-logo">IT<span>Hire</span></a>

    <div class="nav-links">
        <?php if (!$isLoggedIn): ?>
            <a href="<?= $depth ?>index.php"                    <?= ($activePage??'')==='home'     ? 'class="active"' : '' ?>>Home</a>
            <a href="<?= $depth ?>provider/view_requests.php"  <?= ($activePage??'')==='requests' ? 'class="active"' : '' ?>>Browse Requests</a>
            <a href="<?= $depth ?>login.html"                   <?= ($activePage??'')==='login'    ? 'class="active"' : '' ?>>Login</a>
            <a href="<?= $depth ?>client/register.html"          class="nav-btn">Post Request</a>

        <?php elseif ($userType === 'client'): ?>
            <a href="<?= $depth ?>client/dashboard.php"         <?= ($activePage??'')==='dashboard'  ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="<?= $depth ?>client/post_request.html"      <?= ($activePage??'')==='post_request' ? 'class="active"' : '' ?>>Post Request</a>
            <a href="<?= $depth ?>client/view_applications.php" <?= ($activePage??'')==='applications' ? 'class="active"' : '' ?>>Applications</a>
            <a href="<?= $depth ?>payment/payment.php"          <?= ($activePage??'')==='payment'    ? 'class="active"' : '' ?>>Payments</a>
            <div class="nav-user">
                <div class="nav-avatar"><?= htmlspecialchars($userInitials) ?></div>
                <span><?= htmlspecialchars($userName) ?></span>
            </div>
            <a href="<?= $depth ?>logout.php" class="nav-btn">Logout</a>

        <?php elseif ($userType === 'provider'): ?>
            <a href="<?= $depth ?>provider/dashboard.php"        <?= ($activePage??'')==='dashboard'  ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="<?= $depth ?>provider/view_requests.php"  <?= ($activePage??'')==='requests'   ? 'class="active"' : '' ?>>Browse Requests</a>
            <a href="<?= $depth ?>provider/add_qualification.html" <?= ($activePage??'')==='qualification' ? 'class="active"' : '' ?>>My Qualifications</a>
            <div class="nav-user">
                <div class="nav-avatar"><?= htmlspecialchars($userInitials) ?></div>
                <span><?= htmlspecialchars($userName) ?></span>
            </div>
            <a href="<?= $depth ?>logout.php" class="nav-btn">Logout</a>

        <?php elseif ($userType === 'admin'): ?>
            <a href="<?= $depth ?>admin/dashboard.php"           <?= ($activePage??'')==='dashboard' ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="<?= $depth ?>admin/verification.php"        <?= ($activePage??'')==='verification' ? 'class="active"' : '' ?>>Verifications</a>
            <a href="<?= $depth ?>admin/payments.php"           <?= ($activePage??'')==='payments' ? 'class="active"' : '' ?>>Payments</a>
            <a href="<?= $depth ?>admin/requests.php"            <?= ($activePage??'')==='requests' ? 'class="active"' : '' ?>>Requests</a>
            <div class="nav-user">
                <div class="nav-avatar"><?= htmlspecialchars($userInitials) ?></div>
                <span><?= htmlspecialchars($userName) ?> (Admin)</span>
            </div>
            <a href="<?= $depth ?>logout.php" class="nav-btn">Logout</a>
        <?php endif; ?>
    </div>
</nav>
