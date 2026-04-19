<?php
require_once __DIR__ . '/../config.php';

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// BUG 1: requireAdmin tidak memanggil requireLogin — halaman admin bisa diakses tanpa login
function requireAdmin(): void {
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}
