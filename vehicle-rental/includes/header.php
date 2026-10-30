<?php
// Expects: $pageTitle (string), $base ('' for root pages, '../' for admin pages)
$base = $base ?? '';
$pageTitle = $pageTitle ?? APP_NAME;
$current = basename($_SERVER['PHP_SELF']);
$inAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
function nav_active($file, $current) { return $file === $current ? ' class="active"' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Vehicle Rental Management System - rent cars, bikes, SUVs and more.">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
    <nav class="navbar container">
        <a href="<?= $base ?>index.php" class="logo">🚘 <span><?= e(APP_NAME) ?></span></a>
        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <ul class="nav-links">
            <?php if ($inAdmin): ?>
                <li><a href="index.php"<?= nav_active('index.php', $current) ?>>Dashboard</a></li>
                <li><a href="vehicles.php"<?= nav_active('vehicles.php', $current) ?>>Vehicles</a></li>
                <li><a href="bookings.php"<?= nav_active('bookings.php', $current) ?>>Bookings</a></li>
                <li><a href="users.php"<?= nav_active('users.php', $current) ?>>Users</a></li>
                <li><a href="../index.php">View Site</a></li>
            <?php else: ?>
                <li><a href="index.php"<?= nav_active('index.php', $current) ?>>Home</a></li>
                <li><a href="vehicles.php"<?= nav_active('vehicles.php', $current) ?>>Vehicles</a></li>
                <li><a href="contact.php"<?= nav_active('contact.php', $current) ?>>Contact</a></li>
                <?php if (is_logged_in()): ?>
                    <li><a href="my_bookings.php"<?= nav_active('my_bookings.php', $current) ?>>My Bookings</a></li>
                    <li><a href="profile.php"<?= nav_active('profile.php', $current) ?>>Profile</a></li>
                    <?php if (is_admin()): ?><li><a href="admin/index.php" class="btn btn-sm btn-accent">Admin Panel</a></li><?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (is_logged_in()): ?>
                <li class="nav-user">Hi, <?= e(explode(' ', current_user()['name'])[0]) ?></li>
                <li><a href="<?= $base ?>logout.php" class="btn btn-sm btn-outline">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php"<?= nav_active('login.php', $current) ?>>Login</a></li>
                <li><a href="register.php" class="btn btn-sm btn-primary">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main class="container main-content">
<?php show_flash(); ?>
