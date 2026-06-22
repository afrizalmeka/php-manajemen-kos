<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';

$pdo = getDB();

$isPenyewa     = !empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'penyewa';
$kamarSendiri  = null;

if ($isPenyewa) {
    $stmt = $pdo->prepare("SELECT h.*, k.nomor, k.tipe, k.harga_bulan, k.fasilitas, k.status
        FROM hunian h JOIN kamar k ON h.kamar_id = k.id
        WHERE h.user_id = ? AND h.status = 'aktif'
        LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $kamarSendiri = $stmt->fetch();
}

$kamarList = [];
$adaKosong = false;
if (!$kamarSendiri) {
    $kamarList = $pdo->query("SELECT * FROM kamar ORDER BY status, nomor")->fetchAll();
    foreach ($kamarList as $k) {
        if ($k['status'] === 'kosong') {
            $adaKosong = true;
            break;
        }
    }
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

    <?php if ($kamarSendiri): ?>
        <div class="alert alert-info">Anda sudah memiliki kamar aktif. Daftar kamar kosong tidak ditampilkan karena Anda saat ini sedang menyewa kamar di bawah ini.</div>

        <div class="kamar-grid">
            <div class="kamar-card terisi">
                <div class="kamar-nomor">Kamar <?= htmlspecialchars($kamarSendiri['nomor']) ?></div>
                <div class="kamar-tipe"><?= htmlspecialchars($kamarSendiri['tipe']) ?></div>
                <div class="kamar-harga">Rp <?= number_format($kamarSendiri['harga_bulan'], 0, ',', '.') ?>/bulan</div>
                <div class="kamar-fasilitas">📋 <?= htmlspecialchars($kamarSendiri['fasilitas'] ?? '-') ?></div>
                <div class="kamar-fasilitas">📅 Masuk sejak: <?= htmlspecialchars($kamarSendiri['tanggal_masuk']) ?></div>
                <span class="badge badge-success">Kamar Anda</span>
            </div>
        </div>

    <?php else: ?>

        <?php if (empty($kamarList)): ?>
            <div class="alert alert-secondary">Belum ada data kamar.</div>
        <?php elseif (!$adaKosong): ?>
            <div class="alert alert-secondary">Saat ini semua kamar sedang terisi, belum ada kamar kosong yang tersedia.</div>
        <?php endif; ?>

        <div class="kamar-grid">
        <?php foreach ($kamarList as $k): ?>
            <div class="kamar-card <?= htmlspecialchars($k['status']) ?>">
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

    <?php endif; ?>
</div>
</body>
</html>