<?php require_once __DIR__ . '/../php/auth.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'KosKu') ?></title>
    <link rel="stylesheet" href="<?= $cssPath ?? 'css/style.css' ?>">
</head>
<body>
<nav class="navbar">
    <a href="index.php" class="brand">🏠 KosKu</a>
    <nav>
        <a href="index.php">Kamar</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin_kamar.php">Kelola Kamar</a>
                <a href="admin_hunian.php">Hunian</a>
                <a href="admin_pembayaran.php">Pembayaran</a>
            <?php else: ?>
                <a href="pembayaran.php">Pembayaran Saya</a>
            <?php endif; ?>
            <a href="logout.php">Keluar (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
        <?php else: ?>
            <a href="login.php">Masuk</a>
        <?php endif; ?>
    </nav>
</nav>
