<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/init.php';
initDatabase(getDB());
require_once __DIR__ . '/php/auth.php';
requireLogin();

$pdo = getDB();
$stmt = $pdo->prepare("SELECT p.*, k.nomor AS kamar_nomor, k.tipe FROM pembayaran p
    JOIN hunian h ON p.hunian_id = h.id JOIN kamar k ON h.kamar_id = k.id
    WHERE h.user_id = ? ORDER BY p.bulan DESC");
$stmt->execute([$_SESSION['user_id']]);
$pembayaranList = $stmt->fetchAll();

$pageTitle = 'Pembayaran Saya — KosKu';
include __DIR__ . '/php/header.php';
?>
<div class="container">
    <div class="page-header"><h1>💳 Pembayaran Saya</h1></div>
    <?php if (empty($pembayaranList)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:2rem;">Tidak ada data pembayaran.</div></div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>Kamar</th><th>Bulan</th><th>Jumlah</th><th>Status</th><th>Tgl Bayar</th></tr></thead>
                <tbody>
                <?php foreach ($pembayaranList as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['kamar_nomor']) ?> (<?= htmlspecialchars($p['tipe']) ?>)</td>
                    <td><?= htmlspecialchars($p['bulan']) ?></td>
                    <td>Rp <?= number_format($p['jumlah'],0,',','.') ?></td>
                    <td><span class="badge <?= $p['status'] === 'lunas' ? 'badge-success' : 'badge-warning' ?>"><?= $p['status'] === 'lunas' ? 'Lunas' : 'Belum Bayar' ?></span></td>
                    <td><?= $p['tanggal_bayar'] ?? '-' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
