<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';

$pdo = getDB();

// BUG-SESI-1 FIX: penyewa hanya lihat kamar miliknya sendiri
if (!empty($_SESSION['user_id']) && $_SESSION['user_role'] === 'penyewa') {
    $stmt = $pdo->prepare("
        SELECT k.* FROM kamar k
        JOIN hunian h ON k.id = h.kamar_id
        WHERE h.user_id = ? AND h.status = 'aktif'
        ORDER BY k.nomor
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $kamarList = $stmt->fetchAll();
} else {
    // Admin atau belum login → tampilkan semua kamar (BUG-KAMAR-1 sudah fix)
    $kamarList = $pdo->query("SELECT * FROM kamar ORDER BY nomor")->fetchAll();
}

$pageTitle = 'Daftar Kamar — KosKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] ?>"><?= htmlspecialchars($_SESSION['flash']['msg']) ?></div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="page-header">
        <h1>🏠 Daftar Kamar Kos</h1>
        <?php if (!empty($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="admin_kamar.php" class="btn btn-primary">Kelola Kamar</a>
        <?php endif; ?>
    </div>

    <div class="kamar-grid">
    <?php foreach ($kamarList as $k): ?>
        <div class="kamar-card <?= $k['status'] ?>">
            <div class="kamar-nomor">Kamar <?= htmlspecialchars($k['nomor']) ?></div>
            <div class="kamar-tipe"><?= htmlspecialchars($k['tipe']) ?></div>
            <div class="kamar-harga">Rp <?= number_format($k['harga_bulan'], 0, ',', '.') ?>/bulan</div>
            <div class="kamar-fasilitas">📋 <?= htmlspecialchars($k['fasilitas'] ?? '-') ?></div>
            <span class="badge <?= $k['status'] === 'terisi' ? 'badge-danger' : 'badge-success' ?>">
                <?= $k['status'] === 'terisi' ? 'Terisi' : 'Tersedia' ?>
            </span>
        </div>
    <?php endforeach; ?>
    </div>
</div>
</body>
</html>